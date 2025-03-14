<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Pinnacle_seamless_service_api extends BaseController {	
	
    const SUCCESS = '0x00';
    
    //error codes	
	const ERROR_SERVICE_NOT_AVAILABLE = '0x01';
	const ERROR_INVALID_SIGN = '0x02';
	const ERROR_INVALID_PARAMETERS = '0x03';
	const ERROR_INSUFFICIENT_BALANCE = '0x04';
	const ERROR_SERVER = '0x05';
	const ERROR_CANNOT_FIND_PLAYER = '0x06';
	const ERROR_TRANSACTION_ALREADY_EXIST = '0x07';
	const ERROR_IP_NOT_ALLOWED = '0x08';
	const ERROR_BET_DONT_EXIST = '0x09';		
	const ERROR_GAME_UNDER_MAINTENANCE = '0x10';
	const ERROR_CONNECTION_TIMED_OUT = '0x11';
	const ERROR_REFUND_PAYOUT_EXIST = '0x12';	
	const ERROR_PLAYER_BLOCKED = '0x13';	
	const ERROR_UNKNOWN = '0x14';

	const ERROR_ACTION_SUCCESS = 0;
	const ERROR_ACTION_UNKNOWN = -1;
	const ERROR_ACTION_INSUFFICIENT = -2;

	const ERROR_CODES = [
		self::SUCCESS=>0,
		self::ERROR_CANNOT_FIND_PLAYER=>-5,//Account Not Found
		self::ERROR_INVALID_SIGN=>-6,//API Authentication Failed
		self::ERROR_SERVER=>-1,//Unknown Error
		self::ERROR_UNKNOWN=>-1,//Unknown Error
		self::ERROR_INVALID_PARAMETERS=>-6,//API Authentication Failed
		self::ERROR_BET_DONT_EXIST=>-8,//Transaction Not Found
		self::ERROR_PLAYER_BLOCKED=>-4,
		self::ERROR_INSUFFICIENT_BALANCE=>-1,//action error Insufficient Funds



		self::ERROR_SERVICE_NOT_AVAILABLE=>-1,
		
		
		
				
		self::ERROR_TRANSACTION_ALREADY_EXIST=>-1,
		self::ERROR_IP_NOT_ALLOWED=>-1,
		self::ERROR_GAME_UNDER_MAINTENANCE=>-1,
		self::ERROR_CONNECTION_TIMED_OUT=>-1,
		self::ERROR_REFUND_PAYOUT_EXIST=>-1,
	];
	
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_SERVICE_NOT_AVAILABLE=>200,		
		self::ERROR_INVALID_SIGN=>200,
		self::ERROR_INVALID_PARAMETERS=>200,
		self::ERROR_INSUFFICIENT_BALANCE=>200,
		self::ERROR_SERVER=>200,
		self::ERROR_CANNOT_FIND_PLAYER=>200,	
		self::ERROR_TRANSACTION_ALREADY_EXIST=>200,	
		self::ERROR_IP_NOT_ALLOWED=>200,
		self::ERROR_BET_DONT_EXIST=>200,
		self::ERROR_REFUND_PAYOUT_EXIST=>200,
		self::ERROR_GAME_UNDER_MAINTENANCE=>200,
		self::ERROR_CONNECTION_TIMED_OUT=>200,
	];

	const WAGER_ACTION_BETTED = 'BETTED';
	const WAGER_ACTION_ACCEPTED = 'ACCEPTED';
	const WAGER_ACTION_SETTLED = 'SETTLED';
	const WAGER_ACTION_REJECTED = 'REJECTED';
	const WAGER_ACTION_CANCELLED = 'CANCELLED';
	const WAGER_ACTION_ROLLBACKED = 'ROLLBACKED';	
	const WAGER_ACTION_UNSETTLED = 'UNSETTLED';

	const WAGER_STATUS_OPEN = 'OPEN';
	const WAGER_STATUS_ACCEPTED = 'ACCEPTED';
	const WAGER_STATUS_SETTLED = 'SETTLED';
    const WAGER_STATUS_REJECTED = 'REJECTED';
    const WAGER_STATUS_CANCELLED = 'CANCELLED';
    const WAGER_STATUS_ROLLBACKED = 'ROLLBACKED';
    const WAGER_STATUS_UNSETTLED = 'UNSETTLED';

    const WAGER_ACTION_STATUS = [
        self::WAGER_ACTION_BETTED => self::WAGER_STATUS_OPEN,
        self::WAGER_ACTION_ACCEPTED => self::WAGER_STATUS_ACCEPTED,
        self::WAGER_ACTION_SETTLED => self::WAGER_STATUS_SETTLED,
        self::WAGER_ACTION_REJECTED => self::WAGER_STATUS_REJECTED,
        self::WAGER_ACTION_CANCELLED => self::WAGER_STATUS_CANCELLED,
        self::WAGER_ACTION_ROLLBACKED => self::WAGER_STATUS_ROLLBACKED,
        self::WAGER_ACTION_UNSETTLED => self::WAGER_STATUS_UNSETTLED,
    ];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;

    private $transaction_for_fast_track = null;

	private $headers;

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','pinnacle_seamless_transactions', 'ip'));
		
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }
        
        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
	
        if(!$this->game_api){
			$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

		$this->game_api->request = $this->request;

        //$this->currency = $this->game_api->getCurrency();
		//$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (initialize) currency: ", $this->currency);
        $this->pinnacle_seamless_transactions->tableName = $this->game_api->getTransactionsTable();

		return true;
	}	

	/*
	* URI: /v1/ping
	* Method: POST 
	*/
	public function ping($gamePlatformId){
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (balance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'ping';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
			'Timestamp'=>'required',
		];

		try {     

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} 
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}   
			
			$success = true;
			$errorCode = self::SUCCESS;
			
		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}	

		$externalResponse['Result'] = [];		
		$externalResponse['Result']['Available'] = $success;		
		$externalResponse['ErrorCode'] = $this->getErrorCode($errorCode);		
		$externalResponse['Timestamp'] = (new DateTime())->format("Y-m-d\TH:i:s");

		$fields = [
			'player_id'		=> $player_id,
		];		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	/*
	* URI: /v1/{agentcode}/wallet/usercode/{usercode}/balance
	* Method: POST 
	*/
	public function balance($gamePlatformId, $agentCode, $userCode){
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (balance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'balance';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
			'Timestamp'=>'required',
			'Signature'=>'required',
		];

		try {     

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} 
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if($agentCode<>$this->game_api->agentCode){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  

			$signature = isset($this->request['Signature'])?$this->request['Signature']:null;
			
			if(empty($signature) || !$this->isValidSign($signature)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			// get player details			
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($userCode);

            if(!$playerStatus || !isset($player->player_id)){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

			$player_id = $player->player_id;

			// $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			// $player_username,				
			// 	&$balance) {		
					
			// 	$balance = $this->game_api->getPlayerBalance($player_id);
			// 	if($balance===false){
			// 		return false;
			// 	}
			
			// 	return true;								
			// });
			$use_read_only = true;
			$balance = $this->player_model->getPlayerSubWalletBalance($player_id, $this->game_platform_id, $use_read_only);
			
			$success = true;
			$errorCode = self::SUCCESS;
			
			
		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}	

		$externalResponse['Result'] = [];		
		$externalResponse['Result']['UserCode'] = $userCode;		
		$externalResponse['Result']['AvailableBalance'] = $this->game_api->dBtoGameAmount($balance);	
		$externalResponse['ErrorCode'] = $this->getErrorCode($errorCode);		
		$externalResponse['Timestamp'] = (new DateTime())->format("Y-m-d\TH:i:s");
		
		$fields = [
			'player_id'		=> null,
		];		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	/*
	* URI: /v1/{agentcode}/wagering/usercode/{usercode}/request/{requestid}
	* Method: POST 
	*/
	public function wagering($gamePlatformId, $agentCode, $userCode, $requestId){
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (wagering)", $this->request);	

		$externalResponse = $this->externalQueryResponse();	

		$callType = 'wagering';
		$origErrorCode = $errorCode = self::SUCCESS;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$betExist = false;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'Timestamp'=>'required',
			'Signature'=>'required',
			'Actions'=>'required',
			'Actions'=>'isArray',
		];

		### START OVERALL VALIDATION
		if(!$this->initialize($gamePlatformId)){
			$errorCode= self::ERROR_SERVICE_NOT_AVAILABLE;			
		}

		if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
			$errorCode= self::ERROR_GAME_UNDER_MAINTENANCE;			
		}

		if(!$this->game_api->validateWhiteIP()){
			$errorCode= self::ERROR_IP_NOT_ALLOWED;
		} 

		if(!$this->isValidParams($this->request, $rules)){
			$errorCode = self::ERROR_INVALID_PARAMETERS;
		}   
			
		if($agentCode<>$this->game_api->agentCode){
			throw new Exception(self::ERROR_INVALID_PARAMETERS);
		}  

		$signature = isset($this->request['Signature'])?$this->request['Signature']:null;
		
		if(empty($signature) || !$this->isValidSign($signature)){
			$errorCode = self::ERROR_INVALID_SIGN;			
		}

		if(empty($userCode)){
			$errorCode =  self::ERROR_CANNOT_FIND_PLAYER;
		}

		list($loginId, $userCode) = $this->extractLoginInfo();

		// list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($userCode);
		list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByLoginIdAndExternalAccountId($userCode, $loginId);
		
		if(!$playerStatus || !isset($player->player_id)){
			$errorCode =  self::ERROR_CANNOT_FIND_PLAYER;
		}

		if($errorCode<>self::SUCCESS){
			$this->utils->error_log("PINNACLE SEAMLESS SERVICE: wagering ", $errorCode);
			$externalResponse['Result']['UserCode'] = $userCode;		
			$externalResponse['Result']['AvailableBalance'] = 0;	
			$externalResponse['ErrorCode'] = $this->getErrorCode($errorCode);		
			$externalResponse['Timestamp'] = (new DateTime())->format("Y-m-d\TH:i:s");
			$success = false;
			$actions = isset($this->request['Actions'])?$this->request['Actions']:[];
			$responseActions = [];
			foreach($actions as $action){
				$callType = isset($action['Name'])?$action['Name']:'wagering';

				$responseActions[]= [
					"Id"=>$action['Id'],
					"TransactionId"=>isset($action['Transaction']['TransactionId'])?$action['Transaction']['TransactionId']:null,
					"WagerId"=>isset($action['WagerInfo']['WagerId'])?$action['WagerInfo']['WagerId']:null,
					"ErrorCode"=>self::ERROR_ACTION_UNKNOWN
				];	
			}			
				
			$externalResponse['Result']['Actions'] = $responseActions;			
			
			$fields = [
				'player_id'		=> null,
			];		
			return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
		}
		### END OVERALL VALIDATION
		
		$player_id = $player->player_id;
		$dateObj = new DateTime($this->request['Timestamp']);
		$actions = isset($this->request['Actions'])?$this->request['Actions']:[];

		if(empty($actions)){
			throw new Exception(self::ERROR_INVALID_PARAMETERS);
		}

		$responseActions = [];
		$overAllErrorCode = 0;
		$success = true;
		foreach($actions as $action){

			try {   

				$actionErrorCode = -1;

				$params = [];

				$callType = isset($action['Name'])?$action['Name']:'wagering';
				if(empty($callType)){
					throw new Exception(self::ERROR_INVALID_PARAMETERS);
				}
				
				//according to game provider uniqueidcan bet transactionId or actionId
				$uniqueid = isset($action['Id'])?$action['Id']:null;
				$isManual = isset($action['ManualWager'])?$action['ManualWager']:false;			
				$params['external_uniqueid'] = $uniqueid;
				$params['request_id'] = $requestId;						
				$params['transaction_type'] = $callType;

				# playerInfo
				$params['player_id'] = $player_id;

				# dates
				$params['timestamp'] = $this->request['Timestamp'];			
				$params['transaction_date'] = $dateObj->format('Y-m-d H:i:s');
				if(isset($action['Transaction']) && isset($action['Transaction']['TransactionDate'])){
					$params['start_at'] = (new DateTime($action['Transaction']['TransactionDate']))->format("Y-m-d H:i:s");
					$params['end_at'] =  (new DateTime($action['Transaction']['TransactionDate']))->format("Y-m-d H:i:s");
				}elseif(isset($action['Transaction']) && isset($action['Transaction']['EventDateFm'])){
					$params['start_at'] = (new DateTime($action['Transaction']['EventDateFm']))->format("Y-m-d H:i:s");
					$params['end_at'] =  (new DateTime($action['Transaction']['EventDateFm']))->format("Y-m-d H:i:s");
				}else{
					$params['start_at'] = (new DateTime())->format("Y-m-d H:i:s");
					$params['end_at'] =  (new DateTime())->format("Y-m-d H:i:s");
				}

				$params['settled_at'] = $params['transaction_date'];
				if($callType==self::WAGER_ACTION_SETTLED && isset($action['WagerInfo']) && isset($action['WagerInfo']['SettlementTime'])){
					$params['settled_at'] = (new DateTime($action['WagerInfo']['SettlementTime']))->format("Y-m-d H:i:s");
				}
				
				# amount
				$params['amount'] = isset($action['Transaction']['Amount'])?$action['Transaction']['Amount']:0;
				$params['after_balance'] = 0;
				$params['before_balance'] = 0;

				# extra
				$params['extra_info'] = $this->request;
				$params['game_id'] = isset($action['WagerInfo']['Sport'])?$action['WagerInfo']['Sport']:null;
				$params['wager_id'] = isset($action['WagerInfo']['WagerId'])?$action['WagerInfo']['WagerId']:null;
				$params['round_id'] = $params['wager_id'];
				if(!empty($action['WagerInfo']['WagerMasterId'])){
					$params['round_id'] = $action['WagerInfo']['WagerMasterId'];
				}
				$params['wager_master_id'] = isset($action['WagerInfo']['WagerMasterId'])?$action['WagerInfo']['WagerMasterId']:null;			
				$params['transaction_id'] = isset($action['Transaction']['TransactionId'])?$action['Transaction']['TransactionId']:null;
                $params['refer_transaction_id'] = isset($action['Transaction']['ReferTransactionId'])?$action['Transaction']['ReferTransactionId']:null;
                
                
				$params['bet_type'] = isset($action['WagerInfo']['Type'])?$action['WagerInfo']['Type']:null;			
				
				if(empty($params['game_id'])&&isset($action['WagerInfo']['Legs'])){
					foreach($action['WagerInfo']['Legs'] as $leg){
						$params['game_id'] = isset($leg['Sport'])?$leg['Sport']:null;
						break;
					}
				}

				

				$mode = null;//no need balance adjustment just add the transaction record, can set amount to 0
				if(isset($action['Transaction']['TransactionType']) && $action['Transaction']['TransactionType']=='CREDIT'){
					$mode = 'credit';
				}elseif(isset($action['Transaction']['TransactionType']) && $action['Transaction']['TransactionType']=='DEBIT'){
					$mode = 'debit';
				}else{
					if(($callType==self::WAGER_ACTION_ACCEPTED || $callType==self::WAGER_ACTION_SETTLED) && $params['amount']==0){
						$mode = 'credit';
					}
				}

				if(empty($mode)){
					$params['amount'] = 0;
				}

				$params['wallet_adjustment_mode'] = $mode;

				if(empty($uniqueid)){
					$this->utils->error_log("PINNACLE SEAMLESS SERVICE: wagering ERROR NO UNIQUEID", $this->request);
					throw new Exception(self::ERROR_INVALID_PARAMETERS);
				}

				if($callType==self::WAGER_ACTION_BETTED){
					$params['status'] = self::WAGER_STATUS_OPEN;
				}else{
					$params['status'] = null;
				}				

				#fix missing game_id
				if($isManual&&empty($params['game_id'])){
					# need to populate missing info for manual
					$description = isset($actions['WagerInfo']['Description'])?$actions['WagerInfo']['Description']:'';
					$descriptionExp = explode('-',$description);
					$params['game_id'] = isset($descriptionExp[0])?trim($descriptionExp[0]):null;
				}

				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
					$params,
					&$insufficient_balance, 
					&$previous_balance, 
					&$after_balance, 
					&$isAlreadyExists,
					&$additionalResponse,
					$mode,
					&$betExist,
					$callType) {
					if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
						$this->wallet_model->setGameProviderRoundId($params['round_id']);
					}
					
					$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
					if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
						$this->wallet_model->setGameProviderBetAmount($params['amount']);
					}
					if($callType ==self::WAGER_ACTION_ACCEPTED || $callType==self::WAGER_ACTION_SETTLED){
						$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
						$isEnd = true;

						if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
							$this->wallet_model->setGameProviderPayoutAmount($params['amount']);
						}
					}
					if(in_array($callType, [self::WAGER_ACTION_SETTLED, self::WAGER_ACTION_CANCELLED, self::WAGER_ACTION_ROLLBACKED, self::WAGER_ACTION_UNSETTLED, self::WAGER_ACTION_REJECTED])){
						$isEnd = true;
						if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
							$this->wallet_model->setGameProviderIsEndRound($isEnd);
						}
					}

					if (method_exists($this->wallet_model, 'setExternalGameId')) {
                        $this->wallet_model->setExternalGameId($params['game_id']);#this will be override on transferGameWallet if amount not equal to zero
                    }

					list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded, $allowedNegativeBalance) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance, $mode,$remoteActionType);
					$this->utils->debug_log("PINNACLE SEAMLESS SERVICE lockAndTransForPlayerBalance $callType",
					'trans_success',$trans_success,
					'previous_balance',$previous_balance,
					'after_balance',$after_balance,
					'insufficient_balance',$insufficient_balance,
					'isAlreadyExists',$isAlreadyExists,
					//'additionalResponse',$additionalResponse,
					'isTransactionAdded',$isTransactionAdded,
                    'allowedNegativeBalance', $allowedNegativeBalance);	

					if(isset($additionalResponse['betExist'])){
						$betExist=$additionalResponse['betExist'];	
					}
					return $trans_success;
				});

				if($insufficient_balance){				
					$actionErrorCode = -2;
					$overAllErrorCode = 0;		
					throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
				}
	
				if($isAlreadyExists){				
					//throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);
				}
	
				if(!$betExist&&$callType!=self::WAGER_ACTION_BETTED){	
					$actionErrorCode = $this->getErrorCode(self::ERROR_BET_DONT_EXIST);					
					throw new Exception(self::ERROR_BET_DONT_EXIST);
				}
	
				if($trans_success==false){
					$actionErrorCode = -1;	
					throw new Exception(self::ERROR_SERVER);
				}

				$actionErrorCode = 0;
			} catch (Exception $error) {
				$origErrorCode = $error->getMessage();
				$overAllErrorCode = -1;
				$errorCode = self::ERROR_UNKNOWN;
				$success = false;

				if($insufficient_balance&&$callType==self::WAGER_ACTION_BETTED){
					$overAllErrorCode = 0;
				}
			}	

			$responseActions[]= [
				"Id"=>$action['Id'],
				"TransactionId"=>isset($action['Transaction']['TransactionId'])?$action['Transaction']['TransactionId']:null,
				"WagerId"=>isset($action['WagerInfo']['WagerId'])?$action['WagerInfo']['WagerId']:null,
				"ErrorCode"=>$actionErrorCode
			];

		}

		$externalResponse['Result']['UserCode'] = $userCode;		
		$externalResponse['Result']['AvailableBalance'] = $this->game_api->dBtoGameAmount($after_balance);	
		$externalResponse['Result']['Actions'] = $responseActions;
		$externalResponse['ErrorCode'] = $overAllErrorCode;		
		$externalResponse['Timestamp'] = (new DateTime())->format("Y-m-d\TH:i:s");	
		//$externalResponse['Message'] = $this->getErrorSuccessMessage($origErrorCode);
		
		
		$fields = [
			'player_id'		=> $player_id,
		];		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	###########################
	
	protected function isValidSign($requestSign){
		return true;
		if($this->game_api->force_valid_signature){
			$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidSign) ", 'isForceTrue', $this->game_api->force_valid_signature, 'requestSign', $requestSign, 'secretKey',$this->game_api->secretKey);	
			return true;
		}
		$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidSign) ", 'requestSign', $requestSign, 'secretKey',$this->game_api->secretKey);	
		$decrypt=$this->game_api->str_decryptaesgcm($requestSign, $this->game_api->secretKey);		
		$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidSign) ", 'decrypt', $decrypt);	
		

		return $decrypt;
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);	
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
				return false;
			}
			if($rule=='isArray'&&!is_array($request[$key])){
				$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidParams) Parameters isNotArray: ". $key, $request, $rules);	
				return false;
			}
		}

		return true;
	}

	public function getErrorSuccessMessage($code){
		$message = '';		

		if(!array_key_exists($code, self::HTTP_STATUS_CODE_MAP)){
			$message = $code;		
			return $message;
		}

        switch ($code) {

			case self::SUCCESS:
				return lang('Success');

			case self::ERROR_INVALID_SIGN:
				return lang('Invalid signature');

			case self::ERROR_INVALID_PARAMETERS:
				return lang('Invalid parameters');

            case self::ERROR_SERVICE_NOT_AVAILABLE:
                return lang('Service not available');	

            case self::ERROR_INSUFFICIENT_BALANCE:
                return lang('Insufficient Balance');

			case self::ERROR_SERVER:
				return lang('Server Error');

			case self::ERROR_UNKNOWN:
				return lang('Unknown Error');
				
			case self::ERROR_IP_NOT_ALLOWED:
				return lang('IP is not allowed');	

			case self::ERROR_TRANSACTION_ALREADY_EXIST:
				return lang('Transactions already exists.');	

			case self::ERROR_CANNOT_FIND_PLAYER:
				return lang('Cannot find player.');		

			case self::ERROR_BET_DONT_EXIST:
				return lang('Bet dont exist.');		

			case self::ERROR_REFUND_PAYOUT_EXIST:
				return lang('Payout already exist.');	

			case self::ERROR_GAME_UNDER_MAINTENANCE:
				return lang('Game under maintenance.');

			case 'Connection timed out.':
			case self::ERROR_CONNECTION_TIMED_OUT:
				return lang('Connection timed out.');

			default:
				$this->CI->utils->error_log("PINNACLE SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				return $code;
		}
	}

	public function getErrorCode($code){
		foreach(self::ERROR_CODES as $key => $val){
			if($code==$key){
				return $val;
			}
		}		
		return -1;
	}

	public function isValidAgent($agentId){		

		if($this->game_api->agent_id==$agentId){
			return true;
		}
		$this->utils->error_log("PINNACLE SEAMLESS SERVICE: (isValidAgent)", $agentId);
		return false;
    }
    
	//default external response template
	public function externalQueryResponse(){
		return array(
            "Errorode" => self::ERROR_INVALID_PARAMETERS,
			"Timestamp" => (new DateTime())->format("Y-m-d\TH:i:s"),
			"Result" => []            
		);
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

	public function getHttpStatusCode($errorCode){
		$httpCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_SERVER];
		foreach(self::HTTP_STATUS_CODE_MAP as $key => $value){
			if($errorCode==$key){
				$httpCode = $value;
			}
		}
		return $httpCode;
	}
	
	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->CI->utils->debug_log("PINNACLE SEAMLESS SERVICE: (handleExternalResponse)", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code, 
            'fields', $fields);	
		
		if(strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log("PINNACLE SEAMLESS SERVICE: (handleExternalResponse) Connection timed out.", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code, 
            'fields', $fields);	
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}
		
		$httpStatusCode = $this->getHttpStatusCode($error_code);

		//$response['err_msg'] = $this->getErrorSuccessMessage($error_code);

		//add request_id
		if(empty($response)){
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);
		//$response['request_id'] = $this->utils->getRequestId();		
		//$response['cost_ms'] = $cost;		

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);	
		
		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}
	public function getPlayerByGameUsername($gameUsername){
		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (getPlayerByGameUsername)", $gameUsername);	
		$player = $this->common_token->getPlayerCompleteDetailsByExternalAccountId($gameUsername, $this->game_platform_id);		 

		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByLoginIdAndExternalAccountId($userCode, $loginId){
		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (getPlayerByGameUsername loginId)", $loginId);	
		$this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (getPlayerByGameUsername userCode)", $userCode);	

		$player = $this->common_token->getPlayerCompleteDetailsByExternalAccountIdAndLoginName($userCode, $this->game_platform_id, $loginId);		 

		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function parseRequest(){				
        $request_json = file_get_contents('php://input');       
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE raw:", $request_json); 
		$this->request = json_decode($request_json, true);		
		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = PINNACLE_SEAMLESS_GAME_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('t1lottery_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function formatBalance($balance){
		return floatval($balance);
	}

	public function isNumeric($amount){
		return is_numeric($amount);
	}
	public function isParametersValid($data){
		return true;
	}

	public function generatePlayerToken($gamePlatformId){	
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (bet)");	

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   
		
		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

	public function testGenerateSign($gamePlatformId){	
		
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (testGenerateSign)");

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   

		$signKey=$this->game_api->getApiSignKey();
		$boolean_to_string_on_sign=false;
		$sign=$this->game_api->generateSignatureByParams($arr, ['sign']);

		echo "<br>sign: ".$sign;
	}

	public function testGenerateToken($gamePlatformId){	
		
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (testGenerateToken)");

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   

		$arr = $this->request;

		
		$token=$this->game_api->generatePlayerToken($arr['username']);

		echo "<br>token: ".$token;
	}

	public function getHotEvents($gamePlatformId){	
		
        $this->utils->debug_log("PINNACLE SEAMLESS SERVICE: (testGenerateToken)");
        $gamePlatformId = (int)$gamePlatformId;
		$arr = $this->request;
        $response = [];
		if(!$this->initialize($gamePlatformId)){
            $this->output->set_status_header(500);
            $response['success'] = false;
            $response['message'] = 'Error initializing game api';
            return $this->output->set_content_type('application/json')->set_output(json_encode($response));            
		}   

		$params = $this->request;
        $sports = 'soccer';
        $locale = $oddsFormat = null;
        if(isset($params['sports'])){
            parse_str($params['sports'], $sports);
        }
        if(isset($params['odds_format'])){
            parse_str($params['odds_format'], $oddsFormat);
        }
        if(isset($params['locale'])){
            parse_str($params['locale'], $locale);
        }
        $response = $this->game_api->getHotEvents($sports, $locale, $oddsFormat);
		
        
        //$response['success'] = true;
        //$response['message'] = 'Success';
        //$response['data'] = [];
        $this->output->set_status_header(200);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	private function extractLoginInfo(){
		$playerInfo =  isset($this->request['Actions'][0]['PlayerInfo']) ? $this->request['Actions'][0]['PlayerInfo'] : null;
		if(!$playerInfo){
			return [null, null];
		}
		return [$playerInfo['LoginId'], $playerInfo['UserCode']];
	}


}///END OF FILE////////////