<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_sdpay2nd extends BaseTesting {

	private $platformCode = SDPAY2ND_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_sdpay2nd', 'Test loaded API\'s class name. Expected: payment_api_sdpay');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'sdpay2nd', 'Test loaded API\'s prefix. Expected: sdpay');
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
		$this->init();
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll');
		foreach ($classMethods as $method) {
			if (strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
				continue;
			}

			$this->$method();
		}
	}

	# Documentation section 2
	private function testEncrypt() {
		$requestXml = '<?xmlversion="1.0"encoding="utf-8" ?><message><cmd>6006</cmd><merchantid>1001</merchantid><language>zh-cn</language><userinfo><order>20130221134055837182</order><username>test1001</username><money>109.00</money><unit></unit><time></time><remark></remark><backurl></backurl><backurlbrowser>http://www.melc.cn/backurlbrowser.aspx</backurlbrowser></userinfo></message>';

		$key1 = 'KAtafVu/M/4=';
		$key2 = 'fLKKfA6RK+c=';
		$md5key = 'f331a67aca0448d382c2e261328d0b2a';

		$md5encrypt = md5($requestXml.$md5key);
		$randomVal = rand();
		$xmlData = $requestXml.$md5encrypt.md5($randomVal);

		$this->utils->debug_log("Sample encryption result: ", $this->api->encryptWithKey($xmlData, $key1, $key2), $xmlData);
	}
}
