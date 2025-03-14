<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Idnplay_seamless_service_api extends BaseController {

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
			$enable_hint,
            $operator_id,
			$invalid_params,
			$custom_error_message
			;

    #error codes from GP

    const SUCCESS               		= 'SUCCESS';
    const INSUFFICIENT_BALANCE  		= 'INSUFFICIENT_BALANCE';
    const OPERATOR_ERROR        		= 'OPERATOR_ERROR';
    const INVALID_PARAMETER     		= 'INVALID_PARAMETER';
    const GAME_NOT_FOUND        		= 'GAME_NOT_FOUND';
    const SYSTEM_ERROR          		= 'SYSTEM_ERROR';
    const INVALID_IP            		= 'INVALID_IP';
    const SYSTEM_MAINTENANCE    		= 'SYSTEM_MAINTENANCE';
    const INVALID_OPERATOR_ID   		= 'INVALID_OPERATOR_ID';
    const INVALID_PLAYER        		= 'INVALID_PLAYER';
    const INVALID_CURRENCY      		= 'INVALID_CURRENCY';
	const BET_TRANSACTION_NOT_FOUND 	= 'BET_TRANSACTION_NOT_FOUND';
	const TRANSACTION_ALREADY_SETTLED 	= 'TRANSACTION_ALREADY_SETTLED';
	const TRANSACTION_ALREADY_SETTLED_BY_REVISION 	= 'TRANSACTION_ALREADY_SETTLED_BY_REVISION';
	const TRANSACTION_ALREADY_REFUNDED 	= 'TRANSACTION_ALREADY_REFUNDED';
	const INVALID_DEBIT_AMOUNT 			= 'INVALID_DEBIT_AMOUNT';
	const INVALID_CREDIT_AMOUNT 		= 'INVALID_CREDIT_AMOUNT';
	const INVALID_BET_TYPE				= 'INVALID_BET_TYPE';
	const TRANSACTION_REVISION_FAILED   = 'TRANSACTION_REVISION_FAILED';


    const ERROR_DETAILS = [
        'SUCCESS' => [
            'error_code' => 0,
            'message' => 'Success'
        ],
        'INSUFFICIENT_BALANCE' => [
            'error_code' => 301,
            'message' => 'Insufficient balance'
        ],
        'OPERATOR_ERROR' => [
            'error_code' => 302,
            'message' => 'Operator error'
        ],
        'INVALID_PARAMETER' => [
            'error_code' => 303,
            'message' => 'Invalid/Missing parameter'
        ],
        'GAME_NOT_FOUND' => [
            'error_code' => 304,
            'message' => 'Game not found'
        ],
        'SYSTEM_ERROR' => [
            'error_code' => 400,
            'message' => 'System Error'
        ],
        'INVALID_IP' => [
            'error_code' => 400,
            'message' => 'Invalid IP address'
        ],
        'SYSTEM_MAINTENANCE' => [
            'error_code' => 400,
            'message' => 'System maintenance'
        ],
        'INVALID_OPERATOR_ID' => [
            'error_code' => 303,
            'message' => 'Invalid operator id'
        ],
        'INVALID_PLAYER' => [
            'error_code' => 303,
            'message' => 'Invalid player'
        ],
        'INVALID_CURRENCY' => [
            'error_code' => 303,
            'message' => 'Invalid Currency'
        ],
		'BET_TRANSACTION_NOT_FOUND' => [
            'error_code' => 303,
            'message' => 'Transaction not found'
        ],
		'TRANSACTION_ALREADY_SETTLED' => [
            'error_code' => 303,
            'message' => 'Transaction already settled',
        ],
		'TRANSACTION_ALREADY_SETTLED_BY_REVISION' => [
            'error_code' => 303,
            'message' => 'Transaction already settled by revision',
        ],
		'TRANSACTION_ALREADY_REFUNDED' => [
            'error_code' => 303,
            'message' => 'Transaction already refunded'
        ],
		'INVALID_DEBIT_AMOUNT' => [
            'error_code' => 303,
            'message' => 'Invalid debit amount'
        ],
		'INVALID_CREDIT_AMOUNT' => [
            'error_code' => 303,
            'message' => 'Invalid credit amount'
        ],
		'INVALID_BET_TYPE' => [
            'error_code' => 303,
            'message' => 'Invalid bet type'
        ],
		'TRANSACTION_REVISION_FAILED' => [
			'error_code' => 303,
			'message' => 'Revision failed. Transaction not yet processed, theres no to revise'
		]
		
    ];
    
	#method
    const METHOD_AUTH    = 'auth';
    const METHOD_DEBIT   = 'debit';
    const METHOD_CREDIT  = 'credit';

	const BET_TYPE_BUY 			= 'Buy';
	const BET_TYPE_WIN 			= 'Win';
	const BET_TYPE_REFUND 		= 'Refund';
	const BET_TYPE_CORRECTION 	= 'Correction';

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','original_seamless_wallet_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
		$this->invalid_params = [];
		$this->custom_error_message = null;
	}

	public function index(...$methods){
        $method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('IDN PLAY--method : '. $method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'auth':
				$this->auth();
				break;
			case 'debit':
				$this->debit();
				break;
			case 'credit':
				$this->credit();
				break;
			default:
				$this->utils->debug_log('IDN PLAY seamless service: Invalid API Method');
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
		$this->game_platform_id = IDN_PLAY_SEAMLESS_GAME_API;
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->error_log("IDN PLAY SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency = $this->game_api->getCurrency();
		$this->enable_hint = $this->game_api->enable_hint;
        $this->operator_id = $this->game_api->operator_id;
		return true;

	}

    public function getError($errorCode){
        $error_response = [
			'error_code' => self::ERROR_DETAILS[$errorCode]['error_code'],
            'message'    => self::ERROR_DETAILS[$errorCode]['message']
        ];
		
		if($errorCode == self::INVALID_PARAMETER){
			$error_response['message'] = self::ERROR_DETAILS[$errorCode]['message'].': '.implode(',',$this->invalid_params);
		}

		if(!empty($this->custom_error_message)){
			$error_response['message'] = $this->custom_error_message;
		}

        return $error_response;
    }

    public function auth(){
        $this->utils->debug_log("IDN PLAY SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_AUTH;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['operatorId','token', 'currency','gameId'];
		try { 
			if(!$this->initialize()) {
				throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if (!$this->isParamsValid($this->request, $requiredParams)) {
				throw new Exception(self::INVALID_PARAMETER);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['token']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_PLAYER);
            }
			

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	    = $error->getMessage();
			$success 	    = false;
		}	

		if($success){
			$balance = $this->game_api->getPlayerBalanceById($player_id);
            $externalResponse = [
                "status" => true,
                "operatorId" => (int) $this->operator_id,
                "sessionToken" => $this->request['token'],
                "uid" => $player_id,
                "nickname" => $game_username,
                "balance" => $balance,
                "currency" => $this->currency,
                "table" => $this->game_api->getSystemInfo('bet_limit_id', 'B'),
                "language" => !empty($this->request['language']) ? $this->request['language'] : 'en',
                "clientIP" => "119.9.106.90", #$this->utils->getIP()
                "timestamp" => time()
			];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'	=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function debit(){
		$callType 					= self::METHOD_DEBIT;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
		
        $requiredParams = [
			'time',
			'operatorId',
			'sessionToken',
			'post_params' => [
				[
					'uid', 
					'transactionId', 
					'roundId', 
					'gameId', 
					'gamegroup', 
					'gamename', 
					'providerId', 
					'currency', 
					'nickname', 
					'debitAmount', 
					'betStatus', 
					'betType', 
					'betDetails',
					'jsonDetails',
				]
			]
		];

		$this->utils->debug_log('IDN PLAY-transaction: '.$callType,$this->request);
		try { 
			if(!$this->initialize()) {
				throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->isParamsValid($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETER);
			}
            
			if(isset($this->request['revision']) && $this->request['revision']){
				list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['post_params'][0]['nickname']);
				if(!$getPlayerStatus){
					throw new Exception(self::INVALID_PLAYER);
				}
			}else{
				list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($this->request['sessionToken']);
				if(!$getPlayerStatus){
					throw new Exception(self::INVALID_PLAYER);
				}
			}

		$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	    = $error->getMessage();
			$success 	    = false;
		}	

		#as per GP, debit only contains single transaction. So using $this->request['post_params'][0] should be okay
		$params = [
			'operator_id' 		=> $this->request['operatorId'],
			'session_token' 	=> $this->request['sessionToken'],
			'uid' 				=> $this->request['post_params'][0]['uid'],
			'transaction_id' 	=> $this->request['post_params'][0]['transactionId'],
			'round_id' 			=> $this->request['post_params'][0]['roundId'],
			'game_id' 			=> $this->request['post_params'][0]['gameId'],
			'game_name' 		=> $this->request['post_params'][0]['gamename'],
			'game_group' 		=> $this->request['post_params'][0]['gamegroup'],
			'provider_id' 		=> $this->request['post_params'][0]['providerId'],
			'currency' 			=> $this->request['post_params'][0]['currency'],
			'nickname' 			=> $this->request['post_params'][0]['nickname'],
			'debit_amount' 		=> isset($this->request['post_params'][0]['debitAmount']) ? $this->game_api->gameAmountToDB($this->request['post_params'][0]['debitAmount']) : null,
			'bet_status' 		=> $this->request['post_params'][0]['betStatus'],
			'bet_type' 			=> $this->request['post_params'][0]['betType'],
			'bet_details' 		=> $this->request['post_params'][0]['betDetails'],
			'json_details' 		=> $this->request['post_params'][0]['jsonDetails'],
			'time'				=> isset($this->request['time']) ? $this->request['time'] : null,

			#additional for db saving
			'transaction_date'			=> isset($this->request['time']) ? date("Y-m-d H:i:s", $this->request['time']) : $this->utils->getNowForMysql(),
			'trans_type'				=> $callType,
			'external_uniqueid' 		=> isset($this->request['post_params'][0]['transactionId']) ? $this->request['post_params'][0]['transactionId'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("IDN PLAY-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){	
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("IDN PLAY SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
				$externalResponse = [
					"status" => true,
					"operatorId" => 287495130,
					"data" => [
						[
							"uid" => $params['uid'],
							"valid" => true,
							"balance" => $balance,
							"roundId" => $params['round_id'],
							"currency" => $this->currency,
							"nickname" => $params['nickname'],
							"timestamp" => time(),
							"transactionId" => $params['transaction_id']
						]
					]
				];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		#to trigger refund
		if($this->game_api->getSystemInfo('force_debit_error', false) && in_array($params['nickname'], $this->game_api->getSystemInfo('force_debit_error_usernames', []))){
			$externalResponse = $this->getError(self::SYSTEM_ERROR);
		}

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function credit(){
		$callType 					= self::METHOD_CREDIT;
		$errorCode 					= self::SYSTEM_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
		
        $requiredParams = [
			'time',
			'operatorId',
			'sessionToken',
			'post_params' => [
				[
					'uid', 
					'transactionId', 
					'roundId', 
					'gameId', 
					'currency', 
					'nickname', 
					'creditAmount', 
					'betType', 
					'gamename', 
					'gamegroup', 
					'providerId', 
					'betDetails',
					'jsonDetails', 
					'refDetails', 
				]
			]
		];

		$this->utils->debug_log('IDN PLAY-transaction: '.$callType,$this->request);
		try { 
			if(!$this->initialize()) {
				throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->isParamsValid($this->request,$requiredParams)){
				throw new Exception(self::INVALID_PARAMETER);
			}

		} catch (Exception $error) {
			$errorCode 	    = $error->getMessage();
			$success 	    = false;
		}	


		$multiple_transaction_response = [];
		
		#multiple transaction, need looping
		foreach($this->request['post_params'] as $transaction){
			$params = [
				'operator_id' 		=> $transaction['operatorId'],
				'uid' 				=> $transaction['uid'],
				'transaction_id' 	=> $transaction['transactionId'],
				'round_id' 			=> $transaction['roundId'],
				'game_id' 			=> $transaction['gameId'],
				'currency' 			=> $transaction['currency'],
				'nickname' 			=> $transaction['nickname'],
				'credit_amount' 	=> isset($transaction['creditAmount']) ? $this->game_api->gameAmountToDB($transaction['creditAmount']) : null,
				'bet_type' 			=> $transaction['betType'],
				'freespin' 			=> $transaction['freespin'],
				'game_name' 		=> $transaction['gamename'],
				'game_group' 		=> $transaction['gamegroup'],
				'provider_id' 		=> $transaction['providerId'],
				'bet_details' 		=> $transaction['betDetails'],
				'json_details' 		=> $transaction['jsonDetails'],
				'ref_details' 		=> $transaction['refDetails'],
				'time'				=> isset($this->request['time']) ? $this->request['time'] : null,
				'session_token'		=> isset($this->request['sessionToken']) ? $this->request['sessionToken'] : null,
	
				#additional for db saving
				'transaction_date'			=> isset($this->request['time']) ? date("Y-m-d H:i:s", $this->request['time']) : $this->utils->getNowForMysql(),
				'trans_type'				=> $callType,
				'external_uniqueid' 		=> isset($transaction['transactionId']) ? $transaction['transactionId'] : null,
			];

			if($params['bet_type'] == self::BET_TYPE_REFUND){
				$params['external_uniqueid'] = isset($transaction['transactionId']) ? self::BET_TYPE_REFUND.'-'.$transaction['transactionId'] : null;
			}

			#player validation
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($transaction['nickname']);
			if(!$getPlayerStatus){
				$success 		= false;
				$errorCode 	    = self::INVALID_PLAYER;
				$this->custom_error_message = 'Invalid Player: '.$transaction['nickname'];
				#stop looping, return error
				break; 
			}

			if($success){
				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
				&$errorCode, &$adjustWalletResponse,&$balance
				) {
					$this->utils->debug_log("IDN PLAY-lockAndTransForPlayerBalance proceed with adjusting wallet");
					$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
	
					if(!$adjustWalletResponse['success']){	
						$errorCode = $adjustWalletResponse['code'];
						return false;	
					}
					$balance = $adjustWalletResponse['current_balance'];
					return true;
				});
			}

	
			$this->utils->debug_log("IDN PLAY SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

			$multiple_transaction_response[] = 
			[
				"uid" => $params['uid'],
				"valid" => $trans_success,
				"balance" => $balance,
				"roundId" => $params['round_id'],
				"currency" => $this->currency,
				"nickname" => $params['nickname'],
				"timestamp" => time(),
				"operatorId" => $params['operator_id'],
				"transactionId" => $params['transaction_id']
			];
		}
		

		if($trans_success){
				$externalResponse = [
					"status" => true,
					"operatorId" => 287495130,
					"data" => $multiple_transaction_response
				];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function validate_currency() {
		$currencies = $this->findKeys($this->request, 'currency'); # get all 'currency' values
		foreach ($currencies as $currency) {
			if (strtolower($currency) !== strtolower($this->currency)) {
				return false; 
			}
		}
		return true; 
	}

	private function findKeys($array, $key, &$results = []) {
		if (!is_array($array)) {
			return $results;
		}
	
		foreach ($array as $k => $value) {
			if ($k === $key) {
				$results[] = $value; 
			}
	
			if (is_array($value)) {
				$this->findKeys($value, $key, $results); 
			}
		}
	
		return $results;
	}

	public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('IDN PLAY-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
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
		if($transaction_type == self::METHOD_DEBIT){
			#revision - WIN
			if(isset($this->request['revision']) && $this->request['revision'] ){
				$amount = $data['debit_amount'];
				$processWinTransaction = $this->processWinTransaction($data, $playerId, $balance);
				#if have error, throw result
				if(!empty($processWinTransaction)){
					return $processWinTransaction;
				}

				$data['win_amount'] 	= $data['debit_amount'];
				$data['status'] 		= GAME_LOGS::STATUS_SETTLED;
				
			}else{
				$amount 		= $data['debit_amount'] * -1; #getting the negative betting value
				$processDebitTransaction = $this->processDebitTransaction($data, $playerId, $balance);
				#if have error, throw result
				if(!empty($processDebitTransaction)){
					return $processDebitTransaction;
				}
			}

        }else if($transaction_type == self::METHOD_CREDIT && $data['bet_type'] == self::BET_TYPE_WIN){
			$amount = $data['credit_amount'];

			#validation function for win transaction
			$processWinTransaction = $this->processWinTransaction($data, $playerId, $balance);
			#if have error, throw result
			if(!empty($processWinTransaction)){
				return $processWinTransaction;
			}
			
			#additional validation
			#validate negative amounts for win amount
			if($data['credit_amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_CREDIT_AMOUNT, $playerId);
			}

			$data['win_amount'] 	= $data['credit_amount'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

		}else if($transaction_type == self::METHOD_CREDIT && $data['bet_type'] == self::BET_TYPE_REFUND){
			$amount = $data['credit_amount'];

			$processRefundTransaction = $this->processRefundTransaction($data, $playerId, $balance);

			#if have error, throw result
			if(!empty($processRefundTransaction)){
				return $processRefundTransaction;
			}
			
			$data['status'] = GAME_LOGS::STATUS_REFUND;
		}else {
			return $this->resultForAdjustWallet(self::INVALID_BET_TYPE, $playerId);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('IDN PLAY-amounts', [$beforeBalance, $afterBalance, $amount]);

		$amount_operator = '>';
		$enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
		if(!empty($enabled_remote_wallet_client_on_currency)){
			if($this->utils->isEnabledRemoteWalletClient()){
				$amount_operator = '>=';
			}
		}

		if(($amount <> 0 && $amount_operator == '>') || ($amount_operator == '>=')){
			if($this->utils->compareResultFloat($amount, $amount_operator, 0)){ 	
				#credit
				$data['balance_adjustment_method'] 	= 'credit';
				$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
				if(!$response){
					return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
				}
				$afterBalance 						= $beforeBalance + $amount;
				$this->utils->debug_log('IDN PLAY', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			}else{	
				#debit
				$amount 							= abs($amount);
				$data['balance_adjustment_method'] 	= 'debit';
				$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
				$afterBalance 						= $beforeBalance - $amount;
				$this->utils->debug_log('IDN PLAY', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
				if(!$response){
					return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
				}
			}
		}

		#additional transaction info for saving 
		$data['extra_info']				 	= $this->raw_request;
		$data['json_details']				= json_encode($data['json_details']);
		$data['ref_details']				= !empty($data['ref_details']) ? json_encode($data['ref_details']) : null;
		$data['freespin']					= !empty($data['freespin']) ? $data['freespin'] : null;
		$data['currency']				 	= $currency;
		$data['amount'] 					= $amount;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
        $data['username'] 				    = $data['nickname'];
		
		
		$this->utils->debug_log('IDN PLAY--adjust-wallet', $data);
		#saving to wallet transaction
		$insertTransaction = $this->original_seamless_wallet_transactions->insertTransactionData($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::SUCCESS, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
	}


	public function processWinTransaction(&$data, $playerId, $balance){
		$tableName 						= $this->game_api->getTransactionsTable();
		$previousTableName				= $this->game_api->getTransactionsPreviousTable();
		$isExistWhereParams = [
			'external_uniqueid' => $data['external_uniqueid'],
			'game_platform_id' => $this->game_platform_id
		];

		$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
		if(empty($existingTrans) && $this->checkPreviousMonth()){
			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
		}

		#idempotency - return success
		if($existingTrans){
			return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
		}

		#get transaction
		$get_transaction_where = [
			'round_id' => $data['round_id'],
			'game_platform_id' => $this->game_platform_id,
		];

		$get_transaction_select_column = [
			'round_id', 'status', 'bet_type'
		];

		$get_transaction = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($tableName, $get_transaction_where, $get_transaction_select_column);
		if(empty($get_transaction) && $this->checkPreviousMonth()){
			$get_transaction  = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($previousTableName, $get_transaction_where, $get_transaction_select_column);
		}

		$bet_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_BUY);
		$refund_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_REFUND);
		$win_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_WIN);
		$revision_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_CORRECTION);
		
		#validate bet transaction
		if(empty($bet_transaction)){
			return $this->resultForAdjustWallet(self::BET_TRANSACTION_NOT_FOUND, $playerId);
		}

		#validate if the tranasction already processed by refund
		if(!empty($refund_transaction)){
			return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_REFUNDED, $playerId);
		}

		#validate if the tranasction already processed by win
		if(!empty($win_transaction)){
			return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_SETTLED, $playerId);
		}

		#for credit win, validate the request if already processed win by revision
		if($data['trans_type'] == self::METHOD_CREDIT && !empty($revision_transaction)){
			return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_SETTLED_BY_REVISION, $playerId);
		}

		#for revision to bet lose, validate the request if its not yet processed by win, if not, throw an error since transaction not yet process, there's no to revise
		#note: revision also use /debit method
		if($data['trans_type'] == self::METHOD_DEBIT && $data['bet_status'] != strtolower(self::BET_TYPE_WIN)){
			return $this->resultForAdjustWallet(self::TRANSACTION_REVISION_FAILED, $playerId);
		}

		#implement action type
		if(method_exists($this->wallet_model, 'setGameProviderActionType')){
			$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
		}

		#implement isEndRound
		if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
			$this->wallet_model->setGameProviderIsEndRound(true);
		}
	}

	public function processDebitTransaction(&$data, $playerId, $balance){
		$tableName 						= $this->game_api->getTransactionsTable();
		$previousTableName				= $this->game_api->getTransactionsPreviousTable();
		$data['bet_amount'] = $data['debit_amount'];
			$isExistWhereParams = [
				'external_uniqueid' => $data['external_uniqueid'],
				'game_platform_id' => $this->game_platform_id
			];

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
			if(empty($existingTrans) && $this->checkPreviousMonth()){
				$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
			}

			if($existingTrans){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			if($data['bet_amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_DEBIT_AMOUNT, $playerId);
			}

			if($balance < $data['bet_amount']){
				return $this->resultForAdjustWallet(self::INSUFFICIENT_BALANCE, $playerId);
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
	}

	public function processRefundTransaction(&$data, $playerId, $balance){
		$tableName 						= $this->game_api->getTransactionsTable();
		$previousTableName				= $this->game_api->getTransactionsPreviousTable();
		$isExistWhereParams = [
			'external_uniqueid' => $data['external_uniqueid'],
			'game_platform_id' => $this->game_platform_id
		];

		$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, $isExistWhereParams);
		if(empty($existingTrans) && $this->checkPreviousMonth()){
			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, $isExistWhereParams);
		}
		
		#idempotency, return success
		if($existingTrans){
			return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
		}

		#get transaction
		$get_transaction_where = [
			'round_id' => $data['round_id'],
			'game_platform_id' => $this->game_platform_id,
		];

		$get_transaction_select_column = [
			'round_id', 'status', 'bet_type'
		];

		$get_transaction = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($tableName, $get_transaction_where, $get_transaction_select_column);
		if(empty($get_transaction) && $this->checkPreviousMonth()){
			$get_transaction  = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($previousTableName, $get_transaction_where, $get_transaction_select_column);
		}

		$bet_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_BUY);
		$win_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_WIN);
		$revision_transaction = $this->getDetailsFromTransaction($get_transaction, self::BET_TYPE_CORRECTION);

		#validate bet transaction
		if(empty($bet_transaction)){
			return $this->resultForAdjustWallet(self::BET_TRANSACTION_NOT_FOUND, $playerId);
		}

		#validate if the tranasction already processed by credit
		if(!empty($win_transaction)){
			return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_SETTLED, $playerId);
		}

		#validate if the tranasction already processed by credit
		if(!empty($credit_transaction)){
			return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_SETTLED, $playerId);
		}

		#validate if the tranasction already processed by revision - win
		if(!empty($revision_transaction)){
			return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_SETTLED_BY_REVISION, $playerId);
		}

		#validate negative amounts for win amount
		if($data['credit_amount'] < 0){
			return $this->resultForAdjustWallet(self::INVALID_CREDIT_AMOUNT, $playerId);
		}

		#implement action type
		if(method_exists($this->wallet_model, 'setGameProviderActionType')){
			$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
		}

		#implement isEndRound
		if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
			$this->wallet_model->setGameProviderIsEndRound(true);
		}
	}

	public function getDetailsFromTransaction($data, $bet_type){
		if(!empty($data)){
			foreach($data as $transaction){
				if($transaction['bet_type'] == $bet_type){
					return $transaction;
				}
			}
		}
		return [];
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
		$this->utils->debug_log("IDN PLAY--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("IDN PLAY SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);
        
        
		$httpStatusCode =  200;

		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### IDN PLAY SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
			$this->utils->debug_log("IDN PLAY SEAMLESS SERVICE raw:", $request_json);
			$this->raw_request = $request_json; 
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("IDN PLAY SEAMLESS SERVICE raw parsed:", $request_json);
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

	public function isParamsValid($params, $required, &$invalid = [], $parentKey = '') {
		foreach ($required as $key => $value) {
			$fullKey = $parentKey ? "{$parentKey}.{$key}" : $key; #get a full key path
	
			if (is_array($value)) {
				if (array_keys($value) === range(0, count($value) - 1)) {
					if (!isset($params[$key]) || !is_array($params[$key])) {
						$invalid[] = $fullKey;
						continue;
					}
	
					foreach ($params[$key] as $index => $item) {
						$this->isParamsValid($item, $value[0], $invalid, "{$fullKey}[{$index}]");
					}
				} else {
					if (!isset($params[$key]) || !is_array($params[$key])) {
						$invalid[] = $fullKey;
						continue;
					}
					$this->isParamsValid($params[$key], $value, $invalid, $fullKey);
				}
			} else {
				if (!isset($params[$value]) || empty($params[$value])) {
					$invalid[] = $parentKey ? "{$parentKey}.{$value}" : $value;
				}
			}
		}
	
		$this->invalid_params = $invalid;
		return empty($invalid);
	}
	
	

	public function checkNestedParams($params, $required) {
		foreach ($required as $key => $value) {
			if (is_array($value)) {
				if (!isset($params[$key]) || !is_array($params[$key])) {
					return false;
				}
	
				foreach ($params[$key] as $item) {
					if (!$this->checkNestedParams($item, $value)) {
						return false;
					}
				}
			} else {
				if (!isset($params[$value])) {
					return false;
				}
			}
		}
		return true;
	}

	public function checkPreviousMonth(){
		if(date('j', $this->utils->getTimestampNow()) <= $this->game_api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
			return true;
		}

		return false;
	}

}///END OF FILE////////////