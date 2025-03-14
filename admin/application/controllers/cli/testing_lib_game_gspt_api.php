<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_gspt_api extends BaseTesting {

	private $platformCode = GSPT_API;
	private $platformName = 'GSPT';

	private $api = null;
	private $existPlayer = 'antok33';


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

		$this->init();

		// $this->testCreatePlayer();
		// $this->testIsPlayerExist();
		// $this->testBlockPlayer();
		// $this->testUnblockPlayer();
	   //  $this->testQueryPlayerInfo();
		// $this->testDeposit();
		// $this->testQueryPlayerBalance();
		// $this->testWithdraw();
		// $this->testLogout();
		// $this->testSyncGameLogs() ;
		 $this->testSyncMergeToGameLogs();
    	// $this->testChangePassword();
		// $this->checkLoginStatus();
		// $this->testQueryForwardGame();


	}

    public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testBase() {
	$this->init();
	//create player
	$username = 'testGSPT' . random_string('numeric');
		//$username = 'edna23';
		$this->utils->debug_log("create player", $username);
		$password = 'edna23';
		$player = $this->getFirstPlayer($username);

		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');
		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->test($rlt['success'], true, $username . ' createPlayer for ' . $this->platformName);
		$this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);


	// if ($rlt['success']) {
	$gameUsername = $this->api->getGameUsernameByPlayerUsername($username);
	$this->utils->debug_log('=====get game username: ' . $username . ' to ' . $gameUsername . ' ======================================================');
	// }
	if ($rlt['success']) {
		//check exists
		$this->utils->debug_log('=====isPlayerExist: ' . $username . ' ======================================================');
		$rlt = $this->api->isPlayerExist($username);

		$this->test($rlt['success'], true, $username . ' isPlayerExist success for ' . $this->platformName);
		$this->test($rlt['exists'], true, $username . ' isPlayerExist exists for ' . $this->platformName);
		$this->utils->debug_log('=====result of isPlayerExist: ' . $username . '======================================================', $rlt);
	}

	if ($rlt['success']) {
		$depositAmount = 3.2;
		//deposit
		$this->utils->debug_log('=====depositToGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
		$rlt = $this->api->depositToGame($username, $depositAmount);
		$this->test($rlt['success'], true, 'depositToGame for ' . $this->platformName);
		$this->utils->debug_log('=====result of depositToGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);
	}

	if ($rlt['success']) {

		//query balance
		$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
		$rlt = $this->api->queryPlayerBalance($username);
		$this->test($rlt['success'], true, 'queryPlayerBalance for ' . $this->platformName);
		$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value for ' . $this->platformName);
		$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
	}


	if ($rlt['success']) {

		$withdrawaltAmount = 1.2;
		//withdrawal
		$this->utils->debug_log('=====withdrawFromGame: ' . $username . ' === ' . $withdrawaltAmount . ' ===================================================');
		$rlt = $this->api->withdrawFromGame($username, $withdrawaltAmount);
		$this->test($rlt['success'], true, 'withdrawFromGame for ' . $this->platformName);
		$this->utils->debug_log('=====result of withdrawFromGame: ' . $username . ' === ' . $withdrawaltAmount . '======================================================', $rlt);
	}

	if ($rlt['success']) {
		//block player
		$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
		$rlt = $this->api->blockPlayer($username);
		$this->test($rlt['success'], true, 'blockPlayer for ' . $this->platformName);
		$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);
	}

	if ($rlt['success']) {
		//unblock player
		$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
		$rlt = $this->api->unblockPlayer($username);
		$this->test($rlt['success'], true, 'unblockPlayer for ' . $this->platformName);
		$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

	 }
	if ($rlt['success']) {
		//change password
		$this->utils->debug_log('=====changePassword: ' . $username . ' ======================================================');
		$newPassword = 'newPass123';
		$rlt = $this->api->changePassword($username, $password, $newPassword);
		$this->test($rlt['success'], true, 'changePassword for ' . $this->platformName);
		// $this->test($rlt['password'], $newPassword, 'changePassword to ' . $newPassword . ' for ' . $this->platformName);
		$this->utils->debug_log('=====result of changePassword: ' . $username . '======================================================', $rlt);

	}

}

	private function testCreatePlayer() {

//		$username = 'testGSPT' . random_string('numeric');
//		//$username = 'edna23';
//		$this->utils->debug_log("create player", $username);
//		$password = 'edna23';
//		$player = $this->getFirstPlayer($username);

		$username = 'testzai';
		$playerId = 1;
		$password = 'pass123';

		#$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');
		$rlt = $this->api->createPlayer($username, $playerId, $password, null);
		print_r($rlt);
		#$this->test($rlt['success'], true, 'create player: ' . $username);
		#$this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);

	}

	private function testIsPlayerExist() {

		$username = $this->existPlayer;
		$rlt = $this->api->isPlayerExist($username);
		$this->test($rlt['success'], true, 'player ' . $username . ' already exists');

	}

	private function testBlockPlayer() {
		$username = $this->existPlayer;
		$rlt = $this->api->blockPlayer($username);
		$this->utils->debug_log('blockPlayer', $rlt);
		$this->test($rlt['success'], true, 'blockPlayer for GSPT');
	}

	private function testUnblockPlayer() {
		$username = $this->existPlayer;
		$rlt = $this->api->unblockPlayer($username);
		$this->utils->debug_log('unblockPlayer', $rlt);
		$this->test($rlt['success'], true, 'unblockPlayer for GSPT');
	}

	private function testQueryPlayerInfo() {
		$username = $this->existPlayer;
		$rlt = $this->api->queryPlayerInfo($username);
		var_dump($rlt);
		$this->utils->debug_log('queryPlayerInfo', $rlt);
		$this->test($rlt['success'], true, 'testQueryPlayerInfo for GSPT');
	}

	public function testQueryPlayerBalance() {
		$playerName = 'testzai'; //$this->existPlayer;

		$rlt = $this->api->queryPlayerBalance($playerName);
		print_r($rlt);
		#$this->utils->debug_log('queryPlayerBalance', $rlt);
		#$this->test($rlt['success'], true, 'balance for ' . $playerName);

	}

	private function testDeposit() {
		$playerName = 'testzai'; //$this->existPlayer;
		$depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		print_r($rlt);
		#$this->utils->debug_log('depositToGame', $rlt);
		#$this->test($rlt['success'], true, 'Test Player Deposit to GSPT');

	}

	private function testWithdraw() {
		$playerName ='testzai'; $this->existPlayer;
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to GSPT');

	}

	private function testLogout() {
		$playerName = $this->existPlayer;
		$rlt = $this->api->logout($playerName);
		$this->utils->debug_log('logout', $rlt);
		$this->test($rlt['success'], true, 'Test Player Logout to GSPT');

	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-10-05 02:00:00');
		$dateTimeTo = new DateTime('2016-10-05 02:30:00');

		$playerName = 'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
         $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to GSPT');
		//$this->utils->readExcel();
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-11-00 12:00:00');
		$dateTimeTo = new DateTime('2016-11-14 17:00:00');

		$playerName = 'ogtest006';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to LB');
	}


	private function testChangePassword(){

		$playerName = $this->existPlayer;
		$newPassword = 'wapak20';
		$password = null;
	    $rlt = $this->api->changePassword($playerName, $password, $newPassword);
	    var_dump($rlt);
	}


	private function checkLoginStatus(){

		$playerName = $this->existPlayer;
	    $rlt = $this->api->checkLoginStatus($playerName);
	    var_dump($rlt);
	}



	private function testQueryForwardGame(){

		$extra =array(
			'username' => 'antok33',
			'password' =>'antok33' ,
		    'language' =>'en',
		    'game_code' => '7bal',
			);

//var_dump($extra); exit();

	$rlt = $this->api->queryForwardGame($extra['username'], $extra);
	 var_dump($rlt);
	  redirect($rlt['url']);
	}



}