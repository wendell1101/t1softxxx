<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_playstar_api extends BaseTesting {

	private $platformCode = PLAYSTAR_API;
	private $platformName = 'PLAYSTAR_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	   	// $this->testGetAvailableApiToken();
	   	// $this->testCreatePlayer();
	    // $this->testQueryPlayerBalance();
	    // $this->testIsPlayerExist();
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    // $this->testQueryPlayerBalance();
	    $this->testQueryForwardgame();
	    
	    // $this->testSyncGameLogs();
	    // $this->testSyncMergeToGameLogs();
	 	// $this->testBlockPlayer();
	 	// $this->testUnblockPlayer();
	}

	private function testCreatePlayer() {
		$username = 'osdemo1'; 
		$password = '123456';
		$playerId = '56963';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);
	}

	public function testQueryPlayerBalance() {
		$username = 'osdemo1'; 
		$rlt = $this->api->queryPlayerBalance($username);
		echo "<pre>";print_r($rlt);exit;

	}

	public function testQueryForwardgame() {
		$username = 'osdemo1'; 
		$params = [
					"game_code" => "PSS-ON-00001",
					"language" => "zh-CN",
					"game_mode" => false
					];
		$rlt = $this->api->queryForwardgame($username,$params);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testGetAvailableApiToken() {
		$rlt = $this->api->getAvailableApiToken();
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2019-05-27 00:00:00');
		$dateTimeTo = new DateTime('2019-05-29 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2019-05-31 00:00:00');
		$dateTimeTo = new DateTime('2019-05-31 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testDeposit() {
	    $playerName = 'osdemo1';
	    $depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);

	}

	private function testWithdraw() {
		$playerName = 'osdemo1';
		$withdrawAmount = 50;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);

	}
}