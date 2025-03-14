<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_entwine_api extends BaseTesting {

	private $platformCode = ENTWINE_API;
	private $platformName = 'ENTWINE';
	private $playerName = 'testdarkred';
	private $password = '123456';

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
		// $this->testQueryForwardGame();
		// $this->testSyncGameLogs();
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
		$password = $this->password;
		$rlt = $this->api->login($username,$password);
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
		$depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
	    $this->test($rlt['success'], true, 'Test Player Deposit to Entwine',' Reference Id: ',@$rlt['transId']);
	}

	private function testWithdraw() {
		$playerName = $this->playerName;
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to Entwine');
		
	}

	private function testSyncGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-03-28 00:10:00');
		$dateTimeTo = new DateTime('2017-03-28 23:59:59');

		$playerName = 'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
         $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to ENTWINE');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-02-13 12:00:00');
		$dateTimeTo = new DateTime('2017-02-13 17:00:00');

		$playerName = $this->playerName;

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to QT');
	}

	private function testQueryForwardGame(){ 
		$playerName = $this->playerName;
		$result = $this->api->queryForwardGame($playerName,array("extra"=>"web"));
		$this->utils->debug_log('testQueryForwardGame: ', $result);
	    $this->test($result['success'], true, 'Test Player Launch Game',' URL: ',@$result['url']);
	}

}