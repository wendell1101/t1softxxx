<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_livegaming_api extends BaseTesting {

	private $platformCode = LIVEGAMING_API;
	private $platformName = 'LIVEGAMING_API';

	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {
	 	$this->init();
	   	// $this->testCreatePlayer();
	    // $this->testIsPlayerExist();
	    // $this->testQueryPlayerBalance();
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    // $this->testQueryTransactions();
	    // $this->testSyncGameLogs();
	    // $this->testSyncMergeToGameLogs();
	 	// $this->testBlockPlayer();
	 	// $this->testUnblockPlayer();
	 		$this->testGetGamelist();
	}

	private function testQueryTransactions(){
		$transactionId = 'T223513093690111a';
		$extra = array(
			'playerName' => 'testdevlocal',
		    'playerId' => '56963',
		);	

		$rlt = $this->api->queryTransaction($transactionId, $extra);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() {
		$username = 'testdevlocal'; 
		$password = 'password';
		$playerId = '56966';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerBalance() {
		$username = 'testdevlocal'; 
		$rlt = $this->api->queryPlayerBalance($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testSyncGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2019-03-01 00:00:00');
		$dateTimeTo = new DateTime('2019-03-01 17:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2019-03-05 00:54:50');
		$dateTimeTo = new DateTime('2019-03-05 23:54:50' );

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {
		$username = 'testdevlocal'; 
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testDeposit() {
	    $playerName = 'testdevlocal';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testWithdraw() {
		$playerName = 'testdevlocal';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testdevlocal';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testdevlocal';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testGetGamelist() {
		// $playerName =  'testdevlocal';
		// $rlt = $this->api->unblockPlayer($username);
		$rlt = $this->api->getGameProviderGameList();
		echo "<pre>";print_r($rlt);exit;
	}


}