<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 98PAY
 *
 * * _98PAY_ALIPAY_PAYMENT_API, ID: 970
 * * _98PAY_ALIPAY_H5_PAYMENT_API, ID: 971
 * * _98PAY_WEIXIN_PAYMENT_API, ID: 972
 * * _98PAY_WEIXIN_H5_PAYMENT_API, ID: 973
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.yduma.cn/pay/api/api.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Secret: ## Callback Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_98pay extends Abstract_payment_api {
    const CODE_TYPE_WEIXIN = 1;
    const CODE_TYPE_ALIPAY = 2;

    const RESULT_CODE_SUCCESS = 0;
    const RESULT_MSG_SUCCESS = '获取二维码成功';

    const CALLBACK_SUCCESS = 0;
    const RETURN_SUCCESS_CODE = 'OK';


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
        $params['action']       = 'pay';
        $params['m']            = 'pay_it';
        $params['partner_no']   = $this->getSystemInfo('account');
        $params['mch_order_no'] = $order->secure_id;
        $params['body']         = 'Topup';
        $params['money']        = $this->convertAmountToCurrency($amount);
        $params['callback_url'] = $this->getNotifyUrl($orderId);
        $params['time_stamp']   = $orderDateTime->getTimestamp().'000';

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['token'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================98pay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['mch_order_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================98pay processPaymentUrlFormPost response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS && $response['msg'] == self::RESULT_MSG_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['mch_order_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['order_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['code_link'],
            );
        }
        else if(isset($response['code'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
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
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['mch_order_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================98pay processPaymentUrlFormQRCode response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS && $response['msg'] == self::RESULT_MSG_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['mch_order_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['order_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'image_url' => $response['code_img_url'],
            );
        }
        else if($response['msg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
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

        $this->CI->utils->debug_log("=====================98pay params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_no'], '', null, null, $response_result_id);
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
            'code', 'money', 'mch_order_no', 'order_no', 'time_stamp', 'token'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================98pay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================98pay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['code'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================98pay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff') && isset($fields['payAmount'])){
                $this->CI->utils->debug_log("======================98pay checkCallbackOrder Payment amount not match, expected [$order->amount]");
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['payAmount']/100, $notes);

            }
            else{
                $this->writePaymentErrorLog("======================98pay checkCallbackOrder Payment amount not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['mch_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================98pay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $keys = array('mch_order_no', 'money', 'partner_no', 'time_stamp');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }

        return $signStr.'key='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $keys = array('mch_order_no', 'money', 'order_no', 'time_stamp');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr .= 'key='.$this->getSystemInfo('secret');
        $sign = md5($signStr);

        if($params['token'] == $sign){
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
        return number_format($amount * 100, 0, '.', '');
    }
}