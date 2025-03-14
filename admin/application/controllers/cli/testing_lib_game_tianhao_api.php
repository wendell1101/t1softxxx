<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_tianhao_api extends BaseTesting {

    private $platformCode   = TIANHAO_API;
    private $platformName   = 'TIANHAO';
    private $api            = null;
    private $test_player_username    = 'test002';
    private $amount         = 1.02;

    public function init() {
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
    }

    ## all tests route through this function
    public function testTarget($methodName) {
        $this->init();
        $this->$methodName();
    }

    # Actual Tests

    ## Invokes all tests defined below. A test function's name should begin with 'test'
    public function testAll() {
        $classMethods = get_class_methods($this);
        $excludeMethods = array('test', 'testTarget', 'testAll');
        foreach ($classMethods as $method) {
            if (strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
                continue;
            }

            $this->$method();
        }
    }

    private function testAesCrypter() {
        $crypter = new AesCrypter('909ed2d5fcf907c79fb9aa341a98febb65291c39');
        $encryptedStr = $crypter->encrypt('AABBCC测试数据');
        $this->test($encryptedStr, 'noMrTUS2A0YTcYaaPQSy9peqF6Mv/faMkI4yYHDvKjw=', "Encrypted string must match expected");
    }

    private function testCreatePlayer() {
        $username = $this->test_player_username;
        $playerId = 112;

        $this->utils->debug_log("Testing createPlayer [$username] for [$this->platformName]");
        $result = $this->api->createPlayer($username, $playerId, '');
        $this->test($result['success'], true, "createPlayer [$username] for [$this->platformName]");
    }

    private function testQueryForwardGame() {
        $this->utils->debug_log("Testing queryForwardGame for existing player [$this->test_player_username] for [$this->platformName]");
        $url = $this->api->queryForwardGame($this->test_player_username);
        $this->test(!empty($url), true, "queryForwardGame for existing player [$this->test_player_username] for [$this->platformName]");
        $this->test(strpos($url, $this->test_player_username) > 0, true, "that URL must contain the player's username [$this->test_player_username] for [$this->platformName]");
    }

    private function testIsPlayerExist() {
        $username = 'non-existent';
        $this->utils->debug_log("Testing existence of [$username] for [$this->platformName]");
        $player_exists = $this->api->isPlayerExist($username);
        $this->utils->debug_log("\$player_exists", $player_exists);
        $this->test($player_exists['success'], true, "Testing existence of [$username] for [$this->platformName] should success");
        $this->test($player_exists['exists'], false, "Testing existence of [$username] for [$this->platformName] should successfully return not exist");

        $username = $this->test_player_username;
        $this->utils->debug_log("Testing existence of [$username] for [$this->platformName]");
        $player_exists = $this->api->isPlayerExist($username);
        $this->utils->debug_log("\$player_exists", $player_exists);
        $this->test($player_exists['success'], true, "Testing existence of [$username] for [$this->platformName] should success");
        $this->test($player_exists['exists'], true, "Testing existence of [$username] for [$this->platformName] should successfully return exist");
    }

    private function testQueryPlayerBalance() {
        $this->utils->debug_log("Testing queryPlayerBalance for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->queryPlayerBalance($this->test_player_username);
        $this->test($result['success'], true, "queryPlayerBalance for existing player [$this->test_player_username] for [$this->platformName] should success");
        $this->test(array_key_exists('balance', $result) && $result['balance'] >= 0, true, "queryPlayerBalance for existing player [$this->test_player_username] for [$this->platformName] should return a numeric non-negative balance value");
    }

    private function testDepositToGame() {
        $amount = 3;
        $this->utils->debug_log("Testing depositToGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->depositToGame($this->test_player_username, $amount, 'T'.uniqid());
        $this->test($result['success'], true, "depositToGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should success");
    }

    private function testWithdrawFromGame() {
        $amount = 9999999;
        $this->utils->debug_log("Testing withdrawFromGame of big amount [$amount] for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->withdrawFromGame($this->test_player_username, $amount, 'T'.uniqid());
        $this->test($result['success'], false, "withdrawFromGame of big amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should fail");

        $amount = 3;
        $this->utils->debug_log("Testing withdrawFromGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->withdrawFromGame($this->test_player_username, $amount, 'T'.uniqid());
        $this->test($result['success'], true, "withdrawFromGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should success");
    }

    private function testQueryTransaction() {
        $validTransId = 'T5b858a8792bbc';
        $invalidTransId = 'T_invalid_transid';
        $this->utils->debug_log("Testing query of transaction status for [$validTransId]");
        $result = $this->api->queryTransaction($validTransId, array('playerName' => $this->test_player_username));
        $this->test($result['success'], true, "query of transaction status for [$validTransId] should success");
        $this->test($result['status'], 'approved', "query of transaction status for [$validTransId] should return 'approved'");

        $this->utils->debug_log("Testing query of transaction status for [$invalidTransId]");
        $result = $this->api->queryTransaction($invalidTransId, array('playerName' => $this->test_player_username));
        $this->test($result['success'], true, "query of transaction status for [$invalidTransId] should success");
        $this->test($result['status'], 'unknown', "query of transaction status for [$invalidTransId] should return 'unknown'");
    }

    private function testSyncGameLogs() {
        $dateTimeFrom = new DateTime('2018-08-23 16:00:00');
        $dateTimeTo = new DateTime('2018-08-23 17:00:00');
        $token = uniqid();

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $this->utils->debug_log("syncGameLogs for [$this->platformName] with token [$token], syncInfo: ", $this->api->syncInfo[$token]);

        $rlt = $this->api->syncOriginalGameLogs($token);
        $this->test($rlt['success'], true, "syncOriginalGameLogs for [$this->platformName] should succeed");

        $rlt = $this->api->syncMergeToGameLogs($token);
        $this->test($rlt['success'], true, "syncMergeToGameLogs for [$this->platformName] should succeed");
    }

}