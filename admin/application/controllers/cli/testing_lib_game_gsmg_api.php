<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_gsmg_api extends BaseTesting {

	private $platformCode = GSMG_API;
	private $platformName = 'GSMG';
	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		// $this->testGetSessionId();
		// $this->testGetCurrency();
		// $this->testCreatePlayer();
		// $this->testDeposit();
		// $this->testWithdraw();
		//$this->testPlayerLogin();
		//$this->testQueryForwardGame();
		// $this->testQueryPlayerBalance();

		// $this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
		//$this->testCreateAndLogin();

		// $this->isPlayerExists();
		// $this->isSessionAlive();

	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testGetSessionId() {
		$rlt = $this->api->getSessionId();
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' test get session id for ' . $this->platformName);
	}

	private function testGetCurrency() {
		$rlt = $this->api->getCurrency();
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' test get currency for ' . $this->platformName);
	}

	private function testCreateAndLogin() {
		$username = 'test' . random_string('alnum');
		$password = '12344321';
		$depositAmount = 1.2;
		$player = $this->getFirstPlayer($username);
		$this->utils->debug_log("create player", $username, 'password', $password, 'amount', $depositAmount);

		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');
		$rlt = $this->api->createPlayer($username, $player->playerId, $password);
		// $this->utils->debug_log('after createPlayer', $rlt);
		$this->test($rlt['success'], true, $username . ' createPlayer for ' . $this->platformName);
		$this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);

		$this->utils->debug_log('=====login: ' . $username . '======================================================');
		$rlt = $this->api->login($username, $password);
		// $this->utils->debug_log('after createPlayer', $rlt);
		$this->test($rlt['success'], true, $username . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $username . '======================================================', $rlt);

	}

	private function testBatchQueryPlayerBalance() {
		$rlt = $this->api->batchQueryPlayerBalance(null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}

	private function testGameLogs() {
		$rowId = '16103653263';
		$rlt = $this->api->convertGameRecordsToFile($rowId);

	}

	private function testCreatePlayer() {
		// $playerName = 'test' . random_string('numeric', 3);
		$playerName = 'test852c';
		//$this->utils->debug_log("create player", $username);
		$password = $playerName;
		$playerId = 127;
		$rlt = $this->api->createPlayer($playerName, $playerId, $password, null);
		$this->test($rlt['success'], true, ' testCreatePlayer for ' . $this->platformName);
		$this->utils->debug_log($rlt);
	}

	private function testPlayerLogin() {
		$playerName = 'test852';
		$password = 'test852';
		$rlt = $this->api->login($playerName, $password);
		$this->test($rlt['success'], true, $playerName . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $playerName . '======================================================', $rlt);
	}

	public function testQueryPlayerBalance() {
		$playerName = 'test852';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		// $this->test($rlt['balance'], 14, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testDeposit() {
		$playerName = 'test852';
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to GSMG');
		// $this->test($rlt['currentplayerbalance'], 15, 'Current Balance after deposit');
	}

	private function testWithdraw() {
		$playerName = 'test852';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to GSMG');
		// $this->test($rlt['currentplayerbalance'], 10, 'Current Balance after withdrawal');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-07-28 00:00:00');
		$dateTimeTo = new DateTime('2016-07-28 23:59:59');

		$playerName = 'test123456789012l23test';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to GSMG');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-11-03 00:00:00');
		$dateTimeTo = new DateTime('2016-11-03 23:59:59');

		$playerName = 'swtest008';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to GSMG');
	}

	private function testQueryForwardGame() {
		$playerName = 'test852';
		$param = array(
			"game_type" => "live",
			"game_code" => "TombRaider",
			"game_mode" => "1", //1demo ,0normal
			"is_mobile_flag" => "false",
		);
		$rlt = $this->api->queryForwardGame($playerName, $param);
		// var_dump($rlt);exit();
		$this->test($rlt['success'], true, 'Test testQueryForwardGame to GSMG');
	}
}