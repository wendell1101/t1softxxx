<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Mgw_seamless_game_service_api extends BaseController {

    //success codes
    const SUCCESS = 0; #'0000'

    #MGW Error codes
	const ERROR_INVALID							= '2000';
	const ERROR_FAILED_DOMAIN					= '2001';
	const ERROR_USER_NOT_EXIST					= '2002';
	const ERROR_MISSING_SITE_INFO				= '2003';
	const ERROR_MISSING_MERCHANT_INFO			= '2004';
	const ERROR_MISSING_FIRM_INFO				= '2005';
	const ERROR_FORBIDDEN_ACCOUNT				= '2006';
	const ERROR_FORBIDDEN_CHANGE_LOGO			= '2007';
	const ERROR_DECRYPT_DATA					= '2008';
	const ERROR_THE_END							= '2015';
	const ERROR_SERVICE_TEMPORARILY_UNAVAILABLE	= '2100';
	const ERROR_ORDER_PROCESSING				= '2101';
	const ERROR_FAILED_TRANSFER					= '2102';
	const ERROR_ORDER_ALREADY_EXIST				= '2103';
	const ERROR_ORDER_DOES_NOT_EXIST			= '2104';
	const ERROR_NOT_ENOUGH_MONEY				= '2105';
	const ERROR_LIMITATION_EXCEEDED				= '2106';
	const ERROR_SYSTEM_ERROR					= '9999';
	#Additional Error Codes
    const ERROR_GAME_MAINTENANCE 				= '1400';
    const ERROR_INVALID_PLAYER 					= '1305';
    const ERROR_BET_FAILED 						= '3033';
    const ERROR_INVALID_PLAYER_SESSION 			= '1307';
    const ERROR_PLAYER_SESSION_EXPIRED 			= '1308';
    const ERROR_IP_INVALID			 			= '1309';
    const ERROR_INVALID_PARAMETERS		 		= '1309';


	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS 								=> 200,
		self::ERROR_INVALID 						=> 500,
        self::ERROR_FAILED_DOMAIN 					=> 503,
        self::ERROR_USER_NOT_EXIST 					=> 500,
        self::ERROR_MISSING_SITE_INFO 				=> 500,
        self::ERROR_MISSING_MERCHANT_INFO 			=> 500,
        self::ERROR_MISSING_FIRM_INFO 				=> 500,
		self::ERROR_FORBIDDEN_ACCOUNT 				=> 503,
        self::ERROR_FORBIDDEN_CHANGE_LOGO 			=> 503,
        self::ERROR_DECRYPT_DATA 					=> 500,
        self::ERROR_THE_END 						=> 500,
        self::ERROR_SERVICE_TEMPORARILY_UNAVAILABLE => 500,
        self::ERROR_ORDER_PROCESSING 				=> 500,
        self::ERROR_FAILED_TRANSFER 				=> 500,
        self::ERROR_ORDER_ALREADY_EXIST 			=> 500,
        self::ERROR_ORDER_DOES_NOT_EXIST 			=> 500,
        self::ERROR_NOT_ENOUGH_MONEY 				=> 500,
        self::ERROR_LIMITATION_EXCEEDED 			=> 500,
        self::ERROR_SYSTEM_ERROR 					=> 500,
        self::ERROR_INVALID_PLAYER 					=> 200,
        self::ERROR_GAME_MAINTENANCE 				=> 503,
        self::ERROR_BET_FAILED 						=> 500,
        self::ERROR_INVALID_PLAYER_SESSION 			=> 500,
        self::ERROR_IP_INVALID 						=> 401,
        self::ERROR_INVALID_PARAMETERS 				=> 500,
	];

	#methods
    const METHOD_AUTHENTICATE   	= 'authenticate';
    const METHOD_PLACE_BET 			= 'placeBet';
    const METHOD_SETTLE_BET	     	= 'settleBet';

	#actions
	const ACTION_BET		 	 	= 'placebet'; 
	const ACTION_BET_SETTLE	 	 	= 'settle'; 
	const ACTION_BET_UNSETTLE	 	= 'unsettle';

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;
	public $currency;
	public $start_time;
	public $end_time;
	public $mgw_seamless_transactions;
	public $manual_request;
	private $headers;

	public function __construct() {
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip', 'mgw_seamless_transactions', 'game_logs'));
		$this->host_name =  $_SERVER['HTTP_HOST'];
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (__construct)", 'request', $this->request, 'REQUEST_URI', $_SERVER['REQUEST_URI']);

		$this->game_api = $this->utils->loadExternalSystemLibObject(MGW_SEAMLESS_GAME_API);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		$this->manual_request = $this->game_api->manual_request;

        if(!$this->game_api){
			$this->utils->debug_log("MGW SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

		if(!$this->validateWhiteIP()){

			return false;
		}

        $this->currency = $this->game_api->getCurrency();
        $this->mgw_seamless_transactions->tableName = $this->game_api->getTransactionsTable();
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (initialize) TABLE: ",  $this->mgw_seamless_transactions->tableName);
		return true;
	}
	public function authenticate($gamePlatformId){
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (auth)");	
     
		$externalResponse 	= $this->externalQueryResponse();	
		$callType 			= self::METHOD_AUTHENTICATE;
		$errorCode 			= self::SUCCESS;
		$externalResponse	= [];
		$player_id 			= null;
		$currency 			= null;
		$gameUsername 		= null;
		$success 			= false;
		$params 			= $this->request;
		try {     


			if(!$this->initialize($gamePlatformId)){
				$this->utils->debug_log('MGW authenticate: ERROR INITIALIZE');
				$errorCode = self::ERROR_SYSTEM_ERROR;
				throw new Exception(self::ERROR_SYSTEM_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
				$errorCode = self::ERROR_SYSTEM_ERROR;
				throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }
		
			#check if request is send by GP or ours using postman by using extra info "manual_request" set to true (default: false)
			if (!$this->manual_request) {
				$params = $this->game_api->decrypt_response(json_encode($params));
			}else{
				#check if request only has key and data even if "manual_request" is set to true
				if($this->hasKeyandData($params)){
					$params = $this->game_api->decrypt_response(json_encode($params));
				}
			}

			$requiredParams = ['uid','firmId','siteId','utoken','merchantId'];

			if(!$this->isParamsRequired($params,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($params['uid']);
			$this->utils->debug_log('MGW authenticate - dec:', $params);
			$player_id = null;

            if(!$playerStatus){                
				$errorCode = self::ERROR_SYSTEM_ERROR;
				throw new Exception(self::ERROR_INVALID_PLAYER_SESSION);
			}
			$player_id = $player->player_id;

			$success  = true;
			$currency = $this->game_api->getCurrency();

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$this->utils->debug_log('MGW errorCode', $errorCode);
			$success = false;
		}	
		
		$externalResponse = [
			'ret' => $errorCode == self::SUCCESS ? "0000" : $errorCode,
			'msg' => $errorCode == self::SUCCESS ? '' :  $this->getErrorSuccessMessage($errorCode),
			'userData' 	=> [
				'uid' 		=> isset($player_username) ? $player_username : null ,
				'userName' 	=> $gameUsername,
				'userEnable'=> 'Y',
				'userTest' 	=> 'Y',
				'currency'	=> $currency,
			],
		];
		
		$encryption = $this->game_api->encrypt_key_data($externalResponse);

		$returnResponse = [
			'key' 	=> $encryption[0],
			'data' 	=> $encryption[1]
		];

		$this->utils->debug_log('MGW authenticate:' ,$returnResponse);
		$this->utils->debug_log('MGW authenticate - decrypted:' ,$externalResponse);

		if ($this->manual_request) {
			if(!$this->hasKeyandData($this->request)){
				$returnResponse = $externalResponse;
			}
		}

		$fields = [
			'player_id'		=> $player_id,
		];

		$requestParams = [
			"raw" 		=> $params,
			"encrypted" => $this->request,
		];

		return $this->handleExternalResponse($success, $callType, $requestParams, $returnResponse, $errorCode, $fields, $externalResponse);	
	}
	
	public function hasOnlyKeys($array, $keys) {
		foreach ($array as $key => $value) {
			if (!in_array($key, $keys)) {
				return false;
			}
		}
		return true;
	}

	public function hasKeyandData($params){
		$requiredKeys = array("key", "data");
		if ($this->hasOnlyKeys($params, $requiredKeys)) {
			return true;
		} else {
			return false;
		}
	}
	
    public function isOperatorValid($operatorToken){
        return $this->game_api->operator_token==$operatorToken;
    }

    public function isSecretKeyValid($secretKey){
        return $this->game_api->secret_key==$secretKey;
    }

    public function isCurrencyCodeValid($currency){
        return $this->game_api->currency==$currency;
    }

	public function placeBet($gamePlatformId = null){

		$this->utils->debug_log("MGW SEAMLESS SERVICE: (PlaceBet)");
		// $externalResponse 	= $this->externalQueryResponse();
		$transaction_mode   = self::ACTION_BET;
		$trans_status 	 	= Game_logs::STATUS_PENDING;
		$errorCode			= self::SUCCESS;
		$callType 			= self::METHOD_PLACE_BET;
		$rules 				= [];
		$wagerDataResp 		= [];
		$success  			= true;
		$trans_success		= false;
		$betExist 			= false;
		$player_id			= null;
		$paramsReceived		= $this->request;
		$beforeBalance      = null;
		$afterBalance		= null;
		try {
			if(!$this->initialize($gamePlatformId)){
				$success   = false;
				$errorCode = self::ERROR_SYSTEM_ERROR;
				throw new Exception(self::ERROR_SYSTEM_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
				$success   = false;
				$errorCode = self::ERROR_SYSTEM_ERROR;
				throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

			
			#check if request is send by GP or ours using postman by using extra info "manual_request" set to true (default: false)
			if (!$this->manual_request) {
				$paramsReceived = $this->game_api->decrypt_response(json_encode($paramsReceived));
			}else{
				#check if request only has key and data even if "manual_request" is set to true
				if($this->hasKeyandData($paramsReceived)){
					$paramsReceived = $this->game_api->decrypt_response(json_encode($paramsReceived));
				}
			}

			$requiredParams = ['firmId','siteId','merchantId','wagerData'];

			if(!$this->isParamsRequired($paramsReceived,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			$this->utils->debug_log("MGW placebet: ",$paramsReceived);
			$this->utils->debug_log("MGW placebet-raw: ",$this->request);

			$merchant_id = $paramsReceived['merchantId']; 
			$site_id 	 = $paramsReceived['siteId'];
			$firm_id 	 = $paramsReceived['firmId'];
			$wagerData 	 = $paramsReceived['wagerData'];

			foreach($wagerData as $key => $value){
				$player_name = $value['uid']; #playername
				$utoken  	 = $value['utoken']; #usertoken
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
				if(!empty($player)){
					$params = [];
					//required params
					$params['player_id']                        = $player_id = $player->player_id;
					$params['merchant_id']                   	= $merchant_id;
					$params['site_id']                	   		= $site_id;
					$params['firm_id']                	   		= $firm_id;
					$params['game_id']							= $value['gameId'];
					$params['game_class'] 						= $value['gameClass'];
					$params['risk'] 							= $value['risk'];
					$params['game_round_id'] 					= $value['gameRoundId'];
					$params['wager_id'] 						= $value['wagerId'];
					$params['gold'] 							= $value['gold']; 
					$params['result'] 							= $value['result'];
					$params['order_date'] 						= $value['orderDate']; #yyyy-MM-dd
					$params['add_date'] 						= $value['addDate']; #yyyy-MM-dd HH:mm:sszzz
					$params['result_date']                   	= $value['resultDate'];
					$params['freegame']                   		= $value['freegame']; #Y/N
					$params['utoken']                   		= $value['utoken']; 
					$params['uid']                  		 	= $value['uid']; 
					$params['ip']                   			= $value['ip']; 
					$params['device']                   		= $value['device']; 
					$params['external_uniqueid']           		= $transaction_mode.'-'.$value['wagerId']; 
					$params['trans_status']						= $trans_status;
					$params['action']							= $transaction_mode;
					$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
					$params,
					&$insufficient_balance,
					&$beforeBalance,
					&$afterBalance,
					&$isAlreadyExists,
					&$additionalResponse,
					&$betExist,
					&$isNotEnoughBalance,
					&$responseMessage,
					$transaction_mode) {
						$balance = $this->game_api->queryPlayerBalance($player->game_username);
						$isNotEnoughBalance = $balance['balance'] < $params['gold'];
						if($isNotEnoughBalance){
							return false;
						}
						list($success, $responseMessage , $beforeBalance, $afterBalance) = $this->adjustWallet($transaction_mode, $player, $params);
						#adjustWalletResult must return before and after balance in array
						if(!$success){
							return false;
						}
						if(isset($additionalResponse['betExist'])){
							$betExist=$additionalResponse['betExist'];
						}
						return true;
					});
					if(!$player_id){
						$errorCode = self::ERROR_INVALID_PLAYER_SESSION;
					}
					if($isNotEnoughBalance){
						$errorCode = self::ERROR_NOT_ENOUGH_MONEY;
					}
				}else{
					$errorCode = self::ERROR_INVALID_PLAYER_SESSION;
				}
				$wagerDataResp[] = [
					'wagerId' 	=>	$value['wagerId'],
					'ret' 		=>	$trans_success ? "0000" : $errorCode,
					'msg' 		=>	$trans_success ? '' : $this->getErrorSuccessMessage($errorCode),
					'beginGold' =>	$beforeBalance,
					'endGold' 	=>	$afterBalance,
					'responseMessage' => $responseMessage
				];
			}
	
			$success = true;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		#return error response 
		if(empty($wagerDataResp)){
			$wagerDataResp[] = [
				'wagerId' 	=>	null,
				'ret' 		=>  $errorCode,
				'msg' 		=>	$this->getErrorSuccessMessage($errorCode),
				'beginGold' =>	null,
				'endGold' 	=>	null,
			];
		}
		$externalResponse['wagerDataResp'] = $wagerDataResp;
		$this->utils->debug_log('MGW placebet - raw', $externalResponse);
		$encryption = $this->game_api->encrypt_key_data($externalResponse);

		$returnResponse = [
			'key' 	=> $encryption[0],
			'data' 	=> $encryption[1]
		];

		if ($this->manual_request) {
			if(!$this->hasKeyandData($this->request)){
				$returnResponse = $externalResponse;
			}
		}

		$requestParams = [
			"raw" 		=> $paramsReceived,
			"encrypted" => $this->request,
		];
	
        $fields = [
			'player_id'	=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $requestParams, $returnResponse, $errorCode, $fields, $externalResponse);
	}

	/**
	 * SetteBet: Calculates the betting result.
	 * UnsetteBet: When a user cancels a PlaceBet.
	 * @param number $gamePlatformId - 6319
	 * @param string $type - 'settle' / 'unsettle'
	 */
	public function settleBet($gamePlatformId = null, $type = 'settle'){

		$this->utils->debug_log("MGW SEAMLESS SERVICE: (SettleBet)");
		$errorCode			= self::SUCCESS;
		$callType 			= self::METHOD_SETTLE_BET;
		$transaction_mode   = $type === 'settle' ? self::ACTION_BET_SETTLE : self::ACTION_BET_UNSETTLE;
		$trans_status 	 	= $type === 'settle' ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_UNSETTLED;
		$rules 				= [];
		$player_id			= null;
		$wagerDataResp  	= [];
		$paramsReceived 	= $this->request;
		try {
			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SYSTEM_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

			if($errorCode <> self::SUCCESS){
				throw new Exception(self::ERROR_SYSTEM_ERROR);
			}
			
			
			#check if request is send by GP or ours using postman by using extra info "manual_request" set to true (default: false)
			if (!$this->manual_request) {
				$paramsReceived = $this->game_api->decrypt_response(json_encode($paramsReceived));
			}else{
				#check if request only has key and data even if "manual_request" is set to true
				if($this->hasKeyandData($paramsReceived)){
					$paramsReceived = $this->game_api->decrypt_response(json_encode($paramsReceived));
				}
			}

			$requiredParams = ['firmId','siteId','merchantId','wagerData'];

			if(!$this->isParamsRequired($paramsReceived,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			$this->utils->debug_log('MGW-settlebet-params' , $paramsReceived);
			$merchant_id	= $paramsReceived['merchantId']; 
			$site_id 		= $paramsReceived['siteId'];
			$firm_id 		= $paramsReceived['firmId'];
			$wagerData 		= $paramsReceived['wagerData'];

			if(is_array($wagerData)){
				foreach($wagerData as $key => $value){
					$player_name = $value['uid']; #playername
					$utoken  	 = $value['utoken']; #usertoken
	
					list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
					
					if(!empty($player)){
						$params = [];
						//required params
						$params['player_id']                        = $player_id = $player->player_id;
						$params['merchant_id']                   	= $merchant_id;
						$params['site_id']                	   		= $site_id;
						$params['firm_id']                	   		= $firm_id;
						$params['game_id']							= $value['gameId'];
						$params['game_class'] 						= $value['gameClass'];
						$params['risk'] 							= $value['risk'];
						$params['game_round_id'] 					= $value['gameRoundId'];
						$params['wager_id'] 						= $value['wagerId'];
						$params['gold'] 							= $value['gold']; 
						$params['vgold'] 							= $value['vgold']; 
						$params['win_gold'] 						= $value['winGold'];
						$params['unused_gold'] 						= $value['unusedGold'];
						$params['commission_gold'] 					= $value['commissionGold'];
						$params['result'] 							= $value['result'];
						$params['order_date'] 						= $value['orderDate']; #yyyy-MM-dd
						$params['add_date'] 						= $value['addDate']; #yyyy-MM-dd HH:mm:sszzz
						$params['result_date']                   	= $value['resultDate'];
						$params['freegame']                   		= $value['freegame']; #Y/N
						$params['utoken']                   		= $value['utoken']; 
						$params['uid']                  		 	= $value['uid']; 
						$params['ip']                   			= $value['ip']; 
						$params['device']                   		= $value['device']; 
						$params['external_uniqueid']           		= $transaction_mode. '-'.$value['wagerId']; 
						$params['trans_status'] 	          		= $trans_status; 
						$params['action']	  	 	          		= $transaction_mode; 

	
						$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,$params,$transaction_mode,$type, &$beforeBalance, &$afterBalance, &$responseMessage) {
							$amount = $params['win_gold'] - $params['gold'];
							$tableName = $this->mgw_seamless_transactions->tableName;

							$isTransactionExist = $this->mgw_seamless_transactions->isTransactionExist($tableName,$params['wager_id'] , 'debit');
							if($isTransactionExist){
								list($success, $responseMessage , $beforeBalance, $afterBalance)  = $this->adjustWallet($transaction_mode, $player, $params);
								#adjustWalletResult must return before and after balance in array
								if(!$success){
									return false;
								}
								$this->utils->debug_log('MGW----trueSettleBet', $isTransactionExist);
								return true;
							}else{
								$this->utils->debug_log('MGW----falseSettleBet', $isTransactionExist);
								return false;
							}
						});

						if(!$player_id){
							$errorCode = self::ERROR_INVALID_PLAYER_SESSION;
						}

						$wagerDataResp[] = [
							'wagerId' 	=>	$value['wagerId'],
							'ret' 		=>	$trans_success ? "0000" : self::ERROR_ORDER_PROCESSING,
							'msg' 		=>	$trans_success ? '' : $this->getErrorSuccessMessage(self::ERROR_ORDER_PROCESSING),
							'beginGold' =>	$beforeBalance,
							'endGold' 	=>	$afterBalance,
							'responseMessage' => $responseMessage
						];
					}else{
						#if player id doesn't found 
						$wagerDataResp[] = [
							'wagerId' 	=>	null,
							'ret' 		=>	self::ERROR_INVALID_PLAYER_SESSION,
							'msg' 		=>	$this->getErrorSuccessMessage(self::ERROR_INVALID_PLAYER_SESSION),
							'beginGold' =>	null,
							'endGold' 	=>  null,
						];
					}
				}
			}

			$success = true;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}
		#return error response 
		if(empty($wagerDataResp)){
			$wagerDataResp[] = [
				'wagerId' 	=>	null,
				'ret' 		=>  $errorCode,
				'msg' 		=>	$this->getErrorSuccessMessage($errorCode),
				'beginGold' =>	null,
				'endGold' 	=>	null,
			];
		}

		$externalResponse['wagerDataResp'] = $wagerDataResp;
		$encryption = $this->game_api->encrypt_key_data($externalResponse);
		$returnResponse = [
			'key' 	=> $encryption[0],
			'data' 	=> $encryption[1]
		];
		$this->utils->debug_log('MGW-settleBet: ',$externalResponse);

		$requestParams = [
			"raw" 		=> $paramsReceived,
			"encrypted" => $this->request,
		];

		if ($this->manual_request) {
			if(!$this->hasKeyandData($this->request)){
				$returnResponse = $externalResponse;
			}
		}

        $fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $requestParams, $returnResponse, $errorCode, $fields, $externalResponse);
	}

	public function getGameClass($int = null){
		$game_class = '';
		switch($int){
			case 0:
				$game_class = 'slot machines';
				break;
			case 1:
				$game_class = 'board games';
				break;
			case 2:
				$game_class = 'fishing';
				break;
			default:
				$game_class = 'slot machine';
				break;
		}

		return $game_class;
	}

	public function getMoneyDisplay($number=0, $currency = null){
		$amount = number_format($number, 2);
		return $amount .' '. strtoupper($currency);
	}

	public function getBetResult($res=0){
		$result = '';
		switch($res){
			case 0:
				$result = 'no result';
				break;
			case 'L':
				$result = 'loses';
				break;
			case 'W':
				$result = 'wins';
				break;
			case 'D':
				$result = 'cancels';
				break;
			case 'D':
				$result = 'draw';
				break;
			default:
				$result = 'no result';
				break;
		}

		return $result;
	}


	public function unixtimeToDateTime($timestamp){
		$timestamp = $timestamp/1000;
		return date('Y-m-d H:i:s', $timestamp);
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("MGW SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("MGW SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("MGW SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}

	public function isParamsRequired($params, $required) {
		if($params && $required){
			# Check if all required parameters are present in the $params array
			foreach ($required as $param) {
				if (!array_key_exists($param, $params)) {
					return false; 
				}
			}
			return true; 
		}
	}
	public function getErrorSuccessMessage($code){
		$message = '';

        switch ($code) {
			case self::SUCCESS:
				$message = lang('Success');
				break;
            case self::ERROR_INVALID_PLAYER:
                $message = lang('Invalid Player');
                break;
            case self::ERROR_SYSTEM_ERROR:
                $message = lang('Internal server error');
                break;
            case self::ERROR_NOT_ENOUGH_MONEY:
                $message = lang('Insufficient player balance');
                break;
            case self::ERROR_ORDER_ALREADY_EXIST:
                $message = lang('Bet already existed');
                break;
            case self::ERROR_BET_FAILED:
                $message = lang('Bet failed');
                break;
            case self::ERROR_ORDER_DOES_NOT_EXIST:
                $message = lang('Order does not exist');
                break;
            case self::ERROR_INVALID_PLAYER_SESSION:
                $message = lang('Invalid Player session');
                break;
            case self::ERROR_PLAYER_SESSION_EXPIRED:
                $message = lang('Player session expired');
                break;
			case self::ERROR_ORDER_PROCESSING:
				$message = lang('Error processing');
				break;
			case self::ERROR_INVALID_PARAMETERS:
				$message = lang('Invalid or missing parameters');
				break;
			default:
				$this->utils->error_log("MGW SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}


	//default external response template
	public function externalQueryResponse(){
        return ['data'=>[],'error'=>null];
	}

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $statusText = null, $extra = null, $fields = [], $cost = null){
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		if(is_array($response)){
			$response = json_encode($response);
		}
		if(is_array($params)){
			$params = json_encode($params);
		}
		$extra = array_merge((array)$extra,(array)$this->headers);
        return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id,
        	$flag,
        	$callMethod,
        	$params,
        	$response,
        	$httpStatusCode,
        	$statusText,
			is_array($extra)?json_encode($extra):$extra,
			$fields,
			false,
			null,
			$cost
        );
	}

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = [], $decryptedResponse){
        $this->utils->debug_log("MGW SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);

		$httpStatusCode = self::HTTP_STATUS_CODE_MAP[self::SUCCESS];

		if(isset($error_code) && array_key_exists($error_code, self::HTTP_STATUS_CODE_MAP)){
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$error_code];
		}

		//add request_id
		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $decryptedResponse, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### MGW SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		return $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
	}

	public function getPlayerByToken($token){
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsername($gameUsername){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username, $player->player_id];
	}

	public function getPlayerByUsernameAndToken($gameUsername, $token){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

    public function getUpdateTime(){
        $milliseconds = round(microtime(true) * 1000);
        return $milliseconds;
    }


    /**
     * Parameters that have 'is_' as their prefix will output either 0 or 1
     */
    public function processIsParameter($parameter = null){
        if ($parameter == 'True'){
            return 1;
        } else if ($parameter == 'False'){
            return 0;
        }
        return null;
    }

	public function parseRequest(){
        $request_json = file_get_contents('php://input');
        $this->utils->debug_log("MGW SEAMLESS SERVICE raw:", $request_json);

        $this->request = json_decode($request_json, true);

        if (!$this->request){
            parse_str($request_json, $request_json);
            $this->utils->debug_log("MGW SEAMLESS SERVICE raw parsed:", $request_json);
            $this->request = $request_json;
        }

		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = MGW_SEAMLESS_GAME_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('t1lottery_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function formatBalance($amount) {
        return floatval(bcdiv($amount, 1,2));
    }

	public function isNumeric($amount){
		return is_numeric($amount);
	}

	public function getPlayerBalance($playerName, $player_id){
		$get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function adjustWallet($transaction_type, $player_info, $data = []) {
		$this->CI->load->model('wallet_model');	
		$tableName 						= $this->game_api->getTransactionsTable();
        $game_id 						= isset($data['game_id']) ? $data['game_id'] : null;
        $uniqueid_of_seamless_service 	= $this->game_api->getPlatformCode() . '-' . $data['external_uniqueid'];
		$currency	 					= $this->game_api->getCurrency();
		$playerName 					= $player_info->username;
		$playerId	 					= $player_info->player_id;
		$playerBalance 					= $this->getPlayerBalance($playerName, $playerId);
		$beforeBalance 					= $playerBalance;
		$afterBalance 					= $playerBalance;
        $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service);
        $this->wallet_model->setExternalGameId($game_id);

        if($transaction_type == self::ACTION_BET){
			#placing bet data
            #for adding more betting conditions
			$amount = $this->game_api->gameAmountToDBTruncateNumber($data['gold']) * -1; #getting the negative betting value [GOLD]
        }else if($transaction_type == self::ACTION_BET_SETTLE){
			#settling bet data
            $check 	= $this->mgw_seamless_transactions->flagBetTransactionSettled($tableName,$data);
			$amount = $this->game_api->gameAmountToDBTruncateNumber($data['win_gold']);
            $this->utils->debug_log('MGW', 'SETTLE!!!!', $check, $tableName);

			#checks if transaction is already unsettled 
			$unsettledData = $data;
			$unsettledData['external_uniqueid'] = self::ACTION_BET_UNSETTLE .'-'.$data['wager_id'];
			$checkTransactionSettled = $this->getExistingTransaction($unsettledData);
			if(!empty($checkTransactionSettled)){
				// $beforeBalance 	= $checkTransactionSettled['before_balance'];
				// $afterBalance 	= $checkTransactionSettled['after_balance'];
				return [
					true,
					'This transaction already been unsettled. ',
					$beforeBalance,
					$afterBalance,
				];
			}

        }else if($transaction_type == self::ACTION_BET_UNSETTLE){
			#unsettling bet data
			$this->mgw_seamless_transactions->flagBetTransactionUnsettled($tableName,$data);
			$amount = $data['unused_gold']; #unused gold 
			$this->utils->debug_log('MGW', 'UNSETTLE!!!!');

			#checks if transaction is already settled 
			$settledData = $data;
			$settledData['external_uniqueid'] = self::ACTION_BET_SETTLE .'-'.$data['wager_id'];
			$checkTransactionSettled = $this->getExistingTransaction($settledData);
			if(!empty($checkTransactionSettled)){
				return [
					true,
					'This transaction already been Settled. ',
					$beforeBalance,
					$afterBalance,
				];
			}
        }
	
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_api->getPlatformCode();
		
		#checks if transaction already exist! 
		$checkTransactionExist = $this->getExistingTransaction($data);
		if(!empty($checkTransactionExist)){
			$beforeBalance 	= $checkTransactionExist['before_balance'];
			$afterBalance 	= $checkTransactionExist['after_balance'];
			return [
				true,
				ucfirst($data['action']) . ' transaction Already Exist',
				$beforeBalance,
				$afterBalance,
			];
		}

		if($amount <> 0){
			if($amount > 0){ 	
				#credit
				$afterBalance 						= $playerBalance + $amount;
				$data['balance_adjustment_method'] 	= 'credit';
				$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $afterBalance);
				$this->utils->debug_log('MGW', 'ADD-AMOUNT: ', $response);
			}else{				
				#debit
				$amount 							= abs($amount);
				$afterBalance 						= $playerBalance - $amount;
				$data['balance_adjustment_method'] 	= 'debit';
				$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $afterBalance);
				$this->utils->debug_log('MGW', 'MINUS-AMOUNT: ', $response , $amount);
				
				$this->utils->debug_log('MGW--before-after-balance: ', $beforeBalance, $afterBalance);
			}
		}

		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
		
		$insertTransaction = $this->mgw_seamless_transactions->insertTransactionData($tableName,$data);
		return $insertTransaction ? [true, 'Success',  $beforeBalance, $afterBalance, ] : [false, 'Error' , null, null];
    }

	public function getExistingTransaction($data){
		$check_bet_params   = [
			'external_uniqueid'   => $data['external_uniqueid'],
		];
		$checkOtherTable 	= $this->game_api->checkOtherTransactionTable();
		$currentTableName   = $this->game_api->getTransactionsTable();
		$currentRoundData 	= $this->CI->mgw_seamless_transactions->getRoundData($currentTableName, $check_bet_params);
		$prevRoundData   	= [];
		if($checkOtherTable){                    
			# get prev table
			$prevTranstable = $this->game_api->getTransactionsPreviousTable();

			$this->utils->debug_log("MGW SEAMLESS SERVICE: (getExistingTransaction)", 'prevTranstable', $prevTranstable);
			# get data from prev table
			$prevRoundData = $this->CI->mgw_seamless_transactions->getRoundData($prevTranstable, $check_bet_params);
		}

		$roundData = array_merge($currentRoundData, $prevRoundData);   
		foreach($roundData as $roundDataRow){
			if($roundDataRow['wager_id'] == $data['wager_id']){
				// $existingTrans = $roundDataRow;
				return $roundDataRow;
			}
		}
		return false;
	}

	public function getBalance($gamePlatformId){
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (GetBalance)");	
		$this->CI->load->model(array('player_model'));
		$externalResponse = $this->externalQueryResponse();	

		$callType 			= 'GetBalance';
		$errorCode			= self::ERROR_SYSTEM_ERROR;
		$externalResponse 	= [];
		$playerId 			= null;
		$balance		 	= 0;
		$currency 			= null;
		$gameUsername 		= null;
		$success 			= false;
		$params 			= $this->request;

		try {     

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SYSTEM_ERROR);
			}

			if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
				throw new Exception(self::ERROR_GAME_MAINTENANCE);
			}
			
			
			if(!empty($this->game_api->trigger_auth_error_response)){
				throw new Exception($this->game_api->trigger_auth_error_response);
			}   

			#check if request is send by GP or ours using postman by using extra info "manual_request" set to true (default: false)
			if (!$this->manual_request) {
				$params = $this->game_api->decrypt_response(json_encode($params));
			}else{
				if($this->hasKeyandData($params)){
					$params = $this->game_api->decrypt_response(json_encode($params));
				}
			}

			$requiredParams = ['uid','firmId','siteId','merchantId'];

			if(!$this->isParamsRequired($params,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			$uid = $params['uid'];

			$gameUsername = $uid;
			$playerName   = $this->game_provider_auth->getPlayerUsernameByGameUsername($gameUsername, $gamePlatformId);
			$playerId     = $this->game_provider_auth->getPlayerIdByPlayerName($gameUsername, $gamePlatformId);

			
			#check if player exist
			if(empty($playerId)){
				throw new Exception(self::ERROR_INVALID_PLAYER);
			}

			$balance 	= $this->game_api->queryPlayerBalance($playerName);
			$success 	= true;
			$errorCode 	= self::SUCCESS;
			

		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}	
		$currency = $this->game_api->getCurrency();
		$externalResponse = [
			'uid' 		=> $gameUsername,
			'userGold'	=> $balance['balance'] ? $balance['balance'] : 0,
			'currency'	=> $currency,
			'ret'		=> $errorCode == self::SUCCESS ? "0000" : $errorCode,
			'msg' 		=> $errorCode == self::SUCCESS ? '' :  $this->getErrorSuccessMessage($errorCode),
		];

		$this->utils->debug_log('MGW getBalance: ',$externalResponse, $params);

		$encryption = $this->game_api->encrypt_key_data($externalResponse);

		$returnResponse = [
			'key' 	=> $encryption[0],
			'data' 	=> $encryption[1]
		];

		if ($this->manual_request) {
			if(!$this->hasKeyandData($this->request)){
				$returnResponse = $externalResponse;
			}
		}
		$requestParams = [
			"raw" 		=> $params,
			"encrypted" => $this->request,
		];

		
		$fields = [
			'player_id'		=> $playerId,
		];
		return $this->handleExternalResponse($success, $callType, $requestParams, $returnResponse, $errorCode, $fields,$externalResponse);
	}


	public function generatePlayerToken($gamePlatformId){
        $this->utils->debug_log("MGW SEAMLESS SERVICE: (bet)");

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}

		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

	public function validateWhiteIP()
	{
		$this->utils->debug_log("MGW SEAMLESS SERVICE: (validateWhiteIP)");
		$validated =  $this->game_api->validateWhiteIP();
		if (!$validated) {
			$this->utils->debug_log("MGW SEAMLESS SERVICE: (validateWhiteIP) : not whitelisted");
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_IP_INVALID];
			http_response_code($httpStatusCode);
			return false;
		}
		return true;
	}

	public function EncryptDecrypt($gamePlatformId, $mode){
		if(!$this->initialize($gamePlatformId)){
			return;
		};
		$data = $this->request;
		if ($mode=='encrypt'){
			$encryption = $this->game_api->encrypt_key_data($data);
			$response = [
				'key' 	=> $encryption[0],
				'data' 	=> $encryption[1]
			];
		}elseif($mode == 'decrypt'){
			$response = $this->game_api->decrypt_response(json_encode($data));
		}
		return $this->handleExternalResponse(true, $mode, $this->request, $response, null, null, $response);
	}
}///END OF FILE////////////