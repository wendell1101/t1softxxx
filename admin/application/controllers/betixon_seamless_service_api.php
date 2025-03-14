<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * http://admin.cop.staging.brlgateway.t1t.in/betixon_seamless_service_api/Authenticate
 * http://admin.cop.staging.brlgateway.t1t.in/betixon_seamless_service_api/DebitAndCredit
 * http://admin.cop.staging.brlgateway.t1t.in/betixon_seamless_service_api/GetBalance
 * http://admin.cop.staging.brlgateway.t1t.in/betixon_seamless_service_api/Rollback
 */
class Betixon_seamless_service_api extends BaseController
{
	const METHOD_AUTHENTICATE = 'authenticate';
	const METHOD_DEBIT_AND_CREDIT = 'debitAndCredit';
	const METHOD_GET_BALANCE = 'getBalance';
	const METHOD_ROLLBACK = 'rollback';
	const METHOD_GENERATE_PLAYER_TOKEN = 'generatePlayerToken';

	const ALLOWED_API_METHODS = [
		self::METHOD_AUTHENTICATE,
		self::METHOD_DEBIT_AND_CREDIT,
		self::METHOD_GET_BALANCE,
		self::METHOD_ROLLBACK,
		self::METHOD_GENERATE_PLAYER_TOKEN
	];

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const ROLLBACK = 'rollback';
	const TIE_OR_CANCEL = 'tie_or_cancel'; #this is custom incase debit and credit is same amount 

	const SUCCESS = 0;

	const ERROR_IP_NOT_WHITELISTED = 401;

	#GP ERROR CODES
	const WRONG_PLAYER_ID = 8;
	const NOT_ENOUGH_BALANCE = 21;
	const LIMIT_REACHED = 26;
	const PLAYER_IS_BLOCKED = 29;
	const INVALID_TOKEN = 102;
	const TRANSACTION_NOT_FOUND = 107;
	const ROLLBACK_ALREADY_PROCESSED = 111;
	const TRANSACTION_EXISTS = 110;
	const GENERAL_ERROR = 130;
	const WRONG_TRANSACTION_AMOUNT = 109;
	const ROLLBACK_IGNORED_NO_ROLLBACK_DONE_AND_TRANSACTION_WILL_BE_MARKED_AS_SUCCESSFUL = 134;

	const INVALID_GAME_CODE = -3;
	const SWITCH_KEY_AND_VALUE_AND_RETURN_THE_OUTPUT = -4;

	const ERROR_CODES = [
		self::WRONG_PLAYER_ID,
		self::NOT_ENOUGH_BALANCE,
		self::LIMIT_REACHED,
		self::PLAYER_IS_BLOCKED,
		self::INVALID_TOKEN,
		self::TRANSACTION_NOT_FOUND,
		self::ROLLBACK_ALREADY_PROCESSED,
		self::TRANSACTION_EXISTS,
		self::GENERAL_ERROR,
		self::WRONG_TRANSACTION_AMOUNT,
		self::ROLLBACK_IGNORED_NO_ROLLBACK_DONE_AND_TRANSACTION_WILL_BE_MARKED_AS_SUCCESSFUL,
		self::INVALID_GAME_CODE,
		self::SWITCH_KEY_AND_VALUE_AND_RETURN_THE_OUTPUT,
	];

	const ERROR_REQUEST_METHOD_NOT_ALLOWED = 405;
	const ERROR_API_METHOD_NOT_ALLOWED = 401;

	const ERROR_INVALID_REQUEST = '0x1034';
	const ERROR_INTERNAL_SERVER_ERROR = '0x1200';
	const ERROR_IP_NOT_ALLOWED = '0x10';
	const ERROR_SERVER = '0x12';
	const ERROR_CONNECTION_TIMED_OUT = '0x13';
	const ERROR_GAME_UNDER_MAINTENANCE = '0x14';
	const ERROR_SERVICE_NOT_AVAILABLE = '0x15';
	const ERROR_INVALID_SIGNATURE = '0x16';
	const ERROR_PLAYER_BLOCKED = '0x17';

	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS => 200,
		self::ERROR_INVALID_REQUEST,
		self::ERROR_INTERNAL_SERVER_ERROR,
		self::ERROR_SERVER => 500,
		self::ERROR_CONNECTION_TIMED_OUT => 500,
		self::ERROR_GAME_UNDER_MAINTENANCE => 400,
		self::ERROR_SERVICE_NOT_AVAILABLE => 400,
		self::ERROR_IP_NOT_ALLOWED => 401,
		self::ERROR_REQUEST_METHOD_NOT_ALLOWED => 405,
		self::ERROR_API_METHOD_NOT_ALLOWED => 500
	];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request, $requestHeaders;
	public $currency;

	public $start_time;
	public $end_time;
	public $method;
	protected $requestMethod;
	private $hasError = true;
	private $headers;
	private $trans_records;
	private $extra=[];
	private $use_third_party_token=false;

	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'betixon_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));

		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();

	}

	public function index($gamePlatformId = null, $method = null)
	{
		$this->game_platform_id = $gamePlatformId;
		$this->method = $method;

		if ($this->initialize()) {
			return $this->selectMethod();			
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('Betixon seamless service: raw request');
		$validRequestMethod = self::POST;

		if(!$this->isRequestMethodValid($validRequestMethod)){
			return false;
		};

		if(!$this->checkIfGamePlatformIdIsValid()){
			return false;
		};
		
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
		$this->use_third_party_token = $this->game_api->use_third_party_token;

		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->betixon_seamless_wallet_transactions->setTableName($tableName);  

		if(!$this->game_api){
			$this->utils->debug_log("BETIXON SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			http_response_code(500);
			return false;
		}

        $this->currency = $this->game_api->getCurrency();	
		
		if(!$this->validateWhiteIP()){
			return false;
		}

		if(!$this->isGameUnderMaintenance()){
			return false;
		}
		

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		return $this->hasError;
	}

	#-----------------METHODS------------------------------
	public function selectMethod(){
		if($this->isAllowedApiMethod()){
			switch ($this->method) {
				case 'Authenticate':
					$this->authenticate();
					break;
				case 'DebitAndCredit':
					$this->debitAndCredit();
					break;
				case 'GetBalance':
					$this->getBalance();
					break;
				case 'Rollback':
					$this->rollback();
					break;
				case 'generatePlayerToken':
					$this->generatePlayerToken();
					break;
				default:
					$this->utils->debug_log('Betixon seamless service: Invalid API Method');
					http_response_code(404);
			}
		}
	}

	public function generatePlayerToken(){
		$username = $this->request['username'];

		$result = $this->game_api->getPlayerTokenByUsername($username);
		print_r(["token" => $result]);
	}

	#----------------------GP API_ALLOWED_METHODS----------------------------
	
	public function authenticate(){
		$type = self::METHOD_AUTHENTICATE;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = null;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'Token' => 'required',
			'auth' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("Betixon Seamless Service API:" . __METHOD__ . " - ". $errorDescription);
				throw new Exception($errorDescription);
			}
							
			if(!isset($this->request['auth'])){
				$this->utils->error_log("Betixon Seamless Service API: Missing/Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			if(!$this->isAccountValid($this->request['auth']['user'],$this->request['auth']['pass'])){
				$this->utils->error_log("Betixon Seamless Service API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			// get player details
			$token = $this->request['Token'];
			
			if($this->use_third_party_token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}	

			if(!$playerStatus){
				$errorId = self::INVALID_TOKEN;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INVALID_TOKEN);
				throw new Exception($errorDescription);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			$success = true;

			$errorCode = self::SUCCESS;
			$currency = $this->currency;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [
			"TotalBalance" => $balance,
			'PlayerId' => $gameUsername,
			'Token' => !$hasError ? $token : null
		];
		$extra = [
			'hasError' => $hasError,
			'errorId' => $errorId,
			'errorDescription' => $errorDescription
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id'		=> $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function debitAndCredit(){
		$type = self::METHOD_DEBIT_AND_CREDIT;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$token = isset($this->request['Token']) ? $this->request['Token'] : null;
		$gameUsername = null;
		$transactionId = null;
		$playerId = null;
		$insufficient_balance = false;
		$player_username = null;

		$rules = [
			'Token'            		=> 'required',
			'GameCode'              => 'required',
			'PlayerId'              => 'required',
			'DebitAmount'           => 'required',
			'CreditAmount'          => 'required',
			'Currency'              => 'required',
			'RGSTransactionId'      => 'required'
		];
		try{
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("Betixon Seamless Service API:" . __METHOD__ . " - ". $errorDescription);
				throw new Exception($errorDescription);
			}

			if(!isset($this->request['auth'])){
				$this->utils->error_log("Betixon Seamless Service API: Missing/Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			if(!$this->isAccountValid($this->request['auth']['user'],$this->request['auth']['pass'])){
				$this->utils->error_log("Betixon Seamless Service API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			if(!$this->isCurrencyCodeValid($this->request['Currency'])){
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Currency';
				$this->utils->error_log('Betixon Seamless Service API:' .__METHOD__.' - '. $errorDescription);
				throw new Exception(self::GENERAL_ERROR);
			}
			
			// get player details
			$player_name = $this->request['PlayerId'];
			if($this->use_third_party_token){				
				if(isset($this->request['Token']) && !empty($this->request['Token'])){					
					$token = $this->request['Token'];				
					list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsernameAndToken($player_name, $token, 'external_common_tokens');		
				}
			}else{
				if(isset($this->request['Token']) && !empty($this->request['Token'])){
					$token = $this->request['Token'];
					list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsernameAndToken($player_name, $token);
				}
			}

            if(!$playerStatus){
				$hasError = 1;
				$errorId = self::INVALID_TOKEN;
				$errorDescription = $this->getResponseErrorMessage($errorId);
				$this->utils->error_log('Betixon Seamless Service API:' .__METHOD__.' - '. $errorDescription);
                throw new Exception(self::INVALID_TOKEN);
			}

			if($player){
				$playerId = $player->player_id;
			}

			$params = [];
			$params['api_username'] 					= $this->request['auth']['user'];
			$params['api_password'] 					= $this->request['auth']['pass'];
            $params['player_username'] 					= $player_username;
            //required params
            $params['player_id']                        = $player->player_id;
            $params['rgs_player_id']                    = $this->request['PlayerId'];
            $params['game_code']                   		= $this->request['GameCode'];
			$params['debit_amount']						= $this->request['DebitAmount'];
			$params['credit_amount']					= $this->request['CreditAmount'];
			$params['currency']							= $this->request['Currency'];
			$params['rgs_transaction_id']				= $this->request['RGSTransactionId'];	
			$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;

			//optional params
			$params['round_start']						= isset( $this->request['round_start']) ?  $this->request['round_start'] :null;
			$params['round_end']						= isset( $this->request['round_end']) ?  $this->request['round_end'] :null;
			$params['promo']							= isset( $this->request['promo']) ?  $this->request['promo'] :null;
			$params['code']								= isset( $this->request['code']) ?  $this->request['code'] :null;
			$params['is_done']							= isset( $this->request['isDone']) ?  $this->request['isDone'] :null;
			$params['total_spins']						= isset( $this->request['TotalSpins']) ?  $this->request['TotalSpins'] :null;
			$params['spins_done']						= isset( $this->request['SpinsDone']) ?  $this->request['SpinsDone'] :null;
			

			$params['external_uniqueid']                = $params['rgs_transaction_id'];
			$params['trans_type']                       = $type;

			$status = true;
			$errorCode = self::SUCCESS;

			if($params['debit_amount'] < 0){
				$hasError = 1;
				$errorId = self::WRONG_TRANSACTION_AMOUNT;
				$errorDescription = $this->getResponseErrorMessage($errorId);
				$this->utils->error_log("Betixon Seamless Service API: (debitAndCredit)" . " - ". $errorDescription);
				throw new Exception($errorDescription);
			}

			#check if currentPlayerBalance > debit_amount
			$currentPlayerBalance = $this->getPlayerBalance($player_username, $playerId);
			if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['debit_amount'])){
				$insufficient_balance = true;
				$hasError = 1;
				$errorId = self::NOT_ENOUGH_BALANCE;
				$errorDescription = $this->getResponseErrorMessage($errorId);
				$this->utils->error_log("Betixon Seamless Service API:" . __METHOD__ . " - ". $errorDescription);
				throw new Exception($errorDescription);
			}			

			#if credit > debit = add
			#if debit > credit = deduct
			#if debit = credit = dont do anything
			$mode = null;
			$debit = $params['debit_amount'];
			$credit = $params['credit_amount'];
			if ($credit > $debit) {
				$mode = self::CREDIT;
				$finalAmount = $credit - $debit;
			} elseif ($debit > $credit) {
				$mode = self::DEBIT;
				$finalAmount = $credit - $debit;
			} else {
				$mode = self::TIE_OR_CANCEL;
				$finalAmount = 0;
			}

			$params['insufficient_balance'] = $insufficient_balance;
			$params['mode'] = $mode;
			$params['final_amount'] = $finalAmount;
			$params['current_player_balance'] = $currentPlayerBalance;

			if(!$hasError){
				$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){
					list($trans_success) = $this->debitCreditAmountToWallet($params);
					return true;
				});	
			
				$status=$trans_success;
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$transactionId = $params['rgs_transaction_id'];
			}
		}catch(Exception $error){
			if($player_username !== null && $playerId !== null){
				$balance = $this->getPlayerBalance($player_username, $playerId);
			}
			$errorCode = $error->getMessage();
			$status = false;
		}
		
		$data = [
			"TotalBalance" => $balance,
			'PlayerId' => $gameUsername,
			'Token' => $token,
			'PlatformTransactionId' => $transactionId,
		];
		$extra = [
			'hasError' => $hasError,
			'errorId' => $errorId,
			'errorDescription' => $errorDescription
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}
	public function getBalance(){
		$type = self::METHOD_GET_BALANCE;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = null;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'Token' => 'required',
			'auth' => 'required'
		];		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("Betixon Seamless Service API:" . __METHOD__ . " - ". $errorDescription);
				throw new Exception($errorDescription);
			}
				
			if(!isset($this->request['auth'])){
				$this->utils->error_log("Betixon Seamless Service API: Missing/Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			if(!$this->isAccountValid($this->request['auth']['user'],$this->request['auth']['pass'])){
				$this->utils->error_log("Betixon Seamless Service API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			// get player details
			$token = $this->request['Token'];
			
			if($this->use_third_party_token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}

			$playerId = $player->player_id;

			if(!$playerStatus){
				$errorId = self::INVALID_TOKEN;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INVALID_TOKEN);
				throw new Exception($errorDescription);
			}

			$player_id = $player->player_id;
			$errorCode = self::SUCCESS;
			$currency = $this->currency;
			$balance = $this->getPlayerBalance($player_username, $player_id);
			$status = true;
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [
			"TotalBalance" => $balance,
			'PlayerId' => $gameUsername,
			'Token' => !$hasError ? $token : null
		];
		$extra = [
			'hasError' => $hasError,
			'errorId' => $errorId,
			'errorDescription' => $errorDescription
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function rollback()
	{
		$type = self::METHOD_ROLLBACK;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$gameUsername = null;
		$token = null;
		$transactionId = null;
		$playerId = null;
		
		$rules = [
			'RGSTransactionId'          => 'required',
			'RGSRelatedTransactionId'   => 'required',
			'PlayerId'              	=> 'required',
			'auth' 						=> 'required'
		];

		try{
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("Betixon Seamless Service API:" . __METHOD__ . " - ". $errorDescription);
				throw new Exception($errorDescription);
			}

			if(!isset($this->request['auth'])){
				$this->utils->error_log("Betixon Seamless Service API: Missing/Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			if(!$this->isAccountValid($this->request['auth']['user'],$this->request['auth']['pass'])){
				$this->utils->error_log("Betixon Seamless Service API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::GENERAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorDescription);
			}

			// get player details
			$player_name = $this->request['PlayerId'];

			if($this->use_third_party_token){	
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
			}
						
            if(!$playerStatus){
				$hasError = 1;
				$errorId = self::INVALID_TOKEN;
				$errorDescription = $this->getResponseErrorMessage($errorId);
				$this->utils->error_log('Betixon Seamless Service API:' .__METHOD__.' - '. $errorDescription);
                throw new Exception(self::INVALID_TOKEN);
			}
			
			$playerId = $player->player_id;
			if($this->use_third_party_token){
				$token = $this->external_common_tokens->getPlayerToken($playerId);
			}else{
				$token = $this->common_token->getPlayerToken($playerId);
			}
		

			$params = [];
			$params['player_username'] 					= $player_username;
			$params['api_username'] 					= $this->request['auth']['user'];
			$params['api_password'] 					= $this->request['auth']['pass'];
       
            //required params
			$params['rgs_transaction_id'] 				= $this->request['RGSTransactionId'];
			$params['rgs_related_transaction_id'] 		= $this->request['RGSRelatedTransactionId'];
            $params['player_id']                        = $this->request['PlayerId'];
			

			$params['external_uniqueid']                = $params['rgs_transaction_id'];
			$params['trans_type']                       = $type;

			$status = true;
			$errorCode = self::SUCCESS;

			$currentPlayerBalance = $this->getPlayerBalance($player_username, $playerId);
	
			$params['current_player_balance'] = $currentPlayerBalance;
			$params['player_username'] = $player_username;

			if(!$hasError){
			
				$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){
					$success = $this->handleRollback($params);
					return true;
				});

				// $status=$trans_success;
				$status=true;
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$transactionId = $params['rgs_related_transaction_id'];
			}
		}catch(Exception $error){
			if($player_username !== null && $playerId !== null){
				$balance = $this->getPlayerBalance($player_username, $playerId);
			}
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [
			"TotalBalance" => $balance,
			'PlayerId' => $gameUsername,
			'Token' => $token,
			'PlatformTransactionId' => $transactionId,
		];

		$extra = [
			'hasError' => $hasError,
			'errorId' => $errorId,
			'errorDescription' => $errorDescription
		];

		if(!empty($this->extra)){
			$extra = $this->extra;
		}
		
		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}
	#-----------------Validations--------------------------
	public function checkIfGamePlatformIdIsValid(){
		$httpStatusCode = $this->getHttpStatusCode(self::ERROR_INTERNAL_SERVER_ERROR);
		if (empty($this->game_platform_id)) {
			http_response_code($httpStatusCode);
			return false;
		}
		return true;
	}

	public function isRequestMethodValid($validMethod)
	{
		if($this->requestMethod != $validMethod){
			$this->utils->error_log('Betixon seamless service: Request method not allowed');
			http_response_code(405);
			return false;
		}
		return true;
	}

	public function validateWhiteIP()
	{
		$validated =  $this->game_api->validateWhiteIP();
		if (!$validated) {
			$this->utils->error_log("Betixon seamless service: " . $this->getResponseErrorMessage(self::ERROR_IP_NOT_ALLOWED));
			$httpStatusCode = $this->getHttpStatusCode(self::ERROR_IP_NOT_ALLOWED);
			http_response_code($httpStatusCode);
			return false;
		}
		return true;
	}

	public function isGameUnderMaintenance()
	{
		$isMaintenance = $this->utils->setNotActiveOrMaintenance($this->game_platform_id);
		if ($isMaintenance) {
			$this->utils->error_log("Betixon seamless service: " . $this->getResponseErrorMessage(self::ERROR_GAME_UNDER_MAINTENANCE));
			$httpStatusCode = $this->getHttpStatusCode(self::ERROR_GAME_UNDER_MAINTENANCE);
			http_response_code($httpStatusCode);
			return false;
		}
		return true;
	}

	public function isAllowedApiMethod()
	{
		$allowedApiMethods = array_map('strtolower', self::ALLOWED_API_METHODS);

		if (!in_array(strtolower($this->method), $allowedApiMethods)) {
			$this->utils->error_log("Betixon seamless service: " . $this->getResponseErrorMessage(self::ERROR_API_METHOD_NOT_ALLOWED));
			$httpStatusCode = $this->getHttpStatusCode(self::ERROR_API_METHOD_NOT_ALLOWED);
			http_response_code($httpStatusCode);
			return false;		
		}
		return true;
	}

	public function isAccountValid($username, $password){
		return ($this->game_api->api_username == $username) && ($this->game_api->api_password == $password);
    }

	public function isCurrencyCodeValid($currency){
        return $this->game_api->currency==$currency;
    }

	public function checkIfInsufficientBalance($currentPlayerBalance, $balance){
		return $currentPlayerBalance >= $balance;
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("BETIXON SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("BETIXON SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("BETIXON SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}
	# ---------------Response-------------------------------

	public function externalResponse($data, $extra, $httpStatusCode = 200)
	{
		if(isset($extra['errorDescription'])){
			$this->utils->error_log('Betixon Semaless API: ', $extra['errorDescription']);
		}
		$hasError = isset($extra['hasError']) ? $extra['hasError'] : 0;
		$errorId = $extra['errorId'];
		$errorDescription = $extra['errorDescription'];
		if ($extra['hasError']) {
			$this->utils->error_log("Betixon seamless service ($this->game_platform_id): $errorDescription");
		}

		if (!empty($data)) {
			foreach ($data as $key => $value) {
				$response[$key] = $value;
			}

			$response["HasError"] = $hasError;
			$response["ErrorId"] = $errorId;
			$response["ErrorDescription"] = $errorDescription;
		} else {
			$response =  [
				"HasError" => $hasError,
				'ErrorId' => $errorId,
				'ErrorDescription' => $errorDescription,
			];
		}

		http_response_code($httpStatusCode);
		$this->hasError = $hasError;
		
		return $this->setOutput($response);
	}

	public function formatResponse($data, $extra){
		$hasError = isset($extra['hasError']) ? $extra['hasError'] : 0;
		$errorId = $extra['errorId'];
		$errorDescription = $extra['errorDescription'];
		if ($extra['hasError']) {
			$this->utils->error_log("Betixon seamless service ($this->game_platform_id): $errorDescription");
		}

		if (!empty($data)) {
			foreach ($data as $key => $value) {
				$response[$key] = $value;
			}

			$response["HasError"] = $hasError;
			$response["ErrorId"] = $errorId;
			$response["ErrorDescription"] = $errorDescription;
		} else {
			$response =  [
				"HasError" => $hasError,
				'ErrorId' => $errorId,
				'ErrorDescription' => $errorDescription,
			];
		}

		return $response;
	}

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->CI->utils->debug_log(__METHOD__ . "  (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code,
            'fields', $fields);

		if(strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log(__METHOD__ . "  (handleExternalResponse) Connection timed out.",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code,
            'fields', $fields);
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}

		$httpStatusCode = $this->getHttpStatusCode($error_code);

		if($error_code == self::SUCCESS){
			$httpStatusCode = 200;
		}

		//add request_id
		if(empty($response)){
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);
		$currentDateTime = date('Y-m-d H:i:s');
		$this->CI->utils->debug_log("betixon save_response_result: $currentDateTime",["status" => $status, "type" => $type,"data" =>  $data, "response" => $response, "http_status" => $httpStatusCode,"fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("betixon save_response: ",$response);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
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

	public function getResponseErrorMessage($code)
	{
		$message = '';

		switch ($code) {
			case self::SUCCESS:
				$message = lang('Success');
				break;

			case self::WRONG_PLAYER_ID:
				$message = lang("Wrong Player Id");
				break;
			case self::NOT_ENOUGH_BALANCE:
				$message = lang("Not Enough Balance");
				break;
			case self::LIMIT_REACHED:
				$message = lang("Limit Reached");
				break;
			case self::PLAYER_IS_BLOCKED:
				$message = lang("Player is Blocked");
				break;
			case self::INVALID_TOKEN:
				$message = lang("Invalid Token");
				break;
			case self::TRANSACTION_NOT_FOUND:
				$message = lang("Transaction Not Found");
				break;
			case self::ROLLBACK_ALREADY_PROCESSED:
				$message = lang("Rollback already processed");
				break;
			case self::TRANSACTION_EXISTS:
				$message = lang("Transaction Exists");
				break;
			case self::GENERAL_ERROR:
				$message = lang('General Error');
				break;
			case self::WRONG_TRANSACTION_AMOUNT:
				$message = lang('Wrong Transaction Amount');
				break;
			case self::ROLLBACK_IGNORED_NO_ROLLBACK_DONE_AND_TRANSACTION_WILL_BE_MARKED_AS_SUCCESSFUL:
				$message = lang('Rollback ignored, no rollback done and transaction will be marked as successful');
				break;

				#SBE ERROR

			case self::ERROR_INVALID_REQUEST:
				$message = lang('Invalid request');
				break;
			case self::ERROR_INTERNAL_SERVER_ERROR:
				$message = lang('Internal server error');
				break;
			case self::ERROR_GAME_UNDER_MAINTENANCE:
				$message = lang('Game Under Maintenance');
				break;
			case self::ERROR_IP_NOT_ALLOWED:
				$message = lang("Error IP Not Allowed");
				break;
			case self::ERROR_REQUEST_METHOD_NOT_ALLOWED:
				$message = lang('Request Method not allowed');
				break;
			case self::ERROR_API_METHOD_NOT_ALLOWED:
				$message = lang('API Method not allowed');
				break;
			default:
				$this->utils->error_log("BETIXON SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("Betixon SEAMLESS SERVICE raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("Betixon SEAMLESS SERVICE raw parsed:", $request_json);
			$this->request = $request_json;
		}
		return $this->request;
	}

	public function getHttpStatusCode($errorCode)
	{
		$httpCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_SERVER];
		foreach (self::HTTP_STATUS_CODE_MAP as $key => $value) {
			if ($errorCode == $key) {
				$httpCode = $value;
			}
		}
		return $httpCode;
	}


	public function setOutput($data)
	{
		return $this->output->set_content_type('application/json')->set_output(json_encode($data));
	}

	public function dumpData($data){
        print_r(json_encode($data));exit;
    }
	public function getPlayerByToken($token, $model="common_token"){

		$player = $this->$model->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsername($gameUsername, $model='common_token'){
		$player = $this->$model->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsernameAndToken($gameUsername, $token,$model="common_token"){
		$player = $this->$model->getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerBalance($playerName, $player_id){
		$get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);

		$this->utils->debug_log("BETIXON SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function debitCreditAmountToWallet($params){
		$this->utils->debug_log("BETIXON SEAMLESS SERVICE: (debitCreditAmount)", $params);
		$player_balance = $params['current_player_balance'];
		$player_balance = $this->game_api->dBtoGameAmount($player_balance);		
		$transaction_id = $params['rgs_transaction_id'];
		$mode = $params['mode'];

		$before_balance = 0;
		$after_balance = 0;

		$flagrefunded = false;

		$existingRow = $this->betixon_seamless_wallet_transactions->getExistingTransaction($transaction_id,$mode);

		if(!empty($existingRow)){
			$response_code['after_balance'] = $player_balance;
			return false;
		}

		$before_balance  = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
		$before_balance = $this->game_api->dbToGameAmount($before_balance);
		$after_balance = $before_balance;

		$flagrefunded = false;
		//insert transaction
		$isAdded = $this->insertIgnoreTransactionRecord($params, $before_balance, $after_balance, $flagrefunded);

		if($isAdded===false){
			$this->utils->error_log("BETIXON SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			return false;
		}

		$response_code = $this->adjustWallet($mode, $params);

		if($response_code['success']){
			$updatedAfterBalance = $this->betixon_seamless_wallet_transactions->updateAfterBalanceByTransactionId($transaction_id, $response_code['after_balance']);
			if(!$updatedAfterBalance){
				$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update after balance');
			}	
			$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
		}

		return $response_code['success'];
	}

	public function handleRollback($params){
		$player_balance = $this->game_api->queryPlayerBalance('testt1dev')['balance'];
		$player_balance = $this->game_api->dbToGameAmount($player_balance);

		$transaction_id = $params['rgs_related_transaction_id'];

		$transaction = $this->betixon_seamless_wallet_transactions->getTransaction($transaction_id);
		
		if(!$transaction){
			$response_code['hasError'] = 1;
			$response_code['errorId'] = self::TRANSACTION_NOT_FOUND;
			$errorDescription = $this->getResponseErrorMessage(self::TRANSACTION_NOT_FOUND);
			$response_code['errorDescription'] = $errorDescription;
			$this->extra = $response_code;
			return true;
		}

		$data = [];
		$data['player_username']				= $params['player_username'];
		$data['api_username'] 					= $params['api_username'];
		$data['api_password'] 					= $params['api_password'];
		$data['debit_amount'] 					= $transaction->debit_amount;
		$data['credit_amount'] 					= $transaction->credit_amount;
		$data['game_code'] 						= $transaction->game_code;
		$data['credit_amount'] 					= $transaction->credit_amount;
		$data['rgs_player_id'] 					= $transaction->rgs_player_id;
		$data['rgs_transaction_id'] 			= $params['rgs_transaction_id'];
		$data['round_id'] 						= $transaction->round_id;
		$data['round_start']					= $transaction->round_start;
		$data['round_end'] 						= $transaction->round_end;
		$data['promo'] 							= $transaction->promo;
		$data['code'] 							= $transaction->code;
		$data['is_done'] 						= $transaction->is_done;
		$data['total_spins'] 					= $transaction->total_spins;
		$data['spins_done'] 					= $transaction->spins_done;

		$data['total_balance'] 					= $transaction->total_balance;
		$data['rgs_related_transaction_id'] 	= $params['rgs_related_transaction_id'];
		$data['raw_data'] 						= $transaction->raw_data;
		$data['currency'] 						= $transaction->currency;
		$data['trans_type']						= $transaction->trans_type;
		$data['trans_status'] 					= $transaction->trans_status;
		$data['player_id']						= $transaction->player_id;
		$data['balance_adjustment_method']		= $transaction->balance_adjustment_method;
		$data['before_balance'] 				= $transaction->before_balance;
		$data['after_balance'] 					= $transaction->after_balance;
		$data['game_platform_id'] 				= $transaction->game_platform_id;
		$data['external_uniqueid'] 				= $params['rgs_transaction_id'];

		$data['mode'] 							= self::ROLLBACK;


		$type = $transaction->balance_adjustment_method;
		$flagrefunded = false;
		

		if($type == self::CREDIT){			
			$amountToSubtract = $data['credit_amount'] - $data['debit_amount'];
			$before_balance = $data['after_balance'];
			$after_balance = $data['after_balance'] - $amountToSubtract;
			
			$data['after_balance'] = $this->game_api->dbToGameAmount($after_balance);
			$data['before_balance'] = $this->game_api->dbToGameAmount($before_balance);
			$data['total_balance'] = $data['after_balance'];
			$data['final_amount'] = $amountToSubtract;

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($data, $before_balance, $after_balance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("BETIXON SEAMLESS SERVICE: (handleRollback) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return true;
			}
			

			$response_code = $this->adjustWallet(self::DEBIT, $data);

			//update after balance
			if($response_code['success']){
				$updatedAfterBalance = $this->betixon_seamless_wallet_transactions->updateAfterBalanceByTransactionId($data['rgs_transaction_id'], $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->error_log(__METHOD__ . ': failed to update after balance after adjust wallet');
				}	
				$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
			}			


			return true;
		}

		if($type == self::DEBIT){
			$amountToAdd = $data['debit_amount'] - $data['credit_amount'];
			$before_balance = $data['after_balance'];
			$after_balance = $data['after_balance'] + $amountToAdd;
			
			$data['after_balance'] = $this->game_api->dbToGameAmount($after_balance);
			$data['before_balance'] = $this->game_api->dbToGameAmount($before_balance);
			$data['total_balance'] = $data['after_balance'];
			$data['final_amount'] = $amountToAdd;
			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($data, $before_balance, $after_balance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("BETIXON SEAMLESS SERVICE: (handleRollback) ERROR: isAdded=false saving error", $isAdded, $this->request);
			}
			$response_code = $this->adjustWallet(self::CREDIT, $data);

			//update after balance
			if($response_code['success']){
				$updatedAfterBalance = $this->betixon_seamless_wallet_transactions->updateAfterBalanceByTransactionId($data['rgs_transaction_id'], $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->error_log(__METHOD__ . ': failed to update after balance after adjust wallet');
				}	
				$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
			}
			return true;
		}

		if($type == self::ROLLBACK){
			#dont allow rollback transaction to rollback
			$this->extra = [
				"hasError" => 1,
				"errorId" => self::ROLLBACK_ALREADY_PROCESSED,
				"errorDescription" => $this->getResponseErrorMessage(self::ROLLBACK_ALREADY_PROCESSED)
			];
			return true;
		}

		return true;
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){

		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;

		$this->trans_record = $trans_record = $this->makeTransactionRecord($data);
		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->betixon_seamless_wallet_transactions->setTableName($tableName);  
		return $this->betixon_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['player_id'];
		$before_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
		$response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);

		$after_balance = $before_balance;

		$response_code['success'] = false;
        if($action == self::DEBIT) {
            $finalAmount = abs($params['final_amount']);
			
            if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$params['rgs_transaction_id'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	
				$finalAmount = $this->game_api->gameAmountToDB($finalAmount);

				$this->CI->utils->debug_log('BETIXON SEAMLESS SERVICE CREDIT-DEBIT FINAL AMOUNT', $finalAmount);
				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$this->utils->debug_log('BETIXON_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to deduct subwallet');
				}
				$response_code['success'] = true;
				$this->CI->utils->debug_log('BETIXON_SEAMLESS_SERVICE DEDUCT BALANCE', $deduct_balance);
            }

			$after_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 

			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {

            $finalAmount = $params['final_amount'];

			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$params['rgs_transaction_id'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$finalAmount = $this->game_api->gameAmountToDB($finalAmount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('BETIXON_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }
				$response_code['success'] = true;
				$this->CI->utils->debug_log('BETIXON_SEAMLESS_SERVICE ADD BALANCE', $add_balance);

			}
			$after_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

		} else {	
           return $response_code;
        }

		return $response_code;

	}

	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['api_username'] 			            = isset($raw_data['api_username']) ? $raw_data['api_username'] : null;
		$data['api_password'] 			            = isset($raw_data['api_password']) ? $raw_data['api_password'] : null;
		$data['player_id'] 			                = isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
		$data['rgs_player_id'] 			            = isset($raw_data['rgs_player_id']) ? $raw_data['rgs_player_id'] : null;
		$data['game_code'] 			                = isset($raw_data['game_code']) ? $raw_data['game_code'] : null;
		$data['debit_amount'] 			            = isset($raw_data['debit_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['debit_amount']) : null;
		$data['credit_amount'] 			            = isset($raw_data['credit_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['credit_amount']): null;
		$data['result_amount'] 			            = isset($raw_data['final_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['final_amount']): null;
		$data['currency'] 			            	= isset($raw_data['currency']) ? $raw_data['currency'] : null;
		$data['rgs_transaction_id'] 			    = isset($raw_data['rgs_transaction_id']) ? $raw_data['rgs_transaction_id'] : null;
		$data['rgs_related_transaction_id'] 		= isset($raw_data['rgs_related_transaction_id']) ? $raw_data['rgs_related_transaction_id'] : null;
		$data['round_id'] 			    			= isset($raw_data['round_id']) ? $raw_data['round_id'] : null;
		$data['round_start'] 			    		= isset($raw_data['round_start]']) ? $raw_data['round_start'] : null;
		$data['round_end'] 			    			= isset($raw_data['round_end']) ? $raw_data['round_end'] : null;
		$data['promo'] 			    				= isset($raw_data['promo']) ? $raw_data['promo'] : null;
		$data['code'] 			    				= isset($raw_data['code']) ? $raw_data['code'] : null;
		$data['is_done'] 			    			= isset($raw_data['is_done']) ? $raw_data['is_done'] : 0;
		$data['trans_status'] 			    		= 1;
		$data['total_spins'] 			    		= isset($raw_data['total_spins']) ? $raw_data['total_spins'] : null;
		$data['spins_done'] 			    		= isset($raw_data['spins_done']) ? $raw_data['spins_done'] : null;
		$data['external_uniqueid'] 			    	= isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : null;
		$data['trans_type'] 			    		= isset($raw_data['trans_type']) ? $raw_data['trans_type'] : null;
		$data['balance_adjustment_method'] 			= isset($raw_data['balance_adjustment_method']) ? $raw_data['balance_adjustment_method'] : null;
		$data['after_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 					= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;
		$data['total_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['raw_data']							= isset($raw_data) ? json_encode($raw_data) : null;

		$data['balance_adjustment_method']			= isset($raw_data['mode']) ? $raw_data['mode'] : null;
		
		$data['game_platform_id'] 	                = $this->game_platform_id;

		return $data;
	}

	
	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount, &$afterBalance=null){
		$success = false; 
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode==self::DEBIT){
			$success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
		}elseif($mode==self::CREDIT){
			$success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
		}

		return $success;
	}
}///END OF FILE////////////