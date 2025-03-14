<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Gets platform code
 * * Generates URL
 * * Creates Player
 * * Checks Player Information
 * * Block/Unblock Players
 * * Deposits to Game
 * * Withdraw from Game
 * * Login/Logout
 * * Update Player information
 * * Checks player balance
 * * Checks player daily balance
 * * Checks Game Records
 * * Check Login Status
 * * Check Forward game
 * * Synchronize Original Game Logs
 * * Gets Gameplay table game logs
 * * Gets Gameplay all Games game Logs
 * * Synchronize and Merge All games game logs
 * * Synchronize gameplay game logs
 * * Get Game Description Information
 * * Get Gameplay game logs statistics

 * All below behaviors are not yet implemented
 * * Check Total Betting amount
 * * check transaction
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_gameplay extends Abstract_game_api {

	private $gameplay_api_url;
	private $gameplay_merchant_id;
	private $gameplay_merchant_pw;
	private $gameplay_currency;
	private $gameplay_language;
	private $gameplay_balance;
	private $gameplay_test_cust;
	private $gameplay_api_history_call;
    private $gameplay_current_subprovider;
    public $gameplay_api_generic_game_history_url;

	const STATUS_SUCCESS = '0';
	const API_RETURN_FAILED = '03';
	const API_RETURN_MSG_FAILED = '​Authentication Failed';
	const LIVECASINO_GAME = 69;
	const SLOTS_GAME = 68;
	const ITEM_PER_PAGE = 500;
	const ITEM_PER_PAGE_RSLOT = 2000;

	//for sync
	const SLOTANDTABLE_API = 1;
	const CTXM_API = 2;
	const SBTECH_API = 3;
		//added Sub-providers
	const BETSOFT = "BETSOFT";
	const CTXM = "CTXM";
	const ISOFTBET = "ISOFTBET";
	const PNG = "PNG";
	const TTG = "AGS";
    const KENO = "KENO";
	const SODE = "SODE";

	const GAME_STATUS_NORMAL	= 1;
	const GAME_STATUS_VOID		= 2;
	const GAME_STATUS_OPEN		= 3;

	const URI_MAP = array(
		self::API_createPlayer => '/op/createuser',
		self::API_login => '/op/login',
		self::API_logout => '/op/SuspenseMember',
		self::API_queryPlayerBalance => '/op/getbalance',
		self::API_depositToGame => '/op/credit',
		self::API_withdrawFromGame => '/op/debit',
		self::API_syncGameRecords => '/op/betSummary',
		self::API_queryTransaction => '/op/check',
	);

	const SLOT = "slot";
	const RSLOT = "rslot";
	const TABLE = "table";

	const UNIQUE_ID_FIELD = 'operationCode';
	const RSLOTS_LOBBY_URL = 'd_lobby';

	const GAME_CODE_THAILOTTERY = 'thailottery';
	const ERROR_REQUEST_TOO_OFTEN = "Request too often, please try again in 15 seconds";
	const ERROR_BLOCKED_PLAYER = "Player is blocked";
	const ERROR_PARAMETER_MISMATCH = "Parameter type mismatch";
	const ERROR_INCORRECT_MERCHANT_PWD = "Invalid merchant password";
	const ERROR_MISSING_INPUT_PARAMS = "Missing input parameter";
	const ERROR_CUSTOMER_EXISTS = "Customer already exist";
	const ERROR_TRANSACTION_NOT_EXISTS = "Transaction not exists";

	public function __construct() {
		parent::__construct();

		$this->gameplay_api_url = $this->getSystemInfo('url');
		$this->gameplay_merchant_id = $this->getSystemInfo('gameplay_merchant_id');
		$this->gameplay_merchant_pw = $this->getSystemInfo('gameplay_merchant_pw');
		$this->gameplay_history_pw = $this->getSystemInfo('gameplay_history_pw');
		$this->gameplay_currency = $this->getSystemInfo('gameplay_currency');
		$this->gameplay_country = $this->getSystemInfo('gameplay_country');
		$this->gameplay_language = $this->getSystemInfo('gameplay_language');
		$this->gameplay_test_cust = $this->getSystemInfo('gameplay_test_cust');

		$this->game_force_language = $this->getSystemInfo('game_force_language', false);
		
		$this->gameplay_mobile_livegame_url = $this->getSystemInfo('gameplay_mobile_livegame_url', 'https://casino.gpiops.com/html5/mobile');
		$this->gameplay_livegame_url = $this->getSystemInfo('live_mode') ? $this->getSystemInfo('gameplay_livegame_url') : $this->getSystemInfo('gameplay_livegame_uat_url');
		$this->gameplay_slotgame_url = $this->getSystemInfo('live_mode') ? $this->getSystemInfo('gameplay_slotgame_url') : $this->getSystemInfo('gameplay_slotgame_uat_url');
		$this->gameplay_rslotgame_url = $this->getSystemInfo('live_mode') ? $this->getSystemInfo('gameplay_rslotgame_url') : $this->getSystemInfo('gameplay_rslotgame_uat_url');
		$this->gameplay_mobile_rslotgame_url = $this->getSystemInfo('gameplay_mobile_rslotgame_url', 'http://mgamelobby.gpiuat.com');
		$this->gameplay_kenogame_url = $this->getSystemInfo('live_mode') ? $this->getSystemInfo('gameplay_kenogame_url') : $this->getSystemInfo('gameplay_kenogame_uat_url');
		$this->gameplay_mslotsgame_url = $this->getSystemInfo('live_mode') ? $this->getSystemInfo('gameplay_mslotsgame_url') : $this->getSystemInfo('gameplay_mslotsgame_url');

		$this->gameplay_api_table_game_history_url = $this->getSystemInfo('gameplay_api_table_game_history_url');
		$this->gameplay_api_rslot_game_history_pwd = $this->getSystemInfo('gameplay_api_rslot_game_history_pwd');
		$this->gameplay_api_slot_game_history_url = $this->getSystemInfo('gameplay_api_slot_game_history_url');
		$this->gameplay_api_rslot_game_history_url = $this->getSystemInfo('gameplay_api_rslot_game_history_url');
        $this->gameplay_api_keno_game_history_url =  $this->getSystemInfo('gameplay_api_keno_game_history_url');

		$this->gameplay_home_url = $this->getSystemInfo('gameplay_home_url');
		$this->gameplay_lobby_url = $this->getSystemInfo('gameplay_lobby_url');
		$this->gameplay_funds_url = $this->getSystemInfo('gameplay_funds_url');

		$this->gameplay_rslots_lobby_url = $this->getSystemInfo('gameplay_rslots_lobby_url', 'https://gamelobby.gpiops.com');

		//token prefix
		$this->token_prefix = $this->getSystemInfo('token_prefix');
		$this->prefix_count = $this->getSystemInfo('prefix_count', 3);
		$this->forward_sites = $this->getSystemInfo('forward_sites');

		//cxtm
		$this->gameplay_cxtm_slotgame_url = $this->getSystemInfo('gameplay_cxtm_slotgame_url');
		$this->gameplay_cxtm_slot_game_history_url = $this->getSystemInfo('gameplay_cxtm_slot_game_history_url');
		//sbt
		$this->gameplay_sbtech_game_url = $this->getSystemInfo('gameplay_sbtech_game_url');
		$this->gameplay_sbtech_game_mobile_url = $this->getSystemInfo('gameplay_sbtech_game_mobile_url');

		$this->gameplay_api_sbtech_game_history_url = $this->getSystemInfo('gameplay_api_sbtech_game_history_url');
		$this->gameplay_sbt_operators_token = $this->getSystemInfo('gameplay_sbt_operators_token');
		$this->gameplay_sbt_langid = $this->getSystemInfo('gameplay_sbt_langid');
		$this->gameplay_sbt_oddsstyleid = $this->getSystemInfo('gameplay_sbt_oddsstyleid');
		$this->gameplay_sbt_demo_account = $this->getSystemInfo('gameplay_sbt_demo_account');

		$this->enabled_api = $this->getSystemInfo('gameplay_enabled_api');

		//added SUBPROVIDERS -> Garry
		//BETSOFT
		$this->gameplay_betsoft_url = $this->getSystemInfo('gameplay_betsoft_url');
		//TTG
		$this->gameplay_ttg_url = $this->getSystemInfo('gameplay_ttg_url');
		$this->gameplay_ttg_web_fun_url = $this->getSystemInfo('gameplay_ttg_web_fun_url');
		$this->gameplay_ttg_mobile_fun_url = $this->getSystemInfo('gameplay_ttg_mobile_fun_url');
		//PlayNGo
		$this->gameplay_png_url = $this->getSystemInfo('gameplay_png_url');
		//ISOFTBET
		$this->gameplay_isb_url = $this->getSystemInfo('gameplay_isb_url');
		//OTHERS HISTORY
		$this->gameplay_others_history = $this->getSystemInfo('gameplay_others_history');
		$this->common_wait_seconds = $this->getSystemInfo('common_wait_seconds',20);
		$this->max_call_attempt = $this->getSystemInfo('max_call_attempt', 10);

		// new history link
		$this->gameplay_api_generic_game_history_url =  $this->getSystemInfo('gameplay_api_generic_game_history_url');
		$this->gameplay_api_keno_redirect_link =  $this->getSystemInfo('gameplay_api_keno_redirect_link');

		$this->gameplay_hide_exit =  $this->getSystemInfo('gameplay_hide_exit');
		$this->gameplay_is_redirect = $this->getSystemInfo('gameplay_is_redirect');
		$this->enable_opposite_bet_checking = $this->getSystemInfo('enable_opposite_bet_checking', true);
		$this->hand_result_for_opposite_bet = $this->getSystemInfo('hand_result_for_opposite_bet', ['Banker-Player', 'Player-Banker', 'Tiger-Dragon', 'Dragon-Tiger']);
	}

	public function getPlatformCode() {
		return GAMEPLAY_API;
	}

	public function generateUrl($apiName, $params) {
		if (array_key_exists('slots', $params)) {
			unset($params["slots"]);
		}
		if (isset($this->gameplay_api_history_call)) {
			$url = $this->gameplay_api_history_call;
			$this->gameplay_api_history_call = null;

            if (isset($this->gameplay_current_subprovider) && $this->gameplay_current_subprovider == "keno") {
                return $url;
            }
		} else {
			$apiUri = self::URI_MAP[$apiName];
			$url = $this->gameplay_api_url . $apiUri;
		}

		$paramsStr = http_build_query($params);
		return $url . "?" . $paramsStr;
	}

    protected function customHttpCall($ch, $params) {
    	if (!array_key_exists('slots', $params)) {
			if(isset($params['game']) && $params['game'] == self::GAME_CODE_THAILOTTERY) {
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				$data =  http_build_query($params);
			} else {
				$data = json_encode($params);
			}
		    curl_setopt($ch, CURLOPT_POST, true);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
    }

    public function getHttpHeaders($params){
 		if(isset($params['game']) && $params['game'] == self::GAME_CODE_THAILOTTERY) {
			return null;
		}
        return array("Content-Type" => "application/json");
    }

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		if(isset($resultArr[0]) && $resultArr[0] == self::ERROR_REQUEST_TOO_OFTEN) {
			return false;
		}
		$success = false;
		if (empty($resultArr)) {
			$this->setResponseResultToError($responseResultId);
			return false;
		}
		if (isset($resultArr['error_code'])) {
			$success = !empty($resultArr) && $resultArr['error_code'] == self::STATUS_SUCCESS;
		} else {
			if (isset($resultArr['history']) || isset($resultArr['@attributes']['pageNum'], $resultArr['@attributes']['pageSize'], $resultArr['@attributes']['totalPage'], $resultArr['@attributes']['currentRows'], $resultArr['@attributes']['totalRows'])) {
				$success = true;
			} elseif (isset($resultArr)){
                $success = true;
            } else {
				$success = false;
			}
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('gameplay got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function callback($method, $result = null, $platform = 'web') {

		$this->CI->utils->debug_log('GAMEPLAY_RAW_RESULT ', $result);


		if ($method == 'validatemember') {

			if ($platform == 'web') {

				#FOR WEB APP
				$this->CI->utils->debug_log('GAMEPLAY_WEB_RESPONSE_TOKEN', $result);
				$error_data = array(
					"error_code" => self::API_RETURN_FAILED,
					"error_msg" => self::API_RETURN_MSG_FAILED,
					);

				$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><resp></resp>");
				$xmlData = null;

				$this->CI->load->model('game_provider_auth');
				
				$token = $result;
				if (!empty($result)) {
					//forward site
					if ($this->forward_sites) {							
						$str = substr($token, 0, 3);
						$this->CI->utils->debug_log('>>>>>>>>>> gameplay forward str', $str);
						$prefix = substr($str, 0, $this->prefix_count)."_";
						$this->CI->utils->debug_log('>>>>>>>>>> gameplay forward prefix', $prefix);
						if (isset($this->forward_sites[$prefix])) {
							$token = substr($result, 4);
							$this->CI->utils->debug_log('>>>>>>>>>> gameplay forward token', $token);
							$url = $this->forward_sites[$prefix].$method.'?ticket='.$token;
							$this->CI->utils->debug_log('>>>>>>>>>> gameplay forward callback url:', $url);
							return $this->forwardCallback($url, array());
						}
					}
					$this->CI->utils->debug_log('>>>>>>>>>> gameplay token', $token);
					//check token
					$playerInfo = $this->getPlayerInfoByToken($token);
					$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

					$playerDetails = $this->CI->player->getPlayerById($playerInfo['playerId']);

					$playerIP = $playerInfo['lastLoginIp'];

					if(isset($playerDetails['birthdate'])){
						$playerBirthDate = date("d-m-Y", strtotime($playerDetails['birthdate']));
					}else{
						$playerBirthDate = "";
					}

					if (!empty($gameUsername)) {

						$data = array(
							"error_code" => self::STATUS_SUCCESS,
							"cust_id" => $gameUsername,
							"cust_name" => $gameUsername,
							"currency_code" => $this->gameplay_currency,
							"language" => $this->gameplay_language,
							"test_cust" => $this->gameplay_test_cust,
							"country" => $this->gameplay_country,
							"IP" => $playerIP,
							"DOB" => $playerBirthDate,
							);
						$this->CI->utils->info_log('GAMEPLAY_WEB_SUCCESS', $data);
						$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);

					} else {
						$data = $error_data;
						$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
						$this->CI->utils->error_log('GAMEPLAY_WEB_NOT_FOUND_ACCOUNT_NAME', $token);
					}
				} else {
					$data = $error_data;
					$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
					$this->CI->utils->error_log('GAMEPLAY_WEB_EMPTY_TOKEN', $result);
				}

				return $xmlData;


			} else {
                #FOR MOBILE AND PC
				$result = json_decode($result, true);
				$this->CI->utils->debug_log('GAMEPLAY (mobile app result)', $result);
				$resultText = NULL;
				$hash_key = $this->getSystemInfo('callback_hash_key');

				if (isset($result['Username']) && isset($result['Password']) && isset($result['Hash'])) {
					$hash = md5($result['Username'] . $result['Password'] . $hash_key);
					$getHash=isset($result['Hash']) && !empty($result['Hash']) ? $result['Hash'] : null;
					$getUsername=isset($result['Username']) && !empty($result['Username']) ? $result['Username'] : null;
					$getPass=isset($result['Password']) && !empty($result['Password']) ? $result['Password'] : null;
					$this->CI->utils->debug_log('GAMEPLAY (Hash for mobile)', 'hash', $getHash, 'username', $getUsername, 'password', $getPass, 'hash_key', $hash_key);
					if ($this->forward_sites) {							
						$prefix = substr($result['Username'], 0, $this->prefix_count)."_";
						if (isset($this->forward_sites[$prefix])) {
							$url = $this->forward_sites[$prefix].$method."/mobile";
							return $this->forwardCallback($url,$result);
						}
					}

					if ($hash == $result['Hash']) {
						$this->CI->load->model(array('game_provider_auth'));
						$password = $this->CI->game_provider_auth->getPasswordByLoginName($result['Username'], $this->getPlatformCode());
						$this->CI->utils->debug_log('GAMEPLAY (Password)', 'password', $password, 'Get result password', $getPass);
						if ($password == $result['Password']) {
							$this->CI->load->model('common_token');
							$playerId = $this->getPlayerIdInGameProviderAuth($result['Username']);
							# Create token via playerId
							$token = $this->CI->common_token->createTokenBy($playerId, 'player_id');
							$resultText = $token;
							if ($this->token_prefix) {	
								$resultText = $this->token_prefix.$token;
							}
							$this->CI->utils->error_log('GAMEPLAY (Mobile app resultText)', $resultText);
							return $resultText;
						} else {
							$resultText = 'InvalidPassword';
						}
					} else {
						$resultText = 'InvalidAccountName';
					}
				}

				$this->CI->utils->error_log('GAMEPLAY (Mobile app resultText)', $resultText);

				return $resultText;
			}
		} 
	}

	function forwardCallback($url, $params) {
		list($header, $resultXml) = $this->httpCallApi($url, $params);
		$this->CI->utils->debug_log('forwardCallback', $url, $header, $resultXml);
		return $resultXml;
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$extra = $this->CI->game_description_model->getGameTypeById($game['g']);
		$game['gp'] = "iframe_module/goto_gpgame/" . $this->getPlatformCode() . "/" . $game['g'] . "/" . $game['c'] . "/" . strtolower($extra); //game param
		return $game;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		// $password = uniqid();
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		return $this->callApi(self::API_createPlayer,
			array(
				"merch_id" => $this->gameplay_merchant_id,
				"merch_pwd" => $this->gameplay_merchant_pw,
				"cust_id" => $playerName,
				"cust_name" => $playerName,
				"currency" => $this->gameplay_currency,
				"test_cust" => $this->gameplay_test_cust,
			),
			$context);
	}

	public function processResultForCreatePlayer($params) {
		// $this->CI->utils->debug_log($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$resultArr=$this->CI->utils->xmlToArray($resultXml);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$exists=$success;
		if(!$success){
			if(isset($resultArr['error_code'])){
				$exists=$resultArr['error_code']=='-203';
			}
		}
		if($exists || $success){
			//set register
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			if($exists){
				$success=$exists;
			}
		}
		return array($success, null);
	}

	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		$newSbePassword = $newPassword;
		$this->updatePasswordForPlayer($playerId, $newSbePassword);

		return array("success" => true);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = !empty($transfer_secure_id) ? $transfer_secure_id : $this->getSecureId('transfer_request', 'secure_id', false, 'T');

		// $trx_id = random_string('alpha');
		$context = array(
			'callback_obj'			  => $this,
			'callback_method' 		  => 'processResultForDepositToGame',
			'playerName' 			  => $playerName,
			'gameUsername' 			  => $gameUsername,
			'amount' 				  => $amount,
			'external_transaction_id' => $external_transaction_id,
		);

		$params = array(
			"merch_id" 	 => $this->gameplay_merchant_id,
			"merch_pwd"  => $this->gameplay_merchant_pw,
			"cust_id"	 => $gameUsername,
			"currency"   => $this->gameplay_currency,
			"amount" 	 => $this->dBtoGameAmount($amount),
			"trx_id" 	 => $external_transaction_id,
			"test_cust"  => $this->gameplay_test_cust,
		);
		
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId  		 = $this->getResponseResultIdFromParams($params);
		$resultXml 		   	 	 = $this->getResultXmlFromParams($params);
		$resultArr 		   		 = $this->CI->utils->xmlToArray($resultXml);
		$playerName 	   		 = $this->getVariableFromContext($params, 'playerName');
		$gameUsername 	   		 = $this->getVariableFromContext($params, 'gameUsername');
		$amount 		   		 = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$success 		   		 = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		
		$result = array(
			'response_result_id' 		=> $responseResultId,
			'external_transaction_id'	=> $external_transaction_id,
            'reason_id'					=> self::REASON_UNKNOWN, 
            'transfer_status'			=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
       	);
		if ($success) {
			//for sub wallet
			// $afterBalance = isset($resultArr['after']) ? $resultArr['after'] : null;
			// $afterBalance = $this->gameAmountToDB($afterBalance);
			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			// $result["userNotFound"] = true;
			if (!empty($resultArr) && isset($resultArr['error_code'])) {
				$result['reason_id'] 	   = $this->getTransferErrorReasonCode($resultArr['error_code']);
				$result['transfer_status'] = $this->isDeclinedStatus($resultArr['error_code']) ? self::COMMON_TRANSACTION_STATUS_DECLINED : self::COMMON_TRANSACTION_STATUS_UNKNOWN;

				# if error 500, treat as success
				if((in_array($resultArr['error_code'], $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$success=true;
				}
			}
			
		}

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {		
		$gameUsername 	   = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = !empty($transfer_secure_id) ? $transfer_secure_id : $this->getSecureId('transfer_request', 'secure_id', false, 'T');

		// $trx_id = random_string('alpha');
		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForWithdrawToGame',
			'playerName' 		=> $playerName,
			'gameUsername' 		=> $gameUsername,
			'amount' 			=> $amount,
			'external_transaction_id' => $external_transaction_id,
		);

		$params = array(
			"merch_id"  => $this->gameplay_merchant_id,
			"merch_pwd" => $this->gameplay_merchant_pw,
			"cust_id" 	=> $gameUsername,
			"currency" 	=> $this->gameplay_currency,
			"amount" 	 => $this->dBtoGameAmount($amount),
			"trx_id" 	=> $external_transaction_id,
			"test_cust" => $this->gameplay_test_cust,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId 		 = $this->getResponseResultIdFromParams($params);
		$resultXml 		  		 = $this->getResultXmlFromParams($params);
		$resultArr 		   		 = $this->CI->utils->xmlToArray($resultXml);
		$playerName		  		 = $this->getVariableFromContext($params, 'playerName');
		$gameUsername 	  		 = $this->getVariableFromContext($params, 'gameUsername');
		$amount 		  		 = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$success 		  		 = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		// $result = array();
		// $result = array('response_result_id' => $responseResultId);
		
		$result = array(
			'response_result_id' 		=> $responseResultId,
			'external_transaction_id'	=> $external_transaction_id,
            'reason_id'					=> self::REASON_UNKNOWN,
            'transfer_status'			=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
       	);

		if ($success) {
			//for sub wallet
			// $afterBalance = isset($resultArr['after']) ? $resultArr['after'] : null;
			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdrawal
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			if (!empty($resultArr) && isset($resultArr['error_code'])) {
				$result['reason_id'] 	   = $this->getTransferErrorReasonCode($resultArr['error_code']);
				$result['transfer_status'] = $this->isDeclinedStatus($resultArr['error_code']) ? self::COMMON_TRANSACTION_STATUS_DECLINED : self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
		}

		return array($success, $result);
	}

	public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function processResultForLogin($params) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function processResultForLogout($params) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function updatePlayerInfo($playerName, $infos) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);
		return $this->callApi(self::API_queryPlayerBalance,
			array(
				"merch_id" => $this->gameplay_merchant_id,
				"merch_pwd" => $this->gameplay_merchant_pw,
				"cust_id" => $playerName,
				"currency" => $this->gameplay_currency,
				"test_cust" => $this->gameplay_test_cust,
			),
			$context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array();
		if ($success) {
			$result['balance'] = @floatval($this->gameAmountToDB($resultArr['balance']));
		}
		return array($success, $result);
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		$daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);

		$result = array();

		if ($daily_balance != null) {
			foreach ($daily_balance as $key => $value) {
				$result[$value['updated_at']] = $value['balance'];
			}
		}

		return array_merge(array('success' => true, "balanceList" => $result));
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}

	public function checkLoginStatus($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true, "loginStatus" => true);
	}

	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {

	}

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
    	$playerId=$extra['playerId'];
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			"merch_id" => $this->gameplay_merchant_id,
			"merch_pwd" => $this->gameplay_merchant_pw,
			"trx_id" => $transactionId,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}
	public function processResultForQueryTransaction($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log('GAMEPLAY queryTransaction: ',$resultArr, $success);

		$result = array(
			'response_result_id' => $responseResultId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if ($success) {
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			if (!empty($resultArr) && isset($resultArr['error_code'])) {
				$result['reason_id'] 	   = $this->getTransferErrorReasonCode($resultArr['error_code']);
				$result['transfer_status'] = $this->isDeclinedStatus($resultArr['error_code']) ? self::COMMON_TRANSACTION_STATUS_DECLINED : self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
		}

		return array($success, $result);
	}

	public function getLauncherLanguage($language){
		$lang='';
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en-us';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'id-id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vi-vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko-kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th-th';
                break;
            default:
                $lang = 'en-us';
                break;
        }
        return $lang;
	}

	public function queryForwardGame($playerName, $params) {
		# get correct param value for language 
		$language = $this->getLauncherLanguage($params['language']);

		if ($this->game_force_language) {
			$language = $this->game_force_language;
		}

		if (!$playerName) {
			$playerName = $this->gameplay_sbt_demo_account;
		}

		$token = null;
		if ($playerName) {
			$token = $this->token_prefix.$this->getPlayerTokenByUsername($playerName);
		}

		$this->CI->utils->debug_log('GAMEPLAY queryForwardGame token: ', $token);
		$this->CI->utils->debug_log('GAMEPLAY queryForwardGame extra: ', $params);

		$platform=isset($params['extra']['platform']) ? $params['extra']['platform'] : '';
		$testurl = $this->gameplay_livegame_url . '/?op=' . $this->gameplay_merchant_id . '&lang=' . $language . '&m=normal&token=' . $token;

		$lottery_mode = $params['extra']['game_mode'] == 0 ? "real" : "try" ; 

		switch ($platform) {
			case 'table':
				if ($params['is_mobile']) {
					$url = $this->gameplay_mobile_livegame_url . '/?op=' . $this->gameplay_merchant_id . '&lang=' . $language . '&m=normal&token=' . $token;
				} else {
					$url = $this->gameplay_livegame_url . '/?op=' . $this->gameplay_merchant_id . '&lang=' . $language . '&m=normal&token=' . $token;
				}
				break;

			case 'rslot':
				$game_mode = $params['extra']['game_mode'] ? $params['extra']['game_mode'] : 0;
				if ($params['is_mobile']) {
					if ($params['game_code'] == self::RSLOTS_LOBBY_URL) {
						$url = $this->gameplay_mobile_rslotgame_url . '/?fun=' . $game_mode . '&token=' . $token . '&op=' . $this->gameplay_merchant_id . '&lang=' . $language;
					} else {
						$url = $this->gameplay_rslotgame_url . '/' . $params['game_code'] . '/?fun=' . $game_mode . '&token=' . $token . '&op=' . $this->gameplay_merchant_id . '&lang=' . $language;
					}
				} else {
					if ($params['game_code'] == self::RSLOTS_LOBBY_URL) {
						$url = $this->gameplay_rslots_lobby_url . '/?fun=' . $game_mode . '&token=' . $token . '&op=' . $this->gameplay_merchant_id . '&lang=' . $language;
					} else {
						$url = $this->gameplay_rslotgame_url . '/' . $params['game_code'] . '/?fun=' . $game_mode . '&token=' . $token . '&op=' . $this->gameplay_merchant_id . '&lang=' . $language;
					}
				}
				
				break;

			case 'cxtm':
				$mode = $params['extra']['game_mode']==0?"real":"fun";
				$url = $this->gameplay_cxtm_slotgame_url . '?op=' . $this->gameplay_merchant_id . '&game_code=' . $params['game_code'] . '&lang=' . $language . '&playmode=' . $mode . '&ticket=' . $token;
				break;
			case 'sbtech':
				if(!$params['extra']['is_mobile']){ // DESKTOP CLIENT
					$url = $this->gameplay_sbtech_game_url . '?stoken=' . $this->gameplay_merchant_id . '_' . $token . '&langid=' . $this->gameplay_sbt_langid . '&oddsstyleid=' . $this->gameplay_sbt_oddsstyleid;
				}else{ // mobile
					$url = $this->gameplay_sbtech_game_mobile_url . '?stoken=' . $this->gameplay_merchant_id . '_' . $token . '&langid=' . $this->gameplay_sbt_langid . '&oddsstyleid=' . $this->gameplay_sbt_oddsstyleid;
				}
				break;
			case 'pk10':
                $url = $this->gameplay_kenogame_url . '/Login?vendor=' . $this->gameplay_merchant_id . '&lang=' . $language . '&game=pk10&view=6&theme=2&version=3&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'keno':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=keno&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'ladder':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=ladder&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'rockpaperscissors':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=rockpaperscissors&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'thailottery':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=thailottery&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'fast3':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=fast3&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'thor':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=thor&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
            case 'sode':
                $url = $this->gameplay_kenogame_url . '/Login?lang='.$language.'&game=sode&version=3&vendor=' . $this->gameplay_merchant_id . '&ticket=' . $token ."&mode=". $lottery_mode ."&domainlink=". $this->gameplay_api_keno_redirect_link . "&hideexit=" . $this->gameplay_hide_exit;
                break;
			case 'betsoft':
				$mode = $params['extra']['game_mode']==0?"real":"fun";
				$language = $language=="zh-cn"?"zh":"en";
				$url = $this->gameplay_betsoft_url . '?op='.$this->gameplay_merchant_id.'&token='.$token."&mode=".$mode.'&lang='.$language.'&gameId='.$params['game_code'];
				break;
			case 'ttg':
				$mode = $params['extra']['game_mode']==0?"real":"fun";
				$language = $language=="zh-cn"?"zh-cn":"en";
				if($mode == "real"){ // real play
					if(!$params['extra']['is_mobile']){ // DESKTOP CLIENT
						$request_url = $this->gameplay_ttg_url.'/ms/loadingAGS.xml?merch_id='.$this->gameplay_merchant_id.'&merch_pwd='.$this->gameplay_merchant_pw.'&ticket='.$token.'&operator='.$this->gameplay_merchant_id.'&gameType='.$params['game_type'].'&gameId='.$params['game_code'].'&lang='.$language.'&gameName='.$params['game_name'];
					}else{ // MOBILE
						$request_url = $this->gameplay_ttg_url.'/ms/loadingAGSMobile.xml?merch_id='.$this->gameplay_merchant_id.'&merch_pwd='.$this->gameplay_merchant_pw.'&ticket='.$token.'&operator='.$this->gameplay_merchant_id.'&gameType='.$params['game_type'].'&gameId='.$params['game_code'].'&lang='.$language.'&gameName='.$params['game_name'].'&lobbyURL=';
					}
					$url = $this->getTtgGameLauncher($request_url);
				}else{ // fun play
					if(!$params['extra']['is_mobile']){ // DESKTOP CLIENT
						$url = $this->gameplay_ttg_web_fun_url."?gameSuite=flash&gameName=".$params['game_name']."&lang=".$language."&playerHandle=999999&gameType=".$params['game_type']."&gameId=".$params['game_code']."&account=FunAcct";
					}else{ // MOBILE
						$url = $this->gameplay_ttg_mobile_fun_url."?playerHandle=999999&account=FunAcct&gameName=".$params['game_name']."&gameType=".$params['game_type']."&gameId=".$params['game_code']."&lang=".$language."&lsdId=awc888&deviceType=mobile&lobbyUrl=?playerHandle=999999&account=FunAcct";
					}
				}
				break;
			case 'png': // PlayNGo
				$mode = $params['extra']['game_mode']==0?"real":"fun";
				$language = $language=="zh-cn"?"zh_CN":"en_GB";
				if(!$params['extra']['is_mobile']){ // DESKTOP CLIENT
					$url = $this->gameplay_png_url.'PNG.html?op='.$this->gameplay_merchant_id.'&token='.$token.'&mode='.$mode.'&lang='.$language.'&gameId='.$params['game_code'];
				}else{ // MOBILE
					$url = $this->gameplay_png_url.'PNGMobile.html?op='.$this->gameplay_merchant_id.'&token='.$token.'&mode='.$mode.'&lang='.$language.'&gameId='.$params['game_code'];
				}
				break;
			case 'isb': // ISOFTBET
				$mode = $params['extra']['game_mode']==0?"real":"fun";
				$language = $language=="zh-cn"?"CHS":"EN"; // language=[EN|CHS| TH|KR|VI|JA ]
				// MOBILE AND WEB applicable
				$url = $this->gameplay_isb_url.'?op='.$this->gameplay_merchant_id.'&game_code='.$params['game_code'].'&language='.$language.'&ticket='.$token.'&playmode='.$mode;
				if($mode == "fun"){ // need to add currency if fungame
					$url = $url.'&cur=CNY';
				}
				break;
			default:
				$url ='';
				break;
		}

		$this->CI->utils->debug_log('GAMEPLAY queryForwardGame url: ', $url);
		return array(
			'success' => true,
			'url' => $url,
			'iframeName' => "GAMEPLAY",
			'is_redirect' => $this->gameplay_is_redirect,
		);

	}

	private function getTtgGameLauncher($url){
		$s = curl_init();
		curl_setopt($s, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($s,CURLOPT_URL,$url);
	    $result = simplexml_load_string(curl_exec($s));
		curl_close($s);
		$url="";
		if($result->error_code == 0){
			$url=$result->redirect_url;
		}
		return $url;
	}

	const START_PAGE = 1;

	public function syncOriginalGameLogsByGameType($startDate, $endDate, $gameType){
		switch ($gameType) {
			case self::SLOT:
				$this->getGameplaySlotGameLogs($startDate, $endDate);
				break;
			case self::RSLOT:
				$this->getGameplayRSlotGameLogs($startDate, $endDate);
				break;
			case self::TABLE:
				$this->getGameplayTableGameLogs($startDate, $endDate);
				break;
		}
		return array('success' => true);
	}

	public function syncOriginalGameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		if (!$this->gameplay_api_generic_game_history_url) {

			$syncByGameType = $this->getValueFromSyncInfo($token, 'syncByGameType');
			if($syncByGameType){
				$gameType = $this->getValueFromSyncInfo($token, 'gameType');
				return $this->syncOriginalGameLogsByGameType($startDate, $endDate, $gameType);
			}

			if ($this->enabled_api['gameplay']['slot']) {
				$gameplaySlotHistoryFlag = $this->getGameplaySlotGameLogs($startDate, $endDate);
			}

			if ($this->enabled_api['gameplay']['rslot']) {
				sleep(0.5);
				$gameplayRSlotHistoryFlag = $this->getGameplayRSlotGameLogs($startDate, $endDate);
			}

			if ($this->enabled_api['gameplay']['table']) {
				sleep(0.5);
				$gameplayTableHistoryFlag = $this->getGameplayTableGameLogs($startDate, $endDate);
			}

			// ==================== Added Sub-Providers ==================== //

	        if (!empty($this->enabled_api['keno'])) {
	            sleep(0.5);
	            $gameplayKenoHistoryFlag = $this->getGameplayKenoGameLogs($startDate, $endDate);
	        }

	        if (!empty($this->enabled_api['ladder'])) {
	            sleep(0.5);
	            $gameplayLadderHistoryFlag = $this->getGameplayLadderGameLogs($startDate, $endDate);
	        }

	        if (!empty($this->enabled_api['pk10'])) {
	            sleep(0.5);
	            $gameplayLadderHistoryFlag = $this->getGameplayPk10GameLogs($startDate, $endDate);
	        }

	        if (!empty($this->enabled_api['rockpaperscissors'])) {
	            sleep(0.5);
	            $gameplayLadderHistoryFlag = $this->getGameplayRPSGameLogs($startDate, $endDate);
	        }

	        if (!empty($this->enabled_api['thailottery'])) {
	            sleep(0.5);
	            $gameplayLadderHistoryFlag = $this->getGameplayTLGameLogs($startDate, $endDate);
	        }

			if (!empty($this->enabled_api['betsoft'])) {
				sleep(0.5);
				$gameplayBetsoftHistoryFlag = $this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::BETSOFT);
			}

			if (!empty($this->enabled_api['ctxm'])) {
				sleep(0.5);
				$gameplayCtxmHistoryFlag = $this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::CTXM);
			}

			if (!empty($this->enabled_api['isoftbet'])) {
				sleep(0.5);
				$gameplayIsoftbetHistoryFlag = $this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::ISOFTBET);
			}

			if (!empty($this->enabled_api['png'])) {
				sleep(0.5);
				$gameplayPngHistoryFlag = $this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::PNG);
			}

			if (!empty($this->enabled_api['ttg'])) {
				sleep(0.5);
				$gameplayTtgHistoryFlag = $this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::TTG);
			}

			if (!empty($this->enabled_api['sbtech'])) {
				sleep(0.5);
				$gameplaySbtechHistoryFlag = $this->getGameplaySbtechGameLogs($startDate, $endDate);
			}
		} else {
			if ($this->enabled_api['gameplay']['rslot']) {
				sleep(0.5);
				$gameplayGenericHistoryFlag = $this->getGameplayGameLogsByProduct($startDate, $endDate, 'slots');
			}

			if ($this->enabled_api['gameplay']['table']) {
				sleep(0.5);
				$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
				$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
				$gameplayGenericHistoryFlag = $this->getGameplayGameLogsByProduct($startDate, $endDate, 'casino');
			}

			if ($this->enabled_api['keno'] || $this->enabled_api['pk10'] || $this->enabled_api['ladder'] || $this->enabled_api['rockpaperscissors'] || $this->enabled_api['thailottery']) {
				sleep(0.5);
				$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
				$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
				$gameplayGenericHistoryFlag = $this->getGameplayGameLogsByProduct($startDate, $endDate, 'lottery');
                $this->getGameplayGameLogsUnsettled($startDate, $endDate);
			}
		}

		return array('success' => true);
	}

	// new history call
	private function getGameplayGameLogsByProduct($startDate, $endDate, $product) {
		// check if `slots` - adjust call by hour
		// if ($product == 'slots') {
			$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+1 hours', function($startDate, $endDate) use($product){
				$this->gameplay_api_history_call = $this->gameplay_api_generic_game_history_url;
				$page = self::START_PAGE;
				$tryAgain = false;
				$attempt = 0;
				
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGenericGameRecords',
					'product' => $product
				);

				$params = array(
	                "merch_id" => $this->gameplay_merchant_id,
	                "merch_pwd" => $this->gameplay_merchant_pw,
	                "product" => $product,
	                "date_from" => $startDate->format('Y-m-d H:i:s'),
	                "date_to" => $endDate->format('Y-m-d H:i:s'),
	                "page_size" => $product == 'slots' ? self::ITEM_PER_PAGE_RSLOT : self::ITEM_PER_PAGE,
	                "page_num" => $page,
	            );

				$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);
				$this->CI->utils->debug_log("gameplay {$product} generic result ====>", json_encode($rlt));
				sleep($this->common_wait_seconds);
				if($rlt['success']){
					$currentPage = $rlt['page_num'];
					$totalPage = $rlt['total_page'];
					if($currentPage < $totalPage) {
						while($currentPage != $totalPage || ($tryAgain && $attempt < $this->max_call_attempt)) {
							$this->gameplay_api_history_call = $this->gameplay_api_generic_game_history_url;
							sleep($this->common_wait_seconds);

							$params['page_num'] = $currentPage + 1;
							
						    $callApiByPage = $this->callApi(self::API_syncGameRecords, $params, $context);
						    $this->CI->utils->debug_log("gameplay {$product} generic by page result ====>", json_encode($callApiByPage));

						    if($callApiByPage['success']){
						    	$currentPage = $callApiByPage['page_num'];
						    } else{
					    		$currentPage = $totalPage;
						    }
						}
					}
				}
				return true;
			});
		// } else {

		// 	$context = array(
		// 		'callback_obj' => $this,
		// 		'callback_method' => 'processResultForSyncGenericGameRecords',
		// 		'product' => $product
		// 	);

		// 	$page = self::START_PAGE;
		// 	$done = false;
		// 	$success = false;

		// 	while (!$done) {

		// 		$this->gameplay_api_history_call = $this->gameplay_api_generic_game_history_url;

		// 		$params = array(
	    //             "merch_id" => $this->gameplay_merchant_id,
	    //             "merch_pwd" => $this->gameplay_merchant_pw,
	    //             "product" => $product,
	    //             "date_from" => $startDate->format('Y-m-d H:i:s'),
	    //             "date_to" => $endDate->format('Y-m-d H:i:s'),
	    //             "page_size" => self::ITEM_PER_PAGE,
	    //             "page_num" => $page,
	    //         );

		// 		$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

		// 		$done = true;
		// 		if ($rlt && $rlt['success']) {
		// 			$page = isset($rlt['page_num']) ? @$rlt['page_num'] : 1;
		// 			$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 1;
		// 			//next page
		// 			$page += 1;

		// 			$done = $page >= $total_pages;

		// 			$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
		// 		}
		// 		if ($done) {
		// 			$success = true;
		// 		}
		// 	}
		// 	return $success;
		// }
	}

    // get lottery unsettled
    public function getGameplayGameLogsUnsettled($startDate, $endDate, $product = 'lottery') {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGenericGameRecords',
            'product' => $product
        );

        $page = self::START_PAGE;
        $done = false;
        $success = false;

        while (!$done) {
            sleep($this->common_wait_seconds);
            $this->gameplay_api_history_call = $this->gameplay_api_generic_game_history_url;

            $params = array(
                'merch_id' => $this->gameplay_merchant_id,
                'merch_pwd' => $this->gameplay_merchant_pw,
                'product' => $product,
                'date_from' => $startDate->format('Y-m-d H:i:s'),
                'date_to' => $endDate->format('Y-m-d H:i:s'),
                'page_size' => self::ITEM_PER_PAGE,
                'page_num' => $page,
                'openbets' => 1,
            );

            $rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

            $done = true;
            if ($rlt && $rlt['success']) {
                $page = isset($rlt['page_num']) ? @$rlt['page_num'] : 1;
                $total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 1;
                //next page
                $page += 1;

                $done = $page >= $total_pages;

                $this->CI->utils->debug_log("---------------- {$product} Unsettled ----------------", 'page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
            }

            if ($done) {
                $success = true;
            }
        }

        return $success;
    }

	public function processResultForSyncGenericGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$product = $this->getVariableFromContext((array) $params, 'product');

		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));

		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system', 'game_description_model'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		if ($success) {
			$gameRecords = null;

            if (isset($resultArr['items']['item'])) {
				$totalRow = isset($resultArr['items']['@attributes']['total_row']) ? $resultArr['items']['@attributes']['total_row'] : 0;
				$gameRecords = $this->processAttributes($totalRow == 1 ? array($resultArr['items']['item']) : $resultArr['items']['item']);
			}

			$pageNum = 0;
			$totalPage = 0;
			if (isset($resultArr['items'])) {
				$pageNum = @$resultArr['items']['@attributes']['page_num'];
				$totalPage = isset($resultArr['items']['@attributes']['total_page']) ? @$resultArr['items']['@attributes']['total_page'] : 0;
			} else {
				$pageNum = @$resultArr['@attributes']['pageNum'];
				$totalPage = @$resultArr['@attributes']['totalPage'];
			}

			$this->CI->utils->debug_log('GAMEPLAY_API sync', $gameRecords, 'product', $product);

			if (!empty($gameRecords)) {
				if ($product &&  $product == 'slots') {
					$count = 0;
					foreach ($gameRecords as $record) {
						$changeTime = new DateTime($this->gameTimeToServerTime($record['trans_date']));
						$gameCode = $this->CI->game_description_model->getGameCodeByGameName($record['table_id'], GAMEPLAY_API);
						$gameplayGameData = array(
							'user_id' => @$record['user_id'],
							'trans_date' => $changeTime->format('Y-m-d H:i:s'),
							'createdAt' => $changeTime->format('Y-m-d H:i:s'),
							'change_time' => $changeTime->format('Y-m-d H:i:s'),
							'change_type' => @$record['player_hand'],
							'game_name' => @$record['table_id'],
							'bet' => @$record['bet'],
							'ret' => @$record['rebate_amount'],
							'changes' => @$record['winlose'],
							'winlose' => @$record['winlose'],
							'balance' => @$record['balance'],
							'trans_id' => isset($record['trxId']) ? $record['trxId'] : null,
							'operator' => isset($record['operator']) ? $record['operator'] : $this->gameplay_merchant_id,
							'jcon' => @$record['jcon'],
							'jwin' => @$record['jwin'],
							'ver' => @$record['ver'],
							'platform' => @$record['platform'],
							'external_uniqueid' => @$record['bet_id'],
							'response_result_id' => $responseResultId,
							'game_platform' => GAMEPLAY_API,
							'game_code' => @$record['table_id'],
							'round_id' => @$record['round_id'],
							'fround' => @$record['fround'],
							'rebate_amount' => @$record['rebate_amount'],
							'bet_id' => @$record['bet_id'],
							'bundle_id' => @$record['bundle_id'],
							'game_type' => @$record['game_type'],
							'status' => @$record['status'],
							'game_result' => @$record['game_result'],
							'player_hand' => @$record['player_hand'],
							'table_id' => @$record['table_id'],
							'game_id' => @$record['game_id'],
						);

						$isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($gameplayGameData['external_uniqueid']);
		                if ($isExists) {
		                    $this->CI->gameplay_game_logs->updateGameLogs($gameplayGameData);
		                } else {
		                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($gameplayGameData);
		                }
		                $count++;
					}
				} elseif ($product == 'casino') {
					//filter available rows first
					$count = 0;
					foreach ($gameRecords as $record) {
						$changeTime = new DateTime($this->gameTimeToServerTime($record['trans_date']));
						$gameplayGameData = array(
							'trans_date' => $changeTime->format('Y-m-d H:i:s'),
							'game_result' => @$record['game_result'],
							'player_hand' => @$record['player_hand'],
							'platform' => @$record['platform'],
							'game_code' => @$record['game_type'] . $record['table_id'],
							'game_id' => @$record['game_id'],
							'currency' => @$record['currency'],
							'balance' => @$record['balance'],
							'winlose' => @$record['winlose'],
							'bet' => @$record['bet'],
							'game_type' => @$record['game_type'],
							'user_id' => @$record['user_id'],
							'status' => @$record['status'],
							'bet_id' => @$record['bet_id'],
							'bundle_id' => @$record['bundle_id'],
							'table_id' => @$record['table_id'],
							'round_id' => @$record['round_id'],
							'external_uniqueid' => $this->gpiLiveCasinoUid(@$record['bundle_id'], @$record['bet_id']),
							'response_result_id' => $responseResultId,
							'game_platform' => GAMEPLAY_API,
						);

						if (isset($record['lucky_num'])) {
							$gameplayGameData['lucky_num'] = @$record['lucky_num'];
						}

						$isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($gameplayGameData['external_uniqueid']);
		                if ($isExists) {
		                    $this->CI->gameplay_game_logs->updateGameLogs($gameplayGameData);
		                } else {
		                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($gameplayGameData);
		                }
		                $count++;
					}
				} elseif ($product = 'lottery') {
					$count = 0;
					foreach ($gameRecords as $record) {
		                $start_date = new DateTime($this->gameTimeToServerTime($record['trans_date']));
		                $end_date = new DateTime($this->gameTimeToServerTime($record['trans_date']));
		                $changeTime = new DateTime($this->gameTimeToServerTime($record['trans_date']));
		                //$status = ($record['status'] && $record['status'] == '1') ? 'settled' : ''; 
                        $status = '';

                        if (isset($record['status'])) {
                            switch ($record['status']) {
                                case 1:
                                    $status = 'settled';
                                    break;
                                case 2:
                                    $status = 'cancelled';
                                    break;
                                case 3:
                                    $status = 'unsettled';
                                    break;
                                default:
                                    $status = '';
                                    break;
                            }
                        }

		                $areaName = $this->lotteryGameName($record['table_id'],$record['game_id']);
		                $insertRecord = array();
		                $insertRecord = array(
		                    'user_id'               => $record['user_id'], // user_id collumn is used instead memberId
		                    'bet_id'                => $record['bet_id'],
		                    'betNo'                 => $record['bundle_id'],
		                    'areaName'              => $areaName,
		                    'betType'               => $record['player_hand'],
		                    'currency'              => $record['currency'],
		                    'bet'                   => $record['bet'],
		                    'winAmount'             => $record['winlose'] + $record['bet'],
		                    'timeBet'               => $start_date->format('Y-m-d H:i:s'),
		                    'actionTime'            => $end_date->format('Y-m-d H:i:s'),
		                    'keno_status'           => $status,
		                    'external_uniqueid'     => $record['bundle_id'],
		                    'response_result_id'    => $responseResultId,
		                    'game_provider'         => self::KENO,
		                    'game_platform'         => GAMEPLAY_API,
		                    'winlose' => @$record['winlose'],
		                    'bundle_id' => @$record['bundle_id'],
		                    'trans_date' => $changeTime->format('Y-m-d H:i:s'),
		                    'game_type' => @$record['game_type'],
		                    'balance' => @$record['balance'],
		                    'status' => @$record['status'],
		                    'player_hand' => @$record['player_hand'],
		                    'table_id' => @$record['table_id'],
		                    'game_id' => @$record['game_id'],
		                    'platform' => @$record['platform'],
		                    'round_id' => @$record['round_id'],
		                    'game_code' => @$record['game_code'],
		                );

		                $isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($insertRecord['external_uniqueid']);
		                if ($isExists) {
		                    $this->CI->gameplay_game_logs->updateGameLogs($insertRecord);
		                } else {
		                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($insertRecord);
		                }
		                $count++;
		            }
				}
			}

			$result['page_num'] = $pageNum;
			$result['total_page'] = $totalPage;
		} else{
			$this->CI->utils->debug_log("gameplay error resultArr====>", json_encode($resultArr));
			$result['error'] = isset($resultArr[0]) ? $resultArr[0] : null;
		}
		return array($success, $result);
	}

	# Sync per sub provider
	# sudo ./command.sh sync_game_play_api '2019-01-21 00:00:00' '2019-01-21 23:59:59' 'game_play' 'rslots'
	public function syncGamePlayPerSubProvider($token, $subProvider, $gameType=null)
	{
		$games = [
			'game_play' => [
				'slots' => @$this->enabled_api['gameplay']['slot'],
				'rslots' => @$this->enabled_api['gameplay']['rslot'],
				'table' => @$this->enabled_api['gameplay']['table'],
			],
			'keno' => [
				'keno' => @$this->enabled_api['keno'],
				'ladder' => @$this->enabled_api['ladder'],
				'pk10' => @$this->enabled_api['pk10'],
				'rockpaperscisors' => @$this->enabled_api['rockpaperscisors'],
				'thailottery' => @$this->enabled_api['thailottery'],
			],
			'betsoft' => @$this->enabled_api['betsoft'],
			'ctxm' => @$this->enabled_api['ctxm'],
			'isoftbet' => @$this->enabled_api['isoftbet'],
			'png' => @$this->enabled_api['png'],
			'ttg' => @$this->enabled_api['ttg'],
			'sbtech' => @$this->enabled_api['sbtech']
		];

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		if(!isset($games[$subProvider])) {
			$this->CI->utils->debug_log("Not exist subprovider [$subProvider] ", 'startDate', $startDate, 'endDate', $endDate);
			return false;
		} else {
			$game = @$games[$subProvider];
			if(is_array($game)) {
				$game_type = @$game[$gameType];
				if(!$game_type) {
					$this->CI->utils->debug_log("Not exist game type [$gameType] ", 'startDate', $startDate, 'endDate', $endDate);
					return false;
				}
			}
		}

		switch($subProvider) {
			case 'game_play' :
				if($gameType == 'rslots') {
					$this->getGameplayRSlotGameLogs($startDate, $endDate);
				}elseif($gameType == 'slots') {
					$this->getGameplaySlotGameLogs($startDate, $endDate);
				}elseif($gameType == 'table') {
					$this->getGameplayTableGameLogs($startDate, $endDate);
				}
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::SLOTANDTABLE_API);
				break;
			case 'sbtech' :
				$this->getGameplaySbtechGameLogs($startDate, $endDate);
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::SBTECH_API);
				break;
			case 'betsoft' :
				$this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::BETSOFT);
				$this->syncMergeGameplayGameLogs($startDate, $startDate, self::BETSOFT);
				break;
			case 'ctxm' :
				$this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::CTXM);
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::CTXM_API);
				break;
			case 'isoftbet' :
				$this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::ISOFTBET);
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::ISOFTBET);
				break;
			case 'png' :
				$this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::PNG);
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::PNG);
				break;
			case 'ttg' :
				$this->getGameplaySubprovidersGameLogs($startDate, $endDate, self::TTG);
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::TTG);
				break;
			case 'keno' :
				if($gameType =='keno') {
					$this->getGameplayKenoGameLogs($startDate, $endDate);
				}elseif($gameType=='sode') {
					$this->getGameplayKenoGameLogs($startDate, $endDate);
					// $this->getGameplaySodeGameLogs($startDate, $endDate);
				}elseif($gameType=='ladder') {
					$this->getGameplayLadderGameLogs($startDate, $endDate);
				}elseif($gameType=='pk10') {
					$this->getGameplayPk10GameLogs($startDate, $endDate);
				}elseif($gameType=='rockpaperscissors') {
					$this->getGameplayRPSGameLogs($startDate, $endDate);
				}elseif($gameType=='thailottery') {
					$this->getGameplayTLGameLogs($startDate, $endDate);
				}
				$this->syncMergeGameplayGameLogs($startDate, $endDate, self::KENO);
		}
		return array('success' => true);
	}

	private function getGameplaySubprovidersGameLogs($startDate, $endDate,$subProvider){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameplaySubprovidersGameLogs',
		);

		$success = false;

		$page = self::START_PAGE;
		$done = false;

		while (!$done) {
			$this->gameplay_api_history_call = $this->gameplay_others_history;

			$params = array(
				"merch_id" => $this->gameplay_merchant_id,
				"merch_pwd" => $this->gameplay_merchant_pw,
				"date_from" => $startDate->format('Y-m-d H:i:s'),
				"date_to" => $endDate->format('Y-m-d H:i:s'),
				"page_num" => (int)$page,
				"page_size" => self::ITEM_PER_PAGE,
				"game_provider" => $subProvider,
			);

			$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);
			$done = true;

			if ($rlt && $rlt['success']) {
				$page = isset($rlt['page_num'])?$rlt['page_num']:1;
				$done = $rlt['realdata_count'] < self::ITEM_PER_PAGE;
				//next page
				$page ++;

				$this->CI->utils->debug_log('page', $page, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForGameplaySubprovidersGameLogs($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$gameRecords = isset($resultArr['items']['item']) ? $resultArr['items']['item'] : array();
		$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);

		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$count = 0;
		$realdata_count = 0;
		if($success){
			$availableRows = !empty($gameRecords)?$this->CI->gameplay_game_logs->getGameplayerSubproviderAvailableRows($gameRecords):array();

			$realdata_count = count($availableRows);
			foreach ($availableRows as $record) {

				$insetData = array(
					'trans_id' => isset($record['@attributes']['trans_id'])?$record['@attributes']['trans_id']:$record['trans_id'],
					'trans_date' => isset($record['@attributes']['trans_date'])?$this->gameTimeToServerTime($record['@attributes']['trans_date']):$this->gameTimeToServerTime($record['trans_date']),
					'user_id' => isset($record['@attributes']['user_id'])?$record['@attributes']['user_id']:$record['user_id'],
					'game_type' => isset($record['@attributes']['game_type'])?$record['@attributes']['game_type']:$record['game_type'],
					'game_provider' => isset($record['@attributes']['game_provider'])?$record['@attributes']['game_provider']:$record['game_provider'],
					'bet' => isset($record['@attributes']['bet'])?$record['@attributes']['bet']:$record['bet'],
					'winlose' => isset($record['@attributes']['winlose'])?$record['@attributes']['winlose']:$record['winlose'],
					'balance' => isset($record['@attributes']['balance'])?$record['@attributes']['balance']:$record['balance'],
					'jcon' => isset($record['@attributes']['jcon'])?$record['@attributes']['jcon']:$record['jcon'],
					'jwin' => isset($record['@attributes']['jwin'])?$record['@attributes']['jwin']:$record['jwin'],
					'external_uniqueid' => isset($record['@attributes']['trans_id'])?$record['@attributes']['trans_id']:$record['trans_id'],
					'response_result_id' => $responseResultId,
					'game_platform' => GAMEPLAY_API,
				);

				$this->CI->gameplay_game_logs->insertGameplayGameLogs($insetData);
				$count++;
			}

		}

		$result = array(
			'insert_count'=> $count,
			'realdata_count'=> $realdata_count,
			'page_num'=> isset($resultArr['items']['@attributes']['page_num'])?$resultArr['items']['@attributes']['page_num']:'',
			'page_size'=> isset($resultArr['items']['@attributes']['page_size'])?$resultArr['items']['@attributes']['page_size']:''
		);

		return array($success, $result);
	}

	private function getGameplayTableGameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsTable',
		);
		$success = false;

		$page = self::START_PAGE;
		$done = false;

		while (!$done) {

			$this->gameplay_api_history_call = $this->gameplay_api_table_game_history_url;

			$params = array(
				"merch_id" => $this->gameplay_merchant_id,
				"merch_pwd" => $this->gameplay_merchant_pw,
				"date_from" => $startDate->format('Y-m-d H:i:s'),
				"date_to" => $endDate->format('Y-m-d H:i:s'),
				"page_num" => $page,
				"page_size" => self::ITEM_PER_PAGE,
			);

			$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

			$done = true;
			if ($rlt && $rlt['success']) {
				$page = isset($rlt['page_num']) ? @$rlt['page_num'] : 1;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 1;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForSyncGameRecordsTable($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		if ($success) {
			$gameRecords = null;

			if (isset($resultArr['items']['item'])) {
				$totalRow = isset($resultArr['items']['@attributes']['total_row']) ? $resultArr['items']['@attributes']['total_row'] : 0;
				$gameRecords = $this->processAttributes($totalRow == 1 ? array($resultArr['items']['item']) : $resultArr['items']['item']);
			}

			$pageNum = 0;
			$totalPage = 0;
			if (isset($resultArr['items'])) {
				$pageNum = @$resultArr['items']['@attributes']['page_num'];
				$totalPage = isset($resultArr['items']['@attributes']['total_page']) ? @$resultArr['items']['@attributes']['total_page'] : 0;
			}
			if ($gameRecords) {
				if(!empty($gameRecords)) {
					foreach($gameRecords as $index => $record) {
						$gameRecords[$index]['external_uniqueid'] = $this->gpiLiveCasinoUid(@$record['bundle_id'], @$record['bet_id']);
					}
				}
				//filter available rows first
				$availableRows = $this->CI->gameplay_game_logs->getGameplayLiveAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

				if (count($availableRows) == 1) {
					$gameplayGameData = array(
						'trans_date' => $this->gameTimeToServerTime(@$availableRows[0]['trans_date']),
						'game_result' => @$availableRows[0]['game_result'],
						'player_hand' => @$availableRows[0]['player_hand'],
						'platform' => @$availableRows[0]['platform'],
						'game_code' => @$availableRows[0]['game_type'] .
						$availableRows[0]['table_id'],
						'game_id' => @$availableRows[0]['game_id'],
						'currency' => @$availableRows[0]['currency'],
						'balance' => @$availableRows[0]['balance'],
						'winlose' => @$availableRows[0]['winlose'],
						'bet' => @$availableRows[0]['bet'],
						'game_type' => @$availableRows[0]['game_type'],
						'user_id' => @$availableRows[0]['user_id'],
						'status' => @$availableRows[0]['status'],
						'table_id' => @$availableRows[0]['table_id'],
						'bet_id' => @$availableRows[0]['bet_id'],
						'bundle_id' => @$availableRows[0]['bundle_id'],
						'external_uniqueid' => $this->gpiLiveCasinoUid(@$availableRows['bundle_id'], @$availableRows['bet_id']),
						'response_result_id' => $responseResultId,
						'game_platform' => GAMEPLAY_API,
					);
					if (isset($availableRows[0]['lucky_num'])) {
						$gameplayGameData['lucky_num'] = @$availableRows[0]['lucky_num'];
					}
					$this->CI->gameplay_game_logs->insertGameplayGameLogs($gameplayGameData);
				} else {
					foreach ($availableRows as $record) {
						$gameplayGameData = array(
							'trans_date' => $this->gameTimeToServerTime(@$record['trans_date']),
							'game_result' => @$record['game_result'],
							'player_hand' => @$record['player_hand'],
							'platform' => @$record['platform'],
							'game_code' => @$record['game_type'] .
							$record['table_id'],
							'game_id' => @$record['game_id'],
							'currency' => @$record['currency'],
							'balance' => @$record['balance'],
							'winlose' => @$record['winlose'],
							'bet' => @$record['bet'],
							'game_type' => @$record['game_type'],
							'user_id' => @$record['user_id'],
							'status' => @$record['status'],
							'table_id' => @$record['table_id'],
							'bet_id' => @$record['bet_id'],
							'bundle_id' => @$record['bundle_id'],
							'external_uniqueid' => $this->gpiLiveCasinoUid(@$record['bundle_id'], @$record['bet_id']),
							'response_result_id' => $responseResultId,
							'game_platform' => GAMEPLAY_API,
						);

						if (isset($record['lucky_num'])) {
							$gameplayGameData['lucky_num'] = @$record['lucky_num'];
						}
						$this->CI->gameplay_game_logs->insertGameplayGameLogs($gameplayGameData);
					}
				}
			}

			$result['page_num'] = $pageNum;
			$result['total_page'] = $totalPage;
		}
		return array($success, $result);
	}

	public function gpiLiveCasinoUid($bundle_id, $bet_id) {
		return $bundle_id.'-'.$bet_id;
	}

	private function getGameplaySlotGameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsSlot',
		);
		$success = false;

		$this->gameplay_api_history_call = $this->gameplay_api_slot_game_history_url;
		$page = self::START_PAGE;
		$done = false;
		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords,
				array(
					"operator" => $this->gameplay_merchant_id,
					"pwd" => $this->gameplay_history_pw,
					"startDate" => $startDate->format('d-m-Y,His'),
					"endDate" => $endDate->format('d-m-Y,His'),
					"pageNum" => $page,
					"pageSize" => self::ITEM_PER_PAGE,
				),
				$context);

			$done = true;
			if ($rlt) {
				$page = isset($rlt['page_num']) ? @$rlt['page_num'] : 0;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processAttributes($history) {
		$gameRecords = null;
		foreach ($history as $item) {
			if (isset($item['@attributes'])) {
				$attrs = $item['@attributes'];
				$gameRecords[] = $attrs;
			} else if (isset($item[self::UNIQUE_ID_FIELD])) {
				$gameRecords[] = $item;
			} else {
				//drop or program problem
				$this->CI->utils->error_log('lost uniqid', $item);
			}
		}

		return $gameRecords;
	}

	public function processResultForSyncGameRecordsSlot($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system', 'game_description_model'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		if ($success) {
			$gameRecords = null;

			if (isset($resultArr['history'])) {
				$totalRow = isset($resultArr['@attributes']['currentRows']) ? $resultArr['@attributes']['currentRows'] : 0;
				$gameRecords = $this->processAttributes($totalRow == 1 ? array($resultArr['history']) : $resultArr['history']);
			}

			$pageNum = 0;
			$totalPage = 0;
			if (isset($resultArr['items'])) {
				$pageNum = @$resultArr['items']['@attributes']['page_num'];
				$totalPage = isset($resultArr['items']['@attributes']['total_page']) ? @$resultArr['items']['@attributes']['total_page'] : 0;
			} else {
				$pageNum = @$resultArr['@attributes']['pageNum'];
				$totalPage = @$resultArr['@attributes']['totalPage'];
			}

			if (!empty($gameRecords)) {
				//filter available rows first
				$availableRows = $this->CI->gameplay_game_logs->getSlotAvailableRows($gameRecords);

				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));
				if (count($availableRows) == 1) {
					$changeTime = new DateTime($this->gameTimeToServerTime($availableRows[0]['changeTime']));
					$gameplayGameData = array(
						'operation_code' => @$availableRows[0]['operationCode'],
						'user_id' => @$availableRows[0]['userId'],
						'trans_date' => $changeTime->format('Y-m-d H:i:s'),
						'createdAt' => $changeTime->format('Y-m-d H:i:s'),
						'change_time' => $changeTime->format('Y-m-d H:i:s'),
						'change_type' => @$availableRows[0]['changeType'],
						'game_name' => @$availableRows[0]['gameName'],
						'bet' => @$availableRows[0]['bet'],
						'ret' => @$availableRows[0]['ret'],
						'changes' => @$availableRows[0]['changes'],
						'winlose' => @$availableRows[0]['changes'],
						'balance' => @$availableRows[0]['endBalance'],
						'trans_id' => isset($availableRows[0]['trxId']) ? $availableRows[0]['trxId'] : null,
						'operator' => @$availableRows[0]['operator'],
						'jcon' => @$availableRows[0]['jcon'],
						'jwin' => @$availableRows[0]['jwin'],
						'ver' => @$availableRows[0]['ver'],
						'platform' => @$availableRows[0]['platform'],
						'external_uniqueid' => @$availableRows[0][self::UNIQUE_ID_FIELD],
						'response_result_id' => $responseResultId,
						'game_platform' => GAMEPLAY_API,
						'game_code' => @$availableRows[0]['gameName'],
					);

					$this->CI->gameplay_game_logs->insertGameplayGameLogs($gameplayGameData);
				} else {
					foreach ($availableRows as $record) {
						$changeTime = new DateTime($this->gameTimeToServerTime($record['changeTime']));
						$gameCode = $this->CI->game_description_model->getGameCodeByGameName($record['gameName'], GAMEPLAY_API);
						$gameplayGameData = array(
							'operation_code' => @$record['operationCode'],
							'user_id' => @$record['userId'],
							'trans_date' => $changeTime->format('Y-m-d H:i:s'),
							'createdAt' => $changeTime->format('Y-m-d H:i:s'),
							'change_time' => $changeTime->format('Y-m-d H:i:s'),
							'change_type' => @$record['changeType'],
							'game_name' => @$record['gameName'],
							'bet' => @$record['bet'],
							'ret' => @$record['ret'],
							'changes' => @$record['changes'],
							'winlose' => @$record['changes'],
							'balance' => @$record['endBalance'],
							'trans_id' => isset($record['trxId']) ? $record['trxId'] : null,
							'operator' => @$record['operator'],
							'jcon' => @$record['jcon'],
							'jwin' => @$record['jwin'],
							'ver' => @$record['ver'],
							'platform' => @$record['platform'],
							'external_uniqueid' => @$record[self::UNIQUE_ID_FIELD],
							'response_result_id' => $responseResultId,
							'game_platform' => GAMEPLAY_API,
							'game_code' => @$record['gameName'],
						);

						$this->CI->gameplay_game_logs->insertGameplayGameLogs($gameplayGameData);
					}
				}

			}

			$result['page_num'] = $pageNum;
			$result['total_page'] = $totalPage;
		} else{
			$this->CI->utils->debug_log("gameplay error resultArr====>", json_encode($resultArr));
			$result['error'] = isset($resultArr[0]) ? $resultArr[0] : null;
		}
		return array($success, $result);
	}

	private function getGameplayRSlotGameLogs($startDate, $endDate) {
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+1 hours', function($startDate, $endDate) {
			$this->gameplay_api_history_call = $this->gameplay_api_rslot_game_history_url;

			$page = self::START_PAGE;
			$tryAgain = false;
			$attempt = 0;

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecordsSlot',
			);
			
			$params = array(
				"opCode" => $this->gameplay_merchant_id,
				"s" => $startDate->format('d-m-Y,His'),
				"e" => $endDate->format('d-m-Y,His'),
				"pageNum" => $page,
				"pageSize" => self::ITEM_PER_PAGE,
				"pwd" => $this->gameplay_api_rslot_game_history_pwd,
				"type" => 2,
				"slots" => 1
			);

			$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);
			$this->CI->utils->debug_log("gameplay rslots result ====>", json_encode($rlt));

			if($rlt['success']){
				$currentPage = $rlt['page_num'];
				$totalPage = $rlt['total_page'];
				while($currentPage != $totalPage || ($tryAgain && $attempt < $this->max_call_attempt)) {
					$this->gameplay_api_history_call = $this->gameplay_api_rslot_game_history_url;
					sleep($this->common_wait_seconds);

					$params['pageNum'] = $currentPage + 1;
					
				    $callApiByPage = $this->callApi(self::API_syncGameRecords, $params, $context);
				    $this->CI->utils->debug_log("gameplay rslots by page result ====>", json_encode($callApiByPage));

				    if($callApiByPage['success']){
				    	$currentPage = $callApiByPage['page_num'];
				    } else{
				    	if(isset($rlt['error']) && $rlt['error'] == self::ERROR_REQUEST_TOO_OFTEN) {
				    		sleep($this->common_wait_seconds);
							$tryAgain = true;
							$attempt++;
						} else {
				    		$currentPage = $totalPage;
						}
				    }
				}
			}
			return true;
			// echo "<pre>";
			// print_r($rlt);exit();


			// $success = false;

			// $this->gameplay_api_history_call = $this->gameplay_api_rslot_game_history_url;
			// $page = self::START_PAGE;
			// $done = false;

			// $params = array(
			// 	"opCode" => $this->gameplay_merchant_id,
			// 	"s" => $startDate->format('d-m-Y,His'),
			// 	"e" => $endDate->format('d-m-Y,His'),
			// 	"pageNum" => $page,
			// 	"pageSize" => self::ITEM_PER_PAGE,
			// 	"pwd" => $this->gameplay_api_rslot_game_history_pwd,
			// 	"type" => 2,
			// 	"slots" => 1
			// );
			// echo "<pre>";
			// print_r($params);
			// while (!$done) {
			// 	$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);
			// 	$done = true;
			// 	// echo "<pre>";
			// 	// print_r($rlt);exit();
			// 	if ($rlt) {
			// 		if(!$rlt['success']){
			// 			$done = false;
			// 			if($rlt['error'] == self::ERROR_REQUEST_TOO_OFTEN){
			// 				sleep($this->common_wait_seconds);
			// 			}
			// 			echo "echooooo page == ".$page;
			// 			echo "<pre>";
			// 			print_r($rlt);
			// 		} else {
			// 			echo "<pre>";
			// 			print_r($rlt);
			// 			$page = isset($rlt['page_num']) ? @$rlt['page_num'] : 0;
			// 			$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
			// 			$done = $page >= $total_pages;

			// 			//next page
			// 			$page = $page + 1;


			// 			$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			// 		}	
			// 	}
			// 	if ($done) {
			// 		$success = true;
			// 	}
			// 	sleep(15);
			// }
			// return $success;
		});
	}

	private function getGameplaySbtechGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecordsSbtech',
        );
        $success = false;

        $this->gameplay_api_history_call = $this->gameplay_api_sbtech_game_history_url;
        $page = self::START_PAGE;
        $done = false;
        while (!$done) {
            $rlt = $this->callApi(self::API_syncGameRecords,
                array(
                    "merch_id" => $this->gameplay_merchant_id,
                    "merch_pwd" => $this->gameplay_merchant_pw,
                    "date_from" => $startDate->format('Y-m-d H:i:s'),
                    "date_to" => $endDate->format('Y-m-d H:i:s'),
                    "page_size" => self::ITEM_PER_PAGE,
                    "page_num" => $page,
                ),
                $context);

            $done = true;
            if ($rlt && $rlt['success']) {
                $page = isset($rlt['page_num']) ? @$rlt['page_num'] : 0;
                $total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
                //next page
                $page += 1;

                $done = $page >= $total_pages;

                $this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
            }
            if ($done) {
                $success = true;
            }
        }
        return $success;
    }

    public function processResultForSyncGameRecordsSbtech($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $this->CI->utils->debug_log("test resultArr====>", count($resultArr));

        // load models
        $this->CI->load->model(array('gameplay_game_logs', 'external_system'));
        $result = array();
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        if ($success) {
            $gameRecords = null;
            if (isset($resultArr['items']['betsfeed'])) {
                $gameRecords = $resultArr['items']['betsfeed'];
            }

            $pageNum = 0;
            $totalPage = 0;
            if (isset($resultArr['items'])) {
                $pageNum = @$resultArr['items']['@attributes']['page_num'];
                $totalPage = isset($resultArr['items']['@attributes']['total_page']) ? @$resultArr['items']['@attributes']['total_page'] : 0;
                $totalRow = @$resultArr['items']['@attributes']['total_row'];
            } else {
                $pageNum = @$resultArr['@attributes']['pageNum'];
                $totalPage = @$resultArr['@attributes']['totalPage'];
            }
            if ($gameRecords) {
                if ($totalRow == 1) {
                    $availableRows = $this->CI->gameplay_game_logs->getSbtechAvailableRows($gameRecords, true);
                    $this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));
                    if (!empty($availableRows)) {
                        $sbtechGameData = array(
                            'rowId  ' => $availableRows['rowId'],
                            'agentId' => $availableRows['agentId'],
                            'customerId' => $availableRows['customerId'],
                            'merchantCustomerId' => $availableRows['merchantCustomerId'],
                            'betId' => $availableRows['betId'],
                            'betTypeId' => $availableRows['betTypeId'],
                            'betTypeName' => $availableRows['betTypeName'],
                            'lineId' => !empty($availableRows['lineId']) ? $availableRows['lineId'] : NULL,
                            'lineTypeId' => !empty($availableRows['lineTypeId']) ? $availableRows['lineTypeId'] : NULL,
                            'lineTypeName' => !empty($availableRows['lineTypeName']) ? $availableRows['lineTypeName'] : NULL,
                            'rowTypeId' => !empty($availableRows['rowTypeId']) ? $availableRows['rowTypeId'] : NULL,
                            'branchId' => !empty($availableRows['branchId']) ? $availableRows['branchId'] : NULL,
                            'branchName' => !empty($availableRows['branchName']) ? $availableRows['branchName'] : NULL,
                            'leagueId' => !empty($availableRows['leagueId']) ? $availableRows['leagueId'] : NULL,
                            'leagueName' => !empty($availableRows['leagueName']) ? $availableRows['leagueName'] : NULL,
                            'creationDate' => !empty($availableRows['creationDate']) ? $this->gameTimeToServerTime($availableRows['creationDate']) : NULL,
                            'homeTeam' => !empty($availableRows['homeTeam']) ? $availableRows['homeTeam'] : NULL,
                            'awayTeam' => !empty($availableRows['awayTeam']) ? $availableRows['awayTeam'] : NULL,
                            'stake' => !empty($availableRows['stake']) ? $availableRows['stake'] : NULL,
                            'odds' => !empty($availableRows['odds']) ? $availableRows['odds'] : NULL,
                            'points' => !empty($availableRows['points']) ? $availableRows['points'] : NULL,
                            'score' => !empty($availableRows['score']) ? $availableRows['score'] : NULL,
                            'status' => !empty($availableRows['status']) ? $availableRows['status'] : NULL,
                            'yourBet' => !empty($availableRows['yourBet']) ? $availableRows['yourBet'] : NULL,
                            'isForEvent' => !empty($availableRows['isForEvent']) ? $availableRows['isForEvent'] : NULL,
                            'eventTypeId' => !empty($availableRows['eventTypeId']) ? $availableRows['eventTypeId'] : NULL,
                            'eventTypeName' => !empty($availableRows['eventTypeName']) ? $availableRows['eventTypeName'] : NULL,
                            'orderId' => !empty($availableRows['orderId']) ? $availableRows['orderId'] : NULL,
                            'updateDate' => !empty($availableRows['updateDate']) ? $this->gameTimeToServerTime($availableRows['updateDate']) : NULL,
                            'pl' => !empty($availableRows['pl']) ? $availableRows['pl'] : NULL,
                            'teamMappingId' => !empty($availableRows['teamMappingId']) ? $availableRows['teamMappingId'] : NULL,
                            'liveScore1' => !empty($availableRows['liveScore1']) ? $availableRows['liveScore1'] : NULL,
                            'liveScore2' => !empty($availableRows['liveScore2']) ? $availableRows['liveScore2'] : NULL,
                            'eventDate' => !empty($availableRows['eventDate']) ? $availableRows['eventDate'] : NULL,
                            'masterEventId' => !empty($availableRows['masterEventId']) ? $availableRows['masterEventId'] : NULL,
                            'commonStatusId' => !empty($availableRows['commonStatusId']) ? $availableRows['commonStatusId'] : NULL,
                            'webProviderId' => !empty($availableRows['webProviderId']) ? $availableRows['webProviderId'] : NULL,
                            'webProviderName' => !empty($availableRows['webProviderName']) ? $availableRows['webProviderName'] : NULL,
                            'bonusAmount' => !empty($availableRows['bonusAmount']) ? $availableRows['bonusAmount'] : NULL,
                            'domainId' => !empty($availableRows['domainId']) ? $availableRows['domainId'] : NULL,
                            'external_uniqueid' => !empty($availableRows['rowId']) ? $availableRows['rowId'] : NULL,
                            'response_result_id' => $responseResultId,
                        );
                        $this->CI->gameplay_game_logs->insertSbtechGameLogs($sbtechGameData);
                    }

                } else {
                    $availableRows = $this->CI->gameplay_game_logs->getSbtechAvailableRows($gameRecords);
                    $this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));
                    foreach ($availableRows as $record) {
                        $sbtechGameData = array(
                            'rowId  ' => $record['rowId'],
                            'agentId' => $record['agentId'],
                            'customerId' => $record['customerId'],
                            'merchantCustomerId' => $record['merchantCustomerId'],
                            'betId' => $record['betId'],
                            'betTypeId' => $record['betTypeId'],
                            'betTypeName' => $record['betTypeName'],
                            'lineId' => !empty($record['lineId']) ? $record['lineId'] : NULL,
                            'lineTypeId' => !empty($record['lineTypeId']) ? $record['lineTypeId'] : NULL,
                            'lineTypeName' => !empty($record['lineTypeName']) ? $record['lineTypeName'] : NULL,
                            'rowTypeId' => !empty($record['rowTypeId']) ? $record['rowTypeId'] : NULL,
                            'branchId' => !empty($record['branchId']) ? $record['branchId'] : NULL,
                            'branchName' => !empty($record['branchName']) ? $record['branchName'] : NULL,
                            'leagueId' => !empty($record['leagueId']) ? $record['leagueId'] : NULL,
                            'leagueName' => !empty($record['leagueName']) ? $record['leagueName'] : NULL,
                            'creationDate' => !empty($record['creationDate']) ? $this->gameTimeToServerTime($record['creationDate']) : NULL,
                            'homeTeam' => !empty($record['homeTeam']) ? $record['homeTeam'] : NULL,
                            'awayTeam' => !empty($record['awayTeam']) ? $record['awayTeam'] : NULL,
                            'stake' => !empty($record['stake']) ? $record['stake'] : NULL,
                            'odds' => !empty($record['odds']) ? $record['odds'] : NULL,
                            'points' => !empty($record['points']) ? $record['points'] : NULL,
                            'score' => !empty($record['score']) ? $record['score'] : NULL,
                            'status' => !empty($record['status']) ? $record['status'] : NULL,
                            'yourBet' => !empty($record['yourBet']) ? $record['yourBet'] : NULL,
                            'isForEvent' => !empty($record['isForEvent']) ? $record['isForEvent'] : NULL,
                            'eventTypeId' => !empty($record['eventTypeId']) ? $record['eventTypeId'] : NULL,
                            'eventTypeName' => !empty($record['eventTypeName']) ? $record['eventTypeName'] : NULL,
                            'orderId' => !empty($record['orderId']) ? $record['orderId'] : NULL,
                            'updateDate' => !empty($record['updateDate']) ? $this->gameTimeToServerTime($record['updateDate']) : NULL,
                            'pl' => !empty($record['pl']) ? $record['pl'] : NULL,
                            'teamMappingId' => !empty($record['teamMappingId']) ? $record['teamMappingId'] : NULL,
                            'liveScore1' => !empty($record['liveScore1']) ? $record['liveScore1'] : NULL,
                            'liveScore2' => !empty($record['liveScore2']) ? $record['liveScore2'] : NULL,
                            'eventDate' => !empty($record['eventDate']) ? $record['eventDate'] : NULL,
                            'masterEventId' => !empty($record['masterEventId']) ? $record['masterEventId'] : NULL,
                            'commonStatusId' => !empty($record['commonStatusId']) ? $record['commonStatusId'] : NULL,
                            'webProviderId' => !empty($record['webProviderId']) ? $record['webProviderId'] : NULL,
                            'webProviderName' => !empty($record['webProviderName']) ? $record['webProviderName'] : NULL,
                            'bonusAmount' => !empty($record['bonusAmount']) ? $record['bonusAmount'] : NULL,
                            'domainId' => !empty($record['domainId']) ? $record['domainId'] : NULL,
                            'external_uniqueid' => !empty($record['rowId']) ? $record['rowId'] : NULL,
                            'response_result_id' => $responseResultId,
                        );
                        $this->CI->gameplay_game_logs->insertSbtechGameLogs($sbtechGameData);
                    }
                }

            }

            $result['page_num'] = $pageNum;
            $result['total_page'] = $totalPage;
        }
        return array($success, $result);
    }

    private function getGameplayKenoGameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsKeno',
		);
		$success = false;

        $endDate = date_sub($endDate,date_interval_create_from_date_string("-60 minutes"));

		$this->gameplay_api_history_call = $this->gameplay_api_keno_game_history_url;
        $this->gameplay_current_subprovider = "keno";
		$page = self::START_PAGE;
		$done = false;

        $params = array(
            "vendorName" => $this->gameplay_merchant_id,
            "game" => "keno",
            "sDate" => $startDate->format('Y-m-d H:i:s'),
            "eDate" => $endDate->format('Y-m-d H:i:s'),
            "status" => "All",
            "password" => $this->gameplay_merchant_pw,
            "pageSize" => self::ITEM_PER_PAGE,
            "pageNumber" => $page
        );

		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);

			$done = true;
			if ($rlt) {
				$page = isset($rlt['pageNumber']) ? @$rlt['pageNumber'] : 0;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForSyncGameRecordsKeno($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode(json_encode($resultJson), true);
        $gameRecords = $resultArr;

		$this->CI->utils->debug_log("test resultArr====>", $resultArr);
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $realdata_count = 0;
        if($success){

            foreach ($gameRecords as $record) {
                $start_date = new DateTime($this->gameTimeToServerTime($record['timeBet']));
                $end_date = new DateTime($this->gameTimeToServerTime($record['actionTime']));
                $insertRecord = array();
                $insertRecord = array(
                    'user_id'               => $record['memberId'], // user_id collumn is used instead memberId
                    'memberType'            => $record['memberType'],
                    'bet_id'                => $record['betId'],
                    'betNo'                 => $record['betNo'],
                    'drawId'                => $record['drawId'],
                    'drawNo'                => $record['drawNo'],
                    'areaName'              => $record['areaName'],
                    'betType'               => $record['betType'],
                    'betContent'            => $record['betContent'],
                    'currency'              => $record['currency'],
                    'bet'                   => $record['betAmount'],
                    'odds'                  => $record['odds'],
                    'isWin'                 => $record['isWin'],
                    'winAmount'             => $record['winAmount'],
                    'timeBet'               => $start_date->format('Y-m-d H:i:s'),
                    'actionTime'            => $end_date->format('Y-m-d H:i:s'),
                    'keno_status'           => $record['status'],
                    'OS'                    => $record['OS'],
                    'external_uniqueid'     => $record['betNo'],
                    'response_result_id'    => $responseResultId,
                    'game_provider'         => self::KENO,
                    'game_platform'         => GAMEPLAY_API,
                );

                $isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($insertRecord['external_uniqueid']);
                if ($isExists) {
                    $this->CI->gameplay_game_logs->updateGameLogs($insertRecord);
                } else {
                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($insertRecord);
                }
                $count++;
            }

        }

        $result = array(
            'insert_count'=> $count,
            'realdata_count'=> $realdata_count,
            'page_num'=> isset($resultArr['items']['@attributes']['page_num'])?$resultArr['items']['@attributes']['page_num']:'',
            'page_size'=> isset($resultArr['items']['@attributes']['page_size'])?$resultArr['items']['@attributes']['page_size']:''
        );

        return array($success, $result);
	}

	private function getGameplayLadderGameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsLadder',
		);
		$success = false;

        $endDate = date_sub($endDate,date_interval_create_from_date_string("-60 minutes"));

		$this->gameplay_api_history_call = $this->gameplay_api_keno_game_history_url;
        $this->gameplay_current_subprovider = "keno";
		$page = self::START_PAGE;
		$done = false;

        $params = array(
            "vendorName" => $this->gameplay_merchant_id,
            "game" => "ladder",
            "sDate" => $startDate->format('Y-m-d H:i:s'),
            "eDate" => $endDate->format('Y-m-d H:i:s'),
            "status" => "All",
            "password" => $this->gameplay_merchant_pw,
            "pageSize" => self::ITEM_PER_PAGE,
            "pageNumber" => $page
        );

		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);

			$done = true;
			if ($rlt) {
				$page = isset($rlt['pageNumber']) ? @$rlt['pageNumber'] : 0;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForSyncGameRecordsLadder($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode(json_encode($resultJson), true);
        $gameRecords = $resultArr;

		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $realdata_count = 0;
        if($success){

            foreach ($gameRecords as $record) {
                $start_date = new DateTime($this->gameTimeToServerTime($record['timeBet']));
                $end_date = new DateTime($this->gameTimeToServerTime($record['actionTime']));
                $insertRecord = array();
                $insertRecord = array(
                    'user_id'               => $record['memberId'], // user_id collumn is used instead memberId
                    'memberType'            => $record['memberType'],
                    'bet_id'                => $record['betId'],
                    'betNo'                 => $record['betNo'],
                    'drawId'                => $record['drawId'],
                    'drawNo'                => $record['drawNo'],
                    'areaName'              => $record['areaName']."_L",
                    'betType'               => $record['betType'],
                    'betContent'            => $record['betContent'],
                    'currency'              => $record['currency'],
                    'bet'                   => $record['betAmount'],
                    'odds'                  => $record['odds'],
                    'isWin'                 => $record['isWin'],
                    'winAmount'             => $record['winAmount'],
                    'timeBet'               => $start_date->format('Y-m-d H:i:s'),
                    'actionTime'            => $end_date->format('Y-m-d H:i:s'),
                    'keno_status'           => $record['status'],
                    'OS'                    => $record['OS'],
                    'external_uniqueid'     => $record['betNo'],
                    'response_result_id'    => $responseResultId,
                    'game_provider'         => self::KENO,
                    'game_platform'         => GAMEPLAY_API,
                );

                $isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($insertRecord['external_uniqueid']);
                if ($isExists) {
                    $this->CI->gameplay_game_logs->updateGameLogs($insertRecord);
                } else {
                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($insertRecord);
                }
                $count++;
            }
        }

        $result = array(
            'insert_count'=> $count,
            'realdata_count'=> $realdata_count,
            'page_num'=> isset($resultArr['items']['@attributes']['page_num'])?$resultArr['items']['@attributes']['page_num']:'',
            'page_size'=> isset($resultArr['items']['@attributes']['page_size'])?$resultArr['items']['@attributes']['page_size']:''
        );

        return array($success, $result);
	}

	private function getGameplayPk10GameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsPk10',
		);
		$success = false;

        $endDate = date_sub($endDate,date_interval_create_from_date_string("-60 minutes"));

		$this->gameplay_api_history_call = $this->gameplay_api_keno_game_history_url;
        $this->gameplay_current_subprovider = "keno";
		$page = self::START_PAGE;
		$done = false;

        $params = array(
            "vendorName" => $this->gameplay_merchant_id,
            "game" => "pk10",
            "sDate" => $startDate->format('Y-m-d H:i:s'),
            "eDate" => $endDate->format('Y-m-d H:i:s'),
            "status" => "All",
            "password" => $this->gameplay_merchant_pw,
            "pageSize" => self::ITEM_PER_PAGE,
            "pageNumber" => $page
        );

		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);

			$done = true;
			if ($rlt) {
				$page = isset($rlt['pageNumber']) ? @$rlt['pageNumber'] : 0;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForSyncGameRecordsPk10($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode(json_encode($resultJson), true);
        $gameRecords = $resultArr;

		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $realdata_count = 0;
        if($success){

            foreach ($gameRecords as $record) {
                $start_date = new DateTime($this->gameTimeToServerTime($record['timeBet']));
                $end_date = new DateTime($this->gameTimeToServerTime($record['actionTime']));
                $insertRecord = array();
                $insertRecord = array(
                    'user_id'               => $record['memberId'], // user_id collumn is used instead memberId
                    'memberType'            => $record['memberType'],
                    'bet_id'                => $record['betId'],
                    'betNo'                 => $record['betNo'],
                    'drawId'                => $record['drawId'],
                    'drawNo'                => $record['drawNo'],
                    'areaName'              => $record['areaName']."_P",
                    'betType'               => $record['betType'],
                    'betContent'            => $record['betContent'],
                    'currency'              => $record['currency'],
                    'bet'                   => $record['betAmount'],
                    'odds'                  => $record['odds'],
                    'isWin'                 => $record['isWin'],
                    'winAmount'             => $record['winAmount'],
                    'timeBet'               => $start_date->format('Y-m-d H:i:s'),
                    'actionTime'            => $end_date->format('Y-m-d H:i:s'),
                    'keno_status'           => $record['status'],
                    'OS'                    => $record['OS'],
                    'external_uniqueid'     => $record['betNo'],
                    'response_result_id'    => $responseResultId,
                    'game_provider'         => self::KENO,
                    'game_platform'         => GAMEPLAY_API,
                );

                $isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($insertRecord['external_uniqueid']);
                if ($isExists) {
                    $this->CI->gameplay_game_logs->updateGameLogs($insertRecord);
                } else {
                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($insertRecord);
                }
                $count++;
            }
        }

        $result = array(
            'insert_count'=> $count,
            'realdata_count'=> $realdata_count,
            'page_num'=> isset($resultArr['items']['@attributes']['page_num'])?$resultArr['items']['@attributes']['page_num']:'',
            'page_size'=> isset($resultArr['items']['@attributes']['page_size'])?$resultArr['items']['@attributes']['page_size']:''
        );

        return array($success, $result);
	}

	private function getGameplayTLGameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsTL',
		);
		$success = false;

        $endDate = date_sub($endDate,date_interval_create_from_date_string("-60 minutes"));

		$this->gameplay_api_history_call = $this->gameplay_api_keno_game_history_url;
        $this->gameplay_current_subprovider = "keno";
		$page = self::START_PAGE;
		$done = false;

        $params = array(
            "vendorName" => $this->gameplay_merchant_id,
            "game" => "thailottery",
            "sDate" => $startDate->format('Y-m-d H:i:s'),
            "eDate" => $endDate->format('Y-m-d H:i:s'),
            "status" => "All",
            "password" => $this->gameplay_merchant_pw,
            "pageSize" => self::ITEM_PER_PAGE,
            "pageNumber" => $page
        );

		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);

			$done = true;
			if ($rlt) {
				$page = isset($rlt['pageNumber']) ? @$rlt['pageNumber'] : 0;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForSyncGameRecordsTL($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode(json_encode($resultJson), true);
        $gameRecords = $resultArr;

		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $realdata_count = 0;

        if($success){
            foreach ($gameRecords as $record) {
                $start_date = new DateTime($this->gameTimeToServerTime($record['timeBet']));
                $end_date = new DateTime($this->gameTimeToServerTime($record['actionTime']));
                $insertRecord = array(
                    'user_id'               => $record['memberId'], // user_id collumn is used instead memberId
                    'memberType'            => $record['memberType'],
                    'bet_id'                => $record['betId'],
                    'betNo'                 => $record['betNo'],
                    'drawId'                => $record['drawId'],
                    'drawNo'                => $record['drawNo'],
                    'areaName'              => $record['areaName']."_R",
                    'betType'               => $record['betType'],
                    'betContent'            => $record['betContent'],
                    'currency'              => $record['currency'],
                    'bet'                   => $record['betAmount'],
                    'odds'                  => $record['odds'],
                    'isWin'                 => $record['isWin'],
                    'winAmount'             => $record['winAmount'],
                    'timeBet'               => $start_date->format('Y-m-d H:i:s'),
                    'actionTime'            => $end_date->format('Y-m-d H:i:s'),
                    'keno_status'           => $record['status'],
                    'OS'                    => $record['OS'],
                    'external_uniqueid'     => $record['betNo'],
                    'response_result_id'    => $responseResultId,
                    'game_provider'         => self::KENO,
                    'game_platform'         => GAMEPLAY_API,
                );

                $isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($insertRecord['external_uniqueid']);
                if ($isExists) {
                    $this->CI->gameplay_game_logs->updateGameLogs($insertRecord);
                } else {
					$this->CI->gameplay_game_logs->insertGameplayGameLogs($insertRecord);
                }
                $count++;
            }
        }

        $result = array(
            'insert_count'=> $count,
            'realdata_count'=> $realdata_count,
            'page_num'=> isset($resultArr['items']['@attributes']['page_num'])?$resultArr['items']['@attributes']['page_num']:'',
            'page_size'=> isset($resultArr['items']['@attributes']['page_size'])?$resultArr['items']['@attributes']['page_size']:''
        );

        return array($success, $result);
	}

	private function getGameplayRPSGameLogs($startDate, $endDate) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecordsRPS',
		);
		$success = false;

        $endDate = date_sub($endDate,date_interval_create_from_date_string("-60 minutes"));

		$this->gameplay_api_history_call = $this->gameplay_api_keno_game_history_url;
        $this->gameplay_current_subprovider = "keno";
		$page = self::START_PAGE;
		$done = false;

        $params = array(
            "vendorName" => $this->gameplay_merchant_id,
            "game" => "rockpaperscissors",
            "sDate" => $startDate->format('Y-m-d H:i:s'),
            "eDate" => $endDate->format('Y-m-d H:i:s'),
            "status" => "All",
            "password" => $this->gameplay_merchant_pw,
            "pageSize" => self::ITEM_PER_PAGE,
            "pageNumber" => $page
        );

		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords,$params,$context);

			$done = true;
			if ($rlt) {
				$page = isset($rlt['pageNumber']) ? @$rlt['pageNumber'] : 0;
				$total_pages = isset($rlt['total_page']) ? @$rlt['total_page'] : 0;
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
	}

	public function processResultForSyncGameRecordsRPS($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode(json_encode($resultJson), true);
        $gameRecords = $resultArr;

		$this->CI->utils->debug_log("test resultArr====>", count($resultArr));
		// load models
		$this->CI->load->model(array('gameplay_game_logs', 'external_system'));
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $realdata_count = 0;
        if($success){

            foreach ($gameRecords as $record) {
                $start_date = new DateTime($this->gameTimeToServerTime($record['timeBet']));
                $end_date = new DateTime($this->gameTimeToServerTime($record['actionTime']));
                $insertRecord = array();
                $insertRecord = array(
                    'user_id'               => $record['memberId'], // user_id collumn is used instead memberId
                    'memberType'            => $record['memberType'],
                    'bet_id'                => $record['betId'],
                    'betNo'                 => $record['betNo'],
                    'drawId'                => $record['drawId'],
                    'drawNo'                => $record['drawNo'],
                    'areaName'              => $record['areaName']."_R",
                    'betType'               => $record['betType'],
                    'betContent'            => $record['betContent'],
                    'currency'              => $record['currency'],
                    'bet'                   => $record['betAmount'],
                    'odds'                  => $record['odds'],
                    'isWin'                 => $record['isWin'],
                    'winAmount'             => $record['winAmount'],
                    'timeBet'               => $start_date->format('Y-m-d H:i:s'),
                    'actionTime'            => $end_date->format('Y-m-d H:i:s'),
                    'keno_status'           => $record['status'],
                    'OS'                    => $record['OS'],
                    'external_uniqueid'     => $record['betNo'],
                    'response_result_id'    => $responseResultId,
                    'game_provider'         => self::KENO,
                    'game_platform'         => GAMEPLAY_API,
                );

                $isExists = $this->CI->gameplay_game_logs->isRowIdAlreadyExists($insertRecord['external_uniqueid']);
                if ($isExists) {
                    $this->CI->gameplay_game_logs->updateGameLogs($insertRecord);
                } else {
                    $this->CI->gameplay_game_logs->insertGameplayGameLogs($insertRecord);
                }
                $count++;
            }
        }

        $result = array(
            'insert_count'=> $count,
            'realdata_count'=> $realdata_count,
            'page_num'=> isset($resultArr['items']['@attributes']['page_num'])?$resultArr['items']['@attributes']['page_num']:'',
            'page_size'=> isset($resultArr['items']['@attributes']['page_size'])?$resultArr['items']['@attributes']['page_size']:''
        );

        return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::SLOTANDTABLE_API);

		if (!empty($this->enabled_api['sbtech'])) {
			$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::SBTECH_API);
		}

		if (!empty($this->enabled_api['betsoft'])){
			$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::BETSOFT);
		}
		if (!empty($this->enabled_api['ctxm'])) {
			$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::CTXM_API);
		}
		if (!empty($this->enabled_api['isoftbet'])) {
			$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::ISOFTBET);
		}
		if (!empty($this->enabled_api['png'])) {
			$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::PNG);
		}
		if (!empty($this->enabled_api['ttg'])) {
			$this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::TTG);
		}
        if (!empty($this->enabled_api['keno'])) {
            $this->syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, self::KENO);
            $this->CI->utils->debug_log('Merge Game logs Keno[24] ==========================>',$this->enabled_api['keno']);
        }
		return array("success" => true);
	}

	private function syncMergeGameplayGameLogs($dateTimeFrom, $dateTimeTo, $gameType) {
		$this->CI->load->model(array('game_logs', 'player_model', 'gameplay_game_logs', 'game_description_model'));
		$unknownGame = $this->getUnknownGame();
		$dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');
		$dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');
        $gpResult = true;
		switch ($gameType) {
			case self::SLOTANDTABLE_API:
				$gpResult = $this->CI->gameplay_game_logs->getGameplayGameLogStatistics($dateTimeFrom,$dateTimeTo);
				break;
			case self::SBTECH_API:
				$gpResult = $this->CI->gameplay_game_logs->getSbtechGameLogStatistics($dateTimeFrom,$dateTimeTo);
				break;
			case self::CTXM_API:
				$gameType = self::CTXM; // replace to CTXM
				$gpResult = $this->CI->gameplay_game_logs->getGameLogStatisticsForSubproviders($dateTimeFrom,$dateTimeTo,$gameType);
				break;
			case self::BETSOFT:
			case self::ISOFTBET:
			case self::PNG:
			case self::TTG:
				$gpResult = $this->CI->gameplay_game_logs->getGameLogStatisticsForSubproviders($dateTimeFrom,$dateTimeTo,$gameType);
				break;
            case self::KENO:
                $gpResult = $this->CI->gameplay_game_logs->getGameLogStatisticsForKeno($dateTimeFrom,$dateTimeTo,$gameType);
                $gpResult = json_decode(json_encode($gpResult));
                break;
			default:
				$gpResult = $this->CI->gameplay_game_logs->getGameplayGameLogStatistics($dateTimeFrom,$dateTimeTo);
			   break;
		}

		$rlt = false;
		if ($gpResult) {
            $unknownGame = $this->getUnknownGame();

			foreach ($gpResult as $gpdata) {
				$player_id = $this->getPlayerIdInGameProviderAuth($gpdata->user_id);
				if (!$player_id) {
					continue;
				}

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

                $extra = null;
				$bet_amount = $gpdata->bet;
				$operation_code = property_exists($gpdata,'operation_code');
				$extra = array('trans_amount' => $this->gameAmountToDB($bet_amount));
				if($operation_code){
					$extra['table'] = $gpdata->operation_code;
				}

                if (isset($gpdata->game_provider) && $gpdata->game_provider == self::KENO) {
                    $gameDate = new \DateTime($gpdata->timeBet);
                    $result_amount = $gpdata->winAmount - $gpdata->bet;
                    $result_amount = ($gpdata->keno_status != "settled") ? null: $result_amount;
					$betdetails = $this->convertGamedetatilsToJson($gpdata,$result_amount,self::KENO);
					$extra['status'] =  $this->getKenoStatus($gpdata->keno_status);
					$extra['note'] = $betdetails;
                } else {
                    $gameDate = new \DateTime($gpdata->trans_date);
                    $result_amount = $gpdata->winlose;
                }
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

				$game_description_id = $gpdata->game_description_id;
                $game_type_id = $gpdata->game_type_id;

                if (empty($gpdata->game_description_id)) {
                    list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($gpdata, $unknownGame, $gameType);
                }

				if ($bet_amount == 0 && $result_amount == 0) {
					$this->CI->utils->debug_log('bet_amount and result amount is zero');
					continue;
				}

				if($gameType == self::SLOTANDTABLE_API && $this->enable_opposite_bet_checking){
					if (in_array($gpdata->gp_game_type, $this->gameplay_standard_game_type['live'])) {
						$hand_result = $this->checkHandResult((array)$gpdata);
						if(in_array($hand_result['player_hand'], $this->hand_result_for_opposite_bet)){
							$bet_amount = 0;
							$extra['bet_for_cashback'] = 0;
							$extra['note'] = "Opposite betting";
						}
					}
				}

				if(empty($extra['status'])){
					$status = property_exists($gpdata,'status');
					if($status){
						$extra['status'] = $gpdata->status;
					}
				}
				$rawStatus = isset($extra['status']) ? $extra['status'] : null;

				$extra['status'] = $this->convertGameStatusToGameLogsStatus($rawStatus);

				$this->syncGameLogs($game_type_id,
					$game_description_id,
					$gpdata->game_code,
					$gpdata->game_type,
					$gpdata->game_name,
					$player_id,
					$player_username,
					$this->gameAmountToDBGameLogsTruncateNumber($bet_amount),
					$this->gameAmountToDBGameLogsTruncateNumber($result_amount),
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$gpdata->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$gpdata->response_result_id,
                    null,
                    $extra);
			}
			$rlt = true;
		}
		return $rlt;
	}

	private function convertGameStatusToGameLogsStatus($status) {
		switch ($status) {
			case self::GAME_STATUS_OPEN:
				return Game_Logs::STATUS_UNSETTLED;
			case self::GAME_STATUS_NORMAL:
				return Game_Logs::STATUS_SETTLED;
			case self::GAME_STATUS_VOID:
				return Game_Logs::STATUS_VOID;
			case Game_Logs::STATUS_CANCELLED:
				return Game_Logs::STATUS_CANCELLED;
			default:
				return Game_Logs::STATUS_SETTLED;
		}
	}
	private function checkHandResult($row){
		$this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
	user_id,
	bundle_id,
	GROUP_CONCAT( player_hand SEPARATOR '-' ) AS player_hand,
	count(*) AS cnt,
	trans_date 
FROM
	gameplay_game_logs 
WHERE
	user_id = ? 
	AND trans_date >= ?
	AND trans_date <= ?
	AND bundle_id = ? 
GROUP BY
	bundle_id
EOD;
		$start = new DateTime($row['trans_date']);
        $start->modify('-5 minutes');
        $end = new DateTime($row['trans_date']);
        $end->modify('+5 minutes');
        $params=[
            $row['user_id'],
            $start->format("Y-m-d H:i:s"),
            $end->format("Y-m-d H:i:s"),
            $row['bundle_id']
        ];

        $this->CI->utils->debug_log('GPI checkHandResult sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result; 
	}

	private function getKenoStatus($status) {
		$this->CI->load->model(array('game_logs'));
		$status = strtolower($status);
		switch ($status) {
			case 'unsettled':
				$status = Game_logs::STATUS_PENDING;
				break;
			case 'cancelled':
			case 'cancel':
				$status = Game_logs::STATUS_CANCELLED;
				break;
			case 'settled':
				$status = Game_logs::STATUS_SETTLED;
				break;
		}
		return $status;
	}

	private $gameplay_standard_game_type = [
			'live' => ['C baccarat', 'NC baccarat', 'S98 baccarat', 'fab4', 'C squeeze', 'NC squeeze', 'dragontiger', 'sicbo', 'roulette', 'sevenup', 's3pictures', 'colourdice', 'blackjack', 'superfatan', 'superhilo', 'LK baccarat']
		];

	private function getGameDescriptionInfo($row, $unknownGame,$gameType) {
        $this->CI->load->model('game_type_model');
        $game_type = $unknownGame->game_name;
        $game_type_id = $unknownGame->game_type_id;

		$game_description_id = $gameTypeDetails = $gameTypeCode = null;
        $select = '*';
		$where = 'game_platform_id = ' . $this->getPlatformCode() . ' and status = '. Game_type_model::DB_TRUE. " and game_type_code = ";
		if ($gameType == self::SLOTANDTABLE_API) {
			if (empty($row->gp_game_type)) {
				$gameTypeCode = '"slots"';
				$gameTypeStr = 'slot';
				$where .= $gameTypeCode;
			}

			if (in_array($row->gp_game_type, $this->gameplay_standard_game_type['live'])) {
				$gameTypeCode = '"live_dealer"';
				$gameTypeStr = 'live';
				$where .= $gameTypeCode;
			}

		}

		if ($gameType == self::KENO) {
			$gameTypeCode = '"lottery"';
			$gameTypeStr = 'lottery';
			$where .= $gameTypeCode;
		}

		if ($gameTypeCode) {
	        $gameTypeDetails = $this->CI->game_type_model->getGameTypeByQuery($select,$where);
	        if (empty($gameTypeDetails)) {
	        	$query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%" .$gameTypeStr. "%')";
	        	$gameTypeDetails = $this->CI->game_type_model->getGameTypeList($query);
	        }
		}

        if ($gameTypeDetails) {
            $gameTypeDetails = reset($gameTypeDetails);
            $game_type = $gameTypeDetails['game_type'];
            $game_type_id = $gameTypeDetails['id'];
        }

		$gameCode = $game_name = $externalGameId = $row->gameshortcode;
		$branchName = isset($row->branchName)?$row->branchName:$row->game_name;

		$extra = array(
			'game_code' => $gameCode,
			'game_type_id' => $game_type_id, 
			'note' => $branchName
		);
		
		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$game_name, $game_type, $externalGameId, $extra,
			$unknownGame);
	}

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function gameAmountToDB($amount) {
		$conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
        // return $amount / $conversion_rate;
	}

	private function getGameplayGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('gameplay_game_logs');
		return $this->CI->gameplay_game_logs->getGameplayGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

    public function convertGamedetatilsToJson($gameDetails = null,$result_amount,$subgameprovider = null){

        $data = null;
        switch ($subgameprovider) {
            case self::KENO :
                    $result = ($gameDetails->keno_status == "settled") ? $result_amount : $gameDetails->keno_status;
                    $data = lang("Place of bet") . ": " . $gameDetails->gameshortcode . ", " . lang("Bet amount") . ": " . $gameDetails->bet . ", " . lang(" Bet result") . ": " . $result;
                break;
        }

        return json_encode($data);

    }

    public function getTransferErrorReasonCode($apiErrorCode) {
        $reasonCode = self::REASON_UNKNOWN;

        switch ((int)$apiErrorCode) {
        	case 1:
        		$reasonCode = self::REASON_DUPLICATE_TRANSFER;
                break;
            case -1:
            	$reasonCode = self::REASON_UNKNOWN;
                break;
            case -3:
            	$reasonCode = self::REASON_NOT_FOUND_PLAYER;
                break;
            case -4:
            	$reasonCode = self::REASON_NO_ENOUGH_BALANCE;
                break;
            case -7:
            	$reasonCode = self::ERROR_BLOCKED_PLAYER;
                break;
            case -27:
            	$reasonCode = self::ERROR_PARAMETER_MISMATCH;
                break;
            case -29:
            	$reasonCode = self::REASON_CURRENCY_ERROR;
                break;
            case -33:
            	$reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case -48:
            	$reasonCode = self::ERROR_INCORRECT_MERCHANT_PWD;
                break;
            case -119:
            	$reasonCode = self::ERROR_MISSING_INPUT_PARAMS;
                break;
            case -201:
            	$reasonCode = self::ERROR_TRANSACTION_NOT_EXISTS;
                break;
            case -203:
            	$reasonCode = self::ERROR_CUSTOMER_EXISTS;
                break;
        }

        return $reasonCode;
    }

    public function isDeclinedStatus($errorCode){
        $status=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        if(!empty($errorCode)){
            $errorCode=(int) $errorCode;
            switch ($errorCode) {
				case 1:
				case -1:
				case -3:
				case -4:
				case -29:
				case -33:
				case -33:
                    $status=self::COMMON_TRANSACTION_STATUS_DECLINED;
                    break;
            }
        }

        return $status;
    }

    public function lotteryGameName($table_id, $game_id) {
    	$game_name = '';
    	if ($game_id) {
    		switch (strtolower($game_id)) {
				case 'keno':
					$game_name = $table_id;
                    break;
				case 'pk10':
					$game_name = $table_id . '_P';
                    break;
				case 'ladder':
					$game_name = $table_id . '_L';
                    break;
				case 'thailottery':
					$game_name = $table_id . '_R';
                    break;
				case 'rps':
					$game_name = $table_id . '_R';
                    break;
				case 'fast3':
					$game_name = $table_id . '_F';
                    break;
				case 'thor':
					$game_name = $table_id . '_T';
                    break;
                case 'vnlottery':
				case 'sode':
                	$game_name = $table_id . '_S';
                    break;
                case 'taixiu':
                	$game_name = $table_id . '_F3';
                    break;
                case 'luckyderby':
                	$game_name = $table_id;
                    break;
				case 'sabaideelottery':
					$game_name = $table_id . '_SA';
					break;
            }
    	}
    	return $game_name;
    }

}

/*end of file*/