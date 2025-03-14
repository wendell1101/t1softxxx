<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_gopay extends BaseTesting {

	private $platformCode = GOPAY_PAYMENT_API;

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
		$params = array(
			'feeAmt' => '0.01',
			'respCode' => '0000',
			'signType' => '1',
			'buyerContact' => '',
			'version' => '2.1',
			'merOrderNum' => '046831854471',
			'tranFinishTime' => '20151119112322',
			'goodsDetail' => '--',
			'msgExt' => '',
			'orderId' => '2015111922008409',
			'tranAmt' => '0.01',
			'merchantID' => '0000009592',
			'charset' => '2',
			'tranIP' => '180.232.133.50',
			'signValue' => 'ff59a6f0b387eb97083d016643c39bcf',
			'buyerName' => '',
			'tranDateTime' => '20151119112026',
			'bankCode' => 'CCB',
			'authID' => '',
			'merRemark2' => '',
			'tranCode' => '8888',
			'gopayOutOrderId' => '20151119-8663-2015111922008409',
			'backgroundMerUrl' => 'http://player.jbyl777.com/callback/process/5/131002',
			'merRemark1' => '',
			'frontMerUrl' => 'http://player.jbyl777.com/callback/browser/success/5/131002',
			'language' => '1',
			'goodsName' => '',
		);

		$info = $this->api->getInfoByEnv();
		$sign = $this->api->createCallbackSign($params, $info);
		$this->utils->debug_log('sign', $sign, $params['signValue']);

		$this->test($sign, $params['signValue'], 'testCallbackSign ');
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
