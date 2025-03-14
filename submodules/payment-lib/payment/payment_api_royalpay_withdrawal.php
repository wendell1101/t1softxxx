<?php
require_once dirname(__FILE__) . '/abstract_payment_api_royalpay.php';

/**
 * ROYALPAY_WITHDRAWAL
 *
 * * ROYALPAY_WITHDRAWAL_PAYMENT_API, ID: 5884
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.bee-earning.com/order/order/payout/submit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_royalpay_withdrawal extends Abstract_payment_api_royalpay {

    const RESPONSE_ORDER_SUCCESS = 'processing';
    const CALLBACK_STATUS_SUCCESS = '1';
    const MODE = 'IMPS';
    const CHARGETYPE = '1';

    public function getPlatformCode() {
        return ROYALPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'royalpay_withdrawal';
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
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================royalpay withdrawal bank whose bankTypeId=[$bank] is not supported by royalpay");
            return array('success' => false, 'message' => 'Bank not supported by royalpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        $this->_custom_curl_header = array('Content-Type:application/json');

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================royalpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================royalpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================royalpay submitWithdrawRequest decoded Result', $decodedResult);

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
        $this->utils->debug_log("===============================royalpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
        } else {
            $province = 'none';
            $city = 'none';
            $bankBranch = 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $player = $this->CI->player_model->getPlayerDetailArrayById($order['playerId']);
        $playerDetails = $this->CI->player_model->getPlayerDetails($order['playerId']);

        $firstname = (!empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : '';
        $lastname  = (!empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : '';
        $emailAddr = (!empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : '';
        $phone     = (!empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';

        $params = array();

        $params['amount']     = strval($this->convertAmountToCurrency($amount));
        $params['channelId']      = strval($this->getSystemInfo('account'));
        $params['channelOid'] = strval($transId);
        $params['fundAccount']['accountType']      = strval($this->getSystemInfo('bank_account','bank_account'));; 
        $params['fundAccount']['bankAccount']['accountNumber'] = strval($accNum);
        $params['fundAccount']['bankAccount']['ifsc'] = strval($bankBranch);
        $params['fundAccount']['bankAccount']['name'] = strval($order['bankAccountFullName']);
        // $params['fundAccount']['card']['name'] = ; 
        // $params['fundAccount']['card']['number'] = ; 

        $params['fundAccount']['contact']['contact'] = strval($player['contactNumber']);
        $params['fundAccount']['contact']['email'] = strval($emailAddr); 
        $params['fundAccount']['contact']['name'] = strval($firstname.$lastname); 
        $params['fundAccount']['contact']['referenceId'] = strval($order['playerId']); 
        $params['fundAccount']['contact']['type'] = strval($order['playerId']); 

        // $params['fundAccount']['vpa']['address'] = ; 
        // $params['fundAccount']['wallet']['email'] = ; 
        // $params['fundAccount']['wallet']['phoneNo'] = ; 

        $params['mode']            = strval($this->getSystemInfo('mode', self::MODE));
        $params['notifyUrl']       = strval($this->getNotifyUrl($transId));
        $params['sign']            = strval($this->sign($params));
        $params['timestamp']       = (int)$this->getMillisecond();


        // $params['payeeBank']       = strval($bankCode);
        // $params['payeeBankCode']   = strval($bankBranch);
        // $params['payeeAcc']        = strval($accNum);
        // $params['payeeName']       = strval($order['bankAccountFullName']);
        // $params['payeePhone']      = strval($player['contactNumber']);
        // $params['currency']        = strval($this->getSystemInfo('currency', self::CURRENCY));
        // $params['chargeType']      = strval($this->getSystemInfo('chargeType', self::CHARGETYPE));
        // $json_params = json_encode($params);
        // $params['parameter']       = strval($this->encrypt($json_params,$this->getSystemInfo('key')));

        $this->CI->utils->debug_log('=========================royalpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================royalpay json_decode result", $result);


        if(isset($result['code'])) {
            if($result['code'] == self::RESULT_CODE_SUCCESS) {
                $returnDesc = $this->getMappingErrorMsg($result['data']['state']);
                $message = "royalpay withdrawal response successful, code:[".$result['code']."]: ".$result['data']['msg'].' state:['.$returnDesc.']';
                return array('success' => true, 'message' => $message);
            }
            $message = "royalpay withdrawal response failed. [".$result['code']."]: ".$result['message'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['message']){
            $message = 'royalpay withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "royalpay decoded fail.");
    }

    private function getMappingErrorMsg($state) {
        $msg = "";
        switch ($state) {
            case 0:
                $msg = "待提现";
                break;
            case 1:
                $msg = "已提现";
                break;
            case 2:
                $msg = "已失效";
                break;      
            case 3:
                $msg = "创建失败";
                break;
        }
        return $msg;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log("=========================royalpay callbackFromServer params", $params, 'transId', $transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================royalpay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================royalpay json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['code'] == self::CALLBACK_CODE_SUCCESS) {
            $msg = sprintf('royalpay withdrawal success: trade ID [%s]', $params['channelOid']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('royalpay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('royalpay withdrawal payment was not successful: [%s]', $params['msg']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'amount', 'channelId', 'channelOid', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================royalpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('======================royalpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_CODE_SUCCESS) {
            $this->writePaymentErrorLog("======================royalpay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $order['amount']) {
            $this->writePaymentErrorLog('======================royalpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['channelOid'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=======================royalpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting royalpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => 'Industrial and Commercial Bank(ICBC)', 'code' => 'ICBC'),
                '2' => array('name' => 'DBI BANK', 'code' => 'IDBIBK'),    
                '3' => array('name' => 'CCB', 'code' => 'CCB'),
                '10' => array('name' => 'CITIC BANK', 'code' => 'CITIC'),
                '33' => array('name' => 'COSMOS BANK (COSMOS)', 'code' => 'COSMOS'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = $params['channelId'].$params['channelOid'].$params['amount'].$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}