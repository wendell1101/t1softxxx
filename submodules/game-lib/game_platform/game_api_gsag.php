<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Create Player
 * * Check Player Information
 * * Check Player Balance
 * * Deposit to Game
 * * Withdraw from Game
 * * Prepare Transfer Credit
 * * Synchronize Original Game Logs
 * * Get Platform Code
 * * Generate URL
 * * Block/Unblock Player
 * Behaviors not implemented
 * * Update Player's information
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check Login Token
 * * Check Total Betting Amount
 * * Check Transaction
 * * Login/Logout
 * * Check Login Stat * us
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @deprecated 2.0
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

# DONE: CheckOrCreateAccount
# DONE: GetAccountLogin
# DONE: GetBalance
# DONE: PrepareTransferCredit
# DONE: ConfirmTransferCredit
# QueryOrderStatus
# DONE: ForwardGame
# TODO: GetReport

class Game_api_gsag extends Abstract_game_api {

	private $service_url;
	private $operator_code;
	private $private_key;
	private $odd_type;
	private $currency;

	const URI_MAP = array(
		self::API_createPlayer => '/api/CheckOrCreateAccount',
		self::API_queryPlayerInfo => '/api/GetAccountLogin',
		self::API_queryPlayerBalance => '/api/GetBalance',
		self::API_prepareTransferCredit => '/api/PrepareTransferCredit',
		self::API_confirmTransferCredit => '/api/ConfirmTransferCredit',
		self::API_syncGameRecords => '/api/GetReport',
		self::API_queryForwardGame => '/api/ForwardGame',

	);

	const API_prepareTransferCredit = 'prepareTransferCredit';
	const API_confirmTransferCredit = 'confirmTransferCredit';

	const SUCCESS_CODE = 0;
	const WITHDRAW = 0;
	const DEPOSIT = 1;
	const FLAG = 1;
	const BET_RECORDS = 'BR';

	public function __construct() {
		parent::__construct();
		$this->service_url = $this->getSystemInfo('url');
		$this->operator_code = $this->getSystemInfo('key');
		$this->private_key = $this->getSystemInfo('secret');
		$this->odd_type = $this->getSystemInfo('gsag_odd_type');
		$this->currency = $this->getSystemInfo('gsag_currency');
	}

	# START CREATE PLAYER #################################################################################################################################

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'op' => $this->operator_code,
			'member_id' => $playerName,
			'password' => $password,
			'oddtype' => $this->odd_type,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	# END CREATE PLAYER #################################################################################################################################

	# START QUERY PLAYER INFO #################################################################################################################################

	public function queryPlayerInfo($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerInfo',
			'playerName' => $playerName,
		);

		$params = array(
			'op' => $this->operator_code,
			'member_id' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerInfo, $params, $context);

	}

	public function processResultForQueryPlayerInfo($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	# END QUERY PLAYER INFO #################################################################################################################################

	# START QUERY PLAYER BALANCE #################################################################################################################################

	public function queryPlayerBalance($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'op' => $this->operator_code,
			'member_id' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, array(
			'balance' => $success && isset($resultJson['balance']) ? @floatval($resultJson['balance']) : 0,
		));

	}

	# END QUERY PLAYER BALANCE #################################################################################################################################

	# START DEPOSIT TO GAME #################################################################################################################################

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

		$transfer_type = self::API_depositToGame;
		$result = $this->prepareTransferCredit($playerName, $amount, self::DEPOSIT, $transfer_type);
		if ($result['success'] && isset($result['ticket_id'])) {
			$result = $this->confirmTransferCredit($playerName, $amount, $result['ticket_id'], self::DEPOSIT, $transfer_type);
		}

		return $result;

	}

	# END DEPOSIT TO GAME #################################################################################################################################

	# START WITHDRAW FROM GAME #################################################################################################################################

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$transfer_type = self::API_withdrawFromGame;
		$result = $this->prepareTransferCredit($playerName, $amount, self::WITHDRAW, $transfer_type);
		if ($result['success'] && isset($result['ticket_id'])) {
			$result = $this->confirmTransferCredit($playerName, $amount, $result['ticket_id'], self::WITHDRAW, $transfer_type);
		}

		return $result;

	}

	# END WITHDRAW FROM GAME #################################################################################################################################

	# START PREPARE TRANSFER CREDIT #################################################################################################################################

	public function prepareTransferCredit($playerName, $amount, $direction, $transfer_type) {

		$this->CI->load->helper('string');

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$ticket_id = random_string('alnum', 16);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForPrepareTransferCredit',
			'playerName' => $playerName,
			'ticket_id' => $ticket_id,
			'transfer_type' => $transfer_type,
		);

		$params = array(
			'op' => $this->operator_code,
			'member_id' => $playerName,
			'ticket_id' => $ticket_id,
			'amount' => $amount,
			'direction' => $direction,
		);

		return $this->callApi(self::API_prepareTransferCredit, $params, $context);

	}

	public function processResultForPrepareTransferCredit($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$ticket_id = $this->getVariableFromContext($params, 'ticket_id');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$resultJson['ticket_id'] = $ticket_id;

		$resultJson['response_result_id'] = $responseResultId;
		return array($success, $resultJson);

	}

	# END PREPARE TRANSFER CREDIT #################################################################################################################################

	# START CONFIRM TRANSFER CREDIT #################################################################################################################################

	public function confirmTransferCredit($playerName, $amount, $ticket_id, $direction, $transfer_type) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		switch ($direction) {
		case self::DEPOSIT:
			$transType = 'transTypeMainWalletToSubWallet';
			break;

		case self::WITHDRAW:
			$transType = 'transTypeSubWalletToMainWallet';
			break;

		default:
			$transType = '';
			break;
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForConfirmTransferCredit',
			'playerName' => $playerName,
			'amount' => $amount,
			'transType' => $transType,
			'transfer_type' => $transfer_type,
		);

		$params = array(
			'op' => $this->operator_code,
			'member_id' => $playerName,
			'ticket_id' => $ticket_id,
			'amount' => $amount,
			'direction' => $direction,
			'flag' => self::FLAG,
		);

		return $this->callApi(self::API_confirmTransferCredit, $params, $context);

	}

	public function processResultForConfirmTransferCredit($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transType = $this->getVariableFromContext($params, 'transType');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {

			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {

				$playerBalance = $this->queryPlayerBalance($playerName);
				$afterBalance = $playerBalance['balance'];

				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->{$transType}());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		}

		$resultJson['response_result_id'] = $responseResultId;

		return array($success, $resultJson);

	}

	# END CONFIRM TRANSFER CREDIT #################################################################################################################################

	# START SYNC ORIGINAL GAME LOGS #################################################################################################################################

	public function syncOriginalGameLogs($token) {

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());

		$startDate = $this->serverTimeToGameTime($startDate);
		$endDate = $this->serverTimeToGameTime($endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'syncId' => $syncId,
		);

		# GET LIVE
		$params = array(
			'op' => $this->operator_code,
			'rType' => 'BR',
			'frDate' => $startDate,
			'toDate' => $endDate,
		);

		$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

		# GET SLOTS
		$params = array(
			'op' => $this->operator_code,
			'rType' => 'EBR',
			'frDate' => $startDate,
			'toDate' => $endDate,
		);

		$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

		return $rlt;
	}

	public function processResultForSyncGameRecords($params) {

		$this->CI->load->model('gsag_game_logs');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		if(isset($resultJson['data'])){
			$gameRecords = @$resultJson['data'];
	    }

		if (isset($gameRecords) && $gameRecords) {
			$availableRows = $this->CI->gsag_game_logs->getAvailableRows($gameRecords);
			$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

			foreach ($availableRows as $record) {
				$this->CI->gsag_game_logs->insertGsagGameLogs($record);
			}
		}

		$success = $this->processResultBoolean($responseResultId, $resultJson, NULL);
		return array($success);
	}

	# END SYNC ORIGINAL GAME LOGS #################################################################################################################################

	# START SYNC MERGE TO GAME LOGS #################################################################################################################################

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'gsag_game_logs', 'game_description_model'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$result = $this->CI->gsag_game_logs->getGsagGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$cnt = 0;
		if ($result) {

			//$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $gsag_data) {

				if (!$player_id = $this->getPlayerIdInGameProviderAuth($gsag_data->MemberId)) {
					continue;
				}

				$cnt++;

				$player = $this->CI->player_model->getPlayerById($player_id);
				$bet_amount = $this->gameAmountToDB($gsag_data->betAmount);
				$result_amount = $this->gameAmountToDB($gsag_data->netAmount);

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($gsag_data, $gameDescIdMap);

				//var_dump($game_description_id, $game_type_id);
				// $game_description_id = $gsag_data->game_description_id;
				// $game_type_id = $gsag_data->game_type_id;

				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$gsag_data->game_code,
					$gsag_data->CasinoTypeName,
					$gsag_data->game,
					$player_id,
					$player->username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$gsag_data->billNo,
					$gsag_data->datecreated,
					$gsag_data->datecreated,
					null# response_result_id
				);

			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return array('success' => true);
	}

	private function getGameDescriptionInfo($row, $gameDescIdMap) {

		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}

		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		$externalGameId = $row->game_code;
		if (empty($game_description_id)) {
			//search game_description_id by code
			if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
				$game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
				$game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
				if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
					return array(null, null);
				}
			}
		}

		$extra = array('game_code' => 'gsag.' . strtolower($row->game_name_str));

		return $this->processUnknownGame($game_description_id, $game_type_id, 'gsag.' . strtolower($row->game_name_str), $row->game_type_str, $externalGameId, $extra);
	}

	# END SYNC MERGE TO GAME LOGS #################################################################################################################################

	# START QUERY FORWARD GAME #################################################################################################################################

	public function queryForwardGame($playerName, $extra = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
		);

		$params = array_filter(array(
			'op' => $this->operator_code,
			'member_id' => $playerName,
			'dm' => isset($extra['dm']) ? $extra['dm'] : NULL,
			'lang' => isset($extra['lang']) ? $extra['lang'] : NULL,
			'gameType' => isset($extra['gameType']) ? $extra['gameType'] : NULL,
			'oddtype' => isset($extra['oddtype']) ? $extra['oddtype'] : NULL,
		));

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, array('url' => $resultJson['info']));

	}

	# END QUERY FORWARD GAME #################################################################################################################################

	# IMPROVISED / UTILS ##############################################################################################################################################################################
	public function getPlatformCode() {

		return GSAG_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$concat = $this->private_key . $apiUri . '?' . http_build_query($params);
		$concat = str_replace('3A', '3a', $concat);
		$auth = strtoupper(md5($concat));
		$url = $this->service_url . $apiUri . '?' . http_build_query(array_merge(array('auth' => $auth), $params));
		// var_dump(array('params' => $params, 'concat' => $concat, 'url' => $url));
		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName) {

		$success = (!empty($resultJson)) && $resultJson['error_code'] == self::SUCCESS_CODE;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GSAG got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->blockUsernameInDB($playerName);
		return array("success" => $result);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->unblockUsernameInDB($playerName);
		return array("success" => $result);
	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	// public function getGameTimeToServerTime() {
	// 	return '+12 hours';
	// }

	// public function getServerTimeToGameTime() {
	// 	return '-12 hours';
	// }

	public function gameTimeToServerTime($dateTimeStr) {
		if (is_object($dateTimeStr) && $dateTimeStr instanceof DateTime) {
			$dateTimeStr = $dateTimeStr->format('Y-m-d H:i:s');
		}
		$modify = $this->getGameTimeToServerTime();
		$date = $this->utils->modifyDateTime($dateTimeStr, $modify);
		return $date;
	}

	public function serverTimeToGameTime($dateTimeStr) {
		if (is_object($dateTimeStr) && $dateTimeStr instanceof DateTime) {
			$dateTimeStr = $dateTimeStr->format('Y-m-d\TH:i:s\Z');
		}
		$modify = $this->getServerTimeToGameTime();
		return date('Y-m-d\TH:i:s\Z', strtotime($this->utils->modifyDateTime($dateTimeStr, $modify)));
	}

	# NOT IMPLEMENTED ##############################################################################################################################################################################
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

}