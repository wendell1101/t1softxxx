<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Genesis
* Game Type: Slots
* Wallet Type: Seamless
*
* @category Game_platform
* @version 
* @integrator @wilson.php.ph

    Related File
    -genesis_service_api.php
**/

class Abstract_game_api_common_genesis_seamless extends Abstract_game_api {
    const MD5_FIELDS_FOR_ORIGINAL = ['total_bet','total_won','game_id','after_balance','before_balance'];
    const MD5_FLOAT_AMOUNT_FIELDS = ['total_bet','total_won','after_balance','before_balance'];
    const MD5_FIELDS_FOR_MERGE=['token','game_id','roundid','total_bet','total_won','result_amount','status','before_balance','after_balance'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['total_bet','result_amount','after_balance','before_balance'];

    const URI_MAP = array(
        self::API_syncGameRecords => '/m4/spindata/query/partner',
    );

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const HISTORY = 'history';

    const FISHING_GAME_CODE = 'M4-0075';

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->slots_game_url = $this->getSystemInfo('slots_game_url');
        $this->history_url = $this->getSystemInfo('history_url');
        $this->partner_token = $this->getSystemInfo('partnerToken');
        $this->language = $this->getSystemInfo('language');
        $this->return_slot_url = $this->getSystemInfo('return_slot_url');
        // max value 5000
        $this->api_history_limit = $this->getSystemInfo('api_history_limit');
        $this->fishing_game_demo_url = $this->getSystemInfo('fishing_game_demo_url','https://ugc.star9ad.com/M4-0075/HTML5/index.html?partner=2548d896-1eb5-eb5c-51fa-d62c25755132&gs=nurgs-stg&partnerCode=dummy_verticle&referer=ugc.star88ad.com&refererRetries=0');
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        if (array_key_exists(self::HISTORY, $params)) {

            $url = $this->history_url.$params["method"];
        } else{
            return $this->returnUnimplemented();
        }
        return $url;
    }

    public function getHttpHeaders($params){
        return array(   "X-Genesis-PartnerToken" => $this->partner_token,
                        // "X-Genesis-Secret"       => $this->secret,
                        "Content-Type"           => "application/json"
                    );
    }

    protected function customHttpCall($ch, $params) {
        $this->utils->debug_log("GENESIS_SEAMLESS customHttpCall ============================>", json_encode($params));
        switch ($params['request_method']){
            case 'GET':
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                break;
            case 'POST':

            break;
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        return $this->returnUnimplemented();
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){

        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for genesis seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for genesis seamless api";
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );
    }
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );
    }
    
    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        return $result;
    }
    public function queryForwardGame($playerName, $extra) {
        $player_id = $this->getPlayerIdInPlayer($playerName);
        $token = $this->getPlayerTokenByUsername($playerName);

        $random = $this->utils->getTimestampNow();

        $game_code = $extra['game_code'];
        // $game_url = ($extra['type'] == "slots") ? $this->slots_game_url : $this->fishing_game_url;
        $game_url = $this->slots_game_url;
        $game_url =str_replace("?", $game_code, $game_url);

         $params = array(
            "partner" => $this->partner_token,
            "session" => $token,
            "mode" => $extra['game_mode'] == "real" ? "real" : "play",
        );

        $returnurl = $extra['is_mobile'] ? $this->utils->getSystemUrl('m') : $this->utils->getSystemUrl('www').$this->return_slot_url;

        // if (isset($extra['extra']['t1_lobby_url'])) {
        //     $returnurl = $extra['extra']['t1_lobby_url'];
        // }

        $language = $this->getLauncherLanguage($extra['language']);

        if($language == "zh-hans"){
            $params['language'] = $language;
        }

        $url_params = "?".http_build_query($params);
        $generateUrl = $game_url.$url_params."&returnurl=".urlencode($returnurl);

        if($game_code == self::FISHING_GAME_CODE && $extra['game_mode'] != "real"){
            $generateUrl = $this->fishing_game_demo_url;
        }

        $result = array(
            "success" => true,
            "data" =>$params,
            "url" => $generateUrl
        );

        $this->utils->debug_log("GENESIS_SEAMLESS_QUERYFORWARD ============================>", json_encode($result));
        return $result;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }
    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d\TH:i:s\Z');
        $endDate = $endDate->format('Y-m-d\TH:i:s\Z');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords'
        );

        $success = false;
        $cur_fetch_size = 0;
        $done = false;

        while (!$done) {
            $date_params = array(
                "startDate" => $startDate,
                "endDate"   => $endDate,
                "startIndex"    => $cur_fetch_size,
                "limit" => $this->api_history_limit
            );

            $url_params = "?".http_build_query($date_params);
            
            $params = array(
                "method"            => self::URI_MAP[self::API_syncGameRecords].$url_params,
                "request_method"    => self::METHOD_GET,
                "history"           => true
            );

            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
            $this->utils->debug_log("Sync Orignal params ============================>", $params, $api_result);

            if ($api_result && $api_result['success']) {
                $total_query_size = @$api_result['total_query_size'];
                $fetch_size = @$api_result['fetch_size'];

                //next page
                $cur_fetch_size += $fetch_size;
                $done = $cur_fetch_size >= $total_query_size;
                $this->CI->utils->debug_log('cur_fetch_size: ',$cur_fetch_size,'total_query_size:',$total_query_size,'fetch_size:', $fetch_size, 'done', $done, 'result', $api_result);
            }
            if ($done) {
                $success = true;
            }
        }
        return array('success' => $success);
    }

    public function processResultForSyncGameRecords($params) {
        // $this->CI->load->model('original_game_logs_model');
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [];

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );
        $responseRecords = !empty($resultArr)?$resultArr:[];
        $gameRecords = !empty($responseRecords['data']) ? $responseRecords['data'] : [];
        $this->utils->debug_log("processResultForSyncGameRecords============================>", json_encode($gameRecords));
        if($success&&!empty($gameRecords))
        {
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

            $this->CI->utils->debug_log('before process available rows', 'gamerecords ->',count($gameRecords), 'gameRecords->', json_encode($gameRecords));

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            $dataResult['data_count'] = count($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($updateRows);

            $result['total_data'] = count($responseRecords['data']);
            $result['total_query_size'] = $responseRecords['total_query_size'];
            $result['fetch_size'] = $responseRecords['fetch_size'];
        }
        
        return array($success, $result);
    }

    private function rebuildGameRecords(&$gameRecords,$extra)
    {
        $responseResultId = $extra['response_result_id'];
        $insertRecord =[];
        foreach($gameRecords as $key => $record)
        {
            $playerID = isset($record['user_id']) ? $this->getPlayerIdInGameProviderAuth(strtolower($record['user_id'])) : NULL;
            $playerUsername = $this->getPlayerUsernameByGameUsername($record['user_id']);
            $token = $this->getPlayerTokenByUsername($playerUsername);

            //Data from Genesis API
            $insertRecord[$key]['partner_data']       = isset($record['partner_data']) ? $record['partner_data'] : NULL;
            $insertRecord[$key]['user_id']        = isset($record['user_id']) ? $record['user_id'] : NULL;
            $insertRecord[$key]['game_id']    = isset($record['game_id']) ? $record['game_id'] : NULL;
            $insertRecord[$key]['causaility']      = isset($record['causality']) ? $record['causality'] : NULL;
            $insertRecord[$key]['timestamp']  = isset($record['timestamp']) ? date('Y-m-d H:i:s', strtotime($record['timestamp'])) : NULL; //gmt+8
           
            // $insertRecord[$key]['timestamp']   = isset($record['timestamp']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['timestamp']))) : NULL;
            $insertRecord[$key]['currency']           = isset($record['currency']) ? $record['currency'] : NULL;
            $insertRecord[$key]['total_bet']      = isset($record['total_bet']) ? $this->gameAmountToDB($record['total_bet']) : NULL;
            $insertRecord[$key]['total_won']      = isset($record['total_won']) ? $this->gameAmountToDB($record['total_won']) : NULL;
            $insertRecord[$key]['balance']        = isset($record['balance']) ? $this->gameAmountToDB($record['balance']) : NULL;
            $insertRecord[$key]['merchantcode']       = isset($record['merchantcode']) ? $record['merchantcode'] : NULL;
            $insertRecord[$key]['device']     = isset($record['device']) ? $record['device'] : NULL;
            $insertRecord[$key]['user_type']  = isset($record['user_type']) ? $record['user_type'] : NULL;
            $insertRecord[$key]['bonusproviderref']  = isset($record['bonusproviderref']) ? $record['bonusproviderref'] : NULL;
            $insertRecord[$key]['roundType']  = isset($record['roundType']) ? $record['roundType'] : NULL;
            $insertRecord[$key]['boosterType']  = isset($record['boosterType']) ? $record['boosterType'] : NULL;
            $insertRecord[$key]['bonusBet']  = isset($record['bonusBet']) ? $this->gameAmountToDB($record['bonusBet']) : NULL;
            $insertRecord[$key]['betValue']  = isset($record['betValue']) ? $this->gameAmountToDB($record['betValue']) : NULL;
            $insertRecord[$key]['roundid']  = isset($record['roundid']) ? $record['roundid'] : NULL;
            $insertRecord[$key]['jp_id']  = isset($record['jp_id']) ? $record['jp_id'] : NULL;
            $insertRecord[$key]['jpcontrib']  = isset($record['jpcontrib']) ? $record['jpcontrib'] : NULL;
            
            //extra info from SBE
            $insertRecord[$key]['username'] = isset($record['user_id']) ? $record['user_id'] : NULL;
            $insertRecord[$key]['player_id'] = ($playerID) ? $playerID : $record['user_id'];
            $insertRecord[$key]['external_uniqueid'] = $insertRecord[$key]['causaility']; //add external_uniueid for og purposes
            $insertRecord[$key]['response_result_id'] = $responseResultId;
            $insertRecord[$key]['created_at'] = $this->utils->getNowForMysql();

            $insertRecord[$key]['status'] = Game_logs::STATUS_SETTLED;
            $insertRecord[$key]['token'] = $token;
            $insertRecord[$key]['after_balance'] = isset($record['balance']) ? $this->gameAmountToDB($record['balance']) : null;
            $insertRecord[$key]['before_balance'] = $this->gameAmountToDB( ($record['balance'] - $record['total_won']) + $record['total_bet'] );
        }
        $gameRecords = $insertRecord;
    }

    public function getAvailableRows($rows) {

        $this->db->select('causaility')->from($this->tableName)->where_in('causaility', array_column($rows, 'causaility'));
        $existsRow = $this->runMultipleRowArray();
        $availableRows = null;
        if (!empty($existsRow)) {
            $existsId = array_column($existsRow, 'causaility');
            $availableRows = array();
            foreach ($rows as $row) {
                $causaility = $row['causaility'];
                if (!in_array($causaility, $existsId)) {
                    $availableRows[] = $row;
                }
            }
        } else {
            $availableRows = $rows;
        }
        return $availableRows;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token){
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='gns.timestamp >= ? and gns.timestamp <= ?';
        $sql = <<<EOD
SELECT gns.id as sync_index,
gns.username as player_username,
gns.token,
gns.game_id as game, 
gns.game_id as game_code, 
gns.roundId as round,
gns.total_won AS won_amount,
gns.total_bet AS bet_amount,
gns.balance AS after_balance,
gns.user_id,
gns.causaility,
gns.timestamp as game_date,
gns.response_result_id,
gns.external_uniqueid,
gns.created_at,
gns.updated_at,
gns.md5_sum,
gns.status,
gns.before_balance,
gns.after_balance,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_name,
gd.game_type_id,

gt.game_type AS game_type

FROM {$this->original_gamelogs_table} as gns
LEFT JOIN game_description as gd ON gns.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON gns.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $extra_info=[];
        $has_both_side=0;

        if(empty($row['md5_sum'])){
            //genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $result_amount = $row['won_amount'] - $row['bet_amount'];
        $bet_amount = $row['bet_amount'];

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['game_date'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['game_date'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            // 'status' => $status,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => $extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 2:
            case 'zh-cn':
                $lang = 'zh-hans'; // chinese
                break;
            default:
                $lang = 'en_US'; // default as english
                break;
        }
        return $lang;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));
        
$sql = <<<EOD
SELECT 
t.playerId as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.roundId as round_no,
t.external_uniqueid as external_uniqueid,
t.`action` trans_type
FROM {$this->original_transaction_table} as t
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
      
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);                
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], ['debit'])){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}