<?php

require_once dirname(__FILE__) . '/base_testing.php';

class testing_lib_game_xyzblue_api extends BaseTesting {

	private $platformCode = XYZBLUE_API;
	private $platformName = 'XYZBLUE_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	 	// $this->testCreatePlayer();
	 	$this->testIsPlayerExist();
	 	// $this->testQueryPlayerBalance();
	 	// $this->testDeposit(1);
	 	// $this->testWithdraw(1);
	 	// $this->testQueryForwardGame();
	 	// $this->testSyncGameLogs();
	 	// $this->testSyncMergeToGameLogs();
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
		$dateTimeFrom = new DateTime('2017-09-13 00:00:00');
		$dateTimeTo = new DateTime('2017-09-13 23:00:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-09-13 00:00:00');
		$dateTimeTo = new DateTime('2017-09-13 23:00:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);

		echo "<pre>";print_r($rlt);
	}

	private function testCreatePlayer() { 
	
		$username = 'testjerb1011'; 
		$password = 'pass1234'; 
		$player_id = '56999';
		$extra = array(
			"mode"	=> 'demo'
			
		);
		
		$rlt = $this->api->createPlayer($username,$player_id,$password,null,$extra);
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
		$username =  'testjerb06';
		$rlt = $this->api->depositToGame($username,$depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerBalance() {

		$username =  'testjerb06';
		$rlt = $this->api->queryPlayerBalance($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testWithdraw($withdrawtAmount) {
		$username =  'testjerb06';
		$rlt = $this->api->withdrawFromGame($username,$withdrawtAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testIsPlayerExist() {
	
		$username =  'testjerb1011';
		$extra = array(
			"mode"	=> 'demo'
			
		);
		$rlt = $this->api->isPlayerExist($username,$extra);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testQueryForwardGame($username){ 
     	$extra = array(
 				"game_name" => "oddeven",
 				"sound_info" => 0,
 				"is_mobile"	=> false
 		);
 		$username =  'testjerb06';
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