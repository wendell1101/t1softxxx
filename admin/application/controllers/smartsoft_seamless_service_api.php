<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Smartsoft_seamless_service_api extends BaseController {

    public $game_platform_id,$start_time,$load,$host_name,$method, $game_api, $currency,$request, $headers, $response_result_id,$end_time, $output, $portal_name, $request_method,$raw_request;

    #error codes
    const SUCCESS                   		= 200; 
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
	const ERROR_INVALID_SIGNATURE		    = 2010;

	#method
    const METHOD_ACTIVATE_SESSION   	= 'ActivateSession';
    const METHOD_GET_BALANCE   			= 'GetBalance';
    const METHOD_DEPOSIT 	  			= 'Deposit';
    const METHOD_WITHDRAW 	  			= 'Withdraw';
    const METHOD_ROLLBACK_TRANSACTION   = 'Rollback';

	#transaction mode
	const TRANSACTION_DEPOSIT				= 'deposit';
	const TRANSACTION_WITHDRAW				= 'withdraw';
	const TRANSACTION_ROLLBACK_TRANSACTION	= 'rollback';

	const TRANSACTION_INITIAL_BET	= 'InitialBet';
	const TRANSACTION_CLOSE_rouNd	= 'CloseRound';

	const ERROR_MESSAGE = [
		self::SUCCESS 						=> 'success',
		self::ERROR_INTERNAL_ERROR 			=> 'Internal Server error',
		self::ERROR_SERVICE_UNAVAILABLE 	=> 'Service Unavailable',
		self::ERROR_NOT_ALLOWED 			=> 'Request not allowed',
		self::ERROR_UNAUTHORIZED 			=> 'Unauthorized',
		self::ERROR_LOSS_LIMIT 				=> 'Error loss limit',
		self::ERROR_NOT_ENOUGH_BALANCE 		=> 'Player insufficient balance',
		self::ERROR_INVALID_PLAYER 			=> 'Invalid Player',
		self::ERROR_INVALID_PARAMETERS  	=> 'Invalid or missing parameters',
		self::ERROR_INVALID_AMOUNT  		=> 'Invalid Amount',
		self::ERROR_TRANSACTION_NOT_FOUND  	=> 'Transaction not found',
		self::ERROR_INVALID_TRANSACTION	  	=> 'Invalid Transaction',
		self::ERROR_ALREADY_SETTLED	  		=> 'Transaction already settled',
		self::ERROR_ALREADY_ROLLBACK	    => 'Transaction already rollback',
		self::ERROR_ALREADY_CANCELLED	    => 'Transaction already cancelled',
		self::ERROR_INVALID_SIGNATURE	    => 'Invalid Signature',
	];

	const ERROR_HTTP_CODE = [
		self::SUCCESS 						=> 200,
		self::ERROR_INTERNAL_ERROR 			=> 500,
		self::ERROR_SERVICE_UNAVAILABLE 	=> 503,
		self::ERROR_NOT_ALLOWED 			=> 405,
		self::ERROR_UNAUTHORIZED 			=> 401,
		self::ERROR_LOSS_LIMIT 				=> 112,
		self::ERROR_NOT_ENOUGH_BALANCE 		=> 400,
		self::ERROR_INVALID_PLAYER 			=> 400,
		self::ERROR_INVALID_PARAMETERS  	=> 400,
		self::ERROR_INVALID_AMOUNT  		=> 400,
		self::ERROR_TRANSACTION_NOT_FOUND  	=> 400,
		self::ERROR_INVALID_TRANSACTION  	=> 400,
		self::ERROR_ALREADY_SETTLED  		=> 400,
		self::ERROR_ALREADY_ROLLBACK  		=> 400,
		self::ERROR_ALREADY_CANCELLED  		=> 400,
		self::ERROR_INVALID_SIGNATURE  		=> 400,
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','smartsoft_seamless_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index($method){
		$this->request_method = $method;
		if ($this->initialize()) {
			return $this->selectMethod();			
		}
	}

	private function selectMethod(){
		switch ($this->request_method) {
			case 'ActivateSession':
				$this->ActivateSession();
				break;
			case 'Deposit':
				$this->DepositAndWithdraw(self::TRANSACTION_DEPOSIT);
				break;
			case 'Withdraw':
				$this->DepositAndWithdraw(self::TRANSACTION_WITHDRAW);
				break;
			case 'GetBalance':
				$this->GetBalance();
				break;
			case 'GenerateXsignature':
				$this->generateXsignature();
				break;
			case 'RollbackTransaction':
				$this->DepositAndWithdraw(self::TRANSACTION_ROLLBACK_TRANSACTION);
				break;
			default:
				$this->utils->debug_log('SMARTSOFT seamless service: Invalid API Method');
				http_response_code(404);
		}
	}

	private function initialize(){
		$this->game_platform_id = SMARTSOFT_SEAMLESS_GAME_API;
        
		$currencyToken  = isset($_SERVER['HTTP_X_SESSIONID']) ? $_SERVER['HTTP_X_SESSIONID'] : null ;
		if(!$currencyToken){
			$currencyToken = isset($this->request['Token']) ? $this->request['Token'] : null;
		}

		# set currency based on the  currency-token
		$dbLoaded = false;
		if(!$dbLoaded && isset($currencyToken) && !empty($currencyToken)){
			$dbLoaded =$this->loadDbFromToken($currencyToken);
		}

		if(!$dbLoaded){
			$this->utils->error_log('SMARTSOFT ' . __METHOD__ , $this->request_method . ' cannot load DB');
		}
		
		$this->game_api 	= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 	= $this->game_api->getCurrency();
        $this->portal_name 	= $this->game_api->portal_name;

		return true;
	}

	private function loadDbFromToken($token){
        $this->utils->debug_log('SMARTSOFT ' . __METHOD__ , ' token', $token);
        $currency = null;
        //switch to target db
        if(!is_null($token)){
            $currencyExplode = explode('-', $token);
            if(isset($currencyExplode[0])){
                $currency = strtolower($currencyExplode[0]);
                if(!$this->utils->isAvailableCurrencyKey($currency)){
                    return false;
                }
                $this->utils->debug_log('SMARTSOFT ' . __METHOD__ , ' currency', $currency);
                // if(isset($currencyExplode[1]) && !empty($currencyExplode[1])){
                //     $token=$currencyExplode[1];
                //     $this->requestParams->params['username'] = $token;
                // }
                $_multiple_db=Multiple_db::getSingletonInstance();
                $res = $_multiple_db->switchCIDatabase($currency);
                $this->utils->debug_log('SMARTSOFT ' . __METHOD__ , ' switchCIDatabase result', $res);
                return true;
            }
        }
        return false;
    }

	private function explodeToken($token_with_currency){
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

	private function ActivateSession(){
		$this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE: (ActivateSession)");	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_ACTIVATE_SESSION;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$additional_response_header = [];
		$token 						= null;
		try { 
			if(!$this->initialize()){
				throw new Exception(self::ERROR_INTERNAL_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {

                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_NOT_ALLOWED);
			}

			$requiredParams = ['Token'];

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			$request 				= $this->request;
			$original_token 		= isset($request['Token']) ? $request['Token'] : null;
			$token      			= $this->explodeToken($original_token)['token'];
			$this->utils->debug_log('SMARTSOFT-activatesession', $request);
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($token);

			if(!$getPlayerStatus){
				throw new Exception(self::ERROR_INVALID_PLAYER);
			}
			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	
		
		$externalResponse = [
			"UserName" 			=>	$errorCode == self::SUCCESS ? $player_username : null,
			"SessionId" 		=>	$original_token,
			"ClientExternalKey" =>	$errorCode == self::SUCCESS ? $game_username : null,
			"PortalName" 		=>	$this->portal_name,
			"CurrencyCode" 		=> 	$this->currency
		];

		$fields = [
			'player_id'		=> $player_id,
		];

		#no need to return error code on headers if success
		if($errorCode != self::SUCCESS){
			$additional_response_header = [
				'error_code' 	=> $errorCode,
				'error_message' => self::ERROR_MESSAGE[$errorCode],
			];
		}
		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields, $additional_response_header);	
	}


	private function GetBalance(){
		$this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE: (GetBalance)");	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_GET_BALANCE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$additional_response_header = [];
		$balance					= null;
		try { 
			if(!$this->initialize()){
				throw new Exception(self::ERROR_INTERNAL_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_NOT_ALLOWED);
			}

			$request 	= $this->request;
			$token 		= $_SERVER['HTTP_X_SESSIONID']; 
			$token      = $this->explodeToken($token)['token'];
			$this->utils->debug_log('SMARTSOFT-getbalance', $request);
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($token);

			if(!$getPlayerStatus){
				throw new Exception(self::ERROR_INVALID_PLAYER);
			}
			$player_id 	= $player->player_id;
			$balance 	= $this->game_api->getPlayerBalanceById($player_id);

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	
		
		$externalResponse = [
			"CurrencyCode" 	=> $this->currency,
			"Amount" 		=> $balance,
		];

		$fields = [
			'player_id'		=> $player_id,
		];

		#no need to return error code on headers if success
		if($errorCode != self::SUCCESS){
			$additional_response_header = [
				'error_code' 	=> $errorCode,
				'error_message' => self::ERROR_MESSAGE[$errorCode],
			];
		}
		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields, $additional_response_header);	
	}


	private function DepositAndWithdraw($request_type){
		$externalResponse 			= $this->externalQueryResponse();	
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$additional_response_header = [];
		$request  					= $this->request;
		$adjustWalletResponse       = [];


		if($request_type == self::TRANSACTION_DEPOSIT){
			$callType 					= self::METHOD_DEPOSIT;
			$transaction_mode 			= self::TRANSACTION_DEPOSIT;
			$transaction_status 		= GAME_LOGS::STATUS_PENDING;
		}elseif($request_type == self::TRANSACTION_WITHDRAW){
			$callType 					= self::METHOD_WITHDRAW;
			$transaction_mode 			= self::TRANSACTION_WITHDRAW;
			$transaction_status 		= GAME_LOGS::STATUS_SETTLED;
		}
		elseif($request_type == self::TRANSACTION_ROLLBACK_TRANSACTION){
			$callType 					= self::METHOD_ROLLBACK_TRANSACTION;
			$transaction_mode 			= self::TRANSACTION_ROLLBACK_TRANSACTION;
			$transaction_status 		= GAME_LOGS::STATUS_CANCELLED;
		}else{
			http_response_code(404);
			return false;
		}

		try { 
			if(!$this->initialize()){
				throw new Exception(self::ERROR_INTERNAL_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_NOT_ALLOWED);
			}

			$requiredParams = ['Amount','CurrencyCode','TransactionId'];

			if(!$this->isParamsRequired($this->request,$requiredParams)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}


			$original_token 	  = $_SERVER['HTTP_X_SESSIONID']; 
			$token    			  = $this->explodeToken($original_token)['token'];
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByToken($token);
			$this->utils->debug_log('SMARTSOFT-DepositAndWithdraw',$request_type, $request);

			if($this->game_api->enable_signature_validation){
				if(!$this->isValidSignature($request)){
					throw new Exception(self::ERROR_INVALID_SIGNATURE);
				}
			}

			if(!$getPlayerStatus){
				throw new Exception(self::ERROR_INVALID_PLAYER);
			}
			$player_id 	= $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		if($player_id){
			$params = [
				'transaction_id' 		 => $request['TransactionId'],
				'transaction_type' 		 => isset($request['TransactionType']) ? $request['TransactionType'] : null,
				'amount' 				 => $request['Amount'],
				'currency_code' 		 => $request['CurrencyCode'],
				'source' 				 => isset($request['TransactionInfo']['Source']) ? $request['TransactionInfo']['Source'] : null,
				'game_name' 			 => isset($request['TransactionInfo']['GameName']) ? $request['TransactionInfo']['GameName'] : null,
				'round_id' 				 => isset($request['TransactionInfo']['RoundId']) ? $request['TransactionInfo']['RoundId'] : null ,
				'game_number' 			 => isset($request['TransactionInfo']['GameNumber']) ? $request['TransactionInfo']['GameNumber'] : null ,
				'cashier_transaction_id' => isset($request['TransactionInfo']['CashierTransacitonId']) ? $request['TransactionInfo']['CashierTransacitonId'] : null ,
				'external_uniqueid' 	 => isset($request['TransactionId']) ? $request['TransactionId'] : null,
				'player_id' 	 		 => $player_id,
				'trans_type' 	 		 => isset($request['TransactionType']) ? $request['TransactionType'] : null,
				'player_name' 	 		 => $game_username,
				'trans_status' 			 => $transaction_status,
				'extra_info' 			 => json_encode($request),
				
				#used only for "where query" of settling bet
				'bet_transaction_id'	 => isset($request['TransactionInfo']['BetTransactionId']) ? $request['TransactionInfo']['BetTransactionId'] : null,
			];

			#if transaction is rollback 
			if($request_type == self::TRANSACTION_ROLLBACK_TRANSACTION){
				$params['external_uniqueid'] = isset($request['CurrentTransactionId']) ? $request['CurrentTransactionId'] : null;
			}


			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,$request,$params,$transaction_mode,
			&$errorCode, &$adjustWalletResponse
			) {
				$adjustWalletResponse = $this->adjustWallet($transaction_mode, $player, $params);
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				return true;
			});
		}

		$externalResponse = [
			"TransactionId" 	=> $request['TransactionId'],
			"Balance" 			=> isset($adjustWalletResponse['current_balance']) ? floatval($adjustWalletResponse['current_balance']) : null,
		];

		$fields = [
			'player_id'		=> $player_id,
		];

		#no need to return error code on headers if success
		if($errorCode != self::SUCCESS){
			$additional_response_header = [
				'error_code' 	=> $errorCode,
				'error_message' => self::ERROR_MESSAGE[$errorCode],
			];
		}
		
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields, $additional_response_header);	
	}

	private function adjustWallet($transaction_mode,$player_info,$data){
		
        $uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerName 					= $player_info->username;
		$playerId	 					= $player_info->player_id;
		$game_id						= $data['game_name'];
		$balance						= $this->game_api->getPlayerBalanceById($playerId);
		$currency						= $data['currency_code'];
		$tableName 						= $this->game_api->getTransactionsTable();
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service);
        $this->wallet_model->setExternalGameId($game_id);

		
		if($transaction_mode == self::TRANSACTION_DEPOSIT){
			$this->wallet_model->setGameProviderActionType('bet');

			#placing bet data
			$amount = $this->game_api->gameAmountToDBTruncateNumber($data['amount']) * -1; #getting the negative betting value
			
			#return existing transaction if found 
			$existingTrans = $this->isTransactionExist($data);
			
			if(!empty($existingTrans)){
				#already exist, returns previous and after balance
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			if($balance < $data['amount']){
				#bet amount is greater than current balance
				return $this->resultForAdjustWallet(self::ERROR_NOT_ENOUGH_BALANCE, $playerId);
			}

        }
		else if($transaction_mode == self::TRANSACTION_WITHDRAW){
			$this->wallet_model->setGameProviderActionType('payout');

			#---------------- Withdraw Transaction -----------------
			$current_transaction_id = $data['transaction_id'];
			$check = false;

			#return existing transaction if found 
			$existingTrans = $this->isTransactionExist($data);
			if(!empty($existingTrans)){
				#already exist, returns previous and after balance
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

            if ($data['transaction_type'] != 'CloseRound') {
                $extra_where_params = [
                    'check_bet_exist' => true,
                    'external_uniqueid' => $data['bet_transaction_id'],
                ];
    
                $isBetExists = $this->isTransactionExist($data, $extra_where_params);
    
                if (!$isBetExists) {
                    return $this->resultForAdjustWallet(self::ERROR_TRANSACTION_NOT_FOUND, $playerId);
                }
            }

			#check if placebet is already cancelled / rollback
			if($data['transaction_type'] == 'WinAmount'){
				$placeBetDetails = $this->smartsoft_seamless_transactions->getTransactionDetails($tableName,$data['bet_transaction_id']);
				if($placeBetDetails->trans_status == GAME_LOGS::STATUS_CANCELLED){
					return $this->resultForAdjustWallet(self::ERROR_ALREADY_CANCELLED, $playerId);
				}
				$check 	= $this->smartsoft_seamless_transactions->flagBetTransactionSettled($tableName,$data);
			}

			#if Withdraw transaction with has type of "CloseRound", no win amount. Settling the placebet (lose)
			if($data['transaction_type'] == 'CloseRound'){
				//$roundId 			= $data['round_id'];
				//$roundDetails 		= $this->smartsoft_seamless_transactions->getRoundDetails($tableName,$roundId);
				//$bet_transaction_id = $roundDetails->transaction_id;
				// $checkingBetDetails = $this->smartsoft_seamless_transactions->getTransactionDetails($tableName,$bet_transaction_id);
				// if($checkingBetDetails->trans_status == GAME_LOGS::STATUS_CANCELLED){
				// 	return $this->resultForAdjustWallet(self::ERROR_ALREADY_CANCELLED, $playerId);
				// }
				//$data['bet_transaction_id'] = $bet_transaction_id;
				$check 	= $this->smartsoft_seamless_transactions->flagBetTransactionAllRoundSettled($tableName,$data);

				if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
					$this->wallet_model->setGameProviderIsEndRound(true);
				}
			}
			
			#settling bet data
			$amount = $this->game_api->gameAmountToDBTruncateNumber($data['amount']);
            $this->utils->debug_log('SMARTSOFT', 'SETTLE!!!!', $check, $tableName);

        }
		else if($transaction_mode == self::TRANSACTION_ROLLBACK_TRANSACTION){
			$this->wallet_model->setGameProviderActionType('refund');
			#---------- unsettling bet data ----------------

			#getting the transaction id of rollback transaction
			$rollbackTransactionid = json_decode($data['extra_info'])->CurrentTransactionId;


			$extra_where_params = [
				'external_uniqueid'   => $data['transaction_id'],
			];

			#getting the transaction id of transaction that will be rollback 
			$existingTrans  = $this->isTransactionExist($data, $extra_where_params);
			$rollbackAmount = $data['amount'];

			#in this rollback transaction, the params 'transactionId' is the transaction id that will be rollback
			if(!empty($existingTrans)){
				#checking if found transaction is placebet (debit) or winamout/settled (credit)
				if($existingTrans['trans_type'] == 'PlaceBet'){
					#also check if placebet transaction is already settled
					if($existingTrans['trans_status'] == GAME_LOGS::STATUS_SETTLED){
						return $this->resultForAdjustWallet(self::ERROR_ALREADY_SETTLED, $playerId);
					}elseif($existingTrans['trans_status'] == GAME_LOGS::STATUS_CANCELLED){
						// return $this->resultForAdjustWallet(self::ERROR_ALREADY_ROLLBACK, $playerId);
						return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
					}
				}else{
					return $this->resultForAdjustWallet(self::ERROR_INVALID_TRANSACTION, $playerId);
				}
				#checking if the rollback amount is the same amount in the request parameters
				if($rollbackAmount != $existingTrans['amount']){
					return $this->resultForAdjustWallet(self::ERROR_INVALID_AMOUNT, $playerId);
				}
			}else{
				#if transation not found
				return $this->resultForAdjustWallet(self::ERROR_TRANSACTION_NOT_FOUND, $playerId);
			}


			#if above conditions all good then do a rollback
			$this->smartsoft_seamless_transactions->flagBetTransactionUnsettled($tableName,$data);
			$amount = $data['amount']; 
			$this->utils->debug_log('SMARTSOFT', 'ROLLBACK !!!!');

			#after flagging placebet transaction as canceled, make rollbackTransactionId as external_uniqueid
			#setting the rollback transaction id as the transaction id of this request
			$data['transaction_id'] 	= $rollbackTransactionid;
			$data['external_uniqueid'] 	= $rollbackTransactionid;
			$data['trans_type'] 		= self::TRANSACTION_ROLLBACK_TRANSACTION;
        }
		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;



	
		if($amount >= 0){ 	
			#credit

			$this->smartsoft_seamless_transactions->setTableName($tableName);
			$whereParams = ['round_id'=>$data['round_id'], 'transaction_type' => self::TRANSACTION_INITIAL_BET ];
			$betDetails = $this->smartsoft_seamless_transactions->getTransactionByParamsArray($whereParams);

			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
				$related_unique_id 	= 'game-'.$this->game_platform_id . '-' . $betDetails['external_uniqueid'];
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_unique_id);
			}
	
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
				$this->wallet_model->setRelatedActionOfSeamlessService('bet');
			}

			$afterBalance 						= $balance + $amount;
			$data['balance_adjustment_method'] 	= 'credit';
			$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$this->utils->debug_log('SMARTSOFT', 'ADD-AMOUNT: ', $response);
		}else{	
			#debit
			$amount 							= abs($amount);
			$afterBalance 						= $balance - $amount;
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$this->utils->debug_log('SMARTSOFT', 'MINUS-AMOUNT: ', $response , $amount);
		}
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;

		#removes info that not in database table column
		unset($data['bet_transaction_id']);
		
		$insertTransaction = $this->smartsoft_seamless_transactions->insertTransactionData($tableName,$data);
		return $insertTransaction ? $this->resultForAdjustWallet(self::SUCCESS, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::ERROR_INTERNAL_ERROR, $playerId);
	}

	private function resultForAdjustWallet($errorCode, $player_id = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $errorCode == self::SUCCESS ? true : false,
				'code' 		 	  => $errorCode,
				'current_balance' => $errorCode == self::SUCCESS ? $current_balance : null,
			];
		$this->utils->debug_log("SMARTSOFT--AdjustWalletResult" , $response);
		return $response;
	}

    private function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = [], $additional_response_header=[]){
        $this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE: (handleExternalResponse)",
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
		$this->utils->debug_log("##### SMARTSOFT SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
		if($error_code != self::SUCCESS){
			$output->set_header("X-ErrorCode: {$additional_response_header['error_code']}");
			$output->set_header("X-ErrorMessage: {$additional_response_header['error_message']}");
		}
		return $output;
	}

	private function isTransactionExist($data, $extra = null){
        $check_bet_exist = false;

        if (isset($extra['check_bet_exist'])) {
            if ($extra['check_bet_exist']) {
                $check_bet_exist = true;
            }

            unset($extra['check_bet_exist']);
        }

		$check_bet_params = [
			'external_uniqueid'   => $data['external_uniqueid'],
		];
		if($extra){
			$check_bet_params = $extra;
		}

		$checkOtherTable 	= $this->game_api->checkOtherTransactionTable();
		$currentTableName   = $this->game_api->getTransactionsTable();
		$currentRoundData 	= $this->CI->smartsoft_seamless_transactions->getRoundData($currentTableName, $check_bet_params);
		$prevRoundData   	= [];
		$existingTrans      = false;
		if($checkOtherTable){                    
			# get prev table
			$prevTranstable = $this->game_api->getTransactionsPreviousTable();

			$this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE: (debitCreditAmount)", 'prevTranstable', $prevTranstable);
			# get data from prev table
			$prevRoundData = $this->CI->smartsoft_seamless_transactions->getRoundData($prevTranstable, $check_bet_params);
		}

		$roundData = array_merge($currentRoundData, $prevRoundData);   

        if ($check_bet_exist) {
            if (!empty($roundData)) {
                return true;
            } else {
                return false;
            }
        }

		foreach($roundData as $roundDataRow){
			if($roundDataRow['transaction_id']==$data['transaction_id']){
				$existingTrans = $roundDataRow;
			}
		}

		return $existingTrans;
	}

	//default external response template
	private function externalQueryResponse(){
        return ['data'=>[],'error'=>null];
	}

	private function retrieveHeaders() {
		$this->headers = getallheaders();
	}
	
	private function parseRequest(){
        $request_json = file_get_contents('php://input');
		$this->raw_request = $request_json;
        $this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE raw:", $request_json);

        $this->request = json_decode($request_json, true);

        if (!$this->request){
            parse_str($request_json, $request_json);
            $this->utils->debug_log("SMARTSOFT SEAMLESS SERVICE raw parsed:", $request_json);
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

    private function generateXsignature(){
        $generatedSignature = $this->getGeneratedXsignature($this->request);

        $response = [
            'generated_signature' => $generatedSignature,
        ];

        return $this->output->set_content_type('application/json')->set_status_header(200)->set_output(json_encode($response));
    }

    private function getGeneratedXsignature($request=null){
		$http_request_method 	= $this->method;
		$secret_key 			= $this->game_api->key;
		$rawRequest 			= $this->raw_request;
		$stringConcat   		= $secret_key."|".$http_request_method."|".$rawRequest;
		$this->utils->debug_log('SMARTSOFT seamless service: generateXsignature',$stringConcat);
		return md5($stringConcat);
	}

	private function isValidSignature($request = null){
		if($request){
			$requestSignature = isset($_SERVER['HTTP_X_SIGNATURE']) ? $_SERVER['HTTP_X_SIGNATURE'] : null; 
			$generatedSignature = $this->getGeneratedXsignature($request); 
			$this->utils->debug_log('SMARTSOFT seamless service: isValidSignature',$requestSignature, $generatedSignature);
			
			if($requestSignature == $generatedSignature){
				return true;
			}
		}
		return false;
	}

	private function getPlayerByToken($token){
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		return [true, $player, $player->game_username, $player->username];
	}

	private function isParamsRequired($params, $required) {
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