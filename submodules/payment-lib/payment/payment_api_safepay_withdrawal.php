<?php
require_once dirname(__FILE__) . '/abstract_payment_api_safepay.php';

/**
 * SAFEPAY
 * 
 *
 * * SAFEPAY_WITHDRAWAL_PAYMENT_API, ID: 393
 *
 * Required Fields:
 * 
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * 
 * * URL: https://service.safepay.com/api/quickdraw
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * > 	"safepay_priv_key" : "## merchant private key (pem formatted, escaped, no start/end tag) ##",
 * > 	"safepay_pub_key" : "## API public key (pem formatted, escaped, no start/end tag) ##",
 * > 	"callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_safepay_withdrawal extends Abstract_payment_api_safepay {
	const CALLBACK_STATUS_SUCCESS = '1';
	const RETURN_SUCCESS_CODE = 'SUCCESS';

	public function getPlatformCode() {
		return SAFEPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'safepay_withdrawal';
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

		$params['customerID'] = $this->getSystemInfo("account");
		$params['orderID'] = $transId;
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$params['bankCardNum'] = $accNum;
		$params['MerchantNum'] = $this->getSystemInfo("MerchantNum");
		$params['userName'] = $name;

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$params['province'] = $playerBankDetails['province'];
			$params['city'] = $playerBankDetails['city'];
			$params["bankAddress"] = $playerBankDetails['bankAddress'];
		} else {
			$params['province'] = '';
			$params['city'] = '';
			$params["bankAddress"] = '';
		}

		$params['province'] = empty($params['province']) ? "" : $params['province'];
		$params['city'] = empty($params['city']) ? "" : $params['city'];
		$params['bankAddress'] = empty($params['bankAddress']) ? "" : $params['bankAddress'];	

		$params['reviewedStatue'] = "1";
		$params['interfaceID'] = $this->getSystemInfo("interfaceID");
		$params['bankLineNum'] = "";
		$params['chineseRemark'] = '无';
		$params['EnglishRemark'] = 'Withdrawal';
		$params['asynURL'] = $this->getNotifyUrl($transId); # Invokes callBackFromServer

		# look up bank code
		$bankInfo = $this->getSafepayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================safepay withdraw bank whose bankTypeId=[$bank] is not supported by safepay");
			return array('success' => false, 'message' => 'Bank not supported by safepay');
		}

		$params['bank'] = $bankInfo[$bank]['code']; # bank SN mapping
		$params['bank'] = '';
		$params['payCompayType'] = $this->getSystemInfo("payCompayType");
		$params['type'] = "1";

        

        $params["md5Str"] = $this->sign($params);

		$this->utils->debug_log("========================safepay submit withdrawal order Params: ", $params);
		
		return $params;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString) {
		$result = str_replace("\r\n\r\n", "", $resultString);

		$this->utils->debug_log("=========================safepay decoded result string", $result);

		if($result == 'ok') {
			$successMsg = $this->getMappingErrorMsg($result);
			$message = '['.$result.']: '.$successMsg;
			return array('success' => true, 'message' => $message);
		} 
		else {
			$errMsg = $this->getMappingErrorMsg($result);
			$this->errMsg = '['.$result.']: '.$errMsg;
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

		$this->utils->debug_log('=========================safepay process withdrawalResult order id', $transId);

		$this->utils->debug_log("=========================safepay checkCallback params", $params);		

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if ($params['statue'] != self::CALLBACK_STATUS_SUCCESS) {
			$errMsg = $this->getMappingErrorMsg($result['statue']);
			$message = '['.$result['statue'].']: '.$errMsg;
			$msg = sprintf('======================safepay withdrawal payment was not successful: status code [%s]', $params['statue']);
			$this->writePaymentErrorLog($msg, $fields);
			//$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $message;
		} else {
			$successMsg = $this->getMappingErrorMsg($result['statue']);
			$message = '['.$result['statue'].']: '.$successMsg;
			$msg = sprintf('======================safepay withdrawal payment payment was successful: trade ID [%s]', $params['orderID']);

			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

			$result['message'] = self::RETURN_SUCCESS_CODE;
			$result['success'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'interfaceID', 'orderID', 'amt', 'statue'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================safepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifySign($fields)) {
			$this->writePaymentErrorLog('=========================safepay withdrawal checkCallback signature Error', $fields);
			return false;
		}	

		if ($fields['amt'] != $order['amount']) {
			$this->writePaymentErrorLog("======================safepay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
			return false;
		}

		if ($fields['orderID'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================safepay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case 'ok':
				$msg = "下发执行操作成功";
				break;

			case 'A-01':
				$msg = "请填写【下发银行】";
				break;

			case 'A-02':
				$msg = "请填写【下发账户开户区域】";
				break;			

			case 'A-03':
				$msg = "请填写【下发账户开户城市】";
				break;

			case 'A-04':
				$msg = "请填写【下发账户开户网点】";
				break;

			case 'A-05':
				$msg = "IP阻止";
				break;

			case 'A-06':
				$msg = "订单号不能为空";
				break;

			case 'A-07':
				$msg = "签字错误";
				break;

			case 'A-08':
				$msg = "重复订单";
				break;			

			case 'A-09':
				$msg = "余额不足";
				break;

			case 'A-10':
				$msg = "无自动审核权限";
				break;

			case 'A-11':
				$msg = "系统忙或支付公司线路不好，请稍后再试";
				break;

			case 'A-12':
				$msg = "目标银行卡未确认是否能正常收款，请检查此卡最后一笔下发订单";
				break;

			case '0':
				$msg = "银行转账执行中";
				break;

			case '1':
				$msg = "银行转账已成功";
				break;

			case '2':
				$msg = "银行转账已失败";
				break;			

			case '3':
				$msg = "系统自动提交中";
				break;

			case '4':
				$msg = "成功提交支付公司";
				break;

			case '5':
				$msg = "支付公司校验未通过";
				break;

			case '-1':
				$msg = "安全校验未通过";
				break;

			case 'N':
				$msg = "下发执行操作失败";
				break;																								
			
			default:
				$msg = "safepay payment got unknown error";
				break;
		}
		return $msg;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function getSafepayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("safepay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("================== getting safepay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'R'),
				'2' => array('name' => '招商银行', 'code' => 'P'),	
				'3' => array('name' => '中国建设银行', 'code' => 'T'),
				'4' => array('name' => '中国农业银行', 'code' => 'U'),
				//'5' => array('name' => '交通银行', 'code' => 'BCOM'),
				//'6' => array('name' => '中国银行', 'code' => 'BOC'),
				//'7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				//'8' => array('name' => '广东发展银行', 'code' => 'CGB'),
				'10' => array('name' => '中信银行', 'code' => 'W'),
				//'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				//'12' => array('name' => '中国邮政储蓄', 'code' => 'PSBC'),
				//'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				//'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				//'15' => array('name' => '平安银行', 'code' => 'PAB'),
				//'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				//'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'S')
			);
			$this->utils->debug_log("=======================getting safepay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function sign($params) {
		$md5key = $this->getSystemInfo('key');

		if(isset($params['MerchantNum'])) {	//withdrawal
			$data = array(
				"orderID", "MerchantNum", "bankCardNum", "amt", "reviewedStatue"
			);			
		}
		else {
			$data = array(
				"orderId", "amt", "statue"
			);			
		}
	    
	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $params[$data[$i]];
			}
	    }
	    $signStr = implode('&', $arr);
	    $signStr .= '&'.$md5key;

		$sign = sha1($signStr);

		return $sign;
	}

	public function verifySign($params){
		if($this->sign($params) == $params["md5Str"]){
			return true;
		} else {
			return false;
		}
	}	
}
