<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * {{url}}/lightning_seamless_service_api/GetBalance
 * {{url}}/lightning_seamless_service_api/BetTransact
 * {{url}}/lightning_seamless_service_api/CancelBetTransact
 * {{url}}/lightning_seamless_service_api/RewardBetTransact
 * {{url}}/lightning_seamless_service_api//RollbackTransact
 */
class Lightning_seamless_service_api extends BaseController
{
	const METHOD_GET_BALANCE = 'GetBalance';
	const METHOD_BET_TRANSACT = 'BetTransact'; #bet
	const METHOD_CANCEL_BET_TRANSACT = 'CancelBetTransact'; #cancel
	const METHOD_REWARD_BET_TRANSACT = 'RewardBetTransact'; #payout
	const METHOD_ROLLBACK_TRANSACT = 'RollbackTransact'; #rollback
	const ALLOWED_API_METHODS = [
		self::METHOD_GET_BALANCE,
		self::METHOD_BET_TRANSACT,
		self::METHOD_CANCEL_BET_TRANSACT,
		self::METHOD_REWARD_BET_TRANSACT,
		self::METHOD_ROLLBACK_TRANSACT
	];
	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel'; 

	const ACTION_BET 	= 'bet';
	const ACTION_PAYOUT ='payout';
	const ACTION_CANCEL = 'cancel';
	const ACTION_ROLLBACK = 'rollback';

	const SUCCESS = 0;

	# GP ERROR_CODES
	const CODE_SUCCCESS 								= 0;
	const CODE_PARAMETER_ERROR 							= 1;
	const CODE_TOKEN_VERIFICATION_FAILED 				= 2;
	const CODE_USER_DOES_NOT_EXIST 						= 3;
	const CODE_INSUFFICIENT_BALANCE 					= 4;
	const CODE_OPERATION_FAILED 						= 5;
	const CODE_USER_DISABLED 							= 6;
	const CODE_NO_AGENT_ACCOUNT_FOUND 					= 7;
	const CODE_UNKNOWN_ERROR 							= 99;
	const CODE_SYSTEM_MAINTENANCE 						= 300;
	const CODE_API_KEY_ERROR 							= 301;
	const CODE_TRANSFER_SERIAL_NUMBER_OCCUPIED 			= 302;
	const CODE_TRANSFER_FAILED 							= 303;
	const CODE_PROXY_STATUS_UNAVAILABLE 				= 304;
	const CODE_CLIENT_IP_RESTRICTED 					= 400;
	const CODE_NETWORK_DELAY 							= 401;
	const CODE_CONNECTION_CLOSED 						= 402;
	const CODE_CLIENT_SOURCE_RESTRICTED 				= 403;
	const CODE_REQUESTED_RESOURCE_DOES_NOT_EXIST 		= 404;
	const CODE_REQUEST_TOO_FREQUENT 					= 405;
	const CODE_REQUEST_TIMEOUT 							= 406;
	const CODE_GAME_ADDRESS_NOT_FOUND 					= 407;
	const CODE_NULL_POINTER_EXCEPTION 					= 500;
	const CODE_SYSTEM_EXCEPTION 						= 501;
	const CODE_SYSTEM_BUSY 								= 502;
	const CODE_DATA_OPERATION_EXCEPTION 				= 503;


	const GAME_STATUS_PLAYER_TIE = 0;
	const GAME_STATUS_PLAYER_LOSE = 1;
	const GAME_STATUS_PLAYER_WIN = 2;
	const GAME_STATUS_CANCEL = 3;
	const GAME_STATUS_UNDRAWN = 4;


	//INTERNAL ERROR CODES
	const ERROR_REQUEST_METHOD_NOT_ALLOWED = 405;
	const ERROR_API_METHOD_NOT_ALLOWED = 401;
	const ERROR_CONNECTION_TIMED_OUT = 500;
	const ERROR_INTERNAL_SERVER_ERROR = 500;

	const RESPONSE_CODES = [
		self::CODE_SUCCCESS								=> "Operation successful",
		self::CODE_PARAMETER_ERROR						=> "Parameter error",
		self::CODE_TOKEN_VERIFICATION_FAILED			=> "Token verification failed",
		self::CODE_USER_DOES_NOT_EXIST					=> "User does not exist",
		self::CODE_INSUFFICIENT_BALANCE					=> "Insufficient balance",
		self::CODE_OPERATION_FAILED						=> "Operation failed",
		self::CODE_USER_DISABLED						=> "User disabled",
		self::CODE_NO_AGENT_ACCOUNT_FOUND				=> "No agent account found",
		self::CODE_UNKNOWN_ERROR						=> "Unknown error",
		self::CODE_SYSTEM_MAINTENANCE					=> "System maintenance",
		self::CODE_API_KEY_ERROR						=> "API Key error",
		self::CODE_TRANSFER_SERIAL_NUMBER_OCCUPIED		=> "Transfer serial number occupied",
		self::CODE_TRANSFER_FAILED						=> "Transfer failed",
		self::CODE_PROXY_STATUS_UNAVAILABLE				=> "Proxy status unavailable",
		self::CODE_CLIENT_IP_RESTRICTED					=> "Client IP restricted",
		self::CODE_NETWORK_DELAY						=> "Network delay",
		self::CODE_CONNECTION_CLOSED					=> "Connection closed",
		self::CODE_CLIENT_SOURCE_RESTRICTED				=> "Client source restricted",
		self::CODE_REQUESTED_RESOURCE_DOES_NOT_EXIST	=> "Requested resource does not exist",
		self::CODE_REQUEST_TOO_FREQUENT					=> "Request too frequent",
		self::CODE_REQUEST_TIMEOUT						=> "Request timeout",
		self::CODE_GAME_ADDRESS_NOT_FOUND				=> "Game address not found",
		self::CODE_NULL_POINTER_EXCEPTION				=> "Null pointer exception",
		self::CODE_SYSTEM_EXCEPTION						=> "System exception",
		self::CODE_SYSTEM_BUSY							=> "System busy",
		self::CODE_DATA_OPERATION_EXCEPTION				=> "Data operation exception",
		#internal response
		self::ERROR_REQUEST_METHOD_NOT_ALLOWED			=> "Request method not allowed",
		self::ERROR_API_METHOD_NOT_ALLOWED				=> "API Method not allowed",
		self::ERROR_CONNECTION_TIMED_OUT				=> "Connection Timeout",
		self::ERROR_INTERNAL_SERVER_ERROR				=> "Internal Server Error"
	];

	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS 										=> 200,
		self::ERROR_CONNECTION_TIMED_OUT 					=> 500,
		self::ERROR_REQUEST_METHOD_NOT_ALLOWED 				=> 405,
		self::ERROR_API_METHOD_NOT_ALLOWED 					=> 500,
		self::ERROR_INTERNAL_SERVER_ERROR 					=> 500,
		self::CODE_SUCCCESS 								=> 200,
		self::CODE_PARAMETER_ERROR 							=> 400,
		self::CODE_TOKEN_VERIFICATION_FAILED 				=> 401,
		self::CODE_USER_DOES_NOT_EXIST 						=> 500,
		self::CODE_INSUFFICIENT_BALANCE 					=> 500,
		self::CODE_OPERATION_FAILED 						=> 500,
		self::CODE_USER_DISABLED 							=> 500,
		self::CODE_NO_AGENT_ACCOUNT_FOUND 					=> 500,
		self::CODE_UNKNOWN_ERROR 							=> 500,
		self::CODE_SYSTEM_MAINTENANCE 						=> 500,
		self::CODE_API_KEY_ERROR 							=> 500,
		self::CODE_TRANSFER_SERIAL_NUMBER_OCCUPIED 			=> 500,
		self::CODE_TRANSFER_FAILED 							=> 500,
		self::CODE_PROXY_STATUS_UNAVAILABLE 				=> 500,
		self::CODE_CLIENT_IP_RESTRICTED 					=> 401,
		self::CODE_NETWORK_DELAY 							=> 500,
		self::CODE_CONNECTION_CLOSED 						=> 500,
		self::CODE_CLIENT_SOURCE_RESTRICTED 				=> 500,
		self::CODE_REQUESTED_RESOURCE_DOES_NOT_EXIST 		=> 500,
		self::CODE_REQUEST_TOO_FREQUENT 					=> 500,
		self::CODE_REQUEST_TIMEOUT 							=> 599,
		self::CODE_GAME_ADDRESS_NOT_FOUND 					=> 400,
		self::CODE_NULL_POINTER_EXCEPTION 					=> 500,
		self::CODE_SYSTEM_EXCEPTION 						=> 500,
		self::CODE_SYSTEM_BUSY 								=> 500,
		self::CODE_DATA_OPERATION_EXCEPTION 				=> 500,
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
	private $rules;
	private $transactions_table;
	private $remote_wallet_status = null;
	private $api_method=null;
	private $allow_token_validation;

	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'lightning_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));
		$this->requestMethod = $_SERVER['REQUEST_METHOD'];
		$this->trans_records = [];
		$this->game_platform_id = LIGHTNING_SEAMLESS_GAME_API;

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();
	}

	public function index($method = null)
	{
		$this->method = $this->api_method = $method;
		
		if ($this->initialize()) {
			return $this->selectMethod();			
		}else{
			$this->debug_log("LIGHTNING_SEAMLESS_SERVICE_API:", "Failed to initialized, check for error_log");
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API: raw request', $this->request);
		$validated = $this->validateInitialRequest();
		

		$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API: validateInitialRequest result', $validated);
		if(!$validated['success']){
			$this->setOutput($validated['code'], [
				"codeId" => $validated['code'],
				"message" => $validated['message'],
				"data" => []
			], $validated['success']);
		}

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		$this->allow_token_validation = $this->game_api->allow_token_validation;

		if($this->game_api->enable_write_response_result_to_dir){
			$this->utils->setConfig("write_response_result_to_dir", '/var/log/response_results');
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API - Config set write_response_result_to_dir value", $this->utils->getConfig("write_response_result_to_dir"));
		}

		$validated = $this->validateInitialRequestAfterGameLoaded();

		$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API: validateInitialRequestAfterGameLoaded result', $validated);

		if(!$validated['success']){
			$this->setOutput($validated['code'], [
				"codeId" => $validated['code'],
				"message" => $validated['message'],
				"data" => []
			], $validated['success']);
		}
		
		if(date('j', $this->utils->getTimestampNow()) <= $this->game_api->allowed_days_to_check_previous_monthly_table) {
			$this->checkPreviousMonth = true;
		}

		$this->use_third_party_token = $this->game_api->use_third_party_token;

		$this->transactions_table = $this->game_api->getTransactionsTable();
        $this->lightning_seamless_wallet_transactions->setTableName($this->transactions_table);  
		
		if(!$this->game_api){
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: (initialize) ERROR lOAD: ", $this->game_platform_id);
			http_response_code(500);
			return false;
		}

        $this->currency = $this->game_api->getCurrency();	

		return true;
	}

	#-----------------METHODS------------------------------
	public function selectMethod(){
		if($this->isAllowedApiMethod()){
			switch ($this->method) {
				case self::METHOD_GET_BALANCE:
					return $this->getBalance();
					break;
				case self::METHOD_BET_TRANSACT:
					return $this->betTransact();
					break;
				case self::METHOD_CANCEL_BET_TRANSACT:
					return $this->cancelBetTransact();
					break;
				case self::METHOD_REWARD_BET_TRANSACT:
					return $this->rewardBetTransact();
					break;
				case self::METHOD_ROLLBACK_TRANSACT:
					return $this->rollbackTransact();
					break;
				default:
					$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API: Invalid API Method');
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

	public function getBalance(){
		$type = self::METHOD_GET_BALANCE;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$code = self::CODE_SUCCCESS;
		$commonRules = $this->getCommonValidationRules();
		$customRules = ['userName' => 'required'];
		$this->rules = array_merge($commonRules, $customRules);
		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @getBalance: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try{
			$validated = $this->validateRequest();	
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @getBalance: validateRequest result: ", $validated);
			if(!$validated['success']){
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @getBalance", [$code, $message]);
				throw new Exception($validated['code']);
			}		
		
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @getBalance: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			
			if(!$player){
				$code = self::CODE_USER_DOES_NOT_EXIST;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @getBalance - User does not exist", [$code, $message]);
				throw new Exception(self::CODE_USER_DOES_NOT_EXIST);
			}
			$playerId = $player->player_id;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(\Exception $e){
			$code = $e->getMessage();
		}
		
		$data = [
			'codeId' => $code,
			'message' => $message,
			'data' => [
				'balance' => $balance
			]
		];
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function betTransact(){
		$type = self::METHOD_BET_TRANSACT;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [
			'userName' => 'required',
			'transactionId' => 'required',
			'amount' => 'required',
			'bets' => 'required'
		];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try{
			$validated = $this->validateRequest();	
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact: validateRequest result: ", $validated);
			if(!$validated['success']){
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact", [$code, $message]);
				throw new Exception($validated['code']);
			}		
			#token validation
			if($this->allow_token_validation){
				$extra['token'] = isset($this->request['token']) ? $this->request['token']: null;
				$extra['agentName'] = isset($this->request['agentName'])  ? $this->request['agentName'] : null;
				$extra['timestamp'] = isset($this->request['timestamp']) ? $this->request['timestamp'] :null;

				$verifiedToken= $this->verifyToken($extra['token'], $extra);

				if(!$verifiedToken){
					$code = self::CODE_TOKEN_VERIFICATION_FAILED;
					$message = $this->setResponseMessage($code);
					$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact - INVALID TOKEN", [$code, $message]);
					throw new Exception($code);
				}
			}

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			
			if(!$player){
				$code = self::CODE_USER_DOES_NOT_EXIST;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact - User does not exist", [$code, $message]);
				throw new Exception($code);
			}
			$playerId = $player->player_id;
			$amount 					= isset($this->request['amount']) ? $this->request['amount'] : 0;
			$bets 						= isset($this->request['bets']) ? $this->request['bets'] : [];
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);

			$extra['method'] 			= $type;
			$extra['transaction_id'] 		= isset($this->request['transactionId']) ? $this->request['transactionId'] : null;
			$extra['bet_ids'] 			= array_column($bets, 'id');
			$extra['total_amount'] 		= $amount;
			$extra['player_id'] 		= $player->player_id;
			$extra['player_username'] 	= $gameUsername;
			$extra['mode'] 				= self::DEBIT;
			$extra['action'] 			= self::ACTION_BET;
			$extra['room_id'] 			= isset($bets[0]['roomId']) ? $bets[0]['roomId'] : null; #game_code


			if($this->game_api->allow_multiple_saving_of_transactions_on_single_request){
				foreach($bets as $key => $bet){
					$item['raw_bet'] = $bet;
					$uniqueId = isset($bet['id']) ? $bet['id'] : null;
					$item['bet_id'] = $uniqueId;
					$item['method'] = $type;
					$item['mode'] = $extra['mode'];
					$item['room_id'] = isset($bet['roomId']) ? $bet['roomId'] : null;
					$item['external_uniqueid'] = 'bet-'.$uniqueId;
					$item['player_id'] = isset($extra['player_id']) ? $extra['player_id'] : null;
					$betAmount = isset($bet['bet']) ? $bet['bet'] : null;
					$item['bet_amount'] = abs($betAmount);
					$item['payout_amount'] = 0;
					$item['valid_amount'] = $item['bet_amount'];
					$item['result_amount'] = $item['payout_amount'] - $item['bet_amount'];
					if($key > 0){
						$before_balance-= $item['bet_amount'];
					}
			
					$after_balance = $before_balance - $item['bet_amount'];

					$item['before_balance'] = $before_balance;
					$item['after_balance'] = $after_balance;

					$params[] = array_merge($item, $bet);
			
					if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
						$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
						$this->wallet_model->setGameProviderBetAmount($betAmount);
					}

					if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
						$this->wallet_model->setGameProviderRoundId($uniqueId);
					}
					if( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
						$this->wallet_model->setGameProviderIsEndRound(false);
					}
					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
						$this->wallet_model->setRelatedActionOfSeamlessService(null);
					}
					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
						$this->wallet_model->setRelatedUniqueidOfSeamlessService(null);
					}
					
				}
				#temp comment lockj he
				$before_balance = $after_balance = $current_player_balance;
				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
						$extra,
						$params,
						&$insufficient_balance, 
						&$before_balance, 
						&$after_balance, 
						&$isAlreadyExists,
						&$additionalResponse) {
						list($trans_success,$before_balance, $after_balance, $insufficient_balance,$isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @betTransact lockAndTransForPlayerBalance",
							'trans_success',$trans_success,
							'before_balance',$before_balance,
							'after_balance',$after_balance,
							'insufficient_balance',$insufficient_balance,
							'isAlreadyExists',$isAlreadyExists,
							'additionalResponse',$additionalResponse,
							'isTransactionAdded',$isTransactionAdded
						);	
						return $trans_success;
					});
					$balance = $after_balance;

					if($insufficient_balance){						
						$code = self::CODE_INSUFFICIENT_BALANCE;
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact - Insufficient balance", [$code, $message]);
						throw new Exception($code);
					}

					if($isAlreadyExists){				
						$code = self::CODE_SUCCCESS; #return success if transaction already exists as per API docs
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact - Transaction already exists", [$code, $message]);
						throw new Exception($code);
					}
		
					if($trans_success==false){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @betTransact  lock failed", [$code, $message]);
						throw new Exception($code);
					}
			}
			$balance = $after_balance;

			$status = true;
		}catch(\Exception $e){
			$code = $e->getMessage();
		}
		
		$data = [
			'codeId' 	=> (int)$code,
			'message' 	=> $message,
			'data' 		=> [
				'balance' => floatval($this->game_api->gameAmountToDBTruncateNumber($balance))
			]
		];
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function rewardBetTransact(){
		$type = self::METHOD_REWARD_BET_TRANSACT;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$gameUsername = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [
			'userName' => 'required',
			'transactionId' => 'required',
			'amount' => 'required',
			'bets' => 'required'
		];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try{
			$validated = $this->validateRequest();	
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact: validateRequest result: ", $validated);
			if(!$validated['success']){
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact", [$code, $message]);
				throw new Exception($validated['code']);
			}	
			
			#token validation
			if($this->allow_token_validation){
				$extra['token'] = isset($this->request['token']) ? $this->request['token']: null;
				$extra['agentName'] = isset($this->request['agentName'])  ? $this->request['agentName'] : null;
				$extra['timestamp'] = isset($this->request['timestamp']) ? $this->request['timestamp'] :null;

				$verifiedToken= $this->verifyToken($extra['token'], $extra);

				if(!$verifiedToken){
					$code = self::CODE_TOKEN_VERIFICATION_FAILED;
					$message = $this->setResponseMessage($code);
					$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact - INVALID TOKEN", [$code, $message]);
					throw new Exception($code);
				}
			}
		
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			
			if(!$player){
				$code = self::CODE_USER_DOES_NOT_EXIST;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact - User does not exist", [$code, $message]);
				throw new Exception($code);
			}
			$playerId = $player->player_id;
			$amount 					= isset($this->request['amount']) ? $this->request['amount'] : 0;
			$bets 						= isset($this->request['bets']) ? $this->request['bets'] : [];
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);

			$extra['method'] 			= $type;
			$extra['transaction_id'] 		= isset($this->request['transactionId']) ? $this->request['transactionId'] : null;
			$extra['bet_ids'] 			= array_column($bets, 'id');
			$extra['total_amount'] 		= $amount;
			$extra['player_id'] 		= $player->player_id;
			$extra['player_username'] 	= $gameUsername;
			$extra['mode'] 				= self::CREDIT;
			$extra['action'] 			= self::ACTION_PAYOUT;
			$extra['room_id'] 			= isset($bets[0]['roomId']) ? $bets[0]['roomId'] : null; #game_code


			if($this->game_api->allow_multiple_saving_of_transactions_on_single_request){
				foreach($bets as $key => $bet){
					$item['raw_bet'] = $bet;
					$uniqueId = isset($bet['id']) ? $bet['id'] : null;
					$item['bet_id'] = $uniqueId;
					$item['method'] = self::METHOD_REWARD_BET_TRANSACT;
					$item['mode'] = $extra['mode'];
					$item['player_id'] = isset($extra['player_id']) ? $extra['player_id'] : null;
					$item['room_id'] = isset($bet['roomId']) ? $bet['roomId'] : null;
					$item['external_uniqueid'] = 'payout-'.$uniqueId;
					$betAmount = 0;
					$item['bet_amount'] = abs($betAmount);
					$payoutAmount = (isset($bet['prize']) && $bet['prize'] > 0) ? $bet['prize'] : 0; #if negative means loss , positve means won

					$item['payout_amount'] = $payoutAmount; 
					$item['valid_amount'] = $item['payout_amount'];
					$item['result_amount'] = $item['payout_amount'] - $item['bet_amount'];
				
					if($key > 0){
						$before_balance= $before_balance - $item['bet_amount'];
					}
			
					$before_balance - $item['bet_amount'];
					$after_balance = $before_balance + $item['payout_amount'];

					$item['before_balance'] = $before_balance;
					$item['after_balance'] = $after_balance;
	
					$item['related_bet'] = $related_bet = $this->lightning_seamless_wallet_transactions->getTransactionByParamsArray([
						'username' => $gameUsername,
						'external_uniqueid' => (string)'bet-'.$uniqueId,
					]);

					if(!$related_bet){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code, "System exception. Related bet does not exists");
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact - System exception. Related bet does not exist", [$code, $message]);
						throw new Exception($code);
					}

					#validate if bet already settled
					if(isset($related_bet['status']) && in_array($related_bet['status'], [Game_logs::STATUS_CANCELLED, Game_logs::STATUS_REFUND])){
						$code = self::CODE_PARAMETER_ERROR; #return success if related bet does not exist
						$message = $this->setResponseMessage($code, "Related bet already cancelled or refunded");
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - System exception. Related bet already cancelled/refunded", [$code, $message]);
						throw new Exception($code);
					}

					$related_bet_room_id = isset($related_bet['room_id']) ? $related_bet['room_id'] : null; 
					$item['room_id'] = empty($item['room_id']) ? $related_bet_room_id : $item['room_id'];

					$params[] = array_merge($item, $bet);
					$before_balance = $item['after_balance'];
			
					if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
						$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
					}
					if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
						$this->wallet_model->setGameProviderBetAmount($item['bet_amount']);
					}

					if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
						$this->wallet_model->setGameProviderPayoutAmount($item['payout_amount']);
					}

					if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
						$this->wallet_model->setGameProviderRoundId($uniqueId);
					}
					if( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
						$this->wallet_model->setGameProviderIsEndRound(true);
					}
					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && $related_bet) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && $related_bet) {
						$relatedUniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($related_bet['transaction_id'], self::DEBIT);
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
					}
				}
				$before_balance = $after_balance = $current_player_balance;
				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
						$extra,
						$params,
						&$insufficient_balance, 
						&$before_balance, 
						&$after_balance, 
						&$isAlreadyExists,
						&$additionalResponse) {
						list($trans_success,$before_balance, $after_balance, $insufficient_balance,$isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @rewardBetTransact lockAndTransForPlayerBalance",
							'trans_success',$trans_success,
							'before_balance',$before_balance,
							'after_balance',$after_balance,
							'insufficient_balance',$insufficient_balance,
							'isAlreadyExists',$isAlreadyExists,
							'additionalResponse',$additionalResponse,
							'isTransactionAdded',$isTransactionAdded
						);	
						return $trans_success;
					});
					$balance = $after_balance;

					if($isAlreadyExists){				
						$code = self::CODE_SUCCCESS; #return success if transaction already exists as per API docs
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact - Transaction already exists", [$code, $message]);
						throw new Exception($code);
					}
		
					if($trans_success==false){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rewardBetTransact  lock failed", [$code, $message]);
						throw new Exception($code);
					}
			}
			$balance = $after_balance;

			$status = true;
		}catch(\Exception $e){
			$code = $e->getMessage();
		}
		
		$data = [  
			'codeId' 	=> (int)$code,
			'message' 	=> $message,
			'data' 		=> [
				'balance' => floatval($this->game_api->gameAmountToDBTruncateNumber($balance))
			]
		];
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function cancelBetTransact(){
		$type = self::METHOD_CANCEL_BET_TRANSACT;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$gameUsername = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [
			'userName' => 'required',
			'transactionId' => 'required',
			'amount' => 'required',
			'bets' => 'required'
		];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try{
			$validated = $this->validateRequest();	
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact: validateRequest result: ", $validated);
			if(!$validated['success']){
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact", [$code, $message]);
				throw new Exception($validated['code']);
			}		

			#token validation
			if($this->allow_token_validation){
				$extra['token'] = isset($this->request['token']) ? $this->request['token']: null;
				$extra['agentName'] = isset($this->request['agentName'])  ? $this->request['agentName'] : null;
				$extra['timestamp'] = isset($this->request['timestamp']) ? $this->request['timestamp'] :null;

				$verifiedToken= $this->verifyToken($extra['token'], $extra);

				if(!$verifiedToken){
					$code = self::CODE_TOKEN_VERIFICATION_FAILED;
					$message = $this->setResponseMessage($code);
					$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact - INVALID TOKEN", [$code, $message]);
					throw new Exception($code);
				}
			}
		
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			
			if(!$player){
				$code = self::CODE_USER_DOES_NOT_EXIST;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - User does not exist", [$code, $message]);
				throw new Exception($code);
			}
			$playerId = $player->player_id;
			$amount 					= isset($this->request['amount']) ? $this->request['amount'] : 0;
			$bets 						= isset($this->request['bets']) ? $this->request['bets'] : [];
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);

			$extra['method'] 			= $type;
			$extra['transaction_id'] 		= isset($this->request['transactionId']) ? $this->request['transactionId'] : null;
			$extra['bet_ids'] 			= array_column($bets, 'id');
			$extra['total_amount'] 		= $amount;
			$extra['player_id'] 		= $player->player_id;
			$extra['player_username'] 	= $gameUsername;
			$extra['mode'] 				= self::CREDIT;
			$extra['action'] 			= self::ACTION_CANCEL;
			$extra['room_id'] 			= isset($bets[0]['roomId']) ? $bets[0]['roomId'] : null; #game_code


			if($this->game_api->allow_multiple_saving_of_transactions_on_single_request){
				foreach($bets as $key => $bet){
					$item['raw_bet'] = $bet;
					$uniqueId = isset($bet['id']) ? $bet['id'] : null;
					$item['bet_id'] = $uniqueId;
					$item['method'] = self::METHOD_CANCEL_BET_TRANSACT;
					$item['mode'] = $extra['mode'];
					$item['player_id'] = isset($extra['player_id']) ? $extra['player_id'] : null;
					$item['room_id'] = isset($bet['roomId']) ? $bet['roomId'] : null;
					$item['external_uniqueid'] = 'cancel-'.$uniqueId;
					$item['bet_amount'] = 0;
					$payoutAmount = isset($bet['amount']) ? abs($bet['amount']) : 0; #always positive
					$item['payout_amount'] = $payoutAmount; 
					$item['valid_amount'] = $item['payout_amount'];
					$item['result_amount'] = $item['payout_amount'] - $item['bet_amount'];
				
					if($key > 0){
						$before_balance= $before_balance - $item['bet_amount'];
					}
			
					$before_balance - $item['bet_amount'];
					$after_balance = $before_balance + $item['payout_amount'];

					$item['before_balance'] = $before_balance;
					$item['after_balance'] = $after_balance;
	
					$item['related_bet'] = $related_bet = $this->lightning_seamless_wallet_transactions->getTransactionByParamsArray([
						'username' => $gameUsername,
						'external_uniqueid' => (string)'bet-'.$uniqueId,
					]);

					$related_bet_room_id = isset($related_bet['room_id']) ? $related_bet['room_id'] : null; 
					$item['room_id'] = empty($item['room_id']) ? $related_bet_room_id : $item['room_id'];

					$params[] = array_merge($item, $bet);
					$before_balance = $item['after_balance'];

					if(!$related_bet){
						$code = self::SUCCESS; #return success if related bet does not exist
						$message = $this->setResponseMessage($code, "Operation Successful. Related bet does not exists");
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact - System exception. Related bet does not exist", [$code, $message]);
						throw new Exception($code);
					}
			
					if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
						$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
					}
					if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
						$this->wallet_model->setGameProviderBetAmount($item['bet_amount']);
					}

					if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
						$this->wallet_model->setGameProviderPayoutAmount($item['payout_amount']);
					}

					if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
						$this->wallet_model->setGameProviderRoundId($uniqueId);
					}
					if( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
						$this->wallet_model->setGameProviderIsEndRound(true);
					}
					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && $related_bet) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && $related_bet) {
						$relatedUniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($related_bet['bet_id'], self::DEBIT);
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
					}
				}
				$before_balance = $after_balance = $current_player_balance;
				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
						$extra,
						$params,
						&$insufficient_balance, 
						&$before_balance, 
						&$after_balance, 
						&$isAlreadyExists,
						&$additionalResponse) {
						list($trans_success,$before_balance, $after_balance, $insufficient_balance,$isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @cancelBetTransact lockAndTransForPlayerBalance",
							'trans_success',$trans_success,
							'before_balance',$before_balance,
							'after_balance',$after_balance,
							'insufficient_balance',$insufficient_balance,
							'isAlreadyExists',$isAlreadyExists,
							'additionalResponse',$additionalResponse,
							'isTransactionAdded',$isTransactionAdded
						);	
						return $trans_success;
					});
					$balance = $after_balance;

					if($isAlreadyExists){				
						$code = self::CODE_SUCCCESS; #return success if transaction already exists as per API docs
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact - Transaction already exists", [$code, $message]);
						throw new Exception($code);
					}
		
					if($trans_success==false){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact  lock failed", [$code, $message]);
						throw new Exception($code);
					}
			}
			$balance = $after_balance;

			$status = true;
		}catch(\Exception $e){
			$code = $e->getMessage();
		}
		
		$data = [
			'codeId' 	=> (int)$code,
			'message' 	=> $message,
			'data' 		=> [
				'balance' => floatval($this->game_api->gameAmountToDBTruncateNumber($balance))
			]
		];
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function cancelBetTransactOld(){
		$type = self::METHOD_CANCEL_BET_TRANSACT;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$gameUsername = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [
			'userName' => 'required',
			'transactionId' => 'required',
			'amount' => 'required',
			'bets' => 'required'
		];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try{
			$validated = $this->validateRequest();	
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact: validateRequest result: ", $validated);
			if(!$validated['success']){
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact", [$code, $message]);
				throw new Exception($validated['code']);
			}		
		
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();

			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			
			if(!$player){
				$code = self::CODE_USER_DOES_NOT_EXIST;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTrxansact - User does not exist", [$code, $message]);
				throw new Exception($code);
			}

			$amount 					= isset($this->request['amount']) ? $this->request['amount'] : 0;
			$params['total_amount'] 	= $amount;
			$params['player_id'] 		= $player->player_id;
			$params['player_username']	= $gameUsername;
			$params['related_bet'] 		= null;
			$params['mode'] 			= self::CREDIT;

			$bets = isset($this->request['bets']) ? $this->request['bets'] : [];

			if($this->game_api->allow_multiple_saving_of_transactions_on_single_request){
				foreach($bets as $bet){
					$params = array_merge($params,$bet);
					$params['method'] 				= self::METHOD_CANCEL_BET_TRANSACT;
					$params['raw_bet'] 				= $bet;
					$uniqueId 						= isset($bet['id']) ? $bet['id'] : null;
					$params['bet_id'] 				= $uniqueId;
					$params['external_uniqueid'] 	= 'cancel-'.$uniqueId;
					$rollbackAmount 				= isset($bet['amount']) ? $bet['amount'] : null;
					$params['bet_amount'] 			= 0;
					$params['payout_amount'] 		= $rollbackAmount;
					$params['valid_amount'] 		= $params['bet_amount'];
					$params['result_amount'] 		= $params['payout_amount'] - $params['bet_amount'];

					$params['related_bet'] = $related_bet = $this->lightning_seamless_wallet_transactions->getTransactionByParamsArray([
						'username' => $gameUsername,
						'external_uniqueid' => 'bet-'.$uniqueId,
					]);
					if(!$related_bet){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code, "System exception. Related bet does not exists");
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact - System exception. Related bet does not exist", [$code, $message]);
						throw new Exception($code);
					}
			
					if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
						$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
					}
					if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
						$this->wallet_model->setGameProviderBetAmount($params['bet_amount']);
					}

					if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
						$this->wallet_model->setGameProviderRoundId($uniqueId);
					}
					if( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
						$this->wallet_model->setGameProviderIsEndRound(true);
					}
					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && $related_bet) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && $related_bet) {
						$relatedUniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($related_bet['bet_id'], self::DEBIT);
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
					}
					$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
						$params,
						&$insufficient_balance, 
						&$before_balance, 
						&$after_balance, 
						&$isAlreadyExists,
						&$additionalResponse) {
						list($trans_success,$before_balance, $after_balance, $insufficient_balance,$isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance);
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @cancelBetTransact lockAndTransForPlayerBalance",
							'trans_success',$trans_success,
							'before_balance',$before_balance,
							'after_balance',$after_balance,
							'insufficient_balance',$insufficient_balance,
							'isAlreadyExists',$isAlreadyExists,
							'additionalResponse',$additionalResponse,
							'isTransactionAdded',$isTransactionAdded
						);	
						return $trans_success;
					});
					$balance = $after_balance;

					if($isAlreadyExists){				
						$code = self::CODE_SUCCCESS; #return success if transaction already exists as per API docs
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact - Transaction already exists", [$code, $message]);
						throw new Exception($code);
					}
		
					if($trans_success==false){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @cancelBetTransact  lock failed", [$code, $message]);
						throw new Exception($code);
					}
				}
			}
		
			$playerId = $player->player_id;
			$balance = $after_balance;

			$status = true;
		}catch(\Exception $e){
			$code = $e->getMessage();

			if($balance == null || $balance == 0){
				$balance = $this->getPlayerBalance($gameUsername, $playerId);
			}
		}
		
		$data = [
			'codeId' 	=> (int)$code,
			'message' 	=> $message,
			'data' 		=> [
				'balance' => floatval($this->game_api->gameAmountToDBTruncateNumber($balance))
			]
		];
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function rollbackTransact(){
		$type = self::METHOD_ROLLBACK_TRANSACT;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$gameUsername = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [
			'userName' => 'required',
			'transactionId' => 'required',
			'amount' => 'required',
			'bets' => 'required'
		];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try{
			$validated = $this->validateRequest();	
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact: validateRequest result: ", $validated);
			if(!$validated['success']){
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact", [$code, $message]);
				throw new Exception($validated['code']);
			}		
			
			#token validation
			if($this->allow_token_validation){
				$extra['token'] = isset($this->request['token']) ? $this->request['token']: null;
				$extra['agentName'] = isset($this->request['agentName'])  ? $this->request['agentName'] : null;
				$extra['timestamp'] = isset($this->request['timestamp']) ? $this->request['timestamp'] :null;

				$verifiedToken= $this->verifyToken($extra['token'], $extra);

				if(!$verifiedToken){
					$code = self::CODE_TOKEN_VERIFICATION_FAILED;
					$message = $this->setResponseMessage($code);
					$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - INVALID TOKEN", [$code, $message]);
					throw new Exception($code);
				}
			}
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			
			if(!$player){
				$code = self::CODE_USER_DOES_NOT_EXIST;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - User does not exist", [$code, $message]);
				throw new Exception($code);
			}
			$playerId = $player->player_id;
			$amount 					= isset($this->request['amount']) ? $this->request['amount'] : 0;
			$bets 						= isset($this->request['bets']) ? $this->request['bets'] : [];
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);

			$extra['method'] 			= $type;
			$extra['transaction_id'] 		= isset($this->request['transactionId']) ? $this->request['transactionId'] : null;
			$extra['bet_ids'] 			= array_column($bets, 'id');
			$extra['total_amount'] 		= $amount;
			$extra['player_id'] 		= $player->player_id;
			$extra['player_username'] 	= $gameUsername;
			$extra['mode'] 				= self::CREDIT;
			$extra['action'] 			= self::ACTION_ROLLBACK;
			$extra['room_id'] 			= isset($bets[0]['roomId']) ? $bets[0]['roomId'] : null; #game_code


			if($this->game_api->allow_multiple_saving_of_transactions_on_single_request){
				foreach($bets as $key => $bet){
					$item['raw_bet'] = $bet;
					$uniqueId = isset($bet['id']) ? $bet['id'] : null;
					$item['bet_id'] = $uniqueId;
					$item['method'] = self::METHOD_ROLLBACK_TRANSACT;
					$item['mode'] = $extra['mode'];
					$item['player_id'] = isset($extra['player_id']) ? $extra['player_id'] : null;
					$item['room_id'] = isset($bet['roomId']) ? $bet['roomId'] : null;
					$item['external_uniqueid'] = 'rollback-'.$uniqueId;
					$item['bet_amount'] = 0;
					$payoutAmount = isset($bet['bet']) ? abs($bet['bet']) : 0; #if negative means loss , positve means won
					$item['payout_amount'] = $payoutAmount; 
					$item['valid_amount'] = $item['payout_amount'];
					$item['result_amount'] = $item['payout_amount'] - $item['bet_amount'];
				
					if($key > 0){
						$before_balance= $before_balance - $item['bet_amount'];
					}
			
					$before_balance - $item['bet_amount'];
					$after_balance = $before_balance + $item['payout_amount'];

					$item['before_balance'] = $before_balance;
					$item['after_balance'] = $after_balance;
	
					$item['related_bet'] = $related_bet = $this->lightning_seamless_wallet_transactions->getTransactionByParamsArray([
						'username' => $gameUsername,
						'external_uniqueid' => (string)'bet-'.$uniqueId,
					]);

					$related_bet_room_id = isset($related_bet['room_id']) ? $related_bet['room_id'] : null; 
					$item['room_id'] = empty($item['room_id']) ? $related_bet_room_id : $item['room_id'];


					$params[] = array_merge($item, $bet);
					$before_balance = $item['after_balance'];

					if(!$related_bet){
						$code = self::SUCCESS; #return success if related bet does not exist
						$message = $this->setResponseMessage($code, "Operation Successful. Related bet does not exists");
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - System exception. Related bet does not exist", [$code, $message]);
						throw new Exception($code);
					}

					#validate if bet already settled
					if(isset($related_bet['status']) && $related_bet['status'] == Game_logs::STATUS_SETTLED){
						$code = self::CODE_PARAMETER_ERROR; #return success if related bet does not exist
						$message = $this->setResponseMessage($code, "Related bet already settled");
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - System exception. Related bet already settled", [$code, $message]);
						throw new Exception($code);
					}
			
					if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
						$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
					}
					if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
						$this->wallet_model->setGameProviderBetAmount($item['bet_amount']);
					}

					if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
						$this->wallet_model->setGameProviderPayoutAmount($item['payout_amount']);
					}

					if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
						$this->wallet_model->setGameProviderRoundId($uniqueId);
					}
					if( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
						$this->wallet_model->setGameProviderIsEndRound(true);
					}
					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && $related_bet) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && $related_bet) {
						$relatedUniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($related_bet['bet_id'], self::DEBIT);
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
					}
				}


				$before_balance = $after_balance = $current_player_balance;
				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
						$extra,
						$params,
						&$insufficient_balance, 
						&$before_balance, 
						&$after_balance, 
						&$isAlreadyExists,
						&$additionalResponse) {
						list($trans_success,$before_balance, $after_balance, $insufficient_balance,$isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @rollbackTransact lockAndTransForPlayerBalance",
							'trans_success',$trans_success,
							'before_balance',$before_balance,
							'after_balance',$after_balance,
							'insufficient_balance',$insufficient_balance,
							'isAlreadyExists',$isAlreadyExists,
							'additionalResponse',$additionalResponse,
							'isTransactionAdded',$isTransactionAdded
						);	
						return $trans_success;
					});
					$balance = $after_balance;

					if($isAlreadyExists){				
						$code = self::CODE_SUCCCESS; #return success if transaction already exists as per API docs
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact - Transaction already exists", [$code, $message]);
						throw new Exception($code);
					}
		
					if($trans_success==false){
						$code = self::CODE_SYSTEM_EXCEPTION;
						$message = $this->setResponseMessage($code);
						$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @rollbackTransact  lock failed", [$code, $message]);
						throw new Exception($code);
					}
			}
			$balance = $after_balance;

			$status = true;
		}catch(\Exception $e){
			$code = $e->getMessage();
		}
		
		$data = [
			'codeId' 	=> (int)$code,
			'message' 	=> $message,
			'data' 		=> [
				'balance' => floatval($this->game_api->gameAmountToDBTruncateNumber($balance))
			]
		];
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	private function handleDebitCreditAmount($params, &$before_balance, &$after_balance, $extra=null){
		$success = false;
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];

		$mode = isset($extra['mode']) ? $extra['mode'] : null;

		$unique_ids = $this->formatArrayOfUniqueIds($extra['bet_ids'], $extra['action']);

		$total_amount = isset($extra['total_amount']) ? $extra['total_amount'] : 0;
		if($mode == self::DEBIT){
			$insufficientBalance = $this->checkIfInsufficientBalance($before_balance, $total_amount);
			if($insufficientBalance){
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @handleDebitCreditAmount insufficient_balance: current_balance = $before_balance, balance to deduct = $total_amount");
				return [false,$before_balance, $after_balance, $insufficientBalance,$isAlreadyExists, $additionalResponse, $isTransactionAdded];
			}
		}
		
		#get all external_uniqueid and do a check
		$isAlreadyExists = $this->lightning_seamless_wallet_transactions->isTransactionsExist([
			'game_platform_id' => $this->game_platform_id, 
			'external_unique_ids' => $unique_ids
		]);

		if($isAlreadyExists){
			$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @handleDebitCreditAmount transaction already exist");
			return [false,$before_balance, $after_balance, $insufficientBalance,$isAlreadyExists, $additionalResponse, $isTransactionAdded];
		}
		$isTransactionAdded = $isAdded = $this->insertIgnoreTransactionRecord($params);

		if(!($isAdded)){
			$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: @handleDebitCreditAmount failed to insert transaction");
			return [false,$before_balance, $after_balance, $insufficientBalance,$isAlreadyExists, $additionalResponse, $isTransactionAdded];
		}

		$extra_data = $extra;
		$extra_data['before_balance'] = $before_balance;
		$extra_data['after_balance'] = $after_balance;

		$response_code = $this->adjustWallet($mode, $extra_data);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @handleDebitCreditAmount - adjustWallet response", $response_code);

		$success = $response_code['success'];
		$before_balance = $response_code['before_balance'];
		$after_balance	 = $response_code['after_balance'];
		$total_amount = isset($extra['total_amount']) ? $extra['total_amount'] : 0;
		$method = isset($extra['method']) ? $extra['method'] : null; 
		$transaction_id = isset($extra['transaction_id']) ? $extra['transaction_id'] : null; 
		
		if ($success) {
			$unique_ids = $this->formatArrayOfUniqueIds($extra['bet_ids'], self::ACTION_BET);

			$statusMapping = [
				self::METHOD_BET_TRANSACT => Game_logs::STATUS_PENDING,
				self::METHOD_CANCEL_BET_TRANSACT => Game_logs::STATUS_CANCELLED,
				self::METHOD_REWARD_BET_TRANSACT => Game_logs::STATUS_SETTLED,
				self::METHOD_ROLLBACK_TRANSACT => Game_logs::STATUS_REFUND
			];
			
			#update status here
			$status = isset($statusMapping[$method]) ? $statusMapping[$method] : Game_logs::STATUS_PENDING;
			
			if($method != self::METHOD_BET_TRANSACT){
				$updateStatus = $this->lightning_seamless_wallet_transactions->updateTransactionStatus([
					'status' => $status,
					'external_unique_ids' => $unique_ids,
					'game_platform_id' => $this->game_platform_id
				]);
				$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @handleDebitCreditAmount update status", $updateStatus);
			}
		}

		$success = true;
		return [$success,$before_balance, $after_balance, $insufficientBalance,$isAlreadyExists, $additionalResponse, $isTransactionAdded];
	}

	private function formatArrayofUniqueIds($ids, $action){
		return array_map(function($id) use($action){
			return $action.'-'.$id;
		}, $ids);
	}

	private function getPlayerDetails() {
		$playerStatus = $player = $gameUsername = $player_username = null;
	
		$userName = isset($this->request['userName']) ? $this->request['userName'] : null;
	
		if (!empty($userName)) {
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsername($userName);
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: getPlayerDetails result from USERNAME: ", [
				$playerStatus, $player, $gameUsername, $player_username
			]);
		}
	
		return [$playerStatus, $player, $gameUsername, $player_username];
	}

	private function validateRequest(){
		$success = true;
		$currency = $this->getCurrency();
		$agentName = isset($this->request['agentName']) ? $this->request['agentName'] : null;
		$code = self::CODE_SUCCCESS;
		$message = $this->setResponseMessage($code);
		if(!$this->isValidParams($this->request, $this->rules)){
			$success = false;
			$code = self::CODE_PARAMETER_ERROR;
			$message = $this->setResponseMessage($code);
			$this->setErrorLog($code, $message);
		}
		if($currency && !$this->isCurrencyCodeValid($currency)){
			$success = false;
			$code = self::CODE_PARAMETER_ERROR;
			$message = $this->setResponseMessage($code, "Parameter error. Currency code invalid");
			$this->setErrorLog($code, $message);
		}

		if($agentName && !$this->isAccountValid($agentName)){
			$success = false;
			$code = self::CODE_PARAMETER_ERROR;
			$message = $this->setResponseMessage($code, "Parameter error. Agent name invalid");
			$this->setErrorLog($code, $message);
		}
		return ['success' => $success, "code" => $code, "message" => $message];
	}

	private function getCurrency()
	{
		$currency = isset($this->request['currency']) ? $this->request['currency'] : null;
		return $currency ? $currency : (isset($this->request['bets'][0]['currency']) ? $this->request['bets'][0]['currency'] : null);
	}
	
	private function getCommonValidationRules($customRequiredFields=[]){
		return ['timestamp'=>'required','token' => 'required','agentName' => 'required'];
	}

	private function setErrorLog($message, $apiMethod=null){
		if($apiMethod === null){
			$this->api_method;
		}

		$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API @($apiMethod) :" . " - ". $message);
	}
	private function setDebugLog($message, $apiMethod=null){
		if($apiMethod === null){
			$this->api_method;
		}
		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @($apiMethod) :" . " - ". $message);
	}

	private function setResponseMessage($code, $forceMessage=false){
		if($forceMessage){
			return $forceMessage;
		}
		foreach(self::RESPONSE_CODES as $key => $value){
			if($code == $key){
				return self::RESPONSE_CODES[$key];
			}
		}
	} 

	private function validateInitialRequest(){
		$success = true;
		$code = self::CODE_SUCCCESS;
		$message = $this->setResponseMessage($code);
		if(!$this->checkIfGamePlatformIdIsValid()){
			$success = false;
			$code = self::CODE_SYSTEM_EXCEPTION;
			$message = $this->setResponseMessage($code, 'Invalid GamePlatformId');
			$this->setErrorLog($code, $message);
		}

		if(!$this->isRequestMethodValid(self::POST)){
			$success = false;
			$code = self::ERROR_REQUEST_METHOD_NOT_ALLOWED;
			$message = $this->setResponseMessage($code, 'Request method not allowed');
			$this->setErrorLog($code, $message);
		}

		return ['success' => $success, "code" => $code, "message" => $message];
	}

	private function validateInitialRequestAfterGameLoaded(){
		$success = true;
		$code = self::CODE_SUCCCESS;
		$message = $this->setResponseMessage($code);
		if(!$this->validateWhiteIP()){
			$success = false;
			$code = self::CODE_CLIENT_IP_RESTRICTED;
			$message = $this->setResponseMessage($code);
			$this->setErrorLog($code, $message);
		}
		if(!$this->isGameUnderMaintenance()){
			$success = false;
			$code = self::CODE_SYSTEM_MAINTENANCE;
			$message = $this->setResponseMessage($code, "Game under maintenance");
			$this->setErrorLog($code, $message);
		}
		if(!$this->isAllowedApiMethod()){
			$success = false;
			$code = self::CODE_OPERATION_FAILED;
			$message = $this->setResponseMessage($code, "Operation Failed. API method not allowed");
			$this->setErrorLog($code, $message);
		}

		return ['success' => $success, "code" => $code, "message" => $message];
	}
	#-----------------Validations--------------------------
	private function verifyToken($token, $extra=[]){
		$agentName = isset($extra['agentName']) ? $extra['agentName'] : null;
		$agentKey = isset($this->game_api->agent_key) ? $this->game_api->agent_key : null;
		$timestamp = isset($extra['timestamp']) ? $extra['timestamp'] : null;
		
		return $token == MD5($agentName.$agentKey.$timestamp);
	}

	private function checkIfGamePlatformIdIsValid(){
		return  !empty($this->game_platform_id);
	}

	public function isRequestMethodValid($validMethod)
	{
		return $this->requestMethod == $validMethod;
	}

	public function validateWhiteIP()
	{
		return $this->game_api->validateWhiteIP();
	}


	public function isGameUnderMaintenance()
	{
		return !$this->utils->setNotActiveOrMaintenance($this->game_platform_id);
	}

	public function isAllowedApiMethod()
	{
		$allowedApiMethods = array_map('strtolower', self::ALLOWED_API_METHODS);
		return in_array(strtolower($this->method), $allowedApiMethods);
	}

	
	public function isAccountValid($agentName){
		return $this->game_api->agent_name == $agentName;
    }



	public function isCurrencyCodeValid($currency){
        return strtolower($this->game_api->currency)==strtolower($currency);
    }

	private function checkIfInsufficientBalance($currentPlayerBalance, $amountToBeDeducted){
		return $currentPlayerBalance <= abs($amountToBeDeducted);
	}

	public function isValidSignature($data = [],$incomingSignature){
		$signature = md5(implode('',$data));
		return $signature == $incomingSignature;
	}	

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("LIGHTNING_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}
		}

		return true;
	}
	
	
	# ---------------Response-------------------------------
	//Function to return output and save response and request
	private function setOutput($code, $data = [], $status=true){
		$httpStatusCode = $this->getHttpStatusCode($code);
		$this->handleExternalResponse($status, $this->method, $this->request, $data, $code);
			 
		$this->output->set_content_type('application/json')
			->set_output(json_encode($data))
			->set_status_header($httpStatusCode);
		$this->output->_display();
		exit();
    }

	
	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = [])
	{
		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API (handleExternalResponse)", 'status', $status, 'type', $type, 'data', $data, 'response', $response, 'error_code', $error_code, 'fields', $fields);
		if (strpos($error_code, 'timed out') !== false) { 
			$this->utils->error_log(__METHOD__ . " (handleExternalResponse) Connection timed out.", 'status', $status, 'type', $type, 'data', $data, 'response', $response, 'error_code', $error_code, 'fields', $fields); $error_code = self::ERROR_CONNECTION_TIMED_OUT; 
		}

		$httpStatusCode = $this->getHttpStatusCode($error_code);

		if ($error_code == self::SUCCESS) {
			$httpStatusCode = 200;
		}

		if (empty($response)) {
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow() * 1000);
		$currentDateTime = date('Y-m-d H:i:s');
		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API save_response: ", $response);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$uniqueId = isset($data['transactionId']) ? $data['transactionId'] : null;
		$this->lightning_seamless_wallet_transactions->updateResponseResultRelated($uniqueId, ["responseResultId" => $this->response_result_id, 'elapsedTime' => $cost]);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => isset($this->ssa_request_params['round']) ? $this->ssa_request_params['round'] : null,
            'round_id' => isset($this->ssa_request_params['sessionId']) ? $this->ssa_request_params['sessionId'] : $this->ssa_request_params['round'],
            'external_game_id' => isset($this->ssa_request_params['game']) ? $this->ssa_request_params['game'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $this->api_method,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->ssa_original_request_params),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $this->external_unique_id,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($this->use_remote_wallet_failed_transaction_monthly_table) {
            $year_month = $this->utils->getThisYearMonth();
            $table_name = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$year_month}";
        } else {
            $table_name = $this->ssa_failed_remote_common_seamless_transactions_table;
        }

        if ($this->ssa_is_transaction_exists($table_name, ['external_uniqueid' => $save_data['external_uniqueid']])) {
            $query_type = $this->ssa_update;

            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $save_data['external_uniqueid'],
                ];
            }
        }

        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);
    }
    private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $statusText = null, $extra = null, $fields = [], $cost = null)
	{
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		if (is_array($response)) {
			$response = json_encode($response);
		}
		if (is_array($params)) {
			$params = json_encode($params);
		}
		$extra = array_merge((array)$extra, (array)$this->requestHeaders);
		return $this->response_result->saveResponseResult(
			$this->game_platform_id,
			$flag,
			$callMethod,
			$params,
			$response,
			$httpStatusCode,
			$statusText,
			is_array($extra) ? json_encode($extra) : $extra,
			$fields,
			false,
			null,
			$cost
		);
	}

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$this->request_json = $request_json = file_get_contents('php://input');
		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API raw parsed:", $request_json);
			$this->request = $request_json;
		}
		return $this->request;
	}

	public function getHttpStatusCode($errorCode)
	{
		$httpCode = self::HTTP_STATUS_CODE_MAP[self::SUCCESS];
		foreach (self::HTTP_STATUS_CODE_MAP as $key => $value) {
			if ($errorCode == $key) {
				$httpCode = $value;
		}
		}
		return $httpCode;
	}

	private function checkIfNegative($num){
		return $num >= 0;
	}

	private function debug_log($key, $value){
		$this->CI->utils->debug_log($key, $value);
	}

	private function getFormattedTimeStamp(){
		return (string)$this->game_api->getFormattedTimeStamp();
	}


	private function dumpData($data){
        print_r(json_encode($data));exit;
    }
	public function getPlayerByToken($token, $model="common_token"){

		$player = $this->$model->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API @getPlayerToken last_query: " . $this->CI->db->last_query());

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerCompleteDetailsByUsername($username){

		$player =  $this->lightning_seamless_wallet_transactions->getPlayerCompleteDetailsByUsername($username, $this->game_api->getPlatformCode());

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}
	public function getPlayerByPlayerId($playerId, $model="common_token"){

		$player = $this->$model->getPlayerCompleteDetailsByPlayerId($playerId, $this->game_platform_id);

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

		$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['player_id'];
		$before_balance = $after_balance = isset($params['before_balance']) ? $params['before_balance'] : 0;
		$response_code['before_balance'] = $response_code['after_balance'] = $this->game_api->dBtoGameAmount($before_balance);
		$response_code['success'] = true;
		$response_code['remote_wallet_status'] = null;
		$amount = isset($params['total_amount']) ? abs($params['total_amount']) : 0;
		$uniqueId = $params['unique_id'] = isset($params['transaction_id']) ? $params['transaction_id'] : null;
		$roomId = isset($params['room_id']) ? $params['room_id'] : null;

        if($action == self::DEBIT) {
			if($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($uniqueId, self::DEBIT);
				if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $roomId);
				}
				$amount = $this->game_api->gameAmountToDB($amount);

				$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API ADJUST_WALLET: AMOUNT', $amount);
				$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API ADJUST_WALLET: params - mode ($action)", 
					array("player_id" => $playerId, "game_platform_id" => $this->game_platform_id,"amount" => $amount, "after_balance" => $after_balance)
				);

				$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API params before debSubWallet", [
					'player_id' => $playerId,
					'game_platform_id' => $this->game_platform_id,
					'amount' => $amount,
					'after_balance' => $after_balance
				]);

				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $after_balance);

				$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API - deduct_balance result:', $deduct_balance);

				$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API.- balance after adjust_wallet:', $deduct_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->error_log('LIGHTNING_SEAMLESS_SERVICE_API: failed to deduct subwallet');
				}

				if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
					if($this->utils->isEnabledRemoteWalletClient()){
						$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
						$response_code['remote_wallet_status'] = $this->remote_wallet_status = $remoteErrorCode;
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @adjustWallet - remoteErrorCode: " , $remoteErrorCode);
					}
				}
            }

			if(is_null($after_balance)){
				$after_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
			} 
			$response_code["success"]  = true;
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {
			if($this->utils->compareResultFloat($amount, '=', 0)){
				$uniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($uniqueId, self::CREDIT);

				if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $roomId);
				}
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
			}elseif($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueIdOfSeamlessService =  $this->game_platform_id.'-'.$uniqueId .'-'. self::CREDIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $roomId);	
				$amount = $this->game_api->gameAmountToDB($amount);
				
				$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API ADJUST_WALLET: params - mode ($action)", 
					array("player_id" => $playerId, "game_platform_id" => $this->game_platform_id,"amount" => $amount, "after_balance" => $after_balance)
				);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);

				$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API - add_balance result:', $add_balance);
				
				$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API: ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('LIGHTNING_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }

				if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
					if($this->utils->isEnabledRemoteWalletClient()){
						$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
						$response_code['remote_wallet_status'] = $this->remote_wallet_status = $remoteErrorCode;
						$this->utils->debug_log("LIGHTNING_SEAMLESS_SERVICE_API: @adjustWallet - remoteErrorCode: " , $remoteErrorCode);
					}
				}
			}
			if(is_null($after_balance)){
				$after_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}
		return $response_code;
	}

	private function formatUniqueidOfSeamlessService($uniqueId, $mode){
		return $this->game_platform_id.'-'.$uniqueId.'-'. $mode;
	}

	public function insertIgnoreTransactionRecord($data){
		$trans_record = $this->makeTransactionRecord($data);

		$tableName = $this->game_api->getTransactionsTable();
        $this->lightning_seamless_wallet_transactions->setTableName($tableName);  
		return $this->lightning_seamless_wallet_transactions->batchInsertIgnoreWithLimit($tableName,$trans_record);
	}


	public function makeTransactionRecord($insert_data){
		$data = [];	
		foreach($insert_data as $raw_data){
				// print_r($raw_data);return true;
			$item['transaction_id']               = isset($this->request['transactionId']) ? $this->request['transactionId'] : null;
			$item['bet_id']              		  = isset($raw_data['bet_id']) ? $raw_data['bet_id'] : null;
			$item['username']                     = isset($this->request['userName']) ? $this->request['userName'] : null;
			// $item['bets']                         = isset($this->request['bets']) ? json_encode($this->request['bets']) : null;
			$item['room_id']                      = isset($raw_data['room_id']) ? $raw_data['room_id'] : null;
			$item['issue']                        = isset($raw_data['issue']) ? $raw_data['issue'] : null;
			$item['currency']                     = $this->game_api->currency;
			$item['gameType']                     = isset($raw_data['gameType']) ? $raw_data['gameType'] : null;
			$item['playType']                     = isset($raw_data['playType']) ? $raw_data['playType'] : null;
			$item['chipTypes']                    = isset($raw_data['chipTypes']) ? $raw_data['chipTypes'] : null;
			$item['bet']                          = isset($raw_data['bet']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet']) : null;
			$item['odds']                         = isset($raw_data['odds']) ? $raw_data['odds'] : null;
			$item['betTime']                      = isset($raw_data['betTime']) ? $raw_data['betTime'] : null;
			$item['updateTime']                   = isset($raw_data['updateTime']) ? $raw_data['updateTime'] : null;
			$item['game_status']                  = isset($raw_data['status']) ? $raw_data['status'] : null;
			$item['game_payout']                  = isset($this->request['gamePayout']) ? $raw_data['gamePayout'] : null;
			$item['prize']                        = isset($raw_data['prize']) ? $raw_data['prize'] : null;
			$item['poker']                        = isset($raw_data['poker']) ? $raw_data['poker'] : null;
			$item['agent_name']                   = isset($this->request['agentName']) ? $this->request['agentName'] : null;
			$item['timestamp']                    = isset($this->request['timestamp']) ? $this->request['timestamp'] : null;
			$item['token']                        = isset($this->request['token']) ? $this->request['token'] : null;

			$item['amount']                       = isset($raw_data['result_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['result_amount']) : 0;
			$item['bet_amount']                   = isset($raw_data['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet_amount']) : 0;
			$item['payout_amount']                = isset($raw_data['payout_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['payout_amount']): 0;
			$item['valid_amount']                 = isset($raw_data['valid_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['valid_amount']) : 0;
			$item['result_amount']                = isset($raw_data['result_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['result_amount']) : 0;
			$item['total_amount']                 = isset($raw_data['total_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['total_amount']) : 0;

			$item['player_id']                    = isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
			$item['trans_type']					= $raw_data['method'];


			$item['raw_data']                     = isset($this->request) ? json_encode($this->request) : null;
			$item['remote_wallet_status']         = null;
			$item['request_id']                   = $this->utils->getRequestId();
			$item['headers']                      = isset($this->requestHeaders) ? json_encode($this->requestHeaders) : null;
			$item['full_url']                     = $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']);
			$item['remote_raw_data']              = null;
			$item['win_amount']                   = isset($raw_data['payout_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['payout_amount']) : null;

			$item['balance_adjustment_amount']    = abs($item['result_amount']);
			$item['balance_adjustment_method']    = isset($raw_data['mode']) ? $raw_data['mode'] : null;
			$item['before_balance']               = isset($raw_data['before_balance']) ? $raw_data['before_balance'] : null;
			$item['after_balance']                = isset($raw_data['after_balance']) ? $raw_data['after_balance'] : null;
			$item['external_uniqueid']            = isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : null;
			$item['game_platform_id']             = $this->game_platform_id;
			$item['elapsed_time']                 = isset($raw_data['elapsedTime']) ? $raw_data['elapsedTime'] : null;

			$status = Game_logs::STATUS_SETTLED;
			if($item['trans_type'] == self::METHOD_BET_TRANSACT){
				$status = Game_logs::STATUS_PENDING;
			}elseif($item['trans_type'] == self::METHOD_CANCEL_BET_TRANSACT){
				$status = Game_logs::STATUS_CANCELLED;
			}elseif($item['trans_type'] == self::METHOD_REWARD_BET_TRANSACT){
				$status = Game_logs::STATUS_SETTLED;
			}elseif($item['trans_type'] == self::METHOD_ROLLBACK_TRANSACT){
				$status = Game_logs::STATUS_REFUND;
			}
			$item['status']                       = $status;
			$data[] = $item;
		}	

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

	public function formatUniqueId($method,$uniqueId)
	{
		switch($method){
			case self::METHOD_WITHDRAW:
				return strtolower(self::METHOD_WITHDRAW) . '-' . $uniqueId;
				break;
			case self::METHOD_DEPOSIT:
				return strtolower(self::METHOD_DEPOSIT) . '-' . $uniqueId;
				break;
			case self::METHOD_WITHDRAW_AND_DEPOSIT:
				return strtolower(self::METHOD_WITHDRAW_AND_DEPOSIT) . '-' . $uniqueId;
				break;
			case self::METHOD_ROLLBACK:
				return strtolower(self::METHOD_ROLLBACK) . '-' . $uniqueId;
				break;
		}
	}
}///END OF FILE////////////