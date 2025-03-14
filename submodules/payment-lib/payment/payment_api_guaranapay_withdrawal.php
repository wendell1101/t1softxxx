<?php
require_once dirname(__FILE__) . '/abstract_payment_api_guaranapay.php';

/**
 * guaranapay取款
 *
 * * guaranapay_WITHDRAWAL_PAYMENT_API, ID: 5993
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.guaranapay.com/pg/dk/payout/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_guaranapay_withdrawal extends Abstract_payment_api_guaranapay {

	public function getPlatformCode() {
		return GUARANAPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'guaranapay_withdrawal';
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

        $this->utils->debug_log("==================guaranapay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
            $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : 'sample@example.com';
            $pixNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        }

		$params = array();
        $params['version'] = '1.1';
		$params['amount'] = $this->convertAmountToCurrency($amount);
        $params['appId'] = $this->getSystemInfo("account");
        $params['currency'] = $this->getSystemInfo("currency");
        $params['merTransNo'] = $transId;
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
		$params['pmId'] = 'CPF';
		$params['extInfo']['bankCode'] = 'CPF';
        $params['extInfo']['accountNumber'] = $pixNumber;
        $params['extInfo']['accountHolderName'] = $name;
        $params['extInfo']['payeePhone'] = $phone;
        $params['extInfo']['payeeEmail'] = $email;
        $params['extInfo']['payeeAddress'] = $address;
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log('=========================guaranapay withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
        $url = $this->getSystemInfo('url');

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================guaranapay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================guaranapay submitWithdrawRequest decoded Result', $decodedResult);
		return $decodedResult;

	}

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['version']      = '1.1';
        $params['merchantId']   = $this->getSystemInfo("account");
        // $params['tradeNo']      = '';
        $params['merTransNo']   = $transId;
        $params['sign']         = $this->sign($params);

        $url = $this->getSystemInfo('check_withdraw_status_url', 'https://gateway.guaranapay.com/pg/dk/payout/query');

        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $decodedResult = $this->decodeResult($content, true);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================guaranapay checkWithdrawStatus url: ', $url );
        $this->CI->utils->debug_log('======================================guaranapay checkWithdrawStatus decoded Result', $decodedResult);

        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================guaranapay json_decode result", $result);
        $errorMsg = 'unknown error';
        if($queryAPI){
            if(!empty($result) && isset($result)){
                if(!empty($result['data']['resultCode']) && isset($result['data']['resultCode']) && $result['data']['resultCode'] == self::REPONSE_CODE_SUCCESS ){
                    if(!empty($result['data']['tradeStatus']) && isset($result['data']['tradeStatus']) && $result['data']['tradeStatus'] == self::ORDER_STATUS_SUCCESS){
                        $message = sprintf('guaranapay withdrawal payment was successful: trade ID [%s]', $result['data']['merTransNo']);
                        $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $message);
                        return array('success' => true, 'message' => $message);
                    }
                    else if(isset($result['data']['tradeStatus']) && !empty($result['data']['tradeStatus'])){
                        $errorMsg = $result['data']['tradeStatus'];
                        return array('success' => false, 'message' => $errorMsg);
                    }
                }
                else{
                    return array('success' => false, 'message' => 'guaranapay withdrawal exist errors');
                }
            }else{
                return array('success' => false, 'message' => 'guaranapay withdrawal exist errors');
            }
        }else{
            if(!empty($result) && isset($result)){
                if(!empty($result['data']['resultCode']) && isset($result['data']['resultCode']) && $result['data']['resultCode'] == self::REPONSE_CODE_SUCCESS ){
                    return array('success' => true, 'message' => 'guaranapay withdrawal request successful.');
                }else if(isset($result['msg']) && !empty($result['msg'])){
                    $errorMsg = $result['msg'];
                    return array('success' => true, 'message' => $errorMsg);
                }
                else{
                    return array('success' => true, 'message' => 'guaranapay withdrawal exist errors');
                }
            }else{
                return array('success' => false, 'message' => 'guaranapay withdrawal exist errors');
            }
        }
    }

	private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================guaranapay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================guaranapay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['tradeStatus'] == self::ORDER_STATUS_SUCCESS) {
            $msg = sprintf('guaranapay withdrawal was successful: trade ID [%s]', $params['merTransNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else if($params['tradeStatus'] == self::ORDER_STATUS_FAILED){
            $this->utils->debug_log('==========================guaranapay withdrawal payment was failed: trade ID [%s]', $params['merTransNo']);
            $msg = sprintf('guaranapay withdrawal payment was failed: trade ID [%s] ',$params['merTransNo']);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('guaranapay withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
    $requiredFields = array(
            'merTransNo', 'amount', 'sign', 'tradeNo'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================guaranapay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================alogateway withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['merTransNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================guaranapay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================newspay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }
}
