<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ab_api extends BaseTesting {

	private $platformCode 	= AB_API;
	private $platformName 	= 'AB';
	private $api 			= null;
	private $test_player 	= null;
	private $amount 		= 1;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);

		$username = 'test' . random_string('numeric', 4);
		$this->test_player = $this->getFirstPlayer($username);
	}

	public function testAll() {
		$this->init();

        $this->testQueryAgentHandicap();

        // Test Case API Functions
        // #5 ForwardGame
        // $this->testQueryForwardGame();

        // #6 QueryBetLogQuery
        // $this->testqueryBetLogQuery();

        // #7 BetLogDailyHistories
        // $this->testbetlog_daily_histories();

        // #8 LogoutGame
        // $this->testLogout();

        // #9 BetLogDailyModifiedHistories
        // $this->testbetlog_daily_modified_histories();

        // #11 ModifyClient
        // $this->testmodify_client();

        // #12 SetupClientPassword
        // $this->testsetup_client_password();

        // #14 BetLogPieceOfHistoriesIn30Days
        // $this->testbetlog_pieceof_histories_in30days();

        // #15 MainStateSetting
        // $this->testmaintain_state_setting();

        // #16 Query or Reset the history Win/Loss
        // $this->testclient_history_surplus();

        // Test Scenario
        // #1 Transfer Credit from Agent to Client
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testwithdrawFromGame();

        // Step 4:
        // $this->testquery_transfer_state();

        // #2 Transfer Credit from Agent to Client (Zero Amount)
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testwithdrawFromGame();

        // Step 4:
        // $this->testquery_transfer_state();

        // #2 Transfer Credit from Agent to Client (Negative Value)
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testwithdrawFromGame();

        // Step 4:
        // $this->testquery_transfer_state();

        // #3 Transfer Credit from Agent to Client (Lack of Money)
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testwithdrawFromGame();

        // Step 4:
        // $this->testquery_transfer_state();
        //
        // #5 Transfer Credit from Client to Agent
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testdepositToGame();

        // Step 4:
        // $this->testquery_transfer_state();

        // #6 Transfer Credit from Client to Agent (Zero Amount)
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testdepositToGame();

        // Step 4:
        // $this->testquery_transfer_state();

        // #7 Transfer Credit from Client to Agent (Negative Value)
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testdepositToGame();

        // Step 4:
        // $this->testquery_transfer_state();

        // #8 Transfer Credit from Client to Agent (Lack of Money)
        // Step 1:
        // $this->testCreatePlayer();

        // Step 2:
        // $this->testQueryPlayerBalance();

        // Step 3:
        // $this->testdepositToGame();

        // Step 4:
        // $this->testquery_transfer_state();
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
		$rlt = $this->api->changePassword($this->test_player->username, $this->test_player->password, '369369');
		return $rlt;
	}

	private function testLogout() {
		$rlt = $this->api->logout($this->test_player->username);

		return $rlt;
	}

	private function testCreatePlayer() {
		$rlt = $this->api->createPlayer($this->test_player->username, $this->test_player->playerId, $this->test_player->password);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testQueryPlayerInfo() {
		$rlt = $this->api->queryPlayerInfo($this->test_player->username);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testLogin() {
		$rlt = $this->api->login($this->test_player->username, $this->test_player->password);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testQueryPlayerBalance() {
		$rlt = $this->api->queryPlayerBalance($this->test_player->username);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testQueryForwardGame() {
		$rlt = $this->api->queryForwardGame($this->test_player->username);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testDeposit() {
		$rlt = $this->api->depositToGame($this->test_player->username, $this->amount);

		$this->test($rlt['success'], true, 'Test Player Deposit to AB');
		return $rlt;
	}

	private function testWithdraw() {
		$rlt = $this->api->withdrawFromGame($this->test_player->username, $this->amount);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testIsPlayerExist() {
		$rlt = $this->api->isPlayerExist($this->test_player->username);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testSyncOriginalGameLogs() {
		// $token 			= 'abc123';
		// $dateTimeFrom 	= new DateTime(date('Y-m-d', strtotime('2016-04-03')));
		// $dateTimeTo 	= new DateTime(date('Y-m-d', strtotime('2016-04-04')));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

	private function testSyncMergeToGameLogs() {
		// $token 			= 'abc123';
		// $dateTimeFrom 	= new DateTime(date('Y-m-d', strtotime('2016-04-03')));
		// $dateTimeTo 	= new DateTime(date('Y-m-d', strtotime('2016-04-04')));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);

		$this->test($rlt['success'], true, 'Test Player Withdraw to AB');
		return $rlt;
	}

    private function testqueryBetLogQuery() {
        // $token = "abc123d";
        // $username = "testmac";

        $dateTimeFrom   = new DateTime(date('Y-m-d', strtotime('2017-06-23')));
        $dateTimeTo     = new DateTime(date('Y-m-d', strtotime('2017-06-27')));

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->queryBetLogQuery($token,$username);
    }

    private function testbetlog_daily_histories() {
        // $dateTimeFrom = new DateTime('2017-06-09 00:00:00');
        // $dateTimeTo = new DateTime('2017-06-10 00:00:00');
        // $token = 'abc123d';

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->betlog_daily_histories($token);
    }

    private function testbetlog_daily_modified_histories() {
        // $dateTimeFrom = new DateTime('2017-06-08 00:00:00');
        // $dateTimeTo = new DateTime('2017-06-09 00:00:00');
        // $token = 'abc123d';

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->betlog_daily_modified_histories($token);
    }

    private function testmodify_client() {
        // $username = 'testmac';
        // $vip = 12;
        // $handi = 11;

        $rlt = $this->api->modify_client($username,$vip,$handi);
    }

    private function testsetup_client_password() {
        // $username = 'testmac';

        $rlt = $this->api->setup_client_password($username);
    }

    private function testbetlog_pieceof_histories_in30days() {
        // $dateTimeFrom = new DateTime('2017-06-24 00:00:00');
        // $dateTimeTo = new DateTime('2017-06-24 00:00:00');
        // $token = 'abc123d';

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->betlog_pieceof_histories_in30days($token);
    }

    private function testmaintain_state_setting() {
        // $state = "0";

        $rlt = $this->api->maintain_state_setting();
    }

    private function testclient_history_surplus() {
        // $username = 'testmac';
        // $operation_type = '0';

        $rlt = $this->api->client_history_surplus($username,$operation_type);
    }

    private function testwithdrawFromGame() {
        // $username = 'testmac';
        // $withdrawAmount = '250';

        $rlt = $this->api->withdrawFromGame($username, $withdrawAmount);
    }

    private function testdepositToGame() {
        // $username = 'testmac';
        // $depositAmount = '250';

        $rlt = $this->api->depositToGame($username, $depositAmount);
    }

    private function testquery_transfer_state() {
        // $sn = '62213090949635143043';

        $rlt = $this->api->query_transfer_state($sn);
    }
}