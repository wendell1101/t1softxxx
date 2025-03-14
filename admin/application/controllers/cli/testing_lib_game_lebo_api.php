<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_lebo_api extends BaseTesting {

	public $platformCode = LEBO_GAME_API;
	public $testUser = 'test002';
	public $api = null;

	public function init() {
        $this->load->model('game_provider_auth');
        $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
        $this->test($this->game_platform_manager == null, false, 'init game platform manager');
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
        $this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
    }

	public function testAll() {
		$this->init();

		$this->testCreatePlayer();
		$this->testLogin();
		$this->testIsPlayerExist();
		$this->testBlockPlayer();
		$this->testUnblockPlayer();
		// $this->testChangePassword(); // game api do not provide
		$this->testQueryPlayerBalance();
		$this->testBatchQueryPlayerBalance();
		$this->testDepositToGame();
		$this->testWithdrawFromGame();
		//$this->testQueryForwardGame();
		//$this->testLogout();
		$this->testSyncOriginalGameLogs();
		$this->testSyncMergeToGameLogs();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

    public function dump($mixed = null) {
        ob_start();
        var_dump($mixed);
        $content = ob_get_contents();
        ob_end_clean();
        $content = sprintf( "<pre>%s</pre>", $content );
        return $content;
    }

	private function testCreatePlayer() {
		$res = $this->api->createPlayer($this->testUser, $playerId, $password, null, $extra);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testIsPlayerExist() {
		$res = $this->api->isPlayerExist($this->testUser);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testChangePassword() {
		$gameUsername = $this->api->getGameUsernameByPlayerUsername($this->testUser);
        $origPassword = $this->api->getPasswordByGameUsername($gameUsername);
        $newPassword = "test".$origPassword;

		$res = $this->api->changePassword($this->testUser, $origPassword, $newPassword);
		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );

		$res = $this->api->changePassword($this->testUser, $newPassword, $origPassword);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testBlockPlayer() {
		$res = $this->api->blockPlayer($this->testUser);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testUnblockPlayer() {
		$res = $this->api->unblockPlayer($this->testUser);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testQueryPlayerBalance() {
		$res = $this->api->queryPlayerBalance($this->testUser);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

    private function testSyncGameLogs(){
        // $this->testSyncOriginalGameLogs();
        $this->testSyncMergeToGameLogs();
    }

	private function testSyncOriginalGameLogs() {
		$token 			= 'LEBO';
		$dateTimeFrom 	= (new Datetime('today'));
		$dateTimeTo 	= (new Datetime('today'))->modify("+ 1 day - 1 sec");

        $this->api->syncInfo[$token] = array(
                "dateTimeFrom" => $dateTimeFrom,
                "dateTimeTo" => $dateTimeTo
                );

		$res = $this->api->syncOriginalGameLogs($token);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testSyncMergeToGameLogs() {
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2018-03-05 20:00:00');
		$dateTimeTo = new DateTime('2018-03-05 20:59:59');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		print_r($rlt);exit();
		// $token = 'LEBO';
		// $dateTimeFrom 	= (new Datetime('today'));
		// $dateTimeTo 	= (new Datetime('today'))->modify("+ 1 day - 1 sec");

  //       $this->api->syncInfo[$token] = array(
  //               "dateTimeFrom" => $dateTimeFrom,
  //               "dateTimeTo" => $dateTimeTo
  //               );

		// $res = $this->api->syncMergeToGameLogs($token);
		// $this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testBatchQueryPlayerBalance(){
		$testUsers = array($this->testUser);
		$res = $this->api->batchQueryPlayerBalance($testUsers);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

	private function testLogin() {
		$res = $this->api->login($this->testUser);

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
	}

    private function testDepositToGame(){
        $testAmount = "3.99";
        $res = $this->api->depositToGame( $this->testUser, $testAmount );

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }

    private function testWithdrawFromGame(){
        $testAmount = "3.99";
        $res = $this->api->withdrawFromGame( $this->testUser, $testAmount );

		$this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }
}
