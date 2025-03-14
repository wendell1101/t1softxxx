<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_one88_api extends BaseTesting {

	private $platformCode = ONE88_API;
	private $platformName = 'ONE88';
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
		//$this->testRaw();
		//$this->testCreatePlayer();
		//$this->testDeposit();
		//$this->testWithdraw();
		//$this->testQueryPlayerBalance();

		//$this->testAESEncryptionForPHP();
		//$this->testCallback();
		$this->testSyncGameLogs();
		//$this->testSyncMergeToGameLogs();
		// $this->testPrefixUsername();
		//$this->testCreateAndLogin();
		$this->testCallback();
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

		//$username = 'test' . random_string('alnum');
		$username = 'testuser188A';
		$key = $this->config->item('one88_merchant_key');

		$post_data = array(
			"LoginName" => $username,
			"CurrencyCode" => 'RMB',
			"OddsTypeId" => 1,
			"LangCode" => 'ENG',
			"TimeZone" => 'GMT-04:00',
			"MemberStatus" => 501,
		);

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='RegisterMember'></Request>");
		$xmlData = $this->utils->arrayToXml($post_data, $xml_object);
		$encryptedXML = $this->utils->aes128_cbc_encrypt($key, $xmlData, $key);

		$api_url = 'http://in.lsbet8.com/Sportsbook/RegisterMember';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptedXML);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);

		curl_close($ch);

		$decryptedXMLRes = $this->utils->aes128_cbc_decrypt($key, base64_decode($result), $key);
		var_dump($decryptedXMLRes);
	}

	private function testCallback() {
		$url = 'http://admin.og.local/callback/game/11/ValidateMember';

		$xml = "<?xml version='1.0' encoding='utf-8'?>
				<Request Method='Login'>
				<Token>1234567890</Token>
				</Request>";

		$key = $this->config->item('one88_merchant_key');
		$encryptedXML = $this->utils->aes256_cbc_encrypt($key, $xml, $key);
		$decryptedXML = $this->utils->aes256_cbc_decrypt($key, $encryptedXML, $key);
		$this->utils->debug_log('decryptedXML:' . $decryptedXML);

		// $post_data = array(
		// 	"encryptedXML" => $encryptedXML,
		// );

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		// 	' Content-Type: application/x-www-form-urlencoded',
		// ));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptedXML);
		$response = curl_exec($ch);
		curl_close($ch);
		$this->utils->debug_log($response);
		echo $response;
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
		$username = 'actfmg3';
		//$this->utils->debug_log("create player", $username);
		$password = '12344321';
		$playerId = 126;
		$rlt = $this->api->createPlayer($username, $playerId, $password, null);
		$this->test($rlt['success'], true, 'create player: ' . $username);
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
		$playerName = 'actfmg3';

		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		// $this->test($rlt['balance'], 0, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testDeposit() {
		$playerName = 'testuser188A';
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to one88');
		$this->test($rlt['currentplayerbalance'], 700, 'Current Balance after deposit 700');
	}

	private function testWithdraw() {
		$playerName = 'testuser188A';
		$withdrawAmount = 100;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to one88');
		$this->test($rlt['currentplayerbalance'], 600, 'Current Balance after withdrawal 600');
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-11-16 00:00:00');
		$dateTimeTo = new DateTime('2015-11-16 23:59:59');

		$playerName = 'ogtest006';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to one88');
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-10-14 00:00:00');
		$dateTimeTo = new DateTime('2015-12-15 23:59:59');

		$playerName = 'ogtest006';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to one88');
	}
}