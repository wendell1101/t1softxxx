<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebetimpt_api extends BaseTesting {

	private $platformCode = EBET_IMPT_API;
	private $platformName = 'EBET_IMPT_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	   	 //$this->testCreatePlayer();
	     //$this->testQueryPlayerBalance();
	     //$this->testDeposit();
	   //$this->testLogin();
	   // $this->testWithdraw();
	    //$this->testIsPlayerExist();
	    //$this->testQueryForwardGame();
	 	// $this->testChangePassword();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
	    $this->testSyncGameLogs();
	    // $this->testIsPlayerExist();
	     // $this->testSyncMergeToGameLogs();
	     //$this->testLogout();
	     //
	     //$this->testEncrypt();
	
	
	}
	private function testLogin() {
		// echo "test";exit();
		$username = 'testjerb725'; 
		$rlt = $this->api->login($username);
		echo "<pre>";print_r($rlt);exit;
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
		$dateTimeFrom = new DateTime('2018-02-25 12:28:00');
		$dateTimeTo = new DateTime('2018-02-25 12:28:30');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		// echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-07-31 00:00:00');
		$dateTimeTo = new DateTime('2017-07-31 14:00:00');
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		 echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() { 
		//$username = $this->existPlayer; 
		$username = 'testvan1'; 
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

	    $playerName = 'testvan1';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testvan1';
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
	
		$username =  'testjerb725';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testQueryForwardGame(){ 
     	$username =  'testjerb725';
     	/*$extra = array(
     		"mode"		=> "real",
     		"game_name" => "ftg",
     		"type"		=> "flash"
     		);*/
     	/*$extra = array(
     		"mode"		=> "real",
     		"game_name" => "hlf2",
     		"type"		=> "html5"
     		);*/

     	$extra = array(
     		"mode"		=> "fun",
     		"game_name" => "hk",
     		);
		$rlt = $this->api->queryForwardGame($username,$extra);
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