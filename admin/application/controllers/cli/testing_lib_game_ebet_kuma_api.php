<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebet_kuma_api extends BaseTesting {

	private $platformCode = EBET_KUMA_API;
	private $platformName = 'EBET_KUMA_API';

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
	   // $this->testSyncGameLogs();
	   $this->testSyncMergeToGameLogs();
	   
	   // $this->testQueryForwardGame();
	   // $this->testBlockPlayer();
	   // $this->testUnblockPlayer();
	   // $this->testIsPlayerExist();
	   // $this->testLogout();
	   // $this->testEncrypt();


	}
	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-08-23 00:00:00');
		$dateTimeTo = new DateTime('2017-08-23 23:59:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
       $dateTimeFrom = new DateTime('2017-08-23 00:00:00');
		$dateTimeTo = new DateTime('2017-08-23 23:59:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {

		$username =  'testshu';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testLogin() {

		$username =  'testshu';
		$rlt = $this->api->login($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() {

		$username = 'testshu';
		$password = 'password';
		$player_id = '56963';

		$rlt = $this->api->createPlayer($username,$player_id,$password);
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