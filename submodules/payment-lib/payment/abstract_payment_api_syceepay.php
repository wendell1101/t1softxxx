<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SyceePay(MonsterPay)
 *
 * * SYCEEPAY_ALIPAY_BANKCARD_PAYMENT_API, ID: 5359
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.syceepay.com.cn/service
 * * Extra Info:
 * > {
 * >    "syceepay_priv_key": "## Private Key ##",
 * >    "syceepay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_syceepay extends Abstract_payment_api {

    const METHOD_ALIPAY_BANKCARD   = 'syceepay.alipay.bank';#支付宝转银行卡
    const METHOD_ALIPAY_TRANSFER   = 'syceepay.alipay.transfer';#支付宝扫码
    const METHOD_ALIPAY_COLLECTION = 'syceepay.alipay.collection';
    const METHOD_ALIPAY_REDPACK    = 'syceepay.alipay.redpack';
    const METHOD_WEIXIN            = 'syceepay.wechat.transfer';#微信扫码
    const METHOD_UNIONPAY          = 'syceepay.ysf.scan';

    const RESPONSE_CODE_SUCCESS = '0';
    const CALLBACK_SUCCESS      = '1';
    const RETURN_SUCCESS_CODE   = 'success';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'syceepay_pub_key', 'syceepay_priv_key');
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

        $params['merchant_code'] = $this->getSystemInfo('account');
        $params['money']         = $this->convertAmountToCurrency($amount);
        $params['bank_code']     = '';
        $params['order_sn']      = $order->secure_id;
        $params['notify_url']    = $this->getNotifyUrl($orderId);
        $params['return_url']    = $this->getReturnUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign']          = $this->sign($params);
        $params['service_type']  = 'direct_pay,json';

        $this->CI->utils->debug_log('=====================syceepay generatePaymentUrlForm params', $params);

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

    protected function processPaymentUrlFormUrl($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_sn']);
        $this->CI->utils->debug_log('=====================syceepay processPaymentUrlFormUrl received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================syceepay processPaymentUrlFormUrl json to array', $response);

        if($response['result'] && isset($response['data']['payment_code'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $this->getSystemInfo('reponse_url').$response['data']['payment_code']
            );
        }else if(!$response['result']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Return code: '.$response['errcode'].'=> '.$response['data']
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

        $this->CI->utils->debug_log("=====================syceepay callbackFrom $source params", $params);

        if($source == 'server'){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================syceepay raw_post_data", $raw_post_data);
            parse_str($raw_post_data, $params);
            $this->CI->utils->debug_log("=====================syceepay json_decode params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_sn'], null, null, null, $response_result_id);
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
            'merchant_code', 'money', 'submit_money', 'order_sn', 'trade_sn', 'payment_time', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================syceepay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================syceepay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================syceepay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order->amount)) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval( $fields['money']));
                if ($diffAmount >= 1) {
                    $this->writePaymentErrorLog("=====================syceepay checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$order->amount]", $fields ,$diffAmount);
                    return false;
                }
                $this->CI->utils->debug_log("=====================syceepay checkCallbackOrder amount not match expected [$order->amount]");
                $notes = $order->notes . "callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['money'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================syceepay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['order_sn'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================syceepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 0, '.', '');
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
        openssl_sign($signStr, $sign_info, $this->getPrivKey(),OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
 
        return $sign;
    }

    protected function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $valid = openssl_verify($signStr, base64_decode($params['sign']), $this->getPubKey(),OPENSSL_ALGO_MD5);


        return $valid;
    }

    protected function createSignStr($params) {
        // ksort($params);
        $signStr = [];
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign' || $value == '' || empty($value)) {
                continue;
            }
            $signStr[$key] = $value;
        }
        ksort($signStr);
        $signStr = urldecode(http_build_query($signStr));
        return $signStr;
    }

    private function getPubKey() {
        $syceepay_pub_key = $this->getSystemInfo('syceepay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($syceepay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $syceepay_priv_key = $this->getSystemInfo('syceepay_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($syceepay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}