<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_mwg_api extends BaseTesting {

	private $platformCode = MWG_API;
	private $platformName = 'MWG_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	   	// $this->getMWGAPIDomain();
	   	// $this->testCreatePlayer();
	    // $this->testQueryPlayerBalance();
        // $this->testIsPlayerExist();
	    // $this->testDeposit();
        // $this->testWithdraw();
        // $this->testGetMWGGamelist();
	    $this->testSyncGameLogs();
	    $this->testSyncMergeToGameLogs();
	}

	private function getMWGAPIDomain() {
		$rlt = $this->api->getMWGAPIDomain();
		echo "<pre>";print_r($rlt);exit;
	}

	private function testGetMWGGamelist() {
		$rlt = $this->api->getMWGGameList();
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {
	
		$username =  'testshu';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() { 
		
		//$username = $this->existPlayer; 
		$username = 'testshu'; 
		$password = 'password';
		$playerId = '56962';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}


	public function testQueryPlayerBalance() {

		$playerName = 'testshu';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-03-09 17:00:00');
		$dateTimeTo = new DateTime('2018-03-09 17:20:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		// echo "<pre>";print_r($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2018-03-09 17:00:00');
		$dateTimeTo = new DateTime('2018-03-09 17:20:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);
	}

	private function testDeposit() { 

	    $playerName = 'testshu';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testshu';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

}