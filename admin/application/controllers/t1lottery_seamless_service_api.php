<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * http://admin.brl.staging.smash.t1t.in/t1lottery_seamless_service_api/[API ID]
 */
class T1lottery_seamless_service_api extends BaseController {	
	
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
	const ERROR_PAYOUT_REFUND_EXIST = '0x13';	

	const ERROR_CODES = [
		self::ERROR_SERVICE_NOT_AVAILABLE,
		self::ERROR_INVALID_SIGN,
		self::ERROR_INVALID_PARAMETERS,
		self::ERROR_INSUFFICIENT_BALANCE,
		self::ERROR_SERVER,
		self::ERROR_CANNOT_FIND_PLAYER,
		self::ERROR_TRANSACTION_ALREADY_EXIST,
		self::ERROR_IP_NOT_ALLOWED,
		self::ERROR_BET_DONT_EXIST,
		self::ERROR_GAME_UNDER_MAINTENANCE,
		self::ERROR_CONNECTION_TIMED_OUT,
		self::ERROR_REFUND_PAYOUT_EXIST,
		self::ERROR_PAYOUT_REFUND_EXIST,
	];
	
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_SERVICE_NOT_AVAILABLE=>404,		
		self::ERROR_INVALID_SIGN=>400,
		self::ERROR_INVALID_PARAMETERS=>400,
		self::ERROR_INSUFFICIENT_BALANCE=>406,
		self::ERROR_SERVER=>500,
		self::ERROR_CANNOT_FIND_PLAYER=>400,	
		self::ERROR_TRANSACTION_ALREADY_EXIST=>409,	
		self::ERROR_IP_NOT_ALLOWED=>401,
		self::ERROR_BET_DONT_EXIST=>400,
		self::ERROR_REFUND_PAYOUT_EXIST=>400,
		self::ERROR_PAYOUT_REFUND_EXIST=>400,
		self::ERROR_GAME_UNDER_MAINTENANCE=>503,
		self::ERROR_CONNECTION_TIMED_OUT=>423,
	];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;

    private $transaction_for_fast_track = null;

	private $headers;

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','t1lottery_transactions', 'ip'));
		
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }
        
        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
	
        if(!$this->game_api){
			$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

		$this->game_api->request = $this->request;

        $this->currency = $this->game_api->getCurrency();
		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (initialize) currency: ", $this->currency);
        $this->t1lottery_transactions->tableName = $this->game_api->getTransactionsTable();

		return true;
	}

	public function player_info($gamePlatformId=null){
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (balance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'player_info';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'sign'=>'required',
			'token'=>'required'
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
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if(!empty($this->game_api->trigger_player_info_error_response)){				
				throw new Exception($this->game_api->trigger_player_info_error_response);
			}   

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}		

			$player_id = $player->player_id;

			if($this->game_api->enable_use_readonly_in_get_balance){				
				// $response = $this->game_api->queryPlayerReadonlyBalanceByPlayerId($player_id);

				//$this->utils->error_log('readonlyMainWalletFromDB player_info response', $response);
				/* if(empty($response)||(isset($response['success'])&& $response['success']==false)){
					throw new Exception(self::ERROR_SERVER);
				}
				$success = true;
				$balance = isset($response['balance'])?$response['balance']:0; */

                $balance = $this->player_model->getPlayerSubWalletBalance($player_id, $gamePlatformId);

                if ($balance === null || $balance === false) {
                    throw new Exception(self::ERROR_SERVER);
                }

                $success = true;
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
			$errorCode = self::SUCCESS;
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
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (bet)");	
     
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
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			'amount'=>'required',
			'amount'=>'isNumeric',
			'currency'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'unique_id'=>'required',
			'sign'=>'required',
			'game_code'=>'required',
			'number'=>'required',
			'amount'=>'nonNegative'
		];
		
		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			} 
			  
			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			$currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}   
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}
			
			if(!empty($this->game_api->trigger_bet_error_response)){
				throw new Exception($this->game_api->trigger_bet_error_response);
			}

			// get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
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
			$params['number'] = $this->request['number'];

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
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
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);// to ask if what to return if trans already exist
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $after_balance;
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

	public function payout($gamePlatformId=null){	
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (payout)");	
     
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
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			'amount'=>'required',
			'amount'=>'isNumeric',
			'currency'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'unique_id'=>'required',
			'sign'=>'required',
			'game_code'=>'required',
			'amount'=>'nonNegative'
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}   
			  
			//if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				//throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			//}
			
			$currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}  
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}
			
			if(!empty($this->game_api->trigger_payout_error_response)){
				throw new Exception($this->game_api->trigger_payout_error_response);
			}

            // get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
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
				&$refundExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE lockAndTransForPlayerBalance payout",
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
				if(isset($additionalResponse['refundExist'])){
					$refundExist=$additionalResponse['refundExist'];	
				}
				return $trans_success;
			});

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);// to ask if what to return if trans already exist
			}

			if(!$betExist){				
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}

			if($refundExist){				
				throw new Exception(self::ERROR_PAYOUT_REFUND_EXIST);
			}	

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $after_balance;
            
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

	public function payouts($gamePlatformId=null){	
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (payouts)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'payouts';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$success = false;
		$fileName = $currency = null;
		$rules = [
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'unique_id'=>'required',
			'payouts'=>'required',
			//'sign'=>'required',			
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}   
			
			$currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}  
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!$this->isValidSign($this->request)){
				//throw new Exception(self::ERROR_INVALID_SIGN);
			}

			//save the request to file
			$unique_id = $params['unique_id'] = $this->request['unique_id'];

			if(empty($this->request)){				
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
			$fileName = $this->saveRequestToFile($unique_id, $this->request);			

			if(!$fileName){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
            
		} catch (Exception $error) {
			//notofy MM to monitor failed payout			
			$errorCode = $error->getMessage();
			$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (payouts) ERROR", 'errorCode', $errorCode);	
			$success = false;
		}				
		
		$externalResponse['is_success'] = $success;						
		$externalResponse['currency'] = $currency;
		$externalResponse['fileName'] = $fileName;
		$fields = [];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	public function settle($gamePlatformId=null){	
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (settle)");	
     
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
			'opencode'=>'required'
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
				throw new Exception(self::ERROR_INVALID_SIGN);
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

			if($this->game_api->enable_settle_by_queue){
				//process remote queue
				$this->load->library(['lib_queue']);

				//add it to queue job
				$params['transactions_table'] = $this->t1lottery_transactions->tableName;
				$callerType=Queue_result::CALLER_TYPE_ADMIN;
				$caller=$this->authentication->getUserId();
				$state='';
				$this->load->library(['language_function','authentication']);
				$lang=$this->language_function->getCurrentLanguage();
				$caller = $this->authentication->getUserId();
				$state = null;
				$lang = $this->language_function->getCurrentLanguage();			
				$systemId = Queue_result::SYSTEM_UNKNOWN;
				$funcName = 't1lottery_settle_round';		
				$token =  $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
			}

			$success = true;
			$errorCode = self::SUCCESS;            
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}				
		
		$externalResponse['is_success'] = $success;		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode);	
	}

	public function refund($gamePlatformId=null){	
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (cancel)");	
     
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
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			'amount'=>'required',
			'amount'=>'isNumeric',
			//'currency'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'unique_id'=>'required',
			'sign'=>'required',
			'game_code'=>'required',
			'amount'=>'nonNegative'
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}  
			  
			/*if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}*/

			$currency = $this->currency;
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} 
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}   
			
			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}
			
			if(!empty($this->game_api->trigger_refund_error_response)){
				throw new Exception($this->game_api->trigger_refund_error_response);
			}

			// get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->game_api->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
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
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
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
				throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);// to ask if what to return if trans already exist
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
			$errorCode = self::SUCCESS;
            $balance = $after_balance;
            
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

	protected function isValidSign($request){

		$signKey=$this->game_api->getApiSignKey();
		$boolean_to_string_on_sign=false;
		list($sign, $signString)=$this->common_token->generateSign($request, $signKey, ['sign'], $boolean_to_string_on_sign);

		$requestSign=$request['sign'];

		$this->CI->utils->debug_log('sign string:'.$signString.', sign:'.$sign.', signKey:'.$signKey.', request sign:'.$requestSign);

		return $sign===$requestSign;
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);	
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
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

			case self::ERROR_PAYOUT_REFUND_EXIST:
				return lang('Refund already exist.');	

			case self::ERROR_GAME_UNDER_MAINTENANCE:
				return lang('Game under maintenance.');

			case 'Connection timed out.':
			case self::ERROR_CONNECTION_TIMED_OUT:
				return lang('Connection timed out.');

			default:
				$this->CI->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				return $code;
		}
	}

	public function isValidAgent($agentId){		

		if($this->game_api->agent_id==$agentId){
			return true;
		}
		$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (isValidAgent)", $agentId);
		return false;
    }
    
	//default external response template
	public function externalQueryResponse(){
		return array(
            "status" => [
                "code" => 999,
                "message" => 'ERROR'
            ]
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

	//http://admin.og.local/amb_service_api/getGames/5849
	public function getGames($gamePlatformId=null){
		

		try { 
			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			$get_bal_req = $this->game_api->queryGameListFromGameProvider();
			echo "<pre>";
			print_r($get_bal_req['data']['games']);
		} catch (Exception $error) {
			echo "error: " . $error->getMessage();
		}
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
        $this->CI->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (handleExternalResponse)", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code, 
            'fields', $fields);	
		
		if(strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (handleExternalResponse) Connection timed out.", 
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

	public function getPlayerByToken($token){
		$username = $this->game_api->decrypt($token);
		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (getPlayerByToken)", $username);	
		$player = $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);		 

		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	/*public function getPlayerByUsername($gameUsername){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);		
		if($trans_record['trans_type']=='payout' && $this->game_api->flag_bet_transaction_settled){
			//mark bet as settled
			$this->CI->t1lottery_transactions->flagBetTransactionSettled($trans_record);
		}	
		return $this->CI->t1lottery_transactions->insertIgnoreRow($trans_record);        		
	}

	public function makeTransactionRecord($raw_data){
		$data = [];		
		$data['username'] 			= isset($raw_data['username'])?$raw_data['username']:null;//string
		$data['timestamp'] 			= isset($raw_data['timestamp'])?$raw_data['timestamp']:null;//string
		$data['timestamp_parsed'] 	= isset($raw_data['timestamp_parsed'])?$raw_data['timestamp_parsed']:null;//datetime
		$data['merchant_code'] 		= isset($raw_data['merchant_code'])?$raw_data['merchant_code']:null;//string
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;//double
		$data['currency'] 			= isset($raw_data['currency'])?$raw_data['currency']:null;//string
		$data['game_code'] 			= isset($raw_data['game_code'])?$raw_data['game_code']:null;//string
		//$data['unique_id'] 			= isset($raw_data['unique_id'])?$raw_data['unique_id']:null;//string
		$data['bet_id'] 			= isset($raw_data['bet_id'])?$raw_data['bet_id']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string
		//$data['sign'] 				= isset($raw_data['sign'])?$raw_data['sign']:null;//string
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;//string
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;//string
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;	
		$data['game_platform_id'] 	= $this->game_platform_id;		
		$data['status'] 			= $this->getTransactionStatus($raw_data);		
		$data['raw_data'] 			= @json_encode($this->request);//text				
		$data['external_uniqueid'] 	= $this->generateUniqueId($raw_data);
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;	
		$data['game_platform_id'] 	= $this->game_platform_id;	
		$data['number'] 		= isset($raw_data['number'])?$raw_data['number']:null;	
		$data['opencode'] 		= isset($raw_data['opencode'])?$raw_data['opencode']:null;
		$data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);
		
		return $data;
	}

	private function getTransactionStatus($data){
		
		if($data['trans_type']=='payout'){
			return Game_logs::STATUS_SETTLED;
		}elseif($data['trans_type']=='refund'){
			return Game_logs::STATUS_REFUND;
		}elseif($data['trans_type']=='settle'){//initially pending if processed by cronjob then it will be flag as processed
			return Game_logs::STATUS_PENDING;
		}else{
			return Game_logs::STATUS_PENDING;
		}
	}

	private function generateUniqueId($data){
		if(empty($data['bet_id'])){
			return $data['game_code'] .'-'. $data['round_id'] .'-'. $data['trans_type'];	
		}
		return $data['game_code'] .'-'. $data['bet_id'] .'-'. $data['trans_type'];
	}*/

	public function parseRequest(){				
        $request_json = file_get_contents('php://input');       
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE raw:", $request_json); 
		$this->request = json_decode($request_json, true);		
		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = T1LOTTERY_SEAMLESS_API;
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

	/*
	public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance){
		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);

		//initialize params
		$player_id			= $params['player_id'];				
		$amount 			= abs($params['amount']);
		
		//initialize response
		$success = false;
		$isValidAmount = true;		
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];

		if($params['trans_type']=='bet'){
			$mode = 'debit';
		}elseif($params['trans_type']=='payout'){
			$mode = 'credit';
		}elseif($params['trans_type']=='refund'){
			$mode = 'credit';
			$flagrefunded = true;		
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		if($amount<>0){

			//get and process balance
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}
				
			}else{				
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//check if bet transaction exists
			if($params['trans_type']=='refund' || $params['trans_type']=='payout'){
				$flagrefunded = true;
				$check_bet_params = ['bet_id'=>$params['bet_id'], 'round_id'=>$params['round_id'], 'player_id'=>$player_id, 'trans_type'=>'bet'];
				$betExist = $this->CI->t1lottery_transactions->getTransactionByParamsArray($check_bet_params);
				
				if(empty($betExist)){
					$additionalResponse['betExist']=false;
					$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist", 'betExist', $betExist, 'params',$params, 'check_bet_params', $check_bet_params);
					$afterBalance = $previousBalance;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}		

				$additionalResponse['betExist']=true;	
			}

			if($params['trans_type']=='refund'){	
				$this->CI->t1lottery_transactions->flagTransactionRefunded($betExist['external_uniqueid']);
			}

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);				
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}	

			$success = $this->transferGameWallet($player_id, $this->game_platform_id, $mode, $amount);

			if(!$success){
				$this->utils->error_log("T1LOTTERY SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
			}

		}else{
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;

				//insert transaction
				$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
			}else{
				$success = false;
			}
		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded);
	}

	public function getPlayerBalance($playerName, $player_id){			
		$get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}	
	}

	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount){
		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
			$success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);	
		}elseif($mode=='credit'){
			$success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
		}

		return $success;
	}
	*/

	public function isParametersValid($data){
		return true;
	}

    /*public function sendNotificationToMattermost($user,$message,$notifType,$texts_and_tags=null){
		if(!$this->game_api->enable_mm_channel_nofifications){
			return false;
		}
    	$this->load->helper('mattermost_notification_helper');

    	$notif_message = array(
    		array(
    			'text' => $message,
    			'type' => $notifType
    		)
    	);
    	return sendNotificationToMattermost($user, $this->game_api->mm_channel, $notif_message, $texts_and_tags);
    }
	*/

	public function generatePlayerToken($gamePlatformId){	
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (bet)");	

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   
		
		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

	public function testGenerateSign($gamePlatformId){	
		
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (testGenerateSign)");
		
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

		echo "<br>sign: ".$sign;
	}

	public function testGenerateToken($gamePlatformId){	
		
        $this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (testGenerateToken)");

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   

		$arr = $this->request;

		
		$token=$this->game_api->generatePlayerToken($arr['username']);

		echo "<br>token: ".$token;
	}
	
    public function isIPAllowed(){
		return true;
        $success=false;

        $this->backend_api_white_ip_list=$this->utils->getConfig('backend_api_white_ip_list');

        $success=$this->ip->checkWhiteIpListForAdmin(function ($ip, &$payload){
			$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (isIPAllowed)", $ip);	
            if($this->ip->isDefaultWhiteIP($ip)){
                $this->utils->debug_log('it is default white ip', $ip);
                return true;
            }
            foreach ($this->backend_api_white_ip_list as $whiteIp) {
                if($this->utils->compareIP($ip, $whiteIp)){
                    $this->utils->debug_log('found white ip', $whiteIp, $ip);
                    //found
                    return true;
                }
            }
            //not found
            return false;
        }, $payload);

		$this->utils->debug_log("T1LOTTERY SEAMLESS SERVICE: (isIPAllowed)", $success);	
        return $success;
    }
	public function saveRequestToFile($unique_id, $data, $dir = null) {

		if(empty($data)){
			return false;
		}

		if(empty($dir)){
			$dir=$this->CI->utils->getBatchpayoutSharingUploadPath($this->game_platform_id, date('Ymd').'/'.date('Hi'));			
		}

		$filename = $unique_id . ".json";
		$f = $dir .'/'. $filename;
		$success = file_put_contents($f, json_encode($data));
		if(!$success){
			return false;
		}

		//verify if really exist
		/*if(!empty($f) && file_exists($f)){
			return false;
		}*/

		return $f;
	}


}///END OF FILE////////////