<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

/**
 * PAYSEC
 * 
 *
 * * PAYSEC_WITHDRAWAL_PAYMENT_API, ID: 374
 *
 * Required Fields:
 * 
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * 
 * * URL: https://service.paysec.com/api/quickdraw
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * > 	"paysec_priv_key" : "## merchant private key (pem formatted, escaped, no start/end tag) ##",
 * > 	"paysec_pub_key" : "## API public key (pem formatted, escaped, no start/end tag) ##",
 * > 	"callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paysec_withdrawal extends Abstract_payment_api_paysec {
	const CALLBACK_STATUS_SUCCESS = 'COMPLETED';

	public function getPlatformCode() {
		return PAYSEC_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'paysec_withdrawal';
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

		# Get player contact number
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];
		$player = $this->CI->player->getPlayerById($playerId);		
		$username = $player['username'];		

		$params['merchantId'] = $this->getSystemInfo("account");
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['currency'] = ($this->getSystemInfo("currency") == 'IDR') ? $this->getSystemInfo("currency") : 'CNY';

		# look up bank code
		$bankInfo = $this->getPaysecBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================paysec withdraw bank whose bankTypeId=[$bank] is not supported by paysec");
			return array('success' => false, 'message' => 'Bank not supported by paysec');
		}

		$params['bankCode'] = $bankInfo[$bank]['code']; # bank SN mapping
		$params['bankName'] = $bankInfo[$bank]['name']; # bank SN mapping
		$params['customerName'] = $username;
		$params['bankAccountName'] = $name;
		$params['bankAccountNumber'] = $accNum;
		$params['transactionId'] = $transId;
		$params['callbackUrl'] = $this->getNotifyUrl($transId); # Invokes callBackFromServer

		
		if($params['currency'] == 'CNY') {
			# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
			# but if we cannot look up those info, will leave the fields blank
			$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
			$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
			if(!empty($playerBankDetails)){
				$params['province'] = $playerBankDetails['province'];
				$params['city'] = $playerBankDetails['city'];
				$params["bankBranch"] = $playerBankDetails['branch'];
			} else {
				$params['province'] = '无';
				$params['city'] = '无';
				$params["bankBranch"] = '无';
			}

			$params['province'] = empty($params['province']) ? "无" : $params['province'];
			$params['city'] = empty($params['city']) ? "无" : $params['city'];
			$params['bankBranch'] = empty($params['bankBranch']) ? "无" : $params['bankBranch'];
		}

     

        $params["postingSignature"] = $this->sign($params);

		$this->utils->debug_log("========================paysec submit withdrawal order Params: ", $params);
		
		return $params;
	}

	// --------- For Withdrawal ------------
	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false);
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$fullParams = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getWithdrawUrl();
		$postString = is_array($fullParams) ? "[".$this->CI->utils->encodeJson($fullParams)."]" : $fullParams;
		$curlConn = curl_init($url);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_HTTPHEADER, array('Content-Type:application/json') );
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		$result['result'] = curl_exec($curlConn);
		$result['success'] = (curl_errno($curlConn) == 0);
		$result['message'] = curl_error($curlConn);
		$this->utils->debug_log("========================paysec withdrawal Post json", $postString, "Result", $result);
		curl_close($curlConn);

		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("Decoded Result", $decodedResult);
		return $decodedResult;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================paysec decoded result string", $result);

		if($result['status'] == '0') {
			$message = $result['message'];
			return array('success' => true, 'message' => $message);
		} 
		else if($result['status'] == '1') {
			$this->errMsg = '['.$result['respCode'].']: '.$result['message'];
		}
		else {
			$this->errMsg = 'paysec payment failed for unknown reason';
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

		$this->utils->debug_log('=========================paysec process withdrawalResult order id', $transId);

		$this->utils->debug_log("=========================paysec checkCallback params", $params);		

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if ($params['status'] != self::CALLBACK_STATUS_SUCCESS) {
			$msg = sprintf('======================paysec withdrawal payment was not successful: status code [%s]', $params['status']);
			$this->writePaymentErrorLog($msg, $fields);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $msg;
		} else {		
			$msg = sprintf('Payment was successful: trade ID [%s]', $params['oid']);

			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

			$result['message'] = $msg;
			$result['success'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'mid', 'oid', 'transactionId', 'cur', 'amt', 'status'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifySign($fields)) {
			$this->writePaymentErrorLog('=========================paysec withdrawal checkCallback signature Error', $fields);
			return false;
		}	

		if ($fields['amt'] != $order['amount']) {
			$this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
			return false;
		}

		if ($fields['transactionId'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function getPaysecBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("paysec_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("================== getting paysec bank info from extra_info: ", $bankInfo);
		} else {
			if($this->getSystemInfo("currency") == 'IDR') {
				$bankInfo = array(
					'27' => array('name' => 'Bank Rakyat Indonesia', 	  'code' => '002'),
					'28' => array('name' => 'Bank Mandiri', 		 	  'code' => '008'),
					'29' => array('name' => 'Bank Central Asia', 	 	  'code' => '014'),
					'30' => array('name' => 'Bank Permata', 		 	  'code' => '013'),
					'31' => array('name' => 'Bank BJB/Bank JABAR', 	 	  'code' => '110'),
					'32' => array('name' => 'Bank Bukopin', 		 	  'code' => '441'),
					'33' => array('name' => 'Bank Syariah Mandiri',  	  'code' => '451'),
					'34' => array('name' => 'Maybank', 				 	  'code' => '016'),
					'35' => array('name' => 'Bank BRI Syariah', 	 	  'code' => '422'),
					'36' => array('name' => 'Bank CIMB NIAGA', 		 	  'code' => '022'),
					'37' => array('name' => 'Bank Tabungan Negara (BTN)', 'code' => '208'),
					'38' => array('name' => 'Bank BCA Syariah', 		  'code' => '536'),
					'39' => array('name' => 'Bank Negara Indonesia', 	  'code' => '009'),
					'40' => array('name' => 'Bank Danamon Indonesia', 	  'code' => '011'),
					'41' => array('name' => 'Bank Mestika', 			  'code' => '151'),
					'42' => array('name' => 'Bank Panin', 				  'code' => '019')
				);
			}
			else {
				$bankInfo = array(
					'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
					'2' => array('name' => '招商银行', 'code' => 'CMB'),	
					'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
					//'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
					'5' => array('name' => '交通银行', 'code' => 'BCOM'),
					'6' => array('name' => '中国银行', 'code' => 'BOC'),
					'7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
					//'8' => array('name' => '广东发展银行', 'code' => 'CGB'),
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
			}

			$this->utils->debug_log("=======================getting paysec bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}	

	public function sign($params, $action = ''){
		$md5key = strtoupper($this->getSystemInfo('secret'));

		if(isset($params['status'])) {
			$data = array(
				"mid", "transactionId", "amt", "cur", "status"	//callback params
			);
		}
		else {
			$data = array(
				"merchantId", "transactionId", "amount", "currency"
			);
		}
	    
	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				if($data[$i] == 'amount' || $data[$i] == 'amt') {
					$fixAmountStr = str_replace('.', '', $params[$data[$i]]);
					$arr[$i] = $fixAmountStr;
				}
				else {
					$arr[$i] = $params[$data[$i]];
				}				
			}
	    }
	    $signStr = implode(';', $arr);
	    $signStr = $md5key.';'.$signStr;

	    $sign = md5($signStr);

		
		return $sign;
	}

	public function verifySign($params){
		if($this->sign($params) == $params["signature"]){
			return true;
		} else {
			return false;
		}
	}	
}
