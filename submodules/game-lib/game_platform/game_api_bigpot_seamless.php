<?php
/**
 * Bigpot Gaming Integration Manual
 * OGP-28086
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
     - bigpot_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_bigpot_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const ERROR_CODE_SUCCESS = 1;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        // $this->lang = $this->getSystemInfo('lang');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "BRL");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 'en');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url', "https://stg-game.bigpotgaming.com");
        $this->api_key = $this->getSystemInfo('api_key', '639fd94cef057');
        $this->op_code = $this->getSystemInfo('op_code', '3796');
        $this->demo_url = $this->getSystemInfo('demo_url', 'https://free.bigpotgaming.com');

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->transaction_table_name = $this->getSystemInfo('transaction_table_name', 'common_seamless_wallet_transactions');
    }

    const URI_MAP = array(
        
    );

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return BIGPOT_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));   
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalPayoutRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/bigpot_service_api/result";
            return $url;
        }

        if($apiName == self::API_triggerInternalRefundRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/bigpot_service_api/result";
            return $url;
        }

        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if(@$statusCode == 200 && isset($resultArr['status']) && $resultArr['status'] == self::ERROR_CODE_SUCCESS){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('bigpot Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for bigpot Game";
        if($return){
            $success = true;
            $message = "Successfull create account for bigpot Game.";
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


    public function queryForwardGame($playerName, $extra = null) {
        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API (queryForwardGame)', $extra);
        $token = $this->getPlayerTokenByUsername($playerName);
        $lang = $this->getLanguage($extra['language']);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        if($game_code == "_null"){
            $game_code = null;
        }

        if(isset($extra['game_mode']) && $extra['game_mode'] != 'real'){
            #sample format https://free.bigpotgaming.com/game/slot/AA?lang=en&currency=usd
            list($gcode, $game) = $this->getGcodeByGameCode($game_code);
            $demo_params = [
                'lang'      => $lang,
                'currency'  => $this->currency
            ];
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API (demo_params)', $demo_params);

            if(strtolower($gcode) == "slot"){
                $demo_url = $this->demo_url ."/game/{$gcode}/{$game_code}?".http_build_query($demo_params);
            } else {
                $demo_url = $this->demo_url ."/run/{$gcode}/{$game_code}?".http_build_query($demo_params);
            }
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API queryForwardGame demo_url', $demo_url);
            return [
                'success' => true,
                'url' => $demo_url,
            ];
        }

        list($gcode, $game) = $this->getGcodeByGameCode($game_code);
        $params = array(
            "currency" => $this->currency,
            "gcode" => $gcode,
            "lang" => $lang,
            "opcode" => $this->op_code,
            "token" => $token
        );
        if(!empty($game_code)){
           $params['tname'] =  $game_code;
        }
        $params['hash'] = $this->generateHash($params);
        $url = $this->game_launch_url . "/prod/{$game}?" . http_build_query($params);
        $result = array(
            'success' => true,
            'url' => $url
        );
        return $result;
    }

    public function getGcodeByGameCode($gameCode){
        $this->CI->load->model('game_description_model');
        if($gameCode == "_null"){
            $gameCode = null;
        }
        $gcode = "LOBBY"; #default
        $game = "lobby"; #default
        if(!empty($gameCode)){
            $attributes = $this->CI->game_description_model->queryAttributeByGameCode($this->getPlatformCode(), $gameCode);
            $attributes = json_decode($attributes, true);
            $gcode = isset($attributes['gcode']) ? $attributes['gcode'] : $gcode;
            $game = isset($attributes['game']) ? $attributes['game'] : $game;
        }
        return [$gcode, $game];
    }

    public function getGsymbolByGameCode($gameCode){
        $this->CI->load->model('game_description_model');
        $gsymbol = "LOBBY"; #default|
        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API gameCode', $gameCode);
        if($gameCode && $gameCode != "_null"){
            $attributes = $this->CI->game_description_model->queryAttributeByGameCode($this->getPlatformCode(), $gameCode);
            $attributes = json_decode($attributes, true);
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API gamelist_attributes', $attributes);
            $gsymbol = isset($attributes['demo_game_launch_code']) ? $attributes['demo_game_launch_code'] : $gsymbol;
        }
        return $gsymbol;
    }

    public function processResultForQueryForwardGame($params) {
        
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'vi';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
            //     $language = 'ko';
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
            case 'pt-br':
            case 'pt-BR':
            case 'pt':
            case 'pt-pt':
            case 'pt-PT':
                $language = 'pt';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case Language_function::PLAYER_LANG_SPANISH :
            case 'es':
            case 'es-es':
            case 'es-ES':
                $language = 'es';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case Language_function::INT_LANG_PORTUGUESE :
                $language = 'hi';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function generateHash($params){
        $params = array_filter($params, function($value) { return !is_null($value) && $value !== ''; });
        $hash = isset($params['hash']) ? $params['hash'] : null;
        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API generateHash REQUEST HASH >>>>>>', $hash);
        ksort($params);
        if(isset($params['jwt'])){
            unset($params['jwt']);
        }
        if(isset($params['hash'])){
            unset($params['hash']);
        }
        $values = array_values($params);
        $string = implode('', $values). $this->api_key;
        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API generateHash REQUEST GENERATED STRING >>>>', $string);
        return MD5($string);
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;

        if ($this->enable_merging_rows) {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTransMerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                [$this, 'preprocessOriginalRowForGameLogsMerge'],
                $enabled_game_logs_unsettle
            );
        } else {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle
            );
        }
    }

        /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = false)
    {
        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('amount', 'transaction.after_balance', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
    transaction.id AS sync_index,
    transaction.bet_amount,
    transaction.result_amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.player_id,
    transaction.game_id,
    transaction.game_id as game_code,
    transaction.game_id as game_name,
    transaction.transaction_type,

    transaction.status,
    transaction.response_result_id,
    transaction.external_unique_id AS external_uniqueid,
    transaction.transaction_id AS round_number,
    transaction.extra_info,
    transaction.start_at,
    transaction.end_at,
    transaction.updated_at,
    MD5(CONCAT({$md5Fields})) AS md5_sum,

    game_description.id AS game_description_id,
    game_description.game_type_id,
    game_description.english_name AS game

FROM 
    {$this->transaction_table_name} AS transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE
    transaction.game_platform_id = ? AND {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) 
    {
        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_id'],
                'game_type' => null,
                'game' => $row['game'],
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

            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
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
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        #set bet amount as absolute value
        $row['bet_amount'] = abs($row['bet_amount']);
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


    public function triggerInternalPayoutRound($params = '{"test":true}'){
        $params = json_decode($params, true);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
        ];
        $this->is_get_method = false;
        $apiName = self::API_triggerInternalPayoutRound;
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $success =  isset($resultArr['code']) ? true : false;  
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }

    public function triggerInternalRefundRound($params = '{"test":true}'){
        $params = json_decode($params, true);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalRefundRound',
        ];
        $this->is_get_method = false;
        $apiName = self::API_triggerInternalRefundRound;
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForTriggerInternalRefundRound($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $success =  isset($resultArr['code']) ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }

    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time = false)
    {
        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('amount', 'transaction.after_balance', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
    transaction.id AS sync_index,
    transaction.result_amount AS bet_amount,
    SUM(transaction.result_amount) AS result_amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.player_id,
    transaction.game_id,
    transaction.game_id as game_code,
    transaction.game_id as game_name,
    transaction.transaction_type,
    transaction.status,
    transaction.response_result_id,
    transaction.external_unique_id AS external_uniqueid,
    transaction.transaction_id AS round_number,
    transaction.extra_info,
    transaction.start_at,
    transaction.end_at,
    transaction.updated_at,
    MD5(CONCAT({$md5Fields})) AS md5_sum,

    game_description.id AS game_description_id,
    game_description.game_type_id,
    game_description.english_name AS game,

    game_provider_auth.login_name as player_username,

    CASE WHEN COUNT(CASE WHEN transaction_type = 'result' THEN 1 END) > 0 THEN 1 ELSE 0 END AS settled

FROM 
    {$this->transaction_table_name} AS transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id AND game_provider_auth.game_provider_id = ?
WHERE 
    transaction.game_platform_id = ? AND {$sqlTime}
    GROUP BY round_id;
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . '===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row) {
        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_id'],
                'game_type' => null,
                'game' => $row['game'],
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
            'status' => $row['settled'] ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING,
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

            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsMerge(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        #set after balance
        $row['after_balance'] = $row['before_balance'] + $row['result_amount'];

        #set bet amount as absolute value
        $row['bet_amount'] = abs($row['bet_amount']);
    }
}
