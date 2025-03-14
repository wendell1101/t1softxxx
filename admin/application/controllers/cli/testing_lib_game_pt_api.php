<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_pt_api extends BaseTesting {

	private $platformCode = PT_API;
	private $platformName = 'PT';
	// protected $depositAmount = 1.2;
	private $api;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		// $this->test($api == null, false, 'init api');
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
//		$this->testGetPassword();
		// $this->testBase();
		// $this->testQueryPlayerBalance();
		//		$this->testQueryPlayerInfo();
		//		$this->testCheckLoginStatus();
		//		$this->testChangePassword();
		//		$this->testBlockPlayer();
		//		$this->testUnblockPlayer();
		//		$this->testLogin();
		//		$this->testLogout();
		//		$this->testDepositToGame();
		//		$this->testWithdrawFromGame();
		//		$this->testUpdateInfo();
		//		$this->testQueryForwardGame();
		//		$this->testQueryGameRecords();
		//		$this->testPlayerDailyBalance();
				$this->testQueryTransaction();
		//		$this->testSyncGameLogs();
		//		$this->testTotalBettingAmount();
		//		$this->testSyncGameRecords();
		// $this->testIsPlayerExist();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testBatchQueryPlayerBalance() {
		$rlt = $this->api->batchQueryPlayerBalance(null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}

	# Withdraws all balances from all players
	// private function testBatchWithdrawPlayerBalance(){
	// 	$this->utils->debug_log("Querying all players' balances...");
	// 	$rlt = $this->api->listPlayers(null);
	// 	$this->utils->debug_log("Query result: ".count($rlt['players']). " players found.");

	// 	foreach($rlt['players'] as $aPlayer){
	// 		if(empty($aPlayer['PLAYERNAME']) || empty($aPlayer['BALANCE'])){
	// 			# Ignoring users with no name or no balance
	// 			continue;
	// 		}
	// 		$this->utils->debug_log($aPlayer);
	// 		$this->utils->debug_log("Balance for Player [".$aPlayer['PLAYERNAME']."] is [".$aPlayer['BALANCE']."], withdrawing from it...");
	// 		$rlt = $this->api->withdrawFromGame($aPlayer['PLAYERNAME'], $aPlayer['BALANCE'], false);
	// 		$this->test($rlt['success'], true, "Test whether withdrawal from Player [".$aPlayer['PLAYERNAME']."] of amount [".$aPlayer['BALANCE']."] is successful");
	// 		if($rlt['success']){
	// 			$this->test($rlt['currentplayerbalance'], 0, "Test Player [".$aPlayer['PLAYERNAME']."]'s balance is 0 after the withdrawal");
	// 		}
	// 		$this->utils->debug_log("Withdrawal result: ". empty($rlt['success']) ? 'success' : 'fail');
	// 	}
	// }

	private function testBase() {
		//create player
		$username = 'test' . random_string('alnum');
		$password = '12344321';
		$depositAmount = 1.2;
		$player = $this->getFirstPlayer($username);
		$this->utils->debug_log("create player", $username, 'password', $password, 'amount', $depositAmount);

		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');
		$rlt = $this->api->createPlayer($username, $player->playerId, $password);
		// $this->utils->debug_log('after createPlayer', $rlt);
		$this->test($rlt['success'], true, $username . ' createPlayer for PT');
		$this->utils->debug_log('=====result of createPlayer: ' . $username . '======================================================', $rlt);

		$gameUsername = $this->api->getGameUsernameByPlayerUsername($username);
		$this->utils->debug_log('=====get game username: ' . $username . ' to ' . $gameUsername . ' ======================================================');

		if ($rlt['success']) {
			//check exists
			$this->utils->debug_log('=====isPlayerExist: ' . $username . ' ======================================================');
			$rlt = $this->api->isPlayerExist($username);
			// $this->utils->debug_log('after isPlayerExist', $rlt);
			$this->test($rlt['success'], true, $username . ' isPlayerExist success for PT');
			$this->test($rlt['exists'], true, $username . ' isPlayerExist exists for PT');
			$this->utils->debug_log('=====result of isPlayerExist: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {
			//query player info
			$this->utils->debug_log('=====queryPlayerInfo: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for PT');
			//must = game username
			//ignore lower/upper
			$this->test(strtolower($rlt['playerInfo']['playerName']), strtolower($gameUsername), $username . ' queryPlayerInfo username ' . $gameUsername . ' for PT');
			// $this->utils->debug_log('succ', $succ);
			$this->utils->debug_log('=====result of queryPlayerInfo: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance for PT');
			$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value for PT');
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//deposit
			$this->utils->debug_log('=====depositToGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
			$rlt = $this->api->depositToGame($username, $depositAmount);
			$this->test($rlt['success'], true, 'depositToGame for PT');
			$this->test($rlt['currentplayerbalance'], $depositAmount, 'depositToGame: ' . $rlt['currentplayerbalance']);
			$this->utils->debug_log('=====result of depositToGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance after deposit for PT');
			$this->test($rlt['balance'], $depositAmount, 'queryPlayerBalance balance value after deposit for PT');
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//withdrawal
			$this->utils->debug_log('=====withdrawFromGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
			$rlt = $this->api->withdrawFromGame($username, $depositAmount);
			$this->test($rlt['success'], true, 'withdrawFromGame for PT');
			$this->test($rlt['currentplayerbalance'], 0, 'withdrawFromGame: ' . $rlt['currentplayerbalance']);
			$this->utils->debug_log('=====result of withdrawFromGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance after withdrawal for PT');
			$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value after withdrawal for PT');
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//block player
			$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer for PT');
			$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for PT');
			$this->test($rlt['playerInfo']['blocked'], true, $username . ' queryPlayerInfo username ' . $gameUsername . ' for PT');
			$this->utils->debug_log('=====result of queryPlayerInfo after blockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//unblock player
			$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->unblockPlayer($username);
			$this->test($rlt['success'], true, 'unblockPlayer for PT');
			$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for PT');
			$this->test($rlt['playerInfo']['blocked'], false, $username . ' queryPlayerInfo username ' . $gameUsername . ' for PT');
			$this->utils->debug_log('=====result of queryPlayerInfo after unblockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//change password
			$this->utils->debug_log('=====changePassword: ' . $username . ' ======================================================');
			$newPassword = 'newPass123';
			$rlt = $this->api->changePassword($username, $password, $newPassword);
			$this->test($rlt['success'], true, 'changePassword for PT');
			$this->test($rlt['password'], $newPassword, 'changePassword to ' . $newPassword . ' for PT');
			$this->utils->debug_log('=====result of changePassword: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {
			//block for save
			$this->utils->debug_log('=====blockPlayer for save: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer save for PT');
			$this->utils->debug_log('=====result of blockPlayer for save: ' . $username . '======================================================', $rlt);
		}
	}

	private function testGetPassword() {
		$this->load->model('game_provider_auth');
		$username = 'test_' . random_string('alnum');
		$player = array('id' => 1, 'username' => $username,
			'password' => '123456', 'source' => Game_provider_auth::SOURCE_REGISTER);
		$this->game_provider_auth->savePasswordForPlayer($player, $this->platformCode);

		$rlt = $this->api->getPassword($username);
		// log_message('error', 'get password:' . $password);
		$this->test($rlt['password'], $player['password'], 'test get password for PT');

	}

	private function testQueryPlayerBalance() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->queryPlayerBalance($player->username);
		$this->utils->debug_log('player', $player, 'rlt', $rlt);
		$this->test($rlt['success'], true, 'testQueryPlayerBalance for PT');
		$this->test($rlt['balance'], 0, 'testQueryPlayerBalance balance value for PT');
	}

	private function testQueryPlayerInfo() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->queryPlayerInfo($player->username);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testQueryPlayerInfo for PT');
		$this->test(is_array($rlt['playerInfo']), true, 'testQueryPlayerInfo for PT');
	}

	private function testCheckLoginStatus() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->checkLoginStatus($player->username);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testCheckLoginStatus for PT');
		$this->test($rlt['loginStatus'], false, 'check if login for PT');
	}

	private function testChangePassword() {
		$player = $this->getFirstPlayer();
		$oldPassword = '12344321';
		$newPassword = 'newPass123';
		$this->utils->debug_log('username', $player->username);
		$rlt = $this->api->changePassword($player->username, $oldPassword, $newPassword);
		$this->utils->debug_log('rlt', $rlt);
		$this->test($rlt['success'], true, 'testChangePassword for PT');
		// $this->test($rlt['password'], 'newPass123', 'check new password');
	}

	private function testBlockPlayer() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->blockPlayer($player->username);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testBlockPlayer for PT');
	}

	private function testUnblockPlayer() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->unblockPlayer($player->username);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'UnblockPlayer for PT');
	}

	private function testLogin() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->login($player->username, $player->password);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'Login for PT');
	}

	private function testLogout() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->logout($player->username, $player->password);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'Logout for PT');
	}

	private function testDepositToGame() {
		$player = $this->getFirstPlayer();
		$amount = '1';
		$rlt = $this->api->depositToGame($player->username, $amount);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testDepositToGame for PT');
	}

	private function testWithdrawFromGame() {
		$player = $this->getFirstPlayer();
		$amount = '4';
		$rlt = $this->api->withdrawFromGame($player->username, $amount);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testWithdrawFromGame for PT');
	}

	private function testUpdateInfo() {
		$player = $this->getFirstPlayer();
		$info = array('playerName' => $player->username,
			'firstname' => 'myfname',
			'lastname' => 'mylname');
		$rlt = $this->api->updatePlayerInfo($player->username, $info);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testUpdateInfo for PT');
	}

	private function testQueryForwardGame() {
		$player = $this->getFirstPlayer();
		$rlt = $this->api->queryForwardGame($player->username, null);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testQueryForwardGame for PT');
	}

	private function testQueryGameRecords() {
		$player = $this->getFirstPlayer();
		$dateFrom = new DateTime('2015-07-17');
		$dateTo = new DateTime('2015-07-20');
		$balResult = $this->api->queryGameRecords($dateFrom, $dateTo, $player->username);
		log_message("error", var_export($balResult, true));
		$this->test($balResult['success'], true, 'Test Game Records Results');
		$this->test(is_array($balResult['gameRecords']), true, 'Test Game Records');
	}

	private function testQueryTransaction() {
		$transactionId = 'T288496722652';
		$rlt = $this->api->queryTransaction($transactionId, null);
		log_message("error", var_export($rlt, true));
		$this->test($rlt['success'], true, 'testQueryTransaction for PT');
		$this->test(is_array($rlt['transactionInfo']), true, 'testQueryTransaction for PT');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-04-01');
		$dateTimeTo = new DateTime('2015-04-30');
		$player = $this->getFirstPlayer();

		$this->api->syncInfo[$token] = array("playerName" => $player->username, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$this->api->syncOriginalGameLogs($token);
	}

	private function testTotalBettingAmount() {

		$dateTimeFrom = new DateTime('2015-04-01');
		$dateTimeTo = new DateTime('2015-04-30');
		$player = $this->getFirstPlayer();
		$rlt = $this->api->totalBettingAmount($player->username, $dateTimeFrom, $dateTimeTo);
		log_message("error", var_export($rlt, true));
		$this->test($rlt['success'], true, 'testTotalBettingAmount for PT');
		$this->test($rlt['bettingAmount'], true, 'testTotalBettingAmount for PT');
	}

	private function testQueryPlayerStatsPerHour() {

		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-04-01');
		$dateTimeTo = new DateTime('2015-04-30');
		$player = $this->getFirstPlayer();

		$this->api->syncInfo[$token] = array("playerName" => $player->username, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$this->api->syncOriginalGameLogs($token);
	}

	private function testSyncGameRecords() {

		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-04-01 00:00:00');
		$dateTimeTo = new DateTime('2015-04-30 23:59:59');
		// $player = $this->getFirstPlayer();

		// $this->api->syncInfo[$token] = array("playerName" => null, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$this->api->syncGameRecords($dateTimeFrom, $dateTimeTo);
	}

	private function testIsPlayerExist() {
		$rlt = $this->api->isPlayerExist("CESHI");
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testIsPlayerExist for PT');
		$this->test($rlt['exists'], true, 'CESHI is exist');
		//try a palyer not exist
		$rlt = $this->api->isPlayerExist("CEeeerSHI");
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testIsPlayerExist for PT');
		$this->test($rlt['exists'], false, 'CEeeerSHI not exist');

	}

	private function testCheckLoginToken() {
		$rlt = $this->api->checkLoginToken("test002", 'wrongtoken');
		$this->utils->debug_log('rlt', $rlt);
		$this->test($rlt['success'], true, 'testCheckLoginToken for PT');
		// $this->test($rlt['exists'], false, 'test002 not exist');

	}
}
