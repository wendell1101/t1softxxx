<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: MG PLUS
	* API docs: K2 API MG PLUS Common Wallet
	* API docs: MG PLUS K2 Common Wallet
	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
	* @integrator @garry.php.ph
**/

class Game_api_common_mgplus extends Abstract_game_api {
	const POST = 'POST';
	const PATCH = 'PATCH';
	const BAL = 'BAL';
	const GAME = 'GAME';
	const GET = 'GET';

	const STATUS_CODE_BAD_REQUEST = 400;
	const STATUS_CODE_UNAUTHORIZED = 401;
	const STATUS_CODE_CONFLICT_REQUEST = 409;
	const STATUS_CLOSED = 1;

	protected $do_update_sync_id; # if true, last sync id will be updated in the external system

	# Fields in og_v2_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'createddateutc',
        'gamestarttimeutc',
        'gameendtimeutc',
        'productplayerid',
        'playerid',
        'gamecode',
        'betamount',
        'payoutamount',
        'betstatus',
        'metadata',
    ];

    const SIMPLE_MD5_FIELDS_FOR_ORIGINAL=[
        'createddateutc',
        'gameendtimeutc',
        'playerid',
        'betamount',
        'payoutamount',
        'betstatus'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'betamount',
        'payoutamount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when aviaesport_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        // 'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
	];

	const NEED_QUERY = [self::API_queryPlayerBalance, self::API_isPlayerExist];

    const MGPLUS_LIVE_GAMES_GAMECODE = 'SMG_titaniumLiveGames_Baccarat_Playboy';

	//Case-insensitive
	const VAR_LIST = ['agentCode','playerId','productId','walletTransactionId'];

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->sts_api_url = $this->getSystemInfo('sts_api_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->agent_code = $this->getSystemInfo('agent_code');
		$this->agent_key = $this->getSystemInfo('agent_key');
		$this->method = null;
		$this->is_auth = false;
		$this->avail_token = false;
		$this->utc_offset = $this->getSystemInfo('utc_offset',8);
		$this->do_update_sync_id = true;
		$this->language = $this->getSystemInfo('language');
		$this->lobby_url = $this->getSystemInfo('lobby_url');
		$this->bank_url = $this->getSystemInfo('bank_url');

		$this->override_sfg_language = $this->getSystemInfo('override_sfg_language', '');

		$this->min_deposit = $this->getSystemInfo('min_deposit',0);
		$this->max_deposit = $this->getSystemInfo('min_deposit',0);
		$this->min_withdrawal = $this->getSystemInfo('min_withdrawal',0);
		$this->max_withdrawal = $this->getSystemInfo('max_withdrawal',0);

		$this->enabled_force_zero_balance = $this->getSystemInfo('enabled_force_zero_balance',false);
		$this->min_balance = $this->getSystemInfo('min_balance',10);

		$this->URI_MAP = array(
			self::API_generateToken => '/connect/token',
			self::API_createPlayer => '/agents/[agentCode]/players',
			self::API_queryPlayerBalance => '/agents/[agentCode]/players/[playerId]',
			self::API_isPlayerExist => '/agents/[agentCode]/players/[playerId]',
	        self::API_depositToGame => '/agents/[agentCode]/WalletTransactions',
	        self::API_withdrawFromGame => '/agents/[agentCode]/WalletTransactions',
	        self::API_queryTransaction => '/agents/[agentCode]/WalletTransactions/[walletTransactionId]',
	        self::API_queryForwardGame => '/agents/[agentCode]/players/[playerId]/sessions',
	        self::API_queryDemoGame => '/agents/[agentCode]/demoSessions',
			self::API_syncGameRecords => '/agents/[agentCode]/bets',
			self::API_blockPlayer => '/agents/[agentCode]/players/[playerId]/products',
			self::API_unblockPlayer => '/agents/[agentCode]/players/[playerId]/products',
			self::API_queryBetDetailLink => '/agents/[agentCode]/players/[playerId]/betVisualizers',
			self::API_queryFailedTransactions => '/agents/[agentCode]/failedTransactions',
			self::API_updateFailedTransactions => '/agents/[agentCode]/failedTransactions/[walletTransactionId]',
			self::API_queryGameListFromGameProvider => '/agents/[agentCode]/games',
		);

		$this->METHOD_MAP = array(
			self::API_generateToken => self::POST,
			self::API_createPlayer => self::POST,
			self::API_queryPlayerBalance => self::GET,
			self::API_isPlayerExist => self::GET,
			self::API_depositToGame => self::POST,
			self::API_withdrawFromGame => self::POST,
			self::API_queryTransaction => self::GET,
			self::API_queryForwardGame => self::POST,
			self::API_queryDemoGame => self::POST,
			self::API_syncGameRecords => self::GAME,
			self::API_blockPlayer => self::PATCH,
			self::API_unblockPlayer => self::PATCH,
			self::API_queryBetDetailLink => self::POST,
			self::API_queryFailedTransactions => self::GET,
			self::API_updateFailedTransactions => self::PATCH,
			self::API_queryGameListFromGameProvider => self::GET,
		);

		#SEAMLESS VARIABLE
		$this->enabled_token_verification = $this->getSystemInfo('enabled_token_verification', false);
		$this->token_verification = $this->getSystemInfo('token_verification');
		$this->enabled_player_token_checking = $this->getSystemInfo('enabled_player_token_checking', false);
		$this->force_failed_transaction = $this->getSystemInfo('force_failed_transaction', false);
		$this->patch_not_exist_failed_transactions = $this->getSystemInfo('patch_not_exist_failed_transactions', false);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	protected function getMethodName($apiName){
		return (isset($this->METHOD_MAP[$apiName])?$this->METHOD_MAP[$apiName]:self::GET);
	}

	protected function getHttpHeaders($params){

		$headers = [];
		if(!$this->is_auth){

			$auth = $this->getAvailableApiToken();

			$headers = [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer '.$auth,
			];

			if($this->method == self::PATCH) {
				$headers = [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Authorization' => 'Bearer '.$auth,
				];
			}
		}
		return $headers;
	}

	private function createVarMap($params) {
		$resultKey = array();
		$resultVal = array();
		foreach (self::VAR_LIST as $var) {
			if (array_key_exists($var, $params) && !empty($params[$var])) {
				$resultKey[] = '[' . $var . ']';
				if ($var == 'playerName') {
					$resultVal[] = urlencode(strtoupper($params[$var]));
				} else {
					$resultVal[] = urlencode($params[$var]);
				}
			}
		}
		return array($resultKey, $resultVal);
	}

	public function generateUrl($apiName, $params) {
		$this->method = $this->getMethodName($apiName);

		$apiUri = $this->URI_MAP[$apiName];
		list($keys, $values) = $this->createVarMap($params);
		//Case-insensitive
		$apiUri = str_ireplace($keys, $values, $apiUri);
		$url = $this->api_url . $apiUri;

		if($apiName == self::API_generateToken){
			$url = $this->sts_api_url . $apiUri;
		}

		if(in_array($apiName,self::NEED_QUERY)){
			$url .= '?properties=balance';
		}

		if( $apiName == self::API_queryTransaction ){
			$url = dirname($url);
			$url .= "?idempotencyKey={$params['walletTransactionId']}";
		}

		return $url;
	}

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return false;
    }

	protected function customHttpCall($ch, $params) {

		switch ($this->method){
			case self::POST:
				$fields=http_build_query($params);
				if(array_key_exists('type', $params) && $params['type']==='Withdraw'){
					//withdraw
					if(array_key_exists('amount', $params) && $params['amount']===null){
						//append amount
						$fields.='&amount=';
						$this->CI->utils->debug_log('withdraw all amount', $fields);
					}
				}
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$this->CI->utils->debug_log('MG PLUS REQUEST POST FIELD: ', $fields);
				break;
			case self::PATCH:
				$fields=http_build_query($params);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::PATCH);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$this->CI->utils->debug_log('MG PLUS REQUEST PATCH FIELD: ', $fields);
				break;
			case self::GAME:
				$params = array(
					'limit' => $params['limit'],
					'startingAfter' => $params['startingAfter']
				);
				$fields=http_build_query($params);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::GET);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$this->CI->utils->debug_log('MG PLUS REQUEST GET FIELD: ', $fields);
				break;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode) {
		$success = false;
		if(@$statusCode == 200 || @$statusCode == 201){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('MGPLUS_API got error ', $responseResultId,'result', $resultArr);
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
	 * Token will be invalid each 60 minutes
	 */
	public function generateToken(){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGenerateToken',
			'playerId'=>null,
		);

		$params = array(
			'grant_type' => 'client_credentials',
			'client_id' => $this->agent_code,
			'client_secret' => $this->agent_key,
		);

		$this->is_auth = TRUE;

		$temp = $this->method;

		$return = $this->callApi(self::API_generateToken, $params, $context);

		$this->method = $temp;
		$this->is_auth = FALSE;

		return $return;
	}

	public function processResultForGenerateToken($params){
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

		if($success){
			$api_token = @$resultArr['access_token'];
			# Token will be invalid each 30 minutes
			$token_timeout = new DateTime($this->utils->getNowForMysql());
			$minutes = ((int)$resultArr['expires_in']/60)-1;
			$token_timeout->modify("+".$minutes." minutes");
			$result['api_token']=$api_token;
			//$this->avail_token = true;
			$result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');


		}

		return array($success,$result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
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
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername, # max 50 chars - _
		);

		//$this->method = self::POST;

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}

		return array($success, $result);
	}

	public function blockPlayer($playerName) {
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername,
			'isLock' => 'true',
		);

		//$this->method = self::PATCH;

		return $this->callApi(self::API_blockPlayer, $params, $context);
	}

	public function processResultForBlockPlayer($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

		if($success){
			# block player on game
			$this->blockUsernameInDB($gameUsername);
			$result['message'] = "player blocked!";
		}

		return array($success, $result);
	}

	public function unblockPlayer($playerName) {
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername,
			'isLock' => 'false',
		);

		//$this->method = self::PATCH;

		return $this->callApi(self::API_unblockPlayer, $params, $context);
	}

	public function processResultForUnblockPlayer($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

		if($success){
			# block player on game
			$this->unblockUsernameInDB($gameUsername);
			$result['message'] = "player unblocked!";
		}

		return array($success, $result);
	}


	private function round_down($number, $precision = 3){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function queryPlayerBalance($playerName) {
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$this->is_auth = !$this->avail_token ? false : true;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername,
		);

		//$this->method = self::BAL;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

		if($success){
			$result['balance'] = $this->gameAmountToDB(@floatval($resultArr['balance']['total']));
			$result['balance'] = $this->round_down($result['balance']);

			#For ticket OGP-21729: GW ONLY
			if($this->enabled_force_zero_balance){
				if( $this->utils->compareResultFloat($result['balance'], '<', $this->min_balance) ){
					$result['real_balance'] = $result['balance'];
					$result['balance'] = 0;
				}
			}
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
        if ($this->isSeamLessGame()) {
             return parent::isPlayerExist($playerName);
        } else {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$password = $this->getPasswordFromPlayer($playerName);
			$result_create_player = $this->createPlayer($playerName, $playerId, $password, $email = null, $extra = null);
			//$result_create_player sample result: exists {"success":true,"player":"testt1dev","exists":true,"response_result_id":4918127}
			$existsValue = $result_create_player["exists"]; 

			return ['success'=>true, 'exists'=>$existsValue];
        }
    }

    public function processResultForIsPlayerExist($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array();

		if($success){
			$result['exists'] = true;
		}else{
			if(@$statusCode == '404'){ # 404 Player not found
				$success = true;
				$result['exists'] = false;
			}else{
				$result['exists'] = null;
			}
		}

		return array($success, $result);
    }

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }
        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;
		$this->is_auth = !$this->avail_token ? false : true;

		$amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
        );

		$params = array(
			'agentCode' => $this->agent_code,
			'amount' => $amount,
			'externalTransactionId' => $external_transaction_id,
			'playerId' => $gameUsername,
			'type' => 'Deposit',
		);

		if($this->utils->compareResultFloat($this->min_deposit, '>', 0)){
			if($this->utils->compareResultFloat($amount, '<', $this->min_deposit)){
				$status_error_code = 409;
				return array(
					"success" => false,
					"message" => lang("promo.msg1"),
					"reason_id" => $this->getReasons($status_error_code),
					'response_result_id' => null,
					'transfer_status'=>self::COMMON_TRANSACTION_STATUS_DECLINED,
				);
			}
		}

		if($this->utils->compareResultFloat($this->max_deposit, '>', 0)){
			if($this->utils->compareResultFloat($amount, '>', $this->max_deposit)){
				$status_error_code = 409;
				return array(
					"success" => false,
					"message" => lang("promo.msg1"),
					"reason_id" => $this->getReasons($status_error_code),
					'response_result_id' => null,
					'transfer_status'=>self::COMMON_TRANSACTION_STATUS_DECLINED,
				);
			}
		}

		//$this->method = self::POST;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['external_transaction_id'] = $resultArr['id'];
			// $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
			if(isset($resultArr['status'])){
				$result['transfer_status'] = $this->checkSuccessResponseStatus($resultArr['status']);
			}
        }else{
            $result['transfer_status'] = $this->getTransferStatus($statusCode);
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	private function getTransferStatus($statusCode){
		switch ($statusCode) {
			case self::STATUS_CODE_BAD_REQUEST:
			case self::STATUS_CODE_UNAUTHORIZED:
			case self::STATUS_CODE_CONFLICT_REQUEST:
				return self::COMMON_TRANSACTION_STATUS_DECLINED;
				break;
			default:
                return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
		}
	}

	private function getReasons($statusCode){
		switch ($statusCode) {
			case 400:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 401:
				return self::REASON_INVALID_KEY;
				break;
			case 404:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 409:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 500:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}
	/**
	 * withdraw all from game, ignore amount
	 * @param  string $playerName
	 * @param  double $amount
	 * @param  string $transfer_secure_id
	 * @return
	 */
    public function withdrawAllFromGame($playerName, $amount, $transfer_secure_id){
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => null,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'agentCode' => $this->agent_code,
			'amount' => null,
			'externalTransactionId' => $external_transaction_id,
			'playerId' => $gameUsername,
			'type' => 'Withdraw',
		);

		//$this->method = self::POST;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;
		$this->is_auth = !$this->avail_token ? false : true;

		$amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'agentCode' => $this->agent_code,
			'amount' => $amount,
			'externalTransactionId' => $external_transaction_id,
			'playerId' => $gameUsername,
			'type' => 'Withdraw',
		);

		if($this->utils->compareResultFloat($this->min_withdrawal, '>', 0)){
			if($this->utils->compareResultFloat($amount, '<', $this->min_withdrawal)){
				$status_error_code = 409;
				return array(
					"success" => false,
					"message" => lang("promo.msg1"),
					"reason_id" => $this->getReasons($status_error_code),
					'response_result_id' => null,
					'transfer_status'=>self::COMMON_TRANSACTION_STATUS_DECLINED,
				);
			}
		}

		if($this->utils->compareResultFloat($this->max_withdrawal, '>', 0)){
			if($this->utils->compareResultFloat($amount, '>', $this->max_withdrawal)){
				$status_error_code = 409;
				return array(
					"success" => false,
					"message" => lang("promo.msg1"),
					"reason_id" => $this->getReasons($status_error_code),
					'response_result_id' => null,
					'transfer_status'=>self::COMMON_TRANSACTION_STATUS_DECLINED,
				);
			}
		}

		//$this->method = self::POST;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'real_amount'=>null,
		);

		if ($success) {
			$result['external_transaction_id'] = $resultArr['id'];
			// $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['real_amount']=@$resultArr['amount'];
			$result['didnot_insert_game_logs']=true;
			if(isset($resultArr['status'])){
				$result['transfer_status'] = $this->checkSuccessResponseStatus($resultArr['status']);
			}
        }else{
            $result['transfer_status'] = $this->getTransferStatus($statusCode);
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	const STATUS_SUCCESS = 4;
	public function queryTransaction($transactionId, $extra) {
		$gameUsername = null;
		$playerId = null;
		if(isset($extra['playerName'])){
			$playerName=$extra['playerName'];
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		}
		if(isset($extra['playerId'])){
			$playerId=$extra['playerId'];
		}

		if(isset($extra['secure_id'])){
			$transactionId = $extra['secure_id'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);

		$params = array(
			'agentCode' => $this->agent_code,
			'walletTransactionId' => $transactionId,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			// $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			if(isset($resultJsonArr['status'])){
				$result['status'] = $this->checkSuccessResponseStatus($resultJsonArr['status']);
			}
		} else {
            // $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
			if(isset($resultJsonArr['error']['message'])){
				$result['error_message'] = $resultJsonArr['error']['message'];
			}
		}

		return array($success, $result);
	}

	public function checkSuccessResponseStatus($responseStatus){
		switch (strtolower($responseStatus)) {
			case 'inprogress':
			case 'unconfirmed':
				$status =  self::COMMON_TRANSACTION_STATUS_PROCESSING;
				break;
			case 'succeeded':
				$status =  self::COMMON_TRANSACTION_STATUS_APPROVED;
				$success = true;
				break;
			case 'failed':
				$status =  self::COMMON_TRANSACTION_STATUS_DECLINED;
				break;

			default:
				$status =  self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				break;
		}
		return $status;
	}

	public function getLauncherLanguage($language){
		$lang='';
        switch ($language) {
            case 1:
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh-CN'; // chinese
                break;
			case 3:
			case 'id-id':
				$lang = 'id-ID'; // indo
				break;
			case 4:
			case 'vi-vi':
            case 'vi-vn':
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
				$lang = 'vi-VN'; // vietnamese
				break;
			case 5:
			case 'ko-kr':
				$lang = 'ko-kr'; // korean
				break;
			case 6:
			case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
				$lang = 'th-TH'; // thailand
				break;
			case 7:
			case 'hi-in':
				$lang = 'hi-IN'; // india
				break;
			case Language_function::INT_LANG_PORTUGUESE:
			case 'pt':
			case 'pt-br':
				$lang = 'pt-BR'; // portuguese
				break;
			case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case Language_function::PLAYER_LANG_SPANISH :
                $lang = 'es-ES';
                break;
            default:
                $lang = 'en-US'; // default as english
                break;
        }
        return $lang;
	}

	public function queryForwardGame($playerName, $extra = null) {
		if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("MG PLUS ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->is_auth = !$this->avail_token ? false : true;
		$liveDealerExtraInfo = $this->getSystemInfo('default_live_dealer_lobby_code');
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];

		if(isset($extra['language']) && !empty($extra['language'])){
			$this->CI->utils->debug_log('MG PLUS Language RC: ', $extra['language']);
            $language = $this->getLauncherLanguage($extra['language']);
        }else{
            $language = $this->getLauncherLanguage($this->language);
        }

		// if(isset($this->lobby_url) && !empty($this->lobby_url)) {
  //           $this->lobby_url = $this->getSystemInfo('lobby_url');
  //       } else {
  //           $this->lobby_url = $extra['is_mobile'] == 'true'
  //                            ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
  //                            : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');
  //       }

		/* if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		if(empty($this->bank_url)){
			$this->bank_url = $this->utils->getSystemUrl('player','/player_center/dashboard/cashier#memberCenter');
			$this->appendCurrentDbOnUrl($this->bank_url);
		} */

		$game_code = $extra['game_code'];
		if ($extra['game_code'] === 'null' || empty($extra['game_code'])) {
			$game_code = !empty($liveDealerExtraInfo) ? $liveDealerExtraInfo : self::MGPLUS_LIVE_GAMES_GAMECODE;
		}

		/* if (isset($extra['home_link'])) {
			$this->lobby_url = $extra['home_link'];
		}

		if (isset($extra['cashier_link'])) {
			$this->bank_url = $extra['cashier_link'];
		} */

        if (!empty($this->lobby_url)) {
            $lobby_url = $this->lobby_url;
        } else {
            $lobby_url = !empty($extra['home_link']) ? $extra['home_link'] : $this->getHomeLink($is_mobile);
        }

        if (!empty($this->bank_url)) {
            $bank_url = $this->bank_url;
        } else {
            if (!empty($extra['cashier_link'])) {
                $bank_url = $extra['cashier_link'];
            } else {
                $bank_url = $this->utils->getSystemUrl('player', '/player_center/dashboard/cashier#memberCenter');
            }
        }

		//extra checking for home link
        if(isset($extra['extra']['home_link'])) {
            $lobby_url = $extra['extra']['home_link'];
        }
        //extra checking for cashier link
        if(isset($extra['extra']['cashier_link'])) {
            $bank_url = $extra['extra']['cashier_link'];
        }

		if(strpos($game_code, 'SFG_')===FALSE){
			$is_sfg = false;
		}else{
			$is_sfg = true;
		}

		if(!empty($this->override_sfg_language) && $is_sfg){
			$language = $this->override_sfg_language;
		}

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $valid_content_type = [
            'Game',
            'Tournament',
            'Launchpad'
        ];
        $content_type = $extra['game_type'];
        if(!in_array($content_type, $valid_content_type)) {
            $content_type = 'Game';
        }


		$params = array(
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername,
			'contentCode' => $game_code,
			'contentType' => $content_type, # default:: Game | Tournament
			'langCode' => $language,
			'platform' => $extra['is_mobile']?'Mobile':'Desktop',
			'homeUrl' => $lobby_url,
			'bankUrl' => $bank_url,
		);
		#remove homeUrl when disable_home_link is set to TRUE
		if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['homeUrl']);
        }

		if($this->force_disable_home_link){
            unset($params['homeUrl']);
        }

		$this->CI->utils->debug_log('PARAMS ==>', $params);

		//$this->method = self::POST;

		if ($extra['game_mode'] != 'real'){
			return $this->callApi(self::API_queryDemoGame, $params, $context);

		} else {
			return $this->callApi(self::API_queryForwardGame, $params, $context);
		}
	}

	public function processResultForQueryForwardGame($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array('url'=>'');

		if($success){
			$result['url'] = @$resultArr['gameURL'];
		}

		return array($success, $result);
	}

    # notes: Attention! This domain is different from other APIs. Access Restriction: 10 seconds
	# "Query limit is 10 minutes"
	public function syncOriginalGameLogs($token = false) {
		$ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');

		// if ($ignore_public_sync == true) {
		// 	$this->CI->utils->debug_log('ignore public sync'); // ignore public sync
		// 	return array('success' => true);
		// }

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->format('Y-m-d H:i:s');
		$last_sync_id = $this->getLastSyncIdFromTokenOrDB($token);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $queryDateTimeStart,
			'endDate' => $queryDateTimeEnd
		);

		$params = array(
			'agentCode' => $this->agent_code,
			'limit' => 20000, # max return
			'startingAfter' => $last_sync_id,
		);

		//$this->method = self::GAME;

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array('data_count'=>0);
		$gameRecords = !empty($resultArr) ? $resultArr : [];
		if($success && !empty($gameRecords)){
            $extra = ['response_result_id'=>$responseResultId];
			$this->rebuildGameRecords($gameRecords,$extra);

			$lastRecord = array_values(array_slice($gameRecords, -1))[0];

			if(array_key_exists('betuid', $lastRecord) && !empty($lastRecord['betuid'])){
				$result['last_sync_id'] = $lastRecord['betuid'];
			}

            if(array_key_exists('betuid', $lastRecord) && !empty($lastRecord['betuid']) && $this->do_update_sync_id) {
			  $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $lastRecord['betuid']);
            } else {
              $this->CI->utils->error_log("Error: Last sync index not updated");
            }

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                $this->getMd5FieldsForOriginal(),
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

	public function getMd5FieldsForOriginal()
    {

        if($this->use_simplified_md5){
            return self::SIMPLE_MD5_FIELDS_FOR_ORIGINAL;
        }

        return self::MD5_FIELDS_FOR_ORIGINAL;
    }

    public function getMD5Fields(){
        return [
            'md5_fields_for_original'=>$this->getMd5FieldsForOriginal(),
            'md5_float_fields_for_original'=>self::MD5_FLOAT_AMOUNT_FIELDS,
            'md5_fields_for_merge'=>self::MD5_FIELDS_FOR_MERGE,
            'md5_float_fields_for_merge'=>self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE,
        ];
    }

	private function rebuildGameRecords(&$gameRecords,$extra)
	{
		if(! empty($gameRecords)){

			foreach($gameRecords as $index => $record){
				$data['betuid'] = isset($record['betUID'])?$record['betUID']:null;
				$data['createddateutc'] = isset($record['createdDateUTC'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($record['createdDateUTC']))):null;
				$data['gamestarttimeutc'] = isset($record['gameStartTimeUTC'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($record['gameStartTimeUTC']))):null;
				$data['gameendtimeutc'] = isset($record['gameEndTimeUTC'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($record['gameEndTimeUTC']))):null;
				$data['productid'] = isset($record['productId'])?$record['productId']:null;
				$data['productplayerid'] = isset($record['productPlayerId'])?$record['productPlayerId']:null;
				$data['playerid'] = isset($record['playerId'])?$record['playerId']:null;
				$data['gamecode'] = isset($record['gameCode'])?$record['gameCode']:null;
				$data['platform'] = isset($record['platform'])?$record['platform']:null;
				$data['currency'] = isset($record['currency'])?$record['currency']:null;
				$data['betamount'] = isset($record['betAmount'])?$record['betAmount']:null;
				$data['payoutamount'] = isset($record['payoutAmount'])?$record['payoutAmount']:null;
				$data['betstatus'] = isset($record['betStatus'])?$record['betStatus']:null;
				$data['pca'] = isset($record['PCA'])?$record['PCA']:null;
				$data['metadata'] = isset($record['metadata'])?json_encode($record['metadata'],true):null;
				$data['external_uniqueid'] = isset($record['betUID'])?$record['betUID']:null;
				$data['response_result_id'] = $extra['response_result_id'];

				$gameRecords[$index] = $data;
				unset($data);
			}

		}
	}


    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }
                # update last vendorid
                // if((count($rows)-1) == $key){
                //     $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $record['betuid']);
                // }
                $dataCount++;
                unset($record);
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
        $sqlTime='`mg`.`gameendtimeutc` >= ?
          AND `mg`.`gameendtimeutc` <= ?';
        if($use_bet_time){
            $sqlTime='`mg`.`gamestarttimeutc` >= ?
          AND `mg`.`gamestarttimeutc` <= ?';
        }

        $sql = <<<EOD
			SELECT
				mg.id as sync_index,
				mg.response_result_id,
				mg.betuid as round,
				mg.playerid as username,
				mg.betamount as bet_amount,
				mg.betamount as valid_bet,
				mg.payoutamount as result_amount,
				mg.gamestarttimeutc as start_at,
				mg.gameendtimeutc as end_at,
				mg.gamestarttimeutc as bet_at,
				mg.gamecode as game_code,
				mg.gamecode as game_name,
				mg.updated_at,
				mg.external_uniqueid,
				mg.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_table as mg
			LEFT JOIN game_description as gd ON mg.gamecode = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON mg.playerid = game_provider_auth.login_name
			AND game_provider_auth.game_provider_id=?
			WHERE
            {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

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
                'bet_amount' => $this->gameAmountToDB($row['valid_bet']),
                'result_amount' => $this->gameAmountToDB(($row['result_amount']-$row['valid_bet'])),
                'bet_for_cashback' => $this->gameAmountToDB($row['valid_bet']),
                'real_betting_amount' => $this->gameAmountToDB($row['bet_amount']),
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
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
            'bet_details' => [],
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

	public function queryBetDetailLink($playerName, $betid = null, $extra = null){
		if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerName, $betid, $extra);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->is_auth = !$this->avail_token ? false : true;

		if(isset($extra['language']) && !empty($extra['language'])){
            $language = $this->getLauncherLanguage($extra['language']);
        }else{
            $language = $this->getLauncherLanguage($this->language);
        }

		$context = array(
			'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'betUid' => $betid,
        );

		$params = array(
			'agentCode' => $this->agent_code,
			'playerId' => $gameUsername,
			'betUid' => $betid,
			'langCode' => $language,
			'utcOffset' => $this->utc_offset,
		);

		//$this->method = self::POST;

		return $this->callApi(self::API_queryBetDetailLink, $params, $context);

	}

	public function processResultForQueryBetDetailLink($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$betUid = @$this->getVariableFromContext($params, 'betUid');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array('url'=>'');

		if($success){
			$resultArr = reset($resultArr);
			$result['url'] = $resultArr['url'];
		}

		return array($success, $result);
	}

	public function queryFailedTransactions($patchFailedTransactions = false) {
		$this->is_auth = !$this->avail_token ? false : true;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryFailedTransactions',
			'patchFailedTransactions' => $patchFailedTransactions
		);

		$params = array(
			'agentCode' => $this->agent_code,
		);

		return $this->callApi(self::API_queryFailedTransactions, $params, $context);
	}

	public function processResultForQueryFailedTransactions($params) {
		$this->CI->load->model(array('common_seamless_wallet_transactions','player_model', 'original_seamless_wallet_transactions'));
		$patchFailedTransactions = $this->getVariableFromContext($params, 'patchFailedTransactions');
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$patchTransExistOnDB = array();
		$patchTransNotExistOnDB = array();
		$patchTransNoBetId = array();
		if(!empty($resultArr)){
			foreach ($resultArr as $failedTrans) {
				$txnId = isset($failedTrans['txnId']) ? $failedTrans['txnId'] : null;
				$betId = isset($failedTrans['betId']) ? $failedTrans['betId'] : null;
				$actionType = isset($failedTrans['betId']) ? $failedTrans['actionType'] : null;
				if(!$this->api->use_monthly_transactions_table){
					$bet = (array)$this->CI->common_seamless_wallet_transactions->getTransactionObjectByField($this->getPlatformCode(), $betId, 'transaction_id');
				} else {
					$bet = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['transaction_id'=> $betId, 'game_platform_id' => $this->getPlatformCode()]);
					if(empty($bet)){
						$bet = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['transaction_id'=> $betId, 'game_platform_id' => $this->getPlatformCode()]);
					}
				}
				if(!empty($bet)){
					if($txnId){
						if(!$this->api->use_monthly_transactions_table){
							$isTxnIdExist = $this->CI->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(), $txnId);
						} else {
							$isTxnIdExist = $this->CI->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(), $txnId, $this->api->getTransactionsTable());
							if(!$isTxnIdExist){
								$isTxnIdExist = $this->CI->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(), $txnId, $this->api->getTransactionsPreviousTable());
							}
						}
						
						if($isTxnIdExist){
							if($patchFailedTransactions && $actionType != "rollback"){
								$patch = $this->updateFailedTransactions($txnId);
								if($patch['success']){
									$patchTransExistOnDB[] = $failedTrans;
								}
							}
							if($patchFailedTransactions && $actionType == "rollback"){
								$failedTrans['response_result_id'] = $responseResultId;
								$success = $this->CI->player_model->lockAndTransForPlayerBalance($bet['player_id'], function() use($failedTrans, $bet) {
				        			$amount = isset($failedTrans['amount']) ? $this->gameAmountToDB($failedTrans['amount']) : null;
									$playerName = $this->getPlayerUsernameByGameUsername($failedTrans['playerId']);
									$beforeBalance = $this->getPlayerBalance($playerName);

									$uniqueid = "RB".$failedTrans['txnId'];
					                $uniqueid_of_seamless_service=$this->getPlatformCode().'-'.$uniqueid;       
					                $this->CI->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service);
					                $exist = $this->CI->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsTable(), $uniqueid);
        							if($exist){ 
        								return true;#return success if exist
        							} else {
        								if($this->api->use_monthly_transactions_table){
        									$exist = $this->CI->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsPreviousTable(), $uniqueid);
        									if($exist){
        										return true;
        									}
        								}
        							}

								    if($this->CI->utils->compareResultFloat($amount, '>', 0)) {
								        if($this->CI->utils->getConfig('enable_seamless_single_wallet')) {
					                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
					                        $success = $this->CI->wallet_model->transferSeamlessSingleWallet($bet['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
					                    } else {
					                        $success = $this->CI->wallet_model->incSubWallet($bet['player_id'], $this->getPlatformCode(), $amount);
					                    }
						            } elseif ($this->CI->utils->compareResultFloat($amount, '=', 0)) {
						                $success = true;#allowed amount 0
						            } else { #default error
						                $success = false;
						            }

						            if($success){
						            	$success = false;
						                $afterBalance = $this->getPlayerBalance($playerName);
						                $failedTrans['before_balance'] = $this->dBtoGameAmount($beforeBalance);
						                $failedTrans['after_balance'] = $this->dBtoGameAmount($afterBalance);
						                $failedTrans['player_id'] = $bet['player_id'];
						                $failedTrans['creationTime'] = $this->utils->getNowForMysql();
						                $failedTrans['txnId'] = "RB".$failedTrans['txnId'];#override unique id, same as bet
						                $failedTrans['completed'] =  self::STATUS_CLOSED; #ovver ride status
						                $transId = $this->processFailedTransactionData($failedTrans);
	                					if($transId){
	                						$success = true;
	                					}
						            }
						            return $success;
				                });

				                if($success){
									$patch = $this->updateFailedTransactions($txnId);
									if($patch['success']){
										$patchTransExistOnDB[] = $failedTrans;
									}
				                }
							}
						} else {
							if($patchFailedTransactions && $this->patch_not_exist_failed_transactions){
								$failedTrans['response_result_id'] = $responseResultId;
								$success = $this->CI->player_model->lockAndTransForPlayerBalance($bet['player_id'], function() use($failedTrans, $bet) {
				        			$amount = isset($failedTrans['amount']) ? $this->gameAmountToDB($failedTrans['amount']) : null;
									$playerName = $this->getPlayerUsernameByGameUsername($failedTrans['playerId']);
									$beforeBalance = $this->getPlayerBalance($playerName);

									$uniqueid = $failedTrans['txnId'];
					                $uniqueid_of_seamless_service=$this->getPlatformCode().'-'.$uniqueid;       
					                $this->CI->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 

								    if($this->CI->utils->compareResultFloat($amount, '>', 0)) {
								        if($this->CI->utils->getConfig('enable_seamless_single_wallet')) {
					                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
					                        $success = $this->CI->wallet_model->transferSeamlessSingleWallet($bet['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
					                    } else {
					                        $success = $this->CI->wallet_model->incSubWallet($bet['player_id'], $this->getPlatformCode(), $amount);
					                    }
						            } elseif ($this->CI->utils->compareResultFloat($amount, '=', 0)) {
						                $success = true;#allowed amount 0
						            } else { #default error
						                $success = false;
						            }

						            if($success){
						            	$success = false;
						                $afterBalance = $this->getPlayerBalance($playerName);
						                $failedTrans['before_balance'] = $this->dBtoGameAmount($beforeBalance);
						                $failedTrans['after_balance'] = $this->dBtoGameAmount($afterBalance);
						                $failedTrans['player_id'] = $bet['player_id'];
						                $failedTrans['creationTime'] = $bet['start_at'];
						                $transId = $this->processFailedTransactionData($failedTrans);
	                					if($transId){
	                						$success = true;
	                					}
						            }
						            return $success;
				                });

				                if($success){
									$patch = $this->updateFailedTransactions($txnId);
									if($patch['success']){
										$patchTransNotExistOnDB[] = $failedTrans;
									}
				                }
				            }
						}
					}
				} else {
					if($patchFailedTransactions){
						$patch = $this->updateFailedTransactions($txnId);
						if($patch['success']){
							$patchTransNoBetId[] = $failedTrans;
						}
					}
				}
			}
		}
		$result['patchFailedTransactions'] = $patchFailedTransactions;
		$result['failed_trans'] = $resultArr;
		$result['patch_trans_exist_on_db'] = $patchTransExistOnDB;
		$result['patch_trans_not_exist_on_db'] = $patchTransNotExistOnDB;
		$result['patch_trans_no_bet_id'] = $patchTransNoBetId;
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		return array($success, $result);
	}

	private function getPlayerBalance($playerName){
        if($this->CI->utils->getConfig('enable_seamless_single_wallet')) {
            $player_id = $this->getPlayerIdFromUsername($playerName);
            $seamless_balance = 0;
            $seamless_reason_id = null;
            $this->CI->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            return $seamless_balance;
        }
        else {
            $get_bal_req = $this->queryPlayerBalance($playerName);
            if($get_bal_req['success']) {
                return $get_bal_req['balance'];
            }
            else {
                return false;
            }
        }
    }

	public function processFailedTransactionData($failedTransData){
        $dataToInsert = array(
            "game_platform_id" => $this->getPlatformCode(),
            "amount" => isset($failedTransData['amount']) ? $failedTransData['amount'] : NULL,
            "before_balance" => isset($failedTransData['before_balance']) ? $failedTransData['before_balance'] : NULL,
            "after_balance" => isset($failedTransData['after_balance']) ? $failedTransData['after_balance'] : NULL,
            "player_id" => isset($failedTransData['player_id']) ? $failedTransData['player_id'] : NULL,
            "game_id" => isset($failedTransData['contentCode']) ? $failedTransData['contentCode'] : NULL,
            "transaction_type" => isset($failedTransData['actionType']) ? strtoupper($failedTransData['actionType']) : NULL,
            "status" => isset($failedTransData['completed']) ? $failedTransData['completed'] : NULL,
            "response_result_id" => isset($failedTransData['response_result_id']) ? $failedTransData['response_result_id'] : NULL,
            "external_unique_id" => isset($failedTransData['txnId']) ? $failedTransData['txnId'] : NULL,
            "extra_info" => json_encode($failedTransData),
            "start_at" => isset($failedTransData['creationTime']) ? $failedTransData['creationTime'] : NULL,
            "end_at" => isset($failedTransData['creationTime']) ? $failedTransData['creationTime'] : NULL,
            "round_id" => isset($failedTransData['roundId']) ? $failedTransData['roundId'] : NULL,
            "transaction_id" => isset($failedTransData['betId']) ? $failedTransData['betId'] : NULL, #mark as bet id
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
        );
        $dataToInsert['md5_sum'] = $this->CI->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->CI->common_seamless_wallet_transactions->insertData($this->api->getTransactionsTable(),$dataToInsert);
        return $transId;
    }



	public function updateFailedTransactions($txnId = "AEBQAAQAQ7ERGDAAAAAABEB3T3FQ4AAAAA") {
		$this->is_auth = !$this->avail_token ? false : true;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdateFailedTransactions',
		);

		$params = array(
			'agentCode' => $this->agent_code,
			'walletTransactionId' => $txnId
		);

		return $this->callApi(self::API_updateFailedTransactions, $params, $context);
	}

	public function processResultForUpdateFailedTransactions($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		return array($success, $resultArr);
	}

    public function login($playerName, $password = null, $extra = null) {
    	return $this->returnUnimplemented();
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

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

    public function queryGameListFromGameProvider($extra = null) {
        $this->is_auth = !$this->avail_token ? false : true;

        $params = [
            'agentCode' => $this->agent_code,
        ];

        if (isset($extra['fromReleaseDateUtc'])) {
            $params['fromReleaseDateUtc'] = $extra['fromReleaseDateUtc'];
        }

        if (isset($extra['toReleaseDateUtc'])) {
            $params['toReleaseDateUtc'] = $extra['toReleaseDateUtc'];
        }

        if (isset($extra['channelCode'])) {
            $params['channelCode'] = $extra['channelCode'];
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = !empty($resultArr) ? $resultArr : [];
        }

        return array($success, $result);
    }
}

/*end of file*/