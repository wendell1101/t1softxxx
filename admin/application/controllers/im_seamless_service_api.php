<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * {{url}}/im_seamless_service_api/6324/GetBalance
 * {{url}}/im_seamless_service_api/6324/PlaceBet
 * {{url}}/im_seamless_service_api/6324/SettleBet
 */
class Im_seamless_service_api extends BaseController
{
	const METHOD_GET_BALANCE = 'getBalance';
	const METHOD_PLACE_BET = 'placeBet';
	const METHOD_SETTLE_BET = 'settleBet';
	const METHOD_GENERATE_PLAYER_TOKEN = 'generatePlayerToken';

	const ALLOWED_API_METHODS = [
		self::METHOD_GET_BALANCE,
		self::METHOD_PLACE_BET,
		self::METHOD_SETTLE_BET,
	];

	const TYPE_PLACE_BET = "PlaceBet";

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

	const PRODUCT_WALLET = "IMSportsbook"; 
	const PRODUCT_WALLET2 = "IMESports";

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const CANCEL = 'cancel';
	const REFUND = 'refund';

	const SUCCESS = 0;

	const ERROR_IP_NOT_WHITELISTED = 401;

	#GP ERROR CODES

	const PLAYER_DOES_NOT_EXIST = 504;
	const PLAYER_IS_NOT_ACTIVE = 542;

    const INVALID_MERCHANT_OR_RESELLER_CODE = 5;
    const UNAUTHORIZED_ACCESS = 501;
	const INSUFICIENT_AMOUNT = 510;
	const INVALID_TOKEN = 531;


    const REQUIRED_FIELD_CANNOT_BE_EMPTY_OR_NULL = 505;
    const SETUP_IN_PROGRESS = 538;

    const INVALID_DOMAIN = 568;
    const PROVIDER_INTERNAL_ERROR = 600;
    const UNAUTHORIZED_PRODUCT_ACCESS = 601;
    const INVALID_ARGUMENT = 612;
    const SYSTEM_ERROR = 998;
    const SYSTEM_FAILURE = 999;



	const INVALID_GAME_CODE = -3;
	const SWITCH_KEY_AND_VALUE_AND_RETURN_THE_OUTPUT = -4;

	const ERROR_CODES = [
		self::INVALID_MERCHANT_OR_RESELLER_CODE,
		self::UNAUTHORIZED_ACCESS,
		self::REQUIRED_FIELD_CANNOT_BE_EMPTY_OR_NULL,
		self::SETUP_IN_PROGRESS,
		self::INVALID_DOMAIN,
		self::PROVIDER_INTERNAL_ERROR,
		self::UNAUTHORIZED_PRODUCT_ACCESS,
		self::INVALID_ARGUMENT,
		self::SYSTEM_ERROR,
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

	const PLACEBET_REFUND = 1005;
	const DANGER_REFUND = 2001;
	const LIVE_BALL_REFUND = 2011;
	const BT_BUY_BACK = 3001;
	const BT_CANCEL_BUY_BACK = 3002;
	const BT_RESTORE_BUYBACK = 3003;
	const LIVE_BALL_REFUND_REVERT = 3011;

	const SETTLE_HT = 4001;
	const SETTLE_FT = 4002;
	const SETTLE_OUTRIGHT = 4003;
	const SETTLE_PARLAY = 4004;
	const SETTLE_ON_HOLD = 4005;
	const UNSETTLE_HT = 5001;
	const UNSETTLE_FT = 5002;
	const UNSETTLE_OUTRIGHT = 5003;
	const UNSETTLE_PARLAY = 5004;
	const UNSETTLE_ON_HOLD = 5005;
	const CANCEL_HT = 6001;
	const CANCEL_FT = 6002;
	const CANCEL_OUTRIGHT = 6003;
	const CANCEL_SINGLE_WAGER = 6011;
	const CANCEL_PARLAY = 6014;
	const UNCANCEL_HT = 7001;
	const UNCANCEL_FT = 7002;
	const UNCANCEL_OUTRIGHT = 7003;
	const UNCANCEL_SINGLE_WAGER = 7011;
	const UNCANCEL_PARLAY = 7014;



	const ACTION_IDS = [
		self::PLACEBET_REFUND => 'PlaceBetRefund',
		self::DANGER_REFUND => 'DangerRefund',
		self::LIVE_BALL_REFUND => 'LiveBallRefund',
		self::BT_BUY_BACK => 'BTBuyBack',
		self::BT_CANCEL_BUY_BACK => 'BTCancelBuyBack',
		self::BT_RESTORE_BUYBACK => 'BTRestoreBuyBack',
		self::LIVE_BALL_REFUND_REVERT => 'LiveBallRefundRevert',
		self::SETTLE_HT => 'SettleHT',
		self::SETTLE_FT => 'SettleFT',
		self::SETTLE_OUTRIGHT => 'SettleOutright',
		self::SETTLE_PARLAY => 'SettleParlay',
		self::SETTLE_ON_HOLD => 'SettleOnHold',
		self::UNSETTLE_HT => 'UnsettleHT',
		self::UNSETTLE_FT => 'UnsettleFT',
		self::UNSETTLE_OUTRIGHT => 'UnsettleOutright',
		self::UNSETTLE_PARLAY => 'UnsettleParlay',
		self::UNSETTLE_ON_HOLD => 'UnsettleOnHold',
		self::CANCEL_HT => 'CancelHT',
		self::CANCEL_FT => 'CancelFT',
		self::CANCEL_OUTRIGHT => 'CancelOutright',
		self::CANCEL_SINGLE_WAGER => 'CancelSingleWager',
		self::CANCEL_PARLAY => 'CancelParlay',
		self::UNCANCEL_HT => 'UncancelHT',
		self::UNCANCEL_FT => 'UncancelFT',
		self::UNCANCEL_OUTRIGHT => 'UncancelOutright',
		self::UNCANCEL_SINGLE_WAGER => 'UncancelSingleWager',
		self::UNCANCEL_PARLAY => 'UncancelParlay',
	];

	#add player balance
	const CREDIT_ACTION_IDS = [
		self::SETTLE_HT,
		self::SETTLE_FT,
		self::SETTLE_OUTRIGHT,
		self::SETTLE_PARLAY,
		self::SETTLE_ON_HOLD,
		self::BT_RESTORE_BUYBACK,	
		self::CANCEL_HT,	
		self::CANCEL_FT,
		self::CANCEL_OUTRIGHT,
		self::CANCEL_SINGLE_WAGER,
		self::CANCEL_PARLAY,
		self::PLACEBET_REFUND,
		self::DANGER_REFUND,
		self::LIVE_BALL_REFUND,
		self::BT_BUY_BACK,
	];

	#decrease player balance
	const DEBIT_ACTION_IDS = [
		self::UNSETTLE_HT,
		self::UNSETTLE_FT,
		self::UNSETTLE_OUTRIGHT,
		self::UNSETTLE_PARLAY,
		self::UNSETTLE_ON_HOLD,
		self::UNCANCEL_HT,
		self::UNCANCEL_FT,
		self::UNCANCEL_OUTRIGHT,
		self::UNCANCEL_SINGLE_WAGER,
		self::UNCANCEL_PARLAY,
		self::BT_CANCEL_BUY_BACK,
		self::LIVE_BALL_REFUND_REVERT,		
	];

	const REFUND_ACTION_IDS = [
		self::PLACEBET_REFUND,
		self::DANGER_REFUND,
		self::LIVE_BALL_REFUND,
		self::BT_BUY_BACK,
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
	private $use_third_party_token=false;
	private $extra=[];
	private $result = [];

	public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'external_common_tokens', 'player_model', 'game_logs', 'im_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'ip'));

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
			$this->debug_log("IM seamless service:", "Failed to initialize inspect error logs");
		}
	}

	public function initialize()
	{
		$this->utils->debug_log('IM seamless service: raw request');
		$validRequestMethod = self::POST;

		if(!$this->isRequestMethodValid($validRequestMethod)){
			return false;
		};

		if(!$this->checkIfGamePlatformIdIsValid()){
			$this->utils->error_log('IM seamless service: game_platform_id not valid');
			return false;
		};
		
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
		$this->use_third_party_token = $this->game_api->use_third_party_token;
		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->im_seamless_wallet_transactions->setTableName($tableName);  

		if(!$this->game_api){
			$this->utils->debug_log("IM SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
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
				case 'GetBalance':
					$this->getBalance();
					break;
				case 'PlaceBet':
					$this->placeBet();
					break;
				case 'SettleBet':
					$this->settleBet();
					break;
				case 'generatePlayerToken':
					$this->generatePlayerToken();
					break;
				default:
					$this->utils->debug_log('IM seamless service: Invalid API Method');
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
	
	public function placeBet(){
		$type = self::METHOD_PLACE_BET;
		$status = false;
		$responseCode = self::SUCCESS;
		$message = '';
		$playerId = null;
		$insufficient_balance  = false;
		$player_username = null;
		$hasError = false;

		$rules = [
			'ProductWallet' => 'required',
			'SessionToken' => 'required',
			'Transactions' => 'required'
		];		
		try{
			if(!$this->isValidParams($this->request, $rules)){
				$responseCode = self::SYSTEM_FAILURE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API: place_bet' .' - '. $message);
				$this->utils->error_log('IM Seamless Service API: place_bet' .' - '. "Invalid Parameters");
				throw new Exception($responseCode);
			}
			
			// get player details
			$token = $this->request['SessionToken'];

			if($this->use_third_party_token){
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token, 'external_common_tokens');
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);
			}

			if($player == null){
				$responseCode = self::PLAYER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API: place_bet' .' - '. $message);
                throw new Exception($responseCode);
			}

            if(!$playerStatus){
				$responseCode = self::PLAYER_IS_NOT_ACTIVE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API: place_bet' .' - '. $message);
                throw new Exception($responseCode);
			}

			if($player){
				$playerId = $player->player_id;
			} 

			$params = [];
			$params['product_wallet'] 						= $this->request['ProductWallet'];
			$params['session_token'] 						= $this->request['SessionToken'];
			$params['transactions']							= $this->request['Transactions'];
			$params['total_amount']							= 0;
			$params['operator_player_id']					= $playerId;
			$params['im_player_id'] 						= isset($transaction['PlayerId']) ? $transaction['PlayerId'] : null;
			$currentPlayerBalance = $this->getPlayerBalance($player_username, $playerId);
			$params['current_player_balance'] = $currentPlayerBalance;
			$params['mode'] = self::DEBIT;

			foreach($this->request['Transactions'] as $transaction){
				$newTrans = [];
				$newTrans['total_amount'] 					= 0;
				$newTrans['ProductWallet'] 					= isset($this->request['ProductWallet']) ? $this->request['ProductWallet'] : null;
				$newTrans['SessionToken'] 					= isset($this->request['SessionToken']) ? $this->request['SessionToken'] : null;
				$newTrans['operator_player_id']				= $playerId;
				$newTrans['PlayerId'] 						= isset($transaction['PlayerId']) ? $transaction['PlayerId'] : null;
				$newTrans['ProviderPlayerId'] 				= isset($transaction['ProviderPlayerId']) ? $transaction['ProviderPlayerId'] : null;
				$newTrans['Provider'] 						= isset($transaction['Provider']) ? $transaction['Provider'] : null;
				$newTrans['GameId'] 						= isset($transaction['GameId']) ? $transaction['GameId'] : null;
				$newTrans['GameName']	 					= isset($transaction['GameName']) ? $transaction['GameName'] : null;
				$newTrans['BetId'] 							= isset($transaction['BetId']) ? $transaction['BetId'] : null;
				$newTrans['TransactionId']	 				= isset($transaction['TransactionId']) ? $transaction['TransactionId'] : null;
				$newTrans['ActionId'] 						= isset($transaction['ActionId']) ? $transaction['ActionId'] : null;
				$newTrans['Type'] 							= isset($transaction['Type']) ? $transaction['Type'] :null;
				$newTrans['Currency'] 						= isset($transaction['Currency']) ? $transaction['Currency'] :null;
				$newTrans['Amount'] 						= isset($transaction['Amount']) ? $transaction['Amount'] : null;
				$newTrans['Product'] 						= isset($transaction['Product']) ? $transaction['Product'] : null;
				$newTrans['TimeStamp'] 						= $transaction['TimeStamp'];
				
				$newTrans['external_uniqueid']              = isset($transaction['TransactionId']) ? $transaction['TransactionId'] : null;
				$newTrans['trans_type']                     = isset($transaction['Type']) ? $transaction['Type'] :null;

				$status = true;
				$responseCode = self::SUCCESS;
				
				#check if currentPlayerBalance > debit_amount				
				if(!$this->checkIfInsufficientBalance($currentPlayerBalance, $this->game_api->dBtoGameAmount($transaction['Amount']))){
					$insufficient_balance = true;
					$responseCode = self::INSUFICIENT_AMOUNT;
					$message = $this->getResponseErrorMessage($responseCode);
					$this->utils->error_log("IM Seamless Service API placeBet - " .  $message);
					$hasError = true;
					throw new Exception($responseCode);
				}			
	
				if(!$hasError){
					$newTrans['total_amount'] += isset($transaction['Amount']) ? - $this->game_api->dBtoGameAmount($transaction['Amount']) : 0;
					$params['total_amount'] += isset($transaction['Amount']) ? $this->game_api->dBtoGameAmount($transaction['Amount']) : 0;
				}
			}		
			if(!$hasError){
				$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($params){
					return $this->handlePlaceBet($params); 
				});		
			}
		
			$status=1;
			
		}catch(Exception $error){
			$hasError = true;
			$responseCode = $error->getMessage();
			$status = false;
		}
		
		$data = [];

		$extra = [
			"Code" => $responseCode,
			"Message" => $this->getResponseErrorMessage($responseCode)
		];

		$data = $this->formatResponse($data, $extra);

		if(!$hasError){
			$data = $this->result;
		}

		$fields = [
			'player_id' => $playerId,
		];

		return $this->handleExternalResponse($status,$type, $this->request, $data,$responseCode, $fields);
	}
	public function settleBet(){
		$type = self::METHOD_SETTLE_BET;
		$status = false;
		
		$responseCode = self::SUCCESS;
		$message = '';
		$playerId = null;
		$insufficient_balance  = false;
		$player_username = null;
		$hasError = false;
		$res = [];

		$rules = [
			'ProductWallet' => 'required',
			'Transactions' => 'required'
		];		
		try{
			if(!$this->isValidParams($this->request, $rules)){
				$responseCode = self::SYSTEM_FAILURE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API: settle_bet' .' - '. $message);
				$this->utils->error_log('IM Seamless Service API: settle_bet' .' - '. "Invalid Parameters");
				throw new Exception($responseCode);
			}						

			$params = [];
			$params['product_wallet'] 						= $this->request['ProductWallet'];
			$params['transactions']							= [];
			$params['total_amount']							= 0;
			$tempData 										= [];
			$data 											= [];
			foreach($this->request['Transactions'] as $transaction){
				// get player details
				$player_name = $transaction['PlayerId'];
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);

				if($player == null){
					$responseCode = self::PLAYER_DOES_NOT_EXIST;
					$message = $this->getResponseErrorMessage($responseCode);
					$this->utils->error_log('IM Seamless Service API: place_bet' .' - '. $message);
					$hasError = true;
					throw new Exception($responseCode);
				}

				if(!$playerStatus){
					$responseCode = self::PLAYER_IS_NOT_ACTIVE;
					$message = $this->getResponseErrorMessage($responseCode);
					$this->utils->error_log('IM Seamless Service API: place_bet' .' - '. $message);
					$hasError = true;
					throw new Exception($responseCode);
				}

				if($player){
					$playerId = $player->player_id;
				} 		

				$currentPlayerBalance = $this->getPlayerBalance($player_username, $playerId);
				$params['current_player_balance'] = $currentPlayerBalance;
	
				$newTrans = [];
				$newTrans['total_amount'] 					= 0;
				$newTrans['product_wallet'] 				= $this->request['ProductWallet'];
				$newTrans['operator_player_id']				= $playerId;
				$newTrans['PlayerId'] 						= isset($transaction['PlayerId']) ? $transaction['PlayerId'] : null;
				$newTrans['Provider'] 						= isset($transaction['Provider']) ? $transaction['Provider'] : null;
				$newTrans['GameId'] 						= isset($transaction['GameId']) ? $transaction['GameId'] : null;
				$newTrans['GameName']	 					= isset($transaction['GameName']) ? $transaction['GameName'] : null;
				$newTrans['BetId'] 							= isset($transaction['BetId']) ? $transaction['BetId'] : null;
				$newTrans['TransactionId']	 				= isset($transaction['TransactionId']) ? $transaction['TransactionId'] : null;
				$newTrans['ActionId'] 						= isset($transaction['ActionId']) ? $transaction['ActionId'] : null;
				$newTrans['Type'] 							= isset($transaction['Type']) ? $transaction['Type'] :null;
				$newTrans['Currency'] 						= isset($transaction['Currency']) ? $transaction['Currency'] :null;
				$newTrans['Amount'] 						= isset($transaction['Amount']) ? $transaction['Amount'] : null;
				$newTrans['Product'] 						= isset($transaction['Product']) ? $transaction['Product'] : null;
				$newTrans['EventId'] 						= isset($transaction['EventId']) ? $transaction['EventId'] : null;
				$newTrans['TimeStamp'] 						= $transaction['TimeStamp'];
				
				$status = true;
				$responseCode = self::SUCCESS;
				
				$actionId = isset($transaction['ActionId']) ? $transaction['ActionId'] :null;

				if(in_array($actionId, self::CREDIT_ACTION_IDS)){
					$newTrans['mode'] = self::CREDIT;
					$params['mode'] = self::CREDIT;
				}

				if(in_array($actionId, self::DEBIT_ACTION_IDS)){
					$newTrans['mode'] = self::DEBIT;
					$params['mode'] = self::DEBIT;

					$transactionAmount = isset($transaction['Amount']) ? $this->game_api->dBtoGameAmount($transaction['Amount']) : 0;
					if(!$this->checkIfInsufficientBalance($currentPlayerBalance,$transactionAmount)){
						$insufficient_balance = true;
						$responseCode = self::INSUFICIENT_AMOUNT;
						$message = $this->getResponseErrorMessage($responseCode);
						$this->utils->error_log("IM Seamless Service API placeBet - " .  $message);
						$hasError = true;
						throw new Exception($responseCode);
					}		
				}

				if(!$hasError){
					$params['transaction_player_id'] = $newTrans['PlayerId'];
					$newTrans['total_amount'] += $this->game_api->dBtoGameAmount($newTrans['Amount']);
					$params['total_amount'] += $this->game_api->dBtoGameAmount($newTrans['Amount']);
				}

				$newTrans['insufficient_balance'] = $insufficient_balance;
				$newTrans['current_player_balance'] = $currentPlayerBalance;			
				$params['transactions'][] = $newTrans;		
			}
			$trans_success = $this->lockAndTransForPlayerBalance($playerId , function() use($params, $newTrans){
				$trans_success  = $this->handleSettleBet($params);
				return $trans_success;
			});			
			
		}catch(Exception $error){
			$hasError = true;
			$responseCode = $error->getMessage();
			$status = false;
		}	
		$data = [];

		$extra = [
			"Code" => $responseCode,
			"Message" => $this->getResponseErrorMessage($responseCode)
		];

		$data = $this->formatResponse($data, $extra);

		if(!empty($this->result)){
			$data = $this->result;
		}

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status,$type, $this->request, $data,$responseCode, $fields);
	}
	public function getBalance(){
		$type = self::METHOD_GET_BALANCE;
		$status = false;
		
		$responseCode = self::SUCCESS;
		$message = $this->getResponseErrorMessage(self::SUCCESS);
		$balance = null;
		$token = null;
		$gameUsername = null;
		$playerId = null;

		$rules = [
			'ProductWallet' => 'required',
			'PlayerId' => 'required',
			'Currency' => 'required'
		];		
		try{		
			if(!$this->isValidParams($this->request, $rules)){
				$responseCode = self::SYSTEM_FAILURE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log("IM Seamless Service API:" . __METHOD__ . " - ". $message);
				$this->utils->error_log("IM Seamless Service API:" . __METHOD__ . " - ". "Invalid parameters");
				throw new Exception($message);
			}
			
			if(!$this->isCurrencyCodeValid($this->request['Currency'])){
				$responseCode = self::SYSTEM_FAILURE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API:' ." GetBalance".' - '. $message);
				$this->utils->error_log('IM Seamless Service API:' ." GetBalance".' - '. "Invalid Currency");
				throw new Exception($message);
			}

			$player_name = $this->request['PlayerId'];
			list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);

			if($player == null){
				$responseCode = self::PLAYER_DOES_NOT_EXIST;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API: ' . 'GetBalance' .' - '. $message);
                throw new Exception($message);
			}

            if(!$playerStatus){
				$responseCode = self::PLAYER_IS_NOT_ACTIVE;
				$message = $this->getResponseErrorMessage($responseCode);
				$this->utils->error_log('IM Seamless Service API: ' . 'GetBalance' .__METHOD__.' - '. $message);
                throw new Exception($message);
			}


			$playerId = $player->player_id;

			$responseCode = self::SUCCESS;
			$currency = $this->request['Currency'];
			$balance = $this->getPlayerBalance($player_username, $playerId);
			$status = true;
		}catch(Exception $error){
			$responseCode = self::SYSTEM_FAILURE;
			$message = $error->getMessage();
			$currency = $this->request['Currency'];
			$status = false;
		}

		$data = [
			"PlayerId" => $gameUsername,
			'Currency' => $currency,
			'Balance' => $this->game_api->gameAmountToDB($balance)
		];
		$extra = [
			'Code' => $responseCode,
			'Message' => $message
		];

		$data = $this->formatResponse($data, $extra);

		$fields = [
			'player_id'		=> $playerId,
		];

		return $this->handleExternalResponse($status,$type, $this->request, $data,$responseCode, $fields);
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
			$this->utils->error_log('IM seamless service: Request method not allowed');
			http_response_code(405);
			return false;
		}
		return true;
	}

	public function validateWhiteIP()
	{
		$validated =  $this->game_api->validateWhiteIP();
		if (!$validated) {
			$this->utils->error_log("IM seamless service: " . $this->getResponseErrorMessage(self::ERROR_IP_NOT_ALLOWED));
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
			$this->utils->error_log("IM seamless service: " . $this->getResponseErrorMessage(self::ERROR_GAME_UNDER_MAINTENANCE));
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
			$this->utils->error_log("IM seamless service: " . $this->getResponseErrorMessage(self::ERROR_API_METHOD_NOT_ALLOWED));
			$httpStatusCode = $this->getHttpStatusCode(self::ERROR_API_METHOD_NOT_ALLOWED);
			http_response_code($httpStatusCode);
			return false;		
		}
		return true;
	}

	public function isAccountValid($merchant_code){
		return $this->game_api->merchant_code == $merchant_code;
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
				$this->utils->error_log("IM SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("IM SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("IM SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}
	# ---------------Response-------------------------------

	public function externalResponse($data, $extra, $httpStatusCode = 200)
	{
		if(isset($extra['errorDescription'])){
			$this->utils->error_log('IM Seamless API: ', $extra['errorDescription']);
		}
		$hasError = isset($extra['hasError']) ? $extra['hasError'] : 0;
		$errorId = $extra['errorId'];
		$errorDescription = $extra['errorDescription'];
		if ($extra['hasError']) {
			$this->utils->error_log("IM seamless service ($this->game_platform_id): $errorDescription");
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
		$response = [];
		if (!empty($data)) {
			foreach ($data as $key => $value) {
				$response[$key] = $value;
			}
			if(isset($extra['Code']) && isset($extra['Message'])){
				$response["Code"] = $extra['Code'];
				$response["Message"] = $extra['Message'];
			}
		} else {
			if(isset($extra['Code']) && isset($extra['Message'])){
				$response =  [
					"Code" => $extra['Code'],
					'Message' => $extra['Message'],
				];
			}
		}

		return $response;
	}

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->CI->utils->debug_log("IM SEAMLESS_SERVICE_API (handleExternalResponse)",
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
		$this->CI->utils->debug_log("IM save_response_result: $currentDateTime",["status" => $status, "type" => $type,"data" =>  $data, "response" => $response, "http_status" => $httpStatusCode,"fields" =>  $fields, "cost" => $cost]);
		$this->CI->utils->debug_log("IM save_response: ",$response);

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
				$message = lang('Success.');
				break;
			
			case self::SYSTEM_FAILURE: 
				$message = lang('System has failed to process your request.');
				break;
			case self::PLAYER_DOES_NOT_EXIST: 
				$message = lang("Player does not exist.");
				break;			
			case self::PLAYER_IS_NOT_ACTIVE:
				$message = lang("Player is inactive.");
				break;
			case self::INSUFICIENT_AMOUNT:
				$message = lang("Insufficient amount.");
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
				$this->utils->error_log("IM SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}

	#-----------------------helpers-----------------------------------
	public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("IM SEAMLESS SERVICE raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("IM SEAMLESS SERVICE raw parsed:", $request_json);
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
	public function debug_log($key, $value){
		$this->CI->utils->debug_log($key, $value);
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

		$this->utils->debug_log("IM SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function handlePlaceBet($params){
		$this->utils->debug_log("IM SEAMLESS SERVICE: (debitCreditAmount)", $params);
		#loop transactions
		$mode = $params['mode'];
		$data = [];
		$current_player_balance = $params['current_player_balance'];
		$before_balance = $current_player_balance;
		$after_balance = $before_balance;
		$response_code['success'] = false;
		$abort = false;

		foreach($params['transactions'] as $key => $transaction){
			$before_balance = $after_balance;

			$mode = $params['mode'];
			if(isset($transaction['mode'])){
				$mode = $transaction['mode'];
			}

			switch($mode){
				case self::DEBIT:
					$after_balance = $before_balance - $this->game_api->dBtoGameAmount($transaction['Amount']);
					break;
				case self::CREDIT:
					$after_balance = $before_balance + $this->game_api->dBtoGameAmount($transaction['Amount']);
					break;
				default:
					break;
			}
			
			$transaction['ProductWallet'] 					= $params['product_wallet'];
			$transaction['SessionToken'] 					= isset($params['session_token']) ? $params['session_token'] : null;
			$transaction['total_amount'] 					= isset($params['total_amount']) ? -$params['total_amount'] : null;

			if(isset($transaction['operator_player_id'])){
				$operator_player_id = $transaction['operator_player_id'];
			}else{
				$operator_player_id = isset($params['operator_player_id']) ? $params['operator_player_id'] : null;
			}
			$transaction['operator_player_id'] = $operator_player_id;	

			$player_balance = $current_player_balance;
			
			$bet_id = $transaction['BetId'];
			$uniqueid = $this->formatExternalUniqueId($mode, $bet_id);
	
			$flagrefunded = false;
	
			$existingRow = $this->im_seamless_wallet_transactions->getExistingTransaction($uniqueid,$mode);

			if(!empty($existingRow)){	
				$response_code['success'] = true;
				$this->hasError = true;
				continue;
			}	
	
			$flagrefunded = false;
			$isAdded = $this->insertIgnoreTransactionRecord($transaction, $before_balance, $after_balance, $flagrefunded, $params);
			
			if($isAdded===false){
				$this->utils->error_log("IM SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return false;
			}			
		}	
		if(!$abort){
			$response_code = $this->placeBetAdjustWallet($mode, $params);

			foreach($params['transactions'] as $transaction){
				$uniqueid = $this->formatExternalUniqueId($mode, $transaction['BetId']);
				$currentTransaction = $this->im_seamless_wallet_transactions->getTransaction($uniqueid);
				$after_balance = $currentTransaction->after_balance;

				$tempData = [
					"OperatorTransactionId" =>  $transaction['TransactionId'],
					"TransactionId" =>  $transaction['TransactionId'],
					"Balance" =>  $this->game_api->gameAmountToDB($after_balance)
				];
				$extra = [
					"Code" => self::SUCCESS,
					"Message" => $this->getResponseErrorMessage(self::SUCCESS)
				];
		
				$tempData = $this->formatResponse($tempData, $extra);
				$data['Results'][]  = $tempData;
			}
		}

		$this->result = $data;
		return $response_code['success'];	
	}

	public function handleSettleBet($params){
		$abort = false;
		$response_code['success'] = false;
		$current_player_balance = $params['current_player_balance'];
		$before_balance = $current_player_balance;
		$after_balance = $before_balance;
		$mode = $params['mode'];
		$data = [];


		#multiple insert-ignore of nested transactions
		$transactions = isset($params['transactions']) ? $params['transactions'] : [];
		foreach($transactions as $transaction){
			#check if existing transaction skip to loop if existing
			$uniqueid = isset($transaction['BetId']) ? 'settle-'.$transaction['BetId'] : null;

			$existingRow = $this->im_seamless_wallet_transactions->getUniqueExistingTransaction($uniqueid);
			#show trans detail on resp even for existing trans
			if(!empty($existingRow)){
				$tempData = [
					"OperatorTransactionId" =>  $transaction['TransactionId'],
					"TransactionId" =>  $transaction['TransactionId'],
					"Balance" =>  $this->game_api->gameAmountToDB($current_player_balance)
				];
				$extra = [
					"Code" => self::SUCCESS,
					"Message" => $this->getResponseErrorMessage(self::SUCCESS)
				];
		
				$tempData = $this->formatResponse($tempData, $extra);
				$data['Results'][]  = $tempData;
				continue;
			}

			// pre calculate after balance
			$before_balance = $after_balance;

			$mode = $params['mode'];
			if(isset($transaction['mode'])){
				$mode = $transaction['mode'];
			}

			switch($mode){
				case self::DEBIT:
					$after_balance = $before_balance - $this->game_api->dBtoGameAmount($transaction['Amount']);
					break;
				case self::CREDIT:
					$after_balance = $before_balance + $this->game_api->dBtoGameAmount($transaction['Amount']);
					break;
				default:
					break;
			}

			$transaction['ProductWallet'] 					= $params['product_wallet'];
			$transaction['SessionToken'] 					= isset($params['session_token']) ? $params['session_token'] : null;
			$transaction['total_amount'] 					= isset($params['total_amount']) ? -$params['total_amount'] : null;

			if(isset($transaction['operator_player_id'])){
				$operator_player_id = $transaction['operator_player_id'];
			}else{
				$operator_player_id = isset($params['operator_player_id']) ? $params['operator_player_id'] : null;
			}
			$transaction['operator_player_id'] = $operator_player_id;	

			# insert ignore
			$flagrefunded = false;
			$isAdded = $this->insertIgnoreTransactionRecord($transaction, $before_balance, $after_balance, $flagrefunded, $params);

			$this->debug_log("added trans:", $isAdded);

		}

		if(!$abort){
			$response_code = $this->settleBetAdjustWallet($mode, $params);
			$this->debug_log("resp_code: ", $response_code);
			$data['Results'] = [];
			foreach($transactions as $transaction){
				$external_unique_id = $this->formatExternalUniqueId($mode, $transaction['BetId']);
				$currentTransaction = $this->im_seamless_wallet_transactions->getTransaction($external_unique_id);
				if($currentTransaction){
					$after_balance = $currentTransaction->after_balance;
				}
		

				$tempData = [
					"OperatorTransactionId" =>  $transaction['TransactionId'],
					"TransactionId" =>  $transaction['TransactionId'],
					"Balance" =>  $this->game_api->gameAmountToDB($after_balance)
				];
				$extra = [
					"Code" => self::SUCCESS,
					"Message" => $this->getResponseErrorMessage(self::SUCCESS)
				];
		
				$tempData = $this->formatResponse($tempData, $extra);
				$data['Results'][]  = $tempData;
				$betId = isset($transaction['BetId']) ? $transaction['BetId'] : null;
				
				$relatedTransaction = $this->im_seamless_wallet_transactions->getTransactionByBetId($betId);
					
				$transactionAmount = isset($relatedTransaction->result_amount) ? $relatedTransaction->result_amount : 0;
				$creditAmount = isset($transaction['Amount']) ? $transaction['Amount'] : 0;

				$resultAmount  = $creditAmount - abs($transactionAmount);
				$updateUnsettleData = $this->im_seamless_wallet_transactions->updateUnsettleData($betId, [
					'trans_status' => GAME_LOGS::STATUS_SETTLED,
					'result_amount' => $resultAmount,
					'after_balance' => $response_code['after_balance'],
					'settled_at' => (new DateTime($transaction['TimeStamp']))->format("Y-m-d H:i:s")
				]);
				if(!$updateUnsettleData){
					$this->utils->debug_log(__METHOD__ . ': inserted transaction but failed to update unsettleData');
				}	
				$this->utils->debug_log(__METHOD__ . ': successfully updated data');
			}

		}
		$this->result = $data;
		return $response_code['success'];
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance, $flagrefunded, $extra=null){	
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;

		$this->trans_records = $trans_record = $this->makeTransactionRecord($data, $extra);
		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->im_seamless_wallet_transactions->setTableName($tableName);  
		return $this->im_seamless_wallet_transactions->insertIgnoreRow($trans_record);
	}

	private function placeBetAdjustWallet($action, $params) {	
		# get first transaction data
		$transaction = isset($params['transactions'][0]) ? $params['transactions'][0] : null;
		$transaction_id = isset($transaction['TransactionId']) ? $transaction['TransactionId'] : null; 

		$playerId = isset($params['operator_player_id']) ? $params['operator_player_id'] : null;
		
		if($playerId == null){
			$playerId = isset($transaction['operator_player_id']) ? $transaction['operator_player_id'] : null;
		}
		
		$before_balance = $this->getPlayerBalance($transaction['PlayerId'], $playerId);
		$after_balance = $before_balance;

		$response_code['before_balance'] = $before_balance;
		$response_code['after_balance'] = $after_balance;
		$response_code['success'] = false;
        if($action == self::DEBIT) {
            $finalAmount = $params['total_amount'];

            if($finalAmount > 0) {
				if($transaction_id != null){
					$uniqueid =  $action.'-'.$transaction_id;
					$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
					$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

					$this->CI->utils->debug_log('IM SEAMLESS SERVICE DEBIT FINAL AMOUNT', $finalAmount);
					$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
					if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
						$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to deduct subwallet');
					}
					$response_code['success'] = true;
					$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE DEDUCT BALANCE - (DEBIT)', $deduct_balance);
				}

				$after_balance = $this->getPlayerBalance($transaction['PlayerId'], $playerId);
			}	

			$after_balance =  $this->getPlayerBalance($transaction['PlayerId'], $playerId);
			$response_code["after_balance"] = $after_balance;
        }elseif ($action == self::CREDIT) {
			$finalAmount = $params['total_amount'];

			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$transaction_id;
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	
				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }
				$response_code['success'] = true;
				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE ADD BALANCE - (CREDIT)', $add_balance);
			}
			$after_balance =  $this->getPlayerBalance($transaction['PlayerId'], $playerId);
			$response_code["after_balance"] = $after_balance;

		} elseif ($action == self::CANCEL) {
			$finalAmount = $params['total_amount'];

			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$transaction_id;
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }
				$response_code['success'] = true;
				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE ADD BALANCE (CANCEL)', $add_balance);
			}
			$after_balance =  $this->getPlayerBalance($transaction['PlayerId'], $playerId);
			$response_code["after_balance"] = $after_balance;
		}
		elseif ($action == self::REFUND) {
			$finalAmount = $params['total_amount'];
			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$transaction_id;
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
                	$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }
				$response_code['success'] = true;
				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE ADD BALANCE (REFUND)', $add_balance);
			}
			$after_balance =  $this->getPlayerBalance($transaction['PlayerId'], $playerId);
			$response_code["after_balance"] = $after_balance;
		}	
		else {	
           return $response_code;
        }

		return $response_code;

	}

	private function settleBetAdjustWallet($action, $params) {
		$params['operator_player_id'] =  isset($params['transactions'][0]['operator_player_id']) ? $params['transactions'][0]['operator_player_id'] : null;
		$params['TransactionId'] =  isset($params['transactions'][0]['TransactionId']) ? $params['transactions'][0]['TransactionId'] : null;
		$params['PlayerId'] =  isset($params['transactions'][0]['PlayerId']) ? $params['transactions'][0]['PlayerId'] : null; 

		$playerId = $this->game_api->getPlayerIdByGameUsername($params['PlayerId']);

		$this->debug_log('the player_id: ', $playerId);

		$before_balance = $this->getPlayerBalance($params['PlayerId'], $params['operator_player_id']);

		$response_code['before_balance'] = $before_balance;

		$after_balance = $before_balance;

		$response_code['success'] = true;
        if($action == self::DEBIT) {
            $finalAmount = $params['total_amount'];
            if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$params['TransactionId'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$this->CI->utils->debug_log('IM SEAMLESS SERVICE CREDIT-DEBIT FINAL AMOUNT', $finalAmount);
				$deduct_balance = $this->wallet_model->decSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$deduct_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
					$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to deduct subwallet');
				}
				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE DEDUCT BALANCE - (DEBIT)', $deduct_balance);
            }

			$after_balance = $this->getPlayerBalance($params['PlayerId'], $params['operator_player_id']);
			$response_code["after_balance"] = $after_balance;
        }elseif ($action == self::CREDIT) {
			$finalAmount = $params['total_amount'];
			$this->debug_log("credit final_amount: ", $finalAmount);
			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$params['TransactionId']; 
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				$this->debug_log("test_add_balance:", $add_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
                	$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }
				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE ADD BALANCE - (CREDIT)', $add_balance);

			}
			$after_balance =  $this->getPlayerBalance($params['PlayerId'], $params['operator_player_id']);
			$response_code["after_balance"] = $after_balance;

		} elseif ($action == self::CANCEL) {
            $finalAmount = $params['total_amount'];

			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$params['TransactionId'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
                	$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }

				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE ADD BALANCE (CANCEL)', $add_balance);

			}
			$after_balance =  $this->getPlayerBalance($params['PlayerId'], $params['operator_player_id']);
			$response_code["after_balance"] = $after_balance;
		}
		elseif ($action == self::REFUND) {
			$finalAmount = $params['total_amount'];

			if($finalAmount > 0) {
				$uniqueid =  $action.'-'.$params['TransactionId'];
				$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid; 
				$this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);	

				$add_balance = $this->wallet_model->incSubWallet($playerId, $this->game_api->getPlatformCode(), $finalAmount, $after_balance);
				if(!$add_balance){ #check if success, posssible balance is lock and response timeout on remote wallet
					$response_code['success'] = false;
                	$this->utils->debug_log('IM_SEAMLESS_SERVICE: ' . __METHOD__ . ' - ' . 'failed to add to  subwallet');
                }
				$this->CI->utils->debug_log('IM_SEAMLESS_SERVICE ADD BALANCE (REFUND)', $add_balance);
			}
			$after_balance =  $this->getPlayerBalance($params['PlayerId'], $params['operator_player_id']);
			$response_code["after_balance"] = $after_balance;
		}		
		else {	
           return $response_code;
        }

		return $response_code;

	}



	public function makeTransactionRecord($raw_data, $extra=null){
		$mode = isset($raw_data['mode']) ? $raw_data['mode'] : null;
		if($mode == null){
			$mode = isset($extra['mode']) ? $extra['mode'] : null;
		}

		$player_id = isset($raw_data['operator_player_id']) ? $raw_data['operator_player_id'] : null;
		
		if($player_id == null){
			$player_id = isset($extra['operator_player_id']) ? $extra['operator_player_id'] : null;
		}
		$betId 	= isset($raw_data['BetId']) ? $raw_data['BetId']: null;

		$currentTransaction = $this->im_seamless_wallet_transactions->getTransactionByBetId($betId);
				
		$transactionAmount = isset($currentTransaction->result_amount) ? $currentTransaction->result_amount : 0;

		$amount = isset($raw_data['Amount']) ? $raw_data['Amount'] : 0;

		if($mode == self::DEBIT){
			$amount = $amount * -1;
		}

		$result_amount  = $amount - abs($transactionAmount);

		
		$this->CI->utils->debug_log("makeTransactionRecord1: $mode)", $raw_data);

		$data = [];
		$data['product_wallet'] 			        = isset($raw_data['product_wallet']) ? $raw_data['product_wallet'] : null;
		$data['session_token'] 			            = isset($raw_data['SessionToken']) ? $raw_data['SessionToken'] : null;
		$data['im_player_id'] 			            = isset($raw_data['PlayerId']) ? $raw_data['PlayerId'] : null;
		$data['player_id'] 			                = $player_id;
		$data['provider_player_id'] 			    = isset($raw_data['ProviderPlayerId']) ? $raw_data['ProviderPlayerId'] : null;
		$data['provider'] 			                = isset($raw_data['Provider']) ? $raw_data['Provider'] : null;
		$data['game_id'] 			            	= isset($raw_data['GameId']) ? $raw_data['GameId']: null;
		$data['game_name'] 			            	= isset($raw_data['GameName']) ? $raw_data['GameName']: null;
		$data['game_type'] 			            	= isset($raw_data['GameType']) ? $raw_data['GameType']: null;
		$data['bet_id'] 			            	= isset($raw_data['BetId']) ? $raw_data['BetId']: null;
		$data['transaction_id'] 			        = isset($raw_data['TransactionId']) ? $raw_data['TransactionId'] : null;
		$data['action_id'] 			   				= isset($raw_data['ActionId']) ? $raw_data['ActionId'] : null;
		$data['event_id'] 			   				= isset($raw_data['EventId']) ? $raw_data['EventId'] : null;
		$data['type'] 								= isset($raw_data['Type']) ? $raw_data['Type'] : null;
		$data['currency'] 							= isset($raw_data['Currency']) ? $raw_data['Currency'] : null;
		$data['amount'] 							= isset($raw_data['Amount']) ? $raw_data['Amount'] : null;
		$data['result_amount'] 						= $result_amount;
		$data['converted_amount'] 					= isset($raw_data['Amount']) ? $this->game_api->dBToGameAmount($raw_data['Amount']) : null;
		$data['product'] 							= isset($raw_data['Product']) ? $raw_data['Product'] : null;
		$data['timestamp'] 							= isset($raw_data['TimeStamp']) ? $raw_data['TimeStamp'] : null;
		$data['settled_at'] 						= null;

		$data['trans_status'] 			    		= GAME_LOGS::STATUS_SETTLED;

		if($data['type'] == self::TYPE_PLACE_BET){
			$data['trans_status'] 			    		= GAME_LOGS::STATUS_UNSETTLED;
		}

		if(in_array($data['action_id'], self::REFUND_ACTION_IDS)){
			$data['trans_status'] 			    	= GAME_LOGS::STATUS_UNSETTLED;
		}		
		$external_unique_id = $this->formatExternalUniqueId($mode, $betId);

		$data['external_uniqueid'] 			    	= $external_unique_id;
		$data['trans_type'] 			    		= isset($raw_data['Type']) ? $raw_data['Type'] : null;
		$data['balance_adjustment_method'] 			= $mode;
		$data['after_balance'] 						= isset($raw_data['after_balance']) ? $raw_data['after_balance'] : 0;
		$data['before_balance'] 					= isset($raw_data['before_balance']) ? $raw_data['before_balance'] : 0;

		// $data['raw_data']							= isset($this->request) ? json_encode($this->request) : null;
		$data['raw_data']							= null;

		$data['game_platform_id'] 	                = $this->game_platform_id;
		return $data;
	}

	private function formatExternalUniqueId($mode, $uniqueid){
		switch($mode){
			case self::DEBIT: 
				return self::DEBIT.'-'.$uniqueid;
				break;
			case self::CREDIT:
				return self::CREDIT.'-'.$uniqueid;
				break;
			case self::CANCEL:
				return self::CREDIT.'-'.$uniqueid;
				break;
			case self::REFUND:
				return self::REFUND.'-'.$uniqueid;
				break;
		}
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