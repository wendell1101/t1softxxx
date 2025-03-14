<?php
/**
 * TRUCO game integration
 * OGP-24704
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
     - truco_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_truco_seamless extends Abstract_game_api {


    const ORIGINAL_LOGS_TABLE_NAME = 'truco_seamless_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status_db', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];

    const FLAG_WIN = 1;
    const FLAG_LOSE = 2;
    const FLAG_REFUND = 3;
    const FlAG_NOT_UPDATED = 0;
    const ERROR_CODE_SUCCESS = 0;
    protected $sign_key;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url','http://api.blaze.casa/gameapi/v1');
        $this->lang = $this->getSystemInfo('lang');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->merchant_code = $this->getSystemInfo('merchant_code','10001');#stg creds
        $this->sign_key = $this->getSystemInfo('sign_key','205becb239024920b1f07ac46cdbd621');
        $this->secure_key = $this->getSystemInfo('secure_key','a763d6514fce46508a0b236d37bfe6c2');#stg creds
        $this->currency = $this->getSystemInfo('currency','test');#stg creds
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);
    }

    const URI_MAP = array(
        self::API_generateToken => '/generate_token',
        self::API_queryForwardGame => '/query_game_launcher',
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return TRUCO_SEAMLESS_API;
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
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
        if(@$statusCode == 200 && isset($resultArr['success']) && $resultArr['code'] == self::ERROR_CODE_SUCCESS){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('truco Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function generateSignature($params){
        if(isset($params['sign'])){
            unset($params['sign']);
        }
        ksort($params);

        $string = "";

        foreach ($params as $key => $val) {
            $string .= $val;
        }

        $string = $string . $this->sign_key;

        $signature =  sha1($string);
        $this->CI->utils->debug_log('TRUCO SIGN: (' . __FUNCTION__ . ')', $signature, "string :", $string);
        return $signature;
    }

    /**
     * will check timeout, if timeout then call again
     * @return token
     */
    public function getAvailableApiToken(){
        $result = $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
        $this->utils->debug_log("TRUCO Available Token: ".$result);
        return $result;
    }

    /**
     * Generate Auth Token
     *
     */
    public function generateToken() {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken'
        );

        $params = array(
            'merchant_code' => $this->merchant_code,
            'secure_key' => $this->secure_key,
        );

        $params['sign'] = $this->generateSignature($params);
        $this->CI->utils->debug_log('TRUCO: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultForGenerateToken($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

        if ($success) {
            if(isset($resultArr['detail'])){
                $resp_timeout = isset($resultArr['detail']['timeout']) ? $resultArr['detail']['timeout'] : null;
                $resp_token = isset($resultArr['detail']['auto_token']) ? $resultArr['detail']['auto_token'] : null;
                if($resp_timeout && $resp_token){
                    $token_timeout = new DateTime($this->utils->getNowForMysql());
                    $minutes = ($resp_timeout/60)-1;
                    $token_timeout->modify("+".$minutes." minutes");
                    $result['api_token']=$resp_token;
                    $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
                }
            } 
        }

        $this->CI->utils->debug_log('TRUCO: (' . __FUNCTION__ . ')', 'success:', $success, 'RETURN:', $success, $resultArr);

        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for truco Game";
        if($return){
            $success = true;
            $message = "Successfull create account for truco Game.";
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
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName
        );
        $params = array(
            'auth_token' => $this->getAvailableApiToken(),
            'game_code' => isset($extra['game_code']) ? $extra['game_code'] : "truco",
            'merchant_code' =>  $this->merchant_code,
            'token' => $this->getPlayerTokenByUsername($playerName),
        );
        $params['sign'] = $this->generateSignature($params);

        $this->CI->utils->debug_log('TRUCO: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $result = array("url" => null);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        if($success){
            $result['url'] = isset($resultArr['detail']['game_url']) ? $resultArr['detail']['game_url'] : null ;
        }
        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
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
        $sqlTime="truco.end_at >= ? AND truco.end_at <= ? AND truco.game_platform_id = ?";

        $sql = <<<EOD
SELECT
truco.id as sync_index,
truco.response_result_id,
truco.external_unique_id as external_uniqueid,
truco.md5_sum,

truco.player_id,
truco.game_platform_id,
truco.bet_amount as bet_amount,
truco.bet_amount as real_betting_amount,
truco.result_amount,
truco.amount,
truco.transaction_type,
truco.game_id as game_code,
truco.game_id as game,
truco.game_id as game_name,
truco.round_id as round_number,
truco.response_result_id,
truco.extra_info,
truco.start_at,
truco.start_at as bet_at,
truco.end_at,
truco.before_balance,
truco.after_balance,
truco.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as truco
LEFT JOIN game_description as gd ON truco.game_id = gd.external_game_id AND gd.game_platform_id = ?
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
        $row['status'] = GAME_LOGS::STATUS_SETTLED;
        if(!isset($row['bet_amount'])){
            $row['bet_amount'] = 0;
            $row['real_betting_amount'] = 0;
            if(strtolower($row['transaction_type']) == "bet"){
                $row['bet_amount'] = $row['amount'];
                $row['real_betting_amount'] = $row['amount'];
            }
        }

        if(!isset($row['result_amount'])){
            $row['result_amount'] = 0;
            if(strtolower($row['transaction_type']) == "bet"){
                $row['result_amount'] = -$row['amount'];
            }
            if(strtolower($row['transaction_type']) == "payout"){
                $row['result_amount'] = $row['amount'];
            }
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
}
