<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Booming games 
* Game Type: Slots
* Wallet Type: Seamless
*
/**
* API NAME: BOOMING
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @renz.php.ph
**/

abstract class Abstract_game_api_common_booming_seamless extends Abstract_game_api {
	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';
	const METHOD_PUT = 'PUT';

	const TRANSACT_DEBIT = 'debit';
	const TRANSACT_CREDIT = 'credit';

	# Fields in redtiger_idr_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'player_id',//(string) Player game username from received callback.
        'game_id',//(string) Round identifier for callback.
        'round',//(string) Round identifier for callback.
        'type',//(string) Type of bet from received callback.
        'bet',//(string) Bet from received callback.
        'win',//(string) Wins amount from received callback.
        'freespins_details',
        'customer_id',
        'game_date',
        'bonus',//(string) After balance amount from received callback.
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet',
        'win',
        'after_balance',
        'round',
    ];

# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
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
        'bet_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	const URI_MAP = array(
		self::API_queryPlayerBalance => '/v2/session/[session_id]',
		self::API_queryForwardGame   => '/v2/session',
		self::API_getGameProviderGamelist => '/v2/games',
	);

	const API_SESSION_URL = '/v2/session';
	
	## RESPONSE RESULT CODES
	const PROCESS_API_HEADER = 'BOOMING_PROCESS_API_HEADER';
	const PROCESS_API_URL = 'BOOMING_PROCESS_API_URL';
	const SAVE_GPA_SESSION = 'BOOMING_SAVE_GPA_SESSION';
	const CREATE_PLAYER = 'BOOMING_CREATE_PLAYER';
	const QUERY_PLAYER_BALANCE = 'BOOMING_QUERY_PLAYER_BALANCE';
	const DEPOSIT = 'BOOMING_DEPOSIT';
	const WITHDRAW = 'BOOMING_WITHDRAW';
	const QUERY_FORWARD_GAME = 'BOOMING_QUERY_FORWARD_GAME';
	const QUERY_FORWARD_GAME_RESPONSE = 'BOOMING_QUERY_FORWARD_GAME_RESPONSE';
	const PROCESS_API_UPDATE_BALANCE = 'PROCESS_API_UPDATE_BALANCE';
	const PROCESS_API_GAME_LIST = 'PROCESS_API_GAME_LIST';
	const PROCESS_API_GAME_LIST_RESPONSE = 'PROCESS_API_GAME_LIST_RESPONSE';
	const PROCESS_API_SYNC_BY_SESSION = 'PROCESS_API_SYNC_BY_SESSION';
	const PROCESS_API_SYNC_BY_SESSION_RESPONSE = 'PROCESS_API_SYNC_BY_SESSION_RESPONSE';
	const PROCESS_API_SYNC_BY_SESSION_DETAILS = 'PROCESS_API_SYNC_BY_SESSION_DETAILS';
	## --------------------------------------------------------------------------------

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
		$this->api_key = $this->getSystemInfo('API_KEY');
		$this->api_secret = $this->getSystemInfo('API_SECRET');
		$this->currency_type = $this->getSystemInfo('currency');
		$this->language = strtolower($this->getSystemInfo('language'));
		$this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
		$this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');
		$this->generated_signature = null;
		$this->generated_nonce = null;
		$this->generated_session_id = null;
		$this->processCustomAPI = false;
		$this->isRedirect = $this->getSystemInfo('isRedirect', false); 
		$this->syncMerge_every_bet = $this->getSystemInfo('syncMerge_every_bet', false); 
		$this->default_demo_balance = $this->getSystemInfo('default_demo_balance', "1000"); 

		// This is use for generating API Call Header since colon (:) on 'http:' got confusing upon json_encode
		$this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
		$this->callback_url = '/booming_service_api/processMethod/callback';
		$this->rollback_url = '/booming_service_api/processMethod/rollback';
		$this->bonus_callback = '/booming_service_api/processMethod/bonus';

		$this->use_default_home_url = $this->getSystemInfo('use_default_home_url', false);
		$this->use_default_cashier_url = $this->getSystemInfo('use_default_cashier_url', false);

        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);

		$this->default_home_url = $this->getSystemInfo('default_home_url', $this->current_domain); 
		$this->default_cashier_url = $this->getSystemInfo('default_cashier_url', $this->current_domain); 
        $this->CI->load->model(['boomingseamless_game_logs', 'game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model']);
	}

    public function isSeamLessGame()
    {
       return true;
    }

	public function getApiNonce() {
		return hexdec(uniqid());
	}

	public function processAPIHeader($params, $apiPath) {
		$paramsHash = hash('sha256', $params);
		$this->generated_nonce = $this->getApiNonce();

		$toHash = $apiPath . $this->generated_nonce . $paramsHash;
		$this->generated_signature = hash_hmac('sha512', $toHash, $this->api_secret);

		$this->CI->utils->debug_log('NONCE ====>', $this->generated_nonce);
		$this->CI->utils->debug_log('SIGNATURE ====>', $this->generated_signature);
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = $apiName;
		if($this->processCustomAPI === false) {
			$apiUri = self::URI_MAP[$apiName];
		}
		$this->processCustomAPI = false;
		$url = $this->api_url.$apiUri;

		return $url;
	}

	protected function customHttpCall($ch, $params)
	{
        if ($this->method == self::METHOD_POST || $this->method == self::METHOD_PUT) {
        	if ($this->method == self::METHOD_POST) {
	            curl_setopt($ch, CURLOPT_POST, true);
        	} else {
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
        	}
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    }
    }

    protected function getHttpHeaders($params)
	{
		$headers['Content-Type'] = 'application/json+vnd.api';
		$headers['X-Bg-Api-Key'] = $this->api_key;
		$headers['X-Bg-Nonce'] = $this->generated_nonce;
		$headers['X-Bg-Signature'] = $this->generated_signature;
		return $headers;
	}

	// Save session_id to game_provider_auth by login_name
	public function saveSession($session_id, $gameUsername)
	{
        $sSession = '{"session_id":"' . $session_id . '"}';
		$this->CI->game_provider_auth->addGameAdditionalInfo($gameUsername, $sSession, $this->getPlatformCode());
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201)){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('BOOMING got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName = null, $playerId = null, $password = null, $email = null, $extra = null)
	{
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$success = false;
		$message = "Unable to create Account for Booming API";
		if($return){
			$success = true;
			$message = "Successfull create account for Booming API";
		}

		$reqParams = ['abstract_create_player_return' => $return, 'success' => $success, 'message' => $message];
		$response = 'Booming API createPlayer';
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::CREATE_PLAYER,$reqParams,$response);

		return array("success" => $success, "message" => $message);
	}


	public function queryPlayerBalance($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true, 
			'balance' => $balance
		);

		$reqParams = ['queryPlayerBalance' => $result, 'success' => $result['success']];
		$response = 'Booming API queryPlayerBalance';
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::QUERY_PLAYER_BALANCE,$reqParams,$response);

		return $result;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$external_transaction_id = $transfer_secure_id;

		$this->doUpdateAPIBalance($playerName);

		$response = 'Booming API deposit';
		$reqParams = ['player_name' => $playerName, 'amount' => $amount, 'external_transaction_id' => $external_transaction_id];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::DEPOSIT,$reqParams,$response);

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false)
    {
		$external_transaction_id = $transfer_secure_id;

		$this->doUpdateAPIBalance($playerName);

		$response = 'Booming API withdraw';
		$reqParams = ['player_name' => $playerName, 'amount' => $amount, 'external_transaction_id' => $external_transaction_id];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::WITHDRAW,$reqParams,$response);

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
    }

	public function doUpdateAPIBalance($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

		$aSession_id = $this->CI->game_provider_auth->getAdditionalInfo($playerId, $this->getPlatformCode());

		if (empty($aSession_id)) {
			return;
		}

		## Typecast of object from result
		$aSession_id = (array) $aSession_id;
		$strSession_id = $aSession_id["session_id"];

		$balance = $this->queryPlayerBalance($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDoUpdateAPIBalance',
        );

		$originalParams = array(
			'balance' => $balance['balance']
		);
		//Removing extra spaces on array
		$params = json_encode($originalParams, JSON_UNESCAPED_SLASHES);

		$apiPath = self::API_SESSION_URL . '/' . $strSession_id;

		$this->CI->utils->debug_log('QUERY_PLAYER_BALANCE ==>', $params);
		$this->processCustomAPI = true;

		$this->processAPIHeader($params, $apiPath);
		$this->method = self::METHOD_PUT;

		$response = 'Booming API updateBalance';
		$reqParams = ['player_name' => $gameUsername, 'balance' => $balance['balance'], 'params' => $params];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::PROCESS_API_UPDATE_BALANCE,$reqParams,$response);

		return $this->callApi($apiPath, $params, $context);
	}

	public function processResultForDoUpdateAPIBalance($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = true;
		$result = array('status_code' => $statusCode);
		$this->CI->utils->debug_log('BALANCE UPDATE STATUS ===>', @$statusCode);


		$response = 'Booming API return updateBalance';
		$reqParams = ['status_code' => $statusCode, 'params' => $params];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::PROCESS_API_UPDATE_BALANCE,$reqParams,$response);

		return array($success, $result);
	}


	private function getReasons($statusCode)
	{
		switch ($statusCode) {
			case 2001:
			case 2003:
			case 2004:
			case 2005:
			case 2006:
			case 2007:
			case 2008:
			case 2009:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 2002:
				return self::REASON_CURRENCY_ERROR;
				break;
			case 2099:
			case 2011:
				return self::REASON_INVALID_KEY;
				break;

			case 3001:
			case 3002:
				return self::REASON_SESSION_TIMEOUT;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	/*
	 *	To Launch Game
	 *
	*/
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $game_launch_code = $this->CI->game_description_model->queryExternalGameIdByCode($this->getPlatformCode(), $extra['game_code']);
		$game_launch_code = $extra['game_code'];
		$balance = $this->queryPlayerBalance($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$originalParams = array(
			'game_id'           => $game_launch_code,
			'balance'           => $balance['balance'],
			'locale'            => $this->language,
			'variant'           => !empty($extra['is_mobile']) ? "mobile" : "desktop",
			'currency'          => $this->currency_type,
			'player_id'         => $gameUsername,
			'callback'          => $this->current_domain . $this->callback_url . '/' . $this->getPlatformCode() . '/' . $game_launch_code,
			'rollback_callback' => $this->current_domain . $this->rollback_url . '/' . $this->getPlatformCode() . '/' . $game_launch_code,
			'bonus_callback'    => $this->current_domain . $this->bonus_callback . '/' . $this->getPlatformCode() . '/' . $game_launch_code
		);

		if (array_key_exists("extra", $extra)) {
            
			//extra checking if mode is demo 
			if (!empty($extra['demo']) || $extra['game_mode']=='demo') {
				$originalParams['demo'] = true;
				$originalParams['balance'] = $this->default_demo_balance;
			}

			//extra checking for cashier link
			if ($this->use_default_cashier_url) {
				if(isset($extra['cashier_link'])) {
					$this->default_cashier_url = $extra['cashier_link'];
				}
			}
	
			//extra checking for home link
			if ($this->use_default_home_url) {
				if(isset($extra['home_link']) && !empty($extra['home_link'])) {
					$this->default_home_url = $extra['home_link'];
				}
			}

            //extra checking for home link
            if(isset($extra['extra']['home_link']) && !empty($extra['extra']['home_link'])) {
                $this->default_home_url = $extra['extra']['home_link'];
            }
            //extra checking for cashier link
            if(isset($extra['extra']['cashier_link'])) {
                $this->default_cashier_url = $extra['extra']['cashier_link'];
            }
        }

		$originalParams['cashier'] = $this->default_cashier_url;
		$originalParams['exit'] = $this->default_home_url;

		#removes home url if disable_home_link is set to TRUE
		if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
			unset($originalParams['cashier']);
			unset($originalParams['exit']);
		}

        if($this->force_disable_home_link){
            if(isset($originalParams['cashier']) && !empty($originalParams['cashier'])){
                unset($originalParams['cashier']);
            }
            if(isset($originalParams['exit']) && !empty($originalParams['exit'])){
                unset($originalParams['exit']);
            }
        }
		
		$params = json_encode($originalParams, JSON_UNESCAPED_SLASHES);

		$this->CI->utils->debug_log('QUERY_FORWARD_GAME_PARAMS ==>', $params);

		$this->processAPIHeader($params, self::API_SESSION_URL);
		
		$this->method = self::METHOD_POST;

		$response = 'Booming API queryForwardGame';
		$reqParams = ['params' => $originalParams];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::QUERY_FORWARD_GAME,$reqParams,$response);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$gameUsername     = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result           = array('url'=>'', 'isRedirect' => $this->isRedirect);

		if($success){
			$result['url'] = @$resultArr['play_url'];
			$this->saveSession(@$resultArr['session_id'], $gameUsername);
			$this->CI->utils->debug_log('URL RESULT ==>', @$resultArr['play_url']);
			$this->CI->utils->debug_log('SESSION RESULT ==>', $resultArr['session_id']);
		}

		$response = 'Booming API queryForwardGame response';
		$reqParams = ['status_code' => $statusCode, 'result' => $resultArr];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::QUERY_FORWARD_GAME_RESPONSE,$reqParams,$response);

		return array($success, $result);
	}

	public function syncBySession($session_id)
	{
		$context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForSyncBySession',
            'session_id'      => $session_id,
        );

		//GET method requires to hash a blank value
		$params = '';
		$apiPath = self::API_SESSION_URL . '/' . $session_id;
		$this->processCustomAPI = true;
		$this->processAPIHeader($params, $apiPath);

		$this->method = self::METHOD_GET;

		$response = 'Booming API syncBySession';
		$reqParams = ['url' => $apiPath, 'params' => $params, 'HTTP_METHOD' => self::METHOD_GET];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::PROCESS_API_SYNC_BY_SESSION,$reqParams,$response);

		return $this->callApi($apiPath, $params, $context);
	}

	public function processResultForSyncBySession($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$session_id       = $this->getVariableFromContext($params, 'session_id');
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		if($success){
			$this->CI->utils->debug_log('SESSION DETAILS RESULT ==>', $resultArr);

			$context = array(
	            'callback_obj'    => $this,
	            'callback_method' => 'processResultForSyncBySessionDetails',
	            'player_id'       => $resultArr['player_id'],
	            'game_code'       => $resultArr['game_id'],
	        );

			//Get method requires to hash a blank value
			$params = '';
			$apiPath = self::API_SESSION_URL . '/' . $session_id . '/rounds';
			$this->processCustomAPI = true;
			$this->processAPIHeader($params, $apiPath);
			$this->method = self::METHOD_GET;

			$response = 'Booming API syncBySession response and call session details';
			$reqParams = ['response' => $params, 'url' => $apiPath, 'params' => $params, 'HTTP_METHOD' => self::METHOD_GET];
			$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::PROCESS_API_SYNC_BY_SESSION_RESPONSE, $reqParams, $response);

			return $this->callApi($apiPath, $params, $context);
		}
		return array($success, $resultArr);
	}

	public function processResultForSyncBySessionDetails($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$player_id        = $this->getVariableFromContext($params, 'player_id');
		$game_code        = $this->getVariableFromContext($params, 'game_code');
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$gameRecords = !empty($resultArr) ? $resultArr : [];

		if ($success && !empty($gameRecords)) {
			$this->CI->utils->debug_log('SESSION ROUNDS RESULT ==>', $resultArr);
			$extra = ['game_code' => $game_code, 'player_id' => $player_id, 'bet_status' => BOOMINGSEAMLESS_GAME_LOGS::BET_SYNC_ORIGINAL];
            $this->rebuildGameRecords($gameRecords,$extra);
			$syncOriginalResult = $this->doSyncOriginal($gameRecords);
		}

		$response = 'Booming API syncBySession response and call session details';
		$reqParams = ['response' => $params, 'session_details' => $gameRecords];
		$this->saveToResponseResult($success, self::PROCESS_API_SYNC_BY_SESSION_DETAILS, $reqParams, $response);

		return array('success' => $success);
	}

	private function rebuildGameRecords(&$gameRecords,$extra)
	{
		$newGR =[];
        foreach($gameRecords as $i => $gr)
        {
			$type = $gr['type'];
			$round = $gr['round'];
			$bet = $gr['total_bet'];
			$win = $gr['result']['win'];
			$session_id = $gr['session_id'];
			$after_balance = $gr['display_balance'];

			$game_date = isset($gr['created_at']) ? date('Y-m-d H:i:s', strtotime(substr($gr['created_at'],0,19))) : null;

			$newGR[$i]['player_id']         = isset($extra['player_id']) ? $extra['player_id'] : null;
			$newGR[$i]['game_id']           = isset($extra['game_code']) ? $extra['game_code'] : null;
			$newGR[$i]['round']             = isset($round) ? $round : null;
			$newGR[$i]['type']              = isset($type) ? $type : null;
			$newGR[$i]['bet']               = isset($bet) ? $bet : null;
			$newGR[$i]['win']               = isset($win) ? $win : null;
			$newGR[$i]['freespins_details'] = isset($gr['result']['freespins']) ? json_encode($gr['result']['freespins']) : 'null';
			$newGR[$i]['customer_id']       = isset($gr['customer_id']) ? $gr['customer_id'] : null;
			$newGR[$i]['bonus']             = isset($gr['bonus']) ? 1 : 0;
			$newGR[$i]['game_date']         = isset($gr['created_at']) ? $this->gameTimeToServerTime($game_date) : null;
			$newGR[$i]['external_uniqueid'] = $session_id . $round;
			$newGR[$i]['created_at']        = date('Y-m-d H:i:s');
			$newGR[$i]['updated_at']        = date('Y-m-d H:i:s');
			$newGR[$i]['session_id']        = isset($session_id) ? $session_id : null;
			$newGR[$i]['after_balance']     = isset($gr['display_balance']) ? $gr['display_balance'] : null;
			$newGR[$i]['bet_status']        = isset($extra['bet_status']) ? $extra['bet_status'] : null;
			$newGR[$i]['before_balance']    = isset($gr['display_balance']) ? $after_balance - $bet : null;

			## if the bonus spins has a value of true the wallet must no be deducted although the bet > 0
			// if (!empty($gr['bonus'])) {
			// 	$newGR[$i]['bet'] = 0;		
			// }
        }
        $gameRecords = $newGR;
	}

	public function doSyncOriginal($data) {
        $success = false;
		$result = ['data_count' => 0];

        if (!empty($data)) {
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $data,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

			$this->CI->utils->debug_log('after process available rows', count($data), count($insertRows), count($updateRows));

            unset($data);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                $success = true;
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                $success = true;
            }
            unset($updateRows);
        }
        return array('success' => $success);
	}

	// This syncMerge is for bulk records which date time can be applied
    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

   public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`b`.`game_date` >= ?
          AND `b`.`game_date` <= ?';

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    b.game_id AS game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    b.player_id AS player_username,
    b.bet AS bet_amount,
    b.win AS win_amount,
    b.after_balance AS after_balance,
    b.game_date AS game_date,
    b.external_uniqueid AS external_uniqueid,
    b.round AS round,
    b.md5_sum AS md5_sum,
    b.session_id AS session_id,
    b.bet_status AS bet_status
FROM boomingseamless_game_logs as b
	LEFT JOIN game_description as gd ON b.game_id = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
	JOIN game_provider_auth as gpa ON b.player_id = gpa.login_name
	AND gpa.game_provider_id= ?
	WHERE
	b.bet_status != ? AND
    {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            BOOMINGSEAMLESS_GAME_LOGS::BET_UNSETTLED,
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
        $extra = [
            'table' =>  $row['session_id'],
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
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['win_amount'] - $row['bet_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['game_date'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['game_date'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

	private function getGameDescriptionInfo($row, $unknownGame)
	{
		$game_description_id = null;
		$game_name = str_replace("알수없음",$row['game_code'],
					 str_replace("不明",$row['game_code'],
					 str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function queryGameList()
    {
		$context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryGameList',
        );

		//GET method requires to hash a blank value
		$params = '';
		$apiPath =  '/v2/games';
		$this->processCustomAPI = true;
		$this->processAPIHeader($params, $apiPath);

		$this->method = self::METHOD_GET;

		$response = 'Booming API queryGameList';
		$reqParams = ['url' => $apiPath, 'params' => $params, 'HTTP_METHOD' => self::METHOD_GET];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::PROCESS_API_GAME_LIST,$reqParams,$response);

		return $this->callApi($apiPath, $params, $context);
    }

	public function processResultForQueryGameList($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$response = 'Booming API queryGameList response';
		$reqParams = ['status_code' => $statusCode, 'result' => $resultArr];
		$this->saveToResponseResult(BOOMINGSEAMLESS_GAME_LOGS::PROCESS_SUCCESS, self::PROCESS_API_GAME_LIST_RESPONSE,$reqParams,$response);

		return array($success, $resultArr);
	}

	public function saveToResponseResult($success, $callMethod, $params, $response){
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag, $callMethod, json_encode($params), $response, 200, null, null);
    }

	/*
	 * Note: syncOrignal by date is not applicable on their API endpoint.
	 */
	public function syncOriginalGameLogs($token = false)
	{
		$this->returnUnimplemented();
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
		$this->returnUnimplemented();
	}

	public function blockPlayer($playerName)
	{
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName)
	{
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $success=true;
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array('success' => $success);
    }

    public function queryTransaction($transactionId, $extra) {
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

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryTransactionByDateTime($startDate, $endDate){

        $sql = <<<EOD
SELECT
game_provider_auth.player_id as player_id,
t.created_at transaction_date,
t.bet as bet,
t.win as win,
(win-bet) as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round as round_no,
t.external_uniqueid
FROM boomingseamless_game_logs as t
JOIN game_provider_auth ON t.player_id = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
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
	
					$temp_game_record = [];
					$temp_game_record['player_id'] = $transaction['player_id'];
					$temp_game_record['game_platform_id'] = $this->getPlatformCode();
					$temp_game_record['transaction_date'] = $transaction['transaction_date'];
					$temp_game_record['amount'] = abs($transaction['amount']);
					$temp_game_record['before_balance'] = $transaction['before_balance'];
					$temp_game_record['after_balance'] = $transaction['after_balance'];
					$temp_game_record['round_no'] = $transaction['round_no'];
					
					$extra=[];

					if($transaction['amount'] < 0){
						$extra['trans_type'] = self::TRANSACT_DEBIT;
					}else{
						$extra['trans_type'] = self::TRANSACT_CREDIT;
					}

					$temp_game_record['extra_info'] = json_encode($extra);
					$temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];
	
					$temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
					if($transaction['amount'] < 0){
						$temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
					}
	
					$temp_game_records[] = $temp_game_record;
					unset($temp_game_record);
				}
			}
	
			$transactions = $temp_game_records;

			$this->CI->utils->debug_log('BOOMINGTEST: (' . __FUNCTION__ . ')', 'transactions:', $transactions);
        }  

}
/*end of file*/
