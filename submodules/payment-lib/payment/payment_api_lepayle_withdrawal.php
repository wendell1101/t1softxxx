<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lepayle.php';

/**
 * 新乐付 LEPAYLE
 * https://cms.lepayle.com/
 *
 * * LEPAYLE_WITHDRAWAL_PAYMENT_API, ID: 262
 *
 * Required Fields:
 * 
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * 
 * * URL: https://service.lepayle.com/api/quickdraw
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * > 	"lepayle_priv_key" : "## merchant private key (pem formatted, escaped, no start/end tag) ##",
 * > 	"lepayle_pub_key" : "## API public key (pem formatted, escaped, no start/end tag) ##",
 * > 	"callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lepayle_withdrawal extends Abstract_payment_api_lepayle {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return LEPAYLE_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lepayle_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}
	protected function processPaymentUrlForm($params) {}

	/**
	 * detail: override common API functions
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$paramsBasic = array();
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		$paramsBasic['input_charset'] = 'UTF-8';
		$paramsBasic['partner'] = $this->getSystemInfo("account");

		$params['service'] = 'pay';
		$params['out_trade_no'] = $transId;
		$params['amount_str'] = $this->convertAmountToCurrency($amount);

		# look up bank code
		$bankInfo = $this->getLepayleBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================lepayle withdraw bank whose bankTypeId=[$bank] is not supported by lepayle");
			return array('success' => false, 'message' => 'Bank not supported by lepayle');
		}

		$params['bank_sn'] = $bankInfo[$bank]['code']; # bank SN mapping
		$params['bank_site_name'] = $params['bank_sn']; # Don't have this info for now 
		$params['bank_account_name'] = $name;
		$params['bank_account_no'] = $accNum;
		$params['bus_type'] = '11'; # 00:对公 11:对私（暂时只对私）

		# Get player contact number
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];
		$player = $this->CI->player_model->getPlayerDetailsById($playerId);
		/*if(!empty($player->contactNumber)) {
			$params['bank_mobile_no'] = $player->contactNumber;
		} else {
			$params['bank_mobile_no'] = '1300000000'; # If no contact number on file, use a placeholder
		}*/
		$params['bank_mobile_no'] = $this->getBankMobileNo();

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$params['bank_province'] = $playerBankDetails['province'];
			$params['bank_city'] = $playerBankDetails['city'];
		} else {
			$params['bank_province'] = '无';
			$params['bank_city'] = '无';
		}

		$params['bank_province'] = empty($params['bank_province']) ? "无" : $params['bank_province'];
		$params['bank_city'] = empty($params['bank_city']) ? "无" : $params['bank_city'];
		
		$params['user_agreement'] = '1'; # Agree User Agreement
		$params['return_url'] = $this->getNotifyUrl($transId); # Invokes callBackFromServer
		
		$params = array_merge($params,$paramsBasic);

		$paramStr = $this->arrayToUrl($params);

		$this->CI->utils->debug_log('=========================lepayle paramStr before sign and encrypt', $paramStr);

		$postData = [];

		if(($content = $this->encrypt($paramStr)) && ($sign = $this->sign($paramStr))) {
			$postData = [
				'sign_type'     => 'SHA1WITHRSA',
				'content'       => $content,
				'sign'          => $sign,
			];
			$postData = array_merge($postData, $paramsBasic);
		}

		return $postData;
	}

	public function getBankMobileNo() {
		$headNum = array("135", "139");
		$k = array_rand($headNum);
		$num = $headNum[$k].mt_rand(10000000, 99999999);

		return $num;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================lepayle decoded result string", $result);

		if($result['is_succ'] == 'T') {
			return array('success' => true, 'message' => $result['trade_id']);
		} else {
			$this->errMsg = '['.$result['fault_code'].']:'.$result['fault_reason'];
		}

		return array('success' => false, 'message' => $this->errMsg);
	}
	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($orderId) {}

	/**
	 * detail: Help2Pay withdraw callback implementation
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $params) {
		$result = array('success' => false, 'message' => 'Payment failed');
		$this->CI->utils->debug_log('process withdrawalResult order id', $transId);

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if ($params['status'] != self::CALLBACK_STATUS_SUCCESS) {
			$msg = sprintf('======================lepayle withdrawal payment was not successful: status code [%s]', $params['status']);
			$this->writePaymentErrorLog($msg, $fields);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $msg;
		} else {
			$decryptCallbackContent = $this->decrypt(urldecode($params['content']));
			$decryptResult = json_decode($decryptCallbackContent, true);		

			$msg = sprintf('Payment was successful: trade ID [%s]', $decryptResult['trade_id']);
			$fee = $this->convertAmountToCurrency($decryptResult['amount_fee']);
			$amount = $this->convertAmountToCurrency($decryptResult['amount_str']);
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg, $fee, $amount);
			$result['message'] = $msg;
			$result['success'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'sign', 'content', 'out_trade_no', 'status',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================lepayle withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# decrypt the main content
		$decryptCallbackContent = $this->decrypt(urldecode($fields['content']));
		$decryptResult = json_decode($decryptCallbackContent, true);

		# does all required fields exist in the decrypted content?
		$decryptResultrequiredFields = array(
			'out_trade_no', 'amount_str', 'amount_fee', 'status'
		);
		foreach ($decryptResultrequiredFields as $f) {
			if (!array_key_exists($f, $decryptResult)) {
				$this->writePaymentErrorLog("======================lepayle withdrawal checkCallbackOrder decrypted callback missing parameter: [$f]", $decryptResult);
				return false;
			}
		}
		
		if (!$this->verify($decryptCallbackContent, urldecode($fields['sign']))) {
			$this->writePaymentErrorLog('Signature error', $fields);
			return false;
		}

		if ($decryptResult['amount_str'] > $order['amount']) {
			$this->writePaymentErrorLog("======================lepayle withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $decryptResult);
			return false;
		}

		if ($fields['out_trade_no'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================lepayle withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function getLepayleBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("lepayle_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("================== getting lepayle bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),	
				'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BOCM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				'8' => array('name' => '广东发展银行', 'code' => 'CGB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'12' => array('name' => '中国邮政储蓄', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB')
			);
			$this->utils->debug_log("=======================getting lepayle bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}	
}
