<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_ggpoker_ew extends Abstract_game_api {

	const METHOD_POST = 1;
	const METHOD_GET = 2;
	const ERROR_CODE = array(
		//A1.2 Error Codes
		"INTERNAL_ERROR",
		"INVALID_REQUEST",
		"BRAND_NOT_FOUND",
		"ACCOUNT_NOT_FOUND",
		"USER_ALREADY_OFFLINE",
		//A.2.1 Error Code
		"TRANSACTION_NOT_FOUND",
		"INSUFFICIENT_BALANCE",
		"TRANSACTION_ALREADY_EXISTS",
		"ACCOUNT_UNAVAILABLE",
		"INVALID_CURRENCY_AMOUNT",
		"ACCESS_DENIED"
	);
	const ORIGINAL_LOGS_TABLE_NAME = 'ggpoker_ew_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['userId', 'ggr', 'rakeOrFee', 'profitAndLoss', 'profitAndLossPoker', 'profitAndLossPokerAofJackpot','profitAndLossPokerBigHandJackpot','profitAndLossPokerFlushJackpot','profitAndLossSideGame','winlossPoker','bet'];
	const MD5_FLOAT_AMOUNT_FIELDS=['ggr', 'rakeOrFee', 'profitAndLoss', 'profitAndLossPoker', 'profitAndLossPokerAofJackpot','profitAndLossPokerBigHandJackpot','profitAndLossPokerFlushJackpot','profitAndLossSideGame','winlossPoker','bet'];
	const MD5_FIELDS_FOR_MERGE =['convertedProfitAndLoss','convertedRakeOrFee','convertedWinlossPoker','convertedBet'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = ['convertedProfitAndLoss','convertedRakeOrFee','convertedWinlossPoker','convertedBet'];
	const GG_REQUEST_TYPE = array(
		"checkAccessToken",
		"getAccessToken",
		"getUrlToken"
	);

	const GG_REQUEST_TYPE_V2 = array(
		"login",
		"validate",
		"access"
	);
	const ACCESS_DENIED = 1;
	const INVALID_REQUEST = 2;
	const BRAND_NOT_FOUND = 3;
	const INVALID_CODE = 4;
	const INVALID_TOKEN = 5;
	const ACCOUNT_NOT_FOUND = 6;
	const INVALID_USERNAME = 7;
	const INVALID_PASSWORD = 8;
	const ACCOUNT_SUSPENDED = 9;

	public function __construct() {
		parent::__construct();
		$this->CI->load->model('daily_currency');
		$this->apiUrl = $this->getSystemInfo('url');
		$this->brandId = $this->getSystemInfo('brandId');//Name of brand
		$this->authorizationKey = $this->getSystemInfo('key');
		$this->currency_rate_cny_usd = $this->getSystemInfo('currency_rate_cny_usd');
		$this->is_fix_rate_cny_usd = $this->getSystemInfo('is_fix_rate_cny_usd',false);//check if use fix rate.
		$this->embeddableWebPage = $this->getSystemInfo('embeddableWebPage','https://leaflet-stage.good-game-network.com/leaflet');//staging sample
		$this->isBrandCurrency = $this->getSystemInfo('isBrandCurrency',true);
		$this->disable_authorization_checking = $this->getSystemInfo('disable_authorization_checking',false);//use for staging only
		$this->web_version_link = $this->getSystemInfo('web_version_link',false);//used for instant play using web client
	}

	public function getPlatformCode() {
		return GGPOKER_GAME_API;
	}

	public function getHttpHeaders($params){
		return array(
				"Content-Type" => 'application/json',
				"Accept" => 'application/json',
				"Authorization" => $this->authorizationKey
			);
	}


	public function generateUrl($apiName, $params) {
		$url = $params['url'];
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		$method = $params["method"];
		unset($params["url"]); //unset action not need on params
		unset($params["method"]);
		if($method == self::METHOD_POST){
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
	  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	  		//curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
		}
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		return $errCode || intval($statusCode, 10) >= 503;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = array_key_exists("code",$resultArr)&&(in_array($resultArr['code'], self::ERROR_CODE)) ? false : true;
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('GGPOKER got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
            $success = false;
        }
        return $success;
	}

	public function callback($request, $authorizationKey, $login = false){
		$this->CI->utils->debug_log('callback of ggpoker ew', $request, $authorizationKey, $login);
		if($authorizationKey == $this->authorizationKey || $this->disable_authorization_checking || $login){
		// if($authorizationKey != $this->authorizationKey){
			if($login){
				if(isset($login['brandId']) && $login['brandId'] == $this->brandId){
					if(isset($login['redirectUri'])){
						return $data = array(
							"login"	=> true,
							"success" => true,
							"redirectUri" => $login['redirectUri']
						);
					}
					return $this->getErrorCode();
				}
				return $this->getErrorCode(self::BRAND_NOT_FOUND);
			} else {
				if(empty($request)){
					return $this->getErrorCode();
				}
				if(isset($request['requestType']) && in_array($request['requestType'], self::GG_REQUEST_TYPE)){
					if(isset($request['brandId']) && $request['brandId'] == $this->brandId){
						$method = $request['requestType'];
						return $this->$method($request);
					}
					return $this->getErrorCode(self::BRAND_NOT_FOUND);
				}
				if(isset($request['requestType']) && in_array($request['requestType'], self::GG_REQUEST_TYPE_V2)){
					if(isset($request['brandId']) && $request['brandId'] == $this->brandId){
						if($request['requestType'] == "login"){
							$playerName = isset($request['username']) ? $request['username'] : null;
							$gamePassword = isset($request['password']) ? $request['password'] : null;
							$playerId = $this->getPlayerIdInPlayer($playerName);
							if(!empty($playerId)){
								$password = $this->getPasswordFromPlayer($playerName);
								if($password == $gamePassword){
									return $this->login($playerName);
								}
								return $this->getErrorCode(self::INVALID_PASSWORD); 
							}
							return $this->getErrorCode(self::INVALID_USERNAME,$playerName); 
						} else {
							return $this->$request['requestType']($request);	
						}
					}
					return $this->getErrorCode(self::BRAND_NOT_FOUND);
				}
				return $this->getErrorCode(self::INVALID_REQUEST);
			}

		} else {
			return $this->getErrorCode(self::ACCESS_DENIED);
		}

	}

	public function getErrorCode($errorCode = null, $user = null){
		$status = 400;
		switch ($errorCode) {
		    case 1:
		    	$status = 403;
				$error =  array(
					"code" 		=> "ACCESS_DENIED",
					"message" 	=> "Invalid authorization key."
				);
		        break;
		    case 2:
		        $error = array(
					"code" 		=> "INVALID_REQUEST",
					"message" 	=> "Invalid request type."
				);
		        break;
		    case 3:
		        $error = array(
					"code" 		=> "BRAND_NOT_FOUND",
					"message" 	=> "Invalid brand id."
				);
		        break;
		    case 4:
		        $error = array(
					"code" 		=> "INVALID_CODE",
					"message" 	=> "Invalid code."
				);
		        break;
		    case 5:
		        $error = array(
					"code" 		=> "INVALID_TOKEN",
					"message" 	=> "Access token has expired."
				);
		        break;
		    case 6:
		        $error = array(
					"code" 		=> "ACCOUNT_NOT_FOUND",
					"message" 	=> "Account {".$user."} not found."
				);
		        break;
		    case 7:
		        $error = array(
					"code" 		=> "INVALID_USERNAME",
					"message" 	=> "Username {".$user."} not found."
				);
		        break;
		    case 8:
		        $error = array(
					"code" 		=> "INVALID_PASSWORD",
					"message" 	=> "Password is invalid."
				);
		        break;
		    case 9:
		        $error = array(
					"code" 		=> "ACCOUNT_SUSPENDED",
					"message" 	=> "Account has been suspended."
				);
		        break;
		    default:
				$error =  array(
					"code" 		=> "INTERNAL_ERROR",
					"message" 	=> "Internal error."
				);
		}
		http_response_code($status);
		return $error;
	}

	private function validate($request){
		$this->CI->load->model(array('common_token'));
		if(isset($request['token'])){
			$token = $request['token'];
			$playerId = $this->getPlayerIdByToken($token);//check token if available
			if(!empty($playerId)){
				$gameUsername = $this->getGameUsernameByPlayerId($playerId);
				//disable token after used
				$this->CI->common_token->disableToken($token);
				return $data = array(
					"userId"	=> $gameUsername
				);
			}
		}
		return $this->getErrorCode(self::INVALID_TOKEN);
	}

	private function access($request){
		return $this->getUrlToken($request);
	}

	public function checkAccessToken($request){
		$this->CI->load->model(array('common_token'));
		if(isset($request['accessToken'])){
			$token = $request['accessToken'];
			$playerId = $this->getPlayerIdByToken($token);//check token if available
			if(!empty($playerId)){
				$gameUsername = $this->getGameUsernameByPlayerId($playerId);//get game username
				$token = $this->getPlayerToken($playerId);//recheck token and get
				return $data = array(
					"userId"	=> $gameUsername
				);
			}
		}
		return $this->getErrorCode(self::INVALID_TOKEN);
	}

	public function getAccessToken($request){
		$this->CI->load->model(array('common_token'));
		if(isset($request['code'])){
			$externalAccountId = $request['code'];
			$playerId = $this->getPlayerIdByExternalAccountId($externalAccountId);
			if(!empty($playerId)){
				$gameUsername = $this->getGameUsernameByPlayerId($playerId);//get game username
				$token = $this->getPlayerToken($playerId);//recheck token and get
				return $data = array(
					"userId"	=> $gameUsername,
					"accessToken" => $token,
				);
			}
		}
		return $this->getErrorCode(self::INVALID_CODE);
	}

	public function getUrlToken($request){//optional
		$playerName = isset($request['userId']) ? $request['userId'] : null;
		$playerId = $this->getPlayerIdInPlayer($playerName);
		if(!empty($playerId)){
			$this->CI->load->model(array('common_token'));
			//need to create token if no token avaialable, for launching client website from poker app(example. cashier link)
			$token = $this->CI->common_token->getPlayerToken($playerId);
			// $token = $this->CI->common_token->getValidPlayerToken($playerId);
			if (!empty($token)) {
				return $data = array(
					"token"	=> $token,
				);
			}
			return $this->getErrorCode(self::INVALID_TOKEN);
		} 
		return $this->getErrorCode(self::ACCOUNT_NOT_FOUND, $playerName);

	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId
        );

		$requestId = substr(date('YmdHis'), 2) . random_string('alnum', 10);
		$amount = floatval(0.01);
		$url = $this->apiUrl . '/brands/' . $this->brandId . '/users/' . $gameUsername . '/credit';
        $params = array(
			"amount" 		=> $amount,
			"requestId" 	=> $requestId,
			"url" 			=> $url,
			"method"		=> self::METHOD_POST
        );
        $this->utils->debug_log("CreatePlayer params ============================>", $params);
	    return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		//update register
		if ($success) {
			$externalAccountId = md5($playerName.$playerId);
			$this->updateExternalAccountIdForPlayer($playerId, $externalAccountId);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$this->withdrawFromGame($playerName, 0.01, null,true);
		}

		return array($success, $resultJsonArr);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );
        $url = $this->apiUrl . '/brands/' . $this->brandId . '/users/' . $gameUsername . '/balance';
        $params = array(
			"url" 			=> $url,
			"method"		=> self::METHOD_GET
        );

        $this->utils->debug_log("isPlayerExist params ============================>", $params);
	    return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {
		$playerId         = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr    = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForIsPlayerExist ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		if($success){
			$result['exists'] = true;
            //update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		elseif($resultJsonArr['code'] == "ACCOUNT_NOT_FOUND"){
			$success = true;
			$result['exists'] = false;
		}
		else{
			$success = false;
			$result['exists'] = false;
		}
		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );
        $url = $this->apiUrl . '/brands/' . $this->brandId . '/users/' . $gameUsername . '/balance';
        $params = array(
			"url" 			=> $url,
			"method"		=> self::METHOD_GET
        );
        $this->utils->debug_log("queryPlayerBalance params ============================>", $params);
	    return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerId         = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr    = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForQueryPlayerBalance ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result =[];
		if($success) {
			// $amount = $resultJsonArr['balance'];
			$amount = $this->gameAmountToDb($resultJsonArr['balance']);
        	$result['balance'] = @floatval($amount);
        	if ($playerId) {
				$this->CI->utils->debug_log('GGPOKER GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $amount);
			} else {
				$this->CI->utils->debug_log('GGPOKER GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
        }

		return array($success, $result);
	}
	# Get current rate for CNY to USD
	public function getCurrencyRateCnyUsd(){
		$result = $this->CI->daily_currency->getCurrentCurrencyRate($this->utils->getTodayForMysql(),'CNY','USD');
		if(!empty($result) && (!$this->is_fix_rate_cny_usd)){
			return $result->rate;
		}
		return $this->currency_rate_cny_usd;
	}

	public function gameAmountToDb($amount) {
	    $conversion_rate = $this->getCurrencyRateCnyUsd();
	    $converted_amount = (float) ($amount/$conversion_rate);
	    return round($converted_amount,2);
	}

	public function dBtoGameAmount($amount) {
	    $conversion_rate = $this->getCurrencyRateCnyUsd();
	    $converted_amount = (float) ($amount*$conversion_rate);
	    return round($converted_amount,2);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$requestId = substr(date('YmdHis'), 2) . random_string('alnum', 10);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId,
            'external_transaction_id' => $requestId
        );

		$amount = $this->dBtoGameAmount($amount);
		$url = $this->apiUrl . '/brands/' . $this->brandId . '/users/' . $gameUsername . '/credit';
        $params = array(
			"amount" 		=> @floatval($amount),
			"requestId" 	=> $requestId,
			"url" 			=> $url,
			"method"		=> self::METHOD_POST
        );

        $this->utils->debug_log("depositToGame params ============================>", $params);
	    return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
   		$playerId         = $this->getVariableFromContext($params, 'playerId');
   		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
   		$playerName = $this->getVariableFromContext($params, 'playerName');
   		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
   		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr    = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );


        if ($success) {
        	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        } else {
        	$result['reason_id'] = $this->getTransferErrorReasonCode($resultJsonArr);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

   		return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$requestId = substr(date('YmdHis'), 2) . random_string('alnum', 10);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId,
            'external_transaction_id' => $requestId
        );

		$amount = $notRecordTransaction ? floatval($amount) : $this->dBtoGameAmount($amount);
		$url = $this->apiUrl . '/brands/' . $this->brandId . '/users/' . $gameUsername . '/debit';
        $params = array(
			"amount" 		=> @floatval($amount),
			"requestId" 	=> $requestId,
			"url" 			=> $url,
			"method"		=> self::METHOD_POST
        );

        $this->utils->debug_log("withdrawFromGame params ============================>", $params);
	    return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
      	$playerId         = $this->getVariableFromContext($params, 'playerId');
   		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
   		$playerName = $this->getVariableFromContext($params, 'playerName');
   		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
   		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr    = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );


        if ($success) {
        	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        } else {
        	$result['reason_id'] = $this->getTransferErrorReasonCode($resultJsonArr);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

   		return array($success, $result);
    }

    public function getTransferErrorReasonCode($params){
    	$result = $params['code'];
    	switch ($result) {
		    case "ACCOUNT_NOT_FOUND":
		        return self::REASON_NOT_FOUND_PLAYER;
		        break;
		    case "INSUFFICIENT_BALANCE":
		        return self::REASON_NO_ENOUGH_BALANCE;
		        break;
		    case "INVALID_REQUEST":
		    case "BRAND_NOT_FOUND":
		    case "DOWNLINE_NOT_FOUND":
		        return self::REASON_INVALID_KEY;
		        break;
		    case "ACCOUNT_UNAVAILABLE":
		        return self::REASON_GAME_ACCOUNT_LOCKED;
		        break;
		    case "TRANSACTION_NOT_FOUND":
		        return self::REASON_INVALID_TRANSACTION_ID;
		        break;
		    case "TRANSACTION_ALREADY_EXISTS":
		        return self::REASON_DUPLICATE_TRANSFER;
		        break;
		    default:
		        return self::REASON_UNKNOWN ;
		}
    }

	public function queryForwardGame($playerName, $extra = null) {
		//sample url https://leaflet-stage.good-game-network.com/leaflet/laba360/main/en
		$language = $this->getLauncherLanguage($extra['language']);
		$embeddableWebPage = $this->embeddableWebPage."/".$this->brandId . "/main/" .$language;
		$token = $this->getPlayerTokenByUsername($playerName);
		if(isset($extra['landing_page']) && $extra['landing_page'] == "web"){
			if($this->web_version_link){
				$embeddableWebPage = $this->web_version_link;	
			}
		}
		$embeddableWebPage.= "?token=" .$token;
		$data = [
            'url' => $embeddableWebPage,
            'success' => true
        ];
        $this->utils->debug_log(' GGPOKER EW embeddableWebPage - =================================================> ' . $embeddableWebPage);
        return $data;
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate = $startDate->format('Y-m-d 00:00:00');
		$endDate   = $endDate->format('Y-m-d 23:59:59');

		$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+24 hours', function($startDate, $endDate) {
			$date = $startDate->format('Y-m-d');

			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForSyncGameRecords',
	            'date' => $date,
	        );

	        $url = $this->apiUrl . '/brands/' . $this->brandId . '/statistics/detailed/' . $date ;
	        $data = array();
	        if($this->isBrandCurrency){
	        	$data['isBrandCurrency'] = 'true';
	        }

	        if(!empty($data)){
	        	$url .= "?". http_build_query($data);
	        }
	        $params = array(
				"url" 			=> $url,
				"method"		=> self::METHOD_GET
	        );
			$this->utils->debug_log("syncOriginalGameLogs params ============================>", $params);
		    return $this->callApi(self::API_syncGameRecords, $params, $context);
		});
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$date = $this->getVariableFromContext($params, 'date');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr    = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, null);
		$result = array(
			'data_count'=> 0
		);
		if($success){
			$gameRecords = $resultJsonArr;
			$this->processGameRecords($gameRecords, $date, $responseResultId);
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

			if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
		}
		return array($success, $result);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function processGameRecords(&$gameRecords, $date, $responseResultId) {
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$data['userId'] = isset($record['userId']) ? $record['userId'] : null;
				$data['nickname'] = isset($record['nickname']) ? $record['nickname'] : null;
				$data['rakeOrFee'] = isset($record['rakeOrFee']) ? $record['rakeOrFee'] : null;
				$data['profitAndLoss'] = isset($record['profitAndLoss']) ? $record['profitAndLoss'] : null;
				$data['profitAndLossPoker'] = isset($record['profitAndLossPoker']) ? $record['profitAndLossPoker'] : null;
				$data['profitAndLossPokerAofJackpot'] = isset($record['profitAndLossPokerAofJackpot']) ? $record['profitAndLossPokerAofJackpot'] : null;
				$data['profitAndLossPokerBigHandJackpot'] = isset($record['profitAndLossPokerBigHandJackpot']) ? $record['profitAndLossPokerBigHandJackpot'] : null;
				$data['profitAndLossPokerFlushJackpot'] = isset($record['profitAndLossPokerFlushJackpot']) ? $record['profitAndLossPokerFlushJackpot'] : null;
				$data['profitAndLossPokerCashback'] = isset($record['profitAndLossPokerCashback']) ? $record['profitAndLossPokerCashback'] : null;
				$data['profitAndLossSideGame'] = isset($record['profitAndLossSideGame']) ? $record['profitAndLossSideGame'] : null;
				$data['fishBuffetReward'] = isset($record['fishBuffetReward']) ? $record['fishBuffetReward'] : null;
				$data['networkGiveaway'] = isset($record['networkGiveaway']) ? $record['networkGiveaway'] : null;
				$data['brandPromotion'] = isset($record['brandPromotion']) ? $record['brandPromotion'] : null;
				$data['tournamentOverlay'] = isset($record['tournamentOverlay']) ? $record['tournamentOverlay'] : null;
				$data['ggr'] = isset($record['ggr']) ? $record['ggr'] : null;
				$data['winlossPoker'] = isset($record['winlossPoker']) ? $record['winlossPoker'] : null;
				$data['sessionCount'] = isset($record['sessionCount']) ? $record['sessionCount'] : null;
				$data['rakedGameCount'] = isset($record['rakedGameCount']) ? $record['rakedGameCount'] : null;
				$data['nonRakedGameCount'] = isset($record['nonRakedGameCount']) ? $record['nonRakedGameCount'] : null;
				$data['buyInCash'] = isset($record['buyInCash']) ? $record['buyInCash'] : null;
				$data['buyInGtd'] = isset($record['buyInGtd']) ? $record['buyInGtd'] : null;
				$data['buyInTicket'] = isset($record['buyInTicket']) ? $record['buyInTicket'] : null;
				$data['bet'] = isset($record['bet']) ? $record['bet'] : null;

				$data['convertedGgr'] = isset($record['ggr']) ? $this->gameAmountToDb($record['ggr']) : NULL;
				$data['convertedRakeOrFee'] = isset($record['rakeOrFee']) ? $this->gameAmountToDb($record['rakeOrFee']) : NULL;
				$data['convertedProfitAndLoss'] = isset($record['profitAndLoss']) ? $this->gameAmountToDb($record['profitAndLoss']) : NULL;
				$data['convertedProfitAndLossPoker'] = isset($record['profitAndLossPoker']) ? $this->gameAmountToDb($record['profitAndLossPoker']) : NULL;
				$data['convertedProfitAndLossPokerAofJackpot'] = isset($record['profitAndLossPokerAofJackpot']) ? $this->gameAmountToDb($record['profitAndLossPokerAofJackpot']) : NULL;
				$data['convertedProfitAndLossPokerBigHandJackpot'] = isset($record['profitAndLossPokerBigHandJackpot']) ? $this->gameAmountToDb($record['profitAndLossPokerBigHandJackpot']) : NULL;
				$data['convertedProfitAndLossPokerFlushJackpot'] = isset($record['profitAndLossPokerFlushJackpot']) ? $this->gameAmountToDb($record['profitAndLossPokerFlushJackpot']) : NULL;
				$data['convertedProfitAndLossSideGame'] = isset($record['profitAndLossSideGame']) ? $this->gameAmountToDb($record['profitAndLossSideGame']) : NULL;
				$data['convertedFishBuffetReward'] = isset($record['fishBuffetReward']) ? $this->gameAmountToDb($record['fishBuffetReward']) : NULL;
				$data['convertedNetworkGiveaway'] = isset($record['networkGiveaway']) ? $this->gameAmountToDb($record['networkGiveaway']) : NULL;
				$data['convertedBrandPromotion'] = isset($record['brandPromotion']) ? $this->gameAmountToDb($record['brandPromotion']) : NULL;
				$data['convertedTournamentOverlay'] = isset($record['tournamentOverlay']) ? $this->gameAmountToDb($record['tournamentOverlay']) : NULL;
				$data['convertedBuyInCash'] = isset($record['buyInCash']) ? $this->gameAmountToDb($record['buyInCash']) : NULL;
				$data['convertedBuyInGtd'] = isset($record['buyInGtd']) ? $this->gameAmountToDb($record['buyInGtd']) : NULL;
				$data['convertedBuyInTicket'] = isset($record['buyInTicket']) ? $this->gameAmountToDb($record['buyInTicket']) : NULL;
				$data['convertedProfitAndLossPokerCashback'] = isset($record['convertedProfitAndLossPokerCashback']) ? $this->gameAmountToDb($record['convertedProfitAndLossPokerCashback']) : NULL;
				$data['convertedWinlossPoker'] = isset($record['winlossPoker']) ? $this->gameAmountToDb($record['winlossPoker']) : NULL;
				$data['convertedBet'] = isset($record['bet']) ? $this->gameAmountToDb($record['bet']) : NULL;

				//default data
				$data['external_uniqueid'] = $record['userId'].'-'.$date;
				$data['response_result_id'] = $responseResultId;
				$data['dateTime'] = $date;
				$gameRecords[$index] = $data;
				unset($data);
			}
		}
	}

	public function syncMergeToGameLogs($token) {
		$this->unknownGame = $this->getUnknownGame($this->getPlatformCode());
        return $this->commonSyncMergeToGameLogs($token,
	        $this,
	        [$this, 'queryOriginalGameLogs'],
	        [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
	        [$this, 'preprocessOriginalRowForGameLogs'],
	        false
	    );
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		$sqlTime='`ggew`.`dateTime` >= ?
          AND `ggew`.`dateTime` <= ?';

        $sql = <<<EOD
SELECT ggew.id as id,
ggew.userId as game_name,
ggew.gameType,
ggew.external_uniqueid,
ggew.dateTime AS game_date,
ggew.response_result_id,
ggew.rakedGameCount,
ggew.nonRakedGameCount,
ggew.profitAndLoss,
ggew.convertedGgr,
ggew.convertedRakeOrFee,
ggew.convertedProfitAndLoss,
ggew.convertedProfitAndLossPoker,
ggew.convertedProfitAndLossPokerAofJackpot,
ggew.convertedProfitAndLossPokerBigHandJackpot,
ggew.convertedProfitAndLossPokerFlushJackpot,
ggew.convertedProfitAndLossSideGame,
ggew.convertedFishBuffetReward,
ggew.convertedNetworkGiveaway,
ggew.convertedBrandPromotion,
ggew.convertedTournamentOverlay,
ggew.convertedBuyInCash,
ggew.convertedBuyInGtd,
ggew.convertedBuyInTicket,
ggew.convertedWinlossPoker,
ggew.md5_sum,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id,
gd.external_game_id

FROM ggpoker_ew_game_logs as ggew

left JOIN game_description as gd ON gd.external_game_id = "GG Poker" and gd.game_platform_id=?
JOIN game_provider_auth ON ggew.userId = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}

EOD;
	$dateFrom = new DateTime($dateFrom);
	$dateTo = new DateTime($dateTo);
	$dateFrom = $dateFrom->format('Y-m-d 00:00:00');
	$dateTo   = $dateTo->format('Y-m-d 23:59:59');
	$params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $bet_amount = 0;
        #Poker Winloss 
        $result_amount = $row['convertedWinlossPoker'];
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['external_game_id'],
                'game_type' => null,
                'game' => $row['external_game_id']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['game_date'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['game_date'],
                'updated_at' => $row['game_date']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['id'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => [
            	'rent' => $row['convertedRakeOrFee']
            ],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
    	if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$this->unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
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


	public function login($playerName, $password = null) {
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$this->CI->load->model(array('common_token'));
		$token = $this->CI->common_token->getPlayerToken($playerId);
		// $token = $this->CI->common_token->getValidPlayerToken($playerId);
		if (!empty($token)) {
			return $data = array(
				"token"	=> $token,
			);
		}
		return $this->getErrorCode(self::INVALID_TOKEN);
	}

	public function processResultForgetVendorId($params) {
		return $this->returnUnimplemented();
	}


	public function queryTransaction($transactionId, $extra) {
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId
        );
        $url = $this->apiUrl . '/brands/' . $this->brandId . '/transactions/' . $transactionId ;
        $params = array(
			"url" 			=> $url,
			"method"		=> self::METHOD_GET
        );
        $this->utils->debug_log("queryTransaction params ============================>", $params);
	    return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultJsonArr = $this->getResultJsonFromParams($params);

		$this->CI->utils->debug_log('processResultForQueryTransaction ==========================>', $resultJsonArr);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, null);
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['reason_id'] = $this->getTransferErrorReasonCode($resultJsonArr);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}
		return array($success, $result);
	}

	public function getLauncherLanguage($currentLang) {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vi';
                break;
            case "en-us":
                $language = 'en';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
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