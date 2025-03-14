<?php
/**
 * DIGITAIN game integration
 * OGP-26521
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
     - digitain_seamless_game_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_digitain_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status_db', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const ERROR_CODE_SUCCESS = 0;
    protected $sign_key;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lang = $this->getSystemInfo('lang');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->partner_id = $this->getSystemInfo('partner_id','285');#stg creds
        $this->secret_key = $this->getSystemInfo('secret_key','BD4DE5D6-FBEC-480E-8BEE-C3BE75D27CB5');#stg creds
        $this->currency = $this->getSystemInfo('currency','VND');#stg creds
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 'en');

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);

        #params on script
        $this->sport_hostname = $this->getSystemInfo('sport_hostname', 'https://digitain.triplenumtech.net');
        $this->current_page = $this->getSystemInfo('current_page', 'Home');
        $this->odds_format = $this->getSystemInfo('odds_format');
        $this->odds_format_list = $this->getSystemInfo('odds_format');
        $this->sports_book_view = $this->getSystemInfo('sports_book_view');
        $this->theme = $this->getSystemInfo('theme');
        $this->view = $this->getSystemInfo('view', 'european');
        $this->parent_domains = $this->getSystemInfo('parent_domains', []);
        $this->sport_partner = $this->getSystemInfo('sport_partner', '2d945fb0-482c-4bd4-bf67-56b98cd76d41'); #beta guid


        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

    const URI_MAP = array(
        self::API_queryForwardGame => '/',
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return DIGITAIN_SEAMLESS_API;
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
            $this->CI->utils->debug_log('digitain Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for digitain Game";
        if($return){
            $success = true;
            $message = "Successfull create account for digitain Game.";
        }
        $this->updateExternalAccountIdForPlayer($playerId, $playerId);
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

    public function queryForwardGame($playerName, $extra = null) {
        $language = $this->getLanguage($extra['language']);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->CI->common_token->getValidPlayerToken($playerId);
        // $token = "d04aa0c1658d811bfbf46c43ebd9ab94";
        // $this->view = "asian";

        #manadatory
        $result = array(
            "hostname" => $this->sport_hostname,
            "server" => $this->sport_hostname,
            "token" => $token, 
            "currentPage" => $this->current_page,
            "language" => $language,
            "view" => $this->view
        );

        #optional
        if($this->odds_format){
            $result['oddsFormat'] = $this->odds_format;
        }

        if($this->odds_format){
            $result['oddsFormatList'] = $this->odds_format_list;
        }

        if($this->sports_book_view){
            $result['sportsBookView'] = $this->sports_book_view;
        }

        if($this->theme){
            $result['theme'] = $this->theme;
        }

        if($this->parent_domains){
            $result['parent_domains'] = implode(',', $this->parent_domains);
        }

        if($this->sport_partner){
            $result['sportPartner'] = $this->sport_partner;
        }
        return $result;
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
            // case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            // case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            //     $language = 'id';
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'ko';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
                $language = 'pt';
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

    public function processResultForQueryForwardGame($params) {
        
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
        $sqlTime="digitain.end_at >= ? AND digitain.end_at <= ? AND digitain.game_platform_id = ?";

        $sql = <<<EOD
SELECT
DISTINCT(digitain.transaction_id) 
FROM common_seamless_wallet_transactions as digitain
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('query distinct transaction digitain merge sql', $sql, $params);
        $transaction_ids = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $transaction_ids = array_column($transaction_ids, 'transaction_id');
        $result = [];
        if(!empty($transaction_ids)){
            $result = $this->preProcessTransactions($transaction_ids);
        }
        return $result;
    }

    private function preProcessTransactions( array $transactions){
        $result = [];
        if(!empty($transactions)){
            foreach ($transactions as $key => $transaction) {
                $details = $this->queryTransactionDetails($transaction);
                $row_details = $this->queryRowDetails($details['last_id']);
                $details['after_balance'] = $row_details['after_balance'];
                $details['status'] = $details['status_db'] = $this->getGameRecordsStatus($row_details['status']);
                $details['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($details, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
                if(!isset($details['bet_amount'])){ //check if null
                    $details['bet_amount'] = 0;
                }
                $result[] = $details;
            }
        }
        return $result;
    }

    public function queryTransactionDetails($transactionId) {
        $this->CI->load->model('original_game_logs_model');
        $sqlRound="digitain.transaction_id = ? AND digitain.game_platform_id = ?";

        $sql = <<<EOD
SELECT
min(digitain.id) as sync_index,
max(digitain.id) as last_id,
sum(digitain.bet_amount) as bet_amount,
sum(digitain.result_amount) as result_amount,
min(start_at) as start_at,
max(end_at) as end_at,
transaction_id as external_uniqueid,
transaction_id as round_number,
transaction_id ,
digitain.player_id,
digitain.response_result_id,
"sportsbook" as game_name,
"sportsbook" as game_code,
"sportsbook" as game_id,
gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as digitain
LEFT JOIN game_description as gd ON "sportsbook" = gd.external_game_id AND gd.game_platform_id = ?
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
    }

    private function getGameRecordsStatus($betState) {
        $this->CI->load->model(array('game_logs'));
        $new = 1;
        $won = 2;
        $lost = 3;
        $cashOut = 5 ;
        $cashBack = 6;
        $expressBonus = 7;
        $boreDrawMoneyBack = 10;
        $ultraCashBack = 11;
        $multyBetOfTheDay = 12;

        switch ($betState) {
        case $new:      
            $status = Game_logs::STATUS_PENDING;
            break;
        case $new:  
        case $won:
        case $lost:
        case $cashOut:
        case $cashBack:
        case $expressBonus:
        case $boreDrawMoneyBack:
        case $ultraCashBack:
        case $multyBetOfTheDay:    
            $status = Game_logs::STATUS_SETTLED;
            break;
        default:
            $status = Game_logs::STATUS_PENDING;
            break;
        }
        return $status;
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
