<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * {{url}}/dragoonsoft_seamless_service_api/api/wallet/balance
 * {{url}}/dragoonsoft_seamless_service_api/api/wallet/bet_n_pay
 * {{url}}/dragoonsoft_seamless_service_api/api/wallet/bet
 * {{url}}/dragoonsoft_seamless_service_api/api/wallet/payout
 * {{url}}/dragonosoft_seamless_service_api/api/wallet/cancel
 */
class Dragoonsoft_seamless_service_api extends BaseController
{

	const METHOD_BALANCE= 'balance';
	const METHOD_BET_AND_PAY = 'bet_n_pay';
	const METHOD_BET = 'bet';
	const METHOD_PAYOUT = 'payout';
	const METHOD_CANCEL= 'cancel';


	const ALLOWED_API_METHODS = [
		self::METHOD_BALANCE,
		self::METHOD_BET_AND_PAY,
		self::METHOD_BET,
		self::METHOD_PAYOUT,
		self::METHOD_CANCEL
	];


	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel'; 

	const SUCCESS = 1;

	# GP ERROR_CODES
	const CODE_UNKNOWN_ERROR = 0;
	const CODE_SUCCESS = 1;
	const CODE_MEMBER_DOES_NOT_EXIST = 2;
	const CODE_VERIFICATION_FAILED = 3;
	const CODE_TRANSACTION_DUPLICATED = 4;
	const CODE_BALANCE_INSUFFICIENT = 5;
	const CODE_TRANSACTION_DOES_NOT_EXIST = 6;

	const ERROR_IP_NOT_WHITELISTED = 401;

	
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
		self::ERROR_INVALID_REQUEST => 400,
		self::ERROR_INTERNAL_SERVER_ERROR => 500,
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
	private $balance_adjustment_type;
	private $extra=[];
	private $use_third_party_token=false;
	private $trans_records = [];
	private $code = null;

	private $remoteWallet = [
		'action_type' => 'bet',
		'round_id' => null,
		'is_end' => false,
		'bet_amount' => 0,
		'payout_amount' => 0,
		'external_game_id' => 0
	];

	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'dragoonsoft_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));

		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();

	}

	public function index($method = null)
	{
		$this->game_platform_id = DRAGOONSOFT_SEAMLESS_GAME_API;
		$this->method = $method;

		if ($this->initialize()) {
			return $this->selectMethod();			
		}else{
			$this->debug_log("Dragoonsoft seamless service:", "Failed to initialized, check for error_log");
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('Dragoonsoft seamless service: raw request');
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
        $this->CI->dragoonsoft_seamless_wallet_transactions->setTableName($tableName);  

		if(!$this->game_api){
			$this->utils->debug_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (initialize) ERROR lOAD: ", $this->game_platform_id);
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
				case 'balance':
					$this->balance();
					break;
				case 'bet_n_pay':
					$this->betAndPay();
					break;
				case 'bet':
					$this->bet();
					break;
				case 'payout':
					$this->payout();
					break;
				case 'cancel':
					$this->cancel();
					break;
				default:
					$this->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: Invalid API Method');
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
	
	

	public function balance(){
		$type = self::METHOD_BALANCE;
		$status = false;
		
		$hasError = 0;
		$errorId = 0;
		$errorDescription = '';
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'agent' => 'required',
			'account' => 'required',
			'token' => 'required',
			'game_id' => 'required',
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$code = self::CODE_VERIFICATION_FAILED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (balance)" . " - ". $message);
				throw new Exception($code);
			}
							
			if(!isset($this->request['agent'])){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (balance) -" . $message);
				throw new Exception($code);
			}

			
			// get player details
			$token = $this->request['token'];
			
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

			if(!$player){
				$code = self::CODE_MEMBER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (balance) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (balance) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			$code = self::SUCCESS;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;
		}

		$data = [
			'balance' => $balance,
			'status' => $code
		];
	

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function betAndPay(){
		$type = self::METHOD_BET_AND_PAY;
		$status = false;

		$code = self::SUCCESS;
		$message = '';
		$balance = 0;
		$token = null;
		$playerId = null;

		$rules = [
			'trans_id' => 'required',
			'agent' => 'required',
			'account' => 'required',
			'game_id' => 'required',
			'token' => 'required',
			'bet_amount' => 'required',
			'payout_amount' => 'required',
			'valid_amount' => 'required',
			'bet_id' => 'required',
		];		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$code = self::CODE_VERIFICATION_FAILED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet_n_pay)" . " - ". $message);
				throw new Exception($code);
			}	
			if(!isset($this->request['agent'])){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet_n_pay) -" . $message);
				throw new Exception($code);
			}

			
			// get player details
			$account = isset($this->request['account']) ? $this->request['account'] : null;
			$token = isset($this->request['token']) ? $this->request['token'] : null;
			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($account);
			}

			if(!$player){
				$code = self::CODE_MEMBER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet_n_pay) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet_n_pay) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}
			

			#check if transaction already exists
			$uniqueId = $this->request['trans_id'] ?  $this->request['trans_id']  : null;
			$formattedUniqueId = $this->game_api->formatUniqueId($this->method, $uniqueId);
			$isTransactionAlreadyExists = $this->dragoonsoft_seamless_wallet_transactions->isTransactionExist($formattedUniqueId);

			if($isTransactionAlreadyExists){
				// $code = self::CODE_TRANSACTION_DUPLICATED;
				$code = self::CODE_VERIFICATION_FAILED;
				$message = "Transaction already exists";
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet_n_pay) -" . $message);
				throw new Exception($code);
			}

			$request = $this->request;
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId, $request){
				#check if insufficient balance
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$betAmount = isset($request['bet_amount']) ? $request['bet_amount'] : 0;
				$payoutAmount = isset($request['payout_amount']) ? $request['payout_amount'] : 0;
				$resultAmount = $payoutAmount - $betAmount;
				$params['result_amount'] = $resultAmount;
				$params['bet_amount']	 = $betAmount;
				$params['payout_amount']	 = $payoutAmount;

				if(!$this->checkIfInsufficientBalance($balance,$betAmount)){
					$this->code = $code = self::CODE_BALANCE_INSUFFICIENT;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet_n_pay) -" . $message);
					throw new Exception($code);
				}	
				#determine the mode

				if($betAmount >= $payoutAmount){
					$mode = self::DEBIT;
				}elseif($betAmount <= $payoutAmount){
					$mode = self::CREDIT;
				}
				
				$params['mode'] = $mode;
				$params['current_player_balance'] = $balance;
				$betId = isset($request['bet_id']) ? $request['bet_id'] : null;
				$gameId = isset($request['game_id']) ? $request['game_id'] : null;

				$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;
				$this->remoteWallet['is_end'] = true;
				$this->remoteWallet['round_id'] = $betId;
				$this->remoteWallet['bet_amount'] = $betAmount;
				$this->remoteWallet['payout_amount'] = $payoutAmount;
				$this->remoteWallet['external_game_id'] = $gameId;
				$this->balance_adjustment_type = $params['mode'];

				$trans_success = $this->debitCreditAmountToWallet($params);
				return $trans_success;
			});

			$code = !is_null($this->code) ? $this->code : $code;
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$status = false;
		}

		$data = [
			'trans_id' => isset($this->request['trans_id']) ? $this->request['trans_id'] : null,
			'balance' => $balance,
			'status' => intval($code)
		];
		
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	
	public function bet(){
		$type = self::METHOD_BET;
		$status = false;

		$code = self::SUCCESS;
		$message = '';
		$balance = 0;
		$token = null;
		$playerId = null;

		$rules = [
			'trans_id' => 'required',
			'agent' => 'required',
			'account' => 'required',
			'game_id' => 'required',
			'token' => 'required',
			'amount' => 'required',
		];		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$code = self::CODE_VERIFICATION_FAILED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet)" . " - ". $message);
				throw new Exception($code);
			}	
			if(!isset($this->request['agent'])){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet) -" . $message);
				throw new Exception($code);
			}

			
			// get player details
			$account = isset($this->request['account']) ? $this->request['account'] : null;
			$token = isset($this->request['token']) ? $this->request['token'] : null;
			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($account);
			}
			


			if(!$player){
				$code = self::CODE_MEMBER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet) -" . $message);
				throw new Exception($code);
			}

			if(!$playerStatus){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}
			

			#check if transaction already exists
			$uniqueId = $this->request['trans_id'] ?  $this->request['trans_id']  : null;
			$formattedUniqueId = $this->game_api->formatUniqueId($this->method, $uniqueId);
			$isTransactionAlreadyExists = $this->dragoonsoft_seamless_wallet_transactions->isTransactionExist($formattedUniqueId);

			if($isTransactionAlreadyExists){
				$code = self::CODE_TRANSACTION_DUPLICATED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet) -" . $message);
				throw new Exception($code);
			}

			
			$request = $this->request;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId, $request){
				#check if insufficient balance
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$betAmount = isset($request['amount']) ? $request['amount'] : 0;
				if($betAmount < 0){
					#don't allow negative bet if single bet transaction
					$this->code = $code = self::CODE_UNKNOWN_ERROR;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet) -" . "bet amount cannot be negative");
					throw new Exception($code);
				}
				$payoutAmount =  0;
				$resultAmount = $payoutAmount - $betAmount;
				$params['result_amount'] 	= $resultAmount;
				$params['bet_amount']		= $betAmount;
				$params['payout_amount']	= $payoutAmount;
				if(!$this->checkIfInsufficientBalance($balance,$betAmount)){
					$this->code = $code = self::CODE_BALANCE_INSUFFICIENT;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (bet) -" . $message);
					throw new Exception($code);
				}	
				#determine the mode
				$mode = self::DEBIT;

				$params['mode'] = $mode;
				$params['current_player_balance'] = $balance;

				$transId = isset($request['trans_id']) ? $request['trans_id'] : null;
				$gameId = isset($request['game_id']) ? $request['game_id'] : null;

				$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
				$this->remoteWallet['is_end'] = false;
				$this->remoteWallet['round_id'] = $transId;
				$this->remoteWallet['bet_amount'] = $betAmount;
				$this->remoteWallet['payout_amount'] = $payoutAmount;
				$this->remoteWallet['external_game_id'] = $gameId;
				$this->balance_adjustment_type = $params['mode'];
	
				$trans_success = $this->debitCreditAmountToWallet($params);
				return $trans_success;
			});
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$code = $code = !is_null($this->code) ? $this->code : $code;
			$status = true;
		}catch(Exception $error){
			$status = false;
	
		}

		$data = [
			'trans_id' => isset($this->request['trans_id']) ? $this->request['trans_id'] : null,
			'balance' => $balance,
			'status' => intval($code)
		];
		
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function payout(){
		$type = self::METHOD_PAYOUT;
		$status = false;

		$code = self::SUCCESS;
		$message = '';
		$balance = 0;
		$token = null;
		$playerId = null;

		$rules = [
			'trans_id' => 'required',
			'agent' => 'required',
			'account' => 'required',
			'game_id' => 'required',
			'token' => 'required',
			'amount' => 'required',
			'bet_id' => 'required',
		];		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$code = self::CODE_VERIFICATION_FAILED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout)" . " - ". $message);
				throw new Exception($code);
			}	
			if(!isset($this->request['agent'])){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout) -" . $message);
				throw new Exception($code);
			}

			
			// get player details
			$token = $this->request['token'];
			$account = $this->request['account'];

			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($account);
			}
			

			if(!$player){
				$code = self::CODE_MEMBER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}
			

			#check if transaction already exists
			$uniqueId = $this->request['trans_id'] ?  $this->request['trans_id']  : null;
			$formattedUniqueId = $this->game_api->formatUniqueId($this->method, $uniqueId);
			$isTransactionAlreadyExists = $this->dragoonsoft_seamless_wallet_transactions->isTransactionExist($formattedUniqueId);

			if($isTransactionAlreadyExists){
				$code = self::CODE_TRANSACTION_DUPLICATED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout) -" . $message);
				throw new Exception($code);
			}

			#check if still can cancel
			$betFormattedUniqueId = $this->game_api->formatUniqueId(self::METHOD_BET, $uniqueId);
			$existingBetTrans = $this->dragoonsoft_seamless_wallet_transactions->getRelatedBetExistingTransaction($betFormattedUniqueId);
			if (!$existingBetTrans || (!empty($existingBetTrans) && in_array($existingBetTrans->status, [Game_logs::STATUS_SETTLED, Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED]))) {
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code) . 'failed to payout transaction, win has been processed or bet already cancelled';
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout) -" . $message);
				throw new Exception($code);
			}


			$request = $this->request;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId, $request){
				#check if insufficient balance
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$payoutAmount = isset($request['amount']) ? $request['amount'] : 0;

				if($payoutAmount < 0){
					#don't allow negative payout if single payout transaction
					$this->code = $code = self::CODE_UNKNOWN_ERROR;
					$message = $this->getResponseErrorMessage($code) . "payout amount cannot be negative";
					$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (payout) - " . $message);
					throw new Exception($code);
				}
				$betAmount =  0;
				$resultAmount = $payoutAmount - $betAmount;
				$params['result_amount'] 	= $resultAmount;
				$params['bet_amount']		= $betAmount;
				$params['payout_amount']	= $payoutAmount;
		
				#determine the mode
				$mode = self::CREDIT;

				$params['mode'] = $mode;
				$params['current_player_balance'] = $balance;

				$transId = isset($request['trans_id']) ? $request['trans_id'] : null;
				$gameId = isset($request['game_id']) ? $request['game_id'] : null;

				$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
				$this->remoteWallet['is_end'] = true;
				$this->remoteWallet['round_id'] = $transId;
				$this->remoteWallet['bet_amount'] = $betAmount;
				$this->remoteWallet['payout_amount'] = $payoutAmount;
				$this->remoteWallet['external_game_id'] = $gameId;
				$this->balance_adjustment_type = $params['mode'];
				$trans_success = $this->debitCreditAmountToWallet($params);
				return $trans_success;
			});
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$code = !is_null($this->code) ? $this->code : $code;
			$status = true;
		}catch(Exception $error){
			$status = false;
	
		}

		$data = [
			'trans_id' => isset($this->request['trans_id']) ? $this->request['trans_id'] : null,
			'balance' => $balance,
			'status' => intval($code)
		];
		
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function cancel(){
		$type = self::METHOD_CANCEL;
		$status = false;

		$code = self::SUCCESS;
		$message = '';
		$balance = 0;
		$token = null;
		$playerId = null;

		$rules = [
			'trans_id' => 'required',
			'agent' => 'required',
			'account' => 'required',
			'game_id' => 'required',
			'token' => 'required',
			'amount' => 'required',
		];		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$code = self::CODE_VERIFICATION_FAILED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (cancel)" . " - ". $message);
				throw new Exception($code);
			}	
			if(!isset($this->request['agent'])){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (cancel) -" . $message);
				throw new Exception($code);
			}

			
			// get player details
			$token = $this->request['token'];
			$account = $this->request['account'];

			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($account);
			}
			

			if(!$player){
				$code = self::CODE_MEMBER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (cancel) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (cancel) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}
			

			#check if transaction already exists
			$uniqueId = $this->request['trans_id'] ?  $this->request['trans_id']  : null;
			$formattedUniqueId = $this->game_api->formatUniqueId($this->method, $uniqueId);
			$isTransactionAlreadyExists = $this->dragoonsoft_seamless_wallet_transactions->isTransactionExist($formattedUniqueId);

			if($isTransactionAlreadyExists){
				$code = self::CODE_TRANSACTION_DUPLICATED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (cancel) -" . $message);
				throw new Exception($code);
			}

			#check if still can cancel
			$betFormattedUniqueId = $this->game_api->formatUniqueId(self::METHOD_BET, $uniqueId);
			$existingBetTrans = $this->dragoonsoft_seamless_wallet_transactions->getRelatedBetExistingTransaction($betFormattedUniqueId);

			if (!$existingBetTrans || (!empty($existingBetTrans) && in_array($existingBetTrans->status, [Game_logs::STATUS_SETTLED, Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED]))) {
				$code = self::CODE_UNKNOWN_ERROR;
				$message = $this->getResponseErrorMessage($code) . 'failed to cancel transaction';
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (cancel) -" . $message);
				throw new Exception($code);
			}


			#check if insufficient balance
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$cancelAmount = isset($this->request['amount']) ? $this->request['amount'] : 0;
			$betAmount =  0;
			$payoutAmount =  0;
			$resultAmount = $cancelAmount;
			$params['result_amount'] 	= $resultAmount;
			$params['bet_amount']		= $betAmount;
			$params['payout_amount']	= $payoutAmount;
	
			#determine the mode
			$mode = self::CANCEL;

			$params['mode'] = $mode;
			$params['current_player_balance'] = $balance;

			$transId = isset($this->request['trans_id']) ? $this->request['trans_id'] : null;
			$gameId = isset($this->request['game_id']) ? $this->request['game_id'] : null;

			$this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
			$this->remoteWallet['is_end'] = true;
			$this->remoteWallet['round_id'] = $transId;
			$this->remoteWallet['bet_amount'] = $betAmount;
			$this->remoteWallet['payout_amount'] = $payoutAmount;
			$this->remoteWallet['external_game_id'] = $gameId;
			$this->balance_adjustment_type = $params['mode'];


			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){
				$trans_success = $this->debitCreditAmountToWallet($params);
				return $trans_success;
			});
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$status = false;
	
		}

		$data = [
			'trans_id' => isset($this->request['trans_id']) ? $this->request['trans_id'] : null,
			'balance' => $balance,
			'status' => intval($code)
		];
		
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}


	#-----------------Validations--------------------------
	public function checkIfGamePlatformIdIsValid(){
		$httpStatusCode = $this->getHttpStatusCode(self::ERROR_INTERNAL_SERVER_ERROR);
		if (empty($this->game_platform_id)) {
			$this->CI->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: Invalid game_platform_id");
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
			$this->utils->error_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: Request method not allowed');
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
			$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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
			$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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
			$this->utils->error_log("DRAGOONSOFT_SEAMLESS_GAME_API: " . $this->getResponseErrorMessage($code));
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
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
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
		$this->CI->utils->debug_log("dragoonsoft save_response_result: $currentDateTime",["status" => $status, "type" => $type,"data" =>  $data, "response" => $response, "http_status" => $httpStatusCode,"fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("dragoonsoft save_response: ",$response);

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
			
			case self::CODE_UNKNOWN_ERROR:
				$message = lang('Unknown Error');
				break;
			case self::CODE_MEMBER_DOES_NOT_EXIST:
				$message = lang('Member does not exist');
				break;
			case self::CODE_VERIFICATION_FAILED:
				$message = lang('Verification Failed');
				break;
			case self::CODE_TRANSACTION_DUPLICATED:
				$message = lang('Transaction duplicated');
				break;
			case self::CODE_BALANCE_INSUFFICIENT:
				$message = lang('Balance Insufficient');
				break;
			case self::CODE_TRANSACTION_DOES_NOT_EXIST:
				$message = lang('Transaction does not exist');
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
				$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("DRAGOONSOFT_SEAMLESS_SERVICE_API raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("DRAGOONSOFT_SEAMLESS_SERVICE_API raw parsed:", $request_json);
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

		$this->utils->debug_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function debitCreditAmountToWallet($params){
		$this->utils->debug_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (debitCreditAmount)", $params);

		$player_balance = $this->game_api->dBtoGameAmount($params['current_player_balance']);
		$before_balance = $this->game_api->dbToGameAmount($player_balance);
		$after_balance = $before_balance;
		
		$mode = $params['mode'];
		$flagrefunded = false;
		$response_code['success'] = true;
		$uniqueId = isset($this->request['trans_id']) ? $this->request['trans_id'] : null;
		$uniqueId = $this->game_api->formatUniqueId($this->method, $uniqueId);

		$isAdded = $this->insertIgnoreTransactionRecord($params, $before_balance, $after_balance, $flagrefunded);

		if($isAdded==false){
			$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			return false;
		}	

		$response_code = $this->adjustWallet($mode, $params);

		$this->CI->debug_log("DRAGOONSOFT_SEAMLESS_SERVICE_API - response_code: ", $response_code);
		if($response_code['success']){
			if($params['result_amount'] <> 0){
				$updatedAfterBalance = $this->dragoonsoft_seamless_wallet_transactions->updateAfterBalanceByTransactionId($uniqueId, $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update after balance');
				}
			}
			$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
			

			#update related bet transaction 
				
			if($this->method == self::METHOD_PAYOUT){
				$uniqueId = isset($this->request['trans_id']) ? $this->request['trans_id'] : null;
				$uniqueId = $this->game_api->formatUniqueId(self::METHOD_BET, $uniqueId);
				$updatedStatus = $this->dragoonsoft_seamless_wallet_transactions->updateBetTransactionStatus($uniqueId, ['status' => Game_logs::STATUS_SETTLED, 'type' => self::METHOD_BET]);
				if(!$updatedStatus){
					$this->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a payout request');
				}
			}

			if($this->method == self::METHOD_CANCEL){
				$uniqueId = isset($this->request['trans_id']) ? $this->request['trans_id'] : null;
				$uniqueId = $this->game_api->formatUniqueId(self::METHOD_BET, $uniqueId);
				$updatedStatus = $this->dragoonsoft_seamless_wallet_transactions->updateBetTransactionStatus($uniqueId, ['status' => Game_logs::STATUS_REFUND, 'type' => self::METHOD_BET]);
				if(!$updatedStatus){
					$this->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a cancel request');
				}
			}
		}
		
		return $response_code['success'];
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['operator_player_id'];

		$before_balance = $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
		$response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);


		$after_balance = $before_balance;
		$response_code['success'] = true;
		$amount = isset($params['result_amount']) ? $params['result_amount'] : 0;

		$params['unique_id'] = isset($params['transaction_id']) ? $params['transaction_id'] : null;
		$uniqueId = isset($this->request['trans_id']) ? $this->request['trans_id'] : null;
		

        if($action == self::DEBIT) {
            $amount = abs($amount);
			if($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueid =  $this->game_platform_id.'-'.$uniqueId.'-'. self::DEBIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				$this->CI->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
				$amount = $this->game_api->gameAmountToDB($amount);

				$this->CI->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API ADJUST_WALLET: AMOUNT', $amount);
				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
				$response_code['success'] = true;

				
				$this->CI->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API.- balance afer adjust_wallet:', $deduct_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: failed to deduct subwallet');
				}
            }
			$after_balance = $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {
			$amount = abs($amount);
			if($this->utils->compareResultFloat($amount, '=', 0)){
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::CREDIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				$this->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
			}elseif($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::CREDIT;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				$this->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
								
				$response_code['success'] = true;
				
				$this->CI->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}
			$after_balance = $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}	elseif ($action == self::CANCEL) {
			$amount = abs($amount);

			if($this->utils->compareResultFloat($amount, '>', 0)) {
				$uniqueid =  $this->game_platform_id.'-'.$params['unique_id'] .'-'. self::CANCEL;
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueid);	
				$this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
				$this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
				$this->CI->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
				$this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
				$this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);

				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
								
				$response_code['success'] = true;
				
				$this->CI->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: CANCEL ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('DRAGOONSOFT_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}
			$after_balance = $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
			if(is_null($after_balance)){
				$after_balance = $this->game_api->dBtoGameAmount($after_balance);
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);
		}
		return $response_code;

	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;

		$this->trans_record = $trans_record = $this->makeTransactionRecord($data);

		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->dragoonsoft_seamless_wallet_transactions->setTableName($tableName);  
		return $this->dragoonsoft_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}


	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['trans_id'] 			            	= isset($this->request['trans_id']) ? $this->request['trans_id'] : null;
		$data['agent'] 			            		= isset($this->request['agent']) ? $this->request['agent'] : null;
		$data['account'] 			                = isset($this->request['account']) ? $this->request['account'] : null;
		$data['game_id'] 			            	= isset($this->request['game_id']) ? $this->request['game_id'] : null;
		$data['owner_account'] 			            = isset($this->request['owner_account']) ? $this->request['owner_account'] : null;
		$data['token'] 			       				= isset($this->request['token']) ? $this->request['token'] : null;
		$data['amount'] 			   				= 	isset($this->request['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['amount']) : null;
		$data['bet_amount'] 			   			= isset($raw_data['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet_amount']) : null;
		$data['payout_amount'] 			   			= isset($raw_data['payout_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['payout_amount']) : null;
		$data['valid_amount'] 			   			= isset($this->request['valid_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['valid_amount']) : null;
		$data['result_amount'] 			   			= isset($raw_data['result_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['result_amount']) : null;
		$data['feature_buy'] 			    		= isset($this->request['feature_buy']) ? $this->request['feature_buy'] : null;
		$data['bet_id'] 			        		= isset($this->request['bet_id']) ? $this->request['bet_id'] : null;
		$data['player_id'] 			    			= isset($raw_data['operator_player_id']) ? $raw_data['operator_player_id'] : null;
		$data['currency'] 			    			= $this->game_api->currency;

		$data['external_uniqueid'] 			    	= $this->game_api->formatUniqueId($this->method, $data['trans_id']);
		$data['trans_type'] 						= $this->method;

		if($this->method === self::METHOD_BET_AND_PAY){
			$data['status'] 			    		= Game_Logs::STATUS_SETTLED;
		}elseif($this->method === self::METHOD_BET){
			$data['status'] 			    		= Game_Logs::STATUS_PENDING;
		}elseif($this->method === self::METHOD_PAYOUT){
			$data['status'] 			    		= Game_Logs::STATUS_SETTLED;
		}elseif($this->method === self::METHOD_CANCEL){
			$data['status'] 			    		= Game_Logs::STATUS_REFUND;
		}

		$data['after_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 					= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;
		$data['raw_data']							= isset($this->request) ? json_encode($this->request) : null;
		$dat['bet_amount']							= $data['bet_amount'];
		$data['win_amount']							= $raw_data['result_amount'];
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