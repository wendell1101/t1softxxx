<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * payone
 *
 * * PAYONE_PAYMENT_API, ID: 6311
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.payone1.com/br/payment.json
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_payone extends Abstract_payment_api {
    const COUNTRY_CODE         = 'BR';
    const CURRENCY_CODE        = 'BRL';
    const TYPE_URL             = '0101';
    const CALLBACK_SUCCESS     = '01';
    const REPONSE_CODE_SUCCESS = '000000';
    const RETURN_SUCCESS_CODE  = '000000';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
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

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname = (!empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'none';
        $lastname  = (!empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'none';
       
        $params = array();
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['appId']           = $this->getSystemInfo("appId");
        $params['backUrl']         = $this->getNotifyUrl($orderId);
        $params['countryCode']     = $this->getSystemInfo('country', self::COUNTRY_CODE);
        $params['currencyCode']    = $this->getSystemInfo('currency', self::CURRENCY_CODE);
        $params['merchantOrderId'] = $order->secure_id;
        $params['remark']          = "deposit";
        $params['custId']          = $this->getSystemInfo("account");
        $params['type']            = self::TYPE_URL;
        $params['userName']        = $lastname.' '.$firstname;
        $params['sign']            = $this->sign($params);

        $this->CI->utils->debug_log("=====================payone  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merchantOrderId']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================payone processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['payContent']) && !empty($response['payContent'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['payContent']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['msg']) && !empty($response['msg'])) {
                $msg = $response['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
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
        $this->CI->utils->debug_log("=====================payone raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("========================payone parse_str params", $params); 
        
        $this->CI->utils->debug_log("=====================payone callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderId'], '', null, null, $response_result_id);
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
            'appId', 'custId', 'merchantOrderId', 'orderStatus', 'amount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================payone checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================payone checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['orderStatus'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================payone checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================payone Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================payone checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $signature = $params['sign'];
        unset($params['sign']);
        $sign = $this->sign($params);
        if ( $signature == $sign ) {
            return true;
        } else {
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

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return floatval(number_format($amount * 100, 2, '.', ''));
    }
}