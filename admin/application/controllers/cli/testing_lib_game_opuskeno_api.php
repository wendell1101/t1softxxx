<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_opuskeno_api extends BaseTesting {

	private $platformCode = OPUS_KENO_API;
	private $platformName = 'OPUS KENO';
	private $api = null;

	public function init() {
		// $this->load->model('game_provider_auth');
		// $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
		// $this->test($this->game_platform_manager == null, false, 'init game platform manager');
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		// $this->test($api == null, false, 'init api');
	}

	public function testAll() {
		$this->init();
		//$this->testCreatePlayer();
		//$this->testQueryPlayerBalance();
		//$this->testDeposit();
		//$this->testWithdraw();
		//$this->testPlayGame();
		//$this->testSyncGameLogs();
		$this->testSyncMergeToGameLogs();
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
		$username = 'testshu';
		$password = 'password';
		$rlt = $this->api->createPlayer($username,111, $password, null);
		echo "<pre>";var_dump($rlt);exit;
	}

	public function testQueryPlayerBalance() {
		$username = 'testshu';
		$rlt = $this->api->queryPlayerBalance($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testDeposit() {
		$username = 'testshu';
		$depositAmount = 1;
		$rlt = $this->api->depositToGame($username, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testWithdraw() {
		$username = 'testshu';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($username, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-11-09 00:00:00');
		$dateTimeTo = new DateTime('2016-11-09 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2016-11-09 00:00:00');
		$dateTimeTo = new DateTime('2016-11-09 23:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testQueryForwardGame() {
		$playerName = 'test1sgame26448807';
		$password = 'pass123';
		$gameType = '51';
		$gameName = 'Xmen';
		$extra = array('password' => $password, 'gameType' => $gameType, 'gameName' => $gameName);
		$rlt = $this->api->queryForwardGame($playerName, $extra);

		$this->test($rlt['success'], true, 'Test Play Games to Opus: ' . $rlt);
	}

	public function testPlayGame() {
		$extra = array();
		$username = 'testshu';
		$rlt = $this->api->queryForwardGame($username, $extra);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testRaw($post_data = null, $url = null) {
		// $url = "https://club8api.bet8uat.com/op/createuser?merch_id=GSOFT05&merch_pwd=53AAAC24-CFE5-4842-B43E-BFEEA057C675&cust_id=testrtt01&cust_name=testrtt01&currency=CNY";
		// $url = "http://110.92.26.200/CreateMember.API?secret_key=862B05FA0F62&operator_id=CA8F608C-A278-4846-8193-6A5195DAD1C6&site_code=RTT&product_code=mcasino&member_id=ogtestopus12295222&language=zh-cn&currency=rmb";
		// $url = "http://mcasino.rtt1889.com/CreateMember.API?secret_key=862B05FA0F62&operator_id=CA8F608C-A278-4846-8193-6A5195DAD1C6&site_code=RTT&product_code=mcasino&member_id=ogtestopus12295222";
		//$url = "http://api.mansion888.net/CreateMember.API?secret_key=862B05FA0F62&operator_id=CA8F608C-A278-4846-8193-6A5195DAD1C6&site_code=RTT&product_code=mcasino&member_id=ogtestopus12295222";

		// $url = "http://api.uat01.mansion888.com/CreateMember.API?secret_key=862B05FA0F62&operator_id=CA8F608C-A278-4846-8193-6A5195DAD1C6&site_code=rtt&product_code=mcasino&member_id=opusp1_rtt&language=en-us&currency=rmb";
		// $url = "http://api.mansion888.com/CreateMember.API?secret_key=862B05FA0F62&operator_id=a&site_code=rtt&product_code=mcasino&member_id=opusp1_rtt&language=en-US&currency=RMB";
		// $url = "http://api.mansion888.net/CreateMember.API?secret_key=862B05FA0F62&operator_id=CA8F608C-A278-4846-8193-6A5195DAD1C6&site_code=rtt&product_code=mcasino&member_id=opusp1_rtt&language=en-us&currency=rmb";
		// $url = "http://api.mansion888.net/CreateMember.API?secret_key=862B05FA0F62&operator_id=CA8F608C-A278-4846-8193-6A5195DAD1C6&site_code=RTT&product_code=mcasino&member_id=97_opusp1_rtt&language=en-US&currency=RMB";
		// var_dump($url);
		// $ch = curl_init($url);

		// curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// // curl_setopt($ch, CURLOPT_POST, 1);
		// // curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

		// $result = curl_exec($ch);
		// $errno = curl_errno($ch);
		// $error = curl_error($ch);
		// var_dump($result, $errno, $error);
		// $this->utils->debug_log('res:' . $result . 'errno:' . $errno . 'error:' . $error);
		// curl_close($ch);
	}
}