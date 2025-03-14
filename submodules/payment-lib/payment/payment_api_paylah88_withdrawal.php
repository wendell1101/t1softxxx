<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paylah88.php';

/**
 * paylah88
 *
 *
 * * PAYLAH88_WITHDRAWAL_PAYMENT_API, ID: 5763
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://service.paylah88test.biz/MerchantPayout/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paylah88_withdrawal extends Abstract_payment_api_paylah88 {

    public function getPlatformCode() {
        return PAYLAH88_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paylah88_withdrawal';
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

        $this->CI->utils->debug_log('======================================paylah88 submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================paylah88 submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================paylah88 submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url').$this->getSystemInfo("account");
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================paylah88 Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
        $params['ClientIP']             = $this->getClientIP();
        $params['ReturnURI']            = $this->getNotifyUrl($transId);
        $params['MerchantCode']         = $this->getSystemInfo("account");
        $params['TransactionID']        = $transId;
        $params['CurrencyCode']         = $this->getSystemInfo('currency', self::CURRENCY);
        $params['MemberCode']           = $playerId;
        $params['Amount']               = $this->convertAmountToCurrency($amount);
        $params['TransactionDateTime']  = date("Y-m-d h:i:sA");
        $params['BankCode']             = $bankCode;
        $params['toBankAccountName']    = $name;
        $params['toBankAccountNumber'] = $accNum;
        $params['toProvince']           = $province;
        $params['toCity']               = $city;
        $params['toBranch']             = $bankBranch;
        $params['Key']                  = $this->sign($params);

        $this->CI->utils->debug_log('=========================paylah88 getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = $this->parseResultXML($resultString);
        $this->utils->debug_log("=========================paylah88 json_decode result", $result);

        $respCode = $result['statusCode'];
        $resultMsg = $result['message'];
        $this->utils->debug_log("=========================paylah88 withdrawal resultMsg", $resultMsg);

        if($respCode == self::ORDER_STATUS_SUCCESS) {
            $message = "paylah88 request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                $this->utils->error_log("========================paylah88 return UNKNOWN ERROR!");
                $resultMsg = "Unknow Error";
            }

            $message = "paylah88 withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function parseResultXML($resultXml) {
        $result = NULL;
        $obj=simplexml_load_string($resultXml);
        $arr=$this->CI->utils->xmlToArray($obj);
        $this->CI->utils->debug_log(' =========================paylah88 parseResultXML', $arr);
        $result = $arr;

        return $result;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================paylah88 raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================paylah88 json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================paylah88 callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================paylah88 callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['Status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('paylah88 withdrawal success: trade ID [%s]', $params['ID']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if ($params['Status'] != self::CALLBACK_STATUS_FAIL) {
            $msg = sprintf('paylah88 withdrawal failed: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }
        else {
            $msg = sprintf('paylah88 withdrawal payment was not successful: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'MerchantCode', 'TransactionID', 'Amount', 'ID', 'Status', 'Key'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================paylah88 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================paylah88 withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['Amount'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================paylah88 withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['TransactionID'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================paylah88 withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting paylah88 bank info from extra_info: ", $bankInfo);
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
                        "28" =>  array('name' => "BANK MANDIRI (PERSERO)", 'code' => 'MDR'),
                        "29" =>  array('name' => "BANK RAKYAT INDONESIA AGRONIAGA", 'code' => 'BRI'),
                        "38" =>  array('name' => "BANK CAPITAL", 'code' => 'BCA'),
                        "39" =>  array('name' => "BANK CIMB NIAGA", 'code' => 'CIMBN'),
                        "62" =>  array('name' => "BANK NEGARA INDONESIA", 'code' => 'BNI'),
                    );
                    break;
                default:
                    return array();
                    break;
            }
            $this->utils->debug_log("=======================getting paylah88 bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $signDateTime = date("YmdHis",strtotime($params['TransactionDateTime']));
        $signStr = $params['MerchantCode'].$params['TransactionID'].$params['MemberCode'].$params['Amount'].$params['CurrencyCode'].$signDateTime.$params['ToBankAccountNumber'].$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $params['MerchantCode'].$params['TransactionID'].$params['MemberCode'].$params['Amount'].$params['CurrencyCode'].$params['Status'].$this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        if($params['Key'] == $sign){
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