<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BAOPAY
 *
 * * BAOPAY_ALIPAY_PAYMENT_API, ID: 955
 * * BAOPAY_WEIXIN_PAYMENT_API, ID: 956
 * * BAOPAY_UNIONPAY_PAYMENT_API, ID: 5569
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.dygoog.com/order/add
 * * Account: ## User ID ##
 * * hash_key
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_baopay extends Abstract_payment_api {
    const TYPE_ALIPAY   = 1;
    const TYPE_WEIXIN   = 2;
    const TYPE_UNIONPAY = 3;

    const CALLBACK_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS_CODE = 'SUCCESS';


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
        $params['partner']    = $this->getSystemInfo('account');
        $params['money']      = $this->convertAmountToCurrency($amount);
        $params['oid']        = $order->secure_id;
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['notify_url'] = $this->getNotifyUrl($orderId);

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['token'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================baopay generatePaymentUrlForm params', $params);


        $info = json_decode($order->direct_pay_extra_info, true);
        $info['return_url'] = $params['return_url'];
        $info['notify_url'] = $params['notify_url'];
        $info = json_encode($info);
        $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($orderId, $info);

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

    public function getPlayerInputInfo() {
        $playerInputInfo = parent::getPlayerInputInfo();

        $playerInputInfo[] = array('name' => 'return_url', 'type' => 'hidden', 'label_lang' => 'cashier.return_url', 'value' => $emailAddr);
        $playerInputInfo[] = array('name' => 'notify_url', 'type' => 'hidden', 'label_lang' => 'cashier.return_url', 'value' => $contactNumber);

        return $playerInputInfo;
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

        $this->CI->utils->debug_log("=====================baopay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['oid'], null, null, null, $response_result_id);
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
            'tradeid', 'money', 'oid', 'state', 'token'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================baopay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================baopay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['state'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================baopay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['money'] != $check_amount) {
            $this->writePaymentErrorLog("======================baopay checkCallbackOrder Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['oid'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================baopay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function sign($params, $callback_params = false) {
        $signStr = $this->createSignStr($params, $callback_params);
        $sign = hash('sha1',$signStr);
        return $sign;
    }

    private function createSignStr($params, $callback_params) {
        if($callback_params){
            $keys = array('money', 'oid', 'partner');
        }else{
            $keys = array('money', 'oid', 'partner', 'notify_url', 'return_url');
        }

        $signStr = '';
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key].'|';
            }
        }
        $signStr .= $this->getSystemInfo('hash_key');
        return $signStr;
    }

    private function validateSign($params) {
        $callback_sign = $params['token'];
        unset($params['token']);
        $params['partner'] = $this->getSystemInfo('account');

        $sign = $this->sign($params, true);
        if($callback_sign == $sign){
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