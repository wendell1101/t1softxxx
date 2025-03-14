<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BTHPAY
 * http://office.bth.ph/login/
 *
 * * BTHPAY_PAYMENT_API, ID: 848
 * * BTHPAY_ALIPAY_PAYMENT_API, ID: 849
 * * BTHPAY_WEIXIN_PAYMENT_API, ID: 850
 * * BTHPAY_QQPAY_PAYMENT_API, ID: 851
 * * BTHPAY_UNIONPAY_PAYMENT_API, ID: 852
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_info: bthpay_sn_key
 *
 * Field Values:
 * * URL: http://apipay.bth.ph/pay/gateway
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra_info: { "bthpay_sn_key" : "## MERCHANT_SIGN_KEY_FOR_SN ##" }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_bthpay extends Abstract_payment_api {
    const PAY_TYPE_BANK = 'b2c';
    const PAY_TYPE_ALIPAY = 'alipay';
    const PAY_TYPE_WEIXIN = 'wechat';
    const PAY_TYPE_QQPAY = 'qq';
    const PAY_TYPE_UNIONPAY = 'unionpay';

    const CALLBACK_SUCCESS = 2;
    const RETURN_SUCCESS_CODE = 'succcess';


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

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        #for $submits['sn']
        $merchant_name = $this->getSystemInfo('account');
        $bthpay_sn_key = $this->getSystemInfo('bthpay_sn_key');
        $encrypted = openssl_encrypt($merchant_name, 'AES-128-CBC', hex2bin($bthpay_sn_key), OPENSSL_RAW_DATA);

        #for $submits['sp']
        $merchat_key = $this->getSystemInfo('key');
        $params = array();
        $params['merchant_name'] = $this->getSystemInfo('account');
        $params['merchant_order_id'] = $order->secure_id;
        $params['return_params'] = $order->secure_id;
        $params['input_charset'] = 'UTF-8';
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['trans_amount'] = $this->convertAmountToCurrency($amount);
        $params['timestamp'] = time();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $str = str_replace("\\/", "/", json_encode($params));
        $str = str_replace("\\/", "/", $str);

        $this->CI->utils->debug_log('=====================bthpay generatePaymentUrlForm sp params', $params);


        $submits['sn'] = strtoupper(bin2hex($encrypted));
        $submits['sp'] = $this->getResValue($str,$merchat_key);


        $this->CI->utils->debug_log('=====================bthpay generatePaymentUrlForm submits', $submits);
        return $this->processPaymentUrlForm($submits);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
        );
    }

    protected function processPaymentUrlFormQRCode($params) {}



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

        if($source == 'server' ){
            $this->CI->utils->debug_log("=====================bthpay sever callback params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outside_order_id'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
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

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'merchant_name', 'trans_amount', 'merchant_order_id', 'outside_order_id', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog('=====================bthpay checkCallbackOrder Missing parameter: [$f]', $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================bthpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog('======================bthpay checkCallbackOrder Status Failed', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['trans_amount'] != $check_amount) {
            $this->writePaymentErrorLog('======================bthpay checkCallbackOrder Payment amount is wrong, expected <= '. $check_amount, $fields);
            return false;
        }

        if ($fields['merchant_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog('======================bthpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]', $fields);
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

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'return_url' || $key == 'notify_url' || $key == 'sign') {
                continue;
            }
            $signStr .= "&$key=$value";
        }
        return $signStr.'&key='.$this->getSystemInfo('key');
    }


    /**
     * copied from demo_php/Security.php
     * @param  [str] $data
     * @param  [str] $key
     * @return [str]
     */
    public function getResValue($data, $key){
        $iv_size = @mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = @mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $res = '';
        $n = strlen($data)/16;
        for ($i=0; $i<=$n; $i++)
        {
            $start = $i*16;
            if($start > strlen($data)) $start = strlen($data);
            $str = substr($data,$start,16);
            $sp_encrypted = strtoupper(bin2hex(openssl_encrypt($str,'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA)));
            $res = $res.(substr($sp_encrypted,0,32));
        }
        return $res;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign){
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

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}