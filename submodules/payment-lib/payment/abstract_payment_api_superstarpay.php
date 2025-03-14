<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SUPERSTARPAY 超级星
 *
 * * SUPERSTARPAY_PAYMENT_API, ID: 923
 * * SUPERSTARPAY_ALIPAY_PAYMENT_API, ID: 924
 * * SUPERSTARPAY_ALIPAY_H5_PAYMENT_API, ID: 925
 * * SUPERSTARPAY_WEIXIN_PAYMENT_API, ID: 926
 * * SUPERSTARPAY_WEIXIN_H5_PAYMENT_API, ID: 927
 * * SUPERSTARPAY_QQPAY_PAYMENT_API, ID: 928
 * * SUPERSTARPAY_QQPAY_H5_PAYMENT_API, ID: 929
 * * SUPERSTARPAY_UNIONPAY_PAYMENT_API, ID: 930
 * * SUPERSTARPAY_QUICKPAY_PAYMENT_API, ID: 931
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: https://www.superstarpay.com/gateway/orderPay
 * * Extra Info:
 * > {
 * >    "superstarpay_priv_key": "## Private Key ##",
 * >    "superstarpay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_superstarpay extends Abstract_payment_api {

    const PAYTYPE_ONLINEBANK = "1003";
    const PAYTYPE_ALIPAY     = "1006";
    const PAYTYPE_ALIPAY_H5  = "1011"; #1008
    const PAYTYPE_WEIXIN     = "1005";
    const PAYTYPE_WEIXIN_H5  = "1010";
    const PAYTYPE_QQPAY      = "1013";
    const PAYTYPE_QQPAY_H5   = "1014";
    const PAYTYPE_JDPAY      = "1017";
    const PAYTYPE_JDPAY_H5   = "1022";
    const PAYTYPE_UNIONPAY   = "1016";
    const PAYTYPE_QUICKPAY   = "1024";

    const APPSENCE_PC = "1001";
    const APPSENCE_H5 = "1002";


    const CALLBACK_SUCCESS = '1003';
    const CALLBACK_NOTIFY = '1001';
    const RETURN_SUCCESS_CODE = 'SUCCESS';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'superstarpay_pub_key', 'superstarpay_priv_key');
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
        $params['merId']         = $this->getSystemInfo('account');
        $params['terId']         = $this->getSystemInfo('key');
        $params['businessOrdid'] = $order->secure_id;
        $params['tradeMoney']    = $this->convertAmountToCurrency($amount);
        $params['orderName']     = 'Topup';
        $params['payType']       = "1000";
        $params['appSence']      = "1001";
        $params['syncURL']       = $this->getReturnUrl($orderId);
        $params['asynURL']       = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);

        $sign = $this->sign($params);
        $submit['version']  = '1.0.9';
        $submit['merId']    = $params['merId'];
        $submit['encParam'] = $sign['encParam'];
        $submit['sign']     = $sign['sign'];
        $this->CI->utils->debug_log('=====================superstarpay generatePaymentUrlForm params', $params);
        $this->CI->utils->debug_log('=====================superstarpay generatePaymentUrlForm submit', $submit);
        return $this->processPaymentUrlForm($submit);
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
        $this->CI->utils->debug_log("=====================superstarpay callbackFrom $source params", $params);

        if($source == 'server'){
            $params = $_REQUEST;

            $decrypted = json_decode($this->decrypt($params['encParam']), true);
            $this->CI->utils->debug_log("=====================superstarpay callbackFrom decrypted", $decrypted);

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

            $this->CI->sale_order->updateExternalInfo($order->id, $decrypted['payOrderId'], null, null, null, $response_result_id);
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
            'sign', 'merId', 'version', 'encParam'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================superstarpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================superstarpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $decrypted = json_decode($this->decrypt($fields['encParam']), true);
        if ($decrypted['notifyType'] != self::CALLBACK_NOTIFY) {
            $this->writePaymentErrorLog("======================superstarpay checkCallbackOrder is not callback notify type", $decrypted);
            return false;
        }


        if ($decrypted['order_state'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================superstarpay checkCallbackOrder Payment status is not success", $decrypted);
            return false;
        }

        if ($decrypted['money'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================superstarpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $decrypted);
            return false;
        }

        if ($decrypted['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================superstarpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $decrypted);
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
    # source: GatewayDemoPhp
    protected function sign($params) {
        $enc_json = json_encode($params,JSON_UNESCAPED_UNICODE);
        $Split = str_split($enc_json, 64);
        $encParam_encrypted = '';
        foreach($Split as $Part){
            openssl_public_encrypt($Part,$PartialData,$this->getPubKey()); //服务器公钥加密
            $encParam_encrypted .= $PartialData;
        }
        $encParam = base64_encode($encParam_encrypted); //加密的业务参数
        openssl_sign($encParam_encrypted, $sign_info, $this->getPrivKey());
        $sign = base64_encode($sign_info);

        return array('encParam' => $encParam, 'sign' => $sign);
    }

    protected function validateSign($params) {
        $valid = openssl_verify(base64_decode($params['encParam']), base64_decode($params['sign']), $this->getPubKey());

        return $valid;
    }

    protected function decrypt($data) {
        $data = base64_decode($data);
        $Split = str_split($data, 128);
        $back = '';
        foreach($Split as $k=>$v){
            openssl_private_decrypt($v, $decrypted, $this->getPrivKey());
            $back.= $decrypted;
        }

        return $back;
    }


    private function getPubKey() {
        $superstarpay_pub_key = $this->getSystemInfo('superstarpay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($superstarpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $superstarpay_priv_key = $this->getSystemInfo('superstarpay_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($superstarpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}