<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Get Login Key
 * * Create Player
 * * Block/Unblock Player
 * * Deposit To game
 * * Withdraw from Game
 * * Check Player Balance
 * * Check Player Daily Balance
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get Game Description Information
 * Behaviors not implemented
 * * Check Player's information
 * * Change Password
 * * Login/Logout
 * * Update Player's information
 * * Check total betting amount
 * * Check Transaction
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

 * HR Changcheng
 *
 *	extra_info:
{
"hrcc_api_parent_userid": "v8kag",
"hrcc_api_tk_code": "",
"hrcc_website_type_id": "21",
"hrcc_currency": "CNY",
"hrcc_language": "en",
"prefix_for_username": "",
"balance_in_game_log": false,
"adjust_datetime_minutes": 10
}
 *
 */

class Game_api_hrcc extends Abstract_game_api {
	private $hrcc_api_url;
	private $hrcc_api_parent_userid;
	private $hrcc_api_tk_code;
	private $hrcc_currency;
	private $hrcc_language;
	private $hrcc_website_type_id;

	const STATUS_SUCCESS = '200';
	const URI_MAP = array(
		self::API_createPlayer => 'website/createplayerbyparent',
		self::API_queryPlayerBalance => 'website/getaccountinfo',
		self::API_depositToGame => 'website/cashdeposit',
		self::API_withdrawFromGame => 'website/cashwithdraw',
		self::API_syncGameRecords => 'website/gettxnsbylastupdatetimebyparentuserid',
		self::API_checkLoginToken => 'website/ssohandshake',
		self::API_login => 'ssologin',
	);

	public function __construct() {
		parent::__construct();
		$this->hrcc_api_url = $this->getSystemInfo('url');
		$this->hrcc_api_parent_userid = $this->getSystemInfo('hrcc_api_parent_userid');
		$this->hrcc_api_tk_code = $this->getSystemInfo('hrcc_api_tk_code');
		$this->hrcc_website_type_id = $this->getSystemInfo('hrcc_website_type_id');
	}

	public function getPlatformCode() {
		return HRCC_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		return $url = $this->hrcc_api_url . "/" . $apiUri . '?' . http_build_query($params);
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['status'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('hrcc got error: ', $responseResultId, 'result: ', $resultArr, 'playerName: ', $playerName);
			$success = false;
		}
		return $success;
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$extra = $this->CI->game_description_model->getGameTypeById($game['g']);
		$game['gp'] = "iframe_module/goto_hrcc_game/slots/" . $game['c']; //game param
		return $game;
	}

	public function getLoginKey($gameUserName) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLoginToken',
			'gameUserName' => $gameUserName,
		);

		$params = array(
			"tkCode" => $this->hrcc_api_tk_code,
			"userID" => $gameUserName,
		);
		$this->CI->utils->debug_log('HRCC Get Login Key params >--------------------> ' . json_encode($params));
		return $this->callApi(self::API_checkLoginToken, $params, $context);
	}

	public function processResultForLoginToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUserName = $this->getVariableFromContext($params, 'gameUserName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUserName);
		if ($success && isset($resultJsonArr['loginKey'])) {
			$this->sessionid = $resultJsonArr['loginKey'];
		}
		return array($success, array("result" => $resultJsonArr));
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		parent::createPlayer($gameUserName, $playerId, $password, $email, $extra);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUserName' => $gameUserName,
			'playerId' => $playerId,
		);

		$params = array(
			"tkCode" => $this->hrcc_api_tk_code,
			"userID" => $gameUserName,
			"parentUserID" => $this->hrcc_api_parent_userid,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUserName = $this->getVariableFromContext($params, 'gameUserName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUserName);
		return array($success, $resultJson);
	}

	//===end createPlayer=====================================================================================

	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
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
	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$randomKey = random_string('alnum', 16);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferFundToGame',
			'gameUserName' => $gameUserName,
			'amount' => $amount,
			'randomKey' => $randomKey,
		);

		$params = array(
			"tkCode" => $this->hrcc_api_tk_code,
			"userID" => $gameUserName,
			"parentUserID" => $this->hrcc_api_parent_userid,
			"deposit" => $amount,
			"randomKey" => $randomKey,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForTransferFundToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUserName = $this->getVariableFromContext($params, 'gameUserName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$randomKey = $this->getVariableFromContext($params, 'randomKey');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUserName, true);
		$result = array('response_result_id' => $responseResultId);

		$afterBalance = null;
		$externalTransactionId = null;
		if ($success) {
			$afterBalance = $resultJsonArr['balance'];
			$externalTransactionId = $randomKey;

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUserName);
			if ($playerId) {
				//deposit/withdraw
				$this->insertTransactionToGameLogs($playerId, $gameUserName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUserName . ' getPlayerIdInGameProviderAuth');
			}

		}

		$result['after_balance'] = $afterBalance;
		$result['external_transaction_id'] = $externalTransactionId;
		$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$randomKey = random_string('alnum', 16);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferFundToGame',
			'gameUserName' => $gameUserName,
			'amount' => $amount,
			'randomKey' => $randomKey,
		);

		$params = array(
			"tkCode" => $this->hrcc_api_tk_code,
			"userID" => $gameUserName,
			"parentUserID" => $this->hrcc_api_parent_userid,
			"withdraw" => $amount,
			"randomKey" => $randomKey,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	//===end withdrawFromGame=====================================================================================

	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUserName' => $gameUserName,
		);

		$params = array(
			"tkCode" => $this->hrcc_api_tk_code,
			"userID" => $gameUserName,
			"parentUserID" => $this->hrcc_api_parent_userid,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUserName = $this->getVariableFromContext($params, 'gameUserName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUserName);
		$result = array();
		if ($success) {
			$result["balance"] = floatval(@$resultJsonArr['account']['cashBalance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUserName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'gameUserName', $gameUserName,
				'balance', @$result["balance"]);
			if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			} else {
				log_message('error', 'cannot get player id from ' . $gameUserName . ' getPlayerIdInGameProviderAuth');
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
		return $this->returnUnimplemented();
	}
	//===end checkLoginStatus=====================================================================================

	//===start totalBettingAmount=====================================================================================
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}
	//===end totalBettingAmount=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {
		return $this->returnUnimplemented();
	}
	//===end queryTransaction=====================================================================================

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $param) {

		$success = true;

		// $gameUsername = $playerName;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForward',
			'gameUsername' => $gameUsername,
		);

		$is_redirect = false;
		$url = null;
		$data = $this->getLoginKey($gameUsername);
		$loginKey = $data['result']['loginKey'];
		$this->CI->utils->debug_log('HRCC Get Login Key >--------------------> ' . $loginKey);
		if (!empty($loginKey)) {
			$params = array(
				"tkCode" => $this->hrcc_api_tk_code,
				"userID" => $gameUsername,
				"webSiteTypeID" => $this->hrcc_website_type_id,
				"loginKey" => $loginKey,
			);
			$this->CI->utils->debug_log('HRCC Validate Login params >--------------------> ' . json_encode($params));

			$is_redirect = true;
			// $apiData = $this->callApi(self::API_login, $params, $context);
			// $this->CI->utils->debug_log('HRCC GAME LAUNCH DATA >--------------------> ' . json_encode($apiData));
			$url = $this->hrcc_api_url . "/" . self::URI_MAP[self::API_login] . '?' . http_build_query($params);
			$success = true;
		} else {
			$success = false;
		}

		$this->CI->utils->debug_log('HRCC GAME URL >--------------------> ' . $url);

		return array(
			'success' => $success,
			'is_redirect' => $is_redirect,
			'url' => $url,
			'iframeName' => "HRCC API",
		);
	}

	public function processResultForQueryForward($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
		return array($success, array("result" => $resultJsonArr));
	}

	public function login($gameUserName, $password = null) {
		return $this->returnUnimplemented();
	}

	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	const START_PAGE = 1;
	public function syncOriginalGameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
		$startDate->modify($this->getDatetimeAdjust());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$this->CI->load->model(array('external_system'));
		$lastSyncDatetime = $this->CI->external_system->getLastSyncDatetime(HRCC_API);
		// if ($lastSyncDatetime != '0000-00-00 00:00:00') {
		// 	$lastSyncDatetime = new DateTime($lastSyncDatetime);
		// 	$lastUpdateDate = $lastSyncDatetime->getTimestamp() + 3600 * 8;
		// } else {
		$lastUpdateDate = $startDate->getTimestamp() + 3600 * 8;
		// }

		$this->CI->utils->debug_log("HRCC SYNC API LASTUPDATEDATE ----------------> ", $lastUpdateDate);

		$params = array(
			"tkCode" => $this->hrcc_api_tk_code,
			"parentUserID" => $this->hrcc_api_parent_userid,
			"lastUpdateDate" => $lastUpdateDate * 1000,
		);
		$this->CI->utils->debug_log("HRCC SYNC API PARAMS ----------------> ", json_encode($params));
		$this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function getGameTimeToServerTime() {
		return '+8 hours';
	}

	public function getServerTimeToGameTime() {
		return '-8 hours';
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr);

		$this->CI->utils->debug_log("HRCC SYNC API RESULT ----------------> ", json_encode($resultJsonArr));
		$this->CI->load->model(array('hrcc_game_logs', 'external_system'));
		$result = array();
		if ($success) {
			$gameRecords = null;
			if (isset($resultJsonArr['transactionList'])) {
				$gameRecords = $resultJsonArr['transactionList'];
				// $availableRows = $this->CI->hrcc_game_logs->getAvailableRows($gameRecords);

				if (!empty($gameRecords)) {
					foreach ($gameRecords as $key) {
						$bet_amount = @$key["win"] ?: @$key["place"];
						$result_amount = $key["payout"] < 0 ? $key["payout"] : $key["payout"];
						$hrccGameData = array(
							"trans_id" => @$key["id"],
							"user_id" => @$key["userID"],
							"race_id" => @$key["raceID"],
							"race_type" => @$key["raceType"],
							"race_date" => @$key["raceDate"],
							"race_no" => @$key["raceNo"],
							"runner_no" => @$key["runnerNo"],
							"currency_id" => @$key["currencyID"],
							"status" => @$key["status"],
							"limit" => @$key["limit"],
							"payout" => @$key["payout"],
							"result_amount" => $result_amount,
							"transaction_date" => $this->gameTimeToServerTime(@$key["transactionDate"]),
							"update_date" => $this->gameTimeToServerTime(@$key["updateDate"]),
							"win" => @$key["win"],
							"place" => @$key["place"],
							"bet_amount" => $bet_amount,
							"external_uniqueid" => @$key["id"],
							"response_result_id" => $responseResultId,
						);

						$isExists = $this->CI->hrcc_game_logs->isTransIdAlreadyExists(@$key['id']);
						if ($isExists) {
							$this->CI->hrcc_game_logs->updateGameLogs($hrccGameData);
						} else {
							$this->CI->hrcc_game_logs->insertGameLogs($hrccGameData);
						}

						// will update last sync id
						$this->CI->external_system->updateLastSyncDatetime(HRCC_API, $this->gameTimeToServerTime($key["updateDate"]));
					}
				}
			}
		}
		$result['responseResultId'] = $responseResultId;
		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$this->CI->load->model(array('game_logs', 'player_model', 'hrcc_game_logs', 'game_description_model'));
		$result = $this->CI->hrcc_game_logs->getGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$rlt = array('success' => true);
		if ($result) {
			$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $hrccdata) {

				$player_id = $this->getPlayerIdInGameProviderAuth($hrccdata->username);
				if (!$player_id) {
					continue;
				}

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

				$gameDate = new \DateTime($hrccdata->transaction_date);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $hrccdata->bet_amount;
				$result_amount = $hrccdata->result_amount;

				$status = $this->getGameRecordsStatus($hrccdata->status);

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($hrccdata, $unknownGame, $gameDescIdMap);
				if (empty($game_description_id)) {
					$this->CI->utils->debug_log('empty game_description_id , hrcc_game_logs_model.id=', $hrccdata->id);
					continue;
				}
				if ($bet_amount == 0 && $result_amount == 0) {
					$this->CI->utils->debug_log('bet_amount and result amount is zero');
					continue;
				}

				$this->syncGameLogs($game_type_id,
					$game_description_id,
					$hrccdata->game_code,
					$hrccdata->game_type,
					$hrccdata->game,
					$player_id,
					$player_username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$hrccdata->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$hrccdata->response_result_id,
					Game_logs::FLAG_GAME,
					['status' => $status]
				);
			}
		} else {
			$rlt = array('success' => false);
		}
		return $rlt;
	}

	const ONE_BIT = 8;
	private function getGameRecordsStatus($status) {
		$result = $status & self::ONE_BIT;
		$gameLogStatus = null;
		$this->CI->load->model(array('game_logs', 'hrcc_game_logs'));
		if ($result == self::ONE_BIT) {
			$gameLogStatus = Game_logs::STATUS_SETTLED;
		} else {
			if ($status == Hrcc_game_logs::STATUS_ACTIVE) {
				$gameLogStatus = Game_logs::STATUS_ACCEPTED;
			} elseif ($status == Hrcc_game_logs::STATUS_ACTIVE_CANCEL_SETTLED) {
				$gameLogStatus = Game_logs::STATUS_CANCELLED;
			} elseif ($status == Hrcc_game_logs::STATUS_ACTIVE_RACE_VOID) {
				$gameLogStatus = Game_logs::STATUS_VOID;
			} elseif ($status == Hrcc_game_logs::STATUS_ACTIVE_CANCELLED) {
				$gameLogStatus = Game_logs::STATUS_CANCELLED;
			} elseif ($status == Hrcc_game_logs::STATUS_ACTIVE_SCR) {
				$gameLogStatus = Game_logs::STATUS_CANCELLED;
			}
		}
		return $gameLogStatus;
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
		$extra = array('game_code' => $externalGameId);
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

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$row->game, $row->game_type, $externalGameId, $extra,
			$unknownGame);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================
}

/*end of file*/