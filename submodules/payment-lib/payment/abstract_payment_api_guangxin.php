<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * GUANGXIN 广信支付
 *
 * * GUANGXIN_PAYMENT_API, ID: 5060
 * * GUANGXIN_UNIONPAY_PAYMENT_API, ID: 5061
 * * GUANGXIN_QUICKPAY_PAYMENT_API, ID: 5062
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://api.6899q.cn/open/v1/order/bankPay
 * * TOKEN URL: http://api.6899q.cn/open/v1/getAccessToken/merchant
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_guangxin extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = true;
    const CALLBACK_SUCCESS = true;
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

        $response = $this->getToken($order->secure_id);
        $result = json_decode($response, true);
        if(isset($result['value']['accessToken'])){
            $params = array();
            $params['accessToken']         = $result['value']['accessToken'];
            $params['param']['outTradeNo'] = $order->secure_id;
            $params['param']['money']      = $this->convertAmountToCurrency($amount);
            $params['param']['type']       = $this->getSystemInfo('type', 'T0');
            $params['param']['body']       = 'Topup';
            $params['param']['detail']     = 'Topup';
            $this->configParams($params, $order->direct_pay_extra_info);
            $params['param']['notifyUrl']  = $this->getNotifyUrl($orderId);
            $params['param']['productId']  = $order->secure_id;
            $params['param']['successUrl'] = $this->getReturnUrl($orderId);
            $this->CI->utils->debug_log("========================guangxin generatePaymentUrlForm params", $params);

            return $this->processPaymentUrlForm($params);
        }
        else if(isset($response['errorCode'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Get token failed. ['.$response['errorCode'].']'.$response['message']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Get token failed.')
            );
        }
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['param']['outTradeNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("========================guangxin processPaymentUrlFormPost response", $response);

        if(isset($response['success']) && $response['success'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['value'],
            );
        }
        else if(isset($response['errorCode'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['errorCode'].']'.$response['message']
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

    protected function processPaymentUrlFormQRCode($params) {
       $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['param']['outTradeNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("========================guangxin processPaymentUrlFormPost response", $response);

        if(isset($response['success']) && $response['success'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['value'],
            );
        }
        else if(isset($response['errorCode'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['errorCode'].']'.$response['message']
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

        $this->CI->utils->debug_log("========================guangxin callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("========================guangxin raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("========================guangxin json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['no'], null, null, null, $response_result_id);
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
            'no', 'outTradeNo', 'merchantNo', 'money', 'success', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("========================guangxin checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("========================guangxin checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['success'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("========================guangxin checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("========================guangxin checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['outTradeNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================guangxin checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function getToken($secure_id) {
        $url = $this->getSystemInfo('token_url', 'http://api.6899q.cn/open/v1/getAccessToken/merchant');
        $timestamp = time();

        $params = array();
        $params['merchantNo'] = $this->getSystemInfo('account');
        $params['nonce']      = random_string('numeric', 5);
        $params['timestamp']  = $timestamp;
        $params['sign']       = $this->sign($params);

        $response = $this->submitPostForm($url, $params, true, $secure_id);
        $this->CI->utils->debug_log("========================guangxin getToken response", $response);

        return $response;
    }

    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $keys = array('merchantNo', 'nonce', 'timestamp', 'token');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $keys = array('merchantId', 'merchantOrderId', 'orderAmount', 'systemOrderId', 'channelType', 'remark', 'ip');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr .= rtrim($signStr, '&').$this->getSystemInfo('key');
        $sign = md5($signStr);

        if($params['sign'] == $sign){
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

    protected function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }

    protected function getBankListInfoFallback() {
        return array(
            array('label' => '建设银行', 'value' => '1004'),
            array('label' => '农业银行', 'value' => '1002'),
            array('label' => '工商银行', 'value' => '1001'),
            array('label' => '光大银行', 'value' => '1008'),
            array('label' => '平安银行', 'value' => '1011'),
            array('label' => '邮政储蓄银行', 'value' => '1006'),
            array('label' => '招商银行', 'value' => '1012'),
            array('label' => '广发银行', 'value' => '1017'),
            array('label' => '北京银行', 'value' => '1016'),
            array('label' => '上海银行', 'value' => '1025'),
            array('label' => '民生银行', 'value' => '1010'),
            array('label' => '交通银行', 'value' => '1005'),
        );
    }
}