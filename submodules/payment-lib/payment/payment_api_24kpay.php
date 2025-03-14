<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 24K Pay
 * http://www.24kpay.com
 *
 * PAY24K_PAYMENT_API, ID: 48
 *
 * General behaviors include
 *
 * * Getting platform code
 * * Generate payment form
 * * Getting withdrawal details
 * * Receiving call backs from server and browser
 * * Get 24k bank info
 *
 * Required Fields:
 *
 * * URL
 * * Key – Signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * Live URL: http://api.24kpay.com/
 * * Sandbox URL: http://test.24kpay.com/mapi/
 * * Extra Info
 > {
 > "24kpay_merchantId": "##merchant ID##",
 > "24kpay_bankInfo": "[[1, \"中国工商银行\", \"102100099996\"], [4, \"中国农业银行\", \"103100000026\"], [6, \"中国银行\", \"104100000004\"], [3, \"中国建设银行\", \"105100000017\"], [5, \"中国交通银行\", \"301290000007\"], [10, \"中国中信银行\", \"302100011000\"], [20, \"中国光大银行\", \"303100000006\"], [14, \"中国华夏银行\", \"304100040000\"], [11, \"中国民生银行\", \"305100000013\"], [15, \"中国平安银行\", \"307584007998\"], [7, \"深圳发展银行\", \"307584007998\"], [8, \"广东发展银行\", \"306581000003\"], [2, \"中国招商银行\", \"308584000013\"], [13, \"中国兴业银行\", \"309391000011\"], [24, \"上海浦东发展银行\", \"310290000013\"], [18, \"南京银行\", \"313301008887\"], [17, \"广州银行\", \"313581003284\"], [19, \"广东省广州农村商业银行\", \"314581000011\"], [9, \"广东省东莞农村商业银行\", \"402602000018\"], [16, \"广西壮族自治区农村信用社联合社\", \"402611099974\"], [12, \"中国邮政储蓄银行\", \"403100000004\"] ]"
 > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_24kpay extends Abstract_payment_api {
	const PAY24K_VERSION = "2.0";
	const PAY24K_CURRENCY = "RMB";
	const PAY24K_CODE_SINGLE_PAY = "1002";
	const PAY24K_RESULT_CODE_SUCCESS = "1000";
	const PAY24K_ORDER_STATUS_SUCCESS = 2;
	const RETURN_SUCCESS_CODE = '{"received":"1"}';

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->CI->load->model(array('wallet_model', 'payment'));

		$this->info = $this->getInfoByEnv();
	}

	# -- implementation of abstract functions --

	/**
	 * detail: Get the platform code from the constant file
	 *
	 * @return string
	 */
	public function getPlatformCode() {
		return PAY24K_PAYMENT_API;
	}

	public function getPrefix() {
		return '24kpay';
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
		# bank info as input
		$params = array(
			'inAcctNum' => $accNum,
			'inAcctName' => strtoupper(urlencode($name)),
		);

		# look up bank code
		$bankInfo = $this->get24KBankInfo()[$bank];
		$params['inBankCode'] = $bankInfo['code'];
		$params['inBankName'] = strtoupper(urlencode($bankInfo['name']));

		# other parameters
		$params['tranCode'] = self::PAY24K_CODE_SINGLE_PAY;
		$params['version'] = self::PAY24K_VERSION;
		$params['currencyCode'] = self::PAY24K_CURRENCY;

		# order-related params
		$params['merchantOrderNum'] = $transId;
		$params['callBackUrl'] = $this->getNotifyUrl($transId);
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['tranTime'] = (new DateTime())->format('U')."000";

		# sign param
		$params['signData'] = $this->signSinglePay($params);

		# Submit result in json format using a single parameter
		# Reference: documentation section 1
		$jsonParam['requestMsg'] = json_encode($params);

		return $jsonParam;
	}

	/**
	 * detail: This function takes in the return value of the payment URL and translate it to the following structure
	 *
	 * @param string $resultString
	 * @return array
	 */
	public function decodeResult($resultString) {
		$resultJson = json_decode($resultString);

		$result['success'] = ($resultJson->resultCode == self::PAY24K_RESULT_CODE_SUCCESS);
		$result['message'] = $resultJson->resultMsg;

		return $result;
	}

	/**
	 * detail: get withdraw URL
	 *
	 * @return string
	 */
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url').$this->getSystemInfo('24kpay_merchantId').'/mapiAction.json';
	}

	/**
	 * detail: This will be called when the payment is completed, API server calls our callback page
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $paramsRaw) {
		$this->utils->debug_log("24K callback from server:", $paramsRaw);
		$params = json_decode($paramsRaw['callBackMsg'], true);
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

		if (!$transId || !$this->checkCallbackParams($transId, $params)) {
			return $result;
		}

		# callback message is valid, now check whether the message indicates success
		if($params['orderStatus'] != self::PAY24K_ORDER_STATUS_SUCCESS) {
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $params['failReason']);
		} else {
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId);
		}

		# No matter whether the payment is successful, we need to return success to indicate to the gateway the callback
		# has been processed
		$result['success'] = true;
		$result['message'] = self::RETURN_SUCCESS_CODE;  # This will be printed using $this->returnText in callback.php
		return $result;
	}

	/**
	 * detail: Validates whether the callback data is authentic
	 *
	 * @param int $transId transaction id
	 * @param array $params
	 * @return boolean
	 */
	private function checkCallbackParams($transId, $params) {
		$this->CI->load->model(['wallet_model', 'withdraw_condition']);

		$walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if(!$walletAccount || empty($walletAccount['walletAccountId'])) {
			$this->utils->debug_log("Error: cannot find transactionCode", $transId);
			return false;
		}

		$transactionDetail = $this->CI->withdraw_condition->getWithdrawalTransactionDetail($walletAccount['walletAccountId']);

		if($transactionDetail[0]['bankAccountFullName'] != $params['inAcctName'] ||
			$transactionDetail[0]['bankAccountNumber'] != $params['inAcctNum']) {
			$this->utils->debug_log("Error: account name or account number do not match");
			return false;
		}

		if($transactionDetail[0]['amount'] != $params['amount']) {
			$this->utils->debug_log("Error: amount does not match. Callback: ".$params['amount'].", Local: ".$transactionDetail[0]['amount']);
			return false;
		}

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

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $transId transaction id
	 * @return array
	 */
	private function getNotifyUrl($transId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $transId);
	}

	/**
	 *
	 * [
	 * [1, "中国工商银行", "102100099996"],
	 * [4, "中国农业银行", "103100000026"],
	 * [6, "中国银行", "104100000004"],
	 * [3, "中国建设银行", "105100000017"],
	 * [5, "中国交通银行", "301290000007"],
	 * [10, "中国中信银行", "302100011000"],
	 * [20, "中国光大银行", "303100000006"],
	 * [14, "中国华夏银行", "304100040000"],
	 * [11, "中国民生银行", "305100000013"],
	 * [15, "中国平安银行", "307584007998"],
	 * [7, "深圳发展银行", "307584007998"],
	 * [8, "广东发展银行", "306581000003"],
	 * [2, "中国招商银行", "308584000013"],
	 * [13, "中国兴业银行", "309391000011"],
	 * [24, "上海浦东发展银行", "310290000013"],
	 * [18, "南京银行", "313301008887"],
	 * [17, "广州银行", "313581003284"],
	 * [19, "广东省广州农村商业银行", "314581000011"],
	 * [9, "广东省东莞农村商业银行", "402602000018"],
	 * [16, "广西壮族自治区农村信用社联合社", "402611099974"],
	 * [12, "中国邮政储蓄银行", "403100000004"]
	 * ]
	 *
	 * detail: Defines the mapping from system bank to API bank., Can be defined in extra_info as array of arrays
	 *
	 * note: Currently use bank_type['id'] as key, might change to bank_type['code']
	 * @return array;
	 */
	public function get24KBankInfo() {
		$bankInfo = array();
		$bankInfoString = $this->getSystemInfo("24kpay_bankInfo");
		if($bankInfoString) {
			$bankInfoArr = json_decode($bankInfoString, true);
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("Getting 24k bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				1 => array('name' => '中国工商银行', 'code' => '102100099996'),
				4 => array('name' => '中国农业银行', 'code' => '103100000026'),
				6 => array('name' => '中国银行', 'code' => '104100000004'),
				3 => array('name' => '中国建设银行', 'code' => '105100000017'),
				5 => array('name' => '中国交通银行', 'code' => '301290000007'),
				10 => array('name' => '中国中信银行', 'code' => '302100011000'),
				20 => array('name' => '中国光大银行', 'code' => '303100000006'),
				14 => array('name' => '中国华夏银行', 'code' => '304100040000'),
				11 => array('name' => '中国民生银行', 'code' => '305100000013'),
				15 => array('name' => '中国平安银行', 'code' => '307584007998'),
				7 => array('name' => '深圳发展银行', 'code' => '307584007998'),
				8 => array('name' => '广东发展银行', 'code' => '306581000003'),
				2 => array('name' => '中国招商银行', 'code' => '308584000013'),
				13 => array('name' => '中国兴业银行', 'code' => '309391000011'),
				24 => array('name' => '上海浦东发展银行', 'code' => '310290000013'),
				#array('name' => '北京银行', 'code' => '313100000013'),
				#array('name' => '天津银行', 'code' => '313110000017'),
				#array('name' => '河北银行', 'code' => '313121006888'),
				#array('name' => '河北省邯郸市商业银行', 'code' => '313127000013'),
				#array('name' => '邢台银行', 'code' => '313131000016'),
				#array('name' => '河北省张家口市商业银行', 'code' => '313138000019'),
				#array('name' => '承德银行', 'code' => '313141052422'),
				#array('name' => '沧州银行', 'code' => '313143005157'),
				#array('name' => '廊坊银行', 'code' => '313146000019'),
				#array('name' => '晋商银行', 'code' => '313161000017'),
				#array('name' => '晋城银行', 'code' => '313168000003'),
				#array('name' => '内蒙古银行', 'code' => '313191000011'),
				#array('name' => '内蒙古自治区包头市商业银行', 'code' => '313192000013'),
				#array('name' => '鄂尔多斯银行', 'code' => '313205057830'),
				#array('name' => '大连银行', 'code' => '313222080002'),
				#array('name' => '辽宁省鞍山市商业银行', 'code' => '313223007007'),
				#array('name' => '锦州银行', 'code' => '313227000012'),
				#array('name' => '葫芦岛银行', 'code' => '313227600018'),
				#array('name' => '营口银行', 'code' => '313228000276'),
				#array('name' => '阜新银行', 'code' => '313229000008'),
				#array('name' => '吉林银行', 'code' => '313241066661'),
				#array('name' => '哈尔滨银行', 'code' => '313261000018'),
				#array('name' => '龙江银行', 'code' => '313261099913'),
				#array('name' => '上海银行', 'code' => '313290000017'),
				18 => array('name' => '南京银行', 'code' => '313301008887'),
				#array('name' => '江苏银行', 'code' => '313301099999'),
				#array('name' => '苏州银行', 'code' => '313305066661'),
				#array('name' => '江苏长江商业银行', 'code' => '313312300018'),
				#array('name' => '杭州银行', 'code' => '313331000014'),
				#array('name' => '宁波银行', 'code' => '313332082914'),
				#array('name' => '温州银行', 'code' => '313333007331'),
				#array('name' => '嘉兴银行', 'code' => '313335081005'),
				#array('name' => '湖州银行', 'code' => '313336071575'),
				#array('name' => '绍兴银行', 'code' => '313337009004'),
				#array('name' => '浙江稠州商业银行', 'code' => '313338707013'),
				#array('name' => '台州银行', 'code' => '313345001665'),
				#array('name' => '浙江泰隆商业银行', 'code' => '313345010019'),
				#array('name' => '浙江民泰商业银行', 'code' => '313345400010'),
				#array('name' => '福建海峡银行', 'code' => '313391080007'),
				#array('name' => '厦门银行', 'code' => '313393080005'),
				#array('name' => '泉州银行', 'code' => '313397075189'),
				#array('name' => '南昌银行', 'code' => '313421087506'),
				#array('name' => '赣州银行', 'code' => '313428076517'),
				#array('name' => '上饶银行', 'code' => '313433076801'),
				#array('name' => '齐鲁银行', 'code' => '313451000019'),
				#array('name' => '青岛银行', 'code' => '313452060150'),
				#array('name' => '齐商银行', 'code' => '313453001017'),
				#array('name' => '枣庄银行', 'code' => '313454000016'),
				#array('name' => '山东省东营市商业银行', 'code' => '313455000018'),
				#array('name' => '烟台银行', 'code' => '313456000108'),
				#array('name' => '潍坊银行', 'code' => '313458000013'),
				#array('name' => '济宁银行', 'code' => '313461000012'),
				#array('name' => '山东省泰安市商业银行', 'code' => '313463000993'),
				#array('name' => '莱商银行', 'code' => '313463400019'),
				#array('name' => '山东省威海市商业银行', 'code' => '313465000010'),
				#array('name' => '德州银行', 'code' => '313468000015'),
				#array('name' => '临商银行', 'code' => '313473070018'),
				#array('name' => '日照银行', 'code' => '313473200011'),
				#array('name' => '郑州银行', 'code' => '313491000232'),
				#array('name' => '河南省开封市商业银行', 'code' => '313492070005'),
				#array('name' => '洛阳银行', 'code' => '313493080539'),
				#array('name' => '河南省漯河市商业银行', 'code' => '313504000010'),
				#array('name' => '河南省商丘市商业银行', 'code' => '313506082510'),
				#array('name' => '南阳银行', 'code' => '313513080408'),
				#array('name' => '汉口银行', 'code' => '313521000011'),
				#array('name' => '长沙银行', 'code' => '313551088886'),
				17 => array('name' => '广州银行', 'code' => '313581003284'),
				#array('name' => '珠海华润银行', 'code' => '313585000990'),
				#array('name' => '广东华兴银行', 'code' => '313586000006'),
				#array('name' => '广东南粤银行', 'code' => '313591001001'),
				#array('name' => '东莞银行', 'code' => '313602088017'),
				#array('name' => '广西北部湾银行', 'code' => '313611001018'),
				#array('name' => '柳州银行', 'code' => '313614000012'),
				#array('name' => '桂林银行', 'code' => '313617000018'),
				#array('name' => '重庆银行', 'code' => '313653000013'),
				#array('name' => '四川省自贡市商业银行', 'code' => '313655091983'),
				#array('name' => '四川省攀枝花市商业银行', 'code' => '313656000019'),
				#array('name' => '德阳银行', 'code' => '313658000014'),
				#array('name' => '四川省绵阳市商业银行', 'code' => '313659000016'),
				#array('name' => '贵州省贵阳市商业银行', 'code' => '313701098010'),
				#array('name' => '富滇银行', 'code' => '313731010015'),
				#array('name' => '西安银行', 'code' => '313791000015'),
				#array('name' => '长安银行', 'code' => '313791030003'),
				#array('name' => '兰州银行', 'code' => '313821001016'),
				#array('name' => '青海银行', 'code' => '313851000018'),
				#array('name' => '宁夏银行', 'code' => '313871000007'),
				#array('name' => '新疆自治区乌鲁木齐市商业银行', 'code' => '313881000002'),
				#array('name' => '昆仑银行', 'code' => '313882000012'),
				#array('name' => '江苏省太仓农村商业银行', 'code' => '314305106644'),
				#array('name' => '江苏省昆山农村商业银行', 'code' => '314305206650'),
				#array('name' => '江苏省吴江农村商业银行', 'code' => '314305400015'),
				#array('name' => '江苏常熟农村商业银行', 'code' => '314305506621'),
				#array('name' => '江苏省张家港农村商业银行', 'code' => '314305670002'),
				19 => array('name' => '广东省广州农村商业银行', 'code' => '314581000011'),
				#array('name' => '广东省佛山顺德农村商业银行', 'code' => '314588000016'),
				#array('name' => '海南省海口联合农村商业银行', 'code' => '314641000014'),
				#array('name' => '重庆农村商业银行', 'code' => '314653000011'),
				#array('name' => '北京农村商业银行', 'code' => '402100000018'),
				#array('name' => '广东省深圳农村商业银行', 'code' => '402584009991'),
				#array('name' => '恒丰银行', 'code' => '315456000105'),
				#array('name' => '浙商银行', 'code' => '316331000018'),
				#array('name' => '浙江商业银行', 'code' => '316331000018'),
				#array('name' => '天津农村合作银行', 'code' => '317110010019'),
				#array('name' => '渤海银行', 'code' => '318110000014'),
				#array('name' => '徽商银行', 'code' => '319361000013'),
				#array('name' => '北京顺义银座村镇银行', 'code' => '320100010011'),
				#array('name' => '浙江景宁银座村镇银行', 'code' => '320343800019'),
				#array('name' => '浙江三门银座村镇银行', 'code' => '320345790018'),
				#array('name' => '江西赣州银座村镇银行', 'code' => '320428090311'),
				#array('name' => '深圳福田银座村镇银行', 'code' => '320584002002'),
				#array('name' => '重庆渝北银座村镇银行', 'code' => '320653000104'),
				#array('name' => '重庆黔江银座村镇银行', 'code' => '320687000016'),
				#array('name' => '上海农村商业银行', 'code' => '322290000011'),
				#array('name' => '吉林省农村信用社联合社', 'code' => '402241000015'),
				#array('name' => '江苏省农村信用社联合社', 'code' => '402301099998'),
				#array('name' => '浙江省农村信用社联合社', 'code' => '402331000007'),
				#array('name' => '宁波鄞州农村合作银行', 'code' => '402332010004'),
				#array('name' => '安徽省农村信用联合社', 'code' => '402361018886'),
				#array('name' => '福建省农村信用社联合社', 'code' => '402391000068'),
				#array('name' => '山东省农村信用社联合社', 'code' => '402451000010'),
				#array('name' => '湖北省农村信用社联合社', 'code' => '402521000032'),
				9 => array('name' => '广东省东莞农村商业银行', 'code' => '402602000018'),
				16 => array('name' => '广西壮族自治区农村信用社联合社', 'code' => '402611099974'),
				#array('name' => '海南省农村信用社联合社', 'code' => '402641000014'),
				#array('name' => '云南省农村信用社联合社', 'code' => '402731057238'),
				#array('name' => '宁夏黄河农村商业银行', 'code' => '402871099996'),
				12 => array('name' => '中国邮政储蓄银行', 'code' => '403100000004'),
				#array('name' => '外换银行（中国）有限公司', 'code' => '591110000016'),
				#array('name' => '友利银行(中国)有限公司', 'code' => '593100000020'),
				#array('name' => '新韩银行(中国)有限公司', 'code' => '595100000007'),
				#array('name' => '企业银行（中国）有限公司', 'code' => '596110000013'),
				#array('name' => '韩亚银行(中国)有限公司', 'code' => '597100000014'),
			);
			$this->utils->debug_log("Getting 24k bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	/**
	 * detail: Reference: documentation, section 5.2.3
	 *
	 * @param array $data
	 * @return string
	 */
	public function signSinglePay($data) {
		$keys = array("tranCode","version","callBackUrl","merchantOrderNum","currencyCode","inAcctNum","inAcctName","amount","inBankName","inBankCode","tranTime");
		$values = array();

		$values[] = $this->info['key'];
		foreach($keys as $key){
			$values[] = $data[$key];
		}

		$dataStr = join('|', $values);
		$this->utils->debug_log("Signing: $dataStr");
		return strtoupper(md5($dataStr));
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


}
