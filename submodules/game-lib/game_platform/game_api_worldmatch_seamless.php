<?php
/**
 * World match game integration
 * OGP-35021
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
     - worldmatch_seamless_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_worldmatch_seamless extends Abstract_game_api {

    const URI_MAP = array(
        self::API_queryGameListFromGameProvider => '/platform/identity/games',
    );

    const STATUS_200_OK = 200;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = 'worldmatch_seamless_wallet_transactions';
        $this->original_table = 'worldmatch_seamless_wallet_game_records';
        $this->api_url = $this->getSystemInfo('url');
        $this->game_url = $this->getSystemInfo('game_url', 'https://devservices.wmcasino888.com');
        $this->licensee_code = $this->getSystemInfo('licensee_code','T1SEAMLESSPHP');
        $this->licensee_token = $this->getSystemInfo('licensee_token','A3XsbO8rMP5Utbp7JU6XJBApi5WXJS3r');
        $this->skin = $this->getSystemInfo('skin','XXXX');
        $this->language = $this->getSystemInfo('language','EN');
        $this->currency = $this->getSystemInfo('currency','PHP');
        $this->enable_currency_prefix_on_token = $this->getSystemInfo('enable_currency_prefix_on_token', false);
        $this->display = $this->getSystemInfo('display', "iframe");
    }

    public function isSeamLessGame()
    {
        return true;
    }
    
	public function getPlatformCode(){
        return WORLDMATCH_CASINO_SEAMLESS_API;
    }

    /**
     * overview : generate url
     *
     * @param $apiName
     * @param $params
     * @return string
     */
    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        if($apiName == self::API_queryGameListFromGameProvider){
            $params = http_build_query($params);
            $url = "{$this->game_url}{$apiUri}/{$this->licensee_token}?{$params}";
        }
        $this->CI->utils->debug_log('==> worldmatch generateUrl : ' . $url);
        return $url;
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
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for worldmatch Game";
        if($return){
            $success = true;
            $message = "Successfull create account for worldmatch Game.";
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


    /**
     * overview : query forward game
     *
     * @param $playerName
     * @param $extra
     * @return array
     */
    public function queryForwardGame($playerName, $extra) {
        $game_mode = isset($extra['game_mode']) && $extra['game_mode'] == 'real' ?  "real" : "free";
        $url = "{$this->game_url}/games/{$this->licensee_code}/{$game_mode}/{$extra['game_code']}";
        $url_params = array(
            "authuser" => $this->getGameUsernameByPlayerUsername($playerName),
            "authkey" => $this->getPlayerTokenByUsername($playerName),
            "authskin" => $this->skin,
            "language" => $this->getLanguage($extra['language']),
            "display" => $this->display
        );
        $url_params = http_build_query($url_params);
        $url .= "?{$url_params}";
        return array("success" => true,"url" => $url);
    }

    public function getLanguage($currentLang) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
            case 'zh-CN':
                $language = 'ZH';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            case 'id-ID':
                $language = 'ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
            case 'vi-VN':
                $language = 'VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
            case 'ko-KR':
                $language = 'KR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
            case 'th-TH':
                $language = 'TH';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
            case 'pt-PT':
                $language = 'PT';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case Language_function::INT_LANG_PORTUGUESE :
            case 'hi-IN':
                $language = 'IN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case 'es-ES':
                $language = 'ES';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_JAPANESE:
            case 'ja-JP':
                $language = 'JP';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_FILIPINO:
                $language = 'PH';
                break;
            default:
                $language = 'EN';
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $start = (new DateTime($dateFrom))->modify('first day of this month');
        $end = (new DateTime($dateTo))->modify('last day of this month');
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);
        $results = [];
        foreach ($period as $dt) {
            $yearMonthStr =  $dt->format("Ym");
            $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
            $sqlTime="wm.updated_at >= ? AND wm.updated_at <= ?";
            if($use_bet_time){
                $sqlTime="wm.created_at >= ? AND wm.created_at <= ?";
            }
            $sql = <<<EOD
SELECT
wm.id as sync_index,
wm.roundid as external_uniqueid,
wm.player_id,
wm.trans_type,
SUBSTRING_INDEX(SUBSTRING_INDEX(wm.gameidentity, '-', 2), '-', -1) as game_code,
wm.roundid as round_number,
wm.created_at as start_at,
wm.created_at as end_at,
wm.created_at as bet_at,
wm.amount,
wm.transactionid,
wm.bet_amount,
wm.payout_amount,
(wm.payout_amount - wm.bet_amount) as result_amount,
wm.before_balance,
wm.after_balance,
wm.sbe_status as status,
wm.md5_sum,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as wm
LEFT JOIN game_description as gd ON SUBSTRING_INDEX(SUBSTRING_INDEX(wm.gameidentity, '-', 2), '-', -1) = gd.external_game_id AND gd.game_platform_id = ?
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
        $this->CI->original_game_logs_model->removeDuplicateUniqueid($results, 'external_uniqueid', function(){ return 2;});
        $results = array_values($results);
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
        $sqlTime="wm.created_at >= ? AND wm.created_at <= ?";

        $sql = <<<EOD
SELECT
wm.id as sync_index,
wm.external_uniqueid as external_uniqueid,
wm.roundid as round_no,
wm.trans_type as trans_type,
ABS(wm.amount) as amount,
wm.md5_sum,
if(wm.trans_type = "debit", "{$decType}", "{$incType}") as transaction_type,
wm.created_at as transaction_date,
wm.before_balance,
wm.after_balance,
wm.player_id

FROM {$tableName} as wm
where
{$sqlTime}
EOD;

        
        $params=[
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('==> worldmatch queryTransactions sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $this->CI->load->model(array('original_game_logs_model'));
        $results = $this->queryOriginalGameLogs($dateFrom, $dateTo);
        if(!empty($results)){
            $results = array_values(array_filter($results, function($row) { return ($row['status'] == GAME_LOGS::STATUS_PENDING); }));
        }
        return $results;
    }

    public function checkBetStatus($row){
        echo "<pre>";
        print_r($row);
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        $settledExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->getTransactionsTable(), ['roundid' => $row['round_number'], 'player_id' => $row['player_id'], 'trans_type !=' => 'debit']);
        if(!$settledExist){
            $settledExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->getTransactionsPreviousTable(), ['roundid' => $row['round_number'], 'player_id' => $row['player_id'], 'trans_type !=' => 'debit']);
        }
        if(!empty($row)){
            if(!$settledExist){
                $insertRow['transaction_date'] = $row['start_at'];
                $insertRow['transaction_type'] = $row['trans_type'];
                $insertRow['round_id'] = $row['round_number'];
                $insertRow['amount'] = $row['amount'];
                $insertRow['deducted_amount'] = $row['amount'];
                $insertRow['transaction_id'] = $row['transactionid'];
                $insertRow['transaction_status']  = $row['status'];
                $insertRow['player_id']  = $row['player_id'];
                $insertRow['status'] = Seamless_missing_payout::NOT_FIXED;
                $insertRow['game_platform_id'] = $this->getPlatformCode();
                $insertRow['external_uniqueid'] = $row['external_uniqueid'];
                $insertRow['game_description_id']  = $row['game_description_id'];
                $insertRow['game_type_id']  = $row['game_type_id'];
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $insertRow);
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $results = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->getTransactionsTable(), ['roundid'=> $external_uniqueid, 'trans_type !=' => 'debit'],['id', 'sbe_status']);
        if(!empty($results)){
            return array('success'=>true, 'status'=> $results['sbe_status']);
        } else {
            $results = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->getTransactionsPreviousTable(), ['roundid'=> $external_uniqueid, 'trans_type !=' => 'debit'],['id', 'sbe_status']);
            if(!empty($results)){
                return array('success'=>true, 'status'=> $results['sbe_status']);
            }
        }
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if($statusCode == self::STATUS_200_OK){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('wm Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function queryGameListFromGameProvider($extra = null){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameProviderGamelist',
        );

        $params = [
            "language" => $this->language
        ];

        $this->CI->utils->debug_log('wm: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
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
}