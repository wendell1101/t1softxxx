<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ZPAY
 *
 * * ZPAY_ALIPAY_PAYMENT_API, ID: 5214
 * * ZPAY_ALIPAY_H5_PAYMENT_API, ID: 5215
 * * ZPAY_ALIPAY_PACKET_PAYMENT_API, ID: 5216
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.payba.top/
 * * Account: ## MerchantID ##
 * * Key: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_zpay extends Abstract_payment_api {

    const TYPE_WEIXIN = '1';
    const TYPE_ALIPAY = '2';

    const CALLBACK_SUCCESS = '0000';
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
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['uid']        = $this->getSystemInfo('account');
        $params['price']      = $this->convertAmountToCurrency($amount);
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['order_id']   = $order->secure_id;
        $params['order_uid']  = $player['username'];
        $params['order_name'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['key']        = $this->sign($params);
        $this->CI->utils->debug_log('=====================zpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
        );
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================zpay callbackFrom $source params", $params);

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
            $external_id = isset($params['transaction_id']) ? $params['transaction_id'] : null;
            $this->CI->sale_order->updateExternalInfo($order->id, $params['pay_num'], $external_id, null, null, $response_result_id);
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
            'pay_num', 'order_id', 'price', 'key', 'code'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================zpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================zpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['code'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================zpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['price'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================zpay checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================zpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
     
        return $sign;
    }

    private function createSignStr($params) {
        $params['token'] = $this->getSystemInfo('key');
        $keys = array('notify_url', 'order_id', 'order_name', 'order_uid', 'price', 'return_url', 'token', 'type', 'uid');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }
        return $signStr;
    }

    private function validateSign($params) {
        $params['token'] = $this->getSystemInfo('key');
        $keys = array('code', 'order_id', 'order_uid', 'pay_num', 'price', 'token', 'transaction_id');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }
        $sign = md5($signStr);
        if($params['key'] == $sign){
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