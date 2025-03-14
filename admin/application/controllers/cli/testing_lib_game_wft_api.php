<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_wft_api extends BaseTesting {

	private $platformCode = WFT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Test API class loaded. Expected: true');
		if(!$loaded){
			$this->utils->debug_log("Error: API not loaded, platformCode = ".$this->platformCode);
			return false;
		}

		$this->test($apiClassName, 'game_api_wft', 'Test API class name. Expected: game_api_wft');
		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test API platform code. Expected: ' . $this->platformCode);
		return true;
	}

	## all tests route through this function
	public function testTarget($methodName) {
		if($this->init()) {
			$this->$methodName();
		}
	}

	# Actual Tests
	## Invokes all tests defined below. A test function's name should begin with 'test'
	public function testAll() {
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll');
		foreach($classMethods as $method){
			if(strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
				continue;
			}

			$this->$method();
		}
	}

	## Available User: testddsu
	private function testLogin() {
		$this->api->login('testddsu');
	}

	private function testOne() {
		$this->utils->debug_log($this->api->login('testddsu'));
		#$this->utils->debug_log($this->api->queryForwardGame('testddsu'));
		#$this->utils->debug_log($this->api->syncMergeToGameLogs());
		#$this->utils->debug_log($this->api->syncOriginalGameLogs());
	}


	private function testCreatePlayer() {
		$username =  'TESTupdate1';
		$this->utils->debug_log("create player", $username);
		$password = 'pass123';
		$player = $this->getFirstPlayer($username);

		$rlt = $this->api->createPlayer($username, $player->playerId, $password, null);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Create Player: ' . $username);
	}

	private function testUpdateInfo() {
		$playerName =  'TESTupdate1';
		$infos= array('username' => 'TESTupdate1');
		$rlt = $this->api->updatePlayerInfo($playerName, $infos);
		$this->utils->debug_log($rlt);
		$this->test($rlt['success'], true, 'Update  Player (parameters): ' . $playerName);
	}





	## test the whole workflow
	public function testBase() {
		# random profile for a test user account
		//$username = 'TEST' . random_string('alnum', 4); # only allow A-Za-z0-9
		$username = 'TESTupdate' . random_string('alnum', 4);
		$password = '12344321';
		$depositAmount = 1.2;
		$player = $this->getFirstPlayer($username);
		$this->api->unblockPlayer($username);

		# create user account
		$result = $this->api->createPlayer($username, $player->playerId, $password);
		$this->test($result['success'], true, 'Test createPlayer, expected: True');
		if(!$result['success']) {
			return;
		}

		# query player info (check balance)
		$result = $this->api->queryPlayerBalance($username);
		$this->test($result['success'], true, 'Test queryPlayerBalance, expected: True');
		$this->test($result['balance'], 0.0, 'Test queryPlayerBalance return value, expected: 0');
		if(!$result['success']) {
			return;
		}

		# deposit to game
		$result = $this->api->depositToGame($username, $depositAmount);
		$this->test($result['success'], true, 'Test depositToGame, expected: True');
		$this->test($result['currentplayerbalance'], $depositAmount, "Test depositToGame balance, expected: $depositAmount");
		if(!$result['success']) {
			return;
		}

		# withdraw from game
		$result = $this->api->withdrawFromGame($username, $depositAmount);
		$this->test($result['success'], true, 'Test withdrawFromGame, expected: True');
		$this->test($result['currentplayerbalance'], 0.0, "Test depositToGame balance, expected: 0");
		if(!$result['success']) {
			return;
		}

		# user login
		$result = $this->api->login($username, $password);
		$this->test($result['success'], true, 'Test login, expected: True');
		$this->test(strlen($result['loginUrl']) > 0, true, 'Test login -> loginUrl, expected: Non-empty string');
		$this->test(strpos($result['loginUrl'], 'ZH-CN') !== false, true, 'Test login -> loginUrl lang code, expected: ZH-CN');
		if(!$result['success']) {
			return;
		}

		# get game url
		$gameUrl = $this->api->queryForwardGame($username, array('lang' => 'EN-US'));
		$this->test(strlen($gameUrl) > 0, true, 'Test queryForwardGame, expected: Non-empty string');
		$this->test(strpos($gameUrl, 'EN-US') !== false, true, 'Test queryForwardGame with custom lang, expected: EN-US');

		# query user login status
		# Note: This is supposed to fail unless the gameUrl above is accessed from a browser to perform an actual login
		$result = $this->api->checkLoginStatus($username, $password);
		$this->test($result['success'], true, 'Test checkLoginStatus, expected: True');
		$this->test($result['loginStatus'], true, 'Test checkLoginStatus -> loginStatus, expected: True');
		if(!$result['success']) {
			return;
		}

		# user logout
		$result = $this->api->logout($username);
		$this->test($result['success'], true, 'Test logout, expected: True');
		if(!$result['success']) {
			return;
		}

		# query user login status after logout
		$result = $this->api->checkLoginStatus($username, $password);
		$this->test($result['success'], true, 'Test checkLoginStatus after logout, expected: True');
		$this->test($result['loginStatus'], false, 'Test checkLoginStatus -> loginStatus after logout, expected: False');
		if(!$result['success']) {
			return;
		}

		# disable user account
		$result = $this->api->blockPlayer($username);
		$this->test($result['success'], true, 'Test blockPlayer, expected: True');
	}
}
