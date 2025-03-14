<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_bbin_api extends BaseTesting {

	private $platformCode = BBIN_API;
	private $platformName = 'BBIN';
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
	    //$this->testCreatePlayer();
	   // $this->testMobileCreatePlayer();
	   // $this->testChangePassword();
		//$this->testPlayerLogin();

		//$this->testDeposit();
		//$this->testQueryPlayerBalance();
		//$this->testWithdraw();
		$this->testSyncGameLogs();
		//$this->testSyncMergeToGameLogs();
		// $this->testPrefixUsername();
		//$this->testCreateAndLogin();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
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

		// $username = 'ogtest006';
		// $password = 'pass123';

		$this->utils->debug_log('=====login: ' . $username . '======================================================');
		$rlt = $this->api->login($username, $password);
		// $this->utils->debug_log('after createPlayer', $rlt);
		$this->test($rlt['success'], true, $username . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $username . '======================================================', $rlt);

	}

	private function testRaw() {
		$this->bbin_api_url = $this->getConfig('bbin_api_url');
		$this->bbin_mywebsite = $this->getConfig('bbin_mywebsite');
		$this->bbin_create_member = $this->getConfig('bbin_create_member');
		$this->bbin_login_member = $this->getConfig('bbin_login_member');
		$this->bbin_logout_member = $this->getConfig('bbin_logout_member');
		$this->bbin_check_member_balance = $this->getConfig('bbin_check_member_balance');
		$this->bbin_transfer = $this->getConfig('bbin_transfer');
		$this->bbin_getbet = $this->getConfig('bbin_getbet');
		$this->bbin_uppername = $this->getConfig('bbin_uppername');

		// $keyb = $this->bbin_check_member_balance['keyb'];
		// $this->utils->debug_log('keyb', $keyb);

		$playerName = 'ogtest006';
		$now = new DateTime();
		$key = strtolower(random_string('alpha', $this->bbin_check_member_balance['start_key_len']))
		. md5($this->bbin_mywebsite . $playerName . $this->bbin_check_member_balance['keyb'] . $this->getEasternStandardTime())
		. strtolower(random_string('alpha', $this->bbin_check_member_balance['end_key_len']));
		$apiName = 'CheckUsrBalance';

		// $key = strtolower(random_string('alpha', $this->bbin_login_member['start_key_len']))
		// . md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getEasternStandardTime())
		// . strtolower(random_string('alpha', $this->bbin_login_member['end_key_len']));
		// $apiName = 'Login';

		$data = array("website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			"password" => "pass123",
			"key" => $key);

		// $data_string = json_encode($data);
		$params_string = http_build_query($data);

		$url = $this->bbin_api_url . '/app/WebService/JSON/display.php/' . $apiName . '?' . $params_string;

		//$this->utils->debug_log($url, 'website', $this->bbin_mywebsite, 'username', $playerName, 'key', $keyb, 'date', $this->getEasternStandardTime());

		$ch = curl_init($url);
		// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		// 	'Content-Type: application/json',
		// 	'Content-Length: ' . strlen($data_string))
		// );
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		//execute post
		$result = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$this->utils->debug_log('res:' . $result . 'errno:' . $errno . 'error:' . $error);
		//close connection
		curl_close($ch);

		echo $result;

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
		$playerName = 'ogtest007';
		//$this->utils->debug_log("create player", $username);
		$password = '12344321';
		$playerId = 127;
		$rlt = $this->api->createPlayer($playerName, $playerId, $password, null);
		$this->utils->debug_log($rlt);
	}

	private function testMobileCreatePlayer() {
		$playerName = 'ogtest006';
		//$this->utils->debug_log("create player", $username);
		$password = '12344321';
		$playerId = 127;
		$extra =array();
		$extra['isMobile'] = 'true';
		$rlt = $this->api->createPlayer($playerName, $playerId, $password,null, $extra);
		$this->utils->debug_log($rlt);
	}

	#for mobile but can be used in desktop
    private function testChangePassword(){

		$playerName = 'ogtest006';
		$newPassword = 'wapak20';
		$password = null;
	    $rlt = $this->api->changePassword($playerName, $password, $newPassword);
	    $this->utils->debug_log($rlt);
	}



	private function testPlayerLogin() {
		$playerName = 'ogtest006';
		$password = 'pass123';
		$rlt = $this->api->login($playerName, $password);
		$this->test($rlt['success'], true, $playerName . ' login for ' . $this->platformName);
		$this->utils->debug_log('=====result of login: ' . $playerName . '======================================================', $rlt);
	}

	public function testQueryPlayerBalance() {
		$playerName = 'ogtest006';

		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 14, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testDeposit() {
		$playerName = 'ogtest006';
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to BBIN');
		$this->test($rlt['currentplayerbalance'], 15, 'Current Balance after deposit');
	}

	private function testWithdraw() {
		$playerName = 'ogtest006';
		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to BBIN');
		$this->test($rlt['currentplayerbalance'], 10, 'Current Balance after withdrawal');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-12-27 00:00:00');
		$dateTimeTo = new DateTime('2017-12-27 23:59:59');

		$playerName = null; // 'ogtest006';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to BBIN');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-04-01 00:00:00');
		$dateTimeTo = new DateTime('2016-05-29 23:59:59');

		$playerName = 'swtest008';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to BBIN');
	}
}