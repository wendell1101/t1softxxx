<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ezugi_api extends BaseTesting {

	private $platformCode = EZUGI_API;
	private $platformName = 'EZUGI_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	    // $this->testCreatePlayer();
	    // $this->testDeposit();
	    //$this->testQueryPlayerBalance();
	    //$this->testWithdraw();
	    //$this->testIsPlayerExist();
	    //$this->testQueryForwardGame();
	 	// $this->testChangePassword();
	 	//$this->testBlockPlayer();
	 	//this->testUnblockPlayer();
	    //$this->testSyncGameLogs();
	    //$this->testSyncMergeToGameLogs();

	    //$this->testIsPlayerExist();
	    $this->testLogin();
	    //$this->testLogout();
	
	}

	private function testLogin() {

		$username = 'testezugin3';
		//$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->login($username,"password");
		
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-01-09 00:00:00');
		$dateTimeTo = new DateTime('2017-01-09 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		//echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-01-09 00:00:00');
		$dateTimeTo = new DateTime('2017-01-09 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		//echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() { 
	
		//$username = $this->existPlayer; 
		$username = 'testshu112221'; 
		$password = 'password'; 
		$player_id = '111';
		
		$rlt = $this->api->createPlayer($username,$player_id,$password);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testChangePassword() {
		$username = 'testezugin2'; 
		//$oldPassword = $this->password;
		$oldPassword = 'password1';
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testshu1';
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

	private function testIsPlayerExist() {
	
		$username =  'testshu1';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

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