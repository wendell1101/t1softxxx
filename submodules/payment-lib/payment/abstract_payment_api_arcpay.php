<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ARCPAY 大强
 * *
 * * ARCPAY_PAYMENT_API, ID: 898
 * * ARCPAY_QQPAY_PAYMENT_API, ID: 899
 * * ARCPAY_QQPAY_H5_PAYMENT_API, ID: 900
 * * ARCPAY_ALIPAY_PAYMENT_API, ID: 906
 * * ARCPAY_ALIPAY_H5_PAYMENT_API, ID: 907
 * * ARCPAY_WEIXIN_PAYMENT_API, ID: 908
 * * ARCPAY_UNIONPAY_PAYMENT_API, ID: 909
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.arcpay.info/gateway/payApi/PayApiController/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_arcpay extends Abstract_payment_api {

    const PAYTYPE_ONLINEBANK  = 'B2C';
    const PAYTYPE_QUICKPAY    = 'QUICK';
    const PAYTYPE_QQPAY       = 'QQ_QR';
    const PAYTYPE_QQPAY_H5    = 'QQ_H5';
    const PAYTYPE_WEIXIN      = 'WECHAT_QR';
    const PAYTYPE_WEIXIN_H5   = 'WECHAT_H5';
    const PAYTYPE_ALIPAY      = 'ALI_QR';
    const PAYTYPE_ALIPAY_H5   = 'ALI_H5';
    const PAYTYPE_UNIONPAY    = 'UNION_QR';
    const PAYTYPE_UNIONPAY_H5 = 'UNION_H5';

    const RESULT_CODE_SUCCESS = '0000';
    const RESULT_MSG_SUCCESS = '成功';
    const CALLBACK_SUCCESS = 'SUCCESS';
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

        $ip_info = file_get_contents("http://ip-api.com/json/");
        $ip_info = json_decode($ip_info, true);
        $this->CI->utils->debug_log('=====================arcpay generatePaymentUrlForm ip_info', $ip_info);

        $params = array();
        $params['version']       = '1.0';
        $params['merchantNo']    = $this->getSystemInfo('account');
        $params['memberOrderId'] = $order->secure_id;
        $params['createTime']    = $orderDateTime->format('YmdHis');
        $params['orderAmount']   = $this->convertAmountToCurrency($amount);
        $params['goodsInfo']     = 'Topup';
        $params['longitude']     = $ip_info['lon'];
        $params['latitude']      = $ip_info['lat'];
        $params['clientIP']      = $ip_info['query'];
        $params['noticeUrl']     = $this->getNotifyUrl($orderId);
        $params['signType']      = '0'; # 0 MD5 ; 1 RSA

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================arcpay generatePaymentUrlForm params', $params);
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

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================arcpay processPaymentUrlFormQRCode response', $response);

        if($response['status'] == self::RESULT_CODE_SUCCESS && $response['message'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }
        else if($response['message']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['status'].': '.$response['message']
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

        $this->CI->utils->debug_log("=====================arcpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input');
                $this->CI->utils->debug_log("=====================arcpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================arcpay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
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
            'merchantNo', 'memberOrderId', 'orderId', 'stateCode', 'orderAmount', 'createTime', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================arcpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================arcpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['stateCode'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================arcpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['orderAmount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================arcpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['memberOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================arcpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $params['key'] = $this->getSystemInfo('key');
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return rtrim($signStr, '&');
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