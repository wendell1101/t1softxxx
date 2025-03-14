<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_crown_api extends BaseTesting {

	private $platformCode = CROWN_API;
	private $platformName = 'CROWN';
	private $playerName = 'testcrown87670680';
	private $password = 'password';

	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

		$this->init();
		// $this->testCreatePlayer();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		// $this->testLogin();
		// $this->testLogout();
		// $this->testQueryForwardGame();
		$this->testSyncGameLogs();
		// $this->testSyncMergeToGameLogs();
	}

	private function testCreatePlayer() {
		$username = 'testcrown'. random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);
	}

	private function testLogin() {
		$username = $this->playerName;
		$this->utils->debug_log("login", $username);
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->login($username);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Login Player: ' . $username);
	}

	private function testLogout() {
		$username = $this->playerName;
		$this->utils->debug_log("login", $username);
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->logout($username);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Logout Player: ' . $username);
	}

    public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testQueryPlayerBalance() {
		$playerName = $this->playerName;
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log('queryPlayerBalance', $rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
	}

	private function testDeposit() { 
		$playerName = $this->playerName;
		$depositAmount = 50;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
	    $this->test($rlt['success'], true, 'Test Player Deposit to Crown',' Reference Id: ',@$rlt['transId']);
	}

	private function testWithdraw() {
		$playerName = $this->playerName;
		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to Crown');
		
	}

	private function testSyncGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-05-25 00:00:00');
		$dateTimeTo = new DateTime('2016-05-25 23:59:59');

		$playerName = 'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
         $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to CROWN');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-05-01 12:00:00');
		$dateTimeTo = new DateTime('2016-05-04 17:00:00');

		$playerName = 'ogtest006';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to QT');
	}

	private function testQueryForwardGame(){ 
		$playerName = $this->playerName;
		$result = $this->api->queryForwardGame($playerName, array("game_code"=>"OGS-volcanoeruption"));
		$this->utils->debug_log('testQueryForwardGame: ', $result);
	    $this->test($result['success'], true, 'Test Player Launch Game',' URL: ',@$result['url']);
	}

}