<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Endorphina_seamless_service_api extends BaseController {

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
            $merchant_key,
			$disable_sign_validation,
			$node_id;

    #error codes from GP
    const SUCCESS 			 = 'SUCCESS'; 
    const ACCESS_DENIED      = 'ACCESS_DENIED'; 
    const INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS'; 
    const TOKEN_EXPIRED      = 'TOKEN_EXPIRED';
    const LIMIT_REACHED      = 'LIMIT_REACHED';
    const TOKEN_NOT_FOUND    = 'TOKEN_NOT_FOUND';
    const INTERNAL_ERROR     = 'INTERNAL_ERROR';

    #additional error codes
    const ERROR_INVALID_PARAMETERS 	= 'ERROR_INVALID_PARAMETERS';
    const INVALID_IP_ADDRESS       	= 'INVALID_IP_ADDRESS';
    const INVALID_AMOUNT       	   	= 'INVALID_AMOUNT';
    const TRANSACTION_NOT_FOUND    	= 'TRANSACTION_NOT_FOUND';
    const ALREADY_REFUNDED     		= 'ALREADY_REFUNDED';
    const ALREADY_SETTLED     		= 'ALREADY_SETTLED';
   
	#method
    const METHOD_SESSION 		= 'Session';
    const METHOD_BALANCE 		= 'Balance';
    const METHOD_BET     		= 'Bet';
    const METHOD_WIN 	 		= 'Win';
    const METHOD_PROMO_WIN 	 	= 'PromoWin';
    const METHOD_REFUND 	 	= 'Refund';
    const METHOD_CHECK 	 		= 'Check';
    const METHOD_END_SESSION 	= 'EndSession';

  
	#transaction mode
	const ERROR_MESSAGE = [
		self::SUCCESS                   	=> 'success',
		self::ACCESS_DENIED             	=> 'Authentication error',
		self::INSUFFICIENT_FUNDS        	=> 'Insufficient funds',
		self::TOKEN_EXPIRED             	=> 'Token expired',
		self::LIMIT_REACHED             	=> 'Reached limit',
		self::TOKEN_NOT_FOUND           	=> 'Invalid Token',
		self::INTERNAL_ERROR            	=> 'Internal Server Error',
		self::ERROR_INVALID_PARAMETERS  	=> 'Invalid Request Parameter',
		self::INVALID_IP_ADDRESS        	=> 'Invalid Ip Address',
		self::INVALID_AMOUNT        		=> 'Invalid Amount',
		self::TRANSACTION_NOT_FOUND     	=> 'Transaction not found',
		self::ALREADY_REFUNDED 				=> 'Transaction already refunded',
		self::ALREADY_SETTLED 				=> 'Transaction already settled',
	];

	const ERROR_HTTP_CODE = [
		self::SUCCESS                       => 200,
		self::ACCESS_DENIED                 => 401,
		self::INSUFFICIENT_FUNDS            => 402,
		self::TOKEN_EXPIRED                 => 403,
		self::LIMIT_REACHED                 => 403,
		self::TOKEN_NOT_FOUND               => 404,
		self::INTERNAL_ERROR                => 500,
		self::ERROR_INVALID_PARAMETERS      => 403,
		self::INVALID_IP_ADDRESS            => 403,
		self::INVALID_AMOUNT            	=> 403,
		self::TRANSACTION_NOT_FOUND         => 403,
		self::ALREADY_REFUNDED 				=> 403,
		self::ALREADY_SETTLED 				=> 403,
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','external_common_tokens','game_logs','endorphina_seamless_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index(...$methods){
        $method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('ENDORPHINA--method : '. $method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'check':
				$this->check();
				break;
			case 'session':
				$this->session();
				break;
			case 'balance':
				$this->balance();
				break;
            case 'bet':
                $this->bet();
                break;
			case 'win':
				$this->win();
				break;
			case 'promoWin':
				$this->promoWin();
				break;
			case 'refund':
				$this->refund();
				break;
            case 'generatePlayerToken':
                $this->generatePlayerToken();
                break;
            case 'generateSign':
                $this->generateSign();
                break;
			default:
				$this->utils->debug_log('ENDORPHINA seamless service: Invalid API Method');
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
		$token			 	= $this->external_common_tokens->getExternalToken($player_id, $this->game_platform_id);
		if(!$token){
			$token = $this->game_api->generateToken($player_id);
			$this->external_common_tokens->addPlayerToken($player_id, $token, $this->game_platform_id, $this->currency);
		}

		$response =  [
			'username' => $player_username,
			'token'    => $token,
		];

		print_r($response); exit;
	}

	public function initialize(){
		$this->game_platform_id = ENDORPHINA_SEAMLESS_GAME_API;
		$this->game_api 	= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 				= $this->game_api->getCurrency();
        $this->merchant_key 			= $this->game_api->merchant_key;
        $this->disable_sign_validation 	= $this->game_api->disable_sign_validation;
        $this->node_id 					= $this->game_api->node_id;
		return true;
	}

    public function session(){
        $this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE: (Session)");	
		$callType 					= self::METHOD_SESSION;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$game_username 			 	= null;
		$success 					= true;
        $requiredParams = ['token', 'sign'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}
	

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			$token = $this->request['token'];
			$external_common_token_data = $this->external_common_tokens->getExternalCommonTokenInfoByToken($token);
			if(!empty($external_common_token_data)){
				$extra_info = json_decode($external_common_token_data['extra_info']);
				$game = $extra_info->game_code;
			}
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($token);

			if(!$getPlayerStatus){
				throw new Exception(self::TOKEN_NOT_FOUND);
			}

			$player_id = $player->player_id;

            if(!$this->isValidSign($this->request)){
				throw new Exception(self::ACCESS_DENIED);
			}

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        if($success){
            $externalResponse = [
                'player'    => $game_username,
                'currency'  => $this->currency,
                'game'      => $game ? 'endorphina'.$game.'@ENDORPHINA' : null,
            ];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }



    public function getError($errorCode){
        $error_response = [
            'code'    => $errorCode,
            'message' => self::ERROR_MESSAGE[$errorCode]
        ];

        $showHint = true;

        if($showHint && $errorCode == self::ACCESS_DENIED){
            $error_response['hint'] = $this->game_api->generateSign($this->request);
        }   

        return $error_response;
    }

    public function balance(){
        $this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE: (Balance)");	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_BALANCE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['token', 'sign'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['token']);
			if(!$getPlayerStatus){
				throw new Exception(self::TOKEN_NOT_FOUND);
			}
			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance      = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
            $externalResponse = [
				'balance' => $balance
			];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function check(){
        $this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE: (check)");	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_CHECK;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['param', 'sign'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isValidSign()){
				throw new Exception(self::ACCESS_DENIED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
            

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		if($success){
			$externalResponse = [
				'nodeId' => $this->node_id,
				'param' => $this->request['param'],
				'sign' => $this->game_api->generateSign($this->request, true),
			];
		}else{
			$externalResponse = $this->getError($errorCode);
		}
		

		$fields = [];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	

	public function bet(){
		$callType 					= self::METHOD_BET;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['amount','date','gameId','id','token','sign'];
		$this->utils->debug_log('ENDORPHINA-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ACCESS_DENIED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
           

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['token']);
            if(!$getPlayerStatus){
                throw new Exception(self::TOKEN_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'amount' 	            => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'bet_amount' 	        => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'currency' 	            => isset($this->request['currency']) ? $this->request['currency'] : null,
			'timestamp' 	        => isset($this->request['date']) ? $this->request['date'] : null,
			'datetime' 	            => isset($this->request['date']) ? date("Y-m-d H:i:s", $this->request['date'] / 1000) : null,
			'game' 		            => isset($this->request['game']) ? $this->request['game'] : null,
			'round_id' 	            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'game_id' 	            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id' 		=> isset($this->request['id']) ? $this->request['id'] : null,
			'player' 	            => isset($this->request['player']) ? $this->request['player'] : null,
			'token' 	            => isset($this->request['token']) ? $this->request['token'] : null,
			'sign' 		            => isset($this->request['sign']) ? $this->request['sign'] : null,
			'external_uniqueid' 	=> isset($this->request['id']) ? $this->request['id'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ENDORPHINA-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
                'transactionId' => $params['transaction_id'],
				'balance'		=> $balance
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
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['amount','date','gameId','sign','player','betTransactionId'];
		$this->utils->debug_log('ENDORPHINA-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ACCESS_DENIED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
           

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['player']);
            if(!$getPlayerStatus){
                throw new Exception(self::TOKEN_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'amount' 	            => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'win_amount' 	        => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'bet_session_id' 	    => isset($this->request['betSessionId']) ? $this->request['betSessionId'] : null,
			'bet_transaction_id' 	=> isset($this->request['betTransactionId']) ? $this->request['betTransactionId'] : null,
			'currency' 	            => isset($this->request['currency']) ? $this->request['currency'] : null,
			'timestamp' 	        => isset($this->request['date']) ? $this->request['date'] : null,
			'datetime' 	            => isset($this->request['date']) ? date("Y-m-d H:i:s", $this->request['date'] / 1000) : null,
			'game' 		            => isset($this->request['game']) ? $this->request['game'] : null,
			'round_id' 	            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'game_id' 	            => isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id' 		=> isset($this->request['id']) ? $this->request['id'] : $this->generateUniqueId($this->request['token'], $this->request['gameId']),
			'player' 	            => isset($this->request['player']) ? $this->request['player'] : null,
			'progressive' 	        => isset($this->request['progressive']) ? $this->request['progressive'] : null,
			'progressive_desc' 	    => isset($this->request['progressiveDesc']) ? $this->request['progressiveDesc'] : null,
			'token' 	            => isset($this->request['token']) ? $this->request['token'] : null,
			'win_description' 		=> isset($this->request['winDescription']) ? $this->request['winDescription'] : null,
			'sign' 		            => isset($this->request['sign']) ? $this->request['sign'] : null,
			'external_uniqueid' 	=> isset($this->request['id']) ? $this->request['id'] : $this->generateUniqueId($this->request['token'], $this->request['gameId']),
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ENDORPHINA-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
                'transactionId' => $params['transaction_id'],
				'balance'		=> $balance
            ];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }
	
	public function generateUniqueId($token, $game_id){
		if(!empty($token) && !empty($game_id)){
			$id = $token.'-'.$game_id;
			return md5($id);
		}
		return null;
	}

	public function refund(){
		$callType 					= self::METHOD_REFUND;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['amount','date','gameId','id','sign','player'];

		$this->utils->debug_log('ENDORPHINA-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ACCESS_DENIED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
           

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['player']);
            if(!$getPlayerStatus){
                throw new Exception(self::TOKEN_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'amount' 				=> isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'currency' 				=> isset($this->request['currency']) ? $this->request['currency'] : null,
			'timestamp' 			=> isset($this->request['date']) ? $this->request['date'] : null,
			'datetime' 				=> isset($this->request['date']) ? date("Y-m-d H:i:s", $this->request['date'] / 1000) : null,
			'game' 					=> isset($this->request['game']) ? $this->request['game'] : null,
			'game_id' 				=> isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'round_id' 				=> isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'bet_transaction_id' 	=> isset($this->request['id']) ? $this->request['id'] : null,
			'player' 				=> isset($this->request['player']) ? $this->request['player'] : null,
			'token' 				=> isset($this->request['token']) ? $this->request['token'] : null,
			'sign'  				=> isset($this->request['sign']) ? $this->request['sign'] : null,
			'external_uniqueid'     => isset($this->request['id']) ? 'refund-'.$this->request['id'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ENDORPHINA-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
                'transactionId' => $this->request['id'],
				'balance'		=> $balance
            ];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

    /* #sign = SHA1HEX{param1param2paramNsalt}
    public function generateSign($use_node_id = false){
		$params = $this->request;
        ksort($params);
		$concatenatedString = '';
        if($use_node_id){
			$concatenatedString = $this->node_id;
		}
        foreach ($params as $name => $value) {
            if ($name != 'sign') { 
                $concatenatedString .= $value;
            }
        }
        $concatenatedString .= $this->merchant_key;
        $sha1Hash = sha1($concatenatedString);
        return $sha1Hash;
    } */

    public function generateSign(){
        $initialized = $this->initialize();
        
        $response = [
            'sign' => null,
        ];

        if ($initialized) {
            $http_response_status_code = 200;
            $response['sign'] = $this->game_api->generateSign($this->request);
        } else {
            $http_response_status_code = 400;
        }

        return $this->output->set_status_header($http_response_status_code)->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function formatDate($dateString = null, $format = 'Y-m-d H:i:s T', $timezone = null){
		if($dateString){
			$dateTime = $dateString == 'now' ? new DateTime() : new DateTime($dateString);
			if ($timezone !== null) {
				$timeZoneObj = new DateTimeZone($timezone);
				$dateTime->setTimezone($timeZoneObj);
			}
			$formattedDate = $dateTime->format($format);
			return $formattedDate;
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
			$this->utils->debug_log('ENDORPHINA-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }


	public function adjustWallet($transaction_type, $player_info, $data) {
		$uniqueid_of_seamless_service = $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId = $player_info->player_id;
		$balance = $this->game_api->gameAmountToDB($this->game_api->getPlayerBalanceById($playerId));
		$currency = $this->game_api->currency;
		$tableName = $this->game_api->getTransactionsTable();
	
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service);
		$this->wallet_model->setExternalGameId($data['game']);
	
		// Validate transaction type and handle accordingly
		switch ($transaction_type) {
			case self::METHOD_BET:
				$amount = $data['amount'] * -1;
	
				if ($this->endorphina_seamless_transactions->isTransactionExist($tableName, $data)) {
					return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
				}
	
				if ($data['amount'] < 0 || $balance < $data['amount']) {
					return $this->resultForAdjustWallet(
						$data['amount'] < 0 ? self::INVALID_AMOUNT : self::INSUFFICIENT_FUNDS,
						$playerId
					);
				}
	
				$data['status'] = GAME_LOGS::STATUS_PENDING;
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
				$this->wallet_model->setGameProviderIsEndRound(false);
				break;
	
			case self::METHOD_WIN:
			case self::METHOD_PROMO_WIN:
				$amount = $data['amount'];
	
				if ($this->endorphina_seamless_transactions->isTransactionExist($tableName, $data)) {
					return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
				}
	
				if ($transaction_type === self::METHOD_WIN) {
					$bet_transaction_id = isset($this->request['betTransactionId']) ? $this->request['betTransactionId'] : null;
	
					if ($bet_transaction_id) {
						$bet_transaction_details = $this->endorphina_seamless_transactions->getExistingTransactionByTransactionId($tableName, $bet_transaction_id);
						if (empty($bet_transaction_details)) {
							return $this->resultForAdjustWallet(self::TRANSACTION_NOT_FOUND, $playerId);
						}
	
						if ($bet_transaction_details->status === GAME_LOGS::STATUS_REFUND) {
							return $this->resultForAdjustWallet(self::ALREADY_REFUNDED, $playerId);
						}
					}
	
					if (!$this->endorphina_seamless_transactions->flagBetTransactionSettled($tableName, $data)) {
						return $this->resultForAdjustWallet(self::INTERNAL_ERROR, $playerId);
					}
				}
	
				$data['status'] = GAME_LOGS::STATUS_SETTLED;
				$this->wallet_model->setGameProviderActionType(
					$transaction_type === self::METHOD_WIN
						? Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT
						: Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT
				);
				$this->wallet_model->setGameProviderIsEndRound(true);
	
				if (isset($this->request['betTransactionId'])) {
					$related_unique_id = 'game-' . $this->game_platform_id . '-' . $this->request['betTransactionId'];
					$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_unique_id);
					$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
				}
				break;
	
			case self::METHOD_REFUND:
				$amount = $data['amount'];
	
				if ($this->endorphina_seamless_transactions->isTransactionExist($tableName, $data)) {
					return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
				}
	
				$bet_transaction_id = isset($this->request['id']) ? $this->request['id'] :null;
				$bet_transaction_details = $this->endorphina_seamless_transactions->getExistingTransactionByTransactionId($tableName, $bet_transaction_id);
	
				if (empty($bet_transaction_details)) {
					return $this->resultForAdjustWallet(self::TRANSACTION_NOT_FOUND, $playerId);
				}
	
				if ($bet_transaction_details->status === GAME_LOGS::STATUS_REFUND) {
					return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
				}
	
				if ($bet_transaction_details->status === GAME_LOGS::STATUS_SETTLED || $bet_transaction_details->amount != $data['amount']) {
					return $this->resultForAdjustWallet(
						$bet_transaction_details->status === GAME_LOGS::STATUS_SETTLED
							? self::ALREADY_SETTLED
							: self::INVALID_AMOUNT,
						$playerId
					);
				}
	
				if (!$this->endorphina_seamless_transactions->flagBetTransactionRefund($tableName, ['transaction_id' => $bet_transaction_id])) {
					return $this->resultForAdjustWallet(self::INTERNAL_ERROR, $playerId);
				}
	
				$data['status'] = GAME_LOGS::STATUS_REFUND;
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
				$this->wallet_model->setGameProviderIsEndRound(true);
				$this->wallet_model->setRelatedUniqueidOfSeamlessService('game-' . $this->game_platform_id . '-' . $bet_transaction_id);
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
				break;
	
			default:
				return $this->resultForAdjustWallet(self::INTERNAL_ERROR, $playerId);
		}
	
		// Update balances and log results
		$beforeBalance = $balance;
		$amount_operator = $this->utils->getConfig('enabled_remote_wallet_client_on_currency') ? '>=' : '>';
	
		if ($this->utils->compareResultFloat($amount, $amount_operator, 0)) {
			$data['balance_adjustment_method'] = 'credit';
			$response = $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $balance);
			$balance += $amount;
		} else {
			$amount = abs($amount);
			$data['balance_adjustment_method'] = 'debit';
			$response = $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $balance);
			$balance -= $amount;
		}
	
		if (!$response) {
			return $this->resultForAdjustWallet(self::INTERNAL_ERROR, $playerId);
		}
	
		// Finalize and insert transaction
		$data['currency'] = $currency;
		$data['balance_adjustment_amount'] = $amount;
		$data['before_balance'] = $beforeBalance;
		$data['after_balance'] = $balance;
		$data['elapsed_time'] = intval($this->utils->getExecutionTimeToNow() * 1000);
		$data['game_platform_id'] = $this->game_platform_id;
		$data['player_id'] = $playerId;
		$data['trans_type'] = $transaction_type;
		$data['extra_info'] = json_encode($this->request);
	
		$this->utils->debug_log('ENDORPHINA--adjust-wallet', $data);
	
		return $this->endorphina_seamless_transactions->insertTransaction($tableName, $data)
			? $this->resultForAdjustWallet(self::SUCCESS, $playerId)
			: $this->resultForAdjustWallet(self::INTERNAL_ERROR, $playerId);
	}
	

	public function promoWin(){
		$callType 					= self::METHOD_PROMO_WIN;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['amount','currency','date','game','gameId','id','player','promoId','promoName','token','sign'];

		$this->utils->debug_log('ENDORPHINA-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::INTERNAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::INTERNAL_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ACCESS_DENIED);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
           
			if(isset($this->request['token'])){
				list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['token']);
			}else{
				list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['player']);
			}
            if(!$getPlayerStatus){
                throw new Exception(self::TOKEN_NOT_FOUND);
            }
			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	
		
		$params = [
			'amount' 				=> isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'currency' 				=> isset($this->request['currency']) ? $this->request['currency'] : null,
			'timestamp' 			=> isset($this->request['date']) ? $this->request['date'] : null,
			'datetime' 				=> isset($this->request['date']) ? date("Y-m-d H:i:s", $this->request['date'] / 1000) : null,
			'game' 					=> isset($this->request['game']) ? $this->request['game'] : null,
			'game_id' 				=> isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'round_id' 				=> isset($this->request['gameId']) ? $this->request['gameId'] : null,
			'transaction_id' 	    => isset($this->request['id']) ? $this->request['id'] : null,
			'player' 				=> isset($this->request['player']) ? $this->request['player'] : null,
			'promo_id' 				=> isset($this->request['promoId']) ? $this->request['promoId'] : null,
			'promo_name' 			=> isset($this->request['promoName']) ? $this->request['promoName'] : null,
			'token' 				=> isset($this->request['token']) ? $this->request['token'] : null,
			'sign' 					=> isset($this->request['sign']) ? $this->request['sign'] : null,
			'external_uniqueid' 	=> isset($this->request['id']) ? $this->request['id'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ENDORPHINA-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
                'transactionId' => $params['transaction_id'],
				'balance'		=> $balance
            ];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function resultForAdjustWallet($code, $player_id = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code == self::SUCCESS ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $code == self::SUCCESS ? $current_balance : null,
			];
		$this->utils->debug_log("ENDORPHINA--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);

		$httpStatusCode =  self::ERROR_HTTP_CODE[self::SUCCESS];

		if($error_code){
			$httpStatusCode = self::ERROR_HTTP_CODE[$error_code];
		}

		#add request_id
		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### ENDORPHINA SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
			$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE raw:", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("ENDORPHINA SEAMLESS SERVICE raw parsed:", $request_json);
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
		$player = $this->external_common_tokens->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		return [true, $player, $player->game_username, $player->username];
	}

	public function isParamsRequired($params, $required) {
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

    public function isValidSign(){
		if($this->disable_sign_validation){
			return true;
		}
		if(isset($this->request['sign']) && $this->request['sign']){
			if($this->game_api->generateSign($this->request) != $this->request['sign']){
				return false;
			}
		}
        return true;
    }
}///END OF FILE////////////