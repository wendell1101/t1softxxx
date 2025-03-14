<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fly2pay.php';
/**
 * FLY2PAY
 *
 * * FLY2PAY_PAYMENT_API, ID: 5640
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://fly2pay.com/api/fundin/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_fly2pay_withdrawal extends Abstract_payment_api_fly2pay {

    public function getPlatformCode() {
        return FLY2PAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fly2pay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankProvince  = empty($playerBankDetails['province'])      ? "none" : $playerBankDetails['province'];
            $bankCity  = empty($playerBankDetails['city'])      ? "none" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "none" : $playerBankDetails['branch'];
        } else {
            $bankProvince = 'none';
            $bankCity = 'none';
            $bankBranch  = 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['merchant_id'] = $this->getSystemInfo("account");
        $params['business_email'] = $this->getSystemInfo('business_email', 'helpdesk@smartbackend.com');
        $params['order_id'] = $transId;
        $params['bank_id'] = $bankInfo[$bank]['code'];
        $params['bank_account_number'] = $accNum;
        $params['withdraw_amount'] = $this->convertAmountToCurrency($amount);
        $params['currency'] = $this->getSystemInfo('currency','THB');
        $params['bank_account_holder_name'] = $name;
        $params['bank_province'] = $bankProvince;
        $params['bank_city'] = $bankCity;
        $params['bank_branch'] = $bankBranch;
        $params['note'] = 'withdrawal';
        $params['website_url'] = $this->CI->utils->site_url_with_http();
        $params['request_time'] = time();
        $params['callback_noti_url'] = $this->getNotifyUrl($transId);
        $params['sign_data'] = $this->sign($params);

        $this->CI->utils->debug_log('=========================fly2pay getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->CI->utils->debug_log('======================================fly2pay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by fly2pay');
            return array('success' => false, 'message' => 'Bank not supported by fly2pay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================fly2pay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================fly2pay submitWithdrawRequest param: ', $params);
        $this->CI->utils->debug_log('======================================fly2pay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================fly2pay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }


    public function submitPostForm($url, $params, $postJson=false, $transId=NULL, $return_all=false) {
        try {
            $ch = curl_init();

            $api_user_name = $this->getSystemInfo('api_user_name');
            $api_password  = $this->getSystemInfo('api_password');
            $submit = array('requestParams' => json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); //request parameter


            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_USERPWD, $api_user_name . ':' . $api_password);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            if(!empty($this->_custom_curl_header)){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_custom_curl_header);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $submit);

            $this->setCurlProxyOptions($ch);

            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

            $response = curl_exec($ch);

            $this->CI->utils->debug_log('=========================fly2pay submitPostForm curl content ', $response);

            $errCode = curl_errno($ch);
            $error = curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $content = substr($response, $header_size);

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            $statusText = $errCode . ':' . $error;
            curl_close($ch);

            $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

            #withdrawal lock processing
            if(substr($transId, 0, 1) == 'W' && $errCode == '28') {
                $content = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
            }

            $response_result_content = is_array($content) ? json_encode($content) : $content;

            #save response result
            $response_result_id = $this->submitPreprocess($submit, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $transId);

            if($return_all){
                $response_result = [
                    $submit, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $transId
                ];
                $this->CI->utils->debug_log('=========================fly2pay submitPostForm return_all response_result', $response_result);
                return array($content, $response_result);
            }

            return $content;
        } catch (Exception $e) {
            $this->CI->utils->error_log('POST failed', $e);
        }
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        #different return type
        if(!is_null(json_decode($resultString))){
            $resultString = json_decode($resultString, true);
            $this->CI->utils->debug_log('==============fly2pay submitWithdrawRequest decodeResult json decoded', $resultString);
        }

        $this->utils->debug_log("=========================fly2pay withdrawal resultMsg", $resultMsg);

        if(isset($resultString['errCode'])) {
            $respCode = $resultString['errCode'];
            $resultMsg = $resultString['error'];
            if($respCode == self::RESULT_CODE_SUCCESS) {
                $message = "fly2pay request successful.";
                return array('success' => true, 'message' => $message);
            }else{
                $message = "fly2pay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
                return array('success' => false, 'message' => $message);
            }
        }
        else{
            if($resultString['error'] == '' || $resultString['error'] == false) {
                $this->utils->error_log("========================fly2pay return UNKNOWN ERROR!");
                $resultMsg = "UNKNOWN ERROR";
            }else{
                $resultMsg =  $resultString['error'];
            }
            return array('success' => false, 'message' => $resultMsg);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log("==========================fly2pay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================fly2pay raw_post_data json_decode params", $params);
        }
        else{
            $params = json_decode($params['requestParams'], true);
            $this->CI->utils->debug_log("=====================fly2pay json_decode params", $params);
        }

        if(!empty($params)){
            if (!$this->checkCallbackOrder($order, $params)) {
                return $result;
            }
        }
        if ($params['order_status'] == self::CALLBACK_SUCCESS)  {
            $this->utils->debug_log('=====================fly2pay withdrawal payment was successful: trade ID [%s]', $params['merchant_id']);
            $msg = sprintf('fly2pay withdrawal was successful: trade ID [%s]',$params['merchant_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['success'] = true;
        }else if($params['order_status'] == self::CALLBACK_FAILED){
            $msg = sprintf('fly2pay withdrawal was failed: trade ID [%s]',$params['merchant_id']);
            $this->writePaymentErrorLog($msg, $params['merchant_id']);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        }
        else {
            $msg = sprintf('fly2pay withdrawal payment was not successful  trade ID [%s] ',$params['merchant_id']);
            $this->writePaymentErrorLog($msg, $params['merchant_id']);
        }
        unset($result['message']);
        $result['message'] = self::RETURN_SUCCESS_CODE;
        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'merchant_id', 'transaction_id', 'order_id', 'withdraw_amount', 'order_status', 'sign_data'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================fly2pay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign_data'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================fly2pay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['withdraw_amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================fly2pay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['order_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================fly2pay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = $this->getSystemInfo('key');
        foreach($params as $key => $value) {
            if($key == 'sign_data'){
                continue;
            }
            $signStr .= $value;
        }
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->getSystemInfo('key');
        $keys = array('merchant_id', 'order_id', 'currency');
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }

        $sign = strtoupper(md5($signStr));

        if($params['sign_data'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting fly2pay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '27' => array('name' => 'Siam Commercial Bank', 'code' => 'SCB'),
                '28' => array('name' => 'Krung Thai Bank', 'code' => 'KTB'),
                '29' => array('name' => 'Krungsri Bank', 'code' => 'BAY'),
                '30' => array('name' => 'Bangkok Bank', 'code' => 'BBL'),
                '32' => array('name' => 'Kasikorn Bank', 'code' => 'KBANK'),
                '35' => array('name' => 'Thai Military Bank', 'code' => 'TMB'),
            );

            $this->utils->debug_log("=======================getting fly2pay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}