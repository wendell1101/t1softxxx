<?php
/**
 * Ray Gaming Integration
 * OGP-14597
 *
 * @author  Erickson Qua
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt.php';

class Game_api_tianhong_mini_games extends Abstract_game_api {

    const METHOD_POST = 'POST';

    const TRANSACTION_WITHDRAW = 1;
    const TRANSACTION_DEPOSIT = 0;

    const MD5_FIELDS_FOR_ORIGINAL=[
        'idx',
        'user_id',
        'bet_choose',
        'betting_amount',
        'change_amount',
        'user_coin',
        'is_win',
        'log_date',
        'game_code',
    ];


    const MD5_FLOAT_AMOUNT_FIELDS = [
        'betting_amount',
        'change_amount',
        'user_coin'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'sync_index',
        'total_stake',
        'total_win',
        'start_at',
        'bet_at',
        'end_at',
        'round_number',
        'game_code',
        'is_win',
        'bet_choose',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'total_stake',
        'total_win',
    ];

    const API_requestOAuthToken = "requestOAuthToken";

    public function __construct() {
        parent::__construct();
        $this->originalTable = "tianhong_mini_games_game_logs";
        $this->apiUrl = $this->getSystemInfo('url','http://112.220.94.163:9089');
        $this->apiKey = $this->getSystemInfo('key','portal');
        $this->apiSecret = $this->getSystemInfo('secret','402e272b5e0446c89d399d56fed71b51');
        // $this->launchURL = $this->getSystemInfo('launchURL','https://pc.raygaming.co');
        // $this->mobileLaunchURL = $this->getSystemInfo('mobileLaunchURL','https://h5.raygaming.co');
        // $this->currency = $this->getSystemInfo('currency','CNY');
        // $this->lobbyURL = $this->getSystemInfo('lobby_url','http://player.og.local');
        // $this->customCSS = $this->getSystemInfo('custom_css', null);
        $this->method = self::METHOD_POST;

        $this->METHOD_MAP = array(
            self::API_requestOAuthToken => 'oauth/token',
            self::API_depositToGame => 'api/trade',
            self::API_withdrawFromGame => 'api/trade',
            self::API_queryTransaction => 'api/trade',
            self::API_queryForwardGame => 'api/createToken',
            self::API_queryPlayerBalance => 'api/gameInfo',
            self::API_isPlayerExist => 'api/gameInfo',
            self::API_createPlayer => 'api/trade',
            self::API_syncGameRecords => 'api/userGameLog',
        );
    }

    public function getPlatformCode(){
        return TIANHONG_MINI_GAMES_API;
    }

    public function getAvailableApiToken(){
        $response = $this->requestOAuthToken();
        return $response['token'];
    }

    public function requestOAuthToken(){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForRequestOAuthToken',
        );

        $params = array(
            'grant_type' => 'client_credentials',
        );

        $this->method = self::METHOD_POST;
        return $this->callApi(self::API_requestOAuthToken, $params, $context);
    }

    public function processResultForRequestOAuthToken($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result = [
            'token'=>null
        ];

        if($success){
            $result['token'] = $resultArr['access_token'];
        }

        return array($success,$result);
    }

    protected function customHttpCall($ch, $params) {

        if($this->method == self::METHOD_POST){
            if(array_key_exists('grant_type', $params)) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            }
            else {
                $token = $this->getAvailableApiToken();
                $authorization = "Authorization: Bearer " . $token;
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', $authorization));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

    }

    public function generateUrl($apiName, $params) {
        $url = $this->apiUrl . '/' . $this->METHOD_MAP[$apiName];

        if($apiName == self::API_queryTransaction) {
            $url .= '/' . $params['transaction_id'];
        }
        if($apiName == self::API_queryPlayerBalance || $apiName == self::API_isPlayerExist) {
            $url .= '/' . $params['account_name'];
        }
        return $url;

    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = false;
        if(!array_key_exists('error', $resultArr) || (array_key_exists('result', $resultArr) && $resultArr['result'] == 0)) {
            $success = true;
        }

        $result = array();
        if(!$success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('---------- TIANHONG Process Result Boolean False ----------', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
        );

        $data = [
            'userId' => $gameUsername,
            'tradeAmount' => 0,
        ];

        $this->CI->utils->debug_log('---------- TIANHONG params for createPlayer ----------', $data);
        return $this->callApi(self::API_createPlayer, $data, $context);
    }

    public function processResultForCreatePlayer($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = ['player' => $playerName, 'exists' => false];
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        $this->CI->utils->debug_log('---------- TIANHONG result for createPlayer ----------', $arrayResult);
        return array($success, $result);
    }

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
        );

        $data = [
            'account_name' => $gameUsername,
        ];

        
        $this->CI->utils->debug_log('---------- TIANHONG isPlayerExist ----------', $data);

        return $this->callApi(self::API_isPlayerExist, $data, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = ['exists' => false];

        if($success && array_key_exists('gameInfo', $arrayResult)) {
            $result['exists'] = true;
        }
        $this->CI->utils->debug_log('---------- TIANHONG result for isPlayerExist ----------', $arrayResult);
        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
        );

        $data = [
            'account_name' => $gameUsername,
        ];

        
        $this->CI->utils->debug_log('---------- TIANHONG queryPlayerBalance ----------', $data);

        return $this->callApi(self::API_queryPlayerBalance, $data, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = [];

        if($success && array_key_exists('gameInfo', $arrayResult)) {
            $result["balance"] = $arrayResult['gameInfo']['coin'];
        }
        $this->CI->utils->debug_log('---------- TIANHONG result for queryPlayerBalance ----------', $arrayResult);
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $orderId = $transfer_secure_id;
        if($orderId == null) {
            $orderId = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id'=> $orderId
        );

        $data = [
            'userId' => $gameUsername,
            'tradeAmount' => $amount,
        ];

        $this->CI->utils->debug_log('---------- TIANHONG params for depositToGame ----------', $data);
        return $this->callApi(self::API_depositToGame, $data, $context);
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
            $result['external_transaction_id'] = $arrayResult['tradeNo'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- TIANHONG result for depositToGame ----------', $arrayResult);
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $orderId = $transfer_secure_id;
        if($orderId == null) {
            $orderId = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
        );

        $data = [
            'userId' => $gameUsername,
            'tradeAmount' => $amount * -1,
        ];

        $this->CI->utils->debug_log('---------- TIANHONG params for withdrawFromGame ----------', $data);
        return $this->callApi(self::API_withdrawFromGame, $data, $context);
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
            $result['external_transaction_id'] = $arrayResult['tradeNo'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- TIANHONG result for withdrawFromGame ----------', $arrayResult);
        return array($success, $result);
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case 'LACK_OF_COIN':
                $reasonCode = self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                break;
            case 'TRADE_AMOUNT_ZERO':
                $reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }


    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        );

        $data = [
            'userId' => $gameUsername,
            'userNick' => $gameUsername,
        ];

        $this->CI->utils->debug_log('---------- TIANHONG params for queryForwardGame ----------', $data);
        return $this->callApi(self::API_queryForwardGame, $data, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $arrayResult = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result['success'] = false;
        if($success){
            $gameUrl = $arrayResult['login_url'];
            $exitUrl = $this->getSystemInfo('exitUrl', null);
            if($exitUrl !== null) {
                $gameUrl .= '&exitUrl=' . $exitUrl;
            }
            $result['success'] = true;
            $result['url'] = $gameUrl;
            $result['redirect'] = true;
            $this->CI->utils->debug_log('---------- TIANHONG result url for queryForwardGame ----------', $result['url']);
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
    
        // $startDate = $startDate->format('Y-m-d H:i:s');
        // $endDate   = $endDate->format('Y-m-d H:i:s');
        $result = [];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        
        $result = [];
        $current_result = [];

        $beginDateOnly = new DateTime($startDate->format('Y-m-d 00:00:00'));
        $endDateOnly = new DateTime($endDate->format('Y-m-d H:i:s'));
        $interval = new DateInterval('P1D');
        $dateChunk = new DatePeriod($beginDateOnly, $interval ,$endDateOnly);
        $total = count(iterator_to_array($dateChunk));
        foreach($dateChunk as $index => $date){
            $startTime = '00:00:00';
            $endTime = '23:59:59';
            if($index == 0) {
                $startTime = $startDate->format('H:i:s');
            }
            if($index == ($total - 1)) {
                $endTime = $endDate->format('H:i:s');
            }

            $page = 1;
            do {
                $data = [
                    'logTimeFrom' => $startTime,
                    'logTimeTo' => $endTime,
                    'pageUnit' => 500,
                    'pageIndex' => $page
                ];

                if($date->format("Ymd") != date('Ymd')) {
                    $data['logDate'] = $date->format("Ymd");
                }

                $this->CI->utils->debug_log('---------- TIANHONG params for syncOriginalGameLogs ----------', $data, 'page', $page);
                $result[] = $current_result = $this->callApi(self::API_syncGameRecords, $data, $context);
                $page++;
                sleep(1);
            } while($current_result['data_count'] > 0);
        }
        // do {

        //     $data = [
        //         'start_time' => strtotime($startDate),
        //         'end_time' => strtotime($endDate),
        //         'pageUnit' => 500,
        //         'pageIndex' => $page
        //     ];

        //     $this->CI->utils->debug_log('---------- TIANHONG params for syncOriginalGameLogs ----------', $data, 'page', $page);
        //     $result[] = $current_result = $this->callApi(self::API_syncGameRecords, $data, $context);
        //     $page++;
        //     sleep(5);
        // } while($current_result['data_count'] > 0);

        // return $current_result;

    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, null);
        $gameRecords = !empty($arrayResult['logs']) ? $arrayResult['logs']:[];

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        if($success && !empty($gameRecords)) {

            $this->CI->utils->debug_log('---------- TIANHONG result for processResultForSyncGameRecords ----------', $gameRecords);
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

                $data['idx'] = isset($record['idx']) ? $record['idx'] : null;
                $data['user_id'] = isset($record['userId']) ? $record['userId'] : null;
                $data['bet_choose'] = isset($record['betChoose']) ? $record['betChoose'] : null;
                $data['betting_amount'] = isset($record['bettingAmt']) ? $record['bettingAmt'] : null;
                $data['change_amount'] = isset($record['changeAmt']) ? $record['changeAmt'] : null;
                $data['user_coin'] = isset($record['userCoin']) ? $record['userCoin'] : null;
                $data['is_win'] = isset($record['isWin']) ? $record['isWin'] : null;
                $data['log_date'] = isset($record['logDt']) ? $this->utils->formatDateTimeForMysql( new DateTime($record['logDt'])) : null;
                $data['game_code'] = 'taisai';

                $data['external_uniqueid'] = date('Ymd', strtotime($data['log_date'])) . '-' . $data['idx'];
                $data['response_result_id'] = $responseResultId;
                $gameRecords[$index] = $data;
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
        $sqlTime='ogl.log_date >= ? and ogl.log_date <= ?';
        $sql = <<<EOD
SELECT
    ogl.id as sync_index,
    ogl.betting_amount as total_stake,
    ogl.change_amount as total_win,
    ogl.log_date as start_at,
    ogl.log_date as bet_at,
    ogl.log_date as end_at,
    ogl.external_uniqueid as round_number,
    ogl.game_code,
    ogl.is_win,
    ogl.bet_choose,

    ogl.external_uniqueid,
    ogl.updated_at,
    ogl.md5_sum,
    ogl.response_result_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as ogl
    LEFT JOIN game_description ON ogl.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON ogl.user_id = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
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

        $bet_details = [
            'is_win' => $row['is_win'],
            'bet_choose' => $row['bet_choose']
        ];

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['total_stake'],
                'result_amount'         => $row['total_win'] ,
                'bet_for_cashback'      => $row['total_stake'],
                'real_betting_amount'   => $row['total_stake'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null,
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $bet_details,
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;

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
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
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
        $playerName = $extra['playerName'];
        $transferMethod = $extra['transfer_method'];
        $amount = $extra['amount'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $transaction_type = $transferMethod == 'deposit' ? self::TRANSACTION_DEPOSIT : self::TRANSACTION_WITHDRAW;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
            'transaction_type' => $transaction_type,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
        );
        // TRANSACTION_WITHDRAW
        // TRANSACTION_DEPOSIT
        $data = [
            'transaction_id' => $transactionId,
        ];


        $this->CI->utils->debug_log('---------- TIANHONG params for queryTransaction ----------', $data);
        return $this->callApi(self::API_queryTransaction, $data, $context);
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
        $transaction_type = $this->getVariableFromContext($params, 'transaction_type');
        $amount = $this->getVariableFromContext($params, 'amount');
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        $this->CI->utils->debug_log('---------- TIANHONG result for queryTransaction ----------', $arrayResult);

        if($success) {
            if(array_key_exists('tradeInfo', $arrayResult)
                && $arrayResult['tradeInfo']['tradeType'] == $transaction_type
                && $arrayResult['tradeInfo']['userId'] == $gameUsername
                && abs($arrayResult['tradeInfo']['tradeAmt']) == $amount) {
                $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            }
            else {
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        } else {
            // no callback error here if api failed
            // display default (network error, reason id) from processGuessSuccess
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
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

}
