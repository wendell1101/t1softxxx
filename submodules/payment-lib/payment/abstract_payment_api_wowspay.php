<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * wowspay
 *
 * * WOWSPAY_PAYMENT_API, ID: 6347
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_wowspay extends Abstract_payment_api {
    const METHOD               = 'POST';
    const API_PATH             = '/be/client/api/v1/collect';
    const MODE_BANKTRANSFER    = 'remit';
    const MODE_EWALLET         = 'remit_qr';
    const CURRENCY             = 'IDR';
    const CALLBACK_SUCCESS     = 'success';
    const REPONSE_CODE_SUCCESS = '0';
    const RETURN_SUCCESS_CODE  = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $this->CI->load->model(array('player_model'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'no lastName';

        $params = array();
        $params['userid']      = $playerId;
        $params['orderid']     = $order->secure_id;
        $params['currency']    = $this->getSystemInfo('currency', self::CURRENCY);
        $params['amount']      = $this->convertAmountToCurrency($amount);
        $params['notifyurl']   = $this->getNotifyUrl($orderId);
        $params['cardname']    = $lastname.' '.$firstname;
        $params['responseurl'] = $this->getReturnUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);

        $this->CI->utils->debug_log("=====================wowspay generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {

        $response = $this->processCurl($this->getSystemInfo('url'), $params);
        $this->CI->utils->debug_log('========================================wowspay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['data']['showurl']) && !empty($response['data']['showurl'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['showurl']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['message']) && !empty($response['message'])) {
                $msg = $response['message'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }

    protected function processCurl($url, $params) {

        $time = round(microtime(true) * 1000);
        $sign = $this->sign($params, self::API_PATH, $time);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        
        $header_array = array(
            "Accept: application/json",
            "Content-Type:application/json",
            "Authorization:".$sign
        );

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('=====================wowspay processCurl url', $url, 'header', $header_array, 'params', $params, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderid']);
        $response = json_decode($response, true);
        return $response;
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;
        
        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
        
        $this->CI->utils->debug_log("=====================wowspay callbackFrom $source params", $params);

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'userid', 'orderid', 'status', 'amount', 'type'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================wowspay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================wowspay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================wowspay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================wowspay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================wowspay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params, $api_path, $time) {
        $key = $this->getSystemInfo('key');
        $secret = $this->getSystemInfo('secret');

        ksort($params);
        $encodeStr = base64_encode(urldecode(http_build_query($params)));

        $msg = self::METHOD.':'.$api_path.':'.$time.':'.$encodeStr;
        $hmacMsg = hash_hmac('sha256', $msg, $secret, false);
        $signStr = 'Y3-HMAC-SHA256 ' . $key . ':' . $time . ':' . $hmacMsg;

        return $signStr;
    }

    private function validateSign($params) {

        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || !isset($_SERVER['REQUEST_URI'])) {
            $this->CI->utils->debug_log("=====================wowspay validateSign server authorization or request_uri not defind");
            return false;
        }

        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        $request_uri = $_SERVER['REQUEST_URI'];
        $time = explode(":", $authorization)[1];
        $signStr = $this->sign($params, $request_uri, $time);

        if($signStr == $authorization){
            return true;
        }else{
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount * 1000, 2, '.', '');
	}

}