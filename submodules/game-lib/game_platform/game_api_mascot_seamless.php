<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
// require_once dirname(__FILE__) . '/../vendor/autoload.php';

use mascotgaming\mascot\api\client\Client;

/**
 * MASCOT Single Wallet API Document
 * OGP-33037
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
     - mascot_service_api.php
 */


class Game_api_mascot_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'mascot_seamless_wallet_transactions';
    const ORIGINAL_LOGS_TABLE_NAME = 'mascot_seamless_game_logs';
    const POST = 'POST';
    const GET = 'GET';
    
    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url'); #ex: https://api.mascot.games/v1/
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "BRL");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', "en");
        $this->caller_id = $this->getSystemInfo('caller_id');
        $this->jsonrpc_version = $this->getSystemInfo('jsonrpc_version', "2.0");
        $this->bank_name = $this->getSystemInfo('bank_name');
        $this->ssl_key_path = $this->getSystemInfo('ssl_key_path', '/home/vagrant/Code/og/secret_keys/mascot_ssl');
        $this->ssl_pem_filename = $this->getSystemInfo('ssl_pem_filename', 'apikey.pem');
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        $this->home_url_target = $this->getSystemInfo('home_url_target', "self");
        $this->demo_start_balance = $this->getSystemInfo('demo_start_balance', 100);
        
        $this->list_of_method_for_force_error = $this->getSystemInfo('list_of_method_for_force_error', []);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->original_transaction_table_name = 'mascot_seamless_wallet_transactions';
        $this->get_all_trans = true;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return MASCOT_SEAMLESS_GAME_API;
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));  
        $this->utils->debug_log('MASCOT Request Field: ',http_build_query($params));
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if(!empty($resultArr) && (!isset($resultArr['error']))){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('MASCOT GAME API got error: ', $responseResultId,'result', $resultArr, $statusCode);
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
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'jsonrpc' => $this->jsonrpc_version,
            'method' => "Player.Set",
            'id' =>  $this->utils->getRequestId(),
            'params' => array(
                "Id" => $gameUsername,
                "BankGroupId" => $this->bank_name,
            )
        );

        $this->CI->utils->debug_log("CreatePlayer params ============================>", $params);
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array($success, $resultArr);
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

    public function initSSL($ch) 
    {
        parent::initSSL($ch);
        $ssl_key_path = realpath($this->ssl_key_path);
        $this->CI->utils->debug_log('MASCOT SSL KEY PATH', $ssl_key_path);
        $pem = "{$ssl_key_path}/{$this->ssl_pem_filename}";
        curl_setopt($ch, CURLOPT_SSLCERT, $pem);
    }

    public function createAgent(){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateAgent',
        );

        $params = array(
            'jsonrpc' => $this->jsonrpc_version,
            'method' => "BankGroup.Set",
            'id' =>  $this->utils->getRequestId(),
            'params' => array(
                "Id" => $this->bank_name,
                "Currency" => $this->currency,
            )
        );
        return $this->callApi(self::API_createAgent, $params, $context);
    }

    public function processResultForCreateAgent($params) {;
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        return array($success, $resultArr);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $apiName = self::API_queryForwardGame;
            $method = "Session.Create";
            $isDemo = false;
        } else{
            $apiName = self::API_queryDemoGame;
            $method = "Session.CreateDemo";
            $isDemo = true;
        }
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'playerId' => $this->getPlayerIdFromUsername($playerName),
            'gameUsername' => $gameUsername,
        );

        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            $this->appendCurrentDbOnUrl($this->lobby_url);
        }

        if(empty($this->cashier_url)){
            $this->cashier_url = $this->utils->getSystemUrl('player','/player_center/dashboard/cashier#memberCenter');
            $this->appendCurrentDbOnUrl($this->cashier_url);
        }

        if (isset($extra['extra']['home_link'])) {
            $this->lobby_url = $extra['extra']['home_link'];
        }

        if (isset($extra['extra']['cashier_link'])) {
            $this->cashier_url = $extra['extra']['cashier_link'];
        }

        // $enableHomeLink = "true";
        #removes homeUrl if disable_home_link is set to TRUE
        if((isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) || $this->force_disable_home_link) {
            // $enableHomeLink = "false";
            $this->lobby_url = "false";
        }
        $params = array(
            'jsonrpc' => $this->jsonrpc_version,
            'method' => $method,
            'id' =>  $this->utils->getRequestId(),
            'params' => array(
                "PlayerId" => $gameUsername,
                "GameId" => $extra['game_code'],
                "Params" => array(
                    "language" => $this->getLanguage($extra['language']),
                    "home_url" => $this->lobby_url,
                    "home_url_target" => $this->home_url_target
                )
            )
        );

        if($isDemo){
            unset($params['params']['PlayerId']);
            $params['params']['BankGroupId'] = $this->bank_name;
            $params['params']['StartBalance'] = $this->dBtoGameAmount($this->demo_start_balance);
        }

        $this->CI->utils->debug_log('MASCOT: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        // print_r($resultArr);exit();
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        // $result = array();

        if($success){
            if(isset($resultArr['result']['SessionUrl']))
            {
                $resultArr['url'] = $resultArr['result']['SessionUrl'];
            }
        }

        return array($success, $resultArr);
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
            case 'zh-CN':
                $language = "cn";
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            //     $language = 11;
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
            //     $language = 10;
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
            //     $language = 3;
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_THAI:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
            //     $language = 5;
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE :
            case 'pt-PT':
            case 'pt-BR':
                $language = "pt";
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case LANGUAGE_FUNCTION::PLAYER_LANG_SPANISH :
            case 'es-ES':
                $language = "es";
                break;
            default:
                $language = "en";
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
        $this->get_all_trans = false;
        $enabled_game_logs_unsettle=true;
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $md5Fields = implode(", ", array('mascot.sbe_status', 'mascot.withdraw', 'mascot.deposit', 'mascot.updated_at'));
        $reFundStatus = Game_logs::STATUS_REFUND;
        $settledStatus = Game_logs::STATUS_SETTLED;
        if($this->use_monthly_transactions_table){            
            $start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            $results = [];
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName=$this->original_transaction_table_name.'_'.$yearMonthStr;
                $sqlTime="mascot.updated_at >= ? AND mascot.updated_at <= ?";

        $sql = <<<EOD
SELECT
mascot.id as sync_index,
mascot.external_uniqueid,
MD5(CONCAT({$md5Fields})) AS md5_sum,

mascot.player_id,
(mascot.deposit - mascot.withdraw)  as result_amount,
mascot.withdraw as bet_amount,
mascot.method,
mascot.game_id as game_code,
mascot.game_id as game,
mascot.game_id as game_name,
mascot.game_round_ref as round_number,
mascot.created_at as start_at,
mascot.created_at as bet_at,
mascot.updated_at as end_at,
mascot.before_balance,
mascot.after_balance,
if(mascot.sbe_status = {$reFundStatus}, {$reFundStatus}, {$settledStatus})  as status,

gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as mascot
LEFT JOIN game_description as gd ON mascot.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
                $params=[
                    $this->getPlatformCode(),
                    $dateFrom,
                    $dateTo
                ];

                $this->CI->utils->debug_log('merge sql', $sql, $params);

                $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                $results = array_merge($results, $monthlyResults);
            }
            
            if(!$this->get_all_trans){
                $results = array_values(array_filter($results, function($row) { return $row['method'] != 'rollbackTransaction'; }));
            }
            
            return $results;
        }
        
        $tableName = $this->getTransactionsTable();
        $sqlTime="mascot.updated_at >= ? AND mascot.updated_at <= ?";

        $sql = <<<EOD
SELECT
mascot.id as sync_index,
mascot.external_uniqueid,
MD5(CONCAT({$md5Fields})) AS md5_sum,

mascot.player_id,
(mascot.deposit - mascot.withdraw)  as result_amount,
mascot.withdraw as bet_amount,
mascot.method,
mascot.game_id as game_code,
mascot.game_id as game,
mascot.game_id as game_name,
mascot.game_round_ref as round_number,
mascot.created_at as start_at,
mascot.created_at as bet_at,
mascot.updated_at as end_at,
mascot.before_balance,
mascot.after_balance,
if(mascot.sbe_status = {$reFundStatus}, {$reFundStatus}, {$settledStatus})  as status,

gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as mascot
LEFT JOIN game_description as gd ON mascot.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        if(!$this->get_all_trans){
            $results = array_values(array_filter($results, function($row) { return $row['method'] != 'rollbackTransaction'; }));
        }
        return $results;
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
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'end_at'],
                ['amount_currency', ' amount']);
        }

        return [
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
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['end_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
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

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table;
        }

        $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like mascot_seamless_wallet_transactions');

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $results = $this->queryOriginalGameLogs($startDate, $endDate);
        array_walk($results, function($rows, $key) use(&$results){
            $results[$key]['transaction_date'] = $rows['start_at'];
            $results[$key]['amount'] = abs($rows['result_amount']);#override amount value
            $results[$key]['round_no'] = $rows['round_number'];
            $results[$key]['trans_type'] = $rows['method'];
            if($rows['method'] == "rollbackTransaction"){
                $rows['result_amount'] = $rows['after_balance'] - $rows['before_balance'];
                $results[$key]['amount'] = abs($rows['result_amount']); 
            }
            if($this->utils->compareResultFloat($rows['result_amount'], '>=', 0)){
                $results[$key]['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
            } else if($this->utils->compareResultFloat($rows['result_amount'], '<', 0)){
                $results[$key]['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
            }
        });
        return $results;
    }
}