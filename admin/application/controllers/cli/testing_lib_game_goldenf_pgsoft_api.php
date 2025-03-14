<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_goldenf_pgsoft_api extends BaseTesting {

	private $platformCode = GOLDENF_PGSOFT_API;
	private $platformName = 'GOLDENF_PGSOFT_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {
	 	$this->init();
	   	// $this->testCreatePlayer();
	    // $this->testQueryPlayerBalance();
        // $this->testIsPlayerExist();
	    // $this->testQueryTransaction();
	    // $this->testDeposit();
        // $this->testWithdraw();
	    // $this->testSyncGameLogs();
	    $this->testSyncMergeToGameLogs();

	}

	private function testLogin() {
	
		$username =  'testshu';
		$rlt = $this->api->login($username,null,array('language'=>'en-US'));
		echo "<pre>";print_r($rlt);exit;

	}

	private function testIsPlayerExist() {
	
		$username =  'testlocal';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testLogout() {
	
		$playerName = 'testlocal';
		$rlt = $this->api->logout($playerName);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() { 
		$username = 'testlocal'; 
		$password = 'password';
		$playerId = '56962';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerBalance() {
		$playerName = 'testlocal';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryTransaction() {
		$externalId = 'T050372858694';
		$extra = ['playerName'=>'testlocal','playerId'=>57024];
		$rlt = $this->api->queryTransaction($externalId,$extra);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-03-15 00:00:00');
		$dateTimeTo = new DateTime('2018-03-15 23:30:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2018-08-14 00:00:00');
		$dateTimeTo = new DateTime('2018-08-14 23:30:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);
	}

	private function testDeposit() { 

	    $playerName = 'testlocal';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testlocal';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testQueryForwardGame(){ 
     	$username =  'testshu';
		$rlt = $this->api->queryForwardGame($username);
		echo "<pre>";print_r($rlt);exit;
	}

}