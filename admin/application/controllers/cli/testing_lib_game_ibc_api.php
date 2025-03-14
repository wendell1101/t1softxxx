<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ibc_api extends BaseTesting {

	private $platformCode = IBC_API;
	private $platformName = 'IBC';
	private $api = null;

	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		// $this->testCreatePlayer();
		// $this->testQueryPlayerBalance();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testPlayGame();
		// $this->testSyncGameLogs();
		 $this->testSyncMergeToGameLogs();
		// $this->testBase();
		// $this->testQueryForwardGame();
		// $this->testSyncLostAndFound();
		//$this->testisPlayerExist();
		// $this->testSetMemberBetSetting();
		// $this->testConfirmMemberBetSetting();
		// $this->testGetMemberBetSetting();
	}

	public function testisPlayerExist(){
		$token = 'testjerb';
		$rlt = $this->api->isPlayerExist($token);
		echo"<pre>";print_r($rlt);
	}

	public function testSyncLostAndFound(){
		$token = 'abc123d';
		// $dateTimeFrom = new DateTime('2017-11-14 12:00:00');
		// $dateTimeTo = new DateTime('2017-11-15 11:59:59');
		$dateTimeFrom = new DateTime('2017-11-14 12:03:44');
		$dateTimeTo = new DateTime('2017-11-17 19:57:28');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncLostAndFound($token);
		echo"<pre>";print_r($rlt);
		// $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to IBC');
	}

	private function testBase() {
		$username = 'testIBC' . random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);

		$depositAmount = 10;
		$rlt = $this->api->depositToGame($username, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to IBC');

		$rlt = $this->api->queryPlayerBalance($username);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $username);
		$this->test($rlt['balance'], 30, 'balance amount ' . $rlt['balance'] . ' for ' . $username);

		$withdrawAmount = 10;
		$rlt = $this->api->withdrawFromGame($username, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IBC');

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
		$username = 'testibc' . random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);
	}

	private function testDeposit() {
		$playerName = 'testibc24164105';
		$depositAmount = 100;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Deposit to IBC');
	}

	private function testWithdraw() {
		$playerName = 'testibc24164105';
		$withdrawAmount = 100;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawToGame', $rlt);
		$this->test($rlt['success'], true, 'Test Player Withdraw to IBC');
	}

	public function testQueryPlayerBalance() {
		$playerName = 'testibc24164105';
		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 0, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testSyncGameLogs() { 
		$token = 'abc123d';
		// $dateTimeFrom = new DateTime('2017-11-14 12:00:00');
		// $dateTimeTo = new DateTime('2017-11-15 11:59:59');
		$dateTimeFrom = new DateTime('2018-03-04 18:00:00');
		$dateTimeTo = new DateTime('2018-03-04 18:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		print_r($rlt);
		// $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to IBC');
	}

	private function testSyncMergeToGameLogs() { 
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		// $token = 'abc123';
		// $dateTimeFrom = new DateTime('2016-04-17 00:00:00');
		// $dateTimeTo = new DateTime('2016-05-19 23:59:59');

		// $playerName = 'wiplixt';

		// $this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		// $rlt = $this->api->syncMergeToGameLogs($token);
		// $this->test($rlt['success'], true, 'Test syncMergeToGameLogs to 1sGames');

		$token = 'abc123d';
		// $dateTimeFrom = new DateTime('2017-11-14 12:00:00');
		// $dateTimeTo = new DateTime('2017-11-15 11:59:59');
		$dateTimeFrom = new DateTime('2018-08-08 00:00:00');
		$dateTimeTo = new DateTime('2018-08-08 23:59:59');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		print_r($rlt);
	}

	private function testQueryForwardGame() {
		// $playerName = 'test1sgame26448807';
		// $password = 'pass123';
		// $gameType = '51';
		// $gameName = 'Xmen';
		// $extra = array('password' => $password, 'gameType' => $gameType, 'gameName' => $gameName);
		// $rlt = $this->api->queryForwardGame($playerName, $extra);

		// $this->test($rlt['success'], true, 'Test Play Games to 1sGames: ' . $rlt);
		$username =  'testjerb06';
     	$extra = array(
     		'language' => 'cs', 
     		'gameType' => 3, 
     		'is_mobile' => 0
     	);
		$rlt = $this->api->queryForwardGame($username,$extra);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testPlayGame() {
		$playerName = 'testibc24164105';
		$password = 'pass123';
		$rlt = $this->api->login($playerName, $password);
		$this->test($rlt['success'], true, 'Login');
		$sessionToken = $rlt["sessionToken"];
		$this->load->helper('cookie');
		$this->input->set_cookie('g', $sessionToken, 3600); //3600 = 1hr
	}

	public function testRaw($post_data = null, $url = null) {
		// $url = "http://api.mid.ib.gsoft88.net/api/CreatePlayer?SecurityToken=587C960551C91AC1275EBB073FC13CEB&OpCode=LBBCN&PlayerName=testibc57663977&OddsType=1&MaxTransfer=1000&MinTransfer=100";
		// $url = "http://api.prod.ib.gsoft88.net/api/CreateMember?SecurityToken=587C960551C91AC1275EBB073FC13CEB&OpCode=LBBCN&PlayerName=testibc57663977&OddsType=1&MaxTransfer=1000&MinTransfer=100";
		// $url = "http://api.prod.ib.gsoft88.net/api/CreateMember?SecurityToken=C8E7E4F11F731D10D25E2DE5274C6CC5&OpCode=LBBCN&PlayerName=testibc35675120&OddsType=1&MaxTransfer=1000&MinTransfer=100";
		$url = "http://api.prod.ib.gsoft88.net/api/FundTrasfer?OpCode=LBBCN&PlayerName=testibc24164105&TransId=7pzdY2l9&Amount=10&Direction=1&SecurityToken=15FD12D78377D8A4EA81A50CB5446AEF";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_POST, 1);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		var_dump($result, $errno, $error);
		$this->utils->debug_log('res:' . $result . 'errno:' . $errno . 'error:' . $error);
		//close connection
		curl_close($ch);
	}

	private function testSetMemberBetSetting() {
		$playerName =  'testlc ';
		$rlt = $this->api->setMemberBetSetting($playerName,$extra = null);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testConfirmMemberBetSetting() {
		$playerName =  'testlc ';
		$rlt = $this->api->confirmMemberBetSetting($playerName,$extra = null);
		echo "<pre>";print_r($rlt);exit;
	}
	
	private function testGetMemberBetSetting() {
		$playerName =  'testlc ';
		$rlt = $this->api->getMemberBetSetting($playerName,$extra = null);
		echo "<pre>";print_r($rlt);
	}

}