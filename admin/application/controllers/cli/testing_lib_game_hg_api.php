<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_hg_api extends BaseTesting {

    private $platformCode = HG_API;
    private $api = null;

    private $player_name = "testf006";
    private $password = "pass123";
    private $player_id = 56970;
    private $amount = 50;
    private $userId = 1004;

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

    private function testCreatePlayer() {

        $rlt = $this->api->createPlayer($this->player_name,$this->player_id, $this->password, null);

        print_r($rlt);
    }


    private function testQueryPlayerBalance() {
        $rlt = $this->api->queryPlayerBalance($this->player_name);

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
        $dateTimeFrom = new DateTime('2018-08-24 00:30:01');
        $dateTimeTo = new DateTime('2018-08-24 23:59:59');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);

        print_r($rlt);
    }

    private function testSyncMergeToGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2018-08-23 00:30:01');
        $dateTimeTo = new DateTime('2018-08-23 23:59:59');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);

        print_r($rlt);
        exit;
    }

    private function testIsPlayerExist() {
        $rlt = $this->api->isPlayerExist($this->player_name);

        print_r($rlt);
        exit;
    }
}