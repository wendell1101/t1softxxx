<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * AsiaLong 手中寶
 *
 * ASIALONG_API, ID: 638
 *
 * Implemented API actions:
 * * Create player
 * * Query Player Balance
 * * Balance Transfer
 * * Goto Game Lobby
 *
 * Required Fields:
 * * URL
 * * Account
 * * Secret
 * * Extra_Info
 *
 * Field Values:
 * * URL: https://mgtapi.pkqaz.xyz/
 * * Account: [client_id]
 * * Secret: [client_secret]
 * * Extra_Info:
 * ```
 * {
 *     "prefix_for_username" : "[prefix]",
 *     "lobby_url" : "[lobby_url]",
 *     "sync_time_interval" : "3600"
 * }
 * ```
 *
 * @category Game API
 *
 * @copyright 2013-2022 tot
 */
class Game_api_asialong extends Abstract_game_api {
    const URI_MAP = array(
        self::API_createPlayer => '/CREATE_USER',
        self::API_isPlayerExist => '/REQUEST_TOKEN',
        self::API_queryPlayerBalance => '/GET_CREDIT',
        self::API_transfer => '/TRANSFER_CREDIT',
        self::API_syncGameRecords => '/GET_REPORT',
    );

    public function __construct() {
        parent::__construct();
    }

    public function getPlatformCode() {
        return ASIALONG_API;
    }

    public function generateUrl($apiName, $params) {
        return $this->getSystemInfo("url").self::URI_MAP[$apiName];
    }

    # HTTP Post Control
    public function getHttpHeaders($params){
        return array("Accept" => "application/json", "Content-Type" => "application/json");
    }

    # Post params as JSON
    public function customHttpCall($ch, $params) {
        $params["sign_code"] = $this->getSignCode($params);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));
    }

    # Modify error detection logic
    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        # statusCode 422 is treated as success (returned when user does not exist)
        return $errCode || (intval($statusCode, 10) >= 400 && $statusCode != '422');
    }

    # Decide whether API call is successful. Returns a single boolean value.
    protected function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = false;
        if(isset($resultArr['message']) && $resultArr['message'] == 'OK'){
            $success = true;
        }
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('asialong got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    # Provide game launch URL
    public function queryForwardGame($playerName, $extra = array()){
        $tokenResult = $this->getPlayerToken($playerName);
        $this->utils->debug_log("Get player token result", $tokenResult);

        if(empty($tokenResult) || !$tokenResult['success']) {
            return '';
        }

        $token = $tokenResult['token'];
        $url = $this->getSystemInfo('lobby_url').'?token='.$token;

        $this->utils->debug_log("queryForwardGame URL: ", $url);
        return $url;
    }

    # Implement API function CREATE_USER
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName
        );

        $params = array(
            "method" => "CREATE_USER",
            "timestamp" => time(),
            "username" => $playerName, # Note: createPlayer takes in playerName without prefix
            "client_id" => $this->getSystemInfo("account"),
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        return array($success, $resultArr);
    }

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName
        );

        # Use REQUEST_TOKEN call to determine whether the user account exists
        $params = array(
            "method" => "REQUEST_TOKEN",
            "timestamp" => time(),
            "username" => $gameUsername,
            "client_id" => $this->getSystemInfo("account"),
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $exists = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        return array(true, array('exists' => $exists));
    }

    public function getPlayerToken($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetPlayerToken',
            'playerName' => $playerName
        );

        $params = array(
            "method" => "REQUEST_TOKEN",
            "timestamp" => time(),
            "username" => $gameUsername,
            "client_id" => $this->getSystemInfo("account"),
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForGetPlayerToken($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        if($success) {
            return array(true, array('token' => $resultArr['data']['token']));
        } else {
            return array(false);
        }
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        );

        $params = array(
            "method" => "GET_CREDIT",
            "timestamp" => time(),
            "username" => $gameUsername,
            "client_id" => $this->getSystemInfo("account"),
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if($success && array_key_exists('data', $resultArr) && array_key_exists('credit', $resultArr['data'])) {
            return array($success, array('balance' => floatval($resultArr['data']['credit'])));
        } else {
            return array(false);
        }
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null) {
        return $this->transferCredit($userName, $amount);
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        return $this->transferCredit($userName, -$amount); # negative amount means transfer out
    }

    public function transferCredit($userName, $amount) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTransferCredit',
            'playerName' => $userName
        );

        $params = array(
            "method" => "TRANSFER_CREDIT",
            "timestamp" => time(),
            "username" => $gameUsername,
            "amount" => number_format($amount, 3, '.', ''), # 3 decimal places, with no thousand separator
            "client_id" => $this->getSystemInfo("account"),
        );

        return $this->callApi(self::API_transfer, $params, $context);
    }

    public function processResultForTransferCredit($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if(array_key_exists('data', $resultArr)) {
            return array($success, $resultArr['data']);
        } else {
            return array(false);
        }
    }

    # API limitation: time span must be less than 1 hour, must wait for 1 minute between each query
    public function syncOriginalGameLogs($token = false) {
        $dateTimeFromInput = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeToInput = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $dateTimeFromInput,
            'endDate' => $dateTimeToInput,
        );

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFromInput));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeToInput));
        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $timestampFrom = $dateTimeFrom->format('U');
        $timestampTo = $dateTimeTo->format('U');

        $this->utils->debug_log("ASIALONG Game Log Sync from [$timestampFrom] to [$timestampTo]");

        $queryTimestampFrom = $timestampFrom;
        $queryTimestampTo = $timestampFrom + $this->getSystemInfo('sync_time_interval');
        $insertCount = 0;
        $loopCounter = 0;
        $maxLoop = 5; # provide fail-safe exit condition to while loop
        while ($queryTimestampFrom < $timestampTo && $loopCounter < $maxLoop) {

            $params = array(
                "method" => "GET_REPORT",
                "timestamp" => time(),
                "start_time" => $queryTimestampFrom,
                "end_time" => $queryTimestampTo,
                "result_ok" => 1, # 1:已結算
                "client_id" => $this->getSystemInfo("account"),
            );

            $apiResult = $this->callApi(self::API_syncGameRecords, $params, $context);

            $this->utils->debug_log("ASIALONG Sync from [$queryTimestampFrom] to [$queryTimestampTo], result: ", $apiResult);

            if(!$apiResult['success']) {
                break;
            } else {
                $insertCount += count($apiResult['id']);
            }

            $queryTimestampFrom = $queryTimestampTo;
            $queryTimestampTo += $this->getSystemInfo('sync_time_interval');
            $loopCounter++;
        }

        $this->utils->debug_log("Done ASIALONG Sync from [$timestampFrom] to [$timestampTo], loop count: [$loopCounter]; insert count: [$insertCount]");
        return array("success"=>true);
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('asialong_game_logs'));
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameRecords = array();
        if(array_key_exists('data', $resultArr)) {
            $gameRecords = $resultArr['data'];
        }

        if(!empty($gameRecords)){
            # add in columns not returned by API
            foreach($gameRecords as $index => $record) {
                $gameRecords[$index]['external_uniqueid'] = $record['BETID'];
                $gameRecords[$index]['response_result_id'] = $responseResultId;
            }
            $availableRows = $this->CI->asialong_game_logs->getAvailableRows($gameRecords);
            $insertIds = $this->CI->asialong_game_logs->insertBatchGameLogsReturnIds($availableRows);
            return array(true, array('id' => $insertIds));
        } else {
            return array(false);
        }
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'asialong_game_logs'));
        $dateTimeFromInput = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeToInput = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFromInput));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeToInput));
        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $dateTimeFromString = $dateTimeFrom->format('Y-m-d H:i:s');
        $dateTimeToString = $dateTimeTo->format('Y-m-d H:i:s');
        $this->CI->utils->debug_log("ASIALONG merge from [$dateTimeFromString] to [$dateTimeToString]");
        $result = $this->CI->asialong_game_logs->getGameLogStatistics($dateTimeFrom, $dateTimeTo);

        $mergeCount = 0;
        if (!empty($result)) {
            $unknownGame = $this->getUnknownGame();

            foreach ($result as $row) {
                $player_id = $this->getPlayerId($row['username']);
                if (!$player_id) {
                    $this->utils->debug_log("ASIALONG merge: Player ID not found for username", $row['username']);
                    continue;
                }

                $game_description_id = $row['game_description_id'];
                $game_type_id = $row['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $extra = array(
                    'trans_amount' => $row['gold'],
                    'odds' => $row['ioratio'],
                    'table' => $row['periodnumber'],
                    'bet_details' => array(
                        'content' => $row['betcontent'],
                        'detail' => $row['betdetail'],
                    ),
                    'ip_address' => $row['orderip'],
                );


                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $row['game_code'],
                    $game_type_id,
                    $row['game_name'],
                    $player_id,
                    $row['username'],
                    $row['gold'], # bet amount
                    $row['wingold'], # result amount
                    null, # win_amount
                    null, # loss_amount
                    null, # after_balance
                    0, # has_both_side
                    $row['external_uniqueid'],
                    $row['adddate'], //start
                    $row['adddate'], //end
                    $row['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );

                $mergeCount++;
            }
        }

        $this->CI->utils->debug_log("Done ASIALONG merge from [$dateTimeFromString] to [$dateTimeToString], merge count: [$mergeCount]");
        return array('success' => true, 'count' => $mergeCount);
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    function login($userName, $password = null) {
        return $this->returnUnimplemented();
    }

    function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return $this->returnUnimplemented();
    }

    private function getSignCode($parameter) {
        $client_secret = $this->getSystemInfo('secret');
        if(array_key_exists('client_id', $parameter)) {
            unset($parameter['client_id']);
        }
        ksort($parameter);
        $queryString = http_build_query($parameter);
        $hmac = hash_hmac('sha1', $queryString, $client_secret, true);
        $sign = base64_encode($hmac);
        $this->utils->debug_log("getSignCode", $parameter, $queryString, $sign);
        return $sign;
    }

    private $_playerIdByUsername = array();
    private function getPlayerId($username) {
        if(!array_key_exists($username, $this->_playerIdByUsername)) {
            $this->_playerIdByUsername[$username] = $this->getPlayerIdInGameProviderAuth($username);
        }
        return $this->_playerIdByUsername[$username];
    }

}
