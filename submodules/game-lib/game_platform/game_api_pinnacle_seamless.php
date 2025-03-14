<?php
require_once dirname(__FILE__) . '/game_api_pinnacle.php';
/*
*
*/

class Game_api_pinnacle_seamless extends game_api_pinnacle {
    public $enable_merging_rows;
    public $remote_wallet_error_code;

    const ORIGINAL_GAME_LOGS = '';
    const ORIGINAL_TRANSACTIONS = 'pinnacle_seamless_wallet_transactions';

	const WAGER_ACTION_BETTED = 'BETTED';
	const WAGER_ACTION_ACCEPTED = 'ACCEPTED';
	const WAGER_ACTION_SETTLED = 'SETTLED';
	const WAGER_ACTION_REJECTED = 'REJECTED';
	const WAGER_ACTION_CANCELLED = 'CANCELLED';
	const WAGER_ACTION_ROLLBACKED = 'ROLLBACKED';	
	const WAGER_ACTION_UNSETTLED = 'UNSETTLED';


	const WAGER_STATUS_OPEN = 'OPEN';
	const WAGER_STATUS_ACCEPTED = 'ACCEPTED';
	const WAGER_STATUS_SETTLED = 'SETTLED';
    const WAGER_STATUS_REJECTED = 'REJECTED';
    const WAGER_STATUS_CANCELLED = 'CANCELLED';
    const WAGER_STATUS_ROLLBACKED = 'ROLLBACKED';
    const WAGER_STATUS_UNSETTLED = 'UNSETTLED';

    const WAGER_ACTION_STATUS = [
        self::WAGER_ACTION_BETTED => self::WAGER_STATUS_OPEN,
        self::WAGER_ACTION_ACCEPTED => self::WAGER_STATUS_ACCEPTED,
        self::WAGER_ACTION_SETTLED => self::WAGER_STATUS_SETTLED,
        self::WAGER_ACTION_REJECTED => self::WAGER_STATUS_REJECTED,
        self::WAGER_ACTION_CANCELLED => self::WAGER_STATUS_CANCELLED,
        self::WAGER_ACTION_ROLLBACKED => self::WAGER_STATUS_ROLLBACKED,
        self::WAGER_ACTION_UNSETTLED => self::WAGER_STATUS_UNSETTLED,
    ];
    
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','game_code', 'status', 'end_at', 'updated_at', 'settled_at'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance'];
    
    
    public function getPlatformCode(){
        return PINNACLE_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function __construct(){
        parent::__construct();  
        $this->api_url = $this->getSystemInfo('url');
		$this->agentCode = $this->getSystemInfo('agentCode');
		$this->agentKey = $this->getSystemInfo('agentKey');
		$this->secretKey = $this->getSystemInfo('secretKey');
		//$this->aesKey = $this->getSystemInfo('rlpc3VsNcxXmrBXZ1asde345f');
        $this->iv = $this->getSystemInfo('iv', 'RandomInitVector');

        $this->use_loginv2_on_launching = $this->getSystemInfo('use_loginv2_on_launching', true);

        $this->force_valid_signature = $this->getSystemInfo('force_valid_signature', false);

        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;

        $this->enabled_game_logs_unsettle = $this->getSystemInfo('enabled_game_logs_unsettle', true);

        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);

        $this->force_check_prev_table_for_sync = $this->getSystemInfo('force_check_prev_table_for_sync', false);

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        
        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);

        $this->remote_wallet_error_code = null;
        
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function queryPlayerBalance($playerName){
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (queryPlayerBalance)");

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function queryTransaction($transactionId, $extra=null) {
        return $this->returnUnimplemented();
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {        
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $this->enabled_game_logs_unsettle);
    }

    protected function processOdds($data){
        return isset($data['settle_data']['WagerInfo']['Odds'])?$data['settle_data']['WagerInfo']['Odds']:null;
    }

    protected function processBetDetails($data){
        $data = isset($data['wager_data']['WagerInfo']) ? $data['wager_data']['WagerInfo'] : [];
        if(isset($data['settle_data']['WagerInfo'])){
           $data = $data['settle_data']['WagerInfo'];
        }
        return $data;
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round_id'],
            'odds' =>  $this->processOdds($row),
        ];

        if(!isset($row['md5_sum']) || empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $after_balance = $this->enable_merging_rows ? null : $row['after_balance'];
        $bet_details = $this->preprocessBetDetails($row, null, false);

        if (isset($bet_details['odds'])) {
            $extra['odds'] = $bet_details['odds'];
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['game_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance
            ],
            'date_info' => [
                'start_at' => $this->gameTimeToServerTime($row['bet_at']),
                'end_at' => $this->gameTimeToServerTime($row['end_at']),
                'bet_at' => $this->gameTimeToServerTime($row['bet_at']),
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            //'bet_details' => $betDetails,
            // 'bet_details' => $this->processBetDetails($row),
            'bet_details' => $bet_details,
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        
        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        

        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        // if($this->force_check_prev_table_for_sync){
        //     $checkOtherTable = true;
        // }

        if(($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table) || $checkOtherTable){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("PINNACLE SEAMLESS: (queryOriginalGameLogs) getting prev month data", 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);
        if($this->enable_merging_rows){
            $this->processGameRecordsFromTrans($gameRecords);
        }
        return $gameRecords;
    }

    public function processGameRecordsFromTrans(&$gameRecords){
        $temp_game_records = [];
        $currentTableName = $this->getTransactionsTable();
        $checkOtherTable = $this->checkOtherTransactionTable();
        $prevTranstable = $this->getTransactionsPreviousTable();
        $this->CI->load->model(array('pinnacle_seamless_transactions'));
        if(!empty($gameRecords)){
            foreach($gameRecords as $rowData){
                $temp = [];
                $temp = $rowData;

                //get wager info
                
                $prevRoundData = [];
                $check_bet_params = ['round_id'=>strval($rowData['round_id'])];
                $currentRoundData = $this->CI->pinnacle_seamless_transactions->getRoundData($currentTableName, $check_bet_params);
        
                if(($this->force_check_prev_table_for_sync&&$this->use_monthly_transactions_table) || $checkOtherTable){                    
                    $prevRoundData = $this->CI->pinnacle_seamless_transactions->getRoundData($prevTranstable, $check_bet_params);
                }
        
                $roundData = array_merge($currentRoundData, $prevRoundData);    
                
                $temp['start_at'] = $rowData['start_at'];
                $temp['end_at'] = $rowData['settled_at'];

                if(empty($rowData['end_at']) && $rowData['settled_at']=='0000-00-00 00:00:00'){
                    $temp['end_at'] = $rowData['start_at'];
                }                     

                $temp['settle_data'] = [];
                $temp['wager_data'] = [];
                $totalCreditAmount = 0;
                $totalDebitAmount = 0;
                $unsettledAmount = 0;
                foreach($roundData as $roundDataRow){
                    $roundDataRowExtra = json_decode($roundDataRow['extra_info'], true);              
                    if(isset($roundDataRowExtra['Actions'])){
                        foreach($roundDataRowExtra['Actions'] as $action){                                       
                            if(isset($action['Name']) && $action['Name']==self::WAGER_ACTION_SETTLED){                                                        
                                $temp['settle_data'] = $action;
                            } 
                            $temp['wager_data'] = $action;
                        }
                    }                    

                    if($roundDataRow['wallet_adjustment_mode']=='debit'){
                        $totalDebitAmount += $roundDataRow['amount'];
                    }elseif($roundDataRow['wallet_adjustment_mode']=='credit'){
                        $totalCreditAmount += $roundDataRow['amount'];
                    }

                    if($roundDataRow['transaction_type']==self::WAGER_ACTION_UNSETTLED){
                        $unsettledAmount += $roundDataRow['amount'];
                    }

                    if ($roundDataRow['transaction_type'] == self::WAGER_ACTION_CANCELLED) {
                        $this->CI->pinnacle_seamless_transactions->setTransactionStatus($rowData['external_uniqueid'], self::WAGER_ACTION_CANCELLED, $rowData['settled_at']);
                    }
                }

                if($unsettledAmount>0){
                    //$this->CI->utils->error_log("PINNACLE SEAMLESS: bermar", 'unsettledAmount', $unsettledAmount, 'rowData', $rowData);
                    $totalDebitAmount = $totalDebitAmount-$unsettledAmount;
                    $totalCreditAmount = $totalCreditAmount-$unsettledAmount;
                }

                $temp['bet_amount'] = $totalDebitAmount;
                $temp['result_amount'] = $totalCreditAmount-$totalDebitAmount;

                $temp_game_records[] = $temp;
            }//end foreach gameinstance id
        }

        $gameRecords = $temp_game_records;
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if(!$this->enable_merging_rows){
            $original_transactions_table = $this->getTransactionsTable();
            
            $checkOtherTable = $this->checkOtherTransactionTable();

            if($this->force_check_prev_table_for_sync){
                $checkOtherTable = true;
            }
            $currentTableData = $this->queryBetStatusByRoundIds($row['round_id'],$original_transactions_table);
            $prevTableData = [];
            if(($this->force_check_other_transaction_table && $this->use_monthly_transactions_table) || $checkOtherTable){            
                $prevTable = $this->getTransactionsPreviousTable();             
                $this->CI->utils->debug_log("PINNACLE SEAMLESS: (queryBetStatusByRoundIds) getting prev month data", 'prevTable', $prevTable);
                $prevTableData = $this->queryBetStatusByRoundIds($row['round_id'],$prevTable);                              
            }

            $bet_status = array_merge($currentTableData, $prevTableData);
            
            $row['status'] = isset($bet_status[0]['status']) ? $bet_status[0]['status'] : $row['status'];
            if($row['transaction_type'] == 'BETTED'){
                $win_amount = 0;
                $row['bet_amount'] = $row['amount']; #amount as bet_amount
                $row['result_amount'] = $win_amount - $row['bet_amount'];
            }else{
                $win_amount = $row['amount']; #amount as win_amount
                $row['bet_amount'] = 0;
                $row['real_bet_amount'] = 0;
                $row['result_amount'] = $win_amount - $row['bet_amount'];
                if($row['transaction_type'] == 'SETTLED'){
                    #get ProfitAndLoss response params from extra info as result_amount
                    $extra_info = json_decode($row['extra_info'],true);
                    $wagerInfo  = isset($extra_info['Actions'][0]['WagerInfo']) ? $extra_info['Actions'][0]['WagerInfo'] : 'no data';
                    $row['result_amount'] = $wagerInfo['ProfitAndLoss'] + $wagerInfo['Stake'];
                }
            }
            
        }
    
        $status = Game_logs::STATUS_PENDING;
        if($row['status']==self::WAGER_STATUS_OPEN) {
            $status = Game_logs::STATUS_PENDING;
        }elseif($row['status']==self::WAGER_ACTION_ACCEPTED) {
            $status = Game_logs::STATUS_ACCEPTED;
        }elseif($row['status']==self::WAGER_STATUS_SETTLED) {
            $status = Game_logs::STATUS_SETTLED;//settled
        }elseif($row['status']==self::WAGER_STATUS_REJECTED) {
            $status = Game_logs::STATUS_REJECTED;
        }elseif($row['status']==self::WAGER_STATUS_CANCELLED) {
            $status = Game_logs::STATUS_CANCELLED;
        }elseif($row['status']==self::WAGER_STATUS_ROLLBACKED) {
            $status = Game_logs::STATUS_CANCELLED;
        }elseif($row['status']==self::WAGER_STATUS_UNSETTLED) {
            $status = Game_logs::STATUS_SETTLED; // unsettled means change status to lose or win
        }
        $row['status'] = $status;
    }

    protected function getGameDescriptionInfo($row, $unknownGame) {
        $gameName = $row['original_game_id'];

        $game_name = !empty($row['game_name'])?$row['game_name']:$row['ogl_game_name'];

		$game_description_id = null;
		$external_game_id = $gameName;
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function queryBetStatusByRoundIds($roundId, $table_name){
        if(empty($roundId)){
            $roundId = '0';
        }
        $where="t.round_id IN ('$roundId')";
        $params = [];

        return $this->queryBetStatusFromTrans($where, $params, $table_name);        
    }

    public function queryBetStatusFromTrans($where, $params = [], $table_name = null){ 
        if($where){
            $where = ' AND ' . $where;
        }          

$sql = <<<EOD
SELECT t.status as status,
t.round_id

FROM {$table_name} as t

WHERE t.transaction_type = 'BETTED'
{$where}
EOD;

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`transaction_date` >= ? AND `original`.`transaction_date` <= ?';
        }
        $this->CI->utils->debug_log('PINNACLE SEAMLESS sqlTime', $sqlTime, 'table', $table);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.transaction_date', 'original.status', 'original.end_at', 'original.updated_at', 'original.settled_at'));

        $transaction_type = "AND original.transaction_type='BETTED'";
        if(!$this->enable_merging_rows){
            $transaction_type = "AND original.transaction_type in ('BETTED','ACCEPTED','SETTLED')";
        }
        //result amount = win - bet
        $sql = <<<EOD
SELECT
	original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.start_at as start_at,
    original.end_at as end_at,
    original.transaction_date as bet_at,
    original.updated_at as updated_at,
    original.player_id as player_id,
    original.settled_at as settled_at,
    original.round_id as round_id,
    original.wager_id as wager_id,
    original.game_id as original_game_id,
    original.game_id as game_code,
    original.game_id as ogl_game_name,
    original.bet_type as bet_type,
    original.transaction_type as transaction_type,
    original.status as status,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    original.amount,
    original.amount as bet_amount,
    original.wallet_adjustment_mode as wallet_adjustment_mode,
    IF(original.wallet_adjustment_mode='debit',original.amount,0) debit_amount,
    IF(original.wallet_adjustment_mode='credit',original.amount,0) credit_amount,
    original.extra_info,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as gd_game_code,    
    gd.game_name as game_name,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id,
    game_provider_auth.external_account_id as game_username,
    gt.game_type_code as game_type
FROM {$table} as original
JOIN game_provider_auth ON game_provider_auth.player_id = original.player_id AND game_provider_auth.game_provider_id=?
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE
{$sqlTime}
{$transaction_type};
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('PINNACLE SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }




    ############### SEAMLESS SERVICE API CODES ###################

	public function debitCreditAmountToWallet($params, $request, &$previousBalance, &$afterBalance, $mode, $remoteActionType = 'bet'){        
        if(empty($this->request)){
            $this->request = $request;
        }

		$this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);
        $this->CI->load->model(array('pinnacle_seamless_transactions'));
        $this->CI->pinnacle_seamless_transactions->tableName = $this->getTransactionsTable();
        $currentTableName = $this->getTransactionsTable();
		//initialize params
		$player_id			= $params['player_id'];		

		//initialize response
		$success = false;	
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];
        $trans_type = $params['transaction_type'];
        $prevTranstable = $this->getTransactionsPreviousTable();
        $betExist = null;
        $allowedNegativeBalance = false;
        
		$uniqueId           = $params['external_uniqueid'];
        $checkOtherTable = $this->checkOtherTransactionTable();
        if($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table){
            $checkOtherTable = true;
        }

        //get and process balance
        $get_balance = $this->getPlayerBalance($player_id);
        if($get_balance!==false){
            $afterBalance = $previousBalance = $get_balance;
        }else{				
            $this->utils->error_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
            return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
        }

        $afterBalance = $previousBalance;

        // get all related transaction under the round
        $check_bet_params = [
            'round_id'=>strval($params['round_id']),                 
            'player_id'=>$player_id
        ];

        if(in_array($params['transaction_type'], [self::WAGER_ACTION_SETTLED, self::WAGER_ACTION_CANCELLED, self::WAGER_ACTION_ROLLBACKED, self::WAGER_ACTION_ACCEPTED])){
            $related_bet_params = [
                'round_id'=>strval($params['round_id']),                 
                'player_id'=>$player_id,
                'transaction_type'=>self::WAGER_ACTION_BETTED
            ];
            $limit=1; #single row
            $relatedBetData = $this->CI->pinnacle_seamless_transactions->getRoundData($currentTableName, $related_bet_params, $limit);
            $this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE @debitCreditAmountToWallet relatedBetData", $relatedBetData);
            $this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE @debitCreditAmountToWallet relatedBetData last_query", $this->CI->db->last_query());
            $relatedBetUniqueId = isset($relatedBetData['external_uniqueid']) ? $relatedBetData['external_uniqueid'] : null;
            $this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE @debitCreditAmountToWallet relatedBetUniqueId", $relatedBetUniqueId);

            $related_bet_uniqueid_of_seamless_service = 'game-'.$this->getPlatformCode().'-'.$relatedBetUniqueId;
            $this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE @debitCreditAmountToWallet related_bet_uniqueid_of_seamless_service", $related_bet_uniqueid_of_seamless_service);
            
            if(method_exists($this->CI->utils, 'isEnabledRemoteWalletClient')){
                if($this->CI->utils->isEnabledRemoteWalletClient()){
                    $this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE @debitCreditAmountToWallet isEnabledRemoteWalletClient", true);
                    if (method_exists($this->CI->wallet_model, 'setRelatedActionOfSeamlessService')) {
                        $this->CI->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                    }
                    if (method_exists($this->CI->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                        $this->CI->wallet_model->setRelatedUniqueidOfSeamlessService($related_bet_uniqueid_of_seamless_service);
                    }
                }
            }
        }
       
        if(!isset($params['WagerMasterId']) || empty($params['WagerMasterId'])){
            $check_wager_id_params = [
                'wager_id' => strval($params['wager_id']),
                'player_id'=>$player_id
            ];
    
            $currentWagerData = $this->CI->pinnacle_seamless_transactions->getSingleRoundData($currentTableName, $check_wager_id_params);
            if(isset($currentWagerData->wager_master_id) && isset($currentWagerData->bet_type)){
                $check_bet_params['round_id'] = $currentWagerData->wager_master_id;
                $params['round_id'] = $currentWagerData->wager_master_id;
                $params['wager_master_id'] = $currentWagerData->wager_master_id;
                $params['bet_type'] = isset($params['bet_type']) ? $params['bet_type'] : $currentWagerData->bet_type;
            }
        }
     
        $prevRoundData = [];
        $currentRoundData = $this->CI->pinnacle_seamless_transactions->getRoundData($currentTableName, $check_bet_params);

        if($checkOtherTable){                    
            $prevRoundData = $this->CI->pinnacle_seamless_transactions->getRoundData($prevTranstable, $check_bet_params);
        }

        $roundData = array_merge($currentRoundData, $prevRoundData);
        $additionalResponse['isAlreadyExistsData'] = [];
        $additionalResponse['unsettledData'] = [];
        $additionalResponse['rollbackedData'] = [];
        $additionalResponse['cancelledData'] = [];
        $additionalResponse['rejectedData'] = [];
        $additionalResponse['settledData'] = [];
        $additionalResponse['acceptData'] = [];
        $additionalResponse['betData'] = [];
        $additionalResponse['toRollbackData'] = [];
        foreach($roundData as $rowData){
            if($rowData['transaction_type']==self::WAGER_ACTION_BETTED){
                $additionalResponse['betData'] = $betExist = $rowData;
                $additionalResponse['betExist']=true;     
            }

            if($rowData['transaction_type']==self::WAGER_ACTION_ACCEPTED){
                $additionalResponse['acceptData'] = $rowData;
            }

            if($rowData['transaction_type']==self::WAGER_ACTION_SETTLED){
                $additionalResponse['settledData'] = $rowData;
            }

            if($rowData['transaction_type']==self::WAGER_ACTION_REJECTED){
                $additionalResponse['rejectedData'] = $rowData;
            }

            if($rowData['transaction_type']==self::WAGER_ACTION_CANCELLED){
                $additionalResponse['cancelledData'] = $rowData;
            }

            if($rowData['transaction_type']==self::WAGER_ACTION_ROLLBACKED){
                $additionalResponse['rollbackedData'] = $rowData;
            }

            if($rowData['transaction_type']==self::WAGER_ACTION_UNSETTLED){
                $additionalResponse['unsettledData'] = $rowData;   
            }

            if($params['external_uniqueid']==$rowData['external_uniqueid']){
                $isAlreadyExists = true;
                $additionalResponse['isAlreadyExistsData'] = $rowData;
            }

            if($params['transaction_type']==self::WAGER_ACTION_ROLLBACKED && 
                $rowData['transaction_id'] = $params['refer_transaction_id']){
                    $additionalResponse['toRollbackData'] = $rowData;
                }
            }
        
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) roundData", 
        'roundData', $roundData, 
        'params',$params);
        
        //check if already exist        
        if($isAlreadyExists){
            $this->utils->error_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already in current transactions", $isAlreadyExists, 
            'params', $params,
            'uniqueId', $uniqueId,
            'currentTableName', $currentTableName);
            $isAlreadyExists = true;
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
        }
        //check if cancelled already recorded in the DB
        /*if(!empty($cancelData)&&$params['transaction_type']<>self::WAGER_ACTION_CANCELLED){
            $this->utils->error_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) transactions already has cancel record", $isAlreadyExists, 
            'params', $params,
            'uniqueId', $uniqueId,
            'currentTableName', $currentTableName);            
            $additionalResponse['isCancelledAlready']=true;				            
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
        }*/

        //if cancel and bet not exist
        if(empty($betExist) && 
        ($params['transaction_type']==self::WAGER_ACTION_ACCEPTED || $params['transaction_type']==self::WAGER_ACTION_CANCELLED ||
        $params['transaction_type']==self::WAGER_ACTION_SETTLED || $params['transaction_type']==self::WAGER_ACTION_REJECTED ||
        $params['transaction_type']==self::WAGER_ACTION_ROLLBACKED || $params['transaction_type']==self::WAGER_ACTION_UNSETTLED)
        ){            
            $additionalResponse['betExist']=false;            
            
            $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist FOR CANCELWAGER SET TRUE", 
            'betExist', $betExist, 
            'params',$params, 
            'check_bet_params', $check_bet_params,
            'prevTranstable', $prevTranstable);
            
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
        }

        if($params['transaction_type']==self::WAGER_ACTION_ACCEPTED || $params['transaction_type']==self::WAGER_ACTION_CANCELLED ||
        $params['transaction_type']==self::WAGER_ACTION_SETTLED || $params['transaction_type']==self::WAGER_ACTION_REJECTED ||
        $params['transaction_type']==self::WAGER_ACTION_UNSETTLED){ 

            $flagStatus = $this->getWagerStatus($params['transaction_type']);
            $flagResp = $this->CI->pinnacle_seamless_transactions->setTransactionStatus($betExist['external_uniqueid'], $flagStatus, $params['settled_at']);

            if($checkOtherTable){                    
                $flagResp = $this->CI->pinnacle_seamless_transactions->setTransactionStatus($betExist['external_uniqueid'], $flagStatus, $params['settled_at'], $prevTranstable);                
            }
        }

        /**
         * In case of any error occurred in the Sportsbook Platform and the bet was not accepted. 
         * Operator need to base on TransactionId and WagerId to rollback and refund Amount that Sportsbook Platform sent 
         * in betted action. If the Transaction and Wager are not found in Operator side, no adjustment is to be made.
         */
        if($params['transaction_type']==self::WAGER_ACTION_ROLLBACKED){	
            if(empty($additionalResponse['toRollbackData'])){
                $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) NOTHING ROLLBACK", 'request', $this->request, 'additionalResponse', 
                $additionalResponse);
                $afterBalance = $previousBalance;
                $additionalResponse['betExist']=false;     
                return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
            }
            $flagStatus = $this->getWagerStatus($params['transaction_type']);
            $flagResp = $this->CI->pinnacle_seamless_transactions->setTransactionStatus($additionalResponse['toRollbackData']['external_uniqueid'], $flagStatus, $params['settled_at']);
            if($checkOtherTable){                    
                $flagResp = $this->CI->pinnacle_seamless_transactions->setTransactionStatus($betExist['external_uniqueid'], $flagStatus, $params['settled_at'], $prevTranstable);                
            }
        }

		$amount = $this->gameAmountToDBTruncateNumber($params['amount']);

		if($amount<>0){
            //compute balance
            $afterBalance = $previousBalance = $get_balance;
            if($mode=='debit'){
                $afterBalance = $afterBalance - $amount;
            }else{
                $afterBalance = $afterBalance + $amount;
            }

            if($mode=='debit' && $previousBalance < $amount ){
                if ($params['transaction_type'] == self::WAGER_ACTION_UNSETTLED) {
                    $allowedNegativeBalance = true;
                } else {
                    $afterBalance = $previousBalance;
                    $insufficientBalance = true;
                    $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);				
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
                }
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);

			if($isAdded===false){
				$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
			}else{
				$isTransactionAdded = true;
			}	

            //add external unique id and game id
			$success = $this->transferGameWallet($player_id, $this->getPlatformCode(), $mode, $amount, $uniqueId, $params['game_id'], $allowedNegativeBalance, $remoteActionType);

            $this->saveRemoteWalletError($params);

			if(!$success){
				$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
			}

		}else{
            $afterBalance = $previousBalance = $get_balance;
            $success = true;

            //insert transaction
            $isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
            if($isAdded===false){
                $this->utils->error_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
            }

            
            if($this->CI->utils->compareResultFloat($amount, '=', 0)){
                if (method_exists($this->CI->wallet_model, 'incRemoteWallet')) {
                    $this->CI->wallet_model->incRemoteWallet($player_id, $amount, $this->getPlatformCode());
                }
            }
            //rollback amount because it already been processed
            if($isAdded==0){
                $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
                $isAlreadyExists = true;					
                $afterBalance = $previousBalance;
                return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance);
            }else{
                $isTransactionAdded = true;
            }

		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded,
                        $allowedNegativeBalance);
	}

	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount, $external_uniqueid, $external_game_id, $allowedNegativeBalance = false, $remoteActionType = 'bet'){
        $uniqueid_of_seamless_service = $game_platform_id.'-'.$external_uniqueid;
        $this->CI->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id );
        $this->CI->wallet_model->setGameProviderActionType($remoteActionType);

		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
            if ($allowedNegativeBalance) {
                $success = $this->CI->wallet_model->decSubWalletAllowNegative($player_id, $game_platform_id, $amount);
            } else {
                $success = $this->CI->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);
            }	
		}elseif($mode=='credit'){
            if($this->CI->utils->compareResultFloat($amount, '=', 0)){
                $this->CI->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
            }else{
                $success = $this->CI->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
            }
		}

        $this->remote_wallet_error_code = $this->remoteWalletErrorCode();

        if($this->remote_wallet_error_code == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
            $success = true;
        }

		return $success;
	}

	public function getPlayerBalance($player_id){			
		$get_bal_req = $this->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}	
	}

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("PINNACLE SEAMLESS: (queryPlayerBalance)");

        $useReadonly = true;
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode(), $useReadonly);

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);		
		if($trans_record['transaction_type']=='payout' && $this->flag_bet_transaction_settled){
			//mark bet as settled
			$this->CI->pinnacle_seamless_transactions->flagBetTransactionSettled($trans_record);
		}	

        $tableName = $this->getTransactionsTable();
        $this->CI->pinnacle_seamless_transactions->setTableName($tableName);        
		return $this->CI->pinnacle_seamless_transactions->insertIgnoreRow($trans_record);        		
	}

	public function makeTransactionRecord($raw_data){
		$data = [];		
        $data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;//bigint
        $data['timestamp'] 			= isset($raw_data['timestamp'])?$raw_data['timestamp']:null;//string
        $data['transaction_date'] 	= isset($raw_data['transaction_date'])?$raw_data['transaction_date']:null;//datetime
        $data['transaction_type'] 	= isset($raw_data['transaction_date'])?$raw_data['transaction_type']:null;//string
        $data['wager_master_id'] 	= isset($raw_data['wager_master_id'])?$raw_data['wager_master_id']:null;//string
        $data['start_at'] 	= isset($raw_data['start_at'])?$raw_data['start_at']:null;//datetime
        $data['end_at'] 	= isset($raw_data['end_at'])?$raw_data['end_at']:null;//datetime
        $data['settled_at'] 	= isset($raw_data['settled_at'])?$raw_data['settled_at']:null;//datetime
        $data['amount'] 			= isset($raw_data['amount'])?$this->gameAmountToDBTruncateNumber($raw_data['amount']):0;//double
        $data['orig_amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;//double
        $data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;//double
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;	//double
        $data['extra_info'] 		    = isset($raw_data['extra_info'])?$raw_data['extra_info']:json_encode([]);//json
        if(is_array($data['extra_info'])){
            $data['extra_info'] = json_encode($data['extra_info']);//json
        }
        $data['wager_id'] 			= isset($raw_data['wager_id'])?$raw_data['wager_id']:null;//string
        $data['game_id'] 			= isset($raw_data['game_id'])?$raw_data['game_id']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string		
        $data['wager_master_id'] 			= isset($raw_data['wager_master_id'])?$raw_data['wager_master_id']:null;//string		
        $data['transaction_id'] 			= isset($raw_data['transaction_id'])?$raw_data['transaction_id']:null;//string			
        $data['to_refund_transaction_id'] 			= isset($raw_data['refer_transaction_id'])?$raw_data['refer_transaction_id']:null;//string		

        
        $data['bet_type'] 		= isset($raw_data['bet_type'])?$raw_data['bet_type']:null;//string
        $data['wallet_adjustment_mode'] 		= isset($raw_data['wallet_adjustment_mode'])?$raw_data['wallet_adjustment_mode']:null;//string
        $data['status'] 			= isset($raw_data['status'])?$raw_data['status']:null;//string
        $data['external_uniqueid'] 	=  isset($raw_data['external_uniqueid'])?$raw_data['external_uniqueid']:null;//string
        $data['game_platform_id'] 	= $this->getPlatformCode();	//int
        $data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;//bigint	

        $data['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);
		return $data;
	}

    function str_encryptaesgcm($plaintext, $password, $encoding = null) {
        if ($plaintext != null && $password != null) {
            $keysalt = openssl_random_pseudo_bytes(16);
            $key = hash_pbkdf2("sha512", $password, $keysalt, 20000, 32, true);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-gcm"));
            $tag = "";
            $encryptedstring = openssl_encrypt($plaintext, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag, "", 16);
            return $encoding == "hex" ? bin2hex($keysalt.$iv.$encryptedstring.$tag) : ($encoding == "base64" ? base64_encode($keysalt.$iv.$encryptedstring.$tag) : $keysalt.$iv.$encryptedstring.$tag);
        }
    }
    
    function str_decryptaesgcm($encryptedstring, $password, $encoding = null) {
        if ($encryptedstring != null && $password != null) {
            $encryptedstring = $encoding == "hex" ? hex2bin($encryptedstring) : ($encoding == "base64" ? base64_decode($encryptedstring) : $encryptedstring);
            $keysalt = substr($encryptedstring, 0, 16);
            $key = hash_pbkdf2("sha512", $password, $keysalt, 20000, 32, true);
            $ivlength = openssl_cipher_iv_length("aes-256-gcm");
            $iv = substr($encryptedstring, 16, $ivlength);
            $tag = substr($encryptedstring, -16);
            return openssl_decrypt(substr($encryptedstring, 16 + $ivlength, -16), "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag);
        }
    }

    public function getTransactionsTable($monthStr = null){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        if(empty($monthStr)){
            $date=new DateTime();
            $monthStr=$date->format('Ym');
        }
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

		$tableName='pinnacle_seamless_wallet_transactions_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{

                $this->CI->load->dbforge();

                $fields = array(
                    'id' => [
                        'type' => 'BIGINT',
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'game_platform_id' => [
                        'type' => 'INT',
                        'constraint' => '6'
                    ],
                    'amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],                    
                    'before_balance' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'after_balance' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'player_id' => [
                        'type' => 'INT',
                        'constraint' => '12',
                        'null' => true
                    ],                    
                    'game_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true
                    ],                 
                    'timestamp' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true
                    ],
                    'transaction_date' => [
                        'type' => 'DATETIME',
                        'null' => true
                    ],
                    'start_at' => [
                        'type' => 'DATETIME',
                        'null' => true
                    ],
                    'end_at' => [
                        'type' => 'DATETIME',
                        'null' => true
                    ],
                    'settled_at' => [
                        'type' => 'DATETIME',
                        'null' => true
                    ],
                    'extra_info' => [
                        'type' => 'JSON',
                        'null' => true
                    ],
                    'transaction_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'bet_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'wager_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'wager_master_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'round_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'transaction_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'to_refund_transaction_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],

                    
                    'wallet_adjustment_mode' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'status' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'response_result_id' => [
                        'type' => 'INT',
                        'constraint' => '11',
                        'null' => true
                    ],
                    'external_uniqueid' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true
                    ],
                    'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                        'null' => false,
                    ],
                    'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                        'null' => false,
                    ],
                    'cost' => [
                        'type' => 'INT',
                        'constraint' => '5',
                        'null' => true
                    ],
                    'orig_amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ]
                );

                $this->CI->dbforge->add_field($fields);
                $this->CI->dbforge->add_key('id', TRUE);
                $this->CI->dbforge->create_table($tableName);
                # Add Index
                $this->CI->load->model('player_model');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_player_id','player_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_settled_at','settled_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_start_at','start_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_end_at','end_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_updated_at','updated_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_transaction_type','transaction_type');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_round_id','round_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_wager_id','wager_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_game_id','game_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_status','status');
                $this->CI->player_model->addUniqueIndex($tableName, 'idx_seamlesstransaction_external_uniqueid', 'external_uniqueid');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

    public function getWagerStatus($transType){
        foreach(self::WAGER_ACTION_STATUS as $key => $val){
            if($transType==$key){
                return $val;
            }
        }
        return false;
    }


    public function queryTransactionByDateTime($startDate, $endDate){
        $date = new DateTime($startDate);
        $monthStr = $date->format('Ym');
        $transactionTable = $this->getTransactionsTable();
        $currentTableData = $this->queryTransactionByDateTimeGetData($transactionTable, $startDate, $endDate);

        $prevTableData = $finalData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();
        if(($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table) || $checkOtherTable){
            $prevTable = $this->getTransactionsPreviousTable(); 
            $prevTableData = $this->queryTransactionByDateTimeGetData($prevTable, $startDate, $endDate);                   
        }
        $finalData = array_merge($currentTableData, $prevTableData);        
        
        return $finalData;
    }



    public function queryTransactionByDateTimeGetData($table, $startDate, $endDate){
        
$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.transaction_type trans_type,
t.wallet_adjustment_mode wallet_adjustment_mode,
t.extra_info extra_info,
t.game_id game_code
FROM {$table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];
        
                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
        
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = array();
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [
                    'wager_id'=>$transaction['round_id'], 
                    'game_code'=>$transaction['game_code']
                ];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['wallet_adjustment_mode'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function rebuildBetDetailsFormat($row, $game_type) {
        $bet_details = [];
        $action = null;

        switch ($game_type) {
            case self::GAME_TYPE_SPORTS:
            case self::GAME_TYPE_E_SPORTS:

                if ($this->enable_merging_rows) {
                    if (!empty($row['wager_data'])) {
                        $action = !empty($row['wager_data']['Name']) ? $row['wager_data']['Name'] : null;

                        if (!empty($row['wager_data']['WagerInfo'])) {
                            $row = $row['wager_data']['WagerInfo'];
                        }
                    }

                    if (!empty($row['settle_data'])) {
                        $action = !empty($row['settle_data']['Name']) ? $row['settle_data']['Name'] : null;

                        if (!empty($row['settle_data']['WagerInfo'])) {
                            $row = $row['settle_data']['WagerInfo'];
                        }
                    }
                } else {
                    if (isset($row['extra_info'])) {
                        $extra_info = !is_array($row['extra_info']) ? json_decode($row['extra_info'], true) : $row['extra_info'];
                        $action = !empty($extra_info['Actions'][0]['Name']) ? $extra_info['Actions'][0]['Name'] : null;

                        if (!empty($extra_info['Actions'][0]['WagerInfo'])) {
                            $row = $extra_info['Actions'][0]['WagerInfo'];
                        }
                    }
                }

                if (!empty($action)) {
                    $bet_details['action'] = $action;
                }

                if (isset($row['Odds'])) {
                    $bet_details['odds'] = $row['Odds'];
                }
        
                if (isset($row['Stake'])) {
                    $bet_details['stake'] = $row['Stake'];
                }
        
                if (isset($row['EventId'])) {
                    $bet_details['event_id'] = $row['EventId'];
                }
        
                if (isset($row['Outcome'])) {
                    $bet_details['outcome'] = $row['Outcome'];
                }
        
                if (isset($row['WagerId'])) {
                    $bet_details['wager_id'] = $row['WagerId'];
                }
        
                if (isset($row['LeagueId'])) {
                    $bet_details['league_id'] = $row['LeagueId'];
                }
        
                if (isset($row['EventName'])) {
                    $bet_details['event_name'] = $row['EventName'];
                }
        
                if (isset($row['Selection'])) {
                    $bet_details['selection'] = $row['Selection'];
                }
        
                if (isset($row['LeagueName'])) {
                    $bet_details['league_name'] = $row['LeagueName'];
                }
        
                if (isset($row['EventDateFm'])) {
                    $bet_details['event_datetime'] =  $this->gameTimeToServerTime($row['EventDateFm']);
                }
                
                if (isset($row['SettlementTime'])) {
                    $bet_details['settlement_datetime'] = $this->gameTimeToServerTime($row['SettlementTime']);
                }
                break;
            default:
                if (isset($row['transaction_id'])) {
                    $bet_details['transaction_id'] = $row['transaction_id'];
                }
        
                if (isset($row['game_code'])) {
                    $bet_details['game_code'] = $row['game_code'];
                }
        
                if (isset($row['game_name'])) {
                    $bet_details['game_name'] = $row['game_name'];
                }
        
                if (isset($row['round_id'])) {
                    $bet_details['round_id'] = $row['round_id'];
                }
                break;
        }

        return $bet_details;
    }

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['ogl_game_name'])) {
            $bet_details['game_name'] = $row['ogl_game_name'];
        }
        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }
        if (isset($row['wager_id'])) {
            $bet_details['bet_id'] = $row['wager_id'];
        }
        if (isset($row['bet_type'])) {
            $bet_details['bet_type'] = $row['bet_type'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }
        if (isset($row['settled_at'])) {
            $bet_details['settlement_datetime'] = $row['settled_at'];
        }

        // if (isset($row['extra_info'])) {
        //     $bet_details['extra '] = $row['extra_info'];
        // }

        return $bet_details;
     }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getTransactionsTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $sqlTime="PINNACLE.created_at >= ? AND PINNACLE.created_at <= ? AND PINNACLE.transaction_type = ? AND PINNACLE.status in ('ACCEPTED', 'OPEN')";
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
PINNACLE.round_id as round_id, 
PINNACLE.wager_id as transaction_id, 
PINNACLE.created_at as transaction_date,
PINNACLE.external_uniqueid as external_uniqueid,
PINNACLE.player_id,
PINNACLE.transaction_type,
PINNACLE.amount,
PINNACLE.amount as deducted_amount,
0 as added_amount,
PINNACLE.game_platform_id as game_platform_id,
gd.id as game_description_id,
gd.game_type_id

from {$original_transactions_table} as PINNACLE
LEFT JOIN game_description as gd ON PINNACLE.game_id = gd.external_game_id and gd.game_platform_id = ?
where
{$sqlTime}
EOD;

        $transaction_type = "BETTED";
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $transaction_type,

        ];
        $this->CI->utils->debug_log('==> PINNACLE getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $external_uniqueid = $row['external_uniqueid'];
            $bet = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_uniqueid'=> $external_uniqueid]);
            if($bet['status'] == 'ACCEPTED' || $bet['status'] == 'OPEN'){
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                unset($row['row_count']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('PINNACLE SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $original_transactions_table = $this->getTransactionsTable();
        $row = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_uniqueid'=> $external_uniqueid]);
        if(!empty($row)){
            if($row['status'] == 'SETTLED'){
                return array('success'=>true, 'status'=> Game_logs::STATUS_SETTLED);
            }
            if($row['status'] == 'CANCELLED'){
                return array('success'=>true, 'status'=> Game_logs::STATUS_CANCELLED);
            }
            if($row['status'] == 'REJECTED'){
                return array('success'=>true, 'status'=> Game_logs::STATUS_REJECTED);
            }
            if($row['status'] == 'ROLLBACKED'){
                return array('success'=>true, 'status'=> Game_logs::STATUS_REFUND);
            }
        }
        
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }

    private function isFailedTransactionExist($where=[]){
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $isExisting = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
        $this->utils->debug_log("EVOPLAY SEAMLESS SERVICE API: isFailedTransactionExist",$table_name, $where, $isExisting);
        return $isExisting;
    }

    private function remoteWalletErrorCode(){
        $this->CI->load->model(['wallet_model']);
        if (method_exists($this->CI->wallet_model, 'getRemoteWalletErrorCode')) {
            $errorCode = $this->CI->wallet_model->getRemoteWalletErrorCode();
            $this->utils->debug_log("EVOPLAY SEAMLESS SERVICE API: remoteWalletErrorCode", $errorCode);
            return $errorCode;
        }
        return null;
    }

    private function saveRemoteWalletError($params){

        if($this->remote_wallet_error_code){
			$failed_external_uniqueid = $params['external_uniqueid'];
			$failed_transaction_data = $md5_data = [
				'round_id' => $params['round_id'],
				'transaction_id' => $params['transaction_id'],
				'external_game_id' => $params['game_id'],
				'player_id' => $params['player_id'],
				'game_username' => null,
				'amount' => $params['amount'],
				'balance_adjustment_type' => $params['wallet_adjustment_mode'],
				'action' => $params['transaction_type'],
				'game_platform_id' => $this->getPlatformCode(),
				'transaction_raw_data' => json_encode($params['extra_info']),
				'remote_raw_data' => null,
				'remote_wallet_status' => $this->remote_wallet_error_code,
				'transaction_date' => $params['start_at'] ,
				'request_id' => $this->utils->getRequestId(),
				'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
				'headers' => json_encode(getallheaders()),
				'external_uniqueid' => $failed_external_uniqueid,
			];
			
			$failed_transaction_data['md5_sum'] = md5(json_encode($md5_data));

			$where = ['external_uniqueid' => $failed_external_uniqueid];
			if($this->isFailedTransactionExist($where)){
				$this->saveFailedTransaction('update',$failed_transaction_data, $where);
			}else{
				$this->saveFailedTransaction('insert',$failed_transaction_data);
			}
        }
    }

    private function saveFailedTransaction($query_type='insert', $data=[], $where=[]){
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $this->utils->debug_log("EVOPLAY SEAMLESS SERVICE API: saveFailedTransaction",$query_type, $table_name, $data, $where);
        $this->CI->original_seamless_wallet_transactions->saveTransactionData($table_name, $query_type, $data, $where);
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
    }
}

/*end of file*/
