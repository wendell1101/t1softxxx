<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * KAITUO
 *
 * * KAITUO_ALIPAY_PAYMENT_API, ID: 5030
 * * KAITUO_ALIPAY_H5_PAYMENT_API, ID: 5031
 * * KAITUO_WEIXIN_PAYMENT_API, ID: 5032
 * * KAITUO_WEIXIN_H5_PAYMENT_API, ID: 5033
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pos.kaituocn.com/payapi/v2/Payinit/genQrCode
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_kaituo extends Abstract_payment_api {

    const PAYMENT_ALIPAY_H5  = 'Ali';
    const PAYMENT_WEIXIN_H5  = 'Wechat';

    const TRADE_TYPE_PC = 'JSAPI';
    const TRADE_TYPE_H5 = 'WEB';

    const RESULT_CODE_SUCCESS = '1';
    const RESULT_MSG_SUCCESS  = '操作成功';
    const CALLBACK_SUCCESS    = 'KT_SUCCESS';
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
        $params['appid']        = $this->getSystemInfo('account');
        $params['tel']          = $this->getSystemInfo('tel');
        $params['str']          = strtoupper(random_string('alnum', 32)); #随机字符串
        $params['m_order_sn']   = $order->secure_id;
        $params['pay_price']    = $this->convertAmountToCurrency($amount);
        $params['desc']         = 'Topup';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['redirect_url'] = $this->getReturnUrl($orderId);
        $params['timestamp']    = time();
        $params['attach']       = $order->secure_id;
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log("=======================kaituo generatePaymentUrlForm params", $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['m_order_sn']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=======================kaituo processPaymentUrlFormPost response", $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS && $response['message'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data'],
            );
        }
        else if($response['message']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['message']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['m_order_sn']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=======================kaituo processPaymentUrlFormPost response", $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS && $response['message'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['data'],
            );
        }
        else if($response['message']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['message']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->utils->debug_log("=======================kaituo getOrderIdFromParameters", $flds);
        $flds = json_decode(key($flds), true);
        if (isset($flds['out_trade_sn'])) {
            $this->CI->load->model(array('sale_order'));
            $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['out_trade_sn']);
            return $order->id;
        }
        else {
            $this->utils->debug_log("=======================kaituo callbackOrder cannot get any order_id when getOrderIdFromParameters", $flds);
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

        $this->CI->utils->debug_log("=======================kaituo callbackFrom $source params", $params);
        $params = json_decode(key($params), true);
        $this->CI->utils->debug_log("=======================kaituo callbackFrom $source json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['kt_order_sn'], null, null, null, $response_result_id);
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
            'return_code', 'out_trade_sn', 'price', 'ment', 'str', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================kaituo checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $fields['price'] = str_replace('_', '.', $fields['price']);
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("=======================kaituo checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['return_code'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=======================kaituo checkCallbackOrder Payment status is not success", $fields);
            return false;
        }


        if ($fields['price'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=======================kaituo checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_trade_sn'] != $order->secure_id) {
            $this->writePaymentErrorLog("=======================kaituo checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "key=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
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
        return number_format($amount, 2, '.', '');
    }
}