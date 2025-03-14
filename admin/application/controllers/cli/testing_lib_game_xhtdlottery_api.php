<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_xhtdlottery_api extends BaseTesting {

	private $platformCode = XHTDLOTTERY_API;
	private $platformName = 'XHTDLOTTERY';
	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		// $this->testCreatePlayer();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		// $this->testLoginPlayer();
		// $this->testQueryForwardGame();
		// $this->testPlayGame();
		// $this->testSyncGameLogs();
		 $this->testSyncMergeToGameLogs();
		// $this->testBase();
	}

	private function testBase() {
		$username = 'xltest' . random_string('numeric',3);
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);

		$depositAmount = 10;
		$rlt = $this->api->depositToGame($username, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to XhtdLottery');

		$rlt = $this->api->queryPlayerBalance($username);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $username);
		$this->test($rlt['balance'], 30, 'balance amount ' . $rlt['balance'] . ' for ' . $username);

		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($username, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to XhtdLottery');

		$rlt = $this->api->queryPlayerBalance($username);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $username);
		$this->test($rlt['balance'], 30, 'balance amount ' . $rlt['balance'] . ' for ' . $username);
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

	public function testPrefixUsername() {
		$api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($api->convertUsernameToGame('testuser'), 'ogtestuser', 'convert username');
	}

	private function testCreatePlayer() {
		$username = 'xltest' . random_string('numeric',3);
		$this->utils->debug_log("create player", $username);
		$password = 'abc123456';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);
	}

	private function testLoginPlayer() {
		$username = 'xltest826';
		$password = 'abc123456';
		$this->utils->debug_log("Login player", $username,$password);
		$rlt = $this->api->login($username, $password);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Login Player: ' . $username);
	}

	private function testDeposit() {
		$playerName = 'xltest826';
		$depositAmount = 10;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to XhtdLottery');
	}

	private function testWithdraw() {
		$playerName = 'xltest826';
		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to XhtdLottery');
	}

	public function testQueryPlayerBalance() {
		$playerName = 'xltest826';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 0, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-04-01 00:00:00');
		$dateTimeTo = new DateTime('2016-04-30 23:59:59');

		$playerName = 'test1sgame26448807';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to XhtdLottery');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-05-00 00:00:00');
		$dateTimeTo = new DateTime('2016-05-20 23:59:59');

		$playerName = 'wiplixt';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to XhtdLottery');
	}

	public function testPlayGame() {
		$this->utils->debug_log('test');
		$token = 'testrtt03';
		echo anchor("http://casino.gpiops.com/?op=GSOFT05&lang=en-us&m=normal&token=" . $token, "live game");
		echo "<br/>";
		echo anchor("http://casino.bet8uat.com/?op=GSOFT05&lang=en-us&m=normal&token=" . $token, "uat game");
		echo "<br/>";
	}

	public function testRaw($post_data = null, $url = null) {
		// $url = "https://club8api.bet8uat.com/op/createuser?merch_id=GSOFT05&merch_pwd=53AAAC24-CFE5-4842-B43E-BFEEA057C675&cust_id=testrtt01&cust_name=testrtt01&currency=CNY";
		$url = "https://club8api.w88.com/op/createuser?merch_id=GSOFT05&merch_pwd=53AAAC24-CFE5-4842-B43E-BFEEA057C675&cust_id=testrtt03&cust_name=testrtt03&currency=RMB";

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		var_dump($result, $errno, $error);
		$this->utils->debug_log('res:' . $result . 'errno:' . $errno . 'error:' . $error);
		//close connection
		curl_close($ch);
	}
}