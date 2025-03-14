<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_pt extends Abstract_game_api {

	const START_PAGE = 1;
	const ITEM_PER_PAGE = 3000;

	protected $transaction_status_declined;
	protected $transaction_status_approved;

	const DEFAULT_TRANSACTION_STATUS_APPROVED='approved';
	const DEFAULT_TRANSACTION_STATUS_DECLINED='declined';
	const LIVE_DEALER_TAG = 'live_dealer';

	protected $launch_game_on_player;

	public function getPlatformCode() {
		return PT_API;
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
        $this->mobile_lobby = $this->getSystemInfo('mobile_lobby', '/ptGame.html');
        $this->mobile_logout_url = $this->getSystemInfo('mobile_logout_url', '');
        $this->support_url = $this->getSystemInfo('support_url');
        $this->client_support_url = $this->getSystemInfo('client_support_url');
        //don't support
        $this->is_enabled_direct_launcher_url=$this->getSystemInfo('is_enabled_direct_launcher_url', false);

		$this->drop_third_decimal_places=$this->getSystemInfo('drop_third_decimal_places', true);
		$this->logout_uri=$this->getSystemInfo('logout_uri', 'player_center/player_center_logout');
		$this->lang=$this->getSystemInfo('lang', '');
		$this->use_new_pt_version = $this->getSystemInfo('use_new_pt_version',false);
		$this->languagecode = $this->getSystemInfo('languagecode','zh-cn');

		$this->country_code = $this->getSystemInfo('country_code',self::DEFAULT_COUNTRYCODE);	
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
		self::API_updatePlayerInfo => 'player/update/playername/[playerName]/countrycode/[countrycode]',
		self::API_queryTransaction => 'player/checktransaction/externaltransactionid/[externaltranid]',
		self::API_syncGameRecords => 'customreport/getdata/reportname/PlayerGames/startdate/[startDate]/enddate/[endDate]/frozen/all/timeperiod/specify/page/[currPage]/perPage/[perPage]',
		self::API_syncGameRecordsByPlayer => 'customreport/getdata/reportname/PlayerGames/startdate/[startDate]/enddate/[endDate]/frozen/all/timeperiod/specify/playername/[playerName]/page/[currPage]/perPage/[perPage]',
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
	// function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
	// 	$result = array();
	// 	// if ($success) {
	// 	//check info
	// 	$resultJson = json_decode($resultText, true);
	// 	// unset($resultText);
	// 	if ($apiName == self::API_queryPlayerBalance) {
	// 		// return $this->processResultForQueryPlayerBalance($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_createPlayer) {
	// 		// 	return $this->processResultForCreatePlayer($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_queryPlayerInfo) {
	// 		// 	return $this->processResultForQueryPlayerInfo($apiName, $params, $responseResultId, $resultJson);
	// 	} else if ($apiName == self::API_checkLoginStatus) {
	// 		// return $this->processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_changePassword) {
	// 		// 	return $this->processResultForChangePassword($apiName, $params, $responseResultId, $resultJson);
	// 	} else if ($apiName == self::API_logout) {
	// 		// return $this->processResultForLogout($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_depositToGame) {
	// 		// 	return $this->processResultForDepositToGame($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_withdrawFromGame) {
	// 		// 	return $this->processResultForWithdrawFromGame($apiName, $params, $responseResultId, $resultJson);
	// 	} else if ($apiName == self::API_updatePlayerInfo) {
	// 		// return $this->processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson);
	// 	} else if ($apiName == self::API_queryTransaction) {
	// 		// return $this->processResultForQueryTransaction($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_syncGameRecords) {
	// 		// 	return $this->processResultForSyncGameRecords($apiName, $params, $responseResultId, $resultJson);
	// 		// } else if ($apiName == self::API_isPlayerExist) {
	// 		// 	return $this->processResultForIsPlayerExist($apiName, $params, $responseResultId, $resultJson);
	// 	}

	// 	return array(false, null);
	// }

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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		// for PT
		$languagecode = $this->languagecode;
		//lang for PT
		// if ($currentLang == '1') {
		// 	$languagecode = 'en';
		// } else {
		// 	$languagecode = 'zh-cn';
		// }

		$params = array(
			"playerName" => $gameUsername,
			"adminName" => $this->getApiAdminName(),
			"kioskName" => $this->getApiKioskName(),
			"password" => $password,
			'languagecode' => $languagecode,
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId'=>$playerId,
		);

		return $callResult = $this->callApi(self::API_isPlayerExist, array("playerName" => $gameUsername),
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerInfo',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerInfo, array("playerName" => $gameUsername),
			$context);
	}
	function processResultForQueryPlayerInfo($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

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
				$this->blockUsernameInDB($gameUsername);
			} else {
				$this->unblockUsernameInDB($gameUsername);
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerUsername,
			'password' => $newPassword,
		);

		return $this->callApi(self::API_changePassword, array("playerName" => $gameUsername, "password" => $newPassword, 'countrycode' => $this->country_code),
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		return $this->callApi(self::API_blockPlayer, array("playerName" => $gameUsername),
			$context);
	}
	function processResultForBlockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$this->blockUsernameInDB($gameUsername);
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		return $this->callApi(self::API_unblockPlayer, array("playerName" => $gameUsername),
			$context);
	}
	function processResultForUnblockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$this->unblockUsernameInDB($gameUsername);
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

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.random_string('md5') : $transfer_secure_id; // $transfer_secure_id ? $transfer_secure_id : random_string('uniqueid');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			// 'externaltranid' => $externaltranid,
			'amount' => $amount,
            'external_transaction_id'=>$external_transaction_id,
            //mock testing
            // 'is_timeout_mock' => $this->getSystemInfo('is_timeout_mock', false),
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		return $this->callApi(self::API_depositToGame, array("playerName" => $gameUsername,
            "amount" => $this->dBtoGameAmount($amount), "adminName" => $this->getApiAdminName(),
			"externaltranid" => $external_transaction_id),
			$context);
	}

	function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array('response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN);

        // $result['status'] = $success ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_PROCESSING;

		if ($success) {
			$ptrlt = $resultJson['result'];
            $afterBalance = $this->gameAmountToDB(floatval($ptrlt['currentplayerbalance']));

			//external_transaction_id means game api system transaction id , not our
			// $result["external_transaction_id"] = $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			// $result["userNotFound"] = false;
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
			// 	// 	$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);

	        $result['didnot_insert_game_logs']=true;

			$apiResult=@$ptrlt['result'];
			if(strpos($apiResult, 'OK')!==false){
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			}
		}else{

			//get error log
			$errorcode=@$resultJson['errorcode'];
			switch ($errorcode) {
				case '98':
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case '41':
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case '1005':
					$result['reason_id']=self::REASON_DISABLED_DEPOSIT_BY_GAME_PROVIDER;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
			}

			# if error 500, treat as success
			if((in_array($errorcode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $success=true;
            }

		}

		return array($success, $result);
	}
	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		// $rlt = $this->logout($playerName);
		// $this->CI->utils->debug_log('logout', $rlt);
		// if ($lookupGameUsername) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// }

        $external_transaction_id = empty($transfer_secure_id) ? 'T'.random_string('md5') : $transfer_secure_id; // $transfer_secure_id ? $transfer_secure_id : random_string('uniqueid');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
            'external_transaction_id'=>$external_transaction_id,
			'amount' => -$amount,
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		return $this->callApi(self::API_withdrawFromGame, array("playerName" => $gameUsername,
            "amount" => $this->dBtoGameAmount($amount), "adminName" => $this->getApiAdminName(),
			"externaltranid" => $external_transaction_id),
			$context);
	}
	function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array('response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN);

        // $result['status'] = $success ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_PROCESSING;

		if ($success) {
			$ptrlt = $resultJson['result'];
            $afterBalance = $this->gameAmountToDB(floatval($ptrlt['currentplayerbalance']));
			//external_transaction_id means game api system transaction id , not our
			// $result["external_transaction_id"] = $ptrlt['ptinternaltransactionid']; // $this->getVariableFromContext($params, 'externaltranid');
			$result["currentplayerbalance"] = $afterBalance;
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
				//withdrawal
				// $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
				// 	$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);

	        $result['didnot_insert_game_logs']=true;
			$apiResult=@$ptrlt['result'];
			if(strpos($apiResult, 'OK')!==false){
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			}

		}else{

			//get error log
			$errorcode=@$resultJson['errorcode'];
			switch ($errorcode) {
				case '98':
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case '41':
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
			}

		}

		return array($success, $result);
	}
	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	function login($playerName, $password = null) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// return array("success" => true);
		return $this->returnUnimplemented();
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			// 'externaltranid' => $externaltranid,
		);

		return $this->callApi(self::API_logout, array("playerName" => $gameUsername), $context);
	}
	function processResultForLogout($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultJson);

		return array($success, null);
	}
	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = [
			"playerName" => $gameUsername,			
			"countrycode" => $this->country_code,
		];

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);
	}

    public function processResultForUpdatePlayerInfo($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = [];
        if ($success){
			$success = true;
		} else {
			$success = false;
		}

		return array($success, $result);
	}


	// function processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson) {
	// 	$success = $this->processResultBoolean($responseResultId, $resultJson);
	// 	// var_dump($resultJson['result']);
	// 	$result = array();

	// 	return array($success, $result);
	// }
	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, array("playerName" => $gameUsername),
			$context);
	}

	private function round_down($number, $precision = 2){
		$fig = (int) str_pad('1', $precision, '0');
		return (floor($number * $fig) / $fig);
	}

	// just get 2 decimal places ( no round up )
	public function gameAmountBalance($amount) {
		$conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
		$value = floatval($amount / $conversion_rate);
		return $this->round_down($value,3);
	}

	function processResultForQueryPlayerBalance($params) {
		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success && isset($resultJson['result']['BALANCE']) &&
			@$resultJson['result']['BALANCE'] !== null) {

			if ($this->drop_third_decimal_places) {
				$result['balance'] = $this->gameAmountBalance(floatval($resultJson['result']['BALANCE']));
			} else {
				$result["balance"] = $this->gameAmountToDB(floatval($resultJson['result']['BALANCE']));
			}

			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// $this->CI->utils->debug_log('query balance playerId', $playerId, 'gameUsername',
			// 	$gameUsername, 'balance', @$resultJson['result']['BALANCE']);
			// if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			// } else {
			// 	log_message('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start queryPlayerDailyBalance=====================================================================================
	// function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
	// 	$daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);

	// 	$result = array();

	// 	if ($daily_balance != null) {
	// 		foreach ($daily_balance as $key => $value) {
	// 			$result[$value['updated_at']] = $value['balance'];
	// 		}
	// 	}

	// 	return array_merge(array('success' => true, "balanceList" => $result));
	// }
	//===end queryPlayerDailyBalance=====================================================================================
	//===start queryGameRecords=====================================================================================
	// function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
	// 	$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
	// 	return array('success' => true, 'gameRecords' => $gameRecords);
	// }
	//===end queryGameRecords=====================================================================================
	//===start checkLoginStatus=====================================================================================
	function checkLoginStatus($playerName) {
		// $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// return $this->callApi(self::API_checkLoginStatus, array("playerName" => $gameUsername));
		return $this->returnUnimplemented();
	}

	// function processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson) {
	// 	$success = $this->processResultBoolean($responseResultId, $resultJson);
	// 	$result = array();
	// 	if ($success) {
	// 		$result["loginStatus"] = $resultJson['result'];
	// 	}

	// 	return array($success, $result);
	// }
	//===end checkLoginStatus=====================================================================================

	//===start checkLoginToken=====================================================================================
	public function checkLoginToken($playerName, $token) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckLoginToken',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_checkLoginToken, array("playerName" => $gameUsername, 'token' => $token),
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
	// function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
	// 	$gameBettingRecord = parent::getGameTotalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo);
	// 	if ($gameBettingRecord != null) {
	// 		if ($gameBettingRecord['bettingAmount']) {
	// 			$result['bettingAmount'] = $gameBettingRecord['bettingAmount'];
	// 		} else {
	// 			$result['bettingAmount'] = 0;
	// 		}
	// 	}
	// 	return array("success" => true, "bettingAmount" => $result['bettingAmount']);
	// }
	//===end totalBettingAmount=====================================================================================
	//===start queryTransaction=====================================================================================
	function queryTransaction($transactionId, $extra) {

		$playerId=$extra['playerId'];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId'=>$playerId,
			// 'playerName' => $playerName,
			// 'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			"externaltranid" => $transactionId,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}
	function processResultForQueryTransaction($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array('response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);

		$success = !empty($resultJson) && !array_key_exists('errorcode', $resultJson) && !array_key_exists('error', $resultJson);

		if ($success) {
			// $result["transactionInfo"] = $resultJson['result'];

			$result['original_status']=@$resultJson['result']['status'];

			if(strtolower($result['original_status'])==$this->transaction_status_approved){
				$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			}elseif(strtolower($result['original_status'])==$this->transaction_status_declined){
				$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
			}

		}else{

			$errorcode=@$resultJson['errorcode'];
			$result['error_code']=$errorcode;

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
        	case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'zh-cn'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'en'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'en'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th'; // thai
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

        $this->CI->load->model(['external_system', 'game_provider_auth', 'player_model','game_description_model']);

		$success=true;
		$platformName = $this->CI->external_system->getNameById($this->getPlatformCode());
		$gameCode=isset($extra['game_code']) ? $extra['game_code'] : null;
		$lang = isset($extra['language']) && !empty($extra['language']) ? $extra['language'] : $this->lang;
		$ptLang=$this->getLauncherLanguage($lang);
		$api_play_pt = $this->getSystemInfo('API_PLAY_PT');
		$api_play_pt_js = $this->getSystemInfo('API_PLAY_PT_JS','https://login.winforfun88.com/jswrapper/integration.js.php?casino=winforfun88'); # for flash games
		$api_play_pt_js_h5 = $this->getSystemInfo('API_PLAY_PT_JS_H5','https://login.ld176988.com/jswrapper/integration.js.php?casino=winforfun88'); # for HTML5 games
		$player_url = $this->CI->utils->getSystemUrl('player');

        $playerId=$this->CI->player_model->getPlayerIdByUsername($playerName);

        $loginInfo = $this->CI->game_provider_auth->getLoginInfoByPlayerId($playerId, $this->getPlatformCode());

        //for pt deposit mini cashier
        $player_token = $this->getPlayerTokenByUsername($playerName);
        //$next = $this->CI->utils->getSystemUrl('player','/iframe_module/iframe_viewMiniCashier/'. $this->getPlatformCode() );
        $deposit_url = $this->CI->utils->getSystemUrl('player','/player_center2/deposit');
		$mobile_lobby = $this->CI->utils->getSystemUrl('m').$this->mobile_lobby;
        $mobile_logout_url = $this->CI->utils->getSystemUrl('m') . (empty($this->mobile_logout_url) ? ("/".$this->logout_uri) : $this->mobile_logout_url);
        $merchant_code = null;
        # for minicashier url for mobile to redirect to client player center not gamegateway player center
        if (isset($extra['extra']['t1_mini_cashier_url']) && !empty($extra['extra']['t1_mini_cashier_url'])) {
            $deposit_url = $extra['extra']['t1_mini_cashier_url'];
        }
        # for gamegateway logout url for mobile to redirect to client player center not gamegateway player center
        if (isset($extra['extra']['t1_mobile_logout_url']) && !empty($extra['extra']['t1_mobile_logout_url'])) {
            $mobile_logout_url = $extra['extra']['t1_mobile_logout_url'];
        }
        # for gamegateway lobby url for mobile to redirect to client player center not gamegateway player center
        if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) {
            $mobile_lobby = $extra['extra']['t1_lobby_url'];
        }
        # get merchant code when using game gateway
        if (isset($extra['extra']['t1_merchant_code']) && !empty($extra['extra']['t1_merchant_code'])) {
            $merchant_code = $extra['extra']['t1_merchant_code'];
            if (!empty($merchant_code)) {
                $this->support_url = isset($this->client_support_url[$merchant_code]['url']) ? $this->client_support_url[$merchant_code]['url'] : null;
            }
		}
		$is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
		$game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
		$client = 'ngm_desktop';
		$gameTag = $this->CI->game_description_model->getGameTagByGameCode($this->getPlatformCode(),$gameCode);

		if(! empty($gameTag)){
			if($is_mobile){
				if($gameTag == self::LIVE_DEALER_TAG){
					$client = 'live_mob';
				}else{
					$client = 'ngm_mobile';
				}
			}else{
				if($gameTag==self::LIVE_DEALER_TAG){
					$client = 'live_desk';
				}
			}
		}

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
			'mobile_lobby'=> $mobile_lobby,
			'mobile_logout_url'=> $mobile_logout_url,
			'deposit_url'=> $deposit_url,
			'support_url'=> $this->support_url,
			'support_button_link' => !empty($this->support_url) && isset($this->support_url) ? $this->support_url : $this->CI->utils->getSystemUrl('player'). '/iframe_module/goto_ptgame/default/default/real/mobile/1/'.$merchant_code,
			'v'=>PRODUCTION_VERSION,
			'home_link' =>  $is_mobile ? $this->utils->getSystemUrl('m') : $this->utils->getSystemUrl('player'),
			'logout_uri' => ltrim($this->logout_uri,'/'),
			'is_mobile' => $is_mobile ? true : false,
			'game_mode' => is_null($game_mode) ? null : $game_mode,
			'use_new_pt_version' => false
		];

		if($this->use_new_pt_version){
			$result['client'] = $client;
			$result['api_play_pt_js'] = $api_play_pt_js; # we have checking in goto_ptgame.php if mobile or desktop
			$result['use_new_pt_version'] = true;
		}

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
	function syncOriginalGameLogs($token) {
		//call report api
		//set flag to error if format wrong or lost fields

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $startDateTimeRef = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$playerName = parent::getValueFromSyncInfo($token, 'playerName');
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

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

				if (!empty($gameUsername)) {

					//API_syncGameRecordsByPlayer
					$rlt = $this->callApi(self::API_syncGameRecordsByPlayer, array(
						// "startDate" => $startDate->format('Y-m-d H:i:s'),
						// "endDate" => $endDate->format('Y-m-d H:i:s'),
						"startDate" => $startDateParam->format('Y-m-d H:i:s'),
						"endDate" => $endDateParam->format('Y-m-d H:i:s'),
						'currPage' => $currentPage,
						'perPage' => $this->perPageSize,
						'playerName' => $gameUsername,
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

	private function isUniqueIdAlreadyExists($uniqueId) {
		$this->CI->load->model('pt_game_logs');
		return $this->CI->pt_game_logs->isUniqueIdAlreadyExists($uniqueId);
	}

	private function isInvalidRow($row) {
		//remove PROGRESSIVEBET
		return $row['BET'] == '0' && $row['WIN'] == '0' && $row['PROGRESSIVEWIN'] == '0'; //$row['PROGRESSIVEBET'] == '0' &&
	}

	function processResultForSyncGameRecords($params) {
		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		// $playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$this->CI->load->model('pt_game_logs');
			// $ptGameRecords = array();
			$gameRecords = $resultJson['result'];
			$sum = 0;
			if ($gameRecords) {
				$availableRows = $this->CI->pt_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

				foreach ($availableRows as $key) {
					$gameName = isset($key['GAMENAME']) ? $key['GAMENAME'] : null;
					preg_match_all("/\(([^()]+)\)/", $gameName, $matches);
					$gameshortcode = $matches[1];
					$gameCode =  isset($key['GAMECODE']) ? $key['GAMECODE'] : null;
					$external_uniqueid = $uniqueId = $gameCode;
					$sum += $key['BET'];
					// $external_uniqueid = $gameshortcode[0] . '-' . $key['SESSIONID'] . '-' . $key['GAMEDATE'] . '-' . $key['WINDOWCODE'] . '-' . $key['RNUM'];
					// if (!$this->isUniqueIdAlreadyExists($uniqueId) &&
					if (!$this->isInvalidRow($key)) {
						$gameDate = isset($key['GAMEDATE']) ? $key['GAMEDATE'] : null;
						$row = array('playername' => $key['PLAYERNAME'],
							'gamename' => isset($key['GAMENAME']) ? $key['GAMENAME'] : null,
							'gameshortcode' => isset($gameshortcode[0]) ? $gameshortcode[0] : null,
							'gamecode' => $gameCode,
							'bet' => isset($key['BET']) ? $key['BET'] : null,
							'win' => isset($key['WIN']) ? $key['WIN'] : null,
							'gamedate' => isset($key['GAMEDATE']) ? $this->gameTimeToServerTime($key['GAMEDATE']) : null,
							'sessionid' => isset($key['SESSIONID']) ? $key['SESSIONID'] : null,
							'gametype' => isset($key['GAMETYPE']) ? $key['GAMETYPE'] : null,
							'windowcode' => isset($key['WINDOWCODE']) ? $key['WINDOWCODE'] : null,
							'balance' => isset($key['BALANCE']) ? $key['BALANCE'] : null,
							'progressivebet' => isset($key['PROGRESSIVEBET']) ? $key['PROGRESSIVEBET'] : null,
							'progressivewin' => isset($key['PROGRESSIVEWIN']) ? $key['PROGRESSIVEWIN'] : null,
							'currentbet' => isset($key['CURRENTBET']) ? $key['CURRENTBET'] : null,
							'livenetwork' => isset($key['LIVENETWORK']) ? $key['LIVENETWORK'] : null,
							'gameid' => isset($key['GAMEID']) ? $key['GAMEID'] : null,
							'uniqueid' => $uniqueId,
							'response_result_id' => $responseResultId,
							'external_uniqueid' => $external_uniqueid);

							$this->CI->utils->debug_log(__METHOD__.' row ',$row,'gamedate raw',$gameDate);
						$this->CI->pt_game_logs->insertPTGameLogs($row);
					}
				}
			}

			// foreach ($ptGameRecords as $key) {
			// 	$this->CI->pt_game_logs->syncToPTGameLogs($key);
			// }
			// $ptGameRecords = array();
			//
			$result['totalPages'] = $resultJson['pagination']['totalPages'];
			$result['currentPage'] = $resultJson['pagination']['currentPage'];
			$result['itemsPerPage'] = $resultJson['pagination']['itemsPerPage'];
			$result['totalCount'] = @$resultJson['pagination']['totalCount'];
			$result['sum'] = $sum;

		}

		// unset($resultJson);

		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {
		//merge ag_game_logs to game_logs, map fields
		//check duplicate record

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$playerName = parent::getValueFromSyncInfo($token, 'playerName');

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// if (!$dateTimeTo) {
		// 	$dateTimeTo = new DateTime();
		// }
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$result = $this->getPTGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'), $gameUsername);

		$unknownGame = $this->getUnknownGame();
		$cnt = 0;
		if ($result) {
			//var_dump($result);
			$this->CI->load->model(array('game_description_model', 'game_logs'));
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $key) {
				// $sum += $key->bet_amount;
				$username = strtolower($key->playername);
				$gameDate = new \DateTime($key->gamedate);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

				// $gameLogs['game_platform_id'] = $this->getPlatformCode();

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($key, $unknownGame, $gameDescIdMap);
				// $this->CI->utils->debug_log('game_description_id', $game_description_id, 'game_type_id', $game_type_id);

				if (empty($game_description_id)) {
					$this->CI->utils->debug_log('empty game_description_id , pt_game_logs.id=', $key->id);
					continue;
				}
				$cnt++;
				$bet_amount=$this->gameAmountToDB($key->bet_amount);

				$running_platform=$this->analyzeRunningPlatform($key);

				//gamecode is round number
				$extra_info=['table'=>$key->gamecode, 'trans_amount'=>$bet_amount, 'running_platform'=> $running_platform, 'sync_index' => $key->id];

				if (strpos($key->gamename, 'gpas_') !== false) {
					$key->gameshortcode = substr_replace(strstr($key->gamename, 'gpas_') ,"",-1);
				}
				if (strpos($key->gamename, 'pop_') !== false) {
					$key->gameshortcode = substr_replace(strstr($key->gamename, 'pop_') ,"",-1);
				}

				if (strpos($key->gamename, 'gpas_') !== false || strpos($key->gamename, 'pop_') !== false) {
					$query = 'game_description.game_platform_id = ' . $this->getPlatformCode() . ' AND game_description.id = "' . $game_description_id . '"';
			        $game_description_details = (array) $this->CI->game_description_model->getGame($query)[0];
			        if (!empty($game_description_details)) {
						$key->gamename = $game_description_details['gameName'];
						$key->gametype = $game_description_details['gameType'];
			        }
				}

				$this->syncGameLogs($game_type_id, $game_description_id, $key->gameshortcode,
					$key->gametype, $key->gamename, $key->player_id, $username,
                    $bet_amount, $this->gameAmountToDBGameLogsTruncateNumber($key->result_amount), null, null, $this->gameAmountToDBGameLogsTruncateNumber($key->after_balance), $key->has_both_side,
					$key->external_uniqueid, $gameDateStr, $gameDateStr, $key->response_result_id,
					Game_logs::FLAG_GAME, $extra_info);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return array('success' => true);
	}

	public function analyzeRunningPlatform($row){
		return self::PLATFORM_UNKNOWN;
	}

	private function processAfterBalance($afterBalance) {
		//gamecode,'-',balance
		if (!empty($afterBalance)) {
			$arr = explode('-', $afterBalance);
			if (count($arr) >= 2) {
				return floatval($arr[1]);
			}
		}
		return floatval($afterBalance);
	}

	private function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
		$this->CI->load->model(array('game_description_model'));

		if (strpos($row->gamename, 'gpas_') !== false) {
			$row->gameshortcode = substr_replace(strstr($row->gamename, 'gpas_') ,"",-1);
		}
		if (strpos($row->gamename, 'pop_') !== false) {
			$row->gameshortcode = substr_replace(strstr($row->gamename, 'pop_') ,"",-1);
		}

		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}
		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		if (strpos($row->gamename, 'gpas_') !== false || strpos($row->gamename, 'pop_') !== false) {
			$query = 'game_description.game_platform_id = ' . $this->getPlatformCode() . ' AND game_description.external_game_id = "' . $row->gameshortcode . '"';
	        $game_description_details = (array) $this->CI->game_description_model->getGame($query)[0];
	        if (!empty($game_description_details)) {
				$game_description_id = $game_description_details['gameDescriptionId'];
				$game_type_id = $game_description_details['gameTypeId'];
			}
		}

		$externalGameId = $row->gameshortcode;

		$extra = array('game_code' => $row->gameshortcode);
		if (empty($game_description_id)) {
            $this->CI->load->model('game_type_model');

            $game_type = explode(" ", $row->gametype);
            if($game_type[0] == "Progressive"){
                $game_type = $game_type[1];
            }else{
                $game_type = $game_type[0];
            }

            $game_type = ($game_type=="Sidegames") ? 'mini' : $game_type;

            $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%" .$game_type. "%')";
            $game_type_details = $this->CI->game_type_model->getGameTypeList($query);

            if(!empty($game_type_details[0])){
                $game_type_id = $game_type_details[0]['id'];
                $row->gametype = $game_type_details[0]['game_type'];
            }else{
                $game_type_id = $unknownGame->game_type_id;
                $row->gametype = $unknownGame->game_name;
            }

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

	private function getPTGameLogStatistics($dateTimeFrom, $dateTimeTo, $gameUsername) {
		$this->CI->load->model('pt_game_logs');
		return $this->CI->pt_game_logs->getPTGameLogStatistics($dateTimeFrom, $dateTimeTo, $gameUsername);
	}

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

//						$this->updatePlayerSubwalletBalance($playerId, $bal);

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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForResetPlayer',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_resetPlayer, array("playerName" => $gameUsername),
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForRevertBrokenGame',
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_revertBrokenGame, array("playerName" => $gameUsername),
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

}

/*end of file*/
