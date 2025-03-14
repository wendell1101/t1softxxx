<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Amb_service_api extends BaseController {	
	
    const SUCCESS = '0x0';
    
    //error codes
    const INVALID_REQUEST_DATA = '0x1';
    const INVALID_AGENT_ID = '0x2';
	const SERVICE_NOT_AVAILABLE = '0x3';
    const INSUFFICIENT_BALANCE = '0x4';
	const DUPLICATE_ROUND_ID = '0x5';
	const TRANSACTION_ALREADY_CANCELLED = '0x6';
	const UNKNOWN_ERROR = '0x7';
	const TRANSACTION_ALREADY_VOIDED = '0x8';
	const IP_NOT_ALLOWED = '0x9';
	
	const ERROR_CODES = [self::INSUFFICIENT_BALANCE,
	self::DUPLICATE_ROUND_ID,
	self::INVALID_REQUEST_DATA,
	self::INVALID_AGENT_ID,
	self::SERVICE_NOT_AVAILABLE,
	self::TRANSACTION_ALREADY_CANCELLED,
	self::TRANSACTION_ALREADY_VOIDED,
	self::IP_NOT_ALLOWED];
	
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::INVALID_REQUEST_DATA=>400,
		self::INVALID_AGENT_ID=>400,
		self::SERVICE_NOT_AVAILABLE=>400,
		self::INSUFFICIENT_BALANCE=>403,
		self::DUPLICATE_ROUND_ID=>403,
		self::TRANSACTION_ALREADY_CANCELLED=>403,
		self::TRANSACTION_ALREADY_VOIDED=>403,
		self::IP_NOT_ALLOWED=>403,
		self::UNKNOWN_ERROR=>400,
	];
	
	const ERROR_CODE_MAP = [
		self::SUCCESS=>0,
		self::INVALID_REQUEST_DATA=>997,
		self::INVALID_AGENT_ID=>998,
		self::SERVICE_NOT_AVAILABLE=>999,
		self::INSUFFICIENT_BALANCE=>800,
		self::DUPLICATE_ROUND_ID=>806,
		self::TRANSACTION_ALREADY_CANCELLED=>807,
		self::TRANSACTION_ALREADY_VOIDED=>807,
		self::IP_NOT_ALLOWED=>999,
		self::UNKNOWN_ERROR=>-1
		
	];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;
	private $response_result_id;
	private $transaction_id;

    private $transaction_for_fast_track = null;

	private $headers;

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','amb_transactions', 'external_system'));
		
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("AMB SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("AMB SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("AMB SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }
        
        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
	
        if(!$this->game_api){
			return false;
        }

		if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
			return false;
		}
        
        $this->currency = $this->game_api->currency;

        $this->amb_transactions->tableName = $this->game_api->original_transactions_table;
		

		return true;
	}

	public function balance($gamePlatformId=null){
        $this->utils->debug_log("AMB SEAMLESS SERVICE: (balance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'balance';
		$params = [];
		$balance = 0;
		$rules = ['username'=>'required','agent'=>'required'];

		try {     

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}   
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}
			
			if($this->game_api->trigger_error_balance_response<>0){
				throw new Exception($this->game_api->trigger_error_balance_response);
			}     
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::INVALID_REQUEST_DATA);
			}  
			
            $params['username'] = $gameUsername = $this->request['username'];
			$params['agent'] = $agentId = $this->request['agent'];
			$params['trans_time'] = $this->trans_time;

            if(!$gameUsername){
                throw new Exception(self::INVALID_REQUEST_DATA);
			}   
            
            //validate agent
            $isValid = $this->isValidAgent($agentId);
			if(!$isValid){			
				throw new Exception(self::INVALID_AGENT_ID);
			}
			
			// get player details
            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$player_status){
                throw new Exception(self::INVALID_REQUEST_DATA);
			}		

			$player_id = $player->player_id;

			$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$player_username,				
				&$balance) {		
					
				$balance = $this->getPlayerBalance($player_username, $player_id);
				if($balance===false){
					return false;
				}
			
				return true;								
			});

			if($trans_success==false){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}

			$error_code = self::SUCCESS;
			$externalResponse = [];
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
            $externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);
			$externalResponse['data']['balance'] = $this->formatBalance($balance);
			sleep($this->game_api->test_delay_balance_response);
			return $this->handleExternalResponse(true, $callType, $this->request, $externalResponse, self::SUCCESS);		

		} catch (Exception $error) {
            $error_code = $error->getMessage();
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
            $externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);
			$externalResponse['data']['balance'] = 0; 				
			return $this->handleExternalResponse(false, $callType, $this->request, $externalResponse, $error_code);
		}		
	}

	public function bet($gamePlatformId=null){	
        $this->utils->debug_log("AMB SEAMLESS SERVICE: (bet)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'bet';

		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'username'=>'required',
			'agent'=>'required',
			'game'=>'required',
			'roundId'=>'required',
			'amount'=>'required',
			'currency'=>'required',
			'refId'=>'required',
			'timestamp'=>'required',
			'outStanding'=>'required',
			'amount'=>'isNumeric',
		];
		
		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
            }  
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}
			
			if($this->game_api->trigger_error_bet_response<>0){
				throw new Exception($this->game_api->trigger_error_bet_response);
			}   
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::INVALID_REQUEST_DATA);
			}

            $params['username'] = $gameUsername = $this->request['username'];
            $params['agent'] = $agentId = $this->request['agent'];
			$params['game'] = $this->request['game'];
			$params['round_id'] = $this->request['roundId'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
            $params['ref_id'] = $this->request['refId'];
			$params['timestamp'] =  $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', strtotime($params['timestamp']));
			$params['out_standing'] = $this->request['outStanding'];  
            
            //validate agent
            $isValid = $this->isValidAgent($agentId);
			if(!$isValid){			
				throw new Exception(self::INVALID_AGENT_ID);
			}

			// get player details
            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$player_status){
                throw new Exception(self::INVALID_REQUEST_DATA);
			}		
			
			$params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("AMB SEAMLESS SERVICE lockAndTransForPlayerBalance",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				return $trans_success;
			});

			if($trans_success==false){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::DUPLICATE_ROUND_ID);
			}

			// insufficient balance
			if($insufficient_balance){						
				throw new Exception(self::INSUFFICIENT_BALANCE);
			}

			$error_code = self::SUCCESS;
			$externalResponse = [];
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
			$externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);			
			$externalResponse['data']['username'] = $game_username;
			$externalResponse['data']['wallet'] = [];
			$externalResponse['data']['wallet']['balance'] = $this->formatBalance($after_balance);            
			$externalResponse['data']['wallet']['lastUpdate'] = $this->getTime();            
			$externalResponse['data']['balance'] = [];
            $externalResponse['data']['balance']['before'] = $this->formatBalance($previous_balance);
            $externalResponse['data']['balance']['after'] = $this->formatBalance($after_balance);
			$externalResponse['data']['refId'] = $params['ref_id'];
			sleep($this->game_api->test_delay_bet_response);
            return $this->handleExternalResponse(true, $callType, $this->request, $externalResponse, self::SUCCESS);		
            
		} catch (Exception $error) {
			$error_code = $error->getMessage();
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
            $externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);				
			return $this->handleExternalResponse(false, $callType, $this->request, $externalResponse, $error_code);
		}		
	}

	public function payout($gamePlatformId=null){	
        $this->utils->debug_log("AMB SEAMLESS SERVICE: (payout)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'payout';

		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'username'=>'required',
			'agent'=>'required',
			'game'=>'required',
			'product'=>'required',
			'roundId'=>'required',
			'amount'=>'required',
			'commission'=>'required',
			'winlose'=>'required',
			'currency'=>'required',
			'refId'=>'required',
			'timestamp'=>'required',
			'outStanding'=>'required',
			'amount'=>'isNumeric',
		];
		
		try {     

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
            }       
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}
			
			if($this->game_api->trigger_error_payout_response<>0){
				throw new Exception($this->game_api->trigger_error_payout_response);
			}        
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::INVALID_REQUEST_DATA);
			}

            $params['username'] = $gameUsername = $this->request['username'];
            $params['agent'] = $agentId = $this->request['agent'];
			$params['game'] = $this->request['game'];
			$params['product'] = $this->request['product'];
			$params['round_id'] = $this->request['roundId'];
			$params['amount'] = $this->request['amount'];
			$params['commission'] = $this->request['commission'];
			$params['winlose'] = $this->request['winlose'];			
			$params['currency'] = $this->request['currency'];
            $params['ref_id'] = $this->request['refId'];
			$params['timestamp'] =  $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', strtotime($params['timestamp']));
			$params['out_standing'] = $this->request['outStanding'];  
            
            //validate agent
            $isValid = $this->isValidAgent($agentId);
			if(!$isValid){			
				throw new Exception(self::INVALID_AGENT_ID);
			}

			// get player details
            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$player_status){
                throw new Exception(self::INVALID_REQUEST_DATA);
			}		
			
			$params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("AMB SEAMLESS SERVICE lockAndTransForPlayerBalance",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				return $trans_success;
			});

			if($trans_success==false){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::DUPLICATE_ROUND_ID);
			}

			// insufficient balance
			if($insufficient_balance){						
				throw new Exception(self::INSUFFICIENT_BALANCE);
			}

			$error_code = self::SUCCESS;
			$externalResponse = [];
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
			$externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);
			
			$externalResponse['data']['username'] = $game_username;
			$externalResponse['data']['wallet'] = [];
			$externalResponse['data']['wallet']['balance'] = $this->formatBalance($after_balance);            
			$externalResponse['data']['wallet']['lastUpdate'] = $this->getTime();            
			$externalResponse['data']['balance'] = [];
            $externalResponse['data']['balance']['before'] = $this->formatBalance($previous_balance);
			$externalResponse['data']['balance']['after'] = $this->formatBalance($after_balance);
			$externalResponse['data']['refId'] = $params['ref_id'];
			sleep($this->game_api->test_delay_payout_response);
            return $this->handleExternalResponse(true, $callType, $this->request, $externalResponse, self::SUCCESS);		
            
		} catch (Exception $error) {
			$error_code = $error->getMessage();
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
            $externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);				
			return $this->handleExternalResponse(false, $callType, $this->request, $externalResponse, $error_code);
		}		
	}

	public function cancel($gamePlatformId=null){	
        $this->utils->debug_log("AMB SEAMLESS SERVICE: (cancel)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'cancel';

		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'username'=>'required',
			'agent'=>'required',
			'game'=>'required',
			'product'=>'required',
			'roundId'=>'required',
			'amount'=>'required',			
			'currency'=>'required',
			'refId'=>'required',
			'timestamp'=>'required',			
			'amount'=>'isNumeric',
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
            }   
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}
			
			if($this->game_api->trigger_error_cancel_response<>0){
				throw new Exception($this->game_api->trigger_error_cancel_response);
			}        
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::INVALID_REQUEST_DATA);
			}

			$isInternal = isset($this->request['is_internal'])?$this->request['is_internal']:false;

            $params['username'] = $gameUsername = $this->request['username'];
            $params['agent'] = $agentId = $this->request['agent'];
			$params['game'] = $this->request['game'];
			$params['product'] = $this->request['product'];
			$params['round_id'] = $this->request['roundId'];
			$params['amount'] = $this->request['amount'];			
			$params['currency'] = $this->request['currency'];
            $params['ref_id'] = $this->request['refId'];
			$params['timestamp'] =  $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', strtotime($params['timestamp']));			
            
            //validate agent
            $isValid = $this->isValidAgent($agentId);
			if(!$isValid){			
				throw new Exception(self::INVALID_AGENT_ID);
			}

			// get player details
            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$player_status){
                throw new Exception(self::INVALID_REQUEST_DATA);
			}		
			
			$params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("AMB SEAMLESS SERVICE lockAndTransForPlayerBalance",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				return $trans_success;
			});

			if($trans_success==false){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::TRANSACTION_ALREADY_CANCELLED);
			}

			// insufficient balance
			if($insufficient_balance){						
				throw new Exception(self::INSUFFICIENT_BALANCE);
			}

			if($isInternal){
				$message = "@all AMB Seamless triggered internal round cancel"."\n";
				$message .= $this->game_api->cancel_callback_url."\n";				
				$message .= json_encode($this->request);
				$this->sendNotificationToMattermost("AMB SEAMLESS SERVICE ($gamePlatformId)", $message, 'warning');
			}

			$error_code = self::SUCCESS;
			$externalResponse = [];
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
			$externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);
			
			$externalResponse['data']['username'] = $game_username;
			$externalResponse['data']['wallet'] = [];
			$externalResponse['data']['wallet']['balance'] = $this->formatBalance($after_balance);            
			$externalResponse['data']['wallet']['lastUpdate'] = $this->getTime();            
			$externalResponse['data']['balance'] = [];
            $externalResponse['data']['balance']['before'] = $this->formatBalance($previous_balance);
			$externalResponse['data']['balance']['after'] = $this->formatBalance($after_balance);
			$externalResponse['data']['refId'] = $params['ref_id'];
			sleep($this->game_api->test_delay_cancel_response);
            return $this->handleExternalResponse(true, $callType, $this->request, $externalResponse, self::SUCCESS);		
            
		} catch (Exception $error) {
			$error_code = $error->getMessage();
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
            $externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);				
			return $this->handleExternalResponse(false, $callType, $this->request, $externalResponse, $error_code);
		}		
	}

	public function void($gamePlatformId=null){	
        $this->utils->debug_log("AMB SEAMLESS SERVICE: (void)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'cancel';

		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'username'=>'required',
			'agent'=>'required',
			'game'=>'required',
			'product'=>'required',
			'roundId'=>'required',
			'amount'=>'required',			
			'currency'=>'required',
			'refId'=>'required',
			'timestamp'=>'required',			
			'amount'=>'isNumeric',
		];
		
		try {        

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
            }   
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}  
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::INVALID_REQUEST_DATA);
			}

            $params['username'] = $gameUsername = $this->request['username'];
            $params['agent'] = $agentId = $this->request['agent'];
			$params['game'] = $this->request['game'];
			$params['product'] = $this->request['product'];
			$params['round_id'] = $this->request['roundId'];
			$params['amount'] = $this->request['amount'];			
			$params['currency'] = $this->request['currency'];
            $params['ref_id'] = $this->request['refId'];
			$params['timestamp'] =  $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', strtotime($params['timestamp']));			
            
            //validate agent
            $isValid = $this->isValidAgent($agentId);
			if(!$isValid){			
				throw new Exception(self::INVALID_AGENT_ID);
			}

			// get player details
            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$player_status){
                throw new Exception(self::INVALID_REQUEST_DATA);
			}		
			
			$params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("AMB SEAMLESS SERVICE lockAndTransForPlayerBalance",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				return $trans_success;
			});

			if($trans_success==false){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::TRANSACTION_ALREADY_CANCELLED);
			}

			// insufficient balance
			if($insufficient_balance){						
				throw new Exception(self::INSUFFICIENT_BALANCE);
			}

			$error_code = self::SUCCESS;
			$externalResponse = [];
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
			$externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);
			
			$externalResponse['data']['username'] = $game_username;
			$externalResponse['data']['wallet'] = [];
			$externalResponse['data']['wallet']['balance'] = $this->formatBalance($after_balance);            
			$externalResponse['data']['wallet']['lastUpdate'] = $this->getTime();            
			$externalResponse['data']['balance'] = [];
            $externalResponse['data']['balance']['before'] = $this->formatBalance($previous_balance);
			$externalResponse['data']['balance']['after'] = $this->formatBalance($after_balance);
			$externalResponse['data']['refId'] = $params['ref_id'];
			sleep($this->game_api->test_delay_cancel_response);
            return $this->handleExternalResponse(true, $callType, $this->request, $externalResponse, self::SUCCESS);		
            
		} catch (Exception $error) {
			$error_code = $error->getMessage();
            $externalResponse['status']['code'] = $this->getResponseCode($error_code);
            $externalResponse['status']['message'] = $this->getErrorSuccessMessage($error_code);				
			return $this->handleExternalResponse(false, $callType, $this->request, $externalResponse, $error_code);
		}		
	}

	private function getTime(){
		$date = new DateTime();
		return gmdate('Y-m-d\TH:i:s\Z', $date->format('U'));
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("AMB SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);	
				return false;
			}


			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("AMB SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
				return false;
			}
		}

		return true;
	}

	public function getErrorSuccessMessage($code){
		$message = '';

        switch ($code) {
			case self::SUCCESS:
				$message = lang('Success');
				break;
			case self::INVALID_REQUEST_DATA:
				$message = lang('Invalid request data');
				break;
			case self::INVALID_AGENT_ID:
				$message = lang('Invalid agent id');
				break;
            case self::SERVICE_NOT_AVAILABLE:
                $message = lang('Service not available');
                break;	
            case self::INSUFFICIENT_BALANCE:
                $message = lang('Balance insufficient');
                break;	
            case self::DUPLICATE_ROUND_ID:
                $message = lang('Duplicate Round Id');
				break;	
			case self::TRANSACTION_ALREADY_CANCELLED:
				$message = lang('Transaction already cancelled');
				break;	
			case self::IP_NOT_ALLOWED:
				$message = lang('IP is not allowed');
				break;			
			default:
				$message = "Unknown";
				break;
		}
		
		return $message;
	}

	public function isValidAgent($agentId){		

		if($this->game_api->agent_id==$agentId){
			return true;
		}
		$this->utils->error_log("AMB SEAMLESS SERVICE: (isValidAgent)", $agentId);
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

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode){
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		if(is_array($response)){
			$response = json_encode($response);
		}
		if(is_array($params)){
			$params = json_encode($params);
		}
		$fields = [
			'player_id'=>(isset($this->player)&&isset($this->player->player_id)?$this->player->player_id:null)
		];
        return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id, 
        	$flag, 
        	$callMethod, 
        	$params, 
        	$response, 
        	$httpStatusCode, 
        	null, 
			is_array($this->headers)?json_encode($this->headers):$this->headers,
			$fields
        );
	}

	//http://admin.og.local/amb_service_api/getGames/5849
	public function getGames($gamePlatformId=null){
		

		try { 
			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}

			$get_bal_req = $this->game_api->queryGameListFromGameProvider();
			echo "<pre>";
			print_r($get_bal_req['data']['games']);
		} catch (Exception $error) {
			echo "error: " . $error->getMessage();
		}
	}

	//http://admin.og.local/amb_service_api/encryptData/5849
	public function encryptData($gamePlatformId=null){
		

		try { 
			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::SERVICE_NOT_AVAILABLE);
			}
			$params = $this->game_api->data_to_encrypt;
			$result = $this->game_api->generateSignature($params);
			echo "<pre>";
			print_r($result);
		} catch (Exception $error) {
			echo "error: " . $error->getMessage();
		}
	}
	
	public function handleExternalResponse($status, $type, $data, $response, $error_code){
        if($error_code<>self::SUCCESS){
            $this->utils->error_log("AMB SEAMLESS SERVICE: (handleExternalResponse)", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code);	
        }else{
            $this->utils->debug_log("AMB SEAMLESS SERVICE: (handleExternalResponse)", $response);	
        }
		
		$httpStatusCode = 400;

		if(isset($error_code) && array_key_exists($error_code, self::HTTP_STATUS_CODE_MAP)){
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$error_code];	
		}

        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $error_code == self::SUCCESS) {
            $this->sendToFastTrack();
        }

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode);	

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	private function getResponseCode($errorCode){
		$code = 999;
		if(array_key_exists($errorCode, self::ERROR_CODE_MAP)){
			$code = self::ERROR_CODE_MAP[$errorCode];	
		}
		return $code;
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
		return [true, $player, $player->game_username, $player->username];
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);		
		if($trans_record<>'bet'){
			//mark bet as settled
			$this->CI->amb_transactions->flagBetSettled($trans_record);
		}	
		$result = $this->CI->amb_transactions->insertIgnoreRow($trans_record);
        $this->transaction_for_fast_track = null;
        if($result) {
            $this->transaction_for_fast_track = $trans_record;
            $this->transaction_for_fast_track['id'] = $this->CI->amb_transactions->getLastInsertedId();
        }
		return $result;
	}

	public function makeTransactionRecord($raw_data){
		$data = [];		
		$data['username'] 			= isset($raw_data['username'])?$raw_data['username']:null;//string
		$data['agent'] 				= isset($raw_data['agent'])?$raw_data['agent']:null;//string
		$data['game'] 				= isset($raw_data['game'])?$raw_data['game']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;//double
		$data['currency'] 			= isset($raw_data['currency'])?$raw_data['currency']:null;//string
		$data['ref_id'] 			= isset($raw_data['ref_id'])?$raw_data['ref_id']:null;//string
		$data['timestamp'] 			= isset($raw_data['timestamp'])?$raw_data['timestamp']:null;//string
		$data['timestamp_parsed'] 	= isset($raw_data['timestamp_parsed'])?$raw_data['timestamp_parsed']:null;//datetime
		$data['out_standing'] 	= isset($raw_data['out_standing'])?$raw_data['out_standing']:null;//double

		//payout
		$data['product'] 			= isset($raw_data['product'])?$raw_data['product']:null;//string
		$data['commission'] 			= isset($raw_data['commission'])?$raw_data['commission']:0;//double
		$data['winlose'] 			= isset($raw_data['winlose'])?$raw_data['winlose']:0;//double

		//additional
		$data['game_platform_id'] 	= $this->game_platform_id;
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;		
		
		$data['status'] 			= $this->getTransactionStatus($data);		
		
		$data['raw_data'] 			= @json_encode($this->request);//text		
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;
		$data['external_uniqueid'] 	= $this->generateUniqueId($data);
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;	
		$data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);
		//$data['updated_at'] 		= date('Y-m-d H:i:s');//datetime	
		//$data['created_at'] 		= date('Y-m-d H:i:s');//datetime	
		
		return $data;
	}

	private function getTransactionStatus($data){
		
		if($data['trans_type']=='cancel'){
			return Game_logs::STATUS_CANCELLED;
		}elseif($data['trans_type']=='payout'){
			return Game_logs::STATUS_SETTLED;
		}elseif($data['trans_type']=='void'){
			return Game_logs::STATUS_VOID;
		}else{
			return Game_logs::STATUS_PENDING;
		}
	}

	private function generateUniqueId($data){
		$uniqueId = isset($data['ref_id'])?$data['ref_id']:null;

		if($data['trans_type']=='cancel'){
			return 'cancel-'.$uniqueId;
		}elseif($data['trans_type']=='payout'){
			return 'payout-'.$uniqueId;
		}elseif($data['trans_type']=='void'){
			return 'void-'.$uniqueId;
		}elseif($data['trans_type']=='bet'){
			return 'bet-'.$uniqueId;
		}else{
			return false;
		}
	}

	public function getErrorCode($code){

		if(!in_array($code, self::ERROR_CODES)){
			$this->utils->error_log("AMB SEAMLESS SERVICE getErrorCode UNKNOWN ERROR:", $this->request, $code); 
			return self::UNKNOWN_ERROR;
		}

		return $code;
	}

	public function parseRequest(){				
        $request_json = file_get_contents('php://input');       
        $this->utils->debug_log("AMB SEAMLESS SERVICE raw:", $request_json); 
		$this->request = json_decode($request_json, true);		
		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = AMB_SEAMLESS_GAME_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('amb_multiple_currency_domain_mapping');
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

	public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance){
		$this->utils->debug_log("AMB SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);

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
		}elseif($params['trans_type']=='cancel'){
			$mode = 'credit';
			$flagrefunded = true;
		}elseif($params['trans_type']=='void'){
			$mode = 'debit';
			if($params['amount']>=0){
				$mode = 'credit';
			}
			$flagrefunded = true;/**/
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
				$this->utils->error_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request, $this->params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//check if round to payout exists
			if($params['trans_type']=='payout'){
				$flagrefunded = true;
				$bet_params = ['player_id'=>$player_id, 'round_id'=>$params['round_id'], 'trans_type'=>'bet'];
				$isToPayoutExist = $this->CI->amb_transactions->getTransactionByParamsArray($bet_params);
				$this->utils->debug_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) isToPayoutExist", $isToPayoutExist , $params);
				if(empty($isToPayoutExist)){
					$this->utils->error_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) bet to payout does not exist", $isToPayoutExist);
				
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}
			}

			//check if round to cancel exists
			if($params['trans_type']=='cancel'){
				$flagrefunded = true;
				$cancel_params = ['player_id'=>$player_id, 'round_id'=>$params['round_id'], 'trans_type'=>'bet', 'status'=>Game_logs::STATUS_PENDING];
				$isToCancelExist = $this->CI->amb_transactions->getTransactionByParamsArray($cancel_params);
				$this->utils->debug_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) isToCancelExist", $isToCancelExist, $params);
				if(empty($isToCancelExist)){
					return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}
				//flag bet cancelled
				$this->CI->amb_transactions->flagBetCancelled($isToCancelExist['external_uniqueid']);
			}

			//check if round to void exists
			if($params['trans_type']=='void'){
				$this->CI->amb_transactions->flagRoundVoid($player_id, $params['round_id']);
			}

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request, $this->params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}	

			$success = $this->transferGameWallet($player_id, $this->game_platform_id, $mode, $amount);

			if(!$success){
				$this->utils->error_log("AMB SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request, $this->params);
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
		//not using querySeamlessSingleWallet this function is for seamless wallet only applicable in GW	
		$get_bal_req = $this->game_api->queryPlayerBalance($playerName);
		$this->utils->debug_log("AMB SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
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

	public function isParametersValid($data){
		return true;
	}

    public function sendNotificationToMattermost($user,$message,$notifType,$texts_and_tags=null){
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

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->game_api->getPlatformCode(), $this->transaction_for_fast_track['game']);
        $betType = null;
        switch($this->transaction_for_fast_track['trans_type']) {
            case 'bet':
                $betType = 'Bet';
                break;
            case 'payout':
                $betType = 'Win';
                break;
            case 'cancel':
                $betType = 'Refund';
                break;
            default:
                $betType = null;
                break;
        }

        if ($betType == null) {
            return;
        }

        $data = [
            "activity_id" =>  strval($this->transaction_for_fast_track['id']),
            "amount" => (float) abs($this->transaction_for_fast_track['amount']),
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->game_api->currency,
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  $this->transaction_for_fast_track['round_id'],
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->game_api->getPlayerIdInGameProviderAuth($this->transaction_for_fast_track['username']),
            "vendor_id" =>  strval($this->game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amount']) : 0,
        ];

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

}///END OF FILE////////////
