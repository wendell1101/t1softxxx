<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Astar_seamless_service_api extends BaseController {

    public $game_platform_id,$start_time,$load,$host_name,$method, $game_api, $currency,$request, $headers, $response_result_id,$end_time, $output, $request_method, $merchant_id;

    #error codes
    const SUCCESS                   		= 0; 
    const ERROR_INTERNAL_ERROR      		= 500;
    const ERROR_SERVICE_UNAVAILABLE 		= 503;
    const ERROR_NOT_ALLOWED         		= 405;
    const ERROR_UNAUTHORIZED        		= 401; 
    const ERROR_LOSS_LIMIT          		= 112; 
	const ERROR_NOT_ENOUGH_BALANCE  		= 2001;
	const ERROR_INVALID_PLAYER      		= 2002;
	const ERROR_INVALID_PARAMETERS  		= 2003;
	const ERROR_INVALID_AMOUNT      		= 2004;
	const ERROR_TRANSACTION_NOT_FOUND       = 2005;
	const ERROR_INVALID_TRANSACTION         = 2006;
	const ERROR_ALREADY_SETTLED		        = 2007;
	const ERROR_ALREADY_ROLLBACK		    = 2008;
	const ERROR_ALREADY_CANCELLED		    = 2009;
	const ERROR_INVALID_AUTHORIZATION_TOKEN = 2010;
	const ERROR_INVALID_MERCHANT_ID 	    = 2011;

	#method
    const METHOD_AUTH  			= 'Auth';
    const METHOD_BALANCE  		= 'Balance';
    const METHOD_DEBIT  		= 'Debit';
    const METHOD_CREDIT  		= 'Credit';
    const METHOD_REFUND  		= 'Refund';
    const METHOD_ROUND_CHECK  	= 'Round Check';
  
	#transaction mode

	const ERROR_MESSAGE = [
		self::SUCCESS 							=> 'success',
		self::ERROR_INTERNAL_ERROR 				=> 'Internal Server error',
		self::ERROR_SERVICE_UNAVAILABLE 		=> 'Service Unavailable',
		self::ERROR_NOT_ALLOWED 				=> 'Request not allowed',
		self::ERROR_UNAUTHORIZED 				=> 'Unauthorized',
		self::ERROR_LOSS_LIMIT 					=> 'Error loss limit',
		self::ERROR_NOT_ENOUGH_BALANCE 			=> 'Player insufficient balance',
		self::ERROR_INVALID_PLAYER 				=> 'Invalid Player',
		self::ERROR_INVALID_PARAMETERS  		=> 'Invalid or missing parameters',
		self::ERROR_INVALID_AMOUNT  			=> 'Invalid Amount',
		self::ERROR_TRANSACTION_NOT_FOUND  		=> 'Transaction not found',
		self::ERROR_INVALID_TRANSACTION	  		=> 'Invalid Transaction',
		self::ERROR_ALREADY_SETTLED	  			=> 'Transaction already settled',
		self::ERROR_ALREADY_ROLLBACK	    	=> 'Transaction already rollback',
		self::ERROR_ALREADY_CANCELLED	    	=> 'Transaction already cancelled',
		self::ERROR_INVALID_AUTHORIZATION_TOKEN => 'Invalid Authorization Token',
		self::ERROR_INVALID_MERCHANT_ID 	    => 'Invalid merchant Id',
	];

	const ERROR_HTTP_CODE = [
		self::SUCCESS 								=> 200,
		self::ERROR_INTERNAL_ERROR 					=> 500,
		self::ERROR_SERVICE_UNAVAILABLE 			=> 503,
		self::ERROR_NOT_ALLOWED 					=> 405,
		self::ERROR_UNAUTHORIZED 					=> 401,
		self::ERROR_LOSS_LIMIT 						=> 112,
		self::ERROR_NOT_ENOUGH_BALANCE 				=> 400,
		self::ERROR_INVALID_PLAYER 					=> 400,
		self::ERROR_INVALID_PARAMETERS  			=> 400,
		self::ERROR_INVALID_AMOUNT  				=> 400,
		self::ERROR_TRANSACTION_NOT_FOUND  			=> 400,
		self::ERROR_INVALID_TRANSACTION  			=> 400,
		self::ERROR_ALREADY_SETTLED  				=> 400,
		self::ERROR_ALREADY_ROLLBACK  				=> 400,
		self::ERROR_ALREADY_CANCELLED  				=> 400,
		self::ERROR_INVALID_AUTHORIZATION_TOKEN  	=> 400,
		self::ERROR_INVALID_MERCHANT_ID  			=> 400,
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','astar_seamless_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index(...$methods){
        $method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('ASTAR--method : '. $method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
        $id = @explode('/',$this->request_method)[4];
		switch ($this->getMethodFromString($this->request_method)) {
			case 'player/auth':
				if(isset($this->request['gametoken']) && !$this->successDBswitchFromToken($this->request['gametoken'])){
					http_response_code(500);
					break;
				} 
				$this->auth();
				break;
            case 'player/balance/'.$id:
				if(isset($id) && !$this->successDBswitchFromGameUsername($id)){
					http_response_code(500);
					break;
				}
                $this->balance($id);
                break;
			case 'table/rollout':
				if(isset($this->request['gametoken']) && !$this->successDBswitchFromToken($this->request['gametoken'])){
					http_response_code(500);
					break;
				} 
				$this->DebitAndCredit(self::METHOD_DEBIT);
				break;
			case 'table/rollin':
				if(isset($this->request['gametoken']) && !$this->successDBswitchFromToken($this->request['gametoken'])){
					http_response_code(500);
					break;
				} 
				$this->DebitAndCredit(self::METHOD_CREDIT);
				break;
			case 'game/bet/refund':
				$this->DebitAndCredit(self::METHOD_REFUND);
				break;
			case 'game/roundcheck':
				$this->roundcheck();
				break;
			case 'generatePlayerToken':
				$this->generatePlayerToken();
				break;
			default:
				$this->utils->debug_log('ASTAR seamless service: Invalid API Method');
				http_response_code(404);
		}
	}

	public function getMethodFromString($string = null){
		if($string){
			$stringExplode = explode('/', $string);
			$resultArray = array_slice($stringExplode, 2); // Extract elements starting from index 2
			$this->merchant_id = $stringExplode[1];
			$this->utils->debug_log('ASTAR-merchant-id: '.$this->merchant_id);
			return implode('/', $resultArray);
		}
	}

	public function generatePlayerToken(){
		if(!$this->initialize()) {
			return [
				'success'   => 'error',
			];
		}
		$player_username = isset($this->request['username']) ? $this->request['username'] : null;
		$token			 = $this->game_api->getPlayerTokenByUsername($player_username);
		$response =  [
			'username' => $player_username,
			'token'    => $token,
		];

		print_r($response); exit;
	}

	public function initialize(){
		$this->game_platform_id = ASTAR_SEAMLESS_GAME_API;
		$this->game_api 	= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("ASTAR SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 	= $this->game_api->getCurrency();
		return true;
	}

	private function loadDbFromToken($token){
        $this->utils->debug_log('ASTAR ' . __METHOD__ , ' token', $token);
        $currency = null;
        //switch to target db
        if(!is_null($token)){
            $currencyExplode = explode('-', $token);
            if(isset($currencyExplode[0])){
                $currency = strtolower($currencyExplode[0]);
                if(!$this->utils->isAvailableCurrencyKey($currency)){
                    return false;
                }
                $this->utils->debug_log('ASTAR ' . __METHOD__ , ' currency', $currency);
                // if(isset($currencyExplode[1]) && !empty($currencyExplode[1])){
                //     $token=$currencyExplode[1];
                //     $this->requestParams->params['username'] = $token;
                // }
                $_multiple_db=Multiple_db::getSingletonInstance();
                $res = $_multiple_db->switchCIDatabase($currency);
                $this->utils->debug_log('ASTAR ' . __METHOD__ , ' switchCIDatabase result', $res);
                return true;
            }
        }
        return false;
    }

	private function loadDbFromGameUsername($gameUsername){
        $this->utils->debug_log('ASTAR ' . __METHOD__ , ' gameUsername', $gameUsername);
        //switch to target db
        if(!is_null($gameUsername)){
			$currency = $this->getCurrencyOnGameUsername($gameUsername);
			$username_without_currency = substr($gameUsername, strlen($currency));
            $gameUsername = explode('-', $gameUsername);
            if($currency){
                if(!$this->utils->isAvailableCurrencyKey($currency)){
                    return false;
                }
                $this->utils->debug_log('ASTAR ' . __METHOD__ , ' currency', $currency);
                $_multiple_db=Multiple_db::getSingletonInstance();
                $res = $_multiple_db->switchCIDatabase($currency);
                $this->utils->debug_log('ASTAR ' . __METHOD__ , ' switchCIDatabase result', $res);
                return true;
            }
        }
        return false;
    }

	
	private function loadDbByCurrency($currency){
		if(!$this->utils->isAvailableCurrencyKey($currency)){
			return false;
		}
		$this->utils->debug_log('ASTAR-roundcheck' . __METHOD__ , ' currency', $currency);
		$_multiple_db=Multiple_db::getSingletonInstance();
		$res = $_multiple_db->switchCIDatabase($currency);
		$this->utils->debug_log('ASTAR-roundcheck' . __METHOD__ , ' switchCIDatabase result', $res);
		return true;
	}

	public function getCurrencyOnGameUsername($gameUsername){
		$currencies = $this->getCurrencyList();
		$substring = substr($gameUsername, 0, 4); // Extract the first four characters
		$currency_found = false;
		foreach ($currencies as $code) {
			if (strpos($substring, $code) !== false) {
				$currency_found = true;
				return $code;
			}
		}
		if (!$currency_found) {
			$this->utils->debug_log('ASTAR--no_currency_found');
			return false;
		}
	}

	public function getCurrencyList(){
		$currencies = $this->utils->getAvailableCurrencyList();
		$this->utils->debug_log('ASTAR-getAvailableCurrencyList', $currencies);
		$list = [];
		foreach($currencies as $key => $values){
			$list[] = $key;
		}
		return $list;
	}


	public function explodeToken($token_with_currency){
		if (!str_contains($token_with_currency, '-')) { 
			return [
				'currency' => null,
				'token'    => $token_with_currency,
			];
		}
		if($token_with_currency){
			$currencyExplode = explode('-', $token_with_currency);
			if(count($currencyExplode) == 2){
				return [
					'currency' => $currencyExplode[0],
					'token'    => $currencyExplode[1],
				];
			}
		}
		return false;
	}

    public function auth(){
        $this->utils->debug_log("ASTAR SEAMLESS SERVICE: (ActivateSession)");	
		$callType 					= self::METHOD_AUTH;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$game_username 			 	= null;
		$success 					= true;
		$token 						= null;
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_NOT_ALLOWED);
			}

			if(!$this->checkAuthorizationToken()){
				throw new Exception(self::ERROR_INVALID_AUTHORIZATION_TOKEN);
			}

			if(strtolower($this->merchant_id) != strtolower($this->game_api->merchant_id)){
				throw new Exception(self::ERROR_INVALID_MERCHANT_ID);
			}

			$requiredParams = ['gametoken'];

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
           
			$request 				= $this->request;
            $original_token         = $this->request['gametoken'];
			$token      			= $this->explodeToken($original_token)['token'];
			$this->utils->debug_log('ASTAR-auth', $request);
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($token);

			if(!$getPlayerStatus){
				throw new Exception(self::ERROR_INVALID_PLAYER);
			}

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance      = $this->game_api->getPlayerBalanceById($player_id);

		$externalResponse = [
            'data' => [
                'account'       => $game_username,
                'balance'       => $balance,
                'betthreshold'  => $this->game_api->bet_threshold,
                'currency'      => $this->currency,
                'id'            => $game_username,
                'parentid'      => $this->game_api->agent_id,
            ],
            'status' => [
                'code'      => $errorCode == self::SUCCESS ? self::SUCCESS : $errorCode,
                'message'   => self::ERROR_MESSAGE[$errorCode],
                'datetime'  => $this->formatDate('now', 'Y-m-d\TH:i:sP', '-04:00'),
                'tracecode' => $this->generateTrackerId(),
            ]
        ];

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

    public function Balance($gameUsername = null){
        $this->utils->debug_log("ASTAR SEAMLESS SERVICE: (Balance)");	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_BALANCE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$token 						= null;
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_NOT_ALLOWED);
			}

			if(!$this->checkAuthorizationToken()){
				throw new Exception(self::ERROR_INVALID_AUTHORIZATION_TOKEN);
			}

			if(strtolower($this->merchant_id) != strtolower($this->game_api->merchant_id)){
				throw new Exception(self::ERROR_INVALID_MERCHANT_ID);
			}

			if(!$gameUsername){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
            
			$this->utils->debug_log('ASTAR-balance', $gameUsername);
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($gameUsername);
			if(!$getPlayerStatus){
				throw new Exception(self::ERROR_INVALID_PLAYER);
			}
			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance      = $this->game_api->getPlayerBalanceById($player_id);

		$externalResponse = [
            'data' => [
                'balance'    => $balance,
                'currency'   => $this->currency
            ],
            'status' => [
                'code'      => $errorCode == self::SUCCESS ? self::SUCCESS : $errorCode,
                'message'   => self::ERROR_MESSAGE[$errorCode],
                'datetime'  => $this->formatDate('now', 'Y-m-d\TH:i:sP', '-04:00'),
                'tracecode' => $this->generateTrackerId(),
            ]
        ];

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function DebitAndCredit($transaction_type = null){
        $this->utils->debug_log("ASTAR SEAMLESS SERVICE: ". $transaction_type);	
		$callType 					= $transaction_type;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$game_username 			 	= null;
		$success 					= true;
		$token 						= null;
		$balance					= null;
		$trans_success 				= false;
		if($transaction_type == self::METHOD_DEBIT){
			$requiredParams = ['gametoken','id','mtcode','round','amount','datetime','roundnumber','tabletype','tablename','tableid'];
		}else if($transaction_type == self::METHOD_CREDIT){
			$requiredParams = ['gametoken','id','mtcode','round','amount','datetime','roundnumber','tabletype','tablename','tableid'];
		}else if($transaction_type == self::METHOD_REFUND){
			$requiredParams = ['mtcode'];
		}
		try { 
			#get player with transaction id in looping DB
			if($transaction_type==self::METHOD_REFUND){
				$gameUsername = $this->getPlayerFromTransactionId($this->request['mtcode']);
				$this->utils->debug_log('ASTAR-transaction-'.$transaction_type, $gameUsername);
			}

			if(!$this->initialize()) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_NOT_ALLOWED);
			}

			if(!$this->checkAuthorizationToken()){
				throw new Exception(self::ERROR_INVALID_AUTHORIZATION_TOKEN);
			}

			if(strtolower($this->merchant_id) != strtolower($this->game_api->merchant_id)){
				throw new Exception(self::ERROR_INVALID_MERCHANT_ID);
			}
			
			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
           
			$request 				= $this->request;

			if($transaction_type==self::METHOD_REFUND){
				$this->utils->debug_log('ASTAR-transaction-debug-gameusername', $gameUsername);
				list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($gameUsername);
				if(!$getPlayerStatus){
					throw new Exception(self::ERROR_INVALID_PLAYER);
				}
			}else{
				$original_token         = $this->request['gametoken'];
				$token      			= $this->explodeToken($original_token)['token'];
				list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($token);
				if(!$getPlayerStatus){
					throw new Exception(self::ERROR_INVALID_PLAYER);
				}
			}
			$this->utils->debug_log('ASTAR-transaction-'.$transaction_type, $request);
			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'player_token' 		=> isset($request['gametoken']) ? $request['gametoken'] : null,
			'player' 		 	=> isset($request['id']) ? $request['id'] : null,
			'transaction_id' 	=> isset($request['mtcode']) ? $request['mtcode'] : null,
			'round' 		 	=> isset($request['round']) ? $request['round'] : null,
			'amount' 		 	=> isset($request['amount']) ? $request['amount'] : null,
			'bet' 		 		=> isset($request['bet']) ? $request['bet'] : null,
			'win' 		 		=> isset($request['win']) ? $request['win'] : null,
			'rake' 		 		=> isset($request['rake']) ? $request['rake'] : null,
			'datetime' 		 	=> isset($request['datetime']) ? $this->game_api->gameTimeToServerTime($request['datetime']) : null,
			'game_code' 	    => isset($request['gamecode']) ? $request['gamecode'] : null,
			'roomfee' 		 	=> isset($request['roomfee']) ? $request['roomfee'] : null,
			'valid_bet' 		=> isset($request['validbet']) ? $request['validbet'] : null,
			'round_number' 		=> isset($request['roundnumber']) ? $request['roundnumber'] : null,
			'table_type' 		=> isset($request['tabletype']) ? $request['tabletype'] : null,
			'table_name' 		=> isset($request['tablename']) ? $request['tablename'] : null,
			'table_id' 		 	=> isset($request['tableid']) ? $request['tableid'] : null,
		];
		
		$params['external_uniqueid'] = md5(json_encode($this->request));

		if($player_id){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,$request,$params,$transaction_type,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("ASTAR-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($transaction_type, $player, $params);
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("ASTAR SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);
		if(!$trans_success && $errorCode == self::SUCCESS){
			$errorCode = self::ERROR_INTERNAL_ERROR;
			$this->utils->debug_log("ASTAR SEAMLESS SERVICE-lockandtrans-FAILED");	
		}

		$externalResponse = [
            'data' => [
               'balance'    => $balance,
               'currency'   => $errorCode == self::SUCCESS ? $this->game_api->currency : null,
            ],
            'status' => [
                'code'      => $errorCode == self::SUCCESS ? self::SUCCESS : $errorCode,
                'message'   => self::ERROR_MESSAGE[$errorCode],
                'datetime'  => $this->formatDate('now', 'Y-m-d\TH:i:sP', '-04:00'),
                'tracecode' => $this->generateTrackerId(),
            ]
        ];
		
		if($transaction_type == self::METHOD_DEBIT){
			$externalResponse['data']['amount'] =  $request['amount'];
		}

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function roundcheck(){
		$fromdate 	   = $this->adjustDateTime($this->input->get('fromdate'), '+12 hours');
		$todate 	   = $this->adjustDateTime($this->input->get('todate'),'+12 hours');
		$bet_round_ids = $this->getBetRoundIds($fromdate, $todate);
        $this->utils->debug_log("ASTAR SEAMLESS SERVICE: (Round Check)" , $fromdate , $todate);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_ROUND_CHECK;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$success 					= true;
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			$this->utils->debug_log('ASTAR-roundcheck', $this->request);
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		
		$externalResponse = [
            'data' => $bet_round_ids,
            'status' => [
                'code'      => $errorCode == self::SUCCESS ? self::SUCCESS : $errorCode,
                'message'   => self::ERROR_MESSAGE[$errorCode],
                'datetime'  => $this->formatDate('now', 'Y-m-d\TH:i:sP', '-04:00'),
                'tracecode' => $this->generateTrackerId(),
            ]
        ];

		$fields = [
			'player_id'		=> null,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function getBetRoundIds($fromdate, $todate){
		$rounds = [];
		$currencies = ['usd','vnd'];
		foreach($currencies as $currency){
			$isDBswitchSuccess = $this->loadDbByCurrency($currency);
			if(!$isDBswitchSuccess){
				break;
			}
			$bets = $this->astar_seamless_transactions->getIncompleteBets($fromdate, $todate);
			foreach($bets as $bet){
				$rounds[] = $bet['round'];
			}
		}
		return $rounds;
	}

	public function adjustDateTime($dateString, $interval=null){
        $date = new DateTime($dateString); 
        if($interval){
            // $date->modify('+12 hours');
            $date->modify($interval);
        }
        $formatted_date = $date->format('Y-m-d H:i:s');
        return $formatted_date;
    }

	public function getPlayerFromTransactionId($round){
		$currencies = ['usd','vnd'];
		$player = null;
		foreach($currencies as $currency){
			$isDBswitchSuccess = $this->loadDbByCurrency($currency);
			if(!$isDBswitchSuccess){
				break;
			}
			$player = $this->astar_seamless_transactions->getPlayerFromTransactionId($round);
			$this->utils->debug_log('ASTAR--getPlayerFromTransactionId-'.$currency, $player);
			if($player){
				return $player;
			}
		}
		return $player;
	}

	public function checkAuthorizationToken(){
		$response = false;
		if($this->game_api->authorization_token == $_SERVER['HTTP_AUTHORIZATION']){
			$response = true;
		}
		$this->utils->debug_log('ASTAR--checkAuthorizationToken', $response);
		return $response;
	}

    public function generateTrackerId(){
        return bin2hex(random_bytes(5)); 
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
			$this->utils->debug_log('ASTAR-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

    public function successDBswitchFromToken($currencyToken){
		# set currency based on the currency-token
		$dbLoaded = false;
		if($currencyToken){
			$dbLoaded =$this->loadDbFromToken($currencyToken);
            if(!$dbLoaded){
                $this->utils->error_log('ASTAR ' . __METHOD__ , $this->request_method . ' cannot load DB');
            }
		}
		
        return $dbLoaded;
    }

	public function successDBswitchFromGameUsername($currencyGameUsername){
		# set currency based on the currency-token
		$dbLoaded = false;
		if($currencyGameUsername){
			$dbLoaded =$this->loadDbFromGameUsername($currencyGameUsername);
            if(!$dbLoaded){
                $this->utils->error_log('ASTAR ' . __METHOD__ , $this->request_method . ' cannot load DB');
            }
		}
		
        return $dbLoaded;
    }


	public function adjustWallet($transaction_type,$player_info,$data){
		#bet_transaction_id is used for rollback transansaction
		$bet_transaction_id = $data['transaction_id'];
		#----

		if($transaction_type == self::METHOD_REFUND){
			$data['external_uniqueid'] = 'rollback-'.$data['transaction_id'];
			$data['transaction_id']    = 'rollback-'.$data['transaction_id'];
		}
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->getPlayerBalanceById($playerId);
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service);
        $this->wallet_model->setExternalGameId($data['table_id']);


		if($transaction_type == self::METHOD_DEBIT){
			$amount 		= $data['amount'] * -1; #getting the negative betting value
			$data['amount']	= $this->game_api->gameAmountToDB($data['amount']);
			$existingTrans  = $this->astar_seamless_transactions->isTransactionExist($data);
			if($existingTrans){
				$this->utils->debug_log('ASTAR--existing_transaction', $data,['external_uniqueid']);
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::ERROR_INVALID_AMOUNT, $playerId);
			}
			if($balance < $data['amount']){
				return $this->resultForAdjustWallet(self::ERROR_NOT_ENOUGH_BALANCE, $playerId);
			}
			$data['trans_status'] = GAME_LOGS::STATUS_UNSETTLED;
        }
		else if($transaction_type == self::METHOD_CREDIT){
			$amount = max(0, $data['amount']);
			$data['bet']				= $this->game_api->gameAmountToDB($data['bet']);
			$data['amount']				= $this->game_api->gameAmountToDB($data['amount']);
			$data['win']				= $this->game_api->gameAmountToDB($data['win']);
			$data['rake']				= $this->game_api->gameAmountToDB($data['rake']);
			$data['settlement_amount']	= $this->game_api->gameAmountToDB($data['amount']);
			$bet_transaction_details 	= $this->astar_seamless_transactions->getExistingTransactionByRound($data['round'],'debit');

			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::ERROR_TRANSACTION_NOT_FOUND, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details->trans_status == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->trans_status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::ERROR_ALREADY_CANCELLED, $playerId);
			}

			$settle_bet = $this->astar_seamless_transactions->flagBetTransactionSettled($data);
			if(!$settle_bet){
				return $this->resultForAdjustWallet(self::ERROR_INTERNAL_ERROR, $playerId); 
			}
			$data['trans_status'] = GAME_LOGS::STATUS_SETTLED;
        }
		else if($transaction_type == self::METHOD_REFUND){
			$bet_transaction_details = $this->astar_seamless_transactions->getExistingTransactionByRound($bet_transaction_id, 'debit');

			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::ERROR_TRANSACTION_NOT_FOUND, $playerId);
			}
			#check if transaction already settled
			if($bet_transaction_details->trans_status == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::ERROR_ALREADY_SETTLED, $playerId);
			}
			#check if transaction already rollback
			if($bet_transaction_details->trans_status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			$cancel_bet = $this->astar_seamless_transactions->flagBetTransactionCancelled(['transaction_id' => $bet_transaction_id]);
			if(!$cancel_bet){
				return $this->resultForAdjustWallet(self::ERROR_INTERNAL_ERROR, $playerId); 
			}
			// $amount = $bet_transaction_details->amount;
			$amount = $this->game_api->dBtoGameAmount($bet_transaction_details->amount);
			$data['trans_status'] = GAME_LOGS::STATUS_CANCELLED;
        }

		$beforeBalance 	= $this->game_api->gameAmountToDB($balance);
		$afterBalance 	= $this->game_api->gameAmountToDB($balance);
		$amount 		= $this->game_api->gameAmountToDB($amount);
		$this->utils->debug_log('ASTAR-amounts', [$beforeBalance, $afterBalance, $amount]);
		if($amount <> 0){
			if($amount > 0){ 	
				#credit
				$afterBalance 						= $beforeBalance + $amount;
				$data['balance_adjustment_method'] 	= 'credit';
				$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
				if(!$response){
					return $this->resultForAdjustWallet(self::ERROR_INTERNAL_ERROR, $playerId);
				}
				$afterBalance 						= $beforeBalance + $amount;
				$this->utils->debug_log('ASTAR', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

			}else{	
				#debit
				$amount 							= abs($amount);
				$afterBalance 						= $beforeBalance - $amount;
				$data['balance_adjustment_method'] 	= 'debit';
				$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
				if(!$response){
					return $this->resultForAdjustWallet(self::ERROR_INTERNAL_ERROR, $playerId);
				}
				$afterBalance 						= $beforeBalance - $amount;
				$this->utils->debug_log('ASTAR', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			}
		}
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
		$data['trans_type'] 				= $transaction_type;
		$data['extra_info'] 				= json_encode($this->request);

		$this->utils->debug_log('ASTAR--adjust-wallet', $data);
		$insertTransaction = $this->astar_seamless_transactions->insertTransactionData($tableName,$data);
		return $insertTransaction ? $this->resultForAdjustWallet(self::SUCCESS, $playerId)  : $this->resultForAdjustWallet(self::ERROR_INTERNAL_ERROR, $playerId);
	}

	public function resultForAdjustWallet($code, $player_id = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code == self::SUCCESS ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $code == self::SUCCESS ? $current_balance : null,
			];
		$this->utils->debug_log("ASTAR--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("ASTAR SEAMLESS SERVICE: (handleExternalResponse)",
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
		$this->utils->debug_log("##### ASTAR SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
        $request_json = file_get_contents('php://input');
        $this->utils->debug_log("ASTAR SEAMLESS SERVICE raw:", $request_json);

        $this->request = json_decode($request_json, true);

        if (!$this->request){
            parse_str($request_json, $request_json);
            $this->utils->debug_log("ASTAR SEAMLESS SERVICE raw parsed:", $request_json);
            $this->request = $request_json;
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