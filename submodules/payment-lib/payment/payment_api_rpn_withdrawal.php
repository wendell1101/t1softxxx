<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rpn.php';

/**
 * RPN
 *
 *
 * * RPN_WITHDRAWAL_PAYMENT_API, ID: 816
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://withdrawal-api.rpnpay.com/payout.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_withdrawal extends Abstract_payment_api_rpn {
    const RETURN_STATUS_SUCCESS    = 20;

    const CALLBACK_STATUS_SUCCESS  = 30;
    const CALLBACK_STATUS_FAILED   = 25;
    const CALLBACK_STATUS_CANCELED = 40;
    const CALLBACK_STATUS_REFUNDED = 50;


    const RETURN_SUCCESS_CODE = "ok";

	public function getPlatformCode() {
		return RPN_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}


	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			return $result;
		}
		if(!array_key_exists($bank, $this->getRPNBankInfo())) {
			$this->utils->error_log("========================RPN submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by rpn");
			return array('success' => false, 'message' => 'Bank not supported by RPN');
		}

		$param['Data'] = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getWithdrawUrl();
		list($response, $response_result) = $this->submitPostForm($url, $param, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

		$this->CI->utils->debug_log('======================================RPN submitWithdrawRequest url: ', $url );
		$this->CI->utils->debug_log('======================================RPN submitWithdrawRequest param: ', $param);
		$this->CI->utils->debug_log('======================================RPN submitWithdrawRequest decoded Result', $decodedResult);

		return $decodedResult;
	}

	# Note: to avoid breaking current APIs, these abstract methods are not marked abstract
	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this AP
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
		# look up bank code
		$bankInfo = $this->getRPNBankInfo();
        $bankName = $bankInfo[$bank];	//銀行名稱

        # look up bank detail
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
			$province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
			$city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
		} else {
			$bankBranch = '无';
			$province = '无';
			$city = '无';
		}

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$params = array();
        $params['mid']       = $this->getSystemInfo('account');
        $params['transId']   = $transId;
        $params['total_num'] = "1";
		$params['amount']    = $this->convertAmountToCurrency($amount,$order['dwDateTime']);
		$params['time']      = date("Y-m-d H:i:s");
		$signature = $this->sign($params);

		$params['sign_type']    = '1'; # 1=MD5, 2=SHA
		$params['currency']     = '156';
		$params['signature']    = $signature;
		$params['return_url']   = $this->getReturnUrl($transId);
		$params['callback_url'] = $this->getNotifyUrl($transId);

		$detial[] = array(
			"OrderSno" => $transId,
			"BankName" => $bankName,
			"SubBranch" => $bankBranch,
			"BankAccountName" => $name,
			"BankCardNo" => $accNum,
			"Province" => $province,
			"Area" => $city,
			"Amount" => $this->convertAmountToCurrency($amount,$order['dwDateTime']),
		);

		$withdrawal = implode( ",", $params);
		$post_data = array(
			"Withdrawal" => $withdrawal,
			"Detial" =>  $detial
		);

		return urlencode(json_encode($post_data));
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
		#different return type
		if(!is_null(json_decode($resultString))){
			$resultString = json_decode($resultString, true);
			$this->CI->utils->debug_log('==============RPN submitWithdrawRequest decodeResult json decoded', $resultString);
		}

		if($queryAPI){
			if($resultString['RespCode']){
				$returnCode = $resultString['RespCode'];
				$returnDesc = ($resultString['RespMsg']) ? urldecode($resultString['RespMsg']) : 'no RespMsg';

				if($returnCode == self::CALLBACK_STATUS_SUCCESS) {
					$message = "RPN withdrawal success! OrderNum: ". $resultString['OrderNum'];
					return array('success' => true, 'message' => $message);
				}
				else{
					if($returnCode == self::CALLBACK_STATUS_FAILED || $returnCode == self::CALLBACK_STATUS_CANCELED || $returnCode == self::CALLBACK_STATUS_REFUNDED){
						$message = "RPN withdrawal failed, Code: ".$returnCode.", Desc: ".$returnDesc;
						$this->CI->wallet_model->withdrawalAPIReturnFailure($resultString['OrderNum'], $message);
						return array('success' => false, 'message' => $message);
					}
					else {
						$message = "RPN withdrawal response status, Code: ".$returnCode.", Desc: ".$returnDesc;
						return array('success' => false, 'message' => $message);
					}
				}

			}
			return array('success' => false, 'message' => "Decode failed");
		}

		else{
			$message = "RPN withdrawal decode fail.";
			if (isset($resultString['RespCode'])) {
				$returnCode = $resultString['RespCode'];
				$returnDesc = urldecode($resultString['RespMsg']);
				if($returnCode == self::RETURN_STATUS_SUCCESS) {
					$message = "RPN withdrawal response successful, transId: ". $resultString['WSnoBatch']. ", RespMsg: ". $returnDesc;
					return array('success' => true, 'message' => $message);
				}
				$message = "RPN withdrawal response failed, Code: ".$returnCode.", Desc: ".$returnDesc;
				return array('success' => false, 'message' => $message);

			}
			else{
				$message = $message.' API response: '.$resultString;
				return array('success' => false, 'message' => $message);
			}

		}
	}

	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($transId) {

        $params = array();
		$params['SignType']      = '1'; # 1=MD5, 2=SHA
        $params['MerchantID']    = $this->getSystemInfo('account');
        $params['OrderSno']      = $transId;
		$params['OrderDateTime'] = date("Y-m-d H:i:s");
		$params['SignValue']     = $this->checkWithdrawSign($params);

		$url = $this->getSystemInfo('check_status_url');
		$response = $this->submitPostForm($url, $params, false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================RPN checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================RPN checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================RPN checkWithdrawStatus result: ', $response);
		$this->CI->utils->debug_log('======================================RPN checkWithdrawStatus decoded Result', $decodedResult);
		return $decodedResult;
    }

	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);

		$result = array('success' => false, 'message' => 'Payment failed');
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$this->CI->utils->debug_log('=========================RPN process withdrawalResult order id', $transId);
		$this->CI->utils->debug_log("=========================RPN checkCallback params", $params);


		$Data = json_decode(urldecode($params['Data']), true);
		$Withdrawal_header = explode(",", $Data["Withdrawal"]);
		$Detial = $Data["Detial"]['0'];

        $this->CI->utils->debug_log("=========================RPN checkCallback Data", $Data);
        $this->CI->utils->debug_log("=========================RPN checkCallback Withdrawal_header", $Withdrawal_header);
        $this->CI->utils->debug_log("=========================RPN checkCallback Detial", $Detial);

		$signArr = array();
		$signArr['mid']        = $Withdrawal_header[0];
		$signArr['transId']    = $Withdrawal_header[1];
		$signArr['total_num']  = $Withdrawal_header[2];
		$signArr['amount']     = $Withdrawal_header[3];
		$signArr['time']       = $Withdrawal_header[4];
		$signArr['rpnBatchNo'] = $Withdrawal_header[5];
		$signArr['signature']  = $params['SignValue'];

		if (!$this->checkCallbackOrder($order, $signArr)) {
			return $result;
		}

        if ($Detial['RowCode'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('RPN withdrawal Payment was successful: trade ID [%s]', $Detial['OrderSno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else if($Detial['RowCode'] == self::CALLBACK_STATUS_FAILED || $Detial['RowCode'] == self::CALLBACK_STATUS_CANCELED || $Detial['RowCode'] == self::CALLBACK_STATUS_REFUNDED){
            $msg = sprintf('RPN withdrawal payment was not successful: status code [%s]. '.$Detial['RowMsg'], $Detial['RowCode']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('RPN withdrawal payment was not successful: status code [%s]. '.$Detial['RowMsg'], $Detial['RowCode']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'mid', 'transId', 'total_num', 'amount', 'time', 'rpnBatchNo', 'signature'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================RPN withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================RPN withdrawal checkCallback signature Error', $fields['signature']);
			return false;
		}

		if($this->getSystemInfo('use_usd_currency')) {
			$amount = $this->convertAmountToCurrency($order['amount'], $order['dwDateTime']);
			$diff = abs($fields['amount'] - $amount);
			$limit = ($this->getSystemInfo('rate_diff_allowance')) ? $this->getSystemInfo('rate_diff_allowance') : 1;
			$wrong_amount = ($diff > $limit) ? true :false;
        }


		if ($wrong_amount) {
			$this->writePaymentErrorLog("======================RPN withdrawal checkCallbackOrder payment amount is wrong, expected [". $amount. "]", $fields['amount']);
			return false;
		}

		if ($fields['transId'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================RPN withdrawal checkCallbackOrder order IDs do not match, expected [". $order['transactionCode']. "]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}


	public function getRPNBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("rpn_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
			}
			$this->utils->debug_log("==================getting RPN bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => '中国工商银行',
				'2' => '招商银行',
				'3' => '中国建设银行',
				'4' => '中国农业银行',
				'5' => '交通银行',
				'6' => '中国银行',
				'8' => '广发银行',
				'10' => '中信银行',
				'11' => '中国民生银行',
				'12' => '中国邮政储蓄银行',
				'13' => '兴业银行',
				'14' => '华夏银行',
				'15' => '平安银行',
				'20' => '中国光大银行',
				'24' => '浦发银行'
			);
			$this->utils->debug_log("=======================getting RPN bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	# -- signatures --
	# Reference: PHP Demo
	private function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		
		return $sign;
	}

	private function createSignStr($params) {
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'signature' || $key == 'ext' || $key == 'SignValue'|| $key == 'APIKEY') {
				continue;
			}
			$signStr .= "$value|";
		}
		$signStr .= $this->getSystemInfo('key');
		return $signStr;
	}

	private function checkWithdrawSign($params) {
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'APIKEY') {
				continue;
			}
			$signStr .= "$key=[$value]";
		}
		$signStr .= "APIKEY=[".$this->getSystemInfo('key')."]";
		$sign = md5($signStr);
	
		return $sign;
	}

	private function validateSign($params) {
		$sign = $this->sign($params);
		if($params['signature'] == $sign)
			return true;
		else
			return false;
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount, $orderDateTime) {
		if($this->getSystemInfo('use_usd_currency')){
			if(is_string($orderDateTime)){
				$orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime); 
			}
			$amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime),'USD','CNY');
			$this->CI->utils->debug_log('=====================rpn convertAmountToCurrency use_usd_currency', $amount);
		}
		return number_format($amount, 2, '.', '');
	}
}
