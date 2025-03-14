<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Oneapi_seamless_service_api extends BaseController {

    public  $game_platform_id,
            $start_time,
            $load,
            $host_name,
            $method, 
            $game_api, 
            $currency,
            $request, 
            $headers, 
            $response_result_id,
            $end_time, $output, 
            $request_method,
			$raw_request,
			$enable_hint
			;

    #error codes from GP
    const SC_OK 							= 'SC_OK';
    const SC_UNKNOWN_ERROR 					= 'SC_UNKNOWN_ERROR';
	const SC_INVALID_REQUEST			 	= 'SC_INVALID_REQUEST';
	const SC_AUTHENTICATION_FAILED 			= 'SC_AUTHENTICATION_FAILED';
	const SC_INVALID_SIGNATURE 				= 'SC_INVALID_SIGNATURE';
	const SC_INVALID_TOKEN 					= 'SC_INVALID_TOKEN';
	const SC_INVALID_GAME 					= 'SC_INVALID_GAME';
	const SC_DUPLICATE_REQUEST 				= 'SC_DUPLICATE_REQUEST';
	const SC_CURRENCY_NOT_SUPPORTED 		= 'SC_CURRENCY_NOT_SUPPORTED';
	const SC_WRONG_CURRENCY 				= 'SC_WRONG_CURRENCY';
	const SC_INSUFFICIENT_FUNDS 			= 'SC_INSUFFICIENT_FUNDS';
	const SC_USER_NOT_EXISTS 				= 'SC_USER_NOT_EXISTS';
	const SC_USER_DISABLED 					= 'SC_USER_DISABLED';
	const SC_TRANSACTION_DUPLICATED 		= 'SC_TRANSACTION_DUPLICATED';
	const SC_TRANSACTION_NOT_EXISTS 		= 'SC_TRANSACTION_NOT_EXISTS';
	const SC_VENDOR_ERROR 					= 'SC_VENDOR_ERROR';
	const SC_UNDER_MAINTENANCE 				= 'SC_UNDER_MAINTENANCE';
	const SC_MISMATCHED_DATA_TYPE 			= 'SC_MISMATCHED_DATA_TYPE';
	const SC_INVALID_RESPONSE 				= 'SC_INVALID_RESPONSE';
	const SC_INVALID_VENDOR 				= 'SC_INVALID_VENDOR';
	const SC_INVALID_LANGUAGE 				= 'SC_INVALID_LANGUAGE';
	const SC_GAME_DISABLED 					= 'SC_GAME_DISABLED';
	const SC_INVALID_PLATFORM 				= 'SC_INVALID_PLATFORM';
	const SC_GAME_LANGUAGE_NOT_SUPPORTED 	= 'SC_GAME_LANGUAGE_NOT_SUPPORTED';
	const SC_GAME_PLATFORM_NOT_SUPPORTED 	= 'SC_GAME_PLATFORM_NOT_SUPPORTED';
	const SC_GAME_CURRENCY_NOT_SUPPORTED 	= 'SC_GAME_CURRENCY_NOT_SUPPORTED';
	const SC_VENDOR_LINE_DISABLED 			= 'SC_VENDOR_LINE_DISABLED';
	const SC_VENDOR_CURRENCY_NOT_SUPPORTED 	= 'SC_VENDOR_CURRENCY_NOT_SUPPORTED';
	const SC_VENDOR_LANGUAGE_NOT_SUPPORTED 	= 'SC_VENDOR_LANGUAGE_NOT_SUPPORTED';
	const SC_VENDOR_PLATFORM_NOT_SUPPORTED 	= 'SC_VENDOR_PLATFORM_NOT_SUPPORTED';
    
	#method
    const METHOD_BALANCE    = 'balance';
    const METHOD_BET  		= 'bet';
	const METHOD_BET_RESULT = 'bet_result';
	const METHOD_ROLLBACK 	= 'rollback';
	const METHOD_ADJUSTMENT = 'adjustment';

	#transaction mode
	const ERROR_MESSAGE = [
		self::SC_OK 							=> "Successful response",
		self::SC_UNKNOWN_ERROR 					=> "Generic status code for unknown errors",
		self::SC_INVALID_REQUEST			 	=> "Wrong/missing parameters sent in request body",
		self::SC_AUTHENTICATION_FAILED 			=> "Authentication failed. X-API-Key is missing or invalid",
		self::SC_INVALID_SIGNATURE 				=> "X-Signature verification failed",
		self::SC_INVALID_TOKEN 					=> "Invalid token on Operator's system",
		self::SC_INVALID_GAME 					=> "Not a valid game",
		self::SC_DUPLICATE_REQUEST 				=> "Duplicate request",
		self::SC_CURRENCY_NOT_SUPPORTED 		=> "Currency is not supported",
		self::SC_WRONG_CURRENCY 				=> "Transaction's currency is different from user's wallet currency",
		self::SC_INSUFFICIENT_FUNDS 			=> "User's wallet does not have enough funds",
		self::SC_USER_NOT_EXISTS 				=> "User does not exists in Operator's system",
		self::SC_USER_DISABLED 					=> "User is disabled and not allowed to place bets",
		self::SC_TRANSACTION_DUPLICATED 		=> "Duplicate transaction Id was sent",
		self::SC_TRANSACTION_NOT_EXISTS 		=> "Corresponding bet transaction cannot be found",
		self::SC_VENDOR_ERROR 					=> "Error encountered on game vendor",
		self::SC_UNDER_MAINTENANCE 				=> "Game is under maintenance",
		self::SC_MISMATCHED_DATA_TYPE 			=> "Invalid data type",
		self::SC_INVALID_RESPONSE 				=> "Invalid response",
		self::SC_INVALID_VENDOR 				=> "Vendor is not supported",
		self::SC_INVALID_LANGUAGE 				=> "Language is not supported",
		self::SC_GAME_DISABLED 					=> "Game is disabled",
		self::SC_INVALID_PLATFORM 				=> "Platform is not supported",
		self::SC_GAME_LANGUAGE_NOT_SUPPORTED 	=> "Game language is not supported",
		self::SC_GAME_PLATFORM_NOT_SUPPORTED 	=> "Game platform is not supported",
		self::SC_GAME_CURRENCY_NOT_SUPPORTED 	=> "Game currency is not supported",
		self::SC_VENDOR_LINE_DISABLED 			=> "Vendor line is disabled",
		self::SC_VENDOR_CURRENCY_NOT_SUPPORTED 	=> "Vendor currency is not supported",
		self::SC_VENDOR_LANGUAGE_NOT_SUPPORTED 	=> "Vendor language is not supported",
		self::SC_VENDOR_PLATFORM_NOT_SUPPORTED 	=> "Vendor platform is not supported",
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','original_seamless_wallet_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index(...$methods){
        $method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('ONEAPI--method : '. $method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'wallet/balance':
				$this->balance();
				break;
			case 'wallet/bet':
				$this->bet();
				break;
			case 'wallet/bet_result':
				$this->bet_result();
				break;
			case 'wallet/rollback':
				$this->rollback();
				break;
			case 'wallet/adjustment':
				$this->adjustment();
				break;
			default:
				$this->utils->debug_log('ONEAPI seamless service: Invalid API Method');
				http_response_code(404);
		}
	}

	public function generatePlayerToken(){
		if(!$this->initialize()) {
			return [
				'success'   => 'error',
			];
		}
		if(!$this->game_api->validateWhiteIP()){
			return [
				'success'   => 'error',
				'message'   => 'invalid ip',
			];
		}

		$this->CI->load->model(array('player_model'));
		$player_username 	= isset($this->request['username']) ? $this->request['username'] : null;
        $player_id 			= $this->CI->player_model->getPlayerIdByUsername($player_username);
		$token			 	= $this->common_token->getPlayerToken($player_id);
		$response =  [
			'username' => $player_username,
			'token'    => $token,
		];

		print_r($response); exit;
	}

	public function initialize(){
		#get vendor/sub provider by gameCode paramater
		$this->game_platform_id = $this->getPlatformId();
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->error_log("ONEAPI SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency = $this->game_api->getCurrency();
		$this->enable_hint = $this->game_api->enable_hint;
		return true;

	}

	private function getPlatformId(){
		$game_platform_id = null;
		$vendor_prefix = null;
		if(!empty($this->request['gameCode']) && $this->request['gameCode']){
			$vendor_prefix = explode('_', $this->request['gameCode'])[0];	
			switch ($vendor_prefix) {
				case 'PP': #PP
					$game_platform_id = ONEAPI_PP_SEAMLESS_GAME_API;
					break;
				case 'GPKBG': #GPKBG
					$game_platform_id = ONEAPI_BGAMING_SEAMLESS_GAME_API;
					break;
				case 'HB': 
					$game_platform_id = ONEAPI_HABANERO_SEAMLESS_GAME_API;
					break;
				case 'EVOP': 
					$game_platform_id = ONEAPI_EVOPLAY_SEAMLESS_GAME_API;
					break;
				case 'EVONTT': 
					$game_platform_id = ONEAPI_NETENT_SEAMLESS_GAME_API;
					break;
				case 'EVORT': 
					$game_platform_id = ONEAPI_REDTIGER_SEAMLESS_GAME_API;
					break;
				case 'EZUGI': 
					$game_platform_id = ONEAPI_EZUGI_SEAMLESS_GAME_API;
					break;
				case 'JDB': 
					$game_platform_id = ONEAPI_JDB_SEAMLESS_GAME_API;
					break;
				case 'QM': 
					$game_platform_id = ONEAPI_QUEENMAKER_SEAMLESS_GAME_API;
					break;
				case 'YB': 
					$game_platform_id = ONEAPI_YEEBET_SEAMLESS_GAME_API;
					break;
				case 'WIFY': 
					$game_platform_id = ONEAPI_WINFINITY_SEAMLESS_GAME_API;
					break;
				case 'ILU': 
					$game_platform_id = ONEAPI_ILOVEU_SEAMLESS_GAME_API;
					break;
				case 'AP': 
					$game_platform_id = ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API;
					break;
				case 'BNG': 
					$game_platform_id = ONEAPI_BNG_SEAMLESS_GAME_API;
					break;
				case 'EVONLC': 
					$game_platform_id = ONEAPI_NLC_SEAMLESS_GAME_API;
					break;
				case 'EVOBTG': 
					$game_platform_id = ONEAPI_BTG_SEAMLESS_GAME_API;
					break;
				case 'JDB-GTF': 
					$game_platform_id = ONEAPI_JDBGTF_SEAMLESS_GAME_API;
					break;
				case 'SPNX': 
					$game_platform_id = ONEAPI_SPINIX_SEAMLESS_GAME_API;
					break;
				case 'SPDG': 
					$game_platform_id = ONEAPI_SPADEGAMING_SEAMLESS_GAME_API;
					break;
				case 'YBNGO': 
					$game_platform_id = ONEAPI_YELLOWBAT_SEAMLESS_GAME_API;
					break;
				case 'RG': 
					$game_platform_id = ONEAPI_RELAXGAMING_SEAMLESS_GAME_API;
					break;
				case 'PNG': 
					$game_platform_id = ONEAPI_PNG_SEAMLESS_GAME_API;
					break;
				case 'HSD': 
					$game_platform_id = ONEAPI_HACKSAW_SEAMLESS_GAME_API;
					break;
				case 'CQ9': 
					$game_platform_id = ONEAPI_CQ9_SEAMLESS_GAME_API;
					break;
				case 'FC': 
					$game_platform_id = ONEAPI_FACHAI_SEAMLESS_GAME_API;
					break;
				case 'SPB': 
					$game_platform_id = ONEAPI_SPRIBE_SEAMLESS_GAME_API;
					break;
				case 'IFG': 
					$game_platform_id = ONEAPI_3OAKS_SEAMLESS_GAME_API;
					break;
				case 'GPKBM': 
					$game_platform_id = ONEAPI_BOOMING_SEAMLESS_GAME_API;
					break;
				case 'GPKSPINO': 
					$game_platform_id = ONEAPI_SPINOMENAL_SEAMLESS_GAME_API;
					break;
				case 'EW': 
					$game_platform_id = ONEAPI_EPICWIN_SEAMLESS_GAME_API;
					break;
				case 'CPG': 
					$game_platform_id = ONEAPI_CPGAMES_SEAMLESS_GAME_API;
					break;
				case 'LIVE22': 
					$game_platform_id = ONEAPI_LIVE22_SEAMLESS_GAME_API;
					break;
				case 'CG': 
					$game_platform_id = ONEAPI_CG_SEAMLESS_GAME_API;
					break;
				case 'DB': 
					$game_platform_id = ONEAPI_DB_SEAMLESS_GAME_API;
					break;
				case 'ALG': 
					$game_platform_id = ONEAPI_ALIZE_SEAMLESS_GAME_API;
					break;
				case 'GPKTBG': 
					$game_platform_id = ONEAPI_TURBOGAMES_SEAMLESS_GAME_API;
					break;
				case 'BB': 
					$game_platform_id = ONEAPI_LIVE88_SEAMLESS_GAME_API;
					break;
				default:
					$this->utils->error_log('ONEAPI seamless service: Vendor not found');
			}
		}else if(!empty($this->request['username']) && $this->request['username']){
			if(!$game_platform_id){
				$allowed_game_api = [
					ONEAPI_PP_SEAMLESS_GAME_API,
					ONEAPI_BGAMING_SEAMLESS_GAME_API,
					ONEAPI_HABANERO_SEAMLESS_GAME_API,
					ONEAPI_EVOPLAY_SEAMLESS_GAME_API,
					ONEAPI_NETENT_SEAMLESS_GAME_API,
					ONEAPI_REDTIGER_SEAMLESS_GAME_API,
					ONEAPI_EZUGI_SEAMLESS_GAME_API,
					ONEAPI_JDB_SEAMLESS_GAME_API,
					ONEAPI_QUEENMAKER_SEAMLESS_GAME_API,
					ONEAPI_YEEBET_SEAMLESS_GAME_API,
					ONEAPI_WINFINITY_SEAMLESS_GAME_API,
					ONEAPI_ILOVEU_SEAMLESS_GAME_API,
					ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API,
					ONEAPI_BNG_SEAMLESS_GAME_API,
					ONEAPI_NLC_SEAMLESS_GAME_API,
					ONEAPI_BTG_SEAMLESS_GAME_API,
					ONEAPI_JDBGTF_SEAMLESS_GAME_API,
					ONEAPI_SPINIX_SEAMLESS_GAME_API,
					ONEAPI_SPADEGAMING_SEAMLESS_GAME_API,
					ONEAPI_YELLOWBAT_SEAMLESS_GAME_API,
					ONEAPI_RELAXGAMING_SEAMLESS_GAME_API,
					ONEAPI_PNG_SEAMLESS_GAME_API,
					ONEAPI_HACKSAW_SEAMLESS_GAME_API,
					ONEAPI_CQ9_SEAMLESS_GAME_API,
					ONEAPI_FACHAI_SEAMLESS_GAME_API,
					ONEAPI_SPRIBE_SEAMLESS_GAME_API,
					ONEAPI_3OAKS_SEAMLESS_GAME_API,
					ONEAPI_BOOMING_SEAMLESS_GAME_API,
					ONEAPI_SPINOMENAL_SEAMLESS_GAME_API,
					ONEAPI_EPICWIN_SEAMLESS_GAME_API,
					ONEAPI_CPGAMES_SEAMLESS_GAME_API,
					ONEAPI_LIVE22_SEAMLESS_GAME_API,
					ONEAPI_CG_SEAMLESS_GAME_API,
					ONEAPI_DB_SEAMLESS_GAME_API,
					ONEAPI_ALIZE_SEAMLESS_GAME_API,
					ONEAPI_TURBOGAMES_SEAMLESS_GAME_API,
					ONEAPI_LIVE88_SEAMLESS_GAME_API
				];
				#get first 4 character of username (game_platform_id)
				$getPrefix =  substr($this->request['username'], 0, 4);
				if(in_array($getPrefix, $allowed_game_api)){
					$game_platform_id = $getPrefix;
				}
			}
		}
		
		return $game_platform_id;
	}

    public function getError($errorCode){
        $error_response = [
			'traceId' => $this->request['traceId'],
            'status'    => $errorCode,
        ];

		if($this->enable_hint && $errorCode == self::SC_INVALID_SIGNATURE){
			$error_response['hint'] = $this->game_api->generateSignature($this->raw_request);
		}

        return $error_response;
    }

    public function balance(){
        $this->utils->debug_log("ONEAPI SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_BALANCE;
		$errorCode 					= self::SC_OK;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['traceId','username', 'currency', 'token'];
		try { 
			if(!$this->initialize()) {
				return $this->throw_error(self::SC_USER_NOT_EXISTS);
                // throw new Exception(self::SC_INVALID_REQUEST);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SC_UNDER_MAINTENANCE);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::SC_INVALID_REQUEST);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::SC_WRONG_CURRENCY);
			}

			if(!$this->validate_signature()){
				throw new Exception(self::SC_INVALID_SIGNATURE);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::SC_INVALID_REQUEST);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['username']);
            if(!$getPlayerStatus){
                throw new Exception(self::SC_USER_NOT_EXISTS);
            }
			

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		if($success){
			$balance = $this->game_api->getPlayerBalanceById($player_id);
            $externalResponse = [
                'traceId' => $this->request['traceId'],
                'status' => $errorCode,
                'data' 	=> 
					[
						'username' 	=> $this->request['username'],
						'currency' 	=> $this->currency,
						'balance' 	=> $balance,
					],
			];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'	=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function bet(){
		$callType 					= self::METHOD_BET;
		$errorCode 					= self::SC_OK;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('ONEAPI-transaction: '.$callType,$this->request);
		try { 
			
			if(!$this->initialize()) {
                throw new Exception(self::SC_INVALID_REQUEST);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SC_UNDER_MAINTENANCE);
            }
			
			if(!$this->validate_currency()){
				throw new Exception(self::SC_WRONG_CURRENCY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::SC_INVALID_REQUEST);
			}
			
			if(!$this->validate_signature()){
				throw new Exception(self::SC_INVALID_SIGNATURE);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::SC_INVALID_REQUEST);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['username']);
            if(!$getPlayerStatus){
                throw new Exception(self::SC_USER_NOT_EXISTS);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'trace_id'					=> isset($this->request['traceId']) ? $this->request['traceId'] : null,
			'username'					=> isset($this->request['username']) ? $this->request['username'] : null,
			'transaction_id'			=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'bet_id'					=> isset($this->request['betId']) ? $this->request['betId'] : null,
			'external_transaction_id'	=> isset($this->request['externalTransactionId']) ? $this->request['externalTransactionId'] : null,
			'amount'					=> isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'currency'					=> isset($this->request['currency']) ? $this->request['currency'] : null,
			'token'						=> isset($this->request['token']) ? $this->request['token'] : null,
			'game_code'					=> isset($this->request['gameCode']) ? $this->request['gameCode'] : null,
			'round_id'					=> isset($this->request['roundId']) ? $this->request['roundId'] : null,
			'timestamp'					=> isset($this->request['timestamp']) ? $this->request['timestamp'] : null,
			'transaction_date'			=> isset($this->request['timestamp']) ? $this->utils->convertTimestampToDateTime($this->request['timestamp']) : $this->utils->getNowForMysql(),
			'trans_type'				=> $callType,
			'external_uniqueid' 		=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
		];

        $params['bet_time'] = isset($this->request['betTime']) ? $this->request['betTime'] : $params['transaction_date'];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ONEAPI-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){	
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("ONEAPI SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
				$externalResponse = [
					'traceId' => $this->request['traceId'],
					'status' => $errorCode,
					'data' 	=> 
						[
							'username' 	=> $this->request['username'],
							'currency' 	=> $this->currency,
							'balance' 	=> $balance,
						],
				];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }


	public function bet_result(){
		$callType 					= self::METHOD_BET_RESULT;
		$errorCode 					= self::SC_OK;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('ONEAPI-transaction: '.$callType,$this->request);
		try { 
			
			if(!$this->initialize()) {
                throw new Exception(self::SC_INVALID_REQUEST);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SC_UNDER_MAINTENANCE);
            }
			
			if(!$this->validate_currency()){
				throw new Exception(self::SC_WRONG_CURRENCY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::SC_INVALID_REQUEST);
			}

			if(!$this->validate_signature()){
				throw new Exception(self::SC_INVALID_SIGNATURE);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::SC_INVALID_REQUEST);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['username']);
            if(!$getPlayerStatus){
                throw new Exception(self::SC_USER_NOT_EXISTS);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			"trace_id"					=> isset($this->request['traceId']) ? $this->request['traceId'] : null,
			"username"					=> isset($this->request['username']) ? $this->request['username'] : null,
			"transaction_id"			=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			"bet_id"					=> isset($this->request['betId']) ? $this->request['betId'] : null,
			"external_transaction_id"	=> isset($this->request['externalTransactionId']) ? $this->request['externalTransactionId'] : null,
			"round_id"					=> isset($this->request['roundId']) ? $this->request['roundId'] : null,
			"bet_amount"				=> isset($this->request['betAmount']) ? $this->game_api->gameAmountToDB($this->request['betAmount']) : 0,
			"win_amount"				=> isset($this->request['winAmount']) ? $this->game_api->gameAmountToDB($this->request['winAmount']) : 0,
			"effective_turnover" 		=> isset($this->request['effectiveTurnover']) ? $this->game_api->gameAmountToDB($this->request['effectiveTurnover']) : null,
			"winloss" 					=> isset($this->request['winLoss']) ? $this->game_api->gameAmountToDB($this->request['winLoss']) : null,
			"jackpot_amount"			=> isset($this->request['jackpotAmount']) ? $this->game_api->gameAmountToDB($this->request['jackpotAmount']) : 0,
			"result_type" 				=> isset($this->request['resultType']) ? $this->request['resultType'] : null,
			"is_freespin" 				=> isset($this->request['isFreespin']) ? $this->request['isFreespin'] : null,
			"is_endround" 				=> isset($this->request['isEndRound']) ? $this->request['isEndRound'] : null,
			"currency"					=> isset($this->request['currency']) ? $this->request['currency'] : null,
			"token"						=> isset($this->request['token']) ? $this->request['token'] : null,
			"game_code"					=> isset($this->request['gameCode']) ? $this->request['gameCode'] : null,
			"bet_time" 					=> isset($this->request['betTime']) ? $this->utils->convertTimestampToDateTime($this->request['betTime']) : $this->utils->getNowForMysql(),
			"settled_time" 				=> isset($this->request['settledTime']) ? $this->utils->convertTimestampToDateTime($this->request['settledTime']) : $this->utils->getNowForMysql(),
			'trans_type'				=> $callType,
			'external_uniqueid' 		=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
		];

        $params['transaction_date'] = $params['bet_time'];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ONEAPI-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("ONEAPI SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
				$externalResponse = [
					'traceId' => $this->request['traceId'],
					'status' => $errorCode,
					'data' 	=> 
						[
							'username' 	=> $this->request['username'],
							'currency' 	=> $this->currency,
							'balance' 	=> $balance,
						],
				];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function rollback(){
		$callType 					= self::METHOD_ROLLBACK;
		$errorCode 					= self::SC_OK;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('ONEAPI-transaction: '.$callType,$this->request);
		try { 
			
			if(!$this->initialize()) {
                throw new Exception(self::SC_INVALID_REQUEST);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SC_UNDER_MAINTENANCE);
            }
			
			if(!$this->validate_currency()){
				throw new Exception(self::SC_WRONG_CURRENCY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::SC_INVALID_REQUEST);
			}

			if(!$this->validate_signature()){
				throw new Exception(self::SC_INVALID_SIGNATURE);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::SC_INVALID_REQUEST);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['username']);
            if(!$getPlayerStatus){
                throw new Exception(self::SC_USER_NOT_EXISTS);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			"trace_id"					=> isset($this->request['traceId']) ? $this->request['traceId'] : null,
			"username"					=> isset($this->request['username']) ? $this->request['username'] : null,
			"transaction_id"			=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			"bet_id"					=> isset($this->request['betId']) ? $this->request['betId'] : null,
			"external_transaction_id"	=> isset($this->request['externalTransactionId']) ? $this->request['externalTransactionId'] : null,
			"round_id"					=> isset($this->request['roundId']) ? $this->request['roundId'] : null,
			"currency"					=> isset($this->request['currency']) ? $this->request['currency'] : null,
			"game_code"					=> isset($this->request['gameCode']) ? $this->request['gameCode'] : null,
			"timestamp"					=> isset($this->request['timestamp']) ? $this->request['timestamp'] : null,
			'trans_type'				=> $callType,
			'external_uniqueid' 		=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ONEAPI-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("ONEAPI SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
				$externalResponse = [
					'traceId' => $this->request['traceId'],
					'status' => $errorCode,
					'data' 	=> 
						[
							'username' 	=> $this->request['username'],
							'currency' 	=> $this->currency,
							'balance' 	=> $balance,
						],
				];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function adjustment(){
		$callType 					= self::METHOD_ADJUSTMENT;
		$errorCode 					= self::SC_OK;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('ONEAPI-transaction: '.$callType,$this->request);
		try { 
			
			if(!$this->initialize()) {
                throw new Exception(self::SC_INVALID_REQUEST);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SC_UNDER_MAINTENANCE);
            }
			
			if(!$this->validate_currency()){
				throw new Exception(self::SC_WRONG_CURRENCY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::SC_INVALID_REQUEST);
			}

			if(!$this->validate_signature()){
				throw new Exception(self::SC_INVALID_SIGNATURE);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::SC_INVALID_REQUEST);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['username']);
            if(!$getPlayerStatus){
                throw new Exception(self::SC_USER_NOT_EXISTS);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			"amount"					=> isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : null,
			"trace_id"					=> isset($this->request['traceId']) ? $this->request['traceId'] : null,
			"username"					=> isset($this->request['username']) ? $this->request['username'] : null,
			"transaction_id"			=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			"bet_id"					=> isset($this->request['betId']) ? $this->request['betId'] : null,
			"external_transaction_id"	=> isset($this->request['externalTransactionId']) ? $this->request['externalTransactionId'] : null,
			"round_id"					=> isset($this->request['roundId']) ? $this->request['roundId'] : null,
			"currency"					=> isset($this->request['currency']) ? $this->request['currency'] : null,
			"game_code"					=> isset($this->request['gameCode']) ? $this->request['gameCode'] : null,
			"timestamp"					=> isset($this->request['timestamp']) ? $this->request['timestamp'] : null,
			'trans_type'				=> $callType,
			'external_uniqueid' 		=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ONEAPI-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("ONEAPI SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
				$externalResponse = [
					'traceId' => $this->request['traceId'],
					'status' => $errorCode,
					'data' 	=> 
						[
							'username' 	=> $this->request['username'],
							'currency' 	=> $this->currency,
							'balance' 	=> $balance,
						],
				];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function validate_currency(){
		$request_currency = isset($this->request['currency']) ? $this->request['currency'] : null;
		if(strtolower($request_currency) == strtolower($this->currency)){
			return true;
		}
		return false;
	}

	public function validate_signature(){
		$generated_signature = $this->game_api->generateSignature($this->raw_request);
		$request_signature = isset($this->headers['X-Signature']) ? $this->headers['X-Signature'] : null;
		if($generated_signature == $request_signature){
			return true;
		}
		return false;
	}

	public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('ONEAPI-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->gameAmountToDB($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$previousTableName				= $this->game_api->getTransactionsPreviousTable();
		$game_code 						= isset($this->request['gameCode']) ? $this->request['gameCode'] : null;
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$game_code);
		$flag_update_on_previous_table = false;
		if($transaction_type == self::METHOD_BET){
			$amount 		= $data['amount'] * -1; #getting the negative betting value
			$data['bet_amount'] = $data['amount'];

			$isExistWhereParams = [
				'external_uniqueid' => $data['external_uniqueid'],
				'game_platform_id' => $this->game_platform_id
			];

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
			if(empty($existingTrans) && $this->checkPreviousMonth()){
				$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
			}

			if($existingTrans){
				return $this->resultForAdjustWallet(self::SC_OK, $playerId);
			}
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::SC_INSUFFICIENT_FUNDS, $playerId);
			}

			if($balance < $data['amount']){
				return $this->resultForAdjustWallet(self::SC_INSUFFICIENT_FUNDS, $playerId);
			}

			$data['status'] = GAME_LOGS::STATUS_PENDING;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}

			#implement is_end
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				$this->wallet_model->setGameProviderIsEndRound(false);
			}

        }else if($transaction_type == self::METHOD_BET_RESULT){

			$isExistWhereParams = [
				'external_uniqueid' => $data['external_uniqueid'],
				'game_platform_id' => $this->game_platform_id
			];

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
			if(empty($existingTrans) && $this->checkPreviousMonth()){
				$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
			}
			
			if($existingTrans){
				return $this->resultForAdjustWallet(self::SC_OK, $playerId);
			}
			
			#validate negative amounts for win amount
			if($data['win_amount'] < 0){
				return $this->resultForAdjustWallet(self::SC_INVALID_REQUEST, $playerId);
			}

			// if($data['result_type'] == 'END' || $data['result_type'] == 'LOSE'){
			if(in_array(strtoupper($data['result_type']), ['WIN','LOSE','END'])){
				if(in_array(strtoupper($data['result_type']), ['END','LOSE'])){
					$amount = 0;
				}else{
					$amount = $data['win_amount'];
					if(isset($data['jackpot_amount']) && $data['jackpot_amount'] > 0){
						$amount = $amount + $data['jackpot_amount'];
					}
				}

				#get bet transaction
				$bet_transaction_where = [
					'bet_id' => $data['bet_id'],
					'game_platform_id' => $this->game_platform_id
				];

				$bet_select_column = [
					'bet_id', 'status', 'bet_amount'
				];

				$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($tableName, $bet_transaction_where, $bet_select_column);
				if(empty($bet_transaction_details) && $this->checkPreviousMonth()){
					$bet_transaction_details  = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($previousTableName, $bet_transaction_where, $bet_select_column);
					if(!empty($bet_transaction_details)){
						$flag_update_on_previous_table = true;
					}
				}

				#check if bet transaction existing 
				if(empty($bet_transaction_details)){
					return $this->resultForAdjustWallet(self::SC_TRANSACTION_NOT_EXISTS, $playerId);
				}
				
				#check if transaction already rollback
				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
					return $this->resultForAdjustWallet(self::SC_INVALID_REQUEST, $playerId);
				}

				$flag_where_update = [
					'bet_id' => $data['bet_id'],
					'game_platform_id' => $this->game_platform_id
				];

				$flag_set_update = [
					'status' => Game_logs::STATUS_SETTLED,
					'bet_amount' => $bet_transaction_details['bet_amount'],
					'effective_turnover' => $data['effective_turnover'],
					'bet_time' => $data['bet_time'],
					'settled_time' => $data['settled_time'],
					// 'winloss' => $data['winloss'],
				];

				// if(isset($data['win_amount']) && $data['win_amount'] > 0){
				// 	$flag_set_update['win_amount'] = $data['win_amount'];
				// }
				$table_to_be_updated = $flag_update_on_previous_table ? $previousTableName : $tableName;
				$this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($table_to_be_updated, $flag_where_update, $flag_set_update);
				if(in_array(strtoupper($data['result_type']), ['WIN'])){
					$data['bet_amount'] = isset($bet_transaction_details['bet_amount']) ? $bet_transaction_details['bet_amount'] : 0;
				}
			}else{
				if($data['bet_amount'] < 0){
					return $this->resultForAdjustWallet(self::SC_INSUFFICIENT_FUNDS, $playerId);
				}

				if($balance < $data['bet_amount']){
					return $this->resultForAdjustWallet(self::SC_INSUFFICIENT_FUNDS, $playerId);
				}

				#get the total to be deducted or increased
				$amount = $data['win_amount'] - $data['bet_amount'];

				if(isset($data['jackpot_amount']) && $data['jackpot_amount'] > 0){
					$amount = $amount + $data['jackpot_amount'];
				}

				if(strtoupper($data['result_type']) == 'BET_WIN'){
					#update bet transaction win_amount
					$flag_where_update = [
						'round_id' => $data['round_id'],
						'trans_type' => self::METHOD_BET,
						'game_platform_id' => $this->game_platform_id
					];

					$flag_set_update = [];
					if(isset($data['win_amount']) && $data['win_amount'] > 0){
						$flag_set_update['win_amount'] = $data['win_amount'];
						$this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($tableName, $flag_where_update, $flag_set_update);
					}
				}
			}
			

			$data['win_amount'] 	= $data['win_amount'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
			}

			#implement isEndRound
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				$this->wallet_model->setGameProviderIsEndRound(isset($data['is_endround']) && $data['is_endround'] ? true : false);
			}

		}else if($transaction_type == self::METHOD_ROLLBACK){

			$isExistWhereParams = [
				'external_uniqueid' => $data['external_uniqueid'],
				'game_platform_id' => $this->game_platform_id
			];

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
			if(empty($existingTrans) && $this->checkPreviousMonth()){
				$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
			}

			if($existingTrans){
				return $this->resultForAdjustWallet(self::SC_OK, $playerId);
			}
			
			#get bet transaction
			$bet_transaction_where = [
				'bet_id' => $data['bet_id'],
				'game_platform_id' => $this->game_platform_id
			];

			$bet_select_column = [
				'bet_id', 'status','bet_amount','win_amount'
			];

			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($tableName, $bet_transaction_where, $bet_select_column);
			if(empty($bet_transaction_details) && $this->checkPreviousMonth()){
				$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($previousTableName, $bet_transaction_where, $bet_select_column);
				$flag_update_on_previous_table = true;
			}

			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::SC_TRANSACTION_NOT_EXISTS, $playerId);
			}

			#check if transaction already rollback
			#should return success if already cancelled 
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::SC_OK, $playerId);
			}

			#NOTE: settled transation can still be rollback as per GP
			$bet_amount = isset($bet_transaction_details['bet_amount']) ? $bet_transaction_details['bet_amount'] : 0;
			// $win_amount = isset($bet_transaction_details['win_amount']) ? $bet_transaction_details['win_amount'] : 0;
			
			$query_total_win_where_data = [
				'bet_id' => $data['bet_id'],
				'round_id' => $data['round_id'],
				'game_platform_id' => $this->game_platform_id,
				'player_id' => $playerId
			];

			$query_total_win = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($tableName, $query_total_win_where_data, ['sum(win_amount) as total_win']);
			$win_amount = isset($query_total_win['total_win']) ? $query_total_win['total_win'] : 0;

			$result_amount = $win_amount - $bet_amount;
			if($result_amount > 0){
				#check balance 
				$amount = $result_amount * -1; #gets the negative amount to deduct
				if($result_amount > $balance){
					return $this->resultForAdjustWallet(self::SC_INSUFFICIENT_FUNDS, $playerId);
				}
			}else{
				$amount = abs($result_amount); #gets the positive amount for refund
			}

		
			$flag_where_update = [
				'bet_id' => $data['bet_id'],
				'game_platform_id' => $this->game_platform_id,
				'player_id' => $playerId
			];

			$flag_set_update = [
				'status' => Game_logs::STATUS_CANCELLED,
			];

			$table_to_be_updated = $flag_update_on_previous_table ? $previousTableName : $tableName;
			$this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($table_to_be_updated, $flag_where_update, $flag_set_update);

			$data['status'] = GAME_LOGS::STATUS_CANCELLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
			}

			#implement isEndRound
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				$this->wallet_model->setGameProviderIsEndRound(false);
			}
		}else if($transaction_type == self::METHOD_ADJUSTMENT){
			$isExistWhereParams = [
				'external_uniqueid' => $data['external_uniqueid'],
				'game_platform_id' => $this->game_platform_id
			];

			$amount = $data['amount'];
			$data['amount'] = abs($data['amount']); 

			if($data['amount'] > $balance){
				return $this->resultForAdjustWallet(self::SC_INSUFFICIENT_FUNDS, $playerId);
			}

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
			if(empty($existingTrans) && $this->checkPreviousMonth()){
				$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
			}

			if($existingTrans){
				return $this->resultForAdjustWallet(self::SC_OK, $playerId);
			}

			#get bet transaction
			$round_transaction_where = [
				'round_id' => $data['round_id'],
				'game_platform_id' => $this->game_platform_id
			];

			$round_select_column = [
				'round_id', 'status','bet_amount','win_amount'
			];

			$round_transaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($tableName, $round_transaction_where, $round_select_column);
			if(empty($round_transaction) && $this->checkPreviousMonth()){
				$round_transaction  = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($previousTableName, $round_transaction_where, $round_select_column);
			}
			
			#check if round transaction existing 
			if(empty($round_transaction)){
				return $this->resultForAdjustWallet(self::SC_TRANSACTION_NOT_EXISTS, $playerId);
			}
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('ONEAPI-amounts', [$beforeBalance, $afterBalance, $amount]);

		$amount_operator = '>';
		$enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
		if(!empty($enabled_remote_wallet_client_on_currency)){
			if($this->utils->isEnabledRemoteWalletClient()){
				$amount_operator = '>=';
			}
		}

		if($amount <> 0 && $amount_operator == '>' || $amount == 0 && $amount_operator == '>='){
			if($this->utils->compareResultFloat($amount, $amount_operator, 0)){ 	
				#credit
				$data['balance_adjustment_method'] 	= 'credit';
				$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
				if(!$response){
					return $this->resultForAdjustWallet(self::SC_UNKNOWN_ERROR, $playerId);
				}
				$afterBalance 						= $beforeBalance + $amount;
				$this->utils->debug_log('ONEAPI', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			}else{	
				#debit
				$amount 							= abs($amount);
				$data['balance_adjustment_method'] 	= 'debit';
				$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
				$afterBalance 						= $beforeBalance - $amount;
				$this->utils->debug_log('ONEAPI', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
				if(!$response){
					return $this->resultForAdjustWallet(self::SC_UNKNOWN_ERROR, $playerId);
				}
			}
		}

		#additional transaction info for saving 
		$data['extra_info']				 	= $this->raw_request;
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
		
		$this->utils->debug_log('ONEAPI--adjust-wallet', $data);

		$insertTransaction = $this->original_seamless_wallet_transactions->insertTransactionData($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::SC_OK, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
	}


	public function resultForAdjustWallet($code, $player_id = null, $before_balance = null, $after_balance = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code === self::SC_OK ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $code === self::SC_OK ? $current_balance : null,
				'before_balance'  => $code === self::SC_OK ? $before_balance : null,
				'after_balance'   => $code === self::SC_OK ? $after_balance : null,
			];
		$this->utils->debug_log("ONEAPI--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("ONEAPI SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);
        
        #all response should be 200 http status
		$httpStatusCode =  200;

		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### ONEAPI SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
	
		return $output;
	}

	public function throw_error($errorCode){
		$error_response = [
			'traceId' => $this->request['traceId'],
            'status'    => $errorCode,
        ];
        #all response should be 200 http status
		$httpStatusCode =  200;
		$this->output->set_status_header($httpStatusCode);
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($error_response));
		return $output;
	}

	//default external response template
	public function externalQueryResponse(){
        return ['data'=>[],'error'=>null];
	}

	
	public function getBaseUrl(){
		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$base_url = $protocol . '://' . $host;

		return $base_url;
	}
    
	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	public function parseRequest(){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$request_json = file_get_contents('php://input');
			$this->utils->debug_log("ONEAPI SEAMLESS SERVICE raw:", $request_json);
			$this->raw_request = $request_json; 
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("ONEAPI SEAMLESS SERVICE raw parsed:", $request_json);
				$this->request = $request_json;
			}
		}else{
			$this->request = $this->input->get();
		}
		return $this->request;
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

	public function getPlayerByToken($token){
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		return [true, $player, $player->game_username, $player->username];
	}

	public function isParamsRequired($params, $required) {
		if(empty($required)){
			return true;
		}
		if($params && $required){
			# Check if all required parameters are present in the $params array
			foreach ($required as $param) {
				if (!array_key_exists($param, $params)) {
					return false; 
				}
			}
			return true; 
		}
	}

	public function checkPreviousMonth(){
		if(date('j', $this->utils->getTimestampNow()) <= $this->game_api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
			return true;
		}

		return false;
	}

}///END OF FILE////////////