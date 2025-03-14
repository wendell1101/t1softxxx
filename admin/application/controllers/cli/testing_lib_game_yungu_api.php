<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_yungu_api extends BaseTesting {

    public $platformCode = YUNGU_GAME_API;
    public $testUser = 'testegg01';
    public $api = null;

    public $password = "123456";  //  "R0uY5JT9kPwf";
    public $player_id = 10441;
    public $amount = 1;

    public function init() {
        $this->load->model('game_provider_auth');
        $this->load->library('game_platform/game_platform_manager', array("platform_code" => $this->platformCode));
        $this->test($this->game_platform_manager == null, false, 'init game platform manager');
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
        $this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
    }

    public function testAll() {
        $this->init();

        $this->testLogin();
        $this->testCreatePlayer();
        $this->testIsPlayerExist();
        $this->testChangePassword();
        $this->testBlockPlayer();
        $this->testUnblockPlayer();

        $this->testQueryPlayerBalance();
        $this->testBatchQueryPlayerBalance();
        $this->testDepositToGame();
        $this->testWithdrawFromGame();

        $this->testSyncOriginalGameLogs();
        $this->testSyncMergeToGameLogs();

        $this->testQueryForwardGame();
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
        $res = $this->api->createPlayer($this->testUser, $this->player_id, $this->password, null, array());
        print_r($res);
    }

    private function testQueryPlayerBalance() {
        $res = $this->api->queryPlayerBalance($this->testUser);
        print_r($res);
    }

    private function testDepositToGame() {
        $res = $this->api->depositToGame($this->testUser, $this->amount);
        print_r($res);
    }

    private function testWithdrawFromGame() {
        $res = $this->api->withdrawFromGame($this->testUser, $this->amount );
        print_r($res);
    }

    private function testLogin() {
        $res = $this->api->login($this->testUser);
        print_r($res);
    }

    private function testIsPlayerExist() {
        $res = $this->api->isPlayerExist($this->testUser);
        print_r($res);
    }

    private function testChangePassword() {
        $oldPassword = $this->password;
        $newPassword = 'pass12345';

        $res = $this->api->changePassword($this->testUser, $oldPassword, $newPassword);
        print_r($res);
    }

    private function testBlockPlayer() {
        $res = $this->api->blockPlayer($this->testUser);
        $this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }

    private function testUnblockPlayer() {
        $res = $this->api->unblockPlayer($this->testUser);
        $this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }

    private function testBatchQueryPlayerBalance(){
        $testUsers = array($this->testUser);
        $res = $this->api->batchQueryPlayerBalance($testUsers);

        $this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }



    private function testSyncGameLogs(){
        $this->testSyncOriginalGameLogs();
        $this->testSyncMergeToGameLogs();
    }

    private function testSyncOriginalGameLogs() {
        $token 			= uniqid();
        $dateTimeFrom 	= (new Datetime('today -1 day '));
        $dateTimeTo 	= (new Datetime('today'))->modify("+ 1 day - 1 sec");

        $this->api->syncInfo[$token] = array(
                "dateTimeFrom" => $dateTimeFrom,
                "dateTimeTo"   => $dateTimeTo
                );

        $res = $this->api->syncOriginalGameLogs($token);

        $this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }

    private function testSyncMergeToGameLogs() {
        $token 			= uniqid();
        $dateTimeFrom 	= (new Datetime('today -1 day'));
        $dateTimeTo 	= (new Datetime('today'))->modify("+ 1 day - 1 sec");

        $this->api->syncInfo[$token] = array(
                "dateTimeFrom" => $dateTimeFrom,
                "dateTimeTo"   => $dateTimeTo
                );

        $res = $this->api->syncMergeToGameLogs($token);
        $this->test($res['success'], true, __METHOD__ , $this->dump( $res, true ) );
    }

    private function testQueryForwardGame() {
        #$extras = array( "game_code" => "" , "game_mode" => "real"  , "mobile" => "true" );
        $extras = array( "game_code" => "" , "game_mode" => "real"  , "mobile" => "" );

        $res = $this->api->queryForwardGame($this->testUser, $extras );
        print_r($res);
    }
}