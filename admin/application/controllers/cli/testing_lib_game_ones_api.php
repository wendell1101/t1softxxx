<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ones_api extends BaseTesting {

	private $platformCode = ONESGAME_API;
	private $platformName = 'ONESGAME';
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
		// $this->testPlayerLogin();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		$this->testSyncGameLogs();
		// $this->testSyncMergeToGameLogs();
		// $this->testPrefixUsername();
		//$this->testCreateAndLogin();
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
		$username = 'test1sgame' . random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);
	}

	private function testDeposit() {
		$playerName = 'test1sgame26448807';
		$depositAmount = 10;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to 1sGames');
	}

	private function testWithdraw() {
		$playerName = 'test1sgame26448807';
		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to 1sGames');
	}

	public function testQueryPlayerBalance() {
		$playerName = 'test1sgame26448807';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 30, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2018-01-09 00:00:00');
		$dateTimeTo = new DateTime('2018-01-09 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-04-01 00:00:00');
		$dateTimeTo = new DateTime('2016-05-29 23:59:59');

		$playerName = 'wiplixt';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to 1sGames');
	}

	private function testQuesryForwardGame() {
		$playerName = 'test1sgame26448807';
		$password = 'pass123';
		$gameType = '51';
		$gameName = 'Xmen';
		$extra = array('password' => $password, 'gameType' => $gameType, 'gameName' => $gameName);
		$rlt = $this->api->queryForwardGame($playerName, $extra);

		$this->test($rlt['success'], true, 'Test Play Games to 1sGames: ' . $rlt);
	}

	public function testingRaw() {
		$playerName = 'test' . random_string('alnum');
		$password = 'pass123';
		$d = new Datetime();

		$post_data = array(
			"OperatorID" => $this->getConfig('onesgame_operator_id'),
			"OperatorPassword" => $this->getConfig('onesgame_operator_password'),
			"RequestDateTime" => $d->format('Y-m-d H:i:s'),
			"Signature" => md5(
				$this->getConfig('onesgame_operator_id') .
				'GetGameList' .
				$d->format('Y-m-d H:i:s') .
				$this->getConfig('onesgame_operator_secret_key'
				)
			),
		);
		$xml_object = new SimpleXMLElement("<GetGameList></GetGameList>");

		$xmlData = $this->utils->arrayToXml($post_data, $xml_object);

		$onesgame_operator_uat_url = $this->getConfig('onesgame_operator_production_url');
		$onesgame_operator_production_url = $this->getConfig('onesgame_operator_production_url');
		$url = $onesgame_operator_uat_url . "/GameLobbyWs.svc/OPAPI/GetGameList";
		$this->testRaw($xmlData, $url);
	}

	private function testRaw($post_data, $url) {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$this->utils->debug_log('res:' . $result . 'errno:' . $errno . 'error:' . $error);
		//close connection
		curl_close($ch);
	}
}
