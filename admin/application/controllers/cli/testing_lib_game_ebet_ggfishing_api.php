<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebet_ggfishing_api extends BaseTesting {

	private $platformCode = EBET_GGFISHING_API;

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
	   // $this->testLogin();
	   // $this->testLogout();
	   // $this->testSyncGameLogs();
	   $this->testSyncMergeToGameLogs();

	   // $this->testQueryForwardGame();
	   // $this->testBlockPlayer();
	   // $this->testUnblockPlayer();
	   // $this->testIsPlayerExist();
	   // $this->testEncrypt();


	}
	private function testLogout(){
		$username =  'testgar';
		$rlt = $this->api->logout($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {

		$username =  'testgar';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() {

		$username = 'testgar';
		$password = 'password';
		$player_id = '56962';

		$rlt = $this->api->createPlayer($username,$player_id,$password);
		echo "<pre>";print_r($rlt);exit;

	}

	public function testQueryPlayerBalance() {

		$playerName = 'testgar';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-09-15 00:00:00');
		$dateTimeTo = new DateTime('2017-09-15 23:59:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-09-20 00:00:00');
		$dateTimeTo = new DateTime('2017-09-20 23:59:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testLogin() {

		$username =  'testshu';
		$rlt = $this->api->login($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testDeposit() {

	    $playerName = 'testshu';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testWithdraw() {
		$playerName = 'testgray';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testQueryForwardGame(){
     	$username =  'testgray';
        $extra = [
            "lang"      => "en_US",
            "minigame"  => "false",
            "game_code" => "S-DG02",
            "is_mobile" => "false",
            "menumode"  => "on"
        ];
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