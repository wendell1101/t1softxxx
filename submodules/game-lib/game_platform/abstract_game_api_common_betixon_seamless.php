<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Betixon Seamless Integration
 * OGP-30817
 * ? uses betixon_seamless_service_api for its service API
 *
 * Game Platform ID: 6317
 *
 */

abstract class Abstract_game_api_common_betixon_seamless extends Abstract_game_api
{
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'round_id',
        'credit_amount',
        'debit_amount',
        'is_done',
        'before_balance',
        'after_balance',
        'total_balance',
        'trans_status',
        'rgs_related_transaction_id'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'debit_amount',
        'credit_amount',        
        'before_balance',
        'after_balance',
        'total_balance'
    ];
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'debit_amount',
        'credit_amount',        
    ];
    const METHOD = [
        'GET' => 'GET',
        'POST' => 'POST'
    ];

    const CLIENT_PC = 1;
    const CLIENT_MOBILE = 2;


    const URI_MAP = [
        self::API_queryForwardGame => "/Server/LaunchGame",
        self::API_queryDemoGame => "/Server/LaunchDemoGame",
    ];

    public $original_transactions_table;
    public $api_url, $launch_url, $sync_time_interval, $currency, $language;
    public $enable_home_link, $home_link, $cashier_link, $ip, $return_url;
    public $api_username, $api_password, $use_third_party_token;

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->launch_url = $this->getSystemInfo('launch_url');
        $this->return_url = $this->getSystemInfo('return_url');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');

        $this->original_transactions_table = 'betixon_seamless_wallet_transactions';

        $this->api_username = $this->getSystemInfo('api_username', null);
        $this->api_password = $this->getSystemInfo('api_password', null);

        $this->use_third_party_token = $this->getSystemInfo('use_third_party_token', false);

        $this->enable_home_link = $this->getSystemInfo('enable_home_link', true);
        $this->home_link = $this->getSystemInfo('home_link');
        $this->cashier_link = $this->getSystemInfo('cashier_link');
        
        // $this->ip =  $_SERVER['REMOTE_ADDR'];
    }


    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return BETIXON_SEAMLESS_GAME_API;
    }

    public function getCurrency()
    {
        return $this->currency;
    }


    public function generateUrl($apiName, $params)
    {
        $uri = self::URI_MAP[$apiName];

        $url = $this->api_url . $uri;
        return $url;
    }


    public function getHttpHeaders($params = [])
    {
        return array(
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        );
    }

    protected function customHttpCall($ch, $params)
    {
        if ($params["actions"]["method"] == self::METHOD["POST"]) {
            $function = $params["actions"]['function'];

            unset($params["actions"]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
    {
        $success = false;
        if ((isset($resultArr['Status']['Error']) && $resultArr['Status']['Error'] == 0) && (isset($resultArr['Status']['ErrCode']) && $resultArr['Status']['ErrCode'] == 0)) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('BETIXON SEAMLESS GAME API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for BETIXON";
        if ($return) {
            $success = true;
            $message = "Successfull create account for BETIXON.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null)
    {
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs' => true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null)
    {
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs' => true,
        );
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $apiName = self::API_queryDemoGame;
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($player_id);
        $gameUsername = null;
        $player = null;
        $isDemo = true;
        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $isDemo = false;
            $apiName = self::API_queryForwardGame;
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $player = $this->CI->player_model->getPlayerByUsername($playerName);
        }

        $this->CI->utils->debug_log('Betixon Player Details: ', $extra, $playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $is_mobile = $extra['is_mobile'];
        if ($is_mobile) {
            $clienttype = self::CLIENT_MOBILE;
        } else {
            $clienttype = self::CLIENT_PC;
        }

        if (isset($extra['home_link']) && !empty($extra['home_link'])) {
            $return_url = $extra['home_link'];

        } else if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) {
            $return_url = $extra['extra']['t1_lobby_url'];
        } else if (!empty($this->return_url)) {
            $return_url = $this->return_url;
        } else {
            $return_url = $this->getHomeLink();
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'player' => $player,
            'isDemo' => $isDemo,
            'return_url' => $return_url
        );

        $gameCode = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $ip =  $this->CI->utils->getIP();

        // $testAuthToken = time() . '_' . 'testauthtoken';
        $main_params = [
            "CurrencyCode" => $this->currency,
            "Platform" => $clienttype,
            "LanguageCode" => $this->language,
            "HomeUrl" => $this->home_link,
            "GameCode" => $gameCode,
            // "GameCode" => 'BTX_GoldenEra',
            "PlayerId" => $gameUsername,
            "PlayerIP" =>  $ip,
            "ptoken" => $token,
            "Account" => [
                "UserName" => $this->api_username,
                "Password" => $this->api_password
            ]
        ];
       

        if (!empty($gameCode)) {
            $params['gid'] = $gameCode;
        }


        $gameMode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        if (in_array($gameMode, $this->demo_game_identifier)) {
            $params['ist'] = 1;
        }

        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => $apiName,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $player = $this->getVariableFromContext($params, 'player');
        $return_url = $this->getVariableFromContext($params, 'return_url');
        $playerId = isset($player->playerId) ? $player->playerId : null; 
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();    


        $result['url'] = $return_url;
        
        if($success){
            $this->CI->load->model(['external_common_tokens']);

            $token = isset($resultArr['Token']) ? $resultArr['Token'] : null;

            if($this->use_third_party_token && $token != null){
                #save token here
                $this->CI->external_common_tokens->addPlayerToken($playerId, $token, $this->getPlatformCode(),$this->currency);
            }
            if(isset($resultArr['Url']))
            {
                $result['url'] = $resultArr['Url'];
            }
        }

        $this->CI->utils->debug_log("betixon launch url: " , $result['url']);
        $this->CI->utils->debug_log("betixon launch game status: " , $success);
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
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
        
        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);   

        $this->CI->utils->debug_log("BETIXON SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("BETIXON SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);        
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';


        $this->CI->utils->debug_log('BETIXON SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.round_id', 'original.trans_status','original.is_done','original.credit_amount', 'original.debit_amount', 'original.before_balance', 'original.after_balance', 'original.total_balance', 'original.external_uniqueid', 'original.rgs_related_transaction_id'));

        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.debit_amount,
    original.credit_amount,
    original.result_amount,
    original.game_code as game_id,
    original.rgs_player_id,
    original.rgs_transaction_id,
    original.round_id,
    original.round_start,
    original.round_end,
    original.promo,
    original.code,
    original.is_done,
    original.total_spins,
    original.spins_done,
    original.total_balance,
    original.rgs_related_transaction_id,
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
    original.raw_data,
    MD5(CONCAT({$md5Fields})) as md5_sum,

    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE (original.trans_type='debitAndCredit') AND
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('BETIXON SEAMLESS GAME (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('BETIXON SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        // $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('BETIXON SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

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
                'player_username'       => $row['rgs_player_id']
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['debit_amount']) ? $this->gameAmountToDBTruncateNumber($row['debit_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDBTruncateNumber($row['result_amount']) : 0,
				'bet_for_cashback'      => isset($row['debit_amount']) ? $this->gameAmountToDBTruncateNumber($row['debit_amount']) : 0,
				'real_betting_amount'   => isset($row['debit_amount']) ? $this->gameAmountToDBTruncateNumber($row['debit_amount']) : 0,
				'win_amount'            => 0,
				'loss_amount'           => 0,
                'after_balance'         => 0,
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
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['balance_adjustment_method'],
            ],
            'bet_details' => $row['raw_data'],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('BETIXON after_balance:', $data);
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

    public function queryPlayerBalanceByPlayerId($playerId) {
        $this->CI->load->model(['player_model']);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like betixon_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}

		return $tableName;
	}

    public function queryTransactionByDateTime($startDate, $endDate){
        $transTable = $this->getTransactionsTable();

        
        
$sql = <<<EOD
SELECT
t.rgs_player_id as player_id,
t.updated_at as transaction_date,
t.result_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.balance_adjustment_method,
t.trans_type,
t.raw_data as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;
        
        
        // $startDate = strval(strtotime($startDate) * 1000);
        // $endDate = strval(strtotime($endDate) * 1000);
        $params=[$this->getPlatformCode(),$startDate, $endDate];

        

        $this->CI->utils->debug_log('Betixon SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
        
    public function processTransactions(&$transactions){
        $this->CI->utils->debug_log('Betixon process transaction', $transactions);
        $temp_game_records = [];

        if(!empty($transactions)){
           
            foreach($transactions as $transaction){
                $temp_game_record = [];
                $temp_game_record['player_id'] = $this->getPlayerIdByGameUsername($transaction['player_id']);
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['balance_adjustment_method'];
                
                if($transaction['after_balance'] == $transaction['before_balance']){
                    $extra['trans_type'] = $transaction['trans_type'];
                }     

                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance'] < $transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }
        $transactions = $temp_game_records;
    }
        
    public function dumpData($data){
        print_r(json_encode($data));exit;
    }

}

/*end of file*/