<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_asialong_api extends BaseTesting {

    private $platformCode   = ASIALONG_API;
    private $platformName   = 'ASIALONG';
    private $api            = null;
    private $test_player_username    = null;
    private $amount         = 1.02;

    public function init() {
        $this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);
        $this->test_player_username = 'test_asialong'; # Corresponding gameUsername = LBBTEST_FSXRHXWH
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

    ## https://<server_url>/CREATE_USER
    private function testCreatePlayer() {
        $username = 'test_' . random_string('alnum');
        $password = '123456';
        $playerId = 2;

        $this->utils->debug_log("Testing createPlayer [$username] for [$this->platformName]");
        $result = $this->api->createPlayer($username, $playerId, $password);
        $this->test($result['success'], true, "createPlayer [$username] for [$this->platformName]");
    }

    ## https://<server_url>/REQUEST_TOKEN
    private function testQueryForwardGame() {
        $username = 'test_' . random_string('alnum');
        $this->utils->debug_log("Testing request token for non-existing player [$username] for [$this->platformName]");
        $url = $this->api->queryForwardGame($username);
        $this->test($url, '', "Testing request token for non-existing player [$username] for [$this->platformName]");

        $this->utils->debug_log("Testing request token for existing player [$this->test_player_username] for [$this->platformName]");
        $url = $this->api->queryForwardGame($this->test_player_username);
        $this->test(!empty($url), true, "request token for existing player [$this->test_player_username] for [$this->platformName]");
        $this->test(strpos($url, 'token') > 0, true, "that URL must contain token for existing player [$this->test_player_username] for [$this->platformName]");
    }

    private function testIsPlayerExist() {
        $username = 'test_' . random_string('alnum');
        $this->utils->debug_log("Testing existence of [$username] for [$this->platformName]");
        $player_exists = $this->api->isPlayerExist($username);
        $this->utils->debug_log("\$player_exists", $player_exists);
        $this->test($player_exists['success'], true, "Testing existence of [$username] for [$this->platformName] should success");
        $this->test($player_exists['exists'], false, "Testing existence of [$username] for [$this->platformName] should successfully return not-exist");
    }

    ## https://<server_url>/GET_CREDIT
    private function testQueryPlayerBalance() {
        # Our code will throw error when trying to get game user for a non-existing account
        #$username = 'test_' . random_string('alnum');
        #$this->utils->debug_log("Testing queryPlayerBalance for non-existing player [$username] for [$this->platformName]");
        #$result = $this->api->queryPlayerBalance($username);
        #$this->test($result['success'], false, "Testing queryPlayerBalance for non-existing player [$username] for [$this->platformName] should fail");

        $this->utils->debug_log("Testing queryPlayerBalance for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->queryPlayerBalance($this->test_player_username);
        $this->test($result['success'], true, "queryPlayerBalance for existing player [$this->test_player_username] for [$this->platformName] should success");
        $this->test(array_key_exists('balance', $result) && $result['balance'] >= 0, true, "queryPlayerBalance for existing player [$this->test_player_username] for [$this->platformName] should return a numeric non-negative balance value");
    }

    ## https://<server_url>/TRANSFER_CREDIT
    private function testDepositToGame() {
        $amount = 1.2;
        $this->utils->debug_log("Testing depositToGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->depositToGame($this->test_player_username, $amount);
        $this->test($result['success'], true, "depositToGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should success");
        $this->test(array_key_exists('credit', $result) && $result['credit'] >= $amount, true, "depositToGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should return the balance");
    }

    ## https://<server_url>/TRANSFER_CREDIT
    private function testWithdrawFromGame() {
        $amount = 9999999;
        $this->utils->debug_log("Testing withdrawFromGame of big amount [$amount] for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->withdrawFromGame($this->test_player_username, $amount);
        $this->test($result['success'], false, "withdrawFromGame of big amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should fail");

        $amount = 1.2;
        $this->utils->debug_log("Testing withdrawFromGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName]");
        $result = $this->api->withdrawFromGame($this->test_player_username, $amount);
        $this->test($result['success'], true, "withdrawFromGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should success");
        $this->test(array_key_exists('credit', $result) && $result['credit'] >= 0, true, "withdrawFromGame of amount [$amount] for existing player [$this->test_player_username] for [$this->platformName] should return the balance");
    }

    ## https://<server_url>/GET_REPORT
    private function testSyncGameLogs() {
        $dateTimeFrom = new DateTime('2018-04-25 13:00:00');
        $dateTimeTo = new DateTime('2018-04-25 13:30:00');
        $token = uniqid();

        $this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
        $this->utils->debug_log("syncGameLogs for [$this->platformName] with token [$token], syncInfo: ", $this->api->syncInfo[$token]);

        $rlt = $this->api->syncOriginalGameLogs($token);
        $this->test($rlt['success'], true, "syncOriginalGameLogs for [$this->platformName] should succeed");

        $rlt = $this->api->syncMergeToGameLogs($token);
        $this->test($rlt['success'], true, "syncMergeToGameLogs for [$this->platformName] should succeed");
    }

}