<?php
/**
 * World Entertainment Game integration
 * OGP-27885
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
     - we_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_we_seamless extends Abstract_game_api {

    public $use_new_game_launch;
    public $redirect_url;
    public $use_utils_get_url;
    public $disable_redirection;

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status', 'round_number'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount', 'valid_bet'];
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
        $this->operator_id = $this->getSystemInfo('operator_id', 'kash777stgqr5jg'); #stg
        $this->app_secret = $this->getSystemInfo('app_secret', 'LGaDqxsWwlYarr_BYgIMspD0BrrAicu6Zx7LLCAZfN0='); #stg
        $this->game_url = $this->getSystemInfo('game_url', 'http://uat-web-game-fe-op.bpweg.com'); #stg
        $this->default_bet_limit = $this->getSystemInfo('default_bet_limit'); #example "TEST-A,TEST-B,TEST-C"
        $this->default_timezone = $this->getSystemInfo('default_timezone'); #example "utc +8"
        $this->default_entrance_table = $this->getSystemInfo('default_entrance_table'); #example "STUDIO-BAS-964";

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);
        $this->signature = null;
        $this->enforce_http2 = $this->getSystemInfo('enforce_http2', false);
        $this->enforce_http1 = $this->getSystemInfo('enforce_http1', false);
        $this->use_new_game_launch = $this->getSystemInfo('use_new_game_launch', false);
        $this->redirect_url = $this->getSystemInfo('redirect_url');
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->use_utils_get_url = $this->getSystemInfo('use_utils_get_url', false);
        $this->disable_redirection = $this->getSystemInfo('disable_redirection', false);
    }

    const URI_MAP = array(
        self::API_queryBetDetailLink => '/report/betrecord',
        self::API_queryForwardGame => '/player/launch',
        self::API_queryDemoGame => '/game/demo',
    );

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return WE_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {

        if($this->enforce_http1){
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }

        if($this->enforce_http2){
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "signature: {$this->signature}"));   
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalPayoutRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/we_service_api/credit";
            return $url;
        }

        if($apiName == self::API_triggerInternalRefundRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/we_service_api/rollback";
            return $url;
        }

        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;

        if (@$statusCode == 200) {
            $success = true;

            if (isset($resultArr['status'])) {
                if ($resultArr['status'] != self::ERROR_CODE_SUCCESS) {
                    $success = false;
                }
            }
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('we Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for we seamless Game";
        if($return){
            $success = true;
            $message = "Successfull create account for we seamless Game.";
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
        $token = $this->getPlayerTokenByUsername($playerName);
        $lang = $this->getLanguage($extra['language']);
        $operator_id = $this->operator_id;
        $game_type = !empty($extra['game_type']) ? $extra['game_type'] : null;
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        switch ($game_type) {
            case self::GAME_TYPE_LIVE_DEALER:
                $category = 'Live';
                break;
            case self::GAME_TYPE_LOTTERY:
                $category = 'Lottery';
                break;
            case self::GAME_TYPE_FISHING_GAME:
                $category = 'Fishing';
                break;
            case self::GAME_TYPE_SLOTS:
                $category = 'Slot';
                break;
            case self::GAME_TYPE_SPORTS:
            case self::GAME_TYPE_E_SPORTS:
                $category = 'Sportbook';
                break;
            default:
                $category = 'Live';
                break;
        }

        $params = array(
            "token" => $token,
            "operator" => $this->operator_id,
            "lang" => $lang,
        );
 
        if(!empty($this->default_bet_limit)){
            $string = $this->default_bet_limit;
            $app_secret =  $this->app_secret;
            $iv =  substr($this->app_secret, 0, 16);
            $key = md5($app_secret);
            $cipher_text = openssl_encrypt($string, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            $output =  base64_encode($cipher_text);

            // $data = array(
            //     "key" => $key,
            //     "iv" => $iv,
            //     "string" => $string,
            //     "output" => $output
            // );
            // echo "<pre>";print_r($data);exit();
            $params['betlimit'] = $output;
        }

        if(!empty($this->default_timezone)){
            $params['timezone'] = $this->default_timezone;

        }

        if(!empty($this->default_entrance_table)){
            $params['tableid'] = $this->default_entrance_table;
        }

        /* if(!empty($game_code)){
            // $tableId = $this->getDefaultTableIdByGameCode($game_code);
            // if(!empty($tableId)){
                // $params['tableid'] = $tableId;
            // }

            $params['tableid'] = $game_code;
        } */

        if (!empty($game_code) && $game_code != '_null') {
            $params['tableid'] = $extra['game_code'];
        }

        if (!empty($game_type)) {
            $params['category'] = $category;
        }

        if ($this->use_new_game_launch) {
            $get_game_domain_link = $this->getGameDomainLink($playerName, $extra);

            $url = $get_game_domain_link['url'];
        } else {
            $url = $this->game_url;
        }

        if ($is_demo_mode) {
            $get_game_demo_link = $this->getGameDemoLink($playerName, $extra);

            $url = $get_game_demo_link['url'];
            unset($params['token']);
        }

        // $url .= "?" . http_build_query($params);

        $redirect_url_params = $params;
        unset($redirect_url_params['tableid']);
        $redirect_url = $url . '?' . http_build_query($redirect_url_params);

        if ($this->use_utils_get_url) {
            $this->redirect_url = $this->utils->getUrl();
        }

        $params['redirecturl'] = !empty($this->redirect_url) ? $this->redirect_url : $redirect_url;

        if ($this->disable_redirection) {
            unset($params['redirecturl']);
        }

        $game_launch_url = $url . '?' . http_build_query($params);

        $result = array(
            'success' => true,
            'url' => $game_launch_url
        );

        return $result;
    }

    public function getGameDomainLink($playerName, $extra = null) {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetDomainLink',
            'playerName' => $playerName,
        ];

        $request_time = $this->utils->getTimestampNow();

        $params = [
            'operatorID' => $this->operator_id,
            'requestTime' => $request_time,
        ];

        $this->signature = md5($this->app_secret . $this->operator_id . $request_time);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForGetDomainLink($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'url' => '',
        ];

        if ($success && !empty($resultArr)) {
            $result['url'] = $resultArr['url'];
        }

        return array($success, $result);
    }

    public function getGameDemoLink($playerName, $extra = null) {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameDemoLink',
            'playerName' => $playerName,
        ];

        $request_time = $this->utils->getTimestampNow();

        $params = [
            'operatorID' => $this->operator_id,
            'requestTime' => $request_time,
        ];

        $this->signature = md5($this->app_secret . $this->operator_id . $request_time);

        return $this->callApi(self::API_queryDemoGame, $params, $context);
    }

    public function processResultForGetGameDemoLink($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'url' => '',
        ];

        if ($success && !empty($resultArr)) {
            $result['url'] = $resultArr['url'];
        }

        return array($success, $result);
    }

    public function getDefaultTableIdByGameCode($game_code) {
        switch (strtolower($game_code)) {
            case 'bac':
                $tableId = $this->getSystemInfo('bac_default_table', 'STUDIO-BAC-1'); # classic baccarat
                break;
            case 'bas':
                $tableId = $this->getSystemInfo('bas_default_table', 'STUDIO-BAS-2'); # speed baccarat
                break;
            case 'baa':
                $tableId = $this->getSystemInfo('baa_default_table', 'STUDIO-BAA-4'); # traditional baccarat
                break;
            case 'bam':
                $tableId = $this->getSystemInfo('bam_default_table', 'STUDIO-BAM-8'); # peek baccarat
                break;
            case 'dt':
                $tableId = $this->getSystemInfo('dt_default_table', 'STUDIO-DT-12'); # dragon tiger
                break;
            case 'dts':
                $tableId = $this->getSystemInfo('dts_default_table', 'STUDIO-DTS-13'); # speed dragon tiger
                break;
            case 'di':
                $tableId = $this->getSystemInfo('di_default_table', 'STUDIO-DI-90'); # sicbo
                break;
            case 'lw':
                $tableId = $this->getSystemInfo('lw_default_table', 'STUDIO-LW-101'); # lucky wheel
                break;
            case 'dil':
                $tableId = $this->getSystemInfo('dil_default_table', 'STUDIO-DIL-102'); # GOF sicbo
                break;
            case 'bal':
                $tableId = $this->getSystemInfo('bal_default_table', 'STUDIO-BAC-118'); # GOF baccarat
                break;   
            case 'lo':
                $tableId = $this->getSystemInfo('lo_default_table', 'STUDIO-LO-311'); #lottery
                break; 
            case 'rol':
                $tableId = $this->getSystemInfo('rol_default_table', 'STUDIO-ROL-103'); # GOF roulette
                break; 
            default:
                $tableId = null;
                break;
        }
        return $tableId;
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }
        $currentLang = strtolower($currentLang);
        
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
            case 'pt':
            case 'pt-pt':
            case 'pt-br':
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

    public function queryTransaction($transactionId, $extra) {
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
        $sqlTime="we.updated_at >= ? AND we.updated_at <= ? AND we.game_platform_id = ? AND we.transaction_type = ?";
        if(!$this->enable_merging_rows){
            $sqlTime="we.updated_at >= ? AND we.updated_at <= ? AND we.game_platform_id = ? AND (we.transaction_type = ? OR we.transaction_type = ?)";
        }
        $sql = <<<EOD
SELECT
we.id as sync_index,
we.response_result_id,
we.md5_sum,
we.player_id,
we.game_platform_id,
we.amount,
we.amount as real_betting_amount,
we.bet_amount,
we.result_amount,
we.transaction_type,
we.game_id as game_code,
we.game_id as game,
we.game_id as game_name,
we.round_id,
we.response_result_id,
we.extra_info,
we.start_at,
we.start_at as bet_at,
we.end_at,
we.before_balance,
we.after_balance,
we.transaction_id as round_number,
we.`status` as transaction_status,
we.updated_at,
we.round_id,
we.external_unique_id as external_uniqueid,

gd.id as game_description_id,
gd.game_type_id

FROM {$this->original_transaction_table} as we
LEFT JOIN game_description as gd ON we.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            "debit"
        ];

        if(!$this->enable_merging_rows){
            $params=[
                $this->getPlatformCode(),
                $dateFrom,
                $dateTo,
                $this->getPlatformCode(),
                "debit",
                "credit",
            ];
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
            if($row['transaction_type'] == 'debit'){
                $winAmount = 0;
                $row['bet_amount'] = $row['amount'];
                $row['real_betting_amount'] = $row['amount'];
            }else{
                $row['bet_amount'] = 0;
                $row['real_betting_amount'] = 0;
                $winAmount = $row['amount'];
            }
            $row['result_amount'] = $winAmount - $row['bet_amount'];
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
                'bet_amount' => isset($row['valid_bet']) ? $row['valid_bet'] : 0,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => isset($row['valid_bet']) ? $row['valid_bet'] : 0,
                'real_betting_amount' => isset($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
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
        /* $extra = $row['extra_info'];
        $param_str = substr($extra, 1, -1);
        parse_str($param_str, $output);
        $game_status = null;
        if(isset($output['data'])){
            $data = json_decode($output['data'], true);
            $key = array_search($row['transaction_id'], array_column($data, 'betID'));
            if(isset($data[$key])){
                $result_data = $data[$key];
                $game_status = strtolower($result_data['gameStatus']);
            }   
        }
        $bet = $this->queryBetByTransactionId($row['transaction_id']);
        $all_bet = $this->queryAllbetByRoundId($bet['round_id']);
        if($all_bet['count'] > 1){ # multiple bet on 1 round set valid bet as zero, example bet on player and banker, etc
            if($row['amount'] > 0){ #win
                $row['result_amount'] = $row['amount'] - $bet['amount'];
                $row['valid_bet'] = $all_bet['amount'] - $row['amount'];
                $row['real_betting_amount'] = $bet['amount'];
            } else{ #lose
                $row['valid_bet'] = $bet['amount'];
                $row['result_amount'] = $row['amount'] - $bet['amount'];
                $row['real_betting_amount'] = $bet['amount'];
                if($row['game_code'] == "BAC"){
                    $row['valid_bet'] = 0;
                }
            }
        } else{
            $row['result_amount'] = $row['amount'] - $bet['amount'];
            $row['valid_bet'] = $bet['amount'];
            $row['real_betting_amount'] = $bet['amount'];
        }
        if($row['status'] == GAME_LOGS::STATUS_CANCELLED || $row['status'] == GAME_LOGS::STATUS_REFUND){
            $row['valid_bet'] = 0;
        }
        if(!empty($game_status) && $game_status == "tie"){
           $row['valid_bet'] = 0; 
        }
        $row['round_number'] = $bet['round_id']; */

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $result_details = $this->queryBetResultDetailsByBetId($row['round_number'], $row['player_id']);

        if($this->enable_merging_rows){
            $row['valid_bet'] = $row['bet_amount'];
            if (!empty($result_details['after_balance'])) {
                $row['after_balance'] = $result_details['after_balance'];
            }
    
            if (!empty($result_details['transaction_status'])) {
                $row['status'] =  $result_details['transaction_status'];
            } else {
                $row['status'] =  $row['transaction_status'];
            }
    
            if ($row['transaction_status'] == GAME_LOGS::STATUS_CANCELLED || $row['transaction_status'] == GAME_LOGS::STATUS_REFUND) {
                $row['valid_bet'] = 0;
            }
    
            if (!empty($result_details['result_amount'])) {
                $row['result_amount'] =  $result_details['result_amount'] - $row['valid_bet'];
            }
        }else{
            if (!empty($result_details['transaction_status'])) {
                $row['status'] =  $result_details['transaction_status'];
            } else {
                $row['status'] =  $row['transaction_status'];
            }
        }
    }

    private function queryAllbetByRoundId($id){
        $this->CI->load->model('original_game_logs_model');
        $sqlId="we.round_id = ?";
        $sql = <<<EOD
SELECT
sum(amount) as amount,
count(*) as count
FROM common_seamless_wallet_transactions as we
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

    private function queryBetByTransactionId($id){
        $this->CI->load->model('original_game_logs_model');
        $sqlId="we.transaction_id = ?";
        $sql = <<<EOD
SELECT
amount,
round_id,
after_balance
FROM common_seamless_wallet_transactions as we
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

    public function queryBetResultDetailsByBetId($bet_id, $player_id) {
        $this->CI->load->model('original_game_logs_model');
        $sqlId="we.transaction_type IN ('credit', 'rollback') AND we.transaction_id = ? AND we.player_id = ?";
        $sql = <<<EOD
SELECT
transaction_id,
transaction_type,
`status` as transaction_status,
external_unique_id,
extra_info,
after_balance,
SUM(amount) as amount,
SUM(result_amount) as result_amount
FROM common_seamless_wallet_transactions as we
WHERE
{$sqlId}
EOD;
        $params=[
            $bet_id,
            $player_id,
        ];

        $this->CI->utils->debug_log('queryBetResultDetailsByBetId sql', $sql, $params);
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

    #test bet id CEI0IR6IG61TH88LD72G on stg
    public function queryBetDetailLink($playerUsername = null, $betId = 'CEI0IR6IG61TH88LD72G', $lang = "en") {

        if (strpos($betId, 'D') === 0 || strpos($betId, 'C') === 0 || strpos($betId, 'R') === 0) {
            $betId = substr($betId, 1);
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'lang' => $this->getLanguage($lang)
        );

        $params = array(
            'appSecret' => $this->app_secret,
            'betID' => $betId,
            'operatorID' => $this->operator_id,
            'requestTime' => $this->utils->getTimestampNow(),
            // 'signature' => md5($this->app_secret.$betId.$this->operator_id.$this->utils->getTimestampNow())
        );
        $sign_string = implode('', $params);
        unset($params['appSecret']);
        $this->signature = md5($sign_string);

        $this->CI->utils->debug_log('-----------------------WE queryBetDetailLink params ----------------------------',$params);
        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = isset($resultArr['url']) ? true : false;
        $lang = $this->getVariableFromContext($params, 'lang');
        $result['url'] = isset($resultArr['url']) ? $resultArr['url'] : '';
        if(!empty($result['url'] )){
            $result['url'] .= "&lang={$lang}";
        }
        return array($success, $result);
    }



    #example json for triggerInternalPayoutRound
    /*
        {
   "operatorID":"kash777stgqr5jg",
   "appSecret":"LGaDqxsWwlYarr_BYgIMspD0BrrAicu6Zx7LLCAZfN0=",
   "playerID":"ks7stgtestt1dev",
   "gameID":"STUDIO-BAC-9",
   "betID":"betID01",
   "amount":"2",
   "gameStatus":"win",
   "gameResult":"test",
   "currency":"BRL",
   "type":"game",
   "time":"1671504366"
}
    */

    public function triggerInternalPayoutRound($params = '{"test":true}'){
        $params = array("data" => "[{$params}]");
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
        $success =  isset($resultArr['refID']) ? true : false;  
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }

    #example json for triggerInternalRefundRound
    /*
        {
            "operatorID": "kash777stgqr5jg",
            "appSecret": "LGaDqxsWwlYarr_BYgIMspD0BrrAicu6Zx7LLCAZfN0=",
            "playerID": "ks7stgtestt1dev",
            "gameID": "STUDIO-BAC-9",
            "betID": "betID01",
            "amount": 0,
            "currency": "BRL",
            "type": "bet",
            "time": "1672739526"
        }
    */
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
        $success =  isset($resultArr['refID']) ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }
}
