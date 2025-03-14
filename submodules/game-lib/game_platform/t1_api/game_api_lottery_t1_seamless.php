<?php
require_once dirname(__FILE__) . '/game_api_lottery_t1.php';
/*
*
*/

class Game_api_lottery_t1_seamless extends Game_api_lottery_t1 {

    const ORIGINAL_GAME_LOGS = 't1lottery_seamless_game_logs';
    const ORIGINAL_TRANSACTIONS = 't1lottery_transactions';

    const TRANSTYPE_BET = 'bet';
    const TRANSTYPE_PAYOUT = 'payout';
    const TRANSTYPE_SETTLE = 'settle';
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','game_code'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance'];

    const ODDS_MAP = [
        'red' => 2,
        'black' => 2,
        'white' => 14
    ];

	const API_triggerInternalPayoutRound = 'triggerInternalPayoutRound';
    const API_triggerInternalBetRound = 'triggerInternalBetRound';
    const API_triggerInternalRefundRound = 'triggerInternalRefundRound';

    ############### SEAMLESS SERVICE API CODES ###################  
    const SUCCESS = '0x00';    
    const ERROR_SERVICE_NOT_AVAILABLE = '0x01';
	const ERROR_INVALID_SIGN = '0x02';
	const ERROR_INVALID_PARAMETERS = '0x03';
	const ERROR_INSUFFICIENT_BALANCE = '0x04';
	const ERROR_SERVER = '0x05';
	const ERROR_CANNOT_FIND_PLAYER = '0x06';
	const ERROR_TRANSACTION_ALREADY_EXIST = '0x07';
	const ERROR_IP_NOT_ALLOWED = '0x08';
	const ERROR_BET_DONT_EXIST = '0x09';	
	const ERROR_GAME_UNDER_MAINTENANCE = '0x10';
	const ERROR_CONNECTION_TIMED_OUT = '0x11';
	const ERROR_REFUND_PAYOUT_EXIST = '0x12';	

	
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_SERVICE_NOT_AVAILABLE=>404,		
		self::ERROR_INVALID_SIGN=>400,
		self::ERROR_INVALID_PARAMETERS=>400,
		self::ERROR_INSUFFICIENT_BALANCE=>406,
		self::ERROR_SERVER=>500,
		self::ERROR_CANNOT_FIND_PLAYER=>400,	
		self::ERROR_TRANSACTION_ALREADY_EXIST=>409,	
		self::ERROR_IP_NOT_ALLOWED=>401,
		self::ERROR_BET_DONT_EXIST=>400,
		self::ERROR_REFUND_PAYOUT_EXIST=>400,
		self::ERROR_GAME_UNDER_MAINTENANCE=>503,
		self::ERROR_CONNECTION_TIMED_OUT=>423,
	];
    ##############################################################

    public function getPlatformCode(){
        return T1LOTTERY_SEAMLESS_API;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function generateUrl($apiName, $params) {
        # generate signature
        $params['sign'] = $this->generateSignatureByParams($params);
        
        $apiUri = '';
        if(array_key_exists($apiName,self::URI_MAP)){
            $apiUri = self::URI_MAP[$apiName];
        }

        if (self::METHOD_POST == $this->method) {
            $url = $this->api_url .$this->game_api_uri.'/'. $apiUri;
            if($apiName==self::API_createPlayer || $apiName==self::API_queryForwardGame || $apiName==self::API_queryGameInfo){
                $url = $this->api_url .$this->game_api_uri.'/seamless/'. $apiUri;
            }
        }else{
            $url = $this->api_url .$this->game_api_uri.'/'. $apiUri . '?' . http_build_query($params);
        }
        
        if($apiName==self::API_triggerInternalPayoutRound){
			$url = $this->payout_callback_url;
		}
        
        if($apiName==self::API_triggerInternalBetRound){
			$url = $this->bet_callback_url;
		}
        
        if($apiName==self::API_triggerInternalRefundRound){
			$url = $this->refund_callback_url;
		}

        $this->CI->utils->debug_log('apiName', $apiName, 'url', $url);
        $this->CI->utils->debug_log('====================params', $params);
        return $url;
    }

    public function __construct($args){
        parent::__construct($args);        
        $this->CI->load->model(['common_token']);

        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        $this->game_api_uri = $this->getSystemInfo('game_api_uri', '/gameapi/v1');

        //FOR TESTING
        $this->trigger_bet_error_response = $this->getSystemInfo('trigger_bet_error_response', 0);
        $this->trigger_payout_error_response = $this->getSystemInfo('trigger_payout_error_response', 0);
        $this->trigger_refund_error_response = $this->getSystemInfo('trigger_refund_error_response', 0);
        $this->trigger_player_info_error_response = $this->getSystemInfo('trigger_player_info_error_response', 0);
        $this->trigger_settle_error_response = $this->getSystemInfo('trigger_settle_error_response', 0);

        //token ecryption
        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');

        $this->payout_callback_url      = $this->getSystemInfo('payout_callback_url', '');
        $this->bet_callback_url      = $this->getSystemInfo('bet_callback_url', '');
        $this->refund_callback_url      = $this->getSystemInfo('refund_callback_url', '');
        $this->flag_bet_transaction_settled      = $this->getSystemInfo('flag_bet_transaction_settled', true);
        $this->enable_settle_by_queue      = $this->getSystemInfo('enable_settle_by_queue', true);

        $this->enabled_game_logs_unsettle      = $this->getSystemInfo('enabled_game_logs_unsettle', true);
        $this->opencode_list = [];

        $this->adjust_datetime_minutes_sync_batch_payout      = $this->getSystemInfo('adjust_datetime_minutes_sync_batch_payout', 5);
        //$this->sync_batch_payout_interval      = $this->getSystemInfo('sync_batch_payout_interval', 1);

        $this->enable_mm_channel_nofifications = $this->getSystemInfo('enable_mm_channel_nofifications', false);        
        $this->mm_channel = $this->getSystemInfo('mm_channel', 'test_mattermost_notif');

        $this->enable_use_readonly_in_get_balance = $this->getSystemInfo('enable_use_readonly_in_get_balance', false);

        $this->get_after_balance_from_db = $this->getSystemInfo('get_after_balance_from_db', true);
        $this->check_if_balance_changed = $this->getSystemInfo('check_if_balance_changed', false);
        $this->alert_if_balance_not_changed = $this->getSystemInfo('alert_if_balance_not_changed', false);

        $this->request = null;

    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (createPlayer)",$playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $this->current_player_id=$playerId;

        $this->utils->debug_log("T1LOTTERY SEAMLESS: (createPlayer) playerName token",$this->generatePlayerToken($playerName));
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (createPlayer) gameUsername token",$this->generatePlayerToken($gameUsername));

        $api_token=$this->getAvailableApiToken();
        if(empty($api_token)){
            return ['success'=>false, 'error_message'=>'no auth token'];
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
        );

        $params = array(
            'auth_token' => $api_token,
            'merchant_code' => $this->api_merchant_code,
            'username' => $gameUsername
        );

        $this->last_response_result_id=null;
        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function queryGameInfo($params = []) {
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (queryGameInfo)",'params',$params);

        $api_token=$this->getAvailableApiToken();
        if(empty($api_token)){
            return ['success'=>false, 'error_message'=>'no auth token'];
        }

        $gameCode = isset($params['game_code'])?$params['game_code']:null;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameInfo',
            'gameCode' => $gameCode,
        );

        $params = array(
            'auth_token' => $api_token,
            'merchant_code' => $this->api_merchant_code,
            'game_code' => $gameCode
        );

        $this->last_response_result_id=null;
        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_queryGameInfo, $params, $context);
    }

    public function processResultForQueryGameInfo($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameCode = $this->getVariableFromContext($params, 'gameCode');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result=null;
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (processResultForQueryGameInfo)",$gameCode,$resultArr);

        if($success){
            $result = isset($resultArr['detail'])?$resultArr['detail']:null;
        }

        return array($success,$result);
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

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (queryPlayerBalance)");

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function queryForwardGame($playerName, $extra) {
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (queryForwardGame)",$playerName, $extra);
        $api_token=$this->getAvailableApiToken();
        if(empty($api_token)){
            return ['success'=>false, 'url'=>null, 'error_message'=>'no auth token'];
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $this->player_name = $gameUsername;

        $params = array(
            "auth_token" => $api_token,
            "game_code" => $game_code,
            "merchant_code" => $this->api_merchant_code,
            "token" => $this->generatePlayerToken($playerName)
        );

        $this->last_response_result_id=null;
        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId, 'url' => null];
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (processResultForQueryForwardGame)", 'resultArr', $resultArr);

        if($success){
            if(isset($resultArr['detail']) && isset($resultArr['detail']['game_url'])){
                $result['url']=$resultArr['detail']['game_url'];
            }else{
                $success=false;
            }
        }

        return [$success, $result];

    }

    public function generatePlayerToken($playerName){
        $token = $this->encrypt($playerName);
        return $token;
    }

    public function encrypt($data){
        if(is_array($data)){
            $data = json_encode($data);
        }
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_encrypt($data, $this->encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function decrypt($data){
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($data), $this->encrypt_method, $key, 0, $iv);
        return $output;
    }

    public function isBlocked($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        return $this->isBlockedUsernameInDB($gameUsername);
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

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`timestamp_parsed` >= ? AND `original`.`timestamp_parsed` <= ?';
        }
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS sqlTime', $sqlTime, 'table', $table);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.timestamp_parsed', 'original.updated_at'));

        //result amount = win - bet
        $sql = <<<EOD
SELECT
	original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
	original.timestamp_parsed as start_at,
    original.timestamp_parsed as end_at,
    original.timestamp_parsed as bet_at,
    original.updated_at as updated_at,
    original.player_id as player_id,
    original.bet_id as bet_id,
    original.round_id as round,
    original.username as username,
    original.trans_type as trans_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    IF(original.trans_type='bet',original.amount,0) bet_amount,
    IF(original.trans_type='payout',original.amount,0) payout_amount,
    original.`status` `is_settled`,
    original.player_id,
    original.game_code as game,
    original.raw_data,
    original.number,
    original.opencode,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,    
    gd.game_name as game_name,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE (original.trans_type='bet' OR original.trans_type='payout') AND
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('T1LOTTERY SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        

        $prevTableData = $finalData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();
        if(($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table) || $checkOtherTable){
            $prevTable = $this->getTransactionsPreviousTable(); 
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                   
        }
        $finalData = array_merge($currentTableData, $prevTableData);        
        
        return $finalData;
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round'],
            'odds' =>  $this->processOdds($row),
        ];

        $row['result_amount'] = floatval($row['payout_amount']) - floatval($row['bet_amount']);

        if(!isset($row['md5_sum']) || empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
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
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getStatus($row),
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            //'bet_details' => $betDetails,
            'bet_details' => $this->processBetDetails($row),
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function processBetDetails($row){
        $opencode = $row['opencode'];
        $roundId = $row['round'];

        if(empty($opencode) && array_key_exists((string)$roundId,$this->opencode_list)){
            $opencode = $this->opencode_list[$roundId];
        }        
        
        //$prevTranstable = $this->getTransactionsPreviousTable();
        //get opencode if empty
        if(empty($opencode)){
            $this->CI->load->model(array('t1lottery_transactions'));
            $whereParams = ['round_id'=>$roundId, 'trans_type'=>self::TRANSTYPE_SETTLE];
            $settleDatails = $this->CI->t1lottery_transactions->getTransactionByParamsArray($whereParams);
            if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                $this->opencode_list[] = [(string)$roundId=>$settleDatails['opencode']];
                $opencode = $settleDatails['opencode'];                
            }

            //check other table
            /*if(empty($settleDatails)){
                $settleDatails = $this->CI->t1lottery_transactions->getTransactionByParamsArray($whereParams, $prevTranstable);
                if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                    $this->opencode_list[] = [(string)$roundId=>$settleDatails['opencode']];
                    $opencode = $settleDatails['opencode'];                
                }
            }*/
        }            

        $row['opencode'] = $opencode;

        $number = $row['number'];
        if(isset($row['raw_data'])&&!empty($row['raw_data'])){
            $rawDataArr = json_decode($row['raw_data'], true);
            $number = isset($rawDataArr['number'])?$rawDataArr['number']:null;
        }

        $result = [
            'round_id'=>$row['round'],
            'bet_id'=>$row['bet_id'],
            'transaction_type'=>$row['trans_type'],
            'number'=>$number,
            'opencode'=>$row['opencode'],
            'odds'=>$this->processOdds($row)
        ];

        return $result;
    }

    public function processOdds($row){

        $odds = isset($row['opencode'])?$row['opencode']:null;
        $roundId = $row['round'];
        $game_code = $row['game'];
        $key = $game_code.$roundId;

        if(empty($odds) && array_key_exists($key,$this->opencode_list)){
            $odds = $this->opencode_list[$key];
        } 

        //get opencode if empty
        if(empty($odds)){
            $this->CI->load->model(array('t1lottery_transactions'));
            $whereParams = ['round_id'=>$roundId, 'game_code'=>$game_code, 'trans_type'=>self::TRANSTYPE_SETTLE];
            $this->CI->t1lottery_transactions->tableName = $this->getTransactionsTable();
            $settleDatails = $this->CI->t1lottery_transactions->getTransactionByParamsArray($whereParams);

            $checkOtherTable = $this->checkOtherTransactionTable();
            if(empty($settleDatails) && $checkOtherTable){
                $prevTranstable = $this->getTransactionsPreviousTable();
                $settleDatails = $this->CI->t1lottery_transactions->getTransactionByParamsArray($whereParams, $prevTranstable);

            }

            if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                $this->opencode_list[$key] = $settleDatails['opencode'];
                $odds = $settleDatails['opencode'];                
            }
        }  

        if(array_key_exists($odds, self::ODDS_MAP)) {
            return self::ODDS_MAP[$odds];
        }

        return $odds;
    }

    public function getStatus($row){
        $status = Game_logs::STATUS_SETTLED;
        if($row['is_settled']==Game_logs::STATUS_SETTLED || $row['is_settled']==Game_logs::STATUS_SETTLED_NO_PAYOUT) {
            $status = Game_logs::STATUS_SETTLED;
        }elseif($row['is_settled']==Game_logs::STATUS_REFUND) {
            $status = Game_logs::STATUS_REFUND;
        }
        return $status;
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $status = Game_logs::STATUS_SETTLED;
        if($row['is_settled']==Game_logs::STATUS_SETTLED || $row['is_settled']==Game_logs::STATUS_SETTLED_NO_PAYOUT) {
            $status = Game_logs::STATUS_SETTLED;
        }elseif($row['is_settled']==Game_logs::STATUS_REFUND) {
            $status = Game_logs::STATUS_REFUND;
        }
        $row['status'] = $status;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
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

    public function logout($playerName, $password = null) {
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (logout)",$playerName);
        $api_token=$this->getAvailableApiToken();
        if(empty($api_token)){
            return ['success'=>false, 'url'=>null, 'error_message'=>'no auth token'];
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $this->player_name = $gameUsername;

        $params = array(
            "auth_token" => $api_token,
            "merchant_code" => $this->api_merchant_code,
            'game_platform_id' => $this->original_platform_code,
            "username" => $gameUsername
        );

        $this->last_response_result_id=null;
        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (processResultForLogout)", 'params', $params, 'resultArr', $resultArr);

        return [$success, $result];
    }

    public function triggerInternalPayoutRound($transaction){  
        //API_triggerInternalPayoutRound
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (triggerInternalPayoutRound)', 'transaction', $transaction);		
        
        //check if parameters complete
        if(!is_array($transaction)){
            $transaction = json_decode($transaction, true);            
        }

        //check api path
        if(!isset($transaction['apiPath'])){
            return array('success' => false, 'message' => 'Missing apiPath. Check format.');
        }

        //check if apipath contains '/payout'
        $apiPath = @$transaction['apiPath'];
        if(strpos($apiPath, '/payout') !== false){
            
        } else{
            return array('success' => false, 'message' => 'Function accepts only payout calls. Check format. '.$apiPath);
        }

        //check payload
        if(!isset($transaction['payload'])){
            return array('success' => false, 'message' => 'Missing payload. Check format.');
        }

        $payload = @$transaction['payload'];

        if(empty($payload)){
            return array('success' => false, 'message' => 'Invalid payload data.');
        }

        //get game username
        $gameUserName = '';
        if(isset($payload['username']) && !empty($payload['username'])){
            $gameUserName = $payload['username'];            
        }else{
            return array('success' => false, 'message' => 'Missing payload.username.');
        }

        //get complete player info by game username        
        $player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUserName, $this->getPlatformCode());
        if(!isset($player) || empty($player)){
            return array('success' => false, 'message' => 'Cannot find player.');
        }
        if(!isset($player->player_id) || empty($player->player_id)){
            return array('success' => false, 'message' => 'Cannot find player.');
        }        

        //check other data needed if complete
        if(!isset($payload['game_code'])){
            return array('success' => false, 'message' => 'Missing payload.game_code.');
        }

        if(!isset($payload['bet_id'])){
            return array('success' => false, 'message' => 'Missing payload.bet_id.');
        }

        if(!isset($payload['round_id'])){
            return array('success' => false, 'message' => 'Missing payload.round_id.');
        }

        if(!isset($payload['amount'])){
            return array('success' => false, 'message' => 'Missing payload.amount.');
        }

        if(!isset($payload['unique_id'])){
            return array('success' => false, 'message' => 'Missing payload.unique_id.');
        }

        if(!isset($payload['merchant_code'])){
            return array('success' => false, 'message' => 'Missing payload.merchant_code.');
        }

        if(!isset($payload['timestamp'])){
            return array('success' => false, 'message' => 'Missing payload.timestamp.');
        }

        $this->CI->load->model(array('t1lottery_transactions'));
        $this->CI->t1lottery_transactions->tableName = $this->getTransactionsTable();

        //check if there is bet        
        $where = [
            'round_id'=>$payload['round_id'],
            'trans_type'=>'bet',
            'bet_id'=>$payload['bet_id'],
            'player_id'=>$player->player_id,
        ];
        $row = $this->CI->t1lottery_transactions->getTransactionByParamsArray($where);   
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (triggerInternalPayoutRound) existing bet transactions:', $row);		
        if(empty($row)){
            return array('success' => false, 'message' => 'Player bet does not exist.');
        }
        
        //check if already payout
        $where = [
            'round_id'=>$payload['round_id'],
            'trans_type'=>'payout',
            'bet_id'=>$payload['bet_id'],
            'player_id'=>$player->player_id,
        ];
        $row = $this->CI->t1lottery_transactions->getTransactionByParamsArray($where);
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (triggerInternalPayoutRound) existing payout transactions:', $row);		
        if(!empty($row)){
            return array('success' => false, 'message' => 'Payout already processed. Double payout is not allowed.');
        }     
        
        //check if already refunded
        $where = [
            'round_id'=>$payload['round_id'],
            'trans_type'=>'refund',
            'bet_id'=>$payload['bet_id'],
            'player_id'=>$player->player_id,
        ];
        $row = $this->CI->t1lottery_transactions->getTransactionByParamsArray($where);
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (triggerInternalPayoutRound) existing refund transactions:', $row);		
        if(!empty($row)){
            return array('success' => false, 'message' => 'Bet already refunded.');
        }    

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTriggerInternalPayoutRound',
            'gameUsername' => $payload['username'],
			'transaction' => $payload
        );
        
        $params = $payload;

        $this->method = self::METHOD_POST;
		return $this->callApi(self::API_triggerInternalPayoutRound, $params, $context);        
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);		
        
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (processResultForTriggerInternalPayoutRound)', $params, $resultArr);	
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'gameUsername' => $gameUsername,
			'transaction' => $transaction,
			'status' => null,
			'exists' => null,
			'triggered_payout' => false
        );
        $success = false;
        if(isset($resultArr['is_success']) && $resultArr['is_success']==true){      
            $result['status']=true;            
            $result['triggered_payout']=true;
            $success = true;
        }

        $result['message']=isset($resultArr['err_msg'])?$resultArr['err_msg']:'';            

		return array($success, $result);
	}

    public function triggerInternalBetRound($transaction){  
        //API_triggerInternalBetRound
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (triggerInternalBetRound)', 'transaction', $transaction);		
        
        //check if parameters complete
        if(!is_array($transaction)){
            $transaction = json_decode($transaction, true);            
        }

        $payload = $transaction;

        //check payload
        if(isset($transaction['payload'])){
            //check api path
            if(!isset($transaction['apiPath'])){
                return array('success' => false, 'message' => 'Missing apiPath. Check format.');
            }

            //check if apipath contains '/bet'
            $apiPath = @$transaction['apiPath'];
            if(strpos($apiPath, '/bet') !== false){
                
            } else{
                return array('success' => false, 'message' => 'Function accepts only bet calls. Check format. '.$apiPath);
            }

            $payload = $transaction['payload'];
        }
        
        if(empty($payload)){
            return array('success' => false, 'message' => 'Invalid payload data.');
        }

        //get game username
        $gameUserName = '';
        if(isset($payload['username']) && !empty($payload['username'])){
            $gameUserName = $payload['username'];            
        }else{
            return array('success' => false, 'message' => 'Missing payload.username.');
        }

        //get complete player info by game username        
        $player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUserName, $this->getPlatformCode());
        if(!isset($player) || empty($player)){
            return array('success' => false, 'message' => 'Cannot find player.');
        }
        if(!isset($player->player_id) || empty($player->player_id)){
            return array('success' => false, 'message' => 'Cannot find player.');
        }        

        //check other data needed if complete
        if(!isset($payload['game_code'])){
            return array('success' => false, 'message' => 'Missing payload.game_code.');
        }

        if(!isset($payload['bet_id'])){
            return array('success' => false, 'message' => 'Missing payload.bet_id.');
        }

        if(!isset($payload['round_id'])){
            return array('success' => false, 'message' => 'Missing payload.round_id.');
        }

        if(!isset($payload['amount'])){
            return array('success' => false, 'message' => 'Missing payload.amount.');
        }

        if(!isset($payload['unique_id'])){
            return array('success' => false, 'message' => 'Missing payload.unique_id.');
        }

        if(!isset($payload['merchant_code'])){
            return array('success' => false, 'message' => 'Missing payload.merchant_code.');
        }

        if(!isset($payload['timestamp'])){
            return array('success' => false, 'message' => 'Missing payload.timestamp.');
        }

        $this->CI->load->model(array('t1lottery_transactions'));
        $this->CI->t1lottery_transactions->tableName = $this->getTransactionsTable();
        
        //check if already bet
        $where = [
            'round_id'=>$payload['round_id'],
            'trans_type'=>'payout',
            'bet_id'=>$payload['bet_id'],
            'player_id'=>$player->player_id,
        ];
        $row = $this->CI->t1lottery_transactions->getTransactionByParamsArray($where);        
        if(!empty($row)){
            $this->CI->utils->error_log('T1LOTTERY SEAMLESS (triggerInternalBetRound) existing payout transactions:', $row);		
            return array('success' => false, 'message' => 'Bet already processed. Double bet is not allowed.');
        }        

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTriggerInternalBetRound',
            'gameUsername' => $payload['username'],
			'transaction' => $payload
        );
        
        $params = $payload;

        $this->method = self::METHOD_POST;
		return $this->callApi(self::API_triggerInternalBetRound, $params, $context);        
    }

    public function processResultForTriggerInternalBetRound($params){
        $resultArr = $this->getResultJsonFromParams($params);		
        
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (processResultForTriggerInternalBetRound)', $params, $resultArr);	
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'gameUsername' => $gameUsername,
			'transaction' => $transaction,
			'status' => null,
			'exists' => null,
			'triggered_bet' => false
        );
        $success = false;
        if(isset($resultArr['is_success']) && $resultArr['is_success']==true){      
            $result['status']=true;            
            $result['triggered_bet']=true;
            $success = true;
        }

        $result['message']=isset($resultArr['err_msg'])?$resultArr['err_msg']:'';            

		return array($success, $result);
	}

    public function triggerInternalRefundRound($transaction){  
        //API_triggerInternalBetRound
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (triggerInternalBetRound)', 'transaction', $transaction);		
        
        //check if parameters complete
        if(!is_array($transaction)){
            $transaction = json_decode($transaction, true);            
        }

        $payload = $transaction;

        //check payload
        if(isset($transaction['payload'])){
            //check api path
            if(!isset($transaction['apiPath'])){
                return array('success' => false, 'message' => 'Missing apiPath. Check format.');
            }

            //check if apipath contains '/bet'
            $apiPath = @$transaction['apiPath'];
            if(strpos($apiPath, '/refund') !== false){
                
            } else{
                return array('success' => false, 'message' => 'Function accepts only refund calls. Check format. '.$apiPath);
            }

            $payload = $transaction['payload'];
        }
        
        if(empty($payload)){
            return array('success' => false, 'message' => 'Invalid payload data.');
        }

        //get game username
        $gameUserName = '';
        if(isset($payload['username']) && !empty($payload['username'])){
            $gameUserName = $payload['username'];            
        }else{
            return array('success' => false, 'message' => 'Missing payload.username.');
        }

        //get complete player info by game username
        $this->CI->load->model(['common_token']);
        $player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUserName, $this->getPlatformCode());
        if(!isset($player) || empty($player)){
            return array('success' => false, 'message' => 'Cannot find player.');
        }
        if(!isset($player->player_id) || empty($player->player_id)){
            return array('success' => false, 'message' => 'Cannot find player.');
        }        

        //check other data needed if complete
        if(!isset($payload['game_code'])){
            return array('success' => false, 'message' => 'Missing payload.game_code.');
        }

        if(!isset($payload['bet_id'])){
            return array('success' => false, 'message' => 'Missing payload.bet_id.');
        }

        if(!isset($payload['round_id'])){
            return array('success' => false, 'message' => 'Missing payload.round_id.');
        }

        if(!isset($payload['amount'])){
            return array('success' => false, 'message' => 'Missing payload.amount.');
        }

        if(!isset($payload['unique_id'])){
            return array('success' => false, 'message' => 'Missing payload.unique_id.');
        }

        if(!isset($payload['merchant_code'])){
            return array('success' => false, 'message' => 'Missing payload.merchant_code.');
        }

        if(!isset($payload['timestamp'])){
            return array('success' => false, 'message' => 'Missing payload.timestamp.');
        }

        $this->CI->load->model(array('t1lottery_transactions'));
        $this->CI->t1lottery_transactions->tableName = $this->getTransactionsTable();
        
        //check if already refunded
        $where = [
            'round_id'=>$payload['round_id'],
            'trans_type'=>'refund',
            'bet_id'=>$payload['bet_id'],
            'game_code'=>$payload['game_code'],
            'player_id'=>$player->player_id,
        ];
        $row = $this->CI->t1lottery_transactions->getTransactionByParamsArray($where);        
        if(!empty($row)){
            $this->CI->utils->error_log('T1LOTTERY SEAMLESS (triggerInternalRefundRound) existing refund transactions:', $row);		
            return array('success' => false, 'message' => 'Refund already processed. Double refund is not allowed.');
        }                
        
        //check if already have payout
        $where = [
            'round_id'=>$payload['round_id'],
            'trans_type'=>'payout',
            'bet_id'=>$payload['bet_id'],
            'game_code'=>$payload['game_code'],
            'player_id'=>$player->player_id,
        ];
        $row = $this->CI->t1lottery_transactions->getTransactionByParamsArray($where);        
        if(!empty($row)){
            $this->CI->utils->error_log('T1LOTTERY SEAMLESS (triggerInternalRefundRound) existing payout transactions:', $row);		
            return array('success' => false, 'message' => 'Refund cannot process. Bet already settled/with payout.');
        }  

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTriggerInternalRefundRound',
            'gameUsername' => $payload['username'],
			'transaction' => $payload
        );
        
        $params = $payload;

        $this->method = self::METHOD_POST;
		return $this->callApi(self::API_triggerInternalRefundRound, $params, $context);        
    }

    public function processResultForTriggerInternalRefundRound($params){
        $resultArr = $this->getResultJsonFromParams($params);		
        
        $this->CI->utils->debug_log('T1LOTTERY SEAMLESS (processResultForTriggerInternalRefundRound)', $params, $resultArr);	
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'gameUsername' => $gameUsername,
			'transaction' => $transaction,
			'status' => null,
			'exists' => null,
			'triggered_refund' => false
        );
        $success = false;
        if(isset($resultArr['is_success']) && $resultArr['is_success']==true){      
            $result['status']=true;            
            $result['triggered_refund']=true;
            $success = true;
        }

        $result['message']=isset($resultArr['err_msg'])?$resultArr['err_msg']:'';            

		return array($success, $result);
	}

    public function manualFixMissingPayoutFormat(){
        return '{
            "flowId":200883,
            "apiPath":"http://admin.brl.staging.smash.t1t.in/t1lottery_service_api/5928/payout",
            "payload":{
                "username":"smblucasleo39",
                "game_code":"double",
                "bet_id":7178,
                "round_id":"202109060614",
                "amount":16,
                "currency":"BRL",
                "number":"black",
                "unique_id":"59b03cfa-f64c-45d1-97ee-ded31031dd1f",
                "merchant_code":"8048290dec464435a04b531f5171b45c",
                "timestamp":1630876020,
                "sign":"7e892979c23e078929d523df4293cdcd6355445e"
            },
            "err":{"is_success":false,"err_msg":"Server Error","username":"smblucasleo39","balance":0,"currency":"BRL","request_id":"6f6cb7afc817b2b255a686e68b1b0a4a","statusCode":500}
        }';
    }

    public function changePassword($playerName, $oldPassword, $newPassword){
        return ['success'=>$this->changePasswordInDB($playerName, $newPassword)];
    }

    ##### SEAMLESS BALANCE HISTORY METHODS


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
t.trans_type trans_type,
t.raw_data extra_info,
t.bet_id bet_id,
t.game_code game_code,
t.number number,
t.opencode opencode
FROM {$table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?  AND `t`.`trans_type`<>'settle'
ORDER BY t.updated_at asc;

EOD;
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];
        
                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
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
                    'bet_id'=>$transaction['bet_id'], 
                    'game_code'=>$transaction['game_code'], 
                    'number'=>$transaction['number'], 
                    'opencode'=>$transaction['opencode']
                ];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;

                ##### additional checking if amount is not 0 and balance really changed, to add alert
                if($this->alert_if_balance_not_changed && $temp_game_record['amount']>0 && $transaction['before_balance']==$transaction['after_balance']){
                    //alert balance not changed
                    $alertData = [];
                    $alertData['error_msg'] = 'Balance not updated';
                    $alertData['error_code'] = -1;
                    $alertData['transaction'] = $temp_game_record;                    
                    $this->CI->utils->error_log("T1LOTTERY SEAMLESS: (processTransactions) ERROR", $alertData); 
                    $this->sendNotificationToMattermost('API ' . $this->getPlatformCode(), ' Balance Not Changed', $alertData);
                }
                #####
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function syncSeamlessBatchPayout($token = false) {
        $this->CI->load->model(array('wallet_model', 't1lottery_transactions'));

        //get shared folder
        $adjust = $this->adjust_datetime_minutes_sync_batch_payout;
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify('-'.$adjust.' minutes');
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $step='+1 minute';

        $dataTimeRanges = $this->CI->utils->generateDateTimeRange($queryDateTimeStart, $queryDateTimeEnd, $step);
        $this->utils->debug_log("T1LOTTERY SEAMLESS: (syncSeamlessBatchPayout) dataTimeRanges",
        'queryDateTimeStart', $queryDateTimeStart, 
        'queryDateTimeEnd', $queryDateTimeEnd, 
        'step', $step);
        $result = [];

        foreach($dataTimeRanges as $dataTimeRange){

            $queryDateTimeStart = $dataTimeRange['from'];
            //check if folder exist
            $baseDir=$this->CI->utils->getBatchpayoutSharingUploadPath($this->getPlatformCode());
            $dateFolder = date('Ymd', strtotime($queryDateTimeStart));
            $timeFolder = date('Hi', strtotime($queryDateTimeStart));
            $completeDirectory = $baseDir.$dateFolder.'/'.$timeFolder;
            $this->utils->debug_log("T1LOTTERY SEAMLESS: (syncSeamlessBatchPayout) dataTimeRange", 'dataTimeRange', $dataTimeRange, 
            'completeDirectory', $completeDirectory);
            if(!is_dir($completeDirectory)){
                $this->utils->info_log("T1LOTTERY SEAMLESS: (syncSeamlessBatchPayout) DIR NOT EXIST", $completeDirectory); 
                continue;
            }

            $scanned_directory = array_diff(scandir($completeDirectory), array('..', '.'));

            foreach($scanned_directory as $fileName){
                $withError = false;                
                $renameFile=false;
                //$failedpayouts = [];
                if(strpos($fileName, 'processed')===false){
                    //read file
                    $fullFileName = $completeDirectory.'/'.$fileName;
                    $json = file_get_contents($fullFileName);
                    $json_arr = json_decode($json, true);
                    if(isset($json_arr['payouts'])){
                        foreach($json_arr['payouts'] as $payout){

                            try {
                                $player = null;
                                $params = [];
                                //check if payout already exist
                                $params['username'] = $gameUsername = $payout['username'];
                                list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);

                                if(!$playerStatus || !$player){
                                    throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
                                }

                                $isAlreadyExists = false;

                                
                                $params['timestamp'] = $payout['timestamp'];
                                $params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
                                $params['merchant_code'] = $payout['merchant_code'];
                                $params['amount'] = $payout['amount'];
                                $params['currency'] = $payout['currency'];
                                $params['bet_id'] = $payout['bet_id'];
                                $params['unique_id'] = $payout['unique_id'];
                                $params['round_id'] = $payout['round_id'];
                                $params['game_code'] = $payout['game_code'];
                                $params['number'] = $payout['number'];
                                $params['sign'] =  $payout['sign'];
                                $player_id = $params['player_id'] = $player->player_id;
                                $params['player_name'] = $player_username;
                                $params['trans_type'] = self::TRANSTYPE_PAYOUT;
                                $betExist = true;
                                
                                $trans_success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player->player_id, function() use($player,$json_arr,
                                    $params,
                                    &$insufficient_balance, 
                                    &$previous_balance, 
                                    &$after_balance, 
                                    &$isAlreadyExists,
                                    &$additionalResponse,
                                    &$betExist) {
                
                                    list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $json_arr, $previous_balance, $after_balance);
                                    $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE lockAndTransForPlayerBalance payout",
                                    'trans_success',$trans_success,
                                    'previous_balance',$previous_balance,
                                    'after_balance',$after_balance,
                                    'insufficient_balance',$insufficient_balance,
                                    'isAlreadyExists',$isAlreadyExists,
                                    'additionalResponse',$additionalResponse,
                                    'isTransactionAdded',$isTransactionAdded);	
                                    if(isset($additionalResponse['betExist'])){
                                        $betExist=$additionalResponse['betExist'];	
                                    }

                                    return $trans_success;
                                });

                                if(!$betExist){				
                                    throw new Exception(self::ERROR_BET_DONT_EXIST);
                                }

                                if(!$trans_success){
                                    throw new Exception(self::ERROR_SERVER);
                                }
                                $renameFile=true;
                            } catch (Exception $error) {
                                $message = $this->getErrorSuccessMessage($error->getMessage());                                
                
                                //notify channel error processing file
                                $fileSuccess = false;

                                //$failedpayouts[] = $payout;
                                $alertData = [];
                                $alertData['error_msg'] = $message;
                                $alertData['error_code'] = $error->getMessage();
                                $alertData['payout_affected'] = $payout;
                                $alertData['file'] = $fullFileName;
                                $this->CI->utils->error_log("T1LOTTERY SEAMLESS: (syncSeamlessBatchPayout) ERROR", $alertData); 
                                $this->sendNotificationToMattermost('API ' . $this->getPlatformCode(), 'Failed sync batch payout', $alertData);
                                $renameFile = false;
                                $withError = true;
                                continue;
                            }
                            


                        }
                    }
                }else{
                    $this->utils->debug_log("T1LOTTERY SEAMLESS: (syncSeamlessBatchPayout) ALREADY PROCESSED", 'fileName', $fileName); 
                    $renameFile = false;
                }

                //flag file as processed
                if($renameFile && !$withError){
                    rename($completeDirectory.'/'.$fileName, $completeDirectory.'/processed_'.$fileName);
                }

            }
            //check if file processed, with 'processed in file name'

        }

		return array("success" => true, "results"=>$result);
    }


    ############### SEAMLESS SERVICE API CODES ###################
	public function debitCreditAmountToWallet($params, $request, &$previousBalance, &$afterBalance){        

        if(empty($this->request)){
            $this->request = $request;
        }

		$this->CI->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);
        $this->CI->load->model(array('t1lottery_transactions'));
        $this->CI->t1lottery_transactions->tableName = $this->getTransactionsTable();
		//initialize params
		$player_id			= $params['player_id'];				
		$amount 			= abs($params['amount']);
		
		//initialize response
		$success = false;
		$isValidAmount = true;		
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];
        $trans_type = $params['trans_type'];
        $prevTranstable = $this->getTransactionsPreviousTable();
        $uniqueid = $this->generateUniqueId($params);
        $external_game_id = isset($params['game_code']) ? $params['game_code'] : null;


        if (method_exists($this->CI->wallet_model, 'setUniqueidOfSeamlessService')) {
            $uniqueid = $this->getPlatformCode().'-'.$uniqueid;
            $this->CI->wallet_model->setUniqueidOfSeamlessService($uniqueid, $external_game_id);
        }

        if (method_exists($this->CI->wallet_model, 'setGameProviderActionType')) {
            $this->CI->wallet_model->setGameProviderActionType($trans_type);
        }

		if($params['trans_type']=='bet'){
			$mode = 'debit';
		}elseif($params['trans_type']=='payout'){
			$mode = 'credit';
		}elseif($params['trans_type']=='refund'){
			$mode = 'credit';
			$flagrefunded = true;		
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}
        
        $checkOtherTable = $this->checkOtherTransactionTable();
        if($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table){
            $checkOtherTable = true;
        }

		if($amount<>0){

			//get and process balance
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}
				
			}else{				
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

            if($checkOtherTable){                                
                $uniqueId = $this->generateUniqueId($params);                
                $isAlreadyExists = $this->CI->t1lottery_transactions->isTransactionExistInOtherTable($prevTranstable, $uniqueId, $trans_type);
                if($isAlreadyExists){
                    $this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already in prev month transactions", $isAlreadyExists, 
                    'trans_records', $this->trans_records,
                    'uniqueId', $uniqueId,
                    'prevTranstable', $prevTranstable);
                    $isAlreadyExists = true;					
                    $afterBalance = $previousBalance;
                    return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
            }

			//check if bet transaction exists
			if($params['trans_type']=='refund' || $params['trans_type']=='payout'){
				$flagrefunded = true;
				$check_bet_params = ['bet_id'=>(string)$params['bet_id'], 
                'round_id'=>(string)$params['round_id'], 
                'player_id'=>$player_id, 
                'trans_type'=>'bet'];
				$betExist = $this->CI->t1lottery_transactions->getTransactionByParamsArray($check_bet_params);

                if(empty($betExist) && $checkOtherTable){                    
                    $betExist = $this->CI->t1lottery_transactions->getTransactionByParamsArray($check_bet_params, $prevTranstable);
                }
				
				if(empty($betExist)){
					$additionalResponse['betExist']=false;
					$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist", 
                    'betExist', $betExist, 
                    'params',$params, 
                    'check_bet_params', $check_bet_params,
                    'prevTranstable', $prevTranstable);
					$afterBalance = $previousBalance;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}	            

				$additionalResponse['betExist']=true;	

                if($params['trans_type']=='payout' && $betExist['status']==Game_logs::STATUS_REFUND){                    
					$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet)  BET TRANSACTION already refunded");
					$afterBalance = $previousBalance;                    
                    $additionalResponse['refundExist']=true;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
			}	

            if($params['trans_type']=='refund'){
                $check_payout_params = ['bet_id'=>(string)$params['bet_id'], 
                'player_id'=>$player_id, 
                'trans_type'=>'payout'];
                $payoutExist = $this->CI->t1lottery_transactions->getTransactionByParamsArray($check_payout_params);

                if(empty($payoutExist) && $checkOtherTable){                    
                    $payoutExist = $this->CI->t1lottery_transactions->getTransactionByParamsArray($check_payout_params, $prevTranstable);
                }

                if(!empty($payoutExist)){
                    $afterBalance = $previousBalance;
                    $additionalResponse['payoutExist']=true;
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
            }     

			if($params['trans_type']=='refund'){	
				$flagTransactionRefundedResp = $this->CI->t1lottery_transactions->flagTransactionRefunded($betExist['external_uniqueid']);

                if($checkOtherTable && !$flagTransactionRefundedResp){                                        
                    $this->CI->t1lottery_transactions->flagTransactionRefunded($betExist['external_uniqueid'], $prevTranstable);
                }
			}

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);				
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}	

			$success = $this->transferGameWallet($player_id, $this->getPlatformCode(), $mode, $amount);

			if(!$success){
                $success = false;
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit adjust balance", $this->request, 'success', $success);
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

            if($this->get_after_balance_from_db){
                $get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
                if($get_balance!==false){
                    $afterBalance = $get_balance;
                }else{
                    $this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance for after balance", $get_balance, $this->request);
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
            }

            if($this->alert_if_balance_not_changed && $amount<>0 && $previousBalance==$afterBalance){
                //alert balance not changed
                $alertData = [];
                $alertData['error_msg'] = 'Balance not updated';
                $alertData['error_code'] = -1;
                $alertData['previousBalance'] = $previousBalance;
                $alertData['afterBalance'] = $afterBalance;
                $alertData['transaction'] = $params;                    
                $this->CI->utils->error_log("T1LOTTERY SEAMLESS: (processTransactions) ERROR", $alertData); 
                $this->sendNotificationToMattermost('API ' . $this->getPlatformCode(), ' Balance Not Changed', $alertData);
            }

            if($this->check_if_balance_changed && $amount<>0 && $previousBalance==$afterBalance){
                $this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: balance did not change", $get_balance, $this->request, 'previousBalance', $previousBalance, 'afterBalance', $afterBalance);                                

                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }

		}else{
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;

				//insert transaction
				$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
			}else{
				$success = false;
			}
		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded);
	}

	public function getPlayerBalance($playerName, $player_id){			
		$get_bal_req = $this->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}	
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);		
		if($trans_record['trans_type']=='payout' && $this->flag_bet_transaction_settled){
			//mark bet as settled
			$this->CI->t1lottery_transactions->flagBetTransactionSettled($trans_record);
		}	

        $tableName = $this->getTransactionsTable();
        $this->CI->t1lottery_transactions->setTableName($tableName);        
		return $this->CI->t1lottery_transactions->insertIgnoreRow($trans_record);        		
	}

	public function makeTransactionRecord($raw_data){
		$data = [];		
		$data['username'] 			= isset($raw_data['username'])?$raw_data['username']:null;//string
		$data['timestamp'] 			= isset($raw_data['timestamp'])?$raw_data['timestamp']:null;//string
		$data['timestamp_parsed'] 	= isset($raw_data['timestamp_parsed'])?$raw_data['timestamp_parsed']:null;//datetime
		$data['merchant_code'] 		= isset($raw_data['merchant_code'])?$raw_data['merchant_code']:null;//string
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;//double
		$data['currency'] 			= isset($raw_data['currency'])?$raw_data['currency']:null;//string
		$data['game_code'] 			= isset($raw_data['game_code'])?$raw_data['game_code']:null;//string		
		$data['bet_id'] 			= isset($raw_data['bet_id'])?$raw_data['bet_id']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string		
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;//string
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;//string
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;	
		$data['game_platform_id'] 	= $this->getPlatformCode();		
		$data['status'] 			= $this->getTransactionStatus($raw_data);		
		$data['raw_data'] 			= @json_encode($this->request);//text				
		$data['external_uniqueid'] 	= $this->generateUniqueId($raw_data);
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;	
		$data['game_platform_id'] 	= $this->getPlatformCode();	
		$data['number'] 		= isset($raw_data['number'])?$raw_data['number']:null;	
		$data['opencode'] 		= isset($raw_data['opencode'])?$raw_data['opencode']:null;
		$data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);
		
		return $data;
	}

	private function getTransactionStatus($data){
		
		if($data['trans_type']=='payout'){
			return Game_logs::STATUS_SETTLED;
		}elseif($data['trans_type']=='refund'){
			return Game_logs::STATUS_REFUND;
		}elseif($data['trans_type']=='settle'){//initially pending if processed by cronjob then it will be flag as processed
			return Game_logs::STATUS_PENDING;
		}else{
			return Game_logs::STATUS_PENDING;
		}
	}

	private function generateUniqueId($data){
		if(empty($data['bet_id'])){
			return $data['game_code'] .'-'. $data['round_id'] .'-'. $data['trans_type'];	
		}
		return $data['game_code'] .'-'. $data['bet_id'] .'-'. $data['trans_type'];
	}

	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount){
		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
			$success = $this->CI->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);	
		}elseif($mode=='credit'){
			$success = $this->CI->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
		}

		return $success;
	}

	public function getPlayerByUsername($gameUsername){
		$player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->getPlatformCode());		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}
    
    public function sendNotificationToMattermost($user, $subject,$data,$notifType='warning',$texts_and_tags=null){
		if(!$this->enable_mm_channel_nofifications){
			//return false;
		}
    	$this->CI->load->helper('mattermost_notification_helper');
        $settings = $this->CI->utils->getConfig('seamless_batch_payout_settings');
        $client = isset($settings['base_url'])?$settings['base_url']:'???';

        $gamePlatformId = $this->getPlatformCode();
        //check if transaction has no payout
        $message = "@all Seamless Game ({$gamePlatformId}) ".$subject."\n";            
        $message .= 'Client: '.$client."\n";  
        $message .= "```\n";  
        if(is_array($data)){
            $message .= json_encode($data);
        }else{
            $message .= $data;
        }     				
        $message .= "```\n";  	
        

        $notif_message = array(
            array(
                'text' => $message,
                'type' => 'warning'
            )
        );

    	return sendNotificationToMattermost($user, $this->mm_channel, $notif_message, $texts_and_tags);
    }

	public function getErrorSuccessMessage($code){
		$message = '';		

		if(!array_key_exists($code, self::HTTP_STATUS_CODE_MAP)){
			$message = $code;		
			return $message;
		}

        switch ($code) {

			case self::SUCCESS:
				return lang('Success');

			case self::ERROR_INVALID_SIGN:
				return lang('Invalid signature');

			case self::ERROR_INVALID_PARAMETERS:
				return lang('Invalid parameters');

            case self::ERROR_SERVICE_NOT_AVAILABLE:
                return lang('Service not available');	

            case self::ERROR_INSUFFICIENT_BALANCE:
                return lang('Insufficient Balance');

			case self::ERROR_SERVER:
				return lang('Server Error');
				
			case self::ERROR_IP_NOT_ALLOWED:
				return lang('IP Address is not whitelisted');	

			case self::ERROR_TRANSACTION_ALREADY_EXIST:
				return lang('Transactions already exists.');	

			case self::ERROR_CANNOT_FIND_PLAYER:
				return lang('Cannot find player.');		

			case self::ERROR_BET_DONT_EXIST:
				return lang('Bet dont exist.');	

            case self::ERROR_REFUND_PAYOUT_EXIST:
                return lang('Payout already exist.');		

			case self::ERROR_GAME_UNDER_MAINTENANCE:
				return lang('Game under maintenance.');

			case 'Connection timed out.':
			case self::ERROR_CONNECTION_TIMED_OUT:
				return lang('Connection timed out.');

			default:
				$this->CI->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				return $code;
		}
	}

    public function getTransactionsPreviousTable(){
        $d = new DateTime('-1 month');                 
        $monthStr = $d->format('Ym');

        if(!empty($this->previous_transactions_table)){
            return $this->previous_transactions_table;
        }
        
        return $this->CI->utils->initT1lotteryTransactionsMonthlyTableByDate($monthStr);        
    }

    public function getTransactionsTable($monthStr = null){
        if(!$this->use_monthly_transactions_table){
            return $this->original_transactions_table;
        }

        if(empty($monthStr)){
            $date=new DateTime();
            $monthStr=$date->format('Ym');
        }
        
        return $this->CI->utils->initT1lotteryTransactionsMonthlyTableByDate($monthStr);        
    }

}

/*end of file*/
