<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_og_api extends BaseTesting {

	private $platformCode = OG_API;
	private $platformName = 'OG_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	   	// $this->testCreatePlayer();
	    // $this->testIsPlayerExist();
	 	// $this->testChangePassword();
	    // $this->testQueryPlayerBalance();
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    // $this->testGetVendorId();
	    // $this->testSyncGameLogs();
	    // $this->testSyncMergeToGameLogs();

	    $this->testQueryForwardGame();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
	    //$this->testSyncMergeToGameLogs();
	     //$this->testLogout();


	}

	private function testGetVendorId(){
		$rlt = $this->api->getVendorId();
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-06-28 00:00:00');
		$dateTimeTo = new DateTime('2018-06-28 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-05-01 00:00:00');
		$dateTimeTo = new DateTime('2018-06-28 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() {

		//$username = $this->existPlayer;
		$username = 'teststg2';
		$password = '123456';
		$playerId = '30';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'testshu';
		//$oldPassword = $this->password;
		$oldPassword = 'password1';
		$newPassword = 'password';

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

	private function testIsPlayerExist() {

		$username =  'teststg2';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}

	private function testQueryForwardGame(){
     	$username =  'teststg2';
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