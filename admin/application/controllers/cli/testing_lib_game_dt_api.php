<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_dt_api extends BaseTesting {

	private $platformCode = DT_API;
	private $platformName = 'DT_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	   	//$this->testCreatePlayer();
	 	//$this->testChangePassword();
	    // $this->testQueryPlayerBalance();

	    //$this->testDeposit();
	    //$this->testWithdraw();
	    //$this->testIsPlayerExist();
	    //$this->testQueryForwardGame();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
	   // $this->testSyncGameLogs();
	    // $this->testSyncMergeToGameLogs();
	    // $this->testIsPlayerExist();
	    //$this->testSyncMergeToGameLogs();
	     //$this->testLogout();
	
	
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-10-06 00:00:00');
		$dateTimeTo = new DateTime('2017-10-06 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {

		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-10-06 00:00:00');
		$dateTimeTo = new DateTime('2017-10-06 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() { 
		
		//$username = $this->existPlayer; 
		$username = 'testoink';
		$password = 'pass123';
		$playerId = '1';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'testoink';
		//$oldPassword = $this->password;
		$oldPassword = 'pass123';
		$newPassword = 'pass123';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testoink';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testoink';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testoink';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testIsPlayerExist() {
	
		$username =  'testoink';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}

	private function testQueryForwardGame(){ 
     	$username =  'testshu';
		$rlt = $this->api->queryForwardGame($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testshu';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testshu';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}



}