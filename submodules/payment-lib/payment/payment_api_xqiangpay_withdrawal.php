<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xqiangpay.php';

/**
 * XQIANGPAY 小強
 *
 *
 * * XQIANGPAY_WITHDRAWAL_PAYMENT_API, ID: 499
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 *
 * * TEST-URL: http://test.xqiangpay.net/website/api/pay2bank.htm
 * * LIVE-URL: hhttps://www.xqiangpay.net/website/api/pay2bank.htm
 * * Account: ## partner ID ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xqiangpay_withdrawal extends Abstract_payment_api_xqiangpay {
	const CALLBACK_STATUS_SUCCESS = '111';
	const CALLBACK_STATUS_PROCCESSING = '101';

	public function getPlatformCode() {
		return XQIANGPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xqiangpay_withdrawal';
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
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		# look up bank code
		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================xqiangpay withdrawl bank whose bankTypeId=[$bank] is not supported by xqiangpay withdrawl");
			return array('success' => false, 'message' => 'Bank not supported by xqiangpay withdrawl');
		}

        # look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$params['payeeBankProvinceName'] = $playerBankDetails['province'];
			$params['payeeBankCityName']     = $playerBankDetails['city'];
			$params["payeeOpeningBankName"]  = $playerBankDetails['branch'];
		}

		$params = array();
		$params['method']                = 'apiPay';
		$params['tradeType']             = '0';
		$params['payeeName']             = $name;
		$params['payeeBankAcctCode']     = $accNum;
		$params['payerMemberCode']       = $this->getSystemInfo('account');
		$params['payeeBankName']         = $bankInfo[$bank]['name'];
		$params['payeeBankCode']         = $bankInfo[$bank]['code'];
		$params['payeeBankProvinceName'] = empty($params['payeeBankProvinceName']) ? "无" : $params['payeeBankProvinceName'];
		$params['payeeBankCityName']     = empty($params['payeeBankCityName'])     ? "无" : $params['payeeBankCityName'];
		$params['payeeOpeningBankName']  = empty($params['payeeOpeningBankName'])  ? "无" : $params['payeeOpeningBankName'];
		$params['requestAmount']         = $amount;
		$params['notifyUrl']             = $this->getNotifyUrl($transId);
		$params["signMsg"]               = $this->getHmac($params);

		$this->utils->debug_log("========================xqiangpay withdrawal submit order Params: ", $params);
		return $params;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
		$this->utils->debug_log("=========================xqiangpay withdrawal decoded result string", $result);

		if(is_array($result)){
            if(key($result) == 'error') {
                $this->errMsg = $result['error'];
            } elseif (key($result) == 'orderId') {
                $realStateDesc = $this->getMappingErrorMsg(key($result));
                $message = '['.$realStateDesc.'] 订单号: '.$result['orderId'];
                return array('success' => true, 'message' => $message);
            }
        } else {
            $this->errMsg = '支付平台回传500错误';
		}

		return array('success' => false, 'message' => $this->errMsg);
	}

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case 'orderId':
				$msg = "成功";
				break;

            default:
				$msg = '失敗';
				break;
		}
		return $msg;
	}

	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($transId) {
		$params = array();
		$params['p0_Cmd']   = 'Money';
		$params['p1_MerId'] = $this->getSystemInfo("account");
		$params['orderID']  = $transId;
		$params["hmac"]     = $this->getHmac($params);

		$url = $this->getSystemInfo('check_withdraw_status_url');
		$response = $this->submitPostForm($url, $params, false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
	}

	public function callbackFromServer($transId, $params) {
	 	$result = array('success' => false, 'message' => 'Payment failed');

		$raw_xml_data = file_get_contents("php://input");
		$this->utils->debug_log('=========================xqiangpay process withdrawalResult raw_xml_data id', $raw_xml_data);

		$result = explode('&',$raw_xml_data);
		foreach($result as $val){
			$temp_arr = explode('=',$val);
			$params[$temp_arr['0']] = $temp_arr['1'];
		}
        $result = $params;

	 	$this->utils->debug_log("=========================xqiangpay checkCallback params", $params);

	 	$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

	 	if (!$this->checkCallbackOrder($order, $params)) {
	 		return $result;
	 	}

	 	if($result['stateCode'] == self::CALLBACK_STATUS_SUCCESS) {
	 		$msg = sprintf('xqiangpay withdrawal payment was successful: trade ID [%s]', $params['orderID']);

	 		$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

	 		$result['message'] = $msg;
	 		$result['success'] = true;
	 	}
	 	else if($result['stateCode'] == self::CALLBACK_STATUS_PROCCESSING) {
	 		$realStateDesc = $result['msg'];
	 		$message = '['.$result['stateCode'].']: '.$realStateDesc;

	 		return array('success' => $success, 'message' => $message);
	 	}
	 	else {
            $realStateDesc = $result['msg'];
	 		$this->errMsg = '['.$result['stateCode'].']: '.$realStateDesc;

	 		$msg = sprintf('======================xqiangpay withdrawal payment was not successful: '.$this->errMsg);
	 		$this->writePaymentErrorLog($msg, $params);
	 		$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
	 		$result['message'] = $msg;
	 	}

	 	return $result;
	}

	private function checkCallbackOrder($order, $fields) {
	 	# does all required fields exist in the header?
	 	$requiredFields = array('orderID', 'stateCode', 'orderAmount', 'partnerID', 'msg', 'signMsg');
	 	foreach ($requiredFields as $f) {
	 		if (!array_key_exists($f, $fields)) {
	 			$this->writePaymentErrorLog("======================xqiangpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
	 			return false;
	 		}
	 	}

	 	if (!$this->validateHmac($fields)) {
	 		$this->writePaymentErrorLog('=========================xqiangpay withdrawal checkCallback signature Error', $fields);
	 		return false;
	 	}

        //post出去,金額單位1元(等於1000厘)
        //callback回來,金額單位1厘(等於1/1000元)
        $order['amount'] = $order['amount']*1000;
	 	if ($fields['orderAmount'] != $order['amount']) {
	 		$this->writePaymentErrorLog("======================xqiangpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
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
            $this->utils->debug_log("=========================xqiangpay bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => '10001001'),
				'2' => array('name' => '招商银行', 'code' => '10006001'),
				'3' => array('name' => '建设银行', 'code' => '10004001'),
				'4' => array('name' => '农业银行', 'code' => '10002001'),
				'5' => array('name' => '交通银行', 'code' => '10005001'),
				'6' => array('name' => '中国银行', 'code' => '10003001'),
				'7' => array('name' => '深圳发展银行', 'code' => '10016001'),
				'8' => array('name' => '广东发展银行', 'code' => '10015001'),
				'10' => array('name' => '中信银行', 'code' => '10013001'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => '10010001'),
				'13' => array('name' => '兴业银行', 'code' => '10008001'),
				'14' => array('name' => '华夏银行', 'code' => '10012001'),
				'15' => array('name' => '平安银行', 'code' => '10017001'),
				'17' => array('name' => '广州银行', 'code' => '10020001'),
				'20' => array('name' => '光大银行', 'code' => '10014001')
			);
			$this->utils->debug_log("=======================getting xqiangpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function getHmac($params) {
        $pkey = $this->getSystemInfo('md5key');
		$keys = array('payeeName', 'payeeBankName', 'payeeBankAcctCode', 'requestAmount', 'payerMemberCode', 'notifyUrl');
        $signStr = "";
        foreach($keys as $key) {
            if(array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr .= 'pkey=' . $pkey;
		$hmac = md5($signStr);
		return $hmac;
	}

	public function validateHmac($params) {
        $pkey = $this->getSystemInfo('md5key');
		$keys = array('orderID', 'stateCode', 'orderAmount', 'partnerID');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $key.'='.$params[$key].'&';
			}
		}
        $signStr .= 'pkey=' . $pkey;
		$hmac = md5($signStr);
		return strcasecmp($params['signMsg'], $hmac) === 0;
	}
}
