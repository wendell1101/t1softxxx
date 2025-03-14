<?php
/**
 * Ameba Integration
 * OGP-28516
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
     - ameba_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt.php';

class Game_api_ameba_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'ameba_seamless_wallet_transactions';
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const ERROR_CODE_SUCCESS = "OK";
    const ERROR_CODE_DUPLICATE_ACCOUNT = "PlayerAlreadyExists";
    const ACTION_BET = "bet";
    const ACTION_PAYOUT = "payout";

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "BRL");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 'en');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');
        $this->api_key = $this->getSystemInfo('api_key', 's_rmCx3aVFZt9hw6D1-JS7w-DcJnI6v3');
        $this->site_id = $this->getSystemInfo('site_id', '7856');
        $this->no_fullscreen = $this->getSystemInfo('no_fullscreen', true);
        $this->enabled_game_logs_unsettle = $this->getSystemInfo('enabled_game_logs_unsettle', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    const URI_MAP = array(
        self::API_createPlayer => '/ams/api',
        self::API_queryDemoGame => '/ams/api',
        self::API_queryForwardGame => '/ams/api',
    );

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getSeamlessTransactionTable() {
        return $this->original_transaction_table;
    }

    public function getPlatformCode() {
        return AMEBA_SEAMLESS_GAME_API;
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
    }

    /**
     * If your API required some headers on every request, we can add it to this method
     *  
     * @param array $params
     * 
     * @return array $headers the headers of your request store in key => value pair
     */
    protected function getHttpHeaders($params)
    {
        $token = $this->generateJwtToken($params,$this->api_key);
        $headers['Content-Type'] = 'application/json';
        $headers['Authorization'] = 'Bearer '.$token;
        return $headers;
    }

    protected function generateJwtToken($payload,$secret_key)
    {
        $jwt = new JWT;
        $generated_jwt_token = $jwt->encode($payload,$secret_key);
        return $generated_jwt_token;
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

    public function processResultBoolean($responseResultId, $resultArr) { 
        $success = false;
        if(isset($resultArr['error_code']) && ($resultArr['error_code'] == self::ERROR_CODE_SUCCESS || $resultArr['error_code'] == self::ERROR_CODE_DUPLICATE_ACCOUNT)){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('ameba Seamless got error ', $responseResultId,'result', $resultArr);
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
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );

        $params = array(
            "action" => 'create_account',
            "site_id" => $this->site_id,
            "account_name" => $gameUsername,
            "currency" => $this->currency
        );

        $this->CI->utils->debug_log('ameba create player params: ' . json_encode($params));
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    /**
     * overview : process result for createPlayer
     *
     * @param $params
     * @return array
     */
    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        $this->CI->utils->debug_log('create player result: ' . json_encode($resultJsonArr));

        $result = array();
        if ($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        return array($success, $result);
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
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $lang = $this->getLanguage($extra['language']);
        $token = $this->getPlayerTokenByUsername($playerName);
        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            $this->appendCurrentDbOnUrl($this->lobby_url);
        }

        if (isset($extra['extra']['home_link'])) {
            $this->lobby_url = $extra['extra']['home_link'];
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            "action" => 'register_token',
            "site_id" => $this->site_id,
            "account_name" => $gameUsername,
            "game_id" => $game_code,
            "lang" => $lang,
            "sessionid" => $token,
            "exit_url" => $this->lobby_url,
            "noFullscreen" => $this->no_fullscreen
        );

        $this->CI->utils->debug_log('ameba queryForwardGame params: ' . json_encode($params));
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        $this->CI->utils->debug_log('queryForwardGame result: ' . json_encode($resultJsonArr));
        $url = null;
        if($success){
            $url = isset($resultJsonArr['game_url']) ? $resultJsonArr['game_url']: null;
        }
        $result = array(
            'url' => $url
        );
        return array($success, $result);
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zhCN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            //     $language = 'id';
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'viVN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'koKR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = 'thTH';
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
                $language = 'enUS';
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

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=$this->enabled_game_logs_unsettle;
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
    }

        /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime="ameba.time >= ? AND ameba.time <= ? AND ameba.action = ?";
        if(!$this->enable_merging_rows){
            $sqlTime="ameba.time >= ? AND ameba.time <= ? AND (ameba.action = ? OR ameba.action = ?)";
        }

        $sql = <<<EOD
SELECT
ameba.id as sync_index,
ameba.response_result_id,
ameba.external_unique_id as external_uniqueid,
ameba.md5_sum,
ameba.player_id,
ameba.bet_amt as bet_amount,
ameba.sum_payout_amt,
ameba.action,
ameba.game_id as game_code,
ameba.game_id as game,
ameba.game_id as game_name,
ameba.round_id as round_number,
ameba.response_result_id,
ameba.json_request,
ameba.time as start_at,
ameba.time as end_at,
ameba.before_balance,
ameba.after_balance,
ameba.tx_id,
ameba.status,
ameba.md5_sum,
gd.id as game_description_id,
gd.game_type_id

FROM ameba_seamless_transactions as ameba
LEFT JOIN game_description as gd ON ameba.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            self::ACTION_PAYOUT
        ];

        if(!$this->enable_merging_rows){
            $params[] = self::ACTION_BET;
        }

        $this->CI->utils->debug_log('merge sql', $sql, $params);

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

        if(!$this->enable_merging_rows){
            if($row['action'] == self::ACTION_BET){
                $row['sum_payout_amt'] = 0;
            }else{
                $row['bet_amount'] = 0;
            }
            $row['result_amount'] = $row['sum_payout_amt'] - $row['bet_amount'];
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
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
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
            'status' => $row['status'],
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

        $row['status']  = Game_logs::STATUS_SETTLED;
        if($this->enabled_game_logs_unsettle){
            $round_details = $this->queryRoundDetails($row['round_number']);
            $row['bet_amount'] = !empty($row['bet_amount']) ? $row['bet_amount'] : $round_details['bet_amount'];
            $row['status'] = $round_details['count_bet'] > 0 ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;
        }
        $row['result_amount'] = $row['sum_payout_amt'] - $row['bet_amount'];
        $row['md5_sum'] = md5($row['status'].$row['result_amount']);
    }

    public function queryRoundDetails($roundid) {
        $this->CI->load->model('original_game_logs_model');
        $sqlRound="ameba.round_id = ?";

        $sql = <<<EOD
SELECT
sum(ameba.bet_amt) as bet_amount,
sum(if(ameba.action ="bet", 1,0)) as count_bet,
round_id

FROM ameba_seamless_transactions as ameba
WHERE
{$sqlRound}
EOD;
        $params=[
            $roundid
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;  
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

    public function queryTransactionByDateTime($startDate, $endDate) {
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = 'ameba_seamless_transactions';

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.time as transaction_date,
t.bet_amt as bet_amount,
t.payout_amt as payout_amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.tx_id as transaction_id,
t.external_unique_id as external_uniqueid,
t.transaction_type as trans_type,
t.json_request as extra_info
FROM {$original_transactions_table} as t
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc, t.id asc;
EOD;

        $params=[
            $startDate,
            $endDate
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions) {
        $temp_game_records = [];

        if (!empty($transactions)) {
            foreach($transactions as $transaction) {
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = $transaction['trans_type'] == self::ACTION_PAYOUT ? abs($transaction['payout_amount']) : abs($transaction['bet_amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];

                if (empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])) {
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                //$extra_info = @json_encode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if ($transaction['trans_type'] == self::ACTION_BET) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                if (isset($transaction['transaction_type'])) {
                    $temp_game_record['transaction_type'] = $transaction['transaction_type'];
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}
