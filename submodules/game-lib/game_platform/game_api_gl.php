<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 * Global Lottery Web API (GL_API) Interfacing library
 * @category	game_platform
 * @version		0.9
 * @copyright	tot 2018
 */
class game_api_gl extends Abstract_game_api {

	const METHOD_POST				= "POST";
	const METHOD_POST_FORCE_JSON	= "POST_FORCE_JSON";
	const METHOD_GET				= "GET";
	const ORIGINAL_LOGS_TABLE_NAME  = "gl_game_logs";

	# Winning status(0:Not assigned yet; 1:Win ;2:Lose)
	const GAME_PROCESSING = 0;
	const GAME_WIN = 1;
	const GAME_LOSS = 2;

	# Cancel or not(0:not cancel yet; 1:user cancel; 2:system cancel
	const GAME_NOT_CANCEL = 0;
	const GAME_USER_CANCEL = 1;
	const GAME_SYSTEM_CANCEL = 2;
	const LOST_AND_FOUND_MAX_DAYS_BACK = 7; # Query lostAndFound for records max 7 days back
	# Based on bonus(field) from api
	const EMPTY_RESULT_AMOUNT = '0.000000';

	const HTTP_MULTIPLE_REQUEST_CODE = 429;

	const MD5_FIELDS_FOR_ORIGINAL=[
		'project_id',
		'user_id',
		'lottery_id',
		'update_time',
		'deduct_time',
		'bonus_time',
		'cancel_time',
		'is_cancel',
		'is_getprize',
		'prize_status',
		'total_price',
		'bonus'
	];
	const MD5_FLOAT_AMOUNT_FIELDS=[
		'total_price',
		'bonus'
	];

	const MD5_FIELDS_FOR_MERGE=[
		'project_id',
		'user_id',
		'lottery_id',
		'update_time',
		'deduct_time',
		'bonus_time',
		'cancel_time',
		'is_cancel',
		'is_getprize',
		'prize_status',
		'total_price',
		'bonus'
	];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
		'total_price',
		'bonus'
	];

	const SETTLED_PERIOD = 2;
	const BETTING_PERIOD = 1;
	const ASSIGNED = 1;

    const API_PATHS = array(
        // self::API_TransferCredit => 'transfer_player_fund',
        // self::API_queryTransaction => 'query_transaction',
        // self::API_syncGameRecords => 'query_game_history',
        self::API_createPlayer			=> 'login/thirdparty',
        self::API_login					=> 'login/thirdparty',
        self::API_depositToGame			=> 'api/v1/general/wallet/recharge',
        self::API_withdrawFromGame		=> 'api/v1/general/wallet/withdraw',
        self::API_queryPlayerBalance	=> 'api/v1/general/wallet/moreUserBalance',
        self::API_queryGameRecords		=> 'api/v1/general/game/projectsHistory',
        // self::API_ordersHistory		=> 'api/v1/general/game/ordershistory',
        self::API_queryTransaction		=> 'api/v1/general/game/ordershistory',
    );

    protected $max_time_diff_allowed = 60;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->CI->load->model(['gl_game_tokens', 'player_model']);
		$this->CI->load->library(['gl_game_lib']);

		$this->api_host	= $this->getSystemInfo('url');
		$this->api_key	= $this->getSystemInfo('key');

		$this->game_demo_mode 	= !empty($this->getSystemInfo('game_demo_mode'));
		$this->demo_username	= $this->getSystemInfo('demo_username');
		$this->use_settled_period_type	= $this->getSystemInfo('use_settled_period_type',true);

		// $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
		// $this->currency = $this->getSystemInfo('currency');
		// $this->secret_key = $this->getSystemInfo('secret_key');
		// $this->operator_token = $this->getSystemInfo('operator_token');
		// $this->game_url = $this->getSystemInfo('game_url');
		// $this->lobby_url = $this->getSystemInfo('lobby_url');
		// $this->max_call_attempt = $this->getSystemInfo('max_call_attempt', 10);
		// $this->max_data_set = $this->getSystemInfo('max_data_set', 500);//default from og game is 300
		// $this->forward_sites = $this->getSystemInfo('forward_sites',null);
		// $this->token_prefix = $this->getSystemInfo('token_prefix', '');

		// Default HTTP method: POST
		$this->method = self::METHOD_POST;

		$this->timeout_seconds_for_sync=$this->getSystemInfo('timeout_seconds_for_sync', 600);
		$this->is_sync_function=false;
		$this->page_limit_on_sync=$this->getSystemInfo('page_limit_on_sync', 500);
		$this->default_sleep_time=$this->getSystemInfo('default_sleep_time', 60);
		$this->enable_speed_test=$this->getSystemInfo('enable_speed_test', false);
		$this->host_data=$this->getSystemInfo('host_data');
	}

	public function getPlatformCode() {
		return GL_API;
	}

	public function forwardCallback($url, $params) {
		list($header, $resultText) = $this->httpCallApi($url, $params);
		$this->CI->utils->debug_log('forwardCallback', $url, $params, $header, $resultText);
		return $resultText;
	}

	public function checkGameIpWhitelistByGameProvider($ip) {
		return true;
	}

	public function callback($post_params = null, $method) {
		$methods_allowed = [ 'login', 'recharge', 'withdraw' ];
		try {
			$exception_ret = null;

			if (!in_array($method, $methods_allowed)) {
				$exception_ret = [ 'method' => $method ];
				throw new Exception('Method not allowed', 0x1);
			}

			// Secure validation, using timestamp, api_key, request token
			$secure			= $this->CI->utils->safeGetArray($post_params, 'secure');
			$timestamp		= $this->CI->utils->safeGetArray($post_params, 'timestamp');
			$token_user		= $this->CI->utils->safeGetArray($post_params, 'token_user');
			$token_trans	= $this->CI->utils->safeGetArray($post_params, 'token_trans');

			if (!$this->secure_validate($secure, $timestamp, $token_user, $token_trans, $method)) {
				throw new Exception('Secure string not match', 0x2);
			}

			$now = time();
			$time_diff = abs($now - $timestamp);
			if ($time_diff > $this->max_time_diff_allowed)  {
				// $this->CI->utils->debug_log(__METHOD__, 'time diff too large', );
				$exception_ret = [ 'time_diff' => $time_diff, 'allowed' => $this->max_time_diff_allowed, 'timestamp' => $timestamp, 'server_time' => $now ];
				throw new Exception('Time diff too large', 103);
			}

			switch ($method) {
				case 'login' :
					$query_creds = $this->CI->gl_game_lib->get_login_creds_by_token($token_user, $this->game_demo_mode, $this->demo_username);

					if ($query_creds['code'] != 0) {
						throw new Exception($query_creds['mesg'], $query_creds['code']);
					}

					$creds = $query_creds['result']['data'];

					if ($creds['player_id'] > 0) {
						$game_username = $this->getGameUsernameByPlayerUsername($creds['thirdpartyUid']);
						$creds['thirdpartyUid'] = $game_username;
					}

					unset($creds['player_id']);

					$retval = [
						'result' => $query_creds['code'],
						'mesg' => $query_creds['mesg'],
						'data' => $creds
					];
					break;

				case 'recharge' : case 'withdraw' :
					// Verify transaction token
					if ($method == 'recharge') {
						$query_tx = $this->CI->gl_game_lib->get_recharge_creds_by_token($token_trans);
					}
					else {
						$query_tx = $this->CI->gl_game_lib->get_withdraw_creds_by_token($token_trans);
					}

					if ($query_tx['code'] != 0) {
						$this->CI->utils->debug_log(__METHOD__, 'token_trans exception', [ 'method' => $method, 'token_trans' => $token_trans, 'token_user' => $token_user ]);

						throw new Exception($query_tx['mesg'], $query_tx['code']);
					}

					// Verify player token
					$query_player = $this->CI->gl_game_lib->get_login_creds_by_token($token_user, $this->game_demo_mode, $this->demo_username);

					if ($query_player['code'] != 0) {
						$this->CI->utils->debug_log(__METHOD__, 'token_user exception', [ 'method' => $method, 'token_trans' => $token_trans, 'token_user' => $token_user ]);
						throw new Exception($query_player['mesg'], $query_player['code']);
					}

					// Match transaction and player
					$player_creds = $query_player['result']['data'];
					$tx_creds = $query_tx['result']['data'];

					if ($tx_creds['player_id'] != $player_creds['player_id']) {
						$this->CI->utils->debug_log(__METHOD__, 'token_user exception', [ 'method' => $method, 'token_trans' => $token_trans, 'token_user' => $token_user, 'tx_player_id' => $tx_creds['player_id'] , 'player_id' => $player_creds['player_id'] ]);
						throw new Exception('Wrong user', 111);
					}

					// (Deactivation moved to model - redis and mysql token are deactivated separatedly)
					// $this->CI->utils->debug_log(__METHOD__, 'token_trans verified, deactivating', [ 'token_trans' => $token_trans, 'token_user' => $token_user ]);

					// Prepare output
					unset($tx_creds['player_id']);
					$tx_creds['thirdpartyUid'] = $this->getGameUsernameByPlayerUsername($player_creds['thirdpartyUid']);

					$retval = [
						'result' => $query_tx['code'],
						'mesg' => $query_tx['mesg'],
						'data' => $tx_creds
					];
					break;

				default :
					throw new Exception('Not implemented yet', 0x7fff);
					break;
			}

			$this->CI->utils->debug_log(__METHOD__, 'response', $retval);
		}
		catch (Exception $ex) {
			$retval = [ 'result' => $ex->getCode(), 'mesg' => $ex->getMessage(), 'data' => $exception_ret ];
			$this->CI->utils->debug_log(__METHOD__, 'Exception', $retval);
		}
		finally {
			return $retval;
		}
	}

	/**
	 * Validates secure string for callback()
	 * @param	string	$secure			secure string provided by call
	 * @param	string	$timestamp		timestamp provided by call
	 * @param	string	$token_user     user token
	 * @param	string	$token_trans    transaction token
	 * @param	string	$method			API method
	 * @return	bool	true if match; otherwise false.
	 */
	protected function secure_validate($secure, $timestamp, $token_user, $token_trans, $method) {
		$api_key = $this->getSystemInfo('api_key');
		// Secure string calculation:
		//   K1 = md5(api_key + timestamp)
		//   HASH = md5(api_key + K1 + token_user + token_trans + timestamp +method)
		$secure_valid = md5($api_key . md5($api_key . $timestamp) . $token_user . $token_trans . $timestamp . $method);

		$this->CI->utils->debug_log(__METHOD__, [ 'api_key' => $api_key, 'timestamp' => $timestamp, 'token_user' => $token_user, 'token_trans' => $token_trans, 'method' => $method, 'secure_provided' => $secure, 'expected' => $secure_valid, 'match' => ($secure == $secure_valid) ]);

		return $secure == $secure_valid;
	}

	protected function getCallType($apiName, $params) {
		return self::CALL_TYPE_HTTP;
	}

	protected function processResultBoolean($responseResultId, $result, $playerName = null) {
		$success = (isset($result['result']) && $result['result']=='0')? true : false;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GL got error', $responseResultId, 'playerName', $playerName, 'result', $result);
		}

		return $success;
	}

	public function createPlayer($username, $player_id, $password, $email = null, $extra = null) {
		$pcp_res = parent::createPlayer($username, $player_id, $password, $email, $extra);

		$result = $this->queryForwardGame($username);
		$success = $result['success'] ? true : false;

		$this->CI->utils->debug_log('GLI game url ===>', $result['url']);
		file_get_contents($result['url']);
		return array("success" => $success);

		// Run a fake game login: Global Lottery has not separate create
		// account API method, game account will be created on first login automatically
		$player_id = $this->getPlayerIdInPlayer($username);
		$game_username = $this->getGameUsernameByPlayerUsername($username);

		$context = [
			'callback_obj'		=> $this,
			'callback_method'	=> 'processResultForCreatePlayer',
			'playerName'		=> $username,
			'playerId'			=> $player_id,
		];

		$login_res = $this->CI->gl_game_lib->player_login($player_id);

		$this->CI->utils->debug_log(__METHOD__, [ 'login_res' => $login_res ]);

		$token_user = $login_res['result']['token'];

		$params = [
			'token'	=> $token_user ,
			'op'	=> $this->getSystemInfo('operator')
		];

		$this->CI->utils->debug_log(__METHOD__, 'running fake login', [ 'params' => $params ]);

		$this->method = self::METHOD_GET;
		$this->is_sync_function=false;

		$res_arr = $this->callApi(self::API_login, $params, $context);
		$this->CI->utils->debug_log(__METHOD__, 'API result', [ 'res_arr' => $res_arr ]);

		return [ 'success' => $pcp_res ];
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        // $success = $this->processResultBoolean($responseResultId, $resultArr);
        $this->CI->utils->debug_log(__METHOD__, [ 'responseResultId' => $responseResultId, 'resultArr' => $resultArr ]);

        $result = array();
		$success = true; # undefine var (will check later)
        if($success){
            $result['token'] = $resultArr['detail']['launcher'];
        }
        return array($success, $result);
	}

	public function isPlayerExist($playerName) {
		$query_bal = $this->queryPlayerBalance($playerName);

		$success = $query_bal['success'] ? false : true;
		$is_exist = $success ? false : true;
		return array('success' => $success, 'exists' => $is_exist);
	}

	/**
	 * Generate custom URL for specific API methods
	 * Parameters are by definition in Abstract_game_api
	 * @param	string	$apiName	The API method
	 * @param	array 	$params		parameter array
	 * @return	string	Full request URL
	 */
    public function generateUrl($apiName, $params) {

        $api_path = array_key_exists($apiName, self::API_PATHS) ? self::API_PATHS[$apiName] : null;

        if (empty($api_path)) {
        	return null;
        }

        $method_url = "{$this->api_host}/{$api_path}";

        $url = '';
        switch ($apiName) {
        	case self::API_login :
        		$room_master_id = $this->getSystemInfo('room_master_id');
				$room_master_hash = substr(md5($room_master_id), -3);
				$query = http_build_query($params);
				$url = "{$method_url}/{$room_master_id}-{$room_master_hash}?{$query}";

				if($this->enable_speed_test && !empty($this->host_data)){
					$url = "/{$api_path}/{$room_master_id}-{$room_master_hash}?{$query}";	
				}
				break;

			case self::API_depositToGame :
			case self::API_withdrawFromGame :
			case self::API_queryPlayerBalance :
			case self::API_queryGameRecords :
        	case self::API_queryTransaction :
				$params_json = json_encode($params);
				$app_key = $this->getSystemInfo('app_key');
				$hash = md5($params_json . $app_key);
				$this->CI->utils->debug_log(__METHOD__, [ 'params' => $params, 'params_json' => $params_json , 'app_key' => $app_key, 'hash' => $hash ]);
				$query = http_build_query([ 'hash' => $hash ]);
				$url = "{$method_url}?{$query}";
				break;

			default :
				break;
        }

        $this->CI->utils->debug_log(__METHOD__, [ 'method' => $apiName, 'method_url' => $method_url , 'url' => $url ]);

        return $url;
    }

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		$statusCode = intval($statusCode, 10);
		if($apiName == self::API_queryGameRecords) {
			if ($statusCode == self::HTTP_MULTIPLE_REQUEST_CODE) {
				sleep($this->default_sleep_time);
			}
		}
		return $errCode || intval($statusCode, 10) >= 400;
	}

	/**
     * httpCallApi() uses GET by default.  Add extra CURL settings if method uses POST.
     * Parameters are by definition in Abstract_game_api
     * @param	object	$ch			CURL object
     * @param	array 	$params		array of parameters
     * @return	none
     */
	protected function customHttpCall($ch, $params) {
		if ($this->method == self::METHOD_POST || $this->method == self::METHOD_POST_FORCE_JSON) {
			$params_json = json_encode($params);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);

			// Global Lottery does not use POST form; must post JSON string directly
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params_json);
			if ($this->method == self::METHOD_POST_FORCE_JSON) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			}

			$this->CI->utils->debug_log('customHttpCall params_json', $params_json);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		else if ($this->method == self::METHOD_GET) {
			// Do nothing
		}
		else {
			// Unsupported
		}
	}

	public function queryForwardGame($player_name, $options = []) {
		try {
			$demo_mode = empty($player_name) ? true : false;
			$player_id = $this->getPlayerIdInPlayer($player_name);
			$login_res = $this->CI->gl_game_lib->player_login($player_id, $demo_mode);

			$this->CI->utils->debug_log(__METHOD__, [ 'demo_mode' => $demo_mode, 'player_name' => $player_name, 'player_id' => $player_id, 'login_res' => $login_res ]);

			// Only raise exception when return code > 0; return code <0 is also successful
			if ($login_res['code'] > 0) {
				throw new Exception($login_res['mesg'], $login_res['code']);
			}

			$params = [
				'token'	=> $login_res['result']['token'] ,
				'op'	=> $this->getSystemInfo('operator')
			];

			$launch_url = $this->generateUrl(Game_api_gl::API_login, $params);

			$ret = [ 'success' => true, 'code' => 0, 'url' => $launch_url, 'host_data' => $this->host_data ];
		}
		catch(Exception $ex) {
			$ret = [ 'success' => false, 'code' => $ex->getCode(), 'mesg' => $ex->getMessage(), 'url' => ''  ];
		}
		finally {
			return $ret;
		}
	}

	// public function processResultForCreatePlayer($params){
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultArr = $this->getResultJsonFromParams($params);
	// 	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
	// 	$playerId = $this->getVariableFromContext($params, 'playerId');
	// 	$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
	// 	$result = array(
	// 		"player" => $gameUsername,
	// 		"exists" => false
	// 	);

	// 	if($success){
	// 		# update flag to registered = true
	//         $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	//         $result["exists"] = true;
	// 	}

	// 	return array($success, $result);
	// }

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj'	=> $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName'	=> $playerName,
			'gameUsername'	=> $gameUsername,
		);

		$params = [
			'roomMasterId'	=> strval($this->getSystemInfo('room_master_id')) ,
			'userArray'		=> [ strval($gameUsername) ]
		];

		$this->is_sync_function=false;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log(__METHOD__, [ 'gameUsername' => $gameUsername, 'resultArr' => $resultArr, 'responseResultId' => $responseResultId ]);


		try {
			$result = [];
			if (!$success) {
				throw new Exception('Success != true', 0x1);
			}

			if (!isset($resultArr['data'])) {
				throw new Exception("'data' not in resultArr", 0x2);
			}

			if (!isset($resultArr['data']['userList'])) {
				throw new Exception("'userList' not in resultArr['data']", 0x3);
			}

			$user_list = $resultArr['data']['userList'];
			foreach ($user_list as $item) {
				foreach ($item as $luser => $balance) {
					$this->CI->utils->debug_log(__METHOD__, [ 'luser' => $luser, 'balance' => $balance, 'balance_float' => floatval($balance) ]);
					if ($luser == $gameUsername) {
						$result[ 'balance' ] = floatval($balance);
						break;
					}
				}
			}

			if (!isset($result['balance'])) {
				throw new Exception("Game user $gameUsername not found in userList", 0x4);
			}

		}
		catch (Exception $ex) {
			$this->CI->utils->debug_log(__METHOD__, 'failure', $ex->getMessage());
			$success = false;
			$mesg_from_api = '';
			if (isset($resultArr['error'])) { $mesg_from_api = $resultArr['error']; }
			if (isset($resultArr['message'])) { $mesg_from_api = $resultArr['message']; }
			$result[ 'message' ] = !empty($mesg_from_api) ? $mesg_from_api : $ex->getMessage();
		}
		finally {
			return [ $success, $result ];
		}
		// return array($success, $result);
	}

	/**
	 * overview : get running logs
	 *
	 * @param  string	$startDate,$endDate
	 *
	 * @return array
	 */
	function getRunningLogs($dateFrom = false,$dateTo = false) {
		$this->CI->load->model(array('external_system','original_game_logs_model'));
		$sqlGetPrize='and gl_game_logs.is_getprize ='.Game_logs::STATUS_PENDING;
		$sql = <<<EOD
SELECT gl_game_logs.write_time 
FROM gl_game_logs
WHERE
gl_game_logs.write_time >= ? and gl_game_logs.write_time <= ?
{$sqlGetPrize}
EOD;
		$params=[$dateFrom,$dateTo];
		return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
	}

	public function syncLostAndFound($token) {
		# Limit the scope of getRunningLogs to avoid returning residue records for too long ago
		$runningLogsStartDate = date('Y-m-d H:i:s', strtotime('-'.self::LOST_AND_FOUND_MAX_DAYS_BACK.' days'));
		$runningLogsEndDate = date('Y-m-d H:i:s');
		$result = $this->getRunningLogs($runningLogsStartDate, $runningLogsEndDate);
		$page = 1;
		$success = true;
		$this->utils->debug_log("syncLostAndFound for results between [$runningLogsStartDate] and [$runningLogsEndDate], result count", count($result));

		$transaction_time = array();
		if(!empty($result)) {
			foreach ($result as $key => $resulti) {
				$transaction_time[] = $resulti['write_time'];
			}
		}

		if(!empty($transaction_time)) {
			$startDate = date('Y-m-d H:i:s',min(array_map('strtotime', $transaction_time)));
			$endDate   = date('Y-m-d H:i:s',max(array_map('strtotime', $transaction_time)));
			if(count($transaction_time) > 1) {
				$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+12 hours', function($startDate, $endDate, $page) {
					$startDate = $startDate->format('Y-m-d H:i:s');
					$endDate = $endDate->format('Y-m-d H:i:s');
					$resultByRange = $this->getRunningLogs($startDate,$endDate);
					if(!empty($resultByRange)) {
						$this->utils->debug_log('syncLostAndFound startDate - '.$startDate);
						$this->utils->debug_log('syncLostAndFound endDate - '.$endDate);
        				$success = $this->syncPaginate( $startDate, $endDate, $page );
        				return $success;
					}else{
						return true;
					}
				});
			}
			else {
				$success = $this->syncPaginate( $startDate, $endDate, $page );
				return $success;
			}
		}
		return array('success' => $success);
	}

	public function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate->modify($this->getDatetimeAdjust());

		$startDateStr = $startDate->format('Y-m-d H:i:s');
		$endDateStr = $endDate->format('Y-m-d H:i:s');
        $page = 1;

        $success= $this->syncPaginate( $startDateStr, $endDateStr, $page );

        return array('success'=>$success);

		// $context = array(
		// 	'callback_obj'	=> $this,
		// 	'callback_method' => 'processResultForSyncGameRecords',
		// );

		// $params = [
		// 	'roomMasterId'		=> strval($this->getSystemInfo('room_master_id')) ,
		// 	'startTime'			=> $startDate,
		// 	'endTime'			=> $endDate,
		// 	'pageLimit'			=> '500'	# optional
		// ];

		// $this->method = self::METHOD_POST_FORCE_JSON;
		// $this->is_sync_function=true;

		// return $this->callApi(self::API_queryGameRecords, $params, $context);
	}

    private function syncPaginate($startDate, $endDate, $page){

        $this->CI->utils->debug_log('start syncPaginate================',$startDate, $endDate, $page);

        $success=true;
        $done=false;
        while (!$done) {
            $rlt = $this->syncGL($startDate, $endDate, $page);
            if($rlt['success']){

                $this->CI->utils->debug_log('sync game logs api result', $rlt);

                if($rlt['total_pages']>$rlt['current_page']){
                    $page=$rlt['current_page']+1;
                    $this->CI->utils->debug_log('not done================',$rlt['total_pages'],$rlt['current_page']);
                }else{
                    $done=true;
                    $this->CI->utils->debug_log('done===================',$rlt['total_pages'],$rlt['current_page']);
                }
                $success=true;
            }else{
                $success=false;
                $done=true;
                $this->CI->utils->error_log('sync game logs api error', $rlt);
            }
        }

        return $success;
    }

    private function syncGL($startDate, $endDate, $page){

		$context = array(
			'callback_obj'	=> $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$params = [
			'roomMasterId'		=> strval($this->getSystemInfo('room_master_id')) ,
			'startTime'			=> $startDate,
			'endTime'			=> $endDate,
			'pageLimit'			=> $this->page_limit_on_sync,	# optional
			'page' => $page,
			'periodType' => $this->use_settled_period_type ? self::SETTLED_PERIOD : self::BETTING_PERIOD
		];

		$this->method = self::METHOD_POST_FORCE_JSON;
		$this->is_sync_function=true;

		return $this->callApi(self::API_queryGameRecords, $params, $context);
	}

	public function preProcessGameRecords(&$gameRecords){
		foreach($gameRecords as $index => $record) {
			if(array_key_exists('animal_code', $record)){
				$gameRecords[$index]['animal_code'] = json_encode($record['animal_code']);
			}
			if(array_key_exists('animal_code_key', $record)){
				$gameRecords[$index]['animal_code_key'] = json_encode($record['animal_code_key']);
			}
			$gameRecords[$index]['one_price'] = isset($record['onePrice'])? $record['onePrice'] : null;
			unset($gameRecords[$index]['onePrice']);
		}
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$result_arr = $this->getResultJsonFromParams($params);

		$this->CI->load->model(array('external_system','original_game_logs_model'));

		$this->CI->utils->debug_log(__METHOD__, [ 'resultArr' => count($result_arr), 'responseResultId' => $responseResultId ]);
		$success = $result_arr['result'] == 0 ? true : false;

		$result = array('data_count'=>0);
		if($success){
			$game_records = $result_arr['data']['list'];
			$this->preProcessGameRecords($game_records);
			$result['total_pages']=$result_arr['pagination']['totalPage'];
			$result['current_page']=$result_arr['pagination']['page'];

			// foreach ($game_records  as $data) {
			// 	$this->CI->utils->debug_log('project_id:'.$data['project_id'].', '.$data['name'].', '.$data['write_time'], $data);
			// }

			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'gl_game_logs',						# original table logs
					$game_records,						# api record (format array)
					'project_id',						# unique field in api
					'external_uniqueid',				# unique field in gl_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
			);

			$this->CI->utils->debug_log('after process available rows', count($game_records), count($insertRows), count($updateRows));

			unset($game_records);

			if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update');
			}

			unset($updateRows);

		}

		$this->CI->utils->debug_log(__METHOD__, [ 'result' => $result ]);

		$this->is_sync_function=false;

		return array($success, $result);
	}

	private function updateOrInsertOriginalGameLogs($rows, $response_result_id, $query_type){
		$data_count=0;
		if(!empty($rows)){
			foreach ($rows as $data) {
				$this->preProcessOriginalLogs($data, $response_result_id);
				if ($query_type == 'update') {
					$this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $data);
				} else {
					unset($data['id']);
					$this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $data);
				}
				$data_count++;
				unset($data);
			}
		}
		return $data_count;
	}

	public function preProcessOriginalLogs(&$data, $response_result_id) {
		if (!empty($data['animal_code']) && is_array($data['animal_code']) ) {
			$data['animal_code'] = json_encode($data['animal_code']);
		}
		$data['external_uniqueid'] = $data['project_id'];
		$data['last_sync_time'] = $this->CI->utils->getNowForMysql();
		$data['response_result_id'] = $response_result_id;


		return $data;
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

		$sqlTime='gl_game_logs.updated_at >= ? and gl_game_logs.updated_at <= ?';
		if($use_bet_time){
			$sqlTime='gl_game_logs.write_time >= ? and gl_game_logs.write_time <= ?';
		}

		// $sqlGameStatus=' and gl_game_logs.is_cancel = ? ';

		$sql = <<<EOD
SELECT gl_game_logs.id as sync_index,
gl_game_logs.id,
gl_game_logs.external_uniqueid,
gl_game_logs.write_time as bet_time,
gl_game_logs.deduct_time as settle_time,
gl_game_logs.name as username,
gl_game_logs.lottery_id,
gl_game_logs.response_result_id,
gl_game_logs.total_price as bet_amount,
gl_game_logs.total_price as real_bet_amount,
gl_game_logs.bonus,
gl_game_logs.prize_status,
gl_game_logs.is_getprize,
gl_game_logs.is_cancel,
gl_game_logs.is_deduct,
gl_game_logs.user_point,
gl_game_logs.point_status,

gl_game_logs.last_sync_time,
gl_game_logs.md5_sum,
gl_game_logs.lottery_id as game_code,
gl_game_logs.lottery_id as game,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM gl_game_logs

left JOIN game_description as gd ON gl_game_logs.lottery_id = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON gl_game_logs.name = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}

EOD;

		$params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo];

		return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

		$extra_info=["note" => $row['note']];

		if(empty($row['md5_sum'])){
			$row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
					self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

		$logs_info = [
			'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
				'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
			'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['username']],
			'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
				'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
				'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
			'date_info'=>['start_at'=>$row['bet_time'], 'end_at'=>$row['settle_time'], 'bet_at'=>$row['bet_time'],
				'updated_at'=>$row['last_sync_time']],
			'flag'=>Game_logs::FLAG_GAME,
			'status'=>$row['status'],
			'additional_info'=>['has_both_side'=>0, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['external_uniqueid'],
			'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
			'bet_type'=>null ],
			'bet_details'=>[],
			'extra'=>$extra_info,
			//from exists game logs
			'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
			'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
		];

		return $logs_info;
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row['game_code'];
		$extra = array('game_code' => $row['game_code'],'game_name' => $row['game']);
		$game_type_id = $unknownGame->game_type_id;
		$game_type = $unknownGame->game_name;

		return $this->processUnknownGame(
				$game_description_id, $game_type_id,
				$external_game_id, $game_type, $external_game_id, $extra,
				$unknownGame);
	}

	public function preprocessOriginalRowForGameLogs(array &$row){

		// if ($row['bonus'] == self::EMPTY_RESULT_AMOUNT) { # means player loss if value is '0.000000';
		// 	$row['result_amount'] = -$row['bet_amount'];
		// } else {
		// 	$row['result_amount'] = $row['bonus'];
		// }
		$row['result_amount'] = $row['bonus']-$row['bet_amount'];
		$row['note'] = null;
		//Check if rebate assigned and add
		if($row['point_status'] == self::ASSIGNED){
			$row['result_amount'] += $row['user_point'];
			if($row['user_point'] > 0){
				$row['note'] = lang("Rebate"). ' '.$row['user_point'];
			}
		}

		$game_description_id = $row['game_description_id'];
		$game_type_id = $row['game_type_id'];
		if (empty($game_description_id)) {
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
		}

		$row['game_description_id']=$game_description_id;
		$row['game_type_id']=$game_type_id;

		$status = $this->getGameRecordsStatus($row['is_getprize'], $row['is_cancel'], $row['prize_status'], $row['is_deduct']);
		$row['status']=$status;
	}

	/**
	 *
cancel
is_cancel : 1
is_getprize : 0
prize_status : 0

win
is_cancel : 0
is_getprize : 1
prize_status : 1

loss
is_cancel : 0
is_getprize : 2
prize_status : 0
	 * @param  string $is_getprize
	 * @return [type]         [description]
	 */
	private function getGameRecordsStatus($is_getprize, $is_cancel, $prize_status, $is_deduct) {

		$status = Game_logs::STATUS_SETTLED;

		$this->CI->load->model(array('game_logs'));
		switch ($is_getprize) {
			case self::GAME_PROCESSING:
				$status = Game_logs::STATUS_PENDING;
				break;
			case self::GAME_WIN;
			case self::GAME_LOSS;
				$status = Game_logs::STATUS_SETTLED;
				break;
		}

		if($is_cancel=='1' || $is_cancel=='2' || $is_cancel=='3'){
			$status= Game_logs::STATUS_CANCELLED;
		}	

		if($is_deduct){
			$status = Game_logs::STATUS_SETTLED;
		}
		return $status;
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

	public function queryGameRecords($dateFrom, $dateTo, $playerName = NULL, $extra = []) {
		return $this->returnUnimplemented();
	}

	// public function queryTransaction($dateFrom, $dateTo, $playerName = NULL, $extra = []) {

	// 	$gameUsername = empty($playerName) ? null :	$this->getGameUsernameByPlayerUsername($playerName);

	// 	$context = array(
	// 		'callback_obj'	=> $this,
	// 		'callback_method' => 'processResultForQueryGameRecords',
	// 		'playerName'	=> $playerName,
	// 		'gameUsername'	=> $gameUsername,
	// 	);

	// 	$params = [
	// 		'roomMasterId'		=> $this->getSystemInfo('room_master_id') ,
	// 		'thirdpartyUserId'	=> $gameUsername ,
	// 		'startTime'			=> $dateFrom ,
	// 		'endTime'			=> $dateTo
	// 	];

	// 	return $this->callApi(self::API_queryGameRecords, $params, $context);
	// }

	// public function processResultForQueryGameRecords($params) {
	// 	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultArr = $this->getResultJsonFromParams($params);

	// 	$this->CI->utils->debug_log(__METHOD__, [ 'gameUsername' => $gameUsername, 'resultArr' => $resultArr, 'responseResultId' => $responseResultId ]);

	// 	$success = $resultArr['result'] == 0 ? true : false;

	// 	$result = [];
	// 	if($success){
	// 		$result['balance'] = 1;
	// 	}

	// 	return array($success, $result);
	// }

	// public function batchQueryPlayerBalance($playerNames, $syncId = null) {
 //        if (empty($playerNames)) {
 //            $playerNames = $this->getAllGameUsernames();
 //        }

 //        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
 //    }
	// private function getReasons($error_code){
 //        switch ($error_code) {
 //            case 3013:
 //                return self::REASON_NO_ENOUGH_BALANCE;
 //                break;
 //            case 3001:
 //                return self::REASON_INVALID_TRANSFER_AMOUNT;
 //                break;
 //            case 1034:
 //                return self::REASON_INCOMPLETE_INFORMATION;
 //                break;
 //            case 1200:
 //            case 1303:
 //            case 1035:
 //            case 3040:
 //                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
 //                break;
 //            case 1204:
 //                return self::REASON_INVALID_KEY;
 //                break;
 //            case 3004:
 //            case 3005:
 //            case 1305:
 //                return self::REASON_NOT_FOUND_PLAYER;
 //                break;
 //            default:
 //                return self::REASON_UNKNOWN;
 //                break;
 //        }
	// }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$this->CI->utils->debug_log(__METHOD__, [ 'playerName' => $playerName, 'amount' => $amount, 'transfer_secure_id' => $transfer_secure_id ]);

		if(is_null($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
		}

        $context = array(
            'callback_obj'	=> $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername'	=> $gameUsername,
            'playerName'	=> $playerName,
            'amount'		=> $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $player_id = $this->getPlayerIdInPlayer($playerName);
        $user_token = $this->CI->gl_game_lib->get_login_token_by_player_id($player_id);
        $tx_res = $this->CI->gl_game_lib->create_token_recharge($player_id, $transfer_secure_id, $amount);
        $tx_token = $tx_res['result']['token'];

		$params = [
			'roomMasterId'		=> strval($this->getSystemInfo('room_master_id')) ,
			'accessUserToken'	=> $user_token ,
			'accessMoneyToken'	=> $tx_token ,
			'operator'			=> $this->getSystemInfo('operator') ,
			'userId'			=> $gameUsername ,
			'money'				=> strval($amount)
		];

		$this->CI->utils->debug_log(__METHOD__, [ 'player_id' => $player_id, 'params' => $params ]);

		$this->is_sync_function=false;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$this->CI->utils->debug_log(__METHOD__, [ 'gameUsername' => $gameUsername, 'playerName' => $playerName, 'external_transaction_id' => $external_transaction_id, 'amount' => $amount, 'resultArr' => $resultArr, 'responseResultId' => $responseResultId ]);

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if($success){
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
        }else{
            // $error_code = @$resultArr['error']['code'];
            // $result['reason_id']=$this->getReasons($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            // $result['reason_id'] = $resultArr['result']; # deprecated
            $result['reason_id'] = $this->getReasons($resultArr['result']);
            $result['mesg'] = $resultArr['message'];
        }

        $this->CI->utils->debug_log(__METHOD__, [ 'success' => $success, 'result' => $result ]);

        return array($success, $result);

	}

	private function getReasons($statusCode)
	{
		switch ($statusCode) {
			case 1007:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 8999:
			case 9000:
				return self::REASON_TOKEN_VERIFICATION_FAILED;
				break;
			case 2000:
				return self::REASON_INCORRECT_MERCHANT_ID;
				break;
			case 2003:
				return self::REASON_USERS_WALLET_LOCKED;
				break;
			case 2004:
				return self::REASON_INSUFFICIENT_AMOUNT;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if(is_null($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
		}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id
        );

        $player_id = $this->getPlayerIdInPlayer($playerName);
        $user_token = $this->CI->gl_game_lib->get_login_token_by_player_id($player_id);
        $tx_res = $this->CI->gl_game_lib->create_token_withdraw($player_id, $transfer_secure_id, $amount);
        $tx_token = $tx_res['result']['token'];

		$params = [
			'roomMasterId'		=> strval($this->getSystemInfo('room_master_id')) ,
			'accessUserToken'	=> $user_token ,
			'accessMoneyToken'	=> $tx_token ,
			'operator'			=> $this->getSystemInfo('operator') ,
			'userId'			=> $gameUsername ,
			'money'				=> strval($amount)
		];

		$this->is_sync_function=false;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$this->CI->utils->debug_log(__METHOD__, [ 'gameUsername' => $gameUsername, 'playerName' => $playerName, 'external_transaction_id' => $external_transaction_id, 'amount' => $amount, 'resultArr' => $resultArr, 'responseResultId' => $responseResultId ]);

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
				// $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
	   //          $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			// $error_code = @$resultArr['error']['code'];
            // $result['reason_id']=$this->getReasons($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            // $result['reason_id'] = $resultArr['result'];
            $result['reason_id'] = $this->getReasons($resultArr['result']);
            $result['mesg'] = $resultArr['message'];
        }

        return array($success, $result);
	}

	// public function queryTransaction($transactionId, $extra) {
 //        $playerName=$extra['playerName'];
 //        $playerId=$extra['playerId'];
 //        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

	// 	$context = array(
	// 		'callback_obj' => $this,
	// 		'callback_method' => 'processResultForQueryTransaction',
	// 		'playerId' => $playerId,
	// 		'playerName' => $playerName,
	// 		'gameUsername' => $gameUsername,
	// 		'external_transaction_id' => $transactionId,
	// 	);

	// 	$params = array(
	// 		"secret_key" => $this->secret_key,
	// 		"operator_token" => $this->operator_token,
	// 		"player_name" => $gameUsername,
	// 		"transfer_reference" => $transactionId
	// 	);

	// 	return $this->callApi(self::API_queryTransaction, $params, $context);
	// }

	// public function processResultForQueryTransaction($params){
	// 	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
	// 	$playerName = $this->getVariableFromContext($params, 'playerName');
	// 	$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
	// 	$resultArr = $this->getResultJsonFromParams($params);
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

	// 	$result = array(
	// 		'response_result_id' => $responseResultId,
	// 		'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
	// 		'reason_id'=>self::REASON_UNKNOWN,
	// 		'external_transaction_id'=>$external_transaction_id
	// 	);

	// 	if($success){
	// 		$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
	// 	}else{
	// 		$error_code = @$resultArr['error']['code'];
 //            $result['reason_id']=$this->getReasons($error_code);
	// 		$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
	// 	}

	// 	return array($success, $result);
	// }


	// # Support Simplified Chinese, other languages are still under development.
	// public function getLauncherLanguage($language){
 //        $lang='';
 //        switch ($language) {
 //        	case 1:
 //            case 'en-us':
 //                $lang = 'en'; // english
 //                break;
 //            case 2:
 //            case 'zh-cn':
 //                $lang = 'zh'; // chinese
 //                break;
 //            default:
 //                $lang = 'en'; // default as english
 //                break;
 //        }
 //        return $lang;
 //    }



	// public function syncOriginalGameLogs($token = false) {
	// 	$ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');
	// 	if ($ignore_public_sync == true) {
	// 		$this->CI->utils->debug_log('ignore public sync');
	// 		return array('success' => true);
	// 	}
	// 	$attempt = 0;

	// 	do {
	// 		$last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());
	//     	$last_sync_id = !empty($last_sync_id)?$last_sync_id:0;

	//     	$context = array(
	// 			'callback_obj' => $this,
	// 			'callback_method' => 'processResultForSyncOriginalGameLogs',
	// 		);

	// 		$params = array(
	// 			"secret_key" => $this->secret_key,
	// 			"operator_token" => $this->operator_token,
	// 			"bet_type" => 1,
	// 			"row_version" => $last_sync_id,
	// 			"count" => $this->max_data_set
	// 		);

	// 		$result =  $this->callApi(self::API_syncGameRecords, $params, $context);

	// 		sleep(1);
	// 		$attempt++;
	// 		$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs attempt: ',$attempt);
	// 		$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs orginal data: ',$result['original_data_count']);
	// 	} while(($attempt < $this->max_call_attempt) && ($result['original_data_count'] >= $this->max_data_set));
	// }

	// private $last_rowversion=0;

	// public function processResultForSyncOriginalGameLogs($params) {
	// 	$this->CI->load->model(array('pgsoft_game_logs'));
	// 	$startDate = $this->getVariableFromContext($params, 'startDate');
	// 	$endDate = $this->getVariableFromContext($params, 'endDate');
 //        $resultArr = $this->getResultJsonFromParams($params);
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$success = $this->processResultBoolean($responseResultId, $resultArr);
	// 	$gameRecords = isset($resultArr['data'])?$resultArr['data']:array();
	// 	$result = [];
	// 	$dataCount = 0;

	// 	if($success){
	// 		$availableRows = $this->CI->pgsoft_game_logs->getAvailableRows($gameRecords);

	// 		foreach ($availableRows as $row) {
	// 			$insertRecord = [];

	// 			$insertRecord['betid'] = isset($row['betId'])?$row['betId']:null;
	// 			$insertRecord['parentbetid'] = isset($row['parentBetId'])?$row['parentBetId']:null;
	// 			$insertRecord['playername'] = isset($row['playerName'])?$row['playerName']:null;
	// 			$insertRecord['currency'] = isset($row['currency'])?$row['currency']:null;
	// 			$insertRecord['gameid'] = isset($row['gameId'])?$row['gameId']:null;
	// 			$insertRecord['platform'] = isset($row['platform'])?$row['platform']:null;
	// 			$insertRecord['bettype'] = isset($row['betType'])?$row['betType']:null;
	// 			$insertRecord['transactiontype'] = isset($row['transactionType'])?$row['transactionType']:null;
	// 			$insertRecord['betamount'] = isset($row['betAmount'])?$row['betAmount']:null;
	// 			$insertRecord['winamount'] = isset($row['winAmount'])?$row['winAmount']:null;
	// 			$insertRecord['jackpotrtpcontributionamount'] = isset($row['jackpotRtpContributionAmount'])?$row['jackpotRtpContributionAmount']:null;
	// 			$insertRecord['jackpotwinamount'] = isset($row['jackpotWinAmount'])?$row['jackpotWinAmount']:null;
	// 			$insertRecord['balancebefore'] = isset($row['balanceBefore'])?$row['balanceBefore']:null;
	// 			$insertRecord['balanceafter'] = isset($row['balanceAfter'])?$row['balanceAfter']:null;
	// 			$insertRecord['rowversion'] = isset($row['rowVersion'])?$row['rowVersion']:null;
	// 			$insertRecord['bettime'] = isset($row['betTime'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',($row['betTime']/1000))):null;

	// 			# SBE USE
	// 			$insertRecord['external_uniqueid'] = isset($row['betId'])?$row['betId']:null;
	// 			$insertRecord['response_result_id'] = $responseResultId;
	// 			$insertRecord['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
	// 			$insertRecord['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
	// 			//insert data to PGSOFT gamelogs table database
	// 			$this->CI->pgsoft_game_logs->insertGameLogs($insertRecord);

	// 			$this->last_rowversion = $insertRecord['rowversion'];

	// 			$dataCount++;
	// 		}

	// 		# synced last rowversion
	// 		if($this->last_rowversion!=0){
	// 			$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $this->last_rowversion);
	// 		}

	// 		$result['original_data_count'] = $dataCount;

	// 	}

	// 	return array($success, $result);
	// }

	// public function syncMergeToGameLogs($token) {
	// 	$this->CI->load->model(array('game_logs', 'player_model', 'pgsoft_game_logs'));
	// 	$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
	// 	$dateTimeFrom->modify($this->getDatetimeAdjust());
	// 	$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

	// 	//observer the date format
	// 	$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
	// 	$endDate = $dateTimeTo->format('Y-m-d H:i:s');

	// 	$rlt = array('success' => true);

	// 	$result = $this->CI->pgsoft_game_logs->getGameLogStatistics($startDate, $endDate);
	// 	$cnt = 0;
	// 	if (!empty($result)) {

	// 		$unknownGame = $this->getUnknownGame();

	// 		foreach ($result as $row) {
	// 			$cnt++;

	// 			$game_description_id = $row->game_description_id;
	// 			$game_type_id = $row->game_type_id;

	// 			if(empty($row->game_type_id)&&empty($row->game_description_id)){
	// 				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
	// 			}

	//             $extra = array(
 //                    'table'       => $row->round_id,
 //                    'trans_amount'=> $row->real_bet_amount
 //                );

 //                #result amount is winloss - bet amount
	//             $result_amount = ((float)$row->result_amount-(float)$row->real_bet_amount);

	// 			$this->syncGameLogs(
	// 				$game_type_id,
	// 				$game_description_id,
	// 				$row->gameid,
	// 				$row->game_type,
	// 				$row->game,
	// 				$row->player_id,
	// 				$row->playername,
	// 				$row->bet_amount,
	// 				$result_amount,
	// 				null, # win_amount
	// 				null, # loss_amount
	// 				$row->after_balance,
	// 				0, # has_both_side
	// 				$row->external_uniqueid,
	// 				$row->end_datetime, //start
	// 				$row->end_datetime, //end
	// 				$row->response_result_id,
	// 				Game_logs::FLAG_GAME,
 //                    $extra
	// 			);

	// 		}
	// 	}

	// 	$this->CI->utils->debug_log('PGSOFT PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
	// 	$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
	// 	return $rlt;
	// }

	// private function getGameDescriptionInfo($row, $unknownGame) {
	// 	$game_description_id = null;

	// 	$external_game_id = $row->gameid;
 //        $extra = array('game_code' => $external_game_id,'game_name' => $row->gameid);

 //        $game_type_id = isset($unknownGame->game_type_id) ? $unknownGame->game_type_id:null;
 //        $game_type = isset($unknownGame->game_name) ? $unknownGame->game_name:"Unknown";

	// 	return $this->processUnknownGame(
	// 		$game_description_id, $game_type_id,
	// 		$external_game_id, $game_type, $external_game_id, $extra,
	// 		$unknownGame);
	// }

    public function login($playerName, $password = null, $extra = null) {
    	return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra){
		return $this->returnUnimplemented();
	}

	// public function preDepositToGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
	// 	$this->CI->utils->debug_log(__METHOD__, 'Invoke of preDepositToGame', [ 'player_id' => $player_id, 'playerName' => $playerName, 'transfer_from' => $transfer_from, 'transfer_to' => $transfer_to, 'amount' => $amount, 'extra_details' => $extra_details ]);
 //        return null;
 //    }

 //    public function preWithdrawFromGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
 //        $this->CI->utils->debug_log(__METHOD__, 'Invoke of preWithdrawFromGame', [ 'player_id' => $player_id, 'playerName' => $playerName, 'transfer_from' => $transfer_from, 'transfer_to' => $transfer_to, 'amount' => $amount, 'extra_details' => $extra_details ]);
 //        return null;
 //    }

 //    public function postDepositToGame($result, $player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
 //        return $this->returnUnimplemented();
 //        $this->CI->utils->debug_log(__METHOD__, 'Invoke of postDepositToGame', [ 'result' => $result, 'player_id' => $player_id, 'playerName' => $playerName, 'transfer_from' => $transfer_from, 'transfer_to' => $transfer_to, 'amount' => $amount, 'extra_details' => $extra_details ]);
 //        return null;
 //    }

 //    public function postWithdrawFromGame($result, $player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
 //        return $this->returnUnimplemented();
 //        $this->CI->utils->debug_log(__METHOD__, 'Invoke of postDepositToGame', [ 'result' => $result, 'player_id' => $player_id, 'playerName' => $playerName, 'transfer_from' => $transfer_from, 'transfer_to' => $transfer_to, 'amount' => $amount, 'extra_details' => $extra_details ]);
 //        return null;
 //    }

    protected function getTimeoutSecond() {
    	if($this->is_sync_function){
	    	return $this->timeout_seconds_for_sync;
    	}

        return $this->CI->utils->getConfig('default_http_timeout');
    }

    function getFileExtension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }

    public function syncOriginalGameLogsFromCSV($isUpdate = false){
    	set_time_limit(0);
    	$this->CI->load->model(array('external_system','original_game_logs_model'));
    	$extensions = array("csv");
    	$game_logs_path = $this->getSystemInfo('gl_game_records_path');
    	$exported_file = array_diff(scandir($game_logs_path,1), array('..', '.'));

    	$game_records = array();
    	$result = array('data_count'=>0);
    	if(!empty($exported_file)){
    		foreach ($exported_file as $key => $csv) {
    			$ext = $this->getFileExtension($csv);
                if (!in_array($ext,$extensions)) {//skip other extension
                    continue;
                }
                $flag = true;
				$file = fopen($game_logs_path."/".$csv,"r");
				while(! feof($file))
				{
					$entry = fgetcsv($file);
					if($flag || empty($entry[0])) { $flag = false; continue; }

					$data = array(
						"project_id" 		=> $entry[0],
						"user_id" 			=> $entry[1],
						"name" 				=> $entry[2],
						"task_id" 			=> $entry[3],
						"lottery_id" 		=> $entry[4],
						"method_id" 		=> $entry[5],
						"issue" 			=> $entry[6],
						"bonus" 			=> $entry[7],
						"code" 				=> $entry[8],
						"total_price" 		=> $entry[9],
						"is_deduct" 		=> $entry[10],
						"is_cancel" 		=> $entry[11],
						"is_getprize" 		=> $entry[12],
						"prize_status" 		=> $entry[13],
						"user_point" 		=> $entry[14],
						"write_microtime" 	=> $entry[15],
						"cancel_time" 		=> $entry[16],
						"deduct_time"		=> date("Y-m-d H:i:s",strtotime($entry[17])),
						"created_at" 		=> date("Y-m-d H:i:s",strtotime($entry[18])),
						"write_time" 		=> date("Y-m-d H:i:s",strtotime($entry[18])),
						"updated_at" 		=> date("Y-m-d H:i:s",strtotime($entry[19])),
						"update_time"		=> date("Y-m-d H:i:s",strtotime($entry[19])),
						"bonus_time"		=> null

					);
					$game_records[] = $data;
				}
				fclose($file);
    		}
    	}

    	// echo "<pre>";
    	// print_r($game_records);exit();

    	list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
				'gl_game_logs',						# original table logs
				$game_records,						# api record (format array)
				'project_id',						# unique field in api
				'external_uniqueid',				# unique field in gl_game_logs table
				self::MD5_FIELDS_FOR_ORIGINAL,
				'md5_sum',
				'id',
				self::MD5_FLOAT_AMOUNT_FIELDS
		);

		$this->CI->utils->debug_log('after process available rows', count($game_records), count($insertRows), count($updateRows));

		unset($game_records);

		if (!empty($insertRows)) {
			$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,null, 'insert');
		}
		unset($insertRows);

		if($isUpdate){
			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,null, 'update');
			}
		}

		unset($updateRows);

		return array(true, $result);
    }

} // End of class game_api_gl

/*end of file*/
