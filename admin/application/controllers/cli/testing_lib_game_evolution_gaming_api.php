<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_evolution_gaming_api extends BaseTesting {

    private $platformCode = EVOLUTION_GAMING_API;
    private $api = null;

    public function init() {

        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);

        $this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
    }

    public function testAll() {
        $this->init();
        #$this->testCreatePlayer();
    }

    public function testTarget($methodName) {
        $this->init();
        $this->$methodName();
    }

    private function testCreatePlayer() {
//        $username = 'testmer' . random_string('numeric');
//        $password = 'pass123';
//        $player = $this->getFirstPlayer($username);
//
//        $rlt = $this->api->createPlayer($username, $player->playerId, $password, null);

        $username = 'testevo123';
        $playerId = 1318;
        $password = 'pass123';

        $rlt = $this->api->createPlayer($username,$playerId, $password, null);

        print_r($rlt);
    }


    private function testQueryPlayerBalance() {
        $playerName = 'testevo123';
        $rlt = $this->api->queryPlayerBalance($playerName);

        print_r($rlt);
    }

    private function testLogin() {
        $username =  'testevo123';
        $extra = array();
        $rlt = $this->api->login($username,$extra);

        print_r($rlt);
    }

    private function testDeposit() {

        $playerName = 'testevo123';
        $depositAmount = 1;
        $rlt = $this->api->depositToGame($playerName, $depositAmount);

        print_r($rlt);
    }

    private function testWithdraw() {
        $playerName = 'testevo123';
        $withdrawAmount = 1;
        $rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);

        print_r($rlt);
    }

    private function testQueryForwardGame(){
        $playerName = 'testevo123';
        $extra = array();
        $rlt = $this->api->queryForwardGame($playerName,$extra);

        print_r($rlt);
    }

    private function testSyncMergeToGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-12-05 00:00:00');
        $dateTimeTo = new DateTime('2017-12-09 23:59:59');

        $playerName = 'testevo123';

        $this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);

        print_r($rlt);
        exit;

    }

    private function testIsPlayerExist() {

        $username =  'testevo1235';
        $rlt = $this->api->isPlayerExist($username);
        echo "<pre>";print_r($rlt);exit;

    }
}