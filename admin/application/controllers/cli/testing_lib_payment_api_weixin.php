<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_weixin extends BaseTesting {

	private $platformCode = WEIXIN_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_weixin', 'Test loaded API\'s class name. Expected: payment_api_weixin');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'weixin', 'Test loaded API\'s prefix. Expected: weixin');
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
		array_key_exists('url', $result);
		$this->test($resultComplete, true,
			"Test API generatePaymentUrlForm function return value has all required fields. Expected: True");

		if (!$resultComplete) {
			$this->utils->debug_log($result);
			return;
		}

		# Test the result's field values
		$this->test($result['success'], true,
			'Test API generatePaymentUrlForm function return field "success". Expected: true');
		$this->test($result['type'], Abstract_payment_api::REDIRECT_TYPE_QRCODE,
			'Test API generatePaymentUrlForm function return field "type". Expected: REDIRECT_TYPE_QRCODE');
		$this->test(!empty($result['url']), true,
			'Test API generatePaymentUrlForm function return field "url". Expected: non-empty string');

		$this->utils->debug_log('URL: '.$result['url']);
	}

	private function testCreateUrlAndCallback() {
		$player = $this->getFirstPlayer();
		$playerId = $player->playerId;
		$amount = 10.0;
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

		## We are not able to simulate results returned from weixin
	}

	private function testDirectPay() {
		$result = $this->api->directPay();
		$this->test($result['success'], false,
			'Test API directPay function. Expected: false (directPay unsupported)');
	}
}
