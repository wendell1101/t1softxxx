<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Help2Pay Withdrawal
 * http://www.41.cn
 *
 * HELP2PAY_WITHDRAWAL_PAYMENT_API, ID: 143
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 *
 * * URL: http://api.mypayout.com/MerchantPayout
 * * Account: ## Merchant ID ##
 * * Key: ## Security Code ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_help2pay_withdrawal extends Abstract_payment_api {

	const CURRENCY = 'CNY';

	const CALLBACK_STATUS_SUCCESS = '000';
	const CALLBACK_STATUS_FAILED  = '001';

	const RETURN_SUCCESS_CODE = 'true';
	const VALID_TRANSACTION = 'true';
	const INVALID_TRANSACTION = 'false';

	public function __construct($params = null) {
		parent::__construct($params);

		$this->CI->load->model(array('playerbankdetails', 'wallet_model'));
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return HELP2PAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'help2pay_withdrawal';
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

	/**
	 * detail: Constructs the parameters to submit to the withdraw API
	 *
	 * note: Note that the $bank is the bank_type ID, we should map it to the bank_code and bank_name required by this API
	 *
	 * @param int $bank
	 * @param string $accNum
	 * @param string $name
	 * @param float $amount
	 * @param int $transId
	 * @return json
	 */
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		# look up bank code
		$bankInfo = $this->getBankInfo();
        # look up bank detail
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$province   = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
			$city       = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
		} else {
			$province   = '无';
			$city       = '无';
		}

		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$param = array();
		$param['ClientIP']            = $this->getClientIP();
		$param['ReturnURI']           = $this->getNotifyUrl($transId);
		$param['MerchantCode']        = $this->getSystemInfo('account');
		$param['TransactionID']       = $transId;
		$param['CurrencyCode']        = $this->getSystemInfo('currency', self::CURRENCY);
		$param['MemberCode']          = $transId; # Should be userId here
		$param['Amount']              = $this->convertAmountToCurrency($amount);
		$param['TransactionDatetime'] = DateTime::createFromFormat('Y-m-d H:i:s', $walletaccount['dwDateTime'])->format('Y-m-d h:i:sA');
		$param['BankCode']            = $bankInfo[$bank]['code'];
		$param['ToBankAccountName']   = $name;
		$param['ToBankAccountNumber'] = $accNum;
		$param['ToProvince']          = $province;
		$param['ToCity']              = $city;
		$param['Key']                 = $this->sign($param);

		$this->utils->debug_log("============================Help2Pay Submit withdrawal order Params: ", $param);
		return $param;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false);
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log('========================',$result);
			return $result;
		}
		if(!array_key_exists($bank, $this->getBankInfo())) {
			$this->utils->error_log("========================Help2Pay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by help2pay");
			return array('success' => false, 'message' => 'Bank not supported by Help2Pay');
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getSystemInfo('url').'/'.$this->getSystemInfo('account');
        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================Help2Pay submitWithdrawRequest decoded Result', $decodedResult);

		return $decodedResult;
	}

	/**
	 * detail: This function takes in the return value of the payment URL and translate it to the following structure
	 *
	 * @param string $resultString Result of curl_exec
	 * @return array
	 */
	public function decodeResult($resultXml) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
		$result = array('success' => false, 'message' => lang('Payout request failed'));
        $this->CI->utils->debug_log('======================================Help2Pay decodeResult resultXml', $resultXml);

		if(empty($resultXml) || strpos($resultXml, '!doctype html') !== false) {
			return $result;
		}

		$resultArray = $this->utils->xmlToArray(new SimpleXMLElement($resultXml));
		$this->utils->debug_log("============================Help2Pay parsing resultXml [$resultXml] into array", $resultArray);

		if($resultArray['statusCode'] == self::CALLBACK_STATUS_SUCCESS) {
			$result['success'] = true;
			$result['message'] = "Help2Pay withdrawal success!";
		}else{
			$result['message'] = "Help2Pay withdrawal failed, [". $resultArray['statusCode'].']: '. $resultArray['message'];
		}
		return $result;
	}

	public function getWithdrawUrl() {
		return $this->getSystemInfo('url').'/'.$this->getSystemInfo('account');
	}

	# Withdrawal verification URL
	# Corresponding validation URL: /callback/fixed_validation/143
	public function getOrderValidation($fields) {
		$valid = self::INVALID_TRANSACTION;
		$transId = isset($fields['transId']) ? $fields['transId'] : $fields['?transId'];
		$this->CI->utils->debug_log("=========================Help2Pay getOrderValidation transId", $transId);
		$response_result_id = $this->callbackFromBrowser($transId, $fields);

		if(is_null($transId)){
			$this->CI->utils->debug_log("=========================Help2Pay getOrderValidation cannot get transId", $fields);
			return $valid;
	    }

		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$this->CI->utils->debug_log("=========================Help2Pay getOrderValidation walletaccount", $walletaccount);
        if(is_null($walletaccount)){
        	$this->CI->utils->debug_log("=========================Help2Pay getOrderValidation cannot get walletaccount by transId", $transId);
        	return $valid;
        }

		$param = array();
		$param['MerchantCode']        = $this->getSystemInfo('account');
		$param['TransactionID']       = $transId;
		$param['MemberCode']          = $transId;
		$param['Amount']              = $this->convertAmountToCurrency($walletaccount['amount']);
		$param['CurrencyCode']        = $this->getSystemInfo('currency', self::CURRENCY);
		$param['TransactionDatetime'] = DateTime::createFromFormat('Y-m-d H:i:s', $walletaccount['dwDateTime'])->format('Y-m-d h:i:sA');
		$param['ToBankAccountNumber'] = $walletaccount['bankAccountNumber'];
		$param['Key']                 = $this->sign($param);

		
        if(strcasecmp($param['Key'], $fields['key']) === 0) {
        	$valid = self::VALID_TRANSACTION;
        }
        $this->CI->utils->debug_log("=========================Help2Pay getOrderValidation signing key", $param['Key']);
		return $valid;
	}

	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);

		$result = array('success' => false, 'message' => 'Payment failed');
		$order  = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$this->CI->utils->debug_log('=========================Help2Pay process withdrawalResult order id', $transId);
		$this->CI->utils->debug_log("=========================Help2Pay checkCallback params", $params);


		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

        if ($params['Status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('Help2Pay withdrawal Payment was successful: trade ID [%s]', $params['ID']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else if($params['Status'] == self::CALLBACK_STATUS_FAILED){
            $msg = sprintf('Help2Pay withdrawal payment was failed. [%s]: '.$params['Message'], $params['Status']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('Help2Pay withdrawal payment was not successful. [%s]: '.$params['Message'], $params['Status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		$requiredFields = array(
			'MerchantCode', 'TransactionID', 'CurrencyCode', 'Amount', 'TransactionDatetime', 'Key', 'Status', 'MemberCode', 'ID'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================Help2Pay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifySignature($fields)) {
			$this->writePaymentErrorLog('=========================Help2Pay withdrawal checkCallback signature Error', $fields);
			return false;
		}

		$amount = $this->convertAmountToCurrency($order['amount']);
		if ($fields['Amount'] != $amount) {
			$this->writePaymentErrorLog("======================Help2Pay withdrawal checkCallbackOrder payment amount is wrong, expected ". $amount, $fields);
			return false;
		}

		if ($fields['TransactionID'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================Help2Pay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['walletAccountId'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}


	# direct pay not supported by this API
	public function directPay($order = null) {
		return array('success' => false);
	}

	public function getBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("help2pay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
				$bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
			}
			$this->utils->debug_log("============================Getting Help2Pay bank info from extra_info: ", $bankInfo);
		} else {
	        $currency  = $this->getSystemInfo('currency', self::CURRENCY);
	        switch ($currency) {
	        	case 'CNY':
					$bankInfo = array(
						1 => array('name' => "中国工商银行", 'code' => "ICBC"),
						2 => array('name' => "中国招商银行", 'code' => "CMB"),
						3 => array('name' => "中国建设银行", 'code' => "CCB"),
						4 => array('name' => "中国农业银行", 'code' => "ABC"),
						5 => array('name' => "中国交通银行", 'code' => "BCM"),
						6 => array('name' => "中国银行", 'code' => "BOC"),
						7 => array('name' => "深圳发展银行", 'code' => "SDB"),
						8 => array('name' => "广东发展银行", 'code' => "GDB"),
						10 => array('name' => "中国中信银行", 'code' => "CNCB"),
						11 => array('name' => "中国民生银行", 'code' => "CMBC"),
						12 => array('name' => "中国邮政储蓄银行", 'code' => "PSBC"),
						13 => array('name' => "中国兴业银行", 'code' => "CIB"),
						14 => array('name' => "中国华夏银行", 'code' => "HXB"),
						15 => array('name' => "中国平安银行", 'code' => "PAB"),
						17 => array('name' => "广州银行", 'code' => "GZCB"),
						18 => array('name' => "南京银行", 'code' => "NJCB"),
						20 => array('name' => "中国光大银行", 'code' => "CEB"),
						24 => array('name' => "上海浦东发展银行", 'code' => "SPDB"),
					);
	        		break;
	        	case 'THB':
					$bankInfo = array(
						1 => array('name' => "Bangkok Bank", 'code' => "BBL"),
						2 => array('name' => "Bank Of Ayudhya", 'code' => "BOA"),
						3 => array('name' => "Government Savings Bank", 'code' => "GSB"),
						4 => array('name' => "KasiKorn Bank", 'code' => "KKR"),
						5 => array('name' => "KTB Net Bank", 'code' => "KTB"),
						6 => array('name' => "Siam Commercial Bank", 'code' => "SCB"),
						7 => array('name' => "TMB Bank Public Company Limited", 'code' => "TMB"),
						8 => array('name' => "CIMB Thai", 'code' => "CIMBT"),
						9 => array('name' => "Kiatnakin Bank", 'code' => "KNK"),
					);
	        		break;
				$this->utils->debug_log("============================Getting Help2Pay bank info from code: ", $bankInfo);
			}
		}
		return $bankInfo;
	}

	/**
	 * detail: Reference: documentation, section "Create Hash Key for Payout Submission"
	 *
	 * @param array $data
	 * @return string
	 */
	private function sign($params) {
		$securityCode = $this->getSystemInfo('key');
		$transTimestamp = DateTime::createFromFormat('Y-m-d h:i:sA', $params['TransactionDatetime'])->format('YmdHis');
		$str = $params['MerchantCode'] . $params['TransactionID'] . $params['MemberCode'] . $params['Amount'] . $params['CurrencyCode'] . $transTimestamp . $params['ToBankAccountNumber'] . $securityCode;
		$sign = md5($str);
		
		return $sign;
	}

	# Verifies the signature of callback parameters
	private function verifySignature($params) {
		$securityCode = $this->getSystemInfo('key');
		$str = $params['MerchantCode'] . $params['TransactionID'] . $params['MemberCode'] . $this->convertAmountToCurrency($params['Amount']) . $params['CurrencyCode'] . $params['Status'] . $securityCode;
		$sign = md5($str);
		
		return strcasecmp($sign, $params['Key']) === 0;
	}

	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		$convert_rate = 1;
		if($this->CI->utils->getConfig('fix_currency_conversion_rate')){
			$convert_rate = $this->CI->utils->getConfig('fix_currency_conversion_rate');
			$this->writePaymentErrorLog("======================Help2Pay convertAmountToCurrency fix_currency_conversion_rate", $convert_rate);
		}
		if(!empty($this->getSystemInfo('convert_multiplier'))){
			$convert_rate = $this->getSystemInfo('convert_multiplier');
		}

        return number_format($amount * $convert_rate, 2, '.', '');
	}
}
