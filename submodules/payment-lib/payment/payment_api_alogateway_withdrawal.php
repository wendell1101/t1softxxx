<?php
require_once dirname(__FILE__) . '/abstract_payment_api_alogateway.php';
/**
 * ALOGATEWAY
 *
 * * ALOGATEWAY_WITHDRAWAL_PAYMENT_API, ID: 5002
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.alogateway.solutions/v2/distribute/withdraw.html
 * * Extra Info:
 * > {
 * >    "alogateway_priv_key": "## Private Key ##",
 * >    "alogateway_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_alogateway_withdrawal extends Abstract_payment_api_alogateway {

    const RESPONSE_SUCCESS = 'A2';
    const CALLBACK_SUCCESS = 'A0';
    const STATUS_FAILED  = 'FI';

    public function getPlatformCode() {
        return ALOGATEWAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'alogateway_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================alogateway submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================alogateway submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================alogateway submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province    = empty($playerBankDetails['province'])    ? "无" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "无" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "无" : $playerBankDetails['bankAddress'];
        } else {
            $bankBranch  = '无';
            $province    = '无';
            $city        = '无';
            $bankAddress = '无';
        }
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================alogateway withdrawal bank whose bankTypeId=[$bank] is not supported by alogateway");
            return array('success' => false, 'message' => 'Bank not supported by alogateway');
        }
        $bankcode = $bankInfo[$bank];
        $bankprovincecode = $this->getProvinceInfo($province);
        $bankcitycode     = $this->getCityInfo($city);


        $params = array();
        $params['version']           = '11';
        $params['merchantaccount']   = $this->getSystemInfo('account');
        $params['merchantorder']     = $transId;
        $params['amount']            = $this->convertAmountToCurrency($amount);
        $params['currency']          = $this->getSystemInfo('currency','CNY');
        $params['customername']      = $name;
        $params['bankprovincecode']  = $bankprovincecode;
        $params['bankcitycode']      = $bankcitycode;
        $params['bankcode']          = $bankcode;
        $params['bankbranchaddress'] = $bankAddress;
        $params['bankaccountnumber'] = $accNum;
        $params['memo']              = $transId;
        $params['serverreturnurl']   = $this->getNotifyUrl($transId);
        $params['control']           = $this->sign($params);


        $this->CI->utils->debug_log('======================================alogateway getWithdrawParams :', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $this->utils->debug_log("=========================alogateway decodeResult resultString", $resultString);

        $result = $this->parseResultXML($resultString, true);
     

        if($queryAPI) {
            $statusCode = $result['transaction']['status'];
            $statusMessage = $result['transaction']['message'];
            if($statusCode == self::CALLBACK_SUCCESS) {
                $message = "Alogateway withdrawal success!";
                return array('success' => true, 'message' => $message);
            }
            else if (preg_match("/".self::STATUS_FAILED."/i", $statusCode)) {
                $message = "Alogateway withdrawal failed. [".$statusCode."]: ". $statusMessage;
                return array('success' => false, 'message' => $message, 'payment_fail' => true);
            }
            else{
                $message = "Alogateway withdrawal response [".$statusCode."]: ". $statusMessage;
                return array('success' => false, 'message' => $message);
            }

        }
        else{
            $statusCode = $result['status'];
            $statusMessage = $result['message'];
            if($statusCode == self::RESPONSE_SUCCESS) {
                $message = "Alogateway request successful.";
                return array('success' => true, 'message' => $message);
            }
            else{
                $message = "Alogateway withdrawal response [".$statusCode."]: ". $statusMessage;
                return array('success' => false, 'message' => $message);
            }
        }

        return array('success' => false, 'message' => $message);
    }

    public function checkWithdrawStatus($transId) {
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);


        $params = array();
        $params['version']           = '1';
        $params['merchant_account']  = $this->getSystemInfo('account');
        $params['merchant_order']    = $transId;
        $params['amount']            = $this->convertAmountToCurrency($order['amount']);
        $params['currency']          = $this->getSystemInfo('currency','CNY');
        $params['control']           = $this->sign($params, true);
        $this->CI->utils->debug_log('======================================alogateway checkWithdrawStatus params: ', $params);

        $url = $this->getSystemInfo('check_status_url', 'https://payment.cdc.alogateway.co/Enquiry');
        $response = $this->submitPostForm($url, $params, false, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('======================================alogateway checkWithdrawStatus url: ', $url );
        $this->CI->utils->debug_log('======================================alogateway checkWithdrawStatus result: ', $response);
        return $decodedResult;
    }


    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================alogateway raw_post_data", $raw_post_data);
            $params = $this->parseResultXML($raw_post_data);
            $statusCode = $params['transaction']['status'];
            $statusMessage = $params['transaction']['message'];
            $this->CI->utils->debug_log("=====================alogateway parseResultXML params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($statusCode == self::CALLBACK_SUCCESS) {
            $msg = "Alogateway withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if(preg_match("/".self::STATUS_FAILED."/i", $statusCode)){
            $msg = "Alogateway withdrawal failed. [".$statusCode."]: ". $statusMessage;
            $this->writePaymentErrorLog($msg, $fields);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }
        else {
            $msg = "Alogateway withdrawal response [".$statusCode."]: ". $statusMessage;
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'transactionid', 'merchantaccount', 'merchant_order', 'amount', 'control'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================alogateway withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================alogateway withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================alogateway checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================alogateway withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['merchant_order'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================alogateway withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- signatures --
    private function sign($params, $queryAPI = false) {
        $signStr = $this->createSignStr($params, $queryAPI);
        $sign = sha1($signStr);
       
        return $sign;
    }

    private function createSignStr($params, $queryAPI) {
        if ($queryAPI) {
            $keys = array('merchant_account', 'merchant_order', 'amount', 'currency', 'version');
        }
        else{
            $keys = array('merchantaccount', 'merchantorder', 'amount', 'currency', 'customername', 'bankcode', 'bankaccountnumber');
        }

        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                if ($key == 'customername') {
                    $signStr .= base64_encode($params[$key]);
                }
                else {
                    $signStr .= $params[$key];
                }
            }
        }

        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $keys = array('transactionid', 'merchantaccount', 'merchant_order', 'amount', 'currency', 'status');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }
        $sign = hash_hmac('SHA1', $signStr, $this->getSystemInfo('key'));

        if($params['control'] == $sign){
            return true;
        }
        else{
          
            return false;
        }
    }

    # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("alogateway_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting alogateway bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => 'ICBC',
                '2' => 'CMB',
                '3' => 'CCB',
                '4' => 'ABC',
                '5' => 'BCOM',
                '6' => 'BOC',
                '7' => 'SDB',
                '8' => 'GDB',
                '10' => 'CITIC',
                '11' => 'CMBC',
                '12' => 'PSBC',
                '13' => 'CIB',
                '14' => 'HXB',
                '15' => 'PAB',
                '17' => 'GZCB',
                '18' => 'NJCB',
                '20' => 'CEB'
            );
            $this->utils->debug_log("=======================getting alogateway bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function getProvinceInfo($province) {
        $url = $this->getSystemInfo('province_url', 'https://payment.cdc.alogateway.co/CNProvince.php');
        $bankprovincecode = '110000';

        $json = file_get_contents($url);
        $provinceInfo = json_decode($json, true);
        $this->utils->debug_log("=======================getting alogateway provinceInfo: ", $provinceInfo);
        foreach ($provinceInfo as $key => $value) {
            if (preg_match("/".$province."/i", $value)) {
                $bankprovincecode = $key;
                break;
            }
        }
        return (string)$bankprovincecode;
    }

    public function getCityInfo($city) {
        $url = $this->getSystemInfo('city_url', 'https://payment.cdc.alogateway.co/CNCity.php');
        $bankcitycode = '110100';

        $json = file_get_contents($url);
        $cityInfo = json_decode($json, true);
        $this->utils->debug_log("=======================getting alogateway cityInfo: ", $cityInfo);
        foreach ($cityInfo as $key => $value) {
            if (preg_match("/".$city."/i", $value)) {
                $bankcitycode = $key;
                break;
            }
        }
        return (string)$bankcitycode;
    }

    protected function parseResultXML($resultXml) {
        $obj = simplexml_load_string($resultXml);
        $arr = $this->CI->utils->xmlToArray($obj);

        return $arr;
    }
}