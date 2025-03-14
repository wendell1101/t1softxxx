<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Funpay (乐盈)
 * http://www.funpay.com
 *
 * FUNPAY_PAYMENT_API, ID: 89
 *
 * Note: This API is used to send payment.
 *
 * Required Fields:
 *
 * * URL
 * * Key – Signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * Live URL: https://www.funpay.com/website/BatchPay.htm
 * * Sandbox URL: https://www.funpay.com/website/BatchPay.htm
 * * Extra Info
 * > {
 * >      "funpay_merchant_code": "## Merchant Code ##",
 * >      "funpay_bankInfo" : "## Bank Info (optional) ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_funpay extends Abstract_payment_api {
	const CHARSET_UTF_8 = 1;
	const SIGN_TYPE_MD5 = 2;
	const PAYEE_TYPE_PERSONAL = 1;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->CI->load->model(array('wallet_model', 'payment'));

		$this->info = $this->getInfoByEnv();
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return FUNPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'funpay';
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	# Ref: Documentation 5.1.1
	## Note that the $bank is the bank_type ID, we should map it to the bank_code and bank_name required by this API
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$params = array();
		$params['MERCHANT_CODE'] = $this->getSystemInfo('funpay_merchant_code');
		$params['BIZ_NO'] = $transId;
		$params['TOTAL_AMOUNT'] = $this->convertAmountToCurrency($amount);
		$params['TOTAL_COUNT'] = 1;
		$params['REQUEST_TIME'] = date('YmdHis');

		# Populate PAY_ITEM
		$transactionDetail = $this->getTransactionDetail($transId);
		$bankInfo = $this->getFunpayBankInfo()[$bank];
		## Get bank details
		$bankName = $bankInfo['name'];
		$bankCity = $transactionDetail['city'];
		$bankProvince = $transactionDetail['province'];
		$bankBranch = $transactionDetail['branch'];

		$params['PAY_ITEM'] = $this->getPayItem(array(
			'ORDER_ID' => $transId,
			'PAYEE_NAME' => $name,
			'PAYEE_ACCOUNT' => $accNum,
			'AMOUNT' => $this->convertAmountToCurrency($amount),
			'NOTE' => lang('Withdrawal').': '.$transId, # Display to beneficary
			'REMARK' => $transId, # For system record
			'BANK_NAME' => $bankName,
			'PROVINCE' => $bankProvince,
			'CITY' => $bankCity,
			'BRANCH' => $bankBranch,
			'PAYEE_TYPE' => self::PAYEE_TYPE_PERSONAL,
		));

		$params['VERSION'] = 'V1.0';
		$params['CHARSET'] = self::CHARSET_UTF_8;
		$params['SIGN_TYPE'] = self::SIGN_TYPE_MD5;
		$params['SIGNVALUE'] = $this->sign($params);
		return $params;
	}

	# Ref: Documentation 5.1.1
	private function getPayItem($params) {
		$keys = array('ORDER_ID', 'PAYEE_NAME', 'PAYEE_ACCOUNT', 'AMOUNT', 'PAYEE_MOBILE',
			'NOTE', 'REMARK', 'BANK_NAME', 'PROVINCE', 'CITY', 'BRANCH', 'PAYEE_TYPE');

		$retStr = '';
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$retStr .= $params[$key].',';
			} else {
				$retStr .= ',';
			}
		}

		return rtrim($retStr, ',');
	}

	# Ref: Documentation 4.2, 5.1.2
	public function decodeResult($resultString) {
		$this->utils->debug_log("Result String: ", $resultString);

		$params = array();
		parse_str($resultString, $params);

		if (!$this->validateSign($params)) {
			$result['success'] = false;
			$result['message'] = 'Invalid signature';
			return $result;
		}

		$result['success'] = ($params['SUCCESS_COUNT'] == 1);
		$result['message'] = $params['ERROR_MSG'];

		# Payment is successful, continue to extract more info
		if ($result['success']) {
			$result['successAmount'] = $params['SUCCESS_AMOUNT'];
			$result['payItem'] = $this->decodePayItem($params); # TODO
		}

		return $result;
	}

	private function decodePayItem($payItemStr) {
		$keys = array('ORDER_ID', 'PAYEE_NAME', 'PAYEE_ACCOUNT', 'AMOUNT', 'PAYEE_MOBILE',
			'NOTE', 'REMARK', 'BANK_NAME', 'PROVINCE', 'CITY', 'BRANCH', 'PAYEE_TYPE');
		$payItemStrParams = explode(',', $payItemStr);
		$payItem = array();
		$index = 0;
		foreach ($keys as $key) {
			$payItem[$key] = $payItemStrParams[$index++];
		}
		return $payItem;
	}

	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	public function callbackFromServer($transId, $paramsRaw) {
		$this->utils->debug_log("Funpay callback from server:", $paramsRaw);
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

		if (!$transId) {
			$result['message'] = lang('error.payment.failed');
			return $result;
		}

		# Results are passed in as "responseParameters"
		# Ref: Documentation section 5.2.1
		$params = $this->decodeResult($paramsRaw['responseParameters']);

		if(!$params['success']) {
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $params['message']);
		} else {
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId);
		}

		# No matter whether the payment is successful, we need to return success to indicate to the gateway the callback
		# has been processed
		$result['success'] = true;
		$result['message'] = ''; # Not specified in docs
		return $result;
	}

	private function checkCallbackParams($transId, $params) {
		$transactionDetail = $this->getTransactionDetail($transId);

		if(!$transactionDetail || empty($transactionDetail)) {
			$this->utils->debug_log("Error: cannot find transactionCode", $transId);
			return false;
		}

		if($transactionDetail['amount'] != $params['successAmount']) {
			$this->utils->debug_log("Error: amount does not match. Callback: ".$params['successAmount'].", Local: ".$transactionDetail[0]['amount']);
			return false;
		}

		return true;
	}

	private function getTransactionDetail($transId) {
		$this->CI->load->model(['wallet_model', 'withdraw_condition']);

		$walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if(!$walletAccount || empty($walletAccount['walletAccountId'])) {
			return false;
		}

		$transactionDetail = $this->CI->withdraw_condition->getWithdrawalTransactionDetail($walletAccount['walletAccountId']);

		if(!is_array($transactionDetail) || empty($transactionDetail)) {
			return false;
		}

		# Only take the first row from query
		$this->utils->debug_log("Obtained transaction detail for [$transId]", $transactionDetail[0]);
		return $transactionDetail[0];
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($transId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $transId);
	}

	/**
	 * Ref: Documentation Appendex 1
	 * [
	 * [1, "中国工商银行"],
	 * [4, "中国农业银行"],
	 * [6, "中国银行"],
	 * [3, "中国建设银行"],
	 * [5, "交通银行"],
	 * [10, "中信银行"],
	 * [20, "中国光大银行"],
	 * [14, "华夏银行"],
	 * [11, "中国民生银行"],
	 * [15, "平安银行"],
	 * [8, "广东发展银行"],
	 * [2, "招商银行"],
	 * [13, "兴业银行"],
	 * [24, "上海浦东发展银行"],
	 * [17, "广州银行"],
	 * [12, "中国邮政储蓄银行"]
	 * ]
	 *
	 * detail: Defines the mapping from system bank to API bank., Can be defined in extra_info as array of arrays
	 *
	 * note: Currently use bank_type['id'] as key, might change to bank_type['code']
	 * @return array;
	 */
	public function getFunpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("funpay_bankInfo");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1]);
			}
			$this->utils->debug_log("Getting funpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				1 => array('name' => "中国工商银行"),
				4 => array('name' => "中国农业银行"),
				6 => array('name' => "中国银行"),
				3 => array('name' => "中国建设银行"),
				5 => array('name' => "交通银行"),
				10 => array('name' => "中信银行"),
				20 => array('name' => "中国光大银行"),
				14 => array('name' => "华夏银行"),
				11 => array('name' => "中国民生银行"),
				15 => array('name' => "平安银行"),
				8 => array('name' => "广东发展银行"),
				2 => array('name' => "招商银行"),
				13 => array('name' => "兴业银行"),
				24 => array('name' => "上海浦东发展银行"),
				17 => array('name' => "广州银行"),
				12 => array('name' => "中国邮政储蓄银行"),
			);
			$this->utils->debug_log("Getting funpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	## Reference: Documentation section 5.1.1
	public function sign($params) {
		$keys = array(
			"MERCHANT_CODE", "BIZ_NO", "TOTAL_AMOUNT", "TOTAL_COUNT",
			"REQUEST_TIME", "PAY_ITEM", "VERSION", "CHARSET", "SIGN_TYPE"
		);
		$signStr = "";

		foreach($keys as $key){
			$signStr .= $key.'='.$params[$key].'&';
		}

		$signStr .= $this->getSystemInfo('funpay_long_md5key');
		$this->utils->debug_log("Signing: $signStr");
		return md5($signStr);
	}

	public function validateSign($params) {
		$sign = $this->sign($params);
		return strncmp($params['SIGNVALUE'], $sign) == 0;
	}

	## Reference: Documentation section 5.1.1, amount in cent
	protected function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 0, '.', '');
	}


}
