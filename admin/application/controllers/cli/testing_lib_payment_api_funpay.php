<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_funpay extends BaseTesting {

	private $platformCode = FUNPAY_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);
		$this->load->helper('date');
		$this->load->model('wallet_model');

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_funpay', 'Test loaded API\'s class name. Expected: payment_api_funpay');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'funpay', 'Test loaded API\'s prefix. Expected: funpay');
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

	private function testGetWithdrawParams() {
		$withdrawParams = $this->api->getWithdrawParams(12, '4444333322221111', 'Zhang San', '100.00', 'W0000009300000002');
		$this->utils->debug_log("Withdraw Params:", $withdrawParams);
	}

}
