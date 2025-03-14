<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_onesgame extends Abstract_game_api {

	private $onesgame_operator_secret_key;
	private $onesgame_operator_id;
	private $onesgame_operator_prefix;
	private $onesgame_operator_password;
	private $onesgame_operator_ip;
	private $onesgame_operator_currency;
	private $onesgame_operator_url;

	const START_PAGE = 1;
	const ITEM_PER_PAGE = 500;
	const STATUS_SUCCESS = '200';

	const URI_MAP = array(
		self::API_createPlayer => 'CreatePlayer',
		self::API_login => 'PlayerLogin',
		self::API_logout => 'Logout',
		self::API_queryPlayerBalance => 'CheckBalance',
		self::API_depositToGame => 'Deposit',
		self::API_withdrawFromGame => 'Withdrawal',
		self::API_syncGameRecords => 'GetBetLogTranSubID',
		self::API_batchQueryPlayerBalance => 'CheckBalance',
		self::API_queryTransaction => 'CheckThirdPartyID',
	);

	const CLIENT_TYPE = array("web" => 0, "ios" => 1, "android" => 2);

	public function __construct() {
		parent::__construct();
		$this->onesgame_operator_secret_key = $this->getSystemInfo('onesgame_operator_secret_key');
		$this->onesgame_operator_id = $this->getSystemInfo('onesgame_operator_id');
		$this->onesgame_operator_prefix = $this->getSystemInfo('onesgame_operator_prefix');
		$this->onesgame_operator_password = $this->getSystemInfo('onesgame_operator_password');
		$this->onesgame_operator_ip = $ip = $this->CI->input->ip_address();//$this->getSystemInfo('onesgame_operator_ip');
		$this->onesgame_operator_currency = $this->getSystemInfo('onesgame_operator_currency');
		$this->onesgame_operator_production_url = $this->getSystemInfo('onesgame_operator_production_url');
		$this->onesgame_operator_uat_url = $this->getSystemInfo('onesgame_operator_uat_url');
		$this->onesgame_api_url = $this->onesgame_operator_production_url;

	}

	public function getPlatformCode() {
		return ONESGAME_API;
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		return $url = $this->onesgame_api_url . "/GameLobbyWs.svc/OPAPI/" . $apiUri;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['Status'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('1sGames got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	private function formatYMDHis($dateTimeStr) {
		$d = new Datetime($dateTimeStr);
		return $d->format('Y-m-d H:i:s');
	}

	private function getYmdHisForRequestDateTime() {
		return $this->formatYMDHis($this->serverTimeToGameTime(new DateTime()));
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
			"PlayerID" => $playerName,
			"PlayerDisplayName" => $playerName,
			"PlayerPassword" => $password,
			"PlayerCurrency" => $this->onesgame_operator_currency,
			"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
			"OperatorID" => $this->onesgame_operator_id,
			"OperatorPassword" => $this->onesgame_operator_password,
			"Ip" => $this->onesgame_operator_ip,
			"Signature" => md5(
				$playerName .
				$this->onesgame_operator_id .
				'CreatePlayer' .
				$this->getYmdHisForRequestDateTime() .
				$this->onesgame_operator_secret_key
			),
		);

		$xml_object = new SimpleXMLElement("<CreatePlayer></CreatePlayer>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		return $this->callApi(self::API_createPlayer, $xmlData, $context);
	}

	public function processResultForCreatePlayer($params) {
		// var_dump($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);

		return array($success, null);
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

		$thirdPartyTransacID = random_string();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $thirdPartyTransacID,
		);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$data = array(
			"PlayerID" => $playerName,
			"Amount" => $amount,
			"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
			"OperatorID" => $this->onesgame_operator_id,
			"OperatorPassword" => $this->onesgame_operator_password,
			"ThirdPartyTransacID" => $thirdPartyTransacID,
			"Signature" => md5(
				$playerName .
				$this->onesgame_operator_id .
				$amount .
				'Deposit' .
				$this->getYmdHisForRequestDateTime() .
				$this->onesgame_operator_secret_key
			),
		);

		$xml_object = new SimpleXMLElement("<Deposit></Deposit>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		return $this->callApi(self::API_depositToGame, $xmlData, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$responseResultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;

			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);
			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			// $ptrlt = $resultJson['result'];
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = $responseResultArr['TransacID']; // $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			//$result["userNotFound"] = false;

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$resultArr = $this->CI->utils->xmlToArray($resultXml);

			$error_code = @$resultArr['Status'];
			switch($error_code) {
				case '900404' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
				case '900405' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case '900407' :
					$result['reason_id']=self::REASON_INVALID_KEY;
					break;
				case '900409' :
					$result['reason_id']=self::REASON_DUPLICATE_TRANSFER;
					break;
				case '900500' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					break;
				case '900501' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_NETWORK_ERROR;
					break;
				case '900605' :
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
				case '900606' :
				case '900608' :
					$result['reason_id']=self::REASON_INVALID_TRANSFER_AMOUNT;
					break;
				case '900607' :
					$result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			//$result["userNotFound"] = true;
		}

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$thirdPartyTransacID = random_string();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $thirdPartyTransacID,
		);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$data = array(
			"PlayerID" => $playerName,
			"Amount" => $amount,
			"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
			"OperatorID" => $this->onesgame_operator_id,
			"OperatorPassword" => $this->onesgame_operator_password,
			"ThirdPartyTransacID" => $thirdPartyTransacID,
			"Signature" => md5(
				$playerName .
				$this->onesgame_operator_id .
				$amount .
				'Withdrawal' .
				$this->getYmdHisForRequestDateTime() .
				$this->onesgame_operator_secret_key
			),
		);

		$xml_object = new SimpleXMLElement("<Withdrawal></Withdrawal>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		return $this->callApi(self::API_withdrawFromGame, $xmlData, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$responseResultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);

		$result = array(
				'response_result_id' => $responseResultId,
				'external_transaction_id'=>$external_transaction_id,
				'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
				'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;

			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = $playerBalance['balance'];

			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = $responseResultArr['TransacID']; // $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			//$result["userNotFound"] = false;

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdrawal
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeSubWalletToMainWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$resultArr = $this->CI->utils->xmlToArray($resultXml);

			$error_code = @$resultArr['Status'];
			switch($error_code) {
				case '900404' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
				case '900405' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case '900407' :
					$result['reason_id']=self::REASON_INVALID_KEY;
					break;
				case '900409' :
					$result['reason_id']=self::REASON_DUPLICATE_TRANSFER;
					break;
				case '900500' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					break;
				case '900501' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_NETWORK_ERROR;
					break;
				case '900605' :
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
				case '900606' :
				case '900608' :
					$result['reason_id']=self::REASON_INVALID_TRANSFER_AMOUNT;
					break;
				case '900607' :
					$result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			//$result["userNotFound"] = true;
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
		$playerInfo = $this->getPlayerInfoByUsername($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		if (empty($playerName)) {
			$this->CI->load->library(array('salt'));
			$password = $this->CI->salt->decrypt($playerInfo->password, $this->CI->config->item('DESKEY_OG'));
			//try create player
			$rlt = $this->createPlayer($playerInfo->username, $playerInfo->playerId, $password);
			if (!$rlt['success']) {
				//failed
				return $rlt;
			} else {
				$this->updateRegisterFlag($playerInfo->playerId, Abstract_game_api::FLAG_TRUE);
				$playerName = $this->getGameUsernameByPlayerUsername($playerInfo->username);

			}
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$data = array(
			"PlayerID" => $playerName,
			"OperatorID" => $this->onesgame_operator_id,
			"OperatorPassword" => $this->onesgame_operator_password,
			"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
			"Signature" => md5(
				$playerName .
				$this->onesgame_operator_id .
				'CheckBalance' .
				$this->getYmdHisForRequestDateTime() .
				$this->onesgame_operator_secret_key
			),
		);

		$xml_object = new SimpleXMLElement("<CheckBalance></CheckBalance>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		return $this->callApi(self::API_queryPlayerBalance, $xmlData, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$responseResultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);

		$result = array();
		if ($success && isset($responseResultArr['PlayerBalance']) &&
			@$responseResultArr['PlayerBalance'] !== null) {
			$result["balance"] = floatval($responseResultArr['PlayerBalance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$responseResultArr['PlayerBalance']);
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

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'external_transaction_id' => $transactionId,
		);

		$data = array(
			"OperatorID" => $this->onesgame_operator_id,
			"OperatorPassword" => $this->onesgame_operator_password,
			"ThirdPartyID" => $transactionId,
			"Features" => 'Deposit',
			"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
			"Signature" => md5(
				$this->onesgame_operator_id .
				'CheckThirdPartyID' .
				$this->getYmdHisForRequestDateTime() .
				$this->onesgame_operator_secret_key
			),
		);

		$xml_object = new SimpleXMLElement("<CheckThirdPartyID></CheckThirdPartyID>");


		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);

		return $this->callApi(self::API_queryTransaction, $xmlData, $context);
	}

	public function processResultForQueryTransaction( $params ){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$responseResultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $this->CI->utils->xmlToArray($resultXml), $playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$responseResultArr['Status'];
			switch($error_code) {
				case '900404' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
				case '900405' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case '900407' :
					$result['reason_id']=self::REASON_INVALID_KEY;
					break;
				case '900409' :
					$result['reason_id']=self::REASON_DUPLICATE_TRANSFER;
					break;
				case '900500' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					break;
				case '900501' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_NETWORK_ERROR;
					break;
				case '900605' :
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
				case '900606' :
				case '900608' :
					$result['reason_id']=self::REASON_INVALID_TRANSFER_AMOUNT;
					break;
				case '900607' :
					$result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
					break;
			}
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			//$result["userNotFound"] = true;
		}

		return array($success, $result);
	}
	//===end queryTransaction=====================================================================================

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $extra) {
		$result = array("success" => false, "blocked" => false, 'url' => null, 'message' => null);

		$player_id = $this->getPlayerIdInPlayer($playerName);
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $this->isBlocked($playerName);

		if ($blocked) {
			$result['success'] = false;
			$result['blocked'] = true;
			$result['message'] = 'goto_game.blocked';
		} else {
			$this->CI->load->model(array('game_provider_auth'));
			$loginInfo = $this->CI->game_provider_auth->getLoginInfoByPlayerId($player_id, $this->getPlatformCode());
			if ($loginInfo) {
				$gameName = $extra['gameName'];
				$password = $extra['password'];

				$playerName = $this->getGameUsernameByPlayerUsername($playerName);

				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForLogin',
					'playerName' => $playerName,
				);

				$data = array(
					"PlayerID" => $playerName,
					"PlayerPassword" => $password,
					"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
					"OperatorID" => $this->onesgame_operator_id,
					"OperatorPassword" => $this->onesgame_operator_password,
					"Ip" => $this->onesgame_operator_ip,
					"GameID" => $gameName,
					"ClientType" => self::CLIENT_TYPE['web'],
					"Lang"=> $extra['language'],
					"Signature" => md5(
						$playerName .
						$this->onesgame_operator_id .
						'PlayerLogin' .
						$this->getYmdHisForRequestDateTime() .
						$this->onesgame_operator_secret_key
					),
				);
				$xml_object = new SimpleXMLElement("<PlayerLogin></PlayerLogin>");
				$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
				$resultArr = $this->callApi(self::API_login, $xmlData, $context);

				$result['url'] = $resultArr['Url'];
				$result['success'] = true;
			} else {
				$result['success'] = false;
				$result['message'] = 'goto_game.error';
			}
		}

		return $result;
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = new SimpleXMLElement(str_replace('&', 'amp', $params['resultText']));
		$responseResultArr = $this->CI->utils->xmlToArray($resultXml);
		$this->CI->utils->debug_log($responseResultArr);
		if (array_key_exists('Url', $responseResultArr)) {
			$responseResultArr['Url'] = str_replace('amp', '&', $responseResultArr['Url']);
		}
		$this->CI->utils->debug_log($responseResultArr);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $responseResultArr, $playerName);
		return array($success, $responseResultArr);
	}
	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
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

		$page = self::START_PAGE;
		$done = false;
		$success = true;

		$max_loops = 10;
		$current_loop = 0;
	
		while (!$done) {
			$current_loop++;
			$data = array(
				"OperatorID" => $this->onesgame_operator_id,
				"OperatorPassword" => $this->onesgame_operator_password,
				"RequestDateTime" => $this->getYmdHisForRequestDateTime(),
				"DateFrom" => $startDate->format('Y-m-d H:i:s'),
				"DateTo" => $endDate->format('Y-m-d H:i:s'),
				"Page" => $page,
				"RecordsPerPage" => self::ITEM_PER_PAGE,
				"Signature" => md5(
					$this->onesgame_operator_id .
					'GetBetLogTranSubID' .
					$this->getYmdHisForRequestDateTime() .
					$this->onesgame_operator_secret_key
				),
			);

			$xml_object = new SimpleXMLElement("<GetBetLogTranSubID></GetBetLogTranSubID>");
			$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);

			$rlt = $this->callApi(self::API_syncGameRecords, $xmlData, $context);

			$done = true;
			if ($rlt) {
				$success = $rlt['success'];
			}
			if ($rlt && $rlt['success']) {
				$page = $rlt['currentPage'];
				$total_pages = $rlt['totalPages'];
				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
				$page += 1;
			}

			# Prevent the case that max loop isn't enough to read total pages
			if(!empty($total_pages) && $total_pages > $max_loops) {
				$max_loops = $total_pages + 1;
			}

			# Define fail-safe exit condition
			if(!$success) {
				$this->CI->utils->error_log('Sync game log failed');
				break;
			}
			if($current_loop > $max_loops) {
				$this->CI->utils->error_log('Max loop reached', $current_loop, $max_loops);
				break;
			}
		}

		return array('success' => $success);
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$responseResultArr = $this->CI->utils->xmlToArray($resultXml);
		$betResultJson = json_encode($resultXml->Bets);
		$resultBetsArr = json_decode($betResultJson, true);
		$this->CI->utils->debug_log('CurrentPage: ', $responseResultArr['CurrentPage'], 'TotalPages: ', $responseResultArr['TotalPages']);
		$this->CI->utils->debug_log('resultBetsArr: ', $resultBetsArr);

		// load models
		$this->CI->load->model(array('onesgame_game_logs', 'external_system'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $responseResultArr);
		if ($success) {
			$gameRecords = $resultBetsArr['Result'];
			$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);
			if ($gameRecords) {
				//filter availabe rows first
				$availableRows = $this->CI->onesgame_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));
				if (count($availableRows) == 1) {
					$record = reset($availableRows);
					$onesGameData = array(
						'player_id' => $record['PlayerID'],
						'player_currency' => $record['PlayerCurrency'],
						'game_id' => $record['GameID'],
						'game_name' => $record['GameName'],
						'game_category' => $record['GameCategory'],
						'tran_id' => $record['TranID'],
						'total_amount' => $record['TotalAmount'],
						'bet_amount' => $record['BetAmount'],
						'jackpot_contribution' => $record['JackpotContribution'],
						'win_amount' => $record['WinAmount'],
						'game_date' => $record['GameDate'],
						'platform' => $record['Platform'],
						'game_platform' => ONESGAME_API,
						'external_uniqueid' => $record['TranID'],
						'response_result_id' => $responseResultId,
					);
					$this->CI->onesgame_game_logs->insertOnesgameGameLogs($onesGameData);
				} else {
					foreach ($availableRows as $record) {
						//onesgame_game_logs data
						$onesGameData = array(
							'player_id' => $record['PlayerID'],
							'player_currency' => $record['PlayerCurrency'],
							'game_id' => $record['GameID'],
							'game_name' => $record['GameName'],
							'game_category' => $record['GameCategory'],
							'tran_id' => $record['TranID'],
							'total_amount' => $record['TotalAmount'],
							'bet_amount' => $record['BetAmount'],
							'jackpot_contribution' => $record['JackpotContribution'],
							'win_amount' => $record['WinAmount'],
							'game_date' => $record['GameDate'],
							'platform' => $record['Platform'],
							'game_platform' => ONESGAME_API,
							'external_uniqueid' => $record['TranID'],
							'response_result_id' => $responseResultId,
						);

						$this->CI->onesgame_game_logs->insertOnesgameGameLogs($onesGameData);
					}
				}
			}

			$result['currentPage'] = $responseResultArr['CurrentPage'];
			$result['totalPages'] = $responseResultArr['TotalPages'];
		}

		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$rlt = array('success' => true);
		$result = $this->getOnesGameGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$cnt = 0;
		if ($result) {

			$this->CI->load->model(array('game_logs', 'player_model'));

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $onesGameData) {

				$player_id = $this->getPlayerIdInGameProviderAuth($onesGameData->player_id);

				if (!$player_id) {
					continue;
				}
				# FREE SPIN
				if ($onesGameData->total_amount == 0 && $onesGameData->win_amount == 0) {
					continue;
				}

				$cnt++;

				$player = $this->CI->player_model->getPlayerById($player_id);

				$gameDate = new \DateTime($onesGameData->game_date);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $this->gameAmountToDB($onesGameData->total_amount);

				$game_type_id = $onesGameData->game_type_id;
				$game_description_id = $onesGameData->game_description_id;

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($onesGameData, $unknownGame);

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				$result_amount = $this->gameAmountToDB($onesGameData->win_amount - $onesGameData->total_amount);

				$this->syncGameLogs(
					$game_type_id, 
					$game_description_id, 
					$onesGameData->game_code,
					$game_type_id, 
					$onesGameData->game_id, 
					$player_id, 
					$player->username,
					$bet_amount, 
					$result_amount, 
					null,// $onesGameData->win_amount, 
					null, 
					null, 
					0,
					$onesGameData->tran_id, 
					$gameDateStr, 
					$gameDateStr, 
					$onesGameData->response_result_id);
			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$externalGameId = $row->game;
		$extra = array('game_code' => $row->game_id . '_' . $row->response_result_id,
			'moduleid' => $row->game_id, 'clientid' => $row->response_result_id);
		return $this->processUnknownGame(
			$row->game_description_id, $row->game_type_id,
			$row->game_id, $row->game_id, $externalGameId, $extra,
			$unknownGame);
	}

	private function getOnesGameGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('onesgame_game_logs');
		return $this->CI->onesgame_game_logs->getOnesGameGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start isPlayerExist=====================================================================================
	// public function isPlayerExist($playerName) {
	// 	$result = $this->queryPlayerBalance($playerName);
	// 	$result['exists'] = $result['success'];
	// 	return $result;
	// }
	//===end isPlayerExist=====================================================================================

	/**
	 * game time + 12 = server time
	 *
	 */
	public function getGameTimeToServerTime() {
		return '+12 hours';
	}

	public function getServerTimeToGameTime() {
		return '-12 hours';
	}

	//===start batchQueryPlayerBalance=====================================================================================
	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return $this->returnUnimplemented();
	}

	//===end batchQueryPlayerBalance=====================================================================================

}

/*end of file*/
