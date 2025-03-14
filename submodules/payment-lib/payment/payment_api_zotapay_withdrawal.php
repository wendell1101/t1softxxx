<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zotapay.php';

/**
 * ZOTAPAY
 *
 *
 * * ZOTAPAY_WITHDRAWAL_PAYMENT_API, ID: 406
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://mg-sandbox.zotapay.com/api/v1/payout/request/
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * >    "EndpointID": "",
 * >    "orderCurrency": "## USD, CNY, or JPY. Fill one of these 3 words. ##",
 * >    "customerBankAccountNumber": "",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zotapay_withdrawal extends Abstract_payment_api_zotapay {

    const CALLBACK_STATUS_SUCCESS  = 'APPROVED';
    const CALLBACK_STATUS_DECLINED = 'DECLINED';
    const CALLBACK_STATUS_ERROR    = 'ERROR';

    public function getPlatformCode() {
        return ZOTAPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'zotapay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url'). $this->getSystemInfo('EndpointID');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bankInfo = $this->getBankInfo();

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province    = empty($playerBankDetails['province'])    ? "none" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "none" : $playerBankDetails['city'];
            $branch      = empty($playerBankDetails['branch'])      ? "none" : $playerBankDetails['branch'];
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "none" : $playerBankDetails['bankAddress'];
        } else {
            $province    = 'none';
            $city        = 'none';
            $branch      = 'none';
            $bankAddress = 'none';
        }

        $playerDetails = $this->CI->player_model->getPlayerDetails($order['playerId']);
        $firstname = (!empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'none';
        $lastname  = (!empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'none';
        $phone     = (!empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $email     = (!empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@nothing.com';

        $params = array();
        $params['merchantOrderID']           = $transId;
        $params['merchantOrderDesc']         = 'withdrawal';
        $params['orderAmount']               = $this->convertAmountToCurrency($amount, $order['dwDateTime']);
        $params['orderCurrency']             = $this->getSystemInfo('orderCurrency');
        $params['customerEmail']             = $email;
        $params['customerFirstName']         = $firstname;
        $params['customerLastName']          = $lastname;
        $params['customerPhone']             = $phone;
        $params['customerIP']                = $this->getClientIp();
        $params['customerBankCode']          = $bankInfo[$bank]['code'];
        $params['customerBankAccountNumber'] = $this->getSystemInfo('customerBankAccountNumber', $accNum);
        $params['customerBankAccountName']   = $name;
        $params["customerBankBranch"]        = $branch;
        $params['customerBankAddress']       = $bankAddress;
        $params['customerBankZipCode']       = '000';
        $params['customerBankRoutingNumber'] = '000';
        $params['customerBankProvince']      = $province;
        $params['customerBankArea']          = $city;
        $params['callbackUrl']               = $this->getNotifyUrl($transId);
        $params['customParam']               = '';
        $params['signature']                 = $this->sign($params);

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================zotapay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by zotapay");
            return array('success' => false, 'message' => 'Bank not supported by Zotapay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        $this->_custom_curl_header = ["Content-Type: application/json"];
        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

    public function decodeResult($response) {
        $decodeResult = json_decode($response, true);

        if($decodeResult['code'] == self::RESPONSE_SUCCESS) {
            $message = 'Zotapay withdrawal request success.';
            return array('success' => true, 'message' => $message);
        }
        else {
            if(empty($decodeResult['code'])) {
                $message = "未知错误";
            }else {
                $message = "Zotapay withdrawal request failed: [".$decodeResult['code']."] ".$decodeResult['data']['errorMessage'];
            }
            return array('success' => false, 'message' => $message);
        }

    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $this->utils->debug_log("=========================zotapay callbackFromServer params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================zotapay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================zotapay json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('Zotapay withdrawal payment was successful: orderid [%s]', $params['orderID']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = $msg;
            $result['success'] = true;
        } else {
            $msg = sprintf('Zotapay withdrawal payment was not successful: orderid [%s], status [%s]', $params['orderID'], $params['status']);
            $errorMessage = empty($params['errorMessage'])? 'Unknown': $params['errorMessage'];

            if ($params['status'] == self::CALLBACK_STATUS_DECLINED) {
                $msg = sprintf('Zotapay withdrawal payment was declined: orderid [%s]. Error Message: [%s]', $params['orderID'], $errorMessage);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            } elseif ($params['status'] == self::CALLBACK_STATUS_ERROR) {
                $msg = sprintf('Zotapay withdrawal payment was error: orderid [%s]. Error Message: [%s]', $params['orderID'], $errorMessage);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            }

            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'status', 'merchantOrderID', 'amount', 'orderID', 'customerEmail', 'signature'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================zotapay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields["signature"] != $this->verifySign($fields)) {
            $this->writePaymentErrorLog('=========================zotapay withdrawal checkCallback signature Error', $fields);
            return false;
        }


        $amount = $this->convertAmountToCurrency($order['amount'], $order['dwDateTime']);
        if ($fields['amount'] != $amount) {
            $this->writePaymentErrorLog("======================zotapay withdrawal checkCallbackOrder payment amount is wrong, expected [". $order['amount']. "]", $fields['amount']);
            return false;
        }

        if ($fields['merchantOrderID'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================zotapay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

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
            $this->utils->debug_log("=======================getting zotapay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '中国建设银行', 'code' => 'CCB'),
                '4' => array('name' => '中国农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BCOM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PAB'),
                '17' => array('name' => '广州银行', 'code' => 'GZCB'),
                '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '20' => array('name' => '光大银行', 'code' => 'CEB')
            );
            $this->utils->debug_log("=======================getting zotapay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function sign($params) {
        $string = $this->getSystemInfo("EndpointID").$params['merchantOrderID'].$params['orderAmount'].$params['customerEmail'].$params['customerBankAccountNumber'].$this->getSystemInfo("key");
        $sign = openssl_digest($string,'sha256');
        return $sign;
    }

    public function verifySign($params){
        $string = $this->getSystemInfo("EndpointID").$params['orderID'].$params['merchantOrderID'].$params['status'].$params['amount'].$params['customerEmail'].$this->getSystemInfo("key");
        $sign = openssl_digest($string,'sha256');

        return $sign;
    }
}