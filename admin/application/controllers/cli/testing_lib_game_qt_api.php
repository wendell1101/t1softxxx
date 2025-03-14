<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_qt_api extends BaseTesting {

	private $platformCode = QT_API;
	private $platformName = 'QT';
	private $playerName = 'testtest';
	private $password = 'password';

	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

		$this->init();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		// $this->testQueryForwardGame();
		// $this->testSyncMergeToGameLogs();
		// $this->testSyncGameLogs();
		// $this->testBatchQueryPlayerBalance();
		$this->testGetGamelist();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	public function testQueryPlayerBalance() {
		$playerName = "test3tbet";
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log('queryPlayerBalance', $rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);

	}

	private function testDeposit() {
		$playerName = $this->playerName;
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to QT', ' Reference Id: ', @$rlt['referenceId']);
	}

	private function testWithdraw() {
		$playerName = $this->existPlayer;
		$withdrawAmount = 100;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdrawal to QT');

	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-10-26 00:30:00');
		$dateTimeTo = new DateTime('2017-10-26 23:59:59');
		$playerName = 'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
        print_r($rlt);
		// $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to QT');
		//$this->utils->readExcel();
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-06-25 00:00:00');
		$dateTimeTo = new DateTime('2016-06-25 23:59:59');

		$playerName = 'ogtest006';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to QT');
	}

	private function testQueryForwardGame() {
		$playerName = "testzai";
		#$extra['game_code'] = 'OGS-theatreofnight';
		$extra['game_code'] = 'OGS-pandapow'; # HAB-birdofthunder
		$extra['game_mode'] = 'real';
		$extra['is_mobile'] = true;
		$extra['game_type'] = 'HTML5';
		$extra['language'] = 'en';
		$result = $this->api->queryForwardGame($playerName, $extra);
		print_r($result);
		exit;
		$this->utils->debug_log('testQueryForwardGame: ', $rlt);
		$this->test($rlt['success'], true, 'Test Player Launch Game', ' URL: ', @$rlt['url']);
	}

	private function testBatchQueryPlayerBalance() {
		$playerNames = array("1" => "test3tbet");
		$rlt = $this->api->batchQueryPlayerBalance($playerNames);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}

	private function testGetGamelist() {
		$rlt = $this->api->getGameProviderGameList();
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}
}
