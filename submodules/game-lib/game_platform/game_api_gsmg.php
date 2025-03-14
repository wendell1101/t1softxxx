<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Get Player's Session ID
 * * Create Player
 * * Block/Unblock Player
 * * Deposit To game
 * * Withdraw from Game
 * * Get Currency
 * * Check Player Balance
 * * Check Player Daily Balance
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get Game Description Information
 * *
 * Behaviors not implemented
 * * Login/Logout player
 * * Update Player's information
 * * Check player's login status
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
 *
 *	extra_info:
{
"gsmg_api_account": "apiv8k",
"gsmg_api_password": "",
"gsmg_key": "",
"gsmg_currency": 8,
"gsmg_betting_profile_id_for_add_account": false,
"gsmg_language": "en-US",
"prefix_for_username": "b3",
"balance_in_game_log": false,
"adjust_datetime_minutes": 10
}
 *
 */

class Game_api_gsmg extends Abstract_game_api {
	private $gsmg_key;
	private $gsmg_api_account;
	private $gsmg_api_password;
	private $gsmg_currency;
	private $gsmg_api_url;
	private $gsmg_language;
	private $sessionid;

	const STATUS_SUCCESS = '0';
	const DEMO_GAME = 1;
	const REAL_GAME = 0;

	const URI_MAP = array(
		self::API_createPlayer => 'register',
		self::API_queryPlayerBalance => 'balance',
		self::API_depositToGame => 'deposit',
		self::API_withdrawFromGame => 'withdrawal',
		self::API_syncGameRecords => 'getspinbyspindata',
		self::API_checkLoginToken => 'sessionguid',
		self::API_login => 'loginlive',
		self::API_setMemberBetSetting => 'getbettingprofilelist',
		self::API_changePassword => 'edit',

	);

	public function __construct() {
		parent::__construct();
		$this->gsmg_key = $this->getSystemInfo('gsmg_key');
		$this->gsmg_api_account = $this->getSystemInfo('gsmg_api_account');
		$this->gsmg_api_password = $this->getSystemInfo('gsmg_api_password');
		$this->gsmg_api_url = $this->getSystemInfo('url');
		$this->gsmg_currency = $this->getSystemInfo('gsmg_currency');
		$this->gsmg_betting_profile_id = $this->getSystemInfo('gsmg_betting_profile_id_for_add_account');
		$this->gsmg_language = $this->getSystemInfo('gsmg_language');
		$this->gsmg_test_account = $this->getSystemInfo('gsmg_test_account');
		$this->gsmg_auto_prefix = $this->getSystemInfo('gsmg_auto_prefix');
	}

	public function getPlatformCode() {
		return GSMG_API;
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		if ($apiName == self::API_login) {
			$apiUri = $this->gameLaunchApi;
		}
		return $url = $this->gsmg_api_url . "/api/mg/" . $apiUri . ".ashx";
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['Code'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('gsmg got error: ', $responseResultId, 'result: ', $resultArr, 'playerName: ', $playerName);
			$success = false;
		}
		return $success;
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$extra = $this->CI->game_description_model->getGameTypeById($game['g']);
		$game['gp'] = "iframe_module/goto_gsmg_game/slots/" . $game['c']; //game param
		return $game;
	}

	public function getSessionId() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetSessionId',
		);

		$salt = random_string('alnum', 5);
		$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $salt);
		$params = array(
			"apiAccount" => $this->gsmg_api_account,
			"code" => $code,
		);
		return $this->callApi(self::API_checkLoginToken, $params, $context);
	}

	public function processResultForGetSessionId($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr);
		if ($success && isset($resultJsonArr['Data'])) {
			$this->sessionid = $resultJsonArr['Data'];
		}
		return array($success, array("result" => $resultJsonArr));
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUserName);

		$this->CI->utils->debug_log('<----------------------< GSMG Create Player >--------------------> GameUsername: ', $gameUserName, ' Password: ', $password);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUserName' => $gameUserName,
			'playerId' => $playerId,
		);

		$sessionGUID = null;
		$data = $this->getSessionId();
		if (!empty($data)) {
			$sessionGUID = $data['result']['Data'];
		}
		$this->CI->utils->debug_log('<----------------------< GSMG Create Player >--------------------> SessionGUID: ', $sessionGUID);

		$salt = random_string('alnum', 5);
		$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $gameUserName . $password . $salt);

		$params = array(
			"apiAccount" => $this->gsmg_api_account,
			"userName" => $gameUserName,
			"password" => $password,
			"currency" => $this->gsmg_currency,
			"bettingProfileID" => $this->gsmg_betting_profile_id,
			"sessionGUID" => $sessionGUID,
			"code" => $code,
		);

		$this->CI->utils->debug_log('<----------------------< GSMG Create Player >--------------------> Params: ', json_encode($params));
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
		// $this->CI->utils->debug_log('playerName', $playerName, 'oldPassword', $oldPassword, 'newPassword', $newPassword);

		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);

		$this->CI->utils->debug_log('gameUserName', $gameUserName, 'playerName', $playerName, 'newPassword', $newPassword);

		// $playerId=null;
		$playerId = $this->getPlayerIdInPlayer($playerName);
		if (empty($gameUserName)) {
			// $playerId = $this->getPlayerIdInPlayer($playerName);
			parent::createPlayer($playerName, $playerId, $newPassword);
			$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		}

		if (!empty($gameUserName)) {
			//EditAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForChangePassword',
				'playerName' => $playerName,
				'playerId'=>$playerId,
				'gameUserName' => $gameUserName,
				'newPassword' => $newPassword,
			);

			$sessionGUID = null;
			$data = $this->getSessionId();
			if (!empty($data)) {
				$sessionGUID = $data['result']['Data'];
			}
			$password = $newPassword;

			$salt = random_string('alnum', 5);
			$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $gameUserName . $password . $salt);

			return $this->callApi(self::API_changePassword,
				[
					"apiAccount" => $this->gsmg_api_account,
					"userName" => $gameUserName,
					"password" => $password,
					"bettingProfileID" => $this->gsmg_betting_profile_id,
					"sessionGUID" => $sessionGUID,
					"code" => $code,
				], $context
			);

			// return array("success" => true);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}
	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		// $resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$resultJson = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$this->CI->utils->debug_log('change password result', $success,'for player',$playerId,' to ', $newPassword);
		if ($success) {
			// $playerId = $this->getPlayerIdInPlayer($playerName);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}
		return array($success, null);
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
		$userName = $playerName;
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferFundToGame',
			'userName' => $userName,
			'gameUserName' => $gameUserName,
			'amount' => $amount,
		);

		$sessionGUID = null;
		$data = $this->getSessionId();
		if (!empty($data)) {
			$sessionGUID = @$data['result']['Data'];
		}

		if(empty($sessionGUID)){
			//empty session return false
			return ['success'=>false, 'message'=>'session is empty'];
		}

		$salt = random_string('alnum', 5);
		$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $gameUserName . $amount . $salt);

		$params = array(
			"apiAccount" => $this->gsmg_api_account,
			"userName" => $gameUserName,
			"amount" => $amount,
			"currency" => $this->gsmg_currency,
			"sessionGUID" => $sessionGUID,
			"code" => $code,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForTransferFundToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$userName = $this->getVariableFromContext($params, 'userName');
		$gameUserName = $this->getVariableFromContext($params, 'gameUserName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transId = $this->getVariableFromContext($params, 'transId');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUserName);
		$result = array('response_result_id' => $responseResultId);

		$afterBalance = null;
		$externalTransactionId = null;
		if ($success) {
			//get current sub wallet balance
			$data = $this->queryPlayerBalance($userName);
			if (isset($data['balance'])) {
				$afterBalance = $data['balance'];
			}
			//for sub wallet
			$externalTransactionId = $resultJsonArr['Data'];

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUserName);
			if ($playerId) {
				//deposit
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
		$userName = $playerName;
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$transId = uniqid();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferFundToGame',
			'gameUserName' => $gameUserName,
			'userName' => $userName,
			'amount' => $amount,
			'transId' => $transId,
		);

		$sessionGUID = null;
		$data = $this->getSessionId();
		if (!empty($data)) {
			$sessionGUID = @$data['result']['Data'];
		}

		if(empty($sessionGUID)){
			//empty session return false
			return ['success'=>false, 'message'=>'session is empty'];
		}

		$salt = random_string('alnum', 5);
		$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $gameUserName . $amount . $salt);

		$params = array(
			"apiAccount" => $this->gsmg_api_account,
			"userName" => $gameUserName,
			"amount" => $amount,
			"currency" => $this->gsmg_currency,
			"sessionGUID" => $sessionGUID,
			"code" => $code,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	//===end withdrawFromGame=====================================================================================
	public function getCurrency() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetCurrency',
		);

		$data = $this->getSessionId();
		$sessionGUID = $data['result']['Data'];

		$salt = random_string('alnum', 5);
		$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $salt);

		$params = array(
			"apiAccount" => $this->gsmg_api_account,
			"sessionGUID" => $sessionGUID,
			"code" => $code,
		);
		return $this->callApi(self::API_setMemberBetSetting, $params, $context);
	}

	public function processResultForGetCurrency($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		if ($success && isset($resultJsonArr['Data'])) {
			$this->sessionid = $resultJsonArr['Data'];
		}
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
		// $gameUserName = $playerName;
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUserName' => $gameUserName,
		);

		$sessionGUID = null;
		$data = $this->getSessionId();
		if (!empty($data)) {
			$sessionGUID = @$data['result']['Data'];
		}

		$salt = random_string('alnum', 5);
		$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $gameUserName . $salt);

		$params = array(
			"apiAccount" => $this->gsmg_api_account,
			"userName" => $gameUserName,
			"sessionGUID" => $sessionGUID,
			"code" => $code,
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
			$result["balance"] = floatval(@$resultJsonArr['Data']);
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
		$loginUsername = null;
		$password = null;
		$isDemoWithoutLogin = false;

		//if player is null, use test account to play demo game
		if (empty($playerName) && $param['game_mode']) {
			$loginUsername = $this->gsmg_test_account;
			$isDemoWithoutLogin = true;
		} else {
			$loginUsername = $this->getGameUsernameByPlayerUsername($playerName);
		}
		$password = $this->getPasswordByGameUsername($loginUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForward',
			'gameUsername' => $loginUsername,
		);

		$data = $this->getSessionId();
		$sessionGUID = $data['result']['Data'];
		$salt = random_string('alnum', 5);

		if ($param['game_type'] == 'live') {
			$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $loginUsername . $password . $this->gsmg_language . $salt);
			$params = array(
				"apiAccount" => $this->gsmg_api_account,
				"userName" => $loginUsername,
				"password" => $password,
				"lang" => $this->gsmg_language,
				"code" => $code,
			);
			$this->gameLaunchApi = 'loginlive';
			$apiData = $this->callApi(self::API_login, $params, $context);
			$url = $apiData['result']['Data'];
		} elseif ($param['game_type'] == 'slots') {
			if ($param['game_mode'] == 'demo' && $isDemoWithoutLogin) {
				$game_mode = self::DEMO_GAME;
			} else {
				$game_mode = $param['game_mode'] == 'demo' ? self::DEMO_GAME : self::REAL_GAME;
			}

			$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $loginUsername . $password . $param['game_code'] . $this->gsmg_language . $game_mode . $salt);
			$params = array(
				"apiAccount" => $this->gsmg_api_account,
				"userName" => $loginUsername,
				"password" => $password,
				"gameName" => $param['game_code'],
				"lang" => $this->gsmg_language,
				"isDemo" => $game_mode,
				"code" => $code,
			);

			$this->gameLaunchApi = 'loginelectronic';
			$apiData = $this->callApi(self::API_login, $params, $context);
			$url = $apiData['result']['Data'];
		} elseif ($param['game_type'] == 'mobile') {
			$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $loginUsername . $password . $param['game_code'] . $this->gsmg_language . $salt);
			$params = array(
				"apiAccount" => $this->gsmg_api_account,
				"userName" => $loginUsername,
				"password" => $password,
				"gameType" => $param['side_game_api'], #1=slots,2=live
				"gameName" => $param['game_code'],
				"lang" => $this->gsmg_language,
				"code" => $code,
			);
			$this->gameLaunchApi = 'mobilelogin';
			$apiData = $this->callApi(self::API_login, $params, $context);
			$url = $apiData['result']['Data'];
		}

		return array(
			'success' => true,
			'url' => $url,
			'iframeName' => "GSMG API",
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

	const START_ROW_ID = 0;

	//===start syncGameRecords=====================================================================================
	public function syncOriginalGameLogs($token) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$this->CI->load->model(array('external_system'));
		// $lastSyncId = $this->CI->external_system->getLastSyncIdByGamePlatform(GSMG_API);

		//order is parameter, db, const
		$lastRowId = $this->getValueFromSyncInfo($token, 'lastRowId');

		$sys = $this->CI->external_system->getSystemById($this->getPlatformCode());
		if (empty($lastRowId)) {
			$lastRowId = $sys->last_sync_id;
		}

		if (empty($lastRowId)) {
			//
			$lastRowId = self::START_ROW_ID;
		}

		$success = true;
		while ($success) {

			$salt = random_string('alnum', 5);
			$code = $salt . md5($this->gsmg_key . $this->gsmg_api_account . $lastRowId . $salt);
			$params = array(
				"apiAccount" => $this->gsmg_api_account,
				"rowID" => $lastRowId,
				"code" => $code,
			);

			$this->CI->utils->debug_log('<--------------------------------< GSMG SYNCORIGINALGAMELOGS >--------------------------------> PARAMS: ', json_encode($params));
			$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

			$success = $rlt['success'];
			$lastRowId = $rlt['maxRowId'];
			if (empty($lastRowId)) {
				break;
			}

			$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $lastRowId);
		}

		return array('success' => $success, 'lastRowId' => $lastRowId);

	}

	private $conversion_rate = 100;

	public function convertDBAmountToGame($amount) {
		return round($amount * $this->conversion_rate);
	}

	public function convertGameAmountToDB($amount) {
		//only need 2
		return round(floatval(floatval($amount) / $this->conversion_rate), 2);
	}

	public function getGameTimeToServerTime() {
		return '+8 hours';
	}

	public function getServerTimeToGameTime() {
		return '-8 hours';
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('GSMG SYNC API RESULT >-------------------------------->: ', count($resultJson));
		$this->CI->load->model(array('gsmg_game_logs', 'external_system'));
		$result = array();
		$maxRowId = null;
		if ($success) {
			$gameRecords = null;
			if (isset($resultJson['Data'])) {
				$gameRecords = $resultJson['Data'];
				$availableRows = $this->CI->gsmg_game_logs->getAvailableRows($gameRecords, $maxRowId);
				$this->CI->utils->debug_log('gameRecords', count($gameRecords), 'availableRows', count($availableRows), 'maxRowId', $maxRowId);

				if (!empty($availableRows)) {
					foreach ($availableRows as $key) {
						$total_wager = $key["TotalWager"];
						$total_payout = $key["TotalPayout"];
						$gsmgGameData = array(
							"row_id" => @$key["RowId"],
							"account_number" => @$key["AccountNumber"],
							"game_account_without_prefix"=> $this->convertAutoPrefixUsername(@$key["AccountNumber"]),
							"display_name" => @$key["DisplayName"],
							"game_name" => @$key["GameName"],
							"display_game_category" => @$key["DisplayGameCategory"],
							"session_id" => @$key["SessionId"],
							"game_end_time" => $this->gameTimeToServerTime($key["GameEndTime"]),
							"total_wager" => $total_wager,
							"total_payout" => $total_payout,
							"result_amount" => $total_payout, //result with bet
							"progressive_wage" => @$key["ProgressiveWager"],
							"iso_code" => @$key["ISOCode"],
							"game_platform" => @$key["GamePlatform"],
							"module_id" => @$key["ModuleId"],
							"client_id" => @$key["ClientId"],
							"transaction_id" => @$key["TransactionId"],
							"pca" => @$key["PCA"],
							"external_uniqueid" => @$key["RowId"],
							"response_result_id" => $responseResultId,
						);
						$this->CI->gsmg_game_logs->insertGameLogs($gsmgGameData);

						//will update last sync id
						// $this->CI->external_system->updateLastSyncId(GSMG_API, array("last_sync_id" => $key["RowId"], "last_sync_datetime" => $this->CI->utils->getNowForMysql()));
					}
				}
			}
		}
		$result['responseResultId'] = $responseResultId;
		$result['maxRowId'] = $maxRowId;
		return array($success, $result);
	}

	public function convertAutoPrefixUsername($username) {
		if (substr($username, 0, strlen($this->gsmg_auto_prefix)) == $this->gsmg_auto_prefix) {
			$username = substr($username, strlen($this->gsmg_auto_prefix));
		}
		return $username;
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$this->CI->load->model(array('player_model', 'gsmg_game_logs', 'game_description_model', 'game_logs'));
		$result = $this->CI->gsmg_game_logs->getGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'), $this->gsmg_auto_prefix);
		$rlt = array('success' => true);
		if ($result) {

			$this->CI->utils->debug_log('get original result', count($result));

			$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $gsmgdata) {

				$player_id = $gsmgdata->player_id;
				$gameUsername= $gsmgdata->account_number;
				// $gameUsername = $this->convertAutoPrefixUsername($gsmgdata->account_number);
				// $player_id = $this->getPlayerIdInGameProviderAuth($gameUsername);
				if (!$player_id) {
					// if($this->CI->utils->)
					// $this->utils->error_log('unknown player', $username, $gsmgdata->account_number);
					continue;
				}

				// $player = $this->CI->player_model->getPlayerById($player_id);
				// $player_username = $player->username;

				$gameDate = new \DateTime($gsmgdata->game_end_time);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				$bet_amount = $this->convertGameAmountToDB($gsmgdata->bet_amount);
				$result_with_bet = $this->convertGameAmountToDB($gsmgdata->total_payout);
				$result_amount = $result_with_bet - $bet_amount;

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($gsmgdata, $unknownGame, $gameDescIdMap);
				if (empty($game_description_id)) {
					$this->CI->utils->error_log('empty game_description_id , gsmg_game_logs_model.id=', $gsmgdata->id);
					continue;
				}
				if ($bet_amount == 0 && $result_amount == 0) {
					$this->CI->utils->error_log('bet_amount and result amount are zero');
					continue;
				}
				$extra_info = ['table' => $gsmgdata->session_id];

				$this->syncGameLogs($game_type_id,
					$game_description_id,
					$gsmgdata->game_code,
					$gsmgdata->game_type,
					$gsmgdata->game,
					$player_id,
					$gameUsername,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$gsmgdata->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$gsmgdata->response_result_id,
					Game_logs::FLAG_GAME,
					$extra_info);
				// $this->CI->utils->debug_log('player usename synced player',$player_username);
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