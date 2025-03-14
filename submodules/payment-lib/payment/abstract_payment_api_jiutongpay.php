<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 *
 * JIUTONGPAY 久通支付
 *
 * * JIUTONGPAY_PAYMENT_API, ID: 640
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.jiutongpay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_jiutongpay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = '00';
    const RESULT_MSG_SUCCESS = '提交成功';


    const CALLBACK_SUCCESS_CODE = '00';
    const RETURN_SUCCESS_CODE = '0';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'jiutongpay_pub_key', 'jiutongpay_priv_key');
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
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['callBackUrl']     = $this->getNotifyUrl($orderId);
        $params['callBackViewUrl'] = $this->getReturnUrl($orderId);
        $params['charset']         = 'UTF-8';
        $params['goodsName']       = 'Deposit';
        $params['merNo']           = $this->getSystemInfo("account");
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['orderNum']        = $order->secure_id;
        $params['random']          = (string)rand(1000,9999);//随机数
        $params['version']         = 'V3.1.0.0';
        $params['sign']            = $this->sign($params);

        $this->CI->utils->debug_log('=========================jiutongpay generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {

        $submit = array();
        $submit['data']    = $this->encryptData($params);
        $submit['merchNo'] = $params['merNo'];
        $submit['version'] = $params['version'];


        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($submit));

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $content     = substr($response, $header_size);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $submit , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($submit, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderNum']);



        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=========================jiutongpay processPaymentUrlFormPost response', $response);

        if($response['stateCode'] == self::RESULT_CODE_SUCCESS && $response['msg'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['qrcodeUrl'],
            );
        }
        else if($response['msg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['stateCode'].': '.$response['msg']
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

        $this->CI->utils->debug_log("=====================jiutongpay callbackFrom $source params", $params);

        if($source == 'server'){
            $data = $params['data'];
            $params = $this->decryptData($data);
            $this->CI->utils->debug_log("=====================jiutongpay callbackFrom $source decrypted params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNum'], '', null, null, $response_result_id);
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

    private function checkCallbackOrder($order, $fields, &$processed) {
        $requiredFields = array(
            'amount', 'goodsName', 'merNo', 'netway', 'orderNum', 'payDate', 'payResult', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=========================jiutongpay checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================jiutongpay checkCallbackOrder validateSign Error', $fields);
            return false;
        }

        $processed = true;

        if ($fields['payResult'] != self::CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog('=========================jiutongpay checkCallbackOrder payment was not successful', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=========================jiutongpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderNum'] != $order->secure_id) {
            $this->writePaymentErrorLog("=========================jiutongpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function getBankListInfoFallback() {
        return array(
            array('value' => 'E_BANK_BOC', 'label' => '中国银行'),
            array('value' => 'E_BANK_ABC', 'label' => '中国农业银行'),
            array('value' => 'E_BANK_ICBC', 'label' => '中国工商银行'),
            array('value' => 'E_BANK_CCB', 'label' => '中国建设银行'),
            array('value' => 'E_BANK_BCM', 'label' => '交通银行'),
            array('value' => 'E_BANK_CMB', 'label' => '中国招商银行'),
            array('value' => 'E_BANK_CEB', 'label' => '中国光大银行'),
            array('value' => 'E_BANK_CMBC', 'label' => '中国民生银行'),
            array('value' => 'E_BANK_HXB', 'label' => '华夏银行'),
            array('value' => 'E_BANK_CIB', 'label' => '兴业银行'),
            array('value' => 'E_BANK_CNCB', 'label' => '中信银行'),
            array('value' => 'E_BANK_SPDB', 'label' => '上海浦东发展银行'),
            array('value' => 'E_BANK_PSBC', 'label' => '中国邮政储蓄银行'),
            array('value' => 'E_BANK_SRCB', 'label' => '上海农商银行'),
            array('value' => 'E_BANK_BOS', 'label' => '上海银行'),
            array('value' => 'E_BANK_BOB', 'label' => '北京银行'),
            array('value' => 'E_BANK_BON', 'label' => '宁波银行'),
            array('value' => 'E_BANK_PAB', 'label' => '平安银行'),
            array('value' => 'E_BANK_GDB', 'label' => '广发银行'),
            array('value' => 'E_BANK_HFB', 'label' => '恒丰银行'),
            array('value' => 'E_BANK_SDB', 'label' => '深圳发展银行')
        );
    }

    # -- signing --
    private function sign($params) {
        ksort($params);
        $signStr = json_encode($params, 320) . $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function encryptData($params) {
        $paramStr   = json_encode($params, 320);
        $public_key = $this->getPubKey();

        $signStr = '';
        foreach (str_split($paramStr, 117) as $chunk) {
            openssl_public_encrypt($chunk, $sign_info, $public_key);
            $signStr .= $sign_info;
        }

        $data = base64_encode($signStr);
        return $data;
    }

    private function decryptData($data){
       $data = base64_decode($data);
       $private_key = $this->getPrivKey();

       $crypto = '';
       foreach (str_split($data, 128) as $chunk) {
           openssl_private_decrypt($chunk, $decryptData, $private_key);
           $crypto .= $decryptData;
       }

       return json_decode($crypto, true);
    }

    private function validateSign($params) {
        ksort($params);
        $callback_sign = $params['sign'] ;
        unset($params['sign']);

        $signStr = json_encode($params, 320) . $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));

        if($callback_sign != $sign){
            return false;
        }
        return true;
    }

    private function getPubKey() {
        $jiutongpay_pub_key = $this->getSystemInfo('jiutongpay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($jiutongpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $jiutongpay_priv_key = $this->getSystemInfo('jiutongpay_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($jiutongpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }


    # -- private functions --
    private function convertAmountToCurrency($amount) {
        $amount = number_format($amount*100, 0 , '', '');
        return (string)$amount;
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }
}