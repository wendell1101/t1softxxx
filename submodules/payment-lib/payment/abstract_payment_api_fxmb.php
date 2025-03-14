<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FXMB
 *
 * * FXMB_PAYMENT_API, ID: 5819
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://prod.fxmb.com/api/v1/external/payment/netbanking
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_fxmb extends Abstract_payment_api {
    const ORDER_STATUS_SUCCESS = "SUCCESS";
    const REQUEST_STATUS_SUCCESS = "SUCCESS";
    const REQUEST_STATUS_PENDING = "PENDING";
    const RETURN_SUCCESS_CODE = "SUCCESS";

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $player = $this->CI->player->getPlayerById($playerId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
        $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $city   = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))       ? $playerDetails[0]['city']       : 'no city';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';

        $params = array();
        $params['address'] = $address;
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['baseCurrency'] = $this->getSystemInfo('currency');
        $params['city'] = $city;
        $params['countryCode'] = 'IN';
        $params['email'] = $email;
        $params['firstName'] = $firstname;
        $params['ipAddress'] = $this->getClientIP();
        $params['lastName'] = $lastname;
        $params['mobile'] = $phone;
        $params['postcode'] = $this->getSystemInfo('postcode');
        $params['state'] = $this->getSystemInfo('state');
        $params['transactionId'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);


        $this->CI->utils->debug_log('=====================fxmb generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->processCurl($params);
        $this->CI->utils->debug_log('=====================fxmb processCurl decoded response', $response);

        if(isset($response['status']) && ($response['status'] == self::RETURN_SUCCESS_CODE || $response['status'] == self::REQUEST_STATUS_PENDING)) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['transactionId']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['transactionId']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['paymentUrl'],
            );
        }
        else if(isset($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['message']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    protected function processCurl($params) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $apikey = $this->getSystemInfo('key');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'apikey:'.$apikey)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);


        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'content', $content, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $content, $url, $content, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['transactionId']);


        $this->CI->utils->debug_log('=====================fxmb processCurl response', $content);

        $response = json_decode($content, true);

        return $response;
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================fxmb getOrderIdFromParameters flds', $flds);

        if(empty($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data ,true);
            $this->utils->debug_log('======fxmb getOrderIdFromParameters raw_post flds ' , $flds);
        }

        if(isset($flds['transactionId'])) {
            $order = $this->CI->sale_order->getSaleOrderByExternalOrderId($flds['transactionId']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================ambpayment getOrderIdFromParameters cannot get ref_no', $flds);
            return;
        }
    }

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

        $this->CI->utils->debug_log("=====================fxmb callbackFrom $source params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================fxmb raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================fxmb json_decode params", $params);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if (!empty($params)){
                    if ($params['status'] == self::ORDER_STATUS_SUCCESS) {
                        $this->CI->sale_order->updateExternalInfo($order->id, $params['transactionId'], '', null, null, $response_result_id);
                        $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                    } else {
                        $this->CI->utils->debug_log("=====================checkCallbackOrder Payment status is not success", $params);
                    }
                }
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'status','amount', 'currency', 'hash'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================fxmb Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================fxmb checkCallbackOrder signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================fxmb Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================fxmb Payment amount is wrong, expected <= ". $check_amount, $fields);
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
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || $key == 'userip') {
                continue;
            }
            $signStr .= "$value";
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($data) {
        ksort($data);
        $signStr = '';
        foreach ($data as $key => $value) {
            if($key == 'hash'){
                continue;
            }elseif($key == 'amount'){
                $value = number_format($value, 2, '.', '');
            }
            $signStr .= "$key=$value&";
        }
        $sign = $this->encrypt(rtrim($signStr, '&'));

        if($data['hash'] == $sign){
            return true;
        }
        else{
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

    public function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '') ;
    }

    public function fixKey($key) {

        if (strlen($key) < 16) {
            return str_pad("$key", 16, "0");
        }

        if (strlen($key) > 16) {
            //truncate to 16 bytes
            return substr($key, 0, 16);
        }

        return $key;
    }

    public function encrypt($data) {
        $key = $this->getSystemInfo('encryptKey');
        $salt = $this->getSystemInfo('salt');

        $encodedEncryptedData = base64_encode(openssl_encrypt($data, 'aes-128-cbc', $this->fixKey($key), OPENSSL_RAW_DATA, $salt));
        $encodedIV = base64_encode($salt);
        $encryptedPayload = $encodedEncryptedData.":".$encodedIV;

        return $encryptedPayload;
    }

}

