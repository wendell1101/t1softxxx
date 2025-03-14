<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * GTPAYNEW
 *
 *
 * * GTPAYNEW_PAYMENT_API, ID: 5878
 * * GTPAYNEW_PAYTM_PAYMENT_API, ID: 5879
 * * GTPAYNEW_PHONEPE_PAYMENT_API, ID: 5880
 * * GTPAYNEW_UPI_PAYMENT_API, ID: 5881
 * *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://interface.grummy.com/api/pay/apply
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_gtpaynew extends Abstract_payment_api {
    const RESPONSE_SUCCESS    = 'success';
    const CALLBACK_SUCCESS = 'success';

    const PAYTYPE_CARD_TO_CARD  = '1';
    const PAYTYPE_PAYTM = '2';
    const PAYTYPE_PHONEPE = '3';
    const PAYTYPE_UPI = '4';

    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'failed';
    const RETURN_SUCCESS_MSG = '支付成功';
    const RETURN_FAILED_MSG = '支付失败';
    const METHOD = 'AES-256-ECB';


    public function __construct($params = null) {
        parent::__construct($params);
        // $this->_custom_curl_header = ['Content-Type: application/x-www-form-urlencoded'];
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
        $params = array();
        $params['platformno']        = $this->getSystemInfo('account');
        $fields['payAmount']         = strval($this->convertAmountToCurrency($amount));
        $fields['commercialOrderNo'] = $order->secure_id;
        $fields['callBackUrl']       = $this->getNotifyUrl($orderId);
        $fields['notifyUrl']         = $this->getReturnUrl($orderId);
        $json_fields = json_encode($fields);
        $params['parameter']         = strval($this->encrypt($json_fields,$this->getSystemInfo('key')));
        $params['sign']              = $this->sign($json_fields);
        $params['commercialOrderNo'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);

        $this->CI->utils->debug_log('=====================gtpaynew generatePaymentUrlForm params', $params,'json_fields',$json_fields);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $orderId = $params['commercialOrderNo'];
        unset($params['commercialOrderNo']);
        $this->CI->utils->debug_log('=====================gtpaynew processPaymentUrlFormRedirect orderId', $orderId);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $orderId);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================gtpaynew processPaymentUrlFormRedirect response', $response);

        if(isset($response['result']) && $response['result'] == self::RESPONSE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['payUrl']
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['result'].': '.$response['msg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
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

        $this->CI->utils->debug_log("=====================gtpaynew callbackFrom $source params", $params);


        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================gtpaynew json_decode params", $params);
        }

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        $decrypt_callback_parameter = $this->decrypt($params['parameter'],$this->getSystemInfo('key'));
        $decrypt_callback_parameter = json_decode($decrypt_callback_parameter,true);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $decrypt_callback_parameter['commercialOrderNo'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if ($decrypt_callback_parameter['result'] == self::CALLBACK_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $resultContent=[
                'result'=> self::RETURN_SUCCESS_CODE,
                'message'=> self::RETURN_SUCCESS_MSG
            ];
            $result['message'] = json_encode($resultContent);
        } else {
            $resultContent=[
                'result'=> self::RETURN_FAILED_CODE,
                'message'=> self::RETURN_FAILED_MSG
            ];
            $result['return_error'] = json_encode($resultContent);
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'platformno', 'parameter', 'sign', 'commercialOrderNo'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================gtpaynew checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $decrypt_callback_parameter = $this->decrypt($fields['parameter'],$this->getSystemInfo('key'));
        $this->utils->debug_log("==================getting checkCallbackOrder decrypt_callback_parameter: ", $decrypt_callback_parameter,gettype($decrypt_callback_parameter));

        $encrypt_callback_parameter = $this->encrypt(json_encode($decrypt_callback_parameter),$this->getSystemInfo('key'));
        $this->utils->debug_log("==================getting checkCallbackOrder encrypt_callback_parameter: ", $encrypt_callback_parameter);

        # is signature authentic?
        if (!$this->validateSign(strval($decrypt_callback_parameter),$fields['sign'])) {
            $this->writePaymentErrorLog('=====================gtpaynew checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $decrypt_callback_parameter = json_decode($decrypt_callback_parameter,true);

        if ($decrypt_callback_parameter['result'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=======================gtpaynew checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($decrypt_callback_parameter['orderAmount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================gtpaynew checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($decrypt_callback_parameter['commercialOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================gtpaynew checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function sign($params) {
        // $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($params));
        return $sign;
    }

    private function validateSign($params,$callback_sign) {
        $sign = $this->sign($params);
        if($callback_sign == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        $use_https_with_callback_url = $this->getSystemInfo('use_https_with_callback_url');
        $notifyUrl = parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
        if($use_https_with_callback_url) {
            $notifyUrl = str_replace('http://', 'https://', $notifyUrl);
        }
        return $notifyUrl;
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function encrypt($params, $key){
        // if (mb_strlen($key, '8bit') !== 32) {
        //     throw new Exception("Needs a 256-bit key!");
        // }
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv     = openssl_random_pseudo_bytes($ivsize);
        $ciphertext = openssl_encrypt(
            $params,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return base64_encode($iv . $ciphertext);
    }

    public function decrypt($params, $key){
        // if (mb_strlen($key, '8bit') !== 32) {
        //     throw new Exception("Needs a 256-bit key!");
        // }
        $params    = base64_decode($params);
        $ivsize     = openssl_cipher_iv_length(self::METHOD);
        $iv         = mb_substr($params, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($params, $ivsize, null, '8bit');
        return openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}