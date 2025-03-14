<?php
/**
 * 5G Gaming integration
 * OGP-35664
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
     - _5ggaming_seamless_service_api.php

    Endpoint for seamless wallet
    -<domain>/5ggaming/api

    example on postman request:
    {{base_url}}/5ggaming/api/authenticate
    {{base_url}}/5ggaming/api/logout
    {{base_url}}/5ggaming/api/bet
    {{base_url}}/5ggaming/api/result
    {{base_url}}/5ggaming/api/refund
    {{base_url}}/5ggaming/api/bonusaward
    {{base_url}}/5ggaming/api/getbalance
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_5ggaming_seamless extends Abstract_game_api {

    const URI_MAP = array(
        self::API_queryForwardGame => '/launch',
        self::API_queryGameListFromGameProvider => '/feed/gamelist',
    );

    const STATUS_200_OK = 200;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = 'fiveg_seamless_wallet_transactions';
        $this->original_table = 'fiveg_seamless_wallet_game_records';
        $this->api_url = $this->getSystemInfo('url');
        $this->host_id = $this->getSystemInfo('host_id');
        $this->return_url = $this->getSystemInfo('return_url');
        $this->return_target = $this->getSystemInfo('return_target');
        $this->enable_currency_prefix_on_token = $this->getSystemInfo('enable_currency_prefix_on_token', false);
        $this->used_html_on_launching = $this->getSystemInfo('used_html_on_launching', true);

    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return FIVEG_GAMING_SEAMLESS_API;
    }

    /**
     * overview : generate url
     *
     * @param $apiName
     * @param $params
     * @return string
     */
    public function generateUrl($apiName, $params)
    {
        $uriMap = self::URI_MAP;
        $uri = $uriMap[$apiName];
        $url = $this->api_url . $uri;
        $stringParams = $this->custom_http_build_params($params);
        $url .= "?{$stringParams}";
        $this->CI->utils->debug_log('==> 5ggaming generateUrl : ' . $url);
        return $url;
    }

    function custom_http_build_params($params) {
        $query = [];
        foreach ($params as $key => $value) {
            $query[] = $key . '=' . $value;
        }
        return implode('&', $query);
    }


    /**
     * overview : create player game
     *
     * @param $playerName
     * @param $playerId
     * @param $password
     * @param null $email
     * @param null $extra
     * @return array
     */
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for 5ggaming Game";
        if ($return) {
            $success = true;
            $message = "Successfull create account for 5ggaming Game.";
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return array(
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return array(
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        );
    }

    /**
     * Overview: Query forward game
     *
     * @param string $playerName
     * @param array $extra
     * @return array
     */
    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'playerId' => $this->getPlayerIdFromUsername($playerName),
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'host_id' => $this->host_id,
            'game_id' => $extra['game_code'],
            'lang' => $this->getLanguage(isset($extra['language']) ? $extra['language'] : ''),
            'nick' => $gameUsername,
        );

        $tokenPrefix = "";
        if ($this->enable_currency_prefix_on_token) {
            $currencyDetails = $this->CI->utils->getActiveCurrencyInfoOnMDB();
            if (is_array($currencyDetails) && isset($currencyDetails['code'])) {
                $tokenPrefix = $currencyDetails['code'] . '-';
            }
        }

        if(isset($extra['game_mode'])){
            if(strtolower($extra['game_mode']) == "real"){
                $params['access_token'] = $tokenPrefix . $this->getPlayerTokenByUsername($playerName);
            }
        }

        if(!empty($this->return_target)){
            $params['return_target'] = $this->return_target;
        }

        if(!empty($this->return_url)){
            $params['return_url'] = $this->return_url;
        }

        if(isset($extra['home_link']) && !empty($extra['home_link'])){
            $params['return_url'] = $extra['home_link'];
        }

        if(isset($extra['extra']['home_link'])) {
            $params['return_url'] = $extra['extra']['home_link'];
        }

        $this->CI->utils->debug_log(
            '5ggaming: (' . __FUNCTION__ . ')',
            'PARAMS:',
            $params
        );
        $url = $this->generateUrl(self::API_queryForwardGame, $params);
        return array("success" => true, "url" => $url);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function getLanguage($currentLang)
    {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE:
            case 'zh-CN':
                $language = 'zh-CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI:
            case 'th-TH':
                $language = 'th-TH';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN:
            case 'id-ID':
                $language = 'id-ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE:
            case 'vi-VN':
                $language = 'vi-VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
            case 'po-BR':
                $language = 'po-BR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDIA:
            case 'hi-IN':
                $language = 'hi-IN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case LANGUAGE_FUNCTION::PLAYER_LANG_SPANISH:
            case 'es-ES':
                $language = 'es-ES';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_JAPANESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_JAPANESE:
            case 'ja-JP':
                $language = 'ja-JP';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN:
            case 'ko-KR':
                $language = 'ko-KR';
                break;
            default:
                $language = 'en-US';
                break;
        }
        return $language;
    }

    /**
     * overview : process result for queryForwardGame
     *
     * @param $params
     * @return array
     */
    public function processResultForQueryForwardGame($params) {
        $this->CI->load->model('external_common_tokens');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $html = $this->getResultTextFromParams($params);
        $url = null;
        if(!$this->used_html_on_launching){
            $url = $this->getUrlOnHtmlString($html);
        }
        
        $result = array("url" => null, "html" => null, "is_html" => $this->used_html_on_launching);
        $success = false;
        if(!empty($html) && $this->used_html_on_launching){
            $result['html'] = $html;
            $success = true;
        } elseif(!empty($url) && !$this->used_html_on_launching){
            $result['url'] = $url;
            $success = true;
        }
        return array($success, $result);
    }

    function getUrlOnHtmlString($string) {
        $regex = '/https?\:\/\/[^\" \n]+/i';
        preg_match_all($regex, $string, $matches);
        if(isset($matches[0][0])){
            return $matches[0][0];
        }
        return null;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if($statusCode == self::STATUS_200_OK){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('5ggaming Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function queryGameListFromGameProvider($extra = null){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameProviderGamelist',
        );

        $params = [
            "host_id" => $this->host_id
        ];

        $this->CI->utils->debug_log('5ggaming: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    /** 
     * Process Result of queryGameListFromGameProvider
    */
    public function processResultForGetGameProviderGamelist($params)
    {
        $this->CI->load->model('external_common_tokens');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        return array($success, $resultArr);
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getTransactionsTable(){
        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like {$this->original_transaction_table}");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false)
    {
        $start = (new DateTime($dateFrom))->modify('first day of this month');
        $end = (new DateTime($dateTo))->modify('last day of this month');
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($start, $interval, $end);

        $results = [];
        $platformCode = $this->getPlatformCode();

        foreach ($period as $dt) {
            $yearMonthStr = $dt->format("Ym");
            $tableName = $this->original_transaction_table . '_' . $yearMonthStr;

            $sqlTimeColumn = $use_bet_time ? 'fiveg.created_at' : 'fiveg.updated_at';
            $sqlTime = $sqlTimeColumn . " >= ? AND " . $sqlTimeColumn . " <= ?";

            $sql = <<<EOD
SELECT
    fiveg.id AS sync_index,
    fiveg.txn_id AS external_uniqueid,
    fiveg.player_id,
    fiveg.trans_type,
    fiveg.game_id AS game_code,
    fiveg.txn_id AS round_number,
    fiveg.round_start_time AS start_at,
    fiveg.round_start_time AS end_at,
    fiveg.round_start_time AS bet_at,
    fiveg.bet_amount,
    fiveg.payout_amount,
    fiveg.jackpot_amount,
    ((fiveg.payout_amount + fiveg.jackpot_amount ) - fiveg.bet_amount) AS result_amount,
    fiveg.before_balance,
    fiveg.after_balance,
    fiveg.sbe_status AS status,
    fiveg.md5_sum,
    gd.id AS game_description_id,
    gd.game_type_id
FROM {$tableName} AS fiveg
LEFT JOIN game_description AS gd
    ON fiveg.game_id = gd.external_game_id
    AND gd.game_platform_id = ?
WHERE {$sqlTime}
    AND fiveg.game_platform_id = ?
EOD;

            $params = array($platformCode, $dateFrom, $dateTo, $platformCode);
            $this->CI->utils->debug_log('Executing SQL', $sql, $params);

            $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            $results = array_merge($results, $monthlyResults);
        }

        $this->CI->original_game_logs_model->removeDuplicateUniqueid($results, 'external_uniqueid', function () {
            return 2;
        });

        return array_values($results);
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
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'start_at', 'end_at'],
                ['bet_amount', 'payout_amount']);
        }

        $data =  [
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
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [
                'bet_amount' => $row['bet_amount'],
                'win_amount' => $row['payout_amount'],
                'jackpot_amount' => $row['jackpot_amount'],
                'round_id' => $row['round_number']
            ],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
        return $data;
    }


    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_code'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

     /**
     * queryTransactionByDateTime
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryTransactionByDateTime($dateFrom, $dateTo){
        $tableName = $this->getTransactionsTable();
        $incType = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
        $decType = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
        $sqlTime="5g.created_at >= ? AND 5g.created_at <= ? AND 5g.game_platform_id = ?";

        $sql = <<<EOD
SELECT
5g.id as sync_index,
5g.external_uniqueid as external_uniqueid,
5g.txn_id as round_no,
5g.trans_type as trans_type,
CASE 
    WHEN 5g.trans_type = 'bet' THEN 5g.bet_amount
    WHEN 5g.trans_type = 'result' THEN 5g.payout_amount
    WHEN 5g.trans_type = 'bonusreward' THEN 5g.jackpot_amount
    ELSE 0
END AS amount,
5g.md5_sum,
if(5g.trans_type = "bet", "{$decType}", "{$incType}") as transaction_type,
5g.created_at as transaction_date,
5g.before_balance,
5g.after_balance,
5g.player_id

FROM {$tableName} as 5g
where
{$sqlTime}
EOD;

        
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('==> 5ggaming queryTransactions sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
    }
}