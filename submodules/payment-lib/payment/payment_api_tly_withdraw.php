<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TLY Withdrawal
 * http://www.41.cn
 *
 * TLY_WITHDRAW_PAYMENT_API, ID: 98
 *
 * Required Fields:
 *
 * * URL
 * * Key - apikey
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: https://www.tly-transfer.com/sfisapi/
 * * Extra Info:
 * 	{
 * 		"tly_company_name" : "## TLY Company Name ##"
 * 		"tly_bank_info" : [
 * 			[1, "中国工商银行", "ICBC"], [4, "中国农业银行", "ABC"], [6, "中国银行", "BOC"], [3, "中国建设银行", "CCB"], [5, "中国交通银行", "BCM"], [10, "中国中信银行", "CNCB"], [20, "中国光大银行", "CEB"], [14, "中国华夏银行", "HXB"], [11, "中国民生银行", "CMBC"], [15, "中国平安银行", "PAB"], [8, "广东发展银行", "GDB"], [2, "中国招商银行", "CMB"], [13, "中国兴业银行", "CIB"], [18, "南京银行", "NJCB"], [17, "广州银行", "GZCB"], [12, "中国邮政储蓄银行", "PSBC"]
 * 		]
 *	}
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_tly_withdraw extends Abstract_payment_api {
	const TRANS_MODE_PAYOUT = "out_trans"; # 出款
	const ORDER_STATUS_SUCCESS = "SUCCESS";

	public function __construct($params = null) {
		parent::__construct($params);

		$this->CI->load->model(array('playerbankdetails'));
	}

	# -- implementation of abstract functions --

	/**
	 * detail: Get the platform code from the constant file
	 *
	 * @return string
	 */
	public function getPlatformCode() {
		return TLY_WITHDRAW_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tly_withdraw';
	}

	/**
	 * detail: override common API functions
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	# Overwrite abstract_payment_api's method to implement custom form post with signature in header
	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false);
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$data = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		if(array_key_exists('success', $data) && !$data['success']) {
			return $data;
		}

		$url=$this->getSystemInfo('url');
		$resp = $this->postForm($url, $data);

		$this->utils->debug_log("Post data", $data, "Result", $resp);

		$decodedResult = $this->decodeResult($resp);
		$this->utils->debug_log("Decoded Result", $decodedResult);
		return $decodedResult;
	}

	/**
	 * detail: Initiates a withdraw request
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
		# Reference: Documentation section 2
		# ---- First add bank card entry ----
		$param = array();
		$param['module'] = 'bankcard';
		$param['method'] = 'api_add_member_card';
		$param['company_name'] = $this->getSystemInfo('tly_company_name');

		# payloads - Reference: Documentation section 3
		$param['payload'] = array(
			'card_number' => $accNum,
			'real_name' => $name,
			'bank_city' => '',
			'bank_branch' => '',
			'bank_flag' => '',
			'bank_area' => '中国',
			'bank_provinces' => '',
			'trans_mode' => self::TRANS_MODE_PAYOUT,
		);

		# look up bank code
		$bankInfo = $this->getTLYBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("Bank whose bankTypeId=[$bank] is not supported by TLY");
			return array('success' => false, 'message' => lang('Bank not supported by TLY'));
		}
		$param['payload']['bank_flag'] = $bankInfo[$bank]['code'];

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$param['payload']['bank_city'] = $playerBankDetails['city'];
			$param['payload']['bank_branch'] = $playerBankDetails['branch'];
			$param['payload']['bank_provinces'] = $playerBankDetails['province'];
		}

		$jsonParam = json_encode($param, JSON_UNESCAPED_UNICODE);
		# Result of adding bank card. Since bank card may already exist, continue execution if any error
		$url=$this->getSystemInfo('url');
		$resp = $this->postForm($url, $jsonParam);
		$this->utils->debug_log("api_add_member_card returns: ", $resp);

		# ---- Now return the param to submit withdraw order ----
		$param = array();
		$param['module'] = 'order';
		$param['method'] = 'api_add_order';
		$param['company_name'] = $this->getSystemInfo('tly_company_name');

		# payloads - Reference: Documentation section 2
		$param['payload'] = array(
			'card_number' => $accNum,
			'amount' => $this->convertAmountToCurrency($amount),
			'trans_mode' => self::TRANS_MODE_PAYOUT,
			'order_number' => $transId,
		);

		$this->utils->debug_log("TLY Submit withdrawal order Params: ", $param);
		$jsonParam = json_encode($param, JSON_UNESCAPED_UNICODE);
		return $jsonParam;
	}

	/**
	 * detail: This function takes in the return value of the payment URL and translate it to the following structure
	 *
	 * @param string $resultString
	 * @return array
	 */
	public function decodeResult($resultObj) {
		# The result is for api_add_order
		# Reference: Documentation section 2
		$result['success'] = $resultObj->success;
		if(isset($resultObj->message)) {
			$result['message'] = $resultObj->message;
		} else {
			$result['message'] = 'SUCCESS';
		}

		return $result;
	}

	/**
	 * detail: get withdraw URL
	 *
	 * @return string
	 */
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	public function checkWithdrawStatus($transId) {
		# ---- First add bank card entry ----
		$param = array();
		$param['module'] = 'order';
		$param['method'] = 'api_get_order_status';
		$param['company_name'] = $this->getSystemInfo('tly_company_name');

		# payloads - Reference: Documentation section 4
		$param['payload'] = array(
			'order_number' => $transId,
		);

		$jsonParam = json_encode($param, JSON_UNESCAPED_UNICODE);
		$url=$this->getSystemInfo('url');
		$resp = $this->postForm($url, $jsonParam);
		$this->utils->debug_log("api_get_order_status returns: ", $resp);

		if (!$resp->success) {
			$errorMsg = $this->getMessage($resp->error_code);
			return array('success' => false, 'payment_fail' => true, 'message' => ($errorMsg ?: $resp->message));
		}
		elseif ($resp->data->status != self::ORDER_STATUS_SUCCESS) {
			return array('success' => false, 'payment_fail' => false, 'message' => $this->getMessage($resp->data->status));
		}
		return array('success' => true, 'message' => $this->getMessage($resp->data->status));
	}

	/**
	 * detail: TLY withdraw does not have callback
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $paramsRaw) {
		return array('success' => false, 'message' => 'Error: not implemented');
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

	# direct pay not supported by this API
	public function directPay($order = null) {
		return array('success' => false);
	}

	/**
	 *
	 * [
	 * [1, "中国工商银行", "ICBC"],
	 * [4, "中国农业银行", "ABC"],
	 * [6, "中国银行", "BOC"],
	 * [3, "中国建设银行", "CCB"],
	 * [5, "中国交通银行", "BCM"],
	 * [10, "中国中信银行", "CNCB"],
	 * [20, "中国光大银行", "CEB"],
	 * [14, "中国华夏银行", "HXB"],
	 * [11, "中国民生银行", "CMBC"],
	 * [15, "中国平安银行", "PAB"],
	 * [8, "广东发展银行", "GDB"],
	 * [2, "中国招商银行", "CMB"],
	 * [13, "中国兴业银行", "CIB"],
	 * [18, "南京银行", "NJCB"],
	 * [17, "广州银行", "GZCB"],
	 * [12, "中国邮政储蓄银行", "PSBC"]
	 * ]
	 *
	 * Not supported:
	 * [7, "深圳发展银行", "307584007998"]
	 * [24, "上海浦东发展银行", "310290000013"]
	 * [19, "广东省广州农村商业银行", "314581000011"]
	 * [9, "广东省东莞农村商业银行", "402602000018"]
	 * [16, "广西壮族自治区农村信用社联合社", "402611099974"]
	 *
	 * detail: Defines the mapping from system bank to API bank., Can be defined in extra_info as array of arrays
	 *
	 * note: Currently use bank_type['id'] as key, might change to bank_type['code']
	 * @return array;
	 */
	public function getTLYBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("tly_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("Getting TLY bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				1 =>  array('name' => "中国工商银行", 'code' => "ICBC"),
				4 =>  array('name' => "中国农业银行", 'code' => "ABC"),
				6 =>  array('name' => "中国银行", 'code' => "BOC"),
				3 =>  array('name' => "中国建设银行", 'code' => "CCB"),
				5 =>  array('name' => "中国交通银行", 'code' => "BCM"),
				10 =>  array('name' => "中国中信银行", 'code' => "CNCB"),
				20 =>  array('name' => "中国光大银行", 'code' => "CEB"),
				14 =>  array('name' => "中国华夏银行", 'code' => "HXB"),
				11 =>  array('name' => "中国民生银行", 'code' => "CMBC"),
				15 =>  array('name' => "中国平安银行", 'code' => "PAB"),
				8 =>  array('name' => "广东发展银行", 'code' => "GDB"),
				2 =>  array('name' => "中国招商银行", 'code' => "CMB"),
				13 =>  array('name' => "中国兴业银行", 'code' => "CIB"),
				18 =>  array('name' => "南京银行", 'code' => "NJCB"),
				17 =>  array('name' => "广州银行", 'code' => "GZCB"),
				12 =>  array('name' => "中国邮政储蓄银行", 'code' => "PSBC"),
			);
			$this->utils->debug_log("Getting TLY bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	/**
	 * detail: Reference: documentation, section 5.2.3
	 *
	 * @param array $data
	 * @return string
	 */
	public function sign($data) {
		$apiKey = $this->getSystemInfo('key');
		$this->utils->debug_log("Signing [$data] with [$apiKey]");
		return base64_encode(hash_hmac('sha256', $data, $apiKey, true));
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

	# Posts request form to the configured TLY URL. Handles signing too.
	public function postForm($url, $data) {
		try {
			$signature = $this->sign($data);
			$this->CI->utils->debug_log('POSTing TLY form for form data and signature:', $data, $signature);
			$response = \Httpful\Request::post($url)
				->method(\Httpful\Http::POST)
				->addHeader('TLYHMAC', $signature)
				->expectsJson()
				->body($data)
				->sendsType(\Httpful\Mime::FORM)
				->send();
			$this->CI->utils->debug_log('response', $response->body);
			return $response->body;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
		}
	}

	private function getMessage($statusCode) {
		$status = array(
			"CREATED" => "已创建",
			"REVOKED" => "已撤销",
			"EXECUTING" => "执行中",
			"SUCCESS" => "成功",
			"FAIL" => "失败",
			"READY" => "等待执行",
			"LAST_STEP" => "最后一步",
			"SUSPEND" => "挂起",
			"1001" => "未知错误",
			"1002" => "转账模式错误",
			"1003" => "卡信息有误",
			"1004" => "不支持的银行",
			"1005" => "卡号非数字",
			"1006" => "卡省份城市错误",
			"1007" => "卡号已存在",
			"1008" => "卡登录名已存在",
			"1009" => "卡号不存在",
			"1010" => "没有收款卡",
			"1011" => "金额格式错误，非数字或则小于0",
			"1012" => "订单号长度大于24",
			"1013" => "订单号已存在",
			"1014" => "订单不存在",
			"1015" => "没有权限",
			"1016" => "登录超时",
			"1017" => "密码错误超过3次",
			"1019" => "数据格式错误，非json格式",
			"1038" => "IP地址不在白名单",
			"1036" => "签名验证失败",
		);

		if(array_key_exists($statusCode, $status)) {
			return $statusCode . " - " .$status[$statusCode];
		} else {
			return '';
		}
	}

}
