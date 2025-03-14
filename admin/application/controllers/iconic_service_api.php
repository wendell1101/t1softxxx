<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Iconic_service_api extends BaseController {	
	
    const SUCCESS = 0;
    const WRONG_TOKEN = 1;
    const NOT_ENOUGH_BALANCE = 2;
    const SOMETHING_WRONG_IN_PARAMETERS = 3;
    const WRONG_HASH = 4;
	const UNDEFINED_ERRORS = 999;
	const IP_NOT_ALLOWED = 888;
	const GAME_MAINTENANCE = 777;
	
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::WRONG_TOKEN=>401,
		self::NOT_ENOUGH_BALANCE=>400,
		self::SOMETHING_WRONG_IN_PARAMETERS=>400,
		self::WRONG_HASH=>400,
		self::UNDEFINED_ERRORS=>400,
		self::IP_NOT_ALLOWED=>401,
		self::GAME_MAINTENANCE=>503
	];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;
	private $response_result_id;
	private $transaction_id;
    private $amount;
    private $show_hash_code;

    private $transaction_for_fast_track = null;

	private $headers;

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','iconic_transactions'));
		
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];
		
		$this->getValidPlatformId();

		$this->parseRequest();

		$this->retrieveHeaders();

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

        $this->transaction_id = null;
        $this->amount = 0;
        $this->show_hash_code = $this->game_api->show_hash_code;

		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function authenticate(){
        $this->utils->debug_log("ICONIC SEAMLESS SERVICE: (authenticate)");	
     
		$externalResponse = $this->externalQueryResponse();	
			
		$request_data = $this->input->get();

		try {

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			} 

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_api->getPlatformCode())) {
                throw new Exception(self::GAME_MAINTENANCE);
            }

            $token = $this->input->get('token');

            if(!$token){
                throw new Exception(self::WRONG_TOKEN);
            }            

            list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token, false);

            if(!$player_status){
                throw new Exception(self::WRONG_TOKEN);
			}
			
			$externalResponse['data']['statusCode'] = self::SUCCESS;
			$externalResponse['data']['username']   = $game_username;			

			$get_balance = $this->getPlayerBalance($player_name);
			if($get_balance===false){
				throw new Exception(self::UNDEFINED_ERRORS);
			}

			$externalResponse['data']['balance']    = $this->convertBalanceToGame($get_balance);
			$externalResponse['data']['hash']   	= md5($this->game_api->secure_code.$externalResponse['data']['username'].$externalResponse['data']['balance']);			
			return $this->handleExternalResponse(true, 'authenticate', $request_data, $externalResponse);		

		} catch (Exception $error) {
			$error_code = $error->getMessage();
			$externalResponse['data']['statusCode'] = $error_code;
			$externalResponse['data']['balance'] = 0;
            $externalResponse['error']['title'] = $this->getErrorSuccessMessage($error_code);
            $externalResponse['error']['description'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, 'authenticate', $request_data, $externalResponse);
		}		
	}

	public function balance(){
        $this->utils->debug_log("ICONIC SEAMLESS SERVICE: (authenticate)");	
     
		$externalResponse = $this->externalQueryResponse();	
			
		$request_data = $this->input->get();

		$game_username = '';

		try {      

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			} 

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::GAME_MAINTENANCE);
            }
			      
            $token = $this->input->get('token');

            if(!$token){
                throw new Exception(self::WRONG_TOKEN);
            }            

            list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token, true);

            if(!$player_status){
                throw new Exception(self::WRONG_TOKEN);
            }
			
			$request_data = $this->input->get();
			
			$externalResponse['data']['statusCode'] = self::SUCCESS;
			$externalResponse['data']['username']   = $game_username;	
			
			$get_balance = $this->getPlayerBalance($player_name);
			if($get_balance===false){
				throw new Exception(self::UNDEFINED_ERRORS);
			}
			
			$externalResponse['data']['balance']    = $this->convertBalanceToGame($get_balance);
			$externalResponse['data']['hash']   	= md5($this->game_api->secure_code.$externalResponse['data']['username'].$externalResponse['data']['balance']);			
			return $this->handleExternalResponse(true, 'balance', $request_data, $externalResponse);		

		} catch (Exception $error) {
			$error_code = $error->getMessage();
			$externalResponse['data']['statusCode'] = $error_code;
			$externalResponse['data']['username']   = $game_username;	
			$externalResponse['data']['balance'] = 0;
            $externalResponse['error']['title'] = $this->getErrorSuccessMessage($error_code);
            $externalResponse['error']['description'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, 'balance', $request_data, $externalResponse);
		}		
	}

	public function bet(){		

		//check request type
		$transaction_type = '';
		$mode = 'debit';
		if($this->method=='POST'){
			$transaction_type = 'bet';
		}elseif($this->method=='DELETE'){
			$transaction_type = 'cancelBet';
			$mode = 'credit';
		}else{
			return $this->invalidRequest(self::SOMETHING_WRONG_IN_PARAMETERS, $transaction_type);
		}

		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: ($transaction_type)", $transaction_type);
		
		//initialize
		$previous_balance = $after_balance = 0;
		$additionalResponse = '';
		$isAlreadyExists = false;
		$insufficient_balance = false;  

		if(!$this->game_api->validateWhiteIP()){			
			return $this->invalidRequest(self::IP_NOT_ALLOWED, $transaction_type);
		} 

        if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
            return $this->invalidRequest(self::GAME_MAINTENANCE, $transaction_type);
        }

		//check token
		$token = $this->request['token'];
		if(!$token){			
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}          

		list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token, true);

		if(!$player_status){
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}

		$params = [
			'player_id'			=>	$player->player_id,
			'player_name'		=>	$player_name,

			'token'				=>	$this->request['token'],
			'updated_time'		=>	isset($this->request['updatedTime'])?$this->request['updatedTime']:$this->CI->utils->getNowForMysql(),
			'bet_time'			=>	isset($this->request['betTime'])?$this->request['betTime']:$this->CI->utils->getNowForMysql(),
			'transaction_id'	=>	$this->request['transactionId'],
			'round_id'			=>	$this->request['roundId'],
			'game_id'			=>	$this->request['gameId'],
			'product_id'		=>	$this->request['productId'],
			'amount'			=>	$this->request['amount'],
			'hash'				=>	$this->request['hash'],
			'transaction_type' 	=> $transaction_type,
			'extra_info'		=> json_encode($this->request, true)
		];

        $this->transaction_id = $params['transaction_id'];
        $this->amount = $params['amount'];
		$externalResponse = $this->externalQueryResponse();
		$externalResponse['data']['username']   = $player->game_username;

		//validate hash
		$isValid = $this->isHashValid($this->request['hash'], [$this->game_api->secure_code,$params['transaction_id'],$params['amount']]);
		if(!$isValid){			
			return $this->invalidRequest(self::WRONG_HASH, $transaction_type);
		}

		//get balance, transaction, update balance
		$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player, 
			$mode,
			$params,
			&$insufficient_balance, 
			&$previous_balance, 
			&$after_balance, 
			&$isAlreadyExists,
			&$additionalResponse) {

			list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $mode, $previous_balance, $after_balance);

			return $trans_success;
		});

		$externalResponse['data']['balance']    = $this->convertBalanceToGame($after_balance);
		$externalResponse['data']['hash']   	= md5($this->game_api->secure_code.$externalResponse['data']['username'].$externalResponse['data']['balance']);			

		//check if already exist
		if($isAlreadyExists){						
			$externalResponse['data']['statusCode'] = self::SUCCESS;
			return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);		
		}

		//check if process is success
		if(!$trans_success){
			$error_code = self::UNDEFINED_ERRORS;
			if($insufficient_balance){				
				$error_code = self::NOT_ENOUGH_BALANCE;
			}			
			$externalResponse['data']['statusCode'] = $error_code;			
			return $this->handleExternalResponse(false, $transaction_type, $this->request, $externalResponse);		
		}

		$externalResponse['data']['statusCode'] = self::SUCCESS;
		
		return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);		
	}

	public function invalidRequest($error_code, $type){
		$externalResponse = $this->externalQueryResponse();	
		$after_balance = 0;
		$game_username = '';

		$externalResponse['data']['statusCode'] = $error_code;
		$externalResponse['data']['username']   = $game_username;	
		$externalResponse['data']['balance']    = $this->convertBalanceToGame($after_balance);
		$externalResponse['error']['title'] = $this->getErrorSuccessMessage($error_code);
		$externalResponse['error']['description'] = $this->getErrorSuccessMessage($error_code);
		return $this->handleExternalResponse(false, $type, $this->request, $externalResponse);
	}

	public function win(){

		//check request type
		$transaction_type = '';
		$mode = 'credit';
		if($this->method=='POST'){
			$transaction_type = 'win';		
		}else{
			return $this->invalidRequest(self::SOMETHING_WRONG_IN_PARAMETERS, $transaction_type);
		}

		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: ($transaction_type)", $transaction_type);
		
		//initialize
		$previous_balance = $after_balance = 0;
		$additionalResponse = '';
		$isAlreadyExists = false;
		$insufficient_balance = false;

		if(!$this->game_api->validateWhiteIP()){			
			return $this->invalidRequest(self::IP_NOT_ALLOWED, $transaction_type);
		} 

        if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
            return $this->invalidRequest(self::GAME_MAINTENANCE, $transaction_type);
        }

		//check token
		$token = $this->request['token'];
		if(!$token){			
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}    

		list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token, true);

		if(!$player_status){
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}

		$params = [
			'player_id'			=>	$player->player_id,
			'player_name'		=>	$player_name,

			'token'				=>	$this->request['token'],
			'updated_time'		=>	isset($this->request['updatedTime'])?$this->request['updatedTime']:$this->CI->utils->getNowForMysql(),
			'bet_time'			=>	isset($this->request['betTime'])?$this->request['betTime']:$this->CI->utils->getNowForMysql(),
			'transaction_id'	=>	$this->request['transactionId'],
			'round_id'			=>	$this->request['roundId'],
			'game_id'			=>	$this->request['gameId'],
			'product_id'		=>	$this->request['productId'],
			'amount'			=>	$this->request['amount'],
			'hash'				=>	$this->request['hash'],
			'transaction_type' 	=> $transaction_type,
			'extra_info'		=> json_encode($this->request, true)
		];

        $this->transaction_id = $params['transaction_id'];
        $this->amount = $params['amount'];
		$externalResponse = $this->externalQueryResponse();
		$externalResponse['data']['username']   = $player->game_username;
		
		//validate hash
		$isValid = $this->isHashValid($this->request['hash'], [$this->game_api->secure_code,$params['transaction_id'],$params['amount']]);
		if(!$isValid){			
			return $this->invalidRequest(self::WRONG_HASH, $transaction_type);
		}

		//get balance, transaction, update balance
		$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player, 
			$mode,
			$params,
			&$insufficient_balance, 
			&$previous_balance, 
			&$after_balance, 
			&$isAlreadyExists,
			&$additionalResponse) {

			list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $mode, $previous_balance, $after_balance);

			return $trans_success;
		});

		$externalResponse['data']['balance']    = $this->convertBalanceToGame($after_balance);
		$externalResponse['data']['hash']   	= md5($this->game_api->secure_code.$externalResponse['data']['username'].$externalResponse['data']['balance']);			

		//check if already exist
		if($isAlreadyExists){						
			$externalResponse['data']['statusCode'] = self::SUCCESS;
			return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);		
		}

		//check if process is success
		if(!$trans_success){
			$externalResponse['data']['statusCode'] = self::UNDEFINED_ERRORS;
			return $this->handleExternalResponse(false, $transaction_type, $this->request, $externalResponse);		
		}

		$externalResponse['data']['statusCode'] = self::SUCCESS;
		
		return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);
	}

	public function deposit(){		

		//check request type
		$transaction_type = '';
		$mode = 'debit';
		if($this->method=='POST'){
			$transaction_type = 'deposit';
		}elseif($this->method=='DELETE'){
			$transaction_type = 'cancelDeposit';
			$mode = 'credit';
		}else{
			return $this->invalidRequest(self::SOMETHING_WRONG_IN_PARAMETERS, $transaction_type);
		}

		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: ($transaction_type)", $transaction_type);
		
		//initialize
		$previous_balance = $after_balance = 0;
		$additionalResponse = '';
		$isAlreadyExists = false;
		$insufficient_balance = false;

		if(!$this->game_api->validateWhiteIP()){			
			return $this->invalidRequest(self::IP_NOT_ALLOWED, $transaction_type);
		} 

        if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
            return $this->invalidRequest(self::GAME_MAINTENANCE, $transaction_type);
        }

		//check token
		$token = $this->request['token'];
		if(!$token){			
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}          

		list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token, true);

		if(!$player_status){
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}

		$params = [
			'player_id'			=>	$player->player_id,
			'player_name'		=>	$player_name,

			'token'				=>	$this->request['token'],
			'updated_time'		=>	isset($this->request['updatedTime'])?$this->request['updatedTime']:$this->CI->utils->getNowForMysql(),
			'bet_time'			=>	isset($this->request['betTime'])?$this->request['betTime']:$this->CI->utils->getNowForMysql(),
			'transaction_id'	=>	$this->request['transactionId'],
			'round_id'			=>	null,
			'game_id'			=>	null,
			'product_id'		=>	null,
			'amount'			=>	$this->request['amount'],
			'hash'				=>	$this->request['hash'],
			'transaction_type' 	=> $transaction_type,
			'extra_info'		=> json_encode($this->request, true)
		];

        $this->transaction_id = $params['transaction_id'];
        $this->amount = $params['amount'];
		$externalResponse = $this->externalQueryResponse();
		$externalResponse['data']['username']   = $player->game_username;

		//validate hash
		$isValid = $this->isHashValid($this->request['hash'], [$this->game_api->secure_code,$params['transaction_id'],$params['amount']]);
		if(!$isValid){			
			return $this->invalidRequest(self::WRONG_HASH, $transaction_type);
		}

		//get balance, transaction, update balance
		$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player, 
			$mode,
			$params,
			&$insufficient_balance, 
			&$previous_balance, 
			&$after_balance, 
			&$isAlreadyExists,
			&$additionalResponse) {

			list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $mode, $previous_balance, $after_balance);

			return $trans_success;
		});

		$externalResponse['data']['balance']    = $this->convertBalanceToGame($after_balance);
		$externalResponse['data']['hash']   	= md5($this->game_api->secure_code.$externalResponse['data']['username'].$externalResponse['data']['balance']);			

		//check if already exist
		if($isAlreadyExists){						
			$externalResponse['data']['statusCode'] = self::SUCCESS;
			return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);		
		}

		if(!$trans_success){
			$error_code = self::UNDEFINED_ERRORS;
			if($insufficient_balance){				
				$error_code = self::NOT_ENOUGH_BALANCE;
			}			
			$externalResponse['data']['statusCode'] = $error_code;			
			return $this->handleExternalResponse(false, $transaction_type, $this->request, $externalResponse);		
		}

		$externalResponse['data']['statusCode'] = self::SUCCESS;
		
		return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);		
	}

	public function withdraw(){

		//check request type
		$transaction_type = '';
		$mode = 'credit';
		if($this->method=='POST'){
			$transaction_type = 'withdraw';		
		}else{
			return $this->invalidRequest(self::SOMETHING_WRONG_IN_PARAMETERS, $transaction_type);
		}

		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: ($transaction_type)", $transaction_type);
		
		//initialize
		$previous_balance = $after_balance = 0;
		$additionalResponse = '';
		$isAlreadyExists = false;
		$insufficient_balance = false;

		if(!$this->game_api->validateWhiteIP()){			
			return $this->invalidRequest(self::IP_NOT_ALLOWED, $transaction_type);
		} 

        if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
            return $this->invalidRequest(self::GAME_MAINTENANCE, $transaction_type);
        }

		//check token
		$token = $this->request['token'];
		if(!$token){			
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}    

		list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token, true);

		if(!$player_status){
			return $this->invalidRequest(self::WRONG_TOKEN, $transaction_type);
		}

		$params = [
			'player_id'			=>	$player->player_id,
			'player_name'		=>	$player_name,

			'token'				=>	$this->request['token'],
			'updated_time'		=>	isset($this->request['updatedTime'])?$this->request['updatedTime']:$this->CI->utils->getNowForMysql(),
			'bet_time'			=>	isset($this->request['betTime'])?$this->request['betTime']:$this->CI->utils->getNowForMysql(),
			'transaction_id'	=>	$this->request['transactionId'],
			'round_id'			=>	null,
			'game_id'			=>	null,
			'product_id'		=>	null,
			'amount'			=>	$this->request['amount'],
			'hash'				=>	$this->request['hash'],
			'transaction_type' 	=> $transaction_type,
			'extra_info'		=> json_encode($this->request, true)
		];

        $this->transaction_id = $params['transaction_id'];
        $this->amount = $params['amount'];
		$externalResponse = $this->externalQueryResponse();
		$externalResponse['data']['username']   = $player->game_username;
		
		//validate hash
		$isValid = $this->isHashValid($this->request['hash'], [$this->game_api->secure_code,$params['transaction_id'],$params['amount']]);
		if(!$isValid){			
			return $this->invalidRequest(self::WRONG_HASH, $transaction_type);
		}

		//get balance, transaction, update balance
		$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player, 
			$mode,
			$params,
			&$insufficient_balance, 
			&$previous_balance, 
			&$after_balance, 
			&$isAlreadyExists,
			&$additionalResponse) {

			list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $mode, $previous_balance, $after_balance);

			return $trans_success;
		});

		$externalResponse['data']['balance']    = $this->convertBalanceToGame($after_balance);
		$externalResponse['data']['hash']   	= md5($this->game_api->secure_code.$externalResponse['data']['username'].$externalResponse['data']['balance']);			

		//check if already exist
		if($isAlreadyExists){						
			$externalResponse['data']['statusCode'] = self::SUCCESS;
			return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);		
		}

		//check if process is success
		if(!$trans_success){
			$externalResponse['data']['statusCode'] = self::UNDEFINED_ERRORS;
			return $this->handleExternalResponse(false, $transaction_type, $this->request, $externalResponse);		
		}

		$externalResponse['data']['statusCode'] = self::SUCCESS;
		
		return $this->handleExternalResponse(true, $transaction_type, $this->request, $externalResponse);
	}

	public function isHashValid($hash, $data){		
		$generatedHash = $this->generateHash($data);

		if($hash==$generatedHash){
			return true;
		}

		return false;
	}

	public function generateHash($data){
		$hash = implode('', $data);
		return md5($hash);
	}

	//default external response template
	public function externalQueryResponse(){
		return array(
			"data" => array(
				"statusCode" => 999,
				"username" => null,
				"balance" => null
            ),
			"error" => array(
				"title" => '',
				"description" => ''
            ),
		);
	}

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $fields = []){
		$cost = intval($this->utils->getExecutionTimeToNow()*1000);

        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id, 
        	$flag, 
        	$callMethod, 
        	json_encode($params), 
        	json_encode($response), 
        	$httpStatusCode, 
        	null, 
			is_array($this->headers)?json_encode($this->headers):$this->headers,
			$fields,
			false,
			null,
			$cost
        );
	}
	
	public function handleExternalResponse($status, $type, $data, $response){
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (handleExternalResponse)", $response);	
		
		$httpStatusCode = 400;

		if(isset($response['data']['statusCode']) && array_key_exists($response['data']['statusCode'], self::HTTP_STATUS_CODE_MAP)){
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$response['data']['statusCode']];	
		}

		if($response['data']['statusCode']==0){
			unset($response['error']);
		}else{
			$response['error']['title'] = $this->getErrorSuccessMessage($response['data']['statusCode']);
            $response['error']['description'] = $this->getErrorSuccessMessage($response['data']['statusCode']);
		}

		$response['data']['statusCode'] = intval($response['data']['statusCode']);

		if(isset($response['data']['balance'])){
			$response['data']['balance'] = intval($response['data']['balance']);
		}		

        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $status == self::SUCCESS) {
            $this->sendToFastTrack();
        }

		
		$fields = [];		
		if($this->player&&isset($this->player->player_id)){
			$fields['player_id'] = isset($this->player->player_id)?$this->player->player_id:null;		
		}
		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, $fields);	

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	public function getPlayerByToken($token, $refreshTimout = false){
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (getPlayerByToken)", $token);	
        $token = urldecode($token);
		if(strpos($token, 'token:')!== false){
			//using new token
			//decrypt			
			$token = str_replace('token:','',$token);//remove prefix
			$username = $this->game_api->decrypt($token);
			$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (getPlayerByToken)", $username);	
			$player = $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);		 
		
			if(!$player){		
				return [false, null, null, null];
			}
			$this->player = $player;
			return [true, $player, $player->game_username, $player->username];
		}else{
			$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id, $refreshTimout);		 
		
			if(!$player){		
				return [false, null, null, null];
			}
			$this->player = $player;
			return [true, $player, $player->game_username, $player->username];
		}
		
	}

	public function insertTransactionRecord($data){

		$trans_id = false;

		$trans_record = $this->makeTransactionRecord($data);	

		$isTransactionExist = $this->iconic_transactions->isTransactionExist($this->getValidPlatformId(), $trans_record['external_unique_id']);

		if(!$isTransactionExist){
			$trans_id = $this->CI->iconic_transactions->insertRow($trans_record);
		}
		return $trans_id;
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_record = $trans_record = $this->makeTransactionRecord($data);
		$result = $this->CI->iconic_transactions->insertIgnoreRow($trans_record);

        $this->transaction_for_fast_track = null;
        if($result) {
            $this->transaction_for_fast_track = $trans_record;
            $this->transaction_for_fast_track['id'] = $this->CI->iconic_transactions->getLastInsertedId();
        }
		return $result;
	}

	public function generateUniqueTransactionId($data){
		$generatedUniqueId = $data['player_id'].'-'.$data['transaction_type'].'-'.$data['transaction_id'];
		return $generatedUniqueId;
	}

	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['game_platform_id'] 	= $this->game_platform_id;
		$data['transaction_id'] 	= isset($raw_data['transaction_id'])?$raw_data['transaction_id']:null;
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;
		$data['game_id'] 			= isset($raw_data['product_id'])?$raw_data['product_id']:null;
		$data['transaction_type'] 	= isset($raw_data['transaction_type'])?$raw_data['transaction_type']:null;
		$data['round_id'] 	        = isset($raw_data['round_id'])?$raw_data['round_id']:null;
		$data['status'] 			= 'Valid';
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;		
		$data['extra_info'] 		= @json_encode($raw_data['extra_info']);
		$data['start_at'] 			= isset($raw_data['bet_time'])?$raw_data['bet_time']:null;
		$data['end_at'] 			= isset($raw_data['updated_time'])?$raw_data['updated_time']:null;

		$generatedUniqueId = $this->generateUniqueTransactionId($data);
		$data['external_unique_id'] = $generatedUniqueId;

		$generatedHash = $this->generateHash([
				$data['before_balance'],
				$data['after_balance'],
				$data['game_id'],
				$data['amount']
			]);

		$data['md5_sum'] = $generatedHash;
		

		return $data;
	}

	public function getErrorSuccessMessage($code){
		$message = '';

		switch ($code) {
			case self::SUCCESS:
				$message = lang('Success.');
				break;
			case self::WRONG_TOKEN:
				$message = lang('Wrong Token.');
				break;
			case self::NOT_ENOUGH_BALANCE:
				$message = lang('Not Enough Balance.');
				break;
            case self::SOMETHING_WRONG_IN_PARAMETERS:
                $message = lang('Something Wrong In Parameters.');
                break;
			case self::WRONG_HASH:
				if ($this->show_hash_code) {
                    $message = lang('Wrong Hash. Generated Hash:' . $this->generateHash([$this->game_api->secure_code, $this->transaction_id, $this->amount]));
                } else {
                    $message = lang('Wrong Hash.');
                }
				break;
			case self::UNDEFINED_ERRORS:
				$message = lang('Undefined Errors.');
				break;
			case self::IP_NOT_ALLOWED:
				$message = lang("You IP is not allowed to access this API.");
				break;
            case self::GAME_MAINTENANCE:
				$message = lang('Game under maintenance or disabled');
				break;
			default:
				$message = "Unknown";
				break;
		}
		
		return $message;
	}

	public function parseRequest(){				
		$request_json = file_get_contents('php://input');       
		
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE raw:", $request_json);

		$this->request = json_decode($request_json, true);
		return;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = ICONIC_SEAMLESS_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('habanero_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function convertBalanceToGame($balance){
		return intval($balance*$this->game_api->game_conversion_rate);
	}

	public function convertBalanceToDB($balance){
		return floatval($balance/$this->game_api->game_conversion_rate);
	}

	private function debitCreditAmount($params, $mode = 'credit'){
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (debitCreditAmount)", $params, $mode);

		$player_id			= $params['player_id'];
		$amount				= $params['amount'];//apply conversion rate		
		$controller 		= $this;
		$previous_balance 	= 0;
		$after_balance 		= 0;
		$success 			= false;
		
		$insufficient_balance = false;

		//check if transaction exists
		$generatedUniqueId = $this->generateUniqueTransactionId($params);

		$isTransactionExist = $this->iconic_transactions->isTransactionExist($this->getValidPlatformId(), $generatedUniqueId);	
		
		if($isTransactionExist){
			$success 			= true;
			$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (debitCreditAmount) DUPLICATE TRANSACTION", $params);
		}
		
		if($amount<>0 && !$isTransactionExist){
			if($mode=='debit'){

				$success = $this->lockAndTransForPlayerBalance($player_id, function() use($params, $controller, $player_id, $amount, &$insufficient_balance, &$previous_balance, &$after_balance) {
					$get_balance = $controller->getPlayerBalance($params['player_name']);
					
					if($get_balance!==false){
						$after_balance = $previous_balance = $get_balance;
					}else{
						return false;
					}

					if($previous_balance < $amount ){
						$insufficient_balance = true;
						return false;
					}

					$success = $controller->wallet_model->decSubWallet($player_id, $controller->game_platform_id, $amount);
					
					$get_balance = $controller->getPlayerBalance($params['player_name']);
					if($get_balance!==false){
						$after_balance = $get_balance;
					}else{
						return false;
					}

					return $success;			
				});

			}else{
				$success = $this->lockAndTransForPlayerBalance($player_id, function() use($params, $controller, $player_id, $amount, &$insufficient_balance, &$previous_balance, &$after_balance) {
					$get_balance = $controller->getPlayerBalance($params['player_name']);
					
					if($get_balance!==false){
						$after_balance = $previous_balance = $get_balance;
					}else{
						return false;
					}

					$insufficient_balance = false;

					$success = $controller->wallet_model->incSubWallet($player_id, $controller->game_platform_id, $amount);		

					$get_balance = $controller->getPlayerBalance($params['player_name']);
					if($get_balance!==false){
						$after_balance = $get_balance;
					}else{
						return false;
					}

					return $success;			
				});
			}
		}else{

			$get_balance = $this->getPlayerBalance($params['player_name']);
			if($get_balance!==false){
				$after_balance = $previous_balance = $get_balance;
				$success = true;
			}else{
				$success = false;
			}
		}				

		return array($success, $previous_balance, $after_balance, $insufficient_balance, $isTransactionExist);
	}

	public function isValidAmount($amount){
		return is_numeric($amount);
	}

	public function debitCreditAmountToWallet($params, $mode, &$previousBalance, &$afterBalance){
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);

		//initialize params
		$player_id			= $params['player_id'];				
		$amount 			= $this->convertBalanceToDB(abs($params['amount']));
		
		//initialize response
		$success = false;
		$isValidAmount = true;		
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$additionalResponse	= [];

		$isValidAmount 		= $this->isValidAmount($params['amount']);

		if(!$isValidAmount){
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		if($amount<>0){

			if(!$isValidAmount){
				$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (debitCreditAmountToWallet) isValidAmount", $isValidAmount);
				$success = false;
				$isValidAmount = false;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}	

			//get and process balance
			$get_balance = $this->getPlayerBalance($params['player_name']);

			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}
				
			}else{				
				$this->utils->error_log("ICONIC SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request, $this->params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

            if ($params['transaction_type'] == 'cancelBet') {
                //check if already processed by win, return false
                if ($this->iconic_transactions->isTransactionExistCustom(['transaction_type' => 'win', 'player_id' => $params['player_id'], 'game_id' => $params['product_id'], 'round_id' => $params['round_id']])) {
                    $afterBalance = $previousBalance;
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
                $params['transaction_type'] = 'bet'; # use bet transaction type for checking
                $external_unique_id = $this->generateUniqueTransactionId($params);
                //check if bet not exist, return false
                if (!$this->iconic_transactions->isTransactionExistCustom(['external_unique_id' => $external_unique_id])) {
                    $afterBalance = $previousBalance;
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
                $params['transaction_type'] = 'cancelBet'; # back to original transaction type
            }
            
            if ($params['transaction_type'] == 'cancelDeposit') {
                $params['transaction_type'] = 'deposit'; # use deposit transaction type for checking
                $external_unique_id = $this->generateUniqueTransactionId($params);
                //check if bet not exist, return false
                if (!$this->iconic_transactions->isTransactionExistCustom(['external_unique_id' => $external_unique_id])) {
                    $afterBalance = $previousBalance;
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
                $params['transaction_type'] = 'cancelDeposit'; # back to original transaction type
            }
    
            if ($params['transaction_type'] == 'win') {
                //check if already processed by cancelBet, return false
                if ($this->iconic_transactions->isTransactionExistCustom(['transaction_type' => 'cancelBet', 'player_id' => $params['player_id'], 'game_id' => $params['product_id'], 'round_id' => $params['round_id']])) {
                    $afterBalance = $previousBalance;
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
            }

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

            //insert transaction
            $isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);

			if($isAdded===false){
				$this->utils->error_log("ICONIC SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request, $this->params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				//$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}				
			
			if($mode=='debit'){
				$success = $this->wallet_model->decSubWallet($player_id, $this->game_platform_id, $amount);	
			}else{
				$success = $this->wallet_model->incSubWallet($player_id, $this->game_platform_id, $amount);
			}	

			if(!$success){
				$this->utils->error_log("ICONIC SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request, $this->params);
			}

		}else{
			$get_balance = $this->getPlayerBalance($params['player_name']);
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;
			}else{
				$success = false;
			}

			//insert transaction
			$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded);
	}

	public function getPlayerBalance($playerName){
		$get_bal_req = $this->game_api->queryPlayerBalance($playerName);
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);

        $game_description = $this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->game_api->getPlatformCode(), $this->transaction_for_fast_track['game_id']);
        $betType = null;
        switch($this->transaction_for_fast_track['transaction_type']) {
            case 'bet':
            case 'DEBIT':
                $betType = 'Bet';
                break;
            case 'CREDIT':
            case 'win':
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
            "currency" =>  'THB',
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  strval($this->transaction_for_fast_track['round_id']),
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->transaction_for_fast_track['player_id'],
            "vendor_id" =>  strval($this->game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amount']) : 0,
        ];
        
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (sendToFastTrack)", $data);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

}///END OF FILE////////////
