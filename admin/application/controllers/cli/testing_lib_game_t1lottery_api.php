<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_t1lottery_api extends BaseTesting {

	private $platformCode = T1LOTTERY_API;
	private $platformName = 'T1LOTTERY_API';
	private $api;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll(){
		$this->init();
		// $this->testGenerateToken();
		// $this->testCreatePlayer();
		// $this->testIsPlayerExist();
		// $this->testQueryPlayerInfo();
		// $this->testUpdatePlayerInfo();
		// $this->testChangePassword();
		// $this->testBlockPlayer();
		// $this->testunblockPlayer();
		// $this->testQueryPlayerBalance();
		// $this->testDepositToGame();
		// $this->testWithdrawFromGame();
		// $this->testQueryTransaction();
		// $this->testLogin();
		// $this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
		// $this->testBatchQueryPlayerBalance();
	}	

	public function testBatchQueryPlayerBalance() {
		$players = array('testgame1','testgame2');
		$rlt = $this->api->BatchQueryPlayerBalance($players);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testGenerateToken() {
		$rlt = $this->api->generateToken(true);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testUpdatePlayerInfo() {
		$playerName = 'testnew';
		$rlt = $this->api->updatePlayerInfo($playerName);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryTransaction() {
		$extId = 'testnew170929162116';
		$rlt = $this->api->queryTransaction($extId);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerInfo() {
		$playerName = 'testnew';
		$rlt = $this->api->queryPlayerInfo($playerName);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testCreatePlayer() {

        $playerName = 'testnew';
        $password = 'password';
        $playerId = 56963;
        $rlt = $this->api->createPlayer($playerName, $playerId, $password);
        echo "<pre>";print_r($rlt);exit;
    }

	public function testIsPlayerExist() {

        $playerName = 'testnew';
        $rlt = $this->api->isPlayerExist($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testChangePassword(){
    	$playerName = 'testnew';
    	$oldPassword = 'password';
    	$newPassword = 'password';
        $rlt = $this->api->changePassword($playerName, $oldPassword, $newPassword);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testBlockPlayer(){
    	$playerName = 'testnew';
        $rlt = $this->api->blockPlayer($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testunblockPlayer(){
    	$playerName = 'testnew';
        $rlt = $this->api->unblockPlayer($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testQueryPlayerBalance(){
    	$playerName = 'testnew';
        $rlt = $this->api->queryPlayerBalance($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testDepositToGame() { 
	    $playerName = 'testnew';
	    $depositAmount = 99;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

    public function testWithdrawFromGame() { 
	    $playerName = 'testnew';
	    $depositAmount = 4;
		$rlt = $this->api->withdrawFromGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

    public function testLogin() { 
	    $playerName = 'testnew';
		$rlt = $this->api->login($playerName);
		echo "<pre>";print_r($rlt);exit;
	}

    public function testSyncGameLogs() { 
	    $token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-03-12 12:11:00');
		$dateTimeTo = new DateTime('2018-03-12 12:11:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-03-23 12:11:00');
		$dateTimeTo = new DateTime('2018-03-23 23:11:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}
}
