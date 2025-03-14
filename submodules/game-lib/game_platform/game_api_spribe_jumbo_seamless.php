<?php
// require_once dirname(__FILE__) . '/game_api_jumbo_seamless.php';

class Game_api_spribe_jumbo_seamless extends Game_api_jumbo_seamless {
	public function getPlatformCode(){
        return SPRIBE_JUMBO_SEAMLESS_GAME_API;
    }

    public function getAccessToken($playerName = null, $extra = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $balance = $this->queryPlayerBalance($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAccessToken',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );


        $api_action = (isset($extra['game_mode']) && $extra['game_mode'] == 'real') ? self::URI_MAP[self::API_checkLoginToken] : self::URI_MAP[self::API_queryDemoGame];

        // check if game type is empty or isset
        $this->CI->load->model('game_description_model');
        $attributes = $this->CI->game_description_model->queryAttributeByGameCode($this->getPlatformCode(), $extra['game_code']);
        $attributes = json_decode($attributes, true);
        $gType = isset($attributes['gType']) ? $attributes['gType'] : null;
        
        $jumb_params = array(
            'action'    => $api_action,    //  
            'ts'        => $this->jumb_now() ,
            'parent'    => $this->agent,
            'uid'       => $gameUsername ,
            'balance'   => $balance['balance'],
            'lang'      => $this->getLauncherLanguage($extra['language']) ,
            'gType'     => $gType, #'0' , # 0 slot , 7 Fishing machine
            'mType'     => $extra['game_code'],
            'windowMode'=> 2 # 1 - Include game hall, 2 - does not contain the game hall, hide the close button in the game gType and mType are required fields.
        );

        $gameMode = isset($extra['game_mode']) ? $extra['game_mode']:null;
		if(in_array($gameMode, $this->demo_game_identifier)){
            unset($jumb_params['uid']);
        }

        $encrypted = $this->encrypt(json_encode($jumb_params), $this->key, $this->iv);

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $encrypted
        );

        $this->utils->debug_log("JUMBO SEAMLESS jumb_params ============================>", $jumb_params);
        $this->utils->debug_log("JUMBo SEAMLESS ecrypted params ============================>", $params);
        
        if($extra['game_mode'] != 'real'){
            return $this->callApi(self::API_queryDemoGame, $params, $context);
        } else {
            return $this->callApi(self::API_queryForwardGame, $params, $context);
        }

    }

    public function queryOriginalGameLogsFromMonthlyTable($dateFrom, $dateTo, $useBetTime){
        $months = $this->get_months_in_range($dateFrom, $dateTo, "Ym");
        $results = [];
        if(!empty($months)){
            foreach ($months as $key => $month) {
                $tableName = $this->original_transaction_table_name.'_'.$month;
                if ($this->CI->utils->table_really_exists($tableName)) {
                    $sqlTime="spribe.updated_at >= ? AND spribe.updated_at <= ? AND spribe.game_platform_id = ? AND spribe.transaction_type = ?";

                    $unsettledStatuses = implode(',', [Game_logs::STATUS_UNSETTLED, Game_logs::STATUS_CANCELLED, Game_logs::STATUS_REFUND]);
                    if(!$this->enable_merging_rows){
                        // $sqlTime="spribe.updated_at >= ? AND spribe.updated_at <= ? AND spribe.game_platform_id = ? AND spribe.game_status NOT IN ($unsettledStatuses)";
                        
                        //don't get canceled type
                        $sqlTime="spribe.updated_at >= ? AND spribe.updated_at <= ? AND spribe.game_platform_id = ? AND spribe.transaction_type NOT IN ('cancelled')";
                    }

                    $sql = <<<EOD
SELECT
spribe.id as sync_index,
spribe.external_unique_id as external_uniqueid,

spribe.player_id,
spribe.game_platform_id,
COALESCE(spribe.valid_bet, spribe.bet_amount) as bet_amount,
spribe.bet_amount as real_betting_amount,
spribe.result_amount,
spribe.amount,
spribe.transaction_type,
spribe.game_id as game_code,
spribe.game_id as game,
spribe.game_id as game_name,
spribe.round_id,
spribe.response_result_id,
spribe.extra_info,
spribe.start_at,
spribe.start_at as bet_at,
spribe.end_at,
spribe.before_balance,
spribe.after_balance,
spribe.transaction_id,
spribe.game_status,
spribe.game_status as status,
MD5(CONCAT(spribe.bet_amount, spribe.valid_bet, spribe.result_amount, spribe.game_status)) as md5_sum,
spribe.updated_at,
gd.id as game_description_id,
gd.game_type_id
FROM {$tableName} as spribe
LEFT JOIN game_description as gd ON spribe.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE {$sqlTime}
EOD;

                    
                    $params=[
                        $this->getPlatformCode(),
                        $dateFrom,
                        $dateTo,
                        $this->getPlatformCode(),
                        self::TRANSACTION_CREDIT
                    ];

                    if(!$this->enable_merging_rows){
                        $params=[
                            $this->getPlatformCode(),
                            $dateFrom,
                            $dateTo,
                            $this->getPlatformCode()
                        ]; 
                    }

                    $this->CI->utils->debug_log('merge sql', $sql, $params);

                    $monthyResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                    $results = array_merge($results, $monthyResult);
                }
            }
        }
        return $results;
    }

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        #queryOriginalGameLogsFromMonthlyTable will be handled by switching tableName
        // if($this->use_monthly_transactions_table){
        //     return $this->queryOriginalGameLogsFromMonthlyTable($dateFrom, $dateTo, $use_bet_time);
        // }
        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);    
        $prevTableData = [];

        if(date('j', $this->utils->getTimestampNow()) <= $this->allowed_day_to_check_monthly_table) {
            $this->force_check_prev_table_for_sync = true;
        }

        $checkOtherTable = $this->checkOtherTransactionTable();

        if(($this->force_check_prev_table_for_sync&&$this->use_monthly_transactions_table) || $checkOtherTable){        
            $prevTable = $this->getTransactionsPreviousTable();        
            $this->CI->utils->debug_log("(queryOriginalGameLogs) getting prev month data", 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                           
        }
        return array_merge($currentTableData, $prevTableData);
  
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime="spribe.updated_at >= ? AND spribe.updated_at <= ? AND spribe.game_platform_id = ? AND spribe.transaction_type = ?";

        if(!$this->enable_merging_rows){
            //don't get canceled type
            $sqlTime="spribe.updated_at >= ? AND spribe.updated_at <= ? AND spribe.game_platform_id = ? AND spribe.transaction_type NOT IN ('cancelled')";
        }

        $sql = <<<EOD
SELECT
spribe.id as sync_index,
spribe.external_unique_id as external_uniqueid,

spribe.player_id,
spribe.game_platform_id,
COALESCE(spribe.valid_bet, spribe.bet_amount) as bet_amount,
spribe.bet_amount as real_betting_amount,
spribe.result_amount,
spribe.amount,
spribe.transaction_type,
spribe.game_id as game_code,
spribe.game_id as game,
spribe.game_id as game_name,
spribe.round_id,
spribe.response_result_id,
spribe.extra_info,
spribe.start_at,
spribe.start_at as bet_at,
spribe.end_at,
spribe.before_balance,
spribe.after_balance,
spribe.transaction_id,
spribe.ref_transfer_id,
spribe.game_status,
spribe.game_status as status,
MD5(CONCAT(spribe.bet_amount, spribe.valid_bet, spribe.result_amount, spribe.game_status)) as md5_sum,
spribe.updated_at,
gd.id as game_description_id,
gd.game_type_id
FROM {$table} as spribe
LEFT JOIN game_description as gd ON spribe.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::TRANSACTION_CREDIT
        ];

        if(!$this->enable_merging_rows){
            $params=[
                $this->getPlatformCode(),
                $dateFrom,
                $dateTo,
                $this->getPlatformCode()
            ]; 
        }

        $this->CI->utils->debug_log('spribe merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if ($row['transaction_type'] == self::TRANSACTION_DEBIT) {
            $credit_transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->getTransactionsTable(), [
                'transaction_type' => self::TRANSACTION_CREDIT,
                'player_id' => isset($row['player_id']) ? $row['player_id'] : null,
                'round_id' => isset($row['round_id']) ? $row['round_id'] : null,
            ]);

            $row['end_at'] = !is_null($credit_transaction['end_at']) ? $credit_transaction['end_at'] : $row['end_at'];

            if($row['status'] != Game_logs::STATUS_SETTLED){

                $table_name = $this->getTransactionsTable();
    
               
    
                if (isset($credit_transaction['game_status']) && $credit_transaction['game_status'] == Game_logs::STATUS_SETTLED) {
                    $row['status'] = $credit_transaction['game_status'];
                }
            }
           
        }

        if ($row['transaction_type'] == self::TRANSACTION_CREDIT ) {
            $debit_transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->getTransactionsTable(), [
                'transaction_type' => self::TRANSACTION_DEBIT,
                'player_id' => isset($row['player_id']) ? $row['player_id'] : null,
                'transaction_id' => isset($row['ref_transfer_id']) ? $row['ref_transfer_id'] : null, #search the bet by ref transfer id instead
            ], ['start_at']);
            $row['start_at'] = isset($debit_transaction['start_at']) ? $debit_transaction['start_at'] : $row['start_at'];
            $row['status'] = Game_logs::STATUS_SETTLED; #always mark credit request as settled on spribe
        }
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $tableName = $this->getTransactionsTable();
        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
if(t.transaction_type = "debit", -t.amount, t.amount) as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$tableName} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
         }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    const REFUND_CODE_SUCCESS = 0000;
    public function triggerInternalRefundRound($params = '{"test":true}'){
        $this->post_json = true;
        $params = json_decode($params, true);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultFortriggerInternalRefundRound',
        ];
        
        $apiName = self::API_triggerInternalRefundRound;
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultFortriggerInternalRefundRound($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $success = isset($resultArr['status']) &&  $resultArr['status'] == self::REFUND_CODE_SUCCESS ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $bet_amount = isset($row['bet_amount']) ? abs($row['bet_amount']) : 0;

        $result_amount = $row['result_amount'];


        if(!$this->enable_merging_rows){
            if($row['transaction_type'] == self::TRANSACTION_CREDIT || $row['transaction_type'] == self::TRANSACTION_CANCELLED){
                $bet_amount = 0;
                $result_amount = abs($row['amount']);
            }else{
                $bet_amount = abs($row['amount']);
                $result_amount = -1 * abs($row['amount']);
            }
        }

        $data = [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null,
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $result_amount,
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('EBET_SEAMLESS ', $data);
        return $data;

    }

    public function batchRefund($data = [], $extra = [])
    {

        $baseUrl = $this->getSystemInfo("batch_refund_api_url", "http://admin.og.local");

        $game_platform_id = $this->getPlatformCode();

        $table = $this->getTransactionsTable();
        // Fetch all records in a single query
        if(empty($data)) return false;

        $sql = "SELECT extra_info FROM $table WHERE external_unique_id IN (" . implode(',', array_fill(0, count($data), '?')) . ") AND game_platform_id = ?";
        $params = array_merge($data, [$this->getPlatformCode()]);
        $query = $this->CI->db->query($sql, $params);
        
        if ($query) {
            foreach ($query->result() as $result) {
                if (empty($result)) {
                    continue;
                }
                $params = json_decode($result->extra_info, true);
                $params['netWin'] = -$params['amount'];
                $params['refTransferIds'] = [$params['transferId']];
                $api_url = $baseUrl . site_url("jumbo_seamless_service_api/index/{$game_platform_id}/rollback"); 

                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

                $api_response = curl_exec($ch);

                $unique_id = $params['transferId'];
                $this->CI->utils->debug_log("response: $unique_id", $api_response);
                curl_close($ch);
            }
        } else {
            return false;
        }
    }


    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $pendingStatus = Game_logs::STATUS_PENDING;
        $transactionType = self::TRANSACTION_DEBIT;

        $sql = <<<EOD
SELECT 
original.round_id, original.transaction_id, game_platform_id
from {$this->original_transactions_table} as original
where
original.game_status=?
and original.transaction_type=?
and {$sqlTime}
EOD;


        $params=[
            $pendingStatus,
            $transactionType,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('SPIBE_SEAMLESS_GAME_API-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        $rlt =  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $rlt;
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $this->original_transactions_table = $this->getTransactionsTable();

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.transaction_type,
original.game_status as status,
original.game_platform_id,
original.player_id,
original.round_id,
original.transaction_id,
ABS(SUM(original.bet_amount)) as amount,
ABS(SUM(original.bet_amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_unique_id as external_uniqueid
from {$this->original_transactions_table} as original
left JOIN game_description as gd ON original.game_id = gd.external_game_id and gd.game_platform_id=?
where
round_id=? and transaction_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('JILI SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
game_status
FROM {$this->original_transactions_table}
WHERE
game_platform_id=? AND external_unique_id=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$trans['game_status']);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }
}