<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * XBei Withdrawal
 * http://www.xbeionline.com
 *
 * XBEI_WITHDRAWAL_PAYMENT_API, ID: 155
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Secret
 *
 * Field Values:
 *
 * * URL: https://apis.xbeionline.com/API/FundsSettle/
 * * Account: ## Merchant account ##
 * * Secret: ## Pay Password ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xbei_withdrawal extends Abstract_payment_api {
	const STATUS_CODE_SUCCESS = 'Success';
	const CALLBACK_STATUS_SUCCESS = '000';
	const VALID_TRANSACTION = 'true';
	const INVALID_TRANSACTION = 'false';

	public function __construct($params = null) {
		parent::__construct($params);

		$this->CI->load->model(array('playerbankdetails', 'wallet_model'));
	}

	# -- implementation of abstract functions --

	public function getPlatformCode() {
		return XBEI_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xbei_withdrawal';
	}

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
		# Reference: Documentation section "Payout Submission Process"
		$param = array();
		$param['ClientIP'] = $this->getClientIP();
		$param['ReturnURI'] = $this->getNotifyUrl($transId);
		$param['MerchantCode'] = $this->getSystemInfo('account');
		$param['TransactionID'] = $transId;
		$param['CurrencyCode'] = 'CNY'; # For now only China banks are supported
		$param['MemberCode'] = $transId; # Should be userId here
		$param['Amount'] = $this->convertAmountToCurrency($amount);
		$param['TransactionDatetime'] = $this->getDate();

		$param['ToBankAccountName'] = $name;
		$param['ToBankAccountNumber'] = $accNum;

		# look up bank code
		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("Bank whose bankTypeId=[$bank] is not supported by xbei");
			return array('success' => false, 'message' => lang('Bank not supported by xbei'));
		}
		$param['BankCode'] = $bankInfo[$bank]['code'];

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$param['ToProvince'] = $playerBankDetails['province'];
			$param['ToCity'] = $playerBankDetails['city'];
			$param['ToBranch'] = $playerBankDetails['branch'];
		}

		$param['Key'] = $this->sign($param);

		# Extra step: save the key to walletAccount row for later verification by API
		$this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $param['Key']);

		$this->utils->debug_log("xbei Submit withdrawal order Params: ", $param);
		return $param;
	}

	/**
	 * detail: This function takes in the return value of the payment URL and translate it to the following structure
	 *
	 * @param string $resultString Result of curl_exec
	 * @return array
	 */
	public function decodeResult($resultXml) {
		$result = array('success' => false, 'message' => lang('Payout request failed'));

		if(!$resultXml) {
			return $result;
		}

		$resultArray = $this->utils->xmlToArray(new SimpleXMLElement($resultXml));
		$this->utils->debug_log("Parsing resultXml [$resultXml] into object", $resultArray);

		if(array_key_exists('head', $resultArray)) {
			# if there is a 'head' element, meaning the $resultXml is in fact a HTML page
			# most likely the call failed, and use the title as the message
			$result['success'] = false;
			$result['message'] = $resultArray['head']['title'];
		} else {
			$result['success'] = (strcasecmp($resultArray['statusCode'], self::STATUS_CODE_SUCCESS) === 0);
			$result['message'] = $resultArray['message'];
		}
		return $result;
	}

	/**
	 * Returns the API url we submit withdraw request to
	 *
	 * Reference: section "Payout Submission Process"
	 *
	 * @return string
	 */
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url').'/'.$this->getSystemInfo('account');
	}

	/**
	 * detail: xbei withdraw callback implementation
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $params) {
		$result = array('success' => false, 'message' => 'Payment failed');
		$this->CI->utils->debug_log('process withdrawalResult order id', $orderId);

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if ($params['Status'] != self::CALLBACK_STATUS_SUCCESS) {
			$msg = sprintf('Payment was not successful: payout ID [%s], status code [%s]', $params['ID'], $params['Status']);
			$this->writePaymentErrorLog($msg, $fields);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $msg;
		} else {
			$msg = sprintf('Payment was successful: payout ID [%s]', $params['ID']);
			$fee = 0; # Fee is not specified by this API
			$amount = $this->convertAmountToCurrency($params['Amount']);
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg, $fee, $amount);
			$result['message'] = $msg;
			$result['success'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		$requiredFields = array(
			'Merchant', 'TransactionID', 'CurrencyCode', 'Amount', 'TransactionDatetime', 'Key', 'Status', 'MemberCode', 'ID'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifySignature($fields)) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		if ($fields['Amount'] > $order['amount']) {
			$this->writePaymentErrorLog("Payment amount is wrong, expected <= ". $order['amount'], $fields);
			return false;
		}

		if ($fields['TransactionID'] != $order['walletAccountId']) {
			$this->writePaymentErrorLog("Order IDs do not match, expected ".$order['walletAccountId'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	/**
	 * detail: The payment call will not redirect back, so this is not implemented
	 *
	 * @param int $transId transaction id
	 * @param array $param
	 * @return array
	 */
	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	# Corresponding validation URL: /callback/fixed_validation/155
	public function getOrderValidation($params) {
		$transId = $params['transId']; # Wxxxxxxxxx string
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		# Compare input key with the key stored in walletAccount during payment request submission
		$key = $order['extra_info'];
		if(strcasecmp($key, $params['key']) === 0) {
			return self::VALID_TRANSACTION;
		} else {
			return self::INVALID_TRANSACTION;
		}
	}

	# direct pay not supported by this API
	public function directPay($order = null) {
		return array('success' => false);
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
            $this->utils->debug_log("=========================xbei bank info from extra_info: ", $bankInfo);
        } else {
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
			$this->utils->debug_log("Getting xbei bank info from code: ", $bankInfo);
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
		$transTimestamp = $this->getTimestamp();
		$str = $params['MerchantCode'] . $params['TransactionID'] . $params['MemberCode'] . $params['Amount'] . $params['CurrencyCode'] . $transTimestamp . $params['ToBankAccountNumber'] . $securityCode;
		$sign = md5($str);
		return $sign;
	}

	# Verifies the signature of callback parameters
	private function verifySignature($params) {
		$securityCode = $this->getSystemInfo('key');
		$str = $params['MerchantCode'] . $params['TransactionID'] . $params['MemberCode'] . $params['Amount'] . $params['CurrencyCode'] . $params['StatusCode'] . $securityCode;
		$sign = md5($str);
		return strcasecmp($sign, $params['Key']) === 0;
	}

	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private $_time;

	private function getDate() {
		if(!$this->_time) {
			$this->_time = time();
		}
		# e.g. 2012-05-01 08:04:00AM
		return date('Y-m-d h:i:sA', $this->_time);
	}

	private function getTimestamp() {
		if(!$this->_time) {
			$this->_time = time();
		}
		return date('YmdHis', $this->_time);
	}
}
