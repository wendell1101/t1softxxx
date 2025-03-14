<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 *
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting Platform Code
 * * Deposit to Game
 * * Create Player
 * * Check Player's info
 * * Check Login Token
 * * Get Total Betting Amount
 * * Check Transaction
 * * Reset Player Account
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
 *
 *	extra_info:
{
"crown_agentacct": "d8888moyb",
"crown_password": "7820fc5ae308b691e634bf8fbe4db653",
"crown_game_url": "http://fd.tbsbet8.com",
"crown_uat_agentacct": "",
"crown_uat_password": "",
"crown_demo_account": "",
"odd_style": 3,
"currency": "RMB",
"min_bet": -1,
"max_bet": -1,
"per_max_bet": -1,
"mix_min_bet": -1,
"mix_max_bet": -1,
"day_limit": -1,
"group_comm": "",
"prefix_for_username": "yabo",
"balance_in_game_log": false,
"adjust_datetime_minutes": 20
}
 *
 */
class Game_api_crown extends Abstract_game_api {
	private $api_url;
	private $crown_game_url;
	private $crown_agentacct;
	private $crown_password;
	private $crown_demo_account;

	const FLAG_SUCCESS = "000000";

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->crown_game_url = $this->getSystemInfo('crown_game_url');
		$this->crown_agentacct = $this->getSystemInfo('crown_agentacct');
		$this->crown_password = $this->getSystemInfo('crown_password');
		$this->crown_demo_account = $this->getSystemInfo('crown_demo_account');
	}

	const API_updateMemberLimit = "updateMemberLimit";
	const API_updateGroupComm = "updateGroupComm";

	const URI_MAP = array(
		self::API_createPlayer => 'sportapi.asmx/RegisterByLimit', // or Register
		self::API_queryPlayerBalance => 'sportapi.asmx/GetBalance',
		self::API_depositToGame => 'sportapi.asmx/Transfer',
		self::API_login => 'sportapi.asmx/Login',
		self::API_logout => 'sportapi.asmx/Logout',
		self::API_withdrawFromGame => 'sportapi.asmx/Transfer',
		// self::API_syncGameRecords => 'sportapi.asmx/GetBetSheet',
		self::API_syncGameRecords => 'sportapi.asmx/GetBetSheetByReport',
		self::API_updateMemberLimit => 'sportapi.asmx/UpdateMemberLimit',
		self::API_updateGroupComm => 'sportapi.asmx/UpdateGroupComm',
	);

	public function getPlatformCode() {
		return CROWN_API;
	}

	function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . "/" . $apiUri . "?" . http_build_query($params);
		return $url;
	}

	protected function getHttpHeaders($params) {
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$game['gp'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c']; //game param
		return $game;
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}

	function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$depositAmount = number_format((float) $amount, 4, '.', '');
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$serialNo = 'DEP' . random_string('unique');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'serialNo' => $serialNo,
		);
		$params = array(
			"APIPassword" => $this->crown_password,
			"MemberAccount" => $playerName,
			"SerialNumber" => $serialNo,
			"Amount" => $amount,
			"TransferType" => 0, # 0 - deposit, 1 - withdraw
			"Key" => substr(md5($this->crown_password . $playerName . $depositAmount), -6), # get last 6
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$serialNo = $this->getVariableFromContext($params, 'serialNo');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log('processResultForDepositToGame resultArr: ', $resultArr);
		$result = array('response_result_id' => $responseResultId);
		if ($success) {
			$afterBalance = @$resultArr['result']['Balance'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["transId"] = $serialNo;
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
		}

		return array($success, $result);
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['errcode'] == self::FLAG_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('Crown got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return array("success" => true);
	}

	//===start createPlayer=====================================================================================
	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			"APIPassword" => $this->crown_password,
			"AgentAccount" => $this->crown_agentacct,
			"MemberAccount" => $playerName,
			"NickName" => $playerName,
			"Currency" => $this->getSystemInfo('currency'), //"RMB"
			"MinBet" => $this->getSystemInfo('min_bet'),
			"MaxBet" => $this->getSystemInfo('max_bet'),
			"PerMaxBet" => $this->getSystemInfo('per_max_bet'),
			"MixMinBet" => $this->getSystemInfo('mix_min_bet'),
			"MixMaxBet" => $this->getSystemInfo('mix_max_bet'),
			"DayLimit" => $this->getSystemInfo('day_limit'),
			"GroupComm" => $this->getSystemInfo('group_comm'),
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		//$this->utils->debug_log('CREATE PLAYER RESULT: ',$resultArr);
		return array($success, $resultArr);
	}

	//===end createPlayer=====================================================================================

	//===start isPlayerExist======================================================================================
	// function isPlayerExist($playerName) {

	// 	$this->CI->load->model(array('player_model','game_provider_auth'));
	// 	$result = array('success'=>true);

	// 	$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
	// 	$platformId = $this->getPlatformCode();

	// 	if ($this->CI->game_provider_auth->isRegisterd($playerId, $platformId)) {
	// 		$result['exists'] = true;
	// 	} else {
	// 		$result['exists'] = false;
	// 	}

	// 	return $result;
	// }

	// function processResultForIsPlayerExist($params) {
	// 	return array("success" => true);
	// }
	//===end isPlayerExist========================================================================================

	//===start queryPlayerInfo=====================================================================================
	function queryPlayerInfo($playerName) {
		return array("success" => true);
	}
	function processResultForQueryPlayerInfo($params) {
		return array("success" => true);
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	function changePassword($playerName, $oldPassword, $newPassword) {
		return array("success" => true);
	}

	function processResultForChangePassword($params) {
		return array("success" => true);
	}
	//===end changePassword=====================================================================================

	//===start blockPlayer=====================================================================================
	function blockPlayer($playerName) {
		return array("success" => true);
	}
	function processResultForBlockPlayer($params) {
		return array("success" => true);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	function unblockPlayer($playerName) {
		return array("success" => true);
	}
	function processResultForUnblockPlayer($params) {
		return array("success" => true);
	}
	//===end unblockPlayer=====================================================================================

	//===start withdrawFromGame=====================================================================================
	function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$withdrawAmount = number_format((float) $amount, 4, '.', '');
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$serialNo = 'WIT' . random_string('unique');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'serialNo' => $serialNo,
		);
		$params = array(
			"APIPassword" => $this->crown_password,
			"MemberAccount" => $playerName,
			"SerialNumber" => $serialNo,
			"Amount" => $amount,
			"TransferType" => 1, # 0 - deposit, 1 - withdraw
			"Key" => substr(md5($this->crown_password . $playerName . $withdrawAmount), -6), # get last 6
		);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$serialNo = $this->getVariableFromContext($params, 'serialNo');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log('processResultForWithdrawFromGame resultArr: ', $resultArr);
		$result = array('response_result_id' => $responseResultId);
		if ($success) {
			$afterBalance = @$resultArr['result']['Balance'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["transId"] = $serialNo;
			$result["userNotFound"] = false;

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}

		return array($success, $result);
	}
	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);
		$params = array(
			"APIPassword" => $this->crown_password,
			"MemberAccount" => $playerName,
		);
		return $this->callApi(self::API_login, $params, $context);
	}

	function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log('processResultForWithdrawFromGame resultArr: ', $resultArr);
		$result = array();
		if ($success) {
			//get current sub wallet balance
			$result = $resultArr;
		}

		return array($success, $result);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);
		$params = array(
			"APIPassword" => $this->crown_password,
			"MemberAccount" => $playerName,
		);
		return $this->callApi(self::API_login, $params, $context);
	}
	function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log('processResultForWithdrawFromGame resultArr: ', $resultArr);
		$result = array();
		if ($success) {
			//get current sub wallet balance
			$result = $resultArr;
		}

		return array($success, $result);
	}
	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	function updatePlayerInfo($playerName, $infos) {

		//API_updateMemberLimit
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdateMemberLimit',
			'playerName' => $playerName,
		);
		$params=$infos['ForMemberLimit'];
		$params["APIPassword"] = $this->crown_password;
		$params["MemberAccount"] = $playerName;

		$rltForLimit=$this->callApi(self::API_updateMemberLimit, $params, $context);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGroupComm',
			'playerName' => $playerName,
		);
		//self::API_updateGroupComm
		$params=$infos['ForGroupComm'];
		$params["APIPassword"] = $this->crown_password;
		$params["MemberAccount"] = $playerName;

		$rltForGroup=$this->callApi(self::API_updateGroupComm, $params, $context);

		$this->utils->debug_log('result',$rltForLimit,$rltForGroup);

		$result = array();

		return array('success'=>$rltForLimit['success'] && $rltForGroup['success'], $result);
	}

	function processResultForUpdateMemberLimit($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array();

		return array($success, $result);
	}

	function processResultForGroupComm($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array();

		return array($success, $result);
	}
	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);
		$params = array(
			"APIPassword" => $this->crown_password,
			"MemberAccount" => $playerName,
		);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}
	function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$this->CI->utils->debug_log('processResultForQueryPlayerBalance resultArr: ', $resultArr);
		$result = array();
		if ($success && isset($resultArr['result']['Balance'])) {
			$result["balance"] = floatval($resultArr['result']['Balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$resultArr['result']['Balance']);
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start queryPlayerDailyBalance=====================================================================================
	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
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
	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}
	//===end queryGameRecords=====================================================================================
	//===start checkLoginStatus=====================================================================================
	function checkLoginStatus($playerName) {
		return array("success" => true);
	}

	function processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson) {
		return array("success" => true);
	}
	//===end checkLoginStatus=====================================================================================

	//===start checkLoginToken=====================================================================================
	public function checkLoginToken($playerName, $token) {
		return array("success" => true);
	}
	public function processResultForCheckLoginToken($params) {
		return array("success" => true);
	}
	//===end checkLoginToken=====================================================================================

	//===start totalBettingAmount=====================================================================================
	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return array("success" => true);
	}
	//===end totalBettingAmount=====================================================================================
	//===start queryTransaction=====================================================================================
	function queryTransaction($transactionId, $extra) {
		return array("success" => true);
	}
	function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultJson) {
		return array("success" => true);
	}
	//===end queryTransaction=====================================================================================
	//===start queryForwardGame=====================================================================================
	function queryForwardGame($playerName, $params) {
		if (!$playerName) {
			$loginResult = $this->login($this->crown_demo_account);
		} else {
			$loginResult = $this->login($playerName);
		}

		$url = $this->crown_game_url . "/MemberLogin.aspx?LoginCode=" . $loginResult['result'] .
		"&Language=ch&PageStyle=1&OddsStyle=" . $this->getSystemInfo('odd_style');
		return array(
			'success' => true,
			'url' => $url,
			'iframeName' => "CROWN API",
		);
	}

	//===end queryForwardGame=====================================================================================
	//===start syncGameRecords=====================================================================================

	// const DEFAULT_DATETIME_ADJUST = '-5 minutes';
	const START_PAGE = 0;
	const ITEM_PER_PAGE = 100;
	function syncOriginalGameLogs($token) {
		//call report api
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
		// $params = array(
		// 	"APIPassword" => $this->crown_password,
		// 	"BetID" => "0",
		// 	"Status" => "2",
		// 	"MemberAccount" => "testcrown87670680",
		// 	"FromDate" => $startDate->format("Y-m-d H:i:s"),
		// 	"ToDate" => $endDate->format("Y-m-d H:i:s"),
		// );

		$params = array(
			"APIPassword" => $this->crown_password,
			"BetID" => "0",
			"Status" => "2",
			"MemberAccount" => "testcrown87670680",
			"ReportDate" => $startDate->format("Y-m-d H:i:s"),
			// "ToDate" => $endDate->format("Y-m-d H:i:s"),
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->CI->utils->xmlToArray($resultXml);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$this->CI->utils->debug_log('processResultForSyncGameRecords resultJson: ', $resultArr);
		$result = array();
		// if ($success && isset($resultArr['items'])) {
		// 	$gameRecords = $resultJson['items'];
		// 	if (empty($gameRecords) || !is_array($gameRecords)) {
		// 		$this->CI->utils->debug_log('No records', $gameRecords);
		// 	}
		// 	$this->CI->load->model(array('qt_game_logs'));
		// 	$this->CI->utils->debug_log('gameRecords', $gameRecords);
		// 	$availableRows = $this->CI->qt_game_logs->getAvailableRows($gameRecords);
		// 	$this->CI->utils->debug_log('availableRows', count($availableRows), 'responseResultId', $responseResultId);
		// 	foreach ($availableRows as $record) {
		// 		if ($record['status'] == "COMPLETED") {
		// 			$qtGameData = array(
		// 				'transId' => $record['id'],
		// 				'status' => $record['status'],
		// 				'totalBet' => $record['totalBet'],
		// 				'totalPayout' => $record['totalPayout'],
		// 				'currency' => $record['currency'],
		// 				'initiated' => $record['initiated'],
		// 				'completed' => $record['completed'],
		// 				'operatorId' => $record['operatorId'],
		// 				'playerId' => $record['playerId'],
		// 				'device' => $record['device'],
		// 				'gameProvider' => $record['gameProvider'],
		// 				'gameId' => $record['gameId'],
		// 				'gameCategory' => $record['gameCategory'],
		// 				'gameClientType' => $record['gameClientType'],
		// 				'external_uniqueid' => $record['id'],
		// 				'response_result_id' => $responseResultId,
		// 			);
		// 			$result = $qtGameData;
		// 			$this->CI->qt_game_logs->insertQtGameLogs($qtGameData);
		// 		}
		// 	}
		// 	$success = true;
		// } else {
		// 	$success = false;
		// }

		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$rlt = array('success' => true);
		$this->CI->load->model(array('game_logs', 'player_model', 'qt_game_logs'));
		// $result = $this->CI->qt_game_logs->getCrownGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		// $cnt = 0;
		// if ($result) {
		// 	$unknownGame = $this->getUnknownGame();
		// 	foreach ($result as $crowndata) {
		// 		$cnt++;
		// 		$player = $this->CI->player_model->getPlayerById($crowndata->player_id);
		// 		$gameDate = new \DateTime($crowndata->completed);
		// 		$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

		// 		$bet_amount = $crowndata->bet_amount;
		// 		$result_amount = $crowndata->result_amount;

		// 		$game_description_id = $crowndata->game_description_id;
		// 		$game_type_id = $crowndata->game_type_id;

		// 		if (empty($game_description_id)) {
		// 			$game_description_id = $unknownGame->id;
		// 			$game_type_id = $unknownGame->game_type_id;
		// 		}

		// 		$this->syncGameLogs($game_type_id,
		// 			$game_description_id,
		// 			$crowndata->game_code,
		// 			$crowndata->game_type,
		// 			$crowndata->game,
		// 			$crowndata->player_id,
		// 			$crowndata->username,
		// 			$bet_amount,
		// 			$result_amount,
		// 			null, # win_amount
		// 			null, # loss_amount
		// 			null, # after_balance
		// 			0, # has_both_side
		// 			$crowndata->external_uniqueid,
		// 			$gameDateStr,
		// 			$gameDateStr,
		// 			$crowndata->response_result_id);
		// 	}
		// }
		return $rlt;
	}

	//===end syncGameRecords=====================================================================================
	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	// public function revertBrokenGame($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);

	// 	$context = array(
	// 		'callback_obj' => $this,
	// 		'callback_method' => 'processResultForRevertBrokenGame',
	// 		'playerName' => $playerName,
	// 	);

	// 	return $this->callApi(self::API_revertBrokenGame, array("playerName" => $playerName),
	// 		$context);

	// }

	// public function processResultForRevertBrokenGame($params) {
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultJson = $this->getResultJsonFromParams($params);
	// 	$playerName = $this->getVariableFromContext($params, 'playerName');

	// 	$success = $this->processResultBoolean($responseResultId, $resultJson);
	// 	$result = array();

	// 	return array($success, $result);
	// }

	public function resetPlayer($playerName) {
		//API_updateMemberLimit
		//self::API_updateGroupComm


		// return $this->unblockPlayer($playerName);
		return $this->updatePlayerInfo($playerName, array(
			'ForMemberLimit'=>array(
				"MinBet" => $this->getSystemInfo('min_bet'),
				"MaxBet" => $this->getSystemInfo('max_bet'),
				"PerMaxBet" => $this->getSystemInfo('per_max_bet'),
				"MixMinBet" => $this->getSystemInfo('mix_min_bet'),
				"MixMaxBet" => $this->getSystemInfo('mix_max_bet'),
				"DayLimit" => $this->getSystemInfo('day_limit'),
			),
			'ForGroupComm'=>array(
				"GroupComm" => $this->getSystemInfo('group_comm'),
			),
		));
	}

}

/*end of file*/