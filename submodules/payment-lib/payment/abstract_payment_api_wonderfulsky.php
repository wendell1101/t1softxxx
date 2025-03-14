<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * WONDERFULSKY 天空付
 *
 * * WONDERFULSKY_PAYMENT_API, ID: 913
 * * WONDERFULSKY_UNIONPAY_PAYMENT_API, ID: 914
 * * WONDERFULSKY_ALIPAY_PAYMENT_API, ID: 934
 * * WONDERFULSKY_ALIPAY_H5_PAYMENT_API, ID: 935
 * * WONDERFULSKY_QUICKPAY_PAYMENT_API, ID: 998
 * * WONDERFULSKY_QQPAY_PAYMENT_API, ID: 5088
 * * WONDERFULSKY_QQPAY_H5_PAYMENT_API, ID: 5089
 * * WONDERFULSKY_WEIXIN_PAYMENT_API, ID: 5138
 * * WONDERFULSKY_WEIXIN_H5_PAYMENT_API, ID: 5139
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.wonderfulsky.com.cn/service
 * * Extra Info:
 * > {
 * >    "wonderfulsky_priv_key": "## Private Key ##",
 * >    "wonderfulsky_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_wonderfulsky extends Abstract_payment_api {

    const CHANNEL_ALIPAY    = 'alipay';
    const CHANNEL_WEIXIN    = 'wechat';
    const CHANNEL_QQPAY     = 'qqpay';
    const CHANNEL_JDPAY     = 'jdpay';
    const CHANNEL_QUICKPAY  = 'quickpay';
    const CHANNEL_ALIPAY_H5 = 'alipayh5';
    const CHANNEL_WEIXIN_H5 = 'wechath5';
    const CHANNEL_QQPAY_H5  = 'qqpayh5';
    const CHANNEL_UNIONPAY  = 'unionpay';

    const RESULT_CODE_SUCCESS = "1";
    const CALLBACK_SUCCESS = '1';
    const RETURN_SUCCESS_CODE = 'success';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'wonderfulsky_pub_key', 'wonderfulsky_priv_key');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        if($this->getSystemInfo('rand_amount')){
            $amount = $this->randAmount($amount);
            if($amount != $order->amount){
                $notes = $order->notes . " | diff amount, old amount is :" . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $amount, $notes);
            }
        }

        $params = array();
        $params['service']    = 'Payment';
        $params['mid']        = $this->getSystemInfo('account');
        $params['merchantid'] = $order->secure_id;
        $params['amount']     = $this->convertAmountToCurrency($amount);
        $params['currency']   = 'CNY';
        $params['notifyurl']  = $this->getNotifyUrl($orderId);
        $params['returnurl']  = $this->getReturnUrl($orderId);

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================wonderfulsky generatePaymentUrlForm params', $params);

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

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merchantid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================wonderfulsky processPaymentUrlFormQRCode response', $response);

        if(isset($response['qrcodeurl'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['qrcodeurl'],
            );
        }
        else if(isset($response['result'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Response result: ['.$response['code'].']'.$response['message']
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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merchantid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================wonderfulsky processPaymentUrlFormQRCode response', $response);

        if(isset($response['qrcodeurl'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['qrcodeurl'],
            );
        }
        else if(isset($response['result'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Response result: ['.$response['code'].']'.$response['message']
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

        $this->CI->utils->debug_log("=====================wonderfulsky callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['systemid'], '', null, null, $response_result_id);
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
            'merchantid', 'amount', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================wonderfulsky checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================wonderfulsky checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================wonderfulsky checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================wonderfulsky checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantid'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================wonderfulsky checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
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
        openssl_sign($signStr, $sign_info, $this->getPrivKey());
        $sign = base64_encode($sign_info);

        return $sign;
    }

    protected function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $valid = openssl_verify($signStr, base64_decode($params['sign']), $this->getPubKey());

        return $valid;
    }

    protected function createSignStr($params) {
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

    private function getPubKey() {
        $wonderfulsky_pub_key = $this->getSystemInfo('wonderfulsky_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($wonderfulsky_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $wonderfulsky_priv_key = $this->getSystemInfo('wonderfulsky_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($wonderfulsky_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}