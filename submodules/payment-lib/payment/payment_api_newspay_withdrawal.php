<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newspay.php';

/**
 * NEWSPAY
 *
 * * NEWSPAY_WITHDRAWAL_PAYMENT_API, ID: 5985
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://onepay.news/api/v1/order/out
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_newspay_withdrawal extends Abstract_payment_api_newspay {
    public function getPlatformCode() {
        return NEWSPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'newspay_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : 'sample@example.com';
            $pixNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }
        $randomNumber = $this->uuid();

        $params = array();
        $params['orderNo']       = $transId;
        $params['payCode']       = $this->getSystemInfo("payCode");
        $params['amount']        = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']     = urlencode($this->getNotifyUrl($transId));
        $params['uid']           = $playerId;
        $params['customerName']  = $lastname.$firstname;
        $params['customerEmail'] = $email;
        $params['customerPhone'] = $phone;
        $params['nonce']         = $randomNumber;
        $params['memberExpand1'] = 'CPF/CNPJ';
        $params['memberExpand2'] = $pixNumber;

        $this->CI->utils->debug_log('=========================newspay getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getSystemInfo('url');
        list($content, $response_result) = $this->processCurl($params, true);
        $this->CI->utils->debug_log('=====================newspay submitWithdrawRequest received response', $content);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================newspay json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['code']) && isset($result['code']) && $result['code'] == self::RESULT_CODE_SUCCESS ){
                return array('success' => true, 'message' => 'newspay withdrawal request successful.');
            }else if(isset($result['message']) && !empty($result['message'])){
                $errorMsg = $result['message'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'newspay withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'newspay withdrawal exist errors');
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $decryptParams = $this->decryptAes($params['data']);
        $this->CI->utils->debug_log("=========================newspay checkCallback params", $decryptParams);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================newspay process withdrawalResult order id', $transId);

        if (!$this->checkCallbackOrder($order, $decryptParams)) {
            return $result;
        }

        if($decryptParams['status'] == self::CALLBACK_SUCCESS_CODE) {
            $msg = sprintf('newspay withdrawal was successful: trade ID [%s]', $params['merchantNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('newspay withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = self::RETURN_FAIL_CODE;
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'merchantNo', 'amount', 'orderNo', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================newspay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================newspay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['merchantNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================newspay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function decryptAes($aesSecret = '')
    {
        $data = '';
        $passWord  = $this->getSystemInfo('key');
        if(!empty($aesSecret)){
            $str="";
            for($i=0;$i<strlen($aesSecret)-1;$i+=2){
                $str.=chr(hexdec($aesSecret[$i].$aesSecret[$i+1]));
            }
            $jsonData =  openssl_decrypt($str, 'AES-128-CBC', $passWord, OPENSSL_RAW_DATA, $passWord);
            $data = json_decode($jsonData,true);
            return $data;
        }else{
            return $data;
        }
    }

    public function processCurl($params, $return_all=false){
        $ch = curl_init();
        $token = $this->getSystemInfo("account");
        $postJsonData['data'] = $this->encryptionAes($params);
        $url = $this->getSystemInfo('url');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postJsonData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: '.$token)
        );

        $this->setCurlProxyOptions($ch);
        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $responseStr = substr($response, $header_size);
        curl_close($ch);
        #save response result
        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderNo']);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['orderNo']
            ];
            return array($response, $response_result);
        }
        return $response;
    }

    public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 4));
    }

}