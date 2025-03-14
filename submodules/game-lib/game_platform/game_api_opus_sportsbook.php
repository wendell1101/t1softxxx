<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_opus_sportsbook extends Abstract_game_api {

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

	const STATUS_SUCCESS = '0';
	const DEBIT = 0;
	const CREDIT = 1;

	const URI_MAP = array(
		self::API_createPlayer => 'CreateMember',
		self::API_queryPlayerBalance => 'CheckUserBalance',
		self::API_depositToGame => 'FundTransfer',
		self::API_withdrawFromGame => 'FundTransfer',

		self::API_login => 'login',
		self::API_logout => 'SuspenseMember',
		self::API_syncGameRecords => 'TransactionMemberDetail',
	);

	public function __construct() {
		parent::__construct();
		$this->opus_api_url = $this->SYSTEM_INFO['opus_api_url'];
		$this->opus_secret_key = $this->SYSTEM_INFO['opus_secret'];
		$this->opus_operator_id = $this->SYSTEM_INFO['opus_operator_id'];
		$this->opus_site_code = $this->SYSTEM_INFO['opus_site_code'];
		$this->opus_product_code = $this->SYSTEM_INFO['opus_product_code'];
		$this->opus_min_transfer = $this->SYSTEM_INFO['opus_min_transfer'];
		$this->opus_max_transfer = $this->SYSTEM_INFO['opus_max_transfer'];
		$this->opus_currency = $this->SYSTEM_INFO['opus_currency'];
		$this->opus_language = $this->SYSTEM_INFO['opus_language'];
		$this->opus_member_type = $this->SYSTEM_INFO['opus_member_type'];
		$this->opus_cookie_domain = $this->SYSTEM_INFO['opus_cookie_domain'];
		$this->member_odds_type = $this->SYSTEM_INFO['member_odds_type'];
		$this->opus_game_url = $this->SYSTEM_INFO['opus_game_url'];
		$this->forward_sites = $this->getSystemInfo('forward_sites');
		$this->opus_mobile_game_url = $this->SYSTEM_INFO['opus_mobile_game_url'];
	}

	public function getPlatformCode() {
		return OPUS_SPORTSBOOK_API;
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
							"status_code" => '00',
							"status_text" => "OK",
							"currency" => $this->opus_currency,
							"member_id" => $login_name,
							"member_name" => $login_name,
							"language" => $this->opus_language,
							"balance" => 0,
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
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);
		return $url = $this->opus_api_url . "/" . $apiUri . ".API?" . $params_string;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		# 6 is duplicate
		$success = !empty($resultArr) && $resultArr['status_code'] == self::STATUS_SUCCESS || $resultArr['status_code'] == 6;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('OPUS_SPORTSBOOK got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
			"operator_id" => $this->opus_operator_id,
			"first_name" => $playerName,
			"user_name" => $gameUsername,
			"Language" => $this->opus_language,
			"odds_type" => $this->member_odds_type,
			"currency" => $this->opus_currency,
			"member_id" => $gameUsername,
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
			"operator_id" => $this->opus_operator_id,
			"user_name" => $gameUsername,
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
		if ($success && isset($resultArr['user_balance']) && $resultArr['user_balance'] !== null) {
			$result["balance"] = $this->gameAmountToDB(floatval($resultArr['user_balance']));
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $resultArr['user_balance']);
			if (!$playerId) {
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

		$reference_id = random_string('alpha');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
		);

		$params = array(
			"operator_id" => $this->opus_operator_id,
			"user_name" => $gameUsername,
			"trans_id" => $reference_id,
			"amount" => $this->dBtoGameAmount($amount),
			"currency" => $this->opus_currency,
			"direction" => self::CREDIT,
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

			$afterBalance = isset($resultArr['after_amount']) ? $resultArr['after_amount'] : null;

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
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$reference_id = random_string('alpha');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
		);

		$params = array(
			"operator_id" => $this->opus_operator_id,
			"user_name" => $gameUsername,
			"trans_id" => $reference_id,
			"amount" => $this->dBtoGameAmount($amount),
			"currency" => $this->opus_currency,
			"direction" => self::DEBIT,
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

			$afterBalance = isset($resultArr['after_amount']) ? $resultArr['after_amount'] : null;

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
	//===end withdrawFromGame=====================================================================================

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
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
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
		if($extra['is_mobile']) {
			$data = array("st" => $sessionToken,"lang" => $language);
			$url_params = http_build_query($data);
			$mobile_url = $this->opus_mobile_game_url.'?'.$url_params;
			return array('success' => TRUE, 'url' => $mobile_url);
		}
		return array('success' => TRUE, 'url' => $this->opus_game_url);

	}

	public function generateGotoUri($playerName, $extra) {
		return '/iframe_module/goto_opus/sportsbook/0/0/' . (isset($extra['gameMode']) ? $extra['gameMode'] : 'real') . '/' . $extra['language'] . (($extra['is_mobile'] == 1) ? '/true': '');

	}
	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	const START_PAGE = 0;
	public function syncOriginalGameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
		$startDate->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$params = array(
			"operator_id" => $this->opus_operator_id,
			"date_start" => $startDate->format('Y-m-d H:i:s'),
			"date_end" => $endDate->format('Y-m-d H:i:s'),
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$this->CI->load->model(array('opus_sportsbook_game_logs', 'external_system'));

		if ($success) {

			$gameRecords = isset($resultArr['bets']['row']) ? $resultArr['bets']['row'] : NULL;
			$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);

			if ($gameRecords) {

				array_walk($gameRecords, function (&$row, $index, $responseResultId) {
					$row = array(
						'trans_id' => $row['trans_id'],
						'member_id' => $row['member_id'],
						'operator_id' => $row['operator_id'],
						'site_code' => $row['site_code'],
						'league_id' => $row['league_id'],
						'home_id' => $row['home_id'],
						'away_id' => $row['away_id'],
						'match_datetime' => date('Y-m-d H:i:s', strtotime($row['match_datetime'])),
						'bet_type' => $row['bet_type'],
						'parlay_ref_no' => $row['parlay_ref_no'],
						'odds' => $row['odds'],
						'currency' => $row['currency'],
						'stake' => $row['stake'],
						'winlost_amount' => $row['winlost_amount'],
						'transaction_time' => date('Y-m-d H:i:s', strtotime($row['transaction_time'])),
						'ticket_status' => $row['ticket_status'],
						'version_key' => $row['version_key'],
						'winlost_datetime' => date('Y-m-d H:i:s', strtotime($row['winlost_datetime'])),
						'odds_type' => $row['odds_type'],
						'sports_type' => $row['sports_type'],
						'bet_team' => $row['bet_team'],
						'home_hdp' => $row['home_hdp'],
						'away_hdp' => $row['away_hdp'],
						'match_id' => $row['match_id'],
						'is_live' => $row['is_live'],
						'home_score' => $row['home_score'],
						'away_score' => $row['away_score'],
						'choicecode' => $row['choicecode'],
						'choicename' => $row['choicename'],
						'txn_type' => $row['txn_type'],
						'last_update' => date('Y-m-d H:i:s', strtotime($row['last_update'])),
						'leaguename' => $row['leaguename'],
						'homename' => $row['homename'],
						'awayname' => $row['awayname'],
						'sportname' => $row['sportname'],
						'oddsname' => $row['oddsname'],
						'bettypename' => $row['bettypename'],
						'winlost_status' => $row['winlost_status'],
						'uniqueid' => $row['trans_id'],
						'external_uniqueid' => $row['trans_id'],
						'response_result_id' => $responseResultId,
					);
				}, $responseResultId);

				$availableRows = $this->CI->opus_sportsbook_game_logs->getAvailableRows($gameRecords);

				$this->CI->utils->debug_log('availableRows', count($availableRows));

				array_walk($availableRows, array($this->CI->opus_sportsbook_game_logs, 'insertGameLogs'));

			}
		}

		return array($success, null);
	}

	private function getStringValueFromXml($xml, $key) {
		$value = (string) $xml[$key];
		if (empty($value) || $value == 'null') {
			$value = '';
		}
		return $value;
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'game_description_model', 'opus_sportsbook_game_logs'));

		$cnt = 0;

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		if ($result = $this->CI->opus_sportsbook_game_logs->getGameLogStatistics($dateTimeFrom, $dateTimeTo)) {
			foreach ($result as $opus_data) {

				$cnt++;

				list($game_description_id, $gameTypeId) = $this->CI->game_description_model->checkGameDesc(
					$this->getPlatformCode(),
					$opus_data->game_name,
					$opus_data->game_name,
					$opus_data->game_type_name
				);

				$player_id = $opus_data->player_id;
				$player = $this->CI->player_model->getPlayerById($player_id);
				$bet_amount = $this->gameAmountToDB($opus_data->bet_amount);
				$result_amount = $this->gameAmountToDB($opus_data->result_amount);
				// $result_amount = $this->gameAmountToDB($opus_data->result_amount) - $bet_amount;
				$has_both_side = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

				$this->syncGameLogs(
					$gameTypeId, # game_type_id
					$game_description_id, # game_description_id
					$opus_data->game_name, # game_code
					$opus_data->game_type_name, # game_type
					$opus_data->game_name, # game
					$player_id, # player_id
					$player->username, # player_username
					$bet_amount, # bet_amount
					$result_amount, # result_amount
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					$has_both_side, # has_both_side
					$opus_data->external_uniqueid, # external_uniqueid
					$opus_data->start_at, # start_at
					$opus_data->end_at, # end_at
					$opus_data->response_result_id, # response_result_id
					Game_logs::FLAG_GAME
				);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return array('success' => true, 'count' => $cnt);
	}

	// public function gameAmountToDB($amount) {
	// 	//only need 2
	// 	return round(floatval($amount), 2);
	// }

	private function getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('opus_sportsbook_game_logs');
		return $this->CI->opus_sportsbook_game_logs->getOpusGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start isPlayerExist=====================================================================================
	// public function isPlayerExist($playerName) {

	// }

	//===end isPlayerExist=====================================================================================
}

/*end of file*/