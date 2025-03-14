<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TIANFU 天天付
 * *
 * * TIANFU_PAYMENT_API, ID: 964
 * * TIANFU_ALIPAY_PAYMENT_API, ID: 965
 * * TIANFU_ALIPAY_H5_PAYMENT_API, ID: 966
 * * TIANFU_UNIONPAY_PAYMENT_API, ID: 967
 * * TIANFU_UNIONPAY_H5_PAYMENT_API, ID: 968
 * * TIANFU_UNIONPAY_H5_2_PAYMENT_API, ID: 5333
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.zhizeng-pay.net/mas/mobile/create.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tianfu extends Abstract_payment_api {

    const CHANNEL_QQPAY       = 'QQ';
    const CHANNEL_WEIXIN      = 'WEIXIN';
    const CHANNEL_ALIPAY      = 'ALIPAY';
    const CHANNEL_UNIONPAY    = 'UNIONPAY';

    const PAYTYPE_DEBITCARD   = 'DEBIT_CARD';
    const PAYTYPE_QQPAY       = 'QqScan';
    const PAYTYPE_WEIXIN      = 'NATIVE';
    const PAYTYPE_WEIXIN_H5   = 'WECHATH5';
    const PAYTYPE_ALIPAY      = 'AliPayScan';
    const PAYTYPE_ALIPAY_H5   = 'AliPayH5';
    const PAYTYPE_UNIONPAY    = 'UnionpayScan';
    const PAYTYPE_UNIONPAY_H5 = 'UnionpayH5';

    const RESULT_CODE_SUCCESS = 'SUCCESS';
    const CALLBACK_SUCCESS = 'SUCCESS';
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
        $params['merchantNo']      = $this->getSystemInfo('account');
        $params['customerOrderNo'] = $order->secure_id;
        $params['orderTime']       = $orderDateTime->format('YmdHis');
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['subject']         = 'Topup';
        $params['body']            = 'Topup';
        $params['payerIp']         = $this->getClientIP();
        $params['notifyUrl']       = $this->getNotifyUrl($orderId);
        $params['pageUrl']         = $this->getReturnUrl($orderId);
        $params['signType']        = 'MD5';
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->sign($params);
        ksort($params);
        $this->CI->utils->debug_log('=====================tianfu generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['customerOrderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================tianfu processPaymentUrlFormPost response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['qrCode'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
            );
        }
        else if(isset($response['code'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Error Code: '.$response['code']
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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['customerOrderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================tianfu processPaymentUrlFormQRCode response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['qrCode'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
            );
        }
        else if(isset($response['code'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Error Code: '.$response['code']
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

        $this->CI->utils->debug_log("=====================tianfu callbackFrom $source params", $params);

        if($source == 'server'){
            $params =json_decode(key($params), true);
            $this->CI->utils->debug_log("=====================tianfu callbackFrom $source decoded params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], '', null, null, $response_result_id);
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
            'merchantNo', 'customerOrderNo', 'orderNo', 'amount', 'code', 'signType', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tianfu checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================tianfu checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['code'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================tianfu checkCallbackOrder Payment code is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================tianfu checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['customerOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================tianfu checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign' || $key == 'biz_content') {
                continue;
            }
            else{
                $signStr .= "$key=$value&";
            }
        }
        return rtrim($signStr, '&').$this->getSystemInfo('key');
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
        return number_format($amount*100, 0, '.', '');
    }
}