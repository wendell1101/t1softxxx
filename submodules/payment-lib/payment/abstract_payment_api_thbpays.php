<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * THBPAYS
 *
 * * THBPAYS_PAYMENT_API, ID:
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://thbpays.com/api/fundin/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_thbpays extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 0;
    const RESULT_MSG_SUCCESS = 'success';

    const CALLBACK_SUCCESS = "Successful";
    const RETURN_SUCCESS_CODE = '{"received":"Yes"}';


    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order         = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
        $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';

        $params = array();
        $params['merchant_id']       = $this->getSystemInfo('account');
        $params['business_email']    = $this->getSystemInfo('business_email', 'helpdesk@smartbackend.com');
        $params['order_id']          = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['deposit_amount']    = $this->convertAmountToCurrency($amount);
        $params['currency']          = $this->getSystemInfo('currency','THB');
        $params['customer_name']     = $username;
        $params['customer_email']    = $email;
        $params['customer_phone_no'] = $phone;
        $params['customer_address']  = $address;
        $params['note']              = $order->secure_id;
        $params['website_url']       = $this->CI->utils->site_url_with_http();;
        $params['request_time']      = $orderDateTime->getTimestamp();
        $params['success_url']       = $this->getReturnUrl($orderId);
        $params['fail_url']          = $this->getReturnFailUrl($orderId);
        $params['callback_noti_url'] = $this->getNotifyUrl($orderId);
        $params['sign_data']         = $this->sign($params);
        $this->CI->utils->debug_log("=====================thbpays generatePaymentUrlForm params", $params);

        return $this->processPaymentUrlForm($params);
    }


    protected function processPaymentUrlFormPost($params) {
        $response = $this->process($params,$params['order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=====================thbpays processPaymentUrlFormPost response", $response);

        if($response['errCode'] == self::RESULT_CODE_SUCCESS && $response['status'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['redirect_url'],
            );
        }
        else if(isset($response['errCode'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => '['.$response['errCode'].']: '.$response['status']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidte API response')
            );
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================thbpays callbackFrom $source params", $params);

        if($source == 'server' ){
            $params = json_decode($params['requestParams'], true);
            $this->CI->utils->debug_log("=====================thbpays json_decode params", $params);

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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->CI->sale_order->updateExternalInfo($order->id, $params['transaction_id'], '', null, null, $response_result_id);
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
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
            'merchant_id', 'transaction_id', 'order_id', 'deposit_amount', 'order_status', 'sign_data'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================thbpays checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("======================thbpays checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['order_status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================thbpays checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['deposit_amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================thbpays checkCallbackOrder Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================thbpays checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'Bangkok Bank', 'value' => 'BBL'),
            array('label' => 'Kasikorn Bank', 'value' => 'KBANK'),
            array('label' => 'Krung Thai Bank', 'value' => 'KTB'),
            array('label' => 'Krungsri Bank', 'value' => 'BAY'),
            array('label' => 'Siam Commercial Bank', 'value' => 'SCB'),
            array('label' => 'Thai Military Bank', 'value' => 'TMB'),
        );
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = $this->getSystemInfo('key');
        foreach($params as $key => $value) {
            if($key == 'sign_data'){
                continue;
            }
            $signStr .= $value;
        }
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->getSystemInfo('key');
        $keys = array('merchant_id', 'order_id', 'deposit_method_id', 'currency');
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }

        $sign = strtoupper(md5($signStr));

        if($params['sign_data'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnFailUrl($orderId) {
        return parent::getCallbackUrl('/callback/show_error/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

    public function process($params, $orderSecureId) {
        $url           = $this->getSystemInfo('url');
        $api_user_name = $this->getSystemInfo('api_user_name');
        $api_password  = $this->getSystemInfo('api_password');
        $submit = array('requestParams' => json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); //request parameter

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $api_user_name . ':' . $api_password);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $submit);
        $this->setCurlProxyOptions($ch);
        $response    = curl_exec($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        curl_close($ch);
        $this->CI->utils->debug_log('url', $url, 'params', $submit , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        #save response result
        $response_result_id = $this->submitPreprocess($submit, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $orderSecureId);

        return $response;
    }

}