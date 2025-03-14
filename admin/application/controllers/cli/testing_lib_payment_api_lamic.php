<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_lamic extends BaseTesting {

	private $platformCode = LAMIC_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_lamic', 'Test loaded API\'s class name. Expected: payment_api_lamic');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'lamic', 'Test loaded API\'s prefix. Expected: lamic');
		$this->test($this->api->isAllowDeposit(), true, 'Test whether API can be used for deposit. Expected: true');
		$this->test($this->api->isAllowWithdraw(), false, 'Test whether API can be used for withdrawal. Expected: false');
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

	private function testEncrypt() {
		$str = "123";
		$encrypted = $this->api->encryptAPI($str);
		$encryptedLocal = $this->api->encrypt($str);
		$this->utils->debug_log("Entrypted String = ", $encrypted);
		$this->utils->debug_log("Local Entrypted String = ", $encryptedLocal);
		$this->test($encryptedLocal, $encrypted, 'Locally encrypted string must match that provided by the API');
	}

	private function testLogin() {
		$token = $this->api->getToken();
		$this->utils->debug_log("Login token = ", $token);
	}

	private function testGeneratePaymentUrlForm() {
		$orderId = 1;
		$playerId = 111;
		$amount = 0.2;
		$orderDateTime = new DateTime();

		# Turn off redirect to 2nd url
		$result = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, null, false);

		# Test the result's field values
		$this->test($result['success'], true,
			'Test API generatePaymentUrlForm function return field "success". Expected: true');
		$this->test($result['type'], Abstract_payment_api::REDIRECT_TYPE_QRCODE,
			'Test API generatePaymentUrlForm function return field "type". Expected: REDIRECT_TYPE_QRCODE');
	}
}
