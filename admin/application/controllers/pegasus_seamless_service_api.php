<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Pegasus_seamless_service_api extends BaseController {

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
            $merchant_id,
            $allow_postman_generate_external_token
			;

    #error codes from GP
    const SUCCESS 			            = 0; 
    const INVALID_TOKEN                 = 401002;
    const INSUFFICIENT_FUNDS            = 403203;
    const INVALID_PARAMETERS            = 403001;
    const DUPLICATE_TRANSACTION         = 500029;
    const INVALID_TRANSACTION_STATUS    = 403601;
    const ALREADY_CANCEL                = 403612;
    const BET_NOT_FOUND                 = 404016;
    const SYSTEM_ERROR                  = 500001;


    #additional error codes
    const INVALID_IP_ADDRESS       	= 'INVALID_IP_ADDRESS';
    const INVALID_AMOUNT       	   	= 'INVALID_AMOUNT';
    const ALREADY_REFUNDED     		= 'ALREADY_REFUNDED';
    const ALREADY_SETTLED     		= 'ALREADY_SETTLED';
   
	#method
    const METHOD_WALLET  		= 'Wallet';
    const METHOD_PLACEBET  		= 'PlaceBet';
    const METHOD_SETTLE  		= 'Settle';
    const METHOD_CANCEL  		= 'Cancel';
    const METHOD_EVENT_BONUS  	= 'EventBonus';

	#transaction mode
	const ERROR_MESSAGE = [
		self::SUCCESS                   	=> 'success',
        self::INVALID_TOKEN                 => 'Invalid Token',
        self::INSUFFICIENT_FUNDS            => 'Insufficient Funds',
        self::INVALID_PARAMETERS            => 'Invalid Request Parameters',
        self::DUPLICATE_TRANSACTION         => 'Duplicate transaction',
        self::INVALID_TRANSACTION_STATUS    => 'Invalid transaction status',
        self::ALREADY_CANCEL                => 'Already Cancel',
        self::BET_NOT_FOUND                 => 'Bet not found',
        self::SYSTEM_ERROR                  => 'System Error',
		self::INVALID_IP_ADDRESS        	=> 'Invalid Ip Address',
		self::INVALID_AMOUNT        		=> 'Invalid Amount',
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','external_common_tokens','game_logs','pegasus_seamless_transactions', 'player_model'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index(...$methods){
        $method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('PEGASUS--method : '. $method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'pgs/players/wallet':
				$this->wallet();
				break;
			case 'pgs/bet/placeBet':
				$this->placeBet();
				break;
			case 'pgs/bet/settle':
				$this->settle();
				break;
			case 'pgs/bet/cancel':
				$this->cancel();
				break;
			case 'pgs/transaction/event-bonus':
				$this->eventBonus();
				break;
            case 'generatePlayerToken':
                $this->generatePlayerToken();
                break;
            case 'generatePlayerTokenAndSessionToken':
                $this->generatePlayerTokenAndSessionToken();
                break;
			default:
				$this->utils->debug_log('PEGASUS seamless service: Invalid API Method');
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

	public function generatePlayerTokenAndSessionToken(){
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

		if(!$this->allow_postman_generate_external_token){
			return [
				'success'   => 'error',
				'message'   => 'not allowed',
			];
		}

		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE: (generatePlayerTokenAndSessionToken) ", $this->request);


		$this->CI->load->model(array('player_model'));
		$player_username 	= isset($this->request['username']) ? $this->request['username'] : null;
        $player_id 			= $this->CI->player_model->getPlayerIdByUsername($player_username);
		$token			 	= $this->common_token->getPlayerToken($player_id);
		$response =  [
			'username' => $player_username,
			'token'    => $token,
		];

		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE: (generatePlayerTokenAndSessionToken) ", $response);

		$this->CI->external_common_tokens->addPlayerToken($player_id, md5($response['token'] . time()),  $this->game_platform_id);
		$sessionToken = $this->CI->external_common_tokens->getPlayerActiveExternalTokens($player_id, $this->game_platform_id);


		$externalResponse = [
			'code' => self::SUCCESS,
			'data' => [
				'username' => $player_username,
				'token'    => $token,
				'sessionToken'    => isset($sessionToken[0]) ? $sessionToken[0] : null,
			]
		];

		$fields = [
			'player_id'		=> $player_id,
		];
		$errorCode 					= self::SUCCESS;

		$success = empty($sessionToken[0]) ? false : true;
		$errorCode = empty($sessionToken[0]) ? self::SYSTEM_ERROR : self::SUCCESS;

		return $this->handleExternalResponse($success, 'generatePlayerTokenAndSessionToken', $this->request, $externalResponse, $errorCode, $fields);	

		print_r($response); exit;
	}

	public function initialize(){
		$this->game_platform_id = PEGASUS_SEAMLESS_GAME_API;
		$this->game_api 	= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("PEGASUS SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 				= $this->game_api->getCurrency();
        $this->merchant_id 				= $this->game_api->merchant_id;
        $this->allow_postman_generate_external_token 				= $this->game_api->allow_postman_generate_external_token;

		if (isset($_SERVER['HTTP_X_PGS_IDENTITY']) && $_SERVER['HTTP_X_PGS_IDENTITY'] === $this->game_api->x_pgs_identity) {
			return true;
		} else {
			return false;
		}
	}

    public function getError($errorCode){
        $error_response = [
            'code'    => $errorCode,
            'message' => self::ERROR_MESSAGE[$errorCode],
            'data' 	  => null,
        ];

        return $error_response;
    }

    public function wallet(){
        $this->utils->debug_log("PEGASUS SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_WALLET;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['sessionToken'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['sessionToken']);
			if(!$getPlayerStatus){
				throw new Exception(self::INVALID_TOKEN);
			}
			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance      = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
            $externalResponse = [
                'code' => self::SUCCESS,
                'data' => [
    				'balance' => $balance
                ]
			];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }


	public function placeBet(){
		$callType 					= self::METHOD_PLACEBET;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['sessionToken', 'betAmount','gameID', 'transactionNo', 'playerUniqueID', 'extraInfo'];
		$this->utils->debug_log('PEGASUS-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['sessionToken']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_TOKEN);
            }

			$player_id = $player->player_id;

			if($this->isMismatchPlayerAndSessionToken($player_id)){
                throw new Exception(self::INVALID_TOKEN);
			}
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'session_token' 	  => isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
			'bet_amount' 	      => isset($this->request['betAmount']) ? $this->game_api->gameAmountToDB($this->request['betAmount']) : 0,
			'game_id' 	          => isset($this->request['gameID']) ? $this->request['gameID'] :  null,
			'transaction_id' 	  => isset($this->request['transactionNo']) ? $this->request['transactionNo'] : null,
			'player_unique_id' 	  => isset($this->request['playerUniqueID']) ? $this->request['playerUniqueID'] : null,
			'extra_info' 	      => isset($this->request['extraInfo']) ? $this->request['extraInfo'] : null,
			'round_id' 	          => isset($this->request['extraInfo']['roundID']) ? $this->request['extraInfo']['roundID'] : null,
			'external_uniqueid'   => isset($this->request['transactionNo']) ? self::METHOD_PLACEBET.'-'.$this->request['transactionNo'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("PEGASUS-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'code' => self::SUCCESS,
				'data' => [
					'balance'		=> $balance,
					'transactionNo' => $params['transaction_id'],
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

	public function settle(){
		$callType 					= self::METHOD_SETTLE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['transactionNo', 'gameID','betAmount', 'taxAmount', 'payoutAmount', 'validAmount' , 'settlementTime', 'sessionToken', 'playerUniqueID', 'extraInfo'];
		$this->utils->debug_log('PEGASUS-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
			#API Docs
			#Don’t validate sessionToken expired for settle process
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerUniqueID']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'transaction_id' 	  => isset($this->request['transactionNo']) ? $this->request['transactionNo'] : null,
			'game_id' 	          => isset($this->request['gameID']) ? $this->request['gameID'] :  null,
			'bet_amount' 	      => isset($this->request['betAmount']) ? $this->game_api->gameAmountToDB($this->request['betAmount']) : 0,
			'tax_amount' 	      => isset($this->request['taxAmount']) ? $this->game_api->gameAmountToDB($this->request['taxAmount']) : 0,
			'payout_amount'       => isset($this->request['payoutAmount']) ? $this->game_api->gameAmountToDB($this->request['payoutAmount']) : 0,
			'valid_amount'        => isset($this->request['validAmount']) ? $this->game_api->gameAmountToDB($this->request['validAmount']) : 0,
			'settlement_time' 	  => isset($this->request['settlementTime']) ? $this->request['settlementTime'] : null,
			'session_token' 	  => isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
			'player_unique_id' 	  => isset($this->request['playerUniqueID']) ? $this->request['playerUniqueID'] : null,
			'extra_info' 	      => isset($this->request['extraInfo']) ? $this->request['extraInfo'] : null,
			'round_id' 	          => isset($this->request['extraInfo']['roundID']) ? $this->request['extraInfo']['roundID'] : null,
			'external_uniqueid'   => isset($this->request['transactionNo']) ? self::METHOD_SETTLE.'-'.$this->request['transactionNo'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("PEGASUS-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'code' => self::SUCCESS,
				'data' => [
					'balance'		=> $balance,
					'transactionNo' => $params['transaction_id']
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


	public function cancel(){
		$callType 					= self::METHOD_CANCEL;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['gameID', 'transactionNo','sessionToken', 'playerUniqueID'];
		$this->utils->debug_log('PEGASUS-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
			
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerUniqueID']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'game_id' 	          => isset($this->request['gameID']) ? $this->request['gameID'] :  null,
			'transaction_id' 	  => isset($this->request['transactionNo']) ? $this->request['transactionNo'] : null,
			'session_token' 	  => isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
			'player_unique_id' 	  => isset($this->request['playerUniqueID']) ? $this->request['playerUniqueID'] : null,
			'external_uniqueid'   => isset($this->request['transactionNo']) ? self::METHOD_CANCEL.'-'.$this->request['transactionNo'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("PEGASUS-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}
		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'code' => self::SUCCESS,
				'data' => [
					'transactionNo' => $params['transaction_id'],
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

	public function eventBonus(){
		$callType 					= self::METHOD_EVENT_BONUS;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams = ['transactionNo', 'amount','playerUniqueID', 'eventDetailInfo'];
		$this->utils->debug_log('PEGASUS-transaction:',$this->request);
		
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETERS);
			}
		
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['playerUniqueID']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_TOKEN);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'transaction_id' 	  => isset($this->request['transactionNo']) ? $this->request['transactionNo'] : null,
			'bonus_amount' 	      => isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'player_unique_id' 	  => isset($this->request['playerUniqueID']) ? $this->request['playerUniqueID'] : null,
			'event_detail_info'   => isset($this->request['eventDetailInfo']) ? $this->request['eventDetailInfo'] : null,
			'external_uniqueid'   => isset($this->request['transactionNo']) ? self::METHOD_EVENT_BONUS.'-'.$this->request['transactionNo'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$before_balance, &$after_balance
			) {
				$this->utils->debug_log("PEGASUS-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$before_balance = $adjustWalletResponse['before_balance'];
				$after_balance  = $adjustWalletResponse['after_balance'];
				return true;
			});
		}
		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'code' => self::SUCCESS,
				'data' => [
					'transactionNo' 	=> $params['transaction_id'],
					'merchantOrderNo' 	=> $this->merchant_id,
					'beforeAmount'		=> $before_balance,
					'afterAmount'		=> $after_balance,
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
	
    public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('PEGASUS-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->gameAmountToDB($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service);
		if($transaction_type == self::METHOD_PLACEBET){
			$amount 		= $data['bet_amount'] * -1; #getting the negative betting value
			$existingTrans  = $this->pegasus_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			if($data['bet_amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_AMOUNT, $playerId);
			}
			if($balance < $data['bet_amount']){
				return $this->resultForAdjustWallet(self::INSUFFICIENT_FUNDS, $playerId);
			}
			$data['status'] = GAME_LOGS::STATUS_PENDING;
        }else if($transaction_type == self::METHOD_SETTLE){
			$amount = $data['payout_amount'];
			$existingTrans  = $this->pegasus_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			#get bet transaction
			$bet_transaction_details = $this->pegasus_seamless_transactions->getExistingTransactionByTransactionId($tableName, $data['transaction_id'], self::METHOD_PLACEBET);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::BET_NOT_FOUND, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::INVALID_TRANSACTION_STATUS, $playerId);
			}

			$settle_bet = $this->pegasus_seamless_transactions->flagBetTransactionSettled($tableName, $data);
			if(!$settle_bet){

				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}

			$data['status'] 	= GAME_LOGS::STATUS_SETTLED;

			#OGP-34775 implement related_unique_id
			#implement increase/decrease balance related_uniqueid and related_action
			$related_uniqueid = isset($this->request['transactionNo']) ? 'game-'.$this->game_platform_id.'-'.self::METHOD_PLACEBET.'-'.$data['transaction_id'] : null;

			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
			}
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
			}
			if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
				$this->wallet_model->setGameProviderIsEndRound(true);
			}
		}else if($transaction_type == self::METHOD_EVENT_BONUS){
			$amount = $data['bonus_amount'];
			$existingTrans  = $this->pegasus_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId, $existingTrans->before_balance, $existingTrans->after_balance);
			}
			$data['status'] 	= GAME_LOGS::STATUS_SETTLED;
			
		}else if($transaction_type == self::METHOD_CANCEL){
			$existingTrans  = $this->pegasus_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			
			$bet_transaction_details = $this->pegasus_seamless_transactions->getExistingTransactionByTransactionId($tableName, $data['transaction_id'],self::METHOD_PLACEBET);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::BET_NOT_FOUND, $playerId);
			}

			#get the bet amount to be refunded
			$amount = $this->game_api->gameAmountToDB($bet_transaction_details->bet_amount);

			#check if transaction already settled
			if($bet_transaction_details->status == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::INVALID_TRANSACTION_STATUS, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::ALREADY_CANCEL, $playerId);
			}

			$refund_bet = $this->pegasus_seamless_transactions->flagBetTransactionRefund($tableName, $data);
			if(!$refund_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}
			$data['status'] = GAME_LOGS::STATUS_CANCELLED;


			#OGP-34775 implement related_unique_id
			#implement increase/decrease balance related_uniqueid and related_action
			$related_uniqueid = isset($this->request['transactionNo']) ? 'game-'.$this->game_platform_id.'-'.self::METHOD_PLACEBET.'-'.$data['transaction_id'] : null;

			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
			}
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
			}
			if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
				$this->wallet_model->setGameProviderIsEndRound(true);
			}
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('PEGASUS-amounts', [$beforeBalance, $afterBalance, $amount]);
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
			$this->utils->debug_log('PEGASUS', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else{	
			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('PEGASUS', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
		}
		$data['extra_info']				 	= isset($data['extra_info']) ? json_encode($data['extra_info']) : null;
		$data['event_detail_info']	 		= isset($data['event_detail_info']) ? json_encode($data['event_detail_info']) : null;
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
		$data['trans_type'] 				= $transaction_type;
		$data['request'] 					= json_encode($this->request);

		$this->utils->debug_log('PEGASUS--adjust-wallet', $data);

		$insertTransaction = $this->pegasus_seamless_transactions->insertTransaction($tableName,$data);

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
		$this->utils->debug_log("PEGASUS--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("PEGASUS SEAMLESS SERVICE: (handleExternalResponse)",
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
		$this->utils->debug_log("##### PEGASUS SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
			$this->utils->debug_log("PEGASUS SEAMLESS SERVICE raw:", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("PEGASUS SEAMLESS SERVICE raw parsed:", $request_json);
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

	private function isMismatchPlayerAndSessionToken($player_id){
		$username = $this->request['playerUniqueID'];
		$playerIdByUsername = $this->player_model->getPlayerIdByUsername( $username );
		$playerExternalToken	= $this->external_common_tokens->getExternalToken($playerIdByUsername, $this->game_platform_id);

		
		$sessionToken = $this->request['sessionToken'];
		$playerIdByExternalToken			 	= $this->external_common_tokens->getPlayerIdByExternalToken($sessionToken, $this->game_platform_id);



		$this->utils->debug_log("PEGASUS SEAMLESS SERVICE (isMismatchPlayerAndSessionToken):", [
			'$username' => $username,
			'$playerIdByUsername' => $playerIdByUsername,
			'$playerIdByExternalToken' => $playerIdByExternalToken,
			'$player_id' => $player_id,
		]);

		if($sessionToken != $playerExternalToken){
			return true;
		}
		

		if($player_id != $playerIdByExternalToken){
			return true;
		}
		return false;
	}

}///END OF FILE////////////