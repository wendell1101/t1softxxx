<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fengyunpay.php';

/**
 * FENGYUNPAY  风云
 *
 * * FENGYUNPAY_WITHDRAWAL_PAYMENT_API, ID: 5243
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: https://gateway.fengyunpay.net/supApi/singlePenTransfer
 * * Account: ## MerId ##
 * * Key: ## APIKEY ##
 * * Secret: ## TerId ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fengyunpay_withdrawal extends Abstract_payment_api_fengyunpay {

    const RESULT_STATUS_SUCCESS = "0000";
    const PAYMENT_STATUS_SUCCESS  = 1008;
    const PAYMENT_STATUS_REFUNDED = 1007;
    const PAYMENT_STATUS_FAILED   = 1006;

    const REALTIME = 1002;

    public function getPlatformCode() {
        return FENGYUNPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fengyunpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'fengyunpay_pub_key', 'fengyunpay_priv_key');
        return $secretsInfo;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('=========================fengyunpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]");

        $params = array();
        $params['businessId'] = $transId;
        $params['cardHolder'] = $name;
        $params['cardNo']     = $accNum;
        $params['tradeMoney'] = $this->convertAmountToCurrency($amount);
        $params['remark']     = $transId;
        $params['apiKey']     = $this->getSystemInfo('key');
        $params['hmac']       = $this->getHmac($params);


        $sign = $this->sign($params);
        $submit = array();
        $submit['encParam'] = $sign['encParam'];
        $submit['sign']     = $sign['sign'];
        $submit['merId']    = $this->getSystemInfo('account');
        $submit['version']  = '1.0.9';
        $submit['terId']    = $this->getSystemInfo('secret');

        $this->CI->utils->debug_log('=========================fengyunpay getWithdrawParams submit', $submit);
        return $submit;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = array('success' => false, 'message' => 'Fengyunpay decoded fail. Please contact payment provider.');
        $encrypted = json_decode($resultString, true);
        if(isset($encrypted['sign'])){
            if(!$this->validateSign($encrypted)){
                $result['message'] = "Fengyunpay withdrawal validate response sign error!";
                return $result;
            }
        }

        if(isset($encrypted['encParam'])){
            $decrypted = json_decode($this->decrypt($encrypted['encParam']), true);
            $response = json_decode($decrypted['details'], true);
            $this->CI->utils->debug_log('=========================fengyunpay submitWithdrawRequest decrypted', $decrypted);
            $this->CI->utils->debug_log('=========================fengyunpay submitWithdrawRequest response', $response);

            $respCode    = $response['respCode'];
            $returnSuccess = $response['success'];
            $returnMsg     = $response['message'];

            if($respCode == self::RESULT_STATUS_SUCCESS) {
                $transStatus = $response['data']['cashState'];
                $transDesc   = $response['data']['failContent'];

                if($queryAPI){
                    $result = array('success' => false, 'message' => 'Fengyunpay check status decoded fail.', 'payment_fail' => false);
                    if($transStatus == self::PAYMENT_STATUS_SUCCESS){
                        $result['success'] = true;
                        $result['message'] = "Fengyunpay withdrawal success! [".$transStatus."]".$transDesc;
                    }
                    elseif($transStatus == self::PAYMENT_STATUS_FAILED || $transStatus == self::PAYMENT_STATUS_REFUNDED){
                        $result['payment_fail'] = true;
                        $result['message'] = "Fengyunpay withdrawal failed. [".$transStatus."]".$transDesc;
                    }
                    else{
                        $result['message'] = "Fengyunpay withdrawal response [".$transStatus."]".$transDesc;
                    }
                }
                else{
                    $realtime = ($response['data']['isRealPay'] == self::REALTIME) ? "，实时到账（2 小时内）" : "";
                    if($returnSuccess){
                        $result['success'] = true;
                        $result['message'] = "Fengyunpay withdrawal response success! [".$transStatus."]".$returnMsg.$realtime;
                    }
                    else{
                        $result['message'] = "Fengyunpay withdrawal response [".$transStatus."]".$transDesc;
                    }
                }
            }
            else{
                $result['message'] = "Fengyunpay withdrawal response failed. [".$respCode."]: ".$returnMsg;
            }
        }
        else if(is_array($encrypted)){
            $respCode   = (isset($encrypted['respCode'])) ? $encrypted['respCode']: '';
            $message    = (isset($encrypted['message'])) ? $encrypted['message']: 'Please contact payment provider.';
            $returnCode = (isset($encrypted['returnCode'])) ? $encrypted['returnCode']: '';
            $result['message'] = "Fengyunpay withdrawal response failed. [".$respCode."]: ".$message.'; '.$returnCode;
        }
        else if($resultString){
            $result['message'] = $resultString;
        }

        return $result;
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['businessId'] = $transId;
        $params['apiKey']     = $this->getSystemInfo('key');
        $params['hmac']       = $this->getHmac($params);
        


        $sign = $this->sign($params);
        $submit = array();
        $submit['encParam'] = $sign['encParam'];
        $submit['sign']     = $sign['sign'];
        $submit['merId']    = $this->getSystemInfo('account');
        $submit['version']  = '1.0.9';
        $submit['terId']    = $this->getSystemInfo('secret');


        $url = $this->getSystemInfo('check_status_url', 'https://gateway.fengyunpay.net/supApi/queryTransferResult');
        $response = $this->submitPostForm($url, $submit, false, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('=========================fengyunpay checkWithdrawStatus result: ', $response);
        return $decodedResult;
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->utils->debug_log("=======================fengyunpay getOrderIdFromParameters", $flds);
        if(empty($flds)) {
            if (empty($flds)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================fengyunpay raw_post_data", $raw_post_data);
                $flds = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================fengyunpay json_decode flds", $flds);
            }
        }
        if(isset($flds['encParam'])){
            $decrypted = json_decode($this->decrypt($flds['encParam']), true);
            $decrypted = json_decode($decrypted['details'], true);
            $this->CI->utils->debug_log("=====================fengyunpay callbackFrom decrypted details", $decrypted);
            $this->CI->utils->debug_log("=====================fengyunpay callbackFrom decrypted details data", $decrypted['data']);

            if(isset($decrypted['data']['businessId'])) {
                $transId = $decrypted['data']['businessId'];
                $this->CI->load->model(array('wallet_model'));
                $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

                return $walletAccount['transactionCode'];
            }
            else {
                $this->utils->debug_log("=======================fengyunpay getOrderIdFromParameters cannot get any businessId", $decrypted);
            }
        }
        else{
            $this->utils->debug_log("=======================fengyunpay getOrderIdFromParameters cannot get any encParam", $flds);
        }
        return null;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================fengyunpay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================fengyunpay json_decode params", $params);
        }
        $decrypted = json_decode($this->decrypt($params['encParam']), true);
        $this->CI->utils->debug_log("=====================fengyunpay callbackFrom decrypted", $decrypted);
        $decrypted = json_decode($decrypted['details'], true);
        $this->CI->utils->debug_log("=====================fengyunpay callbackFrom decrypted details", $decrypted);

        if (!$order || !$this->checkCallbackOrder($order, $params)) {
            return $result;
        }
        $statusCode = $decrypted['data']['cashState'];
        if($statusCode == self::PAYMENT_STATUS_SUCCESS) {
            $msg = "FENGYUNPAY withdrawal success!]";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if($statusCode == self::PAYMENT_STATUS_REFUNDED){
            $msg = "FENGYUNPAY withdrawal failed. [".$statusCode."]: ". $decrypted['message'];
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }
        else {
            $msg = "FENGYUNPAY withdrawal response [".$statusCode."]: ". $decrypted['message'];
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'encParam', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================fengyunpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================fengyunpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        $decrypted = json_decode($this->decrypt($fields['encParam']), true);
        $decrypted = json_decode($decrypted['details'], true);
        $amount = $this->convertAmountToCurrency($order['amount']);
        if ($decrypted['data']['tradeMoney'] != $amount){
            $this->writePaymentErrorLog('=========================fengyunpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $amount, $decrypted);
            return false;
        }

        if ($decrypted['data']['businessId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================fengyunpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $decrypted);
            return false;
        }

        # everything checked ok
        return true;
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    # -- signatures --
    protected function getHmac($params) {
        $keys = array('businessId', 'cardHolder', 'cardNo', 'tradeMoney', 'remark', 'apiKey', 'code', 'currencyName', 'bankCode', 'phone', 'email');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr = rtrim($signStr, '&');
        $sign = md5($signStr);
      
        return $sign;
    }

    protected function sign($params) {
        $enc_json = json_encode($params,JSON_UNESCAPED_UNICODE);
        $encParam_encrypted = '';
        foreach(str_split($enc_json, 64) as $Part){
            openssl_public_encrypt($Part, $PartialData, $this->getPubKey()); //服务器公钥加密
            $encParam_encrypted .= $PartialData;
        }
        $encParam = base64_encode($encParam_encrypted);
        openssl_sign($encParam_encrypted, $sign_info, $this->getPrivKey());  //加密的业务参数
        $sign = base64_encode($sign_info);

     
        return array('encParam' => $encParam, 'sign' => $sign);
    }

    protected function validateSign($params) {
        $valid = openssl_verify(base64_decode($params['encParam']), base64_decode($params['sign']), $this->getPubKey());

        return $valid;
    }

    protected function decrypt($data) {
        $data = base64_decode($data);
        $back = '';
        foreach(str_split($data, 128) as $k=>$v){
            openssl_private_decrypt($v, $decrypted, $this->getPrivKey());
            $back.= $decrypted;
        }
        return $back;
    }

    private function getPubKey() {
        $fengyunpay_pub_key = $this->getSystemInfo('fengyunpay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($fengyunpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $fengyunpay_priv_key = $this->getSystemInfo('fengyunpay_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($fengyunpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}