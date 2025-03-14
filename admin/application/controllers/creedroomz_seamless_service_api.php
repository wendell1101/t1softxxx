<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * {{url}}/creedroomz_seamless_service_api/{game_platform_id}/Authentication
 * {{url}}/creedroomz_seamless_service_api/{game_platform_id}/GetBalance
 * {{url}}/creedroomz_seamless_service_api/{game_platform_id}/Withdraw
 * {{url}}/creedroomz_seamless_service_api/{game_platform_id}/Deposit
 * {{url}}/creedroomz_seamless_service_api/{game_platform_id}/WithdrawAndDeposit
 * {{url}}/creedroomz_seamless_service_api/{game_platform_id}/Rollback
 */
class Creedroomz_seamless_service_api extends BaseController
{

	const METHOD_AUTHENTICATION = 'Authentication';
	const METHOD_GET_BALANCE = 'GetBalance';
	const METHOD_WITHDRAW = 'Withdraw'; //bet
	const METHOD_DEPOSIT = 'Deposit'; //payout
	const METHOD_WITHDRAW_AND_DEPOSIT = 'WithdrawAndDeposit'; // bet-payout
	const METHOD_ROLLBACK = 'Rollback';
	const METHOD_TEST_GENERATE_PUBLIC_KEY= 'testGeneratePublicKey';


	const ALLOWED_API_METHODS = [
		self::METHOD_AUTHENTICATION,
		self::METHOD_GET_BALANCE,
		self::METHOD_WITHDRAW,
		self::METHOD_DEPOSIT,
		self::METHOD_WITHDRAW_AND_DEPOSIT,
		self::METHOD_ROLLBACK,
		self::METHOD_TEST_GENERATE_PUBLIC_KEY
	];


	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel'; 

	const SUCCESS = 0;

	# GP ERROR_CODES
	const CODE_SUCCCESS = 0;
	const CODE_WRONG_PLAYER_ID = 8;
	const CODE_NOT_ENOUGH_BALANCE = 21;
	const CODE_PLAYER_IS_BLOCKED = 29;
	const CODE_INVALID_TOKEN = 102;
	const CODE_TRANSACTION_NOT_FOUND = 107;
	const CODE_WRONG_TRANSACTION_AMOUNT = 109;
	const CODE_TRANSACTION_ALREADY_COMPLETED = 110;
	const CODE_DEPOSIT_TRANSACTION_ALREADY_RECEIVED = 111;
	const CODE_INVALID_BONUS_DEFINITION_ID = 125;
	const CODE_GENERAL_ERROR = 130;

	#GP TRANSACTION TYPES


	const TRANSACTION_TYPES = [
		-9 => 'Tip (Mainly for Live Dealer games)',
		-4 => 'Join a tournament',
		-2 => 'Create or sit behind the skill game table (Buy in)',
		-1 => 'Standard bet (Spins in slots, bets in virtual and live games, etc.)',
		0 => 'Standard DoBetWin (slots wins)',
		1 => 'Standard win (slots wins, wins in virtual and live games, etc)',
		2 => 'Win on skill game table',
		3 => 'Tournament Win',
		4 => 'Unregister from tournament',
		9 => 'CashbackBonus'
	];

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
	private $hasError = null;
	private $headers;
	private $balance_adjustment_type;
	private $extra=[];
	private $use_third_party_token=false;
	private $trans_records = [];
	private $playerId = null;
	private $checkPreviousMonth = false;
	private $code = null;
	private $request_json;
	private $flag_as_error = false;

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
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'creedroomz_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));
		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();
	}

	public function index($method = null)
	{
		$this->game_platform_id = $this->switchGamePlatformCode();
		$this->method = $method;
		
		if ($this->initialize()) {
			return $this->selectMethod();			
		}else{
			$this->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API:", "Failed to initialized, check for error_log");
		}
	}

	private function switchGamePlatformCode(){
		$token = isset($this->request['Token']) ? $this->request['Token'] : null;

		$extractedGamePlatformCode = explode('-',$token)[0];

		return $extractedGamePlatformCode;
	}

	public function initialize()
	{
		$this->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: raw request');
		$validRequestMethod = self::POST;

		if(!$this->isRequestMethodValid($validRequestMethod)){
			return false;
		};

		if(!$this->checkIfGamePlatformIdIsValid()){
			return false;
		};
		
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
		if(!$this->game_api){	
			$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (initialize) ERROR lOAD: ", $this->game_platform_id);
			http_response_code(500);
			return false;
		}

		if(date('j', $this->utils->getTimestampNow()) <= $this->game_api->allowed_days_to_check_previous_monthly_table) {
			$this->checkPreviousMonth = true;
		}

		$this->use_third_party_token = $this->game_api->use_third_party_token;

		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->creedroomz_seamless_wallet_transactions->setTableName($tableName);  

		

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
				case 'Authentication':
					$this->authentication();
					break;
				case 'GetBalance':
					$this->getBalance();
					break;
				case 'Withdraw':
					$this->withdraw();
					break;
				case 'Deposit':
					$this->deposit();
					break;
				case 'WithdrawAndDeposit':
					$this->withdrawAndDeposit();
					break;
				case 'Rollback':
					$this->rollback();
					break;
				case 'testGeneratePublicKey':
					$this->testGeneratePublicKey();
					break;
				default:
					$this->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: Invalid API Method');
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
	
	

	public function authentication(){
		$type = self::METHOD_AUTHENTICATION;
		$status = false;
		
		$hasError = false;
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;
		$player = null;
		$code = $message = null;
		$token = "";
		$operatorId = 0;

		$rules = [
			'OperatorId' => 'required',
			// 'Token' => 'required',
			// 'UserName' => 'required',
			// 'Password' => 'required',
			'PublicKey' => 'required',
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (authentication)" . " - ". $message);
				throw new Exception($code);
			}
			
			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			#validate public key
			$rawRequestBody = $this->request;
			#request body should be alphabetical
			ksort($rawRequestBody);

			unset($rawRequestBody['PublicKey']);

			$generatedPublicKey = $this->generatePublicKey(json_encode($rawRequestBody) . $this->game_api->public_key);

			$this->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API - Authentication publicKey and generatedPublicKey comparison: ",[
				"publicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);

			if(!$this->isValidPublicKey($publicKey, $generatedPublicKey)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid PublicKey";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (authentication) -" . $message);
				throw new Exception($code);
			}


			$operatorId = isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
			if(!$this->isAccountValid($operatorId)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid OperatorId";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (authentication) -" . $message);
				throw new Exception($code);
			}
			
			// get player details
			$token = isset($this->request['Token']) ? $this->request['Token'] : null;
			$userName = isset($this->request['UserName']) ? $this->request['UserName'] : null;
			$password = isset($this->request['Password']) ? $this->request['Password'] : null;
			$playerId = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;


			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}elseif (empty($token) && !empty($userName) && !empty($password)){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsernameAndPassword($userName, $password);
			}elseif($playerId){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByPlayerId($playerId);
			}

			if(!$player){
				$hasError =true;
				$code = self::CODE_INVALID_TOKEN;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (authentication) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$hasError =true;
				$code = self::CODE_PLAYER_IS_BLOCKED;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (authentication) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			$code = self::SUCCESS;
			$message = $this->getResponseErrorMessage($code);
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;
		}
		$type = $this->method;

		$data = [
			"OperatorId" 			=> 0,
			"Name" 					=> null,
			"NickName" 				=> null,
			"UserName"				=> null,
			"Token" 				=> "",
			"TotalBalance"			=> 0,
			"Gender" 				=> false,
			"Currency" 				=> null,
			"Country" 				=> null,
			"PlayerId" 				=> 0,
			"UserIP" 				=> null,
			"HasError" 				=> $hasError,
			"ErrorId" 				=> (int)$code,
			"ErrorDescription" 		=> $message
		];
		
		if(!$hasError){
			$data = [
				"OperatorId" 			=> (int)$this->game_api->operator_id,
				"Name" 					=> null,
				"NickName" 				=> null,
				"UserName"				=> $gameUsername,
				"Token" 				=> $token,
				"TotalBalance"			=> floatval($balance),
				"Gender" 				=> false,
				"Currency" 				=> $this->game_api->currency,
				"Country" 				=> $this->game_api->country,
				"PlayerId" 				=> (int)$playerId,
				"UserIP" 				=> $this->utils->getIp(),
				"HasError" 				=> $hasError,
				"ErrorId" 				=> (int)$code,
				"ErrorDescription" 		=> $message
			];
		}
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	public function getBalance(){
		$type = self::METHOD_GET_BALANCE;
		$status = false;
		$hasError = false;
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;
		$player = null;
		$code = $message = null;

		$rules = [
			'OperatorId' => 'required',
			// 'PlayerId' => 'required',
			// 'Token' => 'required',
			'PublicKey' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Parameters";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (getBalance)" . " - ". $message);
				throw new Exception($code);
			}
			
			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			$rawRequestBody = $this->request;
			#request body should be alphabetical
			ksort($rawRequestBody);

			unset($rawRequestBody['PublicKey']);

			$generatedPublicKey = $this->generatePublicKey(json_encode($rawRequestBody) . $this->game_api->public_key);

			$this->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API - Authentication publicKey and generatedPublicKey comparison: ",[
				"publicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);

			if(!$this->isValidPublicKey($publicKey, $generatedPublicKey)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid PublicKey";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (getBalance) -" . $message);
				throw new Exception($code);
			}
			
			$operatorId = isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
			if(!$this->isAccountValid($operatorId)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid OperatorId";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (authentication) -" . $message);
				throw new Exception($code);
			}
			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;
			
			$token = isset($this->request['Token']) ? $this->request['Token'] : null;
			$userName = isset($this->request['UserName']) ? $this->request['UserName'] : null;
			$password = isset($this->request['Password']) ? $this->request['Password'] : null;
			$playerId = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;

			if(!is_null($playerId) && (!is_int($playerId) || $playerId <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_PLAYER_ID;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (balance) -" . $message);
				throw new Exception($code);
			}
			//getplayerdetails
			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}elseif (empty($token) && !empty($userName) && !empty($password)){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsernameAndPassword($userName, $password);
			}elseif($playerId){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByPlayerId($playerId);
				if(!$player){
					$hasError =true;
					$code = self::CODE_WRONG_PLAYER_ID;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (balance) -" . $message);
					throw new Exception($code);
				}
			}

			if(!$player){
				$hasError =true;
				$code = self::CODE_INVALID_TOKEN;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (balance) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$hasError =true;
				$code = self::CODE_PLAYER_IS_BLOCKED;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player is not active";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (balance) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
			}		

			$code = self::SUCCESS;
			$message = $this->getResponseErrorMessage($code);
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;
		}
		$type = $this->method;

		$data = [
			"PlayerId" 			=> 0,
			"TotalBalance" 		=> 0,
			"Token" 			=> "",
			"HasError"			=> $hasError,
			"ErrorId" 			=> (int)$code,
			"ErrorDescription" 	=> $message
		];
		if(!$hasError){
			$data = [
				"PlayerId" => (int)$playerId,
				"TotalBalance" => $this->game_api->gameAmountToDBTruncateNumber($balance),
				"Token" => $token,
				"HasError" => $hasError,
				"ErrorId" => (int)$code,
				"ErrorDescription" => $message
			];
		}
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}
	#bet and settle
	public function withdrawAndDeposit(){
		$type = self::METHOD_WITHDRAW_AND_DEPOSIT;
		$status = false;
		$hasError = false;
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;
		$player_username = null;
		$player = null;
		$code = $message = null;
		$uniqueId = null;

		$rules = [
			'OperatorId' => 'required',
			'PlayerId' => 'required',
			'Token' => 'required',
			'WithdrawAmount' => 'required',
			'DepositAmount' => 'required',
			'Currency' => 'required',
			'GameId' => 'required',
			'RGSTransactionId' => 'required',
			'TypeId' => 'required',
			'PublicKey' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Parameters";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit)" . " - ". $message);
				throw new Exception($code);
			}
			$rawRequestBody = $this->request;
			$depositAmount = isset($rawRequestBody['DepositAmount']) ? $rawRequestBody['DepositAmount'] : 0;
			$withdrawAmount = isset($rawRequestBody['WithdrawAmount']) ? $rawRequestBody['WithdrawAmount'] : 0;

			if(!is_null($depositAmount) && (!is_float($depositAmount) || $depositAmount <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_TRANSACTION_AMOUNT;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}

			if(!is_null($withdrawAmount) && (!is_float($withdrawAmount) || $withdrawAmount <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_TRANSACTION_AMOUNT;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}

			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			$generatedPublicKeyRawString = $this->generatePublicKeyRawString($publicKey,$this->request_json);
			$generatedPublicKey = $this->generatePublicKey($generatedPublicKeyRawString);

			$this->debug_log("withdrawAndDeposit publicKey and generatedPublicKey comparison: ",[
				"publicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);

			$validPublicKey = $this->isValidPublicKey($publicKey, $generatedPublicKey);

			$testPlayerFromHeader = isset($this->requestHeaders['Testinternalplayer']) ? $this->requestHeaders['Testinternalplayer'] : null;

			if($this->game_api->enable_skip_validate_public_key && in_array($testPlayerFromHeader, $this->game_api->enable_skip_validate_public_key_player_list)){
				$validPublicKey = true;
			}
	
			if(!$validPublicKey){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid PublicKey";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}

			
			$operatorId = isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
			if(!$this->isAccountValid($operatorId)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid OperatorId";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}
			
			$currency = isset($this->request['Currency']) ? $this->request['Currency'] : null;

			if(!$this->isCurrencyCodeValid($currency)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Currency";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}
							
			
			// get player details
			$token = isset($this->request['Token']) ? $this->request['Token'] : null;
			$userName = isset($this->request['UserName']) ? $this->request['UserName'] : null;
			$password = isset($this->request['Password']) ? $this->request['Password'] : null;
			$playerId = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;

			if(!is_null($playerId) && (!is_int($playerId) || $playerId <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_PLAYER_ID;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}

			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}elseif (empty($token) && !empty($userName) && !empty($password)){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsernameAndPassword($userName, $password);
			}elseif($playerId){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByPlayerId($playerId);
			}

			if(!$player){
				$hasError =true;
				$code = self::CODE_INVALID_TOKEN;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player not exist";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$hasError =true;
				$code = self::CODE_PLAYER_IS_BLOCKED;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player is not active";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}		
			$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
			$uniqueId = $this->formatUniqueId($this->method, $uniqueId);

			#check if transaction already exist
			if($this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$currentMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExist($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);

				if(empty($currentMonthTransactionExists)){
					$prevMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExist($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid' => $uniqueId]);
				}	
				if($currentMonthTransactionExists || $prevMonthTransactionExists){
					$hasError =true;
					$code = self::CODE_TRANSACTION_ALREADY_COMPLETED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
					throw new Exception($code);
				}
			}else{
				$currentMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);
				if($currentMonthTransactionExists){
					$hasError =true;
					$code = self::CODE_TRANSACTION_ALREADY_COMPLETED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
					throw new Exception($code);
				}
			}

			
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId){
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$betAmount = isset($this->request['WithdrawAmount']) ? $this->request['WithdrawAmount'] : 0;
				$payoutAmount = isset($this->request['DepositAmount']) ? $this->request['DepositAmount'] : 0;
				$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : 0;

				if($betAmount < 0){
					$this->hasError = $hasError = true;
					#don't allow negative bet if single bet transaction
					$this->code = $code = self::CODE_WRONG_TRANSACTION_AMOUNT;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMS_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . "bet amount cannot be negative");
					throw new Exception($code);
				}
				#check if insufficient balance
				if(!$this->checkIfInsufficientBalance($balance,$betAmount)){
					$this->hasError = $hasError = true;
					$this->code = $code = self::CODE_NOT_ENOUGH_BALANCE;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMS_SEAMLESS_SERVICE_API: (withdrawAndDeposit) -" . $message);
					throw new Exception($code);
				}	
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT);
				$this->wallet_model->setGameProviderBetAmount($betAmount);
				$this->wallet_model->setGameProviderPayoutAmount($payoutAmount);
				$this->wallet_model->setGameProviderIsEndRound(true);
				$this->wallet_model->setExternalGameId($gameId);
				$resultAmount = $payoutAmount - $betAmount;
				$params['result_amount'] 	= $resultAmount;
				$params['bet_amount']		= $betAmount;
				$params['payout_amount']	= $payoutAmount;

				#determine the mode
				$mode = self::DEBIT;
				if($betAmount >= $payoutAmount){
					$mode = self::DEBIT;
				}elseif($betAmount <= $payoutAmount){
					$mode = self::CREDIT;
				}

				$params['mode'] = $mode;
				$params['current_player_balance'] = $balance;

				$this->balance_adjustment_type = $params['mode'];
				$trans_success = $this->debitCreditAmountToWallet($params);
				$this->hasError = !$trans_success;
				return $trans_success;
			});

			$code = !is_null($this->code) ? $this->code : $code;
			$hasError = !is_null($this->hasError) ? $this->hasError : $hasError;
			$message = $this->getResponseErrorMessage($code);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;
		}
		$type = $this->method;

		$data = [
			"PlayerId" => 0,
			"TotalBalance" => 0,
			"Token" => '',
			"PlatformTransactionId" => 0,
			"HasError" => $hasError,
			"ErrorId" => (int)$code,
			"ErrorDescription" => $message
		];
		if(!$hasError){
			$data = [
				"PlayerId" => (int)$playerId,
				"TotalBalance" => $this->getPlayerBalance($player_username, $playerId),
				"Token" => $token,
				"PlatformTransactionId" => (int)isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null,
				"HasError" => $hasError,
				"ErrorId" => (int)$code,
				"ErrorDescription" => $message
			];
		}
		

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	#bet
	public function withdraw(){
		$type = self::METHOD_WITHDRAW;
		$status = false;
		$hasError = false;
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;
		$player_username = null;
		$player = null;
		$code = $message = null;
		$uniqueId = null;

		$rules = [
			'OperatorId' => 'required',
			// 'PlayerId' => 'required',
			// 'Token' => 'required',
			'WithdrawAmount' => 'required',
			'Currency' => 'required',
			'GameId' => 'required',
			'RGSTransactionId' => 'required',
			'TypeId' => 'required',
			'PublicKey' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Parameters";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw)" . " - ". $message);
				throw new Exception($code);
			}
			
			
			$rawRequestBody = $this->request;
			$withdrawAmount = isset($rawRequestBody['WithdrawAmount']) ? $rawRequestBody['WithdrawAmount'] : 0;
		
			// $rawRequestBody['WithdrawAmount'] = $this->game_api->gameAmountToDBTruncateNumber($withdrawAmount); // Format with 4 decimal places

			if(!is_null($withdrawAmount) && (!is_float($withdrawAmount) || $withdrawAmount <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_TRANSACTION_AMOUNT;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}

			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			$generatedPublicKeyRawString = $this->generatePublicKeyRawString($publicKey,$this->request_json);
			$generatedPublicKey = $this->generatePublicKey($generatedPublicKeyRawString);

			$this->debug_log("Withdraw publicKey and generatedPublicKey comparison: ",[
				"publicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);
			
			$validPublicKey = $this->isValidPublicKey($publicKey, $generatedPublicKey);

			$testPlayerFromHeader = isset($this->requestHeaders['Testinternalplayer']) ? $this->requestHeaders['Testinternalplayer'] : null;

			if($this->game_api->enable_skip_validate_public_key && in_array($testPlayerFromHeader, $this->game_api->enable_skip_validate_public_key_player_list)){
				$validPublicKey = true;
			}
	
			if(!$validPublicKey){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid PublicKey";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}
			
			
			$operatorId = isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
			if(!$this->isAccountValid($operatorId)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid OperatorId";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}
			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			$currency = isset($this->request['Currency']) ? $this->request['Currency'] : null;

			if(!$this->isCurrencyCodeValid($currency)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}
			
			if(!is_null($playerId) && (!is_int($playerId) || $playerId <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_PLAYER_ID;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}
			
			// get player details
			$token = isset($this->request['Token']) ? $this->request['Token'] : null;
			$userName = isset($this->request['UserName']) ? $this->request['UserName'] : null;
			$password = isset($this->request['Password']) ? $this->request['Password'] : null;
			$playerId = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;

			
			if(!is_null($playerId) && (!is_int($playerId) || $playerId <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_PLAYER_ID;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}

			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}elseif (empty($token) && !empty($userName) && !empty($password)){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsernameAndPassword($userName, $password);
			}elseif($playerId){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByPlayerId($playerId);
			}


			if(!$player){
				$hasError =true;
				$code = self::CODE_INVALID_TOKEN;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$hasError =true;
				$code = self::CODE_PLAYER_IS_BLOCKED;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player is not active";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}		
			$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
			$uniqueId = $this->formatUniqueId($this->method, $uniqueId);
			#check if transaction already exist
			if($this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$currentMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExist($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);


				if(empty($currentMonthTransactionExists)){
					$prevMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExist($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid' => $uniqueId]);
				}	
				if($currentMonthTransactionExists || $prevMonthTransactionExists){
					$hasError =true;
					$code = self::CODE_TRANSACTION_ALREADY_COMPLETED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
					throw new Exception($code);
				}
			}else{
				$currentMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);
				if($currentMonthTransactionExists){
					$hasError =true;
					$code = self::CODE_TRANSACTION_ALREADY_COMPLETED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
					throw new Exception($code);
				}
			}

			
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId){
				$balance = $this->getPlayerBalance($player_username, $playerId);
				$betAmount = isset($this->request['WithdrawAmount']) ? $this->request['WithdrawAmount'] : 0;
				$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : 0;

				if($betAmount < 0){
					#don't allow negative bet if single bet transaction
					$hasError = true;
					$this->hasError =true;
					$this->code = $code  = self::CODE_WRONG_TRANSACTION_AMOUNT;
					$message = $this->getResponseErrorMessage($code) . ' - withraw amount cannot be negative ';
					$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (withdraw) -" . "bet amount cannot be negative");
					throw new Exception($code);
				}
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
				$this->wallet_model->setGameProviderBetAmount($betAmount);
				$this->wallet_model->setExternalGameId($gameId);
				$payoutAmount =  0;
				$resultAmount = $payoutAmount - $betAmount;
				$params['result_amount'] 	= $resultAmount;
				$params['bet_amount']		= $betAmount;
				$params['payout_amount']	= $payoutAmount;
				if(!$this->checkIfInsufficientBalance($balance,$betAmount)){
					$hasError = true;
					$this->hasError =true;
					$this->code = $code = self::CODE_NOT_ENOUGH_BALANCE;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (withdraw) -" . $message);
					throw new Exception($code);
				}	
				#determine the mode
				$mode = self::DEBIT;

				$params['mode'] = $mode;
				$params['current_player_balance'] = $balance;

				$this->balance_adjustment_type = $params['mode'];
				$trans_success = $this->debitCreditAmountToWallet($params);
				$this->hasError = !$trans_success;
				return $trans_success;
			});
			$code = !is_null($this->code) ? $this->code : $code;
			$hasError = !is_null($this->hasError) ? $this->hasError : $hasError;

			$message = $this->getResponseErrorMessage($code);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;

		}
		$type = $this->method;

		$data = [
			"PlayerId" => 0,
			"TotalBalance" => 0,
			"Token" => "",
			"PlatformTransactionId" => 0,
			"HasError" => $hasError,
			"ErrorId" => (int)$code,
			"ErrorDescription" => $message
		];

		if(!$hasError){
			$data = [
				"PlayerId" => (int)$playerId,
				"TotalBalance" => $this->game_api->gameAmountToDBTruncateNumber($this->getPlayerBalance($player_username, $playerId)),
				"Token" => $token,
				"PlatformTransactionId" => (int)isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null,
				"HasError" => $hasError,
				"ErrorId" => (int)$code,
				"ErrorDescription" => $message
			];
		}

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}

	#payout
	public function deposit(){
		$type = self::METHOD_DEPOSIT;
		$status = false;
		$hasError = false;
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;
		$player_username = null;
		$player = null;
		$code = $message = null;
		$uniqueId = null;

		$rules = [
			'OperatorId' => 'required',
			'PlayerId' => 'required',
			'Token' => 'required',
			'DepositAmount' => 'required',
			'Currency' => 'required',
			'GameId' => 'required',
			'RGSTransactionId' => 'required',
			// 'RGSRelatedTransactionId' => 'required',
			'TypeId' => 'required',
			'PublicKey' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Parameters";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit)" . " - ". $message);
				throw new Exception($code);
			}

			$rawRequestBody = $this->request;
			$depositAmount = isset($rawRequestBody['DepositAmount']) ? $rawRequestBody['DepositAmount'] : 0;

			if(!is_null($depositAmount) && (!is_float($depositAmount) || $depositAmount <=0)){
			// if(!is_null($depositAmount) && $depositAmount < 0){
				$hasError =true;
				$code = self::CODE_WRONG_TRANSACTION_AMOUNT;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}

			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			$generatedPublicKeyRawString = $this->generatePublicKeyRawString($publicKey,$this->request_json);
			$generatedPublicKey = $this->generatePublicKey($generatedPublicKeyRawString);

			$this->debug_log("Authentication publicKey and generatedPublicKey comparison: ",[
				"publicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);

			$validPublicKey = $this->isValidPublicKey($publicKey, $generatedPublicKey);

			$testPlayerFromHeader = isset($this->requestHeaders['Testinternalplayer']) ? $this->requestHeaders['Testinternalplayer'] : null;

			if($this->game_api->enable_skip_validate_public_key && in_array($testPlayerFromHeader, $this->game_api->enable_skip_validate_public_key_player_list)){
				$validPublicKey = true;
			}
	
			if(!$validPublicKey){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid PublicKey";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}
			
			$operatorId = isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
			if(!$this->isAccountValid($operatorId)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid OperatorId";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}
			 
			$currency = isset($this->request['Currency']) ? $this->request['Currency'] : null;

			if(!$this->isCurrencyCodeValid($currency)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Currency";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}			
			
			// get player details
			$token = isset($this->request['Token']) ? $this->request['Token'] : null;
			$userName = isset($this->request['UserName']) ? $this->request['UserName'] : null;
			$password = isset($this->request['Password']) ? $this->request['Password'] : null;
			$playerId = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;

			if(!is_null($playerId) && (!is_int($playerId) || $playerId <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_PLAYER_ID;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}

			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}elseif (empty($token) && !empty($userName) && !empty($password)){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsernameAndPassword($userName, $password);
			}elseif($playerId){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByPlayerId($playerId);
			}

			if(!$player){
				$hasError =true;
				$code = self::CODE_INVALID_TOKEN;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player not exist";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}

			if(!$playerStatus){
				$hasError =true;
				$code = self::CODE_PLAYER_IS_BLOCKED;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player is not active";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}		
			$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
			$uniqueId = $this->formatUniqueId($this->method, $uniqueId);

			#check if transaction already exist
			if($this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$currentMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExist($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);

				if(empty($currentMonthTransactionExists)){
					$prevMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExist($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid' => $uniqueId]);
				}	
				if($currentMonthTransactionExists || $prevMonthTransactionExists){
					$hasError =true;
					$code = self::CODE_DEPOSIT_TRANSACTION_ALREADY_RECEIVED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
					throw new Exception($code);
				}
			}else{
				$currentMonthTransactionExists = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);
				if($currentMonthTransactionExists){
					$hasError =true;
					$code = self::CODE_DEPOSIT_TRANSACTION_ALREADY_RECEIVED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message);
					throw new Exception($code);
				}
			}

			#check if can payout if has related bet/withdraw transaction
			$typeId = isset($this->request['TypeId']) ? $this->request['TypeId'] : null;
			$rgsRelatedTransactionId = isset($this->request['RGSRelatedTransactionId']) ? $this->request['RGSRelatedTransactionId'] : null;
			$rgsRelatedTransactionId = $this->formatUniqueId(self::METHOD_WITHDRAW, $rgsRelatedTransactionId);

			$relatedBetTransaction = null;
			if($this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table && $typeId !=1){
				$relatedBetTransaction = $currentMonthTransactionExists = $this->creedroomz_seamless_wallet_transactions->getRelatedBetExistingTransaction($this->game_api->getTransactionsTable(), ['external_uniqueid' => $rgsRelatedTransactionId]);
				if(empty($currentMonthTransactionExists)){
					$prevMonthTransactionExists = $this->creedroomz_seamless_wallet_transactions->getRelatedBetExistingTransaction($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid' => $rgsRelatedTransactionId]);
				}	
				if(!$currentMonthTransactionExists || !$prevMonthTransactionExists){
					$hasError = false;
					$code = self::SUCCESS;
					$this->flag_as_error = true;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message . ' - payout does not have a related bet');
					throw new Exception($code);
				}else{
					if(isset($relatedBetTransaction->status) && in_array($relatedBetTransaction->status,[Game_logs::STATUS_CANCELLED, Game_logs::STATUS_REFUND])){
						$hasError = false;
						$code = self::SUCCESS;
						$this->flag_as_error = true;
						$message = $this->getResponseErrorMessage($code);
						$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message . ' - does have a related bet status is already settled/cancelled/refunded');
						throw new Exception($code);
					}
				}
			}else{
				$relatedBetTransaction = $currentMonthTransactionExists = $this->creedroomz_seamless_wallet_transactions->getRelatedBetExistingTransaction($this->game_api->getTransactionsTable(), ['external_uniqueid' => $rgsRelatedTransactionId]);
				if(!$currentMonthTransactionExists && $typeId !=1){
					$hasError = false;
					$code = self::SUCCESS;
					$this->flag_as_error = true;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message . ' - payout does not have a related bet');
					throw new Exception($code);
				}else{
					if(isset($relatedBetTransaction->status) && in_array($relatedBetTransaction->status,[Game_logs::STATUS_CANCELLED, Game_logs::STATUS_REFUND])){
						$hasError = false;
						$code = self::SUCCESS;
						$this->flag_as_error = true;
						$message = $this->getResponseErrorMessage($code);
						$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (deposit) -" . $message . ' - does have a related bet status is already settled/cancelled/refunded');
						throw new Exception($code);
					}
				}
			}


			if(!$this->flag_as_error){
				$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId, $relatedBetTransaction){
					$balance = $this->getPlayerBalance($player_username, $playerId);
					$betAmount = 0;
					$payoutAmount = isset($this->request['DepositAmount']) ? $this->request['DepositAmount'] : 0;
					$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : 0; #null if cashback bonus
					if($payoutAmount < 0){
						#don't allow negative payout if single payout transaction
						$this->code = $code = self::CODE_WRONG_TRANSACTION_AMOUNT;
						$message = $this->getResponseErrorMessage($code) . ' - amount cannot be negative';
						$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (withdraw) -" . "payout amount cannot be negative");
						throw new Exception($code);
					}
					$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
					$this->wallet_model->setGameProviderBetAmount($betAmount);
					$this->wallet_model->setGameProviderPayoutAmount($payoutAmount);
					$this->wallet_model->setExternalGameId($gameId);

					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}

					#determine the mode
					$mode = self::CREDIT;
					$uniqueId = isset($relatedBetTransaction->rgs_transaction_id) ? $relatedBetTransaction->rgs_transaction_id : null;
					$relatedBetSeamlessUniqueId = $this->formatUniqueIdOfSeamlessService($uniqueId, self::DEBIT, 'game-');

					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetSeamlessUniqueId);
					}

					$resultAmount = $payoutAmount - $betAmount;
					$params['result_amount'] 	= $resultAmount;
					$params['bet_amount']		= $betAmount;
					$params['payout_amount']	= $payoutAmount;
					$params['mode'] = $mode;
					$params['current_player_balance'] = $balance;
	
					$this->balance_adjustment_type = $params['mode'];
					$trans_success = $this->debitCreditAmountToWallet($params);
					$this->hasError = !$trans_success;
					return $trans_success;
				});
			}
			

			$code = !is_null($this->code) ? $this->code : $code;
			$hasError = !is_null($this->hasError) ? $this->hasError : $hasError;
			$message = $this->getResponseErrorMessage($code);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;
		}
		$type = $this->method;

		$data = [
			"PlayerId" => 0,
			"TotalBalance" => 0,
			"Token" => "",
			"PlatformTransactionId" => 0,
			"HasError" => $hasError,
			"ErrorId" => (int)$code,
			"ErrorDescription" => $message
		];

		if(!$hasError){
			$platformTransactionId = (int)isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;

			if($this->flag_as_error){
				$platformTransactionId = 0;
			}
			$data = [
				"PlayerId" => (int)$playerId,
				"TotalBalance" => $this->getPlayerBalance($player_username, $playerId),
				"Token" => $token,
				"PlatformTransactionId" => $platformTransactionId,
				"HasError" => $hasError,
				"ErrorId" => (int)$code,
				"ErrorDescription" => $message
			];
		}

		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}


	#rollback
	public function rollback(){
		$type = self::METHOD_ROLLBACK;
		$status = false;
		$hasError = false;
		$balance = 0;
		$token = null;
		$gameUsername = null;
		$playerId = null;
		$player_username = null;
		$player = null;
		$code = $message = null;
		$uniqueId = null;

		$rules = [
			'OperatorId' => 'required',
			// 'PlayerId' => 'required',
			// 'Token' => 'required',
			'GameId' => 'required',
			'RGSTransactionId' => 'required',
			'PublicKey' => 'required'
		];		
		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid Parameters";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback)" . " - ". $message);
				throw new Exception($code);
			}

			$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

			$generatedPublicKeyRawString = $this->generatePublicKeyRawString($publicKey,$this->request_json);
			$generatedPublicKey = $this->generatePublicKey($generatedPublicKeyRawString);

			$this->debug_log("Rollback publicKey and generatedPublicKey comparison: ",[
				"publicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);

			$validPublicKey = $this->isValidPublicKey($publicKey, $generatedPublicKey);

			$testPlayerFromHeader = isset($this->requestHeaders['Testinternalplayer']) ? $this->requestHeaders['Testinternalplayer'] : null;

			if($this->game_api->enable_skip_validate_public_key && in_array($testPlayerFromHeader, $this->game_api->enable_skip_validate_public_key_player_list)){
				$validPublicKey = true;
			}
	
			if(!$validPublicKey){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid PublicKey";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
				throw new Exception($code);
			}
			
			$operatorId = isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
			if(!$this->isAccountValid($operatorId)){
				$hasError =true;
				$code = self::CODE_GENERAL_ERROR;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Invalid OperatorId";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
				throw new Exception($code);
			}
			
	
			// get player details
			$token = isset($this->request['Token']) ? $this->request['Token'] : null;
			$userName = isset($this->request['UserName']) ? $this->request['UserName'] : null;
			$password = isset($this->request['Password']) ? $this->request['Password'] : null;
			$playerId = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;

			if(!is_null($playerId) && (!is_int($playerId) || $playerId <=0)){
				$hasError =true;
				$code = self::CODE_WRONG_PLAYER_ID;
				$message = $this->getResponseErrorMessage($code);
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
				throw new Exception($code);
			}

			if($token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}elseif (empty($token) && !empty($userName) && !empty($password)){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerCompleteDetailsByUsernameAndPassword($userName, $password);
			}elseif($playerId){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByPlayerId($playerId);
			}
			
			if(!$player){
				$hasError =true;
				$code = self::CODE_INVALID_TOKEN;
				$message = $this->getResponseErrorMessage($code) . ' - ' . "Player not exist";
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
				throw new Exception($code);
			}


			if(!$playerStatus){
				$hasError =true;
				$code = self::CODE_PLAYER_IS_BLOCKED;
				$message = $this->getResponseErrorMessage($code) . ' - Player is not active';
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
				throw new Exception($code);
			}

			if($player){
				$playerId = $player->player_id;
				$params['operator_player_id'] = $playerId;
				$params['operator_player_username'] = $player_username;
				$params['operator_game_username'] = $gameUsername;
			}	

			$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
			$uniqueId = $this->formatUniqueId($this->method, $uniqueId);

			if($this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$rollbackTransaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);
				if(empty($rollbackTransaction)){
					$rollbackTransaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid' => $uniqueId]);
				}
				if($rollbackTransaction){
					$hasError =false;
					$this->flag_as_error = true;
					$code = self::SUCCESS;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message . "($uniqueId) transaction already refunded");
					throw new Exception($code);
				}
			}else{
				$rollbackTransaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsTable(), ['external_uniqueid' => $uniqueId]);
				if($rollbackTransaction){
					$hasError =false;
					$this->flag_as_error = true;
					$code = self::SUCCESS;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message . "($uniqueId) transaction already refunded");
					throw new Exception($code);
				}
			}


			$relatedTransaction = null;
			#check if transaction not found
			if($this->checkPreviousMonth && $this->game_api->use_monthly_transactions_table){
				$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
				$relatedTransaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsTable(), ['rgs_transaction_id' => $uniqueId]);

				if(empty($currentMonthRelatedTransaction)){
					$relatedTransaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsPreviousTable(), ['rgs_transaction_id' => $uniqueId]);
				}	

				if(!$relatedTransaction){
					$hasError =true;
					$code = self::CODE_TRANSACTION_NOT_FOUND;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
					throw new Exception($code);
				}
				$refundedStatuses = [Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED];
				$relatedTransactionStatus = isset($relatedTransaction['status']) ? $relatedTransaction['status'] : null;
				if(in_array($relatedTransactionStatus, $refundedStatuses)){
					#already refunded throw error
					$hasError =true;
					$code = self::CODE_TRANSACTION_ALREADY_COMPLETED;
					$message = $this->getResponseErrorMessage($code) . ' - Transaction already refunded';
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
					throw new Exception($code);
				}
			}else{
				$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
				$relatedTransaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsTable(), ['rgs_transaction_id' => $uniqueId]);
	
				if(!$relatedTransaction){
					$hasError =true;
					$code = self::CODE_TRANSACTION_NOT_FOUND;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
					throw new Exception($code);
				}
				$refundedStatuses = [Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED];
				$relatedTransactionStatus = isset($relatedTransaction['status']) ? $relatedTransaction['status'] : null;
				if(in_array($relatedTransactionStatus, $refundedStatuses)){
					#already refunded throw error
					$hasError =true;
					$code = self::CODE_TRANSACTION_ALREADY_COMPLETED;
					$message = $this->getResponseErrorMessage($code);
					$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (rollback) -" . $message);
					throw new Exception($code);
				}
			}

			if(!$this->flag_as_error){
				$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params, $player_username, $playerId, $relatedTransaction){
					$balance = $this->getPlayerBalance($player_username, $playerId);
					$betAmount = isset($relatedTransaction['withdraw_amount']) ? $relatedTransaction['withdraw_amount'] : 0;
					$payoutAmount = isset($relatedTransaction['deposit_amount']) ? $relatedTransaction['deposit_amount'] : 0;
					$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : 0; #null if cashback bonus
	
					if($payoutAmount < 0){
						#don't allow negative payout if single payout transaction
						$this->hasError = true;
						$this->code = $code = self::CODE_WRONG_TRANSACTION_AMOUNT;
						$message = $this->getResponseErrorMessage($code . ' -amount cannot be negative');
						$this->utils->error_log("DRAGOONSOFT_SEAMLESS_SERVICE_API: (withdraw) -" . "payout amount cannot be negative");
						throw new Exception($code);
					}
					$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
					$this->wallet_model->setGameProviderBetAmount($betAmount);
					$this->wallet_model->setGameProviderPayoutAmount($payoutAmount);
					$this->wallet_model->setExternalGameId($gameId);

					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					$mode = self::DEBIT;
					$uniqueId = isset($relatedTransaction['rgs_transaction_id']) ? $relatedTransaction['rgs_transaction_id'] : null;
					$relatedBetSeamlessUniqueId = $this->formatUniqueIdOfSeamlessService($uniqueId, $mode, 'game-');
					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedBetSeamlessUniqueId);
					}

					$resultAmount = $payoutAmount - $betAmount;
					$params['result_amount'] 	= $resultAmount;
					$params['bet_amount']		= $betAmount;
					$params['payout_amount']	= $payoutAmount;
				
					if($betAmount >= $payoutAmount){
						$mode = self::CREDIT;
					}elseif($betAmount <= $payoutAmount){
						$mode = self::DEBIT;
					}
	
					$params['mode'] = $mode;
					$params['current_player_balance'] = $balance;
	
					$this->balance_adjustment_type = $params['mode'];
					$trans_success = $this->debitCreditAmountToWallet($params);
					$this->hasError = !$trans_success;
					return $trans_success;
				});
			}


			$code = !is_null($this->code) ? $this->code : $code;
			$hasError = !is_null($this->hasError) ? $this->hasError : $hasError;
			$message = $this->getResponseErrorMessage($code);
			$status = true;
		}catch(Exception $error){
			$code = $error->getMessage();
			$status = false;
		}
		$type = $this->method;

		$data = [
			"PlayerId" => 0,
			"TotalBalance" => 0,
			"Token" => "",
			"HasError" => $hasError,
			"ErrorId" => (int)$code,
			"ErrorDescription" => $message
		];

		if(!$hasError){
			$data = [
				"PlayerId" => (int)$playerId,
				"TotalBalance" => $this->getPlayerBalance($player_username, $playerId),
				"Token" => $token,
				"HasError" => $hasError,
				"ErrorId" => (int)$code,
				"ErrorDescription" => $message
			];
		}
		
		$fields = [
			'player_id' => $playerId,
		];
		return $this->handleExternalResponse($status,$type, $this->request, $data,$code, $fields);
	}


	#-----------------Validations--------------------------
	public function checkIfGamePlatformIdIsValid(){
		$httpStatusCode = $this->getHttpStatusCode(self::ERROR_INTERNAL_SERVER_ERROR);
		if (empty($this->game_platform_id)) {
			$this->CI->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: Invalid game_platform_id");
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
			$this->utils->error_log('CREEDROOMZ_SEAMLESS_SERVICE_API: Request method not allowed');
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
			$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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
			$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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
			$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: " . $this->getResponseErrorMessage($code));
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

	
	public function isAccountValid($operatorId){
		return $this->game_api->operator_id == $operatorId;
    }

	private function isValidPublicKey($publicKey, $generatedPublicKey){
		return $publicKey == $generatedPublicKey;
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

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}
	
	private function generatePublicKey($key){
		$hash = hash('sha256', $key, true);
		return bin2hex($hash);
	}

	private function generatePublicKeyRawString($publicKey, $rawRequest){
		$rawRequest = preg_replace('/"PublicKey":"[^"]*",?/', '', $rawRequest);
		// Extract key-value pairs from JSON string using regular expression
		preg_match_all('/"([^"]+)":([^,}]+)/', $rawRequest, $matches, PREG_SET_ORDER);

		// Sort key-value pairs based on keys
		usort($matches, function($a, $b) {
			return strcmp($a[1], $b[1]);
		});

		// Reconstruct JSON string with sorted key-value pairs
		$newJsonString = '{' . implode(',', array_map(function($match) {
			return '"' . $match[1] . '":' . $match[2];
		}, $matches)) . '}';

		return $newJsonString . $this->game_api->public_key;
	}

	public function testGeneratePublicKey(){
		$publicKey = isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;
			$rawRequestBody = $this->request;
			$rawRequest = $this->request_json;
			$rawRequest = preg_replace('/"PublicKey":"[^"]*",?/', '', $rawRequest);

			// Extract key-value pairs from JSON string using regular expression
			preg_match_all('/"([^"]+)":([^,}]+)/', $rawRequest, $matches, PREG_SET_ORDER);

			// Sort key-value pairs based on keys
			usort($matches, function($a, $b) {
				return strcmp($a[1], $b[1]);
			});

			unset($rawRequestBody['PublicKey']);

			// Reconstruct JSON string with sorted key-value pairs
			$newJsonString = '{' . implode(',', array_map(function($match) {
				return '"' . $match[1] . '":' . $match[2];
			}, $matches)) . '}';

			$generatedPublicKey = $this->generatePublicKey($newJsonString . $this->game_api->public_key);

			print_r([
				"currentPublicKey" => $publicKey,
				"generatedPublicKey" => $generatedPublicKey
			]);

	}


	private function generateRandomNumber($length = 10) {
        $characters = '0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return (int)$randomString;
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
		$this->CI->utils->debug_log(
			"CREEDROOMZ_SEAMLESS_SERVICE_API (handleExternalResponse)",
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
		$this->CI->utils->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API save_response: ", $response);

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
		$extra = array_merge((array)$extra, (array)$this->requestHeaders);
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
				$message = null;
				break;
			case self::CODE_WRONG_PLAYER_ID:
				$message = lang('Wrong Player Id');
				break;
			case self::CODE_NOT_ENOUGH_BALANCE:
				$message = lang('Not enough balance');
				break;
			case self::CODE_PLAYER_IS_BLOCKED:
				$message = lang('Player is blocked');
				break;
			case self::CODE_INVALID_TOKEN:
				$message = lang('Invalid Token');
				break;
			case self::CODE_TRANSACTION_NOT_FOUND:
				$message = lang('Transaction not found');
				break;
			case self::CODE_WRONG_TRANSACTION_AMOUNT:
				$message = lang('Wrong transaction amount');
				break;
			case self::CODE_TRANSACTION_ALREADY_COMPLETED:
				$message = lang('Transaction Already Completed');
				break;
			case self::CODE_DEPOSIT_TRANSACTION_ALREADY_RECEIVED:
				$message = lang('Deposit Transaction Already Received');
				break;
			case self::CODE_INVALID_BONUS_DEFINITION_ID:
				$message = lang('Invalid Bonus Definition Id');
				break;
			case self::CODE_GENERAL_ERROR:
				$message = lang('General Error');
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
				$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$this->request_json = $request_json = file_get_contents('php://input');
		$this->utils->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API raw parsed:", $request_json);
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
	public function getPlayerByToken($token, $model="external_common_tokens"){

		$player = $this->$model->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerCompleteDetailsByUsernameAndPassword($username, $password){

		$player =  $this->creedroomz_seamless_wallet_transactions->getPlayerCompleteDetailsByUsernameAndPassword($username, $password, $this->game_api->getPlatformCode());

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

		$this->utils->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function debitCreditAmountToWallet($params){
		$this->utils->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API: $this->method - (debitCreditAmount)", $params);
		$before_balance = $player_balance = $this->game_api->dBtoGameAmount($params['current_player_balance']);
		$after_balance = $before_balance;

		$mode = $params['mode'];
		$flagrefunded = false;
		$response_code['success'] = true;
		$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
		$uniqueId = $this->formatUniqueId($this->method, $uniqueId);

		$isAdded = $this->insertIgnoreTransactionRecord($params, $before_balance, $after_balance, $flagrefunded);
		if($isAdded==false){
			$this->utils->error_log("CREEDROOMZ_SEAMLESS_SERVICE_API: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			return false;
		}	

		$response_code = $this->adjustWallet($mode, $params);


		$this->CI->debug_log("CREEDROOMZ_SEAMLESS_SERVICE_API - response_code: ", $response_code);
		if($response_code['success']){
			if($params['result_amount'] <> 0){
				$updatedAfterBalance = $this->creedroomz_seamless_wallet_transactions->updateAfterBalanceByTransactionId($uniqueId, $response_code['after_balance']);
				if(!$updatedAfterBalance){
					$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update after balance');
				}
			}
			$this->utils->debug_log(__METHOD__ . ': successfully updated after balance');
			

			#update related bet transaction 
			
			$typeId = isset($this->request['TypeId']) ? $this->request['TypeId'] : null;
			if($this->method == self::METHOD_DEPOSIT && $typeId != 1){
				$rgsRelatedTransactionId = isset($this->request['RGSRelatedTransactionId']) ? $this->request['RGSRelatedTransactionId'] : null;
				$uniqueId = $this->formatUniqueId(self::METHOD_WITHDRAW, $rgsRelatedTransactionId);
				$updatedStatus = $this->creedroomz_seamless_wallet_transactions->updateBetTransactionStatus($uniqueId, ['status' => Game_logs::STATUS_SETTLED, 'type' => self::METHOD_WITHDRAW, 'rgs_transaction_id' => $rgsRelatedTransactionId]);
				if(!$updatedStatus){
				$this->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: failed to update status of bet transaction from a payout request');
				}
			}

			if($this->method == self::METHOD_ROLLBACK){
				$uniqueId = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
				$updatedStatus = $this->creedroomz_seamless_wallet_transactions->updateTransactionStatus($uniqueId, ['status' => Game_logs::STATUS_REFUND]);
				if(!$updatedStatus){
					$this->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: failed to update status  bet transaction from a rollback request');
				}
			}
		}
		
		return $response_code['success'];
	}

	private function adjustWallet($action, $params) {
		$playerId = $params['operator_player_id'];

		$before_balance = isset($params['current_player_balance']) ? $params['current_player_balance'] :  $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
		$response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);

		$after_balance = $before_balance;
		$response_code['success'] = true;
		$amount = isset($params['result_amount']) ? $params['result_amount'] : 0;

		$uniqueId = $params['unique_id'] = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;

        if($action == self::DEBIT) {
            $amount = abs($amount);
			if($this->utils->compareResultFloat($amount, '>', 0)) {
				$seamlessUniqueId = $this->formatUniqueIdOfSeamlessService($uniqueId, self::DEBIT);
				$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : null; 
				$this->wallet_model->setUniqueidOfSeamlessService($seamlessUniqueId, $gameId);
				$amount = $this->game_api->gameAmountToDB($amount);
				$this->CI->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API ADJUST_WALLET: AMOUNT', $amount);
				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
				$response_code['success'] = true;

				
				$this->CI->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API.- balance afer adjust_wallet:', $deduct_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: failed to deduct subwallet');
				}
            }

			if(is_null($after_balance)){
				$after_balance = $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
			} 
			$response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {
			$amount = abs($amount);
			if($this->utils->compareResultFloat($amount, '=', 0)){
				$seamlessUniqueId = $this->formatUniqueIdOfSeamlessService($uniqueId, self::CREDIT);
				$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : null; 
				$this->wallet_model->setUniqueidOfSeamlessService($seamlessUniqueId, $gameId);	
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
			}elseif($this->utils->compareResultFloat($amount, '>', 0)) {
				$seamlessUniqueId = $this->formatUniqueIdOfSeamlessService($uniqueId, self::CREDIT);
				$gameId = isset($this->request['GameId']) ? $this->request['GameId'] : null; 
				$this->wallet_model->setUniqueidOfSeamlessService($seamlessUniqueId, $gameId);	
				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $amount, $after_balance);
								
				$response_code['success'] = true;
				
				$this->CI->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: ADD BALANCE', $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('CREEDROOMZ_SEAMLESS_SERVICE_API: adjustWallet' . ' - ' . 'failed to add to  subwallet');
					$response_code['success'] = false;
                }
			}
			if(is_null($after_balance)){
				$after_balance = $this->game_api->queryPlayerBalance($params['operator_game_username'])['balance'];
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
        $this->creedroomz_seamless_wallet_transactions->setTableName($tableName);  
		return $this->creedroomz_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}


	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['operator_id'] 			            		= isset($this->request['OperatorId']) ? $this->request['OperatorId'] : null;
		$data['player_id'] 			            			= isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
		$data['token'] 			            				= isset($this->request['Token']) ? $this->request['Token'] : null;
		$data['withdraw_amount'] 			   				= isset($this->request['WithdrawAmount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['WithdrawAmount']) : 0;
		$data['deposit_amount'] 			   				= isset($this->request['DepositAmount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['DepositAmount']) : 0;
		$data['currency'] 			            			= isset($this->request['Currency']) ? $this->request['Currency'] : null;
		$data['game_id'] 			            			= isset($this->request['GameId']) ? $this->request['GameId'] : null;
		$data['rgs_transaction_id'] 			            = isset($this->request['RGSTransactionId']) ? $this->request['RGSTransactionId'] : null;
		$data['rgs_related_transaction_id'] 			    = isset($this->request['RGSRelatedTransactionId']) ? $this->request['RGSRelatedTransactionId'] : null;
		$data['type_id'] 			            			= isset($this->request['TypeId']) ? $this->request['TypeId'] : null;
		$data['bonus_def_id'] 			            		= isset($this->request['BonusDefId']) ? $this->request['BonusDefId'] : null;
		$data['public_key'] 			            		= isset($this->request['PublicKey']) ? $this->request['PublicKey'] : null;

		$data['bet_amount'] 			   					= isset($raw_data['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet_amount']) : null;
		$data['payout_amount'] 			   					= isset($raw_data['payout_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['payout_amount']) : null;
		$data['valid_amount'] 			   					= isset($raw_data['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet_amount']) : null;
		$data['result_amount'] 			   					= isset($raw_data['result_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['result_amount']) : null;

		$data['trans_type'] 			    				= $this->method;


		$data['external_uniqueid'] 			    	= $this->formatUniqueId($this->method, $data['rgs_transaction_id']);


		if($this->method === self::METHOD_DEPOSIT){
			$data['status'] 			    				= Game_Logs::STATUS_SETTLED;
		}elseif($this->method === self::METHOD_WITHDRAW){
			// if lose status , only 1 call, status should be settled else will expect a /Deposit call should be pending initially
			$data['status'] 			    				= Game_Logs::STATUS_SETTLED;
			if($data['result_amount'] < 0){
				$data['status'] 			    				= Game_Logs::PENDING;
			}
		}elseif($this->method === self::METHOD_WITHDRAW_AND_DEPOSIT){
			$data['status'] 			    				= Game_Logs::STATUS_SETTLED;
		}elseif($this->method === self::METHOD_ROLLBACK){
			$data['status'] 			    				= Game_Logs::STATUS_REFUND;
		}

		$data['after_balance'] 								= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 							= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;
		$data['raw_data']									= isset($this->request) ? json_encode($this->request) : null;
		$dat['bet_amount']									= $data['bet_amount'];
		$data['win_amount']									= $raw_data['result_amount'];
		$data['balance_adjustment_method']					= isset($raw_data['mode']) ? $raw_data['mode'] : null;
		$data['game_platform_id'] 	                		= $this->game_platform_id;
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

	private function formatUniqueIdOfSeamlessService($uniqueId, $mode, $prefix=''){
		return $prefix.$this->game_platform_id.'-'.$uniqueId.'-'. $mode;
	}
}///END OF FILE////////////