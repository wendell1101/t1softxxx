<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_baofu88 extends BaseTesting {

	private $platformCode = BAOFU88_PAYMENT_API;

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

	private function testSign() {
	}

	private function testCallback() {
		$player = $this->getFirstPlayer();
		$playerId = $player->playerId;
		$amount = 100;
		$player_promo_id = null;
		$d = new DateTime();
		$info = $this->api->getInfoByEnv();
		$bank = $this->getFirstBanklist($this->platformCode);
		$bankId = $bank->id;
		//fake callback from document
		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		$this->test($orderId != null, true, 'order id is not null');

		$rlt = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false, $bankId);
		$this->utils->debug_log('generatePaymentUrlForm', $rlt);
		$this->test($rlt['success'], true, 'generatePaymentUrlForm');

		$params = array(
			'p1_MerId' => '1595',
			'r0_Cmd' => 'Buy',
			'r1_Code' => '1',
			'r2_TrxId' => '201511252035257546',
			'r3_Amt' => '0.10',
			'r4_Cur' => 'RMB',
			'r5_Pid' => '',
			'r6_Order' => '061914501714',
			'r7_Uid' => '',
			'r8_MP' => '129',
			'r9_BType' => '1',
			'rp_PayDate' => '2015-11-25 20:36:56',
			'hmac' => 'af752ac86657f529627f6b123505d0d4',
		);

		$rlt = $this->api->callbackFromServer($orderId, $params);
		$this->utils->debug_log('callbackFromServer', $rlt);

		$this->test($rlt['success'], true, 'callbackFromServer');
		$this->test($rlt['message'], 'OK', 'callbackFromServer message is OK');
	}

}
