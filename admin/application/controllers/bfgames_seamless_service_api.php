<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * {{url}}/bfgames_seamless_service_api/authenticateToken
 * {{url}}/bfgames_seamless_service_api/tokenRefresh
 * {{url}}/bfgames_seamless_service_api/getBalance
 * {{url}}/bfgames_seamless_service_api/withdraw
 * {{url}}/bfgames_seamless_service_api/deposit
 * {{url}}/bfgames_seamless_service_api/rollback
 */
class Bfgames_seamless_service_api extends BaseController
{
	const METHOD_AUTHENTICATE_TOKEN = 'authenticateToken'; #authenticate token
	const METHOD_TOKEN_REFRESH = 'tokenRefresh'; #extend validity of create a new one
	const METHOD_GET_BALANCE = 'getBalance'; #get balance
	const METHOD_WITHDRAW = 'withdraw'; #bet
	const METHOD_DEPOSIT = 'deposit'; #payout
	const METHOD_ROLLBACK = 'rollback'; #rollback //should be accepted even with expired token


	const UI_MESSAGE_TYPES = array('INFO' => 'INFO', 'WARNING' => 'WARNING', 'ERROR' => 'ERROR');
    const UI_MESSAGE_DISPLAY_TYPES = array('NOTIFICATION' => 'NOTIFICATION', 'POPUP' => 'POPUP');

    const UI_MESSAGE_DEFAULT_TITLE = 'Notification';
    const UI_MESSAGE_DEFAULT_TEXT = 'Success';
    const UI_MESSAGE_DEFAULT_TYPE = 'INFO';
    const UI_MESSAGE_DEFAULT_DISPLAY = 'NOTIFICATION';


	const ALLOWED_API_METHODS = [
		self::METHOD_AUTHENTICATE_TOKEN,
		self::METHOD_TOKEN_REFRESH,
		self::METHOD_GET_BALANCE,
		self::METHOD_WITHDRAW,
		self::METHOD_DEPOSIT,
		self::METHOD_ROLLBACK
	];
	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'rollback';


	const SUCCESS = 0;

	# GP ERROR_CODES
	const CODE_SUCCCESS 										= 0;

	#GENERAL ERRORS

	const CODE_GENERAL_API_ERROR_TRIGGERING_ROLLBACK			= 1000;
	const CODE_CURRENCY_TRANSFER_ERROR							= 1001;
	const CODE_WALLET_LOCK_ERROR								= 1002;
	const CODE_INVALID_METHOD_NAME								= 1003;
	const CODE_GENERAL_API_ERROR								= 1004;
	const CODE_REQUEST_CANNOT_SUCCEED							= 1005;


	#AUTHENTICATION ERRORS
	const CODE_GENERAL_AUTHENTICATION_ERROR						= 2000;
	const CODE_INVALID_PLAYER_TOKEN_ERROR						= 2001;
	const CODE_INVALID_PLAYER_INDENTIFICATION_ERROR				= 2002;
	const CODE_PLAYER_NOT_LOGGED_ERROR				 			= 2003;


	// Transaction Errors
	const CODE_TRANSACTION_ALREADY_PROCESSED_ERROR 				= 3000;
	const CODE_TRANSACTION_NOT_FOUND 							= 3001;
	const CODE_ROUND_ALREADY_ENDED 								= 3002;

	// Player Funds Errors
	const CODE_PLAYER_INSUFFICIENT_FUNDS_ERROR 					= 4000;
	const CODE_PLAYER_EXCEEDED_LOSS_LIMIT_ERROR 				= 4001;
	const CODE_PLAYER_EXCEEDED_WAGER_LIMIT_ERROR 				= 4002;
	const CODE_PLAYER_EXCEEDED_GAME_SESSION_TIME_LIMIT_ERROR 	= 4003;
	const CODE_PLAYER_EXCEEDED_LIMIT_ERROR 						= 4004;

	const HTTP_CODE_200 = 200;
	const HTTP_CODE_400 = 400;
	const HTTP_CODE_403 = 403;
	const HTTP_CODE_404 = 404;
	const HTTP_CODE_405 = 405;
	const HTTP_CODE_500 = 500;


	//INTERNAL ERROR CODES
	const ERROR_REQUEST_METHOD_NOT_ALLOWED = 405;
	const ERROR_API_METHOD_NOT_ALLOWED = 401;
	const ERROR_CONNECTION_TIMED_OUT = 500;
	const ERROR_INTERNAL_SERVER_ERROR = 500;

	const RESPONSE_CODES = [
		self::CODE_SUCCCESS											=> "Success",
		self::CODE_GENERAL_API_ERROR_TRIGGERING_ROLLBACK			=> 'General API error',
		self::CODE_CURRENCY_TRANSFER_ERROR 							=> 'Currency transfer error',
		self::CODE_WALLET_LOCK_ERROR 								=> 'Wallet lock error',
		self::CODE_INVALID_METHOD_NAME 								=> 'Invalid method name',
		self::CODE_GENERAL_API_ERROR 								=> 'General API error',
		self::CODE_REQUEST_CANNOT_SUCCEED 							=> 'Request cannot succeed',
		self::CODE_GENERAL_AUTHENTICATION_ERROR 					=> 'Authentication error',
		self::CODE_INVALID_PLAYER_TOKEN_ERROR 						=> 'Invalid player token error',
		self::CODE_INVALID_PLAYER_INDENTIFICATION_ERROR 			=> 'Invalid player identification error',
		self::CODE_PLAYER_NOT_LOGGED_ERROR 							=> 'Player not logged in error',
		self::CODE_TRANSACTION_ALREADY_PROCESSED_ERROR				=> 'Transaction already processed',
		self::CODE_TRANSACTION_NOT_FOUND							=> 'Transaction not found',
		self::CODE_ROUND_ALREADY_ENDED								=> 'Round already ended',

		self::CODE_PLAYER_INSUFFICIENT_FUNDS_ERROR 					=> 'Insufficient fund',
		self::CODE_PLAYER_EXCEEDED_LOSS_LIMIT_ERROR 				=> 'Exceeded lost limit',
		self::CODE_PLAYER_EXCEEDED_WAGER_LIMIT_ERROR				=> 'Exceeded wager limit',
		self::CODE_PLAYER_EXCEEDED_GAME_SESSION_TIME_LIMIT_ERROR 	=> 'Exceeded game session time limit',
		self::CODE_PLAYER_EXCEEDED_LIMIT_ERROR 						=> 'Exceeed limit',

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
		self::CODE_SUCCCESS 								=> 200
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
	private $api_method = null;
	private $token;
	private $message;
	private $enable_ui_message;
	private $checkPreviousMonth=false;
	private $balance;
	private $offline_secret_token;


	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'bfgames_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));
		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->request = $this->parseRequest();

		$this->token = isset($this->request['args']['token']) ? $this->request['args']['token'] : null;
		$this->requestHeaders = $this->input->request_headers();
	}

	public function index()
	{
		$this->game_platform_id = BFGAMES_SEAMLESS_GAME_API;
		$this->method = isset($this->request['methodname']) ? $this->request['methodname'] : null;

		if ($this->initialize()) {
			return $this->selectMethod();
		} else {
			$this->debug_log("BFGAMES_SEAMLESS_SERVICE_API:", "Failed to initialized, check for error_log");
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API: raw request', $this->request);
		$validated = $this->validateInitialRequest();


		$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API: validateInitialRequest result', $validated);

		if (!$validated['success']) {
			$data = [
				"methodname" => isset($this->request['methodname']) ? $this->request['methodname'] : null,
				"reflection" => [
					"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
				],
				"servicenumber" => 1,
				"servicename" => $this->game_api->agent_name,
				"result" => [
					"errorcode" => $validated['code'],
					"currency" => strtoupper($this->game_api->currency),
					"token" => $this->token,
					// "player_id" => $gameUsername,
					"balance" => (int)$this->balance,
				],
				"version" => "1.0",
				"type" => "jsonwsp/response"
			];
			$this->setOutput($validated['code'], $data, $validated['success']);
		}

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		if (!$this->game_api) {
			$this->setOutput(self::CODE_GENERAL_API_ERROR, [
				"codeId" => self::CODE_GENERAL_API_ERROR,
				"message" => "BFGAMES_SEAMLESS_SERVICE_API: (initialize) ERROR LOAD: ",
				$this->game_platform_id,
				"data" => []
			], false);

			$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: (initialize) ERROR LOAD: ", $this->game_platform_id);
			http_response_code(500);
			return false;
		}

		$validated = $this->validateInitialRequestAfterGameLoaded();

		$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API: validateInitialRequestAfterGameLoaded result', $validated);

		if (!$validated['success']) {
			$data = [
				"methodname" => isset($this->request['methodname']) ? $this->request['methodname'] : null,
				"reflection" => [
					"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
				],
				"servicenumber" => 1,
				"servicename" => $this->game_api->agent_name,
				"result" => [
					"errorcode" => $validated['code'],
					"currency" => strtoupper($this->game_api->currency),
					"token" => $this->token,
					// "player_id" => $gameUsername,
					"balance" => (int)$this->balance,
				],
				"version" => "1.0",
				"type" => "jsonwsp/response"
			];
			$this->setOutput($validated['code'], $data, $validated['success']);
		}

		$this->enable_ui_message = $this->game_api->enable_ui_message;

		$isOffline = isset($this->request['args']['offline']) ? $this->request['args']['offline']: false;

		if($isOffline){
			$this->offline_secret_token = $this->game_api->offline_secret_token;
		}

		if (date('j', $this->utils->getTimestampNow()) <= $this->game_api->allowed_days_to_check_previous_monthly_table) {
			$this->checkPreviousMonth = true;
		}

		$this->use_third_party_token = $this->game_api->use_third_party_token;

		$this->transactions_table = $this->game_api->getTransactionsTable();

		$this->bfgames_seamless_wallet_transactions->setTableName($this->transactions_table);

		$this->currency = $this->game_api->getCurrency();
		return true;
	}


	#-----------------METHODS------------------------------
	public function selectMethod()
	{
		if ($this->isAllowedApiMethod()) {
			switch ($this->method) {
				case self::METHOD_AUTHENTICATE_TOKEN:
					return $this->authenticateToken();
				case self::METHOD_TOKEN_REFRESH:
					return $this->tokenRefresh();
				case self::METHOD_GET_BALANCE:
					return $this->getBalance();
				case self::METHOD_WITHDRAW:
					return $this->withdraw();
				case self::METHOD_DEPOSIT:
					return $this->deposit();
				case self::METHOD_ROLLBACK:
					return $this->rollback();
				default:
					$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API: Invalid API Method');
					http_response_code(self::HTTP_CODE_400);
			}
		}
	}

	public function generatePlayerToken()
	{
		$username = $this->request['username'];

		$result = $this->game_api->getPlayerTokenByUsername($username);
		print_r(["token" => $result]);
	}

	#----------------------GP API_ALLOWED_METHODS----------------------------

	public function authenticateToken()
	{
		$type = self::METHOD_AUTHENTICATE_TOKEN;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$gameUsername = null;
		$code = self::CODE_SUCCCESS;
		$commonRules = $this->getCommonValidationRules();
		$customRules = [];
		$this->rules = array_merge($commonRules, $customRules);
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @authenticateToken: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try {
			$validated = $this->validateRequest();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @authenticateToken: validateRequest result: ", $validated);
			if (!$validated['success']) {
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @authenticateToken", [$code, $message]);
				throw new Exception($validated['code']);
			}

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @authenticateToken: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);

			if (!$player) {
				$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @authenticateToken - User does not exist invalid token", [$code, $message]);
				throw new Exception($code);
			}

			$playerId = $player->player_id;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		} catch (\Exception $e) {
			$code = $e->getMessage();
		}
		$this->balance = $balance;

		$data = [
			"methodname" => $type,
			"reflection" => [
				"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
			],
			"servicenumber" => 1,
			"servicename" => $this->game_api->agent_name,
			"result" => [
				"errorcode" => (int)$code,
				"currency" => strtoupper($this->game_api->currency),
				"token" => $this->token,
				"player_id" => $gameUsername,
				"balance" => intval($this->game_api->dbToGameAmount($balance))
			],
			"version" => "1.0",
			"type" => "jsonwsp/response"
		];

		$fields = [
			'player_id' => $playerId,
		];

		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}
	public function getBalance()
	{
		$type = self::METHOD_GET_BALANCE;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$gameUsername=null;
		$player_username=null;
		$code = self::CODE_SUCCCESS;
		$commonRules = $this->getCommonValidationRules();
		$customRules = [];
		$this->rules = array_merge($commonRules, $customRules);
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @getBalance: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try {
			$validated = $this->validateRequest();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @getBalance: validateRequest result: ", $validated);
			if (!$validated['success']) {
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @getBalance", [$code, $message]);
				throw new Exception($validated['code']);
			}

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();

			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @getBalance: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);

			if (!$player) {
				$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @getBalance - User does not exist invalid token", [$code, $message]);
				throw new Exception($code);
			}

			$playerId = $player->player_id;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		} catch (\Exception $e) {
			$code = $e->getMessage();
		}
		$data = [
			"methodname" => $type,
			"reflection" => [
				"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
			],
			"servicenumber" => 1,
			"servicename" => $this->game_api->agent_name,
			"result" => [
				"errorcode" => (int)$code,
				"currency" => strtoupper($this->game_api->currency),
				"token" => $this->token,
				"player_id" => $gameUsername,
				"balance" => intval($this->game_api->dBtoGameAmount($balance))
			],
			"version" => "1.0",
			"type" => "jsonwsp/response"
		];

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}


	public function tokenRefresh()
	{
		$type = self::METHOD_TOKEN_REFRESH;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$code = self::CODE_SUCCCESS;
		$commonRules = $this->getCommonValidationRules();
		$customRules = [];
		$this->rules = array_merge($commonRules, $customRules);
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @refreshToken: rules applied: ", $this->rules);
		$message = $this->setResponseMessage($code);
		try {
			$validated = $this->validateRequest();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @refreshToken: validateRequest result: ", $validated);
			if (!$validated['success']) {
				$code = $validated['code'];
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @refreshToken", [$code, $message]);
				throw new Exception($validated['code']);
			}

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();

			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @refreshToken: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);

			if (!$player) {
				$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
				$message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @refreshToken - User does not exist invalid token", [$code, $message]);
				throw new Exception($code);
			}
			// no need to extend validity of token, external_common_tokens does not expires

			$playerId = $player->player_id;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		} catch (\Exception $e) {
			$code = $e->getMessage();
		}

		$data = [
			"methodname" => $type,
			"reflection" => [
				"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
			],
			"servicenumber" => 1,
			"servicename" => $this->game_api->agent_name,
			"result" => [
				"token" => $this->token,
			],
			"version" => "1.0",
			"type" => "jsonwsp/response"
		];

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}

	public function withdraw()
	{
		// note: bet amount can be zero if has free spin, will not call decrease_balance
		$type = self::METHOD_WITHDRAW;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$additionalResponse = null;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw: rules applied: ", $this->rules);
		$this->message = $this->setResponseMessage($code);
		try {
			$validated = $this->validateRequest();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw: validateRequest result: ", $validated);
			if (!$validated['success']) {
				$code = $validated['code'];
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw", [$code, $this->message]);
				throw new Exception($validated['code']);
			}

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);
			if (!$player) {
				$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw - User does not exist invalid token", [$code, $this->message]);
				throw new Exception($code);
			}


			$playerId = $player->player_id;
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);

			$extra=[];
			$params = [
				'caller_id'             => isset($this->request['args']['caller_id']) ? $this->request['args']['caller_id'] : null,
				'caller_password'       => isset($this->request['args']['caller_password']) ? $this->request['args']['caller_password'] : null,
				'action_id'             => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				'amount'                => isset($this->request['args']['amount']) ? abs($this->request['args']['amount']) : null,
				'currency'              => isset($this->request['args']['currency']) ? $this->request['args']['currency'] : null,
				'game_ref'              => isset($this->request['args']['game_ref']) ? $this->request['args']['game_ref'] : null,
				'game_ver'              => isset($this->request['args']['game_ver']) ? $this->request['args']['game_ver'] : null,
				'jackpot_contributions' => isset($this->request['args']['jackpot_contributions']) ? $this->request['args']['jackpot_contributions'] : null,
				'mode'                  => self::DEBIT,
				'round_id'              => isset($this->request['args']['round_id']) ? $this->request['args']['round_id'] : null,
				'token'                 => isset($this->request['args']['token']) ? $this->request['args']['token'] : null,
				'methodname'            => isset($this->request['methodname']) ? $this->request['methodname'] : null,
				'method'            	=> $type,
				'mirror_id'             => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null,
				'offline'             	=> isset($this->request['args']['offline']) ? $this->request['args']['offline'] : null,
				'external_uniqueid'     => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				'player_id'             => $playerId,
				'before_balance'        => $before_balance,
				'game_username'			=> $gameUsername
			];

			
			$params['converted_amount']  = isset($this->request['args']['amount']) ? abs($this->game_api->gameAmountToDBTruncateNumber($this->request['args']['amount'])) : null;
			$params['bet_amount']       = isset($params['converted_amount']) ? $params['converted_amount'] : null;
			$params['payout_amount']    = 0;
			$params['valid_amount']     = isset($params['converted_amount']) ? $params['converted_amount'] : null;
			$params['result_amount']    = 0 - (isset($params['converted_amount']) ? $params['converted_amount'] : 0);
			//pre-compute, update later if not match
			$params['after_balance'] 	= $before_balance - $params['bet_amount'];
			$params['external_uniqueid'] = $params['methodname'].'-'.$params['action_id'];

			if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_RELATED_ACTION_BET);
			}
			if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
				$this->wallet_model->setGameProviderBetAmount($params['bet_amount']);
			}

			if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
			}
			if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
				$this->wallet_model->setGameProviderIsEndRound(false);
			}
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
				$this->wallet_model->setRelatedActionOfSeamlessService(null);
			}
			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
				$this->wallet_model->setRelatedUniqueidOfSeamlessService(null);
			}

			$before_balance = $after_balance = $current_player_balance;
		

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function () use (
				$player,
				$params,
				&$insufficient_balance,
				&$before_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$extra
			) {
				list($trans_success, $before_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
				$this->utils->debug_log(
					"BFGAMES_SEAMLESS_SERVICE_API @withdraw lockAndTransForPlayerBalance",
					'trans_success',
					$trans_success,
					'before_balance',
					$before_balance,
					'after_balance',
					$after_balance,
					'insufficient_balance',
					$insufficient_balance,
					'isAlreadyExists',
					$isAlreadyExists,
					'additionalResponse',
					$additionalResponse,
					'isTransactionAdded',
					$isTransactionAdded
				);
				return $trans_success;
			});
			$balance = $after_balance;

			if ($insufficient_balance) {
				$code = self::CODE_PLAYER_INSUFFICIENT_FUNDS_ERROR;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw - Insufficient balance", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($isAlreadyExists) {
				$code = self::CODE_TRANSACTION_ALREADY_PROCESSED_ERROR; #return success if transaction already exists as per API docs
				// $code = self::CODE_SUCCCESS; #return success if transaction already exists as per API docs
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw - Transaction already exists", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($trans_success == false) {
				$code = self::CODE_GENERAL_API_ERROR_TRIGGERING_ROLLBACK;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @withdraw  lock failed", [$code, $this->message]);
				throw new Exception($code);
			}

		 	$balance = $after_balance;

			$status = true;
		} catch (\Exception $e) {
			$code = $e->getMessage();
		}
		$data = [
			"methodname" => $type,
			"reflection" => [
				"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
			],
			"servicenumber" => 1,
			"servicename" => $this->game_api->agent_name,
			"result" => [
				"errorcode" => (int)$code,
				"currency" => strtoupper($this->game_api->currency),
				// "token" => $this->token,
				"transaction_id" => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				"balance" => intval($this->game_api->dBtoGameAmount($balance))
			],
			"version" => "1.0",
			"type" => "jsonwsp/response"
		];

		if($this->enable_ui_message){
			$data['result']['ui_message'] = $this->generateMessage($code, $this->message);
		}

		$fields = [
			'player_id' => $playerId,
		];

		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}

	public function deposit()
	{
		$type = self::METHOD_DEPOSIT;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$additionalResponse = null;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit: rules applied: ", $this->rules);
		$this->message = $this->setResponseMessage($code);
		try {
			$validated = $this->validateRequest();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit: validateRequest result: ", $validated);
			if (!$validated['success']) {
				$code = $validated['code'];
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit", [$code, $this->message]);
				throw new Exception($validated['code']);
			}
			
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);

		
			$isOffline = isset($this->request['args']['offline']) ? $this->request['args']['offline']: false;

			// if (!$player && !$isOffline) {
			if (!$player && !$isOffline) {
				$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - User does not exist invalid token", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($isOffline) {
				$token = isset($this->request['args']['token']) ? $this->request['args']['token'] : null;
			
				if ($token && strpos($token, '_') !== false) {
					list($rawOfflineToken, $rawOfflineGameUserName) = explode('_', $token);
					list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails($rawOfflineGameUserName);
			
					if (!$player) {
						$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
						$this->message = $this->setResponseMessage($code);
						$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - User does not exist invalid token", [$code, $this->message]);
						throw new Exception($code);
					}
				} else {
					$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
					$this->message = $this->setResponseMessage($code);
					$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Token format invalid or missing underscore", [$code, $this->message]);
					throw new Exception($code);
				}
			}

			$playerId = isset($player->player_id) ? $player->player_id : null;
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);

			$isAlreadyExists = $this->bfgames_seamless_wallet_transactions->isTransactionAlreadyExistReturnBoolean($this->request['args']['action_id'],$playerId, $this->request['methodname']);

			if($isAlreadyExists){
				$code = self::CODE_TRANSACTION_ALREADY_PROCESSED_ERROR; #return success if transaction already exists as per API docs
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Transaction already exists", [$code, $this->message]);
				throw new Exception($code);
			}

			$roundId = isset($this->request['args']['round_id']) ? $this->request['args']['round_id'] : null;
			$related_bet = $this->bfgames_seamless_wallet_transactions->getTransactionByParamsArray([
				'game_username' => (string)$gameUsername,
				'round_id' =>  (string)$roundId,
				'methodname' => (string)self::METHOD_WITHDRAW
			]);


			if(!$related_bet && $this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$related_bet = $this->bfgames_seamless_wallet_transactions->getTransactionByParamsArray([
					'game_username' => (string)$gameUsername,
					'round_id' =>  (string)$roundId,
					'methodname' => (string)self::METHOD_WITHDRAW
				],$this->game_api->getTransactionsPreviousTable());
			}

			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API @deposit related bet last query: ", $this->CI->db->last_query());

			if (!$related_bet) {
				// $code = self::CODE_GENERAL_API_ERROR;
				$code = self::CODE_TRANSACTION_NOT_FOUND;
				$this->message  = $this->setResponseMessage($code, "General Error. Related bet does not exists");
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - General Error. Related bet does not exist", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($isOffline && $related_bet['status'] != Game_logs::STATUS_PENDING) {
				$code = self::CODE_ROUND_ALREADY_ENDED;
				$this->message  = $this->setResponseMessage($code, "General Error. Related bet does not exists");
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - General Error. Related bet does not exist", [$code, $this->message]);
				throw new Exception($code);
			}

			// don't allow multiple deposit

			$transactionsGroupByRoundsCount = $this->bfgames_seamless_wallet_transactions->getTransactionsGroupByRoundIdCount([
				'game_username' => (string)$gameUsername,
				'round_id' =>  (string)$roundId
			]);

			if(!$transactionsGroupByRoundsCount && $this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$transactionsGroupByRoundsCount = $this->bfgames_seamless_wallet_transactions->getTransactionsGroupByRoundIdCount([
					'game_username' => (string)$gameUsername,
					'round_id' =>  (string)$roundId
				],$this->game_api->getTransactionsPreviousTable());
			}

			if ($transactionsGroupByRoundsCount>=2) {
				$code = self::CODE_ROUND_ALREADY_ENDED;
				$this->message  = $this->setResponseMessage($code, "General Error. Related bet does not exists");
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - General Error. Related bet does not exist", [$code, $this->message]);
				throw new Exception($code);
			}
			
			$related_bet_external_uniqueid = isset($related_bet['external_uniqueid']) ? $related_bet['external_uniqueid'] : null;
			
			$extra=[];
			$params = [
				'caller_id'             => isset($this->request['args']['caller_id']) ? $this->request['args']['caller_id'] : null,
				'caller_password'       => isset($this->request['args']['caller_password']) ? $this->request['args']['caller_password'] : null,
				'action_id'             => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				'amount'                => isset($this->request['args']['amount']) ? $this->request['args']['amount']: null,
				'currency'              => isset($this->request['args']['currency']) ? $this->request['args']['currency'] : null,
				'game_ref'              => isset($this->request['args']['game_ref']) ? $this->request['args']['game_ref'] : null,
				'game_ver'              => isset($this->request['args']['game_ver']) ? $this->request['args']['game_ver'] : null,
				'jackpot_contributions' => isset($this->request['args']['jackpot_contributions']) ? $this->request['args']['jackpot_contributions'] : null,
				'round_details' => isset($this->request['args']['round_details']) ? $this->request['args']['round_details'] : null,
				'jackpot_winnings' => isset($this->request['args']['jackpot_winnings']) ? $this->request['args']['jackpot_winnings'] : null,
				'mode'                  => self::CREDIT,
				'round_id'              => isset($this->request['args']['round_id']) ? $this->request['args']['round_id'] : null,
				'round_end'              => isset($this->request['args']['round_end']) ? $this->request['args']['round_end'] : null,
				'token'                 => isset($this->request['args']['token']) ? $this->request['args']['token'] : null,
				'methodname'            => isset($this->request['methodname']) ? $this->request['methodname'] : null,
				'method'            	=> $type,
				'mirror_id'             => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null,
				'offline'             => isset($this->request['args']['offline']) ? $this->request['args']['offline'] : null,
				'external_uniqueid'     => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				'player_id'             => $playerId,
				'before_balance'        => $before_balance,
				'game_username'			=> $gameUsername,
			];
			//pre-compute, update later if not match
			$params['converted_amount']  =isset($this->request['args']['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['args']['amount']) : null;
			$params['bet_amount']       = 0;
			$params['payout_amount']    = $params['converted_amount'];
			$params['valid_amount']    	= $params['payout_amount'];
			$params['after_balance'] 	= $before_balance + $params['payout_amount'];
			$params['result_amount'] 	= $params['payout_amount'] - $params['bet_amount'];
			$params['external_uniqueid'] = $params['methodname'].'-'.$params['action_id'];
			$params['related_bet_external_uniqueid'] = $related_bet_external_uniqueid;
 
			if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_RELATED_ACTION_PAYOUT);
			}
			if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
				$this->wallet_model->setGameProviderBetAmount($params['bet_amount']);
			}

			if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
				$this->wallet_model->setGameProviderPayoutAmount($params['payout_amount']);
			}

			if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
			}
			if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
				$this->wallet_model->setGameProviderIsEndRound($params['round_end']);
			}
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && $related_bet) {
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
			}
			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && $related_bet) {
				$relatedUniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($related_bet_external_uniqueid, self::DEBIT);
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
			}

			$before_balance = $after_balance = $current_player_balance;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function () use (
				$player,
				$params,
				&$insufficient_balance,
				&$before_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$extra
			) {
				list($trans_success, $before_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
				$this->utils->debug_log(
					"BFGAMES_SEAMLESS_SERVICE_API @deposit lockAndTransForPlayerBalance",
					'trans_success',
					$trans_success,
					'before_balance',
					$before_balance,
					'after_balance',
					$after_balance,
					'insufficient_balance',
					$insufficient_balance,
					'isAlreadyExists',
					$isAlreadyExists,
					'additionalResponse',
					$additionalResponse,
					'isTransactionAdded',
					$isTransactionAdded
				);
				return $trans_success;
			});


			if ($insufficient_balance) {
				$code = self::CODE_PLAYER_INSUFFICIENT_FUNDS_ERROR;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Insufficient balance", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($isAlreadyExists) {
				$code = self::CODE_TRANSACTION_ALREADY_PROCESSED_ERROR; #return success if transaction already exists as per API docs
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Transaction already exists", [$code, $this->message]);
				throw new Exception($code);
			}

			//check if round already ended
			if(isset($additionalResponse['round_end']) && $additionalResponse['round_end']){
				$code = self::CODE_ROUND_ALREADY_ENDED;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Round already ended", [$code, $this->message]);
				throw new Exception($code);
			}
			
			if ($trans_success == false) {
				$code = self::CODE_GENERAL_API_ERROR_TRIGGERING_ROLLBACK;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit  lock failed", [$code,$this->message]);
				throw new Exception($code);
			}

			$balance = $after_balance;

			$status = true;
		} catch (\Exception $e) {
			$code = $e->getMessage();
		}

		$data = [
			"methodname" => $type,
			"reflection" => [
				"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
			],
			"servicenumber" => 1,
			"servicename" => $this->game_api->agent_name,
			"result" => [
				"errorcode" => (int)$code,
				"currency" => strtoupper($this->game_api->currency),
				"token" => $this->token,
				"transaction_id" => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				"balance" => intval($this->game_api->dBtoGameAmount($balance))
			],
			"version" => "1.0",
			"type" => "jsonwsp/response"
		];

		if($isOffline){
			$data['result'] = [
				"errorcode" => ($code == self::CODE_SUCCCESS) ? null : (int)$code,
				"transaction_id" => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
			];
		}
		if($this->enable_ui_message){
			$data['result']['ui_message'] = $this->generateMessage($code, $this->message);
		}


		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}
	public function rollback()
	{
		$type = self::METHOD_ROLLBACK;
		$status = false;
		$balance = 0;
		$playerId = null;
		$player = null;
		$code = self::CODE_SUCCCESS;
		$before_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$additionalResponse = null;

		$commonRules = $this->getCommonValidationRules();
		$customRules = [];
		$this->rules = array_merge($commonRules, $customRules);

		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback: rules applied: ", $this->rules);
		$this->message = $this->setResponseMessage($code);
		try {
			$validated = $this->validateRequest();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback: validateRequest result: ", $validated);
			if (!$validated['success']) {
				$code = $validated['code'];
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback", [$code, $this->message]);
				throw new Exception($validated['code']);
			}

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerDetails();
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback: getPlayerDetails result: ", [$playerStatus, $player, $gameUsername, $player_username]);

			if (!$player) {
				$code = self::CODE_INVALID_PLAYER_TOKEN_ERROR;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - User does not exist invalid token", [$code, $this->message]);
				throw new Exception($code);
			}

			$playerId = $player->player_id;
			$before_balance 			= $after_balance  = $current_player_balance = $this->getPlayerBalance($player_username, $playerId);


			$roundId = isset($this->request['args']['round_id']) ? $this->request['args']['round_id'] : null;
			$rollbackActionId = isset($this->request['args']['rollback_action_id']) ? $this->request['args']['rollback_action_id'] : null;

			$isAlreadyExists = $this->bfgames_seamless_wallet_transactions->isTransactionAlreadyExistReturnBoolean($this->request['args']['action_id'],$playerId, $this->request['methodname']);

			if($isAlreadyExists){
				$code = self::CODE_TRANSACTION_ALREADY_PROCESSED_ERROR; #return success if transaction already exists as per API docs
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - Transaction already exists", [$code, $this->message]);
				throw new Exception($code);
			}

			// related to withdraw using rollback_action_id
			$related_bet = $this->bfgames_seamless_wallet_transactions->getTransactionByParamsArray([
				'game_username' => (string)$gameUsername,
				'action_id' =>  (string)$rollbackActionId,
				'methodname' => (string)self::METHOD_WITHDRAW
			]);

			if(!$related_bet && $this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$related_bet = $this->bfgames_seamless_wallet_transactions->getTransactionByParamsArray([
					'game_username' => (string)$gameUsername,
					'action_id' =>  (string)$rollbackActionId,
					'methodname' => (string)self::METHOD_WITHDRAW
				],$this->game_api->getTransactionsPreviousTable());
			}


			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API @rollback related bet last query: ", $this->CI->db->last_query());
			if (!$related_bet) {
				$code = self::CODE_TRANSACTION_NOT_FOUND;
				$this->message  = $this->setResponseMessage($code, "General Error. Related bet does not exists");
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - General Error. Related bet does not exist", [$code, $this->message]);
				throw new Exception($code);
			}

			if($related_bet['round_id'] !== $this->request['args']['round_id']){
				$code = self::CODE_TRANSACTION_NOT_FOUND;
				$this->message  = $this->setResponseMessage($code, "General Error. Related bet does not exists");
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - General Error. Related bet does not exist", [$code, $this->message]);
				throw new Exception($code);
			}

			// dont allow to rollback status: should be pending status
			// withdraw -> deposit -> rollback = should not allow
			if($related_bet['status'] != Game_logs::STATUS_PENDING){
				$code = self::CODE_ROUND_ALREADY_ENDED;
				$this->message = $this->setResponseMessage($code, "General Error. Related bet status is already settled, rollback is not allowed");
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @rollback - User does not exist invalid token", [$code, $this->message]);
				throw new Exception($code);
			}

			$related_bet_external_uniqueid = isset($related_bet['external_uniqueid']) ? $related_bet['external_uniqueid'] : null;
			
			$extra=[];
			$params = [
				'caller_id'             => isset($this->request['args']['caller_id']) ? $this->request['args']['caller_id'] : null,
				'caller_password'       => isset($this->request['args']['caller_password']) ? $this->request['args']['caller_password'] : null,
				'action_id'             => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				'amount'                => isset($related_bet['amount']) ? $related_bet['amount']: null,
				'currency'              => isset($this->request['args']['currency']) ? $this->request['args']['currency'] : null,
				'game_ref'              => isset($this->request['args']['game_ref']) ? $this->request['args']['game_ref'] : null,
				'game_ver'              => isset($this->request['args']['game_ver']) ? $this->request['args']['game_ver'] : null,
				'mode'                  => self::CREDIT,
				'round_id'              => isset($this->request['args']['round_id']) ? $this->request['args']['round_id'] : null,
				'token'                 => isset($this->request['args']['token']) ? $this->request['args']['token'] : null,
				'methodname'            => isset($this->request['methodname']) ? $this->request['methodname'] : null,
				'method'            	=> $type,
				'mirror_id'             => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null,
				'offline'             	=> isset($this->request['args']['offline']) ? $this->request['args']['offline'] : null,
				'rollback_action_id'    => isset($this->request['args']['rollback_action_id']) ? $this->request['args']['rollback_action_id'] : null,
				'external_uniqueid'     => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null,
				'player_id'             => $playerId,
				'before_balance'        => $before_balance,
				'game_username'			=> $gameUsername,
			];
			//pre-compute, update later if not match
			$params['bet_amount']       = 0;
			$params['payout_amount']    = isset($related_bet['bet_amount']) ? $related_bet['bet_amount'] : null;
			$params['valid_amount']    	= 0;
			$params['after_balance'] 	= $before_balance + $params['payout_amount']; 	//pre-compute, update later if not match
			$params['result_amount'] 	= $params['payout_amount'] - $params['bet_amount'];
			$params['external_uniqueid'] = $params['methodname'].'-'.$params['action_id'];
			$params['related_bet_external_uniqueid'] = $related_bet_external_uniqueid;
 
			if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
			}
			if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
				$this->wallet_model->setGameProviderBetAmount($params['bet_amount']);
			}

			if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
				$this->wallet_model->setGameProviderPayoutAmount($params['payout_amount']);
			}

			if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
				$this->wallet_model->setGameProviderRoundId($params['round_id']);
			}
			if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
				$this->wallet_model->setGameProviderIsEndRound(true);
			}
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && $related_bet) {
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
			}
			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && $related_bet) {
				$relatedUniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($related_bet_external_uniqueid, self::DEBIT);
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
			}

			$before_balance = $after_balance = $current_player_balance;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function () use (
				$player,
				$params,
				&$insufficient_balance,
				&$before_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$extra
			) {
				list($trans_success, $before_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->handleDebitCreditAmount($params, $before_balance, $after_balance, $extra);
				$this->utils->debug_log(
					"BFGAMES_SEAMLESS_SERVICE_API @deposit lockAndTransForPlayerBalance",
					'trans_success',
					$trans_success,
					'before_balance',
					$before_balance,
					'after_balance',
					$after_balance,
					'insufficient_balance',
					$insufficient_balance,
					'isAlreadyExists',
					$isAlreadyExists,
					'additionalResponse',
					$additionalResponse,
					'isTransactionAdded',
					$isTransactionAdded
				);
				return $trans_success;
			});
			$balance = $after_balance;

			if ($insufficient_balance) {
				$code = self::CODE_PLAYER_INSUFFICIENT_FUNDS_ERROR;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Insufficient balance", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($isAlreadyExists) {
				$code = self::CODE_TRANSACTION_ALREADY_PROCESSED_ERROR; #return success if transaction already exists as per API docs
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit - Transaction already exists", [$code, $this->message]);
				throw new Exception($code);
			}

			if ($trans_success == false) {
				$code = self::CODE_GENERAL_API_ERROR_TRIGGERING_ROLLBACK;
				$this->message = $this->setResponseMessage($code);
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @deposit  lock failed", [$code,$this->message]);
				throw new Exception($code);
			}

			$balance = $after_balance;

			$status = true;
		} catch (\Exception $e) {
			$code = $e->getMessage();
		}

		$data = [
			"methodname" => $type,
			"reflection" => [
				"id" => isset($this->request['mirror']['id']) ? $this->request['mirror']['id'] : null
			],
			"servicenumber" => 1,
			"servicename" => $this->game_api->agent_name,
			"result" => [
				"errorcode" => (int)$code,
				"transaction_id" => isset($this->request['args']['action_id']) ? $this->request['args']['action_id'] : null
			],
			"version" => "1.0",
			"type" => "jsonwsp/response"
		];

		if($this->enable_ui_message){
			$data['result']['ui_message'] = $this->generateMessage($code, $this->message);
		}
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status, $type, $this->request, $data, $code, $fields);
	}


	private function handleDebitCreditAmount($params, &$before_balance, &$after_balance, $extra = null)
	{
		$success = false;
		$insufficientBalance = false;
		$isAlreadyExists = false;
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];

		$mode = isset($params['mode']) ? $params['mode'] : null;
		$amount = isset($params['converted_amount']) ? $params['converted_amount'] : 0;


		if ($mode == self::DEBIT) {
			$insufficientBalance = $this->checkIfInsufficientBalance($before_balance, $amount);
			if ($insufficientBalance) {
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @handleDebitCreditAmount insufficient_balance: current_balance = $before_balance, balance to deduct = $amount");
				return [false, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
			}
		}

		// check if action id already exists
		$isAlreadyExistsTrans = $this->bfgames_seamless_wallet_transactions->isTransactionAlreadyExist($params['action_id'],$params['player_id'], $params['methodname']);

		if(!$isAlreadyExistsTrans && $this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
			$isAlreadyExistsTrans = $this->bfgames_seamless_wallet_transactions->isTransactionAlreadyExist($params['action_id'],$params['player_id'], $params['methodname'], $this->game_api->getTransactionsPreviousTable());
		}

		// check if round already ended
		if(isset($isAlreadyExistsTrans->round_end) && $isAlreadyExistsTrans->round_end){
			$additionalResponse = [
				'round_end' => true
			];

			return [false, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
		}

		if (isset($isAlreadyExistsTrans->id)) {
			$isAlreadyExists = true;
			$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @handleDebitCreditAmount transaction already exist");
			return [false, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
		}

		//check if round already exists, included in gp automation test case
		if($params['methodname'] == self::METHOD_WITHDRAW){
			$isAlreadyExists = $this->bfgames_seamless_wallet_transactions->isTransactionRoundAlreadyExists($params['round_id'],$params['player_id'], $params['methodname']);

			if(!$isAlreadyExists && $this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$isAlreadyExists = $this->bfgames_seamless_wallet_transactions->isTransactionRoundAlreadyExists($params['round_id'],$params['player_id'], $params['methodname'], $this->game_api->getTransactionsPreviousTable());
			}
	
			if ($isAlreadyExists) {
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @handleDebitCreditAmount transaction already exist");
				return [false, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
			}
		}


		$isTransactionAdded = $isAdded = $this->insertIgnoreTransactionRecord($params);

		if (!($isAdded)) {
			$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: @handleDebitCreditAmount failed to insert transaction");
			return [false, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
		}

		$response_code = $this->adjustWallet($mode, $params);

		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @handleDebitCreditAmount - adjustWallet response", $response_code);

		$success = $response_code['success'];
		if(!$success){
			return [false, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
		}
		$before_balance = $response_code['before_balance'];
		$after_balance	 = $this->game_api->gameAmountToDBTruncateNumber($response_code['after_balance']);

		$method = isset($params['methodname']) ? $params['methodname'] : null;
		$externalUniqueId = isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null;
		$relatedBetUniqueId = isset($params['related_bet_external_uniqueid']) ? $params['related_bet_external_uniqueid'] : null;

		if ($success) {
			$statusMapping = [
				self::METHOD_WITHDRAW => Game_logs::STATUS_PENDING,
				self::METHOD_DEPOSIT => Game_logs::STATUS_SETTLED,
				self::METHOD_ROLLBACK => Game_logs::STATUS_REFUND
			];
			$status = isset($statusMapping[$method]) ? $statusMapping[$method] : Game_logs::STATUS_PENDING;

			$updateParams = [
				'status' => $status,
			];
			if($params['methodname'] != self::METHOD_WITHDRAW){
				$whereParams = ['external_uniqueid' => $relatedBetUniqueId, 'game_platform_id' => $this->game_platform_id];
				$this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($this->game_api->getTransactionsTable(),$whereParams, $updateParams);
			}
			
			//update after balance if afterbalance not matched
			if($after_balance != $params['after_balance']){
				$updateParams['after_balance'] = $after_balance;
				$whereParams = ['external_uniqueid' => $externalUniqueId, 'game_platform_id' => $this->game_platform_id];
				$this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($this->game_api->getTransactionsTable(),$whereParams, $updateParams);
			}
		}

		return [true, $before_balance, $after_balance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded];
	}

	public function generateMessage($code, $text = null, $type=self::UI_MESSAGE_DEFAULT_TYPE)
	{
		// $finalType = $this->getValueOrDefault($type, self::UI_MESSAGE_TYPES, self::UI_MESSAGE_DEFAULT_TYPE);
		// $finalDisplay = $this->getValueOrDefault($display, self::UI_MESSAGE_DISPLAY_TYPES, self::UI_MESSAGE_DEFAULT_DISPLAY);
		if($code !== self::CODE_SUCCCESS){
			$type=self::UI_MESSAGE_TYPES['ERROR'];
		}

		$message = $this->setResponseMessage($code);

		return array(
			'title' => self::UI_MESSAGE_DEFAULT_TITLE,
			'text' => $text == null ? $message : $text,
			'type' => $type,
			'display' => self::UI_MESSAGE_DEFAULT_DISPLAY
		);
	}

	private function getPlayerDetails($rawGameUserName = null)
	{
		$playerStatus = $player = $gameUsername = $player_username = null;
		$token = isset($this->request['args']['token']) ? $this->request['args']['token'] : null;
		$table = $this->use_third_party_token ? 'external_common_tokens' : 'common_token';

		if ($rawGameUserName) {
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsername($rawGameUserName);
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: getPlayerDetails result from USERNAME:", [
				$playerStatus, $player, $gameUsername, $player_username
			]);
		} elseif (!empty($token)) {
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByToken($token, $table);
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: getPlayerDetails result from TOKEN:", [
				$playerStatus, $player, $gameUsername, $player_username
			]);
		}

		return [$playerStatus, $player, $gameUsername, $player_username];
	}

	private function validateRequest()
	{
		$success = true;
		$currency = $this->getCurrency();

		$token = isset($this->request['args']['token']) ? $this->request['args']['token'] : null;
		$callerId = isset($this->request['args']['caller_id']) ? $this->request['args']['caller_id'] : null;
		$callerPassword = isset($this->request['args']['caller_password']) ? $this->request['args']['caller_password'] : null;

		$data = [
			'token' => $token,
			'caller_id' => $callerId,
			'caller_password' => $callerPassword,
		];

		$code = self::CODE_SUCCCESS;
		$message = $this->setResponseMessage($code);
		if (!$this->isValidParams($this->request, $this->rules)) {
			$success = false;
			$code = self::CODE_GENERAL_API_ERROR;
			$message = $this->setResponseMessage($code, 'Invalid parameters');
			$this->setErrorLog($code, $message);
		}
		if ($currency && !$this->isCurrencyCodeValid($currency)) {
			$success = false;
			$code = self::CODE_CURRENCY_TRANSFER_ERROR;
			$message = $this->setResponseMessage($code, "Parameter error. Currency code invalid");
			$this->setErrorLog($code, $message);
		}

		if (!$this->isAccountValid($data)) {
			$success = false;
			$code = self::CODE_GENERAL_AUTHENTICATION_ERROR;
			$message = $this->setResponseMessage($code);
			$this->setErrorLog($code, $message);
		}
		return ['success' => $success, "code" => $code, "message" => $message];
	}

	private function getCurrency()
	{
		return isset($this->request['args']['currency']) ? strtoupper($this->request['args']['currency']) : null;
	}

	private function getCommonValidationRules($customRequiredFields = [])
	{
		return array_merge(['args' => 'required', 'methodname' => 'required', 'mirror' => 'required'], $customRequiredFields);
	}

	private function setErrorLog($message, $apiMethod = null)
	{
		if ($apiMethod === null) {
			$this->api_method;
		}

		$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API @($apiMethod) :" . " - " . $message);
	}
	private function setDebugLog($message, $apiMethod = null)
	{
		if ($apiMethod === null) {
			$this->api_method;
		}
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API @($apiMethod) :" . " - " . $message);
	}

	private function setResponseMessage($code, $forceMessage = false)
	{
		if ($forceMessage) {
			return $forceMessage;
		}
		foreach (self::RESPONSE_CODES as $key => $value) {
			if ($code == $key) {
				return self::RESPONSE_CODES[$key];
			}
		}
	}

	private function validateInitialRequest()
	{
		$success = true;
		$code = self::CODE_SUCCCESS;
		$message = $this->setResponseMessage($code);
		if (!$this->checkIfGamePlatformIdIsValid()) {
			$success = false;
			$code = self::CODE_GENERAL_API_ERROR;
			$message = $this->setResponseMessage($code, 'Invalid GamePlatformId');
			$this->setErrorLog($code, $message);
		}

		if (!$this->isRequestMethodValid(self::POST)) {
			$success = false;
			$code = self::CODE_GENERAL_API_ERROR;
			$message = $this->setResponseMessage($code, 'Request method not allowed');
			http_response_code(self::HTTP_CODE_405);
			$this->setErrorLog($code, $message);
		}

		return ['success' => $success, "code" => $code, "message" => $message];
	}

	private function validateInitialRequestAfterGameLoaded()
	{
		$success = true;
		$code = self::CODE_SUCCCESS;
		$message = $this->setResponseMessage($code);
		if (!$this->validateWhiteIP()) {
			$success = false;
			$code = self::CODE_REQUEST_CANNOT_SUCCEED;
			$message = $this->setResponseMessage($code, "IP not whitelisted");
			$this->setErrorLog($code, $message);
		}
		if (!$this->isGameUnderMaintenance()) {
			$success = false;
			$code = self::CODE_GENERAL_API_ERROR;
			$message = $this->setResponseMessage($code, "Game under maintenance");
			$this->setErrorLog($code, $message);
		}
		if (!$this->isAllowedApiMethod()) {
			$success = false;
			$code = self::CODE_INVALID_METHOD_NAME;
			$message = $this->setResponseMessage($code, "Invalid method name");
			$this->setErrorLog($code, $message);
		}

		return ['success' => $success, "code" => $code, "message" => $message];
	}
	#-----------------Validations--------------------------
	private function verifyToken($token, $extra = [])
	{
		$agentName = isset($extra['agentName']) ? $extra['agentName'] : null;
		$agentKey = isset($this->game_api->agent_key) ? $this->game_api->agent_key : null;
		$timestamp = isset($extra['timestamp']) ? $extra['timestamp'] : null;

		return $token == MD5($agentName . $agentKey . $timestamp);
	}

	private function checkIfGamePlatformIdIsValid()
	{
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


	public function isAccountValid($data)
	{
		return $this->game_api->caller_id === $data['caller_id']
			&& $this->game_api->caller_password === $data['caller_password'];
	}

	public function isCurrencyCodeValid($currency)
	{
		return strtoupper($this->game_api->currency) == strtoupper($currency);
	}

	private function checkIfInsufficientBalance($currentPlayerBalance, $amountToBeDeducted)
	{
		return $currentPlayerBalance <= abs($amountToBeDeducted);
	}

	private function isValidParams($request, $rules)
	{
		//validate params
		foreach ($rules as $key => $rule) {
			if ($rule == 'required' && !isset($request[$key])) {
				$this->utils->error_log("BFGAMES_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: " . $key, $request, $rules);
				return false;
			}
		}

		return true;
	}


	# ---------------Response-------------------------------
	//Function to return output and save response and request
	private function setOutput($code, $data = [], $status = true)
	{
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
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API (handleExternalResponse)", 'status', $status, 'type', $type, 'data', $data, 'response', $response, 'error_code', $error_code, 'fields', $fields);
		if (strpos($error_code, 'timed out') !== false) {
			$this->utils->error_log(__METHOD__ . " (handleExternalResponse) Connection timed out.", 'status', $status, 'type', $type, 'data', $data, 'response', $response, 'error_code', $error_code, 'fields', $fields);
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
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
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API save_response: ", $response);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$uniqueId = isset($data['args']['action_id']) ? $data['args']['action_id'] : null;
		if ($uniqueId) {
			$this->bfgames_seamless_wallet_transactions->updateResponseResultRelated($uniqueId, ["responseResultId" => $this->response_result_id, 'elapsedTime' => $cost]);
		}

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
		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API raw parsed:", $request_json);
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

	private function checkIfNegative($num)
	{
		return $num >= 0;
	}

	private function debug_log($key, $value)
	{
		$this->CI->utils->debug_log($key, $value);
	}


	public function getPlayerCompleteDetailsByToken($token, $model = "common_token")
	{

		$player = $this->$model->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API @getPlayerToken last_query: " . $this->CI->db->last_query());

		if (!$player) {
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerCompleteDetailsByUsername($username)
	{

		$player =  $this->bfgames_seamless_wallet_transactions->getPlayerCompleteDetailsByUsername($username, $this->game_api->getPlatformCode());

		if (!$player) {
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}
	public function getPlayerByPlayerId($playerId, $model = "common_token")
	{

		$player = $this->$model->getPlayerCompleteDetailsByPlayerId($playerId, $this->game_platform_id);

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

		$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: (getPlayerBalance) get_bal_req: ", $get_bal_req);
		if ($get_bal_req['success']) {
			return $get_bal_req['balance'];
		} else {
			return false;
		}
	}

	private function adjustWallet($action, $params)
	{
		$playerId = $params['player_id'];
		$before_balance = $after_balance = isset($params['before_balance']) ? $params['before_balance'] : 0;
		$response_code['before_balance'] = $response_code['after_balance'] = $this->game_api->dBtoGameAmount($before_balance);
		$response_code['success'] = true;
		$response_code['remote_wallet_status'] = null;
		$amount = isset($params['converted_amount']) ? $params['converted_amount'] : 0;
		$uniqueId = $params['unique_id'] = isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null;
		$game_ref = isset($params['game_ref']) ? $params['game_ref'] : null;
		if ($action == self::DEBIT) {
			if ($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($uniqueId, self::DEBIT);
				if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $game_ref);
				}

				$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API ADJUST_WALLET: AMOUNT', $amount);
				$this->utils->debug_log(
					"BFGAMES_SEAMLESS_SERVICE_API ADJUST_WALLET: params - mode ($action)",
					array("player_id" => $playerId, "game_platform_id" => $this->game_platform_id, "amount" => $amount, "after_balance" => $after_balance)
				);

				$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API params before debSubWallet", [
					'player_id' => $playerId,
					'game_platform_id' => $this->game_platform_id,
					'amount' => $amount,
					'after_balance' => $after_balance
				]);

				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $after_balance);

				$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API - deduct_balance result:', $deduct_balance);

				$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API.- balance after adjust_wallet:', $deduct_balance);
				if (!$deduct_balance) { #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->error_log('BFGAMES_SEAMLESS_SERVICE_API: failed to deduct subwallet');
				}

				if (method_exists($this->utils, 'isEnabledRemoteWalletClient')) {
					if ($this->utils->isEnabledRemoteWalletClient()) {
						$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
						$response_code['remote_wallet_status'] = $this->remote_wallet_status = $remoteErrorCode;
						$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @adjustWallet - remoteErrorCode: ", $remoteErrorCode);

						if($this->remote_wallet_status == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
							$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @adjustWallet - double unique id: treated as success, no balance movement", $remoteErrorCode);
						}
					}
				}

			}

			if (is_null($after_balance)) {
				$after_balance = $this->game_api->queryPlayerBalance($params['game_username'])['balance'];
			}
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		} elseif ($action == self::CREDIT) {
			if ($this->utils->compareResultFloat($amount, '=', 0)) {
				$uniqueIdOfSeamlessService = $this->formatUniqueidOfSeamlessService($uniqueId, self::CREDIT);

				if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $game_ref);
				}
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
			} elseif ($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueIdOfSeamlessService =  $this->game_platform_id . '-' . $uniqueId . '-' . self::CREDIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $game_ref);

				$this->utils->debug_log(
					"BFGAMES_SEAMLESS_SERVICE_API ADJUST_WALLET: params - mode ($action)",
					array("player_id" => $playerId, "game_platform_id" => $this->game_platform_id, "amount" => $amount, "after_balance" => $after_balance)
				);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);

				$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API - add_balance result:', $add_balance);
				if (!$add_balance) { #check if success, posssible balance is lock and response timeout on remote wallet
					$this->utils->debug_log('BFGAMES_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
				}

				if (method_exists($this->utils, 'isEnabledRemoteWalletClient')) {
					if ($this->utils->isEnabledRemoteWalletClient()) {
						$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
						$response_code['remote_wallet_status'] = $this->remote_wallet_status = $remoteErrorCode;
						$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @adjustWallet - remoteErrorCode: ", $remoteErrorCode);

						if($this->remote_wallet_status == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
							$this->utils->debug_log("BFGAMES_SEAMLESS_SERVICE_API: @adjustWallet - double unique id: treated as success, no balance movement", $remoteErrorCode);
						}
					}
				}
			}
			if (is_null($after_balance)) {
				$after_balance = $this->game_api->queryPlayerBalance($params['game_username'])['balance'];
			}
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}
		return $response_code;
	}

	private function formatUniqueidOfSeamlessService($uniqueId, $mode)
	{
		return $this->game_platform_id . '-' . $uniqueId . '-' . $mode;
	}

	public function insertIgnoreTransactionRecord($data)
	{
		$trans_record = $this->makeTransactionRecord($data);

		$tableName = $this->game_api->getTransactionsTable();
		$this->bfgames_seamless_wallet_transactions->setTableName($tableName);
		return $this->bfgames_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}


	public function makeTransactionRecord($raw_data)
	{
		$data = [
			'caller_id'             => isset($raw_data['caller_id']) ? $raw_data['caller_id'] : null,
			'caller_password'       => isset($raw_data['caller_password']) ? $raw_data['caller_password'] : null,
			'action_id'             => isset($raw_data['action_id']) ? $raw_data['action_id'] : null,
			'amount'                => isset($raw_data['amount']) ? abs($raw_data['amount']) : null,
			'converted_amount'      => isset($raw_data['converted_amount']) ? abs($raw_data['converted_amount']) : null,
			'currency'              => isset($raw_data['currency']) ? $raw_data['currency'] : null,
			'game_ref'              => isset($raw_data['game_ref']) ? $raw_data['game_ref'] : null,
			'game_ver'              => isset($raw_data['game_ver']) ? $raw_data['game_ver'] : null,
			'jackpot_contributions' => isset($raw_data['jackpot_contributions']) ? json_encode($raw_data['jackpot_contributions']) : null,
			'round_details' 		=> isset($raw_data['round_details']) ? json_encode($raw_data['round_details']) : null,
			'jackpot_winnings' 		=> isset($raw_data['jackpot_winnings']) ? json_encode($raw_data['jackpot_winnings']) : null,
			'round_id'              => isset($raw_data['round_id']) ? $raw_data['round_id'] : null,
			'token'                 => isset($raw_data['token']) ? $raw_data['token'] : null,
			'methodname'            => isset($raw_data['methodname']) ? $raw_data['methodname'] : null,
			'mirror_id'             => isset($raw_data['mirror_id']) ? $raw_data['mirror_id'] : null,
			'external_uniqueid'     => isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : (isset($raw_data['action_id']) ? $raw_data['action_id'] : null),
			'player_id'             => isset($raw_data['player_id']) ? $raw_data['player_id'] : null,
			'bet_amount'            => isset($raw_data['bet_amount']) ? $raw_data['bet_amount'] : null,
			'payout_amount'         => isset($raw_data['payout_amount']) ? $raw_data['payout_amount'] : null,
			'valid_amount'          => isset($raw_data['valid_amount']) ? $raw_data['valid_amount'] : null,
			'result_amount'         => isset($raw_data['result_amount']) ? $raw_data['result_amount'] : null,
			'game_username'         => isset($raw_data['game_username']) ? $raw_data['game_username'] : null,
			'rollback_action_id'    => isset($raw_data['rollback_action_id']) ? $raw_data['rollback_action_id'] : null,
			'offline'         		=> isset($raw_data['offline']) ? $raw_data['offline'] : null,


			'before_balance'        => isset($raw_data['before_balance']) ? $raw_data['before_balance'] : null,
			'after_balance'         => isset($raw_data['after_balance']) ? $raw_data['after_balance'] : null,
			'trans_type'            => isset($raw_data['methodname']) ? $raw_data['methodname'] : null,
			'raw_data'              => isset($this->request) ? json_encode($this->request) : null,
			'remote_wallet_status'  => null,
			'request_id'            => $this->utils->getRequestId(),
			'headers'               => isset($this->requestHeaders) ? json_encode($this->requestHeaders) : null,
			'full_url'              => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
			'remote_raw_data'       => isset($raw_data['remote_raw_data']) ? $raw_data['remote_raw_data'] : null,
			'win_amount'            => isset($raw_data['payout_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['payout_amount']) : null,
			'balance_adjustment_amount' => isset($raw_data['result_amount']) ? abs($raw_data['result_amount']) : null,
			'balance_adjustment_method' => isset($raw_data['mode']) ? $raw_data['mode'] : null,
			'game_platform_id'      => $this->game_platform_id,
			'elapsed_time'          => isset($raw_data['elapsedTime']) ? $raw_data['elapsedTime'] : null,
		];
	
		// Determine status based on transaction type

		switch ($data['trans_type']) {
			case self::METHOD_WITHDRAW:
				$data['status'] = Game_logs::STATUS_PENDING; 
				break;
			case self::METHOD_DEPOSIT:
				$data['status'] = Game_logs::STATUS_SETTLED;
				break;
			case self::METHOD_ROLLBACK:
				$data['status'] = Game_logs::STATUS_REFUND;
				break;
			default:
				$data['status'] = Game_logs::STATUS_SETTLED;
				break;
		}
		
		return $data;
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

	public function formatUniqueId($method, $uniqueId)
	{
		switch ($method) {
			case self::METHOD_WITHDRAW:
				return strtolower(self::METHOD_WITHDRAW) . '-' . $uniqueId;
			case self::METHOD_DEPOSIT:
				return strtolower(self::METHOD_DEPOSIT) . '-' . $uniqueId;
			case self::METHOD_ROLLBACK:
				return strtolower(self::METHOD_ROLLBACK) . '-' . $uniqueId;
		}
	}

	private function generateOfflineTokenSecretKey($gameUsername)
	{
		$args = isset($this->request['args']) ? $this->request['args'] : [];
		
		$callerId = isset($args['caller_id']) ? $args['caller_id'] : null;
		$roundId = isset($args['round_id']) ? $args['round_id'] : null;
		$actionId = isset($args['action_id']) ? $args['action_id'] : null;
		$amount = isset($args['amount']) ? (string)$args['amount'] : null;
		$playerId = $gameUsername;
		// $playerId = $this->gameUsername;
		$playerId = null;

		$data = $this->offline_secret_token . $callerId . $roundId . $actionId . $amount;
		$hash = hash('sha224', $data); // Generate SHA224 hash

		$token = $hash . '_' . $playerId;

		return $token;
	}

	private function saveFailedTransactions(){

	}
}///END OF FILE////////////