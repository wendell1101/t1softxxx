<?php
require_once dirname(__FILE__) . '/game_api_nextspin.php';

class Game_api_nextspin_seamless extends Game_api_nextspin {
    public $origin;

    const MD5_FIELDS_FOR_ORIGINAL = [
        'type',
        'trans_type',
        'ticket_id',
        'gameCode',
        'serial_no',
        'reference_id',
        'trans_status',
    ];

	const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'before_balance',
        'after_balance'
    ];

	const MD5_FIELDS_FOR_MERGE = [
        'game_code',
        'before_balance',
        'after_balance',
    ];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'before_balance',
        'after_balance'
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCELLED = 'cancel';
    const TRANSACTION_ROLLBACK = 'rollback';

    const TRANSFER_TYPE_BET = 1;
	const TRANSFER_TYPE_CANCEL = 2;
	const TRANSFER_TYPE_PAYOUT = 4;
	const JACKPOT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER = 6;
	const JACKPOT_PAYOUT_BY_MERCHANT = 7;
	const JACKPOT_PAYOUT_BY_GAME_PROVIDER = 8;
	const TOURNAMENT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER = 9;
	const TOURNAMENT_PAYOUT_BY_MERCHANT = 10;
	const TOURNAMENT_PAYOUT_BY_GAME_PROVIDER = 11;
	const RED_PACKET_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER = 12;
	const RED_PACKET_PAYOUT_BY_MERCHANT = 13;
	const RED_PACKET_PAYOUT_BY_GAME_PROVIDER = 14;
	
	const PAYOUT_TRANSFER_TYPES= [
		self::TRANSFER_TYPE_PAYOUT,
		self::JACKPOT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER,
		self::JACKPOT_PAYOUT_BY_MERCHANT,
		self::JACKPOT_PAYOUT_BY_GAME_PROVIDER,
		self::TOURNAMENT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER,
		self::TOURNAMENT_PAYOUT_BY_MERCHANT,
		self::TOURNAMENT_PAYOUT_BY_GAME_PROVIDER,
		self::RED_PACKET_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER,
		self::RED_PACKET_PAYOUT_BY_MERCHANT,
		self::RED_PACKET_PAYOUT_BY_GAME_PROVIDER,
	];

    public function __construct() {
        parent::__construct();
        $this->original_transactions_table = 'nextspin_seamless_wallet_transactions';
        $this->api_url = $this->getSystemInfo('url');
        $this->merchantCode = $this->getSystemInfo('merchantCode', 'ZCH535TEST');
        $this->brand = $this->getSystemInfo('brand', null);
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->game_url = $this->getSystemInfo('game_url', 'https://lobby.lucky88dragon.com');
        $this->demo_url = $this->getSystemInfo('demo_url', 'https://play.lucky88dragon.com/game');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like nextspin_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}

		return $tableName;
	}

    public function isSeamLessGame() {
        return true;
    }

    public function getPlatformCode() {
        return NEXTSPIN_SEAMLESS_GAME_API;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for NEXTSPIN Seamless";
        if($return) {
            $success = true;
            $this->setGameAccountRegistered($playerId);
            $message = "Successfully created account for NEXTSPIN Seamless";
        }

        return [
            "success" => $success,
            "message" => $message
        ];
    }

    public function isPlayerExist($playerName) {
        return [
            'success' => true,
            'exists'=> $this->isPlayerExistInDB($playerName)
        ];
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = [
            'success' => true,
            'balance' => $balance
        ];

        return $result;
    }

    public function queryPlayerBalanceByPlayerId($playerId) {
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );
        
        return $result;
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        ];
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        ];
    }
    public function queryForwardGame($playerName, $extra = null) 
    {
		$this->CI->load->model('common_token');
		$gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId       = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$token 			= $this->CI->common_token->createTokenBy($playerId, 'player_id');
        $merchantCode   = $this->merchantCode;
		$game_url 		= $this->game_url;
        $demo_url       = $this->demo_url;
        $game           = $extra['game_code'] ? $extra['game_code'] : "";
        $language       = $this->language;
		$auth 			= self::API_AUTHORIZE."?";

        //for lobby redirection
		if (isset($extra['home_link']) && !empty($extra['home_link']))
        {
            $this->lobby_url = $extra['home_link'];
        }
        else if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) 
        {
            $this->lobby_url = $extra['extra']['t1_lobby_url'];
        }
        else
        {
            $this->lobby_url = $this->getHomeLink();
        }

        //for language value
        if (isset($extra['language']) && !empty($extra['language']))
        {
            $extra['language'] = $this->language ? $this->language : $this->getLauncherLanguage($extra['language']);
        }
        else 
        {
            $extra['language'] = $this->language;
        }

        if(empty($game))
        {
            // For game lobby
            $params = array(
                "acctId" 	=> $gameUsername,
                "token"		=> $token,
                "channel"   => "Web",
                "language"  => $this->getLauncherLanguage($this->language),
                "isLobby"   => "true",
                "exitUrl"   => $this->lobby_url,
            );
        }
        else
        {
            // For game page
            $params = array(
                "acctId" 	=> $gameUsername,
                "token"		=> $token,
                "language"  => $this->getLauncherLanguage($extra['language']),
                "game"      => $game,
            );
        }

		if($extra['is_mobile'])
        {
			$params["channel"] = "Mobile";
		}

        $url_params  = http_build_query($params);
		// $url_params  = http_build_query(array_merge($params,$extra));
		$generateUrl = $game_url.'/'.$merchantCode.$auth.$url_params;
        
        if($extra['game_mode'] == "trial")
        {
            $params = array(
                "merchantCode" => $merchantCode,
                "game"         => $game,
                "language"     => $this->getLauncherLanguage($this->language)
            );
            $url_params  = http_build_query($params);
            $generateUrl = $demo_url.'?'.$url_params;
        }

		$data = [
            'url' => $generateUrl,
            'success' => true
        ];
        $this->utils->debug_log('NextSpin' . __FUNCTION__ . $generateUrl);
        return $data;
	}

    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
}

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $original_transactions_table = $this->getTransactionsTable();

        $selectedQuery = 'queryOriginalGameLogsMergedWithTable';
        if(!$this->enable_merging_rows){
            $selectedQuery = 'queryOriginalGameLogsUnmergedWithTable';
        }

        $currentTableData = $this->$selectedQuery($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);   

        $this->CI->utils->debug_log("NEXTSPIN_SEAMLESS_GAME_API: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("NEXTSPIN_SEAMLESS_GAME_API: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            
            
            $prevTableData = $this->$selectedQuery($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);        

        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }


    public function queryOriginalGameLogsMergedWithTable($table, $dateFrom, $dateTo, $use_bet_time=true){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        #in use as default
        if($use_bet_time){
            $sqlTime='`original`.`created_at` >= ? AND `original`.`created_at` <= ?';
        }

        $this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API: sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.type','original.amount','original.before_balance', 'original.after_balance', 'original.external_uniqueid', 'original.reference_id'));
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.amount,
CASE   
    WHEN original.reference_id IS NULL THEN
        CASE 
            WHEN (SELECT amount FROM $table WHERE reference_id = original.transfer_id) IS NOT NULL THEN
                (SELECT amount FROM $table WHERE reference_id = original.transfer_id)
            ELSE original.amount
        END
    ELSE original.amount
END AS payout_amount,
    original.game_code as game_id,
    original.player_id,
    original.acct_id as player_username,
    original.transfer_id,
    original.reference_id,
    original.ticket_id,
    original.serial_no,
    original.type,
    original.currency,
    original.trans_type,
    original.trans_status,
    original.trans_status as status,
    original.player_id,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.response_result_id,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    #original.raw_data,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code,
    gd.game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id,
    gd.english_name as game_english_name
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
original.type=?
and {$sqlTime}
;
EOD;

        $params=[
            $this->getPlatformCode(),
            self::TRANSFER_TYPE_BET,  
            $dateFrom,
            $dateTo
		];


		$this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API: (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function queryOriginalGameLogsUnmergedWithTable($table, $dateFrom, $dateTo, $use_bet_time=true){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        #in use as default
        if($use_bet_time){
            $sqlTime='`original`.`created_at` >= ? AND `original`.`created_at` <= ?';
        }

        $this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API: sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.type','original.amount','original.before_balance', 'original.after_balance', 'original.external_uniqueid', 'original.reference_id'));
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.amount,
CASE
    WHEN original.reference_id IS NULL AND type=1 THEN original.amount
    ELSE 0
END AS bet_amount,
CASE
    WHEN original.reference_id IS NOT NULL AND type<>1 THEN original.amount
    ELSE 0
END AS payout_amount,
    original.ticket_id,
    original.game_code as game_id,
    original.player_id,
    original.acct_id as player_username,
    original.transfer_id,
    original.reference_id,
    original.serial_no,
    original.type,
    original.currency,
    original.trans_type,
    original.trans_status,
    original.trans_status as status,
    original.player_id,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.response_result_id,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    #original.raw_data,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code,
    gd.game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id,
    gd.english_name as game_english_name
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE {$sqlTime}
;
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];
		$this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API: (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }
        // $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);
        $result_amount = $row['payout_amount'] - $row['amount'];
        $after_balance = null;
        if(!$this->enable_merging_rows){
            $after_balance = $row['after_balance'];
            if($row['type'] == self::TRANSFER_TYPE_BET){
                $result_amount = 0 - $row['amount'];
            }
            if(in_array($row['type'], self::PAYOUT_TRANSFER_TYPES)){
                $row['amount'] = 0;
                $result_amount = $row['payout_amount'];
            }
        }

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['amount']) ? $this->gameAmountToDBTruncateNumber($row['amount']) : 0,
                'result_amount'         => $result_amount,
				'bet_for_cashback'      => isset($row['amount']) ? $this->gameAmountToDBTruncateNumber($row['amount']) : 0,
				'real_betting_amount'   => isset($row['amount']) ? $this->gameAmountToDBTruncateNumber($row['amount']) : 0,
				'win_amount'            => null,
				'loss_amount'           => null,
                'after_balance'         => $after_balance,
            ],
            'date_info' => [
                'start_at'              => $row['created_at'],
                'end_at'                => $row['updated_at'],
                'bet_at'                => $row['created_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['trans_status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['ticket_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['balance_adjustment_method'],
            ],
            'bet_details' => [],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('NEXTSPIN_SEAMLESS_GAME_API after_balance:', $data);
        return $data;;

    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if(isset($row['bet_type'])){
            $row['bet_type'] = $row['bet_type'];
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
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
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
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['balance_adjustment_method'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                if($transaction['balance_adjustment_method'] == SELF::TRANSACTION_CREDIT || $transaction['balance_adjustment_method'] == SELF::TRANSACTION_ROLLBACK) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                } else {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
    
    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getTransactionsTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.ticket_id as round_no,
t.transfer_id as transaction_id,
t.balance_adjustment_method,
t.external_uniqueid as external_uniqueid,
t.trans_type as trans_type
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
        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['external_uniqueid'];
        }
        if (isset($row['external_uniqueid'])) {
            $bet_details['bet_id'] = $row['external_uniqueid'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['amount'];
        }

        if (isset($row['created_at'])) {
            $bet_details['betting_datetime'] = $row['created_at'];
        }

        return $bet_details;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row)
    {
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
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
            'status' => $row['settled'] ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsMerge(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set after balance
        $row['after_balance'] = $row['before_balance'] + $row['result_amount'];
    }

    public function getLauncherLanguage($language) {
        $lang = '';

        $language = strtolower($language);
        switch($language)
        {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                    $lang = 'en_US';
                    break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                    $lang = 'zh_CN';
                    break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vi':
            case 'vi-vn':
            case 'vi_vn':
                    $lang = 'vi_VN';
                    break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-br':
            case 'pt-pt':
                $lang = 'pt_BR';
                break;
            case Language_function::INT_LANG_INDIA:
                case 'hi-in':
                case 'id-id':
                $lang = 'hi_IN';
                break;
            case Language_function::INT_LANG_THAI:
                case 'th-th':
                $lang = 'th_TH';
            default:
                $lang = 'zh_CN';
                break;
        }

        return $lang;
	}
	
}