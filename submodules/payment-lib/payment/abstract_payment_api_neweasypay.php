<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * NEWEASYPAY
 *
 * * NEWEASYPAY_PAYMENT_API, ID: 5995
 * * NEWEASYPAY_WITHDRAWAL_PAYMENT_API, ID: 5996
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.easypay999.com/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_neweasypay extends Abstract_payment_api {
    const CHANNEL_TYPE_UPI = 1000;
    const RESULT_CODE_SUCCESS = 0;
    const CALLBACK_SUCCESS = 1;
    const RETURN_SUCCESS_CODE = 'OK';


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

        $params = array();
        $params['merchantId']  = $this->getSystemInfo('account');
        $params['orderId']     = $order->secure_id;
        $params['coin']        = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['goods']       = 'deposit';
        $params['attach']      = 'deposit';
        $params['redirectUrl'] = $this->getReturnUrl($orderId);
        // $params['notifyUrl']   = $this->getNotifyUrl($orderId);
        $params['sign']        = $this->sign($params);
        $this->CI->utils->debug_log('=====================neweasypay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================neweasypay processPaymentUrlFormPost response', $response);

        if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderId']);
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['url'],
            );
        }
        else if(isset($response['message']) && !empty($response['message'])) {
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
                'message' => lang('Invalidte API response')
            );
        }
    }

    public function getOrderIdFromParameters($params) {
        if(empty($params) || is_null($params) || is_array($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = $raw_post_data;
        }

        $params = json_decode($params,true);
        $this->utils->debug_log('=====================neweasypay callback params', $params);

        if (isset($params['outTradeNo'])) {
            $this->CI->load->model(array('sale_order','wallet_model'));
            if(substr($params['outTradeNo'],0,1) == 'D'){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($params['outTradeNo']);
                return $order->id;
            }else{
                return $params['outTradeNo'];
            }
        }
        else {
            $this->utils->debug_log('=====================neweasypay callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
            return;
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

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);

            if(substr($params['outTradeNo'] , 0, 1) == 'W'){
                $result = $this->isWithdrawal($params, $params['outTradeNo']);
                $this->CI->utils->debug_log('=======================neweasypay callbackFrom outTradeNo', $params['outTradeNo']);
                return $result;
            }elseif(!$order || !$this->checkCallbackOrder($order, $params, $processed)){
                return $result;
            }
        }

        $this->CI->utils->debug_log("=====================neweasypay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outTradeNo'], null, null, null, $response_result_id);
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

    public function isWithdrawal($params, $orderId){
        $result = array('success' => false, 'message' => 'Payment failed');
        $processed = false;
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
        if (!$this->checkCallbackWithdrawalOrder($order, $params, $processed)) {
            return $result;
        }

        $statusCode = $params['code'];
        if($statusCode == self::CALLBACK_SUCCESS) {
            $msg = "neweasypay withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('neweasypay withdrawal payment was not successful  trade ID [%s] ',$params['outTradeNo']);
            $this->utils->debug_log('=========================neweasypay withdrawal payment was not successful  trade ID [%s]', $params['outTradeNo']);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackWithdrawalOrder($order, $fields) {
        $requiredFields = array(
            'code', 'outTradeNo', 'merchantId', 'coin', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================neweasypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================neweasypay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['coin'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================neweasypay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['outTradeNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================neweasypay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'outTradeNo', 'code', 'coin', 'merchantId', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================neweasypay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================neweasypay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['code'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================neweasypay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['coin'] != $this->convertAmountToCurrency($order->amount)) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================neweasypay amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['coin'], $notes);
            }
            else{
                $this->writePaymentErrorLog("======================neweasypay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['outTradeNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================neweasypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    public function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign' || $key == 'attach' || $key == 'time') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $sign = strtoupper(md5($signStr.'key='.$this->getSystemInfo('key')));
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