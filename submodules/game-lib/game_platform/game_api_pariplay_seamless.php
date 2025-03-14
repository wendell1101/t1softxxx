<?php
/**
 * PARIPLAY_SEAMLESS_API game integration
 * OGP-25059
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
     - pariplay_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_pariplay_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status_db', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const SPORTS_GAMECODE = "BTO_BtoBetSport";
    const ROUND_FINISHED = 1;
    const ROUND_UNFINISHED = 0;
    const ROUND_CANCEL = 2;
    const ENTIRE_ROUND_CANCEL = 3;
    const PARENT_GAME_PLATFORM_ID = PARIPLAY_SEAMLESS_API;

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url','https://hubgames.stage.pariplaygames.com');
        $this->account_username = $this->getSystemInfo('account_username');
        $this->account_password = $this->getSystemInfo('account_password');
        $this->country_code = $this->getSystemInfo('country_code');
        $this->currency_code = $this->getSystemInfo('currency_code');
        $this->language_code = $this->getSystemInfo('language_code');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->cashier_url = $this->getSystemInfo('cashier_url');
        $this->redirect = $this->getSystemInfo('redirect', true);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->sports_url = $this->getSystemInfo('sports_url', 'https://sportsbook-launcher.stage.pariplaygames.com');#stg

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);

        $this->default_launch_game_code = $this->getSystemInfo('default_launch_game_code', '');
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        
        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

    const URI_MAP = array(
        self::API_queryForwardGame => '/api/LaunchGame',
        self::API_getGameProviderGamelist => '/api/GameList',
        self::API_queryDemoGame => '/api/LaunchDemoGame',
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return PARIPLAY_SEAMLESS_API;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));   
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalPayoutRound){
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/pariplay_service_api/Credit";
            return $url;
        }

        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        if(isset($params['GameCode']) && $params['GameCode'] == self::SPORTS_GAMECODE){
            $url = $this->sports_url . $apiUri;
        }
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $isDemo = false) { 
        $success = false;
        if((isset($resultArr['Token']) && isset($resultArr['Url'])) || ($isDemo && isset($resultArr['Url'])) ){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('pariplay Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for pariplay Game";
        if($return){
            $success = true;
            $message = "Successfull create account for pariplay Game.";
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

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
            case 'zh-CN':
            case 'zh-cn':
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            case 'id-ID':
            case 'id-id':
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
            case 'vi-VN':
            case 'id-vn':
                $language = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
            case 'ko-KR':
            case 'id-kr':
                $language = 'ko';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
            case 'th-TH':
            case 'id-th':
                $language = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
            case 'pt-PT':
            case 'pt-pt':
                $language = 'pt';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case Language_function::PLAYER_LANG_INDIA :
            case 'hi-IN':
            case 'id-in':
                $language = 'in';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function queryForwardGame($playerName, $extra = null) {
        $apiName = self::API_queryDemoGame;
        $gameUsername = null;
        $isDemo = true;
        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $isDemo = false;
            $apiName = self::API_queryForwardGame;
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        }
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'playerId' => $this->getPlayerIdFromUsername($playerName),
            'gameUsername' => $gameUsername,
            'isDemo' => $isDemo
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

        if(( !isset($extra['game_code'])||empty($extra['game_code']) ) && !empty($this->default_launch_game_code)){
            $extra['game_code'] = $this->default_launch_game_code;
        }


        $params = array(
            'GameCode' => $extra['game_code'],
            'PlayerId' => $gameUsername,
            'PlayerIP' =>  $this->utils->getIP(),
            'CountryCode' => $this->country_code,
            'CurrencyCode' => $this->currency_code,
            'LanguageCode' => $this->getLanguage($extra['language']),
            'HomeUrl' => $this->lobby_url,
            'CashierUrl' => $this->cashier_url,
            'Account' => array(
                "UserName" => $this->account_username,
                "Password" => $this->account_password,
            )
        );

        #removes homeUrl if disable_home_link is set to TRUE
        if((isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) || $this->force_disable_home_link) {
            unset($params['HomeUrl']);
            unset($params['CashierUrl']);
        }

        $this->CI->utils->debug_log('PARIPLAY: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $this->CI->load->model('external_common_tokens');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $isDemo = $this->getVariableFromContext($params, 'isDemo');
        $result = array("url" => null);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $isDemo);
        if($success){
            $result['url'] = isset($resultArr['Url']) ? $resultArr['Url'] : null ;
            if(isset($resultArr['Token'])){
                
                //Move updating of token after round ends at controller
                // $currentToken = $this->CI->external_common_tokens->getExternalToken($playerId, $this->getPlatformCode());
                // // echo $playerId;exit();
                // $this->CI->external_common_tokens->updatePlayerExternalTokenStatus($playerId, $currentToken, external_common_tokens::T_INACTIVE); 
                
                $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($playerId,
                    $resultArr['Token'],
                    json_encode($resultArr),
                    // $this->getPlatformCode(),
                    self::PARENT_GAME_PLATFORM_ID,
                    $this->currency_code
                );
            }
        } 

        return array($success, $result);
    }

    public function queryGameList(){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameList',
        );

        $params = array(
            'Account' => array(
                "UserName" => $this->account_username,
                "Password" => $this->account_password,
            )
        );

        $this->CI->utils->debug_log('PARIPLAY: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
    }

    public function processResultForQueryGameList($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = false;
        $result = [];
        if(isset($resultArr['Games'])){
            $success = true;
            $result['games'] = $resultArr['Games'];
        }
        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        // return $this->returnUnimplemented();
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $rounds = $this->queryRoundsForUpdate($queryDateTimeStart, $queryDateTimeEnd);
        $success = false;
        if(!empty($rounds)){
            $rounds = array_column($rounds, 'round_id');
            foreach ($rounds as $key => $round) {
                $roundDetails = $this->queryRoundDetails($round);
                if($roundDetails['status'] == self::ROUND_CANCEL || $roundDetails['status'] == self::ROUND_UNFINISHED){
                    if($roundDetails['count_credit'] > 0 || $roundDetails['count_endGame'] > 0){ #possible cancel partial bet only for cancel status, for unfinnish possible status not ended by provider
                        $roundDetails['status'] = self::ROUND_FINISHED; #override status
                    }
                }
                $status = $this->getRoundStatus($roundDetails['status']);
                $data['flag_of_updated_result'] = $status;
                $data['id'] = $roundDetails['id'];
                $data['updated_at'] = $this->CI->utils->getNowForMysql();
                $data['end_at'] = $roundDetails['end_at'];;
                $data['result_amount'] = $roundDetails['result_amount'];
                $data['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($data, ['flag_of_updated_result', 'result_amount']);
                unset($data['result_amount']); #unset dont update result amount of bet
                $success = $this->CI->original_game_logs_model->updateRowsToOriginal('common_seamless_wallet_transactions', $data);
            }
        }
        return array("success" => $success, array("total_rounds_updated" => count($rounds)));
    }

    public function queryRoundsForUpdate($dateFrom, $dateTo) {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="pp.end_at >= ? AND pp.end_at <= ? AND pp.game_platform_id = ?";

        $sql = <<<EOD
SELECT
DISTINCT(round_id)
FROM common_seamless_wallet_transactions as pp
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function queryRoundDetails($roundid) {
        $this->CI->load->model('original_game_logs_model');
        $sqlRound="pp.round_id = ? AND pp.game_platform_id = ?";

        $sql = <<<EOD
SELECT
min(pp.id) as id,
max(pp.id) as last_id,
max(pp.status) as status,
sum(pp.bet_amount) as bet_amount,
sum(pp.result_amount) as result_amount,
min(start_at) as start_at,
max(end_at) as end_at,
sum(if(pp.transaction_type ="credit", 1,0)) as count_credit,
sum(if(pp.transaction_type ="debit", 1,0)) as count_debit,
sum(if(pp.transaction_type ="endGame", 1,0)) as count_endGame,
sum(if(pp.transaction_type ="cancelTransaction", 1,0)) as count_cancelTransaction,
round_id

FROM common_seamless_wallet_transactions as pp
WHERE
{$sqlRound}
EOD;
        $params=[
            $roundid,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function queryAfterBalanceById($id){
        $this->CI->load->model('original_game_logs_model');
        $sqlId="pp.id = ?";
        $sql = <<<EOD
SELECT
after_balance
FROM common_seamless_wallet_transactions as pp
WHERE
{$sqlId}
EOD;
        $params=[
            $id
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result; 
    }

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getRoundStatus($status) {
        $this->CI->load->model(array('game_logs'));
        switch ($status) {
            case self::ROUND_UNFINISHED:
                $status = Game_logs::STATUS_PENDING;
                break;
            case self::ROUND_CANCEL:
            case self::ENTIRE_ROUND_CANCEL:
                $status = Game_logs::STATUS_REJECTED;
                break;
            case self::ROUND_FINISHED:
                $status = Game_logs::STATUS_SETTLED;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }
        return $status;
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
        $sqlTime="pariplay.end_at >= ? AND pariplay.end_at <= ? AND pariplay.game_platform_id = ? AND pariplay.flag_of_updated_result > ?";

        $sql = <<<EOD
SELECT
pariplay.id as sync_index,
pariplay.response_result_id,
pariplay.external_unique_id as external_uniqueid,
pariplay.md5_sum,

pariplay.player_id,
pariplay.game_platform_id,
pariplay.bet_amount as bet_amount,
pariplay.bet_amount as real_betting_amount,
pariplay.result_amount,
pariplay.amount,
pariplay.transaction_type,
pariplay.game_id as game_code,
pariplay.game_id as game,
pariplay.game_id as game_name,
pariplay.round_id as round_number,
pariplay.response_result_id,
pariplay.extra_info,
pariplay.start_at,
pariplay.start_at as bet_at,
pariplay.end_at,
pariplay.before_balance,
pariplay.after_balance,
pariplay.transaction_id,
pariplay.flag_of_updated_result as status,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as pariplay
LEFT JOIN game_description as gd ON pariplay.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::ROUND_UNFINISHED
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
            'bet_details' => $row['bet_details'],
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
        $roundDetails = $this->queryRoundDetails($row['round_number']);
        if(!empty($roundDetails)){
            $row['bet_amount'] = $roundDetails['bet_amount'];
            $row['real_betting_amount'] = $roundDetails['bet_amount'];
            $row['result_amount'] = $roundDetails['result_amount'];
            $row['end_at'] = $roundDetails['end_at'];
            $row['start_at'] = $roundDetails['start_at'];
            $last_id = $this->queryAfterBalanceById($roundDetails['last_id']);
            $row['after_balance'] = $last_id['after_balance'];
        }
        $row['bet_details'] = lang('N/A');
        $extra_info = json_decode($row['extra_info'], true);
        $bet_details = [];
        if(isset($extra_info['BetSlipGroupId'])){
            $bet_details = array(
                lang('Bet Slip') => $extra_info['BetSlipGroupId']
            );
            $row['bet_details'] = $bet_details;
        }
        
        if(isset($extra_info['Feature']) && isset($extra_info['FeatureId'])){
            $bonus_details = array(
                "Feature" => $extra_info['Feature'],
                "FeatureId" => $extra_info['FeatureId'],
                "AfterBonusBalance" => isset($extra_info['freebet_afterbalance'])  ? (string) number_format($extra_info['freebet_afterbalance'], 3) : null,
                "BeforeBonusBalance" => isset($extra_info['freebet_beforeBalance']) ? (string) number_format($extra_info['freebet_beforeBalance'], 3) : null,
            );
            $row['bet_details'] = array_merge($bet_details, $bonus_details);
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

    public function queryBTOBETRounds($dateFrom, $dateTo) {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="DATE(pp.end_at) >= ? AND DATE(pp.end_at) <= ? AND pp.game_platform_id = ?";

        $sql = <<<EOD
SELECT
DISTINCT(round_id)
FROM common_seamless_wallet_transactions as pp
WHERE
{$sqlTime}
AND pp.game_id = 'BTO_BtoBetSport'
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('queryBTOBETRounds sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function get_btobet_rounds_missing_endgame($dateFrom, $dateTo){
        // echo $dateTo;exit();
        $rounds = $this->queryBTOBETRounds($dateFrom, $dateTo);
        // echo "<pre>";print_r($rounds);exit();
        $rounds_missing_endgame = [];
        if(!empty($rounds)){
            $rounds = array_column($rounds, 'round_id');
            foreach ($rounds as $key => $round) {
                $roundDetails = $this->queryRoundDetails($round);
                if($roundDetails['count_debit'] > 0 && 
                        $roundDetails['count_endGame'] == 0 && 
                            $roundDetails['status'] == self::ROUND_UNFINISHED){
                    $rounds_missing_endgame[] = $round;
                }
            }
        }
        return $rounds_missing_endgame;
    }

    public function triggerInternalPayoutRound($params = '{"test":true}'){
        $params = json_decode($params, true);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
        ];
        
        $apiName = self::API_triggerInternalPayoutRound;
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $success = isset($resultArr['TransactionId']) ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }
}
