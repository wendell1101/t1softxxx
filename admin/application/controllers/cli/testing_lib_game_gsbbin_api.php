<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_gsbbin_api extends BaseTesting {

    private $platformCode = GSBBIN_API;
    private $api = null;

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
        $playerName = 'testgsbbin';
        $playerId = 56965;
        $password = 'pass123';

        $rlt = $this->api->createPlayer($playerName,$playerId, $password, null);

        print_r($rlt);
    }


    private function testQueryPlayerBalance() {
        $playerName = 'testgsbbin';

        $rlt = $this->api->queryPlayerBalance($playerName);

        print_r($rlt);
    }

    private function testLogin() {
        $playerName =  'testgsbbin';

        $rlt = $this->api->login($playerName);

        print_r($rlt);
    }

    private function testLogout() {
        $playerName =  'testgsbbin';

        $rlt = $this->api->logout($playerName);
        return $rlt;
    }

    private function testDeposit() {

        $playerName = 'testgsbbin';

        $depositAmount = 1;
        $rlt = $this->api->depositToGame($playerName, $depositAmount);

        print_r($rlt);
    }

    private function testWithdraw() {
        $playerName = 'testgsbbin';
        $withdrawAmount = 1;
        $rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);

        print_r($rlt);
    }

    private function testQueryForwardGame(){
        $playerName = 'testgsbbin';
        $extra = array();
        $rlt = $this->api->queryForwardGame($playerName,$extra);

        print_r($rlt);
    }

    private function testSyncGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-09-07 13:00:00');
        $dateTimeTo = new DateTime('2017-09-07 23:59:59');

        $playerName = 'testgsbbin';

        $this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);

        print_r($rlt);
    }

    private function testSyncMergeToGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-09-07 13:00:00');
        $dateTimeTo = new DateTime('2017-09-07 23:59:59');

        $playerName = 'testgsbbin';

        $this->api->syncInfo[$token] = array("playerName" => $playerName, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);

        print_r($rlt);
        exit;

    }

    private function testIsPlayerExist() {

        $playerName = 'testgsbbin';
        $rlt = $this->api->isPlayerExist($playerName);

        print_r($rlt);
        exit;
    }

    private function testChangePassword() {

        $playerName = 'testgsbbin';
        $pass = 'pass123';
        $new_pass = 'pass123';
        $rlt = $this->api->changePassword($playerName, $pass, $new_pass);

        print_r($rlt);
        exit;
    }
}