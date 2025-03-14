<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_paysec extends BaseTesting {

	private $platformCode = PAYSEC_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		// $this->test($apiClassName, 'payment_api_paysec', 'Test loaded API\'s class name. Expected: payment_api_24kpay');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
	}

	## all tests route through this function
	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	# Actual Tests
	## Invokes all tests defined below. A test function's name should begin with 'test'
	public function testAll() {
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll');
		foreach ($classMethods as $method) {
			if (strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
				continue;
			}

			$this->$method();
		}
	}

	private function testSign() {
		$params = array(
			'CID' => 'M999-C-55',
			'v_CartID' => 'CART001',
			'v_amount' => '10.00',
			'v_currency' => 'CNY',
		);
		$sign = $this->api->sign_posting($params);
		$this->utils->debug_Log($params, 'sign', $sign);
	}

	private function testGeneratePaymentUrlForm() {
		$orderId = 1;
		$playerId = 1;
		$amount = 0.2;
		$orderDateTime = new DateTime();

		# Turn off redirect to 2nd url
		$result = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, null, false);

		# Test the result's completeness
		$resultComplete =
		array_key_exists('success', $result) &&
		array_key_exists('type', $result) &&
		array_key_exists('url', $result) &&
		array_key_exists('params', $result) &&
		array_key_exists('post', $result);
		$this->test($resultComplete, true,
			"Test API generatePaymentUrlForm function return value has all required fields. Expected: True");

		if (!$resultComplete) {
			$this->utils->debug_log($result);
			return;
		}

		# Test the result's field values
		$this->test($result['success'], true,
			'Test API generatePaymentUrlForm function return field "success". Expected: true');
		$this->test($result['type'], Abstract_payment_api::REDIRECT_TYPE_FORM,
			'Test API generatePaymentUrlForm function return field "type". Expected: REDIRECT_TYPE_FORM');
		$this->test(!empty($result['url']), true,
			'Test API generatePaymentUrlForm function return field "url". Expected: non-empty string');
		$this->test(count($result['params']) > 0, true,
			'Test API generatePaymentUrlForm function return field "params". Expected: array of > 0 elements');
		$this->test(array_key_exists('signData', $result['params']) && !empty($result['params']['signData']), true,
			'Test API generatePaymentUrlForm function return field "params" must contain a non-empty signature. Expected: non-empty string');
		$this->test(array_key_exists('post', $result), true,
			'Test API generatePaymentUrlForm function return field "post". Expected: Exists');

		$this->utils->debug_log('URL: ' . $result['url'], $result['params']);
	}

	private function testCreateUrlAndCallback() {
		$player = $this->getFirstPlayer();
		$playerId = $player->playerId;
		$amount = 0.02;
		$player_promo_id = null;
		$d = new DateTime();
		$bank = $this->getFirstBanklist($this->platformCode);
		$bankId = $bank ? $bank->id : 1;

		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		$this->test($orderId != null, true,
			'Test createSaleOrder function. Expected: Order ID not null');

		$this->CI = &get_instance();
		$this->CI->load->model('sale_order');
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$result = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false, $bankId);
		$this->test($result['success'], true,
			'Test API generatePaymentUrlForm function. Expected: success = true');

		## Simulate the params returned from 24KPAY gateway
		## Reference: documentation, section 5.2.5
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => $order->secure_id,
			"merchantName" => "测试一",
			"inAcctNum" => "622908398189586615",
			"inAcctName" => "陈能亮",
			"amount" => $amount,
			"inBankName" => "中国兴业银行",
			"orderStatus" => "2",
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => "",
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], true,
			'Test API callbackFromServer function. Expected: success = true');

		# Payment fail
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => $order->secure_id,
			"merchantName" => "测试一",
			"inAcctNum" => "622908398189586615",
			"inAcctName" => "陈能亮",
			"amount" => $amount,
			"inBankName" => "中国兴业银行",
			"orderStatus" => "3", # indicates payment fail
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => "Testing failure",
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with payment fail. Expected: success = false');

		# Order Numbers do not match
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => "xxx", # incorrect order number
			"merchantName" => "测试一",
			"inAcctNum" => "622908398189586615",
			"inAcctName" => "陈能亮",
			"amount" => $amount,
			"inBankName" => "中国兴业银行",
			"orderStatus" => "2",
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => "",
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching order numbers. Expected: success = false');

		# Payment amounts do not match
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => $order->secure_id,
			"merchantName" => "测试一",
			"inAcctNum" => "622908398189586615",
			"inAcctName" => "陈能亮",
			"amount" => "1000.00", # incorrect amount
			"inBankName" => "中国兴业银行",
			"orderStatus" => "2",
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => "",
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching payment amounts. Expected: success = false');
	}

	private function testDirectPay() {
		$result = $this->api->directPay();
		$this->test($result['success'], false,
			'Test API directPay function. Expected: false (directPay unsupported)');
	}
}
