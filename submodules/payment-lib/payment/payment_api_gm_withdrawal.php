<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gm.php';

/**
 * GM
 * 
 *
 * * GM_WITHDRAWAL_PAYMENT_API, ID: 385
 *
 * Required Fields:
 * 
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * 
 * * URL: https://service.gm.com/api/quickdraw
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * > 	"gm_priv_key" : "## merchant private key (pem formatted, escaped, no start/end tag) ##",
 * > 	"gm_pub_key" : "## API public key (pem formatted, escaped, no start/end tag) ##",
 * > 	"callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gm_withdrawal extends Abstract_payment_api_gm {
	const CALLBACK_STATUS_SUCCESS = '04';
	const CALLBACK_STATUS_PROCCESSING = '00';

	public function getPlatformCode() {
		return GM_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gm_withdrawal';
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

		$params['p0_Cmd'] = 'Withdraw';
		$params['p1_MerId'] = $this->getSystemInfo("account");

		# look up bank code
		$bankInfo = $this->getGmBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================gm withdraw bank whose bankTypeId=[$bank] is not supported by gm");
			return array('success' => false, 'message' => 'Bank not supported by gm');
		}

		$params['p2_BankCode'] = $bankInfo[$bank]['code']; # bank SN mapping
		$params['p3_CardAcType'] = '1';
		$params['p4_BankCardNo'] = $accNum;
		$params['p5_CardHolder'] = $name;
		$params['p6_BankName'] = $bankInfo[$bank]['name']; # bank SN mapping

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$params['p8_BankProvince'] = $playerBankDetails['province'];
			$params['p9_BankCity'] = $playerBankDetails['city'];
			$params["p7_BankBranchName"] = $playerBankDetails['branch'];
		} else {
			$params['p8_BankProvince'] = '无';
			$params['p9_BankCity'] = '无';
			$params["p7_BankBranchName"] = '无';
		}

		$params['p8_BankProvince'] = empty($params['p8_BankProvince']) ? "无" : $params['p8_BankProvince'];
		$params['p9_BankCity'] = empty($params['p9_BankCity']) ? "无" : $params['p9_BankCity'];
		$params['p7_BankBranchName'] = empty($params['p7_BankBranchName']) ? "无" : $params['p7_BankBranchName'];

		$params['p10_PayAmount'] = $this->convertAmountToCurrency($amount);
		$params['p11_OrderID'] = $transId;
		$params['p12_returnUrl'] = $this->getNotifyUrl($transId); # Invokes callBackFromServer
		$params['p13_Cur'] = 'CNY';
		$params['p14_Channel'] = 'bank';

        $this->CI->utils->debug_log('========================gm withdrawal paramStr before sign', $params);

        $params["hmac"] = $this->getHmac($params);

		
		
		return $params;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {
		$result = $this->parseResultXML($resultString);
		$this->utils->debug_log("=========================gm decoded result string", $result);

		if($queryAPI) {
			$success = false;
			$message = "gm check withdraw status failed for unknown reasson.";

			$retCode = trim($result['retCode']);

			if($retCode == '0001') {
				$errMsg = $result['retMsg'];

				$message = '['.$retCode.']: '.$errMsg;
			}
			else if($retCode == '0000') {
				if($result['state'] == '00' || $result['state'] == '05') {
					$errMsg = $result['retMsg'];
					$message = '['.$result['state'].']: '.$errMsg;
				}
				else if($result['state'] == '04' ) {	//success
					$successMsg = $result['retMsg'];
					$message = '['.$result['state'].']: '.$successMsg. ', orderID: ['. $result['orderId'] .']';
					$success = true;
				}
			}

			return array('success' => $success, 'message' => $message);
		}

		if($result['r1_Code'] == '0') {
			$errMsg = $this->getMappingErrorMsg($result['r5_state']);

			$this->errMsg = '['.$result['r5_state'].']: '.$errMsg;
		} 
		else if($result['r1_Code'] == '1') {
			$realStateDesc = $this->getMappingErrorMsg($result['r5_state']);		

			$message = '['.$result['r5_state'].']: '.$realStateDesc;
			return array('success' => true, 'message' => $message);
		}
		else {
			$this->errMsg = 'gm payment failed for unknown reason';
		} 

		return array('success' => false, 'message' => $this->errMsg);
	}

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case '00':
				$msg = "處理中";
				break;

			case '04':
				$msg = "成功";
				break;

			case '05':
				$msg = "失敗或拒絕";
				break;			

			case '1001':
				$msg = "沒有足夠的金額進行出款";
				break;

			case '1002':
				$msg = "參數錯誤";
				break;

			case '1003':
				$msg = "订单号从复";
				break;

			case '1004':
				$msg = "不支援此銀行";
				break;

			case '1005':
				$msg = "签名较验失败";
				break;

			case '1006':
				$msg = "出款要求出現問題";
				break;

			case '1007':
				$msg = "出款金額不能少於2";
				break;																								
			
			default:
				$msg = "gm payment got unknown r5_state";
				break;
		}
		return $msg;
	}

	public function parseResultXML($resultXml) {
        $result = NULL;
		$obj=simplexml_load_string($resultXml);
		$arr=$this->CI->utils->xmlToArray($obj);
		$this->CI->utils->debug_log(' =========================gm parseResultXML', $arr);
        $result = $arr;

        return $result;
	}

	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($transId) {
		$params = array();
		$params['p0_Cmd'] = 'Money';
		$params['p1_MerId'] = $this->getSystemInfo("account");
		$params['orderID'] = $transId;

		$params["hmac"] = $this->getHmac($params);

		$this->CI->utils->debug_log('======================================gm checkWithdrawStatus params: ', $params);

		$url = $this->getSystemInfo('check_withdraw_status_url');	
		$this->CI->utils->debug_log('======================================gm checkWithdrawStatus url: ', $url );

		$response = $this->submitPostForm($url, $params);

		$this->CI->utils->debug_log('======================================gm checkWithdrawStatus result: ', $response );
		
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;		
	}

	/**
	 * detail: Help2Pay withdraw callback implementation
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	// public function callbackFromServer($transId, $params) {
	// 	$result = array('success' => false, 'message' => 'Payment failed');
	// 	$this->utils->debug_log('=========================gm process withdrawalResult order id', $transId);

 //        $raw_xml_data = file_get_contents("php://input");
 //        $this->utils->debug_log('=========================gm process withdrawalResult raw_xml_data id', $raw_xml_data);

 //        $params = $this->parseResultXML($raw_xml_data);

	// 	$this->utils->debug_log("=========================gm checkCallback params", $params);		

	// 	$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

	// 	if (!$this->checkCallbackOrder($order, $params)) {
	// 		return $result;
	// 	}

	// 	if($result['r1_Code'] == '1' && $result['r5_state'] == self::CALLBACK_STATUS_SUCCESS) {
	// 		$msg = sprintf('GM withdrawal payment was successful: trade ID [%s]', $params['r2_OrderID']);

	// 		$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

	// 		$result['message'] = $msg;
	// 		$result['success'] = true;			
	// 	}
	// 	else if($result['r1_Code'] == '1' && $result['r5_state'] == self::CALLBACK_STATUS_PROCCESSING) {
	// 		$realStateDesc = $this->getMappingErrorMsg($result['r5_state']);		
	// 		$message = '['.$result['r5_state'].']: '.$realStateDesc;

	// 		return array('success' => $success, 'message' => $message);
	// 	}
	// 	else {
	// 		$errMsg = $this->getMappingErrorMsg($result['r5_state']);

	// 		$this->errMsg = '['.$result['r5_state'].']: '.$errMsg;

	// 		$msg = sprintf('======================gm withdrawal payment was not successful: '.$this->errMsg);
	// 		$this->writePaymentErrorLog($msg, $params);
	// 		$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
	// 		$result['message'] = $msg;					
	// 	}

	// 	return $result;
	// }

	// private function checkCallbackOrder($order, $fields) {
	// 	# does all required fields exist in the header?
	// 	$requiredFields = array(
	// 		'p1_MerId', 'r0_Cmd', 'r1_Code', 'r2_OrderID', 'r3_Amt', 'r4_Cur', 'r5_state'
	// 	);
	// 	foreach ($requiredFields as $f) {
	// 		if (!array_key_exists($f, $fields)) {
	// 			$this->writePaymentErrorLog("======================gm withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
	// 			return false;
	// 		}
	// 	}

	// 	if (!$this->validateHmac($fields)) {
	// 		$this->writePaymentErrorLog('=========================gm withdrawal checkCallback signature Error', $fields);
	// 		return false;
	// 	}	

	// 	if ($fields['r3_Amt'] != $order['amount']) {
	// 		$this->writePaymentErrorLog("======================gm withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
	// 		return false;
	// 	}

	// 	if ($fields['r2_OrderID'] != $order['transactionCode']) {
	// 		$this->writePaymentErrorLog("======================gm withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
	// 		return false;
	// 	}

	// 	# everything checked ok
	// 	return true;
	// }

	// public function callbackFromBrowser($transId, $params) {
	// 	return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	// }

	public function getGmBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("gm_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("================== getting gm bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),	
				'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'COMM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				//'7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				//'8' => array('name' => '广东发展银行', 'code' => 'CGB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				//'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				//'15' => array('name' => '平安银行', 'code' => 'PAB'),
				//'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				//'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB')
			);
			$this->utils->debug_log("=======================getting gm bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function getHmac($params) {
	
		//orderID is for checkWithdrawStatus
		$keys = array('p0_Cmd', 'p1_MerId', 'p2_BankCode', 'p3_CardAcType', 'p4_BankCardNo', 'p5_CardHolder', 'p6_BankName', 'p7_BankBranchName', 'p8_BankProvince', 'p9_BankCity', 'p10_PayAmount', 'p11_OrderID', 'p12_returnUrl', 'p13_Cur', 'p14_Channel', 'orderID');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}
		$hmac = $this->HmacMd5($signStr, $this->getSystemInfo('key'));
		
		return $hmac;
	}

	public function validateHmac($params) {
		
		$keys = array('p1_MerId', 'r0_Cmd', 'r1_Code', 'r2_OrderID', 'r3_Amt', 'r4_Cur', 'r5_state');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}
		$hmac = $this->HmacMd5($signStr, $this->getSystemInfo('key'));
		
		return strcasecmp($params['hmac'], $hmac) === 0;
	}	
}
