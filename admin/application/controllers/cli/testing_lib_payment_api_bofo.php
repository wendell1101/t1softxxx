<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_bofo extends BaseTesting {

	private $platformCode = BOFO_PAYMENT_API;

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
			'MemberID' => $info['account'],
			'TerminalID' => $info['key'],
			'TransID' => $rlt['params']['TransID'],
			'Result' => '1',
			'ResultDesc' => 'OK',
			'FactMoney' => $amount * 100,
			'AdditionalInfo' => '',
			'SuccTime' => $d->format('YmdHis'),
		);
		$msg = 'MemberID=' . $params['MemberID'] . '~|~TerminalID=' . $params['TerminalID']
			. '~|~TransID=' . $params['TransID'] . '~|~Result=' . $params['Result']
			. '~|~ResultDesc=' . $params['ResultDesc'] . '~|~FactMoney=' . $params['FactMoney']
			. '~|~AdditionalInfo=' . $params['AdditionalInfo'] . '~|~SuccTime=' . $params['SuccTime']
			. '~|~Md5Sign=' . $info['secret'];
		$params['Md5Sign'] = strtolower(md5($msg));
		$rlt = $this->api->callbackFromServer($orderId, $params);
		$this->utils->debug_log('callbackFromServer', $rlt);

		$this->test($rlt['success'], true, 'callbackFromServer');
		$this->test($rlt['message'], 'OK', 'callbackFromServer message is OK');
	}

}
