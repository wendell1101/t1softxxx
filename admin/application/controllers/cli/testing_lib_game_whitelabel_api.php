<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_whitelabel_api extends BaseTesting {

	private $platformCode = SBOBET_API;
	private $platformName = 'SBOBET_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);


	}

	public function testAll() {
        $this->init();
        // $this->testCreatePlayer();
        // $this->testQueryPlayerBalance();

        //$this->testLogin();
        //$this->testIsPlayerExist();
        //$this->testDeposit();
        // $this->testWithdraw();
        // $this->testLogout();
        // $this->testChangePassword();
        //$this->testIsPlayerExist();

        //$this->testCallBack();
        // $this->testQueryForwardGame();
        //$this->testBlockPlayer();
        //this->testUnblockPlayer();
        //$this->testSyncGameLogs();
        $this->testSyncMergeToGameLogs();
        // $this->testCreateMobilePlayer();
        //$this->testCreateAgent();
        // $this->testUpdateDomain();
        //$this->testPayLoad();
        // $this->testUpdateAgentBetSettings();
	}

	private function testIsPlayerExist() {

		$username =  'testjerb09';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() {
		//$username = $this->existPlayer;
		//echo "test";exit();
		/*$username = 'testjerb06';
		$password = 'password';
		$player_id = '56969';*/

		$username = 'testjerb09';
		$password = 'password';
		$player_id = '56973';

		$rlt = $this->api->createPlayer($username,$player_id,$password);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreateMobilePlayer() {

		//$username = $this->existPlayer;
		$username = 'sbetestshu';

		$rlt = $this->api->createMobilePlayer($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testLogin() {

		$username =  'testjerb06';
     	$profolio = 'casino';
     	$extra = array(
				'profolio' => $profolio,
		);
		$extra = null;
		$rlt = $this->api->login($username,$extra);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testLogout() {

		$username = 'testshu';

		$rlt = $this->api->logout($username);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {

		echo $token = 'okd.testdan02';
		$dateTimeFrom = new DateTime('2017-11-10 00:00:00');
		$dateTimeTo = new DateTime('2017-11-11 23:10:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		// echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'okd.testdan02';
        $dateTimeFrom = new DateTime('2017-11-10 00:00:00');
        $dateTimeTo = new DateTime('2017-11-11 23:10:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		 echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'testshu';
		//$oldPassword = $this->password;
		$oldPassword = 'garry1';
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() {

	    $playerName = 'testjerb06';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	public function testQueryPlayerBalance() {

		$playerName = 'testjack0000';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testWithdraw() {
		$playerName = 'testjerb06';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testQueryForwardGame(){
     	$username =  'testjerb06';
     	$profolio = 'casino';
     	$extra = array(
				'profolio' => $profolio,
		);
		//$extra = null;
		$rlt = $this->api->queryForwardGame($username,$extra);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testkeir';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testkeir';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCallBack(){
		$result = array(
			"acctId" => "T1testjerb06",
			"language"	=> "en_US",
			"merchantCode" => "OKADA",
			"token"			=> "7518fd7cc7c891ed186638e2964d5eda",
			"serialNo"		=> "20120722224255982841",
		);
		$rlt = $this->api->callback($result);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreateAgent(){
		$rlt = $this->api->createAgent();
		echo "<pre>";print_r($rlt);exit();
	}

	private function testUpdateDomain(){
		$rlt = $this->api->updateAgentDomain();
		echo "<pre>";print_r($rlt);exit();
	}

	private function testPayLoad() {
		$refNo = "S00112246033";
		$rlt = $this->api->getBetPayload($refNo);
		echo "<pre>";print_r($rlt);exit();
	}

	private function testUpdateAgentBetSettings() {
		$rlt = $this->api->updateAgentBetSettings();
		echo "<pre>";print_r($rlt);exit();
	}


}