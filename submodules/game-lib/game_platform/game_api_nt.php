<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_nt extends Abstract_game_api {

	// protected $ci;
	private $api_url;
	private $game_url;
	private $common_params;
	private $default_software;
	private $default_currency;
	private $default_language;
	private $default_group_id;
	private $conversion_rate;

	// const CURRENCIES = array(
	// 	'CR' => 0,
	// 	'CNY' => 8,
	// );

	const NO_BALANCE = 0;
	const DONOT_RESET_BALANCE = 0;

	const ERROR_CODE_NOT_FOUND_PLAYER = '5';

	const URI_MAP = array(
		self::API_queryPlayerInfo => 'get_balance',
		self::API_queryPlayerBalance => 'get_balance',
		// self::API_checkLoginStatus => '',
		// self::API_changePassword => '',
		self::API_createPlayer => 'open_session',
		self::API_login => 'open_session',
		self::API_logout => 'close_session',
		self::API_isPlayerExist => 'get_balance',
		self::API_depositToGame => 'change_balance',
		self::API_withdrawFromGame => 'change_balance',
		// self::API_updatePlayerInfo => '',
		// self::API_queryTransaction => '',
		self::API_syncGameRecords => 'get_group_stat',
		self::API_totalBettingAmount => 'get_report_by_user',
	);

	public function getPlatformCode() {
		return NT_API;
	}

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->game_url = $this->getSystemInfo('nt_game_url');
		$this->default_software = $this->getSystemInfo('nt_default_software');
		$this->default_group_id = $this->getSystemInfo('nt_default_group_id');
		$this->default_currency = $this->getSystemInfo('nt_default_currency');
		$this->default_language = $this->getSystemInfo('nt_default_language');
		$this->conversion_rate = floatval($this->getSystemInfo('nt_conversion_rate'));
		$this->common_params = array(
			'token' => $this->getSystemInfo('key'),
			'secret_key' => $this->getSystemInfo('secret'),
			'format' => $this->getSystemInfo('nt_format'),
		);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson) && !array_key_exists('error', $resultJson);
		// if ($this->CI->utils->notEmptyInArray('errorcode', $resultJson)) {
		// 	$this->setResponseResultToError($responseResultId);
		// 	$success = false;
		// }
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('NT got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

	// protected function convertUsernameToGame($username) {

	// 	return
	// }
	//
	public function dbAmountToGame($amount) {
		return round($amount * $this->conversion_rate);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval(floatval($amount) / $this->conversion_rate), 2);
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

		return $this->callApi(self::API_createPlayer, array("group_id" => $this->default_group_id,
			"user_id" => $playerName, 'software' => $this->default_software, 'balance' => 0,
		), $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if ($success) {
			//save key to password
			$this->updatePasswordForPlayer($playerId, $resultJson['key']);
		}
		return array($success, null);
	}

	//===end createPlayer=====================================================================================

	//===start isPlayerExist======================================================================================
	public function isPlayerExist($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

		return $callResult = $this->callApi(self::API_isPlayerExist, array(
			"user_id" => $playerName, "group_id" => $this->default_group_id, 'software' => $this->default_software),
			$context);
	}
	public function processResultForIsPlayerExist($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = true;
		$result = array('exists' => true);
		// $this->CI->utils->debug_log('resultJson:', $resultJson);
		if ($this->processResultBoolean($responseResultId, $resultJson, $playerName)) {
			$success = true;
		} else {
			$success = true;
			$result["exists"] = false;
		}

		return array($success, $result);
	}
	//===end isPlayerExist========================================================================================

	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerInfo',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerInfo, array(
			"user_id" => $playerName, "group_id" => $this->default_group_id, 'software' => $this->default_software),
			$context);
	}
	public function processResultForQueryPlayerInfo($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array();
		if ($success) {
			// $ptrlt = $resultJson['result'];
			$playerInfo = array(
				'playerName' => $resultJson['user_id'],
				'balance' => $resultJson['balance'],
				'blocked' => $this->isBlockedUsernameInDB($playerName),
			);
			$result["playerInfo"] = $playerInfo;
		}

		return array($success, $result);
	}
	//===end queryPlayerInfo=====================================================================================
	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerBalance, array(
			"user_id" => $playerName, "group_id" => $this->default_group_id, 'software' => $this->default_software),
			$context);
	}
	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array();
		if ($success && isset($resultJson['balance']) && @$resultJson['balance'] !== null) {
			$result["balance"] = $this->gameAmountToDB($resultJson['balance']);
			// $result["balance"] = floatval($resultJson['balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				'balance', @$resultJson['balance']);
			if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================
	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $externaltranid = 'DEP' . random_string('unique');
		$transfer_secure_id=$this->generateTransferId($transfer_secure_id);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'transfer_secure_id' => $transfer_secure_id,
		);

		$rlt = $this->callApi(self::API_depositToGame, array(
			'group_id' => $this->default_group_id,
			'user_id' => $playerName,
			'software' => $this->default_software,
			'amount' => $this->dbAmountToGame($amount),
		), $context);

		if (!$rlt['success'] && $rlt["userNotFound"]) {
			//if failed, create user
			$playerId = $this->getPlayerIdInPlayer($playerUsername);
			$rlt = $this->createPlayer($playerUsername, $playerId, null);
			if ($rlt['success']) {
				//do it again
				$rlt = $this->callApi(self::API_depositToGame, array(
					'group_id' => $this->default_group_id,
					'user_id' => $playerName,
					'software' => $this->default_software,
					'amount' => $this->dbAmountToGame($amount),
				), $context);
			}
		}

		return $rlt;
	}
	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id=$this->getVariableFromContext($params, 'transfer_secure_id');;

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		// $result = array();
		$result = [];

		if ($success) {
			//for sub wallet
			$afterBalance = $this->gameAmountToDB($resultJson['balance']);

			$external_transaction_id= !empty($resultJson['transactionID']) ? $resultJson['transactionID'] : $external_transaction_id;

			// $ptrlt = $resultJson['result'];
			//external_transaction_id means game api system transaction id , not our
			$result["currentplayerbalance"] = $afterBalance;
			$result["userNotFound"] = false;
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeMainWalletToSubWallet());
			// 	//update sub wallet
			// 	// $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName);
			// 	//should update database
			// 	// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = @$resultJson['error'] == self::ERROR_CODE_NOT_FOUND_PLAYER;
		}

		$result["external_transaction_id"] = $external_transaction_id;
		$result["after_balance"] = $afterBalance;
		$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}
	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$transfer_secure_id=$this->generateTransferId($transfer_secure_id);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'transfer_secure_id' => $transfer_secure_id,
			'amount' => -$amount,
			'transfer_secure_id' => $transfer_secure_id,
		);

		$rlt = $this->callApi(self::API_withdrawFromGame, array(
			'group_id' => $this->default_group_id,
			'user_id' => $playerName,
			'software' => $this->default_software,
			'amount' => -$this->dbAmountToGame($amount),
		), $context);

		if (!$rlt['success'] && isset($rlt["userNotFound"]) && $rlt["userNotFound"]) {
			//if failed, create user
			$playerId = $this->getPlayerIdInPlayer($playerUsername);
			$rlt = $this->createPlayer($playerUsername, $playerId, null);
			if ($rlt['success']) {
				//do it again
				$rlt = $this->callApi(self::API_withdrawFromGame, array(
					'group_id' => $this->default_group_id,
					'user_id' => $playerName,
					'software' => $this->default_software,
					'amount' => -$this->dbAmountToGame($amount),
				), $context);
			}
		}

		return $rlt;

		// return $this->callApi(self::API_withdrawFromGame, array("playerName" => $playerName,
		// 	"amount" => $amount, "adminName" => $this->getApiAdminName(),
		// 	"externaltranid" => $externaltranid),
		// 	$context);
	}

	public function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id=$this->getVariableFromContext($params, 'transfer_secure_id');;

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		// $result = array();
		$result = [];

		if ($success) {
			$afterBalance = $this->gameAmountToDB($resultJson['balance']);
			$external_transaction_id= !empty($resultJson['transactionID']) ? $resultJson['transactionID'] : $external_transaction_id;
			// $ptrlt = $resultJson['result'];
			//external_transaction_id means game api system transaction id , not our
			// $result["external_transaction_id"] = null; //$ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			$result["userNotFound"] = false;
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//withdrawal
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeSubWalletToMainWallet());

			// 	//should update database
			// 	// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = @$resultJson['error'] == self::ERROR_CODE_NOT_FOUND_PLAYER;
		}

		$result["external_transaction_id"] = $external_transaction_id;
		$result["after_balance"] = $afterBalance;
		$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}
	//===end withdrawFromGame=====================================================================================

	//===start blockPlayer=====================================================================================
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => true);
	}
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array('success' => true);
	}
	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}
	//===end blockPlayer=====================================================================================

	// GAME API INTERFACE

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_login, array(
			'group_id' => $this->default_group_id,
			'user_id' => $playerName,
			'software' => $this->default_software,
			'balance' => self::NO_BALANCE,
		), $context);
		// return $result['key'];
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$rlt = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if ($success) {
			//save key to password
			$this->updatePasswordByPlayerName($playerName, $resultJson['key']);
			$rlt['key'] = $resultJson['key'];
		}
		return array($success, $rlt);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_logout, array(
			'group_id' => $this->default_group_id,
			'user_id' => $playerName,
			'software' => $this->default_software,
			'reset_balance' => self::DONOT_RESET_BALANCE,
		), $context);
		// $result['balance'] = floatval($result['balance']) / $this->conversion_rate;
		// return $result;
	}
	public function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$rlt = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if ($success) {
			//don't save key,save key to password
			// $this->updatePasswordByPlayerName($playerName, $resultJson['key']);
			// $rlt['key'] = $resultJson['key'];
		}
		return array($success, null);
	}
	//===end logout=====================================================================================
	//===start totalBettingAmount=====================================================================================
	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		$gameBettingRecord = parent::getGameTotalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo);
		if ($gameBettingRecord != null) {
			if ($gameBettingRecord['bettingAmount']) {
				$result['bettingAmount'] = $gameBettingRecord['bettingAmount'];
			} else {
				$result['bettingAmount'] = 0;
			}
		}
		return array("success" => true, "bettingAmount" => $result['bettingAmount']);
	}
	//===end totalBettingAmount=====================================================================================

	// public function queryPlayerBalance($user_id) {
	// 	if (!$this->isPlayerExist($user_id)) {
	// 		return 0;
	// 	}

	// 	$result = $this->callApi(self::API_queryPlayerBalance, array(
	// 		'group_id' => $this->default_group_id,
	// 		'user_id' => $user_id,
	// 		'software' => $this->default_software,
	// 	));
	// 	$result['balance'] = $result['balance'] / $this->conversion_rate;
	// 	return $result['balance'];
	// }

	// public function depositToGame($user_id, $amount) {
	// 	if (!$this->isPlayerExist($user_id)) {
	// 		return 0;
	// 	}
	// 	$this->CI->load->model(array('wallet_model'));

	// 	$this->CI->db->trans_start();
	// 	if ($this->CI->wallet_model->incMainWallet($user_id, (0 - $amount))) {
	// 		$result = $this->callApi(self::API_depositToGame, array(
	// 			'group_id' => $this->default_group_id,
	// 			'user_id' => $user_id,
	// 			'software' => $this->default_software,
	// 			'amount' => $amount * $this->conversion_rate,
	// 		));
	// 		$result['balance'] = $result['balance'] / $this->conversion_rate;
	// 		$this->CI->wallet_model->syncSubWallet($user_id, NT_API, $result['balance']);
	// 		$this->CI->db->trans_complete();
	// 		return $result;
	// 	} else {
	// 		return false;
	// 	}

	// }

	// public function withdrawFromGame($user_id, $amount) {
	// 	if (!$this->isPlayerExist($user_id)) {
	// 		return 0;
	// 	}
	// 	$this->CI->load->model(array('wallet_model'));

	// 	$this->CI->db->trans_start();
	// 	if ($this->CI->wallet_model->incMainWallet($user_id, $amount)) {
	// 		$result = $this->callApi(self::API_withdrawFromGame, array(
	// 			'group_id' => $this->default_group_id,
	// 			'user_id' => $user_id,
	// 			'software' => $this->default_software,
	// 			'amount' => (0 - $amount) * $this->conversion_rate,
	// 		));
	// 		$result['balance'] = $result['balance'] / $this->conversion_rate;
	// 		$this->CI->wallet_model->syncSubWallet($user_id, NT_API, $result['balance']);
	// 		$this->CI->db->trans_complete();
	// 		return $result;
	// 	} else {
	// 		return false;
	// 	}

	// }

	// public function totalBettingAmount($user_id, $date_from = null, $date_to = null) {

	// 	$date_from = $date_from ?: 0;
	// 	$date_to = $date_to ?: time();

	// 	$result = $this->callApi(self::API_totalBettingAmount, [
	// 		'group_id' => $this->default_group_id,
	// 		'software' => $this->default_software,
	// 		'date_from' => date('Y-m-d', $date_from),
	// 		'h_from' => date('H', $date_from),
	// 		'm_from' => date('i', $date_from),
	// 		'date_to' => date('Y-m-d', $date_to),
	// 		'h_to' => date('H', $date_to),
	// 		'm_to' => date('i', $date_to),
	// 	]);

	// 	foreach ($result['report'] as $record) {
	// 		if ($record[0] == $user_id) {
	// 			return $record[1] / $this->conversion_rate;
	// 		}
	// 	}

	// 	return 0;
	// }

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query(array_merge($this->common_params, $params));
		$url = $this->api_url . "/" . $apiUri . "?" . $params_string;
		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		// $resultJson = json_decode($resultText, true);
		// if ($apiName == self::API_syncGameRecords) {
		// 	return $this->processResultForSyncGameRecords($apiName, $params, $responseResultId, $resultJson);
		// } else if (isset($resultJson['error'])) {
		// 	return array(false, $resultJson);
		// } else {
		// 	return array(true, $resultJson);
		// }
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
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

	public function queryTransaction($transactionId, $extra) {
		//http: //api5.totemcasino.biz:8383/api/get_operations_report?user_id=ogrhaicese08&bo=9de4b39b9f2145ce40518772b8ccf8ca&format=json&group_id=771&date_from=2015-10-19&h_from=16&m_from=00&s_from=00&date_to=2015-10-22&h_to=15&m_to=59&s_to=59
		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		// $result = $this->get_group_stat(0);
		// throw new Exception("Function not implemented", 1);

		//merge ag_game_logs to game_logs, map fields
		//check duplicate record

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$playerName = parent::getValueFromSyncInfo($token, 'playerName');

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// if (!$dateTimeTo) {
		// 	$dateTimeTo = new DateTime();
		// }
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$rlt = array('success' => true);
		$result = $this->getNTGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$this->CI->utils->debug_log('getNTGameLogStatistics', count($result));

		$unknownGame = $this->getUnknownGame();
		$cnt = 0;
		if ($result) {
			$this->CI->load->model(array('game_logs', 'player_model'));
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $ntdata) {

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($ntdata, $unknownGame, $gameDescIdMap);

				if (empty($game_description_id)) {
					$this->CI->utils->debug_log('empty game_description_id , nt_game_logs.id=', $ntdata->id);
					continue;
				}

				$player_id = $this->getPlayerIdInGameProviderAuth($ntdata->username);
				if (!$player_id) {
					continue;
				}
				# FREE SPIN
				if ($ntdata->betting_amount == 0 && $ntdata->win_amount == 0) {
					continue;
				}

				$cnt++;

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

				$gameDate = new \DateTime($ntdata->time);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $this->gameAmountToDB($ntdata->betting_amount * $ntdata->lines * $ntdata->multiplier);

				// $game_type_id = $ntdata->game_type_id;
				// $game_description_id = $ntdata->game_description_id;

				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }

				$result_amount = $this->gameAmountToDB($ntdata->win_amount) - $bet_amount;

				$this->syncGameLogs($game_type_id, $game_description_id, $ntdata->game_code,
					$game_type_id, $ntdata->game_id, $player_id, $player_username,
					$bet_amount, $result_amount, null, null, $this->gameAmountToDB($ntdata->after_balance), 0,
					$ntdata->log_info_id, $gameDateStr, $gameDateStr, $ntdata->response_result_id);

				// $gldata = array(
				// 	'bet_amount' => $this->gameAmountToDB($bet_amount),
				// 	'after_balance' => $this->gameAmountToDB($ntdata->after_balance),
				// 	'result_amount' => $result_amount,
				// 	'win_amount' => $result_amount > 0 ? $result_amount : 0,
				// 	'loss_amount' => $result_amount < 0 ? abs($result_amount) : 0,
				// 	'start_at' => $gameDateStr,
				// 	'end_at' => $gameDateStr,
				// 	'game_platform_id' => $this->getPlatformCode(),
				// 	'game_description_id' => $game_description_id,
				// 	'game_type_id' => $game_type_id,
				// 	'game' => $ntdata->game_id,
				// 	'game_code' => $ntdata->game_code,
				// 	'player_id' => $player_id,
				// 	'player_username' => $player_username,
				// 	'external_uniqueid' => $ntdata->log_info_id,
				// 	'flag' => Game_logs::FLAG_GAME,
				// );

				// $this->CI->game_logs->syncToGameLogs($gldata);
			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
	}

	private function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}
		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		$externalGameId = $row->gameshortcode;
		$extra = array('game_code' => $row->gameshortcode);
		if (empty($game_description_id)) {
			//search game_description_id by code
			if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
				$game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
				$game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
			}
		}

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$row->gamename, $row->gametype, $externalGameId, $extra,
			$unknownGame);
	}

	// function convertGameLogsToSubWallet($token) {

	// 	$playerName = $this->getValueFromSyncInfo($token, 'playerName');
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);

	// 	$playerId = $this->getPlayerIdInGameProviderAuth($playerName);

	// 	// $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
	// 	// $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

	// 	// $dateTimeFrom, $dateTimeTo, $gamePlatformId, $playerId = null
	// 	// $apiArray = $this->utils->getApiListByBalanceInGameLog();

	// 	// if (!empty($apiArray)) {

	// 	$gamePlatformId = $this->getPlatformCode();
	// 	$this->CI->load->model(array('game_logs'));
	// 	// }
	// 	$this->CI->game_logs->convertGameLogsToSubWallet($gamePlatformId, $playerId);
	// }

	// const DEFAULT_DATETIME_ADJUST = '-20 minutes';
	const START_PAGE = 0;

	public function syncOriginalGameLogs($token = null) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			// 'dont_save_response_in_api' => $this->getConfig('dont_save_response_in_api'),
			'syncId' => $syncId,
			// 'playerName' => $playerName,
		);

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->convertServerTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->convertServerTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		// if (empty($startDate)) {
		// 	$startDate = new DateTime();
		// 	$startDate->modify('-1 hour');
		// }
		// if (empty($endDate)) {
		// 	$endDate = new DateTime();
		// }
		$startDate->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('from', $startDate, 'to', $endDate);

		$page = self::START_PAGE;
		$done = false;
		// $result = $this->get_group_stat($lastPage, $startDate, $endDate);

		// $date_from = $date_from ?: 0;
		// $date_to = $date_to ?: time();
		$success = true;
		while (!$done) {
			$rlt = $this->callApi(self::API_syncGameRecords, array(
				'group_id' => $this->default_group_id,
				'date_from' => $startDate->format('Y-m-d'),
				'h_from' => $startDate->format('H'),
				'm_from' => $startDate->format('i'),
				's_from' => $startDate->format('s'),
				'date_to' => $endDate->format('Y-m-d'),
				'h_to' => $endDate->format('H'),
				'm_to' => $endDate->format('i'),
				's_to' => $endDate->format('s'),
				'page' => $page,
				'software' => 'netent',
			), $context);

			$done = true;
			if ($rlt) {
				$success = $rlt['success'];
			}
			if ($rlt && $rlt['success']) {
				$page = $rlt['currentPage'];
				$total_pages = $rlt['totalPages'];
				//next page
				$page += 1;

				$done = $page >= $total_pages;

				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}

		}

		return array('success' => $success);

		// $result = $this->get_group_stat(0);
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		// load models
		// $this->CI->load->model('player');
		$this->CI->load->model('nt_game_logs');
		$this->CI->load->model('game_logs');

		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		if ($success) {
			$ptGameRecords = array();
			$gameRecords = $resultJson['report'];
			if ($gameRecords) {

				//filter availabe rows first
				$availableRows = $this->CI->nt_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

				foreach ($availableRows as $record) {
					$uniqueId = $record[1];
					// check if record not exists
					// if (!$this->CI->nt_game_logs->isUniqueIdAlreadyExists($uniqueId)) {
					// nt_game_logs data
					$ntdata = array(
						'username' => $record[0],
						'log_info_id' => $record[1],
						'time' => $this->convertGameTimeToServerTime($record[2]),
						'after_balance' => $record[3],
						'betting_amount' => $record[4],
						'lines' => $record[5],
						'multiplier' => $record[6],
						'game_id' => $record[7],
						'win_amount' => $record[8],
						'type' => $record[9],
						'external_uniqueid' => $record[1],
					);

					$this->CI->nt_game_logs->insertNTGameLogs($ntdata);
					// }
				}
			}
			$page = $resultJson['page'];
			$totalPages = $resultJson['total_pages'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		}

		return array($success, $result);
	}

	const GAME_TIMEZONE = 'UTC';
	const SYSTEM_TIMEZONE = 'Asia/Hong_Kong';

	private function convertGameTimeToServerTime($dateTimeStr) {
		//from UTC TO UTC+8
		if ($dateTimeStr) {
			$dateTimeStr = $this->CI->utils->convertTimezone($dateTimeStr, self::GAME_TIMEZONE, self::SYSTEM_TIMEZONE);
		}

		return $dateTimeStr;
	}

	private function convertServerTimeToGameTime($dateTimeStr) {
		//from UTC TO UTC+8
		if ($dateTimeStr) {
			$dateTimeStr = $this->CI->utils->convertTimezone($dateTimeStr, self::SYSTEM_TIMEZONE, self::GAME_TIMEZONE);
		}

		return $dateTimeStr;
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// throw new Exception("Function not implemented", 1);
	}

	# API ##############################################################################################
	// function get_stat($user_id, $date_from = null, $date_to = null) {
	// 	$date_from = $date_from ?: 0;
	// 	$date_to = $date_to ?: time();
	// 	$result = $this->callApi('get_stat', [
	// 		'group_id' => $this->default_group_id,
	// 		'user_id' => $user_id,
	// 		'software' => $this->default_software,
	// 		'date_from' => date('Y-m-d', $date_from),
	// 		'h_from' => date('H', $date_from),
	// 		'm_from' => date('i', $date_from),
	// 		'date_to' => date('Y-m-d', $date_to),
	// 		'h_to' => date('H', $date_to),
	// 		'm_to' => date('i', $date_to),
	// 	]);
	// 	return $result;
	// }

	// function get_group_stat($page = 0, $date_from = null, $date_to = null) {
	// 	$date_from = $date_from ?: 0;
	// 	$date_to = $date_to ?: time();
	// 	$result = $this->callApi(self::API_syncGameRecords, array(
	// 		'group_id' => $this->default_group_id,
	// 		'date_from' => date('Y-m-d', $date_from),
	// 		'h_from' => date('H', $date_from),
	// 		'm_from' => date('i', $date_from),
	// 		's_from' => date('s', $date_from),
	// 		'date_to' => date('Y-m-d', $date_to),
	// 		'h_to' => date('H', $date_to),
	// 		'm_to' => date('i', $date_to),
	// 		's_to' => date('s', $date_to),
	// 	));
	// 	return $result;
	// }

	// function generate_game_url($game, $key, $language = null) {
	// 	$language = $language ?: $this->default_language;
	// 	return $this->game_url . http_build_query([
	// 		'game' => $game,
	// 		'key' => $key,
	// 		'language' => $language,
	// 	]);
	// }

	function getNTGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('nt_game_logs');
		return $this->CI->nt_game_logs->getNTGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return $this->returnUnimplemented();

		// $success = false;
		// $result = array();
		// try {
		// 	$this->CI->load->model(array('game_provider_auth', 'player_model'));
		// 	if (empty($playerNames)) {
		// 		// $playerNames = array();
		// 		//load all players
		// 		$playerNames = $this->CI->game_provider_auth->getAllGameUsernames($this->getPlatformCode());
		// 		// if (!empty($playerInfos)) {
		// 		// 	foreach ($playerInfos as $playerInfo) {
		// 		// 		$playerNames[] = $playerInfo->login_name;
		// 		// 	}
		// 		// }

		// 		// foreach ($playerNames as $playerName) {
		// 		// 	$rlt = $this->queryPlayerBalance($playerName);
		// 		// 	$result[$playerName] = $rlt['success'] ? $rlt['balance'] : null;
		// 		// }
		// 		// $success = true;
		// 		// } else {
		// 	} else {
		// 		//convert to game username
		// 		foreach ($playerNames as &$username) {
		// 			$username = $this->getGameUsernameByPlayerUsername($username);
		// 		}
		// 	}
		// 	if (!empty($playerNames)) {
		// 		// if ($this->is_array_and_not_empty($playerNames)) {
		// 		foreach ($playerNames as $playerName) {

		// 			$context = array(
		// 				'callback_obj' => $this,
		// 				'callback_method' => 'processResultForQueryPlayerBalance',
		// 				'playerName' => $playerName,
		// 			);

		// 			$rlt = $this->callApi(self::API_queryPlayerBalance, array(
		// 				"user_id" => $playerName, "group_id" => $this->default_group_id, 'software' => $this->default_software),
		// 				$context);

		// 			// $rlt = $this->queryPlayerBalance($playerName);
		// 			$result[$playerName] = $rlt['success'] ? $rlt['balance'] : null;

		// 			usleep(100);
		// 		}
		// 		$success = true;
		// 	}
		// } catch (\Exception $e) {
		// 	$this->processError($e);
		// }
		// return $this->returnResult($success, "balances", $result);

	}

// 	function getPlayerById($user_id) {
	// 		$this->CI->load->model('player');
	// 		$player = $this->CI->player->getPlayerById($user_id);

// 		return $player['gameName'];
	// 	}
}

/////////END OF FILE/////////