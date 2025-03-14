<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sevpay.php';
/**
 * SEVPAY
 * * http://merchant.777office.com/
 *
 * * SEVPAY_WITHDRAWAL_PAYMENT_API, ID: 5057
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.paynow777.com/MerchantPayout
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sevpay_withdrawal extends Abstract_payment_api_sevpay {
    const RETURN_STATUS_SUCCESS = '000';
    const RETURN_STATUS_FAILED  = '001';

    const CALLBACK_STATUS_SUCCESS = '000';
    const CALLBACK_STATUS_FAILED  = '001';

    const RETURN_SUCCESS_CODE = "true";

    public function getPlatformCode() {
        return SEVPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sevpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}


    # Note: to avoid breaking current APIs, these abstract methods are not marked abstract
    # APIs with withdraw function need to implement these methods
    ## This function returns the URL to submit withdraw request to
    public function getWithdrawUrl() {
        return $this->getSystemInfo('url').'/'.$this->getSystemInfo('account');
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        if(isset($params['success'])) {
            if($params['success'] == false) {
                $result['message'] = $params['message'];
                $this->utils->debug_log($result);
                return $result;
            }
        }

        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================sevpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================sevpay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================sevpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    ## This function returns the params to be submitted to the withdraw URL
    ## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this AP
    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================sevpay withdrawal bank whose bankTypeId=[$bank] is not supported by sevpay");
            return array('success' => false, 'message' => 'Bank not supported by sevpay');
            $bank = '无';
        }
        $bankCode = $bankInfo[$bank];

        # look up bank detail
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
            $province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
            $city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
        } else {
            $bankBranch = '无';
            $province = '无';
            $city = '无';
        }

        # Get player contact number
        $order  = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $player = $this->CI->player->getPlayerById($order['playerId']);
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $order['dwDateTime']);

        $params = array();
        $params['MerchantCode']        = $this->getSystemInfo('account');
        $params['CurrencyCode']        = $this->getSystemInfo('currency', 'CNY'); #CNY IDR
        $params['TransactionID']       = $transId;
        $params['Amount']              = $this->convertAmountToCurrency($amount);
        $params['Membercode']          = $player['username'];
        $params['TransactionDateTime'] = $datetime->format('Y-m-d h:i:sA');
        $params['timestamp']           = $datetime->format('YmdHis');
        $params['ClientIP']            = $this->getClientIP();
        $params['ReturnURI']           = $this->getNotifyUrl($transId);
        $params['BankCode']            = $bankCode;
        $params['toBankAccountName']   = $name;
        $params['toBankAccountNumber'] = $accNum;
        $params['toProvince']          = $province;
        $params['toCity']              = $city;
        $params['toBranch']            = $bankBranch;
        $params['Key']                 = $this->sign($params);
        unset($params['timestamp']);

        $this->CI->utils->debug_log('======================================sevpay getWithdrawParams params: ', $params);
        return $params;
    }

    ## This function takes in the return value of the URL and translate it to the following structure
    ## array('success' => false, 'message' => 'Error message')
    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $this->utils->debug_log("=========================sevpay withdrawal resultString", $resultString);
        $result = $this->loadXmlResp($resultString);
        $this->utils->debug_log("=========================sevpay withdrawal decoded result", $result);
        $message = "Sevpay withdrawal decode fail.";

        $returnCode = $result['statusCode'];
        $returnDesc = $result['message'];
        if($returnCode == self::RETURN_STATUS_SUCCESS) {
            $message = "Sevpay withdrawal response successful";
            return array('success' => true, 'message' => $message);
        }
        else if($returnCode == self::RETURN_STATUS_FAILED){
            $message = "Sevpay withdrawal response failed. [".$returnCode."]: ".$returnDesc;
        }
        return array('success' => false, 'message' => $message);
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters(&$flds) {
        if (isset($flds['TransID'])) {
            $transId = $flds['TransID'];
            $flds['Verification'] = true;

            $this->CI->utils->debug_log('================sevpay getOrderIdFromParameters transId: $transId');
            return $transId;
        }
        else {
            $this->CI->utils->debug_log('================sevpay getOrderIdFromParameters cannot get TransID', $flds);
            return;
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        #for Payout Verification (Doc 4.3)
        if(isset($flds['Verification'])){
            $result = array('success' => false, 'message' => 'true');
        }
        else{
            $result = array('success' => false, 'message' => 'Payment failed');
            $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
            $this->CI->utils->debug_log("=========================sevpay checkCallback params", $params);


            if (!$this->checkCallbackOrder($order, $params)) {
                return $result;
            }

            if ($params['Status'] == self::CALLBACK_STATUS_SUCCESS) {
                $msg = sprintf('Sevpay withdrawal Payment was successful: trade ID [%s]', $params['OrderSno']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            } else if($params['Status'] == self::CALLBACK_STATUS_FAILED){
                $msg = sprintf('Sevpay withdrawal payment was failed. [%s]: '.$params['Message'], $params['Status']);
                $this->CI->utils->debug_log($msg, $params);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
                $result['message'] = $msg;
            } else {
                $msg = sprintf('Sevpay withdrawal payment was not successful. [%s]: '.$params['Message'], $params['Status']);
                $this->CI->utils->debug_log($msg, $params);
                $result['message'] = $msg;
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'MerchantCode', 'TransactionID', 'CurrencyCode', 'Amount', 'TransactionDatetime', 'MemberCode', 'ID', 'Status', 'Message', 'Key'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================sevpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================sevpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['Amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================sevpay checkCallbackOrder Payment amount is wrong, expected [". $order['amount'] ."]", $fields);
            return false;
        }

        if ($fields['TransactionID'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================sevpay withdrawal checkCallbackOrder order IDs do not match, expected [". $order['transactionCode'] ."]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("sevpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting SEVPAY bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1'   => 'ICBC',
                '2'   => 'CMB',
                '3'   => 'CCB',
                '4'   => 'ABC',
                '5'   => 'COMM',
                '6'   => 'BOC',
                '7'   => 'SDB',
                '8'   => 'GDB',
                '11'  => 'CMBC',
                '12'  => 'PSBC',
                '13'  => 'CIB',
                '14'  => 'HXB',
                '17'  => 'GZB',
                '20'  => 'CEB',
                '26'  => 'GDB',
                '27'  => 'SPDB',
                '29'  => 'BOB',
                '30'  => 'TCCB',
                '31'  => 'BOS',
                '44'  => 'BOD',
                '47'  => 'HDCB',
                '48'  => 'HZB',
                '49'  => 'HEBB',
                '52'  => 'BOIMC',
                '61'  => 'LCCB',
                '86'  => 'CBHB',
                '89'  => 'CZB',
                '117' => 'JSHB'
            );
            $this->utils->debug_log("=======================getting SEVPAY bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    public function sign($params) {
        $signStr =
            $params['MerchantCode'].$params['TransactionID'].$params['Membercode'].$params['Amount'].
            $params['CurrencyCode'].$params['timestamp'].$params['toBankAccountNumber'].
            $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
    
        return $sign;
    }

    private function validateSign($params) {
        $signStr =
            $params['MerchantCode'].$params['TransactionID'].$params['MemberCode'].$this->convertAmountToCurrency($params['Amount']).
            $params['CurrencyCode'].$params['Status'].
            $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        if($params['Key'] == $sign){
            return true;
        }
        else{
           
            return false;
        }
    }

    # -- XML --
    public function loadXmlResp($returnQueryString) {
        $xml_object = simplexml_load_string($returnQueryString);
        $xml_array = $this->object2array($xml_object);
        return $xml_array;
    }

    public function object2array($object) {
        return @json_decode(@json_encode($object),1);
    }
}