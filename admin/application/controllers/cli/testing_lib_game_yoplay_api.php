<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_yoplay_api extends BaseTesting {

    private $platformCode = YOPLAY_API;
    private $platformName = 'YOPLAY_API';
    private $api = null;

    public function init() {
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
        $this->test($this->api->getPlatformCode() == $this->platformCode, true, 'init api by ' . $this->platformCode);
    }

    public function testAll() {
        $this->init();
        // $this->testIsPlayerExist();
        $this->testCreatePlayer();
        // $this->testqueryPlayerBalance();
        // $this->testDeposit();

        // $this->testPlayerLogin();
        // $this->testWithdraw();
        // $this->testQueryForwardGame();

        // $this->testSyncGameLogs();
        // $this->testSyncMergeToGameLogs();
        //$this->testCreateAndLogin();
    }

    private function testSyncGameLogs() {
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-08-10 14:25:00');
        $dateTimeTo = new DateTime('2017-08-10 14:35:00');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);
        echo "<pre>";print_r($rlt);
    }

    private function testSyncMergeToGameLogs() {
        //$api = $this->game_platform_manager->initApi($this->platformCode);
        $token = 'abc123';
        $dateTimeFrom = new DateTime('2017-08-10 14:25:00');
        $dateTimeTo = new DateTime('2017-08-10 14:35:00');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testTarget($methodName) {
        $this->init();
        $this->$methodName();
    }

    private function testIsPlayerExist() {
        $username =  'testshu1';
        $rlt = $this->api->isPlayerExist($username);
        echo "<pre>";print_r($rlt);exit;
    }

    private function testCreatePlayer() {
        $playerName = 'testgray';
        $password = '123456';
        $playerId = 12;
        $rlt = $this->api->createPlayer($playerName, $playerId, $password, null);
        echo "<pre>";print_r($rlt);exit;
    }
    private function testDeposit() {
        $playerName = 'testshu';
        $depositAmount = 1;
        $rlt = $this->api->depositToGame($playerName, $depositAmount);
        echo "<pre>";print_r($rlt);exit;
    }

    public function testQueryPlayerBalance() {
        $playerName = 'testshu1';

        $rlt = $this->api->queryPlayerBalance($playerName);
        echo "<pre>";print_r($rlt);exit;
    }

    private function testBatchQueryPlayerBalance() {
        $rlt = $this->api->batchQueryPlayerBalance(null);
        $this->utils->debug_log($rlt);
        $this->test($rlt['success'], true, ' testBatchQueryPlayerBalance for ' . $this->platformName);
    }

    private function testGameLogs() {
        $rowId = '16103653263';
        $rlt = $this->api->convertGameRecordsToFile($rowId);

    }

    private function testPlayerLogin() {
        $playerName = 'test123456789012l234561test';
        $password = 'pass123';
        $rlt = $this->api->login($playerName, $password);
        $this->test($rlt['success'], true, $playerName . ' login for ' . $this->platformName);
        $this->utils->debug_log('=====result of login: ' . $playerName . '======================================================', $rlt);
    }

    private function testWithdraw() {
        $playerName = 'testYOPLAY_APIx6';
        $withdrawAmount = -1;
        $rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
        $this->utils->debug_log('withdrawToGame', $rlt);
        $this->test($rlt['success'], true, 'Test Player Withdraw to YOPLAY_API');
        $this->test($rlt['currentplayerbalance'], 10, 'Current Balance after withdrawal');
    }

    private function testQueryForwardGame() {
        $playerName = 'test123456789012l23test';
        $param = array(
            "game_code" => "123",
            "game_mode" => "true",
            "is_mobile_flag" => "false",
        );
        $rlt = $this->api->queryForwardGame($playerName, $param);
        var_dump($rlt);exit();
        $this->test($rlt['success'], true, 'Test syncOriginalGameLogs to YOPLAY_API');
    }
}