<?php
/**
 * Bti game integration
 * OGP-26860
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
     - bti_seamless_game_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_bti_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    const MD5_FIELDS_FOR_ORIGINAL=[
        'bet_settled_date', 'status',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS=['return'];
    const ERROR_CODE_SUCCESS = 0;

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
        $this->launch_url = $this->getSystemInfo('launch_url');
        $this->mobile_launch_url = $this->getSystemInfo('mobile_launch_url');

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);

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
        return BTI_SEAMLESS_GAME_API;
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
            $this->CI->utils->debug_log('bti Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for bti Game";
        if($return){
            $success = true;
            $message = "Successfull create account for bti Game.";
        }
        $this->updateExternalAccountIdForPlayer($playerId, $playerId);
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
        $this->CI->load->model(array('common_token'));
        $launch_url = ($extra['is_mobile']) ? $this->mobile_launch_url : $this->launch_url;
        $language_code = $this->getLanguage($extra['language']);
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $token = $this->CI->common_token->getValidPlayerToken($player_id);
        $url = $launch_url . "/" . $language_code . "/sports?operatorToken=". $token;
        $isDemo = isset($extra['game_mode']) && strtolower($extra['game_mode']) != "real" ? true : false;
        if($isDemo){
            $url = $launch_url . "/" . $language_code . "/sports?operatorToken=logout";
        }

        $result = array(
            'success' => true,
            'url' => $url
        );
        return $result;
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
            case 'pt':
            case 'pt-br':
            case 'pt-pt':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
                $language = 'pt';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case Language_function::PLAYER_LANG_INDIA :
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
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $this->CI->load->model('original_game_logs_model');
        $result = ['data_inserted' => 0,  'data_updated' => 0];
        $transactions = $this->queryOriginalGameLogsFromTrans($queryDateTimeStart, $queryDateTimeEnd);
        if(!empty($transactions)){
            $gameRecords = [];
            $gameRecordsChanges = [];
            foreach ($transactions as $key => $transaction) {
                $customer_id = $game_username = null;
                $extraInfo = isset($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : null;
                $url = parse_url(isset($extraInfo['url']) ? $extraInfo['url'] : null);
                if(isset($url['query'])){
                    parse_str($url['query'], $query);
                    $game_username = isset($query['cust_id']) ? $query['cust_id'] : null;
                    $customer_id = isset($query['customer_id']) ? $query['customer_id'] : null;
                } else {
                    $game_username = $this->getGameUsernameByPlayerId($transaction['player_id']);
                }
                
                if(!$game_username){
                    continue;
                }

                $xmlstring = isset($extraInfo['data']) ? $extraInfo['data'] : null;
                $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($xml);
                $xmlArray = json_decode($json,TRUE);
                if(!empty($xmlArray)){
                    // echo "<pre>";
                    // print_r($xmlArray);exit();
                    $bet = isset($xmlArray['Bet']['@attributes']) ? $xmlArray['Bet']['@attributes'] : null;
                    if(!empty($bet)){
                        $betData = array(
                            "pl" => 0,
                            "non_cashout_amount" => 0,
                            "combo_bonus_amount" => 0,
                            "bet_settled_date" => isset($bet['CreationDate']) ? $this->gameTimeToServerTime($bet['CreationDate']) : null,
                            "purchase_id" => isset($bet['PurchaseBetID']) ? $bet['PurchaseBetID'] : null,
                            "update_date" => null,
                            "odds" => isset($bet['Odds']) ? $bet['Odds'] : null,
                            "odds_in_user_style" => isset($bet['OddsInUserStyle']) ? $bet['OddsInUserStyle'] : null,
                            "total_stake" => $transaction['bet_amount'],
                            "return" => 0,
                            "bet_status" => isset($bet['Status']) ? $bet['Status'] : null,
                            "username" => isset($game_username) ? $game_username : null,
                            "bet_type_name" => isset($bet['BetTypeName']) ? $bet['BetTypeName'] : null,
                            "bet_type_id" => isset($bet['BetTypeID']) ? $bet['BetTypeID'] : null,
                            "creation_date" => isset($bet['CreationDate']) ? $this->gameTimeToServerTime($bet['CreationDate']) : null,
                            "status" => isset($bet['Status']) ? $bet['Status'] : null,
                            "customer_id" => isset($customer_id) ? $customer_id : null,
                            "merchant_customer_id" => isset($game_username) ? $game_username : null,
                            "created_at" => $this->utils->getNowForMysql(),
                            "updated_at" => $this->utils->getNowForMysql(),
                            "response_result_id" => $transaction['response_result_id'],
                            "external_uniqueid" => isset($bet['BetID']) ? $bet['BetID'] : null,
                            "gain" => isset($bet['Gain']) ? $bet['Gain'] : null,
                            "odds_style_of_user" => isset($bet['UserOddStyle']) ? $bet['UserOddStyle'] : null,
                            "odds_dec" => isset($bet['OddsDec']) ? $bet['OddsDec'] : null,
                            "validStake" => $transaction['bet_amount'],
                        );
                        $betData['status'] = $this->getBetStatus($bet['Status']);
                        $betData['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($betData, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                        $gameRecords[] = $betData;
                        unset($betData);
                    }

                    $betChanges = [];
                    if(isset($xmlArray['Purchases']['Purchase']['Selections']['Selection']['Changes']['Change']['Bets']['Bet']['@attributes'])){
                        $betChanges = $xmlArray['Purchases']['Purchase']['Selections']['Selection']['Changes']['Change']['Bets']['Bet']['@attributes'];
                    }

                    $selections = isset($xmlArray['Purchases']['Purchase']['Selections']['Selection']) ? $xmlArray['Purchases']['Purchase']['Selections']['Selection'] : [];
                    $selections_count = count($selections);
                    if($selections_count > 1  && isset($selections[0])){
                        $end_selections = end($selections);
                        $betChanges = isset($end_selections['Changes']['Change']['Bets']['Bet']['@attributes']) ? $end_selections['Changes']['Change']['Bets']['Bet']['@attributes'] : [];
                    }

                    if(!empty($betChanges)){
                        $betChangesData = array(
                            "return" => isset($betChanges['NewBalance']) ? $this->gameAmountToDB($betChanges['NewBalance']) : null,
                            "bet_settled_date" => isset($betChanges['BetSettledDate']) ? $this->gameTimeToServerTime($betChanges['BetSettledDate']) : null,
                            "bet_status" => isset($betChanges['NewStatus']) ? $betChanges['NewStatus'] : null,
                            "username" => isset($game_username) ? $game_username : null,
                            "status" => isset($betChanges['NewStatus']) ? $betChanges['NewStatus'] : null,
                            "customer_id" => isset($customer_id) ? $customer_id : null,
                            "merchant_customer_id" => isset($game_username) ? $game_username : null,
                            // "created_at" => $this->utils->getNowForMysql(),
                            "updated_at" => $this->utils->getNowForMysql(),
                            "response_result_id" => $transaction['response_result_id'],
                            "external_uniqueid" => isset($betChanges['ID']) ? $betChanges['ID'] : null,
                            "odds" => isset($bet['Odds']) ? $bet['Odds'] : null,
                            "selections" => json_encode(array( "after_balance" => $transaction['after_balance']))
                        );
                        $betChangesData['status'] = $this->getBetStatus($betChanges['NewStatus']);
                        $betChangesData['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($betChangesData, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                        $gameRecordsChanges[] = $betChangesData;
                        unset($betChangesData);
                    }
                }
            }


            if(!empty($gameRecords)){
                foreach ($gameRecords as $key => $gameRecord) {
                    $succ = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('sbtech_bti_game_logs', $gameRecord);
                    if($succ){
                        $result['data_inserted'] ++;
                    }
                }
            }

            if(!empty($gameRecordsChanges)){
                foreach ($gameRecordsChanges as $key => $gameRecordChange) {
                    $succ = $this->CI->original_game_logs_model->updateData('external_uniqueid', $gameRecordChange['external_uniqueid'], 'sbtech_bti_game_logs', $gameRecordChange);
                    if($succ){
                        $result['data_updated'] ++;
                    }
                }
            }
        }
        return array("success" => true, array("result" => $result));
    }

    private function getBetStatus($status_string){
        switch (strtolower($status_string)) {
            case 'opened':
            case 'open':
                $status = GAME_LOGS::STATUS_PENDING;
                break;
            case 'canceled':
            case 'cancel':
                $status = GAME_LOGS::STATUS_CANCELLED;
                break;
            
            default:
                $status = GAME_LOGS::STATUS_SETTLED;
                break;
        }
        return $status;
    }


    private function getBetTypeIdString($id){
        switch ($id) {
            case 1:
                $string = "Single Bet";
                break;
            case 2:
                $string = "Combo Bet";
                break;
            case 3:
                $string = "System Bet";
                break;
            
            default:
                $string = "Single Bet";
                break;
        }
        return $string;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogs'],
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
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo){
        $sqlTime="bti.end_at >= ? AND bti.end_at <= ? AND bti.game_platform_id = ? AND bti.transaction_type in ('creditcustomer', 'debitreserve', 'debitcustomer')";

        $sql = <<<EOD
SELECT
bti.id as sync_index,
bti.response_result_id,
bti.external_unique_id as external_uniqueid,
bti.md5_sum,

bti.player_id,
bti.game_platform_id,
bti.bet_amount as bet_amount,
bti.bet_amount as real_betting_amount,
bti.result_amount,
bti.amount,
bti.transaction_type,
bti.game_id as game_code,
bti.game_id as game,
bti.game_id as game_name,
bti.round_id as round_number,
bti.response_result_id,
bti.extra_info,
bti.start_at,
bti.start_at as bet_at,
bti.end_at,
bti.before_balance,
bti.after_balance,
bti.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as bti
LEFT JOIN game_description as gd ON bti.game_id = gd.external_game_id AND gd.game_platform_id = ?
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
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime='bti.updated_at >= ? and bti.updated_at <= ?';
        if($use_bet_time){
            $sqlTime='bti.created_at >= ? and bti.created_at <= ?';
        }

        $sql = <<<EOD
SELECT bti.id as sync_index,
bti.response_result_id,
bti.merchant_customer_id as player_username,
bti.validStake as bet_amount,
bti.validStake as real_betting_amount,
(bti.return - bti.validStake) as result_amount,
bti.creation_date as start_at,
bti.bet_settled_date as end_at,
bti.creation_date as bet_at,
bti.status,
bti.md5_sum,
bti.purchase_id as round_number,
bti.external_uniqueid,
bti.selections,
bti.odds,
bti.odds_style_of_user as odds_type,
bti.bet_type_id,
bti.bet_type_name,
bti.bet_status,
bti.total_stake,
bti.`return`,
bti.gain,
bti.odds_dec,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name,
gd.english_name as game_english_name,
gd.game_type_id

FROM sbtech_bti_game_logs as bti
LEFT JOIN game_description as gd ON "sportsbook" = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth ON bti.merchant_customer_id = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result =  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // echo "<pre>";
        // print_r($result);exit();
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
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => [
                'odds' => $row['odds'],
                'odds_type' => $row['odds_type'],
            ],
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
        $row['after_balance'] = null;
        if(isset($row['selections'])){
            $selections = json_decode($row['selections'], true);
            $row['after_balance'] = $selections['after_balance'];
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = isset($row['game_name']) ? $row['game_name'] : null;
        $external_game_id = isset($row['game_code']) ? $row['game_code'] : $game_name;

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getSeamlessTransactionTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.start_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.transaction_id as transaction_id,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? and `t`.`transaction_type` != 'debitreserve'
ORDER BY t.updated_at asc, t.id asc;
EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function rebuildBetDetailsFormat($row, $game_type) {
        $bet_details = [];

        switch ($game_type) {
            case self::GAME_TYPE_SPORTS:
            case self::GAME_TYPE_E_SPORTS:
                if (isset($row['round_number'])) {
                    $bet_details['purchaseId'] = $row['round_number'];
                }

                if (isset($row['bet_type_id'])) {
                    $bet_details['betTypeId'] = $row['bet_type_id'];
                }

                if (isset($row['bet_type_name'])) {
                    $bet_details['betTypeName'] = $row['bet_type_name'];
                }

                if (isset($row['bet_status'])) {
                    $bet_details['betStatus'] = $row['bet_status'];
                }

                if (isset($row['odds_type'])) {
                    $bet_details['odds_type'] = $row['odds_type'];
                }

                if (isset($row['odds'])) {
                    $bet_details['odds'] = $row['odds'];
                }

                if (isset($row['odds_dec'])) {
                    $bet_details['oddsDec'] = $row['odds_dec'];
                }

                if (isset($row['gain'])) {
                    $bet_details['gain'] = $row['gain'];
                }

                if (isset($row['bet_amount'])) {
                    $bet_details['validStake'] = $row['bet_amount'];
                }

                if (isset($row['total_stake'])) {
                    $bet_details['totalStake'] = $row['total_stake'];
                }

                if (isset($row['return'])) {
                    $bet_details['return'] = $row['return'];
                }

                if (isset($row['start_at'])) {
                    $bet_details['creationDate'] = $row['start_at'];
                }

                if (isset($row['end_at'])) {
                    $bet_details['betSettledDate'] = $row['end_at'];
                }

                break;
            default:
                $bet_details = $this->defaultBetDetailsFormat($row);
                break;
        }

        if (empty($bet_details) && !empty($row['bet_details'])) {
            $bet_details = is_array($row['bet_details']) ? $row['bet_details'] : json_decode($row['bet_details'], true);
        }

        if (empty($bet_details)) {
            $bet_details = $this->defaultBetDetailsFormat($row);
        }

        return $bet_details;
    }

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['game_english_name'])) {
            $bet_details['game_name'] = $row['game_english_name'];
        }
        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }
        if (isset($row['external_uniqueid'])) {
            $bet_details['bet_id'] = $row['external_uniqueid'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }

        if (isset($row['odds'])) {
            $bet_details['odds'] = $row['odds'];
        }
        if (isset($row['bet_type'])) {
            $bet_details['bet_type'] = $row['bet_type'];
        }

        if (isset($row['extra_info'])) {
            $bet_details['extra'] = $row['extra_info'];
        }

        return $bet_details;
     }



}
