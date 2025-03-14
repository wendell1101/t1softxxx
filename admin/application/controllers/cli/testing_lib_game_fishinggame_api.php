<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_fishinggame_api extends BaseTesting {

	private $platformCode = FISHINGGAME_API;

	public function init() {
		$this->load->model('game_provider_auth');
		$this->load->library(array('game_platform/game_platform_manager','salt'), array("platform_code" => $this->platformCode));

		// $this->test($this->game_platform_manager == null, false, 'init game platform manager');
		$api = $this->game_platform_manager->initApi($this->platformCode);
		$this->api=$api;
		// $this->test($api == null, false, 'init api');
		// $this->test($api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);

	}

	public function testAll() {
		$this->init();
		//$this->testCreatePlayer();exit;
		//$this->testQueryPlayerBalance();exit;
		//$this->testdepositToGame();exit;
		//$this->testQueryForwardGame();
		$this->testSyncGameLogs();
		// $this->testSyncMergeToGameLogs();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testSyncGameLogs() {
		$api = $this->game_platform_manager->initApi($this->platformCode);

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-10-25 10:59:10');
		$dateTimeTo = new DateTime('2017-10-25 15:09:10');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		//var_dump($rlt);
        $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to FISHING GAME');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-09-12 11:30:00');
		$dateTimeTo = new DateTime('2016-09-12 16:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to FISHING GAME');
	}


	private function testCreatePlayer() {
		$username = 'abc1234';
		$password = 'pass123';
		$playerId = 2;
		$result = $this->game_platform_manager->createPlayer($username, $playerId, $password);
		print_r($result);exit;
	}

	private function testQueryPlayerBalance() {
		$username = 'testshu';
		$password = 'password';

		$result = $this->game_platform_manager->queryPlayerBalance($username);
		$this->test($result['success'], true, 'test queryPlayerBalance for FISHINGGAME_API');
		echo "<pre>";print_r($result);exit;
		return $result;
	}

	private function testDepositToGame() {
		$playerName = 'testshu';
		$password = 'password';
		$playerId = 1;

		$result = $this->game_platform_manager->depositToGame($playerName, 1);
		echo "<pre>";print_r($result);exit;
		return $result;
	}

	private function testWithdrawalToGame() {
		$playerName = 'testshu';
		$result = $this->game_platform_manager->withdrawFromGame($playerName, 1);
		echo "<pre>";print_r($result);exit;
	}
	private function testQueryForwardGame(){
     	$username =  'testshu';
		$result = $this->api->queryForwardGame($username);
		echo "<pre>";print_r($result);exit;
	}

	private function testPlayerBalanceAG() {
		$playerName = 'asriinew2';
		$balResult = $this->game_platform_manager->queryPlayerBalance($playerName);
		log_message("error", var_export($balResult, true));
		$this->test($balResult['balance'] > 0, true, 'Test Player Balance');
	}

	private function blockPlayer() {
		$playerName = 'asriinew2';
		$balResult = $this->game_platform_manager->blockPlayer($playerName);
		log_message("error", var_export($balResult, true));
		$this->test($balResult['success'], true, 'Test Block Player');
	}

	private function unblockPlayer() {
		$playerName = 'asriinew2';
		$balResult = $this->game_platform_manager->unblockPlayer($playerName);
		log_message("error", var_export($balResult, true));
		$this->test($balResult['success'], true, 'Test Unblock Player');
	}

	private function queryGameRecords() {
		$playerName = 'asriinew2';
		$dateFrom = date_create('2015-07-09 00:00:00');
		$dateTo = date_create('2015-07-10 00:00:00');
		$balResult = $this->game_platform_manager->queryGameRecords($dateFrom, $dateTo, $playerName);
		log_message("error", var_export($balResult, true));
		$this->test($balResult['success'], true, 'Test Game Records Results');
		$this->test(is_array($balResult['gameRecords']), true, 'Test Game Records');
	}

	private function testIsPlayerExist() {
		$rlt = $this->game_platform_manager->isPlayerExist("johann");
		$this->test($rlt['success'], true, 'testIsPlayerExist for AG');
		$this->test($rlt['exists'], true, 'exist!');
	}

}
