<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * MACHIPAY
 * https://mer.fastpay-technology.com/powerpay-mer/
 *
 * * MACHIPAY_PAYMENT_API, ID: 5256
 * * MACHIPAY_ALIPAY_PAYMENT_API, ID: 5257
 * * MACHIPAY_ALIPAY_H5_PAYMENT_API, ID: 5258
 * * MACHIPAY_WEIXIN_PAYMENT_API, ID: 5259
 * * MACHIPAY_WEIXIN_2_PAYMENT_API, ID: 5260
 * * MACHIPAY_UNIONPAY_PAYMENT_API, ID: 5261
 * * MACHIPAY_QUICKPAY_PAYMENT_API, ID: 5262
 * * MACHIPAY_WITHDRAWAL_PAYMENT_API, ID: 5525
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paygate.fastpay-technology.com/powerpay-gateway-onl/txn
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_machipay extends Abstract_payment_api {

    const SUBTYPE_ONLINEBANK = "21";
    const SUBTYPE_ALIPAY     = "32";
    const SUBTYPE_ALIPAY_H5  = "42";
    const SUBTYPE_WEIXIN     = "31";
    const SUBTYPE_WEIXIN_H5  = "41";
    const SUBTYPE_UNIONPAY   = "34";
    const SUBTYPE_QUICKPAY   = "22";

    const SCENETYPE_WAP = "WAP";

    const RESULT_CODE_SUCCESS = "0000";
    const CALLBACK_STATUS_SUCCESS = "10";

    const RETURN_FAIL_CODE = 'FAIL';
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

        $params = array();
        $params['txnType']       = "01";
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['secpVer']       = "icp3-1.1";
        $params['secpMode']      = "perm";
        $params['macKeyId']      = $this->getSystemInfo('account');
        $params['orderDate']     = $orderDateTime->format('Ymd');
        $params['orderTime']     = $orderDateTime->format('His');
        $params['merId']         = $this->getSystemInfo('account');
        $params['orderId']       = $order->secure_id;
        $params['pageReturnUrl'] = $this->getReturnUrl($orderId);
        $params['notifyUrl']     = $this->getNotifyUrl($orderId);
        $params['productTitle']  = $order->secure_id;
        $params['txnAmt']        = $this->convertAmountToCurrency($amount);
        $params['currencyCode']  = "156";
        $params['timeStamp']     = $orderDateTime->format('YmdHis');

        if($this->getSystemInfo('isH5')){
            $params['sceneBizType'] = self::SCENETYPE_WAP;
            $params['clientIp'] = $this->getClientIP();
            $params['wapUrl']   = $this->getReturnUrl($orderId);
            $params['wapName']  = self::SCENETYPE_WAP;
        }

        $params['mac']          = $this->sign($params);
        $this->CI->utils->debug_log('=====================machipay generatePaymentUrlForm params', $params);

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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================machipay processPaymentUrlFormQRCode response', $response);

        if($response['respCode'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderId']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['txnId']);

            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['codeImgUrl'],
            );
        }
        else if(isset($response['respMsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['respCode'].': '.$response['respMsg']
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

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================machipay processPaymentUrlFormRedirect response', $response);

        if($response['respCode'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderId']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['txnId']);

            if(isset($response['codePageUrl'])){
                $url = $response['codePageUrl'];
            }else{
                $url = $response['codeImgUrl'];
            }
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $url,
            );
        }
        else if(isset($response['respMsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['respCode'].': '.$response['respMsg']
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

        $this->CI->utils->debug_log("=====================machipay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['txnId'], null, null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_FAIL_CODE;
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
            'respCode', 'txnStatus', 'txnId', 'txnAmt', 'orderId', 'mac'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================machipay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================machipay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['txnStatus'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================machipay checkCallbackOrder Payment status is not success', $fields);
            return false;
        }

        if ($fields['txnAmt'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================machipay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================machipay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'mac') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'k='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['mac'] == $sign){
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
        return number_format($amount*100, 0, '.', '');
    }
}