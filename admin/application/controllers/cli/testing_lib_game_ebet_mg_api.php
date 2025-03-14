<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebet_mg_api extends BaseTesting {

	private $platformCode = EBET_MG_API;
	private $platformName = 'EBET_MG_API';

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
	 	// $this->testUnblockPlayer();
	 	// $this->testChangePassword();
	 	// $this->testBatchQueryPlayerBalance();

		$this->testSyncGameLogs();
		// $this->testSyncMergeToGameLogs();
	    // $this->testIsPlayerExist();
	    //$this->testLogout();
	    //$this->testEncrypt();


	}
	private function testBatchQueryPlayerBalance(){
		$rlt = $this->api->batchQueryPlayerBalance(array('testtest','testgame'),null);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() {
		$username = 'testtest';
        $rlt = $this->api->createPlayer($username, 56970, 'password', null);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerBalance() {
		$playerName = 'testtest';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {
		$playerName = 'testtest';
		$rlt = $this->api->isPlayerExist($playerName);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testDeposit() {

	    $playerName = 'testtest';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testWithdraw() {
		$playerName = 'testtest';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testtest';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testtest';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'testtest';
		//$oldPassword = $this->password;
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, 'password', $newPassword);
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
		$dateTimeFrom = new DateTime('2017-12-15 17:00:00');
		$dateTimeTo = new DateTime('2017-12-15 17:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-12-14 00:00:00');
		$dateTimeTo = new DateTime('2017-12-15 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);

		echo "<pre>";print_r($rlt);
	}

	private function testQueryForwardGame(){
     	$username =  'testvan1';
     	$extra = array(
     		"game_name" => "slotgames_lines25_byakuyalinne"
     		);
		$rlt = $this->api->queryForwardGame($username,$extra);
		echo "<pre>";print_r($rlt);exit;
	}

}