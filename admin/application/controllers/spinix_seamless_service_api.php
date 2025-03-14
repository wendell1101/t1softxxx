<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Spinix_seamless_service_api extends BaseController
{
	const METHOD_ROUND_PAYOUT = 'roundPayout';
	const METHOD_GET_BALANCE = 'getBalance';


	const ALLOWED_API_METHODS = [
		self::METHOD_ROUND_PAYOUT,
		self::METHOD_GET_BALANCE,
	];

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel';
	const REFUND = 'refund';

	const SUCCESS = 0;

	const ERROR_IP_NOT_WHITELISTED = 401;

	#GP ERROR CODES
	const CODE_INTERNAL_SERVER_ERROR		= "E10000";
	const CODE_PLATFORM_NOT_FOUND 			= "E10400";
	const CODE_WALLET_NOT_FOUND 			= "E10501";
	const CODE_CURRENCY_NOT_FOUND 			= "E10503";
	const CODE_INVALID_SIGNATURE 			= "E10201";
	const CODE_USER_NOT_FOUND				= "E10700";
	const CODE_PARAM_INVALID				= "E10200";

	#round/payout error codes
	const CODE_UNEXPECTED_INTERNAL_SERVER_ERROR 			= "E20000";
	const CODE_ROUND_PAYOUT_PARAMETER_INVALID 				= "E20200";
	const CODE_ROUND_PAYOUT_BALANCE_NOT_ENOUGH 				= "E20502";
	const CODE_ROUND_PAYOUT_GAME_NOT_FOUND 					= "E20600";
	const CODE_ROUND_PAYOUT_USER_NOT_FOUND 					= "E20700";
	const CODE_ROUND_PAYOUT_TRANSACTION_INVALID 			= "E20700";
	const CODE_ROUND_PAYOUT_USER_TOKEN_NOT_FOUND_OR_INVALID = "E20900";
	const CODE_ROUND_PAYOUT_GAME_NOT_AVAILABLE				= "E20601";
	
	const HTTP_STATUS_CODE_200				= 200;
	const HTTP_STATUS_CODE_201				= 201;
	const HTTP_STATUS_CODE_400				= 400;
	const HTTP_STATUS_CODE_401				= 401;
	const HTTP_STATUS_CODE_403				= 403;
	const HTTP_STATUS_CODE_500				= 500;

	const SUCCESS_HTTP_STATUS_CODE = [self::HTTP_STATUS_CODE_200, self::HTTP_STATUS_CODE_201];
	const ERROR_HTTP_STATUS_CODE = [self::HTTP_STATUS_CODE_500, self::HTTP_STATUS_CODE_400, self::HTTP_STATUS_CODE_401, self::HTTP_STATUS_CODE_403];

	const SUCCESS_MESSAGE					= "Success";


	const RESPONSE_CODES = [
		self::CODE_SUCCESS => [
			"status_code" => self::HTTP_STATUS_CODE_200,
			"message" => "Success",
		],
		self::CODE_INTERNAL_SERVER_ERROR => [
			"status_code" => self::HTTP_STATUS_CODE_500,
			"message" => "Unexpected internal server error",
		],
		self::CODE_INTERNAL_SERVER_ERROR => [
			"status_code" => self::HTTP_STATUS_CODE_500,
			"message" => "Unexpected internal server error",
		],
		self::CODE_INTERNAL_SERVER_ERROR => [
			"status_code" => self::HTTP_STATUS_CODE_500,
			"message" => "Unexpected internal server error",
		],
		self::CODE_PLATFORM_NOT_FOUND => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Platform not found",
		],
		self::CODE_WALLET_NOT_FOUND => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Wallet not found ",
		],
		self::CODE_CURRENCY_NOT_FOUND => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => " Currency not found",
		],
		self::CODE_INVALID_SIGNATURE => [
			"status_code" => self::HTTP_STATUS_CODE_500,
			"message" => " Invalid signature",
		],
		self::CODE_USER_NOT_FOUND => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "User not found",
		],
		self::CODE_PARAM_INVALID => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Param Invalid",
		],
		self::CODE_UNEXPECTED_INTERNAL_SERVER_ERROR => [
			"status_code" => self::HTTP_STATUS_CODE_500,
			"message" => "Unexpected internal server error",
		],

		self::CODE_ROUND_PAYOUT_PARAMETER_INVALID => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Parameter invalid",
		],
		self::CODE_ROUND_PAYOUT_BALANCE_NOT_ENOUGH => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Balance not enough",
		],
		self::CODE_ROUND_PAYOUT_GAME_NOT_FOUND => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Game not found",
		],
		self::CODE_ROUND_PAYOUT_USER_NOT_FOUND => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "User not found",
		],
		self::CODE_ROUND_PAYOUT_TRANSACTION_INVALID => [
			"status_code" => self::HTTP_STATUS_CODE_400,
			"message" => "Transaction Invalid",
		],
		self::CODE_ROUND_PAYOUT_USER_TOKEN_NOT_FOUND_OR_INVALID => [
			"status_code" => self::HTTP_STATUS_CODE_401,
			"message" => "User token not found or invalid",
		],
		self::CODE_ROUND_PAYOUT_GAME_NOT_AVAILABLE => [
			"status_code" => self::HTTP_STATUS_CODE_403,
			"message" => "Game not available",
		],
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

	const ROUND_TYPE_BET = "bet";
	const ROUND_TYPE_WIN = "win";
	const ROUND_TYPE_CANCEL_BET = "cancelBet";

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request, $requestHeaders;
	public $currency;
	private $headers;

	public $start_time;
	public $end_time;
	public $method;
	protected $requestMethod;
	private $hasError = true;
	private $balance_adjustment_type = null;
	private $remoteActionType = null;


	private $result = [];

	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'game_logs', 'spinix_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));

		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();

		$this->game_platform_id = SPINIX_SEAMLESS_GAME_API;
		
	}


	public function initialize()
	{
		$this->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: raw request');
		$validRequestMethod = self::POST;
		if (!$this->isRequestMethodValid($validRequestMethod)) {
			return false;
		};

		if (!$this->checkIfGamePlatformIdIsValid()) {
			$this->utils->error_log('SPINIX_SEAMLESS_SERVICE_API: game_platform_id not valid');
			return false;
		};

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		$tableName = $this->game_api->getTransactionsTable();

		$this->CI->spinix_seamless_wallet_transactions->setTableName($tableName);

		if (!$this->game_api) {
			$this->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API: (initialize) ERROR lOAD: ", $this->game_platform_id);
			http_response_code(500);
			return false;
		}

		$this->currency = $this->game_api->getCurrency();;

		if (!$this->validateWhiteIP()) {
			return false;
		}

		if (!$this->isGameUnderMaintenance()) {
			return false;
		}


		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		return $this->hasError;
	}

	
	public function generatePlayerToken()
	{
		$username = $this->request['username'];

		$result = $this->game_api->getPlayerTokenByUsername($username);
		print_r(["token" => $result]);
	}

	#----------------------GP API_ALLOWED_METHODS----------------------------

	public function getBalance()
	{
		if (!$this->initialize()) {
			$this->debug_log("SPINIX_SEAMLESS_SERVICE_API", "Failed to initialize inspect error logs");
			return;
		} 
		$this->method = self::METHOD_GET_BALANCE;
		$type = self::METHOD_GET_BALANCE;
		$status = false;

		$code = self::SUCCESS;
		$message = self::SUCCESS_MESSAGE;
		$httpStatus = 200;
		$balance = 0;
		$playerId = null;
		$requestId = null;

		$rules = [
			'req_id' => 'required',
			'user_id' => 'required',
			'user_token' => 'required',
			'game_id' => 'required',
			'currency' => 'required'
		];
		try {
			$requestId = isset($this->request['req_id']) ? $this->request['req_id'] : null; 
			if (!$this->isValidParams($this->request, $rules)) {
				$code = self::CODE_PARAM_INVALID;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (getbalance)" . " - " . $message);
				throw new Exception($code);
			}

			// if(!$this->isAccountValid($this->request['platform_id'])){
			// 	$code = self::CODE_PLATFORM_NOT_FOUND;
			// 	$httpStatus = $this->getResponseHttpStatusCode($code);
			// 	$message = $this->getResponseMessage($code);
			// 	$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (getbalance)" . " - " . $message);
			// 	throw new Exception($code);
			// }

			if(!$this->isCurrencyCodeValid($this->request['currency'])){
				$code = self::CODE_INTERNAL_SERVER_ERROR;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (transfer)" . " - " . $message);
				throw new Exception($code);
			}
			$userId = isset($this->request['user_id']) ? $this->request['user_id'] : null;
			$userToken = isset($this->request['user_token']) ? $this->request['user_token'] : null;

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($userToken);


			if ($player == null) {
				$code = self::CODE_USER_NOT_FOUND;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (getbalance)" . ' - ' . $message);
				throw new Exception($code);
			}

			if (!$playerStatus) {
				$code = self::CODE_INTERNAL_SERVER_ERROR;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (getbalance)" . __METHOD__ . ' - ' . $message);
				throw new Exception($message);
			}


			$playerId = $player->player_id;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		} catch (Exception $error) {
			$status = false;
		}
		#success

		if(in_array($httpStatus,self::SUCCESS_HTTP_STATUS_CODE)){
			$data = [
				"req_id" => $requestId,
				"status" => $httpStatus,
				"data" => [
					"wallet" => [
						"currency" => $this->currency,
						"balance" => $balance
					]
				]
			];
		}
		if(in_array($httpStatus,self::ERROR_HTTP_STATUS_CODE)){
			$data = [
				"req_id" => $requestId,
				"status" => $httpStatus,
				"error" => [
					"code" => $code,
					"message" => $message,
				]
			];
		}

		

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}

	public function roundPayout()
	{
		if (!$this->initialize()) {
			$this->debug_log("SPINIX_SEAMLESS_SERVICE_API", "Failed to initialize inspect error logs");
			return;
		} 
		$this->method = self::METHOD_ROUND_PAYOUT;
		$type = self::METHOD_ROUND_PAYOUT;
		$status = false;

		$code = self::SUCCESS;
		$message = self::SUCCESS_MESSAGE;
		$httpStatus = 200;
		$balance = 0;
		$playerId = null;
		$requestId = null;
		$canCancel = false;
		$mode = self::DEBIT;
		$rules = [
			'req_id' => 'required',
			'user_id' => 'required',
			'game_id' => 'required',
			'game_type' => 'required',
			'currency' => 'required',
			'round_id' => 'required',
			'valid_turnover' => 'required',
			'valid_turnover' => 'required',
			'transaction_list' => 'required',

		];
		try {
			$requestId = isset($this->request['req_id']) ? $this->request['req_id'] : null; 
			if (!$this->isValidParams($this->request, $rules)) {
				$code = self::CODE_PARAM_INVALID;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . " - " . $message);
				throw new Exception($code);
			}


			if(!$this->isCurrencyCodeValid($this->request['currency'])){
				$code = self::CODE_INTERNAL_SERVER_ERROR;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . " - " . $message);
				throw new Exception($code);
			}
			$userId = isset($this->request['user_id']) ? $this->request['user_id'] : null;
			$userToken = isset($this->request['user_token']) ? $this->request['user_token'] : null;
			if($userToken){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($userToken);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($userId);
			}

			if ($player == null) {
				$code = self::CODE_USER_NOT_FOUND;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . ' - ' . $message);
				throw new Exception($code);
			}

			if (!$playerStatus) {
				$code = self::CODE_INTERNAL_SERVER_ERROR;
				$httpStatus = $this->getResponseHttpStatusCode($code);
				$message = $this->getResponseMessage($code);
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . __METHOD__ . ' - ' . $message);
				throw new Exception($message);
			}


			$playerId = $player->player_id;
			$params['player_id'] = $playerId;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$params['current_player_balance'] = $balance;


			$transactionList = isset($this->request['transaction_list']) ? $this->request['transaction_list'] : [];
			#transactionList is always one transaction as per GP
			$transaction = isset($transactionList[0]) ? $transactionList[0] : null;
			$uniqueId = isset($transaction['id']) ? $transaction['id'] : null; 

			if(!is_null($transaction)){
				$isTransactionAlreadyExists = $this->spinix_seamless_wallet_transactions->isTransactionExist($uniqueId);
				if($isTransactionAlreadyExists){
					$code = self::CODE_ROUND_PAYOUT_TRANSACTION_INVALID;
					$httpStatus = $this->getResponseHttpStatusCode($code);
					$message = $this->getResponseMessage($code);
					$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . __METHOD__ . ' - ' . $message);
					throw new Exception($message);
				}
				
				$type = $transaction['type'];

				switch($transaction['type']){
					case self::ROUND_TYPE_BET:
						if(!$this->checkIfInsufficientBalance($balance,abs($transaction['amount']))){
							$code = self::CODE_ROUND_PAYOUT_BALANCE_NOT_ENOUGH;
							$httpStatus = $this->getResponseHttpStatusCode($code);
							$message = $this->getResponseMessage($code);
							$this->utils->error_log("SPINIX_SEAMLESS_GAME_API: (roundPayout - type = bet):" . $message);
							throw new Exception($code);
						}	
						$mode = self::DEBIT;
						$this->remoteActionType =  Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
						break;
					case self::ROUND_TYPE_WIN:	
						$mode = self::CREDIT;
						$this->remoteActionType =  Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
						break;
					case self::ROUND_TYPE_CANCEL_BET:
						#can only cancel if is_end = false
						$mode = self::CANCEL;
						$this->remoteActionType =  Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
						if($transaction['is_end']){
							$canCancel = false;
						}	
					
						break;
				}
			} 

			$params['can_cancel'] = $canCancel;
			$params['mode'] = $mode;

			if($transaction['type'] == self::ROUND_TYPE_WIN){
				$existingBetTrans = $this->spinix_seamless_wallet_transactions->getRelatedBetExistingTransaction($this->request['round_id'], $params['player_id']);
				if(!$existingBetTrans){
					$code = self::CODE_ROUND_PAYOUT_TRANSACTION_INVALID;
					$httpStatus = $this->getResponseHttpStatusCode($code);
					$message = $this->getResponseMessage($code);
					$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . __METHOD__ . ' - ' . $message);
					throw new Exception($message);	
				}
			}

			if (in_array($transaction['type'],[self::ROUND_TYPE_CANCEL_BET])){
				$existingBetTrans = $this->spinix_seamless_wallet_transactions->getRelatedBetExistingTransaction($this->request['round_id'], $params['player_id']);
				if (!$existingBetTrans || (!empty($existingBetTrans) && in_array($existingBetTrans->status, [Game_logs::STATUS_SETTLED, Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED]) || $transaction['is_end'])) {
					$code = self::CODE_ROUND_PAYOUT_TRANSACTION_INVALID;
					$httpStatus = $this->getResponseHttpStatusCode($code);
					$message = $this->getResponseMessage($code);
					$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (roundPayout)" . __METHOD__ . ' - ' . $message);
					throw new Exception($message);	
				}
			}

			
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){	
				$this->balance_adjustment_type = $params['mode'];
				$trans_success = $this->debitCreditAmountToWallet($params);
				return $trans_success;
			});

			$status = true;
		} catch (Exception $error) {
			$status  = false;
		}
		$balance = $this->getPlayerBalance($player_username, $playerId);
		#success
		if(in_array($httpStatus,self::SUCCESS_HTTP_STATUS_CODE)){
			$data = [
				"req_id" => $requestId,
				"status" => $httpStatus,
				"data" => [
					"wallet" => [
						"currency" => $this->currency,
						"balance" => $balance
					]
				]
			];
		}
		#error
		if(in_array($httpStatus,self::ERROR_HTTP_STATUS_CODE)){
			$data = [
				"req_id" => $requestId,
				"status" => $httpStatus,
				"error" => [
					"code" => $code,
					"message" => $message,
				]
			];
		}

		

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}


	#-----------------Validations--------------------------
	public function checkIfGamePlatformIdIsValid()
	{
		$httpStatusCode = $this->getHttpStatusCode(self::ERROR_INTERNAL_SERVER_ERROR);
		if (empty($this->game_platform_id)) {
			$this->CI->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: Invalid game_platform_id");
			http_response_code($httpStatusCode);
			$code = self::ERROR_INTERNAL_SERVER_ERROR;
			$data =  [
				'code' => $code,
				'message' => $this->getResponseErrorMessage($code)
			];

			return $this->setOutput($code, $data, false);
		}
		return true;
	}

	public function isRequestMethodValid($validMethod)
	{
		if($this->requestMethod != $validMethod){
			$this->utils->error_log('SPINIX_SEAMLESS_SERVICE_API: Request method not allowed');
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
			$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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
			$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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
			$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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

	public function isAccountValid($platform_id)
	{
		return $this->game_api->platform_id == $platform_id;
	}

	public function isCurrencyCodeValid($currency)
	{
		return $this->game_api->currency == $currency;
	}

	public function checkIfInsufficientBalance($currentPlayerBalance, $balance)
	{
		return $currentPlayerBalance >= $balance;
	}

	private function isValidParams($request, $rules)
	{
		//validate params
		foreach ($rules as $key => $rule) {
			if ($rule == 'required' && !isset($request[$key])) {
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: " . $key, $request, $rules);
				return false;
			}

			if ($rule == 'isNumeric' && isset($request[$key]) && !$this->isNumeric($request[$key])) {
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: " . $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if ($rule == 'nonNegative' && isset($request[$key]) && $request[$key] < 0) {
				$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: " . $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}

	public function getResponseErrorMessage($code)
	{
		$message = '';

		switch ($code) {
			case self::SUCCESS:
				$message = lang('Success');
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
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}
	# ---------------Response-------------------------------

	public function externalResponse($data, $extra, $httpStatusCode = 200)
	{
		if (isset($extra['errorDescription'])) {
			$this->utils->error_log('IM Seamless API: ', $extra['errorDescription']);
		}
		$hasError = isset($extra['hasError']) ? $extra['hasError'] : 0;
		$errorId = $extra['errorId'];
		$errorDescription = $extra['errorDescription'];
		if ($extra['hasError']) {
			$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API ($this->game_platform_id): $errorDescription");
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


	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = [])
	{
		$this->CI->utils->debug_log(
			"SPINIX_SEAMLESS_SERVICE_API (handleExternalResponse)",
			'status',
			$status,
			'type',
			$type,
			'data',
			$data,
			'response',
			$response,
			'error_code',
			$error_code,
			'fields',
			$fields
		);

		if (strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log(
				__METHOD__ . "  (handleExternalResponse) Connection timed out.",
				'status',
				$status,
				'type',
				$type,
				'data',
				$data,
				'response',
				$response,
				'error_code',
				$error_code,
				'fields',
				$fields
			);
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}

		$httpStatusCode = $this->getHttpStatusCode($error_code);

		if ($error_code == self::SUCCESS) {
			$httpStatusCode = 200;
		}

		//add request_id
		if (empty($response)) {
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow() * 1000);
		$currentDateTime = date('Y-m-d H:i:s');
		$this->CI->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API save_response: ", $response);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
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
		$extra = array_merge((array)$extra, (array)$this->headers);
		return $this->CI->response_result->saveResponseResult(
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

	public function getResponseMessage($code)
	{
		return !is_null(self::RESPONSE_CODES[$code]['message']) ? self::RESPONSE_CODES[$code]['message'] : '';
		
	}
	public function getResponseHttpStatusCode($code)
	{
		return !is_null(self::RESPONSE_CODES[$code]['status_code']) ? self::RESPONSE_CODES[$code]['status_code'] : 200;
	}

	

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API raw parsed:", $request_json);
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


    private function setOutput($code, $data = [], $status=true){
		$httpStatusCode = $this->getHttpStatusCode($code);
		$this->handleExternalResponse($status, $this->method, $this->request, $data, $code);
			 
		$this->output->set_content_type('application/json')
			->set_output(json_encode($data))
			->set_status_header($httpStatusCode);
		$this->output->_display();
		exit();
    }

	public function dumpData($data)
	{
		print_r(json_encode($data));
		exit;
	}
	public function debug_log($key, $value)
	{
	$this->CI->utils->debug_log($key, $value);
	}
	public function getPlayerByToken($token, $model = "common_token")
	{

		$player = $this->$model->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if (!$player) {
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsername($gameUsername, $model = 'common_token')
	{
		$player = $this->$model->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);

		if (!$player) {
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsernameAndToken($gameUsername, $token, $model = "common_token")
	{
		$player = $this->$model->getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $this->game_platform_id);

		if (!$player) {
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerBalance($playerName, $player_id)
	{
		$get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);

		$this->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API: (getPlayerBalance) get_bal_req: ", $get_bal_req);
		if ($get_bal_req['success']) {
			return $get_bal_req['balance'];
		} else {
			return false;
		}
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;

		$trans_record = $this->makeTransactionRecord($data);

		$tableName = $this->game_api->getTransactionsTable();
        $this->spinix_seamless_wallet_transactions->setTableName($tableName);  
		return $this->spinix_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}

	public function makeTransactionRecord($raw_data){
		$data = [];
		$status = Game_logs::STATUS_SETTLED;
		$data['req_id'] 			            	= isset($raw_data['req_id']) ? $raw_data['req_id'] : null;
		$data['user_id'] 			            	= isset($raw_data['user_id']) ? $raw_data['user_id'] : null;
		$data['user_token'] 			            = isset($raw_data['user_token']) ? $raw_data['user_token'] : null;
		$data['game_id'] 			            	= isset($raw_data['game_id']) ? $raw_data['game_id'] : null;
		$data['game_type'] 			            	= isset($raw_data['game_type']) ? $raw_data['game_type'] : null;
		$data['currency'] 			            	= isset($raw_data['currency']) ? $raw_data['currency'] : null;
		$data['round_id'] 			            	= isset($raw_data['round_id']) ? $raw_data['round_id'] : null;
		$data['valid_turnover'] 			        = isset($raw_data['valid_turnover']) ? $raw_data['valid_turnover'] : null;
		$data['transaction_list'] 			        = isset($raw_data['transaction_list']) ? json_encode($raw_data['transaction_list']) : null;
		#player_id-	
		$data['transaction_id']						= isset($raw_data['transaction_id']) ? $raw_data['transaction_id'] : null;
		// $data['is_main']							= isset($raw_data['is_main']) ? $raw_data['is_main'] : false;
		$data['type'] 			        			= isset($raw_data['type']) ? $raw_data['type'] : null;
		$data['amount'] 			        		= isset($raw_data['amount']) ? abs($raw_data['amount']) : null;
		$data['info'] 			       				= isset($raw_data['info']) ? $raw_data['info'] : null;
		$data['is_end'] 			       			= isset($raw_data['is_end']) ? $raw_data['is_end'] : null;
		$data['timestamp'] 			       			= isset($raw_data['timestamp']) ? $raw_data['timestamp'] : null;


		$data['raw_data'] 			    			= isset($this->request) ? json_encode($this->request) : null;
		$data['external_uniqueid'] 			    	= isset($data['transaction_id']) ? $data['transaction_id'] : null;

		$status = Game_logs::STATUS_SETTLED;
		$type = $data['type'];
		if($type == self::ROUND_TYPE_WIN){
			$status = Game_logs::STATUS_SETTLED;
		}

		if($type == self::ROUND_TYPE_BET){
			$status = Game_logs::STATUS_PENDING;
		}
		if($type == self::ROUND_TYPE_CANCEL_BET){
			$status = Game_logs::STATUS_REFUND;
		}

		$data['status'] 			    			= $status;
		$data['trans_type'] 			    		= !is_null($data['type']) ? $data['type'] : null;
		$data['after_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 					= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;
		$data['player_id']							= isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
		$data['balance_adjustment_method']			= isset($raw_data['mode']) ? $raw_data['mode'] : null;
		
		$data['game_platform_id'] 	                = $this->game_platform_id;

		return $data;
	}
	
	public function debitCreditAmountToWallet($params){
		$this->utils->debug_log("SPINIX_SEAMLESS_SERVICE_API: (debitCreditAmount)", $params);

		$player_balance = $this->game_api->dBtoGameAmount($params['current_player_balance']);
		$before_balance = $this->game_api->dbToGameAmount($player_balance);
		$after_balance = $before_balance;
		
		$mode = $params['mode'];
		$flagrefunded = false;
		$response_code['success'] = true;

		$params['is_main'] = false;
		$transactionLists = isset($this->request['transaction_list']) ? $this->request['transaction_list'] : null; 
		$transaction = isset($transactionLists[0]) ? $transactionLists[0] : null;
		$params['transaction_list'] = $transactionLists;


		$params['req_id'] 			            	= isset($this->request['req_id']) ? $this->request['req_id'] : null;
		$params['user_id'] 			            	= isset($this->request['user_id']) ? $this->request['user_id'] : null;
		$params['user_token'] 			            = isset($this->request['user_token']) ? $this->request['user_token'] : null;
		$params['game_id'] 			            	= isset($this->request['game_id']) ? $this->request['game_id'] : null;
		$params['game_type'] 			            = isset($this->request['game_type']) ? $this->request['game_type'] : null;
		$params['currency'] 			            = isset($this->request['currency']) ? $this->request['currency'] : null;
		$params['round_id'] 			            = isset($this->request['round_id']) ? $this->request['round_id'] : null;
		$params['valid_turnover'] 			        = isset($this->request['valid_turnover']) ? $this->request['valid_turnover'] : null;		

		$params['transaction_id'] 			   		= isset($transaction['id']) ? $transaction['id'] : null;
		$params['type'] 			       			= isset($transaction['type']) ? $transaction['type'] : null;
		$params['amount'] 			       	 		= isset($transaction['amount']) ? $transaction['amount'] : null;
		$params['info'] 			       			= isset($transaction['info']) ? $transaction['info'] : null;
		$params['is_end'] 			       			= isset($transaction['is_end']) ? $transaction['is_end'] : null;
		$params['timestamp'] 			       		= isset($transaction['timestamp']) ? $transaction['timestamp'] : null;

		$uniqueId = $params['transaction_id'];
		$isAdded = $this->insertIgnoreTransactionRecord($params, $before_balance, $after_balance, $flagrefunded);

		if($isAdded==false){
			$this->utils->error_log("SPINIX_SEAMLESS_SERVICE_API: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			return false;
		}	

		$response_code = $this->adjustWallet($mode, $params);

		$this->CI->debug_log("SPINIX_SEAMLESS_GAME_API - response_code: ", $response_code);
		if($response_code['success']){
			if($params['amount'] <> 0){
				$updatedAfterBalance = $this->spinix_seamless_wallet_transactions->updateAfterBalanceByTransactionId($uniqueId, $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update after balance');
				}
			}
			#update related bet status here
				
			if($params['type'] == self::ROUND_TYPE_WIN){
				$updatedStatus = $this->spinix_seamless_wallet_transactions->updateBetTransactionByRoundIdAndPlayerId($params['round_id'],$params['player_id'], ['status' => Game_logs::STATUS_SETTLED]);
				if(!$updatedStatus){
					$this->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a payout request');
				}
			}
			if($params['type'] == self::ROUND_TYPE_CANCEL_BET){
				$updatedStatus = $this->spinix_seamless_wallet_transactions->updateBetTransactionByRoundIdAndPlayerId($params['round_id'],$params['player_id'], ['status' => Game_logs::STATUS_REFUND]);
				if(!$updatedStatus){
					$this->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a payout request');
				}
			}
			
			$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
		}
		
		return $response_code['success'];
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['player_id'];
		$before_balance = $this->game_api->queryPlayerBalance($params['user_id'])['balance'];
		$response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);

		$after_balance = $before_balance;
		$response_code['success'] = true;
		$amount = isset($params['amount']) ? $params['amount'] : 0;
		$params['unique_id'] = isset($params['transaction_id']) ? $params['transaction_id'] : null;

        if($action == self::DEBIT) {
            $amount = abs($amount);
            if($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::DEBIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteActionType);
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
				$this->CI->wallet_model->setGameProviderIsEndRound($params['is_end']);
				$this->wallet_model->setGameProviderBetAmount($amount);
				$this->wallet_model->setExternalGameId($params['game_id']);
				$amount = $this->game_api->gameAmountToDB($amount);

				$this->CI->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API ADJUST_WALLET: AMOUNT', $amount);
				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
				$response_code['success'] = true;

				
				$this->CI->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API.- balance afer adjust_wallet:', $deduct_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: failed to deduct subwallet');
				}
            }
			$after_balance = $this->game_api->queryPlayerBalance($params['user_id'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {
			$amount = abs($amount);
			if($this->utils->compareResultFloat($amount, '=', 0)){
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::CREDIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteActionType);
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
				$this->wallet_model->setGameProviderPayoutAmount($amount);
				$this->wallet_model->setExternalGameId($params['game_id']);
				$this->wallet_model->setGameProviderIsEndRound($params['is_end']);
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
			}elseif($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::CREDIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteActionType);
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
				$this->wallet_model->setGameProviderPayoutAmount($amount);
				$this->wallet_model->setExternalGameId($params['game_id']);
				$this->wallet_model->setGameProviderIsEndRound($params['is_end']);
				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
								
				$response_code['success'] = true;
				
				$this->CI->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}
			$after_balance = $this->game_api->queryPlayerBalance($params['user_id'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}	elseif ($action == self::CANCEL) {
			$amount = abs($amount);

			if($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::CANCEL;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
				$this->wallet_model->setGameProviderPayoutAmount($amount);
				$this->wallet_model->setExternalGameId($params['game_id']);
				$this->CI->wallet_model->setGameProviderIsEndRound($params['is_end']);
				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
								
				$response_code['success'] = true;
				
				$this->CI->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('SPINIX_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}
			$after_balance = $this->game_api->queryPlayerBalance($params['user_id'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}
		return $response_code;

	}


	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount, &$afterBalance = null)
	{
		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if ($mode == self::DEBIT) {
			$success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
		} elseif ($mode == self::CREDIT) {
			$success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
		}

		return $success;
	}
}///END OF FILE////////////