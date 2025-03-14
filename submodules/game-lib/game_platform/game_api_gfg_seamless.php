<?php
/**
 * Good Fortune Gaming(GFG) Single Wallet API Document
 * OGP-28870
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
     - gfg_seamless_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_gfg_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    const ORIGINAL_LOGS_TABLE_NAME = 'gfg_seamless_game_logs';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status', 'round_number'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount', 'valid_bet'];
    const MD5_FIELDS_FOR_ORIGINAL=[
        'roundId', 'createTime', 'roundBeginTime', 'roundEndTime', 'gameResult'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS=['bet', 'validBet', 'win', 'lose'];
    const ERROR_CODE_SUCCESS = 0;
    const DEFAULT_MAX_SIZE = 100;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "BRL");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 'en');
        $this->agent = $this->getSystemInfo('agent', '1987151');
        $this->company_key = $this->getSystemInfo('company_key', '1971868_haocai');
        $this->api_key = $this->getSystemInfo('api_key', '4ec7db37df699ccb669c813d97deed1b');
        $this->app_url = $this->getSystemInfo('app_url', 'https://admin.brl.staging.brlgateway.t1t.in/gfg_seamless_service_api');#endpoint domain for seamless
        $this->theme = $this->getSystemInfo('theme', 'S007');

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

    }

    const URI_MAP = array(
        self::API_queryGameListFromGameProvider => "/getGameList",
        self::API_queryForwardGame => "/login",
        self::API_syncGameRecords => "/takeBetLogs"
    );

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return GFG_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        $signature = md5(json_encode($params).$this->api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: {$signature}"));   
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if(@$statusCode == 200 && isset($resultArr['code']) && $resultArr['code'] == self::ERROR_CODE_SUCCESS){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('GFG got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for GFG Game";
        if($return){
            $success = true;
            $message = "Successfull create account for GFG Game.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    const LOBBY_CODE = 0;
    const MOBILE_CODE = 1;
    const PC_CODE = 0;
    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getPlayerTokenByUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        );

        $params = array(
            "account" => $this->agent."_".$gameUsername,
            "gameId" => isset($extra['game_code']) ? $extra['game_code'] : self::LOBBY_CODE,
            "ip" => $this->utils->getIP(),
            "agent" => $this->agent,
            "companyKey" => $this->company_key,
            "platform" => isset($extra['is_mobile']) && $extra['is_mobile'] ? self::MOBILE_CODE : self::PC_CODE,
            "appUrl" => $this->app_url,
            "exitUrl" => $this->getHomeLink(),
            "theme" => $this->theme,
            "token" => $token,
            "languageType" => $this->getLanguage($extra['language']),
            "timestamp" => $this->utils->getTimestampNow() * 1000
        );

        if(isset($extra['extra']['home_link'])) {
            $params['exitUrl'] = $extra['extra']['home_link'];
        }

        $this->CI->utils->debug_log('GFG: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['url'] = isset($resultArr['data']['url']) ? $resultArr['data']['url'] : null ;
        return array($success, $result);
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zh_ch';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            //     $language = 'id';
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'vi_vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'ko_kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = 'th_th ';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            // case Language_function::PLAYER_LANG_PORTUGUESE :
            //     $language = 'pt';
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            // case Language_function::INT_LANG_PORTUGUESE :
            //     $language = 'hi';
            //     break;
            default:
                $language = 'en_us';
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
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');
        $result = array();

        $this->CI->utils->debug_log("gfg_start_end_date ===> [{$startDate}, {$endDate}]");
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'startDate' => $startDate,
            'endDate' => $endDate,
        );

        $currentPage = 0;
        $params = array(
            "agent" => $this->agent,
            "companyKey" => $this->company_key,
            "startTime" => $startDate,
            "endTime" => $endDate,
            "size" => self::DEFAULT_MAX_SIZE,
            // "size" => 100,
            "page" => $currentPage,
            "timestamp" => $this->utils->getTimestampNow() * 1000
        );

        $result[] = $rlt =  $this->callApi(self::API_syncGameRecords, $params, $context);
        $next = (isset($rlt['success']) && isset($rlt['next'])) ? $rlt['next'] : false;
        while($next) {
            $params['page'] = $currentPage = $currentPage + 1;
            $result[] = $rlt2 = $this->callApi(self::API_syncGameRecords, $params, $context);
            $next = (isset($rlt2['success']) && isset($rlt2['next'])) ? $rlt2['next'] : false;
        }

        return array('success' => true, $result);
    }

    /**
     * overview : processing result for syncgameRecords
     *
     * @param $params
     * @return array
     */
    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array(
            'data_count_update'=> 0,
            'data_count_insert'=> 0
        );
        if($success) {
            $gameRecords = $resultArr['data']['bets'];
            $result['bet_count'] = count($gameRecords);
            $result['next'] = count($gameRecords) > 0 ? true : false;
            // echo "<pre>";
            // print_r($gameRecords);exit();
            if(!empty($gameRecords)) {
                $this->processGameRecords($gameRecords, $responseResultId);
                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    self::ORIGINAL_LOGS_TABLE_NAME,
                    $gameRecords,
                    'externalUniqueId',
                    'externalUniqueId',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5Sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

                if (!empty($insertRows)) {
                    $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);
            }
        }
        return array($success, $result);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $insertRecord = array();
                //Data from GFG API
                $insertRecord['gameId']       = isset($record['gameId']) ? $record['gameId'] : NULL;
                $insertRecord['account']         = isset($record['account']) ? $record['account'] : NULL;
                $insertRecord['accountId']     = isset($record['accountId']) ? $record['accountId'] : NULL;
                $insertRecord['platform']       = isset($record['platform']) ? $record['platform'] : NULL;
                $insertRecord['roundId']       = isset($record['roundId']) ? $record['roundId'] : NULL;
                $insertRecord['gameResult']         = isset($record['gameResult']) ? $record['gameResult'] : NULL;
                $insertRecord['fieldId']     = isset($record['fieldId']) ? $record['fieldId'] : NULL;
                $insertRecord['tableId']       = isset($record['tableId']) ? $record['tableId'] : NULL;
                $insertRecord['chair']       = isset($record['chair']) ? $record['chair'] : NULL;
                $insertRecord['bet']       = isset($record['bet']) ? $record['bet'] : NULL;
                $insertRecord['validBet']       = isset($record['validBet']) ? $record['validBet'] : NULL;
                $insertRecord['win']       = isset($record['win']) ? $record['win'] : NULL;
                $insertRecord['lose']       = isset($record['lose']) ? $record['lose'] : NULL;
                $insertRecord['fee']       = isset($record['fee']) ? $record['fee'] : NULL;
                $insertRecord['enterMoney']       = isset($record['enterMoney']) ? $record['enterMoney'] : NULL;
                $insertRecord['createTime']     = isset($record['createTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['createTime']))) : NULL;
                $insertRecord['roundBeginTime']     = isset($record['roundBeginTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['roundBeginTime']))) : NULL;
                $insertRecord['roundEndTime']     = isset($record['roundEndTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['roundEndTime']))) : NULL;
                $insertRecord['ip']       = isset($record['ip']) ? $record['ip'] : NULL;
                $insertRecord['uid']       = isset($record['uid']) ? $record['uid'] : NULL;
                $insertRecord['orderId']       = isset($record['orderId']) ? $record['orderId'] : NULL;
                $insertRecord['adjustInfo']       = isset($record['adjustInfo']) && is_array($record['adjustInfo'])? json_encode($record['adjustInfo']) : NULL;

                //extra info from SBE
                $insertRecord['externalUniqueId'] = $insertRecord['roundId']; //add external_uniueid for og purposes
                $insertRecord['responseResultId'] = $responseResultId;
                $gameRecords[$index] = $insertRecord;
                unset($insertRecord);
            }
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updatedAt'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['createdAt'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        $queryMethod = 'queryOriginalGameLogs';

        if(!$this->enable_merging_rows){
            $queryMethod = 'queryOriginalGameLogsFromTrans';
        }
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, $queryMethod],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
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
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime='gfg.created_at >= ? and gfg.created_at <= ?';
        $sql = <<<EOD
SELECT gfg.id as sync_index,
gfg.external_unique_id as external_uniqueid,
gfg.created_at AS start_at,
gfg.created_at AS end_at,
gfg.created_at AS bet_at,
gfg.game_id AS game_code,
gfg.game_id AS game,
gfg.response_result_id as response_result_id,
CASE
    WHEN gfg.transaction_type='LB' THEN gfg.lock_money - gfg.money 
    WHEN gfg.transaction_type='UB' THEN gfg.lock_money + gfg.money 
END as result_amount,
CASE WHEN gfg.transaction_type='LB' THEN gfg.money ELSE 0 END as bet_amount, 
CASE WHEN gfg.transaction_type='LB' THEN gfg.money ELSE 0 END as real_bet_amount, 
gfg.md5_sum,
gfg.status as status,
gfg.order_id as round_number,
gfg.after_balance,
gfg.player_id,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM gfg_seamless_transactions as gfg
LEFT JOIN game_description as gd ON gfg.game_id = gd.game_code AND gd.game_platform_id = ?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(),
        $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

     /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime='gfg.createTime >= ? and gfg.createTime <= ?';
        $sql = <<<EOD
SELECT gfg.id as sync_index,
gfg.externalUniqueId as external_uniqueid,
gfg.roundBeginTime AS start_at,
gfg.roundEndTime AS end_at,
gfg.roundBeginTime AS bet_at,
gfg.gameId AS game_code,
gfg.gameId AS game,
gfg.responseResultId as response_result_id,
gfg.lose AS result_amount,
gfg.validBet AS bet_amount,
gfg.bet AS real_bet_amount,
gfg.md5Sum as md5_sum,
gfg.tableId as table_id,
gfg.gameResult as status,
gfg.fee,
gfg.roundId as round_number,
(gfg.enterMoney + gfg.lose) AS after_balance,
game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM gfg_seamless_game_logs as gfg
LEFT JOIN game_description as gd ON gfg.gameId = gd.game_code AND gd.game_platform_id = ?
JOIN game_provider_auth ON SUBSTRING_INDEX(gfg.account, '_', -1) = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
        $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
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
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $result_amount = isset($row['result_amount']) ? $row['result_amount'] : 0;
        return  [
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
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => $result_amount,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['real_bet_amount']) ? $row['real_bet_amount'] : 0,
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
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
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

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    public function queryGamelist(){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGamelist',
        );

        $params = array(
            "agent" => $this->agent,
            "companyKey" => $this->company_key,
            "timestamp" => $this->utils->getTimestampNow() * 1000
        );

        $this->CI->utils->debug_log('GFG: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGamelist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        return array($success, $resultArr);
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
abs(t.after_balance - t.before_balance) as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.order_id as round_no,
t.transaction_type as trans_type,
t.external_unique_id as external_uniqueid,
if((t.after_balance - t.before_balance) < 0, 1002, 1001) as transaction_type,
CONCAT("before lock balance is ", t.before_lock_balance, " and after lock balance is ", t.after_lock_balance) as note
FROM gfg_seamless_transactions as t
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;
EOD;

$params=[$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
}
