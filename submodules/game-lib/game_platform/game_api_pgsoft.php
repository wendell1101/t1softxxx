<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API DOCS NAME: PG Soft Integration Document Transfer mode
	* Document Number: V2.1.0


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_pgsoft extends Abstract_game_api {
    public $original_gamelogs_table;
    public $operator_token;

	const POST = "POST";
	const GET = "GET";
	const PUT = "PUT";
	const TOURNAMENT_CODE = 3;
	const API_joinTournament = '/Tournament/v1/CreateTournamentPlayer';
	const API_syncGameRecords2 = 'syncGameRecords2';

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
		$this->currency = $this->getSystemInfo('currency');
		$this->secret_key = $this->getSystemInfo('secret_key');
		$this->operator_token = $this->getSystemInfo('operator_token');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->lobby_url = $this->getSystemInfo('lobby_url');
		$this->max_call_attempt = $this->getSystemInfo('max_call_attempt', 10);
		$this->max_data_set = $this->getSystemInfo('max_data_set', 500);//default from og game is 300
		$this->forward_sites = $this->getSystemInfo('forward_sites',null);
		$this->token_prefix = $this->getSystemInfo('token_prefix', '');
		$this->tournament_url = $this->getSystemInfo('tournament_url', 'https://public.pg-redirect.us/tournament/index.html');//staging tournament url
		$this->tournament_registration_url = $this->getSystemInfo('tournament_registration_url', "https://api.int.pg-bo.net"); // staging tournament registration url

		$this->round_transfer_amount =$this->getSystemInfo('round_transfer_amount', true); // use default round to 2 decimal (api rules)
		$this->sync_by_time_range =$this->getSystemInfo('sync_by_time_range', false);
		$this->language =$this->getSystemInfo('language', 'en');

		$this->step =$this->getSystemInfo('step', '+30 minutes');

		$this->method = self::POST; # default as POST
		$this->version = $this->getSystemInfo('version', "v3");

		$this->URI_MAP = array(
			self::API_createPlayer => '/v3/Player/Create', //previous version is v1
			self::API_queryPlayerBalance => '/v3/Cash/GetPlayerWallet',
			self::API_isPlayerExist => '/v3/Player/Check',
			self::API_blockPlayer => '/v3/Player/Suspend',
			self::API_unblockPlayer => '/v3/Player/Reinstate',
			self::API_depositToGame => "/Cash/{$this->version}/TransferIn",
			self::API_withdrawFromGame => "/Cash/{$this->version}/TransferOut",
			self::API_queryTransaction => '/v3/Cash/GetSingleTransaction',
			self::API_logout => '/v3/Player/Kick',
			self::API_syncGameRecords => '/Bet/v4/GetHistory', // old /v2/Bet/GetHistory
			self::API_joinTournament => '/external/Tournament/v2/CreateTournamentPlayersAsync',
			self::API_syncGameRecords2 => '/Bet/v4/GetHistoryForSpecificTimeRange',
			self::API_queryForwardGame => '/external-game-launcher/api/v1/GetLaunchURLHTML',
			self::API_getTournamentList => '/Tournament/v2/GetTournaments',
			self::API_getTournamentRecords => '/Tournament/v2/GetTournamentAutoRewardCashPrize',
			self::API_createTournament => '/Tournament/v2/CreateTournament',
		);
		$this->new_data_grab_endpoint = $this->getSystemInfo('new_data_grab_endpoint');
		$this->enable_sync_lost_and_found =$this->getSystemInfo('enable_sync_lost_and_found', true);
		$this->enabled_new_queryforward =$this->getSystemInfo('enabled_new_queryforward', false);
		$this->pgsoft_api_domain =$this->getSystemInfo('pgsoft_api_domain', 'https://api.pg-bo.me');
		$this->used_html_on_launching = $this->getSystemInfo('used_html_on_launching', true);
		$this->enable_real_transfer_amount_param = $this->getSystemInfo('enable_real_transfer_amount_param', false);
		$this->original_gamelogs_table = 'pgsoft_game_logs';
		$this->unset_ot_extraargs = $this->getSystemInfo('unset_ot_extraargs', true);
		$this->only_transfer_positive_integer = $this->getSystemInfo('only_transfer_positive_integer', false);
		$this->enable_mock_duplicate_transfer_secure_id = $this->getSystemInfo('enable_mock_duplicate_transfer_secure_id', false);
		$this->mock_duplicate_transfer_secure_id = $this->getSystemInfo('mock_duplicate_transfer_secure_id', '');
		$this->mock_duplicate_transfer_secure_id_allowed_players = $this->getSystemInfo('mock_duplicate_transfer_secure_id_allowed_players', []);
		

		/*
			If the operator handles a 1:1000 conversion for a currency with a base unit of 1000, and amount in the
			request is 1, PG is expected to receive real_transfer_amount as 1000.

			ratio_handler = operator
			amount = 1(VND|IDR)
			real_transfer_amount = 1K(VND|IDR)

			ratio_handler = provider
			amount = 1k(VND|IDR)
			real_transfer_amount = 1K(VND|IDR)

			[NEW] real_transfer_amount
			Actual value of the transaction amount in the real world
			Note:
			The operator is required to validate the value and inform
			PG if any of the following conditions are not met:
			• For 1:1 base unit currency: realTransferAmount
			= amount
			• For 1:1000 base unit currency and conversion handle
			by PG:
			realTransferAmount = amount
			• For 1:1000 base unit currency and conversion handle
			by operator (default):
			realTransferAmount = amount*1000
		*/
		$this->ratio_handler = $this->getSystemInfo('ratio_handler', 'operator');
	}

	const ORIGINAL_LOGS_TABLE_NAME = 'pgsoft_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=[
		"gameid",
		"betamount",
		"winamount",
		"jackpotrtpcontributionamount",
		"jackpotwinamount",
		"balancebefore",
		"balanceafter",
		"rowversion",
		"bettime"
	];
	const MD5_FLOAT_AMOUNT_FIELDS=[
		'betamount',
		'winamount',
		"jackpotrtpcontributionamount",
		"jackpotwinamount",
		"balancebefore",
		"balanceafter",
	];

	public function getPlatformCode() {
		return PGSOFT_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		if ($apiUri == self::API_joinTournament) {
			$url = $this->tournament_registration_url . $this->URI_MAP[$apiName];
		}

		if($this->new_data_grab_endpoint){
			if($apiName == self::API_syncGameRecords || $apiName == self::API_syncGameRecords2){
				$url = $this->new_data_grab_endpoint . $this->URI_MAP[$apiName];
			}
		}
		if($apiName == self::API_queryForwardGame){
			$url = $this->pgsoft_api_domain . $this->URI_MAP[self::API_queryForwardGame];
		}

		$guid = trim($this->createGUID(), '{}');
		$url_params = http_build_query(array(
			"trace_id" => $guid
		));
		$url .= "?{$url_params}";

		return $url;
	}

	protected function customHttpCall($ch, $params) {
		if(isset($params['url_type']) && $params['url_type'] == "game-entry"){
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		} elseif ($this->method == self::POST){
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);     
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
	}

	public function forwardCallback($url, $params) {
		list($header, $resultText) = $this->httpCallApi($url, $params);
		$this->CI->utils->debug_log('forwardCallback', $url, $params, $header, $resultText);
		return $resultText;
	}

	public function callback($post_params = null, $method = null){
		$secret_key = @$post_params['secret_key'];
		$player_token = urldecode(@$post_params['operator_player_session']);
		$invalid_request = array(
			'data' => null,
			'error'=> [
				'code' => '1200',
				'message' => 'Invalid request.'
			]
		);

		if($secret_key != $this->secret_key){
			return $invalid_request;
		}

		if(isset($post_params["operator_token"]) && $post_params["operator_token"] != $this->operator_token){
			return $invalid_request;
		}

		# forward to site
		if ($this->forward_sites) {
			@list($prefix, $player_token) = explode('|', $player_token);
			
			if(isset($this->forward_sites[$prefix])){
				$url = $this->forward_sites[$prefix];
				$this->CI->utils->debug_log('PGSOFT_API FORWARD URL',$url);
				return $this->forwardCallback($url, $post_params);
			}
		}

		# remove prefix on token
		if(!empty($this->token_prefix)){
			@list($prefix, $player_token) = explode('|', $player_token);
		}

		$prefix = !empty($prefix) ? $prefix : null;

		$this->CI->utils->debug_log(' PLAYER TOKEN : ',$player_token,' TOKEN PREFIX : ',$prefix);
		$success = false;
		$this->CI->load->model(array('common_token', 'player_model'));
		$playerId = $this->CI->common_token->getPlayerIdByToken($player_token);

		if (!empty($playerId)) {
			$success = true;
			$playerInfo = $this->CI->player_model->getPlayerInfoById($playerId);
		}
		if ($success) {
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);
			$params = array(
				'data' => [
					'player_name' => $gameUsername,
					'nickname' => $gameUsername,
					'currency' => $this->getPlayerPGSoftCurrency($gameUsername),
					'reminder_time' => (time()*1000)
				],
				'error'=> null
			);
		}else{
			$params = $invalid_request;
		}

		return $params;
	}

    private function getPlayerPGSoftCurrency($username){
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($username);
		if(!is_null($playerId)){
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
			if(!is_null($currencyCode)){
				return $currencyCode;
			}else{
				return $this->currency;
			}
		}else{
			return $this->currency;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(is_null(@$resultArr['error'])){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PGSOFT_API API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
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
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"nickname" => $gameUsername,
			"currency" => $this->getPlayerPGSoftCurrency($gameUsername)
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			"player" => $gameUsername,
			"exists" => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
	        $result["exists"] = true;
		}

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();
		if($success){
			$result['balance'] = @floatval($resultArr['data']['cashBalance']);
			if(strtolower($this->ratio_handler) == 'operator'){
				$result['balance'] = @floatval($resultArr['data']['cashBalance']);
			} else { #ratio_handler = provider
				$balance = @floatval($resultArr['data']['cashBalance']);
				$result['balance'] = $this->gameAmountToDBTruncateNumber($balance);
			}
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdByGameUsername($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'playerId' => $playerId
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['exists'] = true;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}else{
			# Player not Exists
			if(isset($resultArr['error']['code'])&&$resultArr['error']['code']==1305){
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


	public function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForBlockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

    	if ($success) {
			$this->blockUsernameInDB($gameUsername);
		}

		return array($success, $result);
    }

    public function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if ($success) {
			$this->unblockUsernameInDB($gameUsername);
		}

		return array($success, $result);
    }

	public function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		return array($success, $result);
	}

	private function getReasons($error_code){
        switch ($error_code) {
            case 3013:
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case 3001:
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case 1034: 
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 1200:
            case 1303:
            case 1035: 
            case 3040: 
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case 1204: 
                return self::REASON_INVALID_KEY;
                break;
            case 3004: 
            case 3005: 
            case 1305: 
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);
		$transfer_secure_id = $this->getSecureId('transfer_request', 'external_transaction_id', true, 'T');

		// test duplicate
		// if($this->enable_mock_duplicate_transfer_secure_id && in_array($playerName, $this->mock_duplicate_transfer_secure_id_allowed_players)){
		// 	$transfer_secure_id = $this->mock_duplicate_transfer_secure_id;
		// }

		/* if(is_null($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
		} */
		$amount = strval($amount);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"amount" => $amount,
			"currency" => $this->getPlayerPGSoftCurrency($gameUsername),
			"transfer_reference" => $transfer_secure_id
		);

		/*
			ratio_handler = operator
			amount = 1(VND|IDR)
			real_transfer_amount = 1K(VND|IDR)

			ratio_handler = provider
			amount = 1k(VND|IDR)
			real_transfer_amount = 1k(VND|IDR)
		 */

		if(strtolower($this->ratio_handler) == 'operator'){
			if($this->enable_real_transfer_amount_param){
				$params['real_transfer_amount'] = strval($this->dBtoGameAmount($amount));
			}
		} else { #ratio_handler = provider
			$params['amount'] = $this->dBtoGameAmount($amount);
			if($this->enable_real_transfer_amount_param){
				$params['real_transfer_amount'] = strval($params['amount']);
			}
		}

		$this->CI->utils->debug_log("game_api_pgsoft @depositToGame final params", $params);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if($success){			
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if($playerId){
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
            // }else{
            //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            //     $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
            // }
			// $this->CI->utils->debug_log("game_api_pgsoft @depositToGame after transaction realTransferAmount value", $resultArr['data']['realTransferAmount']);
			// $this->CI->utils->debug_log("game_api_pgsoft @depositToGame after transaction amount value", $amount);
			// $this->CI->utils->debug_log(
			// 	"game_api_pgsoft @depositToGame after transaction realTransferAmount!=amount", 
			// 	isset($resultArr['data']['realTransferAmount']) && $resultArr['data']['realTransferAmount'] != $amount
			// );            
			// if (isset($resultArr['data']['realTransferAmount']) && $resultArr['data']['realTransferAmount'] != $amount) {
            //     $success = false;
            //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            //     $result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
            // } else {
            //     $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            //     $result['didnot_insert_game_logs']=true;
            // }

			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['error']['code'];
			if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id']=$this->getReasons($error_code);
			}
			
        }

        return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);
		$transfer_secure_id = $this->getSecureId('transfer_request', 'external_transaction_id', true, 'T');

		// if($this->enable_mock_duplicate_transfer_secure_id && in_array($playerName, $this->mock_duplicate_transfer_secure_id_allowed_players)){
		// 	$transfer_secure_id = $this->mock_duplicate_transfer_secure_id;
		// }

		/* if(is_null($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
		} */
		$amount = strval($amount);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"amount" => $amount,
			"currency" => $this->getPlayerPGSoftCurrency($gameUsername),
			"transfer_reference" => $transfer_secure_id
		);

		/*
			ratio_handler = operator
			amount = 1(VND|IDR)
			real_transfer_amount = 1K(VND|IDR)

			ratio_handler = provider
			amount = 1k(VND|IDR)
			real_transfer_amount = 1k(VND|IDR)
		 */

		if(strtolower($this->ratio_handler) == 'operator'){
			if($this->enable_real_transfer_amount_param){
				$params['real_transfer_amount'] = strval($this->dBtoGameAmount($amount));
			}
		} else { #ratio_handler = provider
			$params['amount'] = $this->dBtoGameAmount($amount);
			if($this->enable_real_transfer_amount_param){
				$params['real_transfer_amount'] = strval($params['amount']);
			}
		}

		$this->CI->utils->debug_log("game_api_pgsoft @withdrawFromGame final params", $params);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		$this->CI->utils->debug_log("game_api_pgsoft @withdrawFromGame after transaction realTransferAmount value", $resultArr['data']['realTransferAmount']);
		$this->CI->utils->debug_log("game_api_pgsoft @withdrawFromGame after transaction amount value", $amount);
		
		$this->CI->utils->debug_log(
			"game_api_pgsoft @withdrawFromGame after transaction realTransferAmount!=amount", 
			isset($resultArr['data']['realTransferAmount']) && $resultArr['data']['realTransferAmount'] != $amount
		);
		
		if ($success) { 
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			//     $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
			// } else {
			//     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			//     $result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
			// }

			// if (isset($resultArr['data']['realTransferAmount']) && $resultArr['data']['realTransferAmount'] != $amount) {
			// 	$success = false;
			// 	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			// 	$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
			// } else {
			// 	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			// 	$result['didnot_insert_game_logs']=true;
			// }
			
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;

        }else{
			$error_code = @$resultArr['error']['code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($error_code);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"transfer_reference" => $transactionId
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if($success){
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
			$error_code = @$resultArr['error']['code'];
            $result['reason_id']=$this->getReasons($error_code);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}


	public function processResultForLogin($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		$result = array();

		if($success){
			$result['token'] = $resultArr;
		}

		return array($success, $result);
	}	

	# Support Simplified Chinese, other languages are still under development.
	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case 1:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $lang = 'id'; // indonesian
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-th":
                $lang = 'th'; // thailand
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $lang = 'vi';
                break;
            default:
                $lang = $this->language; // default as english
                break;
        }
        return $lang;
    }

    public function joinTournament($playerName, $extra = null) {		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForJoinTournament',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_names" => $gameUsername,
			"tournament_id" => $extra["tournament_id"],
			"allow_re-register" => $extra["allow_re-register"]
		);
		return $this->callApi(self::API_joinTournament, $params, $context);
	}

	public function processResultForJoinTournament($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		return array($success, $resultArr);
	}

	public function queryForwardGame($playerName, $extra = null) {
        if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }

		if (isset($extra['tournament_id']) && !empty($extra['tournament_id'])) {
			$this->joinTournament($playerName, $extra);
		}

		if($this->enabled_new_queryforward){
			return $this->queryForwardGameV2($playerName, $extra);
		}

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		
		$playerToken = $this->getPlayerTokenByUsername($playerName);
		# add token prefix for callback forwarding 
		if(!empty($this->token_prefix)){
			$playerToken = $this->token_prefix.'|'.$playerToken;
		}
		

		switch (@$extra['game_mode']) {
			case 'real': # Real game
				$bet_type = 1;
				break;
			case "tournament": # Tournament game
				$bet_type = 3;
				break;
			default:
				$bet_type = 1;
				break;
		}

		if(!$this->language){
			$this->language = $this->getLauncherLanguage($extra['language']);
		}

		$params = array(
			"l" => $this->language,
			"btt" =>  $bet_type,
			"ot" => $this->operator_token,
			"ops" => $playerToken, 
			"f" => isset($extra['home_url']) ? $extra['home_url'] : $this->getSystemInfo('return_slot_url',null), 
		);

		# build game link
		$url = $this->game_url."/".$extra['game_code']."/index.html?".http_build_query($params);

		// if($extra['game_mode'] == self::TOURNAMENT_CODE){
		// 	$url = $this->tournament_url."?".http_build_query($params);
		// }
		return array("success"=>true,"url"=>$url);
	}

	public function getHistoryForSpecificTime($token){
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		$result = array();
		
		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, $this->step, function($startDate, $endDate)  {
			$startDate = strtotime($startDate->format('Y-m-d H:i:s')) * 1000;
			$endDate = strtotime($endDate->format('Y-m-d H:i:s')) * 1000;
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'sync_by_time' => true
			);

	        $params = array(
				"secret_key" => $this->secret_key,
				"operator_token" => $this->operator_token,
				"count" => $this->max_data_set,
				"bet_type" => 1,
				"from_time" => $startDate,
				"to_time" => $endDate,
				
			);
			// echo "<pre>";
			// print_r($params);exit();
			$this->CI->utils->debug_log('PGSOFT API:PARAMS: ',json_encode($params));
			return $this->callApi(self::API_syncGameRecords2, $params, $context);
	    });
	    return array("success" => true, $result);
	}
	
	public function syncOriginalGameLogs($token = false) {
		$sync_by_time_range = $this->getValueFromSyncInfo($token, 'sync_by_time_range');
		$ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');
		if($this->sync_by_time_range || $sync_by_time_range || $ignore_public_sync){
			#should disable sync_by_time_range, only use this on manual(ignore_public_sync == true)
			#this is call on synclostfound
			return $this->getHistoryForSpecificTime($token);
		}

		if ($ignore_public_sync == true) {
			$this->CI->utils->debug_log('ignore public sync');
			return array('success' => true);
		}
		$attempt = 0;
		$result = [];

		do {
			$last_sync_id = $this->getLastSyncIdFromTokenOrDB($token);
	    	$last_sync_id = !empty($last_sync_id)?$last_sync_id:0;

	    	$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'sync_by_time' => false,
				'token' => $token
			);

			$params = array(
				"secret_key" => $this->secret_key,
				"operator_token" => $this->operator_token,
				"bet_type" => 1,
				"row_version" => $last_sync_id,
				"count" => $this->max_data_set
			);
			$this->CI->utils->debug_log('PGSOFT API:PARAMS: ',json_encode($params));
			$result =  $this->callApi(self::API_syncGameRecords, $params, $context);
			
			sleep(1);
			$attempt++;
			$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs attempt: ',$attempt);
			$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs orginal data: ',$result['original_data_count']);
		} while(($attempt < $this->max_call_attempt) && ($result['original_data_count'] >= $this->max_data_set));
		if (!empty($result['last_sync_id'])) {
			return $result;
		}
	}

	private $last_rowversion=0;

	public function processGameRecords(&$gameRecords,$responseResultId) {
		$preResult = array();
		foreach ($gameRecords as $index => $row) {
			$preResult[$index]['betid'] = isset($row['betId'])?$row['betId']:null;
			$preResult[$index]['parentbetid'] = isset($row['parentBetId'])?$row['parentBetId']:null;
			$preResult[$index]['playername'] = isset($row['playerName'])?$row['playerName']:null;
			$preResult[$index]['currency'] = isset($row['currency'])?$row['currency']:null;
			$preResult[$index]['gameid'] = isset($row['gameId'])?$row['gameId']:null;
			$preResult[$index]['platform'] = isset($row['platform'])?$row['platform']:null;
			$preResult[$index]['bettype'] = isset($row['betType'])?$row['betType']:null;
			$preResult[$index]['transactiontype'] = isset($row['transactionType'])?$row['transactionType']:null;
			$preResult[$index]['betamount'] = isset($row['betAmount'])?$row['betAmount']:null;
			$preResult[$index]['winamount'] = isset($row['winAmount'])?$row['winAmount']:null;
			$preResult[$index]['jackpotrtpcontributionamount'] = isset($row['jackpotRtpContributionAmount'])?$row['jackpotRtpContributionAmount']:null;
			$preResult[$index]['jackpotwinamount'] = isset($row['jackpotWinAmount'])?$row['jackpotWinAmount']:null;
			$preResult[$index]['balancebefore'] = isset($row['balanceBefore'])?$row['balanceBefore']:null;
			$preResult[$index]['balanceafter'] = isset($row['balanceAfter'])?$row['balanceAfter']:null;
			$preResult[$index]['rowversion'] = isset($row['rowVersion'])?$row['rowVersion']:null;
			$preResult[$index]['bettime'] = isset($row['betTime'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',($row['betTime']/1000))):null;
			
			# SBE USE
			$preResult[$index]['external_uniqueid'] = isset($row['betId'])?$row['betId']:null;
			$preResult[$index]['response_result_id'] = $responseResultId;
			$preResult[$index]['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
			$preResult[$index]['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
		}
		$gameRecords = $preResult;
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
		$dataCount=0;
		if(!empty($data)){
			foreach ($data as $record) {
				if ($queryType == 'update') {
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

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('pgsoft_game_logs','original_game_logs_model','external_system'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$token = $this->getVariableFromContext($params, 'token');
		$sync_by_time = $this->getVariableFromContext($params, 'sync_by_time');
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$gameRecords = isset($resultArr['data'])?$resultArr['data']:array();
		$result = [];
		$dataCount = 0;
		$gameRecordsCount = is_array($gameRecords) ? count($gameRecords) : 0;

		$this->CI->utils->debug_log('PGSOFT gameRecords: ',$gameRecordsCount);

		$result = array('original_data_count'=> 0);
		if($success){	
			// reprocess game records base on database field in mg_game_logs
			$this->processGameRecords($gameRecords,$responseResultId);
			
			$gameRecordsKey = array_keys($gameRecords);

			$last_record = end($gameRecordsKey);
			$maxRowId = (!$sync_by_time) ? $gameRecords[$last_record]['rowversion'] : null;
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
				$this->original_gamelogs_table,		# original table logs
				$gameRecords,						# api record (format array)
				'external_uniqueid',				# unique field in api
				'external_uniqueid',				# unique field in mg_game_logs table
				self::MD5_FIELDS_FOR_ORIGINAL,
				'md5_sum',
				'id',
				self::MD5_FLOAT_AMOUNT_FIELDS
			);

			$insertRowsCount = is_array($insertRows) ? count($insertRows) : 0;
			$updateRowsCount = is_array($updateRows) ? count($updateRows) : 0;


			$this->CI->utils->debug_log('PG game api after process available rows', 'gamerecords ->',$gameRecordsCount,
							'insertrows->',$insertRowsCount, 'updaterows->',$updateRowsCount);

			if (!empty($insertRows)) {
				$result['original_data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['original_data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);

			$sync_id = $this->getValueFromSyncInfo($token, 'manual_last_sync_id');
			if ($sync_id) {
				$result['last_sync_id'] = $maxRowId;
				$this->CI->utils->debug_log('PGSOFT MANUAL_SYNC_LAST_ID', $maxRowId);
			} else if ($maxRowId) {
				$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
				$lastRowId = $maxRowId;
			} 
			// echo "<pre>";
			// print_r($insertRows);exit();

			// $availableRows = $this->CI->pgsoft_game_logs->getAvailableRows($gameRecords);
			// $this->CI->utils->debug_log('PGSOFT availableRows: ',count($availableRows));
			// foreach ($availableRows as $row) {
			// 	$insertRecord = [];

			// 	$insertRecord['betid'] = isset($row['betId'])?$row['betId']:null;
			// 	$insertRecord['parentbetid'] = isset($row['parentBetId'])?$row['parentBetId']:null;
			// 	$insertRecord['playername'] = isset($row['playerName'])?$row['playerName']:null;
			// 	$insertRecord['currency'] = isset($row['currency'])?$row['currency']:null;
			// 	$insertRecord['gameid'] = isset($row['gameId'])?$row['gameId']:null;
			// 	$insertRecord['platform'] = isset($row['platform'])?$row['platform']:null;
			// 	$insertRecord['bettype'] = isset($row['betType'])?$row['betType']:null;
			// 	$insertRecord['transactiontype'] = isset($row['transactionType'])?$row['transactionType']:null;
			// 	$insertRecord['betamount'] = isset($row['betAmount'])?$row['betAmount']:null;
			// 	$insertRecord['winamount'] = isset($row['winAmount'])?$row['winAmount']:null;
			// 	$insertRecord['jackpotrtpcontributionamount'] = isset($row['jackpotRtpContributionAmount'])?$row['jackpotRtpContributionAmount']:null;
			// 	$insertRecord['jackpotwinamount'] = isset($row['jackpotWinAmount'])?$row['jackpotWinAmount']:null;
			// 	$insertRecord['balancebefore'] = isset($row['balanceBefore'])?$row['balanceBefore']:null;
			// 	$insertRecord['balanceafter'] = isset($row['balanceAfter'])?$row['balanceAfter']:null;
			// 	$insertRecord['rowversion'] = isset($row['rowVersion'])?$row['rowVersion']:null;
			// 	$insertRecord['bettime'] = isset($row['betTime'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',($row['betTime']/1000))):null;
				
			// 	# SBE USE
			// 	$insertRecord['external_uniqueid'] = isset($row['betId'])?$row['betId']:null;
			// 	$insertRecord['response_result_id'] = $responseResultId;
			// 	$insertRecord['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
			// 	$insertRecord['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
			// 	//insert data to PGSOFT gamelogs table database
			// 	$this->CI->pgsoft_game_logs->insertGameLogs($insertRecord);

			// 	$this->last_rowversion = $insertRecord['rowversion'];

			// 	$dataCount++;
			// }

			# synced last rowversion 
			// if($this->last_rowversion!=0){
			// 	$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $this->last_rowversion);
			// }

			$result['original_data_count'] = $gameRecordsCount;

		}

		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
        /*
		$this->CI->load->model(array('game_logs', 'player_model', 'pgsoft_game_logs'));
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->pgsoft_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;
		if (!empty($result)) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $row) {
				$cnt++;

				$game_description_id = $row->game_description_id;
				$game_type_id = $row->game_type_id;

				if(empty($row->game_type_id)&&empty($row->game_description_id)){
					list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
				}

	            $extra = array(
                    'table'       => $row->round_id,
                    'trans_amount'=> $row->real_bet_amount
                );

                #result amount is winloss - bet amount
	            $result_amount = ((float)$row->result_amount-(float)$row->real_bet_amount);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$row->gameid,
					$row->game_type,
					$row->game,
					$row->player_id,
					$row->playername,
					$row->bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$row->after_balance,
					0, # has_both_side
					$row->external_uniqueid,
					$row->end_datetime, //start
					$row->end_datetime, //end
					$row->response_result_id,
					Game_logs::FLAG_GAME,
                    $extra
				);

			}
		}

		$this->CI->utils->debug_log('PGSOFT PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
		*/
	}

	    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){

        $sql = <<<EOD
            SELECT
              pgsoft_game_logs.id as sync_index,
              pgsoft_game_logs.playername,
              pgsoft_game_logs.gameid as gameid,
              pgsoft_game_logs.gameid as game_type,
              pgsoft_game_logs.betamount as bet_amount,
              pgsoft_game_logs.betamount as real_bet_amount,
              pgsoft_game_logs.winamount AS win_amount,
              pgsoft_game_logs.balanceafter AS after_balance,
              pgsoft_game_logs.parentbetid,
              pgsoft_game_logs.betid AS round_id,
              pgsoft_game_logs.betid AS round_number,
              pgsoft_game_logs.bettime as start_datetime,
              pgsoft_game_logs.bettime as end_datetime,
              pgsoft_game_logs.external_uniqueid,
              pgsoft_game_logs.response_result_id,
              pgsoft_game_logs.bettype,
              pgsoft_game_logs.md5_sum,
              game_provider_auth.player_id,
              game_description.id AS game_description_id,
              game_description.game_name AS game,
              game_description.game_code,
              game_description.game_type_id,
              game_description.void_bet
            FROM
              {$this->original_gamelogs_table} as pgsoft_game_logs
              LEFT JOIN game_description
                ON (
                  pgsoft_game_logs.gameid = game_description.external_game_id
                  AND game_description.game_platform_id = ?
                )
              JOIN `game_provider_auth`
                ON (
                  `pgsoft_game_logs`.`playername` = `game_provider_auth`.`login_name`
                  AND `game_provider_auth`.`game_provider_id` = ?
                )
            WHERE (
                pgsoft_game_logs.bettime >= ?
                AND pgsoft_game_logs.bettime <= ?
              )
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'bets', 'game_create_time'],
                ['amount']);
        }

        $result_amount = $row['win_amount'] - $row['bet_amount'];
		$this->CI->utils->debug_log('PGSOFT: (makeParamsForInsertOrUpdateGameLogsRowFromTrans)', 'row:', $row);

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => $result_amount,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_datetime'],
                'end_at' => $row['end_datetime'],
                'bet_at' => $row['start_datetime'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_Logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => $row['game_code'] == 'tournament' ? $this->preprocessBetDetails($row, null, $row['game_code'] == 'tournament') : $this->processNonTournamentBetDetailFormat($row),
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row['gameid'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['gameid']);

        $game_type_id = isset($unknownGame->game_type_id) ? $unknownGame->game_type_id:null;
        $game_type = isset($unknownGame->game_name) ? $unknownGame->game_name:"Unknown";

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function login($playerName, $password = null, $extra = null) {
    	return $this->returnUnimplemented();
	}
	
	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword = null) {
		return $this->returnUnimplemented();
	}

	public function syncLostAndFound($token) {
		if($this->enable_sync_lost_and_found){
        	return $this->getHistoryForSpecificTime($token);
		}
        return $this->returnUnimplemented();
    }

	public function queryForwardGameV2($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerToken = $this->getPlayerTokenByUsername($playerName);
		# add token prefix for callback forwarding 
		if(!empty($this->token_prefix)){
			$playerToken = $this->token_prefix.'|'.$playerToken;
		}

		$btt_real = 1;
		$btt_tournament = 3;
		$extra_args = array(
			"l" => $this->getLauncherLanguage($extra['language']),
			"btt" =>  $this->getGamelaunchCode($extra['game_mode']),
			"ot" => $this->operator_token,
			"ops" => $playerToken, 
			"f" => isset($extra['home_url']) ? $extra['home_url'] : $this->getSystemInfo('return_slot_url',null), 
		);

		if($this->unset_ot_extraargs){
			unset($extra_args['ot']);
		}

		$this->CI->utils->debug_log('PGSOFT: (queryForwardGameV2)', 'extra_args:', $extra_args);

		$params = array(
			"operator_token" => $this->operator_token,
			"path" => "/{$extra['game_code']}/index.html",
			"extra_args" => http_build_query($extra_args),
			"url_type" => "game-entry",
			"client_ip" =>  $this->CI->utils->getIP(),
		);

		$this->CI->utils->debug_log('PGSOFT: (queryForwardGameV2)', 'params:', $params);

		$guid = trim($this->createGUID(), '{}');
		$url_params = array(
			"trace_id" => $guid
		);
		$url = $this->pgsoft_api_domain . $this->URI_MAP[self::API_queryForwardGame]. "?". http_build_query($url_params);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$this->CI->utils->debug_log('PARIPLAY: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {
        $html = $this->getResultTextFromParams($params);
        $url = null;
        if(!$this->used_html_on_launching){
        	$url = $this->getUrlOnHtmlString($html);
        	// preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $html, $match);
        }
        
        $result = array("url" => null, "html" => null, "is_html" => $this->used_html_on_launching);
        $success = false;
        if(!empty($html) && $this->used_html_on_launching){
        	$result['html'] = $html;
        	$success = true;
        } elseif(!empty($url) && !$this->used_html_on_launching){
        	$result['url'] = $url;
        	$success = true;
        }
        return array($success, $result);
    }

    function getUrlOnHtmlString($string) {
        $regex = '/https?\:\/\/[^\" \n]+/i';
        preg_match_all($regex, $string, $matches);
        if(isset($matches[0][0])){
        	return $matches[0][0];
        }
        return null;
    }

	public function getGamelaunchCode($mode){
        switch ($mode) {
			case 'real': # Real game
				$bet_type = 1;
				break;
			case "tournament": # Tournament game
				$bet_type = 3;
				break;
			default:
				$bet_type = 1;
				break;
		}
		return $bet_type;
    }

    private function createGUID()
	{
		if (function_exists('com_create_guid')){
			return com_create_guid();
		} else {
			mt_srand((double)microtime()*10000);
			//optional for php 4.2.0 and up.
			$set_charid = strtoupper(md5(uniqid(rand(), true)));
			$set_hyphen = chr(45);
			// "-"
			$set_uuid = chr(123)
			.substr($set_charid, 0, 8).$set_hyphen
			.substr($set_charid, 8, 4).$set_hyphen
			.substr($set_charid,12, 4).$set_hyphen
			.substr($set_charid,16, 4).$set_hyphen
			.substr($set_charid,20,12)
			.chr(125);
			return $set_uuid;
		}
	}

	public function onlyTransferPositiveInteger(){
		return $this->only_transfer_positive_integer;
	}

    public function getTournamentList($request_params = []) {
        $params = [
            'operator_token' => !empty($request_params['operator_token']) ? $request_params['operator_token'] : $this->operator_token,
            'secret_key' => !empty($request_params['secret_key']) ? $request_params['secret_key'] : $this->secret_key,
            'start_time' => !empty($request_params['start_time']) ? strtotime($request_params['start_time']) * 1000 : null,
            'end_time' => !empty($request_params['end_time']) ? strtotime($request_params['end_time']) * 1000 : null,
            'currency' => !empty($request_params['currency']) ? $request_params['currency'] : $this->currency,
        ];

        if (isset($request_params['status'])) { // optional
            $params['status'] = $request_params['status'];
        }

        if (isset($request_params['game_ids'])) { // optional
            $params['game_ids'] = $request_params['game_ids'];
        }

        if (isset($request_params['language'])) { // optional
            $params['language'] = $request_params['language'];
        }

        $context =[
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTournamentList',
            'request_params' => $request_params,
            'params' => $params,
        ];

        $this->CI->utils->debug_log(__METHOD__, $params);

        return $this->callApi(self::API_getTournamentList, $params, $context);
    }

    public function processResultForGetTournamentList($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = [
            'success' => $success,
        ];

        if ($success) {
            $result = $resultArr;

            $this->CI->utils->debug_log(__METHOD__, $resultArr);
        }

        return array($success, $result);
   }

    public function getTournamentRecords($request_params = []) {
        $params = [
            'operator_token' => !empty($request_params['operator_token']) ? $request_params['operator_token'] : $this->operator_token,
            'secret_key' => !empty($request_params['secret_key']) ? $request_params['secret_key'] : $this->secret_key,
            'start_time' => !empty($request_params['start_time']) ? strtotime($request_params['start_time']) * 1000 : null,
            'end_time' => !empty($request_params['end_time']) ? strtotime($request_params['end_time']) * 1000 : null,
            'status' => !empty($request_params['status']) ? $request_params['status'] : 1, // 1: Receive reward successfully, 0: Failed to receive reward  
            'tournament_ids' => !empty($request_params['tournament_ids']) ? $request_params['tournament_ids'] : null,
            'currency' => !empty($request_params['currency']) ? $request_params['currency'] : $this->currency,
        ];

        if (isset($request_params['transaction_id'])) { // optional
            $params['transaction_id'] = $request_params['transaction_id'];
        }

        if (isset($request_params['player_name'])) { // optional
            $params['player_name'] = $request_params['player_name'];
        }

        if (isset($request_params['page_number'])) { // optional
            $params['page_number'] = $request_params['page_number'];
        }

        if (isset($request_params['row_count'])) { // optional
            $params['row_count'] = $request_params['row_count'];
        }

        $context =[
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTournamentRecords',
            'request_params' => $request_params,
            'params' => $params,
        ];

        $this->CI->utils->debug_log(__METHOD__, $params);

        return $this->callApi(self::API_getTournamentRecords, $params, $context);
    }

    public function processResultForGetTournamentRecords($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $request_params = $this->getVariableFromContext($params, 'request_params');

        $result = [
            'success' => $success,
        ];

        if ($success) {
            $result = $resultArr;

            $this->CI->utils->debug_log(__METHOD__, $resultArr);

            if (isset($request_params['save_to_game_logs']) && $request_params['save_to_game_logs']) {
                $this->CI->load->model(['original_seamless_wallet_transactions']);
                $to_save_data = [];
                $inserted = 0;
                $updated = 0;

                if (!empty($resultArr['data']['result']) && is_array($resultArr['data']['result'])) {
                    foreach ($resultArr['data']['result'] as $data) {
                        $tournament_id = !empty($data['tournamentId']) ? $data['tournamentId'] : null;
                        $winners = !empty($data['winners']) ? $data['winners'] : [];

                        foreach ($winners as $winner) {
                            $operator_token = !empty($winner['operatorToken']) ? $winner['operatorToken'] : null;

                            $to_save_data['betid'] = isset($winner['transactionId']) ? $winner['transactionId'] : null;
                            $to_save_data['parentbetid'] = $tournament_id;
                            $to_save_data['playername'] = isset($winner['playerName']) ? $winner['playerName'] : null;
                            $to_save_data['currency'] = isset($winner['playerCurrency']) ? $winner['playerCurrency'] : null;
                            $to_save_data['gameid'] = 'tournament';
                            $to_save_data['platform'] = null;
                            $to_save_data['bettype'] = null;
                            $to_save_data['transactiontype'] = isset($winner['transactionType'])?$winner['transactionType']:null;
                            $to_save_data['betamount'] = 0;
                            $to_save_data['winamount'] = isset($winner['adjustmentAmount']) ? $winner['adjustmentAmount'] : 0;
                            $to_save_data['jackpotrtpcontributionamount'] = 0;
                            $to_save_data['jackpotwinamount'] = 0;
                            $to_save_data['balancebefore'] = 0;
                            $to_save_data['balanceafter'] = 0;
                            $to_save_data['rowversion'] = null;
                            $to_save_data['bettime'] = isset($winner['transactionTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', ($winner['transactionTime'] / 1000))) : null;

                            # SBE USE
                            $to_save_data['external_uniqueid'] = $this->utils->mergeArrayValues([$tournament_id, $to_save_data['betid']]);
                            $to_save_data['response_result_id'] = $responseResultId;
                            $to_save_data['created_at'] = $this->utils->getNowForMysql();
                            $to_save_data['updated_at'] = $this->utils->getNowForMysql();
                            $to_save_data['md5_sum'] = md5(json_encode($winner));

                            $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_gamelogs_table, ['external_uniqueid' => $to_save_data['external_uniqueid']]);

                            // insert if not exists
                            if (empty($transaction) && $operator_token == $this->operator_token) {
                                $is_inserted = $this->CI->original_seamless_wallet_transactions->insertOrUpdateTransactionData($this->original_gamelogs_table, 'insert', $to_save_data);

                                if ($is_inserted) {
                                    $inserted++;
                                    $this->utils->debug_log(__METHOD__, 'inserted external_uniqueid', $to_save_data['external_uniqueid']);
                                }
                            } else { // update if exists
                                if ($transaction['md5_sum'] != $to_save_data['md5_sum']) {
                                    $is_updated = $this->CI->original_seamless_wallet_transactions->insertOrUpdateTransactionData($this->original_gamelogs_table, 'update', $to_save_data, 'external_uniqueid', $to_save_data['external_uniqueid'], true);

                                    if ($is_updated) {
                                        $updated++;
                                        $this->utils->debug_log(__METHOD__, 'updated external_uniqueid', $to_save_data['external_uniqueid']);
                                    }
                                }
                            }
                        }
                    }
                }

                $result['inserted'] = $inserted;
                $result['updated'] = $updated;
            }
        }

        return array($success, $result);
    }

    public function defaultBetDetailsFormat($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = [];

        if ($row['game_code'] == 'tournament') {
            if (isset($row['parentbetid'])) {
                $bet_details['tournamentId'] = $row['parentbetid'];
            }

            if (isset($row['round_id'])) {
                $bet_details['transactionId'] = $row['round_id'];
            }

            if (isset($row['start_datetime'])) {
                $bet_details['transactionTime'] = $row['start_datetime'];
            }
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

	public function processNonTournamentBetDetailFormat($row){
		
        $bet_details = [];

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

		if (isset($row['bettype'])) {
			if($row['bettype']){
				$bet_details['bet_type'] = 'Slots';
			}
		}

		if (isset($row['start_datetime'])) {
            $bet_details['betting_time'] = $row['start_datetime'];
		}

        return $bet_details;

	}
}

/*end of file*/