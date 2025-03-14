<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_mg_api extends BaseTesting {

	private $platformCode = MG_API;
	private $platformName = 'MG';
	private $api = null;

	private $existPlayer = 'ktest001';

	public function init() {
		// $this->load->model('game_provider_auth');
		// $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
		// $this->test($this->game_platform_manager == null, false, 'init game platform manager');
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		// $this->test($api == null, false, 'init api');
		//var_dump($this->platformCode);
		$this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
	}

	public function testAll() {
		$this->init();
		 $this->testCreatePlayer();
		// $this->testDeposit();
		// $this->testWithdraw();
		// $this->testIsPlayerExist();
		//$this->testQueryPlayerBalance();
		// $this->testBlockPlayer();
		// $this->testUnblockPlayer();
		// $this->testQueryPlayerInfo();
		 //$this->testSyncGameLogs();
		// $this->testSyncMergeToGameLogs();
		// $this->testPrefixUsername();
		//$this->testGetPrepareData();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testSoap() {
		// echo $unknown;
		try {
			$url = 'https://entservices.totalegame.net';
			$options = array(
				'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
				'save_response' => true,
				'exceptions' => true);

			// $client = new SoapClient($url, $options);
			$client = new ProxySoapClient($url, $options);
		} catch (SoapFault $f) {
			$this->utils->debug_log('SoapFault', $f, $f->faultcode, $f->faultstring, $f->getCode(), $f->getMessage());
		}

		$this->utils->debug_log('done=============');
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

	private function testBase() {
		//create player
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

		// if ($rlt['success']) {
		$gameUsername = $this->api->getGameUsernameByPlayerUsername($username);
		$this->utils->debug_log('=====get game username: ' . $username . ' to ' . $gameUsername . ' ======================================================');
		// }
		if ($rlt['success']) {
			//check exists
			$this->utils->debug_log('=====isPlayerExist: ' . $username . ' ======================================================');
			$rlt = $this->api->isPlayerExist($username);
			// $this->utils->debug_log('after isPlayerExist', $rlt);
			$this->test($rlt['success'], true, $username . ' isPlayerExist success for ' . $this->platformName);
			$this->test($rlt['exists'], true, $username . ' isPlayerExist exists for ' . $this->platformName);
			$this->utils->debug_log('=====result of isPlayerExist: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query player info
			$this->utils->debug_log('=====queryPlayerInfo: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			//must = game username
			//ignore lower/upper
			$this->test(strtolower($rlt['playerInfo']['playerName']), strtolower($gameUsername), $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			// $this->utils->debug_log('succ', $succ);
			$this->utils->debug_log('=====result of queryPlayerInfo: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance for ' . $this->platformName);
			$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//deposit
			$this->utils->debug_log('=====depositToGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
			$rlt = $this->api->depositToGame($username, $depositAmount);
			$this->test($rlt['success'], true, 'depositToGame for ' . $this->platformName);
			$this->test($rlt['currentplayerbalance'], $depositAmount, 'depositToGame: ' . $rlt['currentplayerbalance']);
			$this->utils->debug_log('=====result of depositToGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance after deposit for ' . $this->platformName);
			$this->test($rlt['balance'], $depositAmount, 'queryPlayerBalance balance value after deposit for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//withdrawal
			$this->utils->debug_log('=====withdrawFromGame: ' . $username . ' === ' . $depositAmount . ' ===================================================');
			$rlt = $this->api->withdrawFromGame($username, $depositAmount);
			$this->test($rlt['success'], true, 'withdrawFromGame for ' . $this->platformName);
			$this->test($rlt['currentplayerbalance'], 0, 'withdrawFromGame: ' . $rlt['currentplayerbalance']);
			$this->utils->debug_log('=====result of withdrawFromGame: ' . $username . ' === ' . $depositAmount . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//query balance
			$this->utils->debug_log('=====queryPlayerBalance: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerBalance($username);
			$this->test($rlt['success'], true, 'queryPlayerBalance after withdrawal for ' . $this->platformName);
			$this->test($rlt['balance'], 0, 'queryPlayerBalance balance value after withdrawal for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerBalance: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//block player
			$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer for ' . $this->platformName);
			$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);
		}
		if ($rlt['success']) {

			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after blockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			$this->test($rlt['playerInfo']['blocked'], true, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerInfo after blockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//unblock player
			$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->unblockPlayer($username);
			$this->test($rlt['success'], true, 'unblockPlayer for ' . $this->platformName);
			$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//check block status
			$this->utils->debug_log('=====queryPlayerInfo after unblockPlayer: ' . $username . ' ======================================================');
			$rlt = $this->api->queryPlayerInfo($username);
			$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
			$this->test($rlt['playerInfo']['blocked'], false, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of queryPlayerInfo after unblockPlayer: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {
			//change password
			$this->utils->debug_log('=====changePassword: ' . $username . ' ======================================================');
			$newPassword = 'newPass123';
			$rlt = $this->api->changePassword($username, $password, $newPassword);
			$this->test($rlt['success'], true, 'changePassword for ' . $this->platformName);
			// $this->test($rlt['password'], $newPassword, 'changePassword to ' . $newPassword . ' for ' . $this->platformName);
			$this->utils->debug_log('=====result of changePassword: ' . $username . '======================================================', $rlt);

		}
		if ($rlt['success']) {

			//block for save
			$this->utils->debug_log('=====blockPlayer for save: ' . $username . ' ======================================================');
			$rlt = $this->api->blockPlayer($username);
			$this->test($rlt['success'], true, 'blockPlayer save for ' . $this->platformName);
			$this->utils->debug_log('=====result of blockPlayer for save: ' . $username . '======================================================', $rlt);

		}
	}

	private function testGetPrepareData() {
		$rlt = $this->api->getPrepareData();
		$this->utils->debug_log('rlt', $rlt);
		var_export($rlt);
	}

	private function testBlock() {
		$username = 'testPWd8rdLm';
		$gameUsername = $this->api->getGameUsernameByPlayerUsername($username);
		//block player
		$this->utils->debug_log('=====blockPlayer: ' . $username . ' ======================================================');
		$rlt = $this->api->blockPlayer($username);
		$this->test($rlt['success'], true, 'blockPlayer for MG');
		$this->utils->debug_log('=====result of blockPlayer: ' . $username . '======================================================', $rlt);

		//check block status
		$this->utils->debug_log('=====queryPlayerInfo after blockPlayer: ' . $username . ' ======================================================');
		$rlt = $this->api->queryPlayerInfo($username);
		$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
		$this->test($rlt['playerInfo']['blocked'], true, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
		$this->utils->debug_log('=====result of queryPlayerInfo after blockPlayer: ' . $username . '======================================================', $rlt);

		//unblock player
		$this->utils->debug_log('=====unblockPlayer: ' . $username . ' ======================================================');
		$rlt = $this->api->unblockPlayer($username);
		$this->test($rlt['success'], true, 'unblockPlayer for ' . $this->platformName);
		$this->utils->debug_log('=====result of unblockPlayer: ' . $username . '======================================================', $rlt);

		//check block status
		$this->utils->debug_log('=====queryPlayerInfo after unblockPlayer: ' . $username . ' ======================================================');
		$rlt = $this->api->queryPlayerInfo($username);
		$this->test($rlt['success'], true, $username . ' queryPlayerInfo success for ' . $this->platformName);
		$this->test($rlt['playerInfo']['blocked'], false, $username . ' queryPlayerInfo username ' . $gameUsername . ' for ' . $this->platformName);
		$this->utils->debug_log('=====result of queryPlayerInfo after unblockPlayer: ' . $username . '======================================================', $rlt);

	}

	public function testPrefixUsername() {
		$this->test($this->api->convertUsernameToGame('ktest001'), 'ogktest001', 'convert username');
	}

	public function testQueryPlayerBalance() {
		$playerName = $this->existPlayer;

		$rlt = $this->api->queryPlayerBalance($playerName);
		$this->utils->debug_log($rlt);
		var_dump($rlt);

		// list($success, $balance) = $this->game_platform_manager->queryPlayerBalance($playerName);
		$this->test($rlt['success'], true, 'balance for ' . $playerName);
		$this->test($rlt['balance'], 0, 'balance amount ' . $rlt['balance'] . ' for ' . $playerName);
	}

	private function testCreatePlayer() {
		$username = 'testdan02' . random_string('numeric');
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);

		$this->utils->debug_log($rlt);

		$this->test($rlt['success'], true, 'create player: ' . $username);

		$rlt = $this->api->isPlayerExist($username);
		$this->utils->debug_log('query player if exists: ', $rlt);

		$this->test($rlt['success'], true, 'player ' . $username . ' already exists');
		$this->test(@$rlt['exists'], true, 'player ' . $username . ' already exists');
	}

	private function testIsPlayerExist() {
		$username = $this->existPlayer; // 'test_mg_JBhUTqgn';
		$rlt = $this->api->isPlayerExist($username);
		log_message('error', 'query player if exists: ' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'player ' . $username . ' already exists');
	}

	private function testBlockPlayer() {
		$username = 'ktest001';
		$rlt = $this->api->blockPlayer($username);
		var_dump($rlt);
		// log_message('error', 'query player info: ' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'blockPlayer for MG');
	}

	private function testUnblockPlayer() {
		$username = 'ktest001';
		$rlt = $this->api->unblockPlayer($username);
		var_dump($rlt);
		// log_message('error', 'query player info: ' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'unblockPlayer for MG');
	}

	private function testQueryPlayerInfo() {
		$username = 'ktest001';
		$rlt = $this->api->queryPlayerInfo($username);
		var_dump($rlt);
		// log_message('error', 'query player info: ' . var_export($rlt, true));
		$this->test($rlt['success'], true, 'testQueryPlayerInfo for MG');
	}

	private function testDeposit() {
		$playerName = 'ktest001';
		$depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		$this->utils->debug_log('depositToGame', $rlt);
		// log_message("error", var_export($rlt, true));
		$this->test($rlt['success'], true, 'Test Player Deposit to MG');
		$this->test($rlt['currentplayerbalance'], $depositAmount, 'Current Balance after deposit is ');

		// $rlt = $this->api->withdrawFromGame($playerName, $depositAmount);
		// $this->utils->debug_log('withdrawFromGame', $rlt);
		// // log_message("error", var_export($rlt, true));
		// $this->test($rlt['success'], true, 'Test Player withdrawal to MG');
		// $this->test($rlt['currentplayerbalance'], 0, 'Current Balance after wtihdrawal is ');

	}

	private function testWithdraw() {
		$playerName = 'ktest001';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		$this->utils->debug_log('withdrawFromGame', $rlt);
		// log_message("error", var_export($rlt, true));
		$this->test($rlt['success'], true, 'Test Player Withdrawal to MG');
		$this->test($rlt['currentplayerbalance'], $amount, 'Current Balance after withdrawal is ');
	}

	private function testSyncGameLogs() {

		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-10-14 09:00:00');
		$dateTimeTo = new DateTime('2015-10-15 00:00:00');

		$playerName = 'actfmg1';

		$this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);
		$this->test($rlt['success'], true, 'Test syncOriginalGameLogs to MG');
	}

	private function testSyncMergeToGameLogs() {
		$api = $this->game_platform_manager->initApi($this->platformCode);

		$token = 'abc123';
		$dateTimeFrom = new DateTime('2015-10-14 09:00:00');
		$dateTimeTo = new DateTime('2015-10-15 00:00:00');

		$playerName = 'actfmg1';
		$gameName = '';

		$api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo, "playerName" => $playerName, "gameName" => $gameName);
		$api->syncMergeToGameLogs($token);
	}

	private function testProcessUnknownGame() {
		$api = $this->api;

		$unknownGame = $api->getUnknownGame();
		$game_description_id = null;
		$game_type_id = null;
		$testCode = random_string('alnum');
		$game = 'Test Game' . $testCode;
		$game_type = 'Test Game Type' . $testCode;
		$externalGameId = 'Test Game1' . $testCode;
		$module_id = 'testmodule' . $testCode;
		$client_id = 'testclient' . $testCode;

		$extra = array('game_code' => $module_id . '_' . $client_id,
			'moduleid' => $module_id, 'clientid' => $client_id);

		list($game_description_id, $game_type_id) = $api->processUnknownGame(
			$game_description_id, $game_type_id,
			$game, $game_type, $externalGameId, $extra,
			$unknownGame);

		$this->test($game_description_id != null, true, 'process unknownGame');
	}

}