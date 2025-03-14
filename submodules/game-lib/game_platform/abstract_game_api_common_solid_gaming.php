<?php
/**
 * Solid Gaming integration
 * OGP-13885
 *
 * @author  Erickson Qua
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

abstract class Abstract_game_api_common_solid_gaming extends Abstract_game_api {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const TRANS_GAME_BET = 1;
    const TRANS_GAME_WIN = 11;

    const GAME_ENABLED = 1;
    const GAME_MAINTENANCE = 2;
    const GAME_DISABLED = 3;

    const MD5_FIELDS_FOR_ORIGINAL=[
        'change_id',
        'timestamp',
        'player_id',
        'transfer_type',
        'delta_amount',
        'balance',
        'server_round',
        'round_ended',
    ];


    const MD5_FLOAT_AMOUNT_FIELDS = [
        'delta_amount',
        'balance'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'change_id',
        'transfer_type',
        'delta_amount',
        'after_balance',
        'round_number',
        'round_ended',
        'game_code',
        'external_uniqueid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'delta_amount',
        'after_balance',
    ];

    public function __construct() {
        parent::__construct();
        $this->apiUrl = $this->getSystemInfo('url','https://instage.solidgaming.net/api/wallet');
        $this->brand = $this->getSystemInfo('brand','SCS188');
        $this->currency = $this->getSystemInfo('currency','THB');
        $this->language = $this->getSystemInfo('language','th_TH');
        $this->country =  $this->getSystemInfo('country','TH');
        $this->method = self::METHOD_POST;
        $this->apiUser = $this->getSystemInfo('apiUser','tripleone-stage');
        $this->apiPassword = $this->getSystemInfo('apiPassword','rE2dk8KgxArJPnQf');
        $this->exitUrl = $this->getSystemInfo('exitUrl', '');

        $this->URI_MAP = array(
            self::API_createPlayer => '/playercreate',
            self::API_isPlayerExist => '/playercheck',
            self::API_queryPlayerBalance => '/playercheck',
            self::API_depositToGame => '/wallettransferin',
            self::API_withdrawFromGame => '/wallettransferout',
            self::API_queryForwardGame => '/gamelauncher',
            self::API_syncGameRecords => '/wallettransactions',
            self::API_queryTransaction => '/wallettransfercheck',
            self::API_queryGameListFromGameProvider => '/gamelist',
        );

        $this->API_ACTIONS = [
            self::API_logout => 'MB_LOGOUT',
            self::API_blockPlayer => 'MB_UPD_STATUS', // status 0: locked
            self::API_unblockPlayer => 'MB_UPD_STATUS' //status 1: unblocked
        ];
    }

    public function getPlatformCode(){
        return $this->returnUnimplemented();
    }

    protected function customHttpCall($ch, $params) {

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiUser.':'.$this->apiPassword);
        if($this->method == self::METHOD_POST){
            $data_json = json_encode($params);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }
    
    public function generateUrl($apiName, $params) {
        $endPoint = $this->URI_MAP[$apiName];
        $url = $this->apiUrl.$endPoint;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = (array_key_exists('status', $resultArr) && $resultArr['status'] == 'OK') ? true : false;
        $result = array();
        if(!$success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('---------- SOLID GAMING Process Result Boolean False ----------', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
        );

        $params = [
            'brand' => $this->brand,
            'username' => $gameUsername,
            'currency' => $this->currency,
            'playerid' => $playerId,
        ];
        
        $this->CI->utils->debug_log('---------- SOLID GAMING createPlayer ----------', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = ['player' => $playerName];
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        $result['exists'] = true;
        $this->CI->utils->debug_log('---------- SOLID GAMING result for createPlayer ----------', $arrayResult);
        return array($success, $result);
    }

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
        );

        $params = [
            'playerid' => $playerId,
        ];
        
        $this->CI->utils->debug_log('---------- SOLID GAMING isPlayerExist ----------', $params);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = ['exists' => false];

        if($success) {
            $result['exists'] = true;
        }
        $this->CI->utils->debug_log('---------- SOLID GAMING result for isPlayerExist ----------', $arrayResult);
        return array(true, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
        );

        $params = [
            'playerid' => $playerId,
        ];
        
        $this->CI->utils->debug_log('---------- SOLID GAMING queryPlayerBalance ----------', $params);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = [];

        if($success) {
            $result["balance"] = $arrayResult['balance'];
        }
        $this->CI->utils->debug_log('---------- SOLID GAMING result for queryPlayerBalance ----------', $arrayResult);
        return array(true, $result);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $orderId = $transfer_secure_id;
        if($orderId === null) {

            $orderId = $this->utils->getDatetimeNow().$gameUsername;
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id'=> $orderId
        );

        $params = [
            'playerid' => $playerId,
            'amount' => $amount,
            'currency' => $this->currency,
            'idempotencyid' => $orderId
        ];

        $this->CI->utils->debug_log('---------- SOLID GAMING params for depositToGame ----------', $params);
        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['errorcode'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- SOLID GAMING result for depositToGame ----------', $arrayResult);
        return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $orderId = $transfer_secure_id;
        if($orderId === null) {

            $orderId = $this->utils->getDatetimeNow().$gameUsername;
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'external_transaction_id'=> $orderId
        );

        $params = [
            'playerid' => $playerId,
            'amount' => $amount,
            'currency' => $this->currency,
            'idempotencyid' => $orderId
        ];

        $this->CI->utils->debug_log('---------- SOLID GAMING params for withdrawFromGame ----------', $params);
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['errorcode'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- SOLID GAMING result for withdrawFromGame ----------', $arrayResult);
        return array($success, $result);
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case 'ACCESS_DENIED':
                $reasonCode = self::REASON_INVALID_KEY;
                break;
            case 'PLAYER_NOT_FOUND':
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;
                break;
            case 'CURRENCY_MISMATCH':
                $reasonCode = self::REASON_CURRENCY_ERROR;
                break;
            case 'BAD_REQUEST':
                $reasonCode = self::REASON_INCOMPLETE_INFORMATION;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        );

        $params = [
            'mode' => $extra['game_mode'],
            'brand' => $this->brand,
            'playerid' => $playerId,
            'gamecode' => $extra['game_code'],
            'platform' => $extra['is_mobile'] ? 'MOBILE' : 'DESKTOP',
            'language' => $this->getLauncherLanguage($extra['language']),
            'country' => $this->country,
            'exiturl' => $this->exitUrl,
        ];

        $this->CI->utils->debug_log('---------- SOLID GAMING params for queryForwardGame ----------', $params);
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }


    public function processResultForQueryForwardGame($params) {
        $arrayResult = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result['success'] = false;
        if($success){
            $result['success'] = true;
            $result['url'] = $arrayResult['launchurl'];
            $this->CI->utils->debug_log('---------- SOLID GAMING result url for queryForwardGame ----------', $result['url']);
        }
        return array($success, $result);
    }

    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vi';
                break;
            case "en-us":
            $language = 'en';
            break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-TH":
                $language = 'th';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());
        
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');
        $syncId = $this->getLastSyncIdFromTokenOrDB($token);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $params = [
            
            'lastchangeid' => $syncId,
            'returntype' => "JSON"
        ];

        $this->CI->utils->debug_log('---------- SOLID GAMING params for syncOriginalGameLogs ----------', $params);
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, null);

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        if($success) {
            $gameRecords = $arrayResult['transactions'];


            $this->processGameRecords($gameRecords,$responseResultId);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $dataResult['data_count'] = count($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);

        }
        return array($success, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {

                if(!array_key_exists('gamecode', $record) && !array_key_exists('serverround', $record) && !array_key_exists('roundended', $record)) {
                    unset($gameRecords[$index]);
                    continue;
                }

                $data['change_id'] = isset($record['changeid']) ? $record['changeid'] : null;
                $data['timestamp'] = isset($record['timestamp']) ? $this->gameTimeToServerTime($record['timestamp']) : null;
                $data['player_id'] = isset($record['playerid']) ? $record['playerid'] : null;
                $data['transfer_type'] = isset($record['transtype']) ? $record['transtype'] : null;
                $data['delta_amount'] = isset($record['deltaamount']) ? $record['deltaamount'] : null;
                $data['balance'] = isset($record['balance']) ? $record['balance'] : null;
                $data['game_code'] = isset($record['gamecode']) ? $record['gamecode'] : null;
                $data['server_round'] = isset($record['serverround']) ? $record['serverround'] : null;
                $data['round_ended'] = isset($record['roundended']) ? $record['roundended'] : null;

                $data['game_externalid'] = $data['game_code'];
                $data['external_uniqueid'] = $data['change_id'];
                $data['response_result_id'] = $responseResultId;

                $gameRecords[$index] = $data;
                if($this->do_update_sync_id) {
                    $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $data['change_id']);
                }
                unset($data);

            }
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $table = $this->originalTable;
        $sqlTime='solid_gaming.timestamp >= ? and solid_gaming.timestamp <= ?';
        $sql = <<<EOD
SELECT
    solid_gaming.id as sync_index,
    solid_gaming.change_id,
    solid_gaming.transfer_type,
    solid_gaming.delta_amount,
    solid_gaming.balance as after_balance,
    solid_gaming.server_round as round_number,
    solid_gaming.round_ended,
    solid_gaming.game_externalid as game_code,
    solid_gaming.external_uniqueid,
    solid_gaming.updated_at,
    solid_gaming.md5_sum,
    solid_gaming.response_result_id,
    solid_gaming.game_code as game,
    solid_gaming.timestamp as bet_at,
    solid_gaming.timestamp as start_at,
    solid_gaming.timestamp as end_at,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as solid_gaming
    LEFT JOIN game_description ON solid_gaming.game_externalid = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON solid_gaming.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => ($row['transfer_type'] == self::TRANS_GAME_BET) ? abs($row['delta_amount']) : 0,
                'result_amount'         => $row['delta_amount'],
                'bet_for_cashback'      => ($row['transfer_type'] == self::TRANS_GAME_BET) ? abs($row['delta_amount']) : 0,
                'real_betting_amount'   => ($row['transfer_type'] == self::TRANS_GAME_BET) ? abs($row['delta_amount']) : 0,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance']
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['bet_at'],
                'bet_at'                => $row['end_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = Game_logs::STATUS_SETTLED; 
    }


    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
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

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*public function getGameTimeToServerTime() {
        //return '+8 hours';
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*public function getServerTimeToGameTime() {
        //return '-8 hours';
    }*/

    public function queryTransaction($transactionId, $extra) {
        $playerId=$extra['playerId'];
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId
        );

        $params = [
            'playerid' => $playerId,
            'idempotencyid' => $transactionId,
        ];

        $this->CI->utils->debug_log('---------- SOLID GAMING params for queryTransaction ----------', $params);
        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    /**
     * overview : process result for queryTransaction
     * @param $apiName
     * @param $params
     * @param $responseResultId
     * @param $resultXml
     * @return array
     */
    public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            // no callback error here if api failed
            // display default (network error, reason id) from processGuessSuccess
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }
    
    public function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        $isError = true;
        switch($apiName) {
            case self::API_isPlayerExist:
            case self::API_queryPlayerBalance:
                $isError = $errCode || intval($statusCode, 10) > 404;
                break;
            default:
                $isError =  parent::isErrorCode($apiName, $params, $statusCode, $errCode, $error);
        }

        return $isError;
    }

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    public function queryGameListFromGameProvider($filter = false) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
            'filter' => $filter,
        );

        $params = [
            'allgames' => true,
        ];

        $this->CI->utils->debug_log('---------- SOLID GAMING params for queryGameListFromGameProvider ----------', $params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $filter = $this->getVariableFromContext($params, 'filter');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);
        $result = [
            'games'
        ];
        if($success) {
            if($filter) {
                $result['games'] = $this->filterGames($arrayResult['games']);
            }
            else {
                $result['games'] = $arrayResult['games'];
            }
        }
        return array($success, $result);
    }

    private function filterGames($games) {
        $data = [];
        $active_providers = $this->utils->getConfig('solid_gaming_active_providers');
        $active_game_types = $this->utils->getConfig('solid_gaming_active_game_types');
        foreach ($games as $game) {
            if(in_array($game['gamestudio'], $active_providers) && in_array($game['gametype'], $active_game_types)) {
                $data[] = $game;
            }
        }
        return $data;
    }


    const MD5_FIELDS_FOR_GAMES = [
        'provider',
        'game_studio',
        'game_code',
        'game_name',
        'chinese_name',
        'game_type',
        'rtp',
        'status',
    ];

    const GAMELIST_TABLE = 'solid_gaming_gamelist';

    public function preProccessGames($games) {
        $data = [];
        foreach ($games as $game) {
            $newGame = [];
            $newGame['provider'] = isset($game['provider']) ? $game['provider'] : '';
            $newGame['game_studio'] = isset($game['gamestudio']) ? $game['gamestudio'] : '';
            $newGame['game_code'] = isset($game['gamecode']) ? $game['gamecode'] : '';
            $newGame['game_name'] = isset($game['gamename']) ? $game['gamename'] : '';
            $newGame['chinese_name'] = isset($game['chinesename']) ? $game['chinesename'] : '';
            $newGame['game_type'] =  isset($game['gametype']) ? $game['gametype'] : '';
            $newGame['rtp'] = isset($game['rtp']) ? $game['rtp'] : '';
            $newGame['status'] = isset($game['status']) ? $game['status'] : '';
            $newGame['external_uniqueid'] = isset($game['uniqueid']) ? $game['uniqueid'] : '';
            $newGame['release_date'] = isset($game['releasedate']) ? $game['releasedate'] : '';

            $md5sum = "";
            foreach(self::MD5_FIELDS_FOR_GAMES as $field) {
                $md5sum .= $newGame[$field];
            }
            $md5sum = md5($md5sum);
            $newGame['md5_sum'] = $md5sum;

            $data[$newGame['external_uniqueid']] = $newGame;
        }

        return $data;
    }

    public function updateGameList($games) {

        $this->CI->load->model(array('original_game_logs_model'));
        $games = $this->preProccessGames($games);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            self::GAMELIST_TABLE,
            $games,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_GAMES,
            'md5_sum',
            'id',
            []
        );

        $dataResult = [
            'data_count' => count($games),
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] += $this->updateOrInsertGameList($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] += $this->updateOrInsertGameList($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    private function processGameListStatus($status) {
        $data = "";
        switch($status) {
            case self::GAME_ENABLED:
                $data = "Enabled";
                break;
            case self::GAME_MAINTENANCE:
                $data = "Maintenance";
                break;
            case self::GAME_DISABLED:
                $data = "Disabled";
                break;
        }
        return $data;
    }

    private function updateOrInsertGameList($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            $caption = [];
            if ($queryType == 'update') {
                $caption = "## UPDATE SOLID GAMING GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW SOLID GAMING GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Type | RTP | Release Date | Status |\n";
            $body .= "| :--- | :--- | :--- |\n";
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::GAMELIST_TABLE, $record);
                    $body .= "| {$record['game_name']} | {$record['chinese_name']} | {$record['game_code']} | {$record['game_type']} | {$record['rtp']} | {$record['release_date']} | {$this->processGameListStatus($record['status'])} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::GAMELIST_TABLE, $record);
                    $body .= "| {$record['game_name']} | {$record['chinese_name']} | {$record['game_code']} | {$record['game_type']} | {$record['rtp']} | {$record['release_date']} | {$this->processGameListStatus($record['status'])} |\n";
                }
                $dataCount++;
                unset($record);
            }
            $this->sendMatterMostMessage($caption, $body);
        }
        return $dataCount;
    }

    public function sendMatterMostMessage($caption, $body){
        $message = [
            $caption,
            $body,
            "#SOLIDGAMING"
        ];

        $channel = $this->utils->getConfig('solid_gaming_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $channel = $channel;
        $user = 'Solid Gaming Game List';

        sendNotificationToMattermost($user, $channel, [], $message);

    }

}
