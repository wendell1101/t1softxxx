<?php
/**
 * Skywind game integration
 * OGP-27294
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
     - skywind_seamless_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_skywind_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const ERROR_CODE_SUCCESS = 0;
    const METHOD_POST = "POST";
    const METHOD_GET = "GET";

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->cashier_url = $this->getSystemInfo('bank_url');
        $this->currency = $this->getSystemInfo('currency', "USD");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 'en');
        $this->launch_url = $this->getSystemInfo('launch_url');
        $this->mobile_launch_url = $this->getSystemInfo('mobile_launch_url');
        $this->secret_key = $this->getSystemInfo('secret_key', '78b7225b-84e1-4d48-b45a-de880dc2322f');
        $this->api_username = $this->getSystemInfo('api_username', 'sw_alphabook_api_stg');
        $this->api_password = $this->getSystemInfo('api_password', 'JcX28heU0kvI9gp2');
        $this->merch_id = $this->getSystemInfo('merch_id', 'sw_alphabook_stg');
        $this->merch_password = $this->getSystemInfo('merch_password', '7ywlc9RqubEvdsiw');
        $this->version = $this->getSystemInfo('version', 'v1');

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);


    }

    const URI_MAP = array(
        self::API_login => '/login',
        self::API_queryForwardGame => '/players/{playerCode}/games/{gameCode}',
        self::API_queryDemoGame => '/fun/games/{gameCode}',
        self::API_getGameProviderGamelist => '/games/info/search'
    );

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return SKYWIND_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        if($this->method == self::METHOD_POST){
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-ACCESS-TOKEN: ' . $params['accessToken']));
        }
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {

        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . "/" . $this->version . $apiUri;
        if($apiName == self::API_queryForwardGame){
            $searchVal = array("{playerCode}", "{gameCode}");
            $replaceVal = array("{$params['path']['playerCode']}", "{$params['path']['gameCode']}");
            $url = str_replace($searchVal, $replaceVal, $url);
            $query = http_build_query($params['query']);
            $url .= "?{$query}";
        }

        if($apiName == self::API_queryDemoGame){
            $searchVal = array("{gameCode}");
            $replaceVal = array("{$params['path']['gameCode']}");
            $url = str_replace($searchVal, $replaceVal, $url);
            $query = http_build_query($params['query']);
            $url .= "?{$query}";
        }
        
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if($statusCode == 200){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('skywind Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for skywind Game";
        if($return){
            $success = true;
            $message = "Successfull create account for skywind Game.";
        }
        // $this->updateExternalAccountIdForPlayer($playerId, $playerId);
        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
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

    public function login($playerName = null, $password = null, $extra = null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
        );
        $params = array(
            'secretKey' => $this->secret_key,
            'username' => $this->api_username,
            'password' => $this->api_password,
        );

        $this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('SKYWIND: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];
        if($success){
            $result['token'] = $resultArr['accessToken'];
        }
        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $this->CI->load->model(array('common_token'));
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);
        $language_code = $this->getLanguage($extra['language']);
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $token = $this->CI->common_token->getValidPlayerToken($player_id);
        $player_mode = (isset($extra['game_mode']) && $extra['game_mode'] == 'real') ? "real" : "fun";

        $login = $this->login($playerName);
        if($login['success'] && isset($login['token'])){
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

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryForwardGame',
            );

            $params = array(
                'path' => array(
                    "playerCode" => $game_username,
                    "gameCode" => $extra['game_code'],
                ),
                'query' => array(
                    "ticket" => $token,
                    "playmode" => $player_mode,
                    "language" => $language_code,
                    "lobby" => $this->lobby_url,
                    "cashier" => $this->cashier_url, #http://player.staging.t1bet.t1t.in/player_center/dashboard/cashier#memberCenter
                    "cashier_target_same_tab" => 1
                ),
                'accessToken' => $login['token']
            );
   
            $this->method = self::METHOD_GET;
            $this->CI->utils->debug_log('SKYWIND: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

            if($extra['game_mode'] != 'real'){
                return $this->callApi(self::API_queryDemoGame, $params, $context);
            } else {
                return $this->callApi(self::API_queryForwardGame, $params, $context);
            }
        } else {
            return array("success" => false, array("url" => null));
        } 
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $result = array("url" => null);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        if($success){
            $result['url'] = isset($resultArr['url']) ? $resultArr['url'] : null ;
        } 

        return array($success, $result);
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zh-cn';
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
                $language = 'pt';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            // case Language_function::INT_LANG_PORTUGUESE :
            //     $language = 'hi';
            //     break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
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
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        $sqlTime="skywind.end_at >= ? AND skywind.end_at <= ? AND skywind.game_platform_id = ? AND skywind.transaction_type in('debit','bonus')";

        if(!$this->enable_merging_rows){
            $sqlTime="skywind.end_at >= ? AND skywind.end_at <= ? AND skywind.game_platform_id = ? AND skywind.transaction_type in('debit','bonus','credit')";
        }

        $sql = <<<EOD
SELECT
skywind.id as sync_index,
skywind.response_result_id,
skywind.external_unique_id as external_uniqueid,
skywind.md5_sum,

skywind.player_id,
skywind.game_platform_id,
skywind.bet_amount as bet_amount,
skywind.bet_amount as real_betting_amount,
skywind.result_amount,
skywind.amount,
skywind.transaction_type,
skywind.game_id as game_code,
skywind.game_id as game,
skywind.game_id as game_name,
skywind.round_id as round_number,
skywind.response_result_id,
skywind.extra_info,
skywind.start_at,
skywind.start_at as bet_at,
skywind.end_at,
skywind.before_balance,
skywind.after_balance,
skywind.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as skywind
LEFT JOIN game_description as gd ON skywind.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

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
        
        $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
        $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        

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

        $trans_details = $this->queryTransactionDetails($row['transaction_id']);
        if($this->enable_merging_rows){
            $bet_amount = isset($trans_details['total_bet_amount']) ? $trans_details['total_bet_amount'] : 0;
            $payout_amount = isset($trans_details['total_result_amount']) ? $trans_details['total_result_amount'] : 0;
            $row['bet_amount'] = $bet_amount;
            $row['real_betting_amount'] = $bet_amount;
            $row['result_amount'] = $payout_amount - $bet_amount;
            // $row['bet_amount'] = $trans_details['total_bet_amount'];
            // $row['real_betting_amount'] = $trans_details['total_bet_amount'];
            // $row['result_amount'] = $trans_details['total_result_amount'];
            $row['start_at'] = $trans_details['start_at'];
            $row['end_at'] = $trans_details['end_at'];
            $id_details = $this->queryDetailsById($trans_details['max_id']);
            $row['status'] = $id_details['status'];
            $row['after_balance'] = $id_details['after_balance'];
        }else{
            $id_details = $this->queryDetailsById($trans_details['max_id']);
            $row['status'] = $id_details['status'];

            if($row['transaction_type'] == 'debit'){
                $payout_amount = 0;
            }else{
                $payout_amount = $row['amount'];
                $row['bet_amount'] = 0;
                $row['real_betting_amount'] = 0;
            }
            $row['result_amount'] = $payout_amount - $row['bet_amount'];
        }
    }

    private function queryTransactionDetails($transaction_id){
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
transaction_id,
max(round_id) as purchase_id,
sum( bet_amount ) as total_bet_amount,
sum( result_amount ) as total_result_amount,
max( id ) as max_id,
min( id ) as min_id,
max( end_at ) as end_at,
min( start_at ) as start_at
FROM
common_seamless_wallet_transactions 
WHERE
game_platform_id = ?
AND transaction_id = ?
EOD;
        $params=[
            $this->getPlatformCode(),
            $transaction_id
        ];

        $this->CI->utils->debug_log('queryReserveDetails sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result; 
    }

    private function queryDetailsById($id){
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
status,
after_balance,
extra_info
FROM common_seamless_wallet_transactions as cwt
WHERE
cwt.id = ?
EOD;
        $params=[
            $id
        ];
        $this->CI->utils->debug_log('queryStatusById sql', $sql, $params);
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

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function queryGameList(){
        $login = $this->login();
        if($login['success'] && isset($login['token'])){

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryGameList',
            );

            $params = array(
                'accessToken' => $login['token']
            );
            
            $this->method = self::METHOD_GET;
            $this->CI->utils->debug_log('SKYWIND: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
            return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
        } else {
            return array("success" => false, array("list" => []));
        } 
    }

    public function processResultForQueryGameList($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $list = [];
        $success = false;
        if(!empty($resultArr)){
            $success = true;
            foreach ($resultArr as $key => $value) {
                $list[] = array(
                    "game_code" => $value['code'],
                    "game_type" => $value['type'],
                    "game_name" => $value['title']
                );
            }
        }
        return array($success, ["games" => $list]);
    }
}
