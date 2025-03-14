<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: Oriental Game
	* API docs: http://mucho.oriental-game.com:8059/
	*
	* @category Game_platform
	* @copyright 2013-2022 tot
	* @integrator @bermar.php.ph
	* Unsupported endpoints https://tripleonetech2.atlassian.net/browse/OGP-33569?focusedCommentId=420436
**/

abstract class Abstract_game_api_common_ogplus extends Abstract_game_api {
	const POST = 'POST';
	const GET = 'GET';

    private $original_gamelogs_table = 'ogplus_game_logs';

	# Fields in ogplus_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'bettingcode',
        'bettingdate',
        'bet',
        'game_information',
        'winloseresult',
        'bettingamount',
        'validbet',
        'winloseamount',
        'balance',
        'currency',
        'gamecategory',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'winloseresult',
        'bettingamount',
        'validbet',
        'winloseamount',
        'balance',
    ];

    # Fields in game_logs we want to detect changes for merge and when md5_sum
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at',
        'bet_details'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'after_balance',
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    const ERROR_CODE_RECORD_NOT_FOUND = 1;
    const BACCARAT_TYPES = [
    	'BACCARAT',
    	'SPEED BACCARAT',
    	'BIDDING BACCARAT',
    ];
    const DT_TYPES = [
    	'NEW DT',
    	'CLASSIC DT',
    ];

    const ROULETTE_TYPES = [
    	'ROULETTE'
    ];

	public function __construct() {
		parent::__construct();

        //$this->original_gamelogs_table = $this->getOriginalTable();

		$this->api_url = $this->getSystemInfo('url');
		$this->data_api_url = $this->getSystemInfo('data_api_url');
		$this->method = self::POST; # default as POST
		$this->data_api = false; # default as false
		$this->x_operator = $this->getSystemInfo('x_operator');
		$this->x_key = $this->getSystemInfo('x_key');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
		$this->default_country = $this->getSystemInfo('default_country','China');
		$this->default_lang = $this->getSystemInfo('default_lang','cn');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '11');
		$this->limitvideo = $this->getSystemInfo('limitvideo', '60');
		$this->limitroulette = $this->getSystemInfo('limitroulette', '20');
		$this->provider_id = $this->getSystemInfo('provider_id', '1');
		$this->provider = $this->getSystemInfo('provider', 'ogplus');
		$this->encode_json_post_data = $this->getSystemInfo('encode_json_post_data', true);
		$this->min_bet_setting = $this->getSystemInfo('min_bet_setting');
		$this->max_bet_setting = $this->getSystemInfo('max_bet_setting');
		$this->bet_limit_id = $this->getSystemInfo('bet_limit_id', 1);
		$this->use_extra_info_date_field = $this->getSystemInfo('use_extra_info_date_field', false);
        $this->extra_info_date_field = $this->getSystemInfo('extra_info_date_field', 'updated_at');
		$this->force_language = $this->getSystemInfo('force_language', '');


		$this->URI_MAP = array(
			self::API_generateToken => '/token',
			self::API_createPlayer => '/register',
			self::API_queryPlayerBalance => "/game-providers/{$this->provider_id}/balance",
			self::API_isPlayerExist => "/game-providers/{$this->provider_id}/balance",
	        self::API_depositToGame => "/game-providers/{$this->provider_id}/balance",
	        self::API_withdrawFromGame => "/game-providers/{$this->provider_id}/balance",
	        self::API_queryTransaction => '/transfer',       
	        self::API_login => "/game-providers/{$this->provider_id}/games/{$this->provider}/key", 
	        self::API_queryForwardGame => "/game-providers/{$this->provider_id}/play",
			self::API_syncGameRecords => '/transaction',
			self::API_setMemberBetSetting => "/game-providers/{$this->provider_id}/operator-bet-limit",
			self::API_updatePlayerInfo => "/game-providers/{$this->provider_id}/user-bet-limit"
		);

		$this->METHOD_MAP = array(
			self::API_generateToken => self::GET,
			self::API_createPlayer => self::POST,
			self::API_queryPlayerBalance => self::GET,
			self::API_isPlayerExist => self::GET,
	        self::API_depositToGame => self::POST,
	        self::API_withdrawFromGame => self::POST,
	        self::API_queryTransaction => self::POST,
	        self::API_login => self::GET,
	        self::API_queryForwardGame => self::GET,
			self::API_syncGameRecords => self::POST,
			self::API_setMemberBetSetting => self::POST,
			self::API_updatePlayerInfo => self::POST
		);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	protected function getHttpHeaders($params){
		$this->CI->utils->debug_log('OGPLUS (getHttpHeaders)', $params);		

		$headers = [];
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';//for data requests

		if(!$this->data_api){
			if($this->currentAPI == self::API_generateToken){
				$headers['X-Operator'] = $this->x_operator;
				$headers['X-key'] = $this->x_key;
				$this->CI->utils->debug_log('OGPLUS (getHttpHeaders)', $this->method);
				return $headers;
			}			

			$clone = clone $this;
			$headers['Content-Type'] = 'application/json';
			$headers['X-Token'] =  $clone->getAvailableApiToken();
			$this->CI->utils->debug_log('OGPLUS (getHttpHeaders) clone:', $clone->method);			
			$this->CI->utils->debug_log('OGPLUS (getHttpHeaders) headers:', $headers);			
		}
		return $headers;
	}

	public function generateUrl($apiName, $params) {
		$this->CI->utils->debug_log('OGPLUS (generateUrl)', $apiName, $params);		

		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		if($this->data_api){
			$url = $this->data_api_url . $apiUri;
		}
		$this->currentAPI = $apiName;

		$this->method = $this->METHOD_MAP[$apiName];

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
		}

		$this->CI->utils->debug_log('OGPLUS (generateUrl) :', $this->method, $url);

		return $url;
	}

	protected function customHttpCall($ch, $params) {	
		$this->CI->utils->debug_log('OGPLUS (customHttpCall)', $this->method);		

		switch ($this->method){
			case self::POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);
				if($this->encode_json_post_data && !$this->data_api){
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				}else{
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				}
				
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
		}
		$this->utils->debug_log('OGPLUS (customHttpCall) ', $this->method, http_build_query($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null,$is_querytransaction= false) {
		$this->CI->utils->debug_log('OGPLUS (processResultBoolean)');	

		$success = false;
		if(@$resultArr['status'] == "success"){
			$success = true;
		}

		if($is_querytransaction){
			$success = $resultArr['statusCode'] == 200?true:false;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('OGPLUS got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	/**
	 * will check timeout, if timeout then call again
	 * @return token
	 */
    public function getAvailableApiToken(){
        return $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
    }

    /**
	 * Generate Access Token
	 *
	 * Token will be invalid each 30 minutes
	 */
	public function generateToken(){
		$this->CI->utils->debug_log('OGPLUS (generateToken)');	

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGenerateToken',
			'playerId'=>null,
		);

		$params = [];
		$this->method = self::GET;

		return $this->callApi(self::API_generateToken, $params, $context);
	}

	public function processResultForGenerateToken($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForGenerateToken)', $params);	

		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

		if($success){
			$api_token = @$resultArr['data']['token'];
			# Token will be invalid each 30 minutes
			$token_timeout = new DateTime($this->utils->getNowForMysql());
			$token_timeout->modify("+29 minutes");
			//minus 30 seconds
			$api_token_timeout_datetime = $this->CI->utils->getMinusSecondsForMysql($this->utils->getNowForMysql(), 30);
			$result['api_token']=$api_token;
			$result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
		}
		return array($success,$result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$this->CI->utils->debug_log('OGPLUS (createPlayer)', $playerName);	

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'username' => $gameUsername, # English letters and number (No special characters) min: 5 max: 22
			'country' => $this->default_country,
			'fullname' => $gameUsername,
			'email' => $gameUsername.'@test.com',
			'language' => $this->default_lang,
			'birthdate' => '1990-01-01'
		);
		
		$this->method = self::POST;

		$this->CI->utils->debug_log('OGPLUS (createPlayer) :', $params, $this->method);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForCreatePlayer)', $params);	

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
	        $this->setMemberBetSetting($playerName);
		}

		return array($success, $result);
	}

	private function round_down($number, $precision = 3){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('OGPLUS (queryPlayerBalance)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'username' => $gameUsername,
		);

		$this->method = self::GET;
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('OGPLUS (processResultForQueryPlayerBalance)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = [];

		if($success){
			$result['balance'] = $this->round_down(@floatval($resultArr['data']['balance']));
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$this->CI->utils->debug_log('OGPLUS (isPlayerExist)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'username' => $gameUsername,
		);

		$this->method = self::GET;
		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForIsPlayerExist)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['exists'] = true;
		}else{
			// if((@$resultArr['data']['message'] == 'User not found.') || (@$resultArr['data']['message'] == 'Record not found.')){
			if(@$resultArr['data']['code'] == self::ERROR_CODE_RECORD_NOT_FOUND){
				$success = true;//meaning request success.
				$result['exists'] = false;
			}else{
				$result['exists'] = null;
			}
		}

		return array($success, $result);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('OGPLUS (depositToGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'username' => $gameUsername,
			'balance' => $amount,
			'action' => 'IN',
			'transferId' => $external_transaction_id
		);

		$this->method = self::POST;
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$this->CI->utils->debug_log('OGPLUS (processResultForDepositToGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_msg = @$resultArr['data']['message'];

			if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || $error_msg=="InternalServerError") && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons($error_msg);
			}
        }

        return array($success, $result);
	}

	private function getReasons($error_msg){
		switch ($error_msg) {
			case 'providerId not found.':
			case 'Invalid parameter.':
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 'username not found.':
			case 'The username must not be empty.':
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('OGPLUS (withdrawFromGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'username' => $gameUsername,
			'balance' => $amount,
			'action' => 'OUT',
			'transferId' => $external_transaction_id
		);

		$this->method = self::POST;
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForWithdrawFromGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
        	$error_msg = @$resultArr['data']['message'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($error_msg);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
		$this->CI->utils->debug_log('OGPLUS (queryTransaction)', $transactionId, $extra);	
		
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$transfer_time=$extra['transfer_time'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$startDateTime = new DateTime($transfer_time);
    	$startDateTime->modify('-2 minutes');
		$endDateTime = new DateTime($transfer_time);
    	$endDateTime->modify('+2 minutes');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);

		$params = array(
			'Operator' => $this->x_operator,
			'Key' => $this->x_key,
			'SDate' => $this->serverTimeToGameTime($startDateTime->format("Y-m-d H:i:s")),
			'EDate' => $this->serverTimeToGameTime($endDateTime->format("Y-m-d H:i:s")),
			'Provider' => $this->provider,
			'PlayerID' => $gameUsername,
			'TransferCode' => $transactionId,
			'Exact' => 'true',
		);

		$this->data_api = TRUE;
		$this->method = self::POST;
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForQueryTransaction)', $params);	

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername, true);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$trans_status = @$resultJsonArr['info']['Status'];
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			if(empty($resultJsonArr)){
				$result['reason_id'] = self::REASON_UNKNOWN;
				$result['status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
		} else {
			$result['reason_id'] = self::REASON_UNKNOWN;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

    public function login($playerName, $password = null, $extra = null) {
		$this->CI->utils->debug_log('OGPLUS (login)', $playerName);	
		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$params = array(
			'username' => $gameUsername,
			'betlimit' => $this->bet_limit_id
		);

		$this->method = self::GET;
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForLogin)', $params);	

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result= [];
		if($success){
			$result['key'] = $resultArr['data']['key'];
		}
		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra = null) {
		$this->CI->utils->debug_log('OGPLUS (queryForwardGame)', $playerName, $extra);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);
		$token = $this->login($playerName);
		if($token['success']){
			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForQueryForwardGame',
	            'playerName' => $playerName,
	            'gameUsername' => $gameUsername,
	        );

			$language = $this->default_lang;
			if(isset($extra['language'])){
				$language = $extra['language'];
			}

			if($this->force_language && !empty($this->force_language)){
				$language = $this->force_language;
			}
			
			$language = $this->getLauncherLanguage($language);
	
			$params = array(
				'key' => $token['key'],
				'type' => $extra['is_mobile']?'mobile':'desktop',
				'lang' => $language,
			);

			$this->method = self::GET; 
			return $this->callApi(self::API_queryForwardGame, $params, $context);
		}else{
			return ['success'=>false,'url'=>null];
		}
	}

	public function processResultForQueryForwardGame($params){
		$this->CI->utils->debug_log('OGPLUS (processResultForQueryForwardGame)', $params);	

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array('url'=>'');

		if($success){
			$result['url'] = @$resultArr['data']['url'];
		}

		return array($success, $result);
	}

    # notes: Attention! This domain is different from other APIs. Access Restriction: 10 seconds
	# "Query limit is 10 minutes"
	public function syncOriginalGameLogs($token = false) {
		$this->CI->utils->debug_log('OGPLUS (syncOriginalGameLogs)', $token, $this->original_gamelogs_table);	

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i:s");
    	# Query Exact end
    	if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
    	}

    	while ($queryDateTimeMax  > $queryDateTimeStart) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
			);

			$params = array(
				'Operator' => $this->x_operator,
				'Key' => $this->x_key,
				'SDate' => $queryDateTimeStart,
				'EDate' => $queryDateTimeEnd,
				'Provider' => $this->provider,
				'Exact' => 'True',
			);

			$this->data_api = TRUE;
			$this->method = self::POST;
			$result[] = $cur_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			sleep($this->sync_sleep_time);
			$queryDateTimeStart = $queryDateTimeEnd;
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
	    	}
		}

		return array("success" => true, "results"=>$result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra){

		$this->CI->utils->debug_log('OGPLUS (rebuildGameRecords)', $gameRecords);

		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$new_gameRecords[$index] = $gameRecords[$index];
        	$gameusername = explode("_",$record['membername']);			
			$new_gameRecords[$index]['gameusername'] = isset($gameusername[1])?$gameusername[1]:reset($gameusername);
            $new_gameRecords[$index]['external_uniqueid'] = isset($record['bettingcode'])?$record['bettingcode']:null;
			$new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
			
			if(isset($new_gameRecords[$index]['game_information']) && is_array($new_gameRecords[$index]['game_information'])){
				$new_gameRecords[$index]['game_information'] = json_encode($new_gameRecords[$index]['game_information']);
			}
        }

        $gameRecords = $new_gameRecords;
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->utils->debug_log('OGPLUS (processResultForSyncOriginalGameLogs)');

        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,null,true);
		$result = array('data_count'=>0);

		$gameRecords = !empty($resultArr)?$resultArr:[];
		if($success&&!empty($gameRecords)){
            $extra = ['response_result_id'=>$responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
			$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
		}

		return array($success, $result);
	}


    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		// $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
		$sqlTime='`original`.`bettingdate` >= ? AND `original`.`bettingdate` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`bettingdate` >= ? AND `original`.`bettingdate` <= ?';
        }

        if ($this->use_extra_info_date_field) {
            $sqlTime = 'original.' . $this->extra_info_date_field . ' >= ? AND original.' . $this->extra_info_date_field . ' <= ?';
        }
        $this->CI->utils->debug_log('OGPLUS sqlTime ===>', $sqlTime);


        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.response_result_id,
	original.gameid as table_id,
	original.bettingcode as round,
	original.gameusername as username,
	original.bettingamount as bet_amount,
	original.validbet as valid_bet,
	original.balance as after_balance,
	original.winloseamount as result_amount,
	original.bettingdate as start_at,
	original.bettingdate as end_at,
	original.bettingdate as bet_at,
	original.gamename as game_code,
	original.gamename as game_name,
	original.game_information,
	original.result,
	original.bet,
	original.updated_at,
	original.external_uniqueid,
	original.md5_sum,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.gamename = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.gameusername = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];
		
		$this->CI->utils->debug_log('OGPLUS (queryOriginalGameLogs) sql:', $sql);
		
		$this->CI->utils->debug_log('OGPLUS (queryOriginalGameLogs) params: ', $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round'],
        ];

        if(empty($row['md5_sum'])){
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
                'bet_for_cashback' => $row['valid_bet'],
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
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
        $bet_details = $this->processBetDetails($row);
        $row['bet_details'] = $bet_details;
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
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array('success' => true);
	}

	private function processBetDetails($gameRecords) {
		$bet_details = array();
		if(!empty($gameRecords)) {
			$bet_details = array(
				'tableID' => $gameRecords['table_id'],
				'result' => $gameRecords['result'],
				'betPlaced' => $gameRecords['bet'],
			);

			if(in_array($gameRecords['game_name'], self::ROULETTE_TYPES)) {
				$result = json_decode($gameRecords['game_information'], true);
				if (!empty($result)) {
					$bet_details['result'] = $result['value'];
				}
			}

			if(in_array($gameRecords['game_name'], self::BACCARAT_TYPES)) {
				$bet_details['playerCards'] = $this->getCardDetails($gameRecords['game_name'], $gameRecords['game_information'], true);
				$bet_details['bankerCards'] = $this->getCardDetails($gameRecords['game_name'], $gameRecords['game_information'], false);
			} elseif (in_array($gameRecords['game_name'], self::DT_TYPES)) {
				$bet_details['dragonCards'] = $this->getCardDetails($gameRecords['game_name'], $gameRecords['game_information'], true);
				$bet_details['tigerCards'] = $this->getCardDetails($gameRecords['game_name'], $gameRecords['game_information'], false);
			}

			return $bet_details;
		}

	}

	private function getCardDetails($gameType, $cardTypes, $playerCard = null) {

		$card_type_details = array(
			'1C' => '1 Cloves', '2C' => '2 Cloves', '3C' => '3 Cloves', '4C' => '4 Cloves', '5C' => '5 Cloves', '6C' => '6 Cloves', '7C' => '7 Cloves', '8C' => '8 Cloves', '9C' => '9 Cloves', '10C' => '10 Cloves', 'JC' => 'Jack Cloves', 'QC' => 'Queen Cloves', 'KC' => 'King Cloves', 'AC' => 'Ace Cloves',
			'1S' => '1 Spade', '2S' => '2 Spade', '3S' => '3 Spade', '4S' => '4 Spade', '5S' => '5 Spade', '6S' => '6 Spade', '7S' => '7 Spade', '8S' => '8 Spade', '9S' => '9 Spade', '10S' => '10 Spade', 'JS' => 'Jack Spade', 'QS' => 'Queen Spade', 'KS' => 'King Spade', 'AS' => 'Ace Spade',
			'1H' => '1 Heart', '2H' => '2 Heart', '3H' => '3 Heart', '4H' => '4 Heart', '5H' => '5 Heart', '6H' => '6 Heart', '7H' => '7 Heart', '8H' => '8 Heart', '9H' => '9 Heart', '10H' => '10 Heart', 'JH' => 'Jack Heart', 'QH' => 'Queen Heart', 'KH' => 'King Heart', 'AH' => 'Ace Heart',
			'1D' => '1 Diamonds', '2D' => '2 Diamonds', '3D' => '3 Diamonds', '4D' => '4 Diamonds', '5D' => '5 Diamonds', '6D' => '6 Diamonds', '7D' => '7 Diamonds', '8D' => '8 Diamonds', '9D' => '9 Diamonds', '10D' => '10 Diamonds', 'JD' => 'Jack Diamonds', 'QD' => 'Queen Diamonds', 'KD' => 'King Diamonds', 'AD' => 'Ace Diamonds', "" => "",
		);

		$cardTypes = json_decode($cardTypes, true);
		if(in_array($gameType, self::BACCARAT_TYPES)){
			if($playerCard == true) {
				$card_details = (explode(",",$cardTypes['playerCards']));

				foreach ($card_details as $card_detail) {
					$hand_card[] = $card_type_details[$card_detail];
				}

				return implode(", ", $hand_card);
			} else {
				$card_details = (explode(",",$cardTypes['bankerCards']));

				foreach ($card_details as $card_detail) {
					$hand_card[] = $card_type_details[$card_detail];
				}

				return implode(", ", $hand_card);
			}
		} elseif(in_array($gameType, self::DT_TYPES)) {
			if($playerCard == true) {
				$card_details = (explode(",",$cardTypes['dragonCards']));

				foreach ($card_details as $card_detail) {
					$hand_card[] = $card_type_details[$card_detail];
				}

				return implode(", ", $hand_card);
			} else {
				$card_details = (explode(",",$cardTypes['tigerCards']));

				foreach ($card_details as $card_detail) {
					$hand_card[] = $card_type_details[$card_detail];
				}

				return implode(", ", $hand_card);
			}
		} else {
			return $cardTypes;
		}
	}

	public function setOperatorBetSetting($limit_group = null) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForSetOperatorBetSetting',			
		);

		$code = $limit_group;
		if(!$code){
			$code = $this->bet_limit_id;
		}

		$params = array(
			'code' => $code,			
		);

		$this->method = self::POST;

		$this->CI->utils->debug_log('OGPLUS (setOperatorBetSetting) :', $params, $this->method);

		return $this->callApi(self::API_setMemberBetSetting, $params, $context);

	}

	public function processForSetOperatorBetSetting($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array('response_result_id' => $responseResultId, 'result' => $resultJson);

		return array($success, $result);

	}

	public function setMemberBetSetting($playerName) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForSetMemberBetSetting',
			'playerName' => $playerName
		);

		$params = array(
			'username' => $gameUsername,
			'code' => $this->bet_limit_id,
		);


		$this->method = self::POST;

		$this->CI->utils->debug_log('OGPLUS (setMemberBetSetting) :', $params);

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);

	}

	public function processForSetMemberBetSetting($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array('response_result_id' => $responseResultId, 'result' => $resultJson);

		return array($success, $result);

	}

	public function setMemberBetSettingByGameUsername($gameUsername, $limit_group = null) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForSetMemberBetSettingByGameUsername',
			'gameUsername' => $gameUsername
		);

		$code = $limit_group;
		if(!$code){
			$code = $this->bet_limit_id;
		}

		$params = array(
			'username' => $gameUsername,
			'code' => $code,
		);


		$this->method = self::POST;

		$this->CI->utils->debug_log('OGPLUS (setMemberBetSettingByGameUsername) :', $params);

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);

	}

	public function processForSetMemberBetSettingByGameUsername($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);
		$result = array('response_result_id' => $responseResultId, 'result' => $resultJson);

		return array($success, $result);

	}

	public function logout($playerName, $password = null) {
    	return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function getLauncherLanguage($language) {
        $lang = '';

        $language = strtolower($language);
        switch($language)
        {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
            case 'en':
                    $lang = 'en';
                    break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh':
            case 'zh-cn':
                    $lang = 'cn';
                    break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vn':
            case 'vi-vn':
                    $lang = 'vn';
                    break;
            case Language_function::INT_LANG_THAI:
			case 'th':
			case 'th_th':
			case 'th-th':
                $lang = 'th';
                break;
            case "jp":
            case "ja-JP":
            case "ja-en":
            case "jp-jp":
                $lang = 'jp';
                break;
			case Language_function::INT_LANG_INDONESIAN:
            case "id":
            case "id-id":
                $lang = 'id';
                break;
            default:
                $lang = 'zh';
                break;
        }

        return $lang;
	}

}

/*end of file*/