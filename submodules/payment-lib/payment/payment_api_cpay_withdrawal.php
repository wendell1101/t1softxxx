<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpay.php';

/**
 * CPAY
 *
 * * CPAY_WITHDRAWAL_PAYMENT_API, ID: 878
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info { "cpay_priv_key" }
 *
 * Field Values:
 * * URL: https://api.dobopay.com/v1/api/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra Info: { "cpay_priv_key" : " ## Private Key ## "}
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cpay_withdrawal extends Abstract_payment_api_cpay {
    const CALLBACK_STATUS_SUCCESS = 1;
    const CALLBACK_STATUS_FAILED  = 2;
    const CALLBACK_RETUTRN_SUCCESS = '{"code":200,"msg":"success"}';


    public function getPlatformCode() {
        return CPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'cpay_priv_key');
        return $secretsInfo;
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getCpayBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================cpay withdrawal bank whose bankTypeId=[$bank] is not supported by cpay");
            return array('success' => false, 'message' => 'Bank not supported by cpay');
            $bank = '无';
        }
        $bankName = $bankInfo[$bank]['name'];
        $bankCode = $bankInfo[$bank]['code'];

        # look up bank detail
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
            $city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
            $bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
        } else {
            $province = '无';
            $city = '无';
            $bankBranch = '无';
        }

        $params = array();
        $params['usercode'] = $this->getSystemInfo("account");
        $params['customno'] = $transId;
        $params['paytype'] = 'DB00015'; #fixed
        $params['type'] = '2'; #网银付款1 支付宝款2 微信付款3
        $params['money'] = $this->convertAmountToCurrency($this->randAmount($amount));
        $params['bankname'] = $bankName;
        $params['bankcode'] = $bankCode;
        $params['realname'] = $name;
        $params['cardno'] = $accNum;
        $params['idcard'] = "123456789123456789";
        $params['currency'] = "RMB";
        $params['province'] = $province;
        $params['city'] = $city;
        $params['branchname'] = $bankBranch;
        $params['sendtime'] = date('YmdHis');
        $params['notifyurl'] = $this->getNotifyUrl($transId);
        $params['buyerip'] = $this->utils->getIP();
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log("=====================cpay getWithdrawParams", $params);

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'Payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $cipherStr = json_encode($params, JSON_UNESCAPED_SLASHES);
        $params['cipherData'] = $this->getCipher($cipherStr);
        $cipherData['cipherData'] = $params['cipherData'];

        $submit = json_encode($cipherData, JSON_UNESCAPED_SLASHES);
        $url = $this->getSystemInfo('url');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $submit);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());
        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($response, 0, $header_size);
        $content     = substr($response, $header_size);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->CI->utils->debug_log('=====================cpay submitWithdrawRequest submit', $submit);


        #save response result
        $this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['customno']);
        $this->CI->utils->debug_log('=====================cpay submitWithdrawRequest content', $content);

        $decodedResult = $this->decodeResult($content);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {

        $result = json_decode($resultString, true);

        if($queryAPI) {
            $returnCode = $result['resultCode'];
            $returnDesc = $result['resultMsg'];
            $this->utils->debug_log("=========================cpay checkWithdrawStatus decoded result string", $result);
            $this->utils->debug_log("=========================cpay checkWithdrawStatus orderStatus", $returnCode);
        }
        else {
            $returnCode = $result['resultCode'];
            $returnDesc = $result['resultMsg'];
            $this->utils->debug_log("=========================cpay withdrawal decoded result string", $result);
        }

        #when success
        if($result['success'] == true) {
            $success = true;
            $message = "Cpay withdrawal response successful.";
            if($queryAPI) {
                if ($result['data']['status'] == self::CALLBACK_STATUS_SUCCESS) {
                    $message = sprintf('Cpay withdrawal payment was successful: trade ID [%s]', $result['data']['customNo']);
                    $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $message);
                } else if ($result['data']['status'] == self::CALLBACK_STATUS_FAILED) {
                    $success = false;
                    $message = sprintf('Cpay withdrawal payment was failed: status code [%s], '.$result['data']['resultMsg'], $result['data']['status']);
                    $this->writePaymentErrorLog($message, $fields);
                    $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
                } else {
                    $success = false;
                    $message = sprintf('Cpay withdrawal payment was not successful: status code [%s], '.$result['data']['resultMsg'], $result['data']['status']);
                    $this->writePaymentErrorLog($message, $fields);
                }
            }
            return array('success' => $success, 'message' => $message);
        } else {
            $message = "Cpay withdrawal response failed, [".$returnCode."]: ".$returnDesc;
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "Decode failed");
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['usercode'] = $this->getSystemInfo("account");
        $params['ordertype'] = '1'; #fixed 0:收款，1：付款，2：退款
        $params['customno'] = $transId;
        $params['sendtime'] = date('YmdHis');
        $params['sign'] = $this->sign($params);

        $url = $this->getSystemInfo('check_status_url');
        $response = $this->submitPostForm($url, $params, true, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('======================================cpay checkWithdrawStatus params: ', $params);
        $this->CI->utils->debug_log('======================================cpay checkWithdrawStatus url: ', $url );
        $this->CI->utils->debug_log('======================================cpay checkWithdrawStatus result: ', $response);
        return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        return $this->callbackFrom('server', $transId, $params, $response_result_id);
    }

    public function callbackFrom($source, $transId, $params, $response_result_id) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================cpay process withdrawalResult transId', $transId);
        $this->CI->utils->debug_log("=========================cpay checkCallback params", $params);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================cpay callbackFromServer raw_post_data", $raw_post_data);
        }

        $params = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log("=====================cpay callbackFromServer json_decode params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('Cpay withdrawal payment was successful: trade ID [%s]', $params['customNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['success'] = true;
            $result['message'] = self::CALLBACK_RETUTRN_SUCCESS;

        } else if ($params['status'] == self::CALLBACK_STATUS_FAILED) {
            $msg = sprintf('Cpay withdrawal payment was failed: status code [%s], '.$params['resultMsg'], $params['status']);
            $this->writePaymentErrorLog($msg, $fields);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('Cpay withdrawal payment was not successful: status code [%s], '.$params['resultMsg'], $params['status']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'sign', 'money', 'status', 'customNo', 'userCode'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================cpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySign($fields)) {
            $this->writePaymentErrorLog('=========================cpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['money'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================cpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['customNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================cpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getCpayBankInfo() {
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
            $this->utils->debug_log("==================getting cpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1'  => array('name' => '工商银行', 'code' => 'ICBC'),
                '2'  => array('name' => '招商银行', 'code' => 'CMB'),
                '3'  => array('name' => '建设银行', 'code' => 'CCB'),
                '4'  => array('name' => '农业银行', 'code' => 'ABC'),
                '5'  => array('name' => '交通银行', 'code' => 'COMM'),
                '6'  => array('name' => '中国银行', 'code' => 'BOC'),
                '8'  => array('name' => '广东发展银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行  ', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'SZPAB'),
                '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '19' => array('name' => '广州农商银行', 'code' => 'GNXS'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
            );
            $this->utils->debug_log("=======================getting cpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }


    public function sign($params) {
        $keys = array('usercode','ordertype', 'customno', 'type', 'cardno', 'idcard', 'money', 'sendtime', 'buyerip');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key] . '|';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);

        return $sign;
    }

    public function verifySign($params){
        $keys = array('orderNo', 'customNo', 'resultMsg', 'bankCode', 'userCode', 'money', 'currency', 'status', 'orderType');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                if($key == 'money'){
                    $signStr .= number_format($params[$key], 3, '.', '') . '|'; #注意money字段必须保留三位小数
                }
                else{
                    $signStr .= $params[$key] . '|';
                }
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);

        if($sign == $params["sign"]){
            return true;
        } else {
            return false;
        }
    }

    public function getPrivKey() {
        $cpay_priv_key = $this->getSystemInfo('cpay_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($cpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

    public function getCipher($cipherStr){
        $split_arr = str_split($cipherStr, 117);

        $cipherData = '';
        for ($i = 0; $i < count($split_arr); $i++) {
            openssl_private_encrypt($split_arr[$i], $temp, $this->getPrivKey());
            $cipherData .= $temp;
        }
        return base64_encode($cipherData);
    }

}