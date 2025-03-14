<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ttg_api extends BaseTesting {

	private $platformCode 	= TTG_API;
	private $playerId;
	private $playerName;
	private $password;

	private $api = null;


	public function init() {
		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
		#$username = 'test' . random_string('numeric', 10);
		#$player = $this->getFirstPlayer($username);
		$this->playerId = 1; //$player->playerId;
		$this->playerName = 'testoink'; //$player->username;
		$this->password = 'pass123'; // Wu/10iH03wA=   //$player->password;
	}

	public function testAll() {
		echo "<pre>";
		$this->init();
		$this->testSyncOriginalGameLogs();
		// $result = $this->testisPlayerExist();
		// var_dump(array('testisPlayerExist' => $result));
		// $result = $this->testCreatePlayer();
		// var_dump(array('testCreatePlayer' => $result));
		// $result = $this->testisPlayerExist();
		// var_dump(array('testisPlayerExist' => $result));
		// $result = $this->testDepositToGame();
		// var_dump(array('testDepositToGame' => $result));
		// $result = $this->testQueryPlayerBalance();
		// var_dump(array('testQueryPlayerBalance' => $result));
		// $result = $this->testWithdrawFromGame();
		// var_dump(array('testWithdrawFromGame' => $result));
		// $result = $this->testQueryPlayerBalance();
		// var_dump(array('testQueryPlayerBalance' => $result));
		// $result = $this->testQueryForwardGame();
		// var_dump(array('testQueryForwardGame' => $result));
		// $result = $this->testSyncOriginalGameLogs();
		// print_r(array('testSyncOriginalGameLogs' => $result));
		// $result = $this->testSyncMergeToGameLogs();
		// print_r(array('testSyncMergeToGameLogs' => $result));
		// $result = $this->testBatchQueryPlayerBalance();
		// print_r(array('testBatchQueryPlayerBalance' => $result));
		// echo "</pre>";
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testCreatePlayer() {
		$rlt = $this->api->createPlayer($this->playerName, $this->playerId, $this->password, null);
		print_r($rlt);exit;

		return $rlt;
	}

	private function testLogin() {
		$extra = array();
		$rlt = $this->api->login($this->playerName,$extra);
		print_r($rlt);
	}

	private function testisPlayerExist() {
		$playerName = $this->playerName;
		$rlt = $this->api->isPlayerExist($playerName);
		print_r($rlt);exit;

		return $rlt;
	}

	private function testQueryPlayerBalance() {
		$playerName = $this->playerName;
		$rlt = $this->api->queryPlayerBalance($playerName);
		print_r($rlt);exit;

		return $rlt;
	}

	private function testDepositToGame() {
		$playerName = $this->playerName;
		$rlt = $this->api->depositToGame($playerName,1);
		print_r($rlt);exit;

		return $rlt;
	}

	private function testWithdrawFromGame() {
		$playerName = $this->playerName;
		$rlt = $this->api->withdrawFromGame($playerName,1);
		print_r($rlt);exit;

		return $rlt;
	}

	private function testBatchQueryPlayerBalance() {
		$rlt = $this->api->batchQueryPlayerBalance(null);
		return $rlt;
	}

	private function testQueryForwardGame() {
		$playerName = $this->playerName;
		$extra = array(
				'game_code' => 1003,
				'game_mode' => 'DolphinGold',
				'game_type' => 0,
		);
		//$extra['extra']['deviceType'] = 'web';

		$rlt = $this->api->queryForwardGame($playerName, $extra);

		print_r($rlt);exit;

		return $rlt;
	}

	private function testSyncOriginalGameLogs() {
		// echo"terst";exit();
		$token 			= 'abc123';
		$dateTimeFrom 	= new DateTime(date('2017-10-05 11:10:00'));
		$dateTimeTo 	= new DateTime(date('2017-10-05 11:20:00'));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		print_r($rlt);
		exit;

		echo"<pre>";print_r($rlt);exit();
		return $rlt;
	}

	private function testSyncMergeToGameLogs() {

		$token 			= 'abc123';
		$dateTimeFrom 	= new DateTime(date('2016-05-01 16:20:00'));
		$dateTimeTo 	= new DateTime(date('2016-05-29 16:30:00'));

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		return $rlt;
	}


}
