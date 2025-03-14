<?php
/**
 * Jgameworks game integration
 * OGP-35448
 *
 * @author  Jerbey Capoquian
 *
 *
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - jgameworks_seamless_service_api.php

    Endpoint for seamless wallet
    -<domain>/jgameworksapi

    example on postman request:
    {{base_url}}/jgameworksapi/GetToken
    {{base_url}}/jgameworksapi/VerifySession
    {{base_url}}/jgameworksapi/Cash/Get
    {{base_url}}/jgameworksapi/Cash/Bet
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_jgameworks_seamless extends Abstract_game_api {

    const URI_MAP = array(
        self::API_queryForwardGame => '/api/usr/ingame',
        self::API_queryGameListFromGameProvider => '/api/game/loadlist',
    );

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = 'jgameworks_seamless_wallet_transactions';
        $this->original_table = 'jgameworks_seamless_wallet_game_records';
        $this->api_url = $this->getSystemInfo('url');
        $this->mchid = $this->getSystemInfo('mchid');
        $this->mchkey = $this->getSystemInfo('mchkey');
        $this->enable_currency_prefix_on_token = $this->getSystemInfo('enable_currency_prefix_on_token', false);
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return JGAMEWORKS_SEAMLESS_API;
    }

    /**
     * overview : generate url
     *
     * @param $apiName
     * @param $params
     * @return string
     */
    public function generateUrl($apiName, $params)
    {
        $uriMap = self::URI_MAP;
        $uri = $uriMap[$apiName];
        $url = $this->api_url . $uri;
        $this->CI->utils->debug_log('==> jgameworks generateUrl : ' . $url);
        return $url;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params)
    {
        $timestamp = $this->CI->utils->getTimestampNow();
        $signString = json_encode($params) . $timestamp . $this->mchkey;
        $calculatedHash = strtoupper(md5($signString));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Atgame-Mchid: {$this->mchid}",
            "X-Atgame-Timestamp: {$timestamp}",
            "X-Atgame-Sign: {$calculatedHash}",
        ));
    }

    /**
     * overview : create player game
     *
     * @param $playerName
     * @param $playerId
     * @param $password
     * @param null $email
     * @param null $extra
     * @return array
     */
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for jgameworks Game";
        if ($return) {
            $success = true;
            $message = "Successfull create account for jgameworks Game.";
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return array(
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return array(
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        );
    }

    /**
     * Overview: Query forward game
     *
     * @param string $playerName
     * @param array $extra
     * @return array
     */
    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'playerId' => $this->getPlayerIdFromUsername($playerName),
            'gameUsername' => $gameUsername,
        );

        $tokenPrefix = "";
        if ($this->enable_currency_prefix_on_token) {
            $currencyDetails = $this->CI->utils->getActiveCurrencyInfoOnMDB();
            if (is_array($currencyDetails) && isset($currencyDetails['code'])) {
                $tokenPrefix = $currencyDetails['code'] . '-';
            }
        }

        $this->CI->load->model('game_description_model');
        $gameCode = isset($extra['game_code']) ? $extra['game_code'] : '';
        $provider = $this->CI->game_description_model->getSubProviderByGameCode(
            $this->getPlatformCode(),
            $gameCode
        );

        if (empty($provider)) {
            return array("success" => false, "url" => "");
        }

        // Construct game ID
        $gameId = $provider . '/' . $gameCode;
        
        $params = array(
            'uname' => $gameUsername,
            'gameid' => $gameId,
            'token' => $tokenPrefix . $this->getPlayerTokenByUsername($playerName),
            'lang' => $this->getLanguage(isset($extra['language']) ? $extra['language'] : ''),
            'nick' => $gameUsername,
        );

        $this->CI->utils->debug_log(
            'jgameworks: (' . __FUNCTION__ . ')',
            'PARAMS:',
            $params
        );

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function getLanguage($currentLang)
    {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN:
            case 'id-ID':
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE:
            case 'vi-VN':
                $language = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI:
            case 'th-TH':
                $language = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE:
            case 'pt-PT':
                $language = 'pt';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    /**
     * overview : process result for queryForwardGame
     *
     * @param $params
     * @return array
     */
    public function processResultForQueryForwardGame($params) {
        $this->CI->load->model('external_common_tokens');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $url = isset($resultArr['data']['gameurl']) ? $resultArr['data']['gameurl'] : null;
        $result = array("url" => isset($resultArr['data']['gameurl']) ? $resultArr['data']['gameurl'] : null);
        return array($success, $result);
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        $successCode = 0;
        if(isset($resultArr['code']) && $resultArr['code'] == $successCode){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('jgameworks Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function queryGameListFromGameProvider($extra = null){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameProviderGamelist',
        );

        $params = [];

        $this->CI->utils->debug_log('jgameworks: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    /** 
     * Process Result of queryGameListFromGameProvider
    */
    public function processResultForGetGameProviderGamelist($params)
    {
        $this->CI->load->model('external_common_tokens');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array("list" => isset($resultArr['data']['glist']) ? $resultArr['data']['glist'] : []);
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getTransactionsTable(){
        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like {$this->original_transaction_table}");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
    }

     /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false)
    {
        $start = (new DateTime($dateFrom))->modify('first day of this month');
        $end = (new DateTime($dateTo))->modify('last day of this month');
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($start, $interval, $end);

        $results = [];
        $platformCode = $this->getPlatformCode();

        foreach ($period as $dt) {
            $yearMonthStr = $dt->format("Ym");
            $tableName = $this->original_transaction_table . '_' . $yearMonthStr;

            $sqlTimeColumn = $use_bet_time ? 'jg.created_at' : 'jg.updated_at';
            $sqlTime = $sqlTimeColumn . " >= ? AND " . $sqlTimeColumn . " <= ?";

            $sql = <<<EOD
SELECT
    jg.id AS sync_index,
    jg.external_uniqueid AS external_uniqueid,
    jg.player_id,
    jg.trans_type,
    SUBSTRING_INDEX(jg.game_id, '/', -1) AS game_code,
    jg.session_id AS round_number,
    jg.betting_time AS start_at,
    jg.betting_time AS end_at,
    jg.betting_time AS bet_at,
    jg.bet AS bet_amount,
    jg.award AS payout_amount,
    jg.result_amount,
    jg.before_balance,
    jg.after_balance,
    jg.sbe_status AS status,
    jg.md5_sum,
    gd.id AS game_description_id,
    gd.game_type_id
FROM {$tableName} AS jg
LEFT JOIN game_description AS gd
    ON SUBSTRING_INDEX(jg.game_id, '/', -1) = gd.external_game_id
    AND gd.game_platform_id = ?
WHERE {$sqlTime}
    AND jg.game_platform_id = ?
    AND jg.trans_type not in('rollbacklose', 'rollbackwin')
EOD;

            $params = array($platformCode, $dateFrom, $dateTo, $platformCode);
            $this->CI->utils->debug_log('Executing SQL', $sql, $params);

            $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            $results = array_merge($results, $monthlyResults);
        }

        $this->CI->original_game_logs_model->removeDuplicateUniqueid($results, 'external_uniqueid', function () {
            return 2;
        });

        return array_values($results);
    }


    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'start_at', 'end_at'],
                ['bet_amount', 'payout_amount']);
        }

        $data =  [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            // 'status' => $row['status'],#ignore status, as per GP this is for multiple amount website
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [
                'bet_amount' => $row['bet_amount'],
                'win_amount' => $row['payout_amount'],
                'round_id' => $row['round_number']
            ],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
        return $data;
    }


    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_code'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

     /**
     * queryTransactionByDateTime
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryTransactionByDateTime($dateFrom, $dateTo){
        $tableName = $this->getTransactionsTable();
        $incType = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
        $decType = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
        $sqlTime="jg.created_at >= ? AND jg.created_at <= ? AND jg.game_platform_id = ?";

        $sql = <<<EOD
SELECT
jg.id as sync_index,
jg.external_uniqueid as external_uniqueid,
jg.session_id as round_no,
jg.trans_type as trans_type,
ABS(jg.result_amount) as amount,
jg.md5_sum,
if(jg.trans_type = "debit", "{$decType}", "{$incType}") as transaction_type,
jg.created_at as transaction_date,
jg.before_balance,
jg.after_balance,
jg.player_id

FROM {$tableName} as jg
where
{$sqlTime}
EOD;

        
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('==> jgameworks queryTransactions sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
    }
}