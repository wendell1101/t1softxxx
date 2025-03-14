<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Tiaohao Chess 天豪棋牌
 *
 * TIAOHAO_API, ID: 818
 *
 * Implemented API actions:
 * * Create player
 * * Query Player Balance
 * * Balance Transfer
 * * Goto Game Lobby
 *
 * Required Fields:
 * * URL: http://101.132.78.203
 * * Account: [agent_user / merchant id]
 * * Key: [signing key]
 * * Extra_Info:
 * ```
 * {
 *     "suffix_for_username" : "@tianhao002",
 *     "sync_time_interval" : 600  # max 10 mins
 * }
 * ```
 *
 * @category Game API
 *
 * @copyright 2013-2022 tot
 */
class Game_api_tianhao extends Abstract_game_api {
    const URI_MAP = array(
        self::API_createPlayer => '/access/gameauth',
        self::API_queryForwardGame => '/access/gameauth',
        self::API_queryPlayerBalance => '/access/getmoney',
        self::API_isPlayerExist => '/access/getmoney',
        self::API_depositToGame => '/access/orderin',
        self::API_withdrawFromGame => '/access/orderout',
        self::API_syncGameRecords => '/access/gamerecord',
        self::API_queryTransaction => '/access/orderinfo',
    );

    # Fields in tianhao_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'username',
        'end_time',
        'game_type',
        'room_type',
        'start_money',
        'win_money',
        'end_money',
        'bank_money',
        'deal_money',
        'desk_uuid',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'start_money',
        'win_money',
        'end_money',
        'bank_money',
        'deal_money',
    ];

    # Fields in game_logs we want to detect changes for merge, and when tianhao_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'username',
        'end_time',
        'game_type',
        'room_type',
        'start_money',
        'win_money',
        'end_money',
        'bank_money',
        'deal_money',
        'desk_uuid',
        'effect_money',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'start_money',
        'win_money',
        'end_money',
        'bank_money',
        'deal_money',
        'effect_money',
    ];

    const HIDE_BACK_BUTTON_FLAG = 0;

    public function __construct() {
        parent::__construct();

        $this->data_url  = $this->getSystemInfo('data_url','https://gasdata.th705.com');
        $this->linecode  = $this->getSystemInfo('linecode','tianhao10');
    }

    public function getPlatformCode() {
        return TIANHAO_API;
    }

    # http call for this API uses GET
    public function generateUrl($apiName, $params) {
        // $suffix_skip_method = [self::API_createPlayer,self::API_queryForwardGame];
        // if(!in_array($apiName, $suffix_skip_method) && array_key_exists('username', $params)) {
        //     $params['username'] = $params['username'].$this->getSystemInfo('suffix_for_username');
        // }
        if(!array_key_exists("orderid", $params)) {
            $params["orderid"] = $this->uuid();
        }
        $params["ip"] = $this->utils->getIP();

        // Other params
        // $params["linecode"] = '';
        // $params["gametype"] = 'tbnn';
        // $params["roomlevel"] = '';

        $this->utils->debug_log("Processed params: ", $params);

        $p = array();
        $p['userid'] = $this->getSystemInfo('account');
        $p['timestamp'] = round(microtime(true) * 1000);
        $p['params'] = $this->encrypt($params);
        $p['sig'] = $this->sign($p);

        if($apiName=="syncGameRecords") {
            $url = $this->data_url.self::URI_MAP[$apiName].'/?'.http_build_query($p);
        }else {
            $url = $this->getSystemInfo("url").self::URI_MAP[$apiName].'/?'.http_build_query($p);
        }

        $this->utils->debug_log("Tianhao URL with actual params: [$url]");

        return $url;
    }

    public function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, FALSE);
    }

    # Decide whether API call is successful. Returns a single boolean value.
    protected function processResultBoolean($responseResultId, $resultArr) {
        $success = false;
        if(isset($resultArr['code']) && trim($resultArr['code']) == '0'){
            $success = true;
        }
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('tiaohao got error ', $responseResultId, 'result', $resultArr);
        }
        return $success;
    }

    public function queryForwardGame($playerName, $extra = null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAuthorize',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            "username" => $gameUsername,
            "money" => 0,
            "orderid" => $this->uuid(),
            "ip" => $this->utils->getIP(),
            "gametype" => $extra['game_code'],
            "linecode" => $this->linecode
        );

        if($this->getSystemInfo("showbackbutton")){
            $showbackbutton = $this->getSystemInfo("showbackbutton");
            $params['showbackbutton'] = $showbackbutton;
            if($showbackbutton == 2 && $this->getSystemInfo("backurl")){
                $params['backurl'] = $this->getSystemInfo("backurl");
            }
        }else{ 
            $params['showbackbutton'] = self::HIDE_BACK_BUTTON_FLAG;
        }

        $result = $this->callApi(self::API_queryForwardGame, $params, $context);

        if($result['success']) {
            return array("success"=>true,"url"=>$result['loginurl']);
        } else {
            return array("success"=>false,"url"=>"");
        }
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAuthorize',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "username" => $gameUsername,
            "money" => 0,
            "linecode" => $this->linecode
        );

       $data = $this->callApi(self::API_createPlayer, $params, $context);
       if($data['success']){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
       }
       return $data;
    }

    public function processResultForAuthorize($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('tianhao authorize ==============>', $resultArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        return array($success, $resultArr);
    }

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName
        );

        $params = array(
            "username" => $gameUsername,
        );

        if(!empty($this->getSystemInfo('suffix_for_username'))){
            $params['username'] = $gameUsername.$this->getSystemInfo('suffix_for_username');
        }

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('tianhao player exists ==============>', $resultArr);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if($success && array_key_exists('bag_money', $resultArr)) {
            $result['exists'] = true;
        } else {
            if($resultArr['code']==11){ # 11 means not authorized/ not registered
                $success = true;
                $result['exists'] = false;
            }else{
                $result['exists'] = null;
            }
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName
        );

        $params = array(
            "username" => $gameUsername,
        );

        if(!empty($this->getSystemInfo('suffix_for_username'))){
            $params['username'] = $gameUsername.$this->getSystemInfo('suffix_for_username');
        }

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('tianhao query balance ==============>', $resultArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if($success && array_key_exists('bag_money', $resultArr)) {
            return array($success, array('balance' => floatval($resultArr['bag_money'])));
        } else {
            return array(false);
        }
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null) {
        return $this->depositWithdraw($userName, $amount, self::API_depositToGame, $transfer_secure_id);
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        return $this->depositWithdraw($userName, $amount, self::API_withdrawFromGame, $transfer_secure_id);
    }

    private function depositWithdraw($userName, $amount, $apiName, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $transId = $transfer_secure_id;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositWithdraw',
            'apiName' => $apiName,
            'playerName' => $userName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $params = array(
            "username" => $gameUsername,
            "money" => number_format($amount, 2, '.', ''),
            "orderid" => $transfer_secure_id,
        );

        if(!empty($this->getSystemInfo('suffix_for_username'))){
            $params['username'] = $gameUsername.$this->getSystemInfo('suffix_for_username');
        }

        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForDepositWithdraw($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $isDeposit = ($this->getVariableFromContext($params, 'apiName') == self::API_depositToGame);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $amount = $this->getVariableFromContext($params, 'amount');
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $this->CI->utils->debug_log('tianhao DepositWithdraw ==============>', $resultArr);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if ($success) {
            //get username without prefix
            //get current sub wallet balance
            $playerBalance = $this->queryPlayerBalance($playerName);

            //for sub wallet
            $afterBalance = $playerBalance['balance'];

            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            if ($playerId) {
                $this->insertTransactionToGameLogs(
                    $playerId,
                    $playerName,
                    $afterBalance,
                    $amount,
                    $responseResultId,
                    $isDeposit ? $this->transTypeMainWalletToSubWallet() : $this->transTypeSubWalletToMainWallet()
                );
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            } else {
                $this->CI->utils->error_log("cannot get player id for [$playerName]");
            }
            $result['didnot_insert_game_logs']=true;
        } else {
            $result['reason_id'] = $this->getErrorReason($resultArr['code']);
            $result['transfer_status'] = $this->getTransferStatusCode($resultArr['code']);
        }

        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {
        $playerName = $extra['playerName'];
        $playerId = $this->getPlayerId($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId'=>$playerId,
            'external_transaction_id' => $transactionId
        );
        $params = array(
            "orderid" => $transactionId,
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $transId = $this->getVariableFromContext($params, 'external_transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);

        $this->CI->utils->debug_log('tianhao query response', $resultJsonArr, 'transaction id', $transId);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $transId,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        # Only update status when query success. Leave result defaults if query fail
        if($success) {
            $result['status'] = $this->getTransactionStatus($resultJsonArr['status']);
        }

        return array($success, $result);
    }

    # API Recommendation: 若没有上送用户名，则拉取区间不能超过10分钟
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

        $timestampFrom = $dateTimeFrom->format('U') * 1000;
        $timestampTo = $dateTimeTo->format('U') * 1000;

        return $this->doSyncOriginalGameLogs($context, $timestampFrom, $timestampTo);

    }

    public function doSyncOriginalGameLogs($context, $timestampFrom, $timestampTo) {
        $maxLoop = 60; # provide fail-safe exit condition to while loop
        $syncInterval = $this->getSystemInfo('sync_time_interval');

        if($syncInterval > 10 * 60) {
            $this->utils->error_log("Warning: sync_time_interval too large: [$syncInterval]");
        }
        $this->utils->debug_log("tiaohao Game Log Sync from [$timestampFrom] to [$timestampTo]. ## Note: max number of sync iterations is [$maxLoop], each iteration covers [$syncInterval] seconds.");


        $queryTimestampFrom = $timestampFrom;
        $queryTimestampTo = $timestampFrom + ($syncInterval * 1000);
        $loopCount = 0;
        $dataCount = 0;

        while ($queryTimestampFrom < $timestampTo && $loopCount < $maxLoop) {
            $params = array(
                "query_date" => date('Y-m-d', $queryTimestampFrom/1000),
                "starttime" => $queryTimestampFrom,
                "endtime" => $queryTimestampTo
            );

            $apiResult = $this->callApi(self::API_syncGameRecords, $params, $context);

            $this->utils->debug_log("tiaohao Sync from [$queryTimestampFrom] to [$queryTimestampTo], params:" , $params, "result:", $apiResult);

            if(!$apiResult['success']) {
                break;
            } else {
                $dataCount += count($apiResult['data_count']);
            }

            $queryTimestampFrom = $queryTimestampTo;
            $queryTimestampTo += ($syncInterval * 1000);
            $loopCount++;
        }

        $this->utils->debug_log("Done tiaohao Sync from [$timestampFrom] to [$timestampTo], loop count: [$loopCount]; insert/update count: [$dataCount];");
        return array("success"=>true);

    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $callSuccess = $this->processResultBoolean($responseResultId, $resultArr);

        if(!$callSuccess) {
            return array(false);
        }

        $gameRecords = array();
        if(array_key_exists('info', $resultArr)) {
            $gameRecords = json_decode(json_encode($resultArr['info']), true);
        }

        $result = ['data_count' => 0];

        if(!empty($gameRecords) && is_array($gameRecords)){
            # add in columns not returned by API, and process username column to remove suffix
            foreach($gameRecords as $index => $record) {
                $gameRecords[$index]['external_uniqueid'] = $this->getUid($record);
                $gameRecords[$index]['response_result_id'] = $responseResultId;
                $gameRecords[$index]['username'] = str_replace(
                    $this->getSystemInfo('suffix_for_username'),
                    "",
                    $gameRecords[$index]['username']);
            }

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                'tianhao_game_logs',
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
        }

        return array(true, $result);
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {

                $record['end_time'] = $this->gameTimeToServerTime($record['end_time']);

                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('tianhao_game_logs', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('tianhao_game_logs', $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = false; # unsettle not needed for tianhao
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sql = <<<EOD
SELECT
    tianhao_game_logs.id as sync_index,
    game_provider_auth.player_id,
    tianhao_game_logs.username,
    tianhao_game_logs.end_time,
    tianhao_game_logs.game_type,
    tianhao_game_logs.room_type,
    tianhao_game_logs.start_money,
    tianhao_game_logs.win_money,
    tianhao_game_logs.end_money,
    tianhao_game_logs.bank_money,
    tianhao_game_logs.deal_money,
    tianhao_game_logs.desk_uuid,
    tianhao_game_logs.external_uniqueid,
    tianhao_game_logs.response_result_id,
    tianhao_game_logs.md5_sum,
    tianhao_game_logs.user_id,
    tianhao_game_logs.game_group,
    tianhao_game_logs.effect_money,
    tianhao_game_logs.tax_money,
    game_description.id as game_description_id,
    game_description.game_code,
    game_description.game_type_id,
    game_description.game_name
FROM
    tianhao_game_logs
    LEFT JOIN game_description ON game_description.game_code = game_type AND game_description.game_platform_id = ?
    JOIN game_provider_auth ON tianhao_game_logs.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE
    end_time BETWEEN ? AND ?
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['effect_money'],
                'result_amount' => $row['win_money'],
                'bet_for_cashback' => $row['effect_money'],
                'real_betting_amount' => $row['effect_money'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['end_money']
            ],
            'date_info' => [
                'start_at' => $row['end_time'],
                'end_at' => $row['end_time'],
                'bet_at' => $row['end_time'],
                'updated_at' => $row['end_time']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['desk_uuid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            $row['game_description_id']= $unknownGame->id;
            $row['game_type_id'] = $unknownGame->game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED; # No unsettle for tianhao
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
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

    # Based on documentation, translate API returned error code into SBE defined error reason
    private function getErrorReason($code) {
        if($code == 0) {
            return self::COMMON_TRANSACTION_STATUS_APPROVED;
        }

        switch ((int)$code) {
            case 6:
            case 7:
            case 11:
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;            # 用户授权失败 / 未登录用户
                break;
            case 9:
            case 10:
            case 17:
            case 18:
                $reasonCode = self::REASON_NO_ENOUGH_BALANCE;           # 平台转入/出金额不能为空/小于0
                break;
            case 14:
                $reasonCode = self::REASON_LOWER_OR_GREATER_THAN_MIN_OR_MAX_TRANSFER;   # 金额过大
                break;
            case 15:
                $reasonCode = self::REASON_DUPLICATE_TRANSFER;          # 重复订单
                break;
            case 8:
                $reasonCode = self::REASON_INCOMPLETE_INFORMATION;      # params中的值错误
                break;
            case 13:
            case 19:
                $reasonCode = self::REASON_CANT_TRANSFER_WHILE_PLAYING_THE_GAME; # 你的账号在游戏中(不能进行转入和转出)
                break;
            case 2:
            case 3:
                $reasonCode = self::REASON_API_MAINTAINING;             # 平台不存在/已关闭
                break;
            case 4:
            case 26:
                $reasonCode = self::REASON_INVALID_KEY;                 # 字符校验不通过 / 加密错误
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;                     # 其他错误
                break;
        }

        return $reasonCode;
    }

    private function getTransferStatusCode($code) {
        return $code == 0 ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_UNKNOWN;
    }

    private function getTransactionStatus($code) {
        switch ((int)$code) {
            case -1:
                return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            case 0:
                return self::COMMON_TRANSACTION_STATUS_APPROVED;
            default:
                return self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
    }

    private function getUid($row) {
        return substr(join('|', array($row['username'], $row['end_time'], $row['end_money'], md5($row['game_type']))), 0, 60);
    }

    // Caveat: this is actually uuid v4, not v1 as written in tianhao's doc
    private function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    private function sign($p) {
        $key = $this->getSystemInfo('key');
        $signStr = $p['userid'].$p['timestamp'].$key;
        $this->utils->debug_log("Signing params ", $p, $signStr);
        return md5($signStr);
    }

    private function encrypt($params) {
        $crypter = new AesCrypter($this->getSystemInfo('aes_key'));
        $originalString = urldecode(http_build_query($params));
        $encryptedString = $crypter->encrypt($originalString);
        $this->utils->debug_log("Encrypting [$originalString] into [$encryptedString]");
        return $encryptedString;
    }

    private $_playerIdByUsername = array();
    private function getPlayerId($username) {
        $suffix = $this->getSystemInfo('suffix_for_username');
        # if there is a suffix in username, strip it away
        if(strpos($username, $suffix) > 0) {
            $username = substr($username, 0, strlen($username) - strlen($suffix));
        }

        if(!array_key_exists($username, $this->_playerIdByUsername)) {
            $this->_playerIdByUsername[$username] = $this->getPlayerIdInGameProviderAuth($username);
        }
        return $this->_playerIdByUsername[$username];
    }

}

class AesCrypter {

    private $key = '';
    private $algorithm;
    private $mode;

    public function __construct($key = '', $algorithm = MCRYPT_RIJNDAEL_128,
        $mode = MCRYPT_MODE_CBC) {
        if (!empty($key)) {
            $this->key = $key;
        }
        $this->key = hash('sha256', $this->key, true);
        $this->algorithm = $algorithm;
        $this->mode = $mode;
    }

    public function encrypt($orig_data) {
        $encrypter = @mcrypt_module_open($this->algorithm, '',
            $this->mode, '');
        $orig_data = $this->pkcs7padding(
            $orig_data, @mcrypt_enc_get_block_size($encrypter)
        );
        @mcrypt_generic_init($encrypter, $this->key, substr($this->key, 0, 16));
        $ciphertext = @mcrypt_generic($encrypter, $orig_data);
        @mcrypt_generic_deinit($encrypter);
        @mcrypt_module_close($encrypter);
        return base64_encode($ciphertext);
    }

    public function decrypt($ciphertext) {
        $encrypter = @mcrypt_module_open($this->algorithm, '',
            $this->mode, '');
        $ciphertext = base64_decode($ciphertext);
        @mcrypt_generic_init($encrypter, $this->key, substr($this->key, 0, 16));
        $orig_data = @mdecrypt_generic($encrypter, $ciphertext);
        @mcrypt_generic_deinit($encrypter);
        @mcrypt_module_close($encrypter);
        return $this->pkcs7unPadding($orig_data);
    }

    public function pkcs7padding($data, $blocksize) {
        $padding = $blocksize - strlen($data) % $blocksize;
        $padding_text = str_repeat(chr($padding), $padding);
        return $data . $padding_text;
    }

    public function pkcs7unPadding($data) {
        $length = strlen($data);
        $unpadding = ord($data[$length - 1]);
        return substr($data, 0, $length - $unpadding);
    }

}


