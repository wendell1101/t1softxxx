<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
* tianjinpay 天津支付 deposit
*
* * 'tianjinpay_ALIPAY_H5_PAYMENT_API', ID 936
*
* Required Fields:
*
* * URL
* * Account - Merchant ID
* * Key - Signing key
* * Extra Info
*
*    http://123.207.58.120:28080/Pay/gateway/payGetewayOrder
*
* @category Payment
* @copyright 2013-2022 tot
*/
abstract class Abstract_payment_api_tianjinpay extends Abstract_payment_api {

    const CARD_TYPE = '01';//借记卡(目前仅支持)
    const USER_TYPE = '1'; //个人

    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '000000';
    const PAY_RESULT_SUCCESS = '01';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'tianjinpay_pub_key', 'tianjinpay_priv_key');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params['MERCNUM'] = $this->getSystemInfo("account");
        $params['TRANDATA'] = array(
            'ORDERNO' => $order->secure_id,
            'TXNAMT' => $this->convertAmountToCurrency($amount),//分
            'RETURNURL' => $this->getReturnUrl($orderId),
            'NOTIFYURL' => $this->getNotifyUrl($orderId),
            'CARD_TYPE' => self::CARD_TYPE,
            'USER_TYPE' => $this->getSystemInfo("user_type")?$this->getSystemInfo("user_type"):self::USER_TYPE,
            'REMARK' => 'Deposit'
        );
        $this->configParams($params, $order->direct_pay_extra_info);
        $data = $params['TRANDATA'];
        $params['TRANDATA'] = $this->trandata($data);
        $params['SIGN'] = $this->sign($this->argSort($data));
        $this->CI->utils->debug_log("=====================tianjinpay generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    public function trandata($data){
        ksort($data);
        $str='';
        foreach ($data as $key => $value) {

            $str .= $key.'='.$value.'&';
        }
        $this->CI->utils->debug_log("=====================tianjinpay trandata str", $str);
        return rtrim($str, '&');
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {

        $url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log("=====================tianjinpay processPaymentUrlFormPost URL", $url);
        $this->CI->utils->debug_log("=====================tianjinpay processPaymentUrlFormPost params", $params);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => true,
        );
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {

        $this->CI->utils->debug_log("=====================tianjinpay processPaymentUrlFormQRCode params", $params);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true ,$params['TRANDATA']['ORDERNO']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================tianjinpay processPaymentUrlFormQRCode response', $response);

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


    /**
     * detail: This will be called when the payment is async, API server calls our callback page,
     * When that happens, we perform verifications and necessary database updates to mark the payment as successful
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    /**
     * detail: This will be called when user redirects back to our page from payment API
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $this->CI->utils->debug_log('=======================tianjinpay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $params = $_REQUEST;
            $this->CI->utils->debug_log('=======================tianjinpay callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success=true;

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
            $this->CI->sale_order->updateExternalInfo($order->id,
                $params['ORDERNO'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    /**
     * detail: Validates whether the callback from API contains valid info and matches with the order
     *
     * @return boolean
     */

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('ORDERNO', 'RECODE','REMSG','TXNAMT','PAYORDNO','ORDSTATUS','SIGN');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tianjinpay missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['ORDSTATUS'] != self::PAY_RESULT_SUCCESS) {
            $payStatus = $fields['ORDSTATUS'];
            $this->writePaymentErrorLog("=====================tianjinpay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['TXNAMT'] )
        ) {
            $this->writePaymentErrorLog("=====================tianjinpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['ORDERNO'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================tianjinpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================tianjinpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            // array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            // array('label' => '农业发展银行', 'value' => '203'),
            // array('label' => '交通银行', 'value' => 'BOCM'),
            // array('label' => '中信银行', 'value' => 'CNCB'),
            array('label' => '光大银行', 'value' => 'CEB'),
            // array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            // array('label' => '广发银行', 'value' => 'GDB'),
            // array('label' => '平安银行', 'value' => 'PAB'),
            // array('label' => '招商银行', 'value' => 'CMB'),
            // array('label' => '兴业银行', 'value' => 'CIB'),
            // array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            // array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
            // array('label' => '徽商银行', 'value' => '440'),
            // array('label' => '广州银行', 'value' => 'GZCB')
        );
    }

    # -- Private functions --
    /**
     * detail: After payment is complete, the gateway will invoke this URL asynchronously
     *
     * @param int $orderId
     * @return void
     */
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: After payment is complete, the gateway will send redirect back to this URL
     *
     * @param int $orderId
     * @return void
     */
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: Format the amount value for the API
     *
     * @param float $amount
     * @return float
     */
    protected function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }

    # -- private helper functions --

    /**
     * detail: getting the signature
     *
     * @param array $data
     * @return    string
     */
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        openssl_sign($signStr, $sign_info, $this->getPrivKey());
        $sign = base64_encode($sign_info);
        return $sign;
    }

    public function verifySignature($data) {
        $callback_sign = $data['SIGN'];
        unset($data['SIGN']);
        unset($data['RECODE']);
        unset($data['REMSG']);
        unset($data['PAYORDNO']);
        unset($data['CHNLORDERNO']);
        $signStr = $this->createSignStr($data);
        $valid = (bool)openssl_verify($signStr, base64_decode($callback_sign), $this->getPubKey());
        return $valid;
    }

    protected function createSignStr($params) {
        $i = 0;
        $signStr = "";
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v =  $this->characet($v, "UTF-8");
                if ($i == 0) {
                    $signStr .= "$k" . "=" . "$v";
                } else {
                    $signStr .= "&" . "$k" . "=" . "$v";
             }
            $i++;
        }
    }
        unset ($k, $v);
        return $signStr;
    }

    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    private function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    private function characet($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = "UTF-8";
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    private function getPubKey() {
        $tianjinpay_pub_key = $this->getSystemInfo('tianjinpay_pub_key');

        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($tianjinpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $tianjinpay_priv_key = $this->getSystemInfo('tianjinpay_priv_key');

        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($tianjinpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

}
