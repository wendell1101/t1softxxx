<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_fg_api extends BaseTesting {

	private $platformCode = FG_API;
	private $platformName = 'FG';
	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		// $this->testCreatePlayer();
		// $this->testPlayerLogin();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		// $this->testQueryForwardGame();

		// $this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
		//$this->testCreateAndLogin();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
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
		$playerName = 'testgray';
		//$this->utils->debug_log("create player", $username);
		$password = '123456';
		$playerId = 12;
		$rlt = $this->api->createPlayer($playerName, $playerId, $password, null);
		$this->test($rlt['success'], true, ' testCreatePlayer for ' . $this->platformName);
		$this->utils->debug_log($rlt);
	}

	private function testPlayerLogin() {
		$playerName = 'test123456789012l234561test';
		$password = 'pass123';
		$rlt = $this->api->login($playerName, $password);
		$this->test($rlt['success'], true, $playerName . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $playerName . '======================================================', $rlt);
	}

	public function testQueryPlayerBalance() {
		$playerName = 'wbttestfg1';

		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 14, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testDeposit() {
		$playerName = 'wbttestfg1';
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to FG');
		$this->test($rlt['currentplayerbalance'], 15, 'Current Balance after deposit');
	}

	private function testWithdraw() {
		$playerName = 'test123456789012l23test';
		$withdrawAmount = 100;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to FG');
		$this->test($rlt['currentplayerbalance'], 10, 'Current Balance after withdrawal');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-07-06 00:00:00');
		$dateTimeTo = new DateTime('2016-07-06 23:59:59');

		$playerName = 'test123456789012l23test';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to FG');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-10-18 14:00:00');
        $dateTimeTo = new DateTime('2017-10-18 14:59:59');

		$playerName = 'swtest008';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to FG');
	}

	private function testQueryForwardGame() {
		$playerName = 'test123456789012l23test';
		$param = array(
			"game_code" => "123",
			"game_mode" => "true",
			"is_mobile_flag" => "false",
		);
		$rlt = $this->api->queryForwardGame($playerName, $param);
		var_dump($rlt);exit();
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to FG');
	}
}