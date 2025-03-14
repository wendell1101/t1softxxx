<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_ddbill extends BaseTesting {

	private $platformCode = DDBILL_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_ddbill', 'Test loaded API\'s class name. Expected: payment_api_ddbill');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'ddbill', 'Test loaded API\'s prefix. Expected: ddbill');
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

	private function testParseResultXml() {
		$resultXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><ddbill><response><resp_code>ILLEGAL_PAY_BUSINESS</resp_code><resp_desc>业务未开启，请联系业务人员</resp_desc><sign_type>RSA-S</sign_type><sign>etsR5F3mGJfIKwrflGW0lxQfqNoQU5er/lVRLsvZIiS7miO6Lnk5ELi2XghKKfDVgaTQ3QPju7T9HOMUCLdyWjU+dTx0MUBdwRDx7v937NnMLTmuLvvdPiyIWMQrDyDWzpDv71zIoVIy8Ky/SewlqTH3jnLhRwn968ZOmwf5HCE=</sign><trade></trade></response></ddbill>";

		$result = $this->api->parseResultXML($resultXml);
		$this->utils->debug_log("Parsed result: ", $result);
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

	private function testCallbackFromServer() {
		## Test failed case - sales order does not exist
		$result = $this->api->callbackFromServer(-1, array());
		$this->test($result['success'], false,
			'Test callbackFromServer function return with invalid orderId. Expected: false');
	}

	private function testCreateUrlAndCallback() {
		$player = $this->getFirstPlayer();
		$playerId = $player->playerId;
		$amount = 10.0;
		$player_promo_id = null;
		$d = new DateTime();
		$info = $this->api->getInfoByEnv();
		//$bank = $this->getFirstBanklist($this->platformCode);
		//$bankId = $bank->id;
		$bankId = 135;

		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		$this->test($orderId != null, true,
			'Test createSaleOrder function. Expected: Order ID not null');

		$this->CI = &get_instance();
		$this->CI->load->model('sale_order');
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$result = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false, $bankId);
		$this->test($result['success'], true,
			'Test API generatePaymentUrlForm function. Expected: success = true');

		## Simulate the params returned from DINPAY gateway
		## However, since we do not have api's private key, we are not able to know what its signature is.
		## The tests below will only make sense after commenting out the DINPAY API signature verification logic
		$params = array(
			'merchant_code' => $this->api->getSystemInfo('ddbill_merchant_code'),
			'interface_version' => $this->api->getSystemInfo('ddbill_interface_version'),
			'notify_type' => 'offline_notify',
			'notify_id' => 'e722dceae317466bbf9cc5f1254b8b0a', # a random string
			'sign_type' => 'RSA-S',
			'sign' => 'we do not have key',
			'order_no' => $order->secure_id,
			'order_time' => '',
			'order_amount' => 10.0,
			'trade_no' => '',
			'trade_time' => '',
			'trade_status' => 'SUCCESS',
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], true,
			'Test API callbackFromServer function. Expected: success = true');

		# Payment fail: trade_status = Fail
		$params = array(
			'merchant_code' => $this->api->getSystemInfo('ddbill_merchant_code'),
			'interface_version' => $this->api->getSystemInfo('ddbill_interface_version'),
			'notify_type' => 'offline_notify',
			'notify_id' => 'e722dceae317466bbf9cc5f1254b8b0a', # a random string
			'sign_type' => 'RSA-S',
			'sign' => 'we do not have key',
			'order_no' => $order->secure_id,
			'order_time' => '',
			'order_amount' => 10.0,
			'trade_no' => '',
			'trade_time' => '',
			'trade_status' => 'Fail',
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with payment fail. Expected: success = false');
		$this->test($result['message'], 'Error: Invalid callback info.',
			'Test API callbackFromServer function return message with payment fail. Expected: Error: Invalid callback info.');

		# Merchant codes do not match. merchant_code : 'invalid merchant code'
		$params = array(
			'merchant_code' => 'invalid merchant code',
			'interface_version' => $this->api->getSystemInfo('ddbill_interface_version'),
			'notify_type' => 'offline_notify',
			'notify_id' => 'e722dceae317466bbf9cc5f1254b8b0a', # a random string
			'sign_type' => 'RSA-S',
			'sign' => 'we do not have key',
			'order_no' => $order->secure_id,
			'order_time' => '',
			'order_amount' => 10.0,
			'trade_no' => '',
			'trade_time' => '',
			'trade_status' => 'SUCCESS',
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching merchant codes. Expected: success = false');
		$this->test($result['message'], 'Error: Invalid callback info.',
			'Test API callbackFromServer function return message with unmatching merchant codes. Expected: Error: Invalid callback info.');

		# Order Numbers do not match. order_no : 'invalid'
		$params = array(
			'merchant_code' => $this->api->getSystemInfo('ddbill_merchant_code'),
			'interface_version' => $this->api->getSystemInfo('ddbill_interface_version'),
			'notify_type' => 'offline_notify',
			'notify_id' => 'e722dceae317466bbf9cc5f1254b8b0a', # a random string
			'sign_type' => 'RSA-S',
			'sign' => 'we do not have key',
			'order_no' => 'invalid',
			'order_time' => '',
			'order_amount' => 10.0,
			'trade_no' => '',
			'trade_time' => '',
			'trade_status' => 'SUCCESS',
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching order numbers. Expected: success = false');
		$this->test($result['message'], 'Error: Invalid callback info.',
			'Test API callbackFromServer function return message with unmatching order numbers. Expected: Error: Invalid callback info.');

		# Payment amounts do not match. order_amount : 123
		$params = array(
			'merchant_code' => $this->api->getSystemInfo('ddbill_merchant_code'),
			'interface_version' => $this->api->getSystemInfo('ddbill_interface_version'),
			'notify_type' => 'offline_notify',
			'notify_id' => 'e722dceae317466bbf9cc5f1254b8b0a', # a random string
			'sign_type' => 'RSA-S',
			'sign' => 'we do not have key',
			'order_no' => $order->secure_id,
			'order_time' => '',
			'order_amount' => 123.0,
			'trade_no' => '',
			'trade_time' => '',
			'trade_status' => 'SUCCESS',
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching payment amounts. Expected: success = false');
		$this->test($result['message'], 'Error: Invalid callback info.',
			'Test API callbackFromServer function return message with unmatching payment amounts. Expected: Error: Invalid callback info.');
	}

	public function testSign() {
		$callbackJson = '{"sign":"XO6Sd7EBU4ek0YHJJiAswYU9Zr6KSHxfwt9vp2nc5eXMI8z0yCZP5aEBDBQYO+2YtZB1lZMlieE9BWMEm91H+HkfTX+hqJQTHUjhUNVDAH86FxbjeKOkjMXTPhYA9zKYihrL0BkqAnlxNram0QJsbg3gy15bEKrTN\/fPVAJ0zIg=","trade_no":"1126464162","order_amount":"200","interface_version":"V3.0","order_time":"2016-04-04 14:49:28","sign_type":"RSA-S","notify_type":"offline_notify","trade_time":"2016-04-04 14:49:50","notify_id":"78c2add8583b4a50a10f202a013a07b7","merchant_code":"4000030305","trade_status":"SUCCESS","order_no":"160893243140"}';
		$params = json_decode($callbackJson, true);
		$this->utils->debug_log('params', $params);
		$info = $this->api->getInfoByEnv();
		$rlt = $this->api->isValidatedServerCallbackSign($params, $info);
		$this->test($rlt, true, 'sign callback');
	}

	private function testDirectPay() {
		$result = $this->api->directPay();
		$this->test($result['success'], false,
			'Test API directPay function. Expected: false (directPay unsupported)');
	}

	private function testResultXML(){
		$xml="<?xml version=\"1.0\" encoding=\"UTF-8\" ?><ddbill><response><resp_code>ILLEGAL_PAY_BUSINESS</resp_code><resp_desc>业务未开启，请联系业务人员</resp_desc><sign_type>RSA-S</sign_type><sign>etsR5F3mGJfIKwrflGW0lxQfqNoQU5er/lVRLsvZIiS7miO6Lnk5ELi2XghKKfDVgaTQ3QPju7T9HOMUCLdyWjU+dTx0MUBdwRDx7v937NnMLTmuLvvdPiyIWMQrDyDWzpDv71zIoVIy8Ky/SewlqTH3jnLhRwn968ZOmwf5HCE=</sign><trade></trade></response></ddbill>";

		$arr=$this->api->parseResultXML($xml);

		$this->utils->debug_log($arr);

		// $obj=simplexml_load_string($xml);

		// $this->utils->debug_log($this->utils->xmlToArray($obj));

		$this->test($arr["ddbill"]["response"]["resp_code"], "ILLEGAL_PAY_BUSINESS",
			'Test result xml');
	}

}
