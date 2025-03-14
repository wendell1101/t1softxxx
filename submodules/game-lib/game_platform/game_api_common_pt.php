<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_common_pt extends Abstract_game_api {

	const START_PAGE = 1;
	const ITEM_PER_PAGE = 3000;

	protected $transaction_status_declined;
	protected $transaction_status_approved;

	const DEFAULT_TRANSACTION_STATUS_APPROVED='approved';
	const DEFAULT_TRANSACTION_STATUS_DECLINED='declined';

	protected $launch_game_on_player;

	public function getPlatformCode(){
        return $this->returnUnimplemented();
    }

	public function __construct() {
		parent::__construct();
		// $this->merchantname = $this->getSystemInfo('key');
		// $this->merchantcode = $this->getSystemInfo('secret');
		// $this->api_url = $this->getSystemInfo('url');
		// $this->game_url = $this->getSystemInfo('game_url');
		// $this->currency = $this->getSystemInfo('currency');
		// $this->method = self::METHOD_POST;

		$this->perPageSize=$this->getSystemInfo('per_page_size', self::ITEM_PER_PAGE);
		$this->add_progressive_bet = $this->getSystemInfo('add_progressive_bet', false);
		$this->add_progressive_win = $this->getSystemInfo('add_progressive_win', true);
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
		$this->launch_game_on_player = $this->getSystemInfo('launch_game_on_player', true);

		$this->transaction_status_approved= $this->getSystemInfo('transaction_status_approved', self::DEFAULT_TRANSACTION_STATUS_APPROVED);
		$this->transaction_status_declined= $this->getSystemInfo('transaction_status_declined', self::DEFAULT_TRANSACTION_STATUS_DECLINED);

		$this->status_map=[
			$this->transaction_status_approved => self::COMMON_TRANSACTION_STATUS_APPROVED,
			$this->transaction_status_declined => self::COMMON_TRANSACTION_STATUS_DECLINED,
		];

        $this->load_pt_js_from_our_server=$this->getSystemInfo('load_pt_js_from_our_server', false);
        $this->languageCode=$this->getSystemInfo('language_code', 'zh-cn');

   	}

	protected function convertStatus($status){

		if(isset($this->status_map[$status])){
			return $$this->status_map[$status];
		}else{
			return self::COMMON_TRANSACTION_STATUS_PROCESSING;
		}

	}

	//Case-insensitive
	const VAR_LIST = array('playerName', 'adminName', 'kioskName', 'entityName', 'password', 'amount', 'externaltranid', 'startDate', 'endDate', 'token', 'currPage', 'perPage', 'countrycode');
	/**
	 * replace VAR_LIST in URI_MAP
	 */
	const URI_MAP = array(
		self::API_queryPlayerBalance => 'player/info/playername/[playerName]',
		self::API_createPlayer => 'player/create/playername/[playerName]/adminname/[adminName]/kioskname/[kioskName]/password/[password]',
		self::API_checkLoginStatus => 'player/online/playername/[playerName]',
		self::API_changePassword => 'player/update/playername/[playerName]/password/[password]/countrycode/[countrycode]',
		self::API_logout => 'player/logout/playername/[playerName]',
		self::API_isPlayerExist => 'player/info/playername/[playerName]',
		self::API_queryPlayerInfo => 'player/info/playername/[playerName]',
		self::API_depositToGame => 'player/deposit/playername/[playerName]/amount/[amount]/adminname/[adminName]/externaltranid/[externaltranid]',
		self::API_withdrawFromGame => 'player/withdraw/playername/[playerName]/amount/[amount]/adminname/[adminName]/externaltranid/[externaltranid]/isForce/1',
		self::API_updatePlayerInfo => 'player/update/playername/[playerName]/firstName/[firstname]/lastName/[lastname]',
		self::API_queryTransaction => 'player/checktransaction/externaltransactionid/[externaltranid]',
		self::API_syncGameRecords => 'customreport/getdata/reportname/PlayerGames/startdate/[startDate]/enddate/[endDate]/frozen/all/timeperiod/specify/sortby/playername/page/[currPage]/perPage/[perPage]',
		self::API_syncGameRecordsByPlayer => 'customreport/getdata/reportname/PlayerGames/startdate/[startDate]/enddate/[endDate]/frozen/all/timeperiod/specify/playername/[playerName]/sortby/gamedate/page/[currPage]/perPage/[perPage]',
		self::API_blockPlayer => 'player/update/playername/[playerName]/frozen/1',
		self::API_unblockPlayer => 'player/update/playername/[playerName]/frozen/0',
		self::API_checkLoginToken => 'player/checktoken/playername/[playerName]/token/[token]',
		self::API_batchQueryPlayerBalance => 'player/list/kioskname/[kioskName]/adminname/[adminName]/page/[currPage]/perPage/[perPage]',
		self::API_resetPlayer => 'player/resetFailedLogin/playername/[playerName]',
		self::API_revertBrokenGame => 'player/revertBrokenGame/playername/[playerName]',
		'listPlayers' => 'player/list/kioskname/[kioskName]/adminname/[adminName]/page/[currPage]/perPage/[perPage]',
	);

	function getApiAdminName() {
		return $this->getSystemInfo('API_ADMIN_NAME');
	}

	function getApiKioskName() {
		return $this->getSystemInfo('API_KIOSK_NAME');
	}

	function createVarMap($params) {
		$resultKey = array();
		$resultVal = array();
		foreach (self::VAR_LIST as $var) {
			if (array_key_exists($var, $params) && !empty($params[$var])) {
				$resultKey[] = '[' . $var . ']';
				if ($var == 'playerName') {
					$resultVal[] = urlencode(strtoupper($params[$var]));
				} else {
					$resultVal[] = urlencode($params[$var]);
				}
			}
		}
		return array($resultKey, $resultVal);
	}
	function generateUrl($apiName, $params) {
		// $playerName = $params["playerName"];
		$apiUri = self::URI_MAP[$apiName];
		list($keys, $values) = $this->createVarMap($params);
		//Case-insensitive
		$apiUri = str_ireplace($keys, $values, $apiUri);
		$url = $this->getSystemInfo('url') . $apiUri;

		if ($apiName == self::API_createPlayer) {
			//if exists custom02, add it
			$custom02 = $this->getSystemInfo('pt_create_player_custom02');
			if (!empty($custom02)) {
				//add custom02
				$url .= '/custom02/' . $custom02;
				$this->CI->utils->debug_log('pt url: ', $url);
			}
			$custom01 = $this->getSystemInfo('pt_create_player_custom01');
			if (!empty($custom01)) {
				//add custom01
				$url .= '/custom01/' . $custom01;
				// $this->CI->utils->debug_log('pt url: ', $url);
			}
			$custom03 = $this->getSystemInfo('pt_create_player_custom03');
			if (!empty($custom03)) {
				//add custom03
				$url .= '/custom03/' . $custom03;
				// $this->CI->utils->debug_log('pt url: ', $url);
			}
		} else if ($apiName == self::API_changePassword) {
			// $custom01 = $this->getSystemInfo('pt_create_player_custom01');
			// if (!empty($custom01)) {
			// 	//add custom01
			// 	$url .= '/custom01/' . $custom01;
			// 	// $this->CI->utils->debug_log('pt url: ', $url);
			// }
			$custom03 = $this->getSystemInfo('pt_create_player_custom03');
			if (!empty($custom03)) {
				//add custom03
				$url .= '/custom03/' . $custom03;
				// $this->CI->utils->debug_log('pt url: ', $url);
			}
		}
		$this->CI->utils->debug_log('pt url: ', $url);
		return $url;
	}
	function getHttpHeaders($params) {
		return array("Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Cache-Control" => "max-age=0",
			"Connection" => "keep-alive",
			"Keep-Alive" => "timeout=5, max=100",
			"Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.3",
			"Accept-Language" => "es-ES,es;q=0.8",
			"X_ENTITY_KEY" => $this->getSystemInfo('key'));
	}

	function initSSL($ch) {
		parent::initSSL($ch);
		// $apiCertPath = realpath(APPPATH) . '/';
		$key = realpath(@$this->getSystemInfo('API_CERT_KEY'));
		$pem = realpath(@$this->getSystemInfo('API_CERT_PEM'));

		$this->CI->utils->debug_log('key', $key, 'pem', $pem);
		if (!file_exists($key) || !file_exists($pem)) {
			$this->CI->utils->debug_log('file not found', 'key', $key, 'pem', $pem);
		}

		curl_setopt($ch, CURLOPT_SSLCERT, $pem);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSLKEY, $key);
	}

	// public function beforeProcessResult($apiName, $params, $resultText, $statusCode, $statusText = null, $extra = null) {
	// 	return array($resultText, $statusCode, $statusText);
	// }
	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		$result = array();
		// if ($success) {
		//check info
		$resultJson = json_decode($resultText, true);
		// unset($resultText);
		if ($apiName == self::API_queryPlayerBalance) {
			// return $this->processResultForQueryPlayerBalance($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_createPlayer) {
			// 	return $this->processResultForCreatePlayer($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_queryPlayerInfo) {
			// 	return $this->processResultForQueryPlayerInfo($apiName, $params, $responseResultId, $resultJson);
		} else if ($apiName == self::API_checkLoginStatus) {
			return $this->processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_changePassword) {
			// 	return $this->processResultForChangePassword($apiName, $params, $responseResultId, $resultJson);
		} else if ($apiName == self::API_logout) {
			return $this->processResultForLogout($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_depositToGame) {
			// 	return $this->processResultForDepositToGame($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_withdrawFromGame) {
			// 	return $this->processResultForWithdrawFromGame($apiName, $params, $responseResultId, $resultJson);
		} else if ($apiName == self::API_updatePlayerInfo) {
			return $this->processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson);
		} else if ($apiName == self::API_queryTransaction) {
			return $this->processResultForQueryTransaction($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_syncGameRecords) {
			// 	return $this->processResultForSyncGameRecords($apiName, $params, $responseResultId, $resultJson);
			// } else if ($apiName == self::API_isPlayerExist) {
			// 	return $this->processResultForIsPlayerExist($apiName, $params, $responseResultId, $resultJson);
		}

		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson);
		if ($this->CI->utils->notEmptyInArray('errorcode', $resultJson) || $this->CI->utils->notEmptyInArray('error', $resultJson)) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PT got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
			$success = false;
		}

		return $success;
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		$this->CI->utils->debug_log('username', $username, 'playerId', $playerId);
		// $success = false;
		$balance = null;
		$rlt = $this->isPlayerExist($username);
		$success = $rlt['success'];
		if ($rlt['success']) {
			if ($rlt['exists']) {
				//update register flag
				$this->updateRegisterFlag($playerId, true);
			} else {
				$rlt = $this->createPlayer($username, $password, $playerId);
				$success = $rlt['success'];
				if ($rlt['success']) {
					$this->updateRegisterFlag($playerId, true);
				}
			}
		}
		if ($success) {
			//update balance
			$rlt = $this->queryPlayerBalance($username);
			$success = $rlt['success'];
			if ($success) {
				//for sub wallet
				$balance = isset($rlt['balance']) ? floatval($rlt['balance']) : null;
				if ($balance !== null) {
					//update
					$this->updatePlayerSubwalletBalance($playerId, $balance);
				}
			}

		}
		return array('success' => $success, 'balance' => $balance);
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
			"playerName" => $playerName,
			"adminName" => $this->getApiAdminName(),
			"kioskName" => $this->getApiKioskName(),
			"password" => $password,
			'languagecode' => $this->languageCode,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		return array($success, null);
	}

	//===end createPlayer=====================================================================================

	//===start isPlayerExist======================================================================================
	function isPlayerExist($playerName) {
		$playerId=$this->getPlayerIdFromUsername($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId'=>$playerId,
		);

		return $callResult = $this->callApi(self::API_isPlayerExist, array("playerName" => $playerName),
			$context);
	}
	function processResultForIsPlayerExist($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');

		$success = false;
		$result = array('exists' => null, 'response_result_id'=>$responseResultId);
		// $this->CI->utils->debug_log('resultJson:', $resultJson);
		if ($this->processResultBoolean($responseResultId, $resultJson, $playerName)) {
			$success = true;
			$result["exists"] = true;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		} elseif ($resultJson['errorcode'] == '41') {
			$success = true;
			$result["exists"] = false;
		} else {
			$success = false;
			$result["exists"] = NULL;
		}

		return array($success, $result);
	}
	//===end isPlayerExist========================================================================================

	//===start queryPlayerInfo=====================================================================================
	function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerInfo',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerInfo, array("playerName" => $playerName),
			$context);
	}
	function processResultForQueryPlayerInfo($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$ptrlt = $resultJson['result'];
			$playerInfo = array(
				'playerName' => $ptrlt['PLAYERNAME'],
				// 'password' => $ptrlt['PASSWORD'],
				// 'email' => $ptrlt['EMAIL'],
				'languageCode' => $ptrlt['LANGUAGECODE'],
				'blocked' => ($ptrlt['FROZEN'] == 1),
			);
			//sync to db
			if ($ptrlt['FROZEN'] == 1) {
				$this->blockUsernameInDB($playerName);
			} else {
				$this->unblockUsernameInDB($playerName);
			}
			$result["playerInfo"] = $playerInfo;
		}

		return array($success, $result);
	}
	//===end queryPlayerInfo=====================================================================================

	const DEFAULT_COUNTRYCODE = 'CN';
	//===start changePassword=====================================================================================
	function changePassword($playerName, $oldPassword, $newPassword) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerUsername,
			'password' => $newPassword,
		);

		return $this->callApi(self::API_changePassword, array("playerName" => $playerName, "password" => $newPassword, 'countrycode' => self::DEFAULT_COUNTRYCODE),
			$context);
	}

	function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$result["password"] = $this->getVariableFromContext($params, 'password');
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getPlayerIdInPlayer($playerName);
			if ($playerId) {
				//sync password to game_provider_auth
				$this->updatePasswordForPlayer($playerId, $result["password"]);
			} else {
				$this->CI->utils->debug_log('cannot find player', $playerName);
			}
		}

		return array($success, $result);
	}
	//===end changePassword=====================================================================================

	//===start blockPlayer=====================================================================================
	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_blockPlayer, array("playerName" => $playerName),
			$context);
	}
	function processResultForBlockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$this->blockUsernameInDB($playerName);
		}
		// if ($success) {
		// 	$playerInfo = array('playerName' => $resultJson['result']['PLAYERNAME'],
		// 		'password' => $resultJson['result']['PASSWORD'],
		// 		'email' => $resultJson['result']['EMAIL'],
		// 		'languageCode' => $resultJson['result']['LANGUAGECODE']);
		// 	$result["playerInfo"] = $playerInfo;
		// }

		return array($success, $result);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_unblockPlayer, array("playerName" => $playerName),
			$context);
	}
	function processResultForUnblockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$this->unblockUsernameInDB($playerName);
		}
		// if ($success) {
		// 	$playerInfo = array('playerName' => $resultJson['result']['PLAYERNAME'],
		// 		'password' => $resultJson['result']['PASSWORD'],
		// 		'email' => $resultJson['result']['EMAIL'],
		// 		'languageCode' => $resultJson['result']['LANGUAGECODE']);
		// 	$result["playerInfo"] = $playerInfo;
		// }

		return array($success, $result);
	}
	//===end unblockPlayer=====================================================================================

	//===start depositToGame=====================================================================================
	function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		// $rlt = $this->logout($playerName);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $externaltranid = empty($transfer_secure_id) ? 'T'.random_string('md5') : $transfer_secure_id; // $transfer_secure_id ? $transfer_secure_id : random_string('uniqueid');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'externaltranid' => $externaltranid,
			'amount' => $amount,
            //mock testing
            'is_timeout_mock' => $this->getSystemInfo('is_timeout_mock', false),
            //for this api
            'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		return $this->callApi(self::API_depositToGame, array("playerName" => $playerName,
            "amount" => $this->dBtoGameAmount($amount), "adminName" => $this->getApiAdminName(),
			"externaltranid" => $externaltranid),
			$context);
	}
	function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array('response_result_id' => $responseResultId);

        $result['status'] = $success ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_PROCESSING;

		if ($success) {
			$ptrlt = $resultJson['result'];
            $afterBalance = $this->gameAmountToDB(floatval($ptrlt['currentplayerbalance']));
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
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
			// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);
		}

		return array($success, $result);
	}
	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		// $rlt = $this->logout($playerName);
		// $this->CI->utils->debug_log('logout', $rlt);
		// if ($lookupGameUsername) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// }

        $externaltranid = empty($transfer_secure_id) ? 'T'.random_string('md5') : $transfer_secure_id; // $transfer_secure_id ? $transfer_secure_id : random_string('uniqueid');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'externaltranid' => $externaltranid,
			'amount' => -$amount,
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		return $this->callApi(self::API_withdrawFromGame, array("playerName" => $playerName,
            "amount" => $this->dBtoGameAmount($amount), "adminName" => $this->getApiAdminName(),
			"externaltranid" => $externaltranid),
			$context);
	}
	function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array('response_result_id' => $responseResultId);

        $result['status'] = $success ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_PROCESSING;

		if ($success) {
			$ptrlt = $resultJson['result'];
            $afterBalance = $this->gameAmountToDB(floatval($ptrlt['currentplayerbalance']));
			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdrawal
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeSubWalletToMainWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
			// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);
		}

		return array($success, $result);
	}
	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			// 'externaltranid' => $externaltranid,
		);

		return $this->callApi(self::API_logout, array("playerName" => $playerName), $context);
	}
	function processResultForLogout($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultJson);

		return array($success, null);
	}
	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	function updatePlayerInfo($playerName, $infos) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->callApi(self::API_updatePlayerInfo, $infos);
	}
	function processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson) {
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		// var_dump($resultJson['result']);
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

		return $this->callApi(self::API_queryPlayerBalance, array("playerName" => $playerName),
			$context);
	}
	function processResultForQueryPlayerBalance($params) {
		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success && isset($resultJson['result']['BALANCE']) &&
			@$resultJson['result']['BALANCE'] !== null) {

            $result["balance"] = $this->gameAmountToDB(floatval($resultJson['result']['BALANCE']));
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$resultJson['result']['BALANCE']);
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
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->callApi(self::API_checkLoginStatus, array("playerName" => $playerName));
	}

	function processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson) {
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$result["loginStatus"] = $resultJson['result'];
		}

		return array($success, $result);
	}
	//===end checkLoginStatus=====================================================================================

	//===start checkLoginToken=====================================================================================
	public function checkLoginToken($playerName, $token) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckLoginToken',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_checkLoginToken, array("playerName" => $playerName, 'token' => $token),
			$context);

	}
	public function processResultForCheckLoginToken($params) {
		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$success = $resultJson['result']['result'] == 1;
		}

		return array($success, $result);

	}
	//===end checkLoginToken=====================================================================================

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
	//===start queryTransaction=====================================================================================
	function queryTransaction($transactionId, $extra) {
		return $this->callApi(self::API_queryTransaction, array("externaltranid" => $transactionId));
	}
	function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultJson) {
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$result["transactionInfo"] = $resultJson['result'];
			$result["order_message"] ="Transaction Success";
        	$result["order_status"] = true;
		}else{
			$result["order_message"] ="Transaction Failed";
        	$result["order_status"] = false;
		}

		return array($success, $result);
	}
	//===end queryTransaction=====================================================================================

	public function generateGotoUri($playerName, $extra){
		return '/iframe_module/goto_ptgame/default/'.$extra['game_code'].'/'.$extra['game_mode'].'/'.$extra['is_mobile'];
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 'en-us':
                $lang = 'en'; // english
                break;
            case 'zh-cn':
                $lang = 'zh-cn'; // chinese
                break;
            case 'id-id':
                $lang = 'en'; // indonesia
                break;
            case 'vi-vn':
                $lang = 'en'; // vietnamese
                break;
            case 'ko-kr':
                $lang = 'en'; // korean
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	//===start queryForwardGame=====================================================================================
	function queryForwardGame($playerName, $extra) {
		$nextUrl=$this->generateGotoUri($playerName, $extra);
		$result=$this->forwardToWhiteDomain($playerName, $nextUrl);
		if($result['success']){
			return $result;
		}

		//load password
		// $password = $this->getPasswordString($playerName);
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		// $resultJson['language'] = $extra['language'];
		// $resultJson['host'] = $this->game_url;
		// $resultJson['url'] = $this->game_url . '/casinoclient.html?' . http_build_query(array(
		// 	'game' => $params['params']['gamecode'],
		// 	'language' => $params['params']['language'],
		// 	'nolobby' => 1,
		// ));

        $this->CI->load->model(['external_system', 'game_provider_auth', 'player_model']);
		$platformName = $this->CI->external_system->getNameById($this->getPlatformCode());
		$gameCode=$extra['game_code'];
		$success=true;
		$ptLang=$this->getLauncherLanguage($extra['language']);
		$api_play_pt = $this->getSystemInfo('API_PLAY_PT');
		$api_play_pt_js = $this->getSystemInfo('API_PLAY_PT_JS','https://login.winforfun88.com/jswrapper/integration.js.php?casino=winforfun88'); # for flash games
		$api_play_pt_js_h5 = $this->getSystemInfo('API_PLAY_PT_JS_H5','https://login.ld176988.com/jswrapper/integration.js.php?casino=winforfun88'); # for HTML5 games
		$player_url = $this->CI->utils->getSystemUrl('player');
		
        $playerId=$this->CI->player_model->getPlayerIdByUsername($playerName);

        $loginInfo = $this->CI->game_provider_auth->getLoginInfoByPlayerId($playerId, $this->getPlatformCode());

		$result = [
			'success'=>$success,
			'launch_game_on_player'=>$this->launch_game_on_player,
			'platformName' => $platformName,
			'game_code' => $gameCode,
			'lang' => $ptLang,
			'api_play_pt' => $api_play_pt,
			'api_play_pt_js' => (isset($extra['is_mobile']) && $extra['is_mobile']) ? $api_play_pt_js_h5 : $api_play_pt_js,	# we will check here if game is HTML5(mobile) or Flash(WEB) by is_mobile
			'player_url' => $player_url,
			'mobile_js_url' => $this->getSystemInfo('mobile_js_url'),
			'mobile'=>$extra['is_mobile']?"mobile":"",
			'mobile_systemId'=>$this->getSystemInfo('mobile_systemId'),
			'mobile_launcher'=>rtrim($this->getSystemInfo('mobile_launcher'), '/').'/',
			'load_pt_js_from_our_server'=>$this->load_pt_js_from_our_server,
			'game_username'=>$loginInfo->login_name,
			'game_secret'=>base64_encode($loginInfo->password),
			'v'=>PRODUCTION_VERSION,
		];
		$this->CI->utils->debug_log('PT params : ===================================>',$result);
		// return $this->CI->load->view('/player/goto_ptgame', $result,true);

		return $result;
	}
	//===end queryForwardGame=====================================================================================
	//===start syncGameRecords=====================================================================================

	// const DEFAULT_DATETIME_ADJUST = '-5 minutes';
	/**
	 * http://ogdev.ddns.net:8090/display/OG/PT+Rules
	 * http://ogdev.ddns.net:8090/display/OG/pt+game+logs+sample
	 * http://ogdev.ddns.net:8090/display/OG/PT+Game+Log+Map
	 */
	public function syncOriginalGameLogs($token) {
		//call report api
		//set flag to error if format wrong or lost fields

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $startDateTimeRef = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$playerName = parent::getValueFromSyncInfo($token, 'playerName');

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$startDate->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('syncOriginalGameLogs: '.$this->getPlatformCode() , 'from', $startDate, 'to', $endDate, 'sync_id', $syncId, 'playerName', $playerName, 'sync_time_interval' ,$this->sync_time_interval);

		$cnt = 0;
		$real_count = 0;
		$sum = 0;
		// $page = self::START_PAGE;
		// $done = false;
		$success = true;

		$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

		while ($queryDateTimeMax  > $queryDateTimeStart) {

			$done = false;
			$currentPage = self::START_PAGE;

			while (!$done) {

				$dont_save_response_in_api = $this->getConfig('dont_save_response_in_api');

				$rlt = null;

				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					'playerName' => $playerName,
					'syncId' => $syncId,
					// 'dont_save_response_in_api' => $dont_save_response_in_api,
				);

				$startDateParam=new DateTime($queryDateTimeStart);
				if($queryDateTimeEnd>$queryDateTimeMax){
					$endDateParam=new DateTime($queryDateTimeMax);
				}else{
					$endDateParam=new DateTime($queryDateTimeEnd);
				}

				if (!empty($playerName)) {

					//API_syncGameRecordsByPlayer
					$rlt = $this->callApi(self::API_syncGameRecordsByPlayer, array(
						// "startDate" => $startDate->format('Y-m-d H:i:s'),
						// "endDate" => $endDate->format('Y-m-d H:i:s'),
						"startDate" => $startDateParam->format('Y-m-d H:i:s'),
						"endDate" => $endDateParam->format('Y-m-d H:i:s'),
						'currPage' => $currentPage,
						'perPage' => $this->perPageSize,
						'playerName' => $playerName,
						// 'dont_save_response_in_api' => $dont_save_response_in_api,
					), $context);
				} else {
					$rlt = $this->callApi(self::API_syncGameRecords, array(
						// "startDate" => $startDate->format('Y-m-d H:i:s'),
						// "endDate" => $endDate->format('Y-m-d H:i:s'),
						"startDate" => $startDateParam->format('Y-m-d H:i:s'),
						"endDate" => $endDateParam->format('Y-m-d H:i:s'),
						'currPage' => $currentPage,
						'perPage' => $this->perPageSize,
						// 'dont_save_response_in_api' => $dont_save_response_in_api,
					), $context);
				}

				$done = true;
				if ($rlt) {
					$success = $rlt['success'];
				}
				if ($rlt && $rlt['success']) {
					$currentPage = $rlt['currentPage'];
					$total_pages = $rlt['totalPages'];
					//next page
					$currentPage += 1;

					$done = $currentPage > $total_pages;
					$cnt += $rlt['totalCount'];
					$sum += $rlt['sum'];
					$this->CI->utils->debug_log('currentPage', $currentPage, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
				}
			}

			$queryDateTimeStart = $endDateParam->format('Y-m-d H:i:s');
	    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

		}//end while outer
		$this->CI->utils->debug_log('queryDateTimeStart', $queryDateTimeStart, 'queryDateTimeEnd', $queryDateTimeEnd,
			'queryDateTimeMax', $queryDateTimeMax);

		//$this->CI->utils->debug_log('syncOriginalGameLogs monitor', 'count', $cnt, 'sum', $sum);

		return array('success' => $success);

	}

	public function isInvalidRow($row) {
		//remove PROGRESSIVEBET
		return $row['BET'] == '0' && $row['WIN'] == '0' && $row['PROGRESSIVEWIN'] == '0'; //$row['PROGRESSIVEBET'] == '0' &&
	}

	public function syncMergeToGameLogs($token) {
		return $this->returnUnimplemented();
	}

	public function processAfterBalance($afterBalance) {
		//gamecode,'-',balance
		if (!empty($afterBalance)) {
			$arr = explode('-', $afterBalance);
			if (count($arr) >= 2) {
				return floatval($arr[1]);
			}
		}
		return floatval($afterBalance);
	}

	public function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
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
				if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
					return array(null, null);
				}
			}
		}

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$row->gamename, $row->gametype, $externalGameId, $extra,
			$unknownGame);
	}

	// function getGameTypeId($gameType) {
	// 	$this->CI->load->model('game_type');
	// 	return $this->CI->game_type->getGameTypeId($gameType);
	// }

	// function getGameDescriptionId($gameShortCode) {
	// 	$this->CI->load->model('game_description');
	// 	return $this->CI->game_description->getGameDescriptionId($gameShortCode);
	// }

	//===end syncGameRecords=====================================================================================
	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start batchQueryPlayerBalance=====================================================================================
	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		//search all player balance for pt
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$this->CI->load->model(array('game_provider_auth', 'player_model'));

		// $this->CI->utils->debug_log('playerNames', count($playerNames));

		if (empty($playerNames)) {
			// $playerNames = array();
			//load all players
			$playerNames = $this->getAllGameUsernames();
			// $this->CI->utils->debug_log('load all playerNames');
		} else {
			//convert to game username
			foreach ($playerNames as &$username) {
				$username = $this->getGameUsernameByPlayerUsername($username);
			}
			// $this->CI->utils->debug_log('convert playerNames');
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBatchQueryPlayerBalance',
			'playerNames' => $playerNames,
			'dont_save_response_in_api' => $this->getConfig('dont_save_response_in_api'),
			'syncId' => $syncId,
		);

		$page = self::START_PAGE;
		$done = false;
		$success = true;
		$result = array();

		try {
			while (!$done) {

				$rlt = $this->callApi(self::API_batchQueryPlayerBalance, array(
					'currPage' => $page,
					'perPage' => self::ITEM_PER_PAGE,
					"adminName" => $this->getApiAdminName(),
					"kioskName" => $this->getApiKioskName(),
				), $context);

				$done = true;
				// if ($rlt) {
				// $success = $rlt['success'];
				// }

				if ($rlt && $rlt['success']) {
					$page = $rlt['currentPage'];
					$total_pages = $rlt['totalPages'];
					//next page
					$page += 1;

					$done = $page > $total_pages;
					// $this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
					if (empty($result)) {
						$result = $rlt['balances'];
					} else {
						$result = array_merge($rlt['balances'], $result);
					}

				}
			}

		} catch (Exception $e) {
			$this->processError($e);
			$success = false;
		}
		return $this->returnResult($success, "balances", $result);
	}

	function processResultForBatchQueryPlayerBalance($params) {

		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		// $playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		// $result = array();
		// if ($success) {
		// 	$result["balance"] = floatval($resultJson['result']['BALANCE']);
		// 	$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		// 	$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName);
		// 	if ($playerId) {
		// 		//should update database
		// 		$this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
		// 	} else {
		// 		log_message('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
		// 	}
		// }

		$result = array('balances' => null);
		$cnt = 0;
		$result['totalPages'] = 0;
		$result['currentPage'] = 0;
		$result['itemsPerPage'] = 0;
		if ($success) {
			if (isset($resultJson['result']) && !empty($resultJson['result'])) {
				foreach ($resultJson['result'] as $balResult) {
					// $success = $balResult->IsSucceed;
					// if ($success) {
					//search account number
					// if ($balResult->AccountNumber == $accountNumber) {
					$gameUsername = $balResult['PLAYERNAME'];
					$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

					if ($playerId) {
						$bal = floatval($balResult['BALANCE']);

						// $this->CI->utils->debug_log('playerId', $playerId, 'bal', $bal);

						$result["balances"][$playerId] = $bal;

						$this->updatePlayerSubwalletBalance($playerId, $bal);

						$cnt++;
					}
					// }
					//break;
					// }
				}
				$this->CI->utils->debug_log('sync balance', $cnt, 'success', $success);
				// $success = true;

			}
			if (isset($resultJson['pagination'])) {
				$result['totalPages'] = @$resultJson['pagination']['totalPages'];
				$result['currentPage'] = @$resultJson['pagination']['currentPage'];
				$result['itemsPerPage'] = @$resultJson['pagination']['itemsPerPage'];
				// $result['totalCount'] = $resultJson['pagination']['totalCount'];
			}
		}

		return array($success, $result);

	}
	//===end batchQueryPlayerBalance=====================================================================================

	# Returns the details of all/specified players returned by the API.
	public function listPlayers($playerNames) {
		$this->CI->load->model(array('game_provider_auth', 'player_model'));
		if (empty($playerNames)) {
			$playerNames = $this->getAllGameUsernames();
		} else {
			foreach ($playerNames as &$username) {
				$username = $this->getGameUsernameByPlayerUsername($username);
			}
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForListPlayers',
			'playerNames' => $playerNames,
		);

		$currentPage = self::START_PAGE;
		$done = false;
		$success = true;
		$result = array();

		try {
			do {
				$rlt = $this->callApi('listPlayers', array(
					'currPage' => $currentPage,
					'perPage' => self::ITEM_PER_PAGE,
					"adminName" => $this->getApiAdminName(),
					"kioskName" => $this->getApiKioskName(),
				), $context);

				if ($rlt && $rlt['success']) {
					$currentPage = $rlt['currentPage'];
					$done = $currentPage >= $rlt['totalPages'];
					$currentPage++;

					$result = array_merge($rlt['players'], $result);
				} else {
					break;
				}
			} while (!$done);
		} catch (Exception $e) {
			$this->processError($e);
			$success = false;
		}

		return $this->returnResult($success, "players", $result);
	}

	function processResultForListPlayers($params) {
		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		$result['players'] = $resultJson['result'];
		$result['totalPages'] = $resultJson['pagination']['totalPages'];
		$result['currentPage'] = $resultJson['pagination']['currentPage'];
		$result['itemsPerPage'] = $resultJson['pagination']['itemsPerPage'];
		return array(true, $result);
	}

	public function resetPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForResetPlayer',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_resetPlayer, array("playerName" => $playerName),
			$context);

	}

	public function processResultForResetPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		// if ($success) {
		// 	$this->blockUsernameInDB($playerName);
		// }
		// if ($success) {
		// 	$playerInfo = array('playerName' => $resultJson['result']['PLAYERNAME'],
		// 		'password' => $resultJson['result']['PASSWORD'],
		// 		'email' => $resultJson['result']['EMAIL'],
		// 		'languageCode' => $resultJson['result']['LANGUAGECODE']);
		// 	$result["playerInfo"] = $playerInfo;
		// }

		return array($success, $result);
	}

	public function revertBrokenGame($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForRevertBrokenGame',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_revertBrokenGame, array("playerName" => $playerName),
			$context);

	}

	public function processResultForRevertBrokenGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();

		return array($success, $result);
	}

	public function processGameList($game) {

		$attributes = json_decode($game['attributes'], true);
		$game_image = $game['game_code'] . '.' . $this->getGameImageExtension();
		$game_image = $this->checkIfGameImageExist($game_image) ? $game_image : $this->getDefaultGameImage();

		return array(
			'c' => $game['game_code'], # C - GAME CODE
			'n' => lang($game['game_name']), # N - GAME NAME
			'i' => $game_image, # I - GAME IMAGE
			'g' => $game['game_type_id'], # G - GAME TYPE ID
			'r' => $game['game_type_id'] != 7 || $game['offline_enabled'] == 1, # R - TRIAL
			't' => isset($attributes['t']) ? $attributes['t'] : null, # T - TICKER
			'a' => $attributes,
		);

	}

	/*
	*	Sample Url: https://cashier.luckydragon88.com:443/casino/game_history_details_content.php?casino=winforfun88&username=3TTEST002&expiration=1504691932&permission=gameHistory&token=L2LV_B24h3yQI1PGem6JsVgdN6Eh3Pu51ram4J8rU9w&gamePlayId=291042668649&gameEndDate=2017-08-24+20%3A40%3A50
	*/
	public function queryBetDetailLink($playerUsername, $params = null) {
		$url = $this->getSystemInfo('pt_bet_detail_url');
		$params = $this->getSystemInfo('pt_bet_detail_params');
		$url .= 'username=' . $playerUsername . '&expiration=' . $params['expiration'] . '&permission=' . $params['permission'] . '&token=' . $token . '&gamePlayId=' . $params['gamePlayId'] . '&gameEndDate=' . $params['gameEndDate'];
		return Array('success' => true, 'url' => $url);
	}

}

/*end of file*/
