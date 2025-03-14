<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: RTG AS API Specifications with Transfer Wallet
	* Document Number: 


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_rtg_master extends Abstract_game_api {

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';
	const API_getRTGApiToken = 'getRTGApiToken';
	const API_getRTGAgentId = 'getRTGAgentId';

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency');
		$this->language = $this->getSystemInfo('language');
		$this->api_username = $this->getSystemInfo('api_username');
		$this->api_password = $this->getSystemInfo('api_password');
		$this->game_url = $this->getSystemInfo('game_launcher_url');
		$this->method = 'POST'; # default as POST

		$this->URI_MAP = array(
			self::API_getRTGApiToken => '/start/token',
			self::API_getRTGAgentId => '/start',
			self::API_createPlayer => '/player',
			self::API_queryPlayerBalance => '/wallet',
	        self::API_depositToGame => '/wallet/deposit/',
	        self::API_withdrawFromGame => '/wallet/withdraw/',
	        self::API_queryForwardGame => '/GameLauncher',
			self::API_syncGameRecords => '/report/playergame'
		);
	}

	public function getPlatformCode() {
		return RTG_MASTER_API;
	}

	public function generateUrl($apiName, $params) {
		unset($params['method']);
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		if($apiUri == $this->URI_MAP[self::API_getRTGApiToken]){
			unset($params['ignore_headders']);
			$url = $this->api_url.$apiUri.'?'.http_build_query($params);
		}
		if($apiUri == $this->URI_MAP[self::API_createPlayer]){
			unset($params['ignore_headders']);
			$url = $this->api_url.$apiUri.'?'.http_build_query($params);
		}
		if($apiUri == $this->URI_MAP[self::API_depositToGame]){
			$url = $this->api_url.$apiUri.$params['amount'].'?trackingOne='.$params['trackingOne'];
		}
		if($apiUri == $this->URI_MAP[self::API_withdrawFromGame]){
			$url = $this->api_url.$apiUri.$params['amount'].'?trackingOne='.$params['trackingOne'];
		}
		$this->utils->debug_log('RTG MASTER API URL: ',$url);
		return $url;
	}

	protected function getHttpHeaders($params){
		# will ignore header
		if(isset($params['ignore_headders'])){
        	return array();
		}

		$token = $this->getRTGApiToken();

		$headers = array(
			'Authorization' => $token['token'],
			'Content-type' => 'application/json',
		    'Cache-control' => 'no-cache'
		);

		$this->utils->debug_log('RTG MASTER HEADERS: ',$headers);
		return $headers;
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 410;
    }

	protected function customHttpCall($ch, $params) {
		$this->method = @$params['method'];
		unset($params['method']);

		switch ($this->method) {
			case self::POST:
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);     
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
			case self::PUT:
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				break;
		}

		$this->utils->debug_log('RTG MASTER POSTFEILD: ',json_encode($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;

		if($resultArr['statusCode'] < 400){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('RTG MASTER API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function getRTGApiToken(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetRTGApiToken'
		);

		$params = array(
			'username' => $this->api_username,
			'password' => $this->api_password,
			'ignore_headders' => true,
			'method' => self::GET
		);

		return $this->callApi(self::API_getRTGApiToken, $params, $context);
	}

	public function processResultForGetRTGApiToken($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params);
	    $result['token'] = @$resultArr['token'];

		return array($success, $result);
	}

	public function getRTGAgentId(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetRTGAgentId'
		);

		$params = array(
			'method' => self::GET
		);

		return $this->callApi(self::API_getRTGAgentId, $params, $context);
	}

	public function processResultForGetRTGAgentId($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params);
	    $result['agentId'] = @$resultArr['agentId'];
		return array($success, $result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agentId = $this->getRTGAgentId();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agentId' => $agentId['agentId'],
		    'username' => $gameUsername,
		    'firstName' => $gameUsername,
		    'lastName' => $gameUsername,
		    'email' => $gameUsername.'@test.com', # Enter fake value if not required.
		    'gender' => 'male', #Enter fake value if not required.
		    'birthdate' => '1980-08-03T09:37:07.085Z',
		    'countryId' => 'CN', # Enter fake value if not required. Follows ISO 3166 Alpha 2 Code
			'currency' => $this->currency, 
			'method' => self::PUT
		);
		
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername);

		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
			$pid = isset($resultArr['id'])?$resultArr['id']:null;
			//update external AccountID 
			$this->updateExternalAccountIdForPlayer($playerId,$pid);
			# update flag to registered = truer
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
	        $result['exists'] = true;
		}

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		// $agentId = $this->getRTGAgentId();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'playerId' => $playerID,
			// 'playerLogin' => $gameUsername,
			// 'agentId' => $agentId['agentId'],
			'method' => self::POST
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername);
		$result = array();
		if($success){
			$result['balance'] = @floatval($resultArr);
		}

		return array($success, $result);

	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		if($playerID){
			$return = array('success'=>true,'exists'=>true);
	    }else{
	    	$return = array('success'=>true,'exists'=>false);
	    }

	    return $return;
    }

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }


	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		// $agentId = $this->getRTGAgentId();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
        );

		$params = array( 
			'playerId' => $playerID,
			// 'playerLogin' => $gameUsername,
			// 'agentId' => $agentId['agentId'],
			'amount' => $amount,
			'trackingOne' => $transfer_secure_id,
			'method' => self::POST
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {			
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            // if ($playerId) {
            //     // Deposit
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
            // } else {
            //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            //     $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
            // }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
        }

        return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		// $agentId = $this->getRTGAgentId();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
        );

		$params = array( 
			'playerId' => $playerID,
			// 'playerLogin' => $gameUsername,
			// 'agentId' => $agentId['agentId'],
			'amount' => $amount,
			'trackingOne' => $transfer_secure_id,
			'method' => self::POST
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
    //             // Withdraw
				
	   //          $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
        }

        return array($success, $result);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array('success' => true);
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case 1:
            case 'en-us':
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            default:
                $lang = 'zh-CN'; // default as chinese
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$params = array(
			'player'=>array(
				'playerId' => $playerID
			),
			'gameId' => $extra['game_code'],
			'locale' => $this->getLauncherLanguage($extra['language']),
			'returnUrl' => $extra['lobby_url'],
			'isDemo' => $extra['game_mode']=='real'?false:true,
			'method' => self::POST
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		$result = array('url'=>'');

		if($success){
			$result['url'] = $resultArr['instantPlayUrl'];
		}

		return array($success, $result);
	}
                
	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
    	$startDate->modify($this->getDatetimeAdjust());
    	$agentId = $this->getRTGAgentId();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		$params = array(
			'params' => array(
				'agentId' => $agentId['agentId'],
				'fromDate' => $startDate->format('Y-m-d H:i:s').'+8:00',
				'toDate' => $endDate->format('Y-m-d H:i:s').'+8:00',
			),
			'method' => self::POST
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('rtg_master_game_logs'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params);

		$result = [];

		$dataCount = 0;
		if($success){	
			$gameRecords = !empty($resultArr['items'])?$resultArr['items']:array();
			$availableRows = !empty($gameRecords)?$this->CI->rtg_master_game_logs->getAvailableRows($gameRecords):array();
			foreach ($availableRows as $record) {
				$insertRecord = array();
				$insertRecord['agentId'] = isset($record['agentId'])?$record['agentId']:null;
				$insertRecord['agentName'] = isset($record['agentName'])?$record['agentName']:null;
				$insertRecord['casinoPlayerId'] = isset($record['casinoPlayerId'])?$record['casinoPlayerId']:null;
				$insertRecord['casinoId'] = isset($record['casinoId'])?$record['casinoId']:null;
				$insertRecord['playerName'] = isset($record['playerName'])?$record['playerName']:null;
				$insertRecord['gameDate'] = isset($record['gameDate'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['gameDate']))):null;
				$insertRecord['gameStartDate'] = isset($record['gameStartDate'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['gameStartDate']))):null;
				$insertRecord['gameNumber'] = isset($record['gameNumber'])?$record['gameNumber']:null;
				$insertRecord['gameName'] = isset($record['gameName'])?$record['gameName']:null;
				$insertRecord['gameId'] = isset($record['gameId'])?$record['gameId']:null;
				$insertRecord['bet'] = isset($record['bet'])?$record['bet']:null;
				$insertRecord['win'] = isset($record['win'])?$record['win']:null;
				$insertRecord['jpBet'] = isset($record['jpBet'])?$record['jpBet']:null;
				$insertRecord['jpWin'] = isset($record['jpWin'])?$record['jpWin']:null;
				$insertRecord['currency'] = isset($record['currency'])?$record['currency']:null;
				$insertRecord['roundId'] = isset($record['roundId'])?$record['roundId']:null;
				$insertRecord['balanceStart'] = isset($record['balanceStart'])?$record['balanceStart']:null;
				$insertRecord['balanceEnd'] = isset($record['balanceEnd'])?$record['balanceEnd']:null;
				$insertRecord['platform'] = isset($record['platform'])?$record['platform']:null;
				$insertRecord['externalGameId'] = isset($record['externalGameId'])?$record['externalGameId']:null;
				$insertRecord['sideBet'] = isset($record['sideBet'])?$record['sideBet']:null;
				$insertRecord['jackpotDetails'] = isset($record['jackpotDetails'])?json_encode($record['jackpotDetails']):null;
				$insertRecord['external_uniqueid'] = isset($record['id']) ? $record['id'] : NULL;
				$insertRecord['response_result_id'] = $responseResultId;
				$insertRecord['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$insertRecord['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');

				//insert data to rtg gamelogs table database
				$this->CI->rtg_master_game_logs->insertGameLogs($insertRecord);
				$dataCount++;
			}

			$result['dataCount'] = $dataCount;
		}

		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'rtg_master_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->rtg_master_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;
		if (!empty($result)) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $row) {
				$cnt++;

				$game_description_id = $row->game_description_id;
				$game_type_id = $row->game_type_id;

				if(empty($row->game_type_id)&&empty($row->game_description_id)){
					list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
				}
				$bet_amount = $row->bet_amount;
				$win_amount = $row->result_amount;
				$result_amount = (float)$win_amount - (float)$bet_amount;
				
				$extra = array('table' => $row->round_id);
				
				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$row->gameid,
					$row->originalGameTypeName,
					$row->originalGameName,
					$row->player_id,
					$row->gameUsername,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$row->after_balance, # after_balance
					0, # has_both_side
					$row->external_uniqueid,
					$row->gamestartdate, //start
					$row->gamestartdate, //end
					$row->response_result_id,
					Game_logs::FLAG_GAME,
                    $extra
				);

			}
		}

		$this->CI->utils->debug_log('RTG MASTER PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row->gameid;
        $extra = array('game_code' => $external_game_id,'game_name' => $row->originalGameName);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function login($playerName, $password = null, $extra = null) {
    	return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
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

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/