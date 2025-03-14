<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * Haoyunpay
 *
 * * HAOYUNPAY_ALIPAY_PAYMENT_API, ID : 5457
 * * HAOYUNPAY_WEXIN_PAYMENT_API, ID : 5458
 * * HAOYUNPAY_UNIONPAY_PAYMENT_API, ID : 5459
 * *
 * Required Fields:
 * * Account
 * * URL
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Live Key: ## Merchant Key ##
 * * URL: https://g88api.com
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_haoyunpay extends Abstract_payment_api {

    const PAYTYPE_ALIPAY   = 'alipay';#支付宝
    const PAYTYPE_WECHAT   = 'wechat';#微信
    const PAYTYPE_UNIONPAY = 'unionpay';#云闪付

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
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'haoyunpay_pub_key', 'haoyunpay_priv_key');
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
        $params['mc']            = $this->getSystemInfo('account'); //商户代码
        $params['uid']           = $playerId; //会员ID
        $params['tid']           = $order->secure_id; //交易ID
        $params['amount']        = $this->convertAmountToCurrency($amount);
        $params['time']          = time();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign']          = $this->sign($params);
        $params['return']        = $this->getReturnUrl($orderId);

        $this->CI->utils->debug_log('=====================haoyunpay generatePaymentUrlForm params', $params);
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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['tid']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================haoyunpay processPaymentUrlFormUrl json to array', $response);

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

        $this->CI->utils->debug_log("=====================haoyunpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['tid'], null, null, null, $response_result_id);
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
            'User', 'Trans_Id', 'Order_Id', 'Order_Time', 'Amount', 'Status', 'Sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================haoyunpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================haoyunpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['Status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================haoyunpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['Amount'] != $this->convertAmountToCurrency($order->amount)) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval( $fields['Amount']));
                if ($diffAmount >= 1) {
                    $this->writePaymentErrorLog("=====================haoyunpay checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$order->amount]", $fields ,$diffAmount);
                    return false;
                }
                $this->CI->utils->debug_log("=====================haoyunpay checkCallbackOrder amount not match expected [$order->amount]");
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['Amount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================haoyunpay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['Order_Id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================haoyunpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $signStr = $params['tid'].$params['time'].$params['uid'].$params['amount'];
        $sign = md5($this->getSystemInfo('key').$signStr);
        return $sign;
    }

    protected function validateSign($params) {
        $vri_sign = md5($this->getSystemInfo('account').$params['User'].$params['Trans_Id']);
        $vri_sign = md5($vri_sign.$params['Order_Id'].$params['Order_Time'].$params['Status'].$params['Amount']);

        $valid = (!empty($data['Sign']) && $data['Sign']==$vri_sign) ? true : false;

        return $valid;
    }

    private function getPubKey() {
        $haoyunpay_pub_key = $this->getSystemInfo('haoyunpay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($haoyunpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $haoyunpay_priv_key = $this->getSystemInfo('haoyunpay_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($haoyunpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}