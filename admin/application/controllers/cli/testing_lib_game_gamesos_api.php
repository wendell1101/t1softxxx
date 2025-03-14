<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_gamesos_api extends BaseTesting {

	private $platformCode = GAMESOS_API;
	private $platformName = 'GAMESOS';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		

	}

	public function testAll() {

	 	$this->init();
	  // $this->testCreatePlayer();
	  //   $this->testDeposit();
	    //$this->testWithdraw();
	//   $this->testQueryPlayerBalance();
	    //$this->testAddandUpdateGameList();
	  	$this->testSyncGameLogs();
	    // $this->testIsPlayerExist();
	 //  $this->testQueryForwardGame();
	    // $this->testSyncMergeToGameLogs();
	     //$this->testLogout();
	
	
	}

    public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testBase() {

	
}

	private function testCreatePlayer() { 

		
		 $this->utils->debug_log('hello');
	
	
		$username = 'chistie10';
		$this->utils->debug_log("create player", $username);
		$password = 'chistie10';
		//$playerId = $this->api->getPlayerIdInGameProviderAuth($username);
		$playerId =21;
		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');

		$extra = array();
		 $rlt = $this->api->createPlayer($username, $playerId, $password, null, $extra );
		 // var_dump($rlt);
		 $this->test($rlt['success'], true, 'create player: ' . $username);
		 $this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);
		
	}


	private function testDeposit() { 
	    $playerName = 'testwebet';
	    $depositAmount = 2.00;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);

		var_dump($rlt);
		$this->utils->debug_log('depositToGame', $rlt);
		 $this->test($rlt['success'], true, 'Test Player Deposit to GAMESOS');
		
	}


	private function testWithdraw() {
		$playerName = 'gutom37';
	    $withdrawAmount = 10.00;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to GAMESOS');
		
	}

	public function testQueryPlayerBalance() {
		//$playerName = 'siopao36';
		$playerName = 'tstwebet3';

		$rlt = $this->api->queryPlayerBalance($playerName);
		var_dump($rlt);
		//$$this->utils->debug_log('queryPlayerBalance', $rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-06-14 00:00:00');
		$dateTimeTo = new DateTime('2016-06-18 17:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		//var_dump($rlt);
         $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to GD');
		//$this->utils->readExcel();
	}


	private function testIsPlayerExist() {

		$username =  'gutom37';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);
		//$this->test($rlt['success'], true, 'player ' . $username . ' already exists');

	}


	private function testQueryForwardGame(){ 
     $username = 'testwebet';


    // $extra =  array(
    // 	'language' =>'EN' ,
    // 	'game_code' => 'xc_polartaleslots',
    // 	'playmode'=>'real'
    // 	);
    $extra =  array(
    	'language' =>'EN' ,
    	'code' => 'xc_mobilenonstoppartyslots',
    	'mode'=>'real',
    	'mobile' => 'true'
    	);



	$rlt = $this->api->queryForwardGame($username, $extra);
	var_dump($rlt);//redirect($rlt['url']);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
	    $dateTimeFrom = new DateTime('2016-06-15 00:00:00');
		$dateTimeTo = new DateTime('2016-06-16 06:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to GAMESOS');
	}

	private function testLogout() {
		$playerName = 'siopao36';
		$rlt = $this->api->logout($playerName);
		$this->utils->debug_log('logout', $rlt);
		$this->test($rlt['success'], true, 'Test Player Logout to GSPT');
		
	}


	private function testAddandUpdateGameList(){

		$playerName = 'gutom37';

		$rlt = $this->api->AddandUpdateGameList($playerName);

	}
	



}