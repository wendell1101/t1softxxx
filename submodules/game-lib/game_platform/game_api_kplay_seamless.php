<?php
/**
 * kplay game integration
 * OGP-27432
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
     - kplay_seamless_game_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_kplay_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const MD5_FIELDS_FOR_ORIGINAL=[
        'bet_settled_date', 'status',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS=['return'];
    const ERROR_CODE_SUCCESS = 1;
    const EVOLUTION_PRODUCT_ID = 1;
    const LOBBY_CODE = 0;
    const TYPE_RESOLVE = 1;
    const TYPE_UNRESOLVE = 0;

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
        $this->mobile_launch_url = $this->getSystemInfo('mobile_launch_url');
        $this->agent_acount = $this->getSystemInfo('agent_acount', 'ZEN9800'); #stg
        $this->agent_token = $this->getSystemInfo('agent_token', 'IGX5DXPSXerBQNlfn1sucekCKUvb9DGl');
        $this->agent_secret_key = $this->getSystemInfo('agent_secret_key', '95wRkaOWmHB3ih2OtDwJIzs1C9ddIykl');

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);
        $this->auth_product_id = null;
        $this->is_get_method = false;

    }

    const URI_MAP = array(
        self::API_createPlayer => '/auth',
        self::API_queryForwardGame => '/auth',
        self::API_checkTicketStatus => '/results'
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return KPLAY_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        $header = array('Content-Type: application/json','Ag-Code: '.$this->agent_acount,'Ag-Token: '.$this->agent_token, 'Content-Type: application/json', 'secret-key: '.$this->agent_secret_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if(!$this->is_get_method){
            $data = json_encode($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } 
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalPayoutRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/kplay_service_api/credit";
            return $url;
        }

        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        if($apiName == self::API_checkTicketStatus){
            $url = $this->api_url . $apiUri . "/" . $params['product_id'] . "/" . $params['transaction_id'];
        }
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if(@$statusCode == 200 && isset($resultArr['status']) && $resultArr['status'] == self::ERROR_CODE_SUCCESS){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('kplay Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $api_name = self::API_createPlayer;
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'player_id' => $playerId
        );

        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            $this->appendCurrentDbOnUrl($this->lobby_url);
        }

        $params = array(
            'user' => array(
                "id" => $playerId,
                "name" => $game_username,
                "balance" => 0,
                "language" => 'en'
            
            ),
            'prd' => array(
                "id" => $this->auth_product_id,
                "is_mobile" =>  false,
            )
        );

        $this->CI->utils->debug_log('KPLAY: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi($api_name, $params, $context);
    }

    /**
     * overview : process result for createPlayer
     *
     * @param $params
     * @return array
     */
    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'player_id');
        $result = array("aas_user_id" => null);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        if($success){
            $result['aas_user_id'] = isset($resultArr['user_id']) ? $resultArr['user_id'] : null ;
            if(!empty($result['aas_user_id'])){
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
                $this->updateExternalAccountIdForPlayer($playerId, $result['aas_user_id']);
            }
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

    private function getAuthPlayerBalance($player_id = null, $is_locked = true){
        if($player_id){
            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                $balance = 0;
                $reasonId = null;
                $this->CI->load->model(array('wallet_model'));
                $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function() use($player_id, &$balance, &$reasonId) {
                    return  $this->CI->wallet_model->querySeamlessSingleWallet($player_id, $balance, $reasonId);
                });
                return $balance;

            } else {
                $playerInfo = (array)$this->getPlayerInfo($player_id);
                $playerName = $playerInfo['username'];
                $get_bal_req = $this->queryPlayerBalance($playerName);
                if($get_bal_req['success']) {
                    return $get_bal_req['balance'];
                }
                else {
                    return false;
                }
            } 
        } else {
            return false;
        }
    }

    public function queryForwardGame($playerName, $extra = null) {
        $api_name = self::API_queryForwardGame;
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);
        $player_id = $this->getPlayerIdInPlayer($playerName);
        $balance = $this->getAuthPlayerBalance($player_id);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        );

        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            $this->appendCurrentDbOnUrl($this->lobby_url);
        }

        $params = array(
            'user' => array(
                "id" => $player_id,
                "name" => $game_username,
                "balance" => $balance,
                "language" => $this->getLanguage($extra['language'])
            
            ),
            'prd' => array(
                "id" => $this->auth_product_id,
                //"type" => self::LOBBY_CODE, //Lobby type id 
                "is_mobile" => isset($extra['is_mobile']) ? $extra['is_mobile'] : false,
                "lobby" => $this->lobby_url,
                "table_id" => $extra['game_code']
            )
        );

        $this->CI->utils->debug_log('KPLAY: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $result = array("url" => null);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        if($success){
            $result['aas_user_id'] = isset($resultArr['user_id']) ? $resultArr['user_id'] : null ;
            $result['url'] = isset($resultArr['launch_url']) ? $resultArr['launch_url'] : null ;
        }
        return array($success, $result);
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            // case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
            //     $language = 'zh';
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            //     $language = 'id';
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
            //     $language = 'vn';
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'ko';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_THAI:
            // case Language_function::PLAYER_LANG_THAI :
            //     $language = 'th';
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            // case Language_function::PLAYER_LANG_PORTUGUESE :
            //     $language = 'pt';
            //     break;
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
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime="kplay.end_at >= ? AND kplay.end_at <= ? AND kplay.game_platform_id = ?";

        $sql = <<<EOD
SELECT
DISTINCT(kplay.transaction_id) 
FROM common_seamless_wallet_transactions as kplay
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('query distinct transaction kplay merge sql', $sql, $params);
        $transaction_ids = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $transaction_ids = array_column($transaction_ids, 'transaction_id');
        $result = [];
        if(!empty($transaction_ids)){
            $result = $this->preProcessTransactions($transaction_ids);
        }
        // echo "<pre>";
        // print_r($result);exit();
        return $result;
    }

    private function preProcessTransactions( array $transactions){
        $result = [];
        if(!empty($transactions)){
            foreach ($transactions as $key => $transaction) {
                $details = $this->queryTransactionDetails($transaction);
                $row_details = $this->queryRowDetails($details['last_id']);
                $details['after_balance'] = $row_details['after_balance'];
                $details['status'] = $row_details['status'];
                $details['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($details, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
                $result[] = $details;
            }

        }
        return $result;
    }

    public function queryTransactionDetails($transactionId) {
        $this->CI->load->model('original_game_logs_model');
        $sqlRound="kplay.transaction_id = ? AND kplay.game_platform_id = ?";

        $sql = <<<EOD
SELECT
min(kplay.id) as sync_index,
max(kplay.id) as last_id,
sum(kplay.bet_amount) as bet_amount,
sum(kplay.result_amount) as result_amount,
min(start_at) as start_at,
max(end_at) as end_at,
transaction_id as external_uniqueid,
transaction_id as round_number,
transaction_id ,
kplay.game_id,
kplay.player_id,
kplay.response_result_id,
kplay.game_id as game_name,
kplay.game_id as game_code,
gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as kplay
LEFT JOIN game_description as gd ON kplay.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlRound}
EOD;
        $params=[
            $this->getPlatformCode(),
            $transactionId,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function queryRowDetails($id){
        $this->CI->load->model('original_game_logs_model');
        $sqlId="kplay.id = ?";
        $sql = <<<EOD
SELECT
after_balance,
status
FROM common_seamless_wallet_transactions as kplay
WHERE
{$sqlId}
EOD;
        $params=[
            $id
        ];

        $this->CI->utils->debug_log('queryRowDetails sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
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
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] - $row['bet_amount'] : 0,
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

    public function checkTicketStatus($productId = "1", $transactionId = "668164292751890082") {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCheckTicketStatus',
        );

        $params = array(
            "product_id" => $productId,
            "transaction_id" => $transactionId,
        );
        // echo "<pre>";
        // print_r($params);exit();
        $this->is_get_method = true;
        return $this->callApi(self::API_checkTicketStatus, $params, $context);
    }

    public function processResultForCheckTicketStatus($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        return array($success, $resultJsonArr);
    }

    public function queryFailedTransactions($patchFailedTransactions = false, $dateFrom, $dateTo) {
        $dateFrom = new DateTime($dateFrom);
        $dateFrom->modify("-1 hours");
        $dateFrom = $dateFrom->format("Y-m-d H:i:s");
        $missing_credit_transactions = $this->queryTransactionWithMissingCredit($dateFrom, $dateTo);
        $total_resolve = 0;
        if(!empty($missing_credit_transactions)){
            foreach ($missing_credit_transactions as $transaction) {
                $transaction_id = $transaction['transaction_id'];
                $player_id = $transaction['player_id'];
                $extra_info = json_decode($transaction['extra_info'], true);
                $product_id = isset($extra_info['prd_id']) ? $extra_info['prd_id'] : null;
                if(empty($product_id)){
                    continue;
                }

                $result = $this->checkTicketStatus($product_id, $transaction_id);
                if(isset($result['success']) &&  $result['success'] && isset($result['status']) &&  $result['status']){
                    $external_account_id = $this->getExternalAccountIdByPlayerId($player_id);
                    $payout = isset($result['payout']) ? $result['payout'] : 0;
                    $type = isset($result['type']) ? $result['type'] : null;
                    if($type == self::TYPE_RESOLVE){
                        $result['user_id'] = $external_account_id;
                        $result['amount']  = $payout;
                        $result['prd_id'] = $product_id;
                        $result['txn_id'] = $transaction_id;
                        $result['note'] = "run by queryFailedTransactions";
                        $json_params = json_encode($result);
                        $payout_result = $this->triggerInternalPayoutRound($json_params);
                        if(isset($payout_result['success']) && $payout_result['success']){
                            $total_resolve++;
                        }
                    }
                    
                }
            }
        }
        return array("success" => true, array("total_resolve" => $total_resolve, "total_missing_credits" => count($missing_credit_transactions)));
    }

    private function queryTransactionWithMissingCredit($dateFrom, $dateTo){
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
    transaction_id,
    sum( IF ( transaction_type = "credit", 1, 0 ) )  as count_credit,
    extra_info, 
    player_id
FROM
    common_seamless_wallet_transactions 
WHERE
    game_platform_id = ? 
    and end_at >= ?
    and end_at <= ?
GROUP BY
    transaction_id
HAVING
    count_credit = 0;
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('queryTransactionWithMissingCredit sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
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
        $success = isset($resultArr['status']) && $resultArr['status'] ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }
}
