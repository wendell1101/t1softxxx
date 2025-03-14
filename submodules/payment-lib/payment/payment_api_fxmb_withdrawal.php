<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fxmb.php';

/**
 * FXMB_WITHDRAWAL
 *
 * * FXMB_WITHDRAWAL_PAYMENT_API, ID: 5864
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://bitplay88.fxmb.com/api/v1/external/payout/bank-transfer
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fxmb_withdrawal extends Abstract_payment_api_fxmb {

    const CHANNLETYPE = '0';
    const RESPONSE_ORDER_SUCCESS = 'PENDING';
    const CALLBACK_STATUS_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS_CODE = "SUCCESS";
    const CURRENCY = 'IN';
    const PAYOUTMETHOD = 'NETBANKING';

    public function getPlatformCode() {
        return FXMB_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fxmb_withdrawal';
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

        $bankInfo = $this->getBankInfo();
        $this->utils->error_log("========================fxmb withdrawal bank whose bankTypeId=[$bank] bankInfo fxmb",$bankInfo);
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================fxmb withdrawal bank whose bankTypeId=[$bank] is not supported by fxmb");
            return array('success' => false, 'message' => 'Bank not supported by fxmb');
        }

        $this->_custom_curl_header = array('Content-Type: application/json','apikey:'.$this->getSystemInfo('key'));
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        if($decodedResult['success']){
            $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $decodedResult['transactionId']);
        }

        $this->CI->utils->debug_log('======================================fxmb submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================fxmb submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================fxmb submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $bankName = $bankInfo[$bank]['name'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================fxmb Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
            $bankAddress = $playerBankDetails['bankAddress'];
            $bankAccountFullName = $playerBankDetails['bankAccountFullName'];
        } else {
            $province = 'none';
            $city = 'none';
            $bankBranch = 'none';
            $bankAddress = 'none';
            $bankAccountFullName = 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $playerId = $this->CI->wallet_model->getplayeridbywalletaccountid($order['walletAccountId']);

        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $this->utils->debug_log("===============================fxmb Get playerDetails", $playerDetails, $playerId);


        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : '';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : '';
        $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : '';
        $city      = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : '';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : '';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : '';


        $params = array();
        $params['callbackUrl']           = $this->getNotifyUrl($transId);
        $params['country']               = $this->getSystemInfo('currency',self::CURRENCY);
        $params['data']['accountName']   = $name;
        $params['data']['accountNumber'] = $accNum;
        $params['data']['address']       = $address;
        $params['data']['amount']        = $this->convertAmountToCurrency($amount);
        $params['data']['bankAddress']   = 'NA';
        $params['data']['bankBranch']    = 'NA';
        $params['data']['bankName']      = $bankName;
        $params['data']['bankifsc']      = $bankBranch;
        $params['data']['city']          = $city;
        $params['data']['email']         = $email;
        $params['data']['firstName']     = $firstname;
        $params['data']['lastName']      = $lastname;
        $params['data']['mobile']        = $phone;
        $params['data']['postcode']      = $this->getSystemInfo('postcode');
        $params['data']['username']      = $username;
        $params['merchantTransactionId'] = $transId;
        $params['payoutMethod']          = $this->getSystemInfo('payoutMethod',self::PAYOUTMETHOD);

        $this->CI->utils->debug_log('=========================fxmb getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================fxmb json_decode result", $result);

        if(isset($result['status'])) {
            if($result['status'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "fxmb withdrawal response successful, status:[".$result['status']."]: ".$result['message'];
                return array('success' => true, 'message' => $message, 'transactionId' => $result['transactionId']);
            }
            $message = "fxmb withdrawal response failed. [".$result['status']."]: ".$result['message'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['message']){
            $message = 'fxmb withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "fxmb decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================fxmb raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================fxmb json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================fxmb callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================fxmb callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('fxmb withdrawal success: trade ID [%s]', $params['transactionId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('fxmb withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('fxmb withdrawal payment was not successful: [%s]', $params['message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'status', 'transactionId','hash'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================fxmb withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================fxmb withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================fxmb withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        // if ($fields['bizAmt'] != $order['amount']) {
        //     $this->writePaymentErrorLog('=========================fxmb withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
        //     return false;
        // }

        // if ($fields['orderNo'] != $order['transactionCode']) {
        //     $this->writePaymentErrorLog('=========================fxmb withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
        //     return false;
        // }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
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
            $this->utils->debug_log("==================getting fxmb bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                // '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                // '2' => array('name' => '招商银行', 'code' => 'CMBCHINA'),
                // '3' => array('name' => '建设银行', 'code' => 'CCB'),
                // '4' => array('name' => '农业银行', 'code' => 'ABC'),
                // '5' => array('name' => '交通银行', 'code' => 'BOCO'),
                // '6' => array('name' => '中国银行', 'code' => 'BOC'),
                // // '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                // '8' => array('name' => '广发银行', 'code' => 'CGB'),
                // '10' => array('name' => '中信银行', 'code' => 'ECITIC'),
                // '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                // '12' => array('name' => '中国邮政银行', 'code' => 'POST'),
                // '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                // '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                // '15' => array('name' => '平安银行', 'code' => 'PINGANBANK'),
                // //'17' => array('name' => '广州银行', 'code' => 'GZCB'),
                // //'18' => array('name' => '南京银行', 'code' => 'NJCB'),
                // '20' => array('name' => '光大银行', 'code' => 'CEB'),
                // '24' => array('name' => '浦发银行', 'code' => 'SPDB'),
                // '25' => array('name' => '北京银行', 'code' => 'BCCB'),
                // '25' => array('name' => '上海银行', 'code' => 'SHB'),
                // '26' => array('name' => '苏州银行', 'code' => 'BSZ'),
                // '27' => array('name' => '桂林银行', 'code' => 'GUILINBANK'),
                // '28' => array('name' => '广西农村信用社', 'code' => 'GX966888'),
                // '29' => array('name' => '郑州银行', 'code' => 'ZZBANK'),
                // '30' => array('name' => '四川天府銀行', 'code' => 'TFB'),
                // '31' => array('name' => '宁波銀行', 'code' => 'NBCB'),
                // '32' => array('name' => '江蘇銀行', 'code' => 'JSBCHINA'),
                // '33' => array('name' => '浙江泰隆商业银行', 'code' => 'ZJTLCB'),
            );
            $this->utils->debug_log("=======================getting fxmb bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr, '&').$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($data) {
        ksort($data);
        $signStr = '';
        foreach ($data as $key => $value) {
            if($key == 'hash'){
                continue;
            }elseif($key == 'amount'){
                $value = number_format($value, 2, '.', '');
            }
            $signStr .= "$key=$value&";
        }
        $sign = $this->encrypt(rtrim($signStr, '&'));

        if($data['hash'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function fixKey($key) {

        if (strlen($key) < 16) {
            return str_pad("$key", 16, "0");
        }

        if (strlen($key) > 16) {
            //truncate to 16 bytes
            return substr($key, 0, 16);
        }

        return $key;
    }

    public function encrypt($data) {
        $key = $this->getSystemInfo('encryptKey');
        $salt = $this->getSystemInfo('salt');

        $encodedEncryptedData = base64_encode(openssl_encrypt($data, 'aes-128-cbc', $this->fixKey($key), OPENSSL_RAW_DATA, $salt));
        $encodedIV = base64_encode($salt);
        $encryptedPayload = $encodedEncryptedData.":".$encodedIV;

        return $encryptedPayload;
    }

}