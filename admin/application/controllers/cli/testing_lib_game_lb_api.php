<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_lb_api extends BaseTesting {

	private $platformCode = LB_API;
	private $platformName = 'LB';
	private $api = null;
	private $amount = 1;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$username = 'test' . random_string('numeric', 4);
		$this->test_player = $this->getFirstPlayer($username);
	}


	public function testAll() {
		$this->init();
		//$this->testingRaw();
		// $this->testCreatePlayer();
		// $this->testQueryPlayerBalance();
		// $this->testDeposit();
		// $this->testQueryPlayerBalance();
		// $this->testWithdraw();
		// $this->testQueryPlayerBalance();
		// $this->testPlayerLogin();
		//$this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
		// $this->testPrefixUsername();
		//$this->testCreateAndLogin();
		//$this->testCallback();
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

	private function testCallback() {
		$this->api->callback('ValidateMember', 'secret_key=973AA2F9AC&operator_id=F021E04C-BBC8-Demo-F8C5-E3C4C792445C&site_code=trylsbet8&product_code=keno&session_token=5671045177fac');
	}

	private function testingRaw() {
		$post_data = array(
			"secret_key" => $this->getConfig('lb_secret_key'),
			"operator_id" => $this->getConfig('lb_operator_id'),
			"site_code" => $this->getConfig('lb_site_code'),
			"product_code" => $this->getConfig('lb_product_code'),
			"start_time" => '2015-12-01 00:00:00',
			"end_time" => '2015-12-01 23:59:59',
			"status" => 'all',
		);
		$url = $this->getConfig('lb_api_domain') . '/keno_game_transaction_detail.ashx';
		$this->testRaw($post_data, $url);

		// $username = 'test' . random_string('alnum');
		// $post_data = array(
		// 	"secret_key" => $this->getConfig('lb_secret_key'),
		// 	"operator_id" => $this->getConfig('lb_operator_id'),
		// 	"site_code" => $this->getConfig('lb_site_code'),
		// 	"product_code" => $this->getConfig('lb_product_code'),
		// 	"session_token" => random_string('alpha'),
		// 	"member_id" => $username,
		// 	"member_name" => $username,
		// 	"language" => 'zh_cn',
		// 	"currency" => 'rmb',
		// 	"balance" => 1000,
		// 	"min_transfer" => 100,
		// 	"max_transfer" => 100000,
		// 	"member_type" => 'Credit',
		// );
		// $url = $this->getConfig('lb_api_domain') . '/keno_create_member.ashx';
		// $this->testRaw($post_data, $url);
	}

	private function testRaw($post_data, $url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

		$response = curl_exec($ch);
		curl_close($ch);
		$this->utils->debug_log('(Testing) RESPONSE:', $response);
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
		$rlt = $this->api->createPlayer($this->test_player->username, $this->test_player->playerId, $this->test_player->password);
		var_dump('createPlayer', $rlt);
		return $rlt;
	}

	private function testQueryPlayerBalance() {
		$rlt = $this->api->queryPlayerBalance($this->test_player->username);
		var_dump('queryPlayerBalance', $rlt);
		return $rlt;
	}

	private function testDeposit() {
		$rlt = $this->api->depositToGame($this->test_player->username, $this->amount);
		var_dump('depositToGame', $rlt);
		return $rlt;
	}

	private function testWithdraw() {
		$rlt = $this->api->withdrawFromGame($this->test_player->username, $this->amount);
		var_dump('withdrawFromGame', $rlt);
		return $rlt;
	}

	private function testPlayerLogin() {
		$rlt = $this->api->queryForwardGame($this->test_player->username, array());
		var_dump('queryForwardGame', $rlt);
		return $rlt;
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-01-05 00:00:00');
		$dateTimeTo = new DateTime('2017-01-05 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		//$rlt = $this->api->syncMergeToGameLogs($token);
		var_dump($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-01-05 00:00:00');
		$dateTimeTo = new DateTime('2017-01-05 23:59:59');
		$playerName = 'ogtest006';

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to LB');
	}

	private function testGameLogsResult() {
		$xml = <<<EOD
<?xml version="1.0" ?><game_transaction_detail><status_code>00</status_code><status_text>OK</status_text><operator_id>F021E04C-BBC8-785D-F8C5-E3C4C792445C</operator_id><start_time>2016-03-26 23:05:30</start_time><end_time>2016-03-27 00:10:30</end_time><bet><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181562999" bet_no="06116032623454060577" match_no="1486661" match_area="Korea" match_id="5634540"  bet_type="B/S"  bet_content="Small @ 1.96 # "  bet_currency="RMB" bet_money="8.00" bet_odds="1.96" bet_winning="15.68"  bet_win="win" bet_status="settled" bet_time="2016-03-26 23:45:39" trans_time="2016-03-26 23:47:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181559859" bet_no="06116032623370633500" match_no="1977950" match_area="Canada" match_id="5634519"  bet_type="B/S"  bet_content="Big @ 1.96 # "  bet_currency="RMB" bet_money="30.00" bet_odds="1.96" bet_winning="58.80"  bet_win="win" bet_status="settled" bet_time="2016-03-26 23:37:05" trans_time="2016-03-26 23:38:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181559075" bet_no="06116032623352226776" match_no="1486654" match_area="Korea" match_id="5634522"  bet_type="B/S"  bet_content="Small @ 1.96 # "  bet_currency="RMB" bet_money="20.00" bet_odds="1.96" bet_winning="0"  bet_win="lost" bet_status="settled" bet_time="2016-03-26 23:35:21" trans_time="2016-03-26 23:36:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181558623" bet_no="06116032623340088287" match_no="1486653" match_area="Korea" match_id="5634520"  bet_type="B/S"  bet_content="Big @ 1.96 # "  bet_currency="RMB" bet_money="20.00" bet_odds="1.96" bet_winning="0"  bet_win="lost" bet_status="settled" bet_time="2016-03-26 23:33:59" trans_time="2016-03-26 23:34:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181557986" bet_no="06116032623323149442" match_no="1486652" match_area="Korea" match_id="5634516"  bet_type="B/S"  bet_content="Big @ 1.96 # "  bet_currency="RMB" bet_money="20.00" bet_odds="1.96" bet_winning="0"  bet_win="lost" bet_status="settled" bet_time="2016-03-26 23:32:30" trans_time="2016-03-26 23:33:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181556753" bet_no="06116032623292833436" match_no="1486650" match_area="Korea" match_id="5634512"  bet_type="B/S"  bet_content="Small @ 1.96 # "  bet_currency="RMB" bet_money="10.00" bet_odds="1.96" bet_winning="19.60"  bet_win="win" bet_status="settled" bet_time="2016-03-26 23:29:27" trans_time="2016-03-26 23:30:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181554946" bet_no="06116032623245505232" match_no="1486647" match_area="Korea" match_id="5634504"  bet_type="B/S"  bet_content="Big @ 1.96 # "  bet_currency="RMB" bet_money="7.00" bet_odds="1.96" bet_winning="0"  bet_win="lost" bet_status="settled" bet_time="2016-03-26 23:24:53" trans_time="2016-03-26 23:26:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181548254" bet_no="06116032623082835115" match_no="1486636" match_area="Korea" match_id="5634476"  bet_type="B/S"  bet_content="Small @ 1.96 # "  bet_currency="RMB" bet_money="19.00" bet_odds="1.96" bet_winning="37.24"  bet_win="win" bet_status="settled" bet_time="2016-03-26 23:08:27" trans_time="2016-03-26 23:09:00" /><item  member_id="xiaowen12" member_type="CASH" session_token="56DB52FF3E749147DB528A00C91E42F6"  bet_id="181547841" bet_no="06116032623071687112" match_no="1486635" match_area="Korea" match_id="5634472"  bet_type="B/S"  bet_content="Small @ 1.96 # "  bet_currency="RMB" bet_money="10.00" bet_odds="1.96" bet_winning="0"  bet_win="lost" bet_status="settled" bet_time="2016-03-26 23:07:15" trans_time="2016-03-26 23:08:00" /></bet><datetime>2016-03-27 00:10:31</datetime></game_transaction_detail>
EOD;
		// $arr = $this->utils->xml2array(new SimpleXMLElement($xml));

		$resultArr = json_decode(json_encode(new SimpleXMLElement($xml)), true);
		$this->utils->debug_log('resultArr', $resultArr);

		if (isset($resultArr['bet']['item'])) {
			$gameRecords = $resultArr['bet']['item'];
			$rows = array();
			foreach ($gameRecords as $item) {

				$this->utils->debug_log('item', $item);

				if (isset($item['@attributes'])) {
					$attrs = $item['@attributes'];
					$rows[] = $attrs;
				}
			}
			$resultArr['bet'] = $rows;
		}

		$this->utils->debug_log('resultArr', $resultArr);

		$arr = array();
		foreach ($resultArr['bet'] as $value) {
			$arr[] = $value['bet_id'];
		}

		$this->utils->debug_log('bet_id', $arr);
	}
}