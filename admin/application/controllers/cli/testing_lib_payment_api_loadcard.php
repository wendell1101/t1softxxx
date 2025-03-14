<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_loadcard extends BaseTesting {

	private $platformCode = LOADCARD_PAYMENT_API;

	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);

		$this->test($this->api != null, true, 'load api :' . $this->api->getName());
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testAll() {
		$this->init();
	}

	private function testCallbackSign() {
		$api = $this->api;

		// $failed_callback = array(
		// 	'sign' => '964af6a6bc0029944465ccdd253fd624',
		// 	'timestamp' => '1455786511561',
		// 	'b1_orderNumber' => '17154',
		// 	'b4_returnCode' => '80808005',
		// 	'b6_completeTime' => '20160218170831',
		// 	'b5_returnMsg' => '系统不支持的卡类型',
		// 	'b8_serialNumber' => 'C00007263596',
		// 	'b3_status' => 'FAILED',
		// 	'b7_desc' => '16808',
		// 	'b2_successAmount' => '0.00',
		// 	'merchantNo' => 'M800028898',
		// );
		// $processed = false;
		// $api->checkCallbackOrder(null, $failed_callback, $processed);

		// $this->test($processed, true, 'failed_callback sign');

		$orderId = '130995';
		$orderSecureId = '776586322596';
		$succ_callback = array(
			'timestamp' => '1456204675856',
			'sign' => '4fa383d8093025e5ef1eeb9775ec6692',
			'b1_orderNumber' => $orderSecureId,
			'b4_returnCode' => '808888',
			'b6_completeTime' => '20160223131755',
			'b5_returnMsg' => '消卡成功',
			'b3_status' => 'SUCCESS',
			'b8_serialNumber' => 'C00007263641',
			'b2_successAmount' => '10.0',
			'b7_desc' => '16809',
			'merchantNo' => 'M800028898',
		);
		// $processed = false;
		// $success = $api->checkCallbackOrder(null, $succ_callback, $processed);

		// $this->test($processed, true, 'succ_callback sign');
		// $this->test($success, true, 'succ_callback checkCallbackOrder');
		$success = $api->callbackFromServer($orderId, $succ_callback);

		$this->test($success, true, 'succ_callback callbackFromServer');
	}

}
