<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_dg_api extends BaseTesting {

	private $platformCode = DG_API;
	private $platformName = 'DG_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	   	// $this->testCreatePlayer();
	    // $this->testIsPlayerExist();
	    // $this->testQueryPlayerBalance();
	    // $this->testDeposit();
	 	// $this->testChangePassword();
	 	// $this->testBlockPlayer();
	 	// $this->testUnblockPlayer();
	 	// $this->testLogin();
	    // $this->testSyncGameLogs();
	    $this->testSyncMergeToGameLogs();
	 	
	    // $this->testWithdraw();
	    //$this->testQueryForwardGame();
	     // $this->testLogout();
	
	
	}

	private function testLogin() {
	
		$username =  'testgar';
		$rlt = $this->api->login($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testIsPlayerExist() {
	
		$username =  'testgar';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testLogout() {
	
		$username =  'testshu1';
		$rlt = $this->api->logout($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() { 
		
		//$username = $this->existPlayer; 
		$username = 'testshu'; 
		$password = 'password';
		$playerId = '56963';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}


	public function testQueryPlayerBalance() {

		$playerName = 'testgar';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-06-15 13:04:45');
		$dateTimeTo = new DateTime('2017-06-15 14:00:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-09-11 00:00:00');
		$dateTimeTo = new DateTime('2017-09-11 23:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);
	}

	private function testChangePassword() {
		$username = 'testgar'; 
		//$oldPassword = $this->password;
		$oldPassword = 'password';
		$newPassword = 'password1';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testgar';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testgar';
		$withdrawAmount = 300;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testQueryForwardGame(){ 
     	$username =  'testshu';
		$rlt = $this->api->queryForwardGame($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testgar';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testgar';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}



}