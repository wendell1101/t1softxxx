<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_gameplay_sbtech extends Abstract_game_api {

	private $api_url;
	private $currency;
	private $language;
	private $op;
	private $opPwd;
	private $gsp;
	private $countryCode;
	private $history_url;

	const URI_MAP = array(
		self::API_createPlayer		 => 'CreateUser',
		self::API_isPlayerExist		 => 'MemberBalance',
		self::API_queryPlayerInfo	 => 'MemberBalance',
		self::API_depositToGame 	 => 'Credit',
		self::API_withdrawFromGame 	 => 'Debit',
		self::API_queryForwardGame	 => 'PlayGame',
		self::API_syncGameRecords	 => 'History'
	);

	const ERROR_PLAYER_NOT_FOUND=203;
    const ERROR_PLAYER_EXISTS=205;
    const DEFAULT_SYNC_SLEEP_TIME = 120;
    const FLAG_TRUE_STR = "Y";
    const PARLAY_GAME_CODE = "Mix Parlay";

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency');
		$this->language = $this->getSystemInfo('language');
		$this->op = $this->getSystemInfo('OP');
		$this->opPwd = $this->getSystemInfo('OPpwd');
		$this->gsp = $this->getSystemInfo('gsp');
		$this->countryCode = $this->getSystemInfo('countryCode');
		$this->history_url = $this->getSystemInfo('history_url');
	}

	public function getPlatformCode() {
		return GAMEPLAY_SBTECH_API;
	}

	public function generateUrl($apiName, $params) {
		if(self::URI_MAP[self::API_syncGameRecords] == $params["method"]) {
		 	unset($params["method"]);
			$url = $this->history_url;
			return $url .= '?' . http_build_query($params);
		}
		else{
			return $this->api_url;
		}
	}


	public function getHttpHeaders($params){
		if(self::URI_MAP[self::API_syncGameRecords] == $params["method"]) {
		 	return array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic '. base64_encode($this->op.':'.$this->opPwd)
			);
		}
		else{
			return array('Content-Type' => 'application/xml');
		}
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		if(self::URI_MAP[self::API_syncGameRecords] == $apiName) {
			return $errCode || intval($statusCode, 10) >= 401;
		}
		return $errCode || intval($statusCode, 10) >= 400;
	}

	protected function customHttpCall($ch, $params) {
		if(self::URI_MAP[self::API_syncGameRecords] != $params["method"]) {
			$header = array(
				'Method' => $params['method'],
				'Op' => $this->op,
				'OPpwd' => $this->opPwd,
			);
			unset($params["method"]);
			$data = array(
				'Header' => $header,
				'Parameter' => $params,
			);
			$xml_object = new SimpleXMLElement("<Request></Request>");
			$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
			// header("Content-type: text/plain");
			// echo $xmlData;exit();
			$this->CI->utils->debug_log('-----------------------GAMEPLAYSBTECH POST XML STRING ----------------------------',$xmlData);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
		}

	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $history = false) {
		$success = false;
		if($history){
			$success = ($resultArr['code'] == 0000) ? true : false;
		}else{
			$success = ($resultArr['ErrorCode'] == 0) ? true : false;
		}
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('GAMEPLAYSBTECH GOT RESPONSE ======================================>', $responseResultId, 'result', json_encode($resultArr));
            $success = false;
        }
        // echo $success ? 'true' : 'false';exit();
        return $success;
	}

	public function createPlayer($userName, $playerId, $password, $email = null, $extra = null) {
			parent::createPlayer($userName, $playerId, $password, $email, $extra);
			$playerName = $this->getGameUsernameByPlayerUsername($userName);
			$externalId = $this->getSystemInfo('prefix_for_username').str_pad($playerId, 6, "0", STR_PAD_LEFT);
	        $context = array(
	            'callback_obj'	 	=> $this,
	            'callback_method' 	=> 'processResultForCreatePlayer',
	            'playerName' 		=> $playerName,
	            'sbe_playerName' 	=> $userName,
	            'playerId' 			=> $playerId,
	            'externalId' 		=> $externalId
	        );

	        $params = array(
	            "GameCode" 		=> $this->gsp,
				"MemberID" 		=> $externalId,
				"Currency" 		=> $this->currency,
				"Language" 		=> $this->language,
				"CountryCode" 	=> $this->countryCode,
				"method" 		=> self::URI_MAP[self::API_createPlayer]
	        );
	        $this->utils->debug_log("CreatePlayer params ============================>", $params);
	        return $this->callApi(self::URI_MAP[self::API_createPlayer], $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId 	= $this->getResponseResultIdFromParams($params);
		$resultXml 			= $this->getResultXmlFromParams($params);
		$resultXml 			= (array) $this->getResultXmlFromParams($params);
		$resultXml 			= (array) $resultXml['Header'];
		$success 			= $this->processResultBoolean($responseResultId, $resultXml);
		$playerId 			= $this->getVariableFromContext($params, 'playerId');
		$playerName 		= $this->getVariableFromContext($params, 'playerName');
		$externalId 		= $this->getVariableFromContext($params, 'externalId');
		if($success){
            //update external AccountID
            $this->updateExternalAccountIdForPlayer($playerId, $externalId);
        }

        $result = array(
        	"responseResultId" => $responseResultId,
        	"player" => $playerName
        );

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function isPlayerExist($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
        $externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);

        if (empty($externalId)) {
            $externalId = $this->getSystemInfo('prefix_for_username').str_pad($playerId, 6, "0", STR_PAD_LEFT);
        }

        $context = array(
            'callback_obj'		=> $this,
            'callback_method' 	=> 'processResultForIsPlayerExist',
            'playerName' 		=> $playerName,
            'playerId' 			=> $playerId,
            'sbe_playerName' 	=> $userName,
            'externalId' 		=> $externalId
        );

        $params = array(
            "GameCode" 		=> $this->gsp,
			"MemberID" 		=> $externalId,
			"Currency" 		=> $this->currency,
			"Language" 		=> $this->language,
			"CountryCode" 	=> $this->countryCode,
			"method" 		=> self::URI_MAP[self::API_isPlayerExist]
        );
        $this->utils->debug_log("playerexist params ============================>", $params);
	    return $this->callApi(self::URI_MAP[self::API_isPlayerExist], $params, $context);
	}

	public function processResultForIsPlayerExist($params) {
		$playerId           = $this->getVariableFromContext($params, 'playerId');
       	$responseResultId 	= $this->getResponseResultIdFromParams($params);
		$resultXml 			= $this->getResultXmlFromParams($params);
		$resultXml 			= (array) $this->getResultXmlFromParams($params);
		$resultXml 			= (array) $resultXml['Header'];
		$success 			= $this->processResultBoolean($responseResultId, $resultXml);
		if($success){
            $result['exists'] = true;
            //update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		} elseif ($resultXml['ErrorCode'] == self::ERROR_PLAYER_EXISTS){
            $this->updateExternalAccountIdForPlayer($playerId, $externalId);
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
            $success = true;
        } else {
			if (@$resultXml['ErrorCode'] == self::ERROR_PLAYER_NOT_FOUND) {
				$success = true;
				$result['exists'] = false;
			}
			else{
				$result['exists'] = null;
			}
		}
        $this->utils->debug_log("Check player if exist ============================>", $success);
		return array($success, $result);
	}

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' 		=> $this,
            'callback_method' 	=> 'processResultForQueryPlayerBalance',
            'playerName' 		=> $playerName,
            'sbe_playerName' 	=> $userName,
            'externalId' 		=> $externalId
        );

        $params = array(
            "GameCode" 		=> $this->gsp,
			"MemberID" 		=> $externalId,
			"Currency" 		=> $this->currency,
			"Language" 		=> $this->language,
			"CountryCode" 	=> $this->countryCode,
			"method" 		=> self::URI_MAP[self::API_queryPlayerInfo]
        );
        $this->utils->debug_log("playercheck balance params ============================>", $params);
	    return $this->callApi(self::URI_MAP[self::API_queryPlayerInfo], $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultXml = (array) $this->getResultXmlFromParams($params);
		$header = (array) $resultXml['Header'];
		$parameter = (array) $resultXml['Parameter'];
		$this->utils->debug_log("check Query params ============================>", $parameter['Balance']);
		$success = $this->processResultBoolean($responseResultId, $header);
		if($success){
			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('GAMEPLAYSBTECH GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $parameter['Balance']);
			} else {
				$this->CI->utils->debug_log('GAMEPLAYSBTECH GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}
		$result['balance'] = @floatval($parameter['Balance']);
       	$result['exists'] = $success;
		return array($success, $result);
	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
            'externalId' 		=> $externalId
        );

        $params = array(
            "GameCode" 		=> $this->gsp,
			"MemberID" 		=> $externalId,
			"Currency" 		=> $this->currency,
			"Language" 		=> $this->language,
			"CountryCode" 	=> $this->countryCode,
			"Amount" 		=> floatval($amount),
			"method" 		=> self::URI_MAP[self::API_depositToGame]
        );
        $this->utils->debug_log("player deposit params ============================>", $params);
	    return $this->callApi(self::URI_MAP[self::API_depositToGame], $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$amount = $this->getVariableFromContext($params, 'amount');
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultXml = (array) $this->getResultXmlFromParams($params);
		$header = (array) $resultXml['Header'];
		$parameter = (array) $resultXml['Parameter'];
		$success = $this->processResultBoolean($responseResultId, $header);
		if($success){
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);
			//for sub wallet
			$afterBalance = @$playerBalance['balance'];
			// $result["external_transaction_id"] = $parameter['transactionId'];
			if(!empty($afterBalance)){
				$result["currentplayerbalance"] = $afterBalance;
			}
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}
		return array($success, $result);
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
            'externalId' 		=> $externalId
        );

        $params = array(
            "GameCode" 		=> $this->gsp,
			"MemberID" 		=> $externalId,
			"Currency" 		=> $this->currency,
			"Language" 		=> $this->language,
			"CountryCode" 	=> $this->countryCode,
			"Amount" 		=> floatval($amount),
			"method" 		=> self::URI_MAP[self::API_withdrawFromGame]
        );
        // print_r($params);exit();
        $this->utils->debug_log("player withdraw params ============================>", $params);
	    return $this->callApi(self::URI_MAP[self::API_withdrawFromGame], $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
    	$amount = $this->getVariableFromContext($params, 'amount');
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultXml = (array) $this->getResultXmlFromParams($params);
		$header = (array) $resultXml['Header'];
		$parameter = (array) $resultXml['Parameter'];
		$success = $this->processResultBoolean($responseResultId, $header);
		if($success){
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);
			//for sub wallet
			$afterBalance = @$playerBalance['balance'];
			// $result["external_transaction_id"] = $parameter['transactionId'];
			if(!empty($afterBalance)){
				$result["currentplayerbalance"] = $afterBalance;
			}
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}
		return array($success, $result);
    }

	public function queryForwardGame($userName, $extra = null) {
		$this->CI->load->model('common_token');
		$playerId 		= $this->getPlayerIdFromUsername($userName);
		$token 			= $this->CI->common_token->createTokenBy($playerId, 'player_id');
		$playerName 	= $this->getGameUsernameByPlayerUsername($userName);
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
            'sportsbook'	=> $extra['game'],
            'externalId' 		=> $externalId
        );
        $params = array(
            "GameCode" 		=> $this->gsp,
			"MemberID" 		=> $externalId,
			"Currency" 		=> $this->currency,
			"Language" 		=> $this->language,
			"CountryCode" 	=> $this->countryCode,
			"MemberToken"	=> $token,
			"method" 		=> self::URI_MAP[self::API_queryForwardGame]
        );
        // echo"<pre>";print_r($params);exit();
        $this->utils->debug_log("playgame params ============================>", $params);
	    return $this->callApi(self::URI_MAP[self::API_queryForwardGame], $params, $context);
	}

	public function processResultForQueryForwardGame($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $sportsbook = $this->getVariableFromContext($params, 'sportsbook');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$result = $resultXml['Parameter'];
		/* ===== sample sportsbook ======
			*EuroSportsbook
			*AsianSportsbook
			*EuroLiveSportsbook
			*MobileHTML5GamesURL
		*/
		$data = [
            'url' => $result[$sportsbook],
            'success' => true
        ];
        // echo"<pre>";print_r($result);exit();
        $this->utils->debug_log(' Gameplay Sbtech generateUrl - =================================================> ' . $result[$sportsbook]);
        return array(true, $data);
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		//observer the date format
		$startDate->modify($this->getDatetimeAdjust());
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');



		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate,
			'syncLostAndFound' => false
		);

		$params = array(
			'gameId' 		=> $this->gsp,
			'start' 		=> $startDate,
			'end' 			=> $endDate,
			'method'		=> self::URI_MAP[self::API_syncGameRecords]
		);
		$this->utils->debug_log(' Gameplay Sbtech params - =================================================> ' .json_encode($params));
		return $this->callApi(self::URI_MAP[self::API_syncGameRecords], $params, $context);

	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('gameplay_sbtech_game_logs', 'player_model'));
		$resultArr 			= $this->getResultJsonFromParams($params);
        $responseResultId 	= $this->getResponseResultIdFromParams($params);
        $syncLostAndFound 	= $this->getVariableFromContext($params, 'syncLostAndFound');
        $success 			= $this->processResultBoolean($responseResultId, $resultArr['status'],true);
        $result 			= array();
        $this->utils->debug_log(' Gameplay Sbtech record response - =================================================> ' .json_encode($resultArr));
        $this->utils->debug_log(' Gameplay Sbtech isSynclost =====> ' .json_encode($syncLostAndFound));
        if($success){
        	$gameRecords = $resultArr['items'];
        	$dateTimeNow = date('Y-m-d H:i:s');
        	if (!empty($gameRecords)) {
        		$count = 0;
        		$this->utils->debug_log(' Gameplay Sbtech record response - =================================================> ' .json_encode($gameRecords));
        		// echo"<pre>";print_r($gameRecords);exit();
				foreach ($gameRecords as $record) {
					$insertRecord = array();
					$playerId = $this->getPlayerIdByExternalAccountId($record['memberId']);
					$playerUsername = $this->getGameUsernameByPlayerId($playerId);
					//Data from Gameplay Sbtech API
					$insertRecord['idx'] 				= isset($record['idx']) ? $record['idx'] : NULL;
					$insertRecord['merchId'] 			= isset($record['merchId']) ? $record['merchId'] : NULL;
					$insertRecord['memberId'] 			= isset($record['memberId']) ? $record['memberId'] : NULL;
					$insertRecord['rowId'] 				= isset($record['rowId']) ? $record['rowId'] : NULL;
					$insertRecord['agentId'] 			= isset($record['agentId']) ? $record['agentId'] : NULL;
					$insertRecord['customerId'] 		= isset($record['customerId']) ? $record['customerId'] : NULL;
					$insertRecord['merchantCustomerId'] = isset($record['merchantCustomerId']) ? $record['merchantCustomerId'] : NULL;
					$insertRecord['betId'] 				= isset($record['betId']) ? $record['betId'] : NULL;
					$insertRecord['purchaseId'] 		= isset($record['purchaseId']) ? $record['purchaseId'] : NULL;
					$insertRecord['betTypeId'] 			= isset($record['betTypeId']) ? $record['betTypeId'] : NULL;
					$insertRecord['betTypeName'] 		= isset($record['betTypeName']) ? $record['betTypeName'] : NULL;
					$insertRecord['lineId'] 			= isset($record['lineId']) ? $record['lineId'] : NULL;
					$insertRecord['lineTypeId'] 		= isset($record['lineTypeId']) ? $record['lineTypeId'] : NULL;
					$insertRecord['lineTypeName'] 		= isset($record['lineTypeName']) ? $record['lineTypeName'] : NULL;
					$insertRecord['rowTypeId'] 			= isset($record['rowTypeId']) ? $record['rowTypeId'] : NULL;
					$insertRecord['branchId'] 			= isset($record['branchId']) ? $record['branchId'] : NULL;
					$insertRecord['branchName'] 		= ($record['parlayChecked'] == self::FLAG_TRUE_STR)? self::PARLAY_GAME_CODE: $record['branchName'];
					$insertRecord['leagueId'] 			= isset($record['leagueId']) ? $record['leagueId'] : NULL;
					$insertRecord['leagueName'] 		= isset($record['leagueName']) ? $record['leagueName'] : NULL;
					$insertRecord['eventName'] 			= isset($record['eventName']) ? $record['eventName'] : NULL;
					$insertRecord['creationDate'] 		= isset($record['creationDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['creationDate']))) : NULL;
					$insertRecord['homeTeam'] 			= isset($record['homeTeam']) ? $record['homeTeam'] : NULL;
					$insertRecord['awayTeam'] 			= isset($record['awayTeam']) ? $record['awayTeam'] : NULL;
					$insertRecord['stake'] 				= isset($record['stake']) ? $record['stake'] : NULL;
					$insertRecord['odds'] 				= isset($record['odds']) ? $record['odds'] : NULL;
					$insertRecord['points'] 			= isset($record['points']) ? $record['points'] : NULL;
					$insertRecord['score'] 				= isset($record['score']) ? $record['score'] : NULL;
					$insertRecord['status'] 			= isset($record['status']) ? $record['status'] : NULL;
					$insertRecord['yourBet'] 			= isset($record['yourBet']) ? $record['yourBet'] : NULL;
					$insertRecord['isForEvent'] 		= isset($record['isForEvent']) ? $record['isForEvent'] : NULL;
					$insertRecord['eventTypeId'] 		= isset($record['eventTypeId']) ? $record['eventTypeId'] : NULL;
					$insertRecord['eventTypeName'] 		= isset($record['eventTypeName']) ? $record['eventTypeName'] : NULL;
					$insertRecord['orderId'] 			= isset($record['orderId']) ? $record['orderId'] : NULL;
					$insertRecord['updateDate'] 		= isset($record['updateDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['updateDate']))) : NULL;
					$insertRecord['pl'] 				= isset($record['pl']) ? $record['pl'] : NULL;
					$insertRecord['teamMappingId'] 		= isset($record['teamMappingId']) ? $record['teamMappingId'] : NULL;
					$insertRecord['liveScore1'] 		= isset($record['liveScore1']) ? $record['liveScore1'] : NULL;
					$insertRecord['liveScore2'] 		= isset($record['liveScore2']) ? $record['liveScore2'] : NULL;
					$insertRecord['eventDate'] 			= isset($record['eventDate']) ? $record['eventDate'] : NULL;
					$insertRecord['masterEventId'] 		= isset($record['masterEventId']) ? $record['masterEventId'] : NULL;
					$insertRecord['commonStatusId'] 	= isset($record['commonStatusId']) ? $record['commonStatusId'] : NULL;
					$insertRecord['webProviderId'] 		= isset($record['webProviderId']) ? $record['webProviderId'] : NULL;
					$insertRecord['webProviderName'] 	= isset($record['webProviderName']) ? $record['webProviderName'] : NULL;
					$insertRecord['bonusAmount'] 		= isset($record['bonusAmount']) ? $record['bonusAmount'] : NULL;
					$insertRecord['domainId'] 			= isset($record['domainId']) ? $record['domainId'] : NULL;
					$insertRecord['regDate'] 			= isset($record['regDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['regDate']))) : NULL;
					$insertRecord['parlayChecked'] 		= isset($record['parlayChecked']) ? $record['parlayChecked'] : NULL;
					$insertRecord['parlays'] 			= isset($record['parlays']) ? json_encode($record['parlays']) : NULL;
					if($insertRecord['status'] !="Open" &&  $insertRecord['status'] !="Opened"){
						$insertRecord['doneTime'] = date('Y-m-d H:i:s'); //add settled date

					}
					//extra info from SBE
					$insertRecord['Username'] = $playerUsername;
					$insertRecord['PlayerId'] = $playerId;
					$insertRecord['external_uniqueid'] = $insertRecord['idx']; //add external_uniueid for og purposes
					$insertRecord['response_result_id'] = $responseResultId;
					//insert data to Gameplay Sbtech gamelogs table database
					$isExists = $this->CI->gameplay_sbtech_game_logs->isRowIdAlreadyExists($insertRecord['idx']);
					if ($isExists) {
						$this->CI->gameplay_sbtech_game_logs->updateGameLogs($insertRecord);
					} else {
						$this->CI->gameplay_sbtech_game_logs->insertGameLogs($insertRecord);
					}
					$count++;

					// if($syncLostAndFound) {
		        		$this->syncMergeToGameLogsInstantly($insertRecord);
			        // }
				}
				$result['data_count'] = $count;
        	}
        }
      	return array($success, $result);
	}

	public function syncMergeToGameLogsInstantly($insertRecord = null) {
		$this->CI->load->model('game_description_model');
		$gameDetails = $this->CI->game_description_model->getGameDescriptionByGamePlatformIdAndGameCode(GAMEPLAY_SBTECH_API,$insertRecord['branchName']);

		$unknownGame = $this->getUnknownGame();
		$slfData = array(
			"player_id" 			=> isset($insertRecord['PlayerId']) ? $insertRecord['PlayerId'] : NULL,
			"username" 				=> isset($insertRecord['Username']) ? $insertRecord['Username'] : NULL,
			"game_code" 			=> (!empty($gameDetails)) ? $gameDetails[0]->game_code : $unknownGame->game_code,
			"game_description_id" 	=> (!empty($gameDetails)) ? $gameDetails[0]->id : $unknownGame->id,
			"game_type_id" 			=> (!empty($gameDetails)) ? $gameDetails[0]->game_type_id : $unknownGame->game_type_id,
			"gameshortcode" 		=> (!empty($gameDetails)) ? $gameDetails[0]->game_code : $unknownGame->game_code,
			"game" 					=> (!empty($gameDetails)) ?	$gameDetails[0]->game_name : $unknownGame->game_name,
			"game_type" 			=> (!empty($gameDetails)) ? $gameDetails[0]->game_type : $unknownGame->game_type,
			"bet_amount" 			=> isset($insertRecord['stake']) ? $insertRecord['stake'] : NULL,
			"result_amount" 		=> isset($insertRecord['pl']) ? $insertRecord['pl'] : NULL,
			"response_result_id" 	=> isset($insertRecord['response_result_id']) ? $insertRecord['response_result_id'] : NULL,
			"external_uniqueid"		=> isset($insertRecord['idx']) ? $insertRecord['idx'] : NULL,
			"bet_date"				=> isset($insertRecord['creationDate']) ? $insertRecord['creationDate'] : NULL,
			"settled_date"			=> isset($insertRecord['updateDate']) ? $insertRecord['updateDate'] : NULL,
			"parlayChecked"			=> isset($insertRecord['parlayChecked']) ? $insertRecord['parlayChecked'] : NULL,
			"parlays"				=> isset($insertRecord['parlays']) ? $insertRecord['parlays'] : NULL,
			"betId"					=> isset($insertRecord['betId']) ? $insertRecord['betId'] : NULL,
			"yourBet"				=> isset($insertRecord['yourBet']) ? $insertRecord['yourBet'] : NULL,
			"status"				=> isset($insertRecord['status']) ? $insertRecord['status'] : NULL,
		);
		$betDetails = $this->CI->utils->encodeJson(array_merge(
                $this->processGameBetDetail($insertRecord),
                array('sports_bet' => $this->setBetDetails($insertRecord) )
            )
        );
		$status = $this->getGameRecordsStatus($slfData['status']);
		$extra = array(
			'trans_amount'	=> 	$slfData['bet_amount'],
			'status'		=> 	$status,
			'note'			=> '',
            'bet_details'	=> $betDetails,
            'table'         => isset($insertRecord['rowId']) ? $insertRecord['rowId'] : NULL
			);
		$this->syncGameLogs(
			$slfData['game_type_id'],
			$slfData['game_description_id'],
			$slfData['game_code'],
			$slfData['game_type'],
			$slfData['game'],
			$slfData['player_id'],
			$slfData['username'],
			$slfData['bet_amount'],
			$slfData['result_amount'],
			null, # win_amount
			null, # loss_amount
			null,//$slfData['after_balance'], # after_balance
			0, # has_both_side
			$slfData['external_uniqueid'],
			$slfData['bet_date'], //start
			$slfData['settled_date'], //end
			$slfData['response_result_id'],
			Game_logs::FLAG_GAME,
			$extra
		);
	}

	public function syncLostAndFound($token) {
		$this->CI->load->model(array('game_logs','gameplay_sbtech_game_logs'));
		$result = $this->CI->gameplay_sbtech_game_logs->getOpenLogs();
		// if(!empty($result)) {
		// 	echo"<pre>";print_r($result);exit();
		// }
		$this->utils->debug_log(' syncLostAndFound - is running ');
		$creationDate = array();
		if(!empty($result)) {
			foreach ($result as $key => $resulti) {
				$creationDate[] = $resulti['creationDate'];
			}
		}

		if(!empty($creationDate)) {
			$startDate = date('Y-m-d H:i:s',min(array_map('strtotime', $creationDate)));
			$endDate   = date('Y-m-d H:i:s',max(array_map('strtotime', $creationDate)));
			if(count($creationDate) > 1) {
				$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+1 days', function($startDate, $endDate) {
					$startDate = $startDate->format('Y-m-d H:i:s');
					$endDate = $endDate->format('Y-m-d H:i:s');
					$resultByRange = $this->CI->gameplay_sbtech_game_logs->getOpenLogs($startDate,$endDate);
					$this->utils->debug_log('syncLostAndFound resultByRange - '.json_encode($resultByRange));
					if(!empty($resultByRange)) {
						$this->utils->debug_log('syncLostAndFound startDate - '.$startDate);
						$this->utils->debug_log('syncLostAndFound endDate - '.$endDate);
						$context = array(
							'callback_obj' => $this,
							'callback_method' => 'processResultForSyncGameRecords',
							'startDate' => $this->serverTimeToGameTime($startDate),
							'endDate' => $this->serverTimeToGameTime($endDate),
							'syncLostAndFound' => true
						);

						$params = array(
							'gameId' 		=> $this->gsp,
							'start' 		=> $this->serverTimeToGameTime($startDate),
							'end' 			=> $this->serverTimeToGameTime($endDate),
							'method'		=> self::URI_MAP[self::API_syncGameRecords]
						);
						return $this->callApi(self::URI_MAP[self::API_syncGameRecords], $params, $context);
						// sleep(self::DEFAULT_SYNC_SLEEP_TIME);
					}else{
						return true;
					}
				});
			}
			else {
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					'startDate' => $this->serverTimeToGameTime($startDate),
					'endDate' => $this->serverTimeToGameTime($endDate),
					'syncLostAndFound' => true
				);

				$params = array(
					'gameId' 		=> $this->gsp,
					'start' 		=> $this->serverTimeToGameTime($startDate),
					'end' 			=> $this->serverTimeToGameTime($endDate),
					'method'		=> self::URI_MAP[self::API_syncGameRecords]
				);
					// print_r($params);exit();
				return $this->callApi(self::URI_MAP[self::API_syncGameRecords], $params, $context);
			}
			// sleep(self::DEFAULT_SYNC_SLEEP_TIME);
		}
		return array('success' => true);
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('game_logs', 'player_model', 'gameplay_sbtech_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->gameplay_sbtech_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $gameplay_sbtech_game_logs) {

				if (!$gameplay_sbtech_game_logs['PlayerId']) {
					continue;
				}

				$cnt++;

				$game_description_id = $gameplay_sbtech_game_logs['game_description_id'];
				$game_type_id = $gameplay_sbtech_game_logs['game_type_id'];
				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				$betDetails = $this->CI->utils->encodeJson(array_merge(
                        $this->processGameBetDetail($gameplay_sbtech_game_logs),
                        array('sports_bet' => $this->setBetDetails($gameplay_sbtech_game_logs))
                    )
                );
				$status = $this->getGameRecordsStatus($gameplay_sbtech_game_logs['status']);

				$extra = array(
					'trans_amount'	=> 	$gameplay_sbtech_game_logs['BetAmount'],
					'odds'			=> 	$gameplay_sbtech_game_logs['odds'],
                    'odds_type'     =>  'eu', # hard-code based on OGP-3819
					'status'		=> 	$status,
                    'note'          =>  '',
					'bet_details'	=>  $betDetails,
                    'table'         =>  isset($gameplay_sbtech_game_logs['rowId']) ? $gameplay_sbtech_game_logs['rowId'] : NULL
				);


				$this->CI->utils->debug_log('=====> Extra monitor', $extra);
				$this->CI->utils->debug_log('=====> Status', $status);
                $sportsGameFields = array();
                if ($status == Game_logs::STATUS_SETTLED) {
                    $sportsGameFields = array(
                        'match_details'     => $gameplay_sbtech_game_logs['match_details'],
                        'match_type'        => $gameplay_sbtech_game_logs['eventTypeName'],
                        'handicap'          => '',
                        'bet_type'          => $gameplay_sbtech_game_logs['betTypeName']
                    );
                }
                $this->utils->debug_log('==============> Gameplay SBtech Sport Game Fields Value', $sportsGameFields);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$gameplay_sbtech_game_logs['game_code'],
					$gameplay_sbtech_game_logs['game_type'],
					$gameplay_sbtech_game_logs['game'],
					$gameplay_sbtech_game_logs['PlayerId'],
					$gameplay_sbtech_game_logs['UserName'],
					$gameplay_sbtech_game_logs['BetAmount'],
					$gameplay_sbtech_game_logs['result_amount'],
					null, # win_amount
					null, # loss_amount
					null,//$gameplay_sbtech_game_logs['after_balance'], # after_balance
					0, # has_both_side
					$gameplay_sbtech_game_logs['external_uniqueid'],
					$gameplay_sbtech_game_logs['game_date'], //start
					$gameplay_sbtech_game_logs['game_date'], //end
					$gameplay_sbtech_game_logs['response_result_id'],
					Game_logs::FLAG_GAME,
					$extra,
                    $sportsGameFields
				);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	public function processGameBetDetail($rowArray) {
		$betDetails = array();
		$parlays = (array)json_decode($rowArray['parlays'], true);

		$this->CI->utils->debug_log('=====> Bet Details monitor', $rowArray);

		if($rowArray['parlayChecked'] == "Y" && !empty($parlays)) {
			$bets = array();
			$rates = array();

			$this->CI->utils->debug_log('=====> parlays monitor', $parlays);
			if (!empty($parlays)) {
				foreach ($parlays as $key => $parlay) {
					array_push($bets, $parlay['stake']);
					array_push($rates, $parlay['odds']);
				}

				$betDetails = array(
					'bet' => implode(',', $bets),
					'rate' => implode(',', $rates),
				);
			}
		} else {
			$betDetails = array(
				'bet' => $rowArray['stake'],
				'rate' => $rowArray['odds'],
			);
		}

		$this->CI->utils->debug_log('=====> Bet Details return', $betDetails);

		return $betDetails;
	}

    public function setBetDetails($field){
        $data = json_decode($field['parlays'],true);
        $set = array();
        if(!empty($data)){
            foreach ($data as $key => $game) {
                $set[$key] = array(
                    'yourBet' => $game['yourBet'],
                    'isLive' => ($game['liveScore1'] || $game['liveScore2']) > 0,
                    'odd'   => $game['odds'],
                    'hdp'   => 'N/A',
                    'htScore'=> $game['score'],
                    'eventName' => $game['homeTeam'].' vs '.$game['awayTeam'],
                    'league' => $game['leagueName'],
                );
            }
        }else{
            $set[] = array(
                'yourBet' => $field['yourBet'],
                'isLive' => ($field['liveScore1'] || $field['liveScore2']) > 0,
                'odd' => $field['odds'],
                'hdp'=> 'N/A',
                'htScore'=> $field['score'],
                'eventName' => $field['match_details'],
                'league' => $field['leagueName'],
            );
        }
        return $set;
    }

	/**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) {
		$this->CI->load->model(array('game_logs'));
		$status = strtolower($status);

		switch ($status) {
		case 'opened':
		case 'open':
			$status = Game_logs::STATUS_ACCEPTED;
			break;
		case 'canceled':
			$status = Game_logs::STATUS_CANCELLED;
			break;
		case 'won':
		case 'half won':
		case 'draw':
		case 'lost':
		case 'half lost':
			$status = Game_logs::STATUS_SETTLED;
			break;
		}
		return $status;
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}


	public function login($username, $password = null) {
		return $this->returnUnimplemented();
	}

	public function processResultForgetVendorId($params) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	/*public function getGameTimeToServerTime() {
		//return '+8 hours';
	}*/

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	/*public function getServerTimeToGameTime() {
		//return '-8 hours';
	}*/

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}


}

/*end of file*/