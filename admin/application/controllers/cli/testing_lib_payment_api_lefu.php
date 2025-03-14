<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_lefu extends BaseTesting {

	private $platformCode = LEFU_PAYMENT_API;

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
		$amount = 10;
		$player_promo_id = null;
		$d = new DateTime();
		$info = $this->api->getInfoByEnv();

		// $bankList = $this->api->getBankListInfo();
		$bankId = 'B2C_BOC-CREDIT_CARD';

		// $bank = $this->getFirstBanklist($this->platformCode);
		// $bankId = $bank->id;
		//fake callback from document
		$orderId = $this->api->createSaleOrder($playerId, $amount, $player_promo_id);
		$this->test($orderId != null, true, 'order id is not null');

		$this->load->model(array('sale_order'));
		$ord = $this->sale_order->getSaleOrderById($orderId);

		$rlt = $this->api->generatePaymentUrlForm($orderId, $playerId, $amount, new DateTime(), $player_promo_id, false, $bankId);
		$this->utils->debug_log('generatePaymentUrlForm', $rlt);
		$this->test($rlt['success'], true, 'generatePaymentUrlForm');

		$params = array(
			'apiCode' => 'directPay',
			'versionCode' => '1.0',
			'currency' => 'CNY',
			'amount' => $amount,
			'handlerResult' => '0000',
			'tradeOrderCode' => 'lefutransaction100',
			'outOrderId' => $ord->secure_id,
			'inputCharset' => 'UTF-8',
			'signType' => 'MD5',
			'partner' => $info['key'],
			'returnParam' => '',
		);
		$params['sign'] = $this->api->createCallbackSign($params, $info);
		$this->utils->debug_log('params', $params);

		$rlt = $this->api->callbackFromServer($orderId, $params);
		$this->utils->debug_log('callbackFromServer', $rlt);

		$this->test($rlt['success'], true, 'callbackFromServer');
		$this->test($rlt['message'], Payment_api_lefu::RETURN_SUCCESS_CODE, 'callbackFromServer message is OK');
	}

}
