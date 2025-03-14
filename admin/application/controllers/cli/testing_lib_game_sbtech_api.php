<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_sbtech_api extends BaseTesting {

    private $platformCode = SBTECH_API;
    private $api = null;

    private $player_name = "testzai01";
    private $password = "pass123";
    private $player_id = 2;
    private $amount = 1;

    public function init() {
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);

        $this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
    }

    public function testAll() {
        $this->init();
    }

    public function testTarget($methodName) {
        $this->init();
        $this->$methodName();
    }

    public function getPlayerAuthToken() {
        $playerName='testauto';
        $rlt = $this->api->getPlayerAuthToken($playerName);
        echo '<pre>';
        print_r($rlt);
    }

    private function testCreatePlayer() {

        $rlt = $this->api->createPlayer($this->player_name,$this->player_id, $this->password, null);

        print_r($rlt);
    }

    private function testChangePassword() {
        $oldPassword = $this->password;
        $newPassword = 'pass123';

        $res = $this->api->changePassword($this->player_name, $oldPassword, $newPassword);
        print_r($res);
    }


    private function testQueryPlayerBalance() {
        $rlt = $this->api->queryPlayerBalance($this->player_name);

        print_r($rlt);
    }

    private function testQueryBatchPlayerBalance() {
        $rlt = $this->api->batchQueryPlayerBalance('',random_string('numeric'));

        print_r($rlt);
    }

    private function testLogin() {
        $extra = array();
        $rlt = $this->api->login($this->player_name,$extra);

        print_r($rlt);
    }

    private function testDeposit() {

        $rlt = $this->api->depositToGame($this->player_name, $this->amount);

        print_r($rlt);
    }

    private function testWithdraw() {
        $rlt = $this->api->withdrawFromGame($this->player_name, $this->amount);

        print_r($rlt);
    }

    private function testQueryForwardGame(){
        $extra = array();
        $rlt = $this->api->queryForwardGame($this->player_name,$extra);

        print_r($rlt);
    }

    private function testSyncGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2018-05-13 04:00:00');
        $dateTimeTo = new DateTime('2018-05-13 04:59:59');

        # 2018-05-13 04:51:59

        // try unsettled
       # $dateTimeFrom = new DateTime('2018-02-09 18:00:00');
       # $dateTimeTo = new DateTime('2018-02-09 18:59:59');

        $this->api->syncInfo[$token] = array("playerName" => $this->player_name, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);

        print_r($rlt);
    }

    private function testSyncMergeToGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2018-05-16 11:03:29');
        $dateTimeTo = new DateTime('2018-05-16 12:03:29');

        $this->api->syncInfo[$token] = array("playerName" => $this->player_name, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);

        print_r($rlt);
    }

    // too slow, ask game provider to update
    private function testSyncOpenBets() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2018-02-15 00:00:00');
        $dateTimeTo = new DateTime('2018-02-18 23:59:59');

        $this->api->syncInfo[$token] = array("playerName" => $this->player_name, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOpenBets($token);

        print_r($rlt);
    }


    private function testIsPlayerExist() {
        $rlt = $this->api->isPlayerExist($this->player_name);

        print_r($rlt);
        exit;
    }
}