<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_impt_api extends BaseTesting {

	private $platformCode = IMPT_API;
	private $platformName = 'IMPT';
	private $api = null;
	private $test_player = null;
	private $amount = 20.00;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$username = 'test' . random_string('numeric', 10);
		// $username = 'test2235180991';
		$this->test_player = $this->getFirstPlayer($username);
	}

	public function testAll() {
		echo "<pre>";
		$this->init();

		$result = $this->testCreatePlayer();
		var_dump(['testCreatePlayer' => $result]);

		$result = $this->testIsPlayerExist();
		var_dump(['testIsPlayerExist' => $result]);

		$result = $this->testChangePassword();
		var_dump(['testChangePassword' => $result]);

		$result = $this->testLogin();
		var_dump(['testLogin' => $result]);

		$result = $this->testCheckLoginToken();
		var_dump(['testCheckLoginToken' => $result]);

		$result = $this->testQueryPlayerBalance();
		var_dump(['testQueryPlayerBalance' => $result]);

		$result = $this->testDeposit();
		var_dump(['testDeposit' => $result]);

		$result = $this->testQueryPlayerBalance();
		var_dump(['testQueryPlayerBalance' => $result]);

		$result = $this->testWithdraw();
		var_dump(['testWithdraw' => $result]);

		$result = $this->testQueryPlayerBalance();
		var_dump(['testQueryPlayerBalance' => $result]);

		$result = $this->testQueryForwardGame();
		var_dump(['testQueryForwardGame' => $result]);

		$result = $this->testLogout();
		var_dump(['testLogout' => $result]);

		$result = $this->testSyncOriginalGameLogs();
		var_dump(['testSyncOriginalGameLogs' => $result]);

		$result = $this->testSyncMergeToGameLogs();
		var_dump(['testSyncMergeToGameLogs' => $result]);

		echo "</pre>";
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testBase() {
		$this->init();
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

	private function testQueryAgentHandicap() {
		$rlt = $this->api->queryAgentHandicap();
		return $rlt;
	}

	private function testChangePassword() {
		$rlt = $this->api->changePassword($this->test_player->username, $this->test_player->password, '123123');
		return $rlt;
	}

	private function testLogout() {
		$rlt = $this->api->logout($this->test_player->username);
		return $rlt;
	}

	private function testCreatePlayer() {
		$rlt = $this->api->createPlayer($this->test_player->username, $this->test_player->playerId, $this->test_player->password);
		$this->utils->debug_log('createPlayer', $rlt);
		$this->test($rlt['success'], true, 'Test createPlayer to IMPT');
		return $rlt;
	}

	private function testQueryPlayerInfo() {
		$rlt = $this->api->queryPlayerInfo($this->test_player->username);
		$this->utils->debug_log('queryPlayerInfo', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testLogin() {
		$rlt = $this->api->login($this->test_player->username, $this->test_player->password);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testQueryPlayerBalance() {
		$rlt = $this->api->queryPlayerBalance($this->test_player->username);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testQueryForwardGame() {
		$rlt = $this->api->queryForwardGame($this->test_player->username, array(
			'game_id' => 191,
			'mode' => 'free',
		));
		$this->utils->debug_log('queryForwardGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testDeposit() {
		$rlt = $this->api->depositToGame($this->test_player->username, $this->amount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to IMPT');
		return $rlt;
	}

	private function testWithdraw() {
		$rlt = $this->api->withdrawFromGame($this->test_player->username, $this->amount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testIsPlayerExist() {
		$rlt = $this->api->isPlayerExist($this->test_player->username);
		$this->utils->debug_log('isPlayerExist', $rlt);
		$this->test($rlt['success'], true, 'Test isPlayerExist');
		return $rlt;
	}

	private function testCheckLoginToken() {
		$rlt = $this->api->checkLoginToken($this->test_player->username, '123123');
		$this->utils->debug_log('checkLoginToken', $rlt);
		$this->test($rlt['success'], true, 'Test checkLoginToken');
		return $rlt;
	}

	private function testSyncOriginalGameLogs() {

		$token = 'IMPT123';
		$dateTimeFrom = new DateTime(date('Y-m-d', strtotime('2016-04-20')));
		$dateTimeTo = new DateTime(date('Y-m-d', strtotime('2016-04-20')));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->utils->debug_log('syncOriginalGameLogs', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testSyncMergeToGameLogs() {

		$token = 'IMPT123';
		$dateTimeFrom = new DateTime(date('Y-m-d H:i:s', strtotime('2016-04-20 00:00:00')));
		$dateTimeTo = new DateTime(date('Y-m-d H:i:s', strtotime('2016-04-20 23:59:59')));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->utils->debug_log('syncMergeToGameLogs', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IMPT');
		return $rlt;
	}

	private function testMovePlayer() {
		$this->init();
		//create player
		$username = 'test002';
		$password = '12344321';

		$depositAmount = 1.2;
		$this->load->model('player_model');
		$player = $this->player_model->getPlayerByUsername($username);

		// $player = $this->getFirstPlayer($username);
		$this->utils->debug_log("create player", $username, 'password', $password, 'amount', $depositAmount);

		$rlt = $this->api->isPlayerExist($username);
		// $this->utils->debug_log('after isPlayerExist', $rlt);
		$this->utils->debug_log("isPlayerExist", $username, $rlt);

		$this->utils->debug_log('=====createPlayer: ' . $username . '======================================================');
		$rlt = $this->api->createPlayer($username, $player->playerId, $password);

		$this->test($rlt['success'], true, $username . ' createPlayer success for ' . $this->platformName);

		if ($rlt['success']) {

			$this->utils->debug_log('=====isPlayerExist: ' . $username . ' ======================================================');
			$rlt = $this->api->isPlayerExist($username);
			// $this->utils->debug_log('after isPlayerExist', $rlt);
			$this->test($rlt['success'], true, $username . ' isPlayerExist success for ' . $this->platformName);
			$this->test($rlt['exists'], true, $username . ' isPlayerExist exists for ' . $this->platformName);
			$this->utils->debug_log('=====result of isPlayerExist: ' . $username . '======================================================', $rlt);

		}
		return $rlt;
	}

}