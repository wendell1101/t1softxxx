<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_lotus_api extends BaseTesting {

	private $platformCode = LOTUS_API;
	private $platformName = 'LOTUS_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {
	 	$this->init();
	   	// $this->testCreatePlayer();
	    // $this->testQueryPlayerBalance();
        // $this->testIsPlayerExist();
	    // $this->testDeposit();
        // $this->testWithdraw();
        // $this->testBlockPlayer();
        // $this->testUnBlockPlayer();
	 	// $this->testLogin();
	    // $this->testSyncGameLogs();
	    $this->testSyncMergeToGameLogs();

	    // $this->testLogout();
	}

	private function testLogin() {
	
		$username =  'testshu';
		$rlt = $this->api->login($username,null,array('language'=>'en-US'));
		echo "<pre>";print_r($rlt);exit;

	}

	private function testBlock() {
	
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
	
		$username =  'testshu';
		$rlt = $this->api->logout($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() { 
		
		//$username = $this->existPlayer; 
		$username = 'testlocal'; 
		$password = 'password';
		$playerId = '57024';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}


	public function testQueryPlayerBalance() {

		$playerName = 'testlocal';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-10-25 18:00:00');
		$dateTimeTo = new DateTime('2018-10-25 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2018-10-25 18:00:00');
		$dateTimeTo = new DateTime('2018-10-26 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);
	}

	private function testChangePassword() {
		$username = 'testshu'; 
		//$oldPassword = $this->password;
		$oldPassword = 'password';
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testlocal';
	    $depositAmount = 2000000;
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