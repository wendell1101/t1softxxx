<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * HOLI SEAMLESS GAME API INTEGRATION
 * OGP-34612
 * ? use holi_seamless_service_api for service API
 * 
 * Game Platform ID: 6564
 * 
 * Current Games as of 09112024 (integration stage)
 * HOLI Baccarat
 *
 */

abstract class Abstract_game_api_common_holi_seamless extends Abstract_game_api {
    use Year_month_table_module;

    public  $METHOD_MAP, 
            $api_url, 
            $method, 
            $currency, 
            $language, 
            $force_lang ,
            $enable_merging_rows,
            $cid,
            $key,
            $lobby_url,
            $use_monthly_transactions_table,
            $conversion_rate,
            $allow_invalid_signature,
            $brand,
            $mode,
            $api_key,
            $salt,      #property required in api doc
            $player_token
            ;

    // monthly transactions table
    public $initialize_monthly_transactions_table;
    public $force_check_previous_transactions_table;
    public $force_check_other_transactions_table;
    public $previous_table = null;
    public $show_logs;
    public $use_monthly_service_logs_table;
    public $use_monthly_game_logs_table;
    public $original_transactions_table;

    public $tableId;
    public $tableName;
    public $gameType;
    public $enable_skip_validation;
    public $limits = [
        "minBet"    => 0,
        "maxBet"    => 0,
        "currency"  => ""
    ];
    public $jackpot = [
        "date" => '',
        "sum" => 0,
    ];


    const MD5_FIELDS_FOR_MERGE = [
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'bet_amount',
        'win_amount',
    ];


    public $URI_MAP = [
        self::API_queryForwardGame => "/launch"
    ];

    const DEMO                      = 'demo';
    const GET                       = 'GET';
    const POST                      = 'POST';
    const METHOD_GET                = 'GET';
	const METHOD_POST               = 'POST';

    const STATUS_CODE_SUCCESS       = 200;
    const SUCCESS_CODE              = "Succeeded";
    const SUCCESS_MESSAGE           = "success";

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','game_description_model'));

 
        $this->tableId                              = $this->getSystemInfo('tableId',null);
        $this->api_url                              = $this->getSystemInfo('url',null);
        $this->currency                             = $this->getSystemInfo('currency',null);
        $this->brand                                = $this->getSystemInfo('brand', 't1seamless');
        $this->mode                                 = $this->getSystemInfo('mode', 'holi');             # value should be 'iframe' or 'holi'. ( as per API docs)
        $this->language                             = $this->getSystemInfo('language', 'tl');
        $this->force_lang                           = $this->getSystemInfo('force_lang', false);
        $this->enable_merging_rows                  = $this->getSystemInfo('enable_merging_rows', false);
        $this->key                                  = $this->getSystemInfo('key', null);
        $this->lobby_url                            = $this->getSystemInfo('lobby_url', null);
        $this->use_monthly_transactions_table       = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->salt                                 = $this->getSystemInfo('salt', '');
        $this->api_key                              = $this->getSystemInfo('api_key', '');
        $this->original_transactions_table          = $this->getSystemInfo('original_transactions_table', 'holi_seamless_wallet_transactions');
        $this->enable_skip_validation               = $this->getSystemInfo('enable_skip_validation', 'false');

        $this->METHOD_MAP = array(
            self::API_queryForwardGame => self::POST,                   
        ); 

        $this->ymt_init();

    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return HOLI_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function generateUrl($apiName, $params)
    {
        $uri = $this->URI_MAP[$apiName];
        $url = $this->api_url . $uri;
        $this->method = 'POST';
        $this->utils->debug_log("HOLI SEAMLESS GAME API: (generateUrl)", [
            '$uri' => $uri,
            '$this->api_url' => $this->api_url,
            '$url' => $url,
        ]);

        return $url;
    }
    
    public function queryForwardGame($playerName, $extra = null)
    {   

        $this->method       = self::POST;
        $requiredParams     = [ 'tableId', 'playerId', 'playerName', 'currency', 'brand', 'mode' ];
        $player             = $this->CI->player_model->getPlayerByUsername($playerName);
        $player_id          = $player->playerId;
        $this->player_token       = $this->getPlayerToken($player_id);
        $gameUsername       = $this->getGameUsernameByPlayerUsername($playerName);

        $language = isset($this->language) ? $this->language : $extra['language'];
        $isDemo = isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo') ?  true :  false;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'player' => null,
            'player_id' => $player_id,
            'isDemo' => $isDemo,
        ];


        if(!empty($this->lobby_url)){
            $params['lobbyUrl'] = $this->lobby_url;
        }
        
        $params = [
            'tableId'       => $this->tableId,
            'playerId'      => $gameUsername,
            'playerName'    => $playerName,
            'playerToken'   => $this->player_token,
            'currency'      => $this->currency,
            'brand'         => $this->brand,
            'mode'          => $this->mode,
            'lang'          => $this->getLauncherLanguage($language),
        ];


        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params)
	{

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$isDemo = $this->getVariableFromContext($params, 'isDemo');

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			'player' => $gameUsername,
		);

		if($success){
			if(isset($resultArr['link']))
            {
                $url = isset($resultArr['link']) ? $resultArr['link'] : null;
                $data['url'] = $url;
                $data['is_demo'] = $isDemo;

                if(!empty($url) && $isDemo){
                    $url = $this->rebuildLaunchUrl($data);
                }
                $result['url'] = $url;
            }
		}

        $this->utils->debug_log("HOLI SEAMLESS GAME API: (processResultForQueryForwardGame)", [
            '$resultArr'    => $resultArr,
            '$success'      => $success,
        ]);
		return array($success, $result);
	}

    protected function customHttpCall($ch, $params) {	
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Api-Key ' . $this->api_key
        );

		switch ($this->method){
            case self::POST:
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				break;
		}
	
	}

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
    {
        $success = false;
        if ( !empty( $resultArr['link'] ) ) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('HOLI SEAMLESS GAME API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }  

    private function rebuildLaunchUrl($data=[]){
        $url = isset($data['url']) ? $data['url'] : null;
        $isDemo = isset($data['is_demo']) ? $data['is_demo'] : false;

        $urlComponents = parse_url($url);

        parse_str($urlComponents['query'], $queryParams);
        if($isDemo)  $queryParams['token'] = self::DEMO;

        $newQueryString = http_build_query($queryParams);
        return  $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'] . '?' . $newQueryString;
    }

    public function getGameTableList(  ){
        $requiredParams = ['tableId', 'tableName', 'gameType', 'status'];

    }
    

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("HOLI SEAMLESS GAME API: (createPlayer)");

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for HOLI seamless GAME API api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for HOLI seamless GAME API api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){

        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){

        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $original_transactions_table = $this->getTransactionsTable();

        if ($this->use_monthly_transactions_table) {
            $original_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateTo);
        }

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);   
        $this->CI->utils->debug_log("HOLI SEAMLESS GAME API: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($transactionTable, $dateFrom, $dateTo, $use_bet_time) {
        if (empty($transactionTable) || !is_string($transactionTable)) {
            throw new InvalidArgumentException("Transaction table name must be a non-empty string.");
        }
    
        $md5Fields = implode(", ", ['original.bet_amount', 'original.win_amount', 'original.after_balance', 'original.updated_at']);
        $sqlTime = $use_bet_time ? 'original.created_at >= ? AND original.created_at <= ?' : 'original.updated_at >= ? AND original.updated_at <= ?';
        
        // Log the initial parameters and time filter condition
        $this->utils->debug_log('HOLI SEAMLESS GAME API-syncOrig', $transactionTable, $dateFrom, $dateTo);
        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API GAME sqlTime', $sqlTime);
        
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
    
        // Query main and previous tables
        $rlt = $this->executeGameLogsQuery($transactionTable, $md5Fields, $sqlTime, $params);
        if ($this->use_monthly_transactions_table && empty($rlt) && 
            $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            $rlt = $this->executeGameLogsQuery($this->previous_table, $md5Fields, $sqlTime, $params);
        }
    
        return $rlt;
    }
    
    // Helper function to execute game logs query
    private function executeGameLogsQuery($tableName, $md5Fields, $sqlTime, $params) {
$sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.transaction_id as transaction_id,
    original.amount,
    original.bet_amount,
    original.win_amount,
    original.game_type as game_code,
    original.game_type,
    original.currency,
    original.trans_type,
    original.status,
    original.extra_info,
    original.round_id,
    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,
    gpa.login_name as player_username,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id,
    gd.english_name as game_english_name,
    gt.game_type
FROM {$tableName} as original
LEFT JOIN game_description as gd ON original.game_type = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
LEFT JOIN game_provider_auth as gpa ON original.player_id = gpa.player_id AND gpa.game_provider_id = ?
WHERE {$sqlTime};
EOD;
    
        // Log SQL and parameters for debugging
        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API-syncSQL', $sql, 'params', $params);
    
        // Execute query and log the result
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API-rlt', $result);
        
        return $result;
    }
    
    

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        $win_amount = isset($row['win_amount']) ? $row['win_amount'] : 0;
        $bet_amount = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
        $result_amount = $win_amount - $bet_amount;

        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API (makeParamsForInsertOrUpdateGameLogsRow)', [
            '$row' => $row,
            '$win_amount' => $win_amount,
            '$bet_amount' => $bet_amount,
            '$result_amount' => $result_amount,
        ]);

        
        if(isset($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }


        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_english_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $result_amount,
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => $win_amount,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
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
                'bet_type'              => null,
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('HOLI SEAMLESS GAME API data ', $data);
        return $data;

    }

     /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){

        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API (preprocessOriginalRowForGameLogs)', 'before-row', $row);

        $game_code =  $row['game_code'];
        $game_desc = $this->CI->game_description_model->getGameDescByGameCode($game_code, $this->getPlatformCode());
        if(!empty($game_desc)){
            $row['game_description_id']     = isset($game_desc['id']) ? $game_desc['id'] : null;
            $row['game_type_id']            = isset($game_desc['game_type_id']) ? $game_desc['game_type_id'] : 'unknown';
            $row['game_english_name']       = isset($game_desc['english_name']) ? $game_desc['english_name'] : 'unknown';
        }

        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if($this->enable_merging_rows){ 
            $row['after_balance']   = $row['after_balance'] + $row['win_amount'];
        }else{
            if($row['trans_type'] == 'Debit'){
                $row['win_amount'] = 0; 
            }else{
                $row['bet_amount'] = 0;
            }
        }

        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API (preprocessOriginalRowForGameLogs)', 'after-row', $row);

    }

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

    public function formatBetDetails($details){
        $bet_details = [];
        if($details){
            $bet_details = [
                'bet_id'                => $details['transaction_id'],
                'action'                => $details['trans_type'],
                'bet_amount'            => $details['bet_amount'],
                'win_amount'            => $details['win_amount'],
                'settlement_datetime'   => $details['updated_at'],
            ];
        }
        return $bet_details;
    }

    public function queryTransactionByDateTime($startDate, $endDate) {
        // Determine which transaction table to use
        $transTable = $this->use_monthly_transactions_table 
            ? $this->ymt_get_year_month_table_by_date(null, $startDate) 
            : $this->getTransactionsTable();
    
        if (empty($transTable)) {
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }
    
        // Define query parameters
        $params = [$this->getPlatformCode(), $startDate, $endDate];
    
        // Execute the query for the main table
        $result = $this->executeTransactionQuery($transTable, $params);
    
        // If using monthly tables, check the previous year/month table if needed
        if ($this->use_monthly_transactions_table && empty($result) && 
            $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            $result = $this->executeTransactionQuery($this->previous_table, $params);
        }
    
        return $result;
    }
    
    // Helper function to execute transaction query
    private function executeTransactionQuery($tableName, $params) {
$sql = <<<EOD
SELECT
    t.player_id as player_id,
    t.updated_at as transaction_date,
    t.balance_adjustment_amount as amount,
    t.after_balance,
    t.before_balance,
    t.transaction_id as transaction_id,
    t.round_id as round_no,
    t.round_id as round_id,
    t.external_uniqueid as external_uniqueid,
    t.trans_type,
    t.balance_adjustment_method,
    t.balance_adjustment_amount,  
    t.extra_info as extra_info
FROM {$tableName} as t
WHERE t.game_platform_id = ? AND t.updated_at >= ? AND t.updated_at <= ?
ORDER BY t.updated_at ASC;
EOD;
    
        // Log SQL and parameters for debugging
        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API (queryTransactionByDateTime)', 'sql', $sql, 'params', $params);
    
        // Execute the query and return the result
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API (queryTransactionByDateTime) Result', $result);
    
        return $result;
    }
    

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record                       = [];
                $temp_game_record['player_id']          = $transaction['player_id'];
                $temp_game_record['game_platform_id']   = $this->getPlatformCode();
                $temp_game_record['transaction_date']   = $transaction['transaction_date'];
                $temp_game_record['amount']             = abs($transaction['amount']);
                $temp_game_record['before_balance']     = $transaction['before_balance'];
                $temp_game_record['after_balance']      = $transaction['after_balance'];
                $temp_game_record['round_no']           = $transaction['round_id'];
                $extra                                  = [];
                $extra['trans_type']                    = $transaction['trans_type'];
                $temp_game_record['extra_info']         = json_encode($extra);
                $temp_game_record['external_uniqueid']  = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type']  = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getPlayerBalanceById($player_id){

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());

        $dbGameToAmount = $this->dBtoGameAmount($balance);

        $this->CI->utils->debug_log('HOLI SEAMLESS GAME API (getPlayerBalanceById)', [
            'CALL'              => "getMainWalletBalance",
            'dbGameToAmount'    => $dbGameToAmount,
            'balance'           => $balance
        ]);

        return $dbGameToAmount;
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like holi_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}
		return $tableName;
	}

    public function getLauncherLanguage($language){
        $language = strtolower($language);
        $lang='';
        switch ($language) {
            case 'en':
            case 'en-us':
            case 'en-US':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en';
                break;
            case 'pt':
            case 'pt-br':
            case 'pt-BR':
            case 'pt-pt':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'pt';
                break;
            case 'hi':
            case 'hi-hi':
            case 'hi-in':
            case 'hi-IN':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'hi';
                break;
            case 'ko':
            case 'ko-ko':
            case 'ko-kr':
            case 'ko-KR':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'ko';
                break;
            case 'tl':
            case LANGUAGE_FUNCTION::INT_LANG_FILIPINO:
                    $lang = 'tl';
                    break;
            default: 
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', true);
        $this->use_monthly_game_logs_table = $this->getSystemInfo('use_monthly_game_logs_table', false);

        $this->ymt_initialize($this->original_transactions_table, $this->use_monthly_transactions_table ? $this->use_monthly_transactions_table : $this->initialize_monthly_transactions_table);

        if ($this->use_monthly_transactions_table) {
            $this->original_transactions_table = $this->ymt_get_current_year_month_table();
            $this->previous_table = $this->ymt_get_previous_year_month_table();
        }

        // end monthly tables
    }

}  
/*end of file*/