<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay668.php';

/**
 * PAY668取款
 *
 * * PAY668_WITHDRAWAL_PAYMENT_API, ID: 6086
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://portal.hkdintlpay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay668_withdrawal extends abstract_payment_api_pay668 {

	public function getPlatformCode() {
		return PAY668_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pay668_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $targetTime=$this->getSystemInfo("targetTime");

        // $this->utils->debug_log("=================pay668 withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bank=$this->withdrawal_bank_info($bank);

        $firstname  = "no firstName";
        $lastname   = "no lastName";
        $playerInfo=$this->getPlayerInfoByTransactionCode($transId, $bank['code']);

		$params = array();
        $params['merchno']        = $this->getSystemInfo("account");
        $params['orderId']        = $transId;
        $params['amount']         = $this->convertAmountToCurrency($amount);
        $params['requestCurrency']        = $this->getSystemInfo("currency");

        $params['tradeType']      = $this->getSystemInfo("tradeType");
        $params['account']        = !empty($playerInfo['firstname'])?$playerInfo['firstname'].$playerInfo['lastname']:$firstname.$lastname;
        $params['cardNo']         = $playerInfo['pixAccount'];
        $params['bankName']       = "PIX";
        $params['depositBank']    = $bank['code'];

        $params['asyncUrl']       = $this->getNotifyUrl($transId);
        $params['timestamp']      = date('YmdHis');

        if($targetTime){
            $currentDateTime = new DateTime();
            $currentDateTime->setTimezone(new DateTimeZone($targetTime));
            $params['timestamp'] = $currentDateTime->format('YmdHis');
        }

        $params['cashType']       = $this->getSystemInfo("cashType");
        $params['requestCurrency']   = $this->getSystemInfo("requestCurrency"); //
        $params['apiVersion']        = $this->getSystemInfo("apiVersion"); //
        $params['sign']              = $this->sign($params);
		$this->CI->utils->debug_log('========================pay668 withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        $this->CI->load->model('playerbankdetails');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $bankInfo=$this->withdrawal_bank_info($bank);
        if(empty($bankInfo)) {
            $this->utils->error_log("========================pay668 submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported");
            return array('success' => false, 'message' => 'Bank not supported by pay668');
        }

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $playerId = $playerBankDetails['playerId'];
        $validationResults = $this->checkWalletaccountPlayerId($playerId, $transId);
        if (!$validationResults['success']) {
            $this->utils->debug_log("==========pay668", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        //params has fail
        if(isset($params['success'])&&!$params['success']){
            $result['message'] = $params['message'];
            return $result;
        }
        
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);


        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('=====================================pay668 submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('=====================================pay668 submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('=====================================pay668 submitWithdrawRequest decoded Result', $decodedResult);
        
        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("========================pay668 json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['responseContent'])&&($result['responseContent']['code']==self::RETURN_SUCCESS_CODE)){
                return array('success' => true, 'message' => 'pay668 withdrawal request successful.');
            }else{
                return array('success' => true, 'message' => $result['responseContent']['msg']);
            }
        }
        return array('success' => false, 'message' => 'PAY668 withdrawal exist errors');
    }

	public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("====================pay668 withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("====================pay668 withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::WITHDRAWAL_SUCCESS_CODE) {
            $msg = sprintf('PAY668 withdrawal was successful: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RESPONSE_MESSAGE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('PAY668 withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('orderId','merchno', 'cardNo', 'depositBank', 'cashType','status','sign',"amount");

        $this->CI->utils->debug_log("========================pay668 checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================pay668 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
           $this->writePaymentErrorLog('====================pay668 withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("=====================pay668 withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("=====================pay668 withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }
    private function withdrawal_bank_info($bankNo){
        $bankcode='';
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if($system_bank_type_id == $bankNo) {
                    $bankcode = $bankInfoArr[$system_bank_type_id];
                    break;
                }
            }
        }
        return $bankcode;
    }
}
