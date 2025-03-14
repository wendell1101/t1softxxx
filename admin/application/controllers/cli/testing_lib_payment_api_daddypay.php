<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_daddypay extends BaseTesting {

	private $platformCode = DADDYPAY_BANKCARD_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_daddypay_bankcard', 'Test loaded API\'s class name. Expected: payment_api_allscore');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		// $this->test($this->api->getPrefix(), 'allscore', 'Test loaded API\'s prefix. Expected: allscore');
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

	private function testExceptionOrder() {
		//http://yourdomain.com?type=exceptionWithdrawApply&exception_order_num=TDP520160106022895&company_id=21
		//&exact_payment_bank=1&pay_card_num=-&pay_card_name=杰&receiving_bank=1&receiving_account_name=张三
		//&channel=网上银行&note=123&area=东莞&exact_time=20160105175546&amount=2.00&fee=0.00&transaction_charge=1.50
		//&key=5d4572d9c3fe0578281e6902b5d5856a&base_info=030220106505103095929231210&operating_time=20160105175916
		$flds=[
			'type'=>'exceptionWithdrawApply',
			'exception_order_num'=>'TDP520160106022895',
			'company_id'=>'21',
			'exact_payment_bank'=>'1',
			'pay_card_num'=>'23432432',
			'pay_card_name'=>'sdfdsf',
			'receiving_bank'=>'1',
			'receiving_account_name'=>'张三',
			'channel'=>'网上银行',
			'note'=>'123',
			'area'=>'东莞',
			'exact_time'=>'20160105175546',
			'amount'=>'2.00',
			'fee'=>'0.00',
			'transaction_charge'=>'1.50',
			'key'=>'8d51218ddee522e72b2c755f6de6d720',
			'base_info'=>'030220106505103095929231210',
			'operating_time'=>'20160105175916',
		];
		$rlt=$this->api->callbackException($flds);

		$this->utils->debug_log('callback exception', $rlt);
	}
}
