<?php
require_once dirname(__FILE__) . '/game_api_common_mgplus.php';

class Game_api_mgplus_seamless_idr1_api extends Game_api_common_mgplus {
	const ORIGINAL_TABLE = "mgplus_seamless_idr1_game_logs";
	const PRODUCT_ID = "smg"; # smg is Microgaming
    const CURRENCY = "IDR"; 
    const STATUS_CLOSED = 1;
    const FLAG_UPDATED = 1;
    const FlAG_NOT_UPDATED = 0;

	public function getPlatformCode(){
		return MGPLUS_SEAMLESS_IDR1_API;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function __construct(){
    	$this->product_id = self::PRODUCT_ID;
        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
        $this->currency = $this->getSystemInfo('currency', self::CURRENCY);
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->CI->utils->debug_log('MGPLUS: (' . __FUNCTION__ . ')', 'PARAMS:', $playerName, 'RESULT:', $result);

        return $result;
    }

    /** 
    * Deposit to Game
    */
    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    /** 
    * Withdraw From Game
    */
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    const NORMAL_COUNT_CREDIT = 2;
    const GAME_CODE_WITH_SPLIT_WAGER = ["SMG_summerHoliday"];
    public function syncOriginalGameLogs($token) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);
        if(!empty($transactions)){
            foreach ($transactions as $key => $transaction) {
                $betInfo = $this->queryAmountByTransactionIdAndType($transaction['transaction_id'], "DEBIT");
                $creditInfo = $this->queryAmountByTransactionIdAndType($transaction['transaction_id'], "CREDIT");
                $totalBet = isset($betInfo['total_amount']) ? $betInfo['total_amount'] : 0;
                $totalPayout = isset($creditInfo['total_amount']) ? $creditInfo['total_amount'] : 0;
                // $transaction['start_at'] = $betInfo['start_at'];

                #possible free spin or bonus
                if($transaction['external_unique_id'] != $transaction['transaction_id']){
                    #check if updated completed credit exist
                    $isBetSet = $this->checkTransactionBetIfSet($transaction['transaction_id']);
                    if($isBetSet){
                        #set other related completed round to zero.
                        $totalBet = 0;
                        $totalPayout = 0;
                    }
                }

                $split_wager = false;
                $first_wager_amount = null;
                if(isset($transaction['game_id']) &&  in_array($transaction['game_id'], self::GAME_CODE_WITH_SPLIT_WAGER)){
                    $split_wager = true;
                    $first_wager = $this->queryAmountById($betInfo['min_id']);
                    $first_wager_amount = $first_wager['amount'];
                }

                $extra_info = json_decode($transaction['extra_info'], true);
                $transaction['end_at'] = is_string($extra_info['creationTime']) ?  $extra_info['creationTime'] : date("Y-m-d H:i:s", $extra_info['creationTime']);
                $transaction['start_at'] = isset($betInfo['start_at']) ? $betInfo['start_at'] : $transaction['end_at'];
                $transaction['bet_amount'] = $split_wager ? $first_wager_amount : $totalBet;
                $transaction['result_amount'] = $totalPayout - $totalBet;
                $transaction['flag_of_updated_result'] = self::FLAG_UPDATED;
                $transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($transaction, ['flag_of_updated_result','start_at','end_at'], ['result_amount','bet_amount']);
                $this->CI->original_game_logs_model->updateRowsToOriginal('common_seamless_wallet_transactions', $transaction);
            }
        }
        return array(true, array("total_trans_updated" => count($transactions)));
    }

    public function queryAmountById($id){
        $this->CI->load->model('original_game_logs_model');
        $sqlId="pp.id = ?";
        $sql = <<<EOD
SELECT
amount
FROM common_seamless_wallet_transactions as pp
WHERE
{$sqlId}
EOD;
        $params=[
            $id
        ];

        $this->CI->utils->debug_log('queryAmountById sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result; 
    }

    public function checkTransactionBetIfSet($transactionId){
        $this->CI->db->from('common_seamless_wallet_transactions')
            ->where("game_platform_id", $this->getPlatformCode())
                ->where("transaction_id", $transactionId)
                    ->where("status", self::STATUS_CLOSED)
                        ->where("flag_of_updated_result", self::FLAG_UPDATED)
                            ->where("bet_amount >", 0);;
        $isBetAlreadySet = $this->CI->original_game_logs_model->runExistsResult();
        return $isBetAlreadySet;
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo) {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="mgpluss.end_at >= ? AND mgpluss.end_at <= ? AND mgpluss.game_platform_id = ? AND mgpluss.status = ? and mgpluss.flag_of_updated_result = ?";

        $sql = <<<EOD
SELECT
mgpluss.id,
mgpluss.external_unique_id,
mgpluss.transaction_id,
mgpluss.game_platform_id,
mgpluss.extra_info,
mgpluss.game_id,
mgpluss.amount

FROM common_seamless_wallet_transactions as mgpluss
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::STATUS_CLOSED,
            self::FlAG_NOT_UPDATED
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;  
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

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        
        $transactions = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time);
        $this->processtransactionsFromTrans($transactions);
        return $transactions;
    }

    public function processtransactionsFromTrans(&$transactions){
        if(!empty($transactions)){
            foreach ($transactions as $key => $transaction) {
                if( is_null($transaction['bet_amount']) || is_null($transaction['result_amount']) ){
                    $totalBet = $this->queryAmountByTransactionIdAndType($transaction['transaction_id'], "DEBIT")['total_amount'];
                    $totalPayout = $this->queryAmountByTransactionIdAndType($transaction['transaction_id'], "CREDIT")['total_amount'];
                    $transactions[$key]['bet_amount'] = $totalBet;
                    $transactions[$key]['real_betting_amount'] = $totalBet;
                    $transactions[$key]['payout'] = $totalPayout;
                    $transactions[$key]['result_amount'] = $totalPayout - $totalBet;
                }
            }
        }
        
    }

    public function queryAmountByTransactionIdAndType($transactionId, $type){
        $sql = <<<EOD
SELECT
sum(amount) as total_amount,
min(start_at) as start_at,
min(id) as min_id,
count(*) as count
FROM common_seamless_wallet_transactions
WHERE game_platform_id = ? and transaction_id = ? and transaction_type = ?
EOD;
        $params=[
            $this->getPlatformCode(),
            $transactionId,
            $type
        ];

        $this->CI->utils->debug_log('queryAmountByTransactionIdAndType sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;
    }



    public function getDataFromTrans($dateFrom, $dateTo, $use_bet_time) {

        $sqlTime="mgpluss.end_at >= ? AND mgpluss.end_at <= ? AND mgpluss.game_platform_id = ? AND mgpluss.flag_of_updated_result = ?";
        // $sqlTime="mgpluss.start_at >= ? AND mgpluss.end_at <= ? AND mgpluss.game_platform_id = ? AND mgpluss.status = ? and mgpluss.amount = 0";

        $sql = <<<EOD
SELECT
mgpluss.id as sync_index,
mgpluss.response_result_id,
mgpluss.external_unique_id as external_uniqueid,
mgpluss.md5_sum,

mgpluss.player_id,
mgpluss.game_platform_id,
mgpluss.bet_amount,
mgpluss.bet_amount as real_betting_amount,
mgpluss.result_amount,
mgpluss.game_id as game_code,
mgpluss.game_id as game,
mgpluss.game_id as game_name,
mgpluss.transaction_type,
mgpluss.status,
mgpluss.round_id as round_number,
mgpluss.response_result_id,
mgpluss.extra_info,
mgpluss.start_at,
mgpluss.start_at as bet_at,
mgpluss.end_at,
mgpluss.before_balance,
mgpluss.after_balance,
mgpluss.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as mgpluss
LEFT JOIN game_description as gd ON mgpluss.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::FLAG_UPDATED
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
        
    }

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
            'status' => Game_logs::STATUS_SETTLED,
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
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }
}
/*end of file*/