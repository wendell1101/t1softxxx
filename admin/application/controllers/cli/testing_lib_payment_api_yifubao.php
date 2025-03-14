<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_yifubao extends BaseTesting {

	private $platformCode = YIFUBAO_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

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

	private function testGeneratePaymentUrlForm() {
		$orderId = 1;
		$playerId = 1;
		$amount = 0.02;
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
		$this->test(array_key_exists('sign', $result['params']) && !empty($result['params']['sign']), true,
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

		## Simulate the params returned from callback
		## Reference: documentation, section 3.3.2
		$params = array(
			"merchant_code" => "<merchant code>",
			"notify_type" => "back_notify",
			"order_no" => $order->secure_id,
			"order_amount" => $amount,
			"order_time" => $d->format('Y-m-d H:i:s'),
			"return_params" => "",
			"trade_no" => "1234567890",
			"trade_time" => $d->format('Y-m-d H:i:s'),
			"trade_status" => "success",
		);
		$params['sign'] = $this->api->sign($params);
		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], true,
			'Test API callbackFromServer function. Expected: success = true');

		# Payment fail
		$params = array(
			"merchant_code" => "<merchant code>",
			"notify_type" => "back_notify",
			"order_no" => $order->secure_id,
			"order_amount" => $amount,
			"order_time" => $d->format('Y-m-d H:i:s'),
			"return_params" => "",
			"trade_no" => "1234567890",
			"trade_time" => $d->format('Y-m-d H:i:s'),
			"trade_status" => "failed", # payment status = failed
		);
		$params['sign'] = $this->api->sign($params);
		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with payment fail. Expected: success = false');

		# Order Numbers do not match
		$params = array(
			"merchant_code" => "<merchant code>",
			"notify_type" => "back_notify",
			"order_no" => "WRONG NUMBER", # $order->secure_id, wrong order number
			"order_amount" => $amount,
			"order_time" => $d->format('Y-m-d H:i:s'),
			"return_params" => "",
			"trade_no" => "1234567890",
			"trade_time" => $d->format('Y-m-d H:i:s'),
			"trade_status" => "success",
		);
		$params['sign'] = $this->api->sign($params);
		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching order numbers. Expected: success = false');

		# Payment amounts do not match
		$params = array(
			"merchant_code" => "<merchant code>",
			"notify_type" => "back_notify",
			"order_no" => $order->secure_id,
			"order_amount" => -1, # Wrong payment amount
			"order_time" => $d->format('Y-m-d H:i:s'),
			"return_params" => "",
			"trade_no" => "1234567890",
			"trade_time" => $d->format('Y-m-d H:i:s'),
			"trade_status" => "success",
		);
		$params['sign'] = $this->api->sign($params);
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
