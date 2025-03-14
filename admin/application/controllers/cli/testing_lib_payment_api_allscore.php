<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_allscore extends BaseTesting {

	private $platformCode = ALLSCORE_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_allscore', 'Test loaded API\'s class name. Expected: payment_api_allscore');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'allscore', 'Test loaded API\'s prefix. Expected: allscore');
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
			"service" => "directPay",
			"merchantId" => "001015013101118",
			"notifyUrl" => "http://og.local/callback/process/91/3266",
			"returnUrl" => "http://og.local/callback/browser/success/91/3266",
			"signType" => "MD5",
			"inputCharset" => "UTF-8",
			"outOrderId" => "D182208725481",
			"subject" => "存款",
			"body" => "存款",
			"transAmt" => "1.00",
			"payMethod" => "bankPay",
			"defaultBank" => "ICBC",
			"channel" => "B2C",
		);

		$result = $this->api->sign($params);
		$this->test(strlen($result) > 0, true, "Must generate valid signature");
	}

	private function testGeneratePaymentUrlForm() {
		$orderId = floatval(random_string('numeric', 2));
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
		$this->test(array_key_exists('sign', $result['params']) && !empty($result['params']['sign']), true,
			'Test API generatePaymentUrlForm function return field "params" must contain a non-empty signature. Expected: non-empty string');
		$this->test(array_key_exists('post', $result), true,
			'Test API generatePaymentUrlForm function return field "post". Expected: Exists');

		$this->utils->debug_log($result['url'], $result['params']);
	}

	private function testCreateUrlAndCallback() {
		$player = $this->getFirstPlayer();
		$playerId = $player->playerId;
		$amount = 10.0;
		$player_promo_id = null;
		$d = new DateTime();
		$info = $this->api->getInfoByEnv();

		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		$this->test($orderId != null, true,
			'Test createSaleOrder function. Expected: Order ID not null');

		$this->CI = &get_instance();
		$this->CI->load->model('sale_order');
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$result = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false);
		$this->test($result['success'], true,
			'Test API generatePaymentUrlForm function. Expected: success = true');

		# Test simulated callback info
		# Normal callback
		$params = array(
			'notifyId' => rand(100000, 1000000),
			'notifyTime' => time('YmdHis'),
			'outOrderId' => $order->secure_id,
			'subject' => 'Deposit',
			'body' => 'Deposit',
			'transAmt' => $amount,
			'tradeStatus' => 2,
			'merchantId' => $this->api->getSystemInfo('allscore_merchantId'),
			'localOrderId' => 'Local'.rand(10000, 100000),
		);
		$params['sign'] = 'do not have key'; # We do not have the private key to sign the content

		$result = $this->api->callbackFromServer($orderId, $params);
		# This will actually fail because we cannot generate valid signature
		// $this->test($result['success'], true,
		// 	'Test API callbackFromServer function. Expected: success = true');
	}

	private function testDirectPay() {
		$result = $this->api->directPay();
		$this->test($result['success'], false,
			'Test API directPay function. Expected: false (directPay unsupported)');
	}
}
