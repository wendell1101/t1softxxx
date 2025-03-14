<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_one88 extends Abstract_game_api {

	private $one88_merchant_id;
	private $one88_merchant_key;
	private $one88_api_url;
	private $one88_lang_code;
	private $one88_member_status;
	private $one88_currency_code;

	const URI_MAP = array(
		self::API_operatorLogin => 'ValidateMember',
		self::API_createPlayer => 'RegisterMember',
		self::API_login => 'Launch',
		self::API_logout => 'LogoutMember',
		self::API_queryPlayerBalance => 'GetMemberBalance',
		self::API_depositToGame => 'DepositFund',
		self::API_withdrawFromGame => 'WithdrawFund',
		self::API_syncGameRecords => 'GetSettledBetSummary',
		self::API_syncGameRecordsByPlayer => 'GetWagerHistory',
	);

	const ONE88_GAME_PROPERTY = array(
		'odds_type_id' => array('euro' => 0, 'hongkong' => 1, 'malay' => 2, 'indo' => 3),
		'lang_code' => array('english' => 'ENG', 'chinese_simplified' => 'CHS', 'chinese_traditional' => 'CHT'),
		'member_status' => array('active' => 501, 'inactive' => 502, 'suspend' => 504),
		'currency_code' => array(
			'algeria_dinar' => 'DZD', 'estonia_kroon' => 'EEK', 'south_korean_won' => 'KRW', 'vietnam' => 'VND',
			'eur' => 'EUR', 'gbp' => 'GBP', 'hkd' => 'HKD', 'idr' => 'IDR', 'myr' => 'MYR', 'php' => 'PHP',
			'rmb' => 'RMB', 'rp' => 'RP', 'sgd' => 'SGD', 'thb' => 'THB', 'twd' => 'TWD', 'usd' => 'USD',
		),
	);

	const DEFAULT_TIMEZONE = 'GMG+08:00';
	const ONE88_TIMEZONE = 'GMG-04:00';
	const API_RETURN_SUCCESS = '000';
	const API_RETURN_FAILED = '002';

	public function __construct() {
		parent::__construct();
		$this->one88_api_url = $this->getSystemInfo('url');
		$this->one88_odds_type_id = self::ONE88_GAME_PROPERTY['odds_type_id']['hongkong'];
		$this->one88_lang_code = self::ONE88_GAME_PROPERTY['lang_code']['english'];
		$this->one88_member_status = self::ONE88_GAME_PROPERTY['member_status']['active'];
		$this->one88_currency_code = self::ONE88_GAME_PROPERTY['currency_code']['rmb'];
		$this->one88_merchant_id = $this->getSystemInfo('account');
		$this->one88_merchant_key = $this->getSystemInfo('key');
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function callback($method, $decryptedXML) {
		$this->CI->utils->debug_log('Game_api_one88 (Callback): ', $decryptedXML);
		if ($method == 'ValidateMember') {
			// 3.2.1 XML Data  Symmetric key encryption will be used to encrypt the XML data before the sent across the wire.
			// For implementation algorithm, please refer to section 7.47.4  Encrypt/Decrypt library code.
			// Note that the key and initialization vector (IV) is identical in encrypting XML data.

			$this->CI->utils->debug_log('decryptedXML:', $decryptedXML);

			$xmlResult = $this->CI->utils->xmlToArray(simplexml_load_string($decryptedXML));
			$this->CI->utils->debug_log('method:', $method, ' ,xmlArr:', $xmlResult);

			$this->CI->load->model('game_provider_auth');
			$result = $this->CI->game_provider_auth->validateToken($xmlResult['Token']);

			if ($result) {
				$data = array(
					"ReturnCode" => self::API_RETURN_SUCCESS,
					"Description" => "Success",
					"LoginName" => $result->login_name,
					"CurrencyCode" => self::ONE88_GAME_PROPERTY['currency_code']['rmb'],
					"OddsTypeId" => $this->one88_odds_type_id,
					"LangCode" => self::ONE88_GAME_PROPERTY['lang_code']['chinese_simplified'],
					"TimeZone" => self::DEFAULT_TIMEZONE,
					"MemberStatus" => self::ONE88_GAME_PROPERTY['member_status']['active'],
				);

				$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='Login'></Request>");
				$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
				$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
				return $encryptedXML;
			} else {
				$data = array(
					"ReturnCode" => self::API_RETURN_FAILED,
					"Description" => "Failed",
				);

				$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='Login'></Request>");
				$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
				$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
				return $encryptedXML;
			}
		}
	}

	public function getPlatformCode() {
		return ONE88_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		return $url = $this->one88_api_url . "/" . $apiUri;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['ReturnCode'] == self::API_RETURN_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ONE88 got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function processResultForOperatorLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		return array($success, null);
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$data = array(
			"LoginName" => $playerName,
			"CurrencyCode" => $this->one88_currency_code,
			"OddsTypeId" => $this->one88_odds_type_id,
			"LangCode" => $this->one88_lang_code,
			"TimeZone" => self::ONE88_TIMEZONE,
			"MemberStatus" => $this->one88_member_status,
		);

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='RegisterMember'></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
		return $this->callApi(self::API_createPlayer, $encryptedXML, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultArr = $this->processEncryptedResult($params['resultText']);
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $responseResultArr, $playerName);
		return array($success, null);
	}

	private function processEncryptedResult($result) {
		$decodedXML = $this->CI->utils->aes128_cbc_decrypt($this->one88_merchant_key, base64_decode($result), $this->one88_merchant_key);
		$jsonResult = json_encode(simplexml_load_string($decodedXML));
		return json_decode($jsonResult, TRUE);
	}

	public function validate($xml) {
		libxml_use_internal_errors(true);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadXML($xml);

		$errors = libxml_get_errors();
		if (empty($errors)) {
			return true;
		}

		$error = $errors[0];
		if ($error->level < 3) {
			return true;
		}

		$lines = explode("r", $xml);
		$line = $lines[($error->line) - 1];

		$message = $error->message . ' at line ' . $error->line . ':
		' . htmlentities($line);

		return $message;
	}

	//===end createPlayer=====================================================================================

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

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$reference_no = random_string('numeric');
		//$playerName = 'testuser188A';
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'reference_no' => $reference_no,
		);

		$data = array(
			"LoginName" => $playerName,
			"Amount" => $amount,
			"ReferenceNo" => $reference_no,
		);

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='DepositFund'></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
		return $this->callApi(self::API_depositToGame, $encryptedXML, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultArr = $this->processEncryptedResult($params['resultText']);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$reference_no = $this->getVariableFromContext($params, 'reference_no');
		$success = $this->processResultBoolean($responseResultId, $responseResultArr, $playerName);
		// $result = array();
		$result = array('response_result_id'=>$responseResultId);

		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			$result["external_transaction_id"] = $responseResultArr['TransactionID'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["reference_no"] = $reference_no;
			$result["userNotFound"] = false;

			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = true;
		}

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		//$playerName = 'testuser188A';
		$reference_no = random_string('numeric');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'reference_no' => $reference_no,
		);

		$data = array(
			"LoginName" => $playerName,
			"Amount" => $amount,
			"ReferenceNo" => $reference_no,
		);

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='WithdrawFund'></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
		return $this->callApi(self::API_withdrawFromGame, $encryptedXML, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultArr = $this->processEncryptedResult($params['resultText']);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$reference_no = $this->getVariableFromContext($params, 'reference_no');
		$success = $this->processResultBoolean($responseResultId, $responseResultArr, $playerName);

		// $result = array();
		$result = array('response_result_id'=>$responseResultId);

		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			$result["external_transaction_id"] = $responseResultArr['TransactionID'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["reference_no"] = $reference_no;
			$result["userNotFound"] = false;

			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//withdrawal
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = true;
		}
		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array(true, null);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array(true, null);
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

	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);
		//$playerName = 'testuser188A';
		$data = array("LoginName" => $playerName);
		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='GetMemberBalance'></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
		return $this->callApi(self::API_queryPlayerBalance, $encryptedXML, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultArr = $this->processEncryptedResult($params['resultText']);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $responseResultArr, $playerName);
		$result = array();
		if ($success && isset($responseResultArr['Balance']) &&
			@$responseResultArr['Balance'] !== null) {
			$result["balance"] = floatval($responseResultArr['Balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$responseResultArr['Balance']);
			if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			} else {
				log_message('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		} else {
			$success = false;
		}
		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

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

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$dont_save_response_in_api = $this->getSystemInfo('dont_save_response_in_api');

		$startDate = new DateTime($startDate->format('Y-m-d'));
		$endDate = new DateTime($endDate->format('Y-m-d'));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'token' => $token,
			'syncId' => $syncId,
			'dont_save_response_in_api' => $dont_save_response_in_api,
		);

		$data = array(
			"Date" => $startDate->format('Y-m-d'),
		);

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='GetSettledBetSummary'></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		//var_dump($xmlData);
		$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
		return $this->callApi(self::API_syncGameRecords, $xmlData, $context);
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$responseResultArr = $this->processEncryptedResult($params['resultText']);
		// var_dump($responseResultArr);
		$this->CI->utils->debug_log('responseResultArr', $responseResultArr);

		$token = $this->getVariableFromContext($params, 'token');
		$memberCode = array('1', '2', '3');
		foreach ($memberCode as $key => $value) {
			$this->syncOne88GameLogs($token, $value);
		}
	}

	private function syncOne88GameLogs($token, $memberCode) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($startDate->format('Y-m-d'));
		$endDate = new DateTime($endDate->format('Y-m-d'));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOne88GameRecords',
		);

		// var_dump($startDate->format('Y-m-d H:i:s'));exit();
		$data = array(
			"MemberCode" => $memberCode,
			"Begin" => $startDate->format('Y-m-d H:i:s'),
			"End" => $endDate->format('Y-m-d H:i:s'),
		);

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><Request Method='GetWagerHistory'></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		$encryptedXML = $this->CI->utils->aes128_cbc_encrypt($this->one88_merchant_key, $xmlData, $this->one88_merchant_key);
		return $this->callApi(self::API_syncGameRecordsByPlayer, $xmlData, $context);
	}

	public function processResultForSyncOne88GameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$responseResultArr = $this->processEncryptedResult($params['resultText']);
		// var_dump($responseResultArr);
		$this->CI->utils->debug_log('responseResultArr', $responseResultArr);

		//load models
		$this->CI->load->model(array('one88_game_logs', 'external_system'));
		$success = $this->processResultBoolean($responseResultId, $responseResultArr);
		if ($success) {
			$gameRecords = $resultJson['data'];

			if ($gameRecords) {

				//filter availabe rows first
				$availableRows = $this->CI->one88_game_logs->getAvailableRows($gameRecords);

				list($availableRows, $maxRowId) = $this->CI->one88_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'maxRowId', $maxRowId);

				foreach ($availableRows as $row) {
					$this->copyRowToDB($row, $responseResultId);
				}
				if ($maxRowId) {
					$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
					$lastRowId = $maxRowId;
				} else {
					return array($success, null);
				}
			}
		}

		return array($success, null);

	}

	private function copyRowToDB($row, $responseResultId) {
		$result = array(
			'user_code' => $row['UserCode'],
			'wagers_no' => $row['WagerNo'],
			'date_created' => $row['DateCreated'],
			'member_ip_address' => $row['MemberIPAddress'],
			'odds_type_id' => $row['OddsTypeId'],
			'odds' => $row['Odds'],
			'handicap' => $row['Handicap'],
			'total_stakef' => $row['TotalStakeF'],
			'BetTypeName' => $row['TotalStakeF'],
			'period_name' => $row['PeriodName'],
			'sport_name' => $row['SportName'],
			'competition_name' => $row['CompetitionName'],
			'event_name' => $row['EventName'],
			'event_id' => $row['EventId'],
			'date_event' => $row['DateEvent'],
			'score_home' => $row['ScoreHome'],
			'score_away' => $row['ScoreAway'],
			'selection_name' => $row['SelectionName'],
			'bet_status' => $row['BetStatus'],
			'settlement_status' => $row['SettlementStatus'],
			'winloss_amount' => $row['WinLossAmount'],
			'void_reason' => $row['VoidReason'],
			'game_platform' => ONE88_API,
			'external_uniqueid' => $row['WagerNo'],
		);

		$this->CI->one88_game_logs->insertOne88GameLogs($result);
	}

	/**
	 * extract file name
	 *
	 * param xmlFileRecord string
	 *
	 * @return  void
	 */
	private function extractXMLRecord($folderName, $file, $playerName = null, $responseResultId = null) {

	}

	private function getStringValueFromXml($xml, $key) {
		$value = (string) $xml[$key];
		if (empty($value) || $value == 'null') {
			$value = '';
		}

		return $value;
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$result = $this->getONE88GameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		if ($result) {
			$this->CI->load->model(array('game_logs', 'player_model'));

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $one88data) {
				$player_id = $this->getPlayerIdInGameProviderAuth($one88data->username);
				if (!$player_id) {
					continue;
				}

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

				$gameDate = new \DateTime($one88data->date_created);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $one88data->bet_amount;

				$game_description_id = $one88data->game_description_id;
				$game_type_id = $one88data->game_type_id;
				$game_code = $one88data->game_code;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				$result_amount = $one88data->result_amount;

				$this->syncGameLogs($game_type_id, $game_description_id, $game_code,
					$game_type, $game, $player_id, $player_username,
					$this->gameAmountToDB($bet_amount), $result_amount, null, null, null, null,
					$one88data->external_id, $gameDateStr, $gameDateStr, $one88data->response_result_id);

				// $gameLogdata = array(
				// 	'bet_amount' => $this->gameAmountToDB($bet_amount),
				// 	'result_amount' => $result_amount,
				// 	'win_amount' => $result_amount > 0 ? $result_amount : 0,
				// 	'loss_amount' => $result_amount < 0 ? abs($result_amount) : 0,
				// 	'start_at' => $gameDateStr,
				// 	'end_at' => $gameDateStr,
				// 	'game_platform_id' => $this->getPlatformCode(),
				// 	'game_description_id' => $game_description_id,
				// 	'game_code' => $game_code,
				// 	'player_id' => $player_id,
				// 	'player_username' => $player_username,
				// 	'external_uniqueid' => $one88data->wagers_id,
				// 	'flag' => Game_logs::FLAG_GAME,
				// );

				// $this->CI->game_logs->syncToGameLogs($gameLogdata);
			}
		}
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	private function getONE88GameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('one88_game_logs');
		return $this->CI->one88_game_logs->getone88GameLogStatistics($dateTimeFrom, $dateTimeTo);
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