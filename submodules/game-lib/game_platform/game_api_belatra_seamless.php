<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 * Belatra
 * OGP-32584
 *
 * @author  Jerbey Capoquian
 *
 * same protocol with bgaming 
 * 
 *
 * By function:
    
 *
 * 
 * Related File

     - bgaming_seamless_service_api.php
 */


class Game_api_belatra_seamless extends Abstract_game_api {

    const LANGUAGE_CODE_ENGLISH = 'en';
    const LANGUAGE_CODE_CHINESE = 'zh-cn';
    const LANGUAGE_CODE_INDONESIAN = 'id';
    const LANGUAGE_CODE_VIETNAMESE = 'vi';
    const LANGUAGE_CODE_KOREAN = 'ko';
    const LANGUAGE_CODE_THAI = 'th';
    const LANGUAGE_CODE_HINDI = 'hi';
    const LANGUAGE_CODE_PORTUGUESE = 'pt';

    const COUNTRY_CODE_US = 'US';
    const COUNTRY_CODE_CN = 'CN';
    const COUNTRY_CODE_ID = 'ID';
    const COUNTRY_CODE_VN = 'VN';
    const COUNTRY_CODE_KR = 'KR';
    const COUNTRY_CODE_TH = 'TH';
    const COUNTRY_CODE_IN = 'IN';
    const COUNTRY_CODE_BR = 'BR';

    const API_freespinsIssue = 'freespinsIssue';
    const API_freespinsCancel = 'freespinsCancel';

    const URI_MAP = [
        self::API_queryDemoGame => '/demo',
        self::API_queryForwardGame => '/sessions',
        self::API_freespinsIssue => '/freespins/issue',
        self::API_freespinsCancel => '/freespins/cancel',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->casino_id = $this->getSystemInfo('casino_id', 'test_t1soft');
        $this->auth_token = $this->getSystemInfo('auth_token', 'test_t1soft');
        $this->currency = $this->getSystemInfo('currency', 'BRL');
        $this->list_of_method_for_force_error = $this->getSystemInfo('list_of_method_for_force_error', []);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->original_transaction_table_name = 'belatra_seamless_wallet_transactions';
        $this->return_url = $this->getSystemInfo('return_url', $this->getHomeLink());
    }

    public function getPlatformCode() {
        return BELATRA_SEAMLESS_GAME_API;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        //$this->api_url = gcp url
        $url = $this->api_url . $apiUri;
        return $url;
    }

    protected function customHttpCall($ch, $params)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    protected function getHttpHeaders($params)
    {
        $headers = [
            'X-REQUEST-SIGN' => $this->generateRequestSign(json_encode($params)),
            'Content-type' => 'application/json',
        ];
        return $headers;
    }

    public function generateRequestSign($params, $algo = 'sha256')
    {
        return hash_hmac($algo, $params, $this->auth_token);
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null)
    {
        $success = false;

        if ($statusCode == 200 || $statusCode == 201) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log(' BELATRA API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table_name;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table_name;
        }

        $tableName=$this->original_transaction_table_name.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like {$this->original_transaction_table_name}");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }
    public function isSeamLessGame(){
        return true;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for BELATRA Game";
        if($return){
            $success = true;
            $message = "Successfull create account for BELATRA Game.";
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
    
    public function queryForwardGame($playerName, $extra = null)
    {
        $this->CI->load->model(['player_model', 'game_provider_auth', 'wallet_model']);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);
        $playerInfo = $this->CI->player_model->getPlayerInfoById($playerId);
        $registeredAt = $this->CI->game_provider_auth->getPlayerCreatedAt($this->getPlatformCode(), $playerId);
        $gameCode = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $gameMode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $isMobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        list($lang, $country) = $this->getLauncherLanguage($extra['language']);
        $language = !empty($this->language) ? $this->language : $lang;

        $playerBalance = $this->CI->wallet_model->readonlyMainWalletFromDB($playerId);
        if(!empty($this->utils->getConfig('enabled_remote_wallet_client_on_currency'))){
            if($this->utils->isEnabledRemoteWalletClient()){
                $useReadonly = true;
                $playerBalance =  $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->gamePlatformId, $useReadonly);
            }
        }

        if (!empty($extra['home_link'])) {
            $return_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $return_url = $extra['extra']['home_link'];
        } else {
            $return_url = $this->return_url;
        }

        if (!empty($extra['cashier_link'])) {
            $deposit_url = $extra['cashier_link'];
        } elseif (!empty($extra['extra']['cashier_link'])) {
            $deposit_url = $extra['extra']['cashier_link'];
        } else {
            $deposit_url = !empty($this->deposit_url) ? $this->deposit_url : $return_url;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_name' => $playerName,
            'game_username' => $gameUsername,
            'game_mode' => $gameMode,
        ];

        if ($isMobile) {
            $client_type = 'mobile';
        } else {
            $client_type = 'desktop';
        }

        if (!empty($playerInfo['gender'])) {
            if (strtolower($playerInfo['gender']) == 'male') {
                $gender = 'm';
            } elseif (strtolower($playerInfo['gender']) == 'female') {
                $gender = 'f';
            } else {
                $gender =  null;
            }
        } else {
            $gender =  null;
        }

        $params = [
            'casino_id' => $this->casino_id,
            'game' => $gameCode,
            'locale' => $language,
            'ip' => $this->utils->getIP(),
            'client_type' => $client_type,
            'jurisdiction' => $country,
        ];

        if (isset($extra['extra']['home_link'])) {
            $return_url = $extra['extra']['home_link'];
        }

        $params['urls'] = [
            'return_url' => $return_url,
            'deposit_url' => $deposit_url,
        ];


        if ($gameMode == 'real') {
            $api_name = self::API_queryForwardGame;
            $params['currency'] = $this->currency;
            $params['balance'] = $playerBalance;
            $params['user'] = [
                'id' => $gameUsername,
                'email' => !empty($playerInfo['email']) ? $playerInfo['email'] : null,
                'firstname' => !empty($playerInfo['firstName']) ? $playerInfo['firstName'] : null,
                'lastname' => !empty($playerInfo['lastName']) ? $playerInfo['lastName'] : null,
                'nickname' => $gameUsername,
                'city' => !empty($playerInfo['city']) ? $playerInfo['city'] : null,
                'date_of_birth' => !empty($playerInfo['birthdate']) ? date('Y-m-d', strtotime($playerInfo['birthdate'])) : null,
                'registered_at' => !empty($registeredAt) ? date('Y-m-d', strtotime($registeredAt)) : null,
                'gender' => $gender,
                'country' => $country,
            ];
        } else {
            $api_name = self::API_queryDemoGame;
        }

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            $params['urls']['return_url'] = "";
        }

        $this->utils->debug_log(__METHOD__, 'BELATRA params', $params);

        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);

        $result = [
            'responseResultId' => $responseResultId,
        ];

        if ($success && isset($resultArr['launch_options'])) {
            $result['url'] = !empty($resultArr['launch_options']['game_url']) ? $resultArr['launch_options']['game_url'] : null;
            $result['strategy'] = !empty($resultArr['launch_options']['strategy']) ? $resultArr['launch_options']['strategy'] : null;
        }

        $this->utils->debug_log(__METHOD__, ' BELATRA result', $resultArr);

        return [$success, $result];
    }

    public function getLauncherLanguage($language)
    {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
                $lang = self::LANGUAGE_CODE_ENGLISH;
                $country = self::COUNTRY_CODE_US;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $lang = self::LANGUAGE_CODE_CHINESE;
                $country = self::COUNTRY_CODE_CN;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $lang = self::LANGUAGE_CODE_INDONESIAN;
                $country = self::COUNTRY_CODE_ID;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $lang = self::LANGUAGE_CODE_VIETNAMESE;
                $country = self::COUNTRY_CODE_VN;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $lang = self::LANGUAGE_CODE_KOREAN;
                $country = self::COUNTRY_CODE_KR;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $lang = self::LANGUAGE_CODE_THAI;
                $country = self::COUNTRY_CODE_TH;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $lang = self::LANGUAGE_CODE_HINDI;
                $country = self::COUNTRY_CODE_IN;
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-BR':
                $lang = self::LANGUAGE_CODE_PORTUGUESE;
                $country = self::COUNTRY_CODE_BR;
                break;
            default:
                $lang = self::LANGUAGE_CODE_ENGLISH;
                $country = self::COUNTRY_CODE_US;
                break;
        }

        return array($lang, $country);
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        if($this->enable_merging_rows){
            return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryDistinctGameIds'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
        }
        
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
    }

    public function queryDistinctGameIds($dateFrom, $dateTo, $use_bet_time){
        if($this->use_monthly_transactions_table){  
            $start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            $results = [];
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName=$this->original_transaction_table_name.'_'.$yearMonthStr;
                $this->CI->load->model('original_game_logs_model');

                $sqlTime="belatra.created_at >= ? AND belatra.created_at <= ? AND belatra.game_id is not null";
        $sql = <<<EOD
SELECT
DISTINCT(belatra.game_id),
belatra.player_id
FROM {$tableName} as belatra
WHERE
{$sqlTime}
EOD;
                $params=[
                    $dateFrom,
                    $dateTo
                ];
                $this->CI->utils->debug_log('query distinct bet transacstion belatra merge sql', $sql, $params);

                $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                $results = array_merge($results, $monthlyResults);
            }

            if(!empty($results)){
                $results = array_unique($results, SORT_REGULAR);
                $this->preProcessResults($results, $dateFrom, $dateTo);
            }
            return $results;
        }
        
        $sqlTime="belatra.created_at >= ? AND belatra.created_at <= ? AND belatra.game_id is not null";
        $tableName = $this->getTransactionsTable();
        $sql = <<<EOD
SELECT
DISTINCT(belatra.game_id),
belatra.player_id
FROM {$tableName} as belatra
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('query distinct bet transacstion belatra merge sql', $sql, $params);

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if(!empty($results)){
            $this->preProcessResults($results, $dateFrom, $dateTo);
            return $results;
        } 
        return [];
    }

    private function preProcessResults( array &$results, $dateFrom, $dateTo){
        if(!empty($results)){
            foreach ($results as $key => $result) {
                $playerId = $result['player_id'];
                $gameId = $result['game_id'];
                $rows = $this->queryTransactionsByGameId($playerId, $gameId, $dateFrom, $dateTo);
                // echo "<pre>";print_r($rows);exit();
                $lastRow = end($rows);
                $betAmount = 0;
                $resultAmount = 0;
                array_walk($rows, function($data, $key) use(&$betAmount, &$resultAmount) {
                    if($data['action'] == "bet"){
                        $betAmount += abs($data['amount_currency']);
                        $resultAmount += -$data['amount_currency'];
                    } elseif($data['action'] == "win"){
                        $betAmount += 0;
                        $resultAmount += $data['amount_currency'];
                    }
                });

                $gameData = array(
                    "sync_index" => $lastRow['sync_index'],
                    "start_at" => $rows[0]['start_at'],
                    "end_at" => $lastRow['end_at'],
                    "after_balance" => $lastRow['after_balance'],
                    "status" => $lastRow['status'],
                    "player_id" => $lastRow['player_id'],
                    "round_number" => $lastRow['round_number'],
                    "external_uniqueid" => $playerId."-".$gameId,
                    "bet_amount" => $betAmount,
                    "result_amount" => $resultAmount,
                    "game_description_id" => $lastRow['game_description_id'],
                    "game_code" => $lastRow['game_code'],
                    "game_type_id" => $lastRow['game_type_id'],
                    "response_result_id" => null,
                );
                $gameData['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($gameData, ['end_at', 'status'], ['bet_amount', 'result_amount']);
                $results[$key] = $gameData;
            }
        }
    }

    private function queryTransactionsByGameId($playerId, $gameId, $dateFrom, $dateTo){
        if($this->use_monthly_transactions_table){  
            $start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            $results = [];
            
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName=$this->original_transaction_table_name.'_'.$yearMonthStr;
                $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
belatra.id as sync_index,
belatra.action,
belatra.created_at as start_at,
belatra.created_at as end_at,
belatra.external_uniqueid,
belatra.game_id as round_number,
belatra.after_balance, 
belatra.sbe_status as status,
belatra.amount_currency,
belatra.game as game,
belatra.game as game_name,
belatra.game as game_code,
belatra.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as belatra
LEFT JOIN game_description as gd ON belatra.game = gd.external_game_id AND gd.game_platform_id = ?
WHERE
belatra.player_id = ? and belatra.game_id = ?
EOD;

                $params=[
                    $this->getPlatformCode(),
                    $playerId,
                    $gameId
                ];

                $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                $results = array_merge($results, $monthlyResults);
            }
            return $results;
        }

        $tableName = $this->getTransactionsTable();
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
belatra.id as sync_index,
belatra.action,
belatra.created_at as start_at,
belatra.created_at as end_at,
belatra.external_uniqueid,
belatra.game_id as round_number,
belatra.after_balance, 
belatra.sbe_status as status,
belatra.amount_currency,
belatra.game as game,
belatra.game as game_name,
belatra.game as game_code,
belatra.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as belatra
LEFT JOIN game_description as gd ON belatra.game = gd.external_game_id AND gd.game_platform_id = ?
WHERE
belatra.player_id = ? and belatra.game_id = ?
EOD;

        $params=[
            $this->getPlatformCode(),
            $playerId,
            $gameId
        ];

        $this->CI->utils->debug_log('belatra queryBet sql', $sql, $params);
        return $rows = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

       /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $md5Fields = implode(", ", array('belatra.sbe_status', 'belatra.amount_currency', 'belatra.created_at'));
        if($this->use_monthly_transactions_table){            
            $start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            $results = [];
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName=$this->original_transaction_table_name.'_'.$yearMonthStr;
                $sqlTime="belatra.created_at >= ? AND belatra.created_at <= ?";

        $sql = <<<EOD
SELECT
belatra.id as sync_index,
belatra.response_result_id,
belatra.external_uniqueid,
MD5(CONCAT({$md5Fields})) AS md5_sum,

belatra.player_id,
belatra.amount,
belatra.amount_currency,
belatra.action,
belatra.game as game_code,
belatra.game as game,
belatra.game as game_name,
belatra.game_id as round_number,
belatra.response_result_id,
belatra.created_at as start_at,
belatra.created_at as bet_at,
belatra.created_at as end_at,
belatra.before_balance,
belatra.after_balance,
belatra.sbe_status as status,

gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as belatra
LEFT JOIN game_description as gd ON belatra.game = gd.external_game_id AND gd.game_platform_id = ?
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
            return $results;
        }
        
        $tableName = $this->getTransactionsTable();
        $sqlTime="belatra.created_at >= ? AND belatra.created_at <= ?";

        $sql = <<<EOD
SELECT
belatra.id as sync_index,
belatra.response_result_id,
belatra.external_uniqueid,
belatra.md5_sum,
MD5(CONCAT({$md5Fields})) AS md5_sum,

belatra.player_id,
belatra.amount,
belatra.amount_currency,
belatra.action,
belatra.game as game_code,
belatra.game as game,
belatra.game as game_name,
belatra.game_id as round_number,
belatra.response_result_id,
belatra.created_at as start_at,
belatra.created_at as bet_at,
belatra.created_at as end_at,
belatra.before_balance,
belatra.after_balance,
belatra.sbe_status as status,

gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as belatra
LEFT JOIN game_description as gd ON belatra.game = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
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

        if(isset($row['action'])){ #for unmerge rows only
            $action = strtolower($row['action']);
            if($action == 'bet'){
                $row['bet_amount'] = $row['amount_currency'];
                $row['result_amount'] = -$row['amount_currency'];
            } elseif($action == 'win' || $action == 'payout') {
                $row['bet_amount'] = 0;
                $row['result_amount'] = $row['amount_currency'];
            } else { #rollback
                $row['bet_amount'] = 0;
                $row['result_amount'] = $row['after_balance'] - $row['before_balance'];
            } 
        }
    }


    public function queryTransactionByDateTime($startDate, $endDate){
        $results = $this->queryOriginalGameLogs($startDate, $endDate);
        array_walk($results, function($rows, $key) use(&$results){
            $results[$key]['transaction_date'] = $rows['start_at'];
            $results[$key]['amount'] = $rows['amount_currency'];#override amount value
            $results[$key]['round_no'] = $rows['round_number'];
            $results[$key]['trans_type'] = $rows['action'];
            $balance_result = $rows['after_balance'] - $rows['before_balance'];
            if($this->utils->compareResultFloat($balance_result, '>', 0)){
                $results[$key]['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
            } else if($this->utils->compareResultFloat($balance_result, '<', 0)){
                $results[$key]['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
            }
        });
        return $results;
    }
}
