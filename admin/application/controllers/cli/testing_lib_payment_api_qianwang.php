<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_qianwang extends BaseTesting {

	private $platformCode = QIANWANG_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, $this->platformCode.' Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_qianwang', 'Test loaded API\'s class name. Expected: Payment_api_qianwang');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'qianwang', 'Test loaded API\'s prefix. Expected: qianwang');
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
		$this->test($result['type'], Abstract_payment_api::REDIRECT_TYPE_FORM,
			'Test API generatePaymentUrlForm function return field "type". Expected: REDIRECT_TYPE_URL');
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
	}

	private function testDirectPay() {
		$result = $this->api->directPay();
		$this->test($result['success'], false,
			'Test API directPay function. Expected: false (directPay unsupported)');
	}

	private function testCallbackSign() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib(QIANWANG_WEIXIN_PAYMENT_API);
		$this->test($loaded, true, 'Is API class loaded. Expected: true '.$apiClassName);
		if (!$loaded) {
			$this->utils->error_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}
		$this->api = $this->$apiClassName;

		// $this->utils->debug_log('Is API class loaded. Expected: true '.$apiClassName);

		// $json='{"orderid":"D205791791206","opstate":"0","ovalue":"8","ekaorderid":"16102521532740000144","ekatime":"2016-10-25 21:54:46","sysorderid":"16102521532740000144","completiontime":"2016-10-25 21:54:46","attach":"","msg":"֧ok","cid":"6245","sign":"264fd3cc19748451533038bd669de7bb"}';
		// $json='{"orderid":"D142001230317","opstate":"0","ovalue":"8.00","ekaorderid":"16102816471576000412","ekatime":"2016/10/28 16:47:46","sysorderid":"16102816471576000412","completiontime":"2016/10/28 16:47:46","attach":"","msg":"","cid":"6254","sign":"64e71fb181ed68f07457742742e1183d"}';

		$json='{"orderid":"D355921143445","opstate":"0","ovalue":"8.00","ekaorderid":"16102820552277000014","ekatime":"2016/10/28 20:55:51","sysorderid":"16102820552277000014","completiontime":"2016/10/28 20:55:51","attach":"","msg":"","cid":"6254","sign":"8e4168c3f5d47e61b509e0ce44868c44"}';
		$data=json_decode($json, true);
		$sign=$this->api->server_sign($data);

		$this->utils->debug_log('data', $data, 'sign', $sign);
		$this->test($sign, $data['sign'], 'Test API sign');
	}

}
