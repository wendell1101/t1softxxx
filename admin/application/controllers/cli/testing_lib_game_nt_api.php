<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_nt_api extends BaseTesting {

	private $platformCode = NT_API;
	private $platformName = 'NT';
	private $api = null;

	public function init() {
		// $this->load->model('game_provider_auth');
		// $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
		// $this->test($this->game_platform_manager == null, false, 'init game platform manager');
		// $api = $this->game_platform_manager->initApi($this->platformCode);
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		// $this->test($api == null, false, 'init api');
		//var_dump($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		// $this->testQueryPlayerBalance();
		// $this->testCreatePlayer();
		// $this->testQueryPlayerInfo();
		// $this->testDeposit();
		// $this->testWithdraw();
		//$this->testIsPlayerExist();
		// $this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testConvertGameLogsToSubWallet() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-10-14 09:00:00');
		$dateTimeTo = new DateTime('2015-10-15 00:00:00');

		$playerName = null; //'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);

		$this->api->convertGameLogsToSubWallet($token);

	}

	private function testBatchQueryPlayerBalance() {
		$rlt = $this->api->batchQueryPlayerBalance(null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}

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
			// $this->utils->debug_log('after isPlayerExist', $rlt);
			$this->test($rlt['success'], true, $username . ' isPlayerExist success for ' . $this->platformName);
			$this->test($rlt['exists'], true, $username . ' isPlayerExist exists for ' . $this->platformName);
			$this->utils->debug_log('=====result of isPlayerExist: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query player info
			$this->utils->debug_log('=====queryPlayerInfo: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			//must = game username
			//ignore lower/upper
			$this->test(strtolower($rlt['playerInfo']['playerName']), strtolower($gameUsername), $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			// $this->utils->debug_log('succ', $succ);
			$this->utils->debug_log('=====result of queryPlayerInfo: ' . $username . '======================================================', $rlt);
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

			//deposit
			$this->utils->debug_log('=====depositToGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
			$rlt = $this->api->depositToGame($username, $depositAmount);
			$this->test($rlt['success'], true, 'depositToGame for ' . $this->platformName);
			$this->test($rlt['currentplayerbalance'], $depositAmount, 'depositToGame: ' . $rlt['currentplayerbalance']);
			$this->utils->debug_log('=====result of depositToGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance after deposit for ' . $this->platformName);
			$this->test($rlt['balance'], $depositAmount, 'queryPlayerBalance balance value after deposit for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//withdrawal
			$this->utils->debug_log('=====withdrawFromGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
			$rlt = $this->api->withdrawFromGame($username, $depositAmount);
			$this->test($rlt['success'], true, 'withdrawFromGame for ' . $this->platformName);
			$this->test($rlt['currentplayerbalance'], 0, 'withdrawFromGame: ' . $rlt['currentplayerbalance']);
			$this->utils->debug_log('=====result of withdrawFromGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance after withdrawal for ' . $this->platformName);
			$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value after withdrawal for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//block player
			$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer for ' . $this->platformName);
			$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			$this->test($rlt['playerInfo']['blocked'], true, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerInfo after blockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//unblock player
			$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->unblockPlayer($username);
			$this->test($rlt['success'], true, 'unblockPlayer for ' . $this->platformName);
			$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			$this->test($rlt['playerInfo']['blocked'], false, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerInfo after unblockPlayer: ' . $username . '======================================================', $rlt);

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
		//login
		if ($rlt['success']) {
			//change password
			$this->utils->debug_log('=====login: ' . $username . ' ======================================================');
			$rlt = $this->api->login($username, $password);
			$this->test($rlt['success'], true, 'login for ' . $this->platformName);
			// $this->test($rlt['password'], $newPassword, 'changePassword to ' . $newPassword . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of login: ' . $username . '======================================================', $rlt);

		}
		//logout
		if ($rlt['success']) {
			//change password
			$this->utils->debug_log('=====logout: ' . $username . ' ======================================================');
			$rlt = $this->api->logout($username, $password);
			$this->test($rlt['success'], true, 'logout for ' . $this->platformName);
			// $this->test($rlt['password'], $newPassword, 'changePassword to ' . $newPassword . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of logout: ' . $username . '======================================================', $rlt);

		}

		if ($rlt['success']) {

			//block for save
			$this->utils->debug_log('=====blockPlayer for save: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer save for ' . $this->platformName);
			$this->utils->debug_log('=====result of blockPlayer for save: ' . $username . '======================================================', $rlt);

		}
	}

	private function testBlock() {
		$username = 'testbTOzbGg7';
		$rlt['success'] = true;
		$gameUsername = $this->api->getGameUsernameByPlayerUsername($username);

		if ($rlt['success']) {

			//block player
			$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer for ' . $this->platformName);
			$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			$this->test($rlt['playerInfo']['blocked'], true, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerInfo after blockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//unblock player
			$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->unblockPlayer($username);
			$this->test($rlt['success'], true, 'unblockPlayer for ' . $this->platformName);
			$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			$this->test($rlt['playerInfo']['blocked'], false, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerInfo after unblockPlayer: ' . $username . '======================================================', $rlt);

		}

	}

	private function testQueryPlayerBalance() {
		$playerName = 'actfmg1';

		$rlt = $this->game_platform_manager->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);

		// list($success, $balance) = $this->game_platform_manager->queryPlayerBalance($playerName);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 200, 'balance amount for ' . $playerName);
	}

	private function testSyncGameLogs() {
		$api = $this->game_platform_manager->initApi($this->platformCode);

		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-10-14 09:00:00');
		$dateTimeTo = new DateTime('2015-10-15 00:00:00');

		$playerName = null; //'actfmg1';

		$api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to NT');
	}

	private function testSyncMergeToGameLogs() {
		// $api = $this->game_platform_manager->initApi($this->platformCode);

		$token = 'ogseshib';
		$dateTimeFrom = new DateTime('2015-01-14 09:00:00');
		$dateTimeTo = new DateTime('2016-10-15 00:00:00');

		$playerName = 'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to NT');
	}
}

/// END OF FILE///////