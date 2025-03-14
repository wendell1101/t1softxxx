<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paymero.php';

/**
 * PAYMERO
 *
 * * PAYMERO_WITHDRAWAL_PAYMENT_API, ID: 5723
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://api.wellpays.com/rsa/withdraw
 * * Key: ## API Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paymero_withdrawal extends Abstract_payment_api_paymero {

    public function getPlatformCode() {
        return PAYMERO_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paymero_withdrawal';
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
            $province    = empty($playerBankDetails['province'])    ? "无" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "无" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
        } else {
            $bankBranch  = '无';
            $province    = '无';
            $city        = '无';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bankInfo = $this->getBankInfo();
        $params = array();
        $params['amount']           = $this->convertAmountToCurrency($amount,$order['dwDateTime']);
        $params['orderId']          = $transId;
        $params['notifyUrl']        = $this->getNotifyUrl($transId);
        $params['currency']         = $this->getSystemInfo('currency','CNY');
        $params['productName']      = 'withdrawal';
        $params['bankName']         = $bankInfo[$bank]['code'];
        $params['subBankName']      = $bankBranch;
        $params['accountNumber']    = $accNum;
        $params['beneficiaryName']  = $name;
        $params['bankProvince']     = $province;
        $params['bankCity']         = $city;
        $params['accountType']      = '个人'; //Corporate or Personal in Chinese characters
        $params['cardType']         = '储蓄卡'; //Debit or Credit Card in Chinese characters
        $params['PMID']             = $this->getSystemInfo('account');

        if ($this->getSystemInfo('unset_pmid_params')) {
            unset($params['PMID']);
        }

        $this->CI->utils->debug_log('=====================paymero generatePaymentUrlForm params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================paymero withdrawal bank whose bankTypeId=[$bank] is not supported by paymero");
            return array('success' => false, 'message' => 'Bank not supported by paymero');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        list($content, $response_result) = $this->processCurl($params, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================paymero submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================paymero json_decode result", $result);

        if(isset($result['status'])) {
            if($result['status'] == self::RESULT_CODE_SUCCESS) {
                $message = "paymero withdrawal response successful, transId: ". $result['data']['orderId'];
                return array('success' => true, 'message' => $message);
            }
            $message = "paymero withdrawal response failed. [".$result['status']."]: ".$result['message'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['message']){
            $message = 'paymero withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "paymero decoded fail.");
    }


    public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['transactionStatus'] == self::CALLBACK_SUCCESS) {
            $this->utils->debug_log('=========================paymero withdrawal payment was successful: trade ID [%s]', $params['orderId']);

            $msg = sprintf('paymero withdrawal was successful: trade ID [%s]',$params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else if($params['transactionStatus'] == self::CALLBACK_FAIL){
            $this->utils->debug_log('==========================paymero withdrawal payment was failed: trade ID [%s]',$params['merchant_order_no']);

            $msg = sprintf('paymero withdrawal was failed: trade ID [%s]',$params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf('paymero withdrawal payment was not successful  trade ID [%s] ',$params['orderId']);
            $this->debug_log($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $this->utils->debug_log('==========================paymero withdrawal checkCallbackOrder fields', $fields);

        $requiredFields = array(
            'amount', 'currency', 'orderId', 'transactionStatus'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================paymero withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $amount = $this->convertAmountToCurrency($order['amount'], $order['dwDateTime']);
        if ($fields['amount'] != $amount) {
            $this->writePaymentErrorLog('=========================paymero withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $decrypted);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================paymero checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $decrypted);
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
            $this->utils->debug_log("=========================paymero bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1'  => array('name' => '工商银行', 'code' => 'ICBC'),
                '4'  => array('label' => '中国农业银行' , 'value' => 'ABC'),
                '6'  => array('label' => '中国银行' , 'value' => 'BOC'),
                '8'  => array('label' => '广发银行' , 'value' => 'GDB'),
                '11' => array('label' => '民生银行' , 'value' => 'CMBC'),
                '12' => array('label' => '邮储银行' , 'value' => 'PSBC'),
            );
            $this->utils->debug_log("=========================paymero bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}