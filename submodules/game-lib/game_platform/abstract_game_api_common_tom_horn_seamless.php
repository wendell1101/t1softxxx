<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * TOM HORN Seamless Integration
 * OGP-35064
 * ? use tom_horn_seamless_service_api for service API
 * 
 * Game Platform ID: 6600
 *
 */

abstract class abstract_game_api_common_tom_horn_seamless extends Abstract_game_api {

    public  $URI_MAP, 
            $METHOD_MAP, 
            $SIGN_REQUIRED_PARAMS_ORDER,
            $url, 
            $method, 
            $currency, 
            $language, 
            $force_lang ,
            $enable_merging_rows,
            $lobby_url,
            $use_monthly_transactions_table,
            $original_transactions_table,
            $conversion_rate,
            $allow_invalid_sign,
            $enable_hint,
            $partner_id,
            $secret_key,
            $demo_url,
            $previous_transactions_table
           ;

    const POST                       = 'POST';
    const GET                        = 'GET';
    const API_GetGameLaunchToken     = 'GetGameLaunchToken';
    const API_AssignPlayerToCampaign = 'API_AssignPlayerToCampaign';

    const MD5_FIELDS_FOR_MERGE = [
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'bet_amount',
        'win_amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','game_description_model'));

    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame              => self::GET,                    
            self::API_queryDemoGame                 => self::GET,   
            self::API_GetGameLaunchToken            => self::POST,             
            self::API_queryGameListFromGameProvider => self::POST,       
            self::API_createPlayer                  => self::POST,
            self::API_createFreeRoundBonus          => self::POST,
            self::API_AssignPlayerToCampaign        => self::POST
        ); 

        $this->url                              = $this->getSystemInfo('url',null);
        $this->currency                         = $this->getSystemInfo('currency',null);
        $this->language                         = $this->getSystemInfo('language',null);
        $this->force_lang                       = $this->getSystemInfo('force_lang', false);
        $this->enable_merging_rows              = $this->getSystemInfo('enable_merging_rows', false);
        $this->lobby_url                        = $this->getSystemInfo('lobby_url', null);
        $this->use_monthly_transactions_table   = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->conversion_rate                  = $this->getSystemInfo('conversion_rate', 100); #multiply by 100 as default (as per API docs)
        $this->allow_invalid_sign               = $this->getSystemInfo('allow_invalid_sign', false);
        $this->enable_hint                      = $this->getSystemInfo('enable_hint', false);
        $this->partner_id                       = $this->getSystemInfo('partner_id', false);
        $this->secret_key                       = $this->getSystemInfo('secret_key', false);
        $this->demo_url                         = $this->getSystemInfo('demo_url', false);
        $this->previous_transactions_table      = $this->getSystemInfo('previous_transactions_table', null);

        $this->URI_MAP = [
            self::API_GetGameLaunchToken            => '/services/gms/RestCustomerIntegrationService.svc/GetGameLaunchToken',
            self::API_queryForwardGame              => '/Integration/'.$this->partner_id.'/GameLauncher',
            self::API_queryDemoGame                 => '',
            self::API_queryGameListFromGameProvider => '/services/gms/RestCustomerIntegrationService.svc/GetGameModules',
            self::API_createPlayer                  => '/services/gms/RestCustomerIntegrationService.svc/CreateIdentity',
            self::API_createFreeRoundBonus          => '/services/gms/RestCustomerIntegrationService.svc/CreateCampaign',
            self::API_AssignPlayerToCampaign        => '/services/gms/RestCustomerIntegrationService.svc/AssignPlayerToCampaign'
            
        ];

        $this->SIGN_REQUIRED_PARAMS_ORDER = [
            'Withdraw' => ['partnerID', 'name', 'amount', 'currency', 'reference', 'sessionID', 'gameRoundID', 'gameModule', 'fgbCampaignCode' ],
            'Deposit' => ['partnerID', 'name', 'amount', 'currency', 'reference', 'sessionID', 'gameRoundID', 'gameModule', 'type','fgbCampaignCode','isRoundEnd' ],
            'GetBalance' => ['partnerID', 'name', 'currency','sessionID','gameModule', 'type'],
            'RollbackTransaction' => ['partnerID', 'name', 'reference', 'sessionID']
        ];
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return TOM_HORN_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function getGameLaunchToken($playerName, $extra = null){
        $this->utils->debug_log("TOM HORN SEAMLESS: (getGameLaunchToken)",$playerName , $extra);
        $apiName = self::API_GetGameLaunchToken;
        $gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameLaunchToken',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'partnerID' => $this->partner_id,
            'name' => $gameUsername,
            'currency' => $this->currency,
        ];

        $params['sign'] = $this->generateSign($params);

        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForGetGameLaunchToken($params){
        $resultArr          = $this->getResultJsonFromParams($params);
        $responseResultId   = $this->getResponseResultIdFromParams($params);
        $gameUsername       = @$this->getVariableFromContext($params, 'gameUsername');
		$success            = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result             = [];

        $this->utils->debug_log("TOME HORN SEAMLESS: (processResultForGetGameLaunchToken)", $resultArr);
        if($success){
            $result['SecurityToken'] = $resultArr['Session']['SecurityToken'];
        }
        return array(true, $result);
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
        
		$success = false;
		if(isset($resultArr['Code']) && $resultArr['Code'] == 0){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('TOM HORN got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

    function generateSign($params, $method = null) {
        $concatenatedString = '';
        
        $requiredOrder = isset($this->SIGN_REQUIRED_PARAMS_ORDER[$method]) ? $this->SIGN_REQUIRED_PARAMS_ORDER[$method] : null;
        if ($requiredOrder !== null) {
            # Concatenate parameters in the specified order, with 'amount' formatted to 2 decimal places
            foreach ($requiredOrder as $key) {
                if (isset($params[$key]) && $key !== 'sign') {
                    $value = $key === 'amount' ? number_format((float)$params[$key], 2, '.', '') : $params[$key];
                    $concatenatedString .= $value;
                }
            }
    
            // # Append any parameters not specified in the required order
            // foreach ($params as $key => $value) {
            //     if (!in_array($key, $requiredOrder) && $key !== 'sign') {
            //         $concatenatedString .= $value;
            //     }
            // }
        } else {
            # Concatenate parameters in their original order if no specific order is given
            foreach ($params as $key => $value) {
                if ($key !== 'sign') {
                    $concatenatedString .= $key === 'amount' ? number_format((float)$value, 2, '.', '') : $value;
                }
            }
        }
        
        # Generate and return the HMAC-SHA256 hash in uppercase
        return strtoupper(hash_hmac('sha256', pack('A*', $concatenatedString), pack('A*', $this->secret_key)));
    }

    public function getPreviousTableName(){
        $d = new DateTime('-1 month');
        $monthStr = $d->format('Ym');

        if(!empty($this->previous_transactions_table)){
            return $this->previous_transactions_table;
        }

        return $this->initGameTransactionsMonthlyTableByDate($monthStr);
    }
    
    public function queryForwardGame($playerName, $extra = null)
    {   
        $this->utils->debug_log("TOM HORN SEAMLESS: (queryForwardGame)",$playerName, $extra);
        $language = isset($this->language) ? $this->language : $extra['language'];
        $params = [
            'gameId'        =>  !empty($extra['game_code']) ? $extra['game_code'] : '_weblobby_',
            'lang'          =>  $this->getLauncherLanguage($language),
            'currency'      =>  $this->currency,
            'clientrender'  =>  'iframe',
        ];

        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo')){
            #demo
            $apiName = self::API_queryDemoGame;
            $this->url = $this->demo_url;
        }else{
            #real
            $game_launch_token = $this->getGameLaunchToken($playerName, $extra);
            $gameUsername           = $this->getGameUsernameByPlayerUsername($playerName);
            $apiName                = self::API_queryForwardGame;
            $params['moneyType']    = 1; #1=real
            $params['playerId']     = $gameUsername;
            $params['token']        = isset($game_launch_token['SecurityToken']) ? $game_launch_token['SecurityToken'] : null;
        }

        if(!empty($extra['extra']['home_link'])){
            $params['exitUrl'] = $extra['extra']['home_link'];
        }

        if($extra['extra']['disable_home_link']){
            unset($params['exitUrl']);
        }

        $result = [
            'success'   => true,
            'url'       => $this->generateUrl($apiName, $params)
        ];

        return $result;
    }

    protected function customHttpCall($ch, $params) {	
        $headers = array(
            'Content-Type: application/json'
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

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("TOM HORN SEAMLESS: (createPlayer)");
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        # create player on game provider auth
        parent::createPlayer($playerName, $playerId, $password, $email, $extra); 

        $context = array (
			'callback_obj'      => $this,
			'callback_method'   => 'processResultForCreatePlayer',
			'gameUsername'      => $gameUsername,
			'playerName'        => $playerName,
			'playerId'          => $playerId,
		);

		$params = array (
			"partnerID" => $this->partner_id,
			"name" => $gameUsername,
			"currency" => $this->currency
		);

        $params['sign'] = $this->generateSign($params);

        $return = $this->callApi(self::API_createPlayer, $params, $context);

        $success = false;
        $message = "Unable to create account for TOM HORN seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for TOM HORN seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function processResultForCreatePlayer($params){
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $result = [];

        if ($success) {
            $result = !empty($result_arr) ? $result_arr : [];
        }

        return array($success, $result);
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
        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        
        $this->CI->utils->debug_log("TOM HORN SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('TOM HORN-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time){
            $sqlTime='`original`.`transaction_date` >= ? AND `original`.`transaction_date` <= ?';
        }

        $trans_type = "original.trans_type in ('Withdraw')";
        if(!$this->enable_merging_rows){
            $trans_type = "original.trans_type in ('Withdraw','Deposit', 'RollbackTransaction')";
        }
      
        $this->CI->utils->debug_log('TOM HORN SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.win_amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.reference as transaction_id,
    original.game_username as player_username,
    original.round_id,
    original.amount,
    original.bet_amount,
    original.win_amount,
    original.game_module as game_code,
    original.currency,
    original.trans_type,
    original.status,
    original.transaction_date,
    original.extra_info,

    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,
    gd.id as game_description_id,
    gd.game_type_id,

    MD5(CONCAT({$md5Fields})) as md5_sum

FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_module = gd.external_game_id AND gd.game_platform_id = ?

WHERE 
{$trans_type} AND original.game_platform_id = ? AND
{$sqlTime};
EOD;
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('TOM HORN-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('TOM HORN SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }
        $result_amount = $row['win_amount'] - $row['bet_amount'];
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_code']
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
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['transaction_date'],
                'end_at'                => $row['updated_at'],
                'bet_at'                => $row['transaction_date'],
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

        $this->utils->debug_log('TOM HORN ', $data);
        return $data;

    }

     /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if($this->enable_merging_rows){ 
            $row['after_balance']   = $row['after_balance'] + $row['win_amount'];
        }else{
            if($row['trans_type'] == 'Withdraw'){
                $row['win_amount'] = 0; 
            }else{
                $row['bet_amount'] = 0;
            }
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_name = $row['game_code'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
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

    public function queryTransactionByDateTime($startDate, $endDate){

$transTable = $this->getTransactionsTable();

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.transaction_date,
t.balance_adjustment_amount as amount,
t.after_balance,
t.before_balance,
t.reference as transaction_id,
t.round_id,
t.external_uniqueid as external_uniqueid,
t.trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount,  
t.extra_info as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('TOM HORN SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
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

    public function generateUrl($apiName,$params){
        $this->CI->utils->debug_log('TOM HORN SEAMLESS (generateUrl)', $apiName, $params);		
		$url            = $this->url;
		$this->method   = $this->METHOD_MAP[$apiName];

        if(isset($this->URI_MAP[$apiName])){
            $url = $url.$this->URI_MAP[$apiName];
        }

        if(!empty($params)){
            $url = $url.'?'. http_build_query($params);
        }

		$this->CI->utils->debug_log('TOM HORN SEAMLESS (generateUrl) :', $this->method, $url);
		return $url;
    }

    public function getPlayerBalanceById($player_id){
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());
        return $this->dBtoGameAmount($balance);
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like tom_horn_seamless_wallet_transactions');

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
            default: 
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $transTable = $this->getTransactionsTable(); 
        $pendingStatus = Game_logs::STATUS_PENDING;
        $transType = 'bet';

        $sql = <<<EOD
SELECT 
original.round_id as round_id, original.tx_id as transaction_id, game_platform_id
from {$transTable} as original
where
original.status=?
and original.trans_type=?
and {$sqlTime}
EOD;

        $params=[
            $pendingStatus,
            $transType,
            $dateFrom,
            $dateTo
		];

        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('TOM HORN SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $transTable = $this->getTransactionsTable(); 

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.trans_type as transaction_type,
original.status as status,
original.game_platform_id,
original.player_id,
original.round_id as round_id,
original.tx_id as transaction_id,
ABS(SUM(original.bet_amount)) as amount,
ABS(SUM(original.bet_amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_uniqueid
from {$transTable} as original
left JOIN game_description as gd ON original.product_id = gd.external_game_id and gd.game_platform_id=?
where
original.round_id=? and original.tx_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = Game_logs::STATUS_PENDING;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;
                
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('TOM HORN SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $transTable = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
original.status as status
FROM {$transTable} as original
WHERE
original.game_platform_id=? AND original.external_uniqueid=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$trans['status']);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }


    public function queryGameListFromGameProvider($extra = []) {
        $get_request = $this->CI->input->get();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];
        
        $params = [
            "partnerID"   => $this->partner_id
        ];

        $params['sign'] = $this->generateSign($params);

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $result = [];

        if ($success) {
            $result = !empty($result_arr) ? $result_arr : [];
        }

        return array($success, $result);
    }

    public function createFreeRound($playerName, $extra = []) {
        return $this->CreateFreeGameBonus($playerName, $extra);
    }

    public function checkRequiredParameters($extra, $required_parameters) {
        foreach ($required_parameters as $param) {
            if (!property_exists($extra, $param) || empty($extra->$param)) {
                return false;
            }
        }
        
        return true;
    }

    public function CreateFreeGameBonus($playerName, $extra = null) {
        #request custom validation
        $success = true;
        $message = '';
        $required_parameters = [
            'player_username', 
            'campaignName', 
            'module', 
            'gamesPerPlayer',
            'timeFrom',
            'timeTo'
        ];
        $is_parameters_valid = $this->checkRequiredParameters($extra, $required_parameters);
        
        if(!$is_parameters_valid){
            $success = false;
            $message = 'Invalid parameters';
        }

        $player_username_array = explode(',',$extra->player_username);
        $game_usernames = [];
        foreach($player_username_array as $username){
            $game_username  = $this->getGameUsernameByPlayerUsername(trim($username));
            if(!$game_username){
                $success = false;
                $message = 'Player not found: '.$username;
                break;
            }

            $game_usernames[] = $game_username;
        }

        if(!$success){
            return ['success' => false, 'message' => $message];
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeGameBonus',
        );

        $params = array(
            'partnerID'     => $this->partner_id,
            'campaignName'  => $extra->campaignName,
            'module'        => $extra->module,
            'currency'      => $this->currency,
            'gamesPerPlayer'=> $extra->gamesPerPlayer,
            'timeFrom'      => $this->formatDate($extra->timeFrom, 'Y-m-d\TH:i:s'),
            'timeTo'        => $this->formatDate($extra->timeTo, 'Y-m-d\TH:i:s'),
        );

        $params['sign'] = $this->generateSign($params);

        $result =  $this->callApi(self::API_createFreeRoundBonus, $params, $context);

        $this->utils->debug_log("TOM HORN SEAMLESS: (CreateFreeGameBonus)",$playerName , $extra, $result);

        if(isset($result['success']) && $result['success']){
            #assign player to created campaign
            $assign_player_to_campaign_data = [
                'campaignCode' =>  $result['Campaign']['Code'],
                'player_username' => implode(';',$game_usernames)
            ];

            $assign_player_to_campaign_result = $this->AssignPlayerToCampaign($assign_player_to_campaign_data);
            $success = $assign_player_to_campaign_result['success'];
            $message = isset($assign_player_to_campaign_result['Message']) ? $assign_player_to_campaign_result['Message'] : 'Error';

            if(isset($assign_player_to_campaign_result['success']) && $assign_player_to_campaign_result['success']){
                $this->CI->load->model(array('free_round_bonus_model'));
                #insert data to db

                foreach($game_usernames as $game_username){
                    $player_id  = $this->getPlayerIdByGameUsername($game_username);
                    $data = [
                        'player_id'         => $player_id,
                        'game_platform_id'  => $this->getPlatformCode(),
                        'free_rounds'       => $extra->gamesPerPlayer,
                        'transaction_id'    => $result['Campaign']['Code'], 
                        'currency'          => $this->currency,
                        'expired_at'        => $params['timeTo'],
                        'extra'             => $extra #request params made on dev function
                    ];
        
                    $this->CI->free_round_bonus_model->insertTransaction($data);
                }
            }

        }else{
            $success = false;
            $message = isset($result['Message']) ? $result['Message'] : 'Error';
        }

        return [
            'success' => $success,
            'message' => $message
        ]; 
    }

    public function processResultForCreateFreeGameBonus($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $resultArr['success'] = $success;
        return [$success,$resultArr];
        
    }

    public function AssignPlayerToCampaign($data){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAssignPlayerToCampaign',
        );

        $params = array(
            'partnerID'   => $this->partner_id,
            'players'     => $data['player_username'],
            'campaignCode'=> $data['campaignCode'],
            'currency'    => $this->currency,
        );

        $params['sign'] = $this->generatesign($params);

        return $this->callApi(self::API_AssignPlayerToCampaign, $params, $context);
    }


    public function processResultForAssignPlayerToCampaign($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $resultArr['success'] = $success;
        return [$success,$resultArr];
        
    }

    public function formatDate($date_string, $format) {
        $date = new DateTime($date_string, new DateTimeZone('Asia/Manila')); // Set the input timezone to PHT
        $date->setTimezone(new DateTimeZone('UTC')); // Convert to UTC
        $formattedDate = $date->format($format); // Format the date
        return $formattedDate;
    }
}  
/*end of file*/