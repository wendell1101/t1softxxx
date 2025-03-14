<?php

use function GuzzleHttp\Psr7\build_query;

require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Creedroomz Seamless Integration
 * OGP-33243
 * ? uses creedroomz_seamless_service_api for its service API
 *
 * Game Platform ID: 6404
 *
 */

abstract class Abstract_game_api_common_creedroomz_seamless extends Abstract_game_api
{    
    # Fields in creedroomz_seamless_wallet_transactiosn we want to detect changes for update
	const MD5_FIELDS_FOR_ORIGINAL = [
		'bet_amount',
		'result_amount',
		'payout_amount',
		'win_amount',
		'valid_amunt',
		'withdraw_amount',
		'deposit_amount',
		'status',
		'game_id',
	];

	# Values of these fields will be rounded when calculating MD5
	const MD5_FLOAT_AMOUNT_FIELDS = [
		'bet_amount',
		'result_amount',
		'payout_amount',
		'win_amount',
		'valid_amount',
		'withdraw_amount',
		'deposit_amount',
	];

	# Fields in game_logs we want to detect changes for merge and when md5_sum
	const MD5_FIELDS_FOR_MERGE = [
		'bet_amount',
		'before_balance',
		'after_balance',
		'result_amount',
		'payout_amount',
		'status',
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'bet_amont',
		'result_amount',
	];

    const GET = 'GET';
    const POST = 'POST';
    const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
    const STATUS_CODE_SUCCESS = 200;


    public $URI_MAP = [
        self::API_queryForwardGame => "/authorization.php",
        self::API_queryGameListFromGameProvider => '/casino/getGames'
    ];
    
    const DEVICE_TYPE_WEB = 1;
    const DEVICE_TYPE_MOBILE_WEB = 2;
    const DEVICE_TYPE_IOS = 3;
    const DEVICE_TYPE_ANDROID = 4;

    public $operator_id;
    public $public_key;
    public $country;
    public $force_disable_home_link;
    public $use_third_party_token;
    public $enable_merging_rows;
    public $use_monthly_transactions_table;
    public $allowed_days_to_check_previous_monthly_table;
    public $force_check_other_transaction_table;
    public $game_list_url;
    public $api_url;
    public $return_url;
    public $deposit_url;
    public $game_list_api_url;
    public $currency;
    public $language;
    public $force_language;
    public $original_transactions_table;
    public $method;
    public $launcher_mode;
    public $enable_skip_validate_public_key;
    public $enable_skip_validate_public_key_player_list;

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->game_list_api_url = $this->getSystemInfo('game_list_api_url', 'https://www.cmsbetconstruct.com/');
        $this->return_url = $this->getSystemInfo('return_url');
        $this->deposit_url = $this->getSystemInfo('deposit_url', '');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');
        $this->force_language = $this->getSystemInfo('force_language', '');

        $this->original_transactions_table = 'creedroomz_seamless_wallet_transactions';

        $this->operator_id = $this->getSystemInfo('operator_id', null);
        $this->public_key = $this->getSystemInfo('public_key', "");
        $this->country = $this->getSystemInfo('country', null);

        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        $this->use_third_party_token = $this->getSystemInfo('use_third_party_token', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->allowed_days_to_check_previous_monthly_table = $this->getSystemInfo('allowed_days_to_check_previous_monthly_table', 1);
        $this->force_check_other_transaction_table = $this->getSystemInfo("force_check_other_transaction_table", false);
        $this->game_list_url = $this->getSystemInfo("game_list_url", "https://www.cmsbetconstruct.com");
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'singleOnly');

        $this->enable_skip_validate_public_key = $this->getSystemInfo('enable_skip_validate_public_key', false);
        $this->enable_skip_validate_public_key_player_list = $this->getSystemInfo('enable_skip_validate_public_key_player_list', []);
    }


    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return CREEDROOMZ_SEAMLESS_GAME_API;
    }

    public function getCurrency()
    {
        return $this->currency;
    }


    public function generateUrl($apiName, $params)
    {

        $uri = $this->URI_MAP[$apiName];

        $url = $this->api_url . $uri;

        if($apiName == self::API_queryGameListFromGameProvider){
            $url = $this->game_list_api_url . $uri .'?'.http_build_query($params);
        }

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
		switch($this->method){
			case self::METHOD_POST:
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				break;
			case self::METHOD_GET:
				$params=http_build_query($params);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_GET);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;

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
            $this->CI->utils->debug_log('CREEDROOMZ SEAMLESS GAME API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }  


    public function queryGameListFromGameProvider($extra=null){ 
        $this->utils->debug_log("CREEDROOMZ SEAMLESS SEAMLESS: (queryGameList)");   
        $this->method = self::GET;
        $params = [
            'partner_id' => $this->operator_id,
            'count' => 'all'
        ];
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    } 

    public function processResultForQueryGameListFromGameProvider($params){
		$this->CI->utils->debug_log('CREEDROOMZ SEAMLESS SEAMLESS (processResultForQueryForwardGame)', $params);	

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');		
		$resultArr = $this->getResultJsonFromParams($params);   
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = $resultArr;

		return array($success, $result);
	}
    


    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for CREEDROOMZ";
        if ($return) {
            $success = true;
            $message = "Successfull create account for CREEDROOMZ.";
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
        $player_id = $this->getPlayerIdFromUsername($playerName);
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

        $openType = "fun";
        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $openType = "real";
        }
        
        $deviceType = (int)self::DEVICE_TYPE_WEB;
        $isMobile = false;
        if(isset($extra['is_mobile']) && $extra['is_mobile']){
			$deviceType = (int)self::DEVICE_TYPE_MOBILE_WEB;
            $isMobile = true;
		}
        $token = $this->generateToken($player_id);
        $params = array(
            "gameId" => $gameCode,
            "token" => $token,
            "partnerId" => $this->operator_id,
            "language" => $this->selectLanguage($extra),
            "openType" => $openType,
            "devicetypeid" => $deviceType,
            "isMobile" => $isMobile,
            "exitUrl" => $return_url,
            "deposit_url" => isset($this->deposit_url) ? $this->deposit_url : $this->return_url,
        );
        
        if(!isset($gameCode) || empty($gameCode)){
            unset($params['gameId']);
        }

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            if(isset($parmas['exitUrl']) && !empty($params['exitUrl'])){
                unset($params['exitUrl']);
            }
        }

        if($this->force_disable_home_link){
            if(isset($parmas['exitUrl']) && !empty($params['exitUrl'])){
                unset($params['exitUrl']);
            }
        }
        
        $result['url'] = $this->buildLaunchUrl($params);
        $this->CI->utils->debug_log("CREEDROOMZ_SEAMLESS_GAME_API @queryForwardGame buildUrl", $result['url']);
        if($result['url']){
            $savedToken = $this->saveExternalCommonToken($token, $player_id, $gameCode);
            if($savedToken){
                $this->CI->utils->debug_log("@queryForwardGame: external_common_tokens successfully saved, token: ($token)");
            }else{
                $this->CI->utils->debug_log("@queryForwardGame: external_common_tokens failed to save, token: ($token)");
            }
        }
        return ['success'=>true,'url'=>$result['url']];
    }
    private function generateToken($player_id){
        return $this->getPlatformCode().'-'.$this->getPlayerToken($player_id);
    }

    private function saveExternalCommonToken($token, $playerId, $externalGameId=null){
        $this->CI->load->model(['external_common_tokens']);
        if($this->use_third_party_token && $token != null){
            #save token here
            $extra = [
                'game_platform_id' => $this->getPlatformCode(),
                'external_game_id' => $externalGameId
            ];
            $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($playerId, $token, json_encode($extra), $this->getPlatformCode(),$this->currency);
        }
    }

    private function selectLanguage($extra){
        $language = $this->language;
		if(isset($extra['language']) && !empty($extra['language'])){
            $language = $extra['language'];
        }else{
            $language = $this->language;
        }

		if($this->force_language && !empty($this->force_language)){
            return $this->force_language;
        }	
		return $this->getLauncherLanguage($language);
    }

    private function buildLaunchUrl($params){
       $params = http_build_query($params);
        return $this->api_url.$this->URI_MAP[self::API_queryForwardGame].'?'.$params;
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

        $this->CI->utils->debug_log("CREEDROOMZ SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("CREEDROOMZ SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        return array_merge($currentTableData, $prevTableData);      
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        if($use_bet_time){
            $sqlTime='`original`.`created_at` >= ? AND `original`.`created_at` <= ?';
        }

        $this->CI->utils->debug_log('CREEDROOMZ SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.game_id', 'original.amount','original.withdraw_amount','original.deposit_amount', 'original.bet_amount', 'original.payout_amount', 'original.valid_amount', 'original.result_amount', 'original.type_id', 'original.status', 'original.before_balance', 'original.after_balance'));


        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.amount,
    original.withdraw_amount,
    original.deposit_amount,
    original.bet_amount,
    original.payout_amount,
    original.valid_amount as valid_bet_amount,
    original.result_amount,
    original.type_id,
    original.game_id,
    original.player_id,
    original.rgs_transaction_id,
    original.rgs_related_transaction_id,
    original.rgs_related_transaction_id  as related_round_id,
    original.rgs_transaction_id as round_id,

    original.currency,
    original.trans_type,
    original.status,
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
original.game_platform_id=? 
AND {$sqlTime}
;
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];
        
		$this->CI->utils->debug_log('CREEDROOMZ_SEAMLES_GAME_API (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        $rlt = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $rlt;    
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('CREEDROOMZ SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $this->CI->utils->debug_log('CREEDROOMZ SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

        $amount = isset($row['amount']) ? $row['amount'] : 0;
        $bet_amount = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
        $valid_bet_amount = isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : 0;
        $payout_amount = isset($row['payout_amount']) ? $row['payout_amount'] : 0;
        $result_amount = isset($row['result_amount']) ? $row['result_amount'] : 0;
        $username = $this->getGameUsernameByPlayerId($row['player_id']);

        $round_id = null;

        if($row['balance_adjustment_method'] == 'credit'){
            $round_id = $row['related_round_id'];
        }else {
            $round_id = $row['round_id'];
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
                'player_username'       => $username
            ],
            'amount_info' => [
                'bet_amount'            => $valid_bet_amount,
                'result_amount'         => $result_amount,
				'bet_for_cashback'      => $valid_bet_amount,
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
                'round_number'          => $round_id,
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
        $this->utils->debug_log('CREEDROOMZ makeParamsForInsertOrUpdateGameLogsRow parameters:', $data);
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
t.player_id,
t.updated_at as transaction_date,
t.result_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.rgs_transaction_id as round_no,
t.rgs_related_transaction_id as related_round_no,
t.external_uniqueid as external_uniqueid,
t.balance_adjustment_method as trans_type,
t.raw_data as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $this->CI->utils->debug_log('CREEDROOMZ SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
        
    public function processTransactions(&$transactions){
        $this->CI->utils->debug_log('CREEDROOMZ process transaction', $transactions);
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
                $temp_game_record['related_round_no'] = $transaction['related_round_no'];
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
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['created_at'])) {
            $bet_details['betting_datetime'] = $row['created_at'];
        }

        return $bet_details;
    }

    public function getLauncherLanguage($language){
		switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
            case "en":
            case "en_us":
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
            case "kr":
            case "ko":
            case "ko-kr":
                return "ko";
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