<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Bgsoft_seamless_service_api extends BaseController {

    const SUCCESS_CODE = 0;
    const ERROR_INVALID_SIGNATURE = 1;
    const ERROR_INVALID_MERCHANT_CODE = 2;
    const ERROR_INVALID_SECURE_KEY = 3;
    const ERROR_INVALID_AUTH_TOKEN = 4;
    const ERROR_INVALID_GAME_PLATFORM_ID = 5;
    const ERROR_INVALID_USERNAME = 6;
    const ERROR_INVALID_PASSWORD = 7;
    const ERROR_DUPLICATE_USERNAME = 8;
    const ERROR_INVALID_EXTRA_INFO = 9;
    const ERROR_INVALID_GAME_PLATFORM_SETTINGS = 10;
    const ERROR_INVALID_BET_LIMIT = 11;
    const ERROR_INVALID_ACTION_TYPE = 12;
    const ERROR_INVALID_AMOUNT = 13;
    const ERROR_DUPLICATE_EXTERNAL_TRANS_ID = 14;
    const ERROR_NO_ENOUGH_BALANCE = 15;
    const ERROR_INVALID_FROM_DATE_TIME = 16;
    const ERROR_INVALID_TO_DATE_TIME = 17;
    const ERROR_INVALID_EXTERNAL_TRANS_ID = 18;
    const ERROR_SECURE_KEY_NOT_ALLOWED = 19;
    const ERROR_BONUS_REQUIRED = 20;
    const ERROR_INVALID_BONUS = 21;
    const ERROR_INVALID_REBATE = 22;
    const ERROR_ACCOUNT_DUPLICATED = 23;
    const ERROR_SUB_ACCOUNT_NOT_FOUND = 24;
    const ERROR_INVALID_PARENT = 25;
    const ERROR_REQUEST_DATA_REQUIRED = 26;
    const ERROR_REQUEST_TOO_FAST = 27;
    const ERROR_SERVER = 28;
    const ERROR_GAME_UNDER_MAINTENANCE = 29;
    const ERROR_SERVICE_NOT_AVAILABLE = 30;
    const ERROR_CONNECTION_TIMED_OUT = 31;
    const ERROR_INVALID_PARAMETERS = 32;
    const ERROR_INSUFFICIENT_BALANCE = 33;
    const ERROR_BET_DONT_EXIST = 34;
    const ERROR_REFUND_PAYOUT_EXIST = 35;
    const ERROR_CANNOT_FIND_PLAYER = 36;
	const INVALID_CURRENCY = 37;
	const ERROR_REFUND_ALREADY_EXIST = 38;
	const ERROR_IP_NOT_ALLOWED = 39;


    const HTTP_STATUS_CODE_MAP = [
        self::SUCCESS_CODE => 200, 
        self::ERROR_INVALID_SIGNATURE => 400,
        self::ERROR_INVALID_MERCHANT_CODE => 400,
        self::ERROR_INVALID_SECURE_KEY => 400,
        self::ERROR_INVALID_AUTH_TOKEN => 400,
        self::ERROR_INVALID_GAME_PLATFORM_ID => 400,
        self::ERROR_INVALID_USERNAME => 400,
        self::ERROR_INVALID_PASSWORD => 400,
        self::ERROR_DUPLICATE_USERNAME => 400,
        self::ERROR_INVALID_EXTRA_INFO => 400,
        self::ERROR_INVALID_GAME_PLATFORM_SETTINGS => 400,
        self::ERROR_INVALID_BET_LIMIT => 400,
        self::ERROR_INVALID_ACTION_TYPE => 400,
        self::ERROR_INVALID_AMOUNT => 400,
        self::ERROR_DUPLICATE_EXTERNAL_TRANS_ID => 400,
        self::ERROR_NO_ENOUGH_BALANCE => 400,
        self::ERROR_INVALID_FROM_DATE_TIME => 400,
        self::ERROR_INVALID_TO_DATE_TIME => 400,
        self::ERROR_INVALID_EXTERNAL_TRANS_ID => 400,
        self::ERROR_SECURE_KEY_NOT_ALLOWED => 400,
        self::ERROR_BONUS_REQUIRED => 400,
        self::ERROR_INVALID_BONUS => 400,
        self::ERROR_INVALID_REBATE => 400,
        self::ERROR_ACCOUNT_DUPLICATED => 400,
        self::ERROR_SUB_ACCOUNT_NOT_FOUND => 400,
        self::ERROR_INVALID_PARENT => 400,
        self::ERROR_REQUEST_DATA_REQUIRED => 400,
        self::ERROR_REQUEST_TOO_FAST => 400,
        self::ERROR_SERVER => 500,
        self::ERROR_CONNECTION_TIMED_OUT => 423,
        self::ERROR_INVALID_PARAMETERS => 400,
        self::ERROR_INSUFFICIENT_BALANCE => 406,
        self::ERROR_BET_DONT_EXIST => 400,
        self::ERROR_REFUND_PAYOUT_EXIST => 400,
        self::ERROR_CANNOT_FIND_PLAYER => 400,
		self::INVALID_CURRENCY => 400,
		self::ERROR_REFUND_ALREADY_EXIST => 400,
        self::ERROR_CANNOT_FIND_PLAYER => 400,
		self::ERROR_IP_NOT_ALLOWED => 400
    ];

    public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;
    public $curretPlayer;

    private $headers;

    public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','bgsoft_transactions', 'ip'));
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

    public function parseRequest(){				
        $request_json = file_get_contents('php://input');       
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE raw:", $request_json); 
		$this->request = json_decode($request_json, true);		
		return $this->request;
	}

    public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

    public function externalQueryResponse(){
		return array(
            "status" => [
                "code" => 999,
                "message" => 'ERROR'
            ]
		);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }
        
        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        $this->utils->error_log("BGSOFT SEAMLESS SERVICE: (initialize) game_api", $this->game_api, 'this->game_platform_id', $this->game_platform_id, 'gamePlatformId', $gamePlatformId);
        
	
        if(!$this->game_api){
			$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

		$this->game_api->request = $this->request;

        $this->currency = $this->game_api->getCurrency();
		$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (initialize) currency: ", $this->currency);

		return true;
	}
    
	public function player_summary($gamePlatformId=null){
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (balance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'player_summary';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
            'unique_id'=>'required',
			'timestamp'=>'required',
			'merchant_code'=>'required',
            'username'=>'required',
			'sign'=>'required',
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
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}
            
            // get player details
            $params['username'] = $player_username = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($player_username);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}	

            $use_no_lock_balance_in_api = $this->game_api->use_no_lock_balance_in_api;
            $player_id = $player->player_id;       
            if(!empty($use_no_lock_balance_in_api) && is_array($use_no_lock_balance_in_api) && in_array($callType,$use_no_lock_balance_in_api)){
                
                $balance = $this->game_api->getPlayerBalance($player_username, $player_id);
                $success = true;
                if($balance===false){
                    $success = false;
                }
            }else{

                $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
                $player_username,				
                    &$balance) {		
    
                    $balance = $this->game_api->getPlayerBalance($player_username, $player_id);
                    
                    if($balance===false){
                        return false;
                    }
                
                    return true;								
                });
            }     

			if(!$success){
				throw new Exception(self::ERROR_SERVER);
			}
			$success = true;
			$errorCode = self::SUCCESS_CODE;
			$currency = $this->currency;
			
		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}	

		$externalResponse['is_success'] = $success;
		$externalResponse['username'] = $gameUsername;			
		$externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

    public function bet($gamePlatformId=null){	
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (bet)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'bet';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
            'unique_id'=>'required',
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			// 'amount'=>'required',
			// 'amount'=>'isNumeric',
			'currency'=>'required',
            'game_code'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
            'number'=>'required',
			'sign'=>'required',
			'amount'=>'required|isNumeric|nonNegative'
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			} 
			  
			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			// $currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}   
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}
			
			if(!empty($this->game_api->trigger_bet_error_response)){
				throw new Exception($this->game_api->trigger_bet_error_response);
			}

			// get player currency
			$params['currency'] = $currency = $this->request['currency'];
			if($currency != $this->currency){
				throw new Exception(self::INVALID_CURRENCY);;
			}

			// get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_USERNAME);
			}

            $params['unique_id'] = $this->request['unique_id'];
            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
			$params['bet_id'] = $this->request['bet_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['sign'] =  $this->request['sign'];
			$player_id = $params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;
			$params['number'] = $this->request['number'];

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse)
                 {
                    
				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("BGSOFT SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				return $trans_success;
			});

			if($insufficient_balance){						
				throw new Exception(self::ERROR_NO_ENOUGH_BALANCE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_INVALID_EXTERNAL_TRANS_ID);// to ask if what to return if trans already exist
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS_CODE;
            $balance = $after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}		
		
		$externalResponse['is_success'] = $success;
        $externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);;
		$externalResponse['username'] = $gameUsername;			
		$externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['currency'] = $currency;
		
		/**
		 * #failed bet
		 * special case: 
		 * insufficient balance
		 * bet retry calls
		 */
		if(!$success){
			$externalResponse['need_refund'] = true; #trigger refund webhook
		}
		if(!$success && ($insufficient_balance || $isAlreadyExists)){
			$externalResponse['need_refund'] = false; #dont trigger refund webhook no balance movement
		}

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

    public function payout($gamePlatformId=null){	
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (payout)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'payout';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$currency = null;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$betExist = true;
		$refundExist = false;
		$additionalResponse = [];
		$rules = [
            'unique_id'=>'required',
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			// 'amount'=>'required',
			// 'amount'=>'isNumeric',
			'currency'=>'required',
            'game_code'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
            'number'=>'required',
			'sign'=>'required',
			// 'amount'=>'nonNegative',
			'amount'=>'required|isNumeric|nonNegative'
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}   

			// $currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}  
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}
			
			if(!empty($this->game_api->trigger_payout_error_response)){
				throw new Exception($this->game_api->trigger_payout_error_response);
			}

			// get player currency
			$params['currency'] = $currency = $this->request['currency'];
			if($currency != $this->currency){
				throw new Exception(self::INVALID_CURRENCY);;
			}

            // get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_USERNAME);
			}
            
            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
			$params['bet_id'] = $this->request['bet_id'];
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['number'] = $this->request['number'];
			$params['sign'] =  $this->request['sign'];
			$player_id = $params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

            $trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse,
				&$betExist,
				&$refundExist)
                 {
                    
				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("BGSOFT SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				if(isset($additionalResponse['refundExist'])){
					$refundExist=$additionalResponse['refundExist'];	
				}
				if(isset($additionalResponse['betExist'])){
					$betExist=$additionalResponse['betExist'];	
				}
				return $trans_success;
			});

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_DUPLICATE_EXTERNAL_TRANS_ID);// to ask if what to return if trans already exist
			}

			if(!$betExist){				
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}

			if($refundExist){				
				throw new Exception(self::ERROR_REFUND_ALREADY_EXIST);
			}	

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$success = true;
			$errorCode = self::SUCCESS_CODE;
            $balance = $after_balance;
            
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}				
		
		$externalResponse['is_success'] = $success;	
        $externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);;	
		$externalResponse['username'] = $gameUsername;			
		$externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

    public function refund($gamePlatformId=null){	
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (cancel)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'refund';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$betExist = true;
		$payoutExist = false;
		$additionalResponse = [];
		$rules = [
            'unique_id'=>'required',
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			// 'amount'=>'required',
			// 'amount'=>'isNumeric',
            'currency'=>'required',
            'game_code'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'sign'=>'required',
            'type'=>'required',
			// 'amount'=>'nonNegative',
			'amount'=>'required|isNumeric|nonNegative'
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}  
			  
			/*if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}*/

			// $currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} 
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}   
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}
			
			if(!empty($this->game_api->trigger_refund_error_response)){
				throw new Exception($this->game_api->trigger_refund_error_response);
			}

			// get player currency
			$params['currency'] = $currency = $this->request['currency'];
			if($currency != $this->currency){
				throw new Exception(self::INVALID_CURRENCY);;
			}

			// get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_USERNAME);
			}
            
            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
			$params['bet_id'] = $this->request['bet_id'];
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['sign'] =  $this->request['sign'];
			$player_id = $params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse,
				&$betExist,
				&$payoutExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("BGSOFT SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);
				if(isset($additionalResponse['betExist'])){
					$betExist=$additionalResponse['betExist'];	
				}
				if(isset($additionalResponse['payoutExist'])){
					$payoutExist=$additionalResponse['payoutExist'];	
				}
				return $trans_success;
			});

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_DUPLICATE_EXTERNAL_TRANS_ID);// to ask if what to return if trans already exist
			}	

			if(!$betExist){				
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}	

			if($payoutExist){				
				throw new Exception(self::ERROR_REFUND_PAYOUT_EXIST);
			}		

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$success = true;
			$errorCode = self::SUCCESS_CODE;
            $balance = $after_balance;
            
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}		
		
		$externalResponse['is_success'] = $success;	
        $externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);;	
		$externalResponse['username'] = $gameUsername;			
		$externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);			
	}

    public function settle($gamePlatformId=null){	
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (settle)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'settle';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$success = false;
		$previous_balance = $after_balance = 0;
		$rules = [
			'unique_id'=>'required',
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'game_code'=>'required',
			'round_id'=>'required',
			'sign'=>'required',
			//'opencode'=>'required'
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
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}
			
			if(!empty($this->game_api->trigger_settle_error_response)){
				throw new Exception($this->game_api->trigger_settle_error_response);
			}
            
            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];			
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['sign'] =  $this->request['sign'];
			$params['trans_type'] = $callType;
			$params['currency'] = $this->currency;
			$params['opencode'] = isset($this->request['opencode'])?$this->request['opencode']:null;

			//insert transaction
			$this->game_api->insertIgnoreTransactionRecord($params, $previous_balance, $after_balance);

			$success = true;
			$errorCode = self::SUCCESS_CODE;            
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}				
		
		$externalResponse['is_success'] = $success;		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode);	
	}

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->CI->utils->debug_log("BGSOFT SEAMLESS SERVICE: (handleExternalResponse)", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code, 
            'fields', $fields);	
		
		if(strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log("BGSOFT SEAMLESS SERVICE: (handleExternalResponse) Connection timed out.", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code, 
            'fields', $fields);	
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}
		
		$httpStatusCode = $this->getHttpStatusCode($error_code);

		$response['err_msg'] = $this->getErrorSuccessMessage($error_code);

		//add request_id
		if(empty($response)){
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);
		$response['request_id'] = $this->utils->getRequestId();		
		$response['cost_ms'] = $cost;		

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);	
		
		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

    protected function isValidSign($request){

		$signKey=$this->game_api->getApiSignKey();
		$boolean_to_string_on_sign=false;
		list($sign, $signString)=$this->common_token->generateSign($request, $signKey, ['sign'], $boolean_to_string_on_sign);

		$requestSign=$request['sign'];

		$this->CI->utils->debug_log('sign string:'.$signString.', sign:'.$sign.', signKey:'.$signKey.', request sign:'.$requestSign);

		return $sign===$requestSign;
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
        
    public function getValidPlatformId(){
		$this->game_platform_id = BGSOFT_SEAMLESS_GAME_API;
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (initialize) GAME_PLATFORM: ", $this->game_platform_id);
		return;
	}

    public function getErrorSuccessMessage($code){
		$message = '';		

		if(!array_key_exists($code, self::HTTP_STATUS_CODE_MAP)){
			$message = $code;		
			return $message;
		}
        switch ($code) {

			case self::SUCCESS_CODE:
				return lang('Success');

			case self::ERROR_INVALID_SIGNATURE:
				return lang('Invalid signature');

			case self::ERROR_INVALID_PARAMETERS:
				return lang('Invalid parameters');

            case self::ERROR_SERVICE_NOT_AVAILABLE:
                return lang('Service not available');	

            case self::ERROR_INSUFFICIENT_BALANCE:
                return lang('Insufficient Balance');

			case self::ERROR_NO_ENOUGH_BALANCE:
				return lang('Insufficient Balance');

			case self::ERROR_SERVER:
				return lang('Server Error');
				
			case self::ERROR_IP_NOT_ALLOWED:
				return lang('IP is not allowed');	

			case self::ERROR_DUPLICATE_EXTERNAL_TRANS_ID:
				return lang('Transactions already exists.');	

            case self::ERROR_CANNOT_FIND_PLAYER:
				return lang('Cannot find player.');
			case self::ERROR_INVALID_USERNAME:
				return lang('Cannot find player.');

			case self::ERROR_BET_DONT_EXIST:
				return lang('Bet dont exist.');		

			case self::ERROR_REFUND_PAYOUT_EXIST:
				return lang('Payout already exist.');	

			case self::ERROR_REFUND_ALREADY_EXIST;
				return lang('Refund already exist.');	

			case self::ERROR_GAME_UNDER_MAINTENANCE:
				return lang('Game under maintenance.');

			case self::INVALID_CURRENCY:
				return lang('Invalid Currency');

			case self::ERROR_INVALID_EXTERNAL_TRANS_ID:
				return lang('Transaction Already Exist!');

			case 'Connection timed out.':
			case self::ERROR_CONNECTION_TIMED_OUT:
				return lang('Connection timed out.');

			default:
				$this->CI->utils->error_log("BGSOFT SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				return $code;
		}
	}

    private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			$key_rules = explode("|", $rule);
			if(!empty($key_rules)){
				foreach ($key_rules as $keyi => $key_rule) {
					if($key_rule=='required'&&!isset($request[$key])){
						$this->utils->error_log("BGSOFT SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);	
						return false;
					}

					if($key_rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
						$this->utils->error_log("BGSOFT SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
						return false;
					}

					if($key_rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
						$this->utils->error_log("BGSOFT SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
						return false;
					}
				}
			} else {
				return false;
			}
		}

		return true;
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

    public function formatBalance($balance){
		return floatval($balance);
	}

    public function isNumeric($amount){
		return is_numeric($amount);
	}

    public function testGenerateSign($gamePlatformId){	
		
        $this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (testGenerateSign)");
		
		/*$rawjson = '{
			"token": "WUtzeHZjc0RvM0JvcHNneWtzcklOZz09",
			"unique_id": "34d3b48d-5c4a-4a71-af52-339a4a74da96",
			"merchant_code": "3d9b1a89471f44cdb9b8b71a7d64ddba",
			"timestamp": 1626665637,
			"sign": "6e784d6b1b46b0716abd81f211cc79c8e3ed4018"
		}';*/

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   

		$signKey=$this->game_api->getApiSignKey();
		$boolean_to_string_on_sign=false;
		$sign=$this->game_api->generateSignatureByParams($arr, ['sign']);

        return $this->output->set_content_type('application/json')->set_output(json_encode(['sign' => $sign]));
	}
}