<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Nextspin_seamless_service_api extends BaseController
{
    use Seamless_service_api_module;

	const METHOD_AUTHORIZE = 'authorize';
	const METHOD_GET_BALANCE = 'getBalance';
	const METHOD_TRANSFER = 'transfer';


	const ALLOWED_API_METHODS = [
		self::METHOD_AUTHORIZE,
		self::METHOD_GET_BALANCE,
		self::METHOD_TRANSFER
	];

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel';
	const REFUND = 'refund';

	const TRANSFER_TYPE_BET = 1;
	const TRANSFER_TYPE_CANCEL = 2;
	const TRANSFER_TYPE_PAYOUT = 4;
	const JACKPOT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER = 6;
	const JACKPOT_PAYOUT_BY_MERCHANT = 7;
	const JACKPOT_PAYOUT_BY_GAME_PROVIDER = 8;
	const TOURNAMENT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER = 9;
	const TOURNAMENT_PAYOUT_BY_MERCHANT = 10;
	const TOURNAMENT_PAYOUT_BY_GAME_PROVIDER = 11;
	const RED_PACKET_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER = 12;
	const RED_PACKET_PAYOUT_BY_MERCHANT = 13;
	const RED_PACKET_PAYOUT_BY_GAME_PROVIDER = 14;
	
	const PAYOUT_TRANSFER_TYPES= [
		self::TRANSFER_TYPE_PAYOUT,
		self::JACKPOT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER,
		self::JACKPOT_PAYOUT_BY_MERCHANT,
		self::JACKPOT_PAYOUT_BY_GAME_PROVIDER,
		self::TOURNAMENT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER,
		self::TOURNAMENT_PAYOUT_BY_MERCHANT,
		self::TOURNAMENT_PAYOUT_BY_GAME_PROVIDER,
		self::RED_PACKET_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER,
		self::RED_PACKET_PAYOUT_BY_MERCHANT,
		self::RED_PACKET_PAYOUT_BY_GAME_PROVIDER,
	];
	#Transfer type Desc:
	/*
		1 = Place bet
		2 = Cancel Bet
		4 = Payout
		6 = Jackpot payout by merchant & game provider
		7 = Jackpot payout by merchant (pending release)
		8 = Jackpot payout by game provider (pending release)
		9 = Tournament payout by merchant & game provider (pending release)
		10 = Tournament payout by merchant (pending release)
		11 = Tournament payout by game provider (pending release)
		12 = Red packet payout by merchant & game provider (pending release)
		13 = Red packet payout by merchant (pending release)
		14 = Red packet payout by game provider (pending release)
	*/
	const SUCCESS = 0;

	const ERROR_IP_NOT_WHITELISTED = 401;

	#GP ERROR CODES
	const SYSTEM_ERROR 					= 1;
	const INVALID_REQUEST 				= 2;
	const SERVICE_INACCESSIBLE 			= 3;
	const REQUEST_TIMEOUT 				= 100;
	const CALL_LIMITED 					= 101;
	const REQUEST_FORBIDDEN 			= 104;
	const MISSING_PARAMETERS 			= 105;
	const INVALID_PARAMETERS 			= 106;
	const DUPLICATE_SERIAL_NO			= 107;
	const MERCHANT_KEY_ERROR 			= 108;
	const TRANSACTION_ALREADY_EXISTS 	= 109;
	const RECORD_ID_NOT_FOUND 			= 110;
	const MERCHANT_NOT_FOUND			= 10113;
	const API_CALL_LIMITED 				= 112;
	const INVALID_ACCT_ID 				= 113;
	const ACCT_NOT_FOUND 				= 50100;
	const ACCT_INACTIVE 				= 50101;

	const ACCT_LOCKED 					= 50102;
	const ACCT_SUSPEND 					= 50103;
	const TOKEN_VALIDATION_FAILED 		= 50104;
	const INSUFFICIENT_BALANCE 			= 50110;
	const EXCEED_MAX_AMOUNT 			= 50111;
	const CURRENCY_INVALID 				= 50112;
	const AMOUNT_INVALID 				= 50113;
	const PASSWORD_INVALID 				= 10104;
	const BET_SETTING_INCOMPLETE 		= 30003;
	const ACCT_STATUS_INACTIVE			= 10105;
	const ACCT_LOCKED_2 				= 10110;
	const ACCT_SUSPEND_2 				= 10111;
	const BET_INSUFFICIENT_BALANCE 		= 11101;
	const BET_DRAW_STOP_BET 			= 11102;
	const BET_TYPE_NOT_OPEN 			= 11103;
	const BET_INFO_INCOMPLETE 			= 11104;
	const BET_ACCT_INFO_INCOMPLETE 		= 11105;
	const BET_REQUEST_INVALID	 		= 11108;
	const BET_REQUEST_INVALID_MAX 		= 1110801;
	const BET_REQUEST_INVALID_MIN 		= 1110802;
	const BET_REQUEST_INVALID_TOTALBET 	= 1110803;
	const GAME_CURRENCY_NOT_ACTIVE 		= 50200;



	const INVALID_GAME_CODE = -3;
	const SWITCH_KEY_AND_VALUE_AND_RETURN_THE_OUTPUT = -4;

	const ERROR_CODES = [
		self::SYSTEM_ERROR,
		self::INVALID_REQUEST,
		self::SERVICE_INACCESSIBLE,
		self::REQUEST_TIMEOUT,
		self::CALL_LIMITED,
		self::REQUEST_FORBIDDEN,
		self::MISSING_PARAMETERS,
		self::INVALID_PARAMETERS,
		self::DUPLICATE_SERIAL_NO,
		self::MERCHANT_KEY_ERROR,
		self::RECORD_ID_NOT_FOUND,
		self::MERCHANT_NOT_FOUND,
		self::API_CALL_LIMITED,
		self::INVALID_ACCT_ID,
		self::ACCT_NOT_FOUND,
		self::ACCT_INACTIVE,
		self::ACCT_LOCKED,
		self::ACCT_SUSPEND,
		self::TOKEN_VALIDATION_FAILED,
		self::INSUFFICIENT_BALANCE,
		self::EXCEED_MAX_AMOUNT,
		self::CURRENCY_INVALID,
		self::AMOUNT_INVALID,
		self::PASSWORD_INVALID,
		self::BET_SETTING_INCOMPLETE,
		self::ACCT_SUSPEND,
		self::ACCT_STATUS_INACTIVE,
		self::ACCT_LOCKED_2,
		self::ACCT_SUSPEND_2,
		self::BET_INSUFFICIENT_BALANCE,
		self::BET_DRAW_STOP_BET,
		self::BET_TYPE_NOT_OPEN,
		self::BET_INFO_INCOMPLETE,
		self::BET_ACCT_INFO_INCOMPLETE,
		self::BET_REQUEST_INVALID,
		self::BET_REQUEST_INVALID_MAX,
		self::BET_REQUEST_INVALID_MIN,
		self::BET_REQUEST_INVALID_TOTALBET,
		self::GAME_CURRENCY_NOT_ACTIVE
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

    const TRANS_TYPE_PLACEBET = 'placebet';
    const TRANS_TYPE_PAYOUT = 'payout';
    const TRANS_TYPE_CANCEL = 'cancel';

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
	private $result = [];
    protected $seamless_service_related_unique_id = null;
    protected $seamless_service_related_action = null;

	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'game_logs', 'nextspin_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));

		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();

		$this->game_platform_id = NEXTSPIN_SEAMLESS_GAME_API;
		$this->method = isset($this->requestHeaders['Api']) ? $this->requestHeaders['Api'] : null;
	}

	public function index()
	{
		if ($this->initialize()) {
			return $this->selectMethod();
		} else {
			$this->debug_log("NEXTSPIN_SEAMLESS_SERVICE_API", "Failed to initialize inspect error logs");
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('NEXTSPIN_SEAMLESS_SERVICE_API: raw request');
		$validRequestMethod = self::POST;
		if (!$this->isRequestMethodValid($validRequestMethod)) {
			return false;
		};

		if (!$this->checkIfGamePlatformIdIsValid()) {
			$this->utils->error_log('NEXTSPIN_SEAMLESS_SERVICE_API: game_platform_id not valid');
			return false;
		};

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		$tableName = $this->game_api->getTransactionsTable();

		$this->CI->nextspin_seamless_wallet_transactions->setTableName($tableName);

		if (!$this->game_api) {
			$this->utils->debug_log("NEXTSPIN_SEAMLESS_SERVICE_API: (initialize) ERROR lOAD: ", $this->game_platform_id);
			http_response_code(500);
			return false;
		}

		$this->currency = $this->game_api->getCurrency();

		if (!$this->validateWhiteIP()) {
			return false;
		}

		if (!$this->isGameUnderMaintenance()) {
			return false;
		}


		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		return $this->hasError;
	}

	#-----------------METHODS------------------------------
	public function selectMethod()
	{
		if ($this->isAllowedApiMethod()) {
			switch ($this->method) {
				case 'authorize':
					$this->authorize();
					break;
				case 'getBalance':
					$this->getBalance();
					break;
				case 'transfer':
					$this->transfer();
					break;
				default:
					$this->utils->error_log('NEXTSPIN_SEAMLESS_SERVICE_API: Invalid API Method: ' . $this->method);
					http_response_code(422);
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

	public function authorize()
	{
		$type = self::METHOD_AUTHORIZE;
		$status = false;

		$responseCode = self::SUCCESS;
		$message = $this->getResponseErrorMessage(self::SUCCESS);
		$balance = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'acctId' => 'required',
			// 'token' => 'required',
			'merchantCode' => 'required',
			'serialNo' => 'required'
		];
		try {
			if (!$this->isValidParams($this->request, $rules)) {
				$responseCode = self::INVALID_PARAMETERS;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (authorize)" . " - " . $message);
				throw new Exception($message);
			}
			$token = isset($this->request['token']) ? $this->request['token'] : null;

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

			if ($player == null) {
				$responseCode = self::TOKEN_VALIDATION_FAILED;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (authorize)" . ' - ' . $message);
				throw new Exception($message);
			}

			if (!$playerStatus) {
				$responseCode = self::ACCT_INACTIVE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (authorize)" . __METHOD__ . ' - ' . $message);
				throw new Exception($message);
			}


			$playerId = $player->player_id;

			$responseCode = self::SUCCESS;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		} catch (Exception $error) {
			$status = false;
		}

		$data = [
			'acctInfo' => [
				'acctId' => $gameUsername,
				'balance' => $balance,
				'userName' => $gameUsername,
				'currency' => $this->currency,
				// 'siteId' => isset($this->request['serialNo']) ?  $this->request['serialNo'] : null
			],
			'merchantCode' => $this->game_api->merchantCode,
			'serialNo' => isset($this->request['serialNo']) ?  $this->request['serialNo'] : null,
			'code' => $responseCode,
			'msg' => $message
		];

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status, $type, $this->request, $data, $responseCode, $fields);
	}

	public function getBalance()
	{
		$type = self::METHOD_GET_BALANCE;
		$status = false;

		$responseCode = self::SUCCESS;
		$message = $this->getResponseErrorMessage(self::SUCCESS);
		$balance = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'acctId' => 'required',
			'merchantCode' => 'required',
			'serialNo' => 'required'
		];
		try {
			if (!$this->isValidParams($this->request, $rules)) {
				$responseCode = self::INVALID_PARAMETERS;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (getbalance)" . " - " . $message);
				throw new Exception($message);
			}
			$acctId = isset($this->request['acctId']) ? $this->request['acctId'] : null;

			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($acctId);

			if ($player == null) {
				$responseCode = self::ACCT_NOT_FOUND;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (getbalance)" . ' - ' . $message);
				throw new Exception($message);
			}

			if (!$playerStatus) {
				$responseCode = self::ACCT_INACTIVE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (getbalance)" . __METHOD__ . ' - ' . $message);
				throw new Exception($message);
			}


			$playerId = $player->player_id;

			$responseCode = self::SUCCESS;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		} catch (Exception $error) {
			$status = false;
		}

		$data = [
			'acctInfo' => [
				'acctId' => $gameUsername,
				'balance' => $balance,
				'userName' => $gameUsername,
				'currency' => strtoupper($this->currency),
			],
			'merchantCode' => $this->game_api->merchantCode,
			'serialNo' => isset($this->request['serialNo']) ?  $this->request['serialNo'] : null,
			'code' => $responseCode,
			'msg' => $message
		];

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status, $type, $this->request, $data, $responseCode, $fields);
	}

	public function transfer()
	{
		$methodType = self::METHOD_TRANSFER;
		$status = false;

		$responseCode = self::SUCCESS;
		$message = $this->getResponseErrorMessage(self::SUCCESS);
		$balance = 0;
		$gameUsername = null;
		$flagAsRefunded = false;
		$playerId = null;
		$playerUsername=null;

		$rules = [
			'transferId' => 'required',
			'acctId' => 'required',
			'currency' => 'required',
			'amount' => 'required',
			'type' => 'required',
			'channel' => 'required',
			'gameCode' => 'required'
		];

		try {
			if (!$this->isValidParams($this->request, $rules)) {
				$responseCode = self::INVALID_PARAMETERS;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer)" . " - " . $message);
				throw new Exception($responseCode);
			}

			if(!$this->isAccountValid($this->request['merchantCode'])){
				$responseCode = self::MERCHANT_KEY_ERROR;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer)" . " - " . $message);
				throw new Exception($responseCode);
			}
			if(!$this->isCurrencyCodeValid($this->request['currency'])){
				$responseCode = self::CURRENCY_INVALID;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer)" . " - " . $message);
				throw new Exception($responseCode);
			}

			$acctId = isset($this->request['acctId']) ? $this->request['acctId'] : null;

			list($playerStatus, $player, $gameUsername, $playerUsername) = $this->getPlayerByUsername($acctId);

			if ($player == null) {
				$responseCode = self::ACCT_NOT_FOUND;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer)" . ' - ' . $message);
				throw new Exception($responseCode);
			}

			if (!$playerStatus) {
				$responseCode = self::ACCT_INACTIVE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer)" . ' - ' . $message);
				throw new Exception($responseCode);
			}

			$playerId = $player->player_id;
			$params['player_id'] = $playerId;
			$params['player_username'] = $playerUsername;
			#Check if sufficient balance
			$balance = $this->getPlayerBalance($playerUsername, $playerId);
			$params['current_player_balance'] = $balance;

			#checker if trans already exists
			$transferId = isset($this->request['transferId']) ? $this->request['transferId'] : null;
			$referenceId = isset($this->request['referenceId']) ? $this->request['referenceId'] : null;
			$isTransactionAlreadyExists = $this->nextspin_seamless_wallet_transactions->isTransactionExist($transferId);
			if($isTransactionAlreadyExists){
				$responseCode = self::TRANSACTION_ALREADY_EXISTS;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
				throw new Exception($responseCode);
			}

			$type = isset($this->request['type']) ? $this->request['type'] : null;		

            if (in_array($type, [self::TRANSFER_TYPE_BET])) {
                if(!$this->checkIfInsufficientBalance($balance,$this->request['amount'])){
                    $responseCode = self::BET_INSUFFICIENT_BALANCE;
                    $message = $this->getResponseErrorMessage($responseCode);
                    $this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
                    throw new Exception($responseCode);
                }
            }

			$is_end_round = false;

			#checker if already refunded
			if($type == self::TRANSFER_TYPE_CANCEL){
				$flagAsRefunded =  $this->nextspin_seamless_wallet_transactions->checkIfIsAlreadyRefunded($referenceId);
				if($flagAsRefunded){
					$responseCode = self::TRANSACTION_ALREADY_EXISTS;
					$message = 'Transaction already refunded';
					$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
					throw new Exception($responseCode);
				}
			}

            if (in_array($type, [self::TRANSFER_TYPE_PAYOUT, self::TRANSFER_TYPE_CANCEL])) {
                // check if bet exists
                $bet_transaction = $this->ssa_get_transaction($this->nextspin_seamless_wallet_transactions->tableName, [
                    'player_id' => $playerId,
                    'transfer_type_as_text' => self::TRANS_TYPE_PLACEBET,
                    'transfer_id' => $this->request['referenceId'],
                ]);

                if (empty($bet_transaction)) {
                    $responseCode = self::RECORD_ID_NOT_FOUND;
                    $message = 'Bet not found.';
                    $this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
                    throw new Exception($responseCode);
                }

                if (!empty($bet_transaction['external_uniqueid'])) {
                    $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $bet_transaction['external_uniqueid']]);
                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                }

                if ($type == self::TRANSFER_TYPE_CANCEL) {
                    // check if payout exists
                    $is_payout_exists = $this->ssa_is_transaction_exists($this->nextspin_seamless_wallet_transactions->tableName, [
                        'player_id' => $playerId,
                        'transfer_type_as_text' => self::TRANS_TYPE_PAYOUT,
                        'reference_id' => $this->request['referenceId'],
                    ]);

                    if ($is_payout_exists) {
                        $responseCode = self::TRANSACTION_ALREADY_EXISTS;
                        $message = 'Transaction already processed by payout';
                        $this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
                        throw new Exception($responseCode);
                    }

                }

                if ($type == self::TRANSFER_TYPE_PAYOUT) {
                    // check if already canceled
                    $is_canceled = $this->ssa_is_transaction_exists($this->nextspin_seamless_wallet_transactions->tableName, [
                        'player_id' => $playerId,
                        'transfer_type_as_text' => self::TRANS_TYPE_CANCEL,
                        'reference_id' => $this->request['referenceId'],
                    ]);

                    if ($is_canceled) {
                        $responseCode = self::TRANSACTION_ALREADY_EXISTS;
                        $message = 'Transaction already refunded.';
                        $this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
                        throw new Exception($responseCode);
                    }
                }

				$is_end_round = true;
				$this->ssa_set_game_provider_is_end_round($is_end_round);
            }

			// checker if duplicated serial no
			$serialNo = isset($this->request['serialNo']) ? $this->request['serialNo'] : null;
			$isSerialNoAlreadyExists = $this->nextspin_seamless_wallet_transactions->isSerialNoAlreadyExist($serialNo);
			if($isSerialNoAlreadyExists){
				$responseCode = self::DUPLICATE_SERIAL_NO;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (transfer):" . $message);
				throw new Exception($responseCode);
			}

			$type = isset($this->request['type']) ? $this->request['type'] : null;
			$params['type'] = $type;
	

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){	
				$this->balance_adjustment_type = $this->getBalanceAdjustmentMethod($params['type']);
				$params['action'] = $this->balance_adjustment_type;
				$trans_success = $this->debitCreditAmountToWallet($params);
				return $trans_success;
			});
			
			$responseCode = self::SUCCESS;
			$status = $trans_success;
		} catch (Exception $error) {
			$status = false;
		}

		$data = [
			"transferId" => isset($this->request['transferId']) ? $this->request['transferId'] : null,
			'merchantCode' => $this->game_api->merchantCode,
			//"merchantTxId" => null, #optional
			"acctId" => isset($this->request['acctId']) ? $this->request['acctId'] : null,
			"balance" => $this->getPlayerBalance($playerUsername, $playerId),
			"msg" => $message,
			"code" => $responseCode,
			"serialNo" => isset($this->request['serialNo']) ? $this->request['serialNo'] : null
		];
		

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status, $methodType, $this->request, $data, $responseCode, $fields);
	}
	private function getRemoteActionType(){
		$type = isset($this->request['type']) ? $this->request['type'] : null;
		switch ($type) {
			case self::TRANSFER_TYPE_BET:
				return Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
				break;
			case self::TRANSFER_TYPE_PAYOUT:
				return  Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
				break;
			case self::TRANSFER_TYPE_CANCEL:
				return Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
				break;
			case in_array($type, self::PAYOUT_TRANSFER_TYPES):
				return  Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
			default:
				return Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
		}
	}
	private function getBalanceAdjustmentMethod($type){
		switch ($type) {
			case self::TRANSFER_TYPE_BET:
				return self::DEBIT;
				break;
			case self::TRANSFER_TYPE_PAYOUT:
				return self::CREDIT;
				break;
			case self::TRANSFER_TYPE_CANCEL:
				return self::CREDIT;
				break;
			case in_array($type, self::PAYOUT_TRANSFER_TYPES):
				return self::CREDIT;
			default:
				return self::DEBIT;
		}
	}
	

	#-----------------Validations--------------------------
	public function checkIfGamePlatformIdIsValid()
	{
		$httpStatusCode = $this->getHttpStatusCode(self::ERROR_INTERNAL_SERVER_ERROR);
		if (empty($this->game_platform_id)) {
			http_response_code($httpStatusCode);
			return false;
		}
		return true;
	}

	public function isRequestMethodValid($validMethod)
	{
		if ($this->requestMethod != $validMethod) {
			$this->utils->error_log('NEXTSPIN_SEAMLESS_SERVICE_API: Request method not allowed');
			http_response_code(405);
			return false;
		}
		return true;
	}

	public function validateWhiteIP()
	{
		$validated =  $this->game_api->validateWhiteIP();
		if (!$validated) {
			$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage(self::ERROR_IP_NOT_ALLOWED));
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
			$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage(self::ERROR_GAME_UNDER_MAINTENANCE));
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
			$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage(self::ERROR_API_METHOD_NOT_ALLOWED));
			$httpStatusCode = $this->getHttpStatusCode(self::ERROR_API_METHOD_NOT_ALLOWED);
			http_response_code($httpStatusCode);
			return false;
		}
		return true;
	}

	public function isAccountValid($merchant_code)
	{
		return $this->game_api->merchantCode == $merchant_code;
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
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: " . $key, $request, $rules);
				return false;
			}

			if ($rule == 'isNumeric' && isset($request[$key]) && !$this->isNumeric($request[$key])) {
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: " . $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if ($rule == 'nonNegative' && isset($request[$key]) && $request[$key] < 0) {
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: " . $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
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
			$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API ($this->game_platform_id): $errorDescription");
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
			"NEXTSPIN_SEAMLESS_SERVICE_API (handleExternalResponse)",
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
		$this->CI->utils->debug_log("IM save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("IM save_response: ", $response);

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

	public function getResponseErrorMessage($code)
	{
		$message = '';

		switch ($code) {
			case self::SUCCESS:
				$message = lang('Success');
				break;

			case self::SYSTEM_ERROR:
				$message = lang('Internal System Error');
				break;
			case self::INVALID_REQUEST:
				$message = lang("Request is not support by Game provider");
				break;
			case self::SERVICE_INACCESSIBLE:
				$message = lang("Game provider API down");
				break;
			case self::REQUEST_TIMEOUT:
				$message = lang("Request timeout");
				break;
			case self::CALL_LIMITED:
				$message = lang("Max call limit reached");
				break;
			case self::REQUEST_FORBIDDEN:
				$message = lang("Request forbidden");
				break;
			case self::MISSING_PARAMETERS:
				$message = lang("Missing Parameters");
				break;
			case self::INVALID_PARAMETERS:
				$message = lang("Invalid Parameters");
				break;
			case self::DUPLICATE_SERIAL_NO:
				$message = lang("Duplicated Serial No");
				break;
			case self::MERCHANT_KEY_ERROR:
				$message = lang("Invalid Merchant Key");
				break;
			case self::API_CALL_LIMITED:
				$message = lang("Exceed the limit of calling API");
				break;
			case self::INVALID_ACCT_ID:
				$message = lang("Acct ID incorrect format");
				break;
			case self::ACCT_NOT_FOUND:
				$message = lang("Account not found");
				break;
			case self::ACCT_INACTIVE:
				$message = lang("Account inactive");
				break;
			case self::ACCT_LOCKED:
				$message = lang("Account locked");
				break;
			case self::ACCT_SUSPEND:
				$message = lang("Account suspended");
				break;
			case self::TOKEN_VALIDATION_FAILED:
				$message = lang("Token validation failed");
				break;
			case self::INSUFFICIENT_BALANCE:
				$message = lang("Insufficient balance");
				break;
			case self::EXCEED_MAX_AMOUNT:
				$message = lang("Exceed max amount");
				break;
			case self::CURRENCY_INVALID:
				$message = lang("Invalid Currency");
				break;
			case self::AMOUNT_INVALID:
				$message = lang("Deposit/withdraw Amount must be greater than > 0");
				break;
			case self::PASSWORD_INVALID:
				$message = lang("Password not matched");
				break;
			case self::BET_SETTING_INCOMPLETE:
				$message = lang("Bet setting incomplete");
				break;
			case self::BET_INSUFFICIENT_BALANCE:
				$message = lang("Insufficient Balance");
				break;
			case self::TRANSACTION_ALREADY_EXISTS:
				$message = lang("Transfer Id is duplicated or not found");
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

	public function getTransferTypeDescription($code)
	{
		$transferType = '';
	
		switch ($code) {
			case self::TRANSFER_TYPE_BET:
				$transferType = lang('placebet');
				break;
			case self::TRANSFER_TYPE_CANCEL:
				$transferType = lang('cancel');
				break;
			case self::TRANSFER_TYPE_PAYOUT:
				$transferType = lang("payout");
				break;
			case self::JACKPOT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER:
				$transferType = lang("jackpot_payout_by_merchant_and_game_provider");
				break;
			case self::JACKPOT_PAYOUT_BY_MERCHANT:
				$transferType = lang("jackpot_payout_by_merchant");
				break;
			case self::JACKPOT_PAYOUT_BY_GAME_PROVIDER:
				$transferType = lang("jackpot_payout_by_game_provider");
				break;
			case self::TOURNAMENT_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER:
				$transferType = lang("tournament_payout_by_merchant_and_game_provider");
				break;
			case self::TOURNAMENT_PAYOUT_BY_MERCHANT:
				$transferType = lang("tournament_payout_by_merchant");
				break;
			case self::TOURNAMENT_PAYOUT_BY_GAME_PROVIDER:
				$transferType = lang("tournament_payout_by_game_provider");
				break;
			case self::RED_PACKET_PAYOUT_BY_MERCHANT_AND_GAME_PROVIDER:
				$transferType = lang("red_packet_payout_by_merchant_and_game_provider");
				break;
			case self::RED_PACKET_PAYOUT_BY_MERCHANT:
				$transferType = lang("red_packet_payout_by_merchant");
				break;
			case self::RED_PACKET_PAYOUT_BY_GAME_PROVIDER:
				$transferType = lang("red_packet_payout_by_game_provider");
				break;
			default:
				$this->utils->error_log("NEXTSPIN_SEAMLESS_SERVICE_API: (getTranferTypeDescription) error: ", $code);
				$transferType = $code;
				break;
		}
	
		return $transferType;
	}
	

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("NEXTSPIN_SEAMLESS_SERVICE_API raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("NEXTSPIN_SEAMLESS_SERVICE_API raw parsed:", $request_json);
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

		$this->utils->debug_log("NEXTSPIN_SEAMLESS_SERVICE_API: (getPlayerBalance) get_bal_req: ", $get_bal_req);
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
        $this->nextspin_seamless_wallet_transactions->setTableName($tableName);  
		return $this->nextspin_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}

	public function makeTransactionRecord($raw_data){
		$data = [];
		$status = Game_logs::STATUS_PENDING;
		$data['acct_id'] 			            	= isset($this->request['acctId']) ? $this->request['acctId'] : null;
		$data['transfer_id'] 			            = isset($this->request['transferId']) ? $this->request['transferId'] : null;
		$data['currency'] 			            	= isset($this->request['currency']) ? $this->request['currency'] : null;
		$data['amount'] 			            	= isset($this->request['amount']) ? $this->request['amount'] : 0;
		$data['type'] 			            		= isset($this->request['type']) ? $this->request['type'] : null;
		$data['transfer_type_as_text'] 			    = $this->getTransferTypeDescription($data['type']);
		$data['ticket_id'] 			            	= isset($this->request['ticketId']) ? $this->request['ticketId'] : null;
		$data['channel'] 			            	= isset($this->request['channel']) ? $this->request['channel'] : null;
		$data['game_code'] 			            	= isset($this->request['gameCode']) ? $this->request['gameCode'] : null;
		$data['merchant_code'] 			            = isset($this->request['merchantCode']) ? $this->request['merchantCode'] : null;
		$data['serial_no'] 			            	= isset($this->request['serialNo']) ? $this->request['serialNo'] : null;
		$data['reference_id'] 			            = isset($this->request['referenceId']) ? $this->request['referenceId'] : null;
		$data['special_game'] 			            = isset($this->request['specialGame']) ? json_encode($this->request['specialGame']) : null;
		$data['special_game_type'] 			        = isset($this->request['specialGame']['type']) ? $this->request['specialGame']['type'] : null;
		$data['special_game_count'] 			    = isset($this->request['specialGame']['count']) ? $this->request['specialGame']['count'] : null;
		$data['special_game_sequence'] 			    = isset($this->request['specialGame']['sequence']) ? $this->request['specialGame']['sequence'] : null;
		$data['raw_data'] 			    			= isset($this->request) ? json_encode($this->request) : null;
		$data['external_uniqueid'] 			    	= isset($this->request['transferId']) ? $this->request['transferId'] : null;

		$type = isset($this->request['type']) ? $this->request['type'] : null;

		if(in_array($type, self::PAYOUT_TRANSFER_TYPES)){
			$status = Game_logs::STATUS_SETTLED;
		}

		if($type == self::TRANSFER_TYPE_BET){
			$status = Game_logs::STATUS_SETTLED; // for bet-payout
		}
		if($type == self::TRANSFER_TYPE_CANCEL){
			$status = Game_logs::STATUS_REFUND;
		}

		$data['trans_status'] 			    		= $status;
		$data['trans_type'] 			    		= $this->getTransferTypeDescription($data['type']);
		$data['after_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 					= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;
		$data['player_id']							= isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
		$data['balance_adjustment_method']			= isset($raw_data['action']) ? $raw_data['action'] : null;
		
		$data['game_platform_id'] 	                = $this->game_platform_id;

		return $data;
	}
	
	public function debitCreditAmountToWallet($params){
		$this->utils->debug_log("NEXTSPIN_SEAMLESS_SERVICE_API: (debitCreditAmount)", $params);

		$player_balance = $this->game_api->dBtoGameAmount($params['current_player_balance']);
		$before_balance = $this->game_api->dbToGameAmount($player_balance);
		$after_balance = $before_balance;
		
		$uniqueId= isset($this->request['transferId']) ? $this->request['transferId'] : null;
		$amount = isset($this->request['amount']) ? $this->request['amount'] : 0;
		$mode = $params['action'];
		$flagrefunded = false;
		$response_code['success'] = false;
		$type = isset($this->request['type']) ? $this->request['type']: null;
		//insert transaction

		$isAdded = $this->insertIgnoreTransactionRecord($params, $before_balance, $after_balance, $flagrefunded);

		if($isAdded===false){
			$this->utils->error_log("SPINOMENAL SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			return false;
		}	

		$response_code = $this->adjustWallet($mode, $params);
		if($response_code['success']){
			$referenceId = isset($this->request['referenceId']) ? $this->request['referenceId'] : null;
			$ticketId = isset($this->request['ticketId']) ? $this->request['ticketId'] : null;


			if($amount <> 0){
				$updatedAfterBalance = $this->nextspin_seamless_wallet_transactions->updateAfterBalanceByTransactionId($uniqueId, $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update after balance');
				}	
			}
			#update bet related trans if payout
			if(in_array($type, self::PAYOUT_TRANSFER_TYPES)){
				if($referenceId != null){
					#get related bet transaction then update status to settle
					$updatedStatus = $this->nextspin_seamless_wallet_transactions->updateBetTransactionByReferenceId($referenceId, ['status' => Game_logs::STATUS_SETTLED, 'ticket_id' => $ticketId]);
					if(!$updatedStatus){
						$this->utils->debug_log('NEXTSPIN_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a payout request');
					}
				}
			}

			if($type == self::TRANSFER_TYPE_CANCEL){
				if($referenceId != null){
					#get related bet transaction then update status to settle
					$updatedStatus = $this->nextspin_seamless_wallet_transactions->updateBetTransactionByReferenceId($referenceId, ['status' => Game_logs::STATUS_REFUND, 'ticket_id' => $ticketId]);
					if(!$updatedStatus){
						$this->utils->debug_log('NEXTSPIN_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a payout request');
					}
				}
			}
			$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
		}
		
		return $response_code['success'];
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['player_id'];
		$type = $params['type'];
		$before_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
		$response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);
		$after_balance = $before_balance;
		$response_code['success'] = false;
		$amount = !empty($this->request['amount']) ? $this->game_api->gameAmountToDB(abs($this->request['amount'])) : 0;
		$params['unique_id'] = isset($this->request['transferId']) ? $this->request['transferId'] : 0;

        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        if($action == self::DEBIT) {
            $uniqueid =  $this->game_platform_id.'-'.$params['unique_id'];
            $uniqueIdOfSeamlessService=$uniqueid; 
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	
            $this->wallet_model->setGameProviderActionType($this->getRemoteActionType());

            $this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_SEVICE_API ADJUST_WALLET: AMOUNT', $amount);
            $deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
            $response_code['success'] = true;

            $this->CI->utils->debug_log('NEXTSPIN_SEAMLESS_SEVICE_API.- balance afer adjust_wallet:', $deduct_balance);
            if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                $response_code['success'] = false;
                $this->utils->debug_log('NEXTSPIN_SEAMLESS_SEVICE_API: failed to deduct subwallet');
            }
			$after_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {
            $increase = true;
            $uniqueid =  $this->game_platform_id.'-'.$params['unique_id'];
            $uniqueIdOfSeamlessService=$uniqueid; 
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService,  isset($this->request['gameCode']) ? $this->request['gameCode'] : null);	
            $this->wallet_model->setGameProviderActionType($this->getRemoteActionType());

            if ($amount == 0) {
                $increase = false;

                if ($this->ssa_enabled_remote_wallet() && in_array($type, [self::TRANSFER_TYPE_PAYOUT])) {
                    $this->utils->debug_log(__METHOD__, "{$this->game_api->seamless_game_api_name}: amount 0 call remote wallet", 'request_params', $this->ssa_request_params);
                    $this->ssa_increase_remote_wallet($playerId, $amount, $this->game_platform_id, $after_balance);
                }
            }

			if ($increase) {
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
								
				$response_code['success'] = true;
				
				$this->CI->utils->debug_log('NEXT_SPIN_SEAMLESS_WALLET_TRANSACTIONS ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('NEXT_SPIN_SEAMLESS_WALLET_TRANSACTIONS: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}
			$after_balance = $this->game_api->queryPlayerBalance($params['player_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}
		return $response_code;

	}


	private function formatExternalUniqueId($mode, $uniqueid)
	{
		switch ($mode) {
			case self::DEBIT:
				return self::DEBIT . '-' . $uniqueid;
				break;
			case self::CREDIT:
				return self::CREDIT . '-' . $uniqueid;
				break;
			case self::CANCEL:
				return self::CREDIT . '-' . $uniqueid;
				break;
			case self::REFUND:
				return self::REFUND . '-' . $uniqueid;
				break;
		}
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