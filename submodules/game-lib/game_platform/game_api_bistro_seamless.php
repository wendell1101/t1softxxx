<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_bistro_seamless extends Abstract_game_api {

    const ORIGINAL_GAMELOGS_TABLE = '';
    const ORIGINAL_TRANSACTION_TABLE = 'bistro_transactions';

    protected $api_sign_key;

    const ODDS_MAP = [
        'red' => 2,
        'black' => 2,
        'white' => 14
    ];

    const TRANSTYPE_BET = 'bet';
    const TRANSTYPE_PAYOUT = 'payout';
    const TRANSTYPE_BATCH_PAYOUT = 'batch_payout';
    const TRANSTYPE_SETTLE = 'settle';


    const MODE_FUN = 0;
    const MODE_REAL = 1;

    const SUCCESS_CODE = 0;
    const ERR_INVALID_SIGN = 1;
    const ERR_INVALID_MERCHANT_CODE = 2;
    const ERR_INVALID_SECURE_KEY = 3;
    const ERR_INVALID_AUTH_TOKEN = 4;
    const ERR_DUPLICATE_USERNAME = 8;

    const URI_MAP = array(
        self::API_generateToken => '/gameapi/v1/generate_token',
        self::API_createPlayer => '/gameapi/v1/seamless/create_player_account',
		self::API_queryForwardGame => '/gameapi/v1/seamless/query_game_launcher',
	);




    public function getPlatformCode(){
        return BISTRO_SEAMLESS_API;
    }

    public function isSeamLessGame()
    {
       return true;
    }

    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','game_code'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance'];

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('key');
        $this->api_sign_key = $this->getSystemInfo('secret');
        $this->merchant_code = $this->getSystemInfo('merchant_code');
        $this->currency        = $this->getSystemInfo('currency');

        //token ecryption
        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');

        //FOR TESTING
        $this->trigger_bet_error_response = $this->getSystemInfo('trigger_bet_error_response', 0);
        $this->trigger_payout_error_response = $this->getSystemInfo('trigger_payout_error_response', 0);
        $this->trigger_refund_error_response = $this->getSystemInfo('trigger_refund_error_response', 0);
        $this->trigger_player_info_error_response = $this->getSystemInfo('trigger_player_info_error_response', 0);
        $this->trigger_settle_error_response = $this->getSystemInfo('trigger_settle_error_response', 0);

        $this->flag_bet_transaction_settled      = $this->getSystemInfo('flag_bet_transaction_settled', true);
        $this->enable_settle_by_queue      = $this->getSystemInfo('enable_settle_by_queue', true);
        $this->opencode_list = [];
    }


    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

    protected function customHttpCall($ch, $params) {

		$requestBodyString = json_encode($params);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Accept:application/json"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyString);

    }

    /**
	 * will check timeout, if timeout then call again
	 * @return token
	 */
    public function getAvailableApiToken(){
    	$result = $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
        $this->utils->debug_log("BISTRO Available Token: ".$result);
        return $result;
    }

    /**
	 * Generate Access Token
	 *
	 */
	public function generateToken()
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultGenerateToken',
		);

		$params = array(
			'secure_key' => $this->api_key,
			'merchant_code' => $this->merchant_code
		);

        $params["sign"] = $this->generateSignature($params);

		$this->utils->debug_log("BISTRO: Generate Token");
		return $this->callApi(self::API_generateToken, $params, $context);
	}

    public function processResultGenerateToken($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = array();

		if($success){
			$api_token = @$resultArr['detail']['auth_token'];
			# Token will be invalid each 2 hours
			$token_timeout = new DateTime($this->utils->getNowForMysql());
			$minutes = ((int)$resultArr['detail']['timeout']/60)-1;
			$token_timeout->modify("+".$minutes." minutes");
			$result['api_token']=$api_token;
			$result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
		}

		return array($success,$result);
	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $api_token=$this->getAvailableApiToken();
        if(empty($api_token)){
            return ['success'=>false, 'error_message'=>'no auth token'];
        }


		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'merchant_code' => $this->merchant_code,
			'auth_token' => $api_token,
			'username' => $gameUsername
		);

        $params["sign"] = $this->generateSignature($params);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}


    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName)  || $resultJson['code'] == self::ERR_DUPLICATE_USERNAME;

		$result=['response_result_id'=>$responseResultId];

		$result['exists']=null;

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists']=true;
		}

		return array($success, $result);

	}

    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);

        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;

        $api_token=$this->getAvailableApiToken();
        if(empty($api_token)){
            return ['success'=>false, 'url'=>null, 'error_message'=>'no auth token'];
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->merchant_code,
			'game_code' => $game_code,
            'token' => $this->generatePlayerToken($playerName)
		);

        $params["sign"] = $this->generateSignature($params);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);


        if(!$success){ #possible error
            $result['message'] = $resultArr['message'];
        } else {
            $result['url'] = ( $success && isset($resultArr['detail']['game_url']) ) ? $resultArr['detail']['game_url'] : null;
        }

        return array($success, $result);
    }

    // public function queryPlayerBalance($playerName) {

    //     $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    //     $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //     $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

    //     $result = array(
    //         'success' => true,
    //         'balance' => $this->dBtoGameAmount($balance)
    //     );

    //     $this->utils->debug_log(__FUNCTION__,'Bistro Seamless (Query Player Balance): ', $result);

    //     return $result;

    // }

    private function generateSignature($params) {
        ksort($params);

        $string = "";

        foreach ($params as $key => $val) {
            $string .= $val;
        }

        $string = $string . $this->api_sign_key;

        return sha1($string);
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName) {

		$success = (!empty($resultJson)) && $resultJson['code'] == self::SUCCESS_CODE;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('Bistro Seamless got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
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

    private function errorReason($code) {

        switch($code) {

            case self::ERR_INVALID_SIGN:
                $errMsg = "invalid signature";
                break;
            case self::ERR_INVALID_SECURE_KEY:
                $errMsg = "invalid secure_key";
                break;
            case self::ERR_INVALID_MERCHANT_CODE:
                $errMsg= "invalid merchant_code";
                break;
            case self::ERR_INVALID_AUTH_TOKEN:
                $errMsg = "invalid auth_token";
                break;
            case self::ERR_DUPLICATE_USERNAME:
                $errMsg = "duplicate username";
                break;

        }

        return $errMsg;

    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'en'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("KA SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );

    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("KA SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token)
    {
        return $this->returnUnimplemented();
    }

    public function changePassword($playerName, $oldPassword, $newPassword){
        return $this->returnUnimplemented(); // need to add this one because once the player change the password of their player center the password in the game_provider_auth was change.
        // as per game provider there is no endpoint to change the password.
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


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

		$sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`timestamp_parsed` >= ? AND `original`.`timestamp_parsed` <= ?';
        }
        $this->CI->utils->debug_log('BISTRO SEAMLESS sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.timestamp_parsed', 'original.status'));

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
    IF(original.trans_type='payout' OR original.trans_type='batch_payout',original.amount,0) payout_amount,
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
FROM {$this->original_transactions_table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE (original.trans_type='bet' OR original.trans_type='payout' OR original.trans_type='batch_payout') AND
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('BISTRO SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  !empty($row['round']) ? $row['round'] : $row['bet_id'], #try get bet id if round not exist
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

        //get opencode if empty
        if(empty($opencode)){
            $this->CI->load->model(array('bistro_transactions'));
            $whereParams = ['round_id'=>$roundId, 'trans_type'=>self::TRANSTYPE_SETTLE];
            $settleDatails = $this->CI->bistro_transactions->getTransactionByParamsArray($whereParams);
            if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                $this->opencode_list[] = [(string)$roundId=>$settleDatails['opencode']];
                $opencode = $settleDatails['opencode'];
            }
        }

        $row['opencode'] = $opencode;

        $result = [
            'round_id'=>$row['round'],
            'bet_id'=>$row['bet_id'],
            'transaction_type'=>$row['trans_type'],
            'number'=>$row['number'],
            'opencode'=>$row['opencode'],
            'odds'=>$this->processOdds($row)
        ];

        return $result;
    }

    public function processOdds($row){

        $odds = isset($row['opencode'])?$row['opencode']:null;
        $roundId = $row['round'];

        if(empty($odds) && array_key_exists($roundId,$this->opencode_list)){
            $odds = $this->opencode_list[$roundId];
        }

        //get opencode if empty
        if(empty($odds)){
            $this->CI->load->model(array('bistro_transactions'));
            $whereParams = ['round_id'=>$roundId, 'trans_type'=>self::TRANSTYPE_SETTLE];
            $settleDatails = $this->CI->bistro_transactions->getTransactionByParamsArray($whereParams);
            if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                $this->opencode_list[$roundId] = $settleDatails['opencode'];
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

    public function getGameDescriptionInfo($row, $unknownGame) {
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

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getGameRecordsStatus($status) {

        switch (strtolower($status)) {
            case "settled":
                $status = Game_logs::STATUS_SETTLED;
                break;
            case "ok":
                $status = Game_logs::STATUS_PENDING;
                break;
            case "cancelled":
                $status = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }
        return $status;
    }

    public function generateUrl($apiName, $params)
    {
        $apiUri = self::URI_MAP[$apiName];


		$url = $this->api_url . $apiUri;

		return $url;
    }

    public function queryTransactionByDateTime($startDate, $endDate){

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
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?  AND `t`.`trans_type`<>'settle'
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
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getApiSignKey(){
        return $this->api_sign_key;
    }

    public function generateSignatureByParams($params, $except=['sign']){
        $signString=$this->getSignString($params, $except);

        if(empty($signString)){
            return '';
        }

        $sign=strtolower(sha1($signString.$this->api_sign_key));

        return $sign;
    }

    public function getSignString($fields, $except=['sign']){
        $params=[];
        foreach ($fields as $key => $value) {
            if( in_array($key, $except) || is_array($value)){
                continue;
            }
            $params[$key]=$value;
        }

        if(empty($params)){
            return '';
        }

        ksort($params);

        return implode('', array_values($params));

    }

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("BISTRO SEAMLESS: (queryPlayerBalance)");

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $this->dBtoGameAmount($balance)
        );

        return $result;
    }

    public function decrypt($data){
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($data), $this->encrypt_method, $key, 0, $iv);
        return $output;
    }
}
/*end of file*/