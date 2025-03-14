<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_xhtdlottery extends Abstract_game_api {

	private $xhtdlottery_secret_key;
	private $xhtdlottery_api_url;
	private $xhtdlottery_game_url;
	private $xhtdlottery_gamehistory_url;

	const STATUS_SUCCESS = '0';
	const AES_CIPHER = MCRYPT_RIJNDAEL_128;
	const AES_MODE = MCRYPT_MODE_ECB;

	public function __construct() {
		parent::__construct();
		$this->xhtdlottery_secret_key = $this->getSystemInfo('xhtdlottery_secret_key');
		$this->xhtdlottery_api_url = $this->getSystemInfo('url');
		$this->xhtdlottery_game_url = $this->getSystemInfo('xhtdlottery_game_url');
		$this->xhtdlottery_gamehistory_url = $this->getSystemInfo('xhtdlottery_gamehistory_url');
		$this->xhtdlottery_id = $this->getSystemInfo('xhtdlottery_id');
	}

	public function getPlatformCode() {
		return XHTDLOTTERY_API;
	}

	public function generateUrl($apiName, $params) {
		return $this->xhtdlottery_api_url . '/api.php?id=' . $this->xhtdlottery_id;
	}

	protected function customHttpCall($ch, $params) {
		$post = $this->encryptStr(json_encode($params), $this->xhtdlottery_secret_key);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $returnJson, $playerName = null) {
		$success = !empty($returnJson) && $returnJson['res'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('xhtdlottery got error', $responseResultId, 'playerName', $playerName, 'result', $returnJson);
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
		$encryptedPassword = md5($password);
		$cmd = 'register';
		$time = time();
		$token = md5($cmd . $playerName . $encryptedPassword . $time . $this->xhtdlottery_secret_key);
		$params = array(
			"cmd" => $cmd,
			"username" => $playerName,
			"password" => $encryptedPassword,
			"time" => $time,
			"token" => $token,
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	private function encryptStr($str, $screct_key) {
		$screct_key = $this->parseKey($screct_key);
		$str = $this->pkcs5_pad($str);
		$encrypt_str = @mcrypt_encrypt(self::AES_CIPHER, $screct_key, $str, self::AES_MODE);
		return base64_encode($encrypt_str);
	}

	private function decryptStr($str, $screct_key) {
		$str = base64_decode($str);
		$screct_key = $this->parseKey($screct_key);
//        $iv = mcrypt_create_iv(mcrypt_get_iv_size(self::AES_CIPHER, self::AES_MODE), MCRYPT_RAND);
		$encrypt_str = @mcrypt_decrypt(self::AES_CIPHER, $screct_key, $str, self::AES_MODE);
		return $this->pkcs5_unpad($encrypt_str);
	}

	private function pkcs5_unpad($str) {
		//$pad = ord($str{strlen($str) - 1});
        $pad = ord(substr($str, -1));

		if ($pad > strlen($str)) {
			return false;
		}

		if (strspn($str, chr($pad), strlen($str) - $pad) != $pad) {
			return false;
		}

		return substr($str, 0, -1 * $pad);
	}

	private function parseKey($screct_key) {
		if (strlen($screct_key) > 16) {
			$screct_key = substr($screct_key, 0, 16);
		}
		return $screct_key;
	}

	private function pkcs5_pad($str) {
		$blocksize = @mcrypt_get_block_size(self::AES_CIPHER, self::AES_MODE);
		$pad = $blocksize - (strlen($str) % $blocksize);
		return $str . str_repeat(chr($pad), $pad);
	}

	public function processResultForCreatePlayer($params) {
		// $this->CI->utils->debug_log($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {

			// $this->CI->utils->debug_log($resultText);

			$resultText = $this->decryptStr($resultText, $this->xhtdlottery_secret_key);

			// $this->CI->utils->debug_log($resultText);

			$resultJson = json_decode($resultText, true);
			$this->CI->utils->debug_log($resultJson);
			// $resultXml = $this->getResultXmlFromParams($params);
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getVariableFromContext($params, 'playerId');
			$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		}
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
		$transfer_id = random_string('alpha');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'reference_id' => $transfer_id,
		);

		$cmd = 'transferOpt';
		$time = time();
		$token = md5($cmd . $playerName . $amount . $transfer_id . $time . $this->xhtdlottery_secret_key);
		$params = array(
			"cmd" => $cmd,
			"username" => $playerName,
			"amount" => $amount,
			"transferId" => $transfer_id,
			"time" => $time,
			"token" => $token,
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');

		$success = false;
		if (!empty($resultText)) {
			$resultText = $this->decryptStr($resultText, $this->xhtdlottery_secret_key);
			$resultJson = json_decode($resultText, true);
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getVariableFromContext($params, 'playerId');
			$transfer_id = $this->getVariableFromContext($params, 'transfer_id');
			$amount = $this->getVariableFromContext($params, 'amount');
			$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		}
		// $result = array();
		$result = array('response_result_id' => $responseResultId);
		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
			// $ptrlt = $resultJson['result'];
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = null; // $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			$result["transfer_id"] = $transfer_id;
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

		$cmd = 'transferOpt';
		$time = time();
		$token = md5($cmd . $playerName . $amount . $transfer_id . $time . $this->xhtdlottery_secret_key);
		$params = array(
			"cmd" => $cmd,
			"username" => $playerName,
			"amount" => $amount,
			"transferId" => $transfer_id,
			"time" => $time,
			"token" => $token,
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');

		$success = false;
		if (!empty($resultText)) {
			$resultText = $this->decryptStr($resultText, $this->xhtdlottery_secret_key);
			$resultJson = json_decode($resultText, true);
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getVariableFromContext($params, 'playerId');
			$transfer_id = $this->getVariableFromContext($params, 'transfer_id');
			$amount = $this->getVariableFromContext($params, 'amount');
			$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		}

		// $result = array();
		$result = array('response_result_id' => $responseResultId);
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

		$cmd = 'login';
		$time = time();
		$encryptedPassword = md5($password);
		$token = md5($cmd . $playerName . $encryptedPassword . $time . $this->xhtdlottery_secret_key);
		$params = array(
			"cmd" => $cmd,
			"username" => $playerName,
			"password" => $encryptedPassword,
			"time" => $time,
			"token" => $token,
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$resultText = $this->decryptStr($resultText, $this->xhtdlottery_secret_key);
			$resultJson = json_decode($resultText, true);
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
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

		$cmd = 'getBalance';
		$time = time();
		$token = md5($cmd . $playerName . $time . $this->xhtdlottery_secret_key);
		$params = array(
			"cmd" => $cmd,
			"username" => $playerName,
			"time" => $time,
			"token" => $token,
		);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$success = false;
		if (!empty($resultText)) {
			$resultText = $this->decryptStr($resultText, $this->xhtdlottery_secret_key);
			$resultJson = json_decode($resultText, true);
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		}
		$result = array();

		if ($success && isset($resultJson['balance']) && @$resultJson['balance'] !== null) {
			$result["balance"] = floatval($resultJson['balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				'balance', @$resultJson['balance']);
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

	//===start totaxhtdlotteryettingAmount=====================================================================================
	public function totaxhtdlotteryettingAmount($playerName, $dateFrom, $dateTo) {

	}
	//===end totaxhtdlotteryettingAmount=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {

	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {

	}
	//===end queryTransaction=====================================================================================

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $extra) {
		$result = $this->login($playerName, $extra['password']);
		$url = $result['url'];
		return array('success' => true, 'url' => $url);
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

		$startTime = new DateTime($startTime->format('Y-m-d H:i:s'));
		$endTime = new DateTime($endTime->format('Y-m-d H:i:s'));
		$startTime->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startTime', $startTime, 'endTime', $endTime);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);
		$cmd = 'getBetHistory';
		$time = time();
		$token = md5($cmd . $startTime->format('Y-m-d H:i:s') . $endTime->format('Y-m-d H:i:s') . $time . $this->xhtdlottery_secret_key);
		$params = array(
			"cmd" => $cmd,
			"startTime" => $startTime->format('Y-m-d H:i:s'),
			"endTime" => $endTime->format('Y-m-d H:i:s'),
			"time" => $time,
			"token" => $token,
		);
		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	protected function getResultJsonFromParams($params) {
		$params['resultText'] = $this->decryptStr($params['resultText'], $this->xhtdlottery_secret_key);
		return $this->convertResultJsonFromParams($params);
	}

	public function processResultForSyncGameRecords($params) {

		$this->CI->load->model('xhtdlottery_game_logs');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		if (isset($resultJson['data'])) {
			$gameRecords = (array) $resultJson['data'];
		} else {
			$gameRecords = array();
			$this->CI->utils->debug_log('XHTDLOTTERY logs is empty');
		}
		if ($gameRecords) {

			$availableRows = $this->CI->xhtdlottery_game_logs->getAvailableRows($gameRecords);

			$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

			foreach ($availableRows as $record) {
				$this->CI->xhtdlottery_game_logs->insertXhtdGameLogs(array(
					'bet_id' => @$record['betId'],
					'user' => @$record['user'],
					'game_id' => @$record['gameId'],
					'game_name' => @$record['gameName'],
					'money' => @$record['money'],
					'bet_type' => @$record['betType'],
					'time' => @$record['time'],
					'result' => @$record['result'],
					'external_uniqueid' => @$record['betId'],
					'response_result_id' => $responseResultId,
				));
			}
		}

		$success = $this->processResultBoolean($responseResultId, $resultJson, NULL);

		return array($success, $resultJson);
	}

	private function copyRowToDB($row, $responseResultId) {
		$result = array(
			'member_id' => $row['member_id'],
			'member_type' => $row['member_type'],
			'session_token' => $row['session_token'],
			'bet_id' => $row['bet_id'],
			'bet_no' => $row['bet_no'],
			'match_no' => $row['match_no'],
			'match_area' => $row['match_area'],
			'match_id' => $row['match_id'],
			'bet_type' => $row['bet_type'],
			'bet_content' => $row['bet_content'],
			'bet_currency' => $row['bet_currency'],
			'bet_money' => $row['bet_money'],
			'bet_odds' => $row['bet_odds'],
			'bet_winning' => $row['bet_winning'],
			'bet_win' => $row['bet_win'],
			'bet_status' => $row['bet_status'],
			'bet_time' => $row['bet_time'],
			'trans_time' => $row['trans_time'],
			'game_platform' => XHTDLOTTERY_API,
			'external_uniqueid' => $row['bet_id'],
		);

		$this->CI->xhtdlottery_game_logs->insertXhtdlotteryGameLogs($result);
	}

	private function getStringValueFromXml($xml, $key) {
		$value = (string) $xml[$key];
		if (empty($value) || $value == 'null') {
			$value = '';
		}

		return $value;
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('game_logs', 'player_model', 'xhtdlottery_game_logs', 'game_description_model'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$rlt = array('success' => true);
		$result = $this->CI->xhtdlottery_game_logs->getXhtdGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$cnt = 0;
		if ($result) {

			//$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $xhtdlotterydata) {

				if (!$player_id = $this->getPlayerIdInGameProviderAuth($xhtdlotterydata->gameUsername)) {
					continue;
				}

				$cnt++;

				$player = $this->CI->player_model->getPlayerById($player_id);

				$bet_amount = $this->gameAmountToDB($xhtdlotterydata->money);
				$result_amount = $this->gameAmountToDB($xhtdlotterydata->result);

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($xhtdlotterydata, $gameDescIdMap);

				//$game_description_id = $xhtdlotterydata->game_description_id;
				// $game_type_id = $xhtdlotterydata->game_type_id;

				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }

				$player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $player->username;

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$xhtdlotterydata->game,
					$game_type_id,
					$xhtdlotterydata->game,
					$player_id,
					$player->username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$xhtdlotterydata->external_uniqueid,
					$xhtdlotterydata->time,
					$xhtdlotterydata->time,
					$xhtdlotterydata->response_result_id
				);

			}
		}
	}

	// private function getGameDescriptionInfo($row, $unknownGame) {
	// 	$externalGameId = $row->bet_type;
	// 	$extra = array('game_code' => $externalGameId);
	// 	$game_description_id = null;
	// 	$game_type_id = null;
	// 	$game = $externalGameId;
	// 	$gametype = $externalGameId;
	// 	return $this->processUnknownGame(
	// 		$game_description_id, $game_type_id,
	// 		$game, $gametype, $externalGameId, $extra,
	// 		$unknownGame);
	// }

	private function getGameDescriptionInfo($row, $gameDescIdMap) {

		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}

		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		$externalGameId = $row->game;
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

		$extra = array('game_code' => $row->game);

		return $this->processUnknownGame($game_description_id, $game_type_id, $row->game, null, $externalGameId, $extra);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	public function decryptResponseStr($str, $screct_key) {

		return $this->decryptStr($str, $screct_key);
	}
	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start isPlayerExist=====================================================================================
	public function isPlayerExist($playerName) {
		$result = $this->queryPlayerBalance($playerName);
		$result["exists"] = $result['success'];
		$result['success'] = true;
		return $result;
	}

	//===end isPlayerExist=====================================================================================
}

/*end of file*/