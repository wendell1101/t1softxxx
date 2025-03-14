<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_lapis_api extends BaseTesting {

	private $platformCode = LAPIS_API;
	private $platformName = 'LAPIS';
	private $api = null;

	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		// $this->testGetToken();
		//$this->testCreatePlayer();
		//$this->testPlayerLogin();
		//$this->testDeposit();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		// $this->testQueryForwardGame();

		$this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
		//$this->testCreateAndLogin();

		// $this->testRaw2();
	}

	private function testRaw() {
		$data = array("j_username" => "oceanteck", "j_password" => "qwer1234");
		$params = json_encode($data);
		$ch = curl_init('https://ag.adminserv88.com/lps/j_spring_security_check');

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Type: application/json',
			'Content-Length: ' . strlen($params),
			'X-Requested-With: X-Api-Client',
			'X-Api-Call: X-Api-Client',
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$result = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error = curl_error($ch);
		var_dump($result, $errCode, $error);
	}

	private function testRaw2() {
		$ch = curl_init('https://ag.adminserv88.com/lps/j_spring_security_check');
		$data = array("j_username" => "oceanteck", "j_password" => "qwer1234");
		// $params = json_encode($data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Type: application/json',
			'X-Requested-With: X-Api-Client',
			'X-Api-Call: X-Api-Client',
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$this->utils->debug_log('res:' . $result . 'errno:' . $errno . 'error:' . $error);
		var_dump($result, $errno, $error);
		//close connection
		curl_close($ch);
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testGetToken() {
		$rlt = $this->api->getToken();
		var_dump($rlt);exit();
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testGetToken for ' . $this->platformName);
	}

	private function testCreateAndLogin() {
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

		$this->utils->debug_log('=====login: ' . $username . '======================================================');
		$rlt = $this->api->login($username, $password);
		// $this->utils->debug_log('after createPlayer', $rlt);
		$this->test($rlt['success'], true, $username . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $username . '======================================================', $rlt);

	}

	private function testBatchQueryPlayerBalance() {
		$rlt = $this->api->batchQueryPlayerBalance(null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
	}

	private function testCreatePlayer() {
		$playerName = "testshu";
		$password = "password";
		$playerId = 111;
		$rlt = $this->api->createPlayer($playerName, $playerId, $password, null);
		$this->test($rlt['success'], true, ' testCreatePlayer for ' . $this->platformName);
		$this->utils->debug_log($rlt);
	}

	private function testPlayerLogin() {
		$playerName = 'testabc123';
		$password = 'testabc123';
		$rlt = $this->api->login($playerName, $password);
		$this->test($rlt['success'], true, $playerName . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $playerName . '======================================================', $rlt);
	}

	public function testQueryPlayerBalance() {
		$playerName = 'wbttestLAPIS1';

		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 14, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testDeposit() {
		$playerName = 'testshu';
		$depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to LAPIS');
		$this->test($rlt['currentplayerbalance'], 15, 'Current Balance after deposit');
	}

	private function testWithdraw() {
		$playerName = 'test123456789012l23test';
		$withdrawAmount = 100;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to LAPIS');
		$this->test($rlt['currentplayerbalance'], 10, 'Current Balance after withdrawal');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		// $dateTimeFrom = new DateTime('2016-11-19 18:00:00');
		// $dateTimeTo = new DateTime('2016-11-19 19:59:59');
		$dateTimeFrom = new DateTime('2017-02-06 13:20:00');
		$dateTimeTo = new DateTime('2017-02-06 14:20:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to LAPIS');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-02-06 13:20:00');
		$dateTimeTo = new DateTime('2017-02-06 14:20:00');
		$playerName = 'swtest008';

		$this->api->syncInfo[$token] = array( "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to LAPIS');
	}

	private function testQueryForwardGame() {
		$playerName = 'test123456789012l23test';
		$param = array(
			"game_code" => "123",
			"game_mode" => "true",
			"is_mobile_flag" => "false",
		);
		$rlt = $this->api->queryForwardGame($playerName, $param);
		var_dump($rlt);exit();
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to LAPIS');
	}
}