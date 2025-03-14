<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dpay.php';

/**
 * DPAY
 *
 * * DPAY_WITHDRAWAL_PAYMENT_API, ID: 507
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: http://if.jhdf.hk/api/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_withdrawal extends Abstract_payment_api_dpay {
	const CALLBACK_STATUS_SUCCESS = '3';
	const RETURN_SUCCESS_CODE = 'ok';

	public function getPlatformCode() {
		return DPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dpay_withdrawal';
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
		$this->CI->load->model(array('wallet_model'));

		# Get player contact number
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];
		$playerDetails = $this->getPlayerDetails($playerId);
		$firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
		$lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';
		# look up bank code
		$bankInfo = $this->getBankInfo();

		$params = array();
		$params['usercode']  = $this->getSystemInfo("account");
		$params['customno']  = $transId;
		$params['type']      = '1';
		$params['money']     = $this->convertAmountToCurrency($amount);
		$params['bankname']  = $bankInfo[$bank]['name'];
		$params['bankcode']  = $bankInfo[$bank]['code']; # bank SN mapping
		$params['realname']  = $lastname.$firstname;
		$params['idcard']    =  '123456789101112';
		$params['cardno']    = $accNum;
		$params["sendtime"]  = date("YmdHis");
		$params['notifyurl'] = $this->getNotifyUrl($transId); # Invokes callBackFromServer
		$params['buyerip']   = $this->getClientIP();
        $params["sign"]      = $this->sign($params);
		$this->utils->debug_log("========================dpay submit withdrawal order Params: ", $params);

		return $params;
	}

	// --------- For Withdrawal ------------
	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================dpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by dpay");
            return array('success' => false, 'message' => 'Bank not supported by dpay');
            $bank = '无';
        }

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================fengyunpay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
		$this->errMsg = 'dpay payment failed for unknown reason';

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================dpay decoded result string", $result);

		if(isset($result['success'])) {
			if($result['success']) {
				$message = '['.$result['resultCode'].'], orderno: '.$result['data']['orderno'].', status: '.$result['data']['status'];
				return array('success' => true, 'message' => $message);
			}
			else {
				$this->errMsg = '['.$result['resultCode'].']: '.$result['resultMsg'];
			}
		}

		return array('success' => false, 'message' => $this->errMsg);
	}

	/**
	 * detail: Help2Pay withdraw callback implementation
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
		$result = array('success' => false, 'message' => 'Payment failed');

		$this->utils->debug_log("=========================dpay checkCallback params", $params);
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if ($params['status'] != self::CALLBACK_STATUS_SUCCESS) {
			$msg = sprintf('dpay withdrawal payment was not successful: status code [%s]', $params['status']);
			$this->writePaymentErrorLog($msg, $fields);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $msg;
		} else {
			$msg = sprintf('dpay withdrawal payment was successful: trade ID [%s]', $params['customno']);
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
			$result['message'] = self::RETURN_SUCCESS_CODE;
			$result['success'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'orderno', 'usercode', 'customno', 'type', 'bankname', 'cardno', 'realname', 'idcard', 'tjmoney', 'money', 'status','currency' , 'sign', 'resultcode');
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================dpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifySign($fields)) {
			$this->writePaymentErrorLog('=========================dpay withdrawal checkCallback signature Error', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order['amount']) != $this->convertAmountToCurrency(floatval($fields['tjmoney'])) ) {
			$this->writePaymentErrorLog("======================dpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
			return false;
		}

		if ($fields['customno'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================dpay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function getBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("dpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
				$bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
			}
			$this->utils->debug_log("================== getting dpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),
				'3' => array('name' => '建设银行', 'code' => 'CCB'),
				'4' => array('name' => '农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'COMM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				// '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				'8' => array('name' => '广东发展银行', 'code' => 'GDB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'SZPAB'),
				'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'24' => array('name' => '浦东发展银行', 'code' => 'SPDB')
			);

			$this->utils->debug_log("=======================getting dpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function sign($params) {
		$keys = array();
		if(isset($params['tjmoney'])) {	//callback params
			$keys = array('usercode', 'orderno', 'customno', 'type', 'cardno' , 'idcard', 'tjmoney', 'money', 'status' ,'currency');
		}
		else {
			$keys = array('usercode', 'customno', 'type', 'cardno', 'idcard', 'money', 'sendtime', 'buyerip');
		}

		$signStr = "";
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $params[$key] . '|';
			}
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		
		return $sign;
	}

	public function verifySign($params){
		if($this->sign($params) == $params["sign"]){
			return true;
		} else {
			return false;
		}
	}

	public function getPlayerDetails($playerId) {
		$this->CI->load->model(array('player_model'));
		$player = $this->CI->player_model->getPlayerDetails($playerId);

		return $player;
	}
}
