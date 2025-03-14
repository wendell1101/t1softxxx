<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_png_api extends BaseTesting {

	private $platformCode = PNG_API;
	private $platformName = 'PNG_API';

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
	}

	public function testAll() {

	 	$this->init();
	 	$this->testInsertMergeToGameLogs();
	 	//$this->testSyncLDF();
	   	// $this->testCreatePlayer();
	    // $this->testQueryPlayerBalance();
	    // $this->testIsPlayerExist(); 
	    // $this->testDeposit();
	    // $this->testWithdraw();
	    //$this->testLogin();
	 	
	    //$this->testIsPlayerExist();
	    //$this->testQueryForwardGame();
	 	// $this->testChangePassword();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
	    // $this->testIsPlayerExist(); 
	    // $this->testSyncGameLogs();
	    // $this->testSyncMergeToGameLogs();
	     // $this->testLogout();
	
	
	}
	private function testInsertMergeToGameLogs() {
		$data = "1568391";
		$rlt = $this->api->insertMergeToGameLogs($data);
		echo "<pre>";print_r($rlt);exit;
	}
	private function testSyncLDF() {
		$json = '{
"Messages": [
		{
			"Username": "testgar1",
			"Password": "",
			"ProductGroup": 2,
			"ClientIP": "193.13.73.210",
			"ContextId": 0,
			"Language": null,
			"GameId": 251,
			"AgentString": "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0",
			"StatusCode": 0,
			"StatusMessage": null,
			"ExternalUserId": "testgar1",
			"Nickname": "testgar1",
			"Currency": "EUR",
			"Country": "GB",
			"Birthdate": null,
			"AffiliateId": "testar",
			"Registration": null,
			"Gender": null,
			"MessageId": null,
			"MessageType": 1,
			"MessageTimestamp": "2016-01-11T14:19:55"
		},
		{
			"TransactionId": 3110992,
			"Status": 1,
			"Amount": 0.76,
			"Time": "2016-01-11T14:20:24.1512",
			"ProductGroup": 2,
			"ExternalUserId": "testgar1",
			"GamesessionId": 27056,
			"GameId": 251,
			"RoundId": 1568391,
			"Currency": "EUR",
			"ExternalTransactionId": "160111021945-b-9",
			"Balance": 9996.2,
			"MessageId": null,
			"MessageType": 3,
			"MessageTimestamp": "2016-01-11T14:20:24"
		},
		{
			"TransactionId": 3110993,
			"Status": 1,
			"Amount": 3.52,
			"Time": "2016-01-11T14:20:41.3736",
			"ProductGroup": 2,
			"ExternalUserId": "testgar1",
			"GamesessionId": 27056,
			"GamesessionState": 0,
			"GameId": 251,
			"RoundId": 1568391,
			"RoundData": null,
			"RoundLoss": 0.76,
			"JackpotLoss": 0.0,
			"JackpotGain": 0.0,
			"Currency": "EUR",
			"ExternalTransactionId": "160111021945-p-10",
			"Balance": 9999.72,
			"NumRounds": 5,
			"TotalLoss": 3.8,
			"TotalGain": 3.52,
			"ExternalFreegameId": null,
			"MessageId": null,
			"MessageType": 4,
			"MessageTimestamp": "2016-01-11T14:20:41"
		},
		{
			"TransactionId": 3110994,
			"Time": "2016-01-11T14:20:41.4516",
			"ProductGroup": 2,
			"ExternalUserId": "testgar1",
			"GamesessionId": 27056,
			"GameId": 251,
			"Currency": "EUR",
			"ExternalTransactionId": "160111021945-p-11",
			"Balance": 9999.72,
			"NumRounds": 5,
			"TotalLoss": 3.8,
			"TotalGain": 3.52,
			"GamesessionStarted": "2016-01-11T14:19:58.77",
			"GamesessionFinished": "2016-01-11T14:20:41.37",
			"ExchangeRate": 1.0,
			"ContextId": 0,
			"ClientIP": "193.13.73.210",
			"ExternalFreegameId": null,
			"JackpotLoss": 0.0,
			"JackpotGain": 0.0,
			"FreegameRounds": null,
			"FreegameBet": null,
			"FreegameWin": null,
			"MessageId": null,
			"MessageType": 5,
			"MessageTimestamp": "2016-01-11T14:20:41"
		},
		{
			"ProductGroup": 2,
			"ExternalUserId": "testgar1",
			"MessageId": null,
			"MessageType": 2,
			"MessageTimestamp": "2016-01-11T14:20:41"
		}
	]
}';
		$data = json_decode($json,true);
		$rlt = $this->api->syncLDF($data);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testIsPlayerExist() {
	
		$username =  'testnew';
		$rlt = $this->api->isPlayerExist($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testLogin() {
	
		$username =  'testgar1';
		$rlt = $this->api->login($username);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testCreatePlayer() { 
		
		//$username = $this->existPlayer; 
		$username = 'testgar1'; 
		$password = 'password';
		$playerId = '56963';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2017-06-15 13:04:45');
		$dateTimeTo = new DateTime('2017-06-15 14:00:00');
		
		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		
		echo "<pre>";print_r($rlt);
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123';
		$dateTimeFrom = new DateTime('2017-06-15 13:04:45');
		$dateTimeTo = new DateTime('2017-06-15 14:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);
	}

	private function testChangePassword() {
		$username = 'testnew'; 
		//$oldPassword = $this->password;
		$oldPassword = 'password1';
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}


	private function testDeposit() { 

	    $playerName = 'testnew';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testnew';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testWithdraw() {
		$playerName = 'testnew';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;
		
	}

	private function testQueryForwardGame(){ 
     	$username =  'testnew';
		$rlt = $this->api->queryForwardGame($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testnew';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testnew';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}



}