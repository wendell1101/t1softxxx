<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

// OGP-34612
// OGP-34613
// OGP-34722

require_once dirname(__FILE__) . '/BaseController.php';

class Holi_seamless_service_api extends BaseController {

    public  $game_platform_id,
            $start_time,
            $load,
            $host_name,
            $method, 
            $game_api, 
            $currency,
            $playerId,
            $request, 
            $headers, 
            $response_result_id,
            $end_time, $output, 
            $request_method,
			$allow_invalid_signature,
			$key,
			$raw_request,
			$api_key,
			$walletResultAdjustmentData
			;
	protected $currentBalanceOnError = 0;
	public $tableId;
	protected $player_token;

   // monthly transaction table
   protected $use_monthly_transactions_table = false;
   protected $force_check_previous_transactions_table = false;
   protected $force_check_other_transactions_table = false;
   protected $previous_table = null;

   protected $enable_skip_validation = false;

    #error codes from GP
    const COMPLETED_SUCCESSFULLY                =  "COMPLETED_SUCCESSFULLY" ;   
    const TABLE_NOT_FOUND                       =  "TABLE_NOT_FOUND" ; 
    const TABLE_CLOSED                          =  "TABLE_CLOSED" ; 
    const PLAYER_NOT_FOUND                      =  "PLAYER_NOT_FOUND" ; 
    const PLAYER_BLOCKED                        =  "PLAYER_BLOCKED" ; 
    const UNSUPPORTED_CURRENCY                  =  "UNSUPPORTED_CURRENCY" ; 
    const UNSUPPORTED_MODE                      =  "UNSUPPORTED_MODE" ; 
    const UNSUPPORTED_GAME_TYPE                 =  "UNSUPPORTED_GAME_TYPE" ; 
    const INSUFFICIENT_FUNDS                    =  "INSUFFICIENT_FUNDS" ; 
    const EXCEEDING_THE_LIMIT                   =  "EXCEEDING_THE_LIMIT" ; 
    const TRANSACTION_NOT_FOUND                 =  "TRANSACTION_NOT_FOUND" ;
    const ROUND_NOT_FOUND                       =  "ROUND_NOT_FOUND" ;
    const SIGNATURE_VERIFICATION_FAILED         =  "SIGNATURE_VERIFICATION_FAILED" ;
    const DUPLICATE_TRANSACTION                 =  "DUPLICATE_TRANSACTION" ;
    const GENERAL_ERROR                         =  "GENERAL_ERROR" ;            				
    const GENERAL_ERROR_SERVICE_UNAVAILABLE     =  "GENERAL_ERROR_SERVICE_UNAVAILABLE" ;        // With custom message
    const GENERAL_ERROR_INVALID_REQUEST     	=  "GENERAL_ERROR_INVALID_REQUEST" ;            // With custom message
    const GENERAL_ERROR_INVALID_SESSION_TOKEN   =  "GENERAL_ERROR_INVALID_SESSION_TOKEN" ;            // With custom message
    const GENERAL_ERROR_GAME_UNDER_MAINTENANCE  =  "GENERAL_ERROR_GAME_UNDER_MAINTENANCE" ;            // With custom message
    const GENERAL_ERROR_UNAUTHORIZED  			=  "GENERAL_ERROR_UNAUTHORIZED" ;            // With custom message

    #additional error codes
    const SYSTEM_ERROR                          = 'SYSTEM_ERROR';
    const IP_NOT_ALLOWED                        = 'IP_NOT_ALLOWED';
    const INVALID_AMOUNT			            = 'INVALID_AMOUNT';
    const ALREADY_SETTLED			            = 'ALREADY_SETTLED';
    const ALREADY_CANCELLED			            = 'ALREADY_CANCELLED';
    const ALREADY_REFUNDED			            = 'ALREADY_REFUNDED';

  
    #error messages
	const ERROR_MESSAGE = [
        self::COMPLETED_SUCCESSFULLY            	=>   'Completed successfully',
        self::TABLE_NOT_FOUND                   	=>   'Table not found',
        self::TABLE_CLOSED                      	=>   'Table closed',
        self::PLAYER_NOT_FOUND                  	=>   'Player not found',
        self::PLAYER_BLOCKED                    	=>   'Player blocked',
        self::UNSUPPORTED_CURRENCY              	=>   'Unsupported currency',
        self::UNSUPPORTED_MODE                  	=>   'Unsupported mode',
        self::UNSUPPORTED_GAME_TYPE             	=>   'Unsupported gameType',
        self::INSUFFICIENT_FUNDS                	=>   'Insufficient funds',
        self::EXCEEDING_THE_LIMIT               	=>   'Exceeding the limit',
        self::TRANSACTION_NOT_FOUND             	=>   'Transaction not found',
        self::ROUND_NOT_FOUND                   	=>   'Round not found',
        self::SIGNATURE_VERIFICATION_FAILED     	=>   'Signature verification failed',
        self::DUPLICATE_TRANSACTION             	=>   'Duplicate transaction',
        self::GENERAL_ERROR                     	=>   'General error. ',         					// With custom message
        self::GENERAL_ERROR_SERVICE_UNAVAILABLE     =>   'General error, Service unavailable ',         // With custom message
        self::GENERAL_ERROR_INVALID_SESSION_TOKEN 	=>   'General error, Invalid Session Token ',       // With custom message
        self::GENERAL_ERROR_INVALID_REQUEST 		=>   'General error, Invalid Request ',         	// With custom message
        self::GENERAL_ERROR_GAME_UNDER_MAINTENANCE 	=>   'General error, Under Maintenance ',         	// With custom message
        self::GENERAL_ERROR_UNAUTHORIZED 			=>   'Unauthorized ',         	// With custom message

        #additional error messages
        self::SYSTEM_ERROR                      	=> 'System Error',
        self::IP_NOT_ALLOWED                    	=> 'IP address not allowed',
        self::INVALID_AMOUNT                    	=> 'Invalid amount',
        self::ALREADY_SETTLED                   	=> 'Already settled',
        self::ALREADY_CANCELLED                 	=> 'Already cancelled',
        self::ALREADY_REFUNDED                 		=> 'Already refunded',
	];

    #error code
    const ERROR_CODE = [
        self::COMPLETED_SUCCESSFULLY            		=> 0,
        self::TABLE_NOT_FOUND                   		=> 1,
        self::TABLE_CLOSED                      		=> 2,
        self::PLAYER_NOT_FOUND                  		=> 3,
        self::PLAYER_BLOCKED                    		=> 4,
        self::UNSUPPORTED_CURRENCY              		=> 5,
        self::UNSUPPORTED_MODE                  		=> 6,
        self::UNSUPPORTED_GAME_TYPE             		=> 7,
        self::INSUFFICIENT_FUNDS                		=> 8,
        self::EXCEEDING_THE_LIMIT               		=> 9,
        self::TRANSACTION_NOT_FOUND             		=> 10,
        self::ROUND_NOT_FOUND                   		=> 11,
        self::SIGNATURE_VERIFICATION_FAILED     		=> 12,
        self::DUPLICATE_TRANSACTION             		=> 13,
        self::GENERAL_ERROR                     		=> 100,		 // With custom message
        self::GENERAL_ERROR_SERVICE_UNAVAILABLE     	=> 100,		 // With custom message
        self::GENERAL_ERROR_INVALID_REQUEST     		=> 100,		 // With custom message
        self::GENERAL_ERROR_INVALID_SESSION_TOKEN   	=> 100,		 // With custom message
        self::GENERAL_ERROR_GAME_UNDER_MAINTENANCE   	=> 100,		 // With custom message
        self::GENERAL_ERROR_UNAUTHORIZED   				=> 100,		 // With custom message
        self::ALREADY_REFUNDED                 			=> 100,
        self::ALREADY_SETTLED                   		=> 100,
        
        #additional error messages
        self::SYSTEM_ERROR                      		=> 500,
        self::IP_NOT_ALLOWED                    		=> 500,
        self::INVALID_AMOUNT                    		=> 500,
        self::ALREADY_CANCELLED                 		=> 500,
    ];

    #NOTE: From GP to SBE
    const METHOD_GETBALANCE    	= 'GetBalance';
    const METHOD_DEBIT          = 'Debit';      // (bet)
    const METHOD_CREDIT         = 'Credit';     // (win)
    const METHOD_ROLLBACK       = 'Rollback';   // (unsuccessful bet refund)

    #NOTE: check gp documentation  - generate signature (5.1)
    const METHOD_SIGNATURE_COLLECTION_1 = [ self::METHOD_DEBIT, self::METHOD_CREDIT, self::METHOD_ROLLBACK ];
    const METHOD_SIGNATURE_COLLECTION_2 = [ self::METHOD_GETBALANCE ];

	const REQUIRED_PARAMS_MAP = [
        'GetBalance' => ['playerId', 'currency', 'timeStamp', 'sessionId', 'signature'],
        'Debit' => ['playerId', 'debitAmount', 'currency', 'tableId', 'roundId', 'gameType', 'transactionId', 'sessionId'],
        'Credit' => ['playerId', 'creditAmount', 'currency', 'tableId', 'roundId', 'gameType', 'transactionId', 'sessionId'],
        'Rollback' => ['playerId', 'rollbackAmount', 'currency', 'tableId', 'roundId', 'gameType', 'transactionId', 'sessionId']
    ];
    

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','original_seamless_wallet_transactions', 'player'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index(... $methods){
		$method = implode('/', $methods);
		$this->request_method = $method;
		$this->utils->debug_log('HOLI_SEAMLESS--method : '. $this->request_method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'GetBalance':
				$this->GetBalance();
                break;
            case 'Debit':
                $this->Debit();
                break;
			case 'Credit':
				$this->Credit();
				break;
			case 'Rollback':
				$this->Rollback();
				break;
			default:
				$this->utils->debug_log('HOLI_SEAMLESS_SERVICE: Invalid API Method');
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
		$this->game_platform_id    =  HOLI_SEAMLESS_GAME_API;
		$this->game_api 		   =  $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("HOLI_SEAMLESS_SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }

        $this->currency                     = $this->game_api->getCurrency();
		$this->tableId      				= $this->game_api->tableId;
		$this->allow_invalid_signature      = $this->game_api->allow_invalid_signature;
		$this->api_key		                = $this->game_api->api_key;
				
		// monthly transaction table
		$this->use_monthly_transactions_table 			= $this->game_api->use_monthly_transactions_table;
		$this->force_check_previous_transactions_table 	= $this->game_api->force_check_previous_transactions_table;
		$this->previous_table 							= $this->game_api->ymt_get_previous_year_month_table();

		$this->enable_skip_validation = $this->game_api->enable_skip_validation;
			
		$this->CI->load->model(array('player_model'));
		$player_username 				= isset($this->request['playerId']) ? $this->request['playerId'] : null;
        $player_id 						= $this->game_api->getPlayerIdByGameUsername($player_username);
		$this->player_token			 	= $this->common_token->getPlayerToken($player_id);
		return true;
	}

    public function GetBalance(){
        return $this->processTransaction('GetBalance', function($player_id) {
            return [
                'balance' => $this->game_api->getPlayerBalanceById($player_id)
            ];
        });
    }

    public function Debit(){
        return $this->processTransaction('Debit', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('Debit', $player_id, $params);
        });
    }

    public function Credit(){
        return $this->processTransaction('Credit', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('Credit', $player_id, $params);
        });
    }

    public function Rollback(){
        return $this->processTransaction('Rollback', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('Rollback', $player_id, $params);
        });
    }

    private function processTransaction($method, $callback) {
        $this->utils->debug_log("HOLI_SEAMLESS_SERVICE: ($method)", ['$this->request' => $this->request]);

        $callType = constant("self::METHOD_" . strtoupper($method));
        $errorCode = self::COMPLETED_SUCCESSFULLY;
        $externalResponse = [];
        $success = true;
        $player_id = null;
        $requiredParams = self::REQUIRED_PARAMS_MAP[$method];

        try {
            $this->initializeTransaction($method, $requiredParams);
			list($getPlayerStatus, $player, $player_username) = $this->getPlayerInfoById($this->request['playerId']);

            if (!$getPlayerStatus) {
                throw new Exception(self::PLAYER_NOT_FOUND);
            }

            $player_id = $player['playerId'];
            $params = $this->buildParams($method);
            $result = $callback($player_id, $params);

            $externalResponse = array_merge($result, [
                'playerId' 	=> $this->request['playerId'],
                'currency' 	=> strtoupper($this->currency),
                'sessionId' => $this->request['sessionId'],
                'timeStamp' => $this->getTimestamp(),
            ]);

			if( in_array( $method, self::METHOD_SIGNATURE_COLLECTION_2 ) ){
				$externalResponse['signature'] = $this->request['signature'];
			}

        } catch (Exception $error) {
            $errorCode = $error->getMessage();
            $success = false;
            

			if(self::ERROR_CODE[$errorCode] != self::ERROR_CODE[self::PLAYER_NOT_FOUND] ){
				$externalResponse = [
					'errorCode' => self::ERROR_CODE[$errorCode],
					'errorMessage' => self::ERROR_MESSAGE[$errorCode],
					'playerId' 	=> $this->request['playerId'],
					'currency' 	=> strtoupper($this->currency),
					'sessionId' => $this->request['sessionId'],
					'timeStamp' => $this->getTimestamp(),
					'balance'	=> $this->currentBalanceOnError
				];

			}else{
				$externalResponse = [
					'errorCode' => self::ERROR_CODE[$errorCode],
					'errorMessage' => self::ERROR_MESSAGE[$errorCode]
				];
			}
        }
        $this->utils->debug_log("HOLI_SEAMLESS_SERVICE: ($method)", [
			'$this->request' => $this->request,
			'$externalResponse' => $externalResponse,
		]);

        $fields = ['player_id' => $player_id];
        return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

    private function initializeTransaction($method, $requiredParams) {
		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE: (initializeTransaction)", [
			'$this->enable_skip_validation' => $this->enable_skip_validation,
		]);
        if (!$this->initialize()) {
            throw new Exception(self::SYSTEM_ERROR);
        }

        if (!$this->isGameUnderMaintenance()) {
            throw new Exception(self::GENERAL_ERROR_GAME_UNDER_MAINTENANCE);
        }

        if (!$this->game_api->validateWhiteIP()) {
            throw new Exception(self::IP_NOT_ALLOWED);
        }

        if (!$this->isValidRequest( $this->request, $requiredParams)) {
            throw new Exception(self::GENERAL_ERROR_INVALID_REQUEST);
        }

		if(!$this->enable_skip_validation){
			if (!$this->isValidKey( constant("self::METHOD_" . strtoupper($method)) )) {
				throw new Exception(self::GENERAL_ERROR_UNAUTHORIZED);
			}
	
			if (!$this->validateCurrency()) {
				throw new Exception(self::UNSUPPORTED_CURRENCY);
			}
	
			if (!$this->validateSignature( constant("self::METHOD_" . strtoupper($method)) )) {
				throw new Exception(self::SIGNATURE_VERIFICATION_FAILED);
			}
		}

		if ( in_array( constant("self::METHOD_" . strtoupper($method)) , self::METHOD_SIGNATURE_COLLECTION_1 ) && !$this->validate_tableId()) {
            throw new Exception(self::TABLE_NOT_FOUND);
        }

    }

	private function buildParams($method) {
		return [
			'player_id'			=> isset($this->request['playerId']) ? $this->request['playerId'] : null,
			'amount' 			=> isset($this->request['debitAmount']) ? $this->request['debitAmount'] : (isset($this->request['creditAmount']) ? $this->request['creditAmount'] : (isset($this->request['rollbackAmount']) ? $this->request['rollbackAmount'] : null)),
			'currency' 			=> isset($this->request['currency']) ? $this->request['currency'] : null,
			'table_id' 			=> isset($this->request['tableId']) ? $this->request['tableId'] : null,
			'round_id' 			=> isset($this->request['roundId']) ? $this->request['roundId'] : null,
			'game_type' 		=> isset($this->request['gameType']) ? $this->request['gameType'] : null,
			'transaction_id' 	=> isset($this->request['transactionId']) ? $this->request['transactionId'] : null,
			'session_id' 		=> isset($this->request['sessionId']) ? $this->request['sessionId'] : null,
			'timestamp' 		=> $this->getTimestamp(),
			'signature' 		=> $this->generateSignature(constant("self::METHOD_" . strtoupper($method))),
			'is_jackpot' 		=> isset($this->request['isJackpot']) ? $this->request['isJackpot'] : null,
			'bet_details' 		=> isset($this->request['betDetails']) ? json_encode($this->request['betDetails']) : null,
			'trans_type' 		=> constant("self::METHOD_" . strtoupper($method)),
			'external_uniqueid' => isset($this->request['transactionId']) ? $method . '-' . $this->request['transactionId'] : null
		];
	}
	

    private function adjustWalletAndReturnBalance($method, $player_id, $params) {
        $trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($method, $player_id, $params, &$errorCode, &$balance) {
            $adjustWalletResponse = $this->adjustWallet($method, $player_id, $params);
            $errorCode = $adjustWalletResponse['code'];
            $balance = $adjustWalletResponse['current_balance'];

            if (!$adjustWalletResponse['success']) {
                return false;
            }

            return true;
        });

        if (!$trans_success) {
			$this->currentBalanceOnError = $balance;
            throw new Exception($errorCode);
        }

        return [
            'balance' => $balance,
            'transactionId' => $params['transaction_id'],
            'timeStamp' => $this->getTimestamp(),
            'signature' => $this->generateSignature($method),
        ];
    }
        

	public function adjustWallet($transaction_type,$player_id, &$data){

		$related_inique_id 				= null;
		$related_action 				= null;
		$playerId	 					= $player_id;
		$balance						= $this->game_api->gameAmountToDBTruncateNumber($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$game_code 						= isset($this->request['gameType']) ? $this->request['gameType'] : null;
		$uniqueid_of_seamless_service 	= $this->game_platform_id. '-' .$data['external_uniqueid']  ;

		#implement unique id
		if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
			$this->wallet_model->setUniqueidOfSeamlessService( $uniqueid_of_seamless_service ,$game_code );
		}


		if($transaction_type == self::METHOD_DEBIT){
			$amount = $data['amount'] * -1; 								#getting the negative betting value
			$data['bet_amount'] = $data['amount'];

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, [ 'external_uniqueid' => $data['external_uniqueid']]);

			if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
				if( empty($existingTrans) ){
					$existingTrans = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->previous_table, [ 'external_uniqueid' => $data['external_uniqueid']]);
				}
			}
			
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::DUPLICATE_TRANSACTION, $playerId, $balance, $balance);
			}

			
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_AMOUNT, $playerId,  $balance, $balance);
			}


			if($balance < $data['amount']){
				return $this->resultForAdjustWallet(self::INSUFFICIENT_FUNDS, $playerId,  $balance, $balance);
			}


			$data['status'] = GAME_LOGS::STATUS_PENDING;
		
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}

			$this->wallet_model->setGameProviderIsEndRound(false);


        }else if($transaction_type == self::METHOD_CREDIT || $transaction_type == self::METHOD_ROLLBACK){
			$amount = $data['amount'];

			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, [ 'round_id' => $data['round_id'], 'trans_type' => self::METHOD_DEBIT]);
			#check if bet transaction existing 

			if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
				if( empty($bet_transaction_details) ){
					$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, [ 'round_id' => $data['round_id'],  'trans_type' => self::METHOD_DEBIT]);
				}
			}
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::TRANSACTION_NOT_FOUND, $playerId,  $balance, $balance);
			}
		

			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::ALREADY_SETTLED, $playerId,  $balance, $balance);
			}

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, ['external_uniqueid' => $data['external_uniqueid']]);

			if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
				if( empty($existingTrans) ){
					$existingTrans = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->previous_table, ['external_uniqueid' => $data['external_uniqueid']]);
				}
			}
			
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::DUPLICATE_TRANSACTION, $playerId, $balance, $balance);
			}

			#check if transaction already settled
			if( $transaction_type == self::METHOD_CREDIT ){
				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
					return $this->resultForAdjustWallet(self::ALREADY_CANCELLED, $playerId,  $balance, $balance);
				}

				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_REFUND){
					return $this->resultForAdjustWallet(self::ALREADY_REFUNDED, $playerId,  $balance, $balance);
				}

				$flag_where_update = [
					'round_id' 			=> $data['round_id'],
					'game_platform_id' 	=> $this->game_platform_id,
					'trans_type' 		=> self::METHOD_DEBIT
				];
	
				$flag_set_update = [
					'status' 		=> Game_logs::STATUS_SETTLED,
					'win_amount' 	=> $data['amount'],
				];
	
				$settle_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($tableName, $flag_where_update, $flag_set_update);
				if(!$settle_bet){
					return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId,  $balance, $balance); 
				}
	
				$data['bet_amount'] 	= $bet_transaction_details['amount'];
				$data['win_amount'] 	= $data['amount'];
				$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

				
				$related_inique_id 	= !empty($bet_transaction_details['external_uniqueid']) ? $this->implodeWithDash( ['game', $bet_transaction_details['game_platform_id'], $bet_transaction_details['external_uniqueid']] ) : null;
				$related_action 	= !empty($bet_transaction_details['balance_adjustment_method']) ? Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET : null;
	
				#implement action type
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
				}

				#implement related unique id type
				if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
					$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_inique_id);
				}
				#implement related action type
				if(method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')){
					$this->wallet_model->setRelatedActionOfSeamlessService($related_action); 
				}

			}else if( $transaction_type == self::METHOD_ROLLBACK ){
				#get bet transaction

				$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, ['transaction_id' => $data['transaction_id'], 'round_id' => $data['round_id'], 'trans_type' => self::METHOD_DEBIT]);
				#check if bet transaction existing 

				if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
					if( empty($bet_transaction_details) ){
						$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, ['transaction_id' => $data['transaction_id'], 'round_id' => $data['round_id'],  'trans_type' => self::METHOD_DEBIT]);
					}
				}

				
				if(empty($bet_transaction_details)){
					return $this->resultForAdjustWallet(self::TRANSACTION_NOT_FOUND, $playerId,  $balance, $balance);
				}
			

				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_SETTLED){
					return $this->resultForAdjustWallet(self::ALREADY_SETTLED, $playerId,  $balance, $balance);
				}

				$flag_where_update = [
					'transaction_id' 			=> $data['transaction_id'],
					'round_id' 			=> $data['round_id'],
					'game_platform_id' 	=> $this->game_platform_id,
					'trans_type' 		=> self::METHOD_DEBIT
				];
	
				$flag_set_update = [
					'status' 		=> Game_logs::STATUS_REFUND,
					'win_amount' 	=> $data['amount'],
				];
	
				$settle_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($tableName, $flag_where_update, $flag_set_update);
				// dd($settle_bet);
				if(!$settle_bet){
					return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId,  $balance, $balance); 
				}
	
				$data['bet_amount'] 	= $bet_transaction_details['amount'];
				$data['win_amount'] 	= $data['amount'];
				$data['status'] 		= GAME_LOGS::STATUS_REFUND;

				$related_inique_id 	= !empty($bet_transaction_details['external_uniqueid']) ? $this->implodeWithDash( ['game', $bet_transaction_details['game_platform_id'], $bet_transaction_details['external_uniqueid']] ) : null;
				$related_action 	= !empty($bet_transaction_details['balance_adjustment_method']) ? Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET : null;

				#implement action type
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
				}

				#implement related unique id type
				if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
					$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_inique_id);
				}
				#implement related action type
				if(method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')){
					$this->wallet_model->setRelatedActionOfSeamlessService($related_action); 
				}
			}

			#implement isEndRound
			$this->wallet_model->setGameProviderIsEndRound(true);

		}else{
			return $this->resultForAdjustWallet(self::GENERAL_ERROR_INVALID_REQUEST, $playerId,  $balance, $balance);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;



		$this->utils->debug_log('HOLI_SEAMLESS-amounts', [$beforeBalance, $afterBalance, $amount]);


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
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId,  $balance, $balance);
			}
			$afterBalance 						= $beforeBalance + $amount;
			$this->utils->debug_log('HOLI_SEAMLESS', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else if($this->utils->compareResultFloat($amount, '<', 0)){	

			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('HOLI_SEAMLESS', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId,  $balance, $balance);
			}
		}

		$data['transaction_date']		  		= isset($data['timestamp']) ? date("Y-m-d H:i:s", $data['timestamp'] / 1000) : date("Y-m-d H:i:s");
		$data['extra_info']				 		= json_encode($this->request);
		$data['balance_adjustment_amount'] 		= $amount;
		$data['before_balance'] 				= $beforeBalance;
        $data['after_balance'] 					= $afterBalance;
        $data['elapsed_time'] 					= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 				= $this->game_platform_id;
        $data['player_id'] 				    	= $playerId;
        $data['created_at'] 					= date("Y-m-d H:i:s");
        $data['updated_at'] 					= date("Y-m-d H:i:s");
		
		$this->utils->debug_log('HOLI_SEAMLESS--adjust-wallet', $data);
		$this->walletResultAdjustmentData = $data;

		$insertTransaction = $this->original_seamless_wallet_transactions->insertTransactionData($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::COMPLETED_SUCCESSFULLY, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId,  $balance, $balance);
	}

	

	public function resultForAdjustWallet($code, $player_id = null, $before_balance = null, $after_balance = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code === self::COMPLETED_SUCCESSFULLY ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $current_balance,
				'before_balance'  => $code === self::COMPLETED_SUCCESSFULLY ? $before_balance : null,
				'after_balance'   => $code === self::COMPLETED_SUCCESSFULLY ? $after_balance : null,
			];
		$this->utils->debug_log("HOLI_SEAMLESS--AdjustWalletResult" , $response);
		return $response;
	}

	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE: (handleExternalResponse)",
		'status', $status,
		'type', $type,
		'data', $data,
		'response', $response,
		'error_code', $error_code);
        
		$httpStatusCode =  200;

		if(!empty($error_code) && self::ERROR_CODE[$error_code] != 0){

			$this->utils->debug_log("HOLI_SEAMLESS_SERVICE: (handleExternalResponse)",
				[
					'status' 		=> $status,
					'type' 			=> $type,
					'data'			=> $data,
					'response' 		=> $response,
					'error_code' 	=> $error_code,
					'self::ERROR_CODE[$error_code]' => self::ERROR_CODE[$error_code]
				]
			);

			if( self::ERROR_CODE[$error_code] <= 100 && self::ERROR_CODE[$error_code] > 0){
				$httpStatusCode = 200;
			}else{
				$httpStatusCode = self::ERROR_CODE[$error_code];
			}

        }

		if(empty($response)){
			$response = [];
		}

		$errorCodeListWithoutBalanceReturn = [
					self::ERROR_MESSAGE[self::GENERAL_ERROR], 
					self::ERROR_MESSAGE[self::SIGNATURE_VERIFICATION_FAILED],
					self::ERROR_MESSAGE[self::GENERAL_ERROR_SERVICE_UNAVAILABLE],
					self::ERROR_MESSAGE[self::GENERAL_ERROR_INVALID_SESSION_TOKEN],
					self::ERROR_MESSAGE[self::GENERAL_ERROR_INVALID_REQUEST],
					self::ERROR_MESSAGE[self::GENERAL_ERROR_GAME_UNDER_MAINTENANCE],
					self::ERROR_MESSAGE[self::GENERAL_ERROR_UNAUTHORIZED],
				];

		if( in_array(self::ERROR_MESSAGE[$error_code] , $errorCodeListWithoutBalanceReturn)  ){
			$response = [
				"errorCode" => $response['errorCode'],
				"errorMessage" => $response['errorMessage']
			];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### HOLI_SEAMLESS_SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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
		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (retrieveHeaders):", [
			'$this->headers' => $this->headers,
		]);
	}

	public function parseRequest(){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$request_json = file_get_contents('php://input');
			$this->raw_request = $request_json;
			$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (parseRequest):", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (parseRequest) parsed:", $request_json);
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

	public function getPlayerInfoById($gameUsername){
		$player = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);		 
		if(empty($player)){
			return [false, null, null];
		}
		$player['playerId'] = $player['player_id'];
		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (getPlayerInfoById):", [
			'$player' => $player,
		]);
		return [true, $player, $player['username']];
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

    private function getError($errorCode){
        $error_response = [
            'message' => self::ERROR_MESSAGE[$errorCode],
        ];
        return $error_response;
    }

    private function validate_playerId(){
		$playerId = isset($this->request['playerId']) ? $this->request['playerId'] : null;
		if($playerId == $this->playerId){
			return true;
		}
		return false;
	}

    private function isValidKey( $callType ){
		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (generateSignature):", [
			'$this->headers[`Authorization`] ' => $this->headers['Authorization'],
			'this->api_key' => [$this->api_key, "Api-Key ". $this->api_key, $this->player_token]
		]);

		if(!isset($this->headers['Authorization'])){
			return false;
		}

		if( $this->headers['Authorization'] != "Api-Key ". $this->player_token){
			return false;
		}
		
		$this->headers['Authorization'] = $this->generateKey( $callType );
		return true;
	}

	private function generateKey( $callType ){
		if( empty($callType) ){
            return false;
        }

		return "Api-Key ". $this->game_api->api_key;
		
	}

	private function generateSignature( $callType ){

		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (generateSignature):", [
			'$callType' => $callType,
		]);

        if( empty($callType) ){
            return false;
        }

		// if( !empty($this->request->signature) ){
		// 	return $this->request->signature;
		// }

		$timeStamp 		= floor(microtime(true) * 1000); 
        $signature      = '';
        $salt           = $this->game_api->salt; 					//unqiue identifier
        $playerId       = $this->request['playerId'];

   
        if( in_array( $callType, self::METHOD_SIGNATURE_COLLECTION_1 ) ){
			$transactionId  = $this->request['transactionId']; 
			$data = $transactionId . $salt . $playerId;
			$signature = hash('sha256', $data);


        }else  if( in_array( $callType, self::METHOD_SIGNATURE_COLLECTION_2 ) ){

			$data = $this->request['timeStamp'] . $salt . $playerId;
			$signature = hash('sha256', $data);

        }else{
			return false;
        }

		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (generateSignature):", [
			'$callType' => $callType,
			'self::METHOD_SIGNATURE_COLLECTION_1' => self::METHOD_SIGNATURE_COLLECTION_1,
			'self::METHOD_SIGNATURE_COLLECTION_2' => self::METHOD_SIGNATURE_COLLECTION_2,
			'$signature' => $signature,
		]);

		return $signature;
	}

	private function validate_tableId(){
		$request_tableId = isset($this->request['tableId']) ? $this->request['tableId'] : null;
		if(strtolower($request_tableId) == strtolower($this->tableId)){
			return true;
		}
		return false;
	}


	private function validateCurrency(){
		$request_currency = isset($this->request['currency']) ? $this->request['currency'] : null;
		if(strtolower($request_currency) == strtolower($this->currency)){
			return true;
		}
		return false;
	}

	private function validateSignature($callType = null){

		$generatedSignature = $this->generateSignature($callType);
		$request_signature = isset($this->request['signature']) ? $this->request['signature'] : null;
		$isSignatureMatch = $request_signature && hash_equals($generatedSignature, $request_signature);

	
		$this->utils->debug_log("HOLI_SEAMLESS_SERVICE (generateSignature):", [
			'$callType' => $callType,
			'generatedSignature' => $generatedSignature,
			'request_signature' => $request_signature,
			'isSignatureMatch' => $isSignatureMatch,
		]);

		
		if($isSignatureMatch){
			return true;
		}
		return false;
	}

	private function generateHash($timestamp, $salt, $playerId) {
		$input = $timestamp . $salt . $playerId;
		return hash('sha256', $input);
	}
	

	private function getTimestamp(){
		$milliseconds = round(microtime(true) * 1000);
		return $milliseconds;

	}

	public function isGameUnderMaintenance()
	{
		return !$this->utils->setNotActiveOrMaintenance($this->game_platform_id);
	}

	private function implodeWithDash($array) {
		return implode("-", $array);
	}

}///END OF FILE////////////