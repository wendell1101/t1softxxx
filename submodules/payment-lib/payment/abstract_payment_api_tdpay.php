<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * TDPAY 顺博支付
 *
 * * TDPAY_PAYMENT_API, ID: 901
 * * TDPAY_ALIPAY_H5_PAYMENT_API, ID: 902
 * * TDPAY_QUICKPAY_PAYMENT_API, ID: 903
 * * TDPAY_ALIPAY_PAYMENT_API, ID: 5097
 * * TDPAY_WEIXIN_PAYMENT_API, ID: 5098
 * * TDPAY_WEIXIN_H5_PAYMENT_API, ID: 5099
 * * TDPAY_UNIONPAY_PAYMENT_API, ID: 5169
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://39.106.2.9:8081/tdpay
 * * Extra Info:
 * > {
 * >    "tdpay_priv_key": "## Private Key ##",
 * >    "tdpay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tdpay extends Abstract_payment_api {

    const MERPAYTYPE_BANK     = '12';
    const MERPAYTYPE_ALIPAY   = '06';
    const MERPAYTYPE_WEIXIN   = '07';
    const MERPAYTYPE_UNIONPAY = '18';

    const APPTYPE_ALIPAY = '16';
    const APPTYPE_WEIXIN = '17';

    const CALLBACK_SUCCESS = '00';
    const RETURN_SUCCESS_CODE = 'success';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'tdpay_pub_key', 'tdpay_priv_key');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        #SHARE
        $params['version']      = 'v1.0';
        $params['language']     = '1';
        $params['accessType']   = '1';
        $params['signType']     = '3';
        $params['merchantId']   = $this->getSystemInfo('account');
        $params['pageUrl']      = urlencode($this->getReturnUrl($orderId));

        $params['temp']['notify_url'] = urlencode($this->getNotifyUrl($orderId));
        $params['temp']['secure_id']  = $order->secure_id;
        $params['temp']['amount']     = $this->convertAmountToCurrency($amount);

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['signMsg'] = trim($this->sign($params));

        $this->CI->utils->debug_log('=====================tdpay generatePaymentUrlForm params', $params);

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

    protected function processPaymentUrlFormQRCode($params) {}


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

        $this->CI->utils->debug_log("=====================tdpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['payOrderId'], '', null, null, $response_result_id);
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
            'merchantId', 'orderNo', 'payAmount', 'payOrderId', 'payResult', 'signMsg'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tdpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================tdpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['payResult'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================tdpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['payAmount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================tdpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================tdpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- signing --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_SHA1);
        $sign = base64_encode($sign_info);
        $sign = str_replace(array('+', '/', '='), array('-', '_', ''), $sign);
        return $sign;
    }

    protected function validateSign($params) {
        $sign = $params['signMsg'];
        unset($params['signMsg']);
        unset($params['rspCod']);
        unset($params['rspMsg']);
        unset($params['urlAsync']);
        $data = str_replace(array('-', '_'), array('+', '/'), $sign);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        $signStr = $this->createSignStr($params);
        $valid = openssl_verify($signStr, base64_decode($data), $this->getPubKey());

        return $valid;

    }

    protected function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'signMsg') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return rtrim($signStr, '&');
    }

    private function getPubKey() {
        $tdpay_pub_key = $this->getSystemInfo('tdpay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($tdpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $tdpay_priv_key = $this->getSystemInfo('tdpay_priv_key');
        $priv_key = '-----BEGIN PRIVATE KEY-----' . PHP_EOL . chunk_split($tdpay_priv_key, 64, PHP_EOL) . '-----END PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}