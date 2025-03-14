<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_ips extends BaseTesting {

	private $platformCode = IPS_PAYMENT_API;

	private $api = null;
	private $manager = null;

	public function init() {

		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_ips', 'Test loaded API\'s class name. Expected: payment_api_ips');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'ips', 'Test loaded API\'s prefix. Expected: ips');
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

	private function testCreateSaleOrder() {
		$amount = floatval(random_string('numeric', 2));
		$playerId = 1;
		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		//check sale order
		$ord = $this->sale_order->getSaleOrderById($orderId);
		$this->test($ord->amount, $amount, 'create sale order');
	}

	private function testGeneratePaymentUrlForm() {
		$orderId = floatval(random_string('numeric', 2));
		$playerId = 1;
		$amount = 2.2;
		$orderDateTime = new DateTime();
		$rlt = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime);
		log_message('debug', 'result:' . var_export($rlt, true));
		$this->test(!empty($rlt['url']), true, 'generatePaymentUrlForm');
	}

	private function testDecodeNewpayCallback() {
		$param['paymentResult'] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Ips><GatewayRsp><head><ReferenceID>123</ReferenceID><RspCode>123</RspCode><RspMsg><![CDATA[]]></RspMsg><ReqDate>123</ReqDate><RspDate>123</RspDate><Signature>123</Signature></head><body><MerBillNo>123</MerBillNo><CurrencyType>123</CurrencyType><Amount>123</Amount><Date>123</Date><Status>123</Status><Msg><![CDATA[]]></Msg><Attach><![CDATA[]]></Attach><IpsBillNo>123</IpsBillNo><IpsTradeNo>123</IpsTradeNo><RetEncodeType>123</RetEncodeType><BankBillNo>123</BankBillNo><ResultType>123</ResultType><IpsBillTime>123</IpsBillTime></body></GatewayRsp></Ips>";
		$this->test($this->api->decodeNewpayCallbackExtraInfo($param), false, 'Parsing of random XML should return error');
	}

	private function testCheckCallback(){
		$xml="<Ips><GateWayRsp><head><ReferenceID></ReferenceID><RspCode>000000</RspCode><RspMsg><![CDATA[交易成功！]]></RspMsg><ReqDate>20161106023141</ReqDate><RspDate>20161106023249</RspDate><Signature>2b14f7a62f42e3bd2b8b411e060caa62</Signature></head><body><MerBillNo>D537411146376</MerBillNo><CurrencyType>156</CurrencyType><Amount>100</Amount><Date>20161106</Date><Status>Y</Status><Msg><![CDATA[支付成功！]]></Msg><IpsBillNo>BO20161106023117093792</IpsBillNo><IpsTradeNo>2016110602114113149</IpsTradeNo><RetEncodeType>17</RetEncodeType><BankBillNo>710018618217</BankBillNo><ResultType>0</ResultType><IpsBillTime>20161106023248</IpsBillTime></body></GateWayRsp></Ips>";

	}

}
