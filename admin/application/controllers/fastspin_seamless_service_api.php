<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Fastspin_seamless_service_api extends BaseController {

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
            $merchant_code
			;

    #error codes from GP
    const SUCCESS               = 0;
    const SYSTEM_ERROR          = 1;
    const INVALID_REQUEST       = 2;
    const SERVICE_INACCESSIBLE 	= 3;
    const REQUEST_TIMEOUT       = 100;
    const CALL_LIMITED          = 101;
    const REQUEST_FORBIDDEN     = 104;
    const MISSING_PARAMETERS    = 105;
    const INVALID_PARAMETERS    =  106;
    const DUPLICATED_SERIAL_NO  = 107;
    const RELATED_ID_NOT_FOUND  = 109;
    const RECORD_ID_NOT_FOUND   = 110;
    // const RECORD_ID_NOT_FOUND   = 111; #repeated
    const API_CALL_LIMITED      = 112;
    const INVALID_ACCOUNT_ID    = 113;
    const INVALID_FORMAT        = 118;
    const IP_NOT_WHITELISTED    = 120;
    const SYSTEM_MAINTENANCE    = 5003;
    const MERCHANT_NOT_FOUND    = 10113;
    const MERCHANT_SUSPENDED    = 10116;
    const ACCOUNT_EXIST         = 50099;
    const ACCOUNT_NOT_FOUND     = 50100;
    const ACCOUNT_INACTIVE      = 50101;
    const ACCOUNT_LOCKED        = 50102;
    const ACCOUNT_SUSPENDED     = 50103;
    const TOKEN_VALIDATION_FAILED = 50104;
    const INSUFFICIENT_BALANCE  = 50110;
    const EXCEED_MAX_AMOUNT     = 50111;
    const CURRENCY_INVALID      = 50112;
    const AMOUNT_INVALID        = 50113;
    const DATE_FORMAT_INVALID   = 50115;

	#method
    const METHOD_GET_BALANCE    = 'GetBalance';
    const METHOD_TRANSFER  		= 'Transfer';

	#transaction type
	const TRANSACTION_TYPE_PLACEBET 	= 1;
	const TRANSACTION_TYPE_CANCELBET 	= 2;
	const TRANSACTION_TYPE_PAYOUT 		= 4;

	#transaction mode
	const ERROR_MESSAGE = [
		self::SUCCESS               => 'Success',
        self::SYSTEM_ERROR          => 'System Error',
        self::INVALID_REQUEST       => 'Invalid Request',
        self::SERVICE_INACCESSIBLE  => 'Service Inaccessible',
        self::REQUEST_TIMEOUT       => 'Request Timeout',
        self::CALL_LIMITED          => 'Call Limited',
        self::REQUEST_FORBIDDEN     => 'Request Forbidden',
        self::MISSING_PARAMETERS    => 'Missing Parameters',
        self::INVALID_PARAMETERS    => 'Invalid Parameters',
        self::DUPLICATED_SERIAL_NO  => 'Duplicated Serial NO.',
        self::RELATED_ID_NOT_FOUND  => 'Related id not found',
        self::RECORD_ID_NOT_FOUND   => 'Record ID Not Found',
        // self::RECORD_ID_NOT_FOUND   => 'Record ID Not Found', #repeated
        self::API_CALL_LIMITED      => 'API Call Limited',
        self::INVALID_ACCOUNT_ID    => 'Invalid Acct ID',
        self::INVALID_FORMAT        => 'Invalid Format',
        self::IP_NOT_WHITELISTED    => 'IP no whitelisted',
        self::SYSTEM_MAINTENANCE    => 'System Maintenance',
        self::MERCHANT_NOT_FOUND    => 'Merchant Not Found',
        self::MERCHANT_SUSPENDED    => 'merchant suspend',
        self::ACCOUNT_EXIST         => 'Acct Exist',
        self::ACCOUNT_NOT_FOUND     => 'Acct Not Found',
        self::ACCOUNT_INACTIVE      => 'Acct Inactive',
        self::ACCOUNT_LOCKED        => 'Acct Locked',
        self::ACCOUNT_SUSPENDED     => 'Acct Suspend',
        self::TOKEN_VALIDATION_FAILED => 'Token Validation Failed',
        self::INSUFFICIENT_BALANCE  => 'Insufficient Balance',
        self::EXCEED_MAX_AMOUNT     => 'Exceed Max Amount',
        self::CURRENCY_INVALID      => 'Currency Invalid',
        self::AMOUNT_INVALID        => 'Amount Invalid',
        self::DATE_FORMAT_INVALID   => 'Date Format Invalid',
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','fastspin_seamless_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index(){
		$this->request_method = isset($_SERVER['HTTP_API']) ? $_SERVER['HTTP_API'] : null;
		$this->utils->debug_log('FASTSPIN--method : '. $this->request_method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'getBalance':
				$this->getBalance();
				break;
			case 'transfer':
				$this->transfer();
				break;
            case 'generatePlayerToken':
                $this->generatePlayerToken();
                break;
			default:
				$this->utils->debug_log('FASTSPIN seamless service: Invalid API Method');
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
		$this->game_platform_id = FASTSPIN_SEAMLESS_GAME_API;
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("FASTSPIN SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 				= $this->game_api->getCurrency();
        $this->merchant_code 			= $this->game_api->merchant_code;

		return true;
	}

    public function getError($errorCode){
        $error_response = [
            'code'    => $errorCode,
            'mgs' 	  => self::ERROR_MESSAGE[$errorCode],
        ];

        return $error_response;
    }

    public function getBalance(){
        $this->utils->debug_log("FASTSPIN SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_GET_BALANCE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['acctId'];
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

			if(!$this->validate_digest()){
				throw new Exception(self::REQUEST_FORBIDDEN);
			}

			if(!$this->validate_merchant()){
				throw new Exception(self::MERCHANT_NOT_FOUND);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::MISSING_PARAMETERS);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername(strtolower($this->request['acctId']));
            if(!$getPlayerStatus){
                throw new Exception(self::ACCOUNT_NOT_FOUND);
            }

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance      = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
            $externalResponse = [
                'acctInfo' => [
                        'userName'  => $game_username,   
                        'currency'  => $this->currency,   
                        'acctId'    => $game_username,   
                        'balance'   => $balance,   
                ],
                'merchantCode'  => $this->merchant_code,
                'msg'           => self::ERROR_MESSAGE[$errorCode],
                'code'          => $errorCode,
                'serialNo'      => isset($this->request['serialNo']) ? $this->request['serialNo'] : null,
			];
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function transfer(){
		$trans_type_text 			= $this->getTransType($this->request['type']);
		$callType 					= self::METHOD_TRANSFER.'-'.$trans_type_text;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('FASTSPIN-transaction:',$this->request);
		try { 
			if($this->request['type'] == self::TRANSACTION_TYPE_PLACEBET){
				$requiredParams = ['type','acctId','amount','currency','gameCode','transferId','merchantCode'];
			}else if($this->request['type'] == self::TRANSACTION_TYPE_PAYOUT){
				$requiredParams = ['type','acctId','amount','currency','gameCode','transferId','merchantCode','referenceId'];
			}else if($this->request['type'] == self::TRANSACTION_TYPE_CANCELBET){
				$requiredParams = ['type','acctId','amount','currency','gameCode','transferId','merchantCode','referenceId'];
			}else{
				$this->utils->debug_log('FASTSPIN-transaction type: '.$this->request['type'].' not matched');
				throw new Exception(self::REQUEST_FORBIDDEN);
			}
			
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_MAINTENANCE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::IP_NOT_WHITELISTED);
			}
			if(!$this->validate_digest()){
				throw new Exception(self::REQUEST_FORBIDDEN);
			}

			if(!$this->validate_merchant()){
				throw new Exception(self::MERCHANT_NOT_FOUND);
			}
			
			if(!$this->validate_currency()){
				throw new Exception(self::CURRENCY_INVALID);
			}

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::MISSING_PARAMETERS);
			}
			$this->request['acctId'] = strtolower($this->request['acctId']);
            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['acctId']);
            if(!$getPlayerStatus){
                throw new Exception(self::ACCOUNT_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$params = [
			'transfer_id' 		=> isset($this->request['transferId']) ? $this->request['transferId'] : null,
			'acct_id' 	  		=> isset($this->request['acctId']) ? $this->request['acctId'] : null,
			'currency' 	  		=> isset($this->request['currency']) ? $this->request['currency'] : null,
			'amount' 	  		=> isset($this->request['amount']) ? $this->game_api->gameAmountToDB($this->request['amount']) : 0,
			'type' 	  			=> isset($this->request['type']) ? $this->request['type'] : null,
			'channel' 	  		=> isset($this->request['channel']) ? $this->request['channel'] : null,
			'game_code' 	  	=> isset($this->request['gameCode']) ? $this->request['gameCode'] : null,
			'ticket_id' 	  	=> isset($this->request['ticketId']) ? $this->request['ticketId'] : null,
			'reference_id' 	  	=> isset($this->request['referenceId']) ? $this->request['referenceId'] : null,
			'special_game' 	  	=> isset($this->request['specialGame']) ? $this->request['specialGame'] : null,
			'ref_ticket_ids' 	=> isset($this->request['refTicketIds']) ? $this->request['refTicketIds'] : null,
			'player_ip' 	  	=> isset($this->request['playerIp']) ? $this->request['playerIp'] : null,
			'game_feature' 	  	=> isset($this->request['gameFeature']) ? $this->request['gameFeature'] : null,
			'transfer_time' 	=> isset($this->request['transferTime']) ? $this->request['transferTime'] : null,
			'trans_type'		=> $trans_type_text,
			'external_uniqueid' => isset($this->request['transferId']) ? 'placebet-'.$this->request['transferId'] : null,
		];

		if($this->request['type'] == self::TRANSACTION_TYPE_PAYOUT){
			$params['external_uniqueid'] = isset($this->request['referenceId']) ? 'payout-'.$this->request['transferId'].'-'.$this->request['referenceId'] : null;
		}else if($this->request['type'] == self::TRANSACTION_TYPE_CANCELBET){
			$params['external_uniqueid'] = isset($this->request['referenceId']) ? 'cancelbet-'.$this->request['transferId'].'-'.$this->request['referenceId'] : null;
		}

		$transaction_type = isset($this->request['type']) ? $this->request['type'] : null;

		if($success && $transaction_type){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,$transaction_type,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("FASTSPIN-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($transaction_type, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("FASTSPIN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'transferId' 	=> $params['transfer_id'],
				'acctId' 		=> $params['acct_id'],
				'balance' 		=> $balance,
				'msg' 			=> self::ERROR_MESSAGE[$errorCode],
				'code' 			=> $errorCode
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function generate_digest(){
		$raw = $this->rawRequest();
		$raw = json_decode($raw,true);
		return $this->game_api->generateKey($raw);
	}


	public function validate_digest(){
		$request_digest = isset($_SERVER['HTTP_DIGEST']) ? $_SERVER['HTTP_DIGEST'] : null;
		if($request_digest == $this->generate_digest()){
			return true;
		}
		return false;
	}

	public function validate_merchant(){
		$request_merchant = isset($this->request['merchantCode']) ? $this->request['merchantCode'] : null;
		if($request_merchant == $this->merchant_code){
			return true;
		}
		return false;
	}

	public function validate_currency(){
		$request_currency = isset($this->request['currency']) ? $this->request['currency'] : null;
		if(strtolower($request_currency) == strtolower($this->currency)){
			return true;
		}
		return false;
	}

	public function getTransType($type){
		$return = '';
		if($type == 1){
			$return = 'placebet';
		}else if($type == 2){
			$return = 'cancelbet';
		}else if($type == 4){
			$return = 'payout';
		}
		return $return;
	}	
	
    public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('FASTSPIN-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->gameAmountToDB($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$game_code 						= isset($this->request['gameCode']) ? $this->request['gameCode'] : null;
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$game_code);
		if($transaction_type == self::TRANSACTION_TYPE_PLACEBET){
			$amount 		= $data['amount'] * -1; #getting the negative betting value
			$data['bet_amount'] = $data['amount'];
			$existingTrans  = $this->fastspin_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
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

        }else if($transaction_type == self::TRANSACTION_TYPE_PAYOUT){
			$amount = $data['amount'];
			$existingTrans  = $this->fastspin_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			#get bet transaction
			$bet_transaction_details = $this->fastspin_seamless_transactions->getExistingTransactionByReferenceId($tableName, $data['reference_id']);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::RECORD_ID_NOT_FOUND, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::INVALID_REQUEST, $playerId);
			}

			$settle_bet = $this->fastspin_seamless_transactions->flagBetTransactionSettled($tableName, $data);
			if(!$settle_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}

			$data['win_amount'] 	= $data['amount'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
			}

			#implement isEndRound
			$isEndRound = true;
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				if(	isset($this->request['specialGame']['count']) && 
					isset($this->request['specialGame']['sequence']) &&
					($this->request['specialGame']['count'] != $this->request['specialGame']['sequence'])
				){
					$isEndRound = false;
				}
				$this->wallet_model->setGameProviderIsEndRound($isEndRound);
			}

		}else if($transaction_type == self::TRANSACTION_TYPE_CANCELBET){
			$amount = $data['amount'];
			$existingTrans  = $this->fastspin_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			
			$bet_transaction_details = $this->fastspin_seamless_transactions->getExistingTransactionByReferenceId($tableName, $data['reference_id']);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::RECORD_ID_NOT_FOUND, $playerId);
			}

			#get the bet amount to be refunded
			$bet_transaction_amount = $this->game_api->gameAmountToDB($bet_transaction_details->amount);

			if($amount != $bet_transaction_amount){
				return $this->resultForAdjustWallet(self::AMOUNT_INVALID, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details->status == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::INVALID_REQUEST, $playerId);
			}

			#check if transaction already rollback
			#should return success if already cancelled 
			if($bet_transaction_details->status == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			$refund_bet = $this->fastspin_seamless_transactions->flagBetTransactionCancel($tableName, $data);
			if(!$refund_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}
			$data['status'] = GAME_LOGS::STATUS_CANCELLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
			}
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('FASTSPIN-amounts', [$beforeBalance, $afterBalance, $amount]);
		if($this->utils->compareResultFloat($amount, '>=', 0)){ 	
			#credit
			$data['balance_adjustment_method'] 	= 'credit';
			$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
			$afterBalance 						= $beforeBalance + $amount;
			$this->utils->debug_log('FASTSPIN', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else{	
			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('FASTSPIN', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
		}
		$data['extra_info']				 	= json_encode($this->request);
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
        $data['special_game'] 			    = isset($data['special_game']) ? json_encode($data['special_game']) : null;
        $data['ref_ticket_ids'] 			= isset($data['ref_ticket_ids']) ? json_encode($data['ref_ticket_ids']) : null;
		
		
		$this->utils->debug_log('FASTSPIN--adjust-wallet', $data);

		$insertTransaction = $this->fastspin_seamless_transactions->insertTransaction($tableName,$data);

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
		$this->utils->debug_log("FASTSPIN--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("FASTSPIN SEAMLESS SERVICE: (handleExternalResponse)",
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
		$this->utils->debug_log("##### FASTSPIN SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
			$this->utils->debug_log("FASTSPIN SEAMLESS SERVICE raw:", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("FASTSPIN SEAMLESS SERVICE raw parsed:", $request_json);
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