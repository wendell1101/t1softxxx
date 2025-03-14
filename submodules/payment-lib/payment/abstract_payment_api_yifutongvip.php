<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * YIFUTONGVIP 易付通
 *
 * * YIFUTONGVIP_ALIPAY_PAYMENT_API, ID: 5328
 * * YIFUTONGVIP_ALIPAY_H5_PAYMENT_API, ID: 5329
 * * YIFUTONGVIP_WEIXIN_PAYMENT_API: 5330
 * * YIFUTONGVIP_WEIXIN_H5_PAYMENT_API, ID: 5331
 * * YIFUTONGVIP_QUICKPAY_PAYMENT_API, ID: 5332
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.yifutongvip.com/Pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yifutongvip extends Abstract_payment_api {
    const PAYTYPE_ALIPAY   = '2';
    const PAYTYPE_WEIXIN   = '1';
    const PAYTYPE_QQPAY    = '3';
    const PAYTYPE_QUICKPAY = '71'; #71-储蓄卡, 72-信用卡

    const PAYTYPE_ALIPAY_H5 = '21';
    const PAYTYPE_WEIXIN_H5 = '11';
    const PAYTYPE_QQPAY_H5  = '31';

    const RESULT_CODE_SUCCESS = '0000';
    const CALLBACK_SUCCESS    = 'SUCCESS';
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

        $params['mch_num']    = $this->getSystemInfo('account');
        $params['order_num']  = $order->secure_id;
        $params['pay_money']  = $this->convertAmountToCurrency($amount);
        $params['user_ip']    = $this->getClientIp();
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign']       = $this->sign($params);

        $this->CI->utils->debug_log('=====================yifutongvip generatePaymentUrlForm params', $params);
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

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['order_num']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================yifutongvip processPaymentUrlFormQRCode response', $response);

        if($response['return_code'] == self::RESULT_CODE_SUCCESS && isset($response['pay_url'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['pay_url'],
            );
        }
        else if($response['return_msg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'return_code: '.$response['return_code'].'=> '.$response['return_msg']
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
        $raw_post_data = file_get_contents('php://input');
        $this->CI->utils->debug_log("=====================yifutongvip callbackFrom raw_post_data", $raw_post_data);
        $params = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log("=====================yifutongvip callbackFrom json_decode params", $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_num'], '', null, null, $response_result_id);
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
            'order_num', 'pay_type', 'pay_money', 'fin_money', 'mch_num', 'order_status', 'create_time', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yifutongvip checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================yifutongvip checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['order_status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================yifutongvip checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['pay_money'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================yifutongvip checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_num'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================yifutongvip checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            $this->utils->debug_log("===================yifutongvip validateSign signature is [$sign], match? ", $params['sign']);
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
        return number_format($amount, 0, '.', '');
    }
}