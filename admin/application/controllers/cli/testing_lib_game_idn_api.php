<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_idn_api extends BaseTesting {

	private $platformCode = IDN_API;
	private $platformName = 'IDN_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	    // $this->testCreatePlayer();
	    // $this->testLogin();
	    // $this->testQueryPlayerBalance();
	    // $this->testIsPlayerExist();
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    // $this->testLogout();
	 	$this->testChangePassword();
	    // $this->testIsPlayerExist();


	    //$this->testQueryForwardGame();
	 	//$this->testBlockPlayer();
	 	//this->testUnblockPlayer();
	    // $this->testSyncGameLogs();
	    // $this->testSyncMergeToGameLogs();
	    // $this->testCreateMobilePlayer();
	  //  $this->testCheckCurrentRatet();

	
	}
	private function testCheckCurrentRatet() {
		$rlt = $this->api->checkCurrentRate();
		var_dump($rlt);

	}

	private function testIsPlayerExist() {
	
		$username =  'testjerb';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}

	private function testCreatePlayer() { 
	
		//$username = $this->existPlayer; 
		$username = 'testshu123'; 
		$password = 'password'; 
		$player_id = '111';
		
		$rlt = $this->api->createPlayer($username,$player_id,$password);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testCreateMobilePlayer() { 
	
		//$username = $this->existPlayer; 
		$username = 'sbetestshu'; 
		
		$rlt = $this->api->createMobilePlayer($username);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testLogin() {

		$username = 'testshu123213123';
		//$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->login($username);
		
		echo "<pre>";print_r($rlt);exit;
	}

	private function testLogout() {

		$username = 'testshu';

		$rlt = $this->api->logout($username);
		
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-02-01 00:00:00');
		$dateTimeTo = new DateTime('2017-02-01 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-02-01 00:00:00');
		$dateTimeTo = new DateTime('2017-02-01 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'localprod '; 
		//$oldPassword = $this->password;
		$oldPassword = '123456a1';
		$newPassword = 'pass1234';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testshu';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testshu';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testshu';
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
		$username =  'testkeir';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testkeir';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}



}