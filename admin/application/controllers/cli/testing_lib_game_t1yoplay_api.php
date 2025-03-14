<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_t1yoplay_api extends BaseTesting {

	private $platformCode = T1PT_API;
	private $platformName = 'T1PT_API';
	private $api;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll(){
		$this->init();
		// $this->testGenerateToken();
		// $this->testCreatePlayer();
		// $this->testIsPlayerExist();
		// $this->testChangePassword();
		// $this->testBlockPlayer();
		// $this->testunblockPlayer();
		// $this->testQueryPlayerBalance();
		// $this->testDepositToGame();
		// $this->testWithdrawFromGame();
		// $this->testQueryTransaction();
		// $this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
	}	

	public function testQueryTransaction() {

		$extId = 'testnew170929162116';
		$rlt = $this->api->queryTransaction($extId,array('player_username'=>'testyoplay1'));
		echo "<pre>";print_r($rlt);exit;
	}

	public function testGenerateToken() {
		$rlt = $this->api->generateToken(true);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testCreatePlayer() {

        $playerName = 'testshu';
        $password = 'password';
        $playerId = 56962;
        $rlt = $this->api->createPlayer($playerName, $playerId, $password);
        echo "<pre>";print_r($rlt);exit;
    }

	public function testIsPlayerExist() {

        $playerName = 'testt1yoplay';
        $rlt = $this->api->isPlayerExist($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testChangePassword(){
    	$playerName = 'testyoplay1';
    	$oldPassword = 'password1';
    	$newPassword = 'password';
        $rlt = $this->api->changePassword($playerName, $oldPassword, $newPassword);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testBlockPlayer(){
    	$playerName = 'testyoplay1';
        $rlt = $this->api->blockPlayer($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testunblockPlayer(){
    	$playerName = 'testyoplay1';
        $rlt = $this->api->unblockPlayer($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testQueryPlayerBalance(){
    	$playerName = 'testyoplay1';
        $rlt = $this->api->queryPlayerBalance($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testDepositToGame() { 
	    $playerName = 'testyoplay1';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

    public function testWithdrawFromGame() { 
	    $playerName = 'testyoplay1';
	    $depositAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

    public function testSyncGameLogs() { 
	    $token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-11-16 00:30:00');
		$dateTimeTo = new DateTime('2017-11-16 23:59:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-11-16 00:30:00');
		$dateTimeTo = new DateTime('2017-11-16 23:59:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}
}
