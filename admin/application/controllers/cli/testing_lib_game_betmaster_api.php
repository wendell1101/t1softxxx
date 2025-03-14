<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_betmaster_api extends BaseTesting {

	private $platformCode = BETMASTER_API;
	private $platformName = 'BETMASTER';
	private $testUser = 'a101radjesh';
	private $api = null;

	public function init()
	{
		 $this->load->model('game_provider_auth');
		 $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
		 $this->test($this->game_platform_manager == null, false, 'init game platform manager');
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		// $this->test($api == null, false, 'init api');
		//var_dump($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll()
	{
		$this->init();

//		$this->testCreatePlayer();
//		$this->testIsPlayerExist();
//		$this->testQueryPlayerInfo();
//		$this->testUpdatePlayerInfo();
//		$this->testChangePassword();
//		$this->testBlockPlayer();
//		$this->testUnblockPlayer();
//		$this->testIsPlayerBlocked();
//		$this->testKickOutGame();
//		$this->testQueryPlayerBalance();
//		$this->testDepositToGameWithPlatformId();
//		$this->testWithdrawFromGameWithPlatformId();
//		$this->testQueryGameRecords();
//		$this->testSyncOriginalGameLogs();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testCreatePlayer() {
//		$playerName = 'test' . random_string('numeric', 5) . '@t1agency.com';
		$playerName = 'test55665';
		//$this->utils->debug_log("create player", $username);
		$password = 'test5566';
		$playerId = 56967;
		$extra = array(
			'country'	=>	'ID',
			'currency'	=>	'MYR',
			'email'		=>	'foobar1@gmail.com',
			'language'	=>	'en',
		);
		$rlt = $this->api->createPlayer($playerName, $playerId, $password, null, $extra);
		$this->test($rlt['success'], true, ' testCreatePlayer for ' . $this->platformName);
		$this->utils->debug_log($rlt);
	}

	private function testIsPlayerExist() {
		$rlt = $this->api->isPlayerExist('a101radjesh@t1agency.com');
		$this->test($rlt['success'], true, 'testIsPlayerExist for BETMASTER');
		$this->utils->debug_log('isPlayerExist', $rlt);
	}

	private function testQueryPlayerInfo() {
		$rlt = $this->api->queryPlayerInfo('a101radjesh@t1agency.com');
		$this->test($rlt['success'], true, 'testQueryPlayerInfo to BETMASTER');
		$this->utils->debug_log('queryPlayerInfo', $rlt);
	}

	private function testUpdatePlayerInfo() {

		$info = array(
			'country'	=>	'ID',
			'currency'	=>	'MYR',
			'email'		=>	'foobar@gmail.com',
			'language'	=>	'en',
		);
		$rlt = $this->api->updatePlayerInfo('a101radjesh@t1agency.com', $info);
//		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testUpdatePlayerInfo for BETMASTER');
	}

	private function testChangePassword() {
		$oldPassword = 'test5566';
		$newPassword = 'test5566123';
		$rlt = $this->api->changePassword('test5566@t1agency.com', $oldPassword, $newPassword);
		$this->utils->debug_log('rlt', $rlt);
		$this->test($rlt['success'], true, 'testChangePassword for BETMASTER');
		// $this->test($rlt['password'], 'newPass123', 'check new password');
	}

	private function testBlockPlayer() {
		$rlt = $this->api->blockPlayer($this->testUser);
		$this->test($rlt['success'], true, 'testBlockPlayer for BETMASTER');
	}

	private function testUnblockPlayer() {
		$rlt = $this->api->unblockPlayer($this->testUser);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'UnblockPlayer for BETMASTER');
	}

	private function testIsPlayerBlocked() {
		$rlt = $this->api->isPlayerBlocked('a101radjesh@t1agency.com');
		$this->test($rlt['success'], true, 'testIsPlayerBlocked to BETMASTER');
	}

	private function testKickOutGame() {
		$rlt = $this->api->kickOutGame($this->testUser, 1001);
//		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testKickOutGame to BETMASTER');
	}

//	private function testCheckLoginStatus() {
//		$rlt = $this->api->checkLoginStatus($this->testUser);
//		log_message('error', 'rlt:' . var_export($rlt, true));
//		$this->test($rlt['success'], true, 'testCheckLoginStatus for BETMASTER');
//		$this->test($rlt['loginStatus'], false, 'check if login for BETMASTER');
//	}

	private function testQueryPlayerBalance() {
		$rlt = $this->api->queryPlayerBalance($this->testUser);
		$this->test($rlt['success'], true, 'testQueryPlayerBalance to BETMASTER');
		$this->utils->debug_log('queryPlayerInfo', $rlt);
	}

	private function testDepositToGameWithPlatformId() {
		$amount = '10';
		$rlt = $this->api->depositToGameWithPlatformId('test993', $amount, null, 1001);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testDepositToGameWithPlatformId for BETMASTER');
	}

	private function testWithdrawFromGameWithPlatformId() {
		$amount = '1';
		$rlt = $this->api->withdrawFromGameWithPlatformId($this->testUser, $amount, null, 1001);
		log_message('error', 'rlt:' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testwithdrawFromGameWithPlatformId for BETMASTER');
	}

	private function testQueryGameRecords() {
//		$dateFrom = new DateTime('2017-05-14 2:12:00');
//		$dateTo = new DateTime('2017-05-14 2:17:00');
		$dateFrom = new DateTime('2017-05-14 10:12:00');
		$dateTo = new DateTime('2017-05-14 10:17:00');
		$rlt = $this->api->queryGameRecords($dateFrom, $dateTo, 'test753');
//		log_message("error", var_export($rlt, true));
		$this->test($rlt['success'], true, 'testQueryGameRecords for BETMASTER');
//		$this->test(is_array($balResult['gameRecords']), true, 'Test Game Records');
	}

	private function testSyncOriginalGameLogs() {

		$token 			= 'BETMASTER';
//		$dateTimeFrom 	= new DateTime(date('Y-m-d H:i:s', strtotime('2017-05-01 17:10:00')));
//		$dateTimeTo 	= new DateTime(date('Y-m-d H:i:s', strtotime('2017-05-01 17:15:00')));
		$dateTimeFrom 	= new DateTime(date('Y-m-d H:i:s', strtotime('2017-05-14 10:12:00')));
		$dateTimeTo 	= new DateTime(date('Y-m-d H:i:s', strtotime('2017-05-14 10:17:00')));

//		$dateTimeFrom 	= new DateTime(date('Y-m-d H:i:s', strtotime('2017-05-01 17:10:00')));
//		$dateTimeTo 	= new DateTime(date('Y-m-d H:i:s', strtotime('2017-05-01 17:23:00')));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);

		$this->utils->debug_log('====================testSyncOriginalGameLogs', $this->api->syncInfo);

		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->utils->debug_log('syncOriginalGameLogs', $rlt);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to BETMASTER');
		return $rlt;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'BETMASTER';
//		$dateTimeFrom = new DateTime('2017-05-01 17:10:00');
//		$dateTimeTo = new DateTime('2017-05-01 17:15:00');
//		$dateTimeFrom = new DateTime('2017-05-02 1:10:00');
//		$dateTimeTo = new DateTime('2017-05-02 1:15:00');
		$dateTimeFrom = new DateTime('2017-05-14 10:12:00');
		$dateTimeTo = new DateTime('2017-05-14 10:17:00');

		$this->api->syncInfo[$token] = array("playerName" => 'a101radjesh', "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncMergeToGameLogs to BETMASTER');
	}


	private function testTimezone() {
		$originTime = '2017-05-01 10:00:00';
		$finalTime = $this->api->convertGameTime($originTime);

		$this->test($originTime, '2017-05-01 10:00:00', 'origin Time');
		$this->test($finalTime, '2017-05-01 18:00:00', 'final Time');
	}

	private function testBatchQueryPlayerBalance(){
		$testUsers = array('test881');
		$rlt = $this->api->batchQueryPlayerBalance($testUsers);

		print_r($rlt);

		$this->test($rlt['success'], true, 'TestBatchQueryPlayerBalance to BETMASTER');
	}

//	private function testQueryGameRecords() {
//		$player = $this->getFirstPlayer();
//		$dateFrom = new DateTime('2015-07-17');
//		$dateTo = new DateTime('2015-07-20');
//		$balResult = $this->api->queryGameRecords($dateFrom, $dateTo, $player->username);
//		log_message("error", var_export($balResult, true));
//		$this->test($balResult['success'], true, 'Test Game Records Results');
//		$this->test(is_array($balResult['gameRecords']), true, 'Test Game Records');
//	}

}