<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_t1oneworks_api extends BaseTesting {

    private $platformCode = T1ONEWORKS_API;
    private $api;

    public function init() {
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
    }

    public function testTarget($methodName) {
        $this->init();
        $this->$methodName();
    }

    public function testCreatePlayer() {
        $playerName = 'testzai';
        $password = 'pass123';
        $playerId = 1;
        $rlt = $this->api->createPlayer($playerName, $playerId, $password);
        print_r($rlt);exit;
    }

    public function testDepositToGame() {
        $playerName = 'testzai';
        $depositAmount = 1;
        $rlt = $this->api->depositToGame($playerName, $depositAmount);
        print_r($rlt);exit;
    }

    public function testAll(){
        $this->init();
        $this->testSyncMergeToGameLogs();
    }

    public function testBatchQueryPlayerBalance() {
        $players = array('testgame1','testgame2');
        $rlt = $this->api->BatchQueryPlayerBalance($players);
        print_r($rlt);exit;
    }

    public function testGenerateToken() {
        $rlt = $this->api->generateToken(true);
        print_r($rlt);exit;
    }

    public function testUpdatePlayerInfo() {
        $playerName = 'testnew';
        $rlt = $this->api->updatePlayerInfo($playerName);
        print_r($rlt);exit;
    }

    public function testQueryTransaction() {
        $extId = 'devtestzai180704174520';

        $extra = [
            'playerName' => 'devtestzai',
            'playerId' => 1
        ];

        $rlt = $this->api->queryTransaction($extId, $extra);
        print_r($rlt);exit;
    }

    public function testQueryPlayerInfo() {
        $playerName = 'testnew';
        $rlt = $this->api->queryPlayerInfo($playerName);
        print_r($rlt);exit;
    }

    public function testIsPlayerExist() {

        $playerName = 'testnew';
        $rlt = $this->api->isPlayerExist($playerName);
        print_r($rlt);exit;
    }

    public function testChangePassword(){
        $playerName = 'testnew';
        $oldPassword = 'password';
        $newPassword = 'password';
        $rlt = $this->api->changePassword($playerName, $oldPassword, $newPassword);
        print_r($rlt);exit;
    }

    public function testBlockPlayer(){
        $playerName = 'testnew';
        $rlt = $this->api->blockPlayer($playerName);
        print_r($rlt);exit;
    }

    public function testunblockPlayer(){
        $playerName = 'testnew';
        $rlt = $this->api->unblockPlayer($playerName);
        print_r($rlt);exit;
    }

    public function testQueryPlayerBalance(){
        $playerName = 'testnew';
        $rlt = $this->api->queryPlayerBalance($playerName);
        print_r($rlt);exit;
    }

    public function testWithdrawFromGame() {
        $playerName = 'testnew';
        $depositAmount = 4;
        $rlt = $this->api->withdrawFromGame($playerName, $depositAmount);
        print_r($rlt);exit;
    }

    public function testLogin() {
        $playerName = 'testnew';
        $rlt = $this->api->login($playerName);
        print_r($rlt);exit;
    }

    public function testSyncGameLogs() {
        $token = 'abc123d';
        $dateTimeFrom = new DateTime('2018-03-12 12:11:00');
        $dateTimeTo = new DateTime('2018-03-12 12:11:00');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncOriginalGameLogs($token);

        print_r($rlt);exit;
    }

    private function testSyncMergeToGameLogs() {
        //$api = $this->game_platform_manager->initApi($this->platformCode);
        $token = 'abc123d';
        $dateTimeFrom = new DateTime('2018-03-23 12:11:00');
        $dateTimeTo = new DateTime('2018-03-23 23:11:00');

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $rlt = $this->api->syncMergeToGameLogs($token);
        print_r($rlt);exit;
    }
}
