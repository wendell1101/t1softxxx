<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 *
 *	extra_info:
{
"seven77_api_key": "",
"seven77_api_agent": "v8kint",
"seven77_api_operator_platform": "onegood",
"seven77_free_game_url": "http://free206.777igt.com/freegame/game.html",
"seven77_flash_game_url": "http://fg.777igt.com/webgame/game.html",
"seven77_html5_game_url": "http://byhtml.777igt.com/htmlgame/index.html",
"seven77_currency_code": "CNY",
"prefix_for_username": "",
"balance_in_game_log": false,
"adjust_datetime_minutes": 10
}
 *
 */
class Game_api_seven77 extends Abstract_game_api {
	private $seven77_api_key;
	private $seven77_api_agent;
	private $seven77_currency_code;
	private $seven77_game_url;
	private $seven77_api_url;
	private $seven77_api_operator_platform;

	const STATUS_SUCCESS = '0';
	const URI_MAP = array(
		self::API_createPlayer => 'registerUser',
		self::API_queryPlayerBalance => 'getUser',
		self::API_depositToGame => 'increaseUserBalance',
		self::API_withdrawFromGame => 'decreaseUserBalance',
		self::API_checkFundTransfer => 'getTransferRecord',
		self::API_syncGameRecords => 'getGameResults',
		self::API_checkLoginToken => 'getUserToken',
	);

	public function __construct() {
		parent::__construct();
		$this->seven77_api_key = $this->getSystemInfo('seven77_api_key');
		$this->seven77_api_url = $this->getSystemInfo('url');
		$this->seven77_api_agent = $this->getSystemInfo('seven77_api_agent');
		$this->seven77_currency_code = $this->getSystemInfo('seven77_currency_code');
		$this->seven77_api_operator_platform = $this->getSystemInfo('seven77_api_operator_platform');
	}

	public function getPlatformCode() {
		return SEVEN77_API;
	}

	protected function customHttpCall($ch, $params) {
		$header_params = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Type: application/json',
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		return $url = $this->seven77_api_url . "/" . $apiUri;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['errorCode'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('seven77 got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
			$success = false;
		}
		return $success;
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$extra = $this->CI->game_description_model->getGameTypeById($game['g']);
		$game['gp'] = "iframe_module/goto_seven77_game/" . $game['c']; //game param
		return $game;
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		// $gameUsername = $playerName;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		parent::createPlayer($gameUsername, $playerId, $password, $email, $extra);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUsername' => $gameUsername,
			'playerId' => $playerId,
		);

		$params = array(
			"key" => $this->seven77_api_key,
			"agent" => $this->seven77_api_agent,
			"username" => $gameUsername,
			"nickname" => $gameUsername,
			"currency" => $this->seven77_currency_code,
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);
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
		$gameUsername = $playerName;
		// $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$referenceID = random_string('alnum', 16);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'referenceID' => $referenceID,
		);
		$date = new DateTime($this->CI->utils->getNowForMysql());
		$params = array(
			"key" => $this->seven77_api_key,
			"agent" => $this->seven77_api_agent,
			"username" => $gameUsername,
			"referenceID" => $referenceID,
			"amount" => $amount,
			"time" => $date->getTimestamp(),
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$referenceID = $this->getVariableFromContext($params, 'referenceID');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername, true);
		$result = array('response_result_id' => $responseResultId);

		if ($success) {
			//get current sub wallet balance
			$playerBalance = $resultJsonArr['record']['balance'];
			//for sub wallet
			$afterBalance = $playerBalance ?: null;

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}

		}

		$result['after_balance'] = $afterBalance;
		$result['external_transaction_id'] = $referenceID;
		$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		// $gameUsername = $playerName;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$referenceID = random_string('alnum', 16);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'referenceID' => $referenceID,
		);

		$date = new DateTime($this->CI->utils->getNowForMysql());
		$params = array(
			"key" => $this->seven77_api_key,
			"agent" => $this->seven77_api_agent,
			"username" => $gameUsername,
			"referenceID" => $referenceID,
			"amount" => $amount,
			"time" => $date->getTimestamp(),
		);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$referenceID = $this->getVariableFromContext($params, 'referenceID');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
		$result = array('response_result_id' => $responseResultId);

		if ($success) {
			//get current sub wallet balance

			$playerBalance = $resultJsonArr['record']['balance'];
			//for sub wallet
			$afterBalance = $playerBalance ?: null;

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			if ($playerId) {
				//withdrawal
				$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
					$this->transTypeSubWalletToMainWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}
		}

		$result['after_balance'] = $afterBalance;
		$result['external_transaction_id'] = $platformTranId;
		$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function getPlayerToken($gameUsername) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetPlayerToken',
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"key" => $this->seven77_api_key,
			"agent" => $this->seven77_api_agent,
			"username" => $gameUsername,
			"period" => 0, #one time token
		);
		return $this->callApi(self::API_checkLoginToken, $params, $context);
	}

	public function processResultForGetPlayerToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
		return array($success, array("result" => $resultJsonArr));
	}
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
		// $gameUsername = $playerName;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"key" => $this->seven77_api_key,
			"agent" => $this->seven77_api_agent,
			"username" => $gameUsername,
		);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
		$result = array();
		if ($success) {
			$result["balance"] = floatval(@$resultJsonArr['user']['balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'gameUsername', $gameUsername,
				'balance', @$resultJsonArr['user']['balance']);
			if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			} else {
				log_message('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
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
		// $gameUsername = $playerName;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$data = $this->getPlayerToken($gameUsername);
		$success = false;
		$url = null;
		if (!empty($data['result'])) {
			if ($param['game_mode'] == 'demo') {
				$this->seven77_game_url = $this->getSystemInfo('seven77_free_game_url');
			} else {
				if ($param['game_type'] == 'flash') {
					$this->seven77_game_url = $this->getSystemInfo('seven77_flash_game_url');
				} elseif ($param['game_type'] == 'html5') {
					$this->seven77_game_url = $this->getSystemInfo('seven77_html5_game_url');
				}
			}

			$url = $this->seven77_game_url . "?p=" . $this->seven77_api_operator_platform . "&a=" . $this->seven77_api_agent . "&lang=" . $param['language'] . '&game=' . $param['game_code'] . '&t=' . $data['result']['token'];
			$success = true;
		}

		return array(
			'success' => $success,
			'url' => $url,
			'iframeName' => "seven77 API",
		);
	}

	public function processResultForQueryForward($params) {

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
		// $endDate = new DateTime($endDate->format('Y-m-d H:i:s T'));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log(' <<----------------------------  777 GAME SYNC startDate', $startDate, 'endDate', $endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$page = self::START_PAGE;
		$done = false;
		$startTimeStamp = $startDate->getTimestamp() + 3600 * 8;
		$endTimeStamp = $endDate->getTimestamp() + 3600 * 8;

		while (!$done) {
			$params = array(
				"key" => $this->seven77_api_key,
				"agent" => $this->seven77_api_agent,
				"startTime" => $startTimeStamp * 1000,
				"endTime" => $endTimeStamp * 1000,
				"page" => $page,
				"count" => 1000,
				"includeDetails" => false,
			);
			$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);
			$done = true;
			if ($rlt && $rlt['success']) {
				$total_pages = @$rlt['totalPage'];
				//next page
				$page += 1;
				$done = $page >= $total_pages;
				$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
			}
			if ($done) {
				$success = true;
			}
		}
		return $success;
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

		// load models
		$this->CI->load->model(array('seven77_game_logs', 'external_system'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr);
		if ($success) {
			$gameRecords = array();
			if (!empty($resultJsonArr['results'])) {
				$gameRecords = $resultJsonArr['results'];
			}

			if (!empty($gameRecords)) {
				$availableRows = $this->CI->seven77_game_logs->getAvailableRows($gameRecords);
				if (!empty($availableRows)) {
					foreach ($availableRows as $key) {
						if (@$key['gameStatus'] == 'finished') {
							$seven77GameData = array(
								'result_id' => @$key['resultID'],
								'agent' => @$key['agent'],
								'username' => @$key['username'],
								'game_system' => @$key['gameSystem'],
								'game_id' => @$key['gameID'],
								'game_type' => @$key['gameType'],
								'game_name' => @$key['gameName'],
								'game_session' => @$key['gameSession'],
								'game_status' => @$key['gameStatus'],
								'game_bet' => @$key['gameBet'] * -1,
								'game_bet_count' => @$key['gameBetCount'],
								'game_bet_refund' => @$key['gameBetRefund'],
								'game_bet_refund_count' => @$key['gameBetRefundCount'],
								'game_paid' => @$key['gamePaid'],
								'game_paid_count' => @$key['gamePaidCount'],
								'game_paid_refund' => @$key['gamePaidRefund'],
								'game_paid_refund_count' => @$key['gamePaidRefundCount'],
								'total_bet' => @$key['totalBet'],
								'total_paid' => @$key['totalPaid'],
								'total_profit' => @$key['totalProfit'],
								'start_time' => $this->gameTimeToServerTime($this->CI->utils->convertTimestampToDateTime(@$key['startTime'])),
								'end_time' => $this->gameTimeToServerTime($this->CI->utils->convertTimestampToDateTime(@$key['endTime'])),
								// 'start_time' => $this->CI->utils->convertTimestampToDateTime(@$key['startTime']),
								// 'end_time' => $this->CI->utils->convertTimestampToDateTime(@$key['endTime']),
								'details' => @$key['details'],
								'external_uniqueid' => @$key['resultID'],
								'response_result_id' => $responseResultId,
							);
							$this->CI->seven77_game_logs->insertSeven77GameLogs($seven77GameData);
						}
					}
				}
			}

			$result['totalPage'] = $resultJsonArr['totalPage'];
		}
		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$this->CI->load->model(array('game_logs', 'player_model', 'seven77_game_logs', 'game_description_model'));
		$result = $this->CI->seven77_game_logs->getSeven77GameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$rlt = array('success' => true);
		if ($result) {
			$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $seven77data) {
				$player_id = $this->getPlayerIdInGameProviderAuth($seven77data->username);
				if (!$player_id) {
					continue;
				}

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

				$gameDate = new \DateTime($seven77data->transaction_time);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $seven77data->bet_amount;
				$result_amount = $seven77data->result_amount;

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($seven77data, $unknownGame, $gameDescIdMap);
				if (empty($game_description_id)) {
					$this->CI->utils->debug_log('empty game_description_id , seven77_game_logs_model.id=', $seven77data->id);
					continue;
				}
				if ($bet_amount == 0 && $result_amount == 0) {
					$this->CI->utils->debug_log('bet_amount and result amount is zero');
					continue;
				}

				$this->syncGameLogs($game_type_id,
					$game_description_id,
					$seven77data->game_code,
					$seven77data->game_type,
					$seven77data->game,
					$player_id,
					$player_username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$seven77data->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$seven77data->response_result_id);
			}
		} else {
			$rlt = array('success' => false);
		}
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

		$externalGameId = $row->game_code;
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