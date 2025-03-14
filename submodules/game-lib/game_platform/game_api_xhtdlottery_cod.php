<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_xhtdlottery_cod extends Abstract_game_api {

	private $xhtd_lottery_secret_key;
	private $xhtd_lottery_api_url;
	private $xhtd_lottery_game_url;
	private $xhtd_lottery_gamehistory_url;

	public function __construct() {
		parent::__construct();
		$this->xhtd_lottery_secret_key = $this->getSystemInfo('xhtd_lottery_secret_key');
		$this->xhtd_lottery_api_url = $this->getSystemInfo('xhtd_lottery_api_url');
		$this->xhtd_lottery_game_url = $this->getSystemInfo('xhtd_lottery_game_url');
		$this->xhtd_lottery_gamehistory_url = $this->getSystemInfo('xhtd_lottery_gamehistory_url');
	}

	public function getPlatformCode() {
		return XHTDLOTTERY_API;
	}

	public function generateUrl($apiName, $params) {
		if($apiName == self::API_createPlayer || $apiName == self::API_login){
			return $this->xhtd_lottery_game_url;
		}elseif($apiName == self::API_syncGameRecords){
			return $this->xhtd_lottery_gamehistory_url;
		}else{
			return $this->xhtd_lottery_api_url;
		}
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $returnResult, $playerName = null) {
		$success = !empty($returnResult);
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('xhtd_lottery got error', $responseResultId, 'playerName', $playerName, 'result', $returnResult);
		}
		return $success;
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

		$params = array(
			"uno" => $playerName,
			"pw" => $password,
			"sign" => md5($playerName . $this->xhtd_lottery_secret_key . $this->getPassword($playerName)['password']),
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$success = $this->processResultBoolean($responseResultId, $resultText, $playerName);
		}

		$result = array(
			'playerName' =>$this->getVariableFromContext($params, 'playerName'),
			'playerId' => $this->getVariableFromContext($params, 'playerId'),
			'resultText' => $resultText,
			);
		return array($success, $result);
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
		return array("success" => true);
	}

	public function processResultForBlockPlayer($params) {
		return array($success, null);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		return array("success" => true);
	}
	public function processResultForUnblockPlayer($params) {
		return array("success" => true);
	}
	//===end unblockPlayer=====================================================================================

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
		);
		$params = array(
			"uno" => $playerName,
			"pw" => $this->getPassword($playerName)['password'],
			"qty" => $amount,
			"opkind" => 'pushin',
			"sign" => md5($playerName . $this->xhtd_lottery_secret_key . $this->getPassword($playerName)['password']),
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$success = $this->processResultBoolean($responseResultId, $resultText, $playerName);
		}
		$result = array();
		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
			$result["external_transaction_id"] = null;
			$result["currentplayerbalance"] = $afterBalance;
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
		$transfer_id = random_string('alpha');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'reference_id' => $transfer_id,
		);

		$params = array(
			"uno" => $playerName,
			"pw" => $this->getPassword($playerName)['password'],
			"qty" => $amount,
			"opkind" => 'pushout',
			"sign" => md5($playerName . $this->xhtd_lottery_secret_key . $this->getPassword($playerName)['password']),
		);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$success = $this->processResultBoolean($responseResultId, $resultText, $playerName);
		}

		$result = array();
		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
			// $ptrlt = $resultJson['result'];
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = null; // $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			$result["reference_id"] = $reference_id;
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
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);

		$params = array(
			"uno" => $playerName,
			"pw" => $password,
			"sign" => md5($playerName . $this->xhtd_lottery_secret_key . $this->getPassword($playerName)['password']),
		);
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$success = $this->processResultBoolean($responseResultId, $resultText, $playerName);
		}
		return array($success, $resultJson);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		return array("success" => true);
	}

	public function processResultForLogout($params) {
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

		$params = array(
			"uno" => $playerName,
			"pw" => $this->getPassword($playerName)['password'],
			"qty" => $amount,
			"opkind" => 'viewmoney',
			"sign" => md5($playerName . $this->xhtd_lottery_secret_key . $this->getPassword($playerName)['password']),
		);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$success = $this->processResultBoolean($responseResultId, $resultText, $playerName);
		}

		$result = array();
		if ($success && isset($resultText) && @$resultText !== null) {
			$result["balance"] = floatval($resultText);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				'balance', @$resultText);
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

	//===start totaxhtd_lotteryettingAmount=====================================================================================
	public function totaxhtd_lotteryettingAmount($playerName, $dateFrom, $dateTo) {

	}
	//===end totaxhtd_lotteryettingAmount=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {

	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {

	}
	//===end queryTransaction=====================================================================================

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $extra) {
		return array('success' => true, 'url' => $this->xhtd_lottery_game_url, 'redirect_url' => $this->getSystemInfo('xhtd_lottery_redirect_url'));
	}
	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	const START_PAGE = 0;
	public function syncOriginalGameLogs($token) {
		$startTime = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endTime = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$playerName = parent::getValueFromSyncInfo($token, 'playerName');
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');

		$startTime = new DateTime($startTime->format('Y-m-d H:i:s'));
		$endTime = new DateTime($endTime->format('Y-m-d H:i:s'));
		$startTime->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startTime', $startTime, 'endTime', $endTime);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'playerName' => $playerName,
			'syncId' => $syncId,
		);
		$params = array(
			"d1" => $startTime->format("Y-m-d H:i:s"),
			"d2" => $endTime->format("Y-m-d H:i:s"),
			"sign" => md5("key=" . $this->xhtd_lottery_secret_key),
		);
		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$result = array();
		$success = false;
		if (!empty($resultText)) {
			$success = $this->processResultBoolean($responseResultId, $resultText, $playerName);
		}
		if (!empty($resultText) && $success) {
			$origResult = explode(";", $resultText);
			$this->CI->utils->debug_log('XLCOD Orig Result =>>>>', $origResult);
			foreach ($origResult as $key) {
				$resultArr = explode("@", $key);
				$this->CI->utils->debug_log('XLCOD Splited Result =>>>>', $resultArr);
				if(!empty($resultArr) && count($resultArr) > 1){
					$gameRec['game_code'] = @$resultArr['0'];
					$gameRec['result_id'] = @$resultArr['1'];
					$gameRec['user_id'] = @$resultArr['2'];
					$gameRec['bet_content'] = @$resultArr['3'];
					$gameRec['odds'] = @$resultArr['4'];
					$gameRec['bet_amount'] = @$resultArr['5'];
					$gameRec['result_amount'] = @$resultArr['6'];
					$gameRec['timestamp'] = date("Y-m-d H:i:s", @$resultArr['7']);
					$gameRec['settle_flag'] = @$resultArr['8'];
					$gameRec['external_uniqueid'] = $gameRec['result_id'].$gameRec['user_id'];
					$gameRecords[] = $gameRec;
				}
			}
			// load models
			$this->CI->load->model(array('xlcod_game_logs', 'external_system'));
			if ($success && $gameRecords) {
				if (empty($gameRecords) || !is_array($gameRecords)) {
					$this->CI->utils->debug_log('wrong game records', $gameRecords);
				}

				if (!empty($gameRecords)) {
					$result = $gameRecords;
					$this->CI->utils->debug_log('gameRecords', $gameRecords);
					$availableRows = $this->CI->xlcod_game_logs->getAvailableRows($gameRecords);
					$this->CI->utils->debug_log('availableRows', count($availableRows), 'responseResultId', $responseResultId);
					foreach ($availableRows as $record) {
						$xlcodGameData = array(
							'game_code' => $record['game_code'],
							'result_id' => $record['result_id'],
							'user_id' => $record['user_id'],
							'bet_content' => $record['bet_content'],
							'odds' => $record['odds'],
							'bet_amount' => $record['bet_amount'],
							'result_amount' => $record['result_amount'],
							'timestamp' => $record['timestamp'],
							'settle_flag' => $record['settle_flag'],
							'external_uniqueid' => $record['external_uniqueid'],
							'response_result_id' => $responseResultId,
						);
						$this->CI->xlcod_game_logs->insertXlcodGameLogs($xlcodGameData);
					}
				}
			}
		}

		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$rlt = array('success' => true);
		$result = $this->getXlcodGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
		$this->CI->load->model(array('game_logs', 'player_model', 'xlcod_game_logs'));
		$cnt = 0;
		if ($result) {
			// var_dump($result);exit();
			$unknownGame = $this->getUnknownGame();
			foreach ($result as $xhtd_lotterydata) {
				if ( ! $player_id = $this->getPlayerIdInGameProviderAuth($xhtd_lotterydata->user_id)) continue;
				$cnt++;
				$player = $this->CI->player_model->getPlayerById($player_id);
				$gameDate = new \DateTime($xhtd_lotterydata->timestamp);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

				$bet_amount = $this->gameAmountToDB($xhtd_lotterydata->bet_amount);
				$result_amount = $this->gameAmountToDB($xhtd_lotterydata->result_amount);

				$game_description_id = $xhtd_lotterydata->game_description_id;
				$game_type_id = $xhtd_lotterydata->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$this->syncGameLogs($game_type_id, $game_description_id, $xhtd_lotterydata->game_code,
					$game_type_id, $xhtd_lotterydata->game_code, $player_id, $player->username,
					$bet_amount, $result_amount, null, null, null, null,
					$xhtd_lotterydata->external_uniqueid, $gameDateStr,
					$gameDateStr, $xhtd_lotterydata->response_result_id);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	private function getXlcodGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('xlcod_game_logs');
		return $this->CI->xlcod_game_logs->getXlcodGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}
}

/*end of file*/