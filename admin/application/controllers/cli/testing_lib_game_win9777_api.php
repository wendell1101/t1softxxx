<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_win9777_api extends BaseTesting {

	private $platformCode = WIN9777_API;
	private $platformName = 'WIN9777';
	private $api = null;

	public function init() {
		// $this->load->model('game_provider_auth');
		// $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
		// $this->test($this->game_platform_manager == null, false, 'init game platform manager');
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		// $this->test($api == null, false, 'init api');
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		$this->testCreatePlayer();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();

		// $this->testSyncGameLogs();
		//$this->testSyncMergeToGameLogs();
		// $this->testPrefixUsername();
		//$this->testCreateAndLogin();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function getEasternStandardTime() {
		$now = new DateTime();
		return $now->modify('-12 hours')->format('Ymd');
	}

	private function testBatchQueryPlayerBalance() {
		$rlt = $this->api->batchQueryPlayerBalance(null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}

	private function testGameLogs() {
		$rowId = '16103653263';
		$rlt = $this->api->convertGameRecordsToFile($rowId);

	}

	public function testPrefixUsername() {
		$api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($api->convertUsernameToGame('testuser'), 'ogtestuser', 'convert username');
	}

	private function testCreatePlayer() {
		$playerName = 'p1win9777';
		//$this->utils->debug_log("create player", $username);
		$password = '12344321';
		$playerId = 127;
		$rlt = $this->api->createPlayer($playerName, $playerId, $password, null);
		$this->utils->debug_log($rlt);
	}

	public function testQueryPlayerBalance() {
		$playerName = 'p1win9777';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		// $this->test($rlt['balance'], 14, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testDeposit() {
		$playerName = 'p1win9777';
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to WIN9777');
	}

	private function testWithdraw() {
		$playerName = 'p1win9777';
		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to WIN9777');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-11-11 00:00:00');
		$dateTimeTo = new DateTime('2015-11-12 23:59:59');

		$playerName = null; // 'p1win9777';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to WIN9777');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-11-11 00:00:00');
		$dateTimeTo = new DateTime('2015-11-29 23:59:59');

		$playerName = 'swtest008';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to WIN9777');
	}
}