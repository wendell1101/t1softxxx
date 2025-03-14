<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_mtech_og_api extends BaseTesting {

	private $platformCode = MTECH_OG_API;
	private $platformName = 'MTECH_OG_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	   	// $this->testGetAvailableApiToken();
	   	// $this->testCreatePlayer();
	    // $this->testQueryPlayerBalance();
	    // $this->testQueryTransactions();
	    // $this->testLogin();
	    // $this->testIsPlayerExist();
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    // $this->testSyncGameLogs();
	    $this->testSyncMergeToGameLogs();
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2019-01-24 00:34:00');
		$dateTimeTo = new DateTime('2019-01-24 23:50:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {
		echo date('Y-m-d H:i:s',(1424962802883/1000));exit;
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2019-01-23 00:34:00');
		$dateTimeTo = new DateTime('2019-01-23 23:50:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testLogin(){
		$username = 'testlocal'; 
		$password = 'password';
		$rlt = $this->api->login($username,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testQueryTransactions(){
		$transactionId = 'T823191324935';
		$extra = array(
			'playerName' => 'testlocal',
		    'playerId' => '57024',
		    'transfer_method' => 'withdrawal',
		    'transfer_time' => '2019-01-17 16:50:21',
		    'secure_id' => 'T823191324935',
		    'amount' => 1,
		    'transfer_updated_at' => '2019-01-17 16:50:23'
		);	

		$rlt = $this->api->queryTransaction($transactionId, $extra);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testGetAvailableApiToken() {
		$rlt = $this->api->getAvailableApiToken();
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() {
		$username = 'testlocal'; 
		$password = 'password';
		$playerId = '57024';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {
		$username = 'testlocal'; 
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testlocal';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testDeposit() {

	    $playerName = 'testlocal';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testWithdraw() {
		$playerName = 'testshu';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;

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