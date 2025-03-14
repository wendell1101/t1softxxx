<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_vivo_api extends BaseTesting {

	private $platformCode = VIVO_API;
	private $platformName = 'VIVO_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	    $this->testDeposit();
	    //$this->testQueryPlayerBalance();
	    //$this->testWithdraw();
	    //$this->testIsPlayerExist();
	   	//$this->testLogIn();
	    //$this->testQueryForwardGame();

	    //$this->testSyncGameLogs();
	    // $this->testIsPlayerExist();
	    //$this->testSyncMergeToGameLogs();
	     //$this->testLogout();
	
	
	}

    public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testBase() {

	
	}

	private function testLogIn() { 
	
		//$username = $this->existPlayer; 
		$username = 'testshu'; 
		$this->utils->debug_log("create player", $username);
		$rlt = $this->api->login($username);
		$this->test($rlt['success'], true, 'Test Player Login to VIVO');
	}


	private function testDeposit() { 

	    $playerName = 'testshu';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to VIVO');
		
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testshu';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		
	}

	private function testWithdraw() {
		$playerName = 'testshu';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to VIVO');
		
	}
	

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-05-04 12:00:00');
		$dateTimeTo = new DateTime('2017-05-13 17:00:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		//var_dump($rlt);
        $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to VIVO');
		//$this->utils->readExcel();
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-05-04 12:00:00');
		$dateTimeTo = new DateTime('2017-05-13 17:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to VIVO');
	}

	private function testIsPlayerExist() {
	
		$username =  'testshu1';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}


	private function testQueryForwardGame(){ 
     	$username =  'testshu1';
		$rlt = $this->api->queryForwardGame($username);
		//var_dump($rlt);
		//redirect($rlt['url']);
	}

	

	private function testLogout() {
		$playerName = 'siopao36';
		$rlt = $this->api->logout($playerName);
		$this->utils->debug_log('logout', $rlt);
		$this->test($rlt['success'], true, 'Test Player Logout to GSPT');
		
	}

	



}