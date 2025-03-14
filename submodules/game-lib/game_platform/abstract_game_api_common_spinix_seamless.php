<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Spinix Seamless Integration
 * OGP-32916
 * ? uses spinix_seamless_service_api for its service API
 *
 * Game Platform ID: 6376
 *
 */

abstract class Abstract_game_api_common_spinix_seamless extends Abstract_game_api
{    # Fields in game_logs we want to detect changes for merge and when md5_sum
    const MD5_FIELDS_FOR_MERGE = [
        'game_id', 
        "game_type", 
        "round_id",
        "valid_turnover",
        "amount",
        "info",
        "is_end",
        "status",
        'before_balance',
        'after_balance',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',     
        'before_balance',
        'after_balance',
    ];
    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',    
    ];

    const GET = 'GET';
    const POST = 'POST';
    const STATUS_CODE_SUCCESS = 200;

    const TRANS_TYPE_BET = "bet";
	const TRANS_TYPE_WIN = "win";
	const TRANS_TYPE_CANCEL_BET = "cancelBet";

    public $URI_MAP = [
        self::API_queryForwardGame => "/games/getGameUrl",
        self::API_queryDemoGame => "/demo/games/getGameUrl",
        self::API_queryGameListFromGameProvider => '/games/getGameList'
    ];
    
    const WALLET_TYPE_SEAMLESS = "seamless";
    const WALLET_TYPE_TRANSFER = "transfer";

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->launch_url = $this->getSystemInfo('launch_url');

        $this->return_url = $this->getSystemInfo('return_url');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');

        $this->original_transactions_table = 'spinix_seamless_wallet_transactions';

        $this->platform_id = $this->getSystemInfo('platform_id', null);
        $this->secret_key = $this->getSystemInfo('secret_key', "");
        $this->signature_key = $this->getSystemInfo('signature_key', null);
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
    }


    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return SPINIX_SEAMLESS_GAME_API;
    }

    public function getCurrency()
    {
        return $this->currency;
    }


    public function generateUrl($apiName, $params)
    {
        $uri = $this->URI_MAP[$apiName];

        $url = $this->api_url . $uri;
        return $url;
    }


    public function getHttpHeaders($params = [])
    {

        $customHeaders = isset($params['custom_headers']) ? $params['custom_headers'] : [];
        $customHeaders = ([
            "X-Gaming-Signature" => $this->getSignature($params, $this->signature_key)
        ]);
        $defaultHeaders = array(
            "Content-Type" => "application/json",
            "Authorization" => $this->secret_key
        );
       
        $res =  array_merge($defaultHeaders, $customHeaders);
        return $res;
    }

    protected function customHttpCall($ch, $params)
    {
        if ($this->method == self::POST) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
    {
        $success = false;
        if (isset($resultArr['status']) == self::STATUS_CODE_SUCCESS) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('SPINIX SEAMLESS GAME API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }  

    

    public function queryGameListFromGameProvider($extra=null){ 
        $this->utils->debug_log("SPINIX SEAMLESS SEAMLESS: (queryGameList)");   
        $this->method = self::POST;
        $params = [
            'platform_id' => $this->platform_id
        ];
        $signature = $this->getSignature($params, $this->signature_key);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    } 

    public function processResultForQueryGameListFromGameProvider($params){
		$this->CI->utils->debug_log('SPINIX SEAMLESS SEAMLESS (processResultForQueryForwardGame)', $params);	

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');		
		$resultArr = $this->getResultJsonFromParams($params);   
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = $resultArr;

		return array($success, $result);
	}
    
    protected function getSignature($args, $signatureKey) {
        $result = $signatureKey;
        $keyObject = $this->_getKeyValueFromObject($args);
        unset($keyObject["signature"]);
        ksort($keyObject);
        foreach ($keyObject as $key => $value) {
            if (is_bool($value)) {
                $value = empty($value) ? 'false' : 'true';
            }
            $result .= "&$key={$value}";
        }
        return md5($result);
    }
    
    protected function _getKeyValueFromObject($args, $prefixKey = "") {
        $result = array();
        foreach ($args as $key => $value) {
            if (is_null($value) == true) {
                continue;
            }
            if (is_array($value) == false) {
                $resultKey = "";
                if ($prefixKey != "") {
                    $resultKey = "$prefixKey.";
                }
                $resultKey .= $key;
                $result[$resultKey] = $value;
            } else {
                if (count($args) > 0) {
                    $nestedPrefixKey = $key;
                    if ($prefixKey != "") {
                        $nestedPrefixKey = "$prefixKey.$key";
                    }
                    $nestedResult = $this->_getKeyValueFromObject($value, $nestedPrefixKey);
                    $result = array_merge($result, $nestedResult);
                }
            }
        }
        return $result;
    }
    

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for SPINIX";
        if ($return) {
            $success = true;
            $message = "Successfull create account for SPINIX.";
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
        $isDemo = true;

        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $isDemo = false;
            $apiName = self::API_queryForwardGame;
        }

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($player_id);
        $this->method = self::POST;

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $player = null;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'player' => $player,
        );

        if (isset($extra['home_link']) && !empty($extra['home_link'])) {
            $return_url = $extra['home_link'];

        } else if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) {
            $return_url = $extra['extra']['t1_lobby_url'];
        } else if (!empty($this->return_url)) {
            $return_url = $this->return_url;
        } else {
            $return_url = $this->getHomeLink();
        }
        
        $gameCode = !empty($extra['game_code']) ? $extra['game_code'] : null;

        $staticGameid = "65a741f59aeb8a916eda2c59";
        $params = array(
            "game_id" => $gameCode,
            "platform_id" => $this->platform_id,
            "user_id" => $gameUsername,
            "user_token" => $token,
            "currency" => $this->currency,
            "wallet_type" => 'seamless',
            "settings" =>  [
                "lang" => $this->getlauncherLanguage($this->language),
                "eurl" =>  $return_url
            ]   
        );

        if($isDemo){
            $params = array(
                "game_id" => $gameCode,
                "platform_id" => $this->platform_id,
            );
        }
     
        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['settings']['eurl']);
        }

        if($this->force_disable_home_link){
            unset($params['settings']['eurl']);
        }



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

            $token = isset($resultArr['GameToken']) ? $resultArr['GameToken'] : null;

            if(isset($resultArr['data']['url']))
            {
                $result['url'] = $resultArr['data']['url'];
            }
        }

        $this->CI->utils->debug_log("spinix launch url: " , $result['url']);
        $this->CI->utils->debug_log("spinix launch game status: " , $success);
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function generateSignature($data = []){
		return md5(implode('',$data));
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

        $this->CI->utils->debug_log("SPINIX SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("SPINIX SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);      
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';


        $this->CI->utils->debug_log('SPINIX SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.game_id', 'original.game_type','original.round_id','original.valid_turnover', 'original.amount', 'original.info', 'original.is_end', 'original.status','original.before_balance', 'original.after_balance'));


        $sql = <<<EOD
SELECT
    original.id as sync_index,
    CASE WHEN original.type = 'bet' THEN original.amount ELSE 0 END AS bet_amount,
    CASE WHEN original.type = 'win' THEN original.amount ELSE 0 END AS win_amount,
    CASE WHEN original.type = 'cancelBet' THEN original.amount ELSE 0 END AS cancel_amount,
    original.amount,
    original.valid_turnover,
    original.game_id,
    original.player_id,
    original.transaction_id,
    original.round_id,

    original.is_end,
    original.currency,
    original.trans_type,
    original.type,
    original.status,
    original.user_id,
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
    gd.game_type_id,
    gd.english_name as game_english_name
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
original.trans_type IN ('bet', 'win')
and {$sqlTime}
;
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];


		$this->CI->utils->debug_log('SPINIX_SEAMLES_GAME_API (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('SPINIX SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        // $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('SPINIX SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

        $amount = isset($row['amount']) ? $row['amount'] : 0;
        $bet_amount = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
        $win_amount = isset($row['win_amount']) ? $row['win_amount'] : 0;
        $win_amount = isset($row['win_amount']) ? $row['win_amount'] : 0;
        $cancel_amount = isset($row['cancel_amount']) ? $row['cancel_amount'] : 0;
        $valid_turnover = isset($row['valid_turnover']) ? $row['valid_turnover'] : 0;
        
        switch ($row['type']) {
            case self::TRANS_TYPE_BET:
                $result_amount = $win_amount - $bet_amount;
                break;
            case self::TRANS_TYPE_WIN:
                $result_amount = $win_amount;
                break;
            case self::TRANS_TYPE_CANCEL_BET:
                $result_amount = $cancel_amount;
                break;
            default:
                // Handle default case if needed
                break;
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
                'player_username'       => $row['user_id']
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $result_amount,
				'bet_for_cashback'      => $bet_amount,
				'real_betting_amount'   => $bet_amount,
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
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['balance_adjustment_method'],
            ],
            'bet_details' =>            $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('SPINIX after_balance:', $data);
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

    public function queryPlayerBalance($gameUsername) {
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = [
            'success' => true,
            'balance' => $balance
        ];

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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like ' . $this->original_transactions_table);

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
t.user_id as game_username,
t.updated_at as transaction_date,
t.amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.balance_adjustment_method as trans_type,
t.raw_data as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];

        

        $this->CI->utils->debug_log('SPINIX SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($this->CI->db->last_query());exit;
        return $result;
    }
        
    public function processTransactions(&$transactions){
        $this->CI->utils->debug_log('SPINIX process transaction', $transactions);
        $temp_game_records = [];

        if(!empty($transactions)){
           
            foreach($transactions as $transaction){
                $temp_game_record = [];
                $temp_game_record['player_id'] = $this->getPlayerIdByGameUsername($transaction['game_username']);
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }
        $transactions = $temp_game_records;
    }
    
    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['game_english_name'])) {
            $bet_details['game_name'] = $row['game_english_name'];
        }
        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
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

    public function getLauncherLanguage($language){
		switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
            case "en-us":
                return "en";
                break;
            case Language_function::INT_LANG_CHINESE:
             case "zh-cn":
                return "zh-cn";
                break;

            case Language_function::INT_LANG_INDONESIAN:
            case "id-id":
                return "id";
                break;
            case Language_function::INT_LANG_VIETNAMESE:
			case "vi-vn":
            case "vi-vi":
                return "vn";
                break;
            case Language_function::INT_LANG_KOREAN:
            case "ko-kr":
                return "kr";
                break;
            case Language_function::INT_LANG_THAI:
            case "th-th":
                return "th";
                break;
            default:
                return "en";
                break;
        }
    }
        
    public function dumpData($data){
        print_r(json_encode($data));exit;
    }

}

/*end of file*/