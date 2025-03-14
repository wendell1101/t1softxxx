<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KJFPAY 快捷付
 * *
 * * KJFPAY_PAYMENT_API, ID: 937
 * * KJFPAY_WEIXIN_PAYMENT_API, ID: 938
 * * KJFPAY_WEIXIN_H5_PAYMENT_API, ID: 939
 * * KJFPAY_UNIONPAY_PAYMENT_API, ID: 940
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://kjfpay.seepay.net/serviceDirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_kjfpay extends Abstract_payment_api {

    const PAYMETHOD_ONLINEBANK = 905; #905 ⽹银直连; 904 ⽹银跳转
    const PAYMETHOD_WEIXIN     = 900;
    const PAYMETHOD_WEIXIN_H5  = 901;
    const PAYMETHOD_ALIPAY     = 902;
    const PAYMETHOD_ALIPAY_H5  = 903;
    const PAYMETHOD_QQPAY      = 907;
    const PAYMETHOD_QQPAY_H5   = 909; #WAP 910
    const PAYMETHOD_JDPAY      = 908;
    const PAYMETHOD_JDPAY_H5   = 913;
    const PAYMETHOD_UNIONPAY   = 911;
    const PAYMETHOD_QUICKPAY   = 912;


    const CALLBACK_SUCCESS = '0000';
    const RETURN_SUCCESS_CODE = 'ok';


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

        $params = array();
        $params['service']      = 'directPay';
        $params['merchantId']   = $this->getSystemInfo("account");
        $params['notifyUrl']    = $this->getNotifyUrl($orderId);
        $params['returnUrl']    = $this->getReturnUrl($orderId);
        $params['signType']     = 'MD5';
        $params['inputCharset'] = 'UTF-8';
        $params['outOrderId']   = $order->secure_id;
        $params['subject']      = lang('pay.deposit');
        $params['body']         = lang('pay.deposit');
        $params['transAmt']     = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['channel']      = 'B2C';
        $params['cardAttr']     = '01';
        $params['attach']       = $order->secure_id;
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log('=====================kjfpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
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

        $this->CI->utils->debug_log("=====================kjfpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================kjfpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================kjfpay json_decode params", $params);
            }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['TransactionId'], null, null, null, $response_result_id);
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

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'MerchantId', 'OrderId', 'TransactionId', 'FaceValue', 'PayMoney', 'PayMethod', 'TransactionTime', 'ErrCode', 'Sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================kjfpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================kjfpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['ErrCode'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================kjfpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['PayMoney'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================kjfpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['OrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================kjfpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'signType' || $key == 'sign' || $key == 'Sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr, '&');
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['Sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}