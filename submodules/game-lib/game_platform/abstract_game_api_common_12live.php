<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: 12Live
* Game Type: Slots
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph

    Related File
     - betgames_seamless_service_api.php
     - 
    
**/

abstract class Abstract_game_api_common_12live extends Abstract_game_api {

    private $player_name = '';

    const MD5_FIELDS_FOR_ORIGINAL= [
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'after_balance',
        'before_balance'
    ];

    const URI_MAP = [
        self::API_createPlayer => '/account/createplayer',
        self::API_syncGameRecords    => '/api/v2/bet-transaction/',
        self::API_queryPlayerBalance     => '/api/v2/balance/',
        self::API_queryTransaction => '/api/v2/transfer-status/',
        self::API_login => '/account/login',
        self::API_generateToken => '/account/refreshtoken',
        self::API_queryForwardGame => '/player/getgameurl',
    ];

    const STATUS_BET = "bet";
    const STATUS_CANCEL_BET = "cancelbet";
    const STATUS_SETTLED = "settle";

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('api_key');
        $this->agent_name = $this->getSystemInfo('agent_name');
        $this->provider_id = $this->getSystemInfo('provider_id', 11);
        $this->language = $this->getSystemInfo('language', 'en-us');

        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');   
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 15);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 10);
        $this->use_transaction = $this->getSystemInfo('use_transaction', true);
        $this->main_provider_id = $this->getSystemInfo('main_provider_id',LIVE12_SEAMLESS_GAME_API);
        $this->sub_provider_list = $this->getSystemInfo('sub_provider_list', array(LIVE12_PGSOFT_SEAMLESS_API,LIVE12_SPADEGAMING_SEAMLESS_API,LIVE12_REDTIGER_SEAMLESS_API,LIVE12_EVOLUTION_SEAMLESS_API));

        $this->auth_header = false;
    }

    public function isSeamLessGame()
    {
       return true;
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        return $this->api_url . self::URI_MAP[$apiName];
    }

    public function getHttpHeaders($params){
        $headers = [];
        if($this->auth_header) {
            $clone = clone $this;
            $token = $clone->getAvailableApiToken();
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ];
        } else {
            $headers = [
                'Content-Type' => 'application/json'
            ];
        }
        // print_r($this->auth_header);exit
        // print_r($headers);

        return $headers;

    }

    protected function customHttpCall($ch, $params) {

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    }

    public function generateCacheKeyOfApiToken(){
        return '_game-api-token-5865'.'-'.$this->player_name;
    }

    /**
     * will check timeout, if timeout then call again
     * @return token
     */
    public function getAvailableApiToken(){
        $token = $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
        $this->CI->utils->debug_log("PGSOFT (Token)",$token);
        return $token;
    }

    private function generateToken() {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken'
        );

        $params = array(
            'ApiKey' => $this->api_key,
            'UserName' => $this->player_name,
            'AgentName' => $this->agent_name
        );

        $this->auth_header = false;

        $this->CI->utils->debug_log('PGSOFT: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForGenerateToken($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

        if ($success) {
            // $this->_access_token = $resultArr['access_token'];
                $this->auth_header = true;
            if($resultArr['data']){
                $token_timeout = new DateTime($this->utils->getNowForMysql());
                $minutes = (1440/60)-1;
                $token_timeout->modify("+".$minutes." minutes");
                $result['api_token']=$resultArr['data'];
                $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
            } 
        }

        $this->CI->utils->debug_log('PGSOFT: (' . __FUNCTION__ . ')', 'success:', $success, 'RETURN:', $success, $resultArr);

        return array($success, $result);
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) {
        $success = false;
        if(($statusCode == 200 || $statusCode == 201) || $resultArr['error'] == 0){
            $success=true;
        }

        // if($resultArr['result']['code'] == self::EXPIRED_TOKEN) {
        //     $this->expired_token = true;
        // }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('PGSOFT Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        // create player on game provider auth
        // $extra = [
        //     'prefix' => $this->prefix_for_username,

        //     # fix exceed game length name
        //     'fix_username_limit' => $this->fix_username_limit,
        //     'minimum_user_length' => $this->minimum_user_length,
        //     'maximum_user_length' => $this->maximum_user_length,
        //     'default_fix_name_length' => $this->default_fix_name_length,
        // ];

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId
        );

        $params = array(
            'ApiKey' => $this->api_key,
            'UserName' => $gameUsername,
            'AgentName' => $this->agent_name
        );

        $this->CI->utils->debug_log('PGSOFT: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_createPlayer, $params, $context); 
    }

    public function processResultForCreatePlayer($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('PGSOFT: (' . __FUNCTION__ . ')', 'Result:', $resultArr);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();
        $result['response_result_id'] = $responseResultId;

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $playerBalance = $this->queryPlayerBalance($playerName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $transaction = $this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, NULL,$this->transTypeMainWalletToSubWallet());

        $this->utils->debug_log('<---------------12Live-PGSOFT------------> External Transaction ID: ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $playerBalance = $this->queryPlayerBalance($playerName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, NULL,$this->transTypeSubWalletToMainWallet());

        $this->utils->debug_log('<---------------12Live-PGSOFT------------> External Transaction ID: ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );
    }

    public function queryPlayerBalance($playerName) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        $this->utils->debug_log(__FUNCTION__,'PGSoft (Query Player Balance): ', $result);

        return $result;

    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    protected function getLauncherLanguage($language) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $language = 'zh-cn';
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $language = 'th-th';
                break;
            default:
                $language = 'en-us';
                break;
        }

        return $language;
    }

    /*
     * Game platform provider id's for 12Live API
     *
     * SpadeGaming - 14
     * PGsoft - 11
     * Evolution - 16
     * Gamatron - 15
     * RedTiger - 10
     */
    public function queryForwardGame($playerName, $extra) {
        
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $game_type = isset($extra['game_type']) ? $extra['game_type'] : 'Slot';
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $provider_id = isset($extra['provider_id']) ? $extra['provider_id'] : $this->provider_id;
        $language = isset($this->language) && !empty($this->language) ? $this->language : $this->getLauncherLanguage($extra['language']);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'] ? "M" : "D";
 
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $this->player_name = $gameUsername;

        $params = array(
            "GameType" => $game_type,
            "GameId" => $game_code,
            "ProviderId" => $provider_id,
            "Language" => $language,
            "Platform" => $is_mobile,
        );

        $this->auth_header = true;

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $params);

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            if(isset($resultArr['data'])){
                $result['url']=$resultArr['data'];
            }else{
                //missing address
                $success=false;
            }
        }

        return [$success, $result];

    }

    public function syncOriginalGameLogs($token) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;

        if($this->use_transaction) {
            return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
        } else {
            return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
        }
    }

    /* queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime='pg.end_at >= ? AND pg.end_at <= ?';

        $sql = <<<EOD
SELECT
pg.id as sync_index,
pg.response_result_id,
pg.external_uniqueid,
pg.md5_sum,

pg.UserId,
pg.UserName as player_username,
pg.OrderTime,
pg.TransGuid,
pg.Stake as bet_amount,
pg.Winlost as result_amount,
pg.TurnOver as real_betting_amount,
pg.Currency,
pg.ProviderId,
pg.ParentId,
pg.GameId as round_number,
pg.ProductType as game_code,
pg.GameType,
pg.TableName as game_name,
pg.PlayType,
pg.ExtraData,
pg.ModifyDate,
pg.WinloseDate,
pg.Status,
pg.ProviderStatus,
pg.CancelledStake,
pg.transaction_status,
pg.transaction_type,
pg.start_at,
pg.start_at as bet_at,
pg.end_at,
pg.before_balance,
pg.after_balance,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM $this->original_gamelogs_table as pg
LEFT JOIN game_description as gd ON pg.ProductType = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON pg.UserName = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;

    }

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        
        $gameRecords = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time);

        $rebuildGameRecords = array();

        $this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);

        return $rebuildGameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo, $use_bet_time) {
        #query bet only
        $sqlTime="pg.end_at >= ? AND pg.end_at <= ? AND pg.game_platform_id = ? AND transaction_type = 'bet'";

        $sql = <<<EOD
SELECT
pg.id as sync_index,
pg.response_result_id,
pg.external_unique_id as external_uniqueid,
pg.md5_sum,
game_provider_auth.login_name as player_username,
pg.player_id,
pg.game_platform_id,
pg.amount as bet_amount,
pg.amount as real_betting_amount,
pg.game_id as game_code,
pg.transaction_type,
pg.status,
pg.round_id as round_number,
pg.response_result_id,
pg.extra_info,
pg.start_at,
pg.start_at as bet_at,
pg.end_at,
pg.before_balance,
pg.after_balance,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as pg
LEFT JOIN game_description as gd ON pg.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_provider_auth 
ON 
	pg.player_id = game_provider_auth.player_id AND 
	game_provider_auth.game_provider_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
        
    }

    public function processGameRecordsFromTrans(&$gameRecords, &$rebuildGameRecords) {

        $round_numbers = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {

                if(!in_array($record['round_number'], $round_numbers)) {

                    

                    $temp_game_records = $record;
                    $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                    $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                    $temp_game_records['player_username'] = isset($record['player_username']) ? $record['player_username'] : null;
                    
                    
                    $bet_amount = $this->queryBetAmount($record['round_number']); // possible to have multiple bet in same round id
                    $temp_game_records['bet_amount'] = $bet_amount;


                    $temp_game_records['real_betting_amount'] = $bet_amount;
                    $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['round_number'] = isset($record['round_number']) ? $record['round_number'] : null;
                    $temp_game_records['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                    $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                    $temp_game_records['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                    $temp_game_records['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                    $temp_game_records['bet_at'] = isset($record['bet_at']) ? $record['bet_at'] : null;
                    $temp_game_records['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                    $temp_game_records['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                    $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                    $temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                    $temp_game_records['result_amount'] = -$temp_game_records['bet_amount'];

                    $result = $this->queryBetResult($temp_game_records['round_number'], $record);   

                    if(!empty($result)) {
                        $temp_game_records['win_amount'] = isset($result['amount']) ? $result['amount'] : null;
                        $temp_game_records['result_amount'] = isset($result['amount']) ? $temp_game_records['win_amount'] - $temp_game_records['bet_amount'] : null;
                        $temp_game_records['after_balance'] = isset($result['after_balance']) ? $result['after_balance'] : $temp_game_records['after_balance'];
                        $temp_game_records['end_at'] = isset($result['end_at']) ? $result['end_at'] : null;
                        $temp_game_records['transaction_type'] = isset($result['transaction_type']) ? $result['transaction_type'] : null;
                    }
                    $temp_game_records['status'] = $this->getGameRecordsStatus($temp_game_records['transaction_type']);

                    if(empty($temp_game_records['md5_sum'])){
                        $this->CI->utils->error_log('no md5 on ', $temp_game_records['external_uniqueid']);
                        $temp_game_records['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($temp_game_records, self::MD5_FIELDS_FOR_MERGE,
                            self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
                    }

                    // $gameRecords[$index] = $temp_game_records;
                    $rebuildGameRecords[] = $temp_game_records;
                    $round_numbers[] = $temp_game_records['round_number'];
                    unset($data);

                }
                
            }

        }

    }

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    public function getGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        switch (strtolower($status)) {
        case self::STATUS_BET:
            $status = Game_logs::STATUS_PENDING;
            break;
        case self::STATUS_CANCEL_BET:
            $status = Game_logs::STATUS_VOID;
            break;
        case self::STATUS_SETTLED:
            $status = Game_logs::STATUS_SETTLED;
            break;
        default:
            $status = Game_logs::STATUS_PENDING;
        }
        return $status;
    }

    public function queryBetAmount($round_id) {

        $sqlTime='pg.round_id = ? and pg.game_platform_id = ? and pg.transaction_type="bet"';

        $sql = <<<EOD
SELECT
pg.id as sync_index,
pg.amount as bet_amount,
pg.end_at,
pg.after_balance,
pg.transaction_type,
pg.game_platform_id
FROM common_seamless_wallet_transactions as pg
WHERE
{$sqlTime}
EOD;

        $params=[
            $round_id,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        
        return $this->processBetAmount($result);


    }

    private function processBetAmount($datas) {

        $this->CI->utils->debug_log('REYNARD DATAS', $datas);

        if(!empty($datas)){

            $result = 0;
            $total_amount = 0;

            foreach($datas as $data) {

                $total_amount += $data["bet_amount"];

                
            
            }

            $result = $total_amount;

            return $result;

        }
    }

    public function queryBetResult($round_id, $betRecord) {

        $sqlTime='pg.round_id = ? and pg.game_platform_id = ? and pg.transaction_type="settle"';

        $sql = <<<EOD
SELECT
pg.id as sync_index,
pg.amount,
pg.end_at,
pg.after_balance,
pg.transaction_type,
pg.game_platform_id
FROM common_seamless_wallet_transactions as pg
WHERE
{$sqlTime}
EOD;

        $params=[
            $round_id,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if(empty($result)){ # if empty , try requery possible rounds associated by player id and game id
            $whereSql='pg.round_id = ? and pg.player_id = ? and pg.game_id = ? and pg.transaction_type="settle"';
            $sql2 = <<<EOD
SELECT
pg.id as sync_index,
pg.amount,
pg.end_at,
pg.after_balance,
pg.game_platform_id,
pg.transaction_type

FROM common_seamless_wallet_transactions as pg
WHERE
{$whereSql}
EOD;
   
    
        $params2=[
            $round_id,
            $betRecord['player_id'],
            $betRecord['game_code'],
        ];

            $settledRounds = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql2, $params2);
            $settledData = [];
            if(!empty($settledRounds)){
                foreach ($settledRounds as $key => $roundData) {
                    if( in_array($roundData['game_platform_id'], $this->sub_provider_list) ||  $roundData['game_platform_id'] == $this->main_provider_id){
                        $settledData[] = $roundData;
                    }
                }
                if(!empty($settledData)){
                    $result = $this->processBetResult($settledData);
                }
            }
        } else{
            $result = $this->processBetResult($result);
        }
        return $result;


    }

    private function processBetResult($datas) {

        if(!empty($datas)){

            $result = array();
            $total_amount = 0;
            $after_balance = 0;
            $end_at = null;
            $game_platform_id = null;
            $transaction_type = null;

            foreach($datas as $data) {

                $total_amount += $data["amount"];

                $after_balance = $data["after_balance"]; // will get the last record
                $end_at = $data["end_at"];  
                $game_platform_id = $data["game_platform_id"];
                $transaction_type = $data["transaction_type"];
            
            }

            $result["amount"] = $total_amount;
            $result["after_balance"] = $after_balance;
            $result["end_at"] = $end_at;
            $result["transaction_type"] = $transaction_type;
            $result["game_platform_id"] = $game_platform_id;

            return $result;

        }


        return null;
        

    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $row['result_amount'] = $row['result_amount'] - $row['bet_amount'];
        
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
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
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

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

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        return $this->returnUnimplemented();
    }

    public function processGameRecords(&$gameRecords) {
        return $this->returnUnimplemented();
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
      
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                
                $extra_info = @json_encode($transaction['extra_info'], true);
                $temp_game_record['round_no'] = isset($extra_info['GameId'])?$extra_info['GameId']:$transaction['round_no'];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = [];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

}