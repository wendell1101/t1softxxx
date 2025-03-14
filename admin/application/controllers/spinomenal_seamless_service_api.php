<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * {{url}}/spinomenal_seamless_service_api/6318/GetPlayerInfo
 * {{url}}/spinomenal_seamless_service_api/6318/GetPlayerBalance
 * {{url}}/spinomenal_seamless_service_api/6318/ResetToken
 * {{url}}/spinomenal_seamless_service_api/6318/ProcessTransactions
 * {{url}}/spinomenal_seamless_service_api/6318/CheckTransactionExist

 */
class Spinomenal_seamless_service_api extends BaseController
{
	const METHOD_GET_PLAYER_INFO = 'getPlayerInfo';
	const METHOD_GET_PLAYER_BALANCE = 'getPlayerBalance';
	const METHOD_RESET_TOKEN = 'resetToken';
	const METHOD_PROCESS_TRANSACTIONS = 'processTransactions';
	const METHOD_CHECK_TRANSACTION_EXIST = 'checkTransactionExist';
	const METHOD_GENERATE_SIGNATURE = 'generateSignature';

	const ALLOWED_API_METHODS = [
		self::METHOD_GET_PLAYER_INFO,
		self::METHOD_GET_PLAYER_BALANCE,
		self::METHOD_RESET_TOKEN,
		self::METHOD_PROCESS_TRANSACTIONS,
		self::METHOD_CHECK_TRANSACTION_EXIST,
		self::METHOD_GENERATE_SIGNATURE
	];

	const TRANSACTION_TYPE_BET_AND_WIN = 'BetAndWin';
	const TRANSACTION_END_ROUND = 'EndRound';
	const TRANSACTION_TYPE_CANCEL_BET = 'CancelBet';
	const TRANSACTION_TYPE_WIN = 'Win';
	const TRANSACTION_TYPE_BONUS = 'Bonus';
	const TRANSACTION_TYPE_FREE_ROUNDS_START = 'FreeRounds_Start';
	const TRANSACTION_TYPE_FREE_ROUNDS_WIN = 'FreeRounds_Win';
	const TRANSACTION_TYPE_FREE_ROUNDS_END = 'FreeRounds_End';

	const ALLOWED_TRANSACTION_TYPES = [
		self::TRANSACTION_TYPE_BET_AND_WIN,
		self::TRANSACTION_END_ROUND,
		self::TRANSACTION_TYPE_CANCEL_BET,
		self::TRANSACTION_TYPE_WIN,
		self::TRANSACTION_TYPE_BONUS,
		self::TRANSACTION_TYPE_FREE_ROUNDS_START,
		self::TRANSACTION_TYPE_FREE_ROUNDS_WIN,
		self::TRANSACTION_TYPE_FREE_ROUNDS_END,
	];

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel'; 

	const REAL_PLAYER = 0;
    const TEST_USER = 1;
    const VIP_PLAYER = 1;

	const SUCCESS = 0;

	const ERROR_IP_NOT_WHITELISTED = 401;

	#GP ERROR CODES
	const CURRENCY_CODE_NOT_FOUND = 5007;
	const DIFFERENT_CURRENCY_ALREADY_ASSIGNED = 5008;
	const INVALID_PARAMETERS = 6001;
	const INVALID_SIGNATURE = 6002;
	const TOKEN_EXPIRED = 6003;
	const PLAYER_ACCOUNT_LOCKED = 6004;
	const RESPONSIBLE_GAMING_LIMIT = 6006;
	const INTERNAL_ERROR = 6008;

	const INSUFFICIENT_FUNDS = 6011;
	const WAGER_LIMIT = 6014;
	const ROUND_ALREADY_CLOSED = 5030;
	const FAILED_FATAL = 6030;

	const TRANSACTION_ID_NOT_FOUND = 6010;
	const UNSUPPORTED_TRANSACTION_TYPE = 6050;
	const INVALID_BET_NOT_WITH_TERMS_AND_CONDITIONS = 6016;
	const UNKNOWN_PLAYER_ID = 6007;
	const FR_INVALID_PROMO_ON_OPERATOR_SIDE = 7011;

	const ERROR_CODES = [
		self::CURRENCY_CODE_NOT_FOUND,
		self::DIFFERENT_CURRENCY_ALREADY_ASSIGNED,
		self::INVALID_PARAMETERS,
		self::INVALID_SIGNATURE,
		self::TOKEN_EXPIRED,
		self::PLAYER_ACCOUNT_LOCKED,
		self::RESPONSIBLE_GAMING_LIMIT,
		self::INTERNAL_ERROR,
		self::INSUFFICIENT_FUNDS,
		self::WAGER_LIMIT,
		self::ROUND_ALREADY_CLOSED,
		self::FAILED_FATAL,
		self::TRANSACTION_ID_NOT_FOUND,
		self::UNSUPPORTED_TRANSACTION_TYPE,
		self::INVALID_BET_NOT_WITH_TERMS_AND_CONDITIONS,
		self::UNKNOWN_PLAYER_ID
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

	private $remoteWallet = [
		'action_type' => 'bet',
		'round_id' => null,
		'is_end' => false,
		'bet_amount' => 0,
		'payout_amount' => 0,
		'external_game_id' => 0
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
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'spinomenal_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip', 'free_round_bonus_model'));

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
		}else{
			$code = self::ERROR_INTERNAL_SERVER_ERROR;
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
			$httpStatusCode = $this->getHttpStatusCode($code);
			http_response_code($httpStatusCode);
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code) . " - Failed to initialized, check error log"
			];

			return $this->setOutput($code, $data, false);
			$this->debug_log("SPINOMENAL_SEAMLESS_SERVICE_API:", "Failed to initialized, check for error_log");
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('SPINOMENAL_SEAMLESS_SERVICE_API: raw request');
		$validRequestMethod = self::POST;

		if(!$this->isRequestMethodValid($validRequestMethod)){
			return false;
		};

		if(!$this->checkIfGamePlatformIdIsValid()){
			return false;
		};
		
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		if(!$this->game_api){
			$code = self::ERROR_INTERNAL_SERVER_ERROR;
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
			$httpStatusCode = $this->getHttpStatusCode($code);
			http_response_code($httpStatusCode);
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code) . " - Invalid game_platform_id"
			];

			return $this->setOutput($code, $data, false);
		}

		$this->use_third_party_token = $this->game_api->use_third_party_token;

		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->spinomenal_seamless_wallet_transactions->setTableName($tableName);  

	
        $this->currency = $this->game_api->getCurrency();	
		
		if(!$this->validateWhiteIP()){
			return false;
		}

		if(!$this->isGameUnderMaintenance()){
			return false;
		}
		

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		return true;
	}

	#-----------------METHODS------------------------------
	public function selectMethod(){
		if($this->isAllowedApiMethod()){
			switch ($this->method) {
				case 'GetPlayerInfo':
					$this->getPlayerInfo();
					break;
				case 'GetPlayerBalance':
					$this->getBalance();
					break;
				case 'ResetToken':
					$this->resetToken();
					break;
				case 'ProcessTransactions':
					$this->processTransactions();
					break;
				case 'GenerateSignature':
					$this->generateSignature();
					break;
				case 'CheckTransactionExist':
					$this->checkTransactionExist();
					break;
				default:
					$this->utils->debug_log('Spinomenal seamless service: Invalid API Method');
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
	
	public function getPlayerInfo(){
		$type = self::METHOD_GET_PLAYER_INFO;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'GameToken' => 'required',
			'GameCode' => 'required',
			'PartnerId' => 'required',
			'Sig' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::INVALID_PARAMETERS;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (GetPlyerInfo)" . " - ". $errorDescription);
				throw new Exception($errorId);
			}

							
			if(!isset($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid partner id");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}

			
			if(!$this->isAccountValid($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}

			// get player details
			$token = $this->request['GameToken'];
			
			if($this->use_third_party_token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}	


			if(!$player){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player not found");
				$errorId = self::INTERNAL_ERROR;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INTERNAL_ERROR);
				throw new Exception($errorId);
			}

			if(!$playerStatus){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player account locked");
				$errorId = self::PLAYER_ACCOUNT_LOCKED;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::PLAYER_ACCOUNT_LOCKED);
				throw new Exception($errorId);
			}

			if($player){
				$playerId = $player->player_id;
			}	

			if(!$this->isValidSignature([$this->request['TimeStamp'], $this->request['GameToken'], $this->game_api->private_key], $this->request['Sig'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player invalid signature");
				$errorId = self::INVALID_SIGNATURE;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INVALID_SIGNATURE);
				throw new Exception($errorId);
			}


			$errorCode = self::SUCCESS;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [];
		if(!$hasError){
			if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
				$this->wallet_model->setUniqueidOfSeamlessService($this->request['GameCode']);
			}
			$data = [
				"PlayerInput" => [
					"PlayerId" => $gameUsername,
					"Currency" => $this->currency,
					"TimeStamp" => $this->getFormattedTimeStamp(),
					"TypeId" => self::REAL_PLAYER
				],
				"Balance" => $balance,
				'TimeStamp' => $this->getFormattedTimeStamp()
			];
		}

		$extra = [
			'ErrorCode' => (int)$errorCode,
			'ErrorMessage' => ($errorCode != 0) ? $this->getResponseErrorMessage($errorCode) : null,
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function checkTransactionExist(){
		$type = self::METHOD_CHECK_TRANSACTION_EXIST;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$transaction_id = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null; 

		$rules = [
			'PartnerId' => 'required',
			'Sig' => 'required',
			'TimeStamp' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::INVALID_PARAMETERS;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (checkTransactionExist)" . " - ". $errorDescription);
				throw new Exception($errorId);
			}
							
			if(!isset($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid partner id");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}

			if(!$this->isAccountValid($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}
			
			if(isset($this->request['PlayerId'])){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($this->request['PlayerId']);
				if(!$player){
					$errorId = self::UNKNOWN_PLAYER_ID;
					$hasError = 1;
					$errorDescription = $this->getResponseErrorMessage(self::UNKNOWN_PLAYER_ID);
					$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player does not exist");
					throw new Exception($errorId);
				}
			}

			if(!$player){
				$errorId = self::INTERNAL_ERROR;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INTERNAL_ERROR);
				throw new Exception($errorId);
			}

			if(!$playerStatus){
				$errorId = self::PLAYER_ACCOUNT_LOCKED;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::PLAYER_ACCOUNT_LOCKED);
				throw new Exception($errorId);
			}

			if($player){
				$playerId = $player->player_id;
			}


			$isRetry = isset($this->request['IsRetry']) && $this->request['IsRetry'] == true;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$existingRow = $this->spinomenal_seamless_wallet_transactions->getExistingTransaction($transaction_id);

			if(!$existingRow){
				$errorId = self::TRANSACTION_ID_NOT_FOUND;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage($errorId);
				throw new Exception($errorId);
			}

			$errorCode = self::SUCCESS;

			$status = true;
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
			if($errorCode == self::TRANSACTION_ID_NOT_FOUND){
				$status = true;
			}
		}

		$data = [
			"Balance" => $balance,
			'ExtTransactionId' => $transaction_id,
			'TimeStamp' => $this->getFormattedTimeStamp()
		];

		$except_ext_transaction_id = [self::TRANSACTION_TYPE_FREE_ROUNDS_START, self::TRANSACTION_TYPE_FREE_ROUNDS_WIN, self::TRANSACTION_TYPE_FREE_ROUNDS_END];

		if(in_array($this->request['TransactionType'], $except_ext_transaction_id)){
			unset($data['ExtTransactionId']);
		}
		$extra = [
			'ErrorCode' => (int)$errorCode,
			'ErrorMessage' => ($errorCode != 0) ? $this->getResponseErrorMessage($errorCode) : null,
		];

		$data = $this->formatResponse($data, $extra);
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function getBalance(){
		$type = self::METHOD_GET_PLAYER_BALANCE;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'GameToken' => 'required',
			'GameCode' => 'required',
			'PartnerId' => 'required',
			'Sig' => 'required',
			'TimeStamp' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::INVALID_PARAMETERS;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (GetPlayerBalance)" . " - ". $errorDescription);
				throw new Exception($errorId);
			}
							
			if(!isset($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid partner id");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}

			if(!$this->isAccountValid($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}
			// get player details
			$token = $this->request['GameToken'];
			
			if($this->use_third_party_token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}	

			if(!$player){
				$errorId = self::INTERNAL_ERROR;
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid token, player does not exists");
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INTERNAL_ERROR);
				throw new Exception($errorId);
			}

			if(!$playerStatus){
				$errorId = self::PLAYER_ACCOUNT_LOCKED;
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player account is blocked");
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::PLAYER_ACCOUNT_LOCKED);
				throw new Exception($errorId);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			// if(!$this->isValidSignature([$this->request['TimeStamp'], $this->request['GameToken'], $this->game_api->private_key], $this->request['Sig'])){
			// 	$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player invalid signature");
			// 	$errorId = self::INVALID_SIGNATURE;
			// 	$hasError = 1;
			// 	$errorDescription = $this->getResponseErrorMessage(self::INVALID_SIGNATURE);
			// 	throw new Exception($errorId);
			// }

			$errorCode = self::SUCCESS;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [];
		if(!$hasError){
			if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
				$this->wallet_model->setUniqueidOfSeamlessService($this->request['GameCode']);
			}
			$data = [
				"Balance" => $balance,
				'TimeStamp' => $this->getFormattedTimeStamp()
			];
		}

		$extra = [
			'ErrorCode' => (int)$errorCode,
			'ErrorMessage' => ($errorCode != 0) ? $this->getResponseErrorMessage($errorCode) : null,
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function resetToken(){
		$type = self::METHOD_GET_PLAYER_BALANCE;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$token = null;
		$newToken = null;
		$playerId = null;

		$rules = [
			'GameToken' => 'required',
			'GameCode' => 'required',
			'PartnerId' => 'required',
			'Sig' => 'required',
			'TimeStamp' => 'required',
			'TargetGameCode'=> 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::INVALID_PARAMETERS;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (ResetToken)" . " - ". $errorDescription);
				throw new Exception($errorId);
			}
							
			if(!isset($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid partner id");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}

			if(!$this->isAccountValid($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}
			// get player details
			$token = $this->request['GameToken'];
			
			if($this->use_third_party_token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}	


			if(!$player){
				$errorId = self::TOKEN_EXPIRED;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::TOKEN_EXPIRED);
				throw new Exception($errorId);
			}

			if(!$playerStatus){
				$errorId = self::PLAYER_ACCOUNT_LOCKED;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::PLAYER_ACCOUNT_LOCKED);
				throw new Exception($errorId);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			if(!$this->isValidSignature([$this->request['TimeStamp'], $this->request['GameToken'], $this->game_api->private_key], $this->request['Sig'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player invalid signature");
				$errorId = self::INVALID_SIGNATURE;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INVALID_SIGNATURE);
				throw new Exception($errorId);
			}
			
			list($newToken, $signkey) = $this->CI->common_token->createTokenWithSignKeyBy($playerId, 'player_id');

			//delete old token
			$this->CI->common_token->deleteToken($token);

			$errorCode = self::SUCCESS;
			$status = true;
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [];
		if(!$hasError){
			$data = [
				'TimeStamp' => $this->getFormattedTimeStamp(),
				"GameToken" => $newToken,				
			];
		}

		$extra = [
			'ErrorCode' => (int)$errorCode,
			'ErrorMessage' => ($errorCode != 0) ? $this->getResponseErrorMessage($errorCode) : null,
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function processTransactions(){
		$type = self::METHOD_PROCESS_TRANSACTIONS;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$token = null;
		$newToken = null;
		$gameUsername = null;
		$playerId = null;

		$free_round_trans_types = [self::TRANSACTION_TYPE_FREE_ROUNDS_START, self::TRANSACTION_TYPE_FREE_ROUNDS_WIN, self::TRANSACTION_TYPE_FREE_ROUNDS_END];

		$rules = [
			'GameCode' 				=> 'required',
			'PartnerId' 			=> 'required',
			'Sig' 					=> 'required',
			'TimeStamp' 			=> 'required',
			'GameToken' 			=> 'required',
			'TransactionId'			=> 'required',
			'RoundId'				=> 'required',
			'TransactionType' 		=> 'required',
			'ProviderCode' 			=> 'required',
			// 'TransactionDetails' 	=> 'required',
			'PlayerId' 				=> 'required',
		];		
		$isRetry = !empty($this->request['IsRetry']) && $this->request['IsRetry'] == true;
		if ($isRetry) {
			unset($rules['GameToken']);
		}
		
		$transactionType = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$transactionId = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		
		if($transactionType == self::TRANSACTION_TYPE_CANCEL_BET){
			$rules['RefTransactionId'] = 'required'; 
			unset($rules['TransactionDetails']);
		}
		
		if($transactionType == self::TRANSACTION_TYPE_BONUS){
			unset($rules['GameCode']);
			unset($rules['GameToken']);
		}

		$free_round_types = [self::TRANSACTION_TYPE_FREE_ROUNDS_START, self::TRANSACTION_TYPE_FREE_ROUNDS_WIN, self::TRANSACTION_TYPE_FREE_ROUNDS_END];
		if(in_array($transactionType, $free_round_types)){
			unset($rules['TransactionId']);
			unset($rules['PlayerId']);
			unset($rules['GameCode']);
		}	

		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError = 1;
				$errorId = self::INVALID_PARAMETERS;
				$errorDescription = 'Invalid Parameters';
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (processTransactions)" . " - ". $errorDescription);
				throw new Exception($errorId);
			}
							
			if(!isset($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid partner id");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid PartnerId';
				throw new Exception($errorId);
			}

			if(!$this->isAccountValid($this->request['PartnerId'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid Credentials");
				$hasError = 1;
				$errorId = self::INTERNAL_ERROR;
				$errorDescription = 'Invalid Credentials';
				throw new Exception($errorId);
			}

			$betAmount = isset($this->request['BetAmount']) ? $this->request['BetAmount'] : null;
			
			if(!$this->checkIfNegative($betAmount)){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Not accepting negative value for bet_amount");
				$hasError = 1;
				$errorId = self::INVALID_BET_NOT_WITH_TERMS_AND_CONDITIONS;
				$errorDescription = 'Not accepting negative value for bet_amount';
				throw new Exception($errorId);
			}


			if(isset($this->request['PlayerId'])){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($this->request['PlayerId']);
				if(!$player){
					$errorId = self::UNKNOWN_PLAYER_ID;
					$hasError = 1;
					$errorDescription = $this->getResponseErrorMessage(self::UNKNOWN_PLAYER_ID);
					$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Player does not exist");
					throw new Exception($errorId);
				}
			}
			// get player details
			if(($this->request['TransactionType'] == self::TRANSACTION_TYPE_BONUS || $isRetry == true) && isset($this->request['PlayerId'])){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($this->request['PlayerId']);
			}else{
				$token = $this->request['GameToken'];				
				if($this->use_third_party_token){
					list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
				}else{
					list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
				}	
			}
			if(!$isRetry){
				if(!$player){
					$errorId = self::TOKEN_EXPIRED;
					$hasError = 1;
					$errorDescription = $this->getResponseErrorMessage(self::TOKEN_EXPIRED);
					throw new Exception($errorId);
				}
			}

			if(!$playerStatus){
				$errorId = self::PLAYER_ACCOUNT_LOCKED;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::PLAYER_ACCOUNT_LOCKED);
				throw new Exception($errorId);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			if(!$this->isValidSignature([$this->request['TimeStamp'], $this->request['PlayerId'], $this->request['TransactionId'], $this->game_api->private_key], $this->request['Sig'])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: reset_token - invalid signature");
				$errorId = self::INVALID_SIGNATURE;
				$hasError = 1;
				$errorDescription = $this->getResponseErrorMessage(self::INVALID_SIGNATURE);
				throw new Exception($errorId);
			}

			$playerData = [
				'player_id' => $playerId,
				'player_username' => $player_username,
				'game_username' => $gameUsername,
				'player' => $player,
			];
			$params = $this->selectTransactionType($this->request['TransactionType'], $playerData);

			#check if free round already end
			if(in_array($transactionType, $free_round_trans_types)){
				$existingTrans = $this->spinomenal_seamless_wallet_transactions->getTransaction($transactionId);
				if($existingTrans){
					$isRoundFinish = isset($existingTrans->is_round_finish) ? $existingTrans->is_round_finish : false;
					if($isRoundFinish && !$isRetry){
						$errorId = self::FR_INVALID_PROMO_ON_OPERATOR_SIDE;
						$hasError = 1;
						$errorDescription = $this->getResponseErrorMessage($errorId);
						throw new Exception($errorId);
					}
				}
				#Check if used and finished from our db
				if($transactionType == self::TRANSACTION_TYPE_FREE_ROUNDS_WIN || self::TRANSACTION_TYPE_FREE_ROUNDS_END){
					$freeRoundsLeft = isset($this->request['TransactionDetails']['FreeRoundsDetails']['RoundsLeft']) ? $this->request['TransactionDetails']['FreeRoundsDetails']['RoundsLeft'] : 0;
					$freeRoundsAssignCode = isset($this->request['TransactionDetails']['FreeRoundsDetails']['FreeRoundsAssignCode']) ? $this->request['TransactionDetails']['FreeRoundsDetails']['FreeRoundsAssignCode'] : null;
					$freeRoundTrans = $this->free_round_bonus_model->queryTransaction($freeRoundsAssignCode, $this->game_api->getPlatformCode());
					
					if(!empty($freeRoundTrans)){
						if($freeRoundTrans['free_rounds'] == 0 && !$isRetry){
							$errorId = self::FR_INVALID_PROMO_ON_OPERATOR_SIDE;
							$hasError = 1;
							$errorDescription = $this->getResponseErrorMessage($errorId);
							throw new Exception($errorId);
						}

						$this->free_round_bonus_model->updateFreeRound([
							'free_rounds' => $freeRoundsLeft
						],$freeRoundsAssignCode, $this->game_api->getPlatformCode());
					}
				}
			}


			$params['raw_data'] = $this->request;

			if($params['insufficient_balance']){
				$hasError = 1;
				$errorCode = self::INSUFFICIENT_FUNDS;
				$errorDescription = $this->getResponseErrorMessage($errorCode);
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - Process Transactions: " . $errorDescription);
				throw new Exception($errorCode);
			}

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){
				if($params['transaction_type']  == self::TRANSACTION_TYPE_CANCEL_BET){
					$trans_success =  $this->handleRollback($params);
				}else{
					$trans_success = $this->debitCreditAmountToWallet($params);
				}
				return $trans_success;
			});	
			$status=$trans_success;

			$errorCode = self::SUCCESS;
			$balance = $this->getPlayerBalance($player_username, $playerId);
		
		}catch(Exception $error){
			$errorCode = $error->getMessage();
			$status = false;
		}

		$data = [];

		$data = [
			'Balance' => $balance,
			'ExtTransactionId' =>  $transactionId,
			'TimeStamp' => $this->getFormattedTimeStamp(),	
		];

		$extra = [
			'ErrorCode' => (int)$errorCode,
			'ErrorMessage' => ($errorCode != 0) ? $this->getResponseErrorMessage($errorCode) : null,
		];
		$trans_type = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$type = $trans_type;

		
		if(!empty($this->extra)){
			$extra = $this->extra;
		}

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$errorCode, $fields);
	}

	public function selectTransactionType($transactionType, $data){
		switch($transactionType){
			case self::TRANSACTION_TYPE_BET_AND_WIN:
				return $this->betAndWin($data);
				break;
			case self::TRANSACTION_END_ROUND:
				return $this->endRound($data);
				break;
			case self::TRANSACTION_TYPE_CANCEL_BET:
				return $this->cancelBet($data);
				break;
			case self::TRANSACTION_TYPE_WIN:
				return $this->win($data);
				break;
			case self::TRANSACTION_TYPE_BONUS:
				return $this->bonus($data);
				break;
			case self::TRANSACTION_TYPE_FREE_ROUNDS_START:
				return $this->freeRoundStart($data);
				break;
			case self::TRANSACTION_TYPE_FREE_ROUNDS_WIN:
				return $this->freeRoundStart($data);
				break;
			case self::TRANSACTION_TYPE_FREE_ROUNDS_END:
				return $this->freeRoundStart($data);
				break;
			default:
				$this->utils->debug_log('Spinomenal seamless service: Invalid API Method');
				http_response_code(404);	
		}
	}

	public function betAndWin($data){
		$params = [];
		#player data 
		$params['operator_player_id']				= $data['player_id'];
		$params['operator_player_username']			= $data['player_username'];
		$params['operator_game_username']			= $data['game_username'];
		#required params
		$params['game_code'] 						= isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
		$params['partner_id'] 						= isset($this->request['PartnerId']) ? $this->request['PartnerId'] : null;
		$params['sig'] 								= isset($this->request['Sig']) ? $this->request['Sig'] : null;
		$params['timestamp']						= isset($this->request['TimeStamp']) ? $this->request['TimeStamp'] : null;
		$params['game_token']						= isset($this->request['GameToken']) ? $this->request['GameToken'] : null;
		$params['transaction_id']					= isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;
		$params['transaction_type']					= isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['provider_code']					= isset($this->request['ProviderCode']) ? $this->request['ProviderCode'] : null;
		$params['transaction_details']				= isset($this->request['TransactionDetails']) ? $this->request['TransactionDetails'] : null;
		$params['player_id']						= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		#optional params
		$params['bet_amount']						= isset($this->request['BetAmount']) ? $this->request['BetAmount'] : 0;
		$params['win_amount']						= isset($this->request['WinAmount']) ? $this->request['WinAmount'] : 0;			
		$params['currency']							= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$params['is_round_finish']					= isset($this->request['IsRoundFinish']) ? $this->request['IsRoundFinish'] : null;
		$params['is_retry']							= isset($this->request['IsRetry']) ? $this->request['IsRetry'] : null;
		$params['sub_game_code']					= isset($this->request['SubGameCode']) ? $this->request['SubGameCode'] : null;
		$params['external_uniqueid']                = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['trans_type']                       = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;


		#check if currentPlayerBalance > bet_amount
		$insufficient_balance = false;
		$currentPlayerBalance = $this->getPlayerBalance($data['player_username'], $data['player_id']);
		if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['bet_amount'])){
			$insufficient_balance = true;
			$hasError = 1;
			$errorCode = self::INSUFFICIENT_FUNDS;
			$errorDescription = $this->getResponseErrorMessage($errorCode);
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - ProcessTransactions - (BetAndWin):" . $errorDescription);
		}			
		$params['insufficient_balance'] = $insufficient_balance;

		$mode = null;
		$bet = $params['bet_amount'];
		$win = $params['win_amount'];

		list($finalAmount, $mode) = $this->getAmountAndMode($bet, $win);

		$params['insufficient_balance'] = $insufficient_balance;
		$params['mode'] = $mode;
		$params['final_amount'] = $finalAmount;
		$params['current_player_balance'] = $currentPlayerBalance;

		$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;
		$this->remoteWallet['is_end'] = $params['is_round_finish'];
		$this->remoteWallet['round_id'] = $params['round_id'];
		$this->remoteWallet['bet_amount'] = $params['bet_amount'];
		$this->remoteWallet['payout_amount'] = $params['win_amount'];
		$this->remoteWallet['external_game_id'] = $params['transaction_id'];

		return $params;
	}

	private function getAmountAndMode($betAmount, $winAmount) {
		$winAmount = abs($winAmount);
		$amount = $winAmount - $betAmount;
		
		if ($amount > 0) {
			$mode = self::CREDIT;
		} else {
			$mode = self::DEBIT;
		}
		
		return [$amount, $mode];
	}

	public function endRound($data){
		$params = [];
		#player data 
		$params['operator_player_id']				= $data['player_id'];
		$params['operator_player_username']			= $data['player_username'];
		$params['operator_game_username']			= $data['game_username'];
		#required params
		$params['game_code'] 						= isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
		$params['partner_id'] 						= isset($this->request['PartnerId']) ? $this->request['PartnerId'] : null;
		$params['sig'] 								= isset($this->request['Sig']) ? $this->request['Sig'] : null;
		$params['timestamp']						= isset($this->request['TimeStamp']) ? $this->request['TimeStamp'] : null;
		$params['game_token']						= isset($this->request['GameToken']) ? $this->request['GameToken'] : null;
		$params['transaction_id']					= isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;
		$params['transaction_type']					= isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['provider_code']					= isset($this->request['ProviderCode']) ? $this->request['ProviderCode'] : null;
		$params['transaction_details']				= isset($this->request['TransactionDetails']) ? $this->request['TransactionDetails'] : null;
		$params['player_id']						= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		#optional params
		$params['bet_amount']						= isset($this->request['BetAmount']) ? $this->request['BetAmount'] : 0;
		$params['win_amount']						= isset($this->request['WinAmount']) ? $this->request['WinAmount'] : 0;			
		$params['currency']							= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$params['is_round_finish']					= isset($this->request['IsRoundFinish']) ? $this->request['IsRoundFinish'] : null;
		$params['is_retry']							= isset($this->request['IsRetry']) ? $this->request['IsRetry'] : null;
		$params['sub_game_code']					= isset($this->request['SubGameCode']) ? $this->request['SubGameCode'] : null;
		$params['external_uniqueid']                = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['trans_type']                       = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;

		#check if currentPlayerBalance > bet_amount
		$insufficient_balance = false;
		$currentPlayerBalance = $this->getPlayerBalance($data['player_username'], $data['player_id']);
		if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['bet_amount'])){
			$insufficient_balance = true;
			$hasError = 1;
			$errorCode = self::INSUFFICIENT_FUNDS;
			$errorDescription = $this->getResponseErrorMessage($errorCode);
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - ProcessTransactions - (BetAndWin):" . $errorDescription);
		}			
		$params['insufficient_balance'] = $insufficient_balance;

		$mode = null;
		$bet = $params['bet_amount'];
		$win = $params['win_amount'];

		list($finalAmount, $mode) = $this->getAmountAndMode($bet, $win);

		$params['insufficient_balance'] = $insufficient_balance;
		$params['mode'] = $mode;
		$params['final_amount'] = $finalAmount;
		$params['current_player_balance'] = $currentPlayerBalance;

		$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;
		$this->remoteWallet['is_end'] = $params['is_round_finish'];
		$this->remoteWallet['round_id'] = $params['round_id'];
		$this->remoteWallet['bet_amount'] = $params['bet_amount'];
		$this->remoteWallet['payout_amount'] = $params['win_amount'];
		$this->remoteWallet['external_game_id'] = $params['transaction_id']	;
		

		$refTransactionId = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;

		$relatedBetTransaction = $this->spinomenal_seamless_wallet_transactions->getTransactionByParams([
			'external_uniqueid' => $refTransactionId,
			'player_id' => isset($params['operator_player_id']) ? $params['operator_player_id'] : null
		]);
		if($relatedBetTransaction){
			$relatedBetUniqueId = $this->formatRemoteUniqueId($relatedBetTransaction['external_uniqueid']);
			$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);	
			$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetUniqueId);
		}
		return $params;
	}
	public function cancelBet($data){
		$params = [];
		#player data 
		$params['operator_player_id']				= $data['player_id'];
		$params['operator_player_username']			= $data['player_username'];
		$params['operator_game_username']			= $data['game_username'];
		#required params
		$params['game_code'] 						= isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
		$params['partner_id'] 						= isset($this->request['PartnerId']) ? $this->request['PartnerId'] : null;
		$params['sig'] 								= isset($this->request['Sig']) ? $this->request['Sig'] : null;
		$params['timestamp']						= isset($this->request['TimeStamp']) ? $this->request['TimeStamp'] : null;
		$params['game_token']						= isset($this->request['GameToken']) ? $this->request['GameToken'] : null;
		$params['transaction_id']					= isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;
		$params['transaction_type']					= isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['provider_code']					= isset($this->request['ProviderCode']) ? $this->request['ProviderCode'] : null;
		$params['transaction_details']				= isset($this->request['TransactionDetails']) ? $this->request['TransactionDetails'] : null;
		$params['player_id']						= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		#optional params
		$params['bet_amount']						= isset($this->request['BetAmount']) ? $this->request['BetAmount'] : 0;
		$params['win_amount']						= isset($this->request['WinAmount']) ? $this->request['WinAmount'] : 0;			
		$params['currency']							= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$params['is_round_finish']					= isset($this->request['IsRoundFinish']) ? $this->request['IsRoundFinish'] : null;
		$params['is_retry']							= isset($this->request['IsRetry']) ? $this->request['IsRetry'] : null;
		$params['sub_game_code']					= isset($this->request['SubGameCode']) ? $this->request['SubGameCode'] : null;
		$params['external_uniqueid']                = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['trans_type']                       = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['ref_transaction_id']               = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;


		#check if currentPlayerBalance > bet_amount
		$insufficient_balance = false;
		$currentPlayerBalance = $this->getPlayerBalance($data['player_username'], $data['player_id']);
		if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['bet_amount'])){
			$insufficient_balance = true;
			$hasError = 1;
			$errorCode = self::INSUFFICIENT_FUNDS;
			$errorDescription = $this->getResponseErrorMessage($errorCode);
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - ProcessTransactions - (CancelBet):" . $errorDescription);
		}			
		$params['insufficient_balance'] = $insufficient_balance;

		$mode = null;
		$bet = $params['bet_amount'];
		$win = $params['win_amount'];
		list($finalAmount, $mode) = $this->getAmountAndMode($bet, $win);
		$params['insufficient_balance'] = $insufficient_balance;
		$params['mode'] = $mode;
		$params['final_amount'] = $finalAmount;
		$params['current_player_balance'] = $currentPlayerBalance;

		
		$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
		$this->remoteWallet['is_end'] = $params['is_round_finish'];
		$this->remoteWallet['round_id'] = $params['round_id'];
		$this->remoteWallet['bet_amount'] = $params['bet_amount'];
		$this->remoteWallet['payout_amount'] = $params['win_amount'];
		$this->remoteWallet['external_game_id'] = $params['transaction_id'];

		$refTransactionId = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;

		$relatedBetTransaction = $this->spinomenal_seamless_wallet_transactions->getTransactionByParams([
			'external_uniqueid' => $refTransactionId,
			'player_id' => isset($params['operator_player_id']) ? $params['operator_player_id'] : null
		]);
		if($relatedBetTransaction){
			$relatedBetUniqueId = $this->formatRemoteUniqueId($relatedBetTransaction['external_uniqueid']);
			$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);	
			$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetUniqueId);	
		}
		return $params;
	}

	public function win($data){
		$params = [];
		#player data 
		$params['operator_player_id']				= $data['player_id'];
		$params['operator_player_username']			= $data['player_username'];
		$params['operator_game_username']			= $data['game_username'];
		#required params
		$params['game_code'] 						= isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
		$params['partner_id'] 						= isset($this->request['PartnerId']) ? $this->request['PartnerId'] : null;
		$params['sig'] 								= isset($this->request['Sig']) ? $this->request['Sig'] : null;
		$params['timestamp']						= isset($this->request['TimeStamp']) ? $this->request['TimeStamp'] : null;
		$params['game_token']						= isset($this->request['GameToken']) ? $this->request['GameToken'] : null;
		$params['transaction_id']					= isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;
		$params['transaction_type']					= isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['provider_code']					= isset($this->request['ProviderCode']) ? $this->request['ProviderCode'] : null;
		$params['transaction_details']				= isset($this->request['TransactionDetails']) ? $this->request['TransactionDetails'] : null;
		$params['player_id']						= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		#optional params
		$params['bet_amount']						= isset($this->request['BetAmount']) ? $this->request['BetAmount'] : 0;
		$params['win_amount']						= isset($this->request['WinAmount']) ? $this->request['WinAmount'] : 0;			
		$params['currency']							= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$params['is_round_finish']					= isset($this->request['IsRoundFinish']) ? $this->request['IsRoundFinish'] : null;
		$params['is_retry']							= isset($this->request['IsRetry']) ? $this->request['IsRetry'] : null;
		$params['sub_game_code']					= isset($this->request['SubGameCode']) ? $this->request['SubGameCode'] : null;
		$params['external_uniqueid']                = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['trans_type']                       = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;


		#check if currentPlayerBalance > bet_amount
		$insufficient_balance = false;
		$currentPlayerBalance = $this->getPlayerBalance($data['player_username'], $data['player_id']);
		if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['bet_amount'])){
			$insufficient_balance = true;
			$hasError = 1;
			$errorCode = self::INSUFFICIENT_FUNDS;
			$errorDescription = $this->getResponseErrorMessage($errorCode);
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - ProcessTransactions - (BetAndWin):" . $errorDescription);
		}			
		$params['insufficient_balance'] = $insufficient_balance;

		$mode = null;
		$bet = $params['bet_amount'];
		$win = $params['win_amount'];
		list($finalAmount, $mode) = $this->getAmountAndMode($bet, $win);

		$params['insufficient_balance'] = $insufficient_balance;
		$params['mode'] = $mode;
		$params['final_amount'] = $finalAmount;
		$params['current_player_balance'] = $currentPlayerBalance;

		$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
		$this->remoteWallet['is_end'] = $params['is_round_finish'];
		$this->remoteWallet['round_id'] = $params['round_id'];
		$this->remoteWallet['bet_amount'] = $params['bet_amount'];
		$this->remoteWallet['payout_amount'] = $params['win_amount'];
		$this->remoteWallet['external_game_id'] = $params['transaction_id'];

		$refTransactionId = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;

		$relatedBetTransaction = $this->spinomenal_seamless_wallet_transactions->getTransactionByParams([
			'external_uniqueid' => $refTransactionId,
			'player_id' => isset($params['operator_player_id']) ? $params['operator_player_id'] : null
		]);
		if($relatedBetTransaction){
			$relatedBetUniqueId = $this->formatRemoteUniqueId($relatedBetTransaction['external_uniqueid']);
			$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);	
			$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetUniqueId);
		}
		return $params;
	}

	public function bonus($data){
		$params = [];
		#player data 
		$params['operator_player_id']				= $data['player_id'];
		$params['operator_player_username']			= $data['player_username'];
		$params['operator_game_username']			= $data['game_username'];
		#required params
		$params['game_code'] 						= isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
		$params['partner_id'] 						= isset($this->request['PartnerId']) ? $this->request['PartnerId'] : null;
		$params['sig'] 								= isset($this->request['Sig']) ? $this->request['Sig'] : null;
		$params['timestamp']						= isset($this->request['TimeStamp']) ? $this->request['TimeStamp'] : null;
		$params['game_token']						= isset($this->request['GameToken']) ? $this->request['GameToken'] : null;
		$params['transaction_id']					= isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;
		$params['transaction_type']					= isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['provider_code']					= isset($this->request['ProviderCode']) ? $this->request['ProviderCode'] : null;
		$params['transaction_details']				= isset($this->request['TransactionDetails']) ? $this->request['TransactionDetails'] : null;
		$params['player_id']						= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		#optional params
		$params['bet_amount']						= isset($this->request['BetAmount']) ? $this->request['BetAmount'] : 0;
		$params['win_amount']						= isset($this->request['WinAmount']) ? $this->request['WinAmount'] : 0;			
		$params['currency']							= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$params['is_round_finish']					= isset($this->request['IsRoundFinish']) ? $this->request['IsRoundFinish'] : null;
		$params['is_retry']							= isset($this->request['IsRetry']) ? $this->request['IsRetry'] : null;
		$params['sub_game_code']					= isset($this->request['SubGameCode']) ? $this->request['SubGameCode'] : null;
		$params['external_uniqueid']                = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['trans_type']                       = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;


		#check if currentPlayerBalance > bet_amount
		$insufficient_balance = false;
		$currentPlayerBalance = $this->getPlayerBalance($data['player_username'], $data['player_id']);
		if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['bet_amount'])){
			$insufficient_balance = true;
			$hasError = 1;
			$errorCode = self::INSUFFICIENT_FUNDS;
			$errorDescription = $this->getResponseErrorMessage($errorCode);
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - ProcessTransactions - (BetAndWin):" . $errorDescription);
		}			
		$params['insufficient_balance'] = $insufficient_balance;

		$mode = null;
		$bet = $params['bet_amount'];
		$win = $params['win_amount'];
		list($finalAmount, $mode) = $this->getAmountAndMode($bet, $win);

		$params['insufficient_balance'] = $insufficient_balance;
		$params['mode'] = $mode;
		$params['final_amount'] = $finalAmount;
		$params['current_player_balance'] = $currentPlayerBalance;

		$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
		$this->remoteWallet['is_end'] = $params['is_round_finish'];
		$this->remoteWallet['round_id'] = $params['round_id'];
		$this->remoteWallet['bet_amount'] = $params['bet_amount'];
		$this->remoteWallet['payout_amount'] = $params['win_amount'];
		$this->remoteWallet['external_game_id'] = $params['transaction_id'];

		$refTransactionId = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;

		$relatedBetTransaction = $this->spinomenal_seamless_wallet_transactions->getTransactionByParams([
			'external_uniqueid' => $refTransactionId,
			'player_id' => isset($params['operator_player_id']) ? $params['operator_player_id'] : null
		]);

		if($relatedBetTransaction){
			$relatedBetUniqueId = $this->formatRemoteUniqueId($relatedBetTransaction['external_uniqueid']);
			$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);	
			$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetUniqueId);	
		}
		return $params;
	}

	public function freeRoundStart($data){
		$params = [];
		#player data 
		$params['operator_player_id']				= $data['player_id'];
		$params['operator_player_username']			= $data['player_username'];
		$params['operator_game_username']			= $data['game_username'];
		#required params
		$params['game_code'] 						= isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
		$params['partner_id'] 						= isset($this->request['PartnerId']) ? $this->request['PartnerId'] : null;
		$params['sig'] 								= isset($this->request['Sig']) ? $this->request['Sig'] : null;
		$params['timestamp']						= isset($this->request['TimeStamp']) ? $this->request['TimeStamp'] : null;
		$params['game_token']						= isset($this->request['GameToken']) ? $this->request['GameToken'] : null;
		$params['transaction_id']					= isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['round_id']							= isset($this->request['RoundId']) ? $this->request['RoundId'] : null;
		$params['transaction_type']					= isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;
		$params['provider_code']					= isset($this->request['ProviderCode']) ? $this->request['ProviderCode'] : null;
		$params['transaction_details']				= isset($this->request['TransactionDetails']) ? $this->request['TransactionDetails'] : null;
		$params['player_id']						= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		#optional params
		$params['bet_amount']						= isset($this->request['BetAmount']) ? $this->request['BetAmount'] : 0;
		$params['win_amount']						= isset($this->request['WinAmount']) ? $this->request['WinAmount'] : 0;			
		$params['currency']							= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$params['is_round_finish']					= isset($this->request['IsRoundFinish']) ? $this->request['IsRoundFinish'] : null;
		$params['is_retry']							= isset($this->request['IsRetry']) ? $this->request['IsRetry'] : null;
		$params['sub_game_code']					= isset($this->request['SubGameCode']) ? $this->request['SubGameCode'] : null;
		$params['external_uniqueid']                = isset($this->request['TransactionId']) ? $this->request['TransactionId'] : null;
		$params['trans_type']                       = isset($this->request['TransactionType']) ? $this->request['TransactionType'] : null;


		#check if currentPlayerBalance > bet_amount
		$insufficient_balance = false;
		$currentPlayerBalance = $this->getPlayerBalance($data['player_username'], $data['player_id']);
		if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$params['bet_amount'])){
			$insufficient_balance = true;
			$hasError = 1;
			$errorCode = self::INSUFFICIENT_FUNDS;
			$errorDescription = $this->getResponseErrorMessage($errorCode);
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API - ProcessTransactions:" . $errorDescription);
		}			
		$params['insufficient_balance'] = $insufficient_balance;

		$mode = null;
		$bet = $params['bet_amount'];
		$win = $params['win_amount'];
		list($finalAmount, $mode) = $this->getAmountAndMode($bet, $win);

		$params['insufficient_balance'] = $insufficient_balance;
		$params['mode'] = $mode;
		$params['final_amount'] = $finalAmount;
		$params['current_player_balance'] = $currentPlayerBalance;

		$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
		$this->remoteWallet['is_end'] = $params['is_round_finish'];
		$this->remoteWallet['round_id'] = $params['round_id'];
		$this->remoteWallet['bet_amount'] = $params['bet_amount'];
		$this->remoteWallet['payout_amount'] = $params['win_amount'];
		$this->remoteWallet['external_game_id'] = $params['transaction_id'];

		$refTransactionId = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;

		$relatedBetTransaction = $this->spinomenal_seamless_wallet_transactions->getTransactionByParams([
			'external_uniqueid' => $refTransactionId,
			'player_id' => isset($params['operator_player_id']) ? $params['operator_player_id'] : null
		]);
		if($relatedBetTransaction){
			$relatedBetUniqueId = $this->formatRemoteUniqueId($relatedBetTransaction['external_uniqueid']);
			$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);	
			$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetUniqueId);	
		}
		return $params;
	}

	#-----------------Validations--------------------------
	public function checkIfGamePlatformIdIsValid(){
		$httpStatusCode = $this->getHttpStatusCode(self::ERROR_INTERNAL_SERVER_ERROR);
		if (empty($this->game_platform_id) || $this->game_platform_id != SPINOMENAL_SEAMLESS_GAME_API) {
			$this->CI->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: Invalid game_platform_id");
			http_response_code($httpStatusCode);
			$code = self::ERROR_INTERNAL_SERVER_ERROR;
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code) . " - Invalid game_platform_id"
			];

			return $this->setOutput($code, $data, false);
		}
		return true;
	}

	public function isRequestMethodValid($validMethod)
	{
		if($this->requestMethod != $validMethod){
			$this->utils->error_log('SPINOMENAL_SEAMLESS_SERVICE_API: Request method not allowed');
			http_response_code(405);
			$code = self::ERROR_REQUEST_METHOD_NOT_ALLOWED;
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code)
			];

			return $this->setOutput($code, $data, false);
		}
		return true;
	}

	public function validateWhiteIP()
	{
		$validated =  $this->game_api->validateWhiteIP();
		if (!$validated) {
			$code = self::ERROR_IP_NOT_ALLOWED;
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
			$httpStatusCode = $this->getHttpStatusCode($code);
			http_response_code($httpStatusCode);
			
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code)
			];

			return $this->setOutput($code, $data, false);
		}
		return true;
	}

	public function isGameUnderMaintenance()
	{	
		$isMaintenance = $this->utils->setNotActiveOrMaintenance($this->game_platform_id);
		if ($isMaintenance) {
			$code = self::ERROR_GAME_UNDER_MAINTENANCE;
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
			$httpStatusCode = $this->getHttpStatusCode($code);
			http_response_code($httpStatusCode);
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code)
			];

			return $this->setOutput($code, $data, false);
		}
		return true;
	}

	public function isAllowedApiMethod()
	{
		$allowedApiMethods = array_map('strtolower', self::ALLOWED_API_METHODS);

		if (!in_array(strtolower($this->method), $allowedApiMethods)) {
			$code = self::ERROR_API_METHOD_NOT_ALLOWED;
			$this->utils->error_log("SPINOMENAL_SEAMLESS_GAME_API: " . $this->getResponseErrorMessage($code));
			$httpStatusCode = $this->getHttpStatusCode($code);
			http_response_code($httpStatusCode);
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code)
			];

			return $this->setOutput($code, $data, false);	
		}
		return true;
	}

	
	public function isAccountValid($partner_id){
		return ($this->game_api->partner_id == $partner_id);
    }


	public function isCurrencyCodeValid($currency){
        return $this->game_api->currency==$currency;
    }

	public function checkIfInsufficientBalance($currentPlayerBalance, $balance){
		return $currentPlayerBalance >= $balance;
	}

	public function isValidSignature($data = [],$incomingSignature){
		$signature = md5(implode('',$data));
		return $signature == $incomingSignature;
	}	

	public function generateSignature(){
		$data = $this->request['data'];
		$signature = md5(implode('',$data));
		print_r($signature);
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}
	
	# ---------------Response-------------------------------

	public function externalResponse($data, $extra, $httpStatusCode = 200)
	{
		if(isset($extra['errorDescription'])){
			$this->utils->error_log('SPINOMENAL_SEAMLESS_SERVICE_API: ', $extra['errorDescription']);
		}
		$hasError = isset($extra['hasError']) ? $extra['hasError'] : 0;
		$errorId = $extra['errorId'];
		$errorDescription = $extra['errorDescription'];
		if ($extra['hasError']) {
			$this->utils->error_log("SPINOMENAL_SEAMLESS_SERVICE_API ($this->game_platform_id): $errorDescription");
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
		if (!empty($data)) {
			foreach ($data as $key => $value) {
				$response[$key] = $value;
			}

			$response["ErrorCode"] = (int)$extra['ErrorCode'];
			$response["ErrorMessage"] = $extra['ErrorMessage'];
		} else {
			$response =  [
				"ErrorCode" => (int)$extra['ErrorCode'],
				"ErrorMessage" => $extra['ErrorMessage']
			];

			if(isset($extra['ErrorDisplayText'])){
				$response['ErrorDisplayText'] = $extra['ErrorDisplayText'];
			}
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
		$this->CI->utils->debug_log("spinomennal save_response_result: $currentDateTime",["status" => $status, "type" => $type,"data" =>  $data, "response" => $response, "http_status" => $httpStatusCode,"fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("spinomenal save_response: ",$response);

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
			
			case self::CURRENCY_CODE_NOT_FOUND:
				$message = lang('The currency code of the player isn’t configured for your brand, contact support to add it');
				break;
			case self::DIFFERENT_CURRENCY_ALREADY_ASSIGNED:
				$message = lang('The PlayerId was already registered with a different currency code');
				break;			
			case self::INVALID_PARAMETERS:
				$message = lang('Invalid parameters');
				break;
			case self::INVALID_SIGNATURE:
				$message = lang('Invalid signature');
				break;
			case self::TOKEN_EXPIRED:
				$message = lang('Token Expired');
				break;
			case self::PLAYER_ACCOUNT_LOCKED:
				$message = lang('Player is locked/suspended');
				break;

			case self::INVALID_SIGNATURE:
				$message = lang('Invalid signature');
				break;
			case self::RESPONSIBLE_GAMING_LIMIT:
				$message = lang('Limit reached');
				break;
			case self::INTERNAL_ERROR:
				$message = lang('General error');
				break;		
			case self::INSUFFICIENT_FUNDS:
				$message = lang('Insufficient funds');
				break;		
			case self::WAGER_LIMIT:
				$message = lang('Bet limit reached');
				break;		
			case self::ROUND_ALREADY_CLOSED:
				$message = lang('Round already closed');
				break;		
			case self::FAILED_FATAL:
				$message = lang('Operator can\'t resolve the requests. Requires manual investigation');
				break;		
			case self::TRANSACTION_ID_NOT_FOUND:
				$message = lang('Transaction does not exist/canceled');
				break;			
			case self::UNSUPPORTED_TRANSACTION_TYPE:
				$message = lang('Unsupported transaction type');
				break;			
			case self::INVALID_BET_NOT_WITH_TERMS_AND_CONDITIONS:
				$message = lang('Invalid bet not with terms and conditions');
				break;			
			case self::UNKNOWN_PLAYER_ID:
				$message = lang('Unknown Player');
				break;			
			case self::FR_INVALID_PROMO_ON_OPERATOR_SIDE:
				$message = lang('Invalid Promo');
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
				$this->utils->error_log("SPINOMENAL SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("Spinomenal SEAMLESS SERVICE raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("Spinomenal SEAMLESS SERVICE raw parsed:", $request_json);
			$this->request = $request_json;
		}
		return $this->request;
	}

	public function getHttpStatusCode($errorCode)
	{

		#As per GP status code should always be 200 or game will timeout 

		#if status code not 200 game will timeout as per GP, will force 200 status code here

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

	private function dumpData($data){
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

		$this->utils->debug_log("SPINOMENAL SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function debitCreditAmountToWallet($params){
		$this->utils->debug_log("SPINOMENAL SEAMLESS SERVICE: (debitCreditAmount)", $params);
		$player_balance = $params['current_player_balance'];	
		$player_balance = $this->game_api->dBtoGameAmount($player_balance);		
		$transaction_id = $params['transaction_id'];
		$mode = $params['mode'];

		$before_balance = 0;
		$after_balance = 0;

		$flagrefunded = false;

		$reponse_code['success'] = false;

		$existingRow = $this->spinomenal_seamless_wallet_transactions->getExistingTransaction($transaction_id);
	
		if(!empty($existingRow)){
			$response_code['after_balance'] = $player_balance;
			return $reponse_code['success'];
		}

		$before_balance  = $this->game_api->queryPlayerBalance($params['operator_player_username'])['balance'];
		$before_balance = $this->game_api->dbToGameAmount($before_balance);
		$params['current_player_balance'] - $before_balance;
		$after_balance = $before_balance;

		$flagrefunded = false;
		//insert transaction
		$isAdded = $this->insertIgnoreTransactionRecord($params, $before_balance, $after_balance, $flagrefunded);

		$methodWithoutAdjustWallet = [self::TRANSACTION_END_ROUND, self::TRANSACTION_TYPE_FREE_ROUNDS_START];

		if(in_array($params['trans_type'], $methodWithoutAdjustWallet)){
			return true;
		}


		if($isAdded===false){
			$this->utils->error_log("SPINOMENAL SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			$reponse_code['success'] = false;
			return $reponse_code['success'];
		}
	
		$response_code = $this->adjustWallet($mode, $params);

		if($response_code['success']){
			$updatedAfterBalance = $this->spinomenal_seamless_wallet_transactions->updateAfterBalanceByTransactionId($transaction_id, $response_code['after_balance']);
			if(!$updatedAfterBalance){
				$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update after balance');
			}	
			$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
		}

		return $response_code['success'];
	}

	public function handleRollback($params){
		$before_balance  = $this->game_api->queryPlayerBalance($params['operator_player_username'])['balance'];
		$player_balance = $before_balance = $this->game_api->dbToGameAmount($before_balance);

		$transaction_id = $params['ref_transaction_id'];

		$transaction = $this->spinomenal_seamless_wallet_transactions->getTransaction($transaction_id);

		if(!$transaction){
			$response_code['hasError'] = 1;
			$response_code['ErrorCode'] = self::TRANSACTION_ID_NOT_FOUND;
			$errorDescription = $this->getResponseErrorMessage(self::TRANSACTION_ID_NOT_FOUND);
			$response_code['ErrorMessage'] = $errorDescription;
			$this->extra = $response_code;
			return true;
		}

		if(!empty($transaction) && $transaction->is_round_finish){
			$response_code['hasError'] = 1;
			$response_code['ErrorCode'] = self::ROUND_ALREADY_CLOSED;
			$errorDescription = $this->getResponseErrorMessage(self::ROUND_ALREADY_CLOSED);
			$response_code['ErrorMessage'] = $errorDescription;
			$this->extra = $response_code;
			return true;
		}

		$data = [];
		$data['current_player_balance']			= $before_balance;
		$data['player_username']				= $params['operator_player_username'];
		$data['partner_id'] 					= $params['partner_id'];
		$data['sig'] 							= $params['sig'];
		$data['timestamp'] 						= $params['timestamp'];
		$data['game_token'] 					= $transaction->game_token;
		$data['bet_amount'] 					= $transaction->bet_amount;
		$data['win_amount'] 					= $transaction->win_amount;
		$data['final_amount']					= $params['final_amount'];
		$data['game_code'] 						= $transaction->game_code;
		$data['spinomenal_player_id'] 			= $params['player_id'];
		$data['transaction_id'] 				= $params['transaction_id'];
		$data['ref_transaction_id'] 			= $params['ref_transaction_id'];
		$data['round_id'] 						= $params['round_id'];
		$data['is_round_finish']				= $params['is_round_finish'];
		$data['provider_code'] 					= $params['provider_code'];
		$data['transaction_details']			= json_decode($transaction->transaction_details);
		$data['transaction_type']				= $params['transaction_type'];
		$data['sub_game_code']					= $transaction->sub_game_code;
		$data['is_retry']						= $params['is_retry'];
		$data['raw_data'] 						= $transaction->raw_data;

		$data['currency'] 						= $transaction->currency;
		$data['trans_type']						= $params['trans_type'];
		$data['trans_status'] 					= $transaction->trans_status;
		$data['player_id']						= $params['player_id'];
		$data['operator_player_id']				= $params['operator_player_id'];
		$data['operator_player_username']		= $params['operator_player_username'];
		$data['balance_adjustment_method']		= $params['mode'];
		$data['before_balance'] 				= $before_balance;
		$data['after_balance'] 					= $transaction->after_balance;
		$data['game_platform_id'] 				= $transaction->game_platform_id;
		$data['external_uniqueid'] 				= $params['transaction_id'];

		$data['mode'] 							= self::CANCEL;


		$type = $transaction->balance_adjustment_method;
		$flagrefunded = false;

		if($type == self::CREDIT){			
			$amountToSubtract = $data['win_amount'] - $data['bet_amount'];
			$before_balance = $data['after_balance'];
			$after_balance = $data['after_balance'] - $amountToSubtract;
			
			$data['after_balance'] = $this->game_api->dbToGameAmount($after_balance);
			$data['before_balance'] = $this->game_api->dbToGameAmount($before_balance);
			$data['total_balance'] = $data['after_balance'];
			$data['final_amount'] = $amountToSubtract;

			$this->remoteWallet['payout_amount'] = $data['final_amount'];

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($data, $before_balance, $after_balance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("SPINOMENAL SEAMLESS SERVICE: (handleRollback) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return true;
			}


			$response_code = $this->adjustWallet(self::DEBIT, $data);

			//update after balance
			if($response_code['success']){
				$updatedAfterBalance = $this->spinomenal_seamless_wallet_transactions->updateAfterBalanceByTransactionId($data['transaction_id'], $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->error_log(__METHOD__ . ': failed to update after balance after adjust wallet');
				}	
				$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
			}			


			return true;
		}

		if($type == self::DEBIT){
			$amountToAdd = $data['bet_amount'] - $data['win_amount'];
			$before_balance = $data['after_balance'];
			$after_balance = $data['after_balance'] + $amountToAdd;
			
			$data['after_balance'] = $this->game_api->dbToGameAmount($after_balance);
			$data['before_balance'] = $this->game_api->dbToGameAmount($before_balance);
			$data['total_balance'] = $data['after_balance'];
			$data['final_amount'] = $amountToAdd;
			//insert transaction

			$this->remoteWallet['bet_amount'] = $data['final_amount'];
			$isAdded = $this->insertIgnoreTransactionRecord($data, $before_balance, $after_balance, $flagrefunded);
			
			if($isAdded===false){
				$this->utils->error_log("SPINOMENAL SEAMLESS SERVICE: (handleRollback) ERROR: isAdded=false saving error", $isAdded, $this->request);
			}
			$response_code = $this->adjustWallet(self::CREDIT, $data);
			//update after balance
			if($response_code['success']){
				$updatedAfterBalance = $this->spinomenal_seamless_wallet_transactions->updateAfterBalanceByTransactionId($data['transaction_id'], $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->error_log(__METHOD__ . ': failed to update after balance after adjust wallet');
				}	
				$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
			}
			return true;
		}

		if($type == self::CANCEL){
			#dont allow rollback transaction to rollback
			$this->extra = [
				"hasError" => 1,
				"ErrorCode" => self::ROUND_ALREADY_CLOSED,
				"ErrorDescription" => $this->getResponseErrorMessage(self::ROUND_ALREADY_CLOSED)
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
        $this->CI->spinomenal_seamless_wallet_transactions->setTableName($tableName);  
		return $this->spinomenal_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['operator_player_id'];
		$before_balance = 0;

		if (isset($params['current_player_balance'])) {
			$before_balance = $params['current_player_balance'];
		} else {
			$username = $params['operator_player_username'];
			$before_balance = $this->game_api->queryPlayerBalance($username)['balance'];
		}

		$response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);
		$after_balance = $before_balance;

		$response_code['success'] = true;
		$response_code["after_balance"] = $after_balance;
        if($action == self::DEBIT) {
            $finalAmount = abs($params['final_amount']);
			
            if($this->utils->compareResultFloat($finalAmount, '>', 0)) {
				$uniqueid =  $action.'-'.$params['transaction_id'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 

				if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	
				}
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderRoundId')){
					$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
					$this->CI->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderBetAmount')){
					$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				}
				if(method_exists($this->wallet_model, 'setExternalGameId')){
					$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
				}
			
				$finalAmount = $this->game_api->gameAmountToDB($finalAmount);

				$this->CI->utils->debug_log('SPINOMENAL SEAMLESS SERVICE CREDIT-DEBIT FINAL AMOUNT', $finalAmount);
				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				$response_code['success'] = true;

				# treat success if remote wallet return double uniqueid
				$enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
				if(!empty($enabled_remote_wallet_client_on_currency)){
					if (method_exists($this->utils, 'isEnabledRemoteWalletClient')) {
						if($this->utils->isEnabledRemoteWalletClient()){
							$remoteErrorCode = $this->CI->wallet_model->getRemoteWalletErrorCode();
							if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
								$response_code['success'] = true;
							}    
						}
					}	
				}
				
				$this->CI->utils->debug_log('SPINOMENAL_SEAMLESS_SERVICE DEDUCT BALANCE', $deduct_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->debug_log('SPINOMENAL_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to deduct subwallet');
				}	
            }


			if(is_null($after_balance)){
				$after_balance = $this->game_api->queryPlayerBalance($params['operator_player_username'])['balance'];
			} 

			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {
            $finalAmount = $params['final_amount'];
			if($this->utils->compareResultFloat($finalAmount, '=', 0)){
				$uniqueid =  $action.'-'.$params['transaction_id'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 

				if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	
				}
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderRoundId')){
					$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
					$this->CI->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderBetAmount')){
					$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				}
				if(method_exists($this->wallet_model, 'setExternalGameId')){
					$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
				}
				$this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				$this->utils->debug_log("SPINOMENAL_SEAMLESS_SERVICE_API incSubwallet even 0 payout");
			}
			if($this->utils->compareResultFloat($finalAmount, '>', 0)) {
				$uniqueid =  $action.'-'.$params['transaction_id'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 

				if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	
				}
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderRoundId')){
					$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
					$this->CI->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				}
				if(method_exists($this->wallet_model, 'setGameProviderBetAmount')){
					$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				}
				if(method_exists($this->wallet_model, 'setExternalGameId')){
					$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
				}
			
				$finalAmount = $this->game_api->gameAmountToDB($finalAmount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
								
				$response_code['success'] = true;
				# treat success if remote wallet return double uniqueid

				$enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
				if(!empty($enabled_remote_wallet_client_on_currency)){
					if (method_exists($this->utils, 'isEnabledRemoteWalletClient')) {
						if($this->utils->isEnabledRemoteWalletClient()){
							$remoteErrorCode = $this->CI->wallet_model->getRemoteWalletErrorCode();
							if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
								$response_code['success'] = true;
							}    
						}
					}	
				}

				
				$this->CI->utils->debug_log('SPINOMENAL_SEAMLESS_SERVICE ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('SPINOMENAL_SEAMLESS_SERVICE: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}

			if(is_null($after_balance)){
				$after_balance = $this->game_api->queryPlayerBalance($params['operator_player_username'])['balance'];
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}
		return $response_code;

	}

	private function formatRemoteUniqueId($uniqueid){
		return $this->game_api->getPlatformCode().'-'.$uniqueid; 
	}
	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['game_code'] 			            	= isset($raw_data['game_code']) ? $raw_data['game_code'] : null;
		$data['partner_id'] 			            = isset($raw_data['partner_id']) ? $raw_data['partner_id'] : null;
		$data['signature'] 			                = isset($raw_data['sig']) ? $raw_data['sig'] : null;
		$data['timestamp'] 			            	= isset($raw_data['timestamp']) ? $raw_data['timestamp'] : null;
		$data['game_token'] 			            = isset($raw_data['game_token']) ? $raw_data['game_token'] : null;
		$data['transaction_id'] 			        = isset($raw_data['transaction_id']) ? $raw_data['transaction_id'] : null;
		$data['ref_transaction_id'] 			    = isset($raw_data['ref_transaction_id']) ? $raw_data['ref_transaction_id'] : null;
		$data['round_id'] 			        		= isset($raw_data['round_id']) ? $raw_data['round_id'] : null;
		$data['transaction_type'] 			        = isset($raw_data['transaction_type']) ? $raw_data['transaction_type'] : null;
		$data['provider_code'] 			        	= isset($raw_data['provider_code']) ? $raw_data['provider_code'] : null;
		$data['transaction_details'] 			    = isset($raw_data['transaction_details']) ? json_encode($raw_data['transaction_details']) : null;
		$data['player_id'] 			    			= isset($raw_data['operator_player_id']) ? $raw_data['operator_player_id'] : null;
		$data['bet_amount'] 			            = isset($raw_data['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet_amount']) : null;
		$data['win_amount'] 			            = isset($raw_data['win_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['win_amount']) : null;
		$data['result_amount'] 			            = isset($raw_data['final_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['final_amount']): null;
		$data['currency'] 			    			= isset($raw_data['currency']) ? $raw_data['currency'] : null;
		$data['is_round_finish'] 			    	= isset($raw_data['is_round_finish']) ? $raw_data['is_round_finish'] : 0;
		$data['is_retry'] 			    			= isset($raw_data['is_retry']) ? $raw_data['is_retry'] : 0;
		$data['sub_game_code'] 			    		= isset($raw_data['sub_game_code']) ? $raw_data['sub_game_code'] : null;
		$data['external_uniqueid'] 			    	= isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : null;
		$data['trans_status'] 			    		= 1;
		$data['trans_type'] 			    		= isset($raw_data['trans_type']) ? $raw_data['trans_type'] : null;
		$data['after_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 					= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;
		$data['raw_data']							= isset($raw_data['raw_data']) ? json_encode($raw_data['raw_data']) : null;

		$data['spinomenal_player_id']				= isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
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