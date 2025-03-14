<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Aviatrix_seamless_service_api extends BaseController {

    public  $game_platform_id,
            $start_time,
            $load,
            $host_name,
            $method, 
            $game_api, 
            $currency,
            $cid,
            $request, 
            $headers, 
            $response_result_id,
            $end_time, $output, 
            $request_method,
			$allow_invalid_signature,
			$key,
			$raw_request,
			$enable_hint
			;

    #error codes from GP
    const INVALID_SIGNATURE         = 'INVALID_SIGNATURE';
    const INVALID_SESSION_TOKEN     = 'INVALID_SESSION_TOKEN';
    const INVALID_REQUEST           = 'INVALID_REQUEST';
    const INVALID_TRANSACTION       = 'INVALID_TRANSACTION';
    const INVALID_PLAYER_CURRENCY   = 'INVALID_PLAYER_CURRENCY';
    const SESSION_TOKEN_EXPIRED     = 'SESSION_TOKEN_EXPIRED';
    const PLAYER_BANNED             = 'PLAYER_BANNED';
    const INSUFFICIENT_BALANCE      = 'INSUFFICIENT_BALANCE';
    const BET_LIMIT_EXCEEDED        = 'BET_LIMIT_EXCEEDED';
    const PLATFORM_NOT_FOUND        = 'PLATFORM_NOT_FOUND';
    const PRODUCT_NOT_FOUND         = 'PRODUCT_NOT_FOUND';
    const PLAYER_NOT_FOUND          = 'PLAYER_NOT_FOUND';
    const BET_NOT_FOUND             = 'BET_NOT_FOUND';
    const SERVICE_OVERLOADED        = 'SERVICE_OVERLOADED';
    const METHOD_NOT_SUPPORTED      = 'METHOD_NOT_SUPPORTED';
    const SERVICE_UNAVAILABLE       = 'SERVICE_UNAVAILABLE';

    #additional error codes
    const SUCCESS                   = 'SUCCESS';
    const SYSTEM_ERROR              = 'SYSTEM_ERROR';
    const IP_NOT_ALLOWED            = 'IP_NOT_ALLOWED';
	const INVALID_AMOUNT			= 'INVALID_AMOUNT';
	const ALREADY_SETTLED			= 'ALREADY_SETTLED';
	const ALREADY_CANCELLED			= 'ALREADY_CANCELLED';
  
	#method
    const METHOD_HEALTH    	   = 'health';
    const METHOD_PLAYER_INFO   = 'playerInfo';
    const METHOD_BET  		   = 'bet';
    const METHOD_WIN  		   = 'win';
	const METHOD_PROMO_WIN	   = 'promoWin';

    #error messages
	const ERROR_MESSAGE = [
        self::INVALID_SIGNATURE         => 'Invalid authentication signature',
        self::INVALID_SESSION_TOKEN     => 'Invalid session token',
        self::INVALID_REQUEST           => 'Invalid request',
        self::INVALID_TRANSACTION       => 'Invalid transaction',
        self::INVALID_PLAYER_CURRENCY   => 'Invalid player currency',
        self::SESSION_TOKEN_EXPIRED     => 'Session token expired',
        self::PLAYER_BANNED             => 'Player banned',
        self::INSUFFICIENT_BALANCE      => 'Insufficient balance',
        self::BET_LIMIT_EXCEEDED        => 'Bet limit exceeded',
        self::PLATFORM_NOT_FOUND        => 'Platform not found',
        self::PRODUCT_NOT_FOUND         => 'Product not found',
        self::PLAYER_NOT_FOUND          => 'Player not found',
        self::BET_NOT_FOUND             => 'Bet not found',
        self::SERVICE_OVERLOADED        => 'Service overloaded',
        self::METHOD_NOT_SUPPORTED      => 'Method not supported',
        self::SERVICE_UNAVAILABLE       => 'Service unavailable',
		
        #additional error messages
		self::SUCCESS                   => 'Success',
        self::SYSTEM_ERROR              => 'System Error',
        self::IP_NOT_ALLOWED            => 'IP address not allowed',
        self::INVALID_AMOUNT            => 'Invalid amount',
        self::ALREADY_SETTLED           => 'Already settled',
        self::ALREADY_CANCELLED         => 'Already cancelled',

	];

    #error messages
	const HTTP_CODE = [
        self::INVALID_SIGNATURE         => '400',
        self::INVALID_SESSION_TOKEN     => '400',
        self::INVALID_REQUEST           => '400',
        self::INVALID_TRANSACTION       => '400',
        self::INVALID_PLAYER_CURRENCY   => '400',
        self::SESSION_TOKEN_EXPIRED     => '401',
        self::PLAYER_BANNED             => '403',
        self::INSUFFICIENT_BALANCE      => '403',
        self::BET_LIMIT_EXCEEDED        => '403',
        self::PLATFORM_NOT_FOUND        => '404',
        self::PRODUCT_NOT_FOUND         => '404',
        self::PLAYER_NOT_FOUND          => '404',
        self::BET_NOT_FOUND             => '404',
        self::SERVICE_OVERLOADED        => '429',
        self::METHOD_NOT_SUPPORTED      => '501',
        self::SERVICE_UNAVAILABLE       => '503',
		
        #additional error messages
		self::SUCCESS                   => '200',
        self::SYSTEM_ERROR              => '500',
        self::IP_NOT_ALLOWED            => '500',
        self::INVALID_AMOUNT            => '500',
        self::ALREADY_SETTLED           => '500',
        self::ALREADY_CANCELLED         => '500',
        
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

	public function index(... $methods){
		$method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('AVIATRIX--method : '. $this->request_method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'health':
				$this->health();
                break;
            case 'playerInfo':
                $this->playerInfo();
				break;
            case 'bet':
                $this->bet();
                break;
			case 'win':
				$this->win();
				break;
			case 'transactions/promoWin':
				$this->promoWin();
				break;
			default:
				$this->utils->debug_log('AVIATRIX seamless service: Invalid API Method');
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
		$this->game_platform_id = AVIATRIX_SEAMLESS_GAME_API;
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("AVIATRIX SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency = $this->game_api->getCurrency();
        $this->cid 	    = $this->game_api->cid;
		$this->allow_invalid_signature = $this->game_api->allow_invalid_signature;
		$this->key		= $this->game_api->key;
		$this->enable_hint = $this->game_api->enable_hint;
		return true;
	}

    public function getError($errorCode){
        $error_response = [
            'message' => self::ERROR_MESSAGE[$errorCode],
        ];
		if($this->enable_hint && $errorCode == self::INVALID_SIGNATURE){
			$error_response['hint'] = $this->generateSignature();
		}
        return $error_response;
    }

    public function health(){
        $this->utils->debug_log("AVIATRIX SEAMLESS SERVICE:" .$this->method);	
		$externalResponse   = $this->externalQueryResponse();	
		$callType 		    = self::METHOD_HEALTH;
		$errorCode 		    = self::SUCCESS;
		$externalResponse   = [];
		$success            = true;
        $this->initialize();
        
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, []);
    }

    public function playerInfo(){
        $this->utils->debug_log("AVIATRIX SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_PLAYER_INFO;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['cid','sessionToken','timestamp'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}

            if(!$this->validate_cid()){
				throw new Exception(self::INVALID_PLAYER_CURRENCY);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::INVALID_REQUEST);
			}

			if(!$this->isValidSignature()){
				throw new Exception(self::INVALID_SIGNATURE);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['sessionToken']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_SESSION_TOKEN);
            }

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
            $externalResponse = [
                'playerId'  => $game_username,
                'balance'   => $balance,
                'currency'  => strtoupper($this->currency),
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
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['betId','cid','sessionToken','playerId','productId','txId','roundId','amount','currency','timestamp'];
		$this->utils->debug_log('AVIATRIX-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_PLAYER_CURRENCY);
			}

            if(!$this->validate_cid()){
				throw new Exception(self::PLATFORM_NOT_FOUND);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::INVALID_REQUEST);
			}

			if(!$this->isValidSignature()){
				throw new Exception(self::INVALID_SIGNATURE);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['sessionToken']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_SESSION_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		#rebuilding parameters
		$params = [
            'bet_id'             => isset($this->request['betId']) ? $this->request['betId'] : null,
            'cid'                => isset($this->request['cid']) ? $this->request['cid'] : null,
            'session_token'      => isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
            'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
            'product_id'         => isset($this->request['productId']) ? $this->request['productId'] : null,
            'tx_id'              => isset($this->request['txId']) ? $this->request['txId'] : null,
            'round_id'           => isset($this->request['roundId']) ? $this->request['roundId'] : null,
            'market'           	 => isset($this->request['market']) ? $this->request['market'] : null,
            'outcome'            => isset($this->request['outcome']) ? $this->request['outcome'] : null,
            'specifier'          => isset($this->request['specifier']) ? $this->request['specifier'] : null,
            'odds'           	 => isset($this->request['odds']) ? $this->request['odds'] : null,
            'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['amount']) : null,
            'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
            'round_closed'       => isset($this->request['roundClosed']) ? $this->request['roundClosed'] : null,
            'timestamp'          => isset($this->request['timestamp']) ? $this->request['timestamp'] : null,
            'bonus_id'         	 => isset($this->request['bonusId']) ? $this->request['bonusId'] : null,
			'trans_type'         => $callType,
			'external_uniqueid'  => isset($this->request['txId']) ? md5($this->request['txId']) : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("AVIATRIX-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				$errorCode 			  = $adjustWalletResponse['code'];
				$this->utils->debug_log("AVIATRIX-lockAndTransForPlayerBalance-adjustWalletResponse", $adjustWalletResponse);
				if(!$adjustWalletResponse['success']){
					return false;	
				}
				$balance 	= $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("AVIATRIX SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'processedTxId' 	=> $this->request['txId'],
				'createdAt' 		=> $this->getFormattedDate(),
				'balance' 	        => $balance,
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id' => $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function win(){
		$callType 					= isset($this->request['operation']) ? $this->request['operation'] : self::METHOD_WIN;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['betId','cid','sessionToken','playerId','productId','txId','roundId','amount','currency','timestamp','operation'];
		$this->utils->debug_log('AVIATRIX-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_PLAYER_CURRENCY);
			}

            if(!$this->validate_cid()){
				throw new Exception(self::PLATFORM_NOT_FOUND);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::INVALID_REQUEST);
			}

			if(!$this->isValidSignature()){
				throw new Exception(self::INVALID_SIGNATURE);
			}


            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerId']);
            if(!$getPlayerStatus){
                throw new Exception(self::PLAYER_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		#rebuilding parameters
		$params = [
            'bet_id'             => isset($this->request['betId']) ? $this->request['betId'] : null,
            'cid'                => isset($this->request['cid']) ? $this->request['cid'] : null,
            'session_token'      => isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
            'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
            'product_id'         => isset($this->request['productId']) ? $this->request['productId'] : null,
            'tx_id'              => isset($this->request['txId']) ? $this->request['txId'] : null,
            'round_id'           => isset($this->request['roundId']) ? $this->request['roundId'] : null,
            'market'           	 => isset($this->request['market']) ? $this->request['market'] : null,
            'outcome'            => isset($this->request['outcome']) ? $this->request['outcome'] : null,
            'specifier'          => isset($this->request['specifier']) ? $this->request['specifier'] : null,
            'odds'           	 => isset($this->request['odds']) ? $this->request['odds'] : null,
            'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['amount']) : null,
            'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
            'round_closed'       => isset($this->request['roundClosed']) ? $this->request['roundClosed'] : null,
            'timestamp'          => isset($this->request['timestamp']) ? $this->request['timestamp'] : null,
            'bonus_id'         	 => isset($this->request['bonusId']) ? $this->request['bonusId'] : null,
            'operation'          => isset($this->request['operation']) ? $this->request['operation'] : null,
			'trans_type'         => self::METHOD_WIN,
			'external_uniqueid'  => isset($this->request['txId']) ? md5($this->request['txId']) : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("AVIATRIX-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet(self::METHOD_WIN, $player, $params);
				$errorCode 			  = $adjustWalletResponse['code'];
				if(!$adjustWalletResponse['success']){
					return false;	
				}
				
				$balance 	= $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("AVIATRIX SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'processedTxId' 	=> $this->request['txId'],
				'createdAt' 		=> $this->getFormattedDate(),
				'balance' 	        => $balance,
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id' => $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function promoWin(){
		$callType 					= self::METHOD_PROMO_WIN;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['cid','sessionToken','playerId','productId','txId','amount','currency'];
		$this->utils->debug_log('AVIATRIX-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_ALLOWED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_PLAYER_CURRENCY);
			}

            if(!$this->validate_cid()){
				throw new Exception(self::PLATFORM_NOT_FOUND);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::INVALID_REQUEST);
			}

			if(!$this->isValidSignature()){
				throw new Exception(self::INVALID_SIGNATURE);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerId']);
            if(!$getPlayerStatus){
                throw new Exception(self::PLAYER_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		#rebuilding parameters
		$params = [
            'cid'                => isset($this->request['cid']) ? $this->request['cid'] : null,
            'session_token'      => isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
            'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
            'product_id'         => isset($this->request['productId']) ? $this->request['productId'] : null,
            'tx_id'              => isset($this->request['txId']) ? $this->request['txId'] : null,
            'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['amount']) : null,
            'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
			'trans_type'         => $callType,
			'external_uniqueid'  => isset($this->request['txId']) ? md5($this->request['txId']) : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("AVIATRIX-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				$errorCode 			  = $adjustWalletResponse['code'];
				$this->utils->debug_log("AVIATRIX-lockAndTransForPlayerBalance-adjustWalletResponse", $adjustWalletResponse);
				if(!$adjustWalletResponse['success']){
					return false;	
				}
				$balance 	= $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("AVIATRIX SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'processedTxId' 	=> $this->request['txId'],
				'createdAt' 		=> $this->getFormattedDate(),
				'balance' 	        => $balance,
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id' => $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function getFormattedDate(){
		$date = new DateTime();
		$formattedDate = $date->format('Y-m-d\TH:i:s.') . sprintf('%03d', (int)($date->format('u') / 1000)) . 'Z';
		return $formattedDate;
	}

	public function validate_currency(){
		$request_currency = isset($this->request['currency']) ? $this->request['currency'] : null;
		if(strtolower($request_currency) == strtolower($this->currency)){
			return true;
		}
		return false;
	}

    public function validate_cid(){
		$cid = isset($this->request['cid']) ? $this->request['cid'] : null;
		if($cid == $this->cid){
			return true;
		}
		return false;
	}

	public function isValidSignature(){
		if($this->allow_invalid_signature){
			return true;
		}
		if($this->headers['X-Auth-Signature'] == $this->generateSignature()){
			return true;
		}
		return false;
	}

	public function generateSignature(){
		$signature = hash_hmac('md5', $this->raw_request, $this->key, true);
		return base64_encode($signature);
	}

    public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('AVIATRIX-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->gameAmountToDBTruncateNumber($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$game_code 						= isset($this->request['productId']) ? $this->request['productId'] : null;
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$game_code);
		
		$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, ['external_uniqueid' => $data['external_uniqueid']]);
		if(!in_array($transaction_type, [self::METHOD_BET, self::METHOD_PROMO_WIN])){
            $bet_record = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, ['bet_id'=> $data['bet_id']],['external_uniqueid', 'bet_id', 'round_id']);

			if( !empty( $bet_record ) ){
				$related_unique_id = "game-". $this->game_platform_id .'-'. $bet_record['external_uniqueid'];
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_unique_id);
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
				$this->wallet_model->setGameProviderIsEndRound(true);
			}
		}

		if($transaction_type == self::METHOD_BET){
			$amount = $data['amount'] * -1; #getting the negative betting value
			$data['bet_amount'] = $data['amount'];
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_AMOUNT, $playerId);
			}
			if($balance < $data['amount']){
				return $this->resultForAdjustWallet(self::INSUFFICIENT_BALANCE, $playerId);
			}
			$data['status'] = GAME_LOGS::STATUS_PENDING;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}
			$this->wallet_model->setGameProviderIsEndRound(false);

        }else if($transaction_type == self::METHOD_WIN && $this->request['operation'] == 'SettleBet'){
			$amount = $data['amount'];
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			#get bet transaction
			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, ['bet_id' => $data['bet_id']]);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::BET_NOT_FOUND, $playerId);
			}


			#check if transaction already settled
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::ALREADY_CANCELLED, $playerId);
			}

			$flag_where_update = [
				'bet_id' => $data['bet_id'],
				'game_platform_id' => $this->game_platform_id
			];

			$flag_set_update = [
				'status' => Game_logs::STATUS_SETTLED,
				'win_amount' => $data['amount'],
			];

			$settle_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($tableName, $flag_where_update, $flag_set_update);
			if(!$settle_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}

			$data['bet_amount'] 	= $bet_transaction_details['amount'];
			$data['win_amount'] 	= $data['amount'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
			}
			
			#implement isEndRound
			$this->wallet_model->setGameProviderIsEndRound(true);
		}else if($transaction_type == self::METHOD_WIN && $this->request['operation'] == 'CancelBet'){
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			
			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, ['bet_id' => $data['bet_id']]);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::BET_NOT_FOUND, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::ALREADY_SETTLED, $playerId);
			}

			#check if transaction already rollback
			#should return success if already cancelled 
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			
			#get the bet amount to be refunded
			$amount = $data['amount'];

			$flag_where_update = [
				'bet_id' => $data['bet_id'],
				'game_platform_id' => $this->game_platform_id
			];

			$flag_set_update = [
				'status' => Game_logs::STATUS_CANCELLED
			];

			$refund_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($tableName, $flag_where_update, $flag_set_update);

			if(!$refund_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}
			$data['status'] = GAME_LOGS::STATUS_CANCELLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
			}

			#implement isEnd
			$this->wallet_model->setGameProviderIsEndRound(true);
		}else if ($transaction_type == self::METHOD_PROMO_WIN){
			$amount = $data['amount'];
			$data['win_amount'] = $data['amount'];
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_AMOUNT, $playerId);
			}
			
			$data['status'] = GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}
			$this->wallet_model->setGameProviderIsEndRound(true);

        }else{
			return $this->resultForAdjustWallet(self::METHOD_NOT_SUPPORTED, $playerId);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('AVIATRIX-amounts', [$beforeBalance, $afterBalance, $amount]);

		$amount_operator = '>';
		$configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
		if(!empty($configEnabled)){
			$amount_operator = '>=';
		} 

		if($this->utils->compareResultFloat($amount, $amount_operator, 0)){ 	
			#credit
			$data['balance_adjustment_method'] 	= 'credit';
			$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
			$afterBalance 						= $beforeBalance + $amount;
			$this->utils->debug_log('AVIATRIX', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else if($this->utils->compareResultFloat($amount, '<', 0)){	
			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('AVIATRIX', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
		}

		$data['transaction_date']		  	= isset($data['timestamp']) ? date("Y-m-d H:i:s", $data['timestamp']) : date("Y-m-d H:i:s");
		$data['extra_info']				 	= json_encode($this->request);
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
		
		$this->utils->debug_log('AVIATRIX--adjust-wallet', $data);

		$insertTransaction = $this->original_seamless_wallet_transactions->insertTransactionData($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::SUCCESS, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
	}


	public function resultForAdjustWallet($code, $player_id = null, $before_balance = null, $after_balance = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code === self::SUCCESS ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $code === self::SUCCESS ? $current_balance : null,
				'before_balance'  => $code === self::SUCCESS ? $before_balance : null,
				'after_balance'   => $code === self::SUCCESS ? $after_balance : null,
			];
		$this->utils->debug_log("AVIATRIX--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("AVIATRIX SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);
        
		$httpStatusCode =  200;
        
        if(!empty($error_code)){
            $httpStatusCode = self::HTTP_CODE[$error_code];
        }

		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### AVIATRIX SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
	
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
			$this->raw_request = $request_json;
			$this->utils->debug_log("AVIATRIX SEAMLESS SERVICE raw:", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("AVIATRIX SEAMLESS SERVICE raw parsed:", $request_json);
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

	public function isValidRequest($params, $required) {
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

}///END OF FILE////////////