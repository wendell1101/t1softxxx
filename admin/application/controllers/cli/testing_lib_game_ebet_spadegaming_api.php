<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_ebet_spadegaming_api extends BaseTesting {

    private $platformCode = EBET_SPADE_GAMING_API;
    private $platformName = 'EBET_SPADE_GAMING_API';

    private $api = null;


    public function init() {

        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);


    }

    public function testAll() {

       $this->init();
       // $this->testCreatePlayer();
       // $this->testIsPlayerExist();
       // $this->testQueryPlayerBalance();
       // $this->testDeposit();
       // $this->testWithdraw();
       $this->testQueryForwardGame();
       // $this->testChangePassword();
       // $this->testBlockPlayer();
       // $this->testUnblockPlayer();
       // $this->testSyncGameLogs();
       // $this->testIsPlayerExist();
       // $this->testSyncMergeToGameLogs();
       // $this->testLogout();
       // $this->testEncrypt();


    }
    private function testSyncGameLogs() {

        $token = 'abc123d';
        $dateTimeFrom = new DateTime('2017-05-01 00:00:00');
        $dateTimeTo = new DateTime('2017-05-01 23:59:59');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);

        // echo "<pre>";print_r($rlt);exit;
    }

    private function testSyncMergeToGameLogs() {
        //$api = $this->game_platform_manager->initApi($this->platformCode);
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-04-25 00:00:00');
        $dateTimeTo = new DateTime('2017-04-25 23:59:59');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);
        // echo "<pre>";print_r($rlt);exit;
    }

    private function testIsPlayerExist() {

        $username =  'aatestgray';
        $rlt = $this->api->isPlayerExist($username);
        var_dump($rlt);

    }

    private function testCreatePlayer() {

        $username = 'testgreen';
        $password = '123456';
        $player_id = '28';

        $rlt = $this->api->createPlayer($username,$player_id,$password);
        echo "<pre>";print_r($rlt);exit;

    }
    private function testDeposit() {

        $playerName = 'testgray';
        $depositAmount = 1;
        $rlt = $this->api->depositToGame($playerName, $depositAmount);
        echo "<pre>";print_r($rlt);exit;

    }

    public function testQueryPlayerBalance() {

        $playerName = 'aatestgray';
        $rlt = $this->api->queryPlayerBalance($playerName);
        echo "<pre>";print_r($rlt);exit;

    }

    private function testWithdraw() {
        $playerName = 'testgray';
        $withdrawAmount = 1;
        $rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
        echo "<pre>";print_r($rlt);exit;

    }

    private function testQueryForwardGame(){
        $username =  'testgray';
        $extra = [
            "lang"      => "en_US",
            "game_code" => "S-DG02",
            "is_mobile" => "false",
            "menumode"  => "on"
        ];
        $rlt = $this->api->queryForwardGame($username,$extra);
        echo "<pre>";print_r($rlt);exit;
    }

    private function testBlockPlayer(){
        $username =  'testshu';
        $rlt = $this->api->blockPlayer($username);
        echo "<pre>";print_r($rlt);exit;
    }

    private function testUnblockPlayer(){
        $username =  'testshu';
        $rlt = $this->api->unblockPlayer($username);
        echo "<pre>";print_r($rlt);exit;
    }



}