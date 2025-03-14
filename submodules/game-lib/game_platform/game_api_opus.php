<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_opus extends Abstract_game_api {

	private $opus_secret_key;
	private $opus_operator_id;
	private $opus_site_code;
	private $opus_product_code;

	private $opus_validation_link;
	private $opus_validation_link_secret_key;

	private $opus_min_transfer;
	private $opus_max_transfer;
	private $opus_currency;
	private $opus_language;
	private $opus_balance;
	private $opus_member_type;
	private $forward_sites = null;

	const STATUS_SUCCESS = '00';
	const BET_STATUS_DRAW = 'tie';

	const URI_MAP = array(
		self::API_createPlayer => 'CreateMember',
		self::API_login => 'login',
		self::API_logout => 'KickOutMember',
		self::API_queryPlayerBalance => 'MemberBalance',
		self::API_depositToGame => 'CreditBalance',
		self::API_withdrawFromGame => 'DebitBalance',
		self::API_syncGameRecords => 'TransactionDetail',
		self::API_isPlayerExist		 => 'MemberBalance',
		self::API_blockPlayer		 => 'SuspenseMember',
		self::API_unblockPlayer		 => 'ResumeMember',
	);

	public function __construct() {
		parent::__construct();
		$this->opus_api_url 			= $this->getSystemInfo('opus_api_url');
		$this->opus_secret_key 			= $this->getSystemInfo('opus_secret');
		$this->opus_operator_id 		= $this->getSystemInfo('opus_operator_id');
		$this->opus_site_code 			= $this->getSystemInfo('opus_site_code');
		$this->opus_product_code 		= $this->getSystemInfo('opus_product_code');
		$this->opus_min_transfer 		= $this->getSystemInfo('opus_min_transfer');
		$this->opus_max_transfer 		= $this->getSystemInfo('opus_max_transfer');
		$this->opus_currency 			= $this->getSystemInfo('opus_currency');
		$this->opus_language 			= $this->getSystemInfo('opus_language');
		$this->opus_member_type 		= $this->getSystemInfo('opus_member_type');
		$this->opus_cookie_domain 		= $this->getSystemInfo('opus_cookie_domain');
		$this->opus_game_url 			= $this->getSystemInfo('opus_game_url');
		$this->forward_sites 			= $this->getSystemInfo('forward_sites');
		$this->enabled_dynamic_domain   = $this->getSystemInfo('enabled_dynamic_domain',false);
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+15 minutes');
	}

	public function getPlatformCode() {
		return OPUS_API;
	}

	function forwardCallback($url, $params) {
		list($header, $resultText) = $this->httpCallApi($url, $params);
		$this->CI->utils->debug_log('forwardCallback', $url, $params, $header, $resultText);
		return $resultText;
	}

	public function callback($method, $result = null, $platform = 'web') {

		$this->CI->utils->debug_log('Game_api_opus (Callback): ', $result);

		if ($method == 'validatemember') {
			if($platform == 'web') {
				if ($result) {
					parse_str($result, $resArr);
				} else {
					$resArr = array();
				}

				$token = isset($resArr['session_token']) ? $resArr['session_token'] : NULL;
				$token = urldecode($token);

				$this->CI->utils->debug_log('originalToken', $token);
				@list($prefix, $token) = explode('|', $token);
				if ($this->forward_sites && $token && isset($this->forward_sites[$prefix])) {
					$url = $this->forward_sites[$prefix];
					$resArr['session_token'] = $token;
					return $this->forwardCallback($url, http_build_query($resArr));
				}

				$token = $token ? : $prefix;

				$this->CI->load->model(array('common_token'));

				$this->CI->utils->debug_log('Game_api_opus (Callback)  Result Array:', $resArr);
				$datetime = new DateTime();

				$this->CI->utils->debug_log('Game_api_opus (Callback)  token:', $token);

				//default result will return if failed
				$data = array(
					"status_code" => '01',
					"status_text" => "OK",
				);

				if (!empty($token)) {
					$playerId = $this->CI->common_token->getPlayerIdByToken($token);

					$this->CI->utils->debug_log('playerId', $playerId);
					if (!empty($playerId)) {
						$login_name = $this->getGameUsernameByPlayerId($playerId);
						$this->CI->utils->debug_log('playerId', $playerId, 'login_name', $login_name);
						if (!empty($login_name)) {
							//this result will return if success
							$data = array(
								"status_code" => self::STATUS_SUCCESS,
								"status_text" => "OK",
								"currency" => $this->opus_currency,
								"member_id" => $login_name,
								"member_name" => $login_name,
								"language" => $this->opus_language,
								"min_transfer" => $this->opus_min_transfer,
								"max_transfer" => $this->opus_max_transfer,
								"member_type" => $this->opus_member_type,
								"datetime" => $datetime->format('m/d/Y H:i:s'),
							);
						}
					}
				}

				$xml_object = new SimpleXMLElement("<?xml version='1.0'?><authenticate></authenticate>");
				$xmlReturn = $this->CI->utils->arrayToXml($data, $xml_object);
				$this->CI->utils->debug_log('Game_api_opus (Callback)  XML Return:', $xmlReturn);
				return $xmlReturn;
			}
			else {
				//default result will return if failed
				$data = array(
					"status_code" => '13',
					"status_text" => "Login failed : Invalid Username and/or Password",
				);

				if(!empty($result['txtLoginID'])) {
					$playerId = $this->getPlayerIdInGameProviderAuth($result['txtLoginID']);
					$password = $this->getPasswordByGameUsername($result['txtLoginID']);
					if(!empty($playerId)){

						if($password == $result['txtPassword']) {
							
							if ($this->getSystemInfo('prefix_for_username')) {
								$sessionToken = $this->getSystemInfo('prefix_for_username') . '|' . $this->getPlayerToken($playerId);
							} else {
								$sessionToken = $this->getPlayerToken($playerId);
							}

							$data = array(
								"status_code" => '00',
								"status_text" => "Success",
								"sessionId"	  => $sessionToken		
							);
						}
					}
				}

				$xml_object = new SimpleXMLElement("<?xml version='1.0'?><login></login>");
				$xmlReturn = $this->CI->utils->arrayToXml($data, $xml_object);
				$this->CI->utils->debug_log('Game_api_opus (Callback) Mobile XML Return:', $xmlReturn);
				return $xmlReturn;
			}
		}
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);
		$url = $this->opus_api_url . "/" . $apiUri . ".API?" . $params_string;
		$this->CI->utils->debug_log('OPUS got generated url', $url);
		return $url = $this->opus_api_url . "/" . $apiUri . ".API?" . $params_string;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['status_code'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('OPUS got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"operator_id" => $this->opus_operator_id,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"member_id" => $gameUsername,
			"language" => $this->opus_language,
			"currency" => $this->opus_currency,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);
		return array($success, null);
	}
	//===end createPlayer=====================================================================================
	//=== start isPlayerExist ================================================================================
	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"operator_id" => $this->opus_operator_id,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"member_id" => $gameUsername,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$success = ($resultArr['status_code'] == 00) ? true : false;
        $this->utils->debug_log("Check Opus player if exist ============================>", $success);
        $result['exists'] = $success;
		return array(true, $result);
	}
	//===end isPlayerExist=====================================================================================
	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"operator_id" => $this->opus_operator_id,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"member_id" => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array();
		if ($success && isset($resultArr['balance']) && $resultArr['balance'] !== null) {
			$result["balance"] = $this->gameAmountToDB(floatval($resultArr['balance']));
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $resultArr['balance']);
			if ( ! $playerId) {
				log_message('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$reference_id = $this->getSystemInfo('prefix_for_username').$transfer_secure_id;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			// 'external_transaction_id' => $reference_id
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"operator_id" => $this->opus_operator_id,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"member_id" => $gameUsername,
			"currency" => $this->opus_currency,
			"amount" => $this->dBtoGameAmount($amount),
			"reference_id" => $reference_id,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		// $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id' => null);
		if ($success) {
			// $afterBalance = isset($resultArr['balance_end']) ? $resultArr['balance_end'] : null;
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = true;
		}
		$result["transfer_status"] = ($success) ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
	    $result["reason_id"] = $this->getReasonId($resultArr);
		return array($success, $result);
	}
	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$reference_id = $this->getSystemInfo('prefix_for_username').$transfer_secure_id;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			// 'external_transaction_id' => $reference_id
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"operator_id" => $this->opus_operator_id,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"member_id" => $gameUsername,
			"currency" => $this->opus_currency,
			"amount" => $this->dBtoGameAmount($amount),
			"reference_id" => $reference_id,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		// $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id' => null);
		if ($success) {
			// $afterBalance = isset($resultArr['balance_end']) ? $resultArr['balance_end'] : null;
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = true;
		}
		$result["transfer_status"] = ($success) ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
	    $result["reason_id"] = $this->getReasonId($resultArr);
		return array($success, $result);
	}
	//===end withdrawFromGame=====================================================================================
	public function getReasonId($params){
		if (array_key_exists("status_code",$params)){
			$reason = $params['status_code'];
			switch ($reason) {
			    case 40.01:
			        return self::REASON_INVALID_KEY;
			        break;
			    case 48.04:
			        return self::REASON_INVALID_TRANSFER_AMOUNT;
			        break;
			    case 60.05:
			        return self::REASON_NOT_FOUND_PLAYER;
			        break;
			    case 60.08:
			        return self::REASON_CURRENCY_ERROR;;
			        break;
			    case 61.15:
			        return self::REASON_INVALID_TRANSFER_AMOUNT;
			        break;
			    default:
			        return self::REASON_UNKNOWN ;
			}
		}
    	return self::REASON_UNKNOWN ;
    }
	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}
	//===end changePassword=====================================================================================

	//===start blockPlayer=====================================================================================
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogoutBlockPlayer',
			'playerName' => $playerName,
			// 'playerId' => $playerId,
		);

		return $this->callApi(self::API_blockPlayer,
			array(
				"secret_key" => $this->opus_secret_key,
				"operator_id" => $this->opus_operator_id,
				"site_code" => $this->opus_site_code,
				"product_code" => $this->opus_product_code,
				"member_id" => $playerName,
			),
			$context);
	}

	public function processResultForLogoutBlockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);
		if ($success) {
			$this->blockUsernameInDB($playerName);
		}

		return array($success, null);
	}

	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			// 'playerId' => $playerId,
		);

		return $this->callApi(self::API_unblockPlayer,
			array(
				"secret_key" => $this->opus_secret_key,
				"operator_id" => $this->opus_operator_id,
				"site_code" => $this->opus_site_code,
				"product_code" => $this->opus_product_code,
				"member_id" => $playerName,
			),
			$context);
	}

	public function processResultForUnblockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);
		if ($success) {
			$this->unblockUsernameInDB($playerName);
		}

		return array($success, null);
	}
	//===end unblockPlayer=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_login,
			array(
				"secret_key" => $this->opus_secret_key,
				"operator_id" => $this->opus_operator_id,
				"site_code" => $this->opus_site_code,
				"product_code" => $this->opus_product_code,
				"session_token" => $playerName,
			),
			$context);
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, $resultJson);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			// 'playerId' => $playerId,
		);

		return $this->callApi(self::API_logout,
			array(
				"secret_key" => $this->opus_secret_key,
				"operator_id" => $this->opus_operator_id,
				"site_code" => $this->opus_site_code,
				"product_code" => $this->opus_product_code,
				"member_id" => $playerName,
			),
			$context);
	}

	public function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);
		return array($success, null);
	}
	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerDailyBalance=====================================================================================
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
	//===end queryPlayerDailyBalance=====================================================================================

	//===start queryGameRecords=====================================================================================
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}
	//===end queryGameRecords=====================================================================================

	//===start checkLoginStatus=====================================================================================
	public function checkLoginStatus($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true, "loginStatus" => true);
	}
	//===end checkLoginStatus=====================================================================================

	//===start totalBettingAmount=====================================================================================
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {

	}
	//===end totalBettingAmount=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {

	}
	//===end queryTransaction=====================================================================================

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $extra) {
		$nextUrl=$this->generateGotoUri($playerName, $extra);
		$result=$this->forwardToWhiteDomain($playerName, $nextUrl);
		if($result['success']){
			return $result;
		}

		$language = isset($extra['language']) ? $extra['language'] : 'en-US';
		$url = $this->opus_game_url;
		if($this->enabled_dynamic_domain){
			$domain 		= $this->getCurrentDomain();
			$parse_domain 	= parse_url($domain);
			$domain 		= str_replace(array('www.','player.','admin.'),array('','',''),$parse_domain['path']);
			$url 			= $this->getCurrentProtocol().'://mcasino.'.$domain;
			$this->opus_cookie_domain = $domain;
		}

		if (isset($extra['gamePlatform'])) {

			$gameMode = isset($extra['gameMode']) && $extra['gameMode'] == 'trial' ? '0' : '1';
			$params = array('gamePlay' => $gameMode);

			switch ($extra['gamePlatform']) {
				case 'mg':
					$url .= '/gameloaderHB1.aspx';
					$params['gid'] = $extra['gameId'];
					break;
				case 'gsos':
					$url .= '/GameLoader.aspx';
					$params['gameID'] = $extra['gameId'];
					break;
				case 'pp':
					$url .= '/GameLoaderPP.aspx';
					$params['gid'] = $extra['gameId'];
					break;
			}

			$url .= '?' . http_build_query($params);
		}

		if ($playerName) {

			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			if ($this->getSystemInfo('prefix_for_username')) {
				$sessionToken = $this->getSystemInfo('prefix_for_username') . '|' . $this->getPlayerToken($playerId);
			} else {
				$sessionToken = $this->getPlayerToken($playerId);
			}

			$this->CI->load->helper('cookie');

			set_cookie('S', $sessionToken, 172800, $this->opus_cookie_domain);
			set_cookie('SelectedLanguage', $language, 172800, $this->opus_cookie_domain);

		}

		return array('success' => TRUE, 'url' => $url);

	}

	public function generateGotoUri($playerName, $extra) {
		return '/iframe_module/goto_opus/' . (isset($extra['gamePlatform'],$extra['gameId'],$extra['gameMode']) ? 'slots' : 'casino') . '/' . (isset($extra['gamePlatform']) ? $extra['gamePlatform'] : 0) . '/' . (isset($extra['gameId']) ? $extra['gameId'] : 0) . '/' . (isset($extra['gameMode']) ? $extra['gameMode'] : 'real') . '/' . $extra['language'];

	}
	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	const START_PAGE = 0;
	public function syncOriginalGameLogs($token) {
		//old
		// $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		// $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		// $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
		// $startDate->modify($this->getDatetimeAdjust());

		// $this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForSyncGameRecords',
		// );

		// $params = array(
		// 	"secret_key" => $this->opus_secret_key,
		// 	"operator_id" => $this->opus_operator_id,
		// 	"site_code" => $this->opus_site_code,
		// 	"product_code" => $this->opus_product_code,
		// 	"start_time" => $startDate->format('Y-m-d H:i:s'),
		// 	"end_time" => $endDate->format('Y-m-d H:i:s'),
		// 	"status" => 'all',
		// );

		// return $this->callApi(self::API_syncGameRecords, $params, $context);
		
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate->modify($this->getDatetimeAdjust());

		$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');
	    $success = false;
	    while ($queryDateTimeMax  > $queryDateTimeStart) {
	    	$startDateParam=new DateTime($queryDateTimeStart);
			if($queryDateTimeEnd>$queryDateTimeMax){
				$endDateParam=new DateTime($queryDateTimeMax);
			}else{
				$endDateParam=new DateTime($queryDateTimeEnd);
			}
			$this->CI->utils->debug_log('startDate', $startDateParam, 'endDate', $endDateParam);
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);

			$params = array(
				"secret_key" => $this->opus_secret_key,
				"operator_id" => $this->opus_operator_id,
				"site_code" => $this->opus_site_code,
				"product_code" => $this->opus_product_code,
				"start_time" => $startDateParam->format('Y-m-d H:i:s'),
				"end_time" => $endDateParam->format('Y-m-d H:i:s'),
				"status" => 'all',
			);
			// echo"<pre>";
			// print_r($params);
			$queryDateTimeStart = $endDateParam->format('Y-m-d H:i:s');
	    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    	$this->callApi(self::API_syncGameRecords, $params, $context);
	    }
	    return array('success'=>true);
	}

	public function processResultForSyncGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$this->CI->load->model(array('opus_game_logs', 'external_system'));

		if ($success) {

			$gameRecords = isset($resultArr['bets']['row']) ? $resultArr['bets']['row'] : NULL;
			if ($gameRecords) {
				$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);
				$availableRows = $this->CI->opus_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows));
				if(!empty($availableRows)){
					array_walk($availableRows, array($this, 'copyRowToDB'), $responseResultId);
				}
			}

		}

		return array($success, null);
	}

	private function copyRowToDB($row, $index, $responseResultId) {
		$result = array(
			'trans_id' => $row['transaction_id'],
			'trans_datetime' => $row['transaction_date_time'],
			'member_code' => $row['member_code'],
			'member_type' => $row['member_type'],
			'currency' => $row['currency'],
			'balance_start' => $row['balance_start'],
			'balance_end' => $row['balance_end'],
			'bet' => $row['bet'],
			'win' => $row['win'],
			'game_code' => $row['game_code'],
			'game_detail' => $row['game_detail'],
			'bet_status' => $row['bet_status'],
			'player_hand' => $row['player_hand'],
			'game_result' => $row['game_result'],
			'game_category' => $row['game_category'],
			'vendor' => $row['vendor'],
			'draw_number' => $row['DrawNumber'],
			'm88_studio' => $row['m88_studio'],
			'stamp_date' => $row['stamp_date'],
			'bet_record_id' => $row['bet_record_id'],
			# 'rebet_amount' => $row['rebet_amount'],
			# 'app_id' => $row['app_id'],
			'game_platform' => $this->getPlatformCode(),
			'external_uniqueid' => $row['bet_record_id'],
			'response_result_id' => $responseResultId,
		);

		array_walk($result, function(&$value) {
			if (is_array($value)) $value = ! empty($value) ? json_encode($value) : NULL;
		});

		$this->CI->opus_game_logs->insertOpusGameLogs($result);
	}

	private function getStringValueFromXml($xml, $key) {
		$value = (string) $xml[$key];
		if (empty($value) || $value == 'null') {
			$value = '';
		}
		return $value;
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs','player_model','game_description_model'));

		$cnt = 0;

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		if ($result = $this->getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo)) {
			foreach ($result as $opus_data) {
				$cnt++;
                $game_description_id = $opus_data->game_description_id;
                $gameTypeId = $opus_data->game_type_id;

                if(empty($game_description_id)){
                    $game_type = 'unknown';
                    $unknown_game_info = $this->processUnknownGame($game_description_id, $gameTypeId,$opus_data->game_detail, $game_type, $opus_data->game_detail);

                    $game_description_id = $unknown_game_info[0];
                    $gameTypeId          = $unknown_game_info[1];
                }

                // print_r($opus_data->game_detail);
				// list($game_description_id, $gameTypeId) = $this->getGameDescriptionInfo($opus_data->game_description_id,
					// $opus_data->game_detail, $opus_data->game_detail, $opus_data->game_detail);

				$player_id 		= $opus_data->player_id;
				// $player 		= $this->CI->player_model->getPlayerById($player_id);
				$real_bat= $this->gameAmountToDB($opus_data->bet);
				$bet_amount 	= $this->gameAmountToDB($opus_data->bet);
				$result_amount 	= $this->gameAmountToDB($opus_data->win) - $bet_amount;
				$has_both_side 	= $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
				$roundNo= $opus_data->external_uniqueid;

                if($opus_data->vendor == 'Casino'){
                    // $details = $this->convertGamedetatilsToJson($opus_data->game_detail,$opus_data->player_placed_bet,$result_amount,$bet_amount);   
                    $details = $this->setBetDetails($opus_data);
                    $bet_details = $bet_details['bet_details'][$opus_data->external_uniqueid] = $details;
                }
                 $extra = ['table'=>$roundNo, 'trans_amount'=>$real_bat, 'bet_details' =>$this->utils->encodeJson($bet_details), 'match_details' => strip_tags($opus_data->game_result)];

                if(strtolower($opus_data->bet_status) == self::BET_STATUS_DRAW){//no available amount when draw status
		        	$bet_amount = 0;
		        	$extra['note'] = lang("Draw");
		        }

				$this->syncGameLogs(
					$gameTypeId,  						# game_type_id
					$game_description_id,				# game_description_id
					$opus_data->game_code, 				# game_code
					$gameTypeId, 						# game_type
					$opus_data->game, 					# game
					$player_id, 						# player_id
					$opus_data->member_code, 					# player_username
					$bet_amount, 						# bet_amount
					$result_amount, 					# result_amount
					null,								# win_amount
					null,								# loss_amount
					$opus_data->balance_end,			# after_balance
					$has_both_side, 					# has_both_side
					$opus_data->external_uniqueid, 		# external_uniqueid
					$opus_data->start_at,				# start_at
					$opus_data->end_at,					# end_at
					$opus_data->response_result_id,		# response_result_id
					Game_logs::FLAG_GAME,
					$extra
				);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return array('success' => true, 'count' => $cnt);
	}

	public function setBetDetails($opus_data){
		$bet_placed_name = str_replace("<BR>", ", ", $opus_data->player_placed_bet);
        return $data = array(
            "Bet Placed" => $bet_placed_name,
            "Bet"   => $this->gameAmountToDB($opus_data->bet),
            "Win/Loss"   => $this->gameAmountToDB($opus_data->win) - $this->gameAmountToDB($opus_data->bet)
        );
	}

	// public function gameAmountToDB($amount) {
	// 	//only need 2
	// 	return round(floatval($amount), 2);
	// }

	private function getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('opus_game_logs');
		return $this->CI->opus_game_logs->getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

    public function convertGamedetatilsToJson($gameType,$bet_placed_name,$betAmount,$resultAmount){
        $bet_placed_name = str_replace("<BR>", ", ", $bet_placed_name);

        $data = array(
            "place_of_bet" => $bet_placed_name,
            "bet_amount" => $betAmount,
            "bet_result" => $resultAmount,
            "bet_details" => lang("Place of bet") . ": (" . $bet_placed_name . "), " . lang("Bet amount") . ": " . $betAmount . ", " . lang(" Bet result") . ": " .  $resultAmount,
        );

        return json_encode($data);

    }

	//===end syncGameRecords=====================================================================================

    public function getGameDescriptionInfo($game_description_id, $gameNameStr, $externalGameId, $gameTypeStr, $extra = null){
        $this->CI->load->model('game_type_model');
        $gameTypeId = $this->CI->game_type_model->checkGameType($this->getPlatformCode(), $gameTypeStr);

        if(!is_numeric($externalGameId)){
             if($gameTypeStr == 'Baccarat' || $gameTypeStr == 'Roulette' || $gameTypeStr == 'Sicbo' || $gameTypeStr == 'Dragon/Tiger'){
                $gameTypeStr = "Live";
             }
             return $this->processUnknownGame(
                $game_description_id, $gameTypeId,
                $gameNameStr, $gameTypeStr, $externalGameId, $extra);
        }else{
             return $this->processUnknownGame(
                $game_description_id, $gameTypeId,
                $gameNameStr, $gameTypeStr, $externalGameId, $extra);
        }

    }
	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start isPlayerExist=====================================================================================
	// public function isPlayerExist($playerName) {

	// }

	//===end isPlayerExist=====================================================================================
}

/*end of file*/