<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_xyzblue extends Abstract_game_api {

	public function __construct() {
		parent::__construct();
		$this->apiUrl = $this->getSystemInfo('url');
		$this->appId = $this->getSystemInfo('account');
		$this->appSecret = $this->getSystemInfo('key');
		$this->encode = $this->getSystemInfo('encode');
		$this->isTester = $this->getSystemInfo('isTester');
		$this->language = $this->getSystemInfo('language');
		$this->currency = $this->getSystemInfo('currency');
		$this->demo_suffix = $this->getSystemInfo('demo_username_suffix');
		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');
	}

	const ENCODE = 3;
	const DEPOSIT = 'deposite';
	const WITHDRAW = 'withdraw';
	const TYPE = 2;
	const GAMECODE = 0;
	const TESTACCOUNT = 'true';

	const URI_MAP = array(
		self::API_createPlayer		 => '/api/user/createuser',
		self::API_changePassword 	 => '/api/user/updateuser',
		self::API_isPlayerExist		 => '/api/user/getuserinfo',
		self::API_queryPlayerInfo	 => '/api/user/getuserinfo',
		self::API_depositToGame 	 => '/api/user/updatecredit',
		self::API_withdrawFromGame 	 => '/api/user/updatecredit',
		self::API_checkLoginToken 	 => '/api/user/getlogintoken',
		self::API_syncGameRecords	 => '/api/game/getbethistory',
	);

	public function getPlatformCode() { 
		return XYZBLUE_API;
	}

	public function generateUrl($apiName, $params) {
		$url = $this->apiUrl.$params['method'];
		return $url;
	}

	public function getHttpHeaders($params){
		return array(
			'APPID' => $this->appId,
			'encode' => self::ENCODE,
			'authentication' => md5($this->appSecret),
			'Content-Type' => 'application/json'
		);
	}

	protected function customHttpCall($ch, $params) {
		unset($params["method"]); //unset action not need on params
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true ); 
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 ); 
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = NULL, $extra = NULL, $resultObj = NULL) {
		return array(FALSE, NULL);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
        $success = ($resultArr['code'] == 0) ? true : false;
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('XYZBLUE_API error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
            $success = false;
        }
        //echo $success ? 'true' : 'false';exit();
        return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );
        $extra['mode'] = isset($extra['mode']) ? $extra['mode'] : null;
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_createPlayer],
            "userid" 	=> ($extra['mode'] == 'demo') ? $gameUsername.'_'.$this->demo_suffix : $gameUsername,
			"userinfo" 	=> array(
								"nickname" 	=> ($extra['mode'] == 'demo') ? $gameUsername.'_'.$this->demo_suffix : $gameUsername,
								"pwd" 		=> $password,
								"currency" 	=> $this->currency,
								"istester" 	=> ($extra['mode'] == 'demo') ? self::TESTACCOUNT : $this->isTester
							)
        );
        $this->utils->debug_log("CreatePlayer params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_createPlayer], $params, $context);     
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, $resultJsonArr);	
	}

	public function changePassword($playerName, $oldPassword = NULL, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );
        
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_changePassword],
            "userid" 	=> $gameUsername,
			"userinfo" 	=> array(
								"nickname" 	=> $gameUsername,
								"pwd" 		=> $newPassword,
								"currency" 	=> $this->currency,
								"istester" 	=> $this->isTester
							)
        );
        $this->utils->debug_log("Change Password params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_changePassword], $params, $context);
	}

	public function processResultForChangePassword() {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForChangePassword ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result = ['response_result_id'=>$responseResultId];
		return array($success, $result);	
	}


	public function queryPlayerBalance($playerName) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId
        );
        
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_queryPlayerInfo],
            "userid" 	=> $gameUsername
        );

        $this->utils->debug_log("queryPlayerBalance params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_queryPlayerInfo], $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForQueryPlayerBalance ==========================>', $resultJsonArr);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$search_array = $resultJsonArr;
		$result['balance'] = @floatval(0);
		if($success) {
			if (array_key_exists('data', $search_array)) {
        		if ($playerId = $this->getPlayerIdInGameProviderAuth($gameUsername)) {
					$this->CI->utils->debug_log('XYZBLUE_API GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $resultJsonArr['data']['credit']);
				} else {
					$this->CI->utils->debug_log('XYZBLUE_API GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				}
				$result['balance'] = @floatval($resultJsonArr['data']['credit']);
				$result['exists'] = true;
			} else {
				$result['exists'] = false;
			}
		} else {
			$result['exists'] = null;
		}
		return array($success, $result);
	}

	public function isPlayerExist($playerName,$extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExists',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId
        );
        
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_isPlayerExist],
            "userid" 	=> ($extra['mode'] == 'demo') ? $gameUsername.'_'.$this->demo_suffix : $gameUsername,
        );

        $this->utils->debug_log("isPlayerExist params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_isPlayerExist], $params, $context);
	}

	public function processResultForIsPlayerExists($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForIsPlayerExists ==========================>', $resultJsonArr);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$search_array = $resultJsonArr;
		if($success) {
			if (array_key_exists('data', $search_array)) {
				$result['exists'] = true;
	        	$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			} else {
				$result['exists'] = false;
			}
		} else {
			$result['exists'] = null;
		}
		return array($success, $result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
            'amount' => $amount
        );
        
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_depositToGame],
            "userid" 	=> $gameUsername,
            "type"		=> self::DEPOSIT,
            "amount"	=> $amount,
            "etc"		=> "user deposit"
        );

        $this->utils->debug_log("depositToGame params ============================>", $params);
        return $this->callApi(self::API_depositToGame, $params, $context);

	}

	public function processResultForDepositToGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForDepositToGame ==========================>', $resultJsonArr);
		$amount = $this->getVariableFromContext($params, 'amount');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result["transfer_status"] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
		if($success){
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);
			//for sub wallet
			// $afterBalance = @$playerBalance['balance'];
			$result["external_transaction_id"] = $resultJsonArr['data']['transactionid'];
			//print_r($afterBalance);exit();
			// if(!empty($afterBalance)){
			// 	$result["currentplayerbalance"] = $afterBalance;
			// }
			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["reason_id"] = self::REASON_UNKNOWN;
		}
		return array($success, $result);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
            'amount' => $amount
        );
        
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_withdrawFromGame],
            "userid" 	=> $gameUsername,
            "type"		=> self::WITHDRAW,
            "amount"	=> $amount,
            "etc"		=> "user withdraw"
        );

        $this->utils->debug_log("withdrawFromGame params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_withdrawFromGame], $params, $context);
	}

	public function processResultForWithdrawFromGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForWithdrawFromGame ==========================>', $resultJsonArr);
		$amount = $this->getVariableFromContext($params, 'amount');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

		$result["transfer_status"] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
		if($success){
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);
			//for sub wallet
			// $afterBalance = @$playerBalance['balance'];
			$result["external_transaction_id"] = $resultJsonArr['data']['transactionid'];
			//print_r($afterBalance);exit();
			// if(!empty($afterBalance)){
			// 	$result["currentplayerbalance"] = $afterBalance;
			// }
			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdraw
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["reason_id"] = self::REASON_UNKNOWN;
		}
		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra = null) {
		$this->CI->load->model('xyzblue_game_logs');
		$gameDes = $this->CI->xyzblue_game_logs->getGameCode($extra['game_name']);
		$token = $this->login($playerName,$gameDes->game_code,$extra);

		$userLoginToken =  $token['token'];
		if(!empty($userLoginToken)){
			$type = $extra['is_mobile'] ? 'contents/mobile' : 'contents';
			$params = array(
				"logintoken" 	=> $userLoginToken,
				"lang" 			=> $this->language,
				"soundinfo"		=> $extra['sound_info']
			);
			$url_params = http_build_query($params);
			$url = $this->apiUrl.'/'.$type.'/'.$extra['game_name'].'.aspx?'.$url_params;
			$data = [
	            'url' => $url,
	            'success' => true
	        ];
	        $this->utils->debug_log('XYZBLUE_API generateUrl - =================================================> ' . $url);
	        return $data;
		}
	}

	public function syncOriginalGameLogs($token = FALSE) {

		

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate,
		);

		$params = array(
			'gamecode' 		=> self::GAMECODE,
			'type' 			=> self::TYPE,
			'startdate' 	=> $startDate,
			'enddate'		=> $endDate,
			'method'		=> self::URI_MAP[self::API_syncGameRecords]
		);
		// echo"<pre>";print_r($params);exit();	
		return $this->callApi(self::URI_MAP[self::API_syncGameRecords], $params, $context);
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('xyzblue_game_logs', 'player_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr);
		$count = 0;
		if($success){
			$betResults = $resultJsonArr['data'];
			if($betResults['totalcount'] > 0){
				$list = $betResults['list'];
				foreach ($list as $data) {
					$gameinfo = array_column($data, 'gameinfo');
					$betinfo = array_column($data, 'betinfo');
					$record = array_merge($gameinfo[0],$betinfo[0]);
					$checkIfExist = $this->CI->xyzblue_game_logs->isRowIdAlreadyExists($record['roundid']);
					if(!$checkIfExist){
						$insertRecord = array();
						$playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['userid']));
						$playerUsername = $this->getGameUsernameByPlayerId($playerID);
						//Data from xyz blue minigame API
						$insertRecord['roundid'] 		= isset($record['roundid']) ? $record['roundid'] : NULL;
						$insertRecord['startdate'] 		= isset($record['startdate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['startdate']))) : NULL;
						$insertRecord['enddate'] 		= isset($record['enddate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['enddate']))) : NULL;
						$insertRecord['result'] 		= isset($record['result']) ? json_encode($record['result']): NULL;
						$insertRecord['userid'] 		= isset($record['userid']) ? $record['userid'] : NULL;
						$insertRecord['currency'] 		= isset($record['currency']) ? $record['currency'] : NULL;
						$insertRecord['gamecode'] 		= isset($record['gamecode']) ? $record['gamecode'] : NULL;
						$insertRecord['betid'] 			= isset($record['betid']) ? $record['betid'] : NULL;
						$insertRecord['idx'] 			= isset($record['idx']) ? $record['idx'] : NULL;
						$insertRecord['val'] 			= isset($record['val']) ? $record['val'] : NULL;
						$insertRecord['amount'] 		= isset($record['amount']) ? $record['amount'] : NULL;
						$insertRecord['winrate'] 		= isset($record['winrate']) ? $record['winrate'] : NULL;
						$insertRecord['winamount'] 		= isset($record['winamount']) ? $record['winamount'] : NULL;
						$insertRecord['registerdate'] 	= isset($record['registerdate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['registerdate']))) : NULL;
						//extra info from SBE
						$insertRecord['Username'] = $playerUsername;
						$insertRecord['PlayerId'] = $playerID;
						$insertRecord['external_uniqueid'] = $insertRecord['roundid']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;
						//insert data to xyz blue minigame gamelogs table database
						$this->CI->xyzblue_game_logs->insertGameLogs($insertRecord);
						$count++;
					}
				}
			}
		}
		$result['data_count'] = $count;	
		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'xyzblue_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->xyzblue_game_logs->getGameLogStatistics($startDate, $endDate);
		// echo"<pre>";print_r($result);exit();
		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $xyzBlue_data) {

				if (!$xyzBlue_data['PlayerId']) {
					continue;
				}

				$cnt++;

				$game_description_id = $xyzBlue_data['game_description_id'];
				$game_type_id = $xyzBlue_data['game_type_id'];

				//for real bet
				$extra = array('trans_amount'=> $xyzBlue_data['BetAmount'] );
				//end
				$result_amount = $xyzBlue_data['result_amount'] - $xyzBlue_data['BetAmount'];
				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$xyzBlue_data['game_code'],
					$xyzBlue_data['game_type'],
					$xyzBlue_data['game'],
					$xyzBlue_data['PlayerId'],
					$xyzBlue_data['UserName'],
					$xyzBlue_data['BetAmount'],
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$xyzBlue_data['external_uniqueid'],
					$xyzBlue_data['startdate'], //start
					$xyzBlue_data['enddate'], //end
					$xyzBlue_data['response_result_id'],
					Game_logs::FLAG_GAME,
					$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => TRUE);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => TRUE);
	}

	function login($playerName, $game = NULL, $extra = NULL) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );
        
        $params = array(
        	"method" 	=> self::URI_MAP[self::API_checkLoginToken],
            "userid" 	=> ($extra['mode'] == 'demo') ? $gameUsername.'_'.$this->demo_suffix : $gameUsername,
            "currency"	=> $this->currency,
            "game"		=> $game
        );
        $this->utils->debug_log("login params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_checkLoginToken], $params, $context);   
	}

	function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result['token'] = null;
		if($success){
			$result['token'] = $resultJsonArr['data']['logintoken'];
		}
		return array($success, $result);
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = NULL, $dateTo = NULL) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = NULL) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	# HELPER ########################################################################################################################################

	# HELPER ########################################################################################################################################

}

/*end of file*/