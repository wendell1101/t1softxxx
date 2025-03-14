<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_gameplay_api extends BaseTesting {

	private $platformCode = GAMEPLAY_API;
	private $platformName = 'GAMEPLAY';
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
		// $this->testCreatePlayer();
		// $this->testQueryPlayerBalance();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testPlayGame();
		$this->testSyncGameLogs();
		// $this->testSyncMergeToGameLogs();
		// $this->testQueryForwardGame();
		// $this->testCallback();
	}

	private function testBase() {
		$username = 'test1sgame' . random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);

		$depositAmount = 10;
		$rlt = $this->api->depositToGame($username, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to 1sGames');

		$rlt = $this->api->queryPlayerBalance($username);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $username);
		$this->test($rlt['balance'], 30, 'balance amount ' . $rlt['balance'] . ' for ' . $username);

		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($username, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to 1sGames');

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
		$username = 'testgameplay' . random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);
	}

	private function testDeposit() {
		$playerName = 'testgameplay58921435';
		$depositAmount = 10;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to GamePlay');
	}

	private function testWithdraw() {
		$playerName = 'testgameplay58921435';
		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to GamePlay');
	}

	public function testQueryPlayerBalance() {
		$playerName = 'testgameplay58921435';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 0, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-12-14 16:00:00');
		$dateTimeTo = new DateTime('2017-12-14 17:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to 1sGames');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-02-25 00:00:00');
		$dateTimeTo = new DateTime('2017-03-03 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to 1sGames');
	}

	private function testQueryForwardGame() {
		$playerName = 'testmel1';
		$password = 'password';
		$gameType = '24';
		$gameName = 'table';
		$extra = array('password' => $password, 'gameType' => $gameType, 'gameName' => $gameName);
		$rlt = $this->api->queryForwardGame($playerName, $extra);

		$this->test($rlt['success'], true, 'Test Play Games to 1sGames: ' . $rlt);
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

	public function testCallback(){

		$token = 'db8e9ce7e57510d159b2af45857f70ce';
		$method = 'validatemember';

		$rlt = $this->api->callback($method, $token);
		echo '<pre>';
		print_r($rlt );
		exit();
	}
}