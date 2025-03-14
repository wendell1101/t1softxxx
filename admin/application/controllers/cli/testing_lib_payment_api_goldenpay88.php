<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_goldenpay88 extends BaseTesting {

	private $platformCode = GOLDENPAY88_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->test($apiClassName, 'payment_api_goldenpay88', 'Test loaded API\'s class name. Expected: payment_api_goldenpay88');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'goldenpay88', 'Test loaded API\'s prefix. Expected: goldenpay88');
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

	public function testOnly() {

	}

	private function testSign() {

		$params = array(
			"terId" => "123456",
			"businessOrdid" => "1",
			"orderName" => "Deposit",
			"tradeMoney" => 7777,
			'payType'=> 1000,
			'appSence'=> 1001,
			'asynURL'=> "http://www.google.com",
		);

		list($encodedParams, $signature) = $this->api->getSignatureByParams($params);

		$this->utils->debug_log($signature);

		$this->test($encodedParams ? true : false, true, 'Expected: getSignatureByParams true');

		$verify_result = $this->api->validateSign($encodedParams, $signature);

		$this->test($verify_result, true, 'Expected: verify Sign true');

	}

	private function testGetInfoByEnv(){
		$result = $this->api->getInfoByEnv();
//		$this->utils->debug_log('testGetInfoByEnv', $result);

		foreach ($this->api->getAllSystemInfo() as $key => $value){
			if($key == 'extra_info'){
				$extraInfo = json_decode($value, true) ?: array();

				$this->utils->debug_log('testGetInfoByEnv->key', $key);
				$this->utils->debug_log('=====================testGetInfoByEnv->valuse', $value);
				$this->utils->debug_log('=====================testGetInfoByEnv->extraInfo', $extraInfo);
			}

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
		$this->test($result['type'], Abstract_payment_api::REDIRECT_TYPE_FORM,
			'Test API generatePaymentUrlForm function return field "type". Expected: REDIRECT_TYPE_QRCODE');
		$this->test(!empty($result['url']), true,
			'Test API generatePaymentUrlForm function return field "url". Expected: non-empty string');

		$this->utils->debug_log('URL: '.$result['url']);
	}

	private function testCallback() {
		$player = $this->getFirstPlayer();
		$playerId = $player->playerId;
		$amount = 100;
		$player_promo_id = null;
		$d = new DateTime();
		$info = $this->api->getInfoByEnv();

		//fake callback from document
		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		$this->test($orderId != null, true, 'order id is not null');
//		$bankId = null;
//
//		$rlt = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false, $bankId);
//		$this->utils->debug_log('generatePaymentUrlForm', $rlt);
//		$this->test($rlt['success'], true, 'generatePaymentUrlForm');

		$params = array();
		$params['respCode'] = Abstract_payment_api_goldenpay88::RESULT_CODE_SUCCESS;
		$params['respDesc'] = 'Just For Test';
		$params['payOrderId'] = 15842;
		$params['orderId'] = $orderId;
		$params['code_img_url'] = 'www.google.com';

		list($encodedParams, $signature) = $this->api->getSignatureByParams($params);

		$final_params = array(
			'sign'		=>	$signature,
			'merId'		=>	$this->api->getSystemInfo('merchant_id'),
			'version'	=>	Abstract_payment_api_goldenpay88::VERSION,
			'encParam'	=>	$encodedParams,
		);

		$rlt = $this->api->callbackFromServer($orderId, $final_params);
		$this->utils->debug_log('===================test->callbackFromServer', $rlt);

		$this->test($rlt['success'], true, 'callbackFromServer');
//		$this->test($rlt['message'], Abstract_payment_api_goldenpay88::RETURN_SUCCESS_CODE, 'callbackFromServer message is OK');
	}


//
//	private function testCreateUrlAndCallback() {
//		$player = $this->getFirstPlayer();
//		$playerId = $player->playerId;
//		$amount = 10.0;
//		$player_promo_id = null;
//		$d = new DateTime();
//		$bank = $this->getFirstBanklist($this->platformCode);
//		$bankId = $bank ? $bank->id : 1;
//
//		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
//		$this->test($orderId != null, true,
//			'Test createSaleOrder function. Expected: Order ID not null');
//
//		$this->CI = &get_instance();
//		$this->CI->load->model('sale_order');
//		$order = $this->CI->sale_order->getSaleOrderById($orderId);
//		$result = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false, $bankId);
//		$this->test($result['success'], true,
//			'Test API generatePaymentUrlForm function. Expected: success = true');
//
//		## We are not able to simulate results returned from weixin
//	}
//
//	private function testDirectPay() {
//		$result = $this->api->directPay();
//		$this->test($result['success'], false,
//			'Test API directPay function. Expected: false (directPay unsupported)');
//	}
}
