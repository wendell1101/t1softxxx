<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebet_ag_api extends BaseTesting {

    private $platformCode = EBET_AG_API;
    private $api = null;

    private $player_name = "testzai";
    private $password = "pass123";
    private $player_id = 1;
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

    private function testCreatePlayer() {

        $rlt = $this->api->createPlayer($this->player_name,$this->player_id, $this->password, null);

        print_r($rlt);
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
        $dateTimeFrom = new DateTime('2017-10-16 01:43:01');
        $dateTimeTo = new DateTime('2017-10-16 16:59:59');

        $this->api->syncInfo[$token] = array("playerName" => $this->player_name, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);

        print_r($rlt);
    }

    private function testSyncMergeToGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-10-16 01:00:01');
        $dateTimeTo = new DateTime('2017-10-16 15:00:00');

        $this->api->syncInfo[$token] = array("playerName" => $this->player_name, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
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