<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_opus_keno extends Abstract_game_api {

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

	const URI_MAP = array(
		self::API_createPlayer => 'live/keno_create_member.aspx',
		self::API_login => 'login',
		self::API_logout => 'SuspenseMember',
		self::API_queryPlayerBalance => 'live/keno_member_wallet.aspx',
		self::API_depositToGame => 'live/keno_credit_fund.aspx',
		self::API_withdrawFromGame => 'live/keno_debit_fund.aspx',
		self::API_syncGameRecords => 'live/keno_game_transaction_detail.aspx',
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
	}

	public function getPlatformCode() {
		return OPUS_KENO_API;
	}

	function forwardCallback($url, $params) {
		list($header, $resultText) = $this->httpCallApi($url, $params);
		$this->CI->utils->debug_log('forwardCallback', $url, $params, $header, $resultText);
		return $resultText;
	}

	public function callback($method, $result = null) {

		$this->CI->utils->debug_log('Game_api_opus (Callback): ', $result);

		if ($method == 'validatemember') {

			if ($result) {
				parse_str($result, $resArr);
			} else {
				$resArr = array();
			}

			$token = isset($resArr['session_token']) ? $resArr['session_token'] : NULL;
			$token = urldecode($token);

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

			$token = isset($resArr['session_token'])?$resArr['session_token']:null;
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
							"member_type" => $this->opus_member_type,
							"balance" => "0",
							"min_transfer" => $this->opus_min_transfer,
							"max_transfer" => $this->opus_max_transfer,
							"member_type" => "CASH",
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
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);
		$url = $this->opus_api_url . "/" . $apiUri;
		return $url;
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
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"operator_id" => $this->opus_operator_id,
			"member_id" => $gameUsername,
			"member_name" => $gameUsername,
			"language" => $this->opus_language,
			"currency" => $this->opus_currency,
			"member_type" => $this->opus_member_type,
			"min_transfer" => $this->opus_min_transfer,
			"max_transfer" => $this->opus_max_transfer
		);

        $this->CI->utils->debug_log("========== createPlayer params: " . json_encode($params) . " ==========");

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
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"operator_id" => $this->opus_operator_id,
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
		if ($success && isset($resultArr['member_wallet']) && $resultArr['member_wallet'] !== null) {
			$result["balance"] = $this->gameAmountToDB(floatval($resultArr['member_wallet']));
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $resultArr['member_wallet']);
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

		$reference_id = date('YmdHis').random_string('alpha');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"operator_id" => $this->opus_operator_id,
			"member_id" => $gameUsername,
			"currency" => $this->opus_currency,
			"reference_id" => $reference_id,
			"Amount" => $this->dBtoGameAmount($amount),
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
		$result = array('response_result_id' => $responseResultId);

		if ($success) {
			$afterBalance = isset($resultArr['wallet_end']) ? $resultArr['wallet_end'] : null;
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			if ($playerId) {
				$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$result["userNotFound"] = true;
		}

		return array($success, $result);
	}
	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$reference_id = date('YmdHis').random_string('alpha');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"operator_id" => $this->opus_operator_id,
			"member_id" => $gameUsername,
			"currency" => $this->opus_currency,
			"reference_id" => $reference_id,
			"Amount" => $this->dBtoGameAmount($amount),
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
		$result = array('response_result_id' => $responseResultId);

		if ($success) {

			$afterBalance = isset($resultArr['wallet_end']) ? $resultArr['wallet_end'] : null;
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

			if ($playerId) {
				$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}
		} else {
			$result["userNotFound"] = true;
		}

		return array($success, $result);
	}

	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
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

	public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		return $this->callApi(self::API_logout,
			array(
				"secret_key" => $this->opus_secret_key,
				"operator_id" => $this->opus_operator_id,
				"site_code" => $this->opus_site_code,
				"product_code" => $this->opus_product_code,
				"member_id" => $playerName,
				"action" => true,
			),
			$context);
	}

	public function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, null);
	}

	public function updatePlayerInfo($playerName, $infos) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
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
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra) {

		$nextUrl=$this->generateGotoUri($playerName, $extra);
		$result=$this->forwardToWhiteDomain($playerName, $nextUrl);
		if($result['success']){
			return $result;
		}

		$language = isset($extra['language']) ? $extra['language'] : 'en-US';

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

		return array('success' => TRUE, 'url' => $this->opus_game_url);

	}

	public function generateGotoUri($playerName, $extra) {
		return '/iframe_module/goto_opus/keno/0/0/' . (isset($extra['gameMode']) ? $extra['gameMode'] : 'real') . '/' . $extra['language'];
	}

	const START_PAGE = 0;
	public function syncOriginalGameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$params = array(
			"secret_key" => $this->opus_secret_key,
			"site_code" => $this->opus_site_code,
			"product_code" => $this->opus_product_code,
			"operator_id" => $this->opus_operator_id,
			"start_time" => $startDate->format('Y-m-d H:i:s'),
			"end_time" => $endDate->format('Y-m-d H:i:s'),
			"status" => 'all',
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$this->CI->load->model(array('opus_keno_game_logs','player_model'));

		if ($success) {
			$gameRecords = isset($resultArr['bet']['item']) ? $resultArr['bet']['item'] : NULL;
			$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);
			if ($gameRecords) {
				$availableRows = $this->CI->opus_keno_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows));
				$this->CI->utils->debug_log('xxxxx OPUS KENO gamelogs' , var_export($availableRows, true) );
				foreach ($availableRows as $record) {
					$data = array();
					$data['username'] = isset($record['@attributes']['member_id'])?$record['@attributes']['member_id']:$record['member_id'];
					$data['player_id'] = $this->getPlayerIdInGameProviderAuth(isset($record['@attributes']['member_id'])?$record['@attributes']['member_id']:$record['member_id']);
					$data['member_id'] = isset($record['@attributes']['member_id'])?$record['@attributes']['member_id']:$record['@attributes']['member_id'];
					$data['member_type'] = isset($record['@attributes']['member_type'])?$record['@attributes']['member_type']:$record['member_type'];
					$data['session_token'] = isset($record['@attributes']['session_token'])?$record['@attributes']['session_token']:$record['session_token'];
					$data['bet_id'] = isset($record['@attributes']['bet_id'])?$record['@attributes']['bet_id']:$record['bet_id'];
					$data['bet_no'] = isset($record['@attributes']['bet_no'])?$record['@attributes']['bet_no']:$record['bet_no'];
					$data['match_no'] = isset($record['@attributes']['match_no'])?$record['@attributes']['match_no']:$record['match_no'];
					$data['match_area'] = isset($record['@attributes']['match_area'])?$record['@attributes']['match_area']:$record['match_area'];
					$data['match_id'] = isset($record['@attributes']['match_id'])?$record['@attributes']['match_id']:$record['match_id'];
					$data['bet_type'] = isset($record['@attributes']['bet_type'])?$record['@attributes']['bet_type']:$record['bet_type'];
					$data['bet_content'] = isset($record['@attributes']['bet_content'])?$record['@attributes']['bet_content']:$record['bet_content'];
					$data['bet_currency'] = isset($record['@attributes']['bet_currency'])?$record['@attributes']['bet_currency']:$record['bet_currency'];
					$data['bet_money'] = isset($record['@attributes']['bet_money'])?$record['@attributes']['bet_money']:$record['bet_money'];
					$data['bet_odds'] = isset($record['@attributes']['bet_odds'])?$record['@attributes']['bet_odds']:$record['bet_odds'];
					$data['bet_winning'] = isset($record['@attributes']['bet_winning'])?$record['@attributes']['bet_winning']:$record['bet_winning'];
					$data['bet_win'] = isset($record['@attributes']['bet_win'])?$record['@attributes']['bet_win']:$record['bet_win'];
					$data['bet_status'] = isset($record['@attributes']['bet_status'])?$record['@attributes']['bet_status']:$record['bet_status'];
					$data['bet_time'] = isset($record['@attributes']['bet_time'])?$record['@attributes']['bet_time']:$record['bet_time'];
					$data['trans_time'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s",strtotime(isset($record['@attributes']['trans_time'])?$record['@attributes']['trans_time']:$record['trans_time'])));
					$data['external_uniqueid'] = isset($record['@attributes']['bet_id'])?$record['@attributes']['bet_id']:$record['bet_id']; //add external_uniueid for og purposes
					$data['response_result_id'] = $responseResultId;
					array_walk($data, function(&$value) {
						if (is_array($value)) $value = ! empty($value) ? json_encode($value) : NULL;
					});
					$this->CI->opus_keno_game_logs->insertOpusGameLogs($data);
				}
			}

		}

		return array($success, null);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'opus_keno_game_logs'));
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');
		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);
		$rlt = array('success' => true);
		$result = $this->CI->opus_keno_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;
		if ($result) {
			$unknownGame = $this->getUnknownGame();
			foreach ($result as $kenogame_data) {
				$cnt++;
				$bet_amount = $this->gameAmountToDB($kenogame_data->bet_money);
				$result_amount = $this->gameAmountToDB($kenogame_data->bet_winning) - $bet_amount;
				// echo "<pre>";print_r($kenogame_data);exit;
				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($kenogame_data, $unknownGame);
				$this->syncGameLogs(
					$game_type_id, # $game_type_id
					$game_description_id, # $game_description_id
					$kenogame_data->game_name, # $game_code
					$game_type_id, #$game_type
					$kenogame_data->game_name, # $game
					$kenogame_data->player_id, # $player_id
					$kenogame_data->player_name, #$player_username
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$kenogame_data->external_uniqueid,
					$kenogame_data->game_date,
					$kenogame_data->game_date,
					$kenogame_data->response_result_id# response_result_id
				);
			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	function getGameTimeToServerTime() {
		return '+8 hours';
	}

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	function getServerTimeToGameTime() {
		return '-8 hours';
	}

	private function getGameInfoFromOriginal($row) {
		$game_type = $row->game_name;
		if (isset($row->match_area)) {
			$game = $row->game_name . ' ' . $row->match_area;
		} else {
			$game = $row->game_name;
		}
		$game_code = $game;
		$externalGameId = $row->game_name;
		return array($game_type, $game, $game_code, $externalGameId);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		// $this->CI->utils->debug_log('GetGameDescriptionInfo Row =>>>>>>>', $row);
		//echo "<pre>";print_r($row);exit;

		$game_name = $this->CI->opus_keno_game_logs->getKenoGameName($row->game_name." ".$row->match_area);
		if (isset($game_name[0])) {
			$game_description_id = $game_name[0]['id'];
			$game_type_id = $game_name[0]['game_type_id'];
			return array($game_description_id, $game_type_id);
		}else{
			list($game_type, $game, $game_code, $externalGameId) = $this->getGameInfoFromOriginal($row);
			$extra = array('game_code' => $game_code);
			return $this->processUnknownGame(
				null, $game_code,
				$game, $game_type, $externalGameId, $extra,
				$unknownGame);
		}
	}

	// public function gameAmountToDB($amount) {
	// 	return round(floatval($amount), 2);
	// }

	private function getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('opus_keno_game_logs');
		return $this->CI->opus_keno_game_logs->getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

}

/*end of file*/