<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ASIAPAY 亚付
 * *
 * * ASIAPAY_PAYMENT_API, ID: 891
 * * ASIAPAY_JDPAY_PAYMENT_API, ID: 892
 * * ASIAPAY_QUICKPAY_PAYMENT_API, ID: 893
 * * ASIAPAY_UNIONPAY_PAYMENT_API, ID: 894
 * * ASIAPAY_ALIPAY_PAYMENT_API, ID: 895
 * * ASIAPAY_ALIPAY_H5_PAYMENT_API, ID: 896
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.asiapaycenter.com/gateway.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_asiapay extends Abstract_payment_api {

    const TRADE_TYPE_ONLINEBANK =  2;
    const TRADE_TYPE_QUICKPAY   =  3;
    const TRADE_TYPE_JDPAY      =  5;
    const TRADE_TYPE_JDPAY_H5   = 25;
    const TRADE_TYPE_QQPAY      =  7;
    const TRADE_TYPE_QQPAY_H5   = 27;
    const TRADE_TYPE_WEIXIN     =  8;
    const TRADE_TYPE_WEIXIN_H5  = 28;
    const TRADE_TYPE_ALIPAY     =  9;
    const TRADE_TYPE_ALIPAY_H5  = 29;
    const TRADE_TYPE_UNIONPAY   = 10;

    const CALLBACK_SUCCESS = 'success';
    const RETURN_SUCCESS_CODE = 'success';


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
        $params['input_charset'] = 'UTF-8';
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['return_url'] = $this->getReturnUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['merchant_code'] = $this->getSystemInfo('account');
        $params['order_no'] = $order->secure_id;
        $params['order_amount'] = $this->convertAmountToCurrency($amount);
        $params['order_time'] = $orderDateTime->format('Y-m-d H:i:s');
        $params['product_name'] = lang('pay.deposit');
        $params['product_num'] = 1;
        $params['req_referer'] = $_SERVER['HTTP_HOST'];
        $params['customer_ip'] = $this->getClientIP();
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================asiapay generatePaymentUrlForm params', $params);

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

        $this->CI->utils->debug_log("=====================asiapay callbackFrom params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], null, null, null, $response_result_id);
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
            'merchant_code', 'notify_type', 'order_no', 'order_amount', 'order_time', 'trade_no', 'trade_time', 'trade_status','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================asiapay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================asiapay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['notify_type'] != 'back_notify') {
            $this->writePaymentErrorLog("======================asiapay checkCallbackOrder Payment notify_type is not back_notify", $fields);
            return false;
        }


        if ($fields['trade_status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================asiapay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['order_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================asiapay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================asiapay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            if(empty($value) || $key == 'sign') {
                continue;
            }
            else{
                $signStr .= "$key=$value&";
            }
        }
        return $signStr."key=".$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign){
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