<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebet_qt_api extends BaseTesting {

	private $platformCode = EBET_QT_API;
	private $platformName = 'EBET_QT_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();

		// $rlt = $this->testDeposit(20);
		// var_dump($rlt);

	 	// $extra['gameId'] = 'OGS-5knights';
	 	// $rlt = $this->api->queryForwardGame('emgtest1', $extra);
	     // $rlt = $this->testQueryPlayerBalance();
	     // die();
	   	// $rlt = $this->testCreatePlayer();
	 	// $rlt = $this->api->isPlayerExist('emgtest1', $extra);
	 	// var_dump($rlt);
	     // $this->testQueryPlayerBalance();
	     // $this->testQueryPlayerBalance();
		 // $this->testWithdraw();
	     // $this->testQueryPlayerBalance();
	    //$this->testIsPlayerExist();
	    // $this->testQueryForwardGame();
	 	// $this->testChangePassword();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
		$this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
	    // $this->testIsPlayerExist();
	     //$this->testLogout();
	     //
	     //$this->testEncrypt();
	
	
	}

	private function testEncrypt() {

		$id = "jdsd989as7dfd8";
		$timestamp = "123456789";
		$plaintext = $id.$timestamp;
		$rlt = $this->api->encrypt($plaintext);
		print_r($rlt);exit();
	}
	private function testSyncGameLogs() {

		$token 			= 'abc123d';
		$dateTimeFrom 	= new DateTime('2017-08-21 00:00:00');
		$dateTimeTo 	= new DateTime('2017-08-22 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";
		print_r($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom 	= new DateTime('2017-08-21 00:00:00');
		$dateTimeTo 	= new DateTime('2017-08-22 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);

		echo "<pre>";print_r($rlt);
	}

	private function testCreatePlayer() { 
	
		//$username = $this->existPlayer; 
		$username = 'emgtest1'; 
		$this->utils->debug_log("create player", $username);
		$rlt = $this->api->createPlayer($username, 186, 'ukistisabam');
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


	private function testDeposit($depositAmount) { 
	    $playerName = 'emgtest1';
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		return $rlt;
	}

	public function testQueryPlayerBalance() {

		$playerName = 'emgtest1';
		$rlt = $this->api->queryPlayerBalance($playerName);
		return $rlt;
	}

	private function testWithdraw() {
		$playerName = 'emgtest1';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		var_dump($rlt);	
		
	}

	private function testIsPlayerExist() {
	
		$username =  'testvan1';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}

	private function testQueryForwardGame($username){ 
     	$extra = array(
 				"game_name" => "slotgames_lines25_byakuyalinne"
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