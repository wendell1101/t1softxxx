<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/******************************
	{
	    "merchant_id": "NNTI_FHC_YBYL",
	    "api_key": "bvcgweIODciaVxxPJLLoFJ",
	    "adjust_datetime_minutes": 10,
	    "prefix_for_username": ""
	}
*******************************/

class Game_api_dt extends Abstract_game_api {

	private $api_url;
	private $merchant_id;
	private $api_key;

	// const URI_MAP = array(
	// 	self::API_createPlayer => '/agent_api/player/register.php',
	// 	self::API_depositToGame => '/agent_api/cashier/funds_transfer_to_player.php',
	// 	self::API_withdrawFromGame => '/agent_api/cashier/funds_transfer_from_player.php',
	// 	self::API_queryPlayerBalance => '/agent_api/player/balance.php',
	// 	self::API_login => '/agent_api/player/login.php',
	// 	self::API_queryForwardGame => '/agent_api/player/game_token.php',
	// 	self::API_syncGameRecords => '/get/'
	// );

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->merchant_id = $this->getSystemInfo('merchant_id');
		$this->api_key = $this->getSystemInfo('api_key');
		$this->fun_game_url = $this->getSystemInfo('fun_game_url', 'http://play.dreamtech8.com/playSlot.aspx');
		$this->bet_link_url = $this->getSystemInfo('bet_link_url', 'https://dt888.dtgame-dtweb.com/betRecordById.aspx?');
		$this->switch_off_feature_button_slots = $this->getSystemInfo('switch_off_feature_button_slots', false);
		$this->currency = $this->getSystemInfo('currency', 'CNY');
		$this->add_currency_in_sign = $this->getSystemInfo('add_currency_in_sign', false);

		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
	}

	public function getPlatformCode() {
		return DT_API;
	}

	public function generateUrl($apiName, $params) {

		$params_string = http_build_query($params);
		$url = $this->api_url.'?'.$params_string;

		return $url;

	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if($resultArr['RESPONSECODE']=='00000'){
			$success = true;
		}
		if($resultArr['RESPONSECODE']=='000011'){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('DT API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

    function isPlayerExist($userName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($userName);
		$gameUsername = !empty($gameUsername)?$gameUsername:$userName;
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $gameUsername,
			'playerId' => $playerId
		);

		$api_method = 'GETAMOUNT';
		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($gameUsername),
			'SIGNATURE' => MD5($this->merchant_id.$api_method.strtoupper($gameUsername).$this->api_key)
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	function processResultForIsPlayerExist($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArr = json_decode($resultText,TRUE);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		if($success) {
			$result['exists'] = true;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		else {
			if($resultArr['RESPONSECODE']=="000012"){
				$success = true;
				$result['exists'] = false;
			}else{
				$success = false;
				$result['exists'] = null;
			}
		}

		return array($success, $result);
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $gameUsername
		);

		$api_method = 'CREATE';

		$signature = $this->merchant_id.$api_method.strtoupper($gameUsername).$password.$this->api_key;

		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($gameUsername),
			'PLAYERPASSWORD' => $password,
			'SIGNATURE' => $this->add_currency_in_sign ? MD5($signature.$this->currency) : MD5($signature)
		);

		if ($this->add_currency_in_sign) {
			$params['CURRENCY'] = $this->currency;
		}

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultJsonArr = json_decode($resultText,TRUE);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

		return array($success, $resultJsonArr);

	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'newPassword' => $newPassword,
		);

		$api_method = 'UPDATE';
		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($gameUsername),
			'PLAYERPASSWORD' => $newPassword,
			'SIGNATURE' => MD5($this->merchant_id.$api_method.strtoupper($gameUsername).$newPassword.$this->api_key)
		);

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params,'playerName');
		$newPassword = $this->getVariableFromContext($params,'newPassword');
		$resultText = $this->getResultTextFromParams($params);
		$resultJsonArr = json_decode($resultText,TRUE);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		return array($success, $resultJsonArr);
	}

	function queryPlayerBalance($userName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($userName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $gameUsername
		);

		$api_method = 'GETAMOUNT';
		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($gameUsername),
			'SIGNATURE' => MD5($this->merchant_id.$api_method.strtoupper($gameUsername).$this->api_key)
		);

		if(!empty($gameUsername)){
			return $this->callApi(self::API_queryPlayerBalance, $params, $context);
		}else{
			return array("success"=>false, "exists"=>false, "message" => "player doesn't exist!");
		}

	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArr = json_decode($resultText,TRUE);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success) {
			if($resultArr['RESPONSECODE']=="000012"){
				$result['exists'] = false;
			}else{
				$result['balance'] =  $this->gameAmountToDB($resultArr['AMOUNT']);
				$this->CI->utils->debug_log('DT GAME API balance' .$result['balance']);
				if($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
					$this->CI->utils->debug_log('DT GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
				}else{
					$this->CI->utils->debug_log('DT GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				}
				$result['exists'] = true;
			}
		}

		return array($success, $result);

	}

	function depositToGame($userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

		$amount = $this->dBtoGameAmount($amount);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $userName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $external_transaction_id,
			'amount' => $amount
		);

		$api_method = 'DEPOSIT';
		$signature = $this->merchant_id.$api_method.strtoupper($gameUsername).number_format($amount,2,'.','').$this->api_key;

		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($gameUsername),
			'PRICE' => number_format($amount,2,'.',''),
			'TRANSFER_ID' => $external_transaction_id,
			'SIGNATURE' => $this->add_currency_in_sign ? MD5($signature.$this->currency) : MD5($signature)
		);

		if ($this->add_currency_in_sign) {
			$params['CURRENCY'] = $this->currency;
		}

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	function processResultForDepositToGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArr = json_decode($resultText,TRUE);
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		if ($success) {
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);
            //$result['external_transaction_id'] = @$resultArr['DATA']['TRANSFER_ID'];

			//for sub wallet
			// $afterBalance = $playerBalance['balance'];
			// $result["currentplayerbalance"] = $afterBalance;

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', $resultArr['details']['title']);
			// }
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
		} else {
			//$result["userNotFound"] = true;
            $error_code = @$resultArr['RESPONSECODE'];

			if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($error_code, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['reason_id'] = $this->getReason($error_code);
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
		}

		return array($success, $result);
	}

	private function getReason($error_code){
		switch($error_code) {
            case '00001' :
            case '00002' :
                return self::REASON_AGENT_NOT_EXISTED;
                break;
            case '00003' :
            case '00004' :
                return self::REASON_INVALID_KEY;
                break;
            case '00009' :
            case '000012' :
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case '000021' :
                return self::REASON_INVALID_KEY;
                break;
            case '000013' :
            case '000023' :
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case '000028' :
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

		$amount = $this->dBtoGameAmount($amount);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'gameUsername' => $gameUsername,
			'playerName' => $userName,
            'external_transaction_id' => $external_transaction_id,
			'amount' => number_format($amount,2,'.','')
		);

		$api_method = 'WITHDRAW';
		$signature = $this->merchant_id.$api_method.strtoupper($gameUsername).number_format($amount,2,'.','').$this->api_key;

		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($gameUsername),
			'PRICE' => number_format($amount,2,'.',''),
			'TRANSFER_ID' => $external_transaction_id,
			'SIGNATURE' => $this->add_currency_in_sign ? MD5($signature.$this->currency) : MD5($signature)
		);

		if ($this->add_currency_in_sign) {
			$params['CURRENCY'] = $this->currency;
		}

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArr = json_decode($resultText,TRUE);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		if($success){
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			// $afterBalance = $playerBalance['balance'];
			// $result["currentplayerbalance"] = $afterBalance;
			// //$result["userNotFound"] = false;

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdraw
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());

			// } else {
			// 	$this->CI->utils->debug_log('error', $resultArr['details']['title']);
			// }
			//$result['external_transaction_id'] = @$resultArr['DATA']['TRANSFER_ID'];

            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
		} else {
            $error_code = @$resultArr['RESPONSECODE'];
            $result['reason_id'] = $this->getReason($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en_US'; // english
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'zh_CN'; // chinese
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th_TH';
                break;
            default:
                $lang = 'en_US'; // default as english
                break;
        }
        return $lang;
    }

	function queryForwardGame($playerName,$extra=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($playerName);

        if ($extra['game_mode'] == "fun" ||$extra['game_mode'] == "demo" || $extra['game_mode'] == "trial") {
			$fun_game_url = $this->fun_game_url;
			$url = $fun_game_url . "?gameCode=" . $extra['game_code'] . "&isfun=1&type=dt";
			$this->CI->utils->debug_log('DT queryForwardGame URL', $url);
			return array('success'=> true, 'url' => $url);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'game_code' => $extra['game_code'],
			'mode' => $extra['game_mode']=='real'?0:1,
			'language' => $this->getLauncherLanguage($extra['language']),
			'playerName' => $playerName
		);

		$api_method = 'LOGIN';
		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'PLAYERNAME' => strtoupper($playerName),
			'PLAYERPASSWORD' => $password,
			'SIGNATURE' => MD5($this->merchant_id.$api_method.strtoupper($playerName).$password.$this->api_key)
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	function processResultForQueryForwardGame($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameCode = $this->getVariableFromContext($params, 'game_code');
		$language = $this->getVariableFromContext($params, 'language');
		$mode = $this->getVariableFromContext($params, 'game_mode');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArr = json_decode($resultText,TRUE);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(); 

		if($success){
			$url =  $resultArr['gameurl'].'?slotKey='.$resultArr['slotKey'].'&gameCode='.$gameCode.'&isfun='.$mode.'&language='.$language;
			if ($this->switch_off_feature_button_slots) {
				$url .= '&psd=0';
			}

			// get home link dynamically
			$home_link = $this->getHomeLink();
			$home_uri_path = $this->getSystemInfo('home_uri_path', null);
			$url .= '&clientType=1&closeUrl=' . $home_link . $home_uri_path;	
			

			$result = array('url' => $url);
		}
		
		return array($success, $result);

	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$queryDateTimeStart = $startDate->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
		$queryDateTimeMax = $endDate->format("Y-m-d H:i:s");

		if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDate->format("Y-m-d H:i:s");
    	}
		
		//observer the date format
		//$startDate = $startDate->format('Y-m-d H:i:s');
		//$endDate = $endDate->format('Y-m-d H:i:s');

		while ($queryDateTimeEnd  > $queryDateTimeStart) {

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
			);

			$this->CI->utils->debug_log('DT (syncOriginalGameLogs) '. $queryDateTimeStart .' to '. $queryDateTimeEnd);		

			$api_method = 'GETBETDETAIL';
			$params = array(
				'METHOD' => $api_method,
				'BUSINESS' => $this->merchant_id,
				'START_TIME' => $queryDateTimeStart,
				'END_TIME' => $queryDateTimeEnd,
				'PAGENUMBER' => 1,
				'PAGESIZE' => '5000',
				'SIGNATURE' => MD5($this->merchant_id.$api_method.$queryDateTimeStart.$queryDateTimeEnd.$this->api_key)
			);

			$result[] = $cur_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			sleep($this->sync_sleep_time);
			$queryDateTimeStart = $queryDateTimeEnd;
			$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
			# Query Exact end
			if($queryDateTimeEnd > $queryDateTimeMax){
				$queryDateTimeEnd = $endDate->format("Y-m-d H:i:s");
			}
		}

		return array("success" => true, "results"=>$result);
	}

	function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('dt_game_logs', 'player_model'));
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array();
		if ($success) {

			if ((int)$resultArr['PAGESIZE']>0) {
				$gameRecords = $resultArr['BETSDETAILS'];
				$availableRows = $this->CI->dt_game_logs->getAvailableRows($gameRecords);

				$dataCount = 0;
				if (!empty($availableRows)) {
					foreach ($availableRows as $record) {
						$insertRecord = array();

						$playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['playerName']));
						$playerUsername = $this->getGameUsernameByPlayerId($playerID);
						if (empty($playerID)) continue;

						//parent id
						if(is_array($record['partentId'])){
							$parentId =  isset($record['partentId']['respin']) ? $record['partentId']['respin']: NULL;
						}else{
							$parentId =  isset($record['partentId']) ? $record['partentId'] : NULL;
						}
						//Data from DT API
						$insertRecord['dt_id'] = isset($record['id']) ? $record['id'] : NULL;
                        $insertRecord['createTime'] = isset($record['createTime']) ? $this->gameTimeToServerTime($record['createTime']) : NULL;
                        $insertRecord['gameCode'] = isset($record['gameCode']) ? $record['gameCode'] : NULL;
                        $insertRecord['playerName'] = isset($record['playerName']) ? $record['playerName'] : NULL;
                        $insertRecord['betWins'] = isset($record['betWins']) ? $record['betWins'] : NULL;
                        $insertRecord['partentId'] = $parentId;
                        $insertRecord['prizeWins'] = isset($record['prizeWins']) ? $record['prizeWins'] : NULL;
                        $insertRecord['betLines'] = isset($record['betLines']) ? $record['betLines'] : NULL;
                        $insertRecord['betPrice'] = isset($record['betPrice']) ? $record['betPrice'] : NULL;
                        $insertRecord['creditAfter'] = isset($record['creditAfter']) ? $record['creditAfter'] : NULL;
                        $insertRecord['fcid'] = isset($record['fcid']) ? $record['fcid'] : NULL;

						//extra info from SBE
						$insertRecord['username'] = $playerUsername;
						$insertRecord['playerId'] = $playerID;
						$insertRecord['uniqueid'] = $record['id']; //add external_uniueid for og purposes
						$insertRecord['external_uniqueid'] = $record['id']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;
						//insert data to Ezugi gamelogs table database
						$this->CI->dt_game_logs->insertGameLogs($insertRecord);
						$dataCount++;
					}

					$result['data_count'] = $dataCount;
				}
			}
		}
		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'dt_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->dt_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $dt_data) {

				$player_id = $dt_data['playerId'];

				if (!$player_id) {
					continue;
				}

				$cnt++;
				$result_amount = $dt_data['result_amount'] - $dt_data['BetAmount'];

				$game_description_id = $dt_data['game_description_id'];
				$game_type_id = $dt_data['game_type_id'];

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$real_bet=$dt_data['BetAmount'];

				$extra_info=['trans_amount'=>$real_bet, 'table'=>$dt_data['external_uniqueid']];

				#check if free round before and modify the real_betting_amount and bet_for_cashback
				if (isset($dt_data['fcid'])) {
					$extra_info['real_betting_amount'] = 0.0;
					$extra_info['bet_for_cashback'] = 0.0;

					if (isset($extra_info['trans_amount'])) {
						unset($extra_info['trans_amount']);
					}
				}

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$dt_data['game_code'],
					$dt_data['game_type'],
					$dt_data['game'],
					$player_id,
					$dt_data['username'],
					$this->gameAmountToDB($dt_data['BetAmount']), # $dt_data['BetAmount'],
					$this->gameAmountToDB($result_amount), #$result_amount,
					null, # win_amount
					null, # loss_amount
					$dt_data['creditAfter'], # after_balance
					0, # has_both_side
					$dt_data['external_uniqueid'],
					$dt_data['game_date'], //start
					$dt_data['game_date'], //end
					$dt_data['response_result_id'],
					Game_logs::FLAG_GAME, $extra_info
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function login($username, $password = null) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	// function getGameTimeToServerTime() {
	// 	//return '+8 hours';
	// }

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	// function getServerTimeToGameTime() {
	// 	//return '-8 hours';
	// }

    function queryTransaction($transactionId, $extra) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
            'playerName'      => $extra['playerName'],
            'external_transaction_id' => $transactionId,
            'playerId' => $extra['playerId'],
		);

		$api_method = 'CHECKTRANSFER';
		$params = array(
			'METHOD' => $api_method,
			'BUSINESS' => $this->merchant_id,
			'TRANSFER_ID' => $transactionId,
			'SIGNATURE' => MD5($this->merchant_id.$api_method.$transactionId.$this->api_key)
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	function processResultForQueryTransaction($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArr = json_decode($resultText,TRUE);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $result = array('response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);

		$success = $resultArr['RESPONSECODE'] == '000034' ? true : false;
		if ($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
            $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
		}
		return array($success, $result);
	} 

	/** 
    *  The api will return the bet details URL link for viewing the details
    */
    public function queryBetDetailLink($playerUsername, $betid = NULL, $extra = NULL)
    {        
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $playerId = $this->getPlayerIdInPlayer($playerUsername);
        $params = [
        	'dto.id'   => $betid,
        	'language' => isset($extra['language']) ? $this->getLauncherLanguage($extra['language'])  : 'en_US'
        ];

        $success = true;
        return array($success, 'url' => $this->bet_link_url .  urldecode(utf8_encode(http_build_query($params))));
    }

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/