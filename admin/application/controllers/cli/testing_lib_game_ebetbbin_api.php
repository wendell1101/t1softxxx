<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebetbbin_api extends BaseTesting {

	private $platformCode = EBET_BBIN_API;
	private $platformName = 'EBET_BBIN_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	   	 //$this->testCreatePlayer();
	     //$this->testQueryPlayerBalance();
	     //$this->testDeposit();
	    
	   // $this->testWithdraw();
	    //$this->testIsPlayerExist();
	   // $this->testQueryForwardGame();
	 	// $this->testChangePassword();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
	   // $this->testSyncGameLogs();
	    //$this->testIsPlayerExist();
	    // $this->testSyncMergeToGameLogs();
	     //$this->testLogout();
	     //
	     $this->testEncrypt();
	
	
	}

	private function testEncrypt() {

		$id = "jdsd989as7dfd8";
		$timestamp = "123456789";
		$plaintext = $id.$timestamp;
		$rlt = $this->api->encrypt($plaintext);
		print_r($rlt);exit();
	}
	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-05-01 00:00:00');
		$dateTimeTo = new DateTime('2017-05-01 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		// echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-04-25 00:00:00');
		$dateTimeTo = new DateTime('2017-04-25 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		// echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() { 
	
		//$username = $this->existPlayer; 
		$username = 'testgar1'; 
		$this->utils->debug_log("create player", $username);
		$rlt = $this->api->createPlayer($username,'56977','password');
		echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'testshu1'; 
		//$oldPassword = $this->password;
		$oldPassword = 'password1';
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testjerb725';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testjerb725';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testjerb725';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testIsPlayerExist() {
	
		$username =  'testshu1';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}

	private function testQueryForwardGame(){ 
     	$username =  'testjerb725';
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