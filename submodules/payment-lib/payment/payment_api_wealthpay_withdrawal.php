<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wealthpay.php';

/**
 * WEALTHPAY
 * http://merchant.topasianpg.co
 *
 * * WEALTHPAY_WITHDRAWAL_PAYMENT_API, ID: 5742
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.wealthpay.asia/merchant/withdrawal
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wealthpay_withdrawal extends Abstract_payment_api_wealthpay {

    public function getPlatformCode() {
        return WEALTHPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wealthpay_withdrawal';
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

        $this->CI->utils->debug_log('======================================wealthpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================wealthpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================wealthpay submitWithdrawRequest decoded Result', $decodedResult);

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
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================wealthpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
        } else {
            $province = 'none';
            $city = 'none';
            $bankBranch = 'none';
        }

        $province = empty($province) ? "none" : $province;
        $city = empty($city) ? "none" : $city;
        $bankBranch = empty($bankBranch) ? "none" : $bankBranch;

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $playerId = $order['playerId'];

        $params = array();
        $params['MerchantCode']  = $this->getSystemInfo("account");
        $params['TransactionID'] = $transId;
        $params['MemberID']      = $playerId;
        $params['CurrencyCode']  = $this->getSystemInfo('currency', self::CURRENCY);
        $params['BankCode']  = $bankCode;
        $params['ToAccountNumber'] = $accNum;
        $params['ToAccountName'] = $name;
        $params['ToProvince'] = $province;
        $params['ToCity'] = $city;
        $params['ToBranch'] = $bankBranch;
        $params['Amount']   = $this->convertAmountToCurrency($amount);
        $params['Note']   = 'withdrawal';
        $params['CallbackURL']     = $this->getNotifyUrl($transId);
        $params['ClientIP']        = $this->getClientIP();
        $params['TransactionTime'] = date("Y-m-d h:i:s");
        $params['Signature']       = $this->sign($params);


        $this->CI->utils->debug_log('=========================wealthpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================wealthpay json_decode result", $result);

        $respCode = $result['status'];
        $resultMsg = $result['message'];
        $this->utils->debug_log("=========================wealthpay withdrawal resultMsg", $resultMsg);

        if($respCode == self::ORDER_STATUS_CREATED) {
            $message = "wealthpay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                $this->utils->error_log("========================wealthpay return UNKNOWN ERROR!");
                $resultMsg = "Unknow Error";
            }

            $message = "wealthpay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================wealthpay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================wealthpay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================wealthpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================wealthpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['Status'] == self::ORDER_STATUS_SUCCESS) {
            $msg = sprintf('wealthpay withdrawal success: trade ID [%s]', $params['ID']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
            $msg = sprintf('wealthpay withdrawal failed: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }
        else {
            $msg = sprintf('wealthpay withdrawal payment was not successful: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'MerchantCode', 'TransactionID', 'Amount', 'ID', 'Status', 'Signature'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================wealthpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================wealthpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['Amount'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================wealthpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['TransactionID'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================wealthpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

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
            $this->utils->debug_log("==================getting wealthpay bank info from extra_info: ", $bankInfo);
        } else  {
            $currency  = $this->getSystemInfo('currency',self::CURRENCY);
            switch ($currency) {
                case 'THB':
                    $bankInfo = array(
                        "28" =>  array('name' => "Bangkok Bank", 'code' => 'BBL'),
                        "29" =>  array('name' => "Krung Thai Bank", 'code' => 'KTB'),
                        "30" =>  array('name' => "Siam Commercial Bank", 'code' => 'SCB'),
                        "31" =>  array('name' => "Karsikorn Bank (K-Bank)", 'code' => 'KBANK'),
                        "32" =>  array('name' => "TMB Bank Public Company Limited", 'code' => 'TMB'),
                        "33" =>  array('name' => "Bank of Ayudhya (Krungsri)", 'code' => 'BAY'),
                        "34" =>  array('name' => "CIMB Thai", 'code' => 'CIMBT'),
                        "37" =>  array('name' => "Kiatnakin Bank", 'code' => 'KKB'),
                        "43" =>  array('name' => "Government Savings Bank", 'code' => 'GSB'),
                    );
                    break;
                case 'IDR':
                    $bankInfo = array(
                        "28" =>  array('name' => "BANK MANDIRI (PERSERO)", 'code' => 'Mandiri'),
                        "38" =>  array('name' => "BANK CAPITAL", 'code' => 'BCA'),
                        "62" =>  array('name' => "BANK NEGARA INDONESIA", 'code' => 'BNI'),
                    );
                    break;
                default:
                    return array();
                    break;
            }
            $this->utils->debug_log("=======================getting wealthpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(hash('sha256', $signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'Signature') {
                continue;
            }
            $signStr .= $value;
        }
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $signStr =
            $params['MerchantCode'].$params['CurrencyCode'].$params['TransactionID'].$params['Amount'].
            $params['TransactionTime'].$params['ID'].$params['Status'].$params['Message'].
            $this->getSystemInfo('key');
        $sign = strtoupper(hash('sha256', $signStr));
        if($params['Signature'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}