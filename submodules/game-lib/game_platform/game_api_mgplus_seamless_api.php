<?php
require_once dirname(__FILE__) . '/game_api_common_mgplus.php';

class Game_api_mgplus_seamless_api extends Game_api_common_mgplus {
    public $original_table;
    public $original_transactions_table;
    public $use_monthly_transactions_table;
    public $use_mgplus_seamless_wallet_transactions_table;
    public $enable_merging_rows;
    public $product_id;
    public $currency;
    public $is_auth;
    public $avail_token;
    public $language;
    public $agent_code;
    public $utc_offset;

    const ORIGINAL_TABLE = "mgplus_seamless_idr1_game_logs";
	const PRODUCT_ID = "smg"; # smg is Microgaming
    const CURRENCY = "BRL";
    // const STATUS_CLOSED = 1;
    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;
    const NORMAL_COUNT_CREDIT = 2;
    const ORIGINAL_TRANSACTIONS =  'common_seamless_wallet_transactions';

    const CREDIT = 'CREDIT';
    const DEBIT = 'DEBIT';

	public function getPlatformCode(){
		return MGPLUS_SEAMLESS_API;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function __construct(){
    	$this->product_id = self::PRODUCT_ID;
        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
        $this->currency = $this->getSystemInfo('currency', $this->utils->getDefaultCurrency());
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['DEBIT', 'ROLLBACK_CREDIT']);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->use_mgplus_seamless_wallet_transactions_table = $this->getSystemInfo('use_mgplus_seamless_wallet_transactions_table', false);

        if ($this->use_mgplus_seamless_wallet_transactions_table) {
            $this->original_transactions_table = 'mgplus_seamless_wallet_transactions';
        }
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
    const GAME_CODE_WITH_SPLIT_WAGER = [
        #pick a price feature
        "SMG_summerHoliday",
        #gamble feature 
        "SMG_stormToRiches",
        "SMG_aztecFalls",
        "SMG_burningDesire",
        "SMG_classic243",
        "SMG_dolphinCoast",
        "SMG_frozenDiamonds",
        "SMG_lionsPride",
        "SMG_luckyfirecracker",
        "SMG_tallyHo",
        "SMG_untamedGiantPanda",
        "SMG_voila",
        #x up size feature
        "SMG_footballFinalsXUP",
        "SMG_boltXUP",
        "SMG_chroniclesOfOlympusXUP",
        "SMG_africaXUP",
        "SMG_aquanauts",
        "SMG_fishinBiggerPots",
        "SMG_tigersIce",
    ];
    public function syncOriginalGameLogs($token = false) {
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
                $extra_info = json_decode($transaction['extra_info'], true);
                if(strtoupper($extra_info['txnEventType']) == "GAME"){
                    $betInfo = $this->queryAmountByTransactionIdAndType($transaction['transaction_id'], "DEBIT");
                    $creditInfo = $this->queryAmountByTransactionIdAndType($transaction['transaction_id'], "CREDIT");
                    $totalBet = isset($betInfo['total_amount']) ? $betInfo['total_amount'] : 0;
                    $totalPayout = isset($creditInfo['total_amount']) ? $creditInfo['total_amount'] : 0;

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

                    $transaction['bet_amount'] = $split_wager ? $first_wager_amount : $totalBet;
                    $transaction['result_amount'] = $totalPayout - $totalBet;
                } else {
                    $transaction['bet_amount'] = 0;
                    $transaction['result_amount'] = $transaction['amount'];
                }
                
                $transaction['flag_of_updated_result'] = self::FLAG_UPDATED;
                $transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($transaction, ['flag_of_updated_result','start_at','end_at'], ['result_amount','bet_amount']);
                $this->CI->original_game_logs_model->updateRowsToOriginal($this->getTransactionsTable(), $transaction);
            }
        }
        return array("success" => true, array("total_trans_updated" => count($transactions)));
    }

    public function queryAmountById($id){
        $transaction_table = $this->getTransactionsTable();
        $this->CI->load->model('original_game_logs_model');
        $sqlId="pp.id = ?";
        $sql = <<<EOD
SELECT
amount
FROM {$transaction_table} as pp
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
        $transaction_table = $this->getTransactionsTable();
        $this->CI->db->from($transaction_table)
            ->where("game_platform_id", $this->getPlatformCode())
                ->where("transaction_id", $transactionId)
                    ->where("status", self::STATUS_CLOSED)
                        ->where("flag_of_updated_result", self::FLAG_UPDATED)
                            ->where("bet_amount >", 0);;
        $isBetAlreadySet = $this->CI->original_game_logs_model->runExistsResult();
        return $isBetAlreadySet;
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo) {
        $transaction_table = $this->getTransactionsTable();
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="mgpluss.start_at >= ? AND mgpluss.start_at <= ? AND mgpluss.game_platform_id = ? AND mgpluss.status = ? and mgpluss.flag_of_updated_result = ?";

        $sql = <<<EOD
SELECT
mgpluss.id,
mgpluss.external_unique_id,
mgpluss.transaction_id,
mgpluss.game_platform_id,
mgpluss.game_id,
mgpluss.extra_info,
mgpluss.amount,
mgpluss.start_at,
mgpluss.end_at

FROM {$transaction_table} as mgpluss
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::STATUS_CLOSED,
            self::FLAG_NOT_UPDATED
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;

        if(!$this->enable_merging_rows){
            return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                $enabled_game_logs_unsettle);
        }else{
            return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTransMerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                [$this, 'preprocessOriginalRowForGameLogsMerge'],
                $enabled_game_logs_unsettle);
        }
    }

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time){

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
        $transaction_table = $this->getTransactionsTable();
        $sql = <<<EOD
SELECT
sum(amount) as total_amount,
min(start_at) as start_at,
min(id) as min_id,
count(*) as count
FROM {$transaction_table}
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
        $transaction_table = $this->getTransactionsTable();
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
gd.game_type_id,
gd.english_name as game_english_name

FROM {$transaction_table} as mgpluss
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

    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row) {
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
            // 'status' => Game_logs::STATUS_SETTLED,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => !empty($row['round_number']) ? $row['round_number'] : $row['transaction_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogsMerge(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
        if(isset($row['transaction_type']) && $row['transaction_type'] == "ROLLBACK"){
            $row['status'] = Game_logs::STATUS_REFUND;
        }
        $row['bet_details'] = [];
        $extra_info = json_decode($row['extra_info'], true);
        if(isset($extra_info['txnEventType']) &&  strtoupper($extra_info['txnEventType']) != "GAME"){
            $row['bet_details'] = isset($extra_info['contentCode']) ? $extra_info['contentCode'] : [];
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

    public function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($gameUsername);
        return array('success' => $success);
    }
    public function unblockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($gameUsername);
        return array('success' => $success);
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL("create table {$tableName} like {$this->original_transactions_table}");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

	public function queryBetDetailLink($playerName, $betid = null, $extra = null){
        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerName, $betid, $extra);
        }
        
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->is_auth = !$this->avail_token ? false : true;

		if(isset($extra['language']) && !empty($extra['language'])){
            $language = $this->getLauncherLanguage($extra['language']);
        }else{
            $language = $this->getLauncherLanguage($this->language);
        }

		$context = array(
			'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'betId' => $betid,
        );

		$params = array(
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername,
			'betId' => $betid,
			'langCode' => $language,
			'utcOffset' => $this->utc_offset,
		);

		//$this->method = self::POST;

		return $this->callApi(self::API_queryBetDetailLink, $params, $context);

	}

	public function processResultForQueryBetDetailLink($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$betId = @$this->getVariableFromContext($params, 'betId');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array('url'=>'');
		if($success){
			//$resultArr = reset($resultArr);
			$result['url'] = isset($resultArr['url'])?$resultArr['url']:'';
		}

		return array($success, $result);
	}

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getTransactionsTable();

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
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc, t.id asc;
EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
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

        if (isset($row['real_betting_amount'])) {
            $bet_details['bet_amount'] = $row['real_betting_amount'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }
        return $bet_details;
     }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = true){
        $transactions_table = $this->getTransactionsTable();
        $this->CI->load->model('original_game_logs_model');

        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
transaction.id as sync_index,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.md5_sum,
transaction.player_id,
transaction.game_platform_id,
transaction.amount,
transaction.transaction_type,
transaction.status,
transaction.transaction_id as round_id,
transaction.extra_info,
transaction.start_at,
transaction.start_at as bet_at,
transaction.end_at,
transaction.before_balance,
transaction.after_balance,
transaction.transaction_id,
transaction.game_id AS game_code,
game_description.id as game_description_id,
game_description.game_type_id,
game_description.english_name as game

FROM {$transactions_table} as transaction
LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND {$sqlTime}
EOD;

    $params=[
        $this->getPlatformCode(),
        $this->getPlatformCode(),
        $dateFrom,
        $dateTo,
    ];

    $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    return $result;

    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;

        // if($row['transaction_type'] == self::DEBIT){
        //     $result_amount = 0 - $row['amount'];
        // }

        // if($row['transaction_type'] == self::CREDIT){
        //     $result_amount = $row['amount'];
        // }

        $data = [
            'game_info' => [
                'game_type_id' => !empty($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => !empty($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'game_type' => !empty($row['game_type']) ? $row['game_type'] : null,
                'game' => !empty($row['game']) ? $row['game'] : null,
            ],
            'player_info' => [
                'player_id' => !empty($row['player_id']) ? $row['player_id'] : null,
                'player_username' => !empty($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => $result_amount,
                'bet_for_cashback' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'win_amount' => !empty($row['win_amount']) ? $row['win_amount'] : 0,
                'loss_amount' => !empty($row['loss_amount']) ? $row['loss_amount'] : 0,
                'after_balance' => !empty($row['after_balance']) ? $row['after_balance'] : 0,
            ],
            'date_info' => [
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => !empty($row['status']) ? $row['status'] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => !empty($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => !empty($row['round_id']) ? $row['round_id'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => !empty($row['transaction_type']) ? $row['transaction_type'] : null,
            ],
            'bet_details' => 'N/A',
            'extra' => [],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;

    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row){

		if (empty($row['game_type_id'])) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
            $row['game_description_id'] = $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        #get round status by querying original_transactions_table
        $getRoundStatus = $this->getRoundStatus($row['transaction_id']);
        $row['status'] = $getRoundStatus == Game_logs::STATUS_SETTLED ? $getRoundStatus : Game_logs::STATUS_UNSETTLED;

        #set bet and win amout
        if(isset($row['transaction_type']) && $row['transaction_type'] == "DEBIT"){
            $row['bet_amount'] = $row['amount'];
            $row['result_amount'] = (-1) * $row['amount'];
        }elseif(isset($row['transaction_type']) && $row['transaction_type'] == "CREDIT"){
            $row['result_amount'] = $row['amount'];
            $row['win_amout'] = $row['amount'];
        }
	}


    public function getRoundStatus($transactionId){
        $transactions_table = $this->getTransactionsTable();

$sql = <<<EOD
SELECT
    transaction.status
FROM {$transactions_table} AS transaction
WHERE transaction.game_platform_id = ? and transaction.status = ? and transaction.transaction_id = ?
EOD;

    $params=[
        $this->getPlatformCode(),
        self::FLAG_UPDATED,
        $transactionId,
    ];

    $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

    if(!empty($result)){
        return self::FLAG_UPDATED;
    }
    return self::FLAG_NOT_UPDATED;

    }
}
/*end of file*/