<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_gd_api extends BaseTesting {

	private $platformCode = GD_API;
	private $platformName = 'GD';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	    // $this->testCreatePlayer();
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    // $this->testQueryPlayerBalance();
	    // $this->testIsPlayerExist();
	    // $this->testQueryForwardGame();
	    $this->testSyncGameLogs();
	    $this->testSyncMergeToGameLogs();
	     //$this->testLogout();
	
	
	}

    public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}	
	
	private function testSyncGameLogs() {

		$token = 'abc123d';
		// $dateTimeFrom = new DateTime('2016-11-12 00:00:00');
		// $dateTimeTo = new DateTime('2016-11-14 23:59:59');
		$dateTimeFrom = new DateTime('2018-04-12 16:00:00');
		$dateTimeTo = new DateTime('2018-04-12 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2018-04-12 16:00:00');
		$dateTimeTo = new DateTime('2018-04-12 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to GD');
	}

	private function testCreatePlayer() { 
	
		//$username = $this->existPlayer; 
		$username = 'siopao38';
		$this->utils->debug_log("create player", $username);
		$password = 'siopao38';
		//$playerId = $this->api->getPlayerIdInGameProviderAuth($username);
		$playerId =12;
	
		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');


		 $rlt = $this->api->createPlayer($username, $playerId, $password, null, $extra );
		 //var_dump($rlt);
		 $this->test($rlt['success'], true, 'create player: ' . $username);
		 $this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);
		
	}


	private function testDeposit() { 
	    $playerName = 'siopao36';
	    $depositAmount = 30;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		 $this->test($rlt['success'], true, 'Test Player Deposit to GD');
		
	}


	private function testWithdraw() {
		$playerName = 'siopao36';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to GD');
		
	}

	public function testQueryPlayerBalance() {
		//$playerName = 'siopao36';
		$playerName = 'testyabo3';

		$rlt = $this->api->queryPlayerBalance($playerName);
		//var_dump($rlt);
		//$$this->utils->debug_log('queryPlayerBalance', $rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		
	}

	private function testIsPlayerExist() {
	
		$username =  'siopao36';
		$rlt = $this->api->isPlayerExist($username);
		//var_dump($rlt);
		//$this->test($rlt['success'], true, 'player ' . $username . ' already exists');

	}

	private function testQueryForwardGame(){ 
     $username =  'siopao36';

    $extra =  array('game_lang' =>'en-us' , 'game_code' => 'RNG4583');
	$rlt = $this->api->queryForwardGame($username, $extra);
	// var_dump($rlt);
	//redirect($rlt[1]['url']);
	}

	private function testLogout() {
		$playerName = 'siopao36';
		$rlt = $this->api->logout($playerName);
		$this->utils->debug_log('logout', $rlt);
		$this->test($rlt['success'], true, 'Test Player Logout to GSPT');
		
	}

}