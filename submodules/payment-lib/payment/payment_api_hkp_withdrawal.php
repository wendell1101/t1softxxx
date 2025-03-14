<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hkp.php';

/**
 * HKP取款
 *
 * * HKP_WITHDRAWAL_PAYMENT_API, ID: 6086
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
class Payment_api_hkp_withdrawal extends abstract_payment_api_hkp {

	public function getPlatformCode() {
		return HKP_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hkp_withdrawal';
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

        $this->utils->debug_log("=================hkp withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        $bankInfo = $this->getBankInfo();
        $bankName = $bankInfo[$bank]['name'];
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId, $bankInfo[$bank]['code']);

		$params = array();
        $params['merchantId']      = $this->getSystemInfo("account");
        $params['merchantOrderNo'] = $transId;
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['accountName']     = $playerInfo['firstName'].$playerInfo['lastName'];
        $params['currency']        = $this->getSystemInfo('currency','BRL');
        $params['accountType']     = $bankName;
        $params['accountNo']       = $playerInfo['pixAccount'];
        $params['callback']        = $this->getNotifyUrl($transId);
        $params['cpf']             = "CPF";
        $params['sign']            = $this->sign($params);

		$this->CI->utils->debug_log('========================hkp withdrawal paramStr before sign', $params);
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

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================hkppay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by hkppay");
            return array('success' => false, 'message' => 'Bank not supported by hkppay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);


        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('=====================================hkp submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('=====================================hkp submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('=====================================hkp submitWithdrawRequest decoded Result', $decodedResult);
        
        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("========================hkp json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['success']) && $result['success'] == self::REPONSE_CODE_SUCCESS ){
                return array('success' => true, 'message' => 'hkp withdrawal request successful.');
            }else if(isset($result['errorCode']) && !empty($result['errorCode'])){
                $errorMsg = $result['message'];
                return array('success' => false, 'message' => $errorMsg);
            }else{
                return array('success' => false, 'message' => 'HKP withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'HKP withdrawal exist errors');
        }
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
            $this->CI->utils->debug_log("====================hkp withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("====================hkp withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::DEPOSIT_CALLBACK) {
            $msg = sprintf('HKP withdrawal was successful: trade ID [%s]', $params['merchantId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RESPONSE_MESSAGE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('HKP withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
            $result['return_error_json'] = array("success");
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('merchantId','merchantOrderNo', 'orderNo', 'amount', 'status','currency','sign');

        $this->CI->utils->debug_log("========================hkp checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================hkp withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
           $this->writePaymentErrorLog('====================hkp withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['merchantOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("=====================hkp withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("=====================hkp withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }

    private function getBankInfo(){
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
            $this->utils->debug_log("==================BCppay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '47' => array('name' => 'PIX_CPF', 'code' => 'CPF'),
                '48' => array('name' => 'PIX_EMAIL', 'code' => 'EMAIL'),
                '49' => array('name' => 'PIX_PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================BCppay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}
