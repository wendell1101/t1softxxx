<?php
/**
 * FA CHAI Gaming Integration Manual
 * OGP-28346
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
     - fc_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_fc_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','status'];
    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_DUPLICATE_ACCOUNT = 502;
    const lang_code_en = 1;
    const lang_code_cn = 2;
    const lang_code_vn = 3;
    const lang_code_th = 4;
    const lang_code_id = 5;
    const lang_code_jp = 7;
    const lang_code_kr = 8;
    const lang_code_br = 9;

    public $agent_code;
    public $agent_key;
    public $game_hall_game_type;
    public $enable_jackpot_status;
    public $eligible_players_for_jackpot_feature;
    public $except_players_for_jackpot_feature;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "USD"); #staging C046_Amusino_USD_NL
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', '1');
        $this->agent_code = $this->getSystemInfo('agent_code', 'AMU'); #staging C046_Amusino_USD_NL
        $this->agent_key = $this->getSystemInfo('agent_key', 'PN9yLZtliyAyJVKt'); #staging C046_Amusino_USD_NL
        $this->enable_jackpot_status = $this->getSystemInfo('enable_jackpot_status', false);
        $this->eligible_players_for_jackpot_feature = $this->getSystemInfo('eligible_players_for_jackpot_feature', []);
        $this->except_players_for_jackpot_feature = $this->getSystemInfo('except_players_for_jackpot_feature', []);

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);
        $this->game_hall_game_type = $this->getSystemInfo('game_hall_game_type', []);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', true);
        $this->game_image_directory = $this->getSystemInfo('game_image_directory', '/gamegatewayincludes/images/game-vendor-icon/Fachai/');
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 35);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);
    }

    const URI_MAP = array(
        self::API_createPlayer => '/AddMember',
        self::API_queryDemoGame => '/GetDemoUrl',
        self::API_queryForwardGame => '/Login',
    );

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return FC_SEAMLESS_GAME_API;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));   
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalPayoutRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/fc_service_api/result";
            return $url;
        }

        if($apiName == self::API_triggerInternalRefundRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/fc_service_api/result";
            return $url;
        }

        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr) { 
        $success = false;
        if(isset($resultArr['Result']) && ($resultArr['Result'] == self::ERROR_CODE_SUCCESS || $resultArr['Result'] == self::ERROR_CODE_DUPLICATE_ACCOUNT)){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('fc Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    // AES Encryption ECB Mode
    private function AESencode($_values)
    {
        Try {
        $data = openssl_encrypt($_values, 'AES-128-ECB', $this->agent_key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);
        }
        Catch (\Exception $e) {
        }
        return $data;
    }

    // AES Decrypt ECB Mode
    private function AESdecode($_values)
    {
        $data = null;
        Try {
        $data = openssl_decrypt(base64_decode($_values), 'AES-128-ECB', $this->agent_key,
        OPENSSL_RAW_DATA);
        }
        Catch (\Exception $e) {
        }
        return $data;
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
        $json_params = array(
            "MemberAccount" => $gameUsername
        );
        $params = array(
            "AgentCode" => $this->agent_code ,
            "Currency" => $this->currency,
            "Params" => $this->AESencode(json_encode($json_params)),
            "Sign" => MD5(json_encode($json_params))
        );

        $this->CI->utils->debug_log('fc create player params: ' . json_encode($params));
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
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_type = isset($extra['game_type']) ? $extra['game_type'] : null;
        $game_type_no = $this->getGametypeNo($game_type);
        $game_hall_game_type = isset($this->game_hall_game_type) ? $this->game_hall_game_type : null;
        $lang = $this->getLanguage($extra['language']);

        //$real_mode = (isset($extra['game_mode']) && strtolower($extra['game_mode']) == "real") ? true : false;
        $real_mode = true;
        $gameMode = (isset($extra['game_mode'])&&!empty($extra['game_mode'])?$extra['game_mode']:null);
        if(in_array($gameMode, $this->demo_game_identifier)){
            $real_mode = false;
        }
        
        $api_name = $real_mode ? self::API_queryForwardGame : self::API_queryDemoGame;
        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            // $this->appendCurrentDbOnUrl($this->lobby_url);
        }

        //extra checking for home link
        if(isset($extra['extra']['home_link'])) {
            $this->lobby_url = $extra['extra']['home_link'];
        }

        if (isset($extra['extra']['home_url'])) {
            $this->lobby_url = $extra['extra']['home_url'];
        } 

      

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $json_params = array(
            "GameID" => $game_code,
            "LanguageID" => $lang
        );


        if($real_mode){
            $json_params = array(
                "MemberAccount" => $gameUsername,
                "GameID" => $game_code,
                "LanguageID" => $lang,
                "HomeUrl" => $this->lobby_url
            );

            if(empty($game_code)){
                unset($json_params['GameID']);
                $json_params["LoginGameHall"] = true;;
            }

            // if(!empty($game_type_no)){
            //     $json_params["GameHallGameType"] = [$game_type_no];
            // }

            if(!empty($game_hall_game_type)){
                $json_params["GameHallGameType"] = $game_hall_game_type;
            }

            // jackpot feature
            if ($this->enable_jackpot_status) {
                if (!empty($this->eligible_players_for_jackpot_feature) && is_array($this->eligible_players_for_jackpot_feature)) {
                    foreach ($this->eligible_players_for_jackpot_feature as $eligible_player) {
                        if ($eligible_player == $playerName) {
                            $json_params["JackpotStatus"] = $this->enable_jackpot_status;
                            break;
                        }
                    }
                } else {
                    // if empty eligible_players_for_jackpot_feature, all players are eligible
                    $json_params["JackpotStatus"] = $this->enable_jackpot_status;
                }
                
                if (!empty($this->except_players_for_jackpot_feature) && is_array($this->except_players_for_jackpot_feature)) {
                    foreach ($this->except_players_for_jackpot_feature as $except_player) {
                        if ($except_player == $playerName) {
                            $json_params["JackpotStatus"] = false;
                            break;
                        }
                    }
                }
            }

            #removes home url if disable_home_link is set to TRUE
            if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
                unset($json_params['HomeUrl']);
            }
        }

        $params = array(
            "AgentCode" => $this->agent_code ,
            "Currency" => $this->currency,
            "Params" => $this->AESencode(json_encode($json_params)),
            "Sign" => MD5(json_encode($json_params))
        );

        $this->CI->utils->debug_log('fc query forward/demo params: ' . json_encode($params));
        return $this->callApi($api_name, $params, $context);
    }

    /**
     * overview : process result for queryForwardGame
     *
     * @param $params
     * @return array
     */
    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $url = null;
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        if($success){
            $url = isset($resultJsonArr['Url']) ? $resultJsonArr['Url']: null;
        }
        $result = array(
            'url' => $url
        );
        return array($success, $result);
    }

    private function getGametypeNo($gameType = null){
        if(empty($gameType)){
            return null;
        }

        $codeNo = null;
        switch (strtolower($gameType)) {
            case 'fishing_game':
                $codeNo = 1;
                break;
            case 'slots':
                $codeNo = 2;
                break;
            case 'arcade':
                $codeNo = 7;
                break;
            case 'table_and_cards':
                $codeNo = 8;
                break;
            
            default:
                $codeNo = null;
                break;
        }
        return $codeNo;
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = self::lang_code_cn;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $language = self::lang_code_id;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = self::lang_code_vn;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = self::lang_code_kr;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = self::lang_code_th;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
                $language = self::lang_code_br;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_JAPANESE:
            case 'jp':
            case 'ja':
            case 'ja-jp':
            case 'ja-JP':
                $language = self::lang_code_jp;
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            // case Language_function::INT_LANG_PORTUGUESE :
            //     $language = 'hi';
            //     break;
            default:
                $language = self::lang_code_en;
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
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime="fc.create_date >= ? AND fc.create_date <= ?";
        $transactionType = '';
        if($this->enable_merging_rows){
            $transactionType = "and fc.transaction_type in ('settle','bet_result')";
        }

        $sql = <<<EOD
SELECT
fc.id as sync_index,
fc.response_result_id,
fc.external_unique_id as external_uniqueid,
md5(fc.net_win) as md5_sum,
fc.player_id,
fc.bet as bet_amount,
fc.bet as real_betting_amount,
fc.net_win as result_amount,
fc.win as win_amount,
fc.game_id as game_code,
fc.game_id as game,
fc.game_id as game_name,
fc.record_id as round_number,
fc.response_result_id,
fc.status,
fc.game_date as start_at,
fc.game_date as bet_at,
fc.game_date as end_at,
fc.before_balance,
fc.after_balance,
fc.game_type,
fc.json_request,
fc.create_date,
fc.transaction_type,

gd.id as game_description_id,
gd.game_type_id

FROM fc_seamless_transactions as fc
LEFT JOIN game_description as gd ON fc.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
fc.record_id is not null
and
{$sqlTime}
{$transactionType}
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('query distinct transaction fc merge sql', $sql, $params);
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

        if(!$this->enable_merging_rows && ($row['transaction_type'] == 'bet' || $row['transaction_type'] == 'settle')){
            if($row['transaction_type'] == 'bet'){
                $row['win_amount'] = 0;
            }else{
                $row['bet_amount'] = 0;
                $row['real_betting_amount'] = 0;
            }
        }else{
        }
        $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
    
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
                'real_betting_amount' => isset($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => isset($row['start_at']) ? $row['start_at'] : $row['create_date'],
                'end_at' =>isset($row['end_at']) ? $row['end_at'] : $row['create_date'],
                'bet_at' => isset($row['start_at']) ? $row['start_at'] : $row['create_date'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
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
            'bet_details' => $this->preprocessBetDetails($row),
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

        $request = !empty($row['json_request']) ? json_decode($row['json_request'], true) : [];

        $row['extra'] = [
            'jackpot_wins' => [
                isset($request['JPPrize']) ? $request['JPPrize'] : 0,
            ], 
            'progressive_contributions' => [
                isset($request['JPBet']) ? $request['JPBet'] : 0,
            ],
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

    public function queryTransactionByDateTime($startDate, $endDate){
$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
if(t.win > 0 , if(t.transaction_type = "bet_result", t.net_win, t.win), t.bet) as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
IFNULL(t.record_id, t.bet_id) as round_no,
t.external_unique_id as external_uniqueid,
if(t.transaction_type = "bet_result", if(t.net_win > 0 , "win", "bet"),t.transaction_type) as trans_type,
t.bet bet_amount,
t.net_win result_amount
FROM fc_seamless_transactions as t
WHERE  `t`.`created_at` >= ? AND `t`.`created_at` <= ? 
ORDER BY t.created_at asc;
EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['round_number'])) {
            $bet_details['bet_id'] = $row['round_number'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (isset($row['extra'])) {
            $bet_details['extra'] = $row['extra'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }
}
