<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Popok_gaming_seamless_service_api extends BaseController {

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
            $platform_id,
            $partner_id,
            $country
			;

    #error codes from GP
    const INVALID_EXTERNAL_TOKEN        = 'invalid.externalToken';
    const INSUFFICIENT_BALANCE          = 'insufficient.balance';
    const TRANSACTION_ALREADY_EXIST     = 'transaction.already.exists';
    const TRANSACTION_ALREADY_CANCELLED = 'transaction.already.cancelled';
    const INVALID_TRANSACTION_ID        = 'invalid.transaction.id';


    #additional error codes
    const SUCCESS                       = 'success';
    const SYSTEM_ERROR                  = 'failure.system';
    const IP_NOT_WHITELISTED            = 'failure.ip';
    const INVALID_PARAMETERS            = 'failure.parameters';
    const SYSTEM_MAINTENANCE            = 'failure.maintenance';
    const INVALID_CURRENCY              = 'failure.currency';
    const AMOUNT_INVALID              	= 'failure.amount';

	#method
    const METHOD_PLAYER_INFO    	= 'playerInfo';
    const METHOD_BET  		    	= 'bet';
    const METHOD_WIN  		    	= 'win';
    const METHOD_CANCEL  	    	= 'cancel';
    const METHOD_TOURNAMENT_WIN 	= 'tournamentWin';
    const METHOD_PROMO_WIN 			= 'promoWin';
    const METHOD_GENERATE_NEW_TOKEN = 'generateNewToken';

    #error messages
	const ERROR_MESSAGE = [
		self::INVALID_EXTERNAL_TOKEN         => 'External token is invalid or expired',
		self::INSUFFICIENT_BALANCE           => 'Insufficient player balance',
		self::TRANSACTION_ALREADY_EXIST      => 'Douplicate transaction',
		self::TRANSACTION_ALREADY_CANCELLED  => 'Already cancelled',
		self::INVALID_TRANSACTION_ID         => 'Unexpected transaction id',

        #additional error messages
		self::SUCCESS                        => 'Success',
		self::SYSTEM_ERROR                   => 'System error',
		self::IP_NOT_WHITELISTED             => 'Invalid IP address',
		self::INVALID_PARAMETERS             => 'Invalid parameters',
		self::SYSTEM_MAINTENANCE             => 'System maintenance',
		self::INVALID_CURRENCY               => 'Invalid Currency',
		self::AMOUNT_INVALID               	 => 'Invalid Amount',
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','popok_gaming_seamless_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index($method){
		$this->request_method = $method;
		$this->utils->debug_log('POPOK_GAMING--method : '. $this->request_method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'playerInfo':
				$this->playerInfo();
				break;
            case 'bet':
                $this->bet();
                break;
			case 'win':
				$this->win();
				break;
			case 'cancel':
				$this->cancel();
				break;
			case 'tournamentWin':
				$this->tournamentWin();
				break;
			case 'promoWin':
				$this->promoWin();
				break;
			case 'generateNewToken':
				$this->generateNewToken();
				break;
            case 'generatePlayerToken':
                $this->generatePlayerToken();
                break;
			default:
				$this->utils->debug_log('POPOK GAMING seamless service: Invalid API Method');
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
		$this->game_platform_id = POPOK_GAMING_SEAMLESS_GAME_API;
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 	= $this->game_api->getCurrency();
        $this->platform_id 	= $this->game_api->platform_id;
        $this->partner_id 	= $this->game_api->partner_id;
        $this->country 	    = $this->game_api->country;
        
		return true;
	}

    public function getError($errorCode){
        $error_response = [
            'code'    => $errorCode,
            'message' => self::ERROR_MESSAGE[$errorCode],
        ];

        return $error_response;
    }

    public function playerInfo(){
        $this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_PLAYER_INFO;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['externalToken'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['externalToken']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
            $externalResponse = [
                'country'   => $this->country,
                'currency'  => $this->currency,
                'balance'   => $balance,
                'playerId'  => $game_username,
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
        $requiredParams 			= [];
		$this->utils->debug_log('POPOK_GAMING-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['externalToken']);
            if(!$getPlayerStatus || ($this->request['playerId'] != $game_username)){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'external_token'     => isset($this->request['externalToken']) ? $this->request['externalToken'] : null,
			'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
			'game_id'            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id'     => isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : null,
			'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
			'trans_type'		 => $callType,
			'external_uniqueid'  => isset($this->request['transactionId']) ? $callType.'-'.$this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("POPOK_GAMING-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'balance' 	        => $balance,
				'transactionId' 	=> $params['transaction_id'],
				'externalTrxId' 	=> $params['external_uniqueid']
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function win(){
		$callType 					= self::METHOD_WIN;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('POPOK_GAMING-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerId']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'external_token'     => isset($this->request['externalToken']) ? $this->request['externalToken'] : null,
			'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
			'game_id'            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id'     => isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : null,
			'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
			'trans_type'		 => $callType,
			'external_uniqueid'  => isset($this->request['transactionId']) ? $callType.'-'.$this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("POPOK_GAMING-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'balance' 	        => $balance,
				'transactionId' 	=> $params['transaction_id'],
				'externalTrxId' 	=> $params['external_uniqueid']
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function cancel(){
		$callType 					= self::METHOD_CANCEL;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('POPOK_GAMING-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerId']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'external_token'     => isset($this->request['externalToken']) ? $this->request['externalToken'] : null,
			'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
			'game_id'            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id'     => isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'trans_type'		 => $callType,
			'external_uniqueid'  => isset($this->request['transactionId']) ? $callType.'-'.$this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("POPOK_GAMING-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'balance' 	        => $balance,
				'transactionId' 	=> $params['transaction_id'],
				'externalTrxId' 	=> $params['external_uniqueid']
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function tournamentWin(){
		$callType 					= self::METHOD_TOURNAMENT_WIN;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('POPOK_GAMING-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerId']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'external_token'     => isset($this->request['externalToken']) ? $this->request['externalToken'] : null,
			'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
			'game_id'            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id'     => isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
			'trans_type'		 => $callType,
			'external_uniqueid'  => isset($this->request['transactionId']) ? $callType.'-'.$this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("POPOK_GAMING-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'balance' 	        => $balance,
				'transactionId' 	=> $params['transaction_id'],
				'externalTrxId' 	=> $params['external_uniqueid']
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
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
        $requiredParams 			= [];
		$this->utils->debug_log('POPOK_GAMING-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerId']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'external_token'     => isset($this->request['externalToken']) ? $this->request['externalToken'] : null,
			'player_id'          => isset($this->request['playerId']) ? $this->request['playerId'] : null,
			'game_id'            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id'     => isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'amount'             => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'currency'           => isset($this->request['currency']) ? $this->request['currency'] : null,
			'trans_type'		 => $callType,
			'external_uniqueid'  => isset($this->request['transactionId']) ? $callType.'-'.$this->request['transactionId'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("POPOK_GAMING-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'balance' 	        => $balance,
				'transactionId' 	=> $params['transaction_id'],
				'externalTrxId' 	=> $params['external_uniqueid']
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }


	public function generateNewToken(){
		$callType 					= self::METHOD_GENERATE_NEW_TOKEN;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('POPOK_GAMING-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['token']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_EXTERNAL_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		if($success){
            $externalResponse = [
				'newToken'=> $this->request['token']
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

    public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('POPOK_GAMING-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->gameAmountToDB($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$game_code 						= isset($this->request['gameId']) ? $this->request['gameId'] : null;
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$game_code);
		if($transaction_type == self::METHOD_BET){
			$amount = $data['amount'] * -1; #getting the negative betting value
			$data['bet_amount'] = $data['amount'];
			$existingTrans  = $this->popok_gaming_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_EXIST, $playerId);
			}
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::AMOUNT_INVALID, $playerId);
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

        }else if($transaction_type == self::METHOD_WIN){
			$amount = $data['amount'];
			$existingTrans  = $this->popok_gaming_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_EXIST, $playerId);
			}

			#get bet transaction
			$bet_transaction_details = $this->popok_gaming_seamless_transactions->getBetTransactionByTransactionId($tableName, $data['transaction_id']);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::INVALID_TRANSACTION_ID, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_CANCELLED, $playerId);
			}

			$settle_bet = $this->popok_gaming_seamless_transactions->flagBetTransactionSettled($tableName, $data);
			if(!$settle_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}

			$data['bet_amount'] 	= $bet_transaction_details->amount;
			$data['win_amount'] 	= $data['amount'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
			}

			#implement isEndRound
			$this->wallet_model->setGameProviderIsEndRound(true);

		}else if($transaction_type == self::METHOD_CANCEL){
			$existingTrans  = $this->popok_gaming_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_EXIST, $playerId);
			}
			
			$bet_transaction_details = $this->popok_gaming_seamless_transactions->getBetTransactionByTransactionId($tableName, $data['transaction_id']);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::INVALID_TRANSACTION_ID, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details->status == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::INVALID_TRANSACTION_ID, $playerId);
			}

			#check if transaction already rollback
			#should return success if already cancelled 
			if($bet_transaction_details->status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_CANCELLED, $playerId);
			}

			#get the bet amount to be refunded
			$amount = $this->game_api->gameAmountToDB($bet_transaction_details->amount);
			$data['amount'] = $amount;
			$refund_bet = $this->popok_gaming_seamless_transactions->flagBetTransactionCancel($tableName, $data);
			if(!$refund_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}
			$data['status'] = GAME_LOGS::STATUS_CANCELLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
			}

			#implement isEnd
			$this->wallet_model->setGameProviderIsEndRound(false);
		}else if($transaction_type == self::METHOD_TOURNAMENT_WIN){
			$amount = $data['amount'];
			$existingTrans  = $this->popok_gaming_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_EXIST, $playerId);
			}
			
			$data['status'] = GAME_LOGS::STATUS_SETTLED;

			#implement isEnd
			$this->wallet_model->setGameProviderIsEndRound(false);
		}else if($transaction_type == self::METHOD_PROMO_WIN){
			$amount = $data['amount'];
			$existingTrans  = $this->popok_gaming_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_EXIST, $playerId);
			}
			
			$data['status'] = GAME_LOGS::STATUS_SETTLED;

			#implement isEnd
			$this->wallet_model->setGameProviderIsEndRound(false);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('POPOK_GAMING-amounts', [$beforeBalance, $afterBalance, $amount]);

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
			$this->utils->debug_log('POPOK GAMING', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else if($this->utils->compareResultFloat($amount, '<', 0)){	
			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('POPOK GAMING', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
		}

		$data['transaction_date']		  	= $this->utils->getCurrentDatetimeWithSeconds('Y-m-d H:i:s');
		$data['extra_info']				 	= json_encode($this->request);
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
		
		$this->utils->debug_log('POPOK_GAMING--adjust-wallet', $data);

		$insertTransaction = $this->popok_gaming_seamless_transactions->insertTransaction($tableName,$data);

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
		$this->utils->debug_log("POPOK_GAMING--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE: (handleExternalResponse)",
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
		$this->utils->debug_log("##### POPOK GAMING SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
			$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE raw:", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("POPOK GAMING SEAMLESS SERVICE raw parsed:", $request_json);
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

}///END OF FILE////////////