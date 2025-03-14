<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_win9777 extends Abstract_game_api {

	private $win9777_api_url;
	private $win9777_sn;
	private $win9777_secret_key;
	private $win9777_create_member;
	private $win9777_check_member_balance;
	private $win9777_transfer;
	private $win9777_getbet;
	private $win9777_memberList = array();
	private $win9777_gameList = array();
	private $win9777_allowedGameList = array('5PK', '7PK');

	const URI_MAP = array(
		self::API_createPlayer => 'AddAccount',
		self::API_queryPlayerBalance => 'GetUserBalance',
		self::API_depositToGame => 'Transfer',
		self::API_withdrawFromGame => 'Transfer',
		self::API_syncGameRecords => 'GetBetRecord',
		self::API_queryPlayerInfo => 'GetMemberList',
		self::API_queryGameRecords => 'GetGameList',
	);

	const STATUS_SUCCESS = '00';

	public function __construct() {
		parent::__construct();
		$this->win9777_api_url = $this->getConfig('win9777_api_url');
		$this->win9777_sn = $this->getConfig('win9777_sn');
		$this->win9777_secret_key = $this->getConfig('win9777_secret_key');
		$this->win9777_create_member = $this->getConfig('win9777_create_member');
		$this->win9777_check_member_balance = $this->getConfig('win9777_check_member_balance');
		$this->win9777_member_list = $this->getConfig('win9777_member_list');
		$this->win9777_game_list = $this->getConfig('win9777_game_list');
		$this->win9777_transfer = $this->getConfig('win9777_transfer');
		$this->win9777_getbet = $this->getConfig('win9777_getbet');
		$this->url_default = $this->getConfig('win9777_api_url') . ':80/WcfFunc.svc';
	}

	public function getPlatformCode() {
		return WIN9777_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);
		return $this->url_default . "/" . $apiUri . "?" . $params_string;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);

	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson) && $resultJson['status'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('WIN9777 got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

	const DATE_FORMAT_STYLE1 = 1; //'YYYYMMDD'
	const DATE_FORMAT_STYLE2 = 2; //'YYYY-MM-DD'
	const DATE_FORMAT_STYLE3 = 3; //'H:i:s'
	private function getEasternStandardTime($dateTime, $formatType = self::DATE_FORMAT_STYLE1) {
		if ($formatType == self::DATE_FORMAT_STYLE1) {
			return $dateTime->modify('-12 hours')->format('Ymd');
		} elseif ($formatType == self::DATE_FORMAT_STYLE2) {
			return $dateTime->modify('-12 hours')->format('Y-m-d');
		} elseif ($formatType == self::DATE_FORMAT_STYLE3) {
			return $dateTime->modify('-12 hours')->format('H:i:s');
		}
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $playerName = "p1win9777";
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$key = strtolower(random_string('alpha', $this->win9777_create_member['start_key_len']))
		. md5($this->win9777_sn . $playerName . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_create_member['end_key_len']));

		return $this->callApi(self::API_createPlayer,
			array(
				"sn" => $this->win9777_sn,
				"account" => $playerName,
				"password" => $password,
				"name" => random_string('alpha'),
				"nickname" => random_string('alpha'),
				"key" => $key),
			$context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		// var_dump($resultJson);exit();
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
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
		return array("success" => true);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}
	//===end unblockPlayer=====================================================================================

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$remitno = random_string('numeric');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'remitno' => $remitno,
		);

		$key = strtolower(random_string('alpha', $this->win9777_transfer['start_key_len']))
		. md5($this->win9777_sn . $playerName . $amount . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_transfer['end_key_len']));

		return $this->callApi(self::API_depositToGame,
			array(
				"sn" => $this->win9777_sn,
				"account" => $playerName,
				"remitno" => $remitno,
				"action" => 'IN',
				"remit" => $amount,
				"key" => $key,
			),
			$context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$remitno = $this->getVariableFromContext($params, 'remitno');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		// $result = array();
		$result = array('response_result_id' => $responseResultId);

		if ($success) {
			//for sub wallet
			$afterBalance = $resultJson['NewBalance'];
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = null; // $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			$result["remitno"] = $remitno;
			$result["userNotFound"] = false;

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
			$result["userNotFound"] = true;
		}

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$remitno = random_string('numeric');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'remitno' => $remitno,
		);

		$key = strtolower(random_string('alpha', $this->win9777_transfer['start_key_len']))
		. md5($this->win9777_sn . $playerName . $amount . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_transfer['end_key_len']));

		return $this->callApi(self::API_depositToGame,
			array(
				"sn" => $this->win9777_sn,
				"account" => $playerName,
				"remitno" => $remitno,
				"action" => 'OUT',
				"remit" => $amount,
				"key" => $key,
			),
			$context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$remitno = $this->getVariableFromContext($params, 'remitno');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		// $result = array();
		$result = array('response_result_id' => $responseResultId);

		if ($success) {
			$afterBalance = $resultJson['NewBalance'];
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = null; // $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			$result["remitno"] = $remitno;
			$result["userNotFound"] = false;

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
			$result["userNotFound"] = true;
		}

		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		return array("success" => true);
	}

	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		return array("success" => true);
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

		$key = strtolower(random_string('alpha', $this->win9777_check_member_balance['start_key_len']))
		. md5($this->win9777_sn . $playerName . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_check_member_balance['end_key_len']));

		return $this->callApi(self::API_queryPlayerBalance,
			array(
				"sn" => $this->win9777_sn,
				"account" => $playerName,
				"key" => $key),
			$context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array();
		if ($success && isset($resultJson['Balance']) && @$resultJson['Balance'] !== null) {
			$result["balance"] = floatval($resultJson['Balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$resultJson['Balance']);
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
		$this->getWIN9777Records($token);
	}

	private function getWIN9777Records($token) {
		$this->getMemberList();
		$this->getGameList();
		$memberList = $this->win9777_memberList;
		$win9777_gameList = $this->win9777_gameList;
		// var_dump($win9777_gameList);exit();
		foreach ($win9777_gameList as $key) {
			if (in_array($key['GameId'], $this->win9777_allowedGameList)) {
				$gameList[] = $key['GameId'];
			}
		}

		foreach ($memberList as $member) {
			foreach ($gameList as $gamelist => $gameid) {
				$this->getMemberRecords($token, $member['cola_Account'], $gameid);
			}
		}
	}

	private function getMemberList() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMemberList',
		);

		$key = strtolower(random_string('alpha', $this->win9777_member_list['start_key_len']))
		. md5($this->win9777_sn . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_member_list['end_key_len']));

		return $this->callApi(self::API_queryPlayerInfo,
			array(
				"sn" => $this->win9777_sn,
				"key" => $key),
			$context);
	}

	public function processResultForGetMemberList($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->win9777_memberList = $resultJson['data'];
	}

	private function getGameList() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetGameList',
		);

		$key = strtolower(random_string('alpha', $this->win9777_game_list['start_key_len']))
		. md5($this->win9777_sn . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_game_list['end_key_len']));

		return $this->callApi(self::API_queryGameRecords,
			array(
				"sn" => $this->win9777_sn,
				"key" => $key),
			$context);
	}

	public function processResultForGetGameList($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->win9777_gameList = $resultJson['data'];
	}

	private function getMemberRecords($token, $account, $gameId) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$start_date = new DateTime($startDate->format('Y-m-d'));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($endDate->format('Y-m-d'));
		$dates = array();

		//split date range (start date to end date)
		$dates = $this->CI->utils->dateRange($this->getEasternStandardTime($start_date, self::DATE_FORMAT_STYLE2), $this->getEasternStandardTime($end_date, self::DATE_FORMAT_STYLE2), true);
		$this->CI->utils->debug_log('dates', $dates);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'token' => $token,
		);

		$key = strtolower(random_string('alpha', $this->win9777_getbet['start_key_len']))
		. md5($this->win9777_sn . $account . $this->win9777_secret_key . $this->getEasternStandardTime(new DateTime()))
		. strtolower(random_string('alpha', $this->win9777_getbet['end_key_len']));

		$cnt = 0;
		while ($cnt < count($dates)) {
			$page = self::START_PAGE;
			$done = false;
			$success = true;

			while (!$done) {
				$data = array(
					"sn" => $this->win9777_sn,
					"account" => $account,
					"gameid" => $gameId,
					"start_datetime" => $cnt == 0 ? $dates[$cnt] . ' ' . $start_date->format('H:i:s') : $dates[$cnt] . ' 00:00:00',
					"end_datetime" => $cnt == count($dates) - 1 ? $dates[$cnt] . ' ' . $end_date->format('H:i:s') : $dates[$cnt] . ' 23:59:59',
					"key" => $key);

				$rlt = $this->callApi(self::API_syncGameRecords, $data, $context);

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
			if ($done) {
				$cnt++;
			}
		}
		return true;
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		// load models
		$this->CI->load->model(array('win9777_game_logs', 'external_system'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		// var_dump($resultJson);
		if ($success) {
			if (!empty($resultJson['data'])) {
				$gameRecords = $resultJson['data'];

				// if ($gameRecords) {

				// 	//filter availabe rows first
				// 	$availableRows = $this->CI->win9777_game_logs->getAvailableRows($gameRecords);

				// 	list($availableRows, $maxRowId) = $this->CI->win9777_game_logs->getAvailableRows($gameRecords);
				// 	$this->CI->utils->debug_log('availableRows', count($availableRows), 'maxRowId', $maxRowId);

				// 	foreach ($availableRows as $row) {
				// 		$this->copyRowToDB($row, $responseResultId, $params['params']['gamekind']);
				// 	}
				// 	if ($maxRowId) {
				// 		$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
				// 		$lastRowId = $maxRowId;
				// 	} else {
				// 		break;
				// 	}
				// }

				// $page = $resultJson['pagination']['Page'];
				// $totalPages = $resultJson['pagination']['TotalPage'];
				// $result['currentPage'] = $page;
				// $result['totalPages'] = $totalPages;
			}
		} else {
			//if failed recall api
			$token = $this->getVariableFromContext($params, 'token');
			// $gameKind = $params['params']['gamekind'];
			// $gameType = isset($params['params']['gametype']) ? $params['params']['gametype'] : null;
			// $subGameKind = isset($params['params']['subgamekind']) ? $params['params']['subgamekind'] : null;
			// $this->CI->utils->debug_log('repeat call => game kind: ', $gameKind . ' gameType: ' . $gameType . ' subGameKind: ' . $subGameKind . ' actual params:' . json_encode($params['params']));
			// $this->getBBINRecords($token, $gameKind, $gameType, $subGameKind, $params['params']['rounddate']);
		}

		return array($success, $result);
	}

	private function copyRowToDB($row, $responseResultId, $gameKind) {
		// $result = array(
		// 	'username' => $row['UserName'],
		// 	'wagers_id' => $row['WagersID'],
		// 	'wagers_date' => $row['WagersDate'],
		// 	'game_type' => $row['GameType'],
		// 	'exchange_rate' => $row['ExchangeRate'],
		// 	'result' => $row['Result'],
		// 	'bet_amount' => $row['BetAmount'],
		// 	'payoff' => $row['Payoff'],
		// 	'currency' => $row['Currency'],
		// 	'game_platform' => WIN9777_API,
		// 	'external_uniqueid' => $row['WagersID'],
		// );

		// if ($gameKind == self::WIN9777_GAME_PROPERTY['bb_sports']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::WIN9777_GAME_PROPERTY['bb_sports']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// } elseif ($gameKind == self::WIN9777_GAME_PROPERTY['live']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::WIN9777_GAME_PROPERTY['live']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// 	$result['serial_id'] = isset($row['SerialID']) ? $row['SerialID'] : null;
		// 	$result['round_no'] = isset($row['RoundNo']) ? $row['RoundNo'] : null;
		// 	$result['game_code'] = isset($row['GameCode']) ? $row['GameCode'] : null;
		// 	$result['result_type'] = isset($row['ResultType']) ? $row['ResultType'] : null;
		// 	$result['card'] = isset($row['Card']) ? $row['Card'] : null;
		// } elseif ($gameKind == self::WIN9777_GAME_PROPERTY['casino']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::WIN9777_GAME_PROPERTY['casino']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// } elseif ($gameKind == self::WIN9777_GAME_PROPERTY['lottery']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::WIN9777_GAME_PROPERTY['lottery']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// 	$result['commision'] = $row['Commission'];
		// 	$result['is_paid'] = $row['IsPaid'];
		// } elseif ($gameKind == self::WIN9777_GAME_PROPERTY['3d_hall']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::WIN9777_GAME_PROPERTY['3d_hall']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// }

		// $this->CI->utils->debug_log('sync to game_log => game kind: ', $gameKind, ' result:', json_encode($result));
		// $this->CI->win9777_game_logs->insertBBINGameLogs($result);
	}

	public function syncLostAndFound($token) {

	}

	public function syncConvertResultToDB($token) {

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
		// if (!$dateTimeTo) {
		// 	$dateTimeTo = new DateTime();
		// }

		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$result = $this->getWin9777GameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		if ($result) {
			$this->CI->load->model(array('game_logs', 'player_model', 'game_description_model'));

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $bbindata) {
				$player_id = $this->getPlayerIdInGameProviderAuth($bbindata->username);

				if (!$player_id) {
					continue;
				}

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

				$gameDate = new \DateTime($bbindata->wagers_date);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $bbindata->bet_amount;

				$game_description_id = $this->CI->game_description_model->getGameDescriptionId($bbindata->game_code);
				$game_code = $bbindata->game_code;
				$game_type_id = $this->CI->game_description_model->getGameTypeIdByGameCode($bbindata->game_code);

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$result_amount = $one88data->commisionable;
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
				// 	'game_type_id' => $game_type_id,
				// 	'player_id' => $player_id,
				// 	'player_username' => $player_username,
				// 	'external_uniqueid' => $bbindata->external_uniqueid,
				// 	'flag' => Game_logs::FLAG_GAME,
				// );
				//please use abstract_game_api
				// $this->CI->game_logs->syncToGameLogs($gameLogdata);
			}
		}
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval(floatval($amount)), 2);
	}

	private function getWin9777GameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('win9777_game_logs');
		return $this->CI->win9777_game_logs->getWin9777GameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start isPlayerExist=====================================================================================
	public function isPlayerExist($playerName) {

	}

	//===end isPlayerExist=====================================================================================
}

/*end of file*/