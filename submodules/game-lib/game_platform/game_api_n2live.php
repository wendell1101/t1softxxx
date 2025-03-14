<?php
/**
 * N2live live casino game integration
 * OGP-13138
 *
 * @author 	Jerbey Capoquian
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_n2live extends Abstract_game_api {

    const API_queryDepositTransaction = "queryDepositTransaction";

	const URI_MAP = array(
		self::API_depositToGame 	 => '/transaction/PlayerDeposit',
		self::API_queryDepositTransaction 	 => '/transaction/PlayerDeposit',
		self::API_withdrawFromGame 	 => '/transaction/PlayerWithdrawal',
		self::API_syncGameRecords	 => '/Trading/GameInfo',
		self::API_queryPlayerBalance	 => '/transaction/CheckClient',
		self::API_isPlayerExist	 => '/transaction/CheckClient'
	);
	const ORIGINAL_LOGS_TABLE_NAME = 'n2live_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['startdate','enddate','status','code','bet_amount','payout_amount','valid_turnover','handle','betdetail'];
	const MD5_FLOAT_AMOUNT_FIELDS=['bet_amount','payout_amount','valid_turnover','handle'];
	const MD5_FIELDS_FOR_MERGE = ['bet_amount','payout_amount','real_bet_amount','handle','result_amount','bet_details','status'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = ['bet_amount','payout_amount','real_bet_amount','handle','result_amount'];

	const PLAYER_NOT_EXIST = 203;
	const ACTION_CHECK_CLIENT = "ccheckclient";
	const ACTION_DEPOSIT = "cdeposit";
	const ACTION_DEPOSIT_CONFIRM = "cdeposit-confirm";
	const ACTION_WITHDRAW = "cwithdrawal";
	const ACTION_GAMEINFO = "gameinfo";
	const STATUS_SUCCESS = 0;
	const ERR_INVALID_REQ = 001;
	const ERR_INVALID_ACCOUNT_ID = 101;
	const ERR_ALREADY_LOGIN = 102;
	const ERR_ACCOUNT_SUSPENDED = 104;
	const ERR_SIGN_FAILURE = 614;
	const ONLINE = "1";
	const OFFLINE = "0";

	const TTC_ACCOUNT = ["wrxtest01","wrxtest02","wrxtest03","wrxtest04","wrxtest05"];//userid using testing currency on winroxy stagin
	const TESTING_CURRENCY = 1111;


	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url','https://stgmerchanttalk.azuritebox.com');//staging api url for winroxy
		$this->currencyId = $this->getSystemInfo('currencyId',3601);//staging idr currency id for winroxy
		$this->vendorId = $this->getSystemInfo('vendorId',360);//staging vendor id for winroxy
		$this->merchantPassCode = $this->getSystemInfo('merchantPassCode','E7C0DE50A2FE7BAB5147FE13B49DB701');//staging merchant pass code for winroxy
		$this->mobileGameUrl = $this->getSystemInfo('mobileGameUrl','https://n2stgm.staging.wir99.com/SingleLogin');
		$this->desktopGameurl = $this->getSystemInfo('desktopGameurl','https://n2stgd.staging.wir99.com/SingleLogin');
		$this->merchantCode = $this->getSystemInfo('merchantCode','WRX');//staging merchant  code for winroxy
		$this->cashierPage = $this->getSystemInfo("cashierPage");
		$this->ttcAccount = $this->getSystemInfo("ttcAccount",self::TTC_ACCOUNT);
		$this->sync_end_minute_modify = $this->getSystemInfo("sync_end_minute_modify",'-5 minutes');
	}

	public function getPlatformCode() {
		return N2LIVE_API;
	}

	public function getHttpHeaders($params) {
		return ['Content-Type' => 'text/xml'];
	}

	public function generateUrl($apiName, $params) {
		$url = $this->api_url.self::URI_MAP[$apiName];
		return $url;
	}

	public function callback($result = null, $method = null) {
		$data = array();
		if(!empty($result) && ($method == "single_login_authenticate" || $method == "login_authenticate") || $method == "auto_cashier_get_ticket"){
			$result = $this->getResultXmlWithAttributeFromParams($result);
			//default value
			$status = self::STATUS_SUCCESS;
			$errorDesc = null;
			$uuid = null;
			$clientip = null;

			//default request parameter
			$action = $result['request']['@attributes']['action'];
			$id = $result['request']['element']['@attributes']['id'];
			$userid = $result['request']['element']['properties'][0];
			

			//check if userid or username exist
			$playerUsername = $this->getPlayerUsernameByGameUsername($userid);
			$isDemo = $this->isGameAccountDemoAccount($userid);
			if($isDemo['is_demo_flag'] || in_array($playerUsername, $this->ttcAccount)){
				$this->currencyId = self::TESTING_CURRENCY;
			}

			if(!$playerUsername){
				$status = self::ERR_INVALID_ACCOUNT_ID;
				$errorDesc = "Invalid user id.";
			} else{
				if($this->isBlocked($playerUsername)){
					$status = self::ERR_ACCOUNT_SUSPENDED;
					$errorDesc = "Account suspended.";
				}
			}


			if($method == "auto_cashier_get_ticket"){
				$date  = $result['request']['element']['properties'][1];
				$sign  = $result['request']['element']['properties'][2];
				$hash = md5('test00420100101E7C0DE50A2FE7BAB5147FE13B49DB701');
				$text= strtolower($userid).$date.$this->merchantPassCode;
				$hash = md5($text);
				if($hash != $sign){
					$status = self::ERR_SIGN_FAILURE;
					$errorDesc = "Invalid sign.";
				}

				$token = $this->getPlayerTokenByUsername($playerUsername);
				$data = array(
					'status' => ($status == self::STATUS_SUCCESS) ? 'success' : 'fail',
					'action' => $action,
					'id' => $id,
					'properties' => array(
						'username' => $userid,
						'ticket' => $token,
						'status' => $status
					)
				);
			}elseif($method == "single_login_authenticate"){
				$uuid = $result['request']['element']['properties'][1];
				$clientip= $result['request']['element']['properties'][2];

				if(!$this->getPlayerIdByToken($uuid)){
					$status = self::ERR_INVALID_REQ;
					$errorDesc = "Token or session expired.";
				}

				$data = array(
					'status' => ($status == self::STATUS_SUCCESS) ? 'success' : 'fail',
					'action' => $action,
					'id' => $id,
					'properties' => array(
						'userid' => $userid,
						'username' => $userid,
						'uuid' => $uuid,
						'vendorid' => $this->vendorId,
						'merchantpasscode' => $this->merchantPassCode,
						'clientip' => $clientip,
						'currencyid' => $this->currencyId,
						'acode' => null,
						'errdesc' => $errorDesc,
						'status' => $status
					)
				);
			}elseif ($method == "login_authenticate") {
				$requestPassword = $result['request']['element']['properties'][1];
				if(!empty($playerUsername)){
					$password = $this->getPasswordFromPlayer($playerUsername);
					if($password != $requestPassword){
						$status = self::ERR_INVALID_REQ;
						$errorDesc = "Invalid password.";
					}
				}

				$data = array(
					'status' => ($status == self::STATUS_SUCCESS) ? 'success' : 'fail',
					'action' => $action,
					'id' => $id,
					'properties' => array(
						'userid' => $userid,
						'username' => $userid,
						'vendorid' => $this->vendorId,
						'merchantpasscode' => $this->merchantPassCode,
						'currencyid' => $this->currencyId,
						'acode' => null,
						'errdesc' => $errorDesc,
						'status' => $status
					)
				);
			}
		} else if ($method == "login_cashier"){
			if(!empty($result)){
				$cashierPage = $this->cashierPage ? $this->cashierPage : $this->utils->getSystemUrl('player');;
				$playerUsername = $this->getPlayerUsernameByGameUsername($result['username']);
				$forwardResult = $this->forwardToWhiteDomain($playerUsername, $cashierPage);
				$success = false;
				$forward_url = $cashierPage ;
				if($this->getPlayerIdByToken($result['ticket'])){
					if($forwardResult['success']){
						$success = true;
						$forward_url = $forwardResult['forward_url']."&game_platform_id=".$this->getPlatformCode();
					} 
				}
				
				return array(
					"success" => $success,
					"forward_url" => $forward_url
				);
			}
		}
		$response = $this->generateXmlResponse($data);
		return $response;
	}

	public function generateXmlResponse($params){
		$sxe  = new SimpleXMLElement("<message/>");
		$status = $sxe->addChild('status',@$params['status']);
		$result = $sxe->addChild('result');
		$result->addAttribute('action', @$params['action']);
		$element = $result->addChild('element');
		$element->addAttribute('id', @$params['id']);
		
		if(!empty(@$params['properties'])){
			foreach ($params['properties'] as $key => $value) {
				$properties = $element->addChild('properties',$value);
				$properties->addAttribute('name', $key);
			}
		}

		return $sxe->asXML();
	}

	protected function customHttpCall($ch, $params) {
		$sxe  = new SimpleXMLElement("<request/>");
		$sxe->addAttribute('action', $params['action']);
		$element = $sxe->addChild('element');
		if($params['action'] != self::ACTION_GAMEINFO){
			$element->addAttribute('id', $params['id']);
		}
		
		if(!empty($params['properties'])){
			foreach ($params['properties'] as $key => $value) {
				$properties = $element->addChild('properties',$value);
				$properties->addAttribute('name', $key);
			}
		}

		$xml = $sxe->asXML();
		// if($params['action'] == self::ACTION_DEPOSIT_CONFIRM ){
		// 	echo "<pre>";
		// 	print_r($xml);exit();
		// }
		// echo "<pre>";
		// print_r($xml);
		$this->CI->utils->debug_log('-----------------------n2 live POST XML STRING ----------------------------',$xml);
		if($params['action'] == self::ACTION_GAMEINFO){
			curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/plain","Accept-Encoding: gzip"));
			curl_setopt($ch, CURLOPT_ENCODING,'gzip');
		}
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = ($resultArr['message']['status'] == 'success') ? true : false;
		$result = array();
		if(!$success){
			$this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('N2LIVE got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		// create player on game provider auth
		if(in_array($playerName, $this->ttcAccount)){
			$extra['is_demo_flag'] = true;
		}
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
		$success = false;
		$message = "Unable to create account for N2Live api";
		if($return){
			$success = true;
			$message = "Successfull create account for N2Live api";
		}
		
		return array("success" => $success, "message" => $message);   
	}

	

	



	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$isDemo = $this->isGameAccountDemoAccount($gameUsername);
		if($isDemo['is_demo_flag'] || in_array($playerName, $this->ttcAccount)){
			$this->currencyId = self::TESTING_CURRENCY;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

		$params = array(
			'action' => self::ACTION_CHECK_CLIENT,
			'id' => "C". $this->utils->getTimestampNow(),
			'properties' => array(
				'userid' => $gameUsername,
				'vendorid' => $this->vendorId,
				'merchantpasscode' => $this->merchantPassCode,
				'currencyid' => $this->currencyId
			)
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultXmlFromParams($params['resultText']);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		$result['isOnline'] = false;
		if($success){
			$result['exists'] = true;
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);	
		} else {
			$errorCode = $arrayResult['message']['result']['element']['properties'][0];//get error code
			if($errorCode == self::PLAYER_NOT_EXIST){
				$success = true;
				$result['exists'] = false;
			} else {
				$result['exists'] = null;
			}
		}
		return array($success,$result);
    }



	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$isDemo = $this->isGameAccountDemoAccount($gameUsername);
		if($isDemo['is_demo_flag'] || in_array($playerName, $this->ttcAccount)){
			$this->currencyId = self::TESTING_CURRENCY;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'action' => self::ACTION_CHECK_CLIENT,
			'id' => "C". $this->utils->getTimestampNow(),
			'properties' => array(
				'userid' => $gameUsername,
				'vendorid' => $this->vendorId,
				'merchantpasscode' => $this->merchantPassCode,
				'currencyid' => $this->currencyId
			)
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultXmlFromParams($params['resultText']);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		$result['isOnline'] = false;
		if($success){
			$result['exists'] = true;
			$result["balance"] = floatval(@$arrayResult['message']['result']['element']['properties'][1]);// get balance
			$result['isOnline'] = @$arrayResult['message']['result']['element']['properties'][3];
		} else {
			$errorCode = $arrayResult['message']['result']['element']['properties'][0];//get error code
			if($errorCode == self::PLAYER_NOT_EXIST){
				$success = true;
				$result['exists'] = false;
			} else {
				$result['exists'] = null;
			}
		}
		return array($success,$result);
	}

	function xmlToArray(SimpleXMLElement $parent)
	{
	    $array = array();

	    foreach ($parent as $name => $element) {
	    	// if($this->utils->isEnabledMDB()){
	     //    	($node = & $array[$name]) && $this->is_countable($node) && (1 === count($node) ? $node = array($node) : 1) && $node = & $node[];
	    	// } else {
	    		// ($node = & $array[$name]) && ( 1 === count($node) ? $node = array($node) : 1) && $node = & $node[];
	    		($node = & $array[$name]) && ( !is_array($node) ? $node = array($node) : 1) && $node = & $node[];
	    	// }

	        $node = $element->count() ? $this->xmlToArray($element) : trim($element);
	    }
	    $this->utils->debug_log(' N2 live  xmlToArray - =================================================> ' ,$array);
	    return $array;
	}

	function is_countable($c) {
    	return is_array($c) || $c instanceof Countable;
    }


	function getResultXmlFromParams($params){
		$result = str_replace('encoding="utf-16"',"",$params); 
		//disregard attribute of xml
		$xml   = simplexml_load_string($result);
		$array = $this->xmlToArray($xml);
		$array = array($xml->getName() => $array);
		$this->utils->debug_log(' N2 live  getResultXmlFromParams - =================================================> ' ,$array);
		return $array;
	}

	function getResultXmlWithAttributeFromParams($params){
		$result = str_replace('encoding="utf-16"',"",$params); 

		$xml   = simplexml_load_string($result);
		$array = json_decode(json_encode((array) $xml), true);
		$array = array($xml->getName() => $array);

		return $array;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$isDemo = $this->isGameAccountDemoAccount($gameUsername);
		if($isDemo['is_demo_flag'] || in_array($playerName, $this->ttcAccount)){
			$this->currencyId = self::TESTING_CURRENCY;
		}
		$refNo = $this->utils->getTimestampNow(). random_string('alnum', 20);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = $refNo;
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'external_transaction_id'=> $transfer_secure_id,
		);
		
		$params = array(
			'action' => self::ACTION_DEPOSIT,
			'id' => "D". $this->utils->getTimestampNow(),
			'properties' => array(
				'userid' => $gameUsername,
				'acode' => null,
				'vendorid' => $this->vendorId,
				'merchantpasscode' => $this->merchantPassCode,
				'currencyid' => $this->currencyId,
				'amount' => $amount,
				'refno' => $transfer_secure_id,
			)
		);

		return $this->callApi(self::API_depositToGame, $params, $context);     
	}

	public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$depositResult = $this->getResultXmlFromParams($params['resultText']);
		$success = $this->processResultBoolean($responseResultId, $depositResult, $playerName);
		$confirmResult = array("success" => false);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		if($success){
			$confirmResult = $this->queryDepositTransaction($playerName, $depositResult);
		}else{
			$result['reason_id']=self::REASON_UNKNOWN;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
		}
		
		$this->utils->debug_log('N2LIVE processResultForDepositToGame',$params,'depositResult',$depositResult,'confirmResult',$confirmResult);
		
		if(isset($confirmResult['success']) && $confirmResult['success']){
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
			$result['reason_id']=self::REASON_UNKNOWN;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($confirmResult['success'], $result);
    }

    public function queryDepositTransaction($playerName, $depositConfirmParams = null){
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryDepositTransaction',
			'playerName' => $playerName,
		);

		$paymentId = $depositConfirmParams['message']['result']['element']['properties'][0];
		$status = $depositConfirmParams['message']['result']['element']['properties'][1];
		$refNo = $depositConfirmParams['message']['result']['element']['properties'][2];
		$aCode = $depositConfirmParams['message']['result']['element']['properties'][3];
		$errDesc = $depositConfirmParams['message']['result']['element']['properties'][4];
		
		$params = array(
			'action' => self::ACTION_DEPOSIT_CONFIRM,
			'id' => "D". $this->utils->getTimestampNow(),
			'properties' => array(
				'acode' => $aCode,
				'status' => $status,
				'paymentid' => $paymentId,
				'vendorid' => $this->vendorId,
				'merchantpasscode' => $this->merchantPassCode,
				'errdesc' => $errDesc,
			)
		);

		return $this->callApi(self::API_queryDepositTransaction, $params, $context);  
    }

    public function processResultForQueryDepositTransaction($params){
    	$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$result = $this->getResultXmlFromParams($params['resultText']);
		$success = $this->processResultBoolean($responseResultId, $result, $playerName);
		$this->utils->debug_log('N2LIVE processResultForQueryDepositTransaction',$params,'result',$result);
		return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $isDemo = $this->isGameAccountDemoAccount($gameUsername);
		if($isDemo['is_demo_flag'] || in_array($playerName, $this->ttcAccount)){
			$this->currencyId = self::TESTING_CURRENCY;
		}
		$refNo = $this->utils->getTimestampNow(). random_string('alnum', 20);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = $refNo;
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'external_transaction_id'=> $transfer_secure_id,
		);
		
		$params = array(
			'action' => self::ACTION_WITHDRAW,
			'id' => "W". $this->utils->getTimestampNow(),
			'properties' => array(
				'userid' => $gameUsername,
				'vendorid' => $this->vendorId,
				'merchantpasscode' => $this->merchantPassCode,
				'currencyid' => $this->currencyId,
				'amount' => $amount,
				'refno' => $transfer_secure_id,
			)
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);   
    }

    public function processResultForWithdrawFromGame($params) {
       	$playerName = $this->getVariableFromContext($params, 'playerName');
       	$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$withdrawalResult = $this->getResultXmlFromParams($params['resultText']);
		$success = $this->processResultBoolean($responseResultId, $withdrawalResult, $playerName);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
        );

        if($success){
        	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        } else {
        	$status = $withdrawalResult['message']['result']['element']['properties'][1];
        	$result['reason_id'] = $this->getTransferErrorReasonCode($status);
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
		return array($success, $result);
    }

    public function getTransferErrorReasonCode($apiErrorCode) {
		$reasonCode = self::REASON_UNKNOWN;

		// 0: SUCCESS
		// 003: ERR_SYSTEM_OPR
		// 105: ERROR_INVALID_CURRENCY
		// 201: ERR_INVALID_REQ
		// 202: ERR_DB_OPEATION
		// 203: ERR_INVALID_CLIENT
		// 204: ERR_EXCEED_AMOUNT
		// 205: ERR_INVALID_VENDOR
		// 401: ERR_DUPLICATE_REFNO
		// 403: ERR_INVALID_AMOUNT
		// 404: ERR_ILLEGAL_DECIMAL
		// 801: ERR_XML_INPUT
		switch ((int)$apiErrorCode) {
			case 401:
				$reasonCode = self::REASON_DUPLICATE_TRANSFER;						
				break;
			case 105:
				$reasonCode = self::REASON_CURRENCY_ERROR;						
				break;
			case 403:
				$reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;						
				break;
			case 204:
				$reasonCode = self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;						
				break;
			case 203:
				$reasonCode = self::REASON_AGENT_NOT_EXISTED;						
				break;
			case 205:
				$reasonCode = self::REASON_AGENT_NOT_EXISTED;						
				break;
			case 404:
				$reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;						
				break;
			case 201:
				$reasonCode = self::REASON_INCOMPLETE_INFORMATION;						
				break;
			case 801:
				$reasonCode = self::REASON_INCOMPLETE_INFORMATION;						
				break;
			case 201:
				$reasonCode = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;						
				break;
			case 003:
				$reasonCode = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;						
				break;
		}

		return $reasonCode;
	}

	public function queryForwardGame($playerName, $extra = null) {
		/*
			Sample generated url
			https://n2stgd.staging.wir99.com/SingleLogin?merchantcode=WRX&lang=en&userId=test004&uuId=4b4e842504c678075078b26e800de711

		*/

		$language = $this->getLauncherLanguage($extra['language']);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$token = $this->getPlayerTokenByUsername($playerName);
		$gameUrl = $this->desktopGameurl;
		if($extra['is_mobile']){
			$gameUrl = $this->mobileGameUrl;
		}

		$params = array(
			"merchantcode" => $this->merchantCode,
			"lang" => $language,
			"userId" => $gameUsername,
			"uuId" => $token
		);
		$urlParams = http_build_query($params);
		$embeddableWebPage = $gameUrl."?".$urlParams;
		
		
		$data = [
            'url' => $embeddableWebPage,
            'redirect' => true,
            'success' => true
        ];
        $this->utils->debug_log(' N2 live  embeddableWebPage - =================================================> ' . $embeddableWebPage);
        return $data;
	}

	public function getLauncherLanguage($currentLang) {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh-CN';
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

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$endDate->modify($this->sync_end_minute_modify);

		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		$result = array();

		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+24 hours', function($startDate, $endDate)  {
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate   = $endDate->format('Y-m-d H:i:s');

			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForSyncGameRecords',
	            'startDate' => $startDate,
	            'endDate' => $endDate,
	        );

	        $params = array(
				'action' => self::ACTION_GAMEINFO,
				'properties' => array(
					'vendorid' => $this->vendorId,
					'merchantpasscode' => $this->merchantPassCode,
					'startdate' => $startDate,
					'enddate' => $endDate,
					'timezone' => 480,//GMT+8
				)
			);

			$this->utils->debug_log("syncOriginalGameLogs params ============================>", $params);
		    return $this->callApi(self::API_syncGameRecords, $params, $context);
		});

		return array(true, $result);	
	}

	public function processGameRecords($result, $responseResultId){
		$rows = array();
		if(!empty($result)){
			$gameinfo = isset($result['message']['result']['gameinfo']['game']) ? $result['message']['result']['gameinfo']['game'] : null;
			if(!empty($gameinfo)){
				if(!array_key_exists(0, $gameinfo)){//if key 0 is not set means single row.
					$singleGameInfo[] = $gameinfo;
					$gameinfo = $singleGameInfo;
				}
				unset($singleGameInfo);//clear array for next loop
				foreach ($gameinfo as $key => $gameinfoi) {
					$gameExternalId = $gameinfoi['@attributes']['code'];
					$gameData = $gameinfoi['deal'];

					if(!array_key_exists(0, $gameData)){//if key 0 is not set means single row.
						$singleData[] = $gameData;
						$gameData = $singleData;
					}
					unset($singleData);//clear array for next loop
					if(!empty($gameData)){
						foreach ($gameData as $key => $gameDatai) {
							$clientBets = $gameDatai['betinfo']['clientbet'];
							if(!array_key_exists(0, $clientBets)){//if key 0 is not set means single row.
								$singleBets[] = $clientBets;
								$clientBets = $singleBets;
							}
							unset($singleBets);//clear array for next loop
							if(!empty($clientBets)){
								foreach ($clientBets as $key => $bet) {
									/*
										note: external_uniqueid = dealid + loginnam or playerusername + gamecodeid.
										Every deal have multiple player.
									 */
									//game info
									$data['external_uniqueid'] = $gameDatai['@attributes']['id'].$bet['@attributes']['login'].$gameDatai['@attributes']['code'];
									$data['dealid'] = $gameDatai['@attributes']['id'];
									$data['startdate'] = $gameDatai['@attributes']['startdate'];
									$data['enddate'] = $gameDatai['@attributes']['enddate'];
									$data['status'] = $gameDatai['@attributes']['status'];
									$data['code'] = $gameDatai['@attributes']['code'];
									//client bet
									$data['login'] = $bet['@attributes']['login'];
									$data['bet_amount'] = $bet['@attributes']['bet_amount'];
									$data['payout_amount'] = $bet['@attributes']['payout_amount'];
									$data['valid_turnover'] = $bet['@attributes']['valid_turnover'];
									$data['handle'] = $bet['@attributes']['handle'];
									$data['hold'] = $bet['@attributes']['hold'];

									//bet details
									$data['betdetail'] = json_encode($bet['betdetail']);

									//gamecode
									$data['game_externalid'] = $gameExternalId;

									//other data
									$data['response_result_id'] = $responseResultId;
									$rows[] = $data;
								}
							}
						}
					}
				}
			}
		}
		return $rows;
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$result = $this->getResultXmlWithAttributeFromParams($params['resultText']);
		// $result = $this->getResultXmlFromParams($params['resultText']);
		$success = $this->processResultBoolean($responseResultId, $result, null);
		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0
		);
		if($success){
			$gameRecords = $this->processGameRecords($result, $responseResultId);
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

            $dataResult['data_count'] = count($gameRecords);
			if (!empty($insertRows)) {
				$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
		}
		return array($success, $dataResult);
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

	public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

	    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime='n2.startdate >= ? and n2.enddate <= ?';
		$sql = <<<EOD
SELECT n2.id as sync_index,
n2.startdate as start_at,
n2.startdate as bet_at,
n2.enddate as end_at,
n2.dealid as round_number,
n2.status,
n2.code,
n2.login as player_username,
n2.bet_amount as real_bet_amount,
n2.payout_amount,
n2.valid_turnover as bet_amount,
n2.handle,
n2.hold as result_amount,
n2.betdetail as bet_details,
n2.game_externalid as game_code,
n2.external_uniqueid,
n2.updated_at,
n2.md5_sum,
n2.response_result_id,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM n2live_game_logs as n2
LEFT JOIN game_description as gd ON n2.game_externalid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON n2.login = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	$extra_info=[];
    	$has_both_side=0;

    	if(empty($row['md5_sum'])){
        	//genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            	self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
        	//set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
    	$row['game']= lang('N2 Live Casino');
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['bet_details']=$this->generateBetDetails($row);
    }

    function generateBetDetails($row){
    	$betDetails = json_decode($row['bet_details'],true);
    	if(isset($betDetails['@attributes'])){
    		unset($betDetails['@attributes']);
    	}
    	// echo "<pre>";
    	// print_r($betDetails);exit();
    	// $result = null;
    	// if(isset($betDetails['bank'])){
    	// 	$result .= lang("bank") . " : ". $betDetails['bank'];
    	// }

    	// if(isset($betDetails['player'])){
    	// 	$string = $result ? " & " : "";
    	// 	$result .= $string . lang("player") . " : ". $betDetails['player'];
    	// }
    	return $betDetails;
    }

    /**
	 * overview : get game description information
	 *
	 * @param $row
	 * @param $unknownGame
	 * @param $gameDescIdMap
	 * @return array
	 */
	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
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
		return $this->returnUnimplemented();
	}

	public function processResultForgetVendorId($params) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
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
/* SAMPLE API CALL 

1. Check Client Function 
	
	<?xml version="1.0" encoding="utf-16"?>
	<request action="ccheckclient">
		<element id="C200509210001">
			<properties name="userid">test01</properties>
			<properties name="vendorid">360</properties>
			<properties name="merchantpasscode">E7C0DE50A2FE7BAB5147FE13B49DB701</properties>
			<properties name="currencyid">3601</properties>
		</element>
	</request>

2. Deposit Function
	
	<?xml version="1.0"?>
	<request action="cdeposit">
	    <element id="D1563254463">
	        <properties name="userid">test005</properties>
	        <properties name="acode"/>
	        <properties name="vendorid">360</properties>
	        <properties name="merchantpasscode">E7C0DE50A2FE7BAB5147FE13B49DB701</properties>
	        <properties name="currencyid">3601</properties>
	        <properties name="amount">0.01</properties>
	        <properties name="refno">1563254463IoXV0GVHQBW6gcS1mzg6</properties>
	    </element>
	</request>

3. Game info SAMPLE POST
	$xml = '<?xml version="1.0" encoding="utf-16"?><request action="gameinfo"><element>
        <properties name="vendorid">360</properties><properties name="merchantpasscode">E7C0DE50A2FE7BAB5147FE13B49DB701</properties><properties name="startdate">2019-07-17 23:30:00</properties><properties name="enddate">2019-07-18 12:52:00</properties><properties name="timezone">480</properties></element></request>';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://stgmerchanttalk.azuritebox.com/Trading/GameInfo');

	// For xml, change the content-type.
	// curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Accept-Encoding: gzip"));
	curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/plain","Accept-Encoding: gzip"));


	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_ENCODING,'gzip');

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned

	// Send to remote and return data to caller.
	$result = curl_exec($ch);
	curl_close($ch);
	echo "<pre>";
	print_r($result);exit();
	return $result;
*/

/*end of file*/