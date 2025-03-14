<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_kenogame_api extends BaseTesting {

	private $platformCode = KENOGAME_API;
	private $platformName = 'KENOGAME';

	private $api = null;
	private $existPlayer = 'polarisd';
	

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);

	}

	public function testAll() {

		$this->init();

	// $this->testCreatePlayer();
		// $this->testIsPlayerExist();


		// $this->testDeposit();
		 // $this->testWithdraw();
	//  $this->testQueryPlayerBalance();
	  //    $this->testBlockPlayer();
	  //    $this->testUnblockPlayer();


	   //  $this->testQueryPlayerInfo();
	     
		// $this->testLogout();
		 $this->testSyncGameLogs() ;
		// $this->testSyncMergeToGameLogs();
    	// $this->testChangePassword();
		// $this->checkLoginStatus();
		//$this->testQueryForwardGame();

	
	}

    public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testBase() {
	// $this->init();
	// //create player
	// //$username = 'testGSPT' . random_string('numeric');
	// 	//$username = 'edna23';
	// 	$username = $this->existPlayer; 

	// 	$this->utils->debug_log("create player", $username);
	// 	$password = 'edna23';
	// 	$player = $this->getFirstPlayer($username);

	// 	$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');
	// 	$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
	// 	$this->test($rlt['success'], true, $username . ' createPlayer for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);

	
	// // if ($rlt['success']) {
	// $gameUsername = $this->api->getGameUsernameByPlayerUsername($username);
	// $this->utils->debug_log('=====get game username: ' . $username . ' to ' . $gameUsername . ' ======================================================');
	// // }
	// if ($rlt['success']) {
	// 	//check exists
	// 	$this->utils->debug_log('=====isPlayerExist: ' . $username . ' ======================================================');
	// 	$rlt = $this->api->isPlayerExist($username);
		
	// 	$this->test($rlt['success'], true, $username . ' isPlayerExist success for ' . $this->platformName);
	// 	$this->test($rlt['exists'], true, $username . ' isPlayerExist exists for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of isPlayerExist: ' . $username . '======================================================', $rlt);
	// }

	// if ($rlt['success']) {
	// 	$depositAmount = 3.2;
	// 	//deposit
	// 	$this->utils->debug_log('=====depositToGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
	// 	$rlt = $this->api->depositToGame($username, $depositAmount);
	// 	$this->test($rlt['success'], true, 'depositToGame for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of depositToGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);
	// }

	// if ($rlt['success']) {

	// 	//query balance
	// 	$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
	// 	$rlt = $this->api->queryPlayerBalance($username);
	// 	$this->test($rlt['success'], true, 'queryPlayerBalance for ' . $this->platformName);
	// 	$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
	// }
	

	// if ($rlt['success']) {

	// 	$withdrawaltAmount = 1.2;
	// 	//withdrawal
	// 	$this->utils->debug_log('=====withdrawFromGame: ' . $username . ' === ' . $withdrawaltAmount . ' ===================================================');
	// 	$rlt = $this->api->withdrawFromGame($username, $withdrawaltAmount);
	// 	$this->test($rlt['success'], true, 'withdrawFromGame for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of withdrawFromGame: ' . $username . ' === ' . $withdrawaltAmount . '======================================================', $rlt);
	// }

	// if ($rlt['success']) {
	// 	//block player
	// 	$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
	// 	$rlt = $this->api->blockPlayer($username);
	// 	$this->test($rlt['success'], true, 'blockPlayer for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);
	// }
	
	// if ($rlt['success']) {
	// 	//unblock player
	// 	$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
	// 	$rlt = $this->api->unblockPlayer($username);
	// 	$this->test($rlt['success'], true, 'unblockPlayer for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

	//  }
	// if ($rlt['success']) {
	// 	//change password
	// 	$this->utils->debug_log('=====changePassword: ' . $username . ' ======================================================');
	// 	$newPassword = 'newPass123';
	// 	$rlt = $this->api->changePassword($username, $password, $newPassword);
	// 	$this->test($rlt['success'], true, 'changePassword for ' . $this->platformName);
	// 	// $this->test($rlt['password'], $newPassword, 'changePassword to ' . $newPassword . ' for ' . $this->platformName);
	// 	$this->utils->debug_log('=====result of changePassword: ' . $username . '======================================================', $rlt);

	// }
	
}

	private function testCreatePlayer() {
	
		//$username = $this->existPlayer; 
		$username = 'siopao36';
		$this->utils->debug_log("create player", $username);
		$password = 'siopao36';
		$playerId = $this->api->getPlayerIdInGameProviderAuth($username);
		//$player = $this->getFirstPlayer($username);

		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');


		$extra = array(
			'VendorSite' => site_url(),
			'FundLink' => 'http://'.$_SERVER['HTTP_HOST'],
			'PlayerRealName' => $username,
			'PlayerCurrency' => 'C01',
			'PlayerCredit' => '6',
			'PlayerAllowStake' => '3',
			'Trial' => '1',
			'PlayerIP' => $_SERVER['REMOTE_ADDR'],
			'VendorRef' => rand(0,5),
			'Remarks' => 'dd',
			'Language' => 'sc',
			'RebateLevel' => '1',

		);
		 $rlt = $this->api->createPlayer($username, $playerId, $password, null, $extra );
		 var_dump($rlt);
		 $this->test($rlt['success'], true, 'create player: ' . $username);
		 $this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);
		
	}

	private function testIsPlayerExist() {
	
		$username = $this->existPlayer; 
		$rlt = $this->api->isPlayerExist($username);
		$this->test($rlt['success'], true, 'player ' . $username . ' already exists');

	}

	private function testBlockPlayer() {
		$username = $this->existPlayer; 
		$rlt = $this->api->blockPlayer($username);
		//var_dump($rlt);
		$this->utils->debug_log('blockPlayer', $rlt);
		$this->test($rlt['success'], true, 'blockPlayer for KENOGAME');
	}

	private function testUnblockPlayer() {
		$username = $this->existPlayer; 
		$rlt = $this->api->unblockPlayer($username);
		//var_dump($rlt);
		$this->utils->debug_log('unblockPlayer', $rlt);
		$this->test($rlt['success'], true, 'unblockPlayer for KENOGAME');
	}

	private function testQueryPlayerInfo() {
		$username = $this->existPlayer; 
		$rlt = $this->api->queryPlayerInfo($username);
		//var_dump($rlt);
		$this->utils->debug_log('queryPlayerInfo', $rlt);
		$this->test($rlt['success'], true, 'testQueryPlayerInfo for KENOGAME');
	}

	public function testQueryPlayerBalance() {
		$playerName = $this->existPlayer;
		$rlt = $this->api->queryPlayerBalance($playerName);
		//var_dump($rlt);
		$this->utils->debug_log('queryPlayerBalance', $rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		
	}

	private function testDeposit() { 
		$playerName = $this->existPlayer;
		//$playerName  ='testGSPT29041514';
		
		$playerName = 'siopao36';
		$depositAmount = 2;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		var_dump($rlt);
		$this->utils->debug_log('depositToGame', $rlt);
		 $this->test($rlt['success'], true, 'Test Player Deposit to KENOGAME');
		
	}

	private function testWithdraw() { 
		$playerName = $this->existPlayer;
		//$playerName  ='testGSPT29041514';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		//var_dump($rlt);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to KENOGAME');
		
	}

	private function testLogout() {
		// $playerName = $this->existPlayer;
		// $rlt = $this->api->logout($playerName);
		// $this->utils->debug_log('logout', $rlt);
		// $this->test($rlt['success'], true, 'Test Player Logout to GSPT');
		
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-12-27 14:00:00');
		$dateTimeTo = new DateTime('2017-12-27 23:0:19');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		var_dump($rlt);
         $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to KENOGAME');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2013-12-01 00:00:00');
		$dateTimeTo = new DateTime('2016-04-09 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to KENOGAME');
	}


	private function testChangePassword(){

		// $playerName = $this->existPlayer;
		// $newPassword = 'wapak20';
		// $password = null;
	 //    $rlt = $this->api->changePassword($playerName, $password, $newPassword);
	 //    var_dump($rlt);
	}


	private function checkLoginStatus(){

	// 	$playerName = $this->existPlayer;
	//     $rlt = $this->api->checkLoginStatus($playerName);
	//     var_dump($rlt);
	 }



	private function testQueryForwardGame(){


		$username = $this->existPlayer; 
		$extra = array(
			'VendorSite' => site_url(),
			'FundLink' => 'http://'.$_SERVER['HTTP_HOST'],
			'PlayerRealName' => $username,
			'PlayerCurrency' => 'C01',
			'PlayerCredit' => '6',
			'PlayerAllowStake' => '3',
			'Trial' => '1',
			'PlayerIP' => $_SERVER['REMOTE_ADDR'],
			'VendorRef' => rand(0,5),
			'Remarks' => 'dd',
			'Language' => 'sc',
			'RebateLevel' => '1',

		);

	$rlt = $this->api->queryForwardGame($username, $extra);
	    var_dump($rlt);
	   //redirect($rlt['Link']);
	}



}