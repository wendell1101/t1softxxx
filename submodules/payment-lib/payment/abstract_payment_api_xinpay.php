<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * XINPAY 新付
 * *
 * * XINPAY_UNIONPAY_PAYMENT_API, ID: 5177
 * * XINPAY_QUICKPAY_PAYMENT_API, ID: 5178
 * * XINPAY_JDPAY_PAYMENT_API, ID: 5179
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * json: https://or.7xinpy.com/pay/orderPay
 * * form: https://or.7xinpy.com/forward/orderPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_xinpay extends Abstract_payment_api {

    const BUSICODE_BANK      = '100301';  #网关支付
    const BUSICODE_ALIPAY    = '100201';  #支付宝扫码支付
    const BUSICODE_ALIPAY_H5 = '100203';  #支付宝h5支付
    const BUSICODE_UNIONPAY  = '100501';  #银联扫码支付
    const BUSICODE_QUICKPAY  = '100401';  #WAP支付
    const BUSICODE_JDPAY     = '100601';  #京东扫码支付
    const BUSICODE_QQPAY     = '100801';  #QQ扫码支付

    const RESULT_CODE_FAILED  = 'FAIL';
    const RESULT_CODE_SUCCESS = 'SUCCESS';
    const RESULT_MSG_SUCCESS  = '提交成功';

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
        $params['mer_no']       = $this->getSystemInfo('account');
        $params['mer_order_no'] = $order->secure_id;
        $params['order_amount'] = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['goods']        = 'Topup';
        $params['bg_url']       = $this->getNotifyUrl($orderId);
        $params['page_url']     = $this->getReturnUrl($orderId);
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log('=====================xinpay generatePaymentUrlForm params', $params);

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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['mer_order_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================xinpay processPaymentUrlFormQRCode response', $response);

        if($response['status'] == self::RESULT_CODE_SUCCESS && $response['resp_desc'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => base64_decode($response['code_url'])
            );
        }
        else if($response['status'] == self::RESULT_CODE_FAILED) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['resp_code'].': '.$response['resp_desc']
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

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['mer_order_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================xinpay processPaymentUrlFormQRCode response', $response);

        if($response['status'] == self::RESULT_CODE_SUCCESS && $response['resp_desc'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => base64_decode($response['code_url'])
            );
        }
        else if($response['status'] == self::RESULT_CODE_FAILED) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['resp_code'].': '.$response['resp_desc']
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

        $this->CI->utils->debug_log("=====================xinpay callbackFrom $source params", $params);

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
            'order_no', 'mer_no', 'order_amount', 'mer_order_no', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================xinpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================xinpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['order_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================xinpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['mer_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================xinpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            if(empty($value) || $key == 'key') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if( ($key == 'sign') || (empty($value)) ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
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
}