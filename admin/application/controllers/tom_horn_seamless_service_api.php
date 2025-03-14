<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Tom_horn_seamless_service_api extends BaseController {

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
			$allow_invalid_sign,
			$secret_key,
			$partner_id,
			$raw_request,
			$enable_hint
			;
	#error code const 
	const SUCCESS                           = 'SUCCESS';
    const GENERAL_ERROR                     = 'GENERAL_ERROR';
    const WRONG_INPUT_PARAMERTERS           = 'WRONG_INPUT_PARAMERTERS';
    const INVALID_SIGN                      = 'INVALID_SIGN';
    const INVALID_PARTNER                   = 'INVALID_PARTNER';
    const IDENTITY_NOT_FOUND                = 'IDENTITY_NOT_FOUND';
    const INSUFFICIENT_FUNDS                = 'INSUFFICIENT_FUNDS';
    const INVALID_CURRENCY                  = 'INVALID_CURRENCY';
    const TRANSACTION_ALREADY_ROLLEDBACK    = 'TRANSACTION_ALREADY_ROLLEDBACK';
    const PLAYERS_LIMIT_REACHED             = 'PLAYERS_LIMIT_REACHED';
    const DUPLICATE_REFERENCE               = 'DUPLICATE_REFERENCE';
    const UNKNOWN_TRANSACTION               = 'UNKNOWN_TRANSACTION';

	#additional error
    const INVALID_IP_ADDRESS               	= 'INVALID_IP_ADDRESS';
    const SERVICE_UNAVAILABLE               = 'SERVICE_UNAVAILABLE';
    const INVALID_AMOUNT               		= 'INVALID_AMOUNT';
    const ALREADY_CANCELLED            		= 'ALREADY_CANCELLED';
    const ALREADY_SETTLED            		= 'ALREADY_SETTLED';
	const METHOD_NOT_SUPPORTED 				= 'METHOD_NOT_SUPPORTED';


	const ERROR_CODES = [
		#error codes from GP
		self::SUCCESS                           => 0,
		self::GENERAL_ERROR                     => 1,
		self::WRONG_INPUT_PARAMERTERS           => 2,
		self::INVALID_SIGN                      => 3,
		self::INVALID_PARTNER                   => 4,
		self::IDENTITY_NOT_FOUND                => 5,
		self::INSUFFICIENT_FUNDS                => 6,
		self::INVALID_CURRENCY                  => 8,
		self::TRANSACTION_ALREADY_ROLLEDBACK    => 9,
		self::PLAYERS_LIMIT_REACHED             => 10,
		self::DUPLICATE_REFERENCE               => 11,
		self::UNKNOWN_TRANSACTION               => 12,

		#treat additional custom error code as GENERAL_ERROR 
		self::INVALID_IP_ADDRESS               	=> 1,
		self::SERVICE_UNAVAILABLE               => 1,
		self::INVALID_AMOUNT               		=> 1,
		self::METHOD_NOT_SUPPORTED 				=> 1,

		#transaction error code as WRONG_INPUT_PARAMERTERS
		self::ALREADY_CANCELLED               	=> 12,
		self::ALREADY_SETTLED               	=> 12,


	];
   
  
	#method
    const METHOD_GET_BALANCE   			= 'GetBalance';
    const METHOD_WITHDRAW      			= 'Withdraw';
    const METHOD_DEPOSIT	   			= 'Deposit';
	const METHOD_ROLLBACK_TRANSACTION  	= 'RollbackTransaction';

    #error messages
	const ERROR_MESSAGE = [
		self::SUCCESS                           => 'Success',
        self::GENERAL_ERROR                     => 'General Error',
        self::WRONG_INPUT_PARAMERTERS           => 'Wrong Input Parameter',
        self::INVALID_SIGN                      => 'Invalid Sign',
        self::INVALID_PARTNER                   => 'Unknown partner or partner is disabled.',
        self::IDENTITY_NOT_FOUND                => 'Cannot find a specified identity.',
        self::INSUFFICIENT_FUNDS                => 'Insufficient Funds',
        self::INVALID_CURRENCY                  => 'Invalid Currency',
        self::TRANSACTION_ALREADY_ROLLEDBACK    => 'Transaction already rolled back',
        self::PLAYERS_LIMIT_REACHED             => 'The limit set for the player was reached, and the player could not play.',
        self::DUPLICATE_REFERENCE               => 'Duplicated transaction',
        self::UNKNOWN_TRANSACTION               => 'Unknown Transaction',
		self::INVALID_IP_ADDRESS               	=> 'Invalid Ip address',
		self::SERVICE_UNAVAILABLE               => 'Service Unavailable',
		self::INVALID_AMOUNT               		=> 'Invalid Amount',
		self::ALREADY_CANCELLED                 => 'Trasaction already cancelled',
		self::ALREADY_SETTLED                   => 'Trasaction already settled',
		self::METHOD_NOT_SUPPORTED 				=> 'Method not supported',
		
	];

    #error messages
	const HTTP_CODE = [
		self::SUCCESS                           => '200',
        self::GENERAL_ERROR                     => '200',
        self::WRONG_INPUT_PARAMERTERS           => '200',
        self::INVALID_SIGN                      => '200',
        self::INVALID_PARTNER                   => '200',
        self::IDENTITY_NOT_FOUND                => '200',
        self::INSUFFICIENT_FUNDS                => '200',
        self::INVALID_CURRENCY                  => '200',
        self::TRANSACTION_ALREADY_ROLLEDBACK    => '200',
        self::PLAYERS_LIMIT_REACHED             => '200',
        self::DUPLICATE_REFERENCE               => '200',
        self::UNKNOWN_TRANSACTION               => '200',
		self::INVALID_IP_ADDRESS 				=> '200',
		self::SERVICE_UNAVAILABLE 				=> '200',
		self::INVALID_AMOUNT 					=> '200',
		self::ALREADY_CANCELLED 				=> '200',
		self::ALREADY_SETTLED 					=> '200',
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

	public function index($game_platform_id, $method){
		// $method = implode('/', $methods);
		$this->game_platform_id = $game_platform_id;
		$this->request_method = $method;
		$this->utils->debug_log('TOM HORN--method : '. $this->request_method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
            case 'GetBalance':
                $this->getBalance();
				break;
            case 'Withdraw':
                $this->withdraw();
                break;
			case 'Deposit':
				$this->deposit();
				break;
			case 'RollbackTransaction':
				$this->RollbackTransaction();
				break;
			case 'GenerateSign':
				$this->generateSign();
				break;
			default:
				$this->utils->debug_log('TOM HORN seamless service: Invalid API Method');
				http_response_code(404);
		}
	}

	public function initialize(){
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("TOM HORN SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency = $this->game_api->getCurrency();
		$this->allow_invalid_sign = $this->game_api->allow_invalid_sign;
		$this->enable_hint = $this->game_api->enable_hint;
		$this->partner_id = $this->game_api->partner_id;
		return true;
	}

    public function getError($errorCode){
        $error_response = [
            'Code' => self::ERROR_CODES[$errorCode],
            'Message' => self::ERROR_MESSAGE[$errorCode],
        ];
		if($this->enable_hint && $errorCode == self::INVALID_SIGN){
			$error_response['hint'] = $this->game_api->generateSign($this->request);
		}
        return $error_response;
    }

    public function getBalance(){
        $this->utils->debug_log("TOM HORN SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_GET_BALANCE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['partnerID', 'sign', 'name', 'currency'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::GENERAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

            if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->validate_partner()){
				throw new Exception(self::INVALID_PARTNER);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::WRONG_INPUT_PARAMERTERS);
			}

			if(!$this->isValidSign(self::METHOD_GET_BALANCE)){
				throw new Exception(self::INVALID_SIGN);
			}
            
			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['name']);
            if(!$getPlayerStatus){
                throw new Exception(self::IDENTITY_NOT_FOUND);
            }

			$player_id = $player->player_id;

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
            $externalResponse = [
                'Code'      => self::ERROR_CODES[$errorCode],
                'Message'   => self::ERROR_MESSAGE[$errorCode],
                'balance'   => [
                    'Amount'    => $balance,
                    'Currency'  => strtoupper($this->currency),
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

	public function generateSign(){
		$method = isset($this->request['method']) ? $this->request['method'] : null;
		unset($this->request['method']);

		$success = true;
		if(!$this->initialize()) {
			$success = false;
			$response = [
				'success'   => 'error',
			];
		}

		if(!$this->game_api->getSystemInfo('enable_generateSign',false)){
			$success = false;
			$response = [
				'success'   => 'error',
				'message'   => 'feature disabled',
			];
		}

		if(!$this->game_api->validateWhiteIP()){
			$success = false;
			$response = [
				'success'   => 'error',
				'message'   => 'invalid ip',
			];
		}

		$this->CI->load->model(array('player_model'));
	
		if($success){
			$response =  [
				'sign' => $this->game_api->generateSign($this->request, $method),
			];
		}
		
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
		return $output;
	}

	public function withdraw(){
		$callType 					= self::METHOD_WITHDRAW;
		$errorCode 					= self::GENERAL_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['partnerID', 'sign', 'name', 'amount', 'currency', 'reference', 'gameRoundID'];
		$this->utils->debug_log('TOM HORN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::GENERAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->validate_partner()){
				throw new Exception(self::INVALID_PARTNER);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::WRONG_INPUT_PARAMERTERS);
			}

			if(!$this->isValidSign(self::METHOD_WITHDRAW)){
				throw new Exception(self::INVALID_SIGN);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['name']);
            if(!$getPlayerStatus){
                throw new Exception(self::IDENTITY_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		#rebuilding parameters
		$params = [
            'partner_id'       	=> isset($this->request['partnerID']) ? $this->request['partnerID'] : null,
            'sign'             	=> isset($this->request['sign']) ? $this->request['sign'] : null,
            'name'             	=> isset($this->request['name']) ? $this->request['name'] : null,
            'amount'           	=> isset($this->request['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['amount']) : null,
            'currency'         	=> isset($this->request['currency']) ? $this->request['currency'] : null,
            'reference'        	=> isset($this->request['reference']) ? (string) $this->request['reference'] : null,
            'session_id'       	=> isset($this->request['sessionID']) ? (string) $this->request['sessionID'] : null,
            'round_id'    	=> isset($this->request['gameRoundID']) ? (string) $this->request['gameRoundID'] : null,
            'game_round_id'    	=> isset($this->request['gameRoundID']) ? (string) $this->request['gameRoundID'] : null,
            'game_module'      	=> isset($this->request['gameModule']) ? $this->request['gameModule'] : null,
            'fgb_campaign_code' => isset($this->request['fgbCampaignCode']) ? $this->request['fgbCampaignCode'] : null,
            'fgb_bet_amount'    => isset($this->request['fgbBetAmount']) ? $this->request['fgbBetAmount'] : null,
			'trans_type'        => $callType,
			'external_uniqueid' => isset($this->request['reference']) ? (string) $this->request['reference'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("TOM HORN-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				$errorCode 			  = $adjustWalletResponse['code'];
				$this->utils->debug_log("TOM HORN-lockAndTransForPlayerBalance-adjustWalletResponse", $adjustWalletResponse);
				if(!$adjustWalletResponse['success']){
					return false;	
				}
				$balance 	= $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("TOM HORN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
            $externalResponse = [
				'Code' 			=> self::ERROR_CODES[$errorCode],
				'Message' 		=> self::ERROR_MESSAGE[$errorCode],
				'Transaction' 	=> [
					'Balance' 		=> $balance,
					'Currency' 		=> $this->currency,
					'ID' 			=> $this->request['reference'],
				],
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id' => $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function deposit(){
		$callType 					= self::METHOD_DEPOSIT;
		$errorCode 					= self::GENERAL_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['partnerID', 'sign', 'name', 'amount', 'currency', 'reference'];
		$this->utils->debug_log('TOM HORN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::GENERAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

            if(!$this->validate_currency()){
				throw new Exception(self::INVALID_CURRENCY);
			}

			if(!$this->validate_partner()){
				throw new Exception(self::INVALID_PARTNER);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::WRONG_INPUT_PARAMERTERS);
			}

			if(!$this->isValidSign(self::METHOD_DEPOSIT)){
				throw new Exception(self::INVALID_SIGN);
			}


            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['name']);
            if(!$getPlayerStatus){
                throw new Exception(self::IDENTITY_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		#rebuilding parameters
		$params = [
            'partner_id'       	=> isset($this->request['partnerID']) ? $this->request['partnerID'] : null,
            'sign'             	=> isset($this->request['sign']) ? $this->request['sign'] : null,
            'name'             	=> isset($this->request['name']) ? $this->request['name'] : null,
            'amount'           	=> isset($this->request['amount']) ? $this->game_api->gameAmountToDBTruncateNumber($this->request['amount']) : null,
            'currency'         	=> isset($this->request['currency']) ? $this->request['currency'] : null,
            'reference'        	=> isset($this->request['reference']) ? (string) $this->request['reference'] : null,
            'session_id'       	=> isset($this->request['sessionID']) ? (string) $this->request['sessionID'] : null,
            'round_id'    		=> isset($this->request['gameRoundID']) ? (string) $this->request['gameRoundID'] : null,
            'game_round_id'    	=> isset($this->request['gameRoundID']) ? (string) $this->request['gameRoundID'] : null,
            'game_module'      	=> isset($this->request['gameModule']) ? $this->request['gameModule'] : null,
            'fgb_campaign_code' => isset($this->request['fgbCampaignCode']) ? $this->request['fgbCampaignCode'] : null,
            'fgb_bet_amount'    => isset($this->request['fgbBetAmount']) ? $this->request['fgbBetAmount'] : null,
            'win_type'    		=> isset($this->request['type']) ? $this->request['type'] : null,
            'is_round_end'      => isset($this->request['isRoundEnd']) ? $this->request['isRoundEnd'] : null,
			'trans_type'        => $callType,
			'external_uniqueid' => isset($this->request['reference']) ? (string) $this->request['reference'] : null,
		];


		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("TOM HORN-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet(self::METHOD_DEPOSIT, $player, $params);
				$errorCode 			  = $adjustWalletResponse['code'];
				if(!$adjustWalletResponse['success']){
					return false;	
				}
				
				$balance 	= $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("TOM HORN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
			$externalResponse = [
				'Code' 			=> self::ERROR_CODES[$errorCode],
				'Message' 		=> self::ERROR_MESSAGE[$errorCode],
				'Transaction' 	=> [
					'Balance' 		=> $balance,
					'Currency' 		=> $this->currency,
					'ID' 			=> $this->request['reference'],
				],
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id' => $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function RollbackTransaction(){
		$callType 					= self::METHOD_ROLLBACK_TRANSACTION;
		$errorCode 					= self::GENERAL_ERROR;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['partnerID', 'sign', 'name', 'reference', 'sessionID'];
		$this->utils->debug_log('TOM HORN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::GENERAL_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SERVICE_UNAVAILABLE);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::INVALID_IP_ADDRESS);
			}

			if(!$this->validate_partner()){
				throw new Exception(self::INVALID_PARTNER);
			}

			if(!$this->isValidRequest($this->request,$requiredParams)){
				throw new Exception(self::WRONG_INPUT_PARAMERTERS);
			}

			if(!$this->isValidSign()){
				throw new Exception(self::INVALID_SIGN);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request['name']);
            if(!$getPlayerStatus){
                throw new Exception(self::IDENTITY_NOT_FOUND);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	


		#rebuilding parameters
		$params = [
            'partner_id'       	=> isset($this->request['partnerID']) ? $this->request['partnerID'] : null,
            'sign'             	=> isset($this->request['sign']) ? $this->request['sign'] : null,
            'name'             	=> isset($this->request['name']) ? $this->request['name'] : null,
            'reference'        	=> isset($this->request['reference']) ? (string) $this->request['reference'] : null,
            'session_id'       	=> isset($this->request['sessionID']) ? $this->request['sessionID'] : null,
			'trans_type'        => $callType,
			'external_uniqueid' => isset($this->request['reference']) ? self::METHOD_ROLLBACK_TRANSACTION.'-'.$this->request['reference'] : null,
		];

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("TOM HORN-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet(self::METHOD_ROLLBACK_TRANSACTION, $player, $params);
				$errorCode 			  = $adjustWalletResponse['code'];
				if(!$adjustWalletResponse['success']){
					return false;	
				}
				
				$balance 	= $adjustWalletResponse['current_balance'];
				return true;
			});
		}

		$this->utils->debug_log("TOM HORN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if($trans_success){
			$externalResponse = [
				'Code' 			=> self::ERROR_CODES[$errorCode],
				'Message' 		=> self::ERROR_MESSAGE[$errorCode],
            ]; 
        }else{
            $externalResponse = $this->getError($errorCode);
        }

		$fields = [
			'player_id' => $player_id,
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

	public function validate_partner(){
		$request_currency = isset($this->request['partnerID']) ? $this->request['partnerID'] : null;
		if(strtolower($request_currency) == strtolower($this->partner_id)){
			return true;
		}
		return false;
	}

	public function isValidSign($method=null){
		if($this->allow_invalid_sign){
			return true;
		}
		if($this->request['sign'] == $this->game_api->generateSign($this->request, $method)){
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
			$this->utils->debug_log('TOM HORN-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->gameAmountToDBTruncateNumber($this->game_api->getPlayerBalanceById($playerId));
		$tableName 						= $this->game_api->getTransactionsTable();
		$previousTableName				= $this->game_api->getPreviousTableName();
		$game_code 						= isset($this->request['game_module']) ? $this->request['game_module'] : null;
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$game_code);
		
		$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, ['external_uniqueid' => $data['external_uniqueid']]);
		if(empty($existingTrans) && $this->checkPreviousMonth()){
			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($previousTableName, ['external_uniqueid' => $data['external_uniqueid']]);
		}

		$flag_update_on_previous_table = false;

		if($transaction_type == self::METHOD_WITHDRAW){
			$amount = $data['amount'] * -1; #getting the negative betting value
			$data['bet_amount'] = $data['amount'];
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::DUPLICATE_REFERENCE, $playerId);
			}
			if($data['amount'] < 0){
				return $this->resultForAdjustWallet(self::INVALID_AMOUNT, $playerId);
			}
			if($balance < $data['amount']){
				return $this->resultForAdjustWallet(self::INSUFFICIENT_FUNDS, $playerId);
			}
			$data['status'] = GAME_LOGS::STATUS_PENDING;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}
			#is_end
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				$this->wallet_model->setGameProviderIsEndRound(false); 
			}

			if(method_exists($this->wallet_model, 'setExternalGameId')){
				$this->wallet_model->setExternalGameId($data['game_module']); 
			}
			
        }else if($transaction_type == self::METHOD_DEPOSIT){
			$amount = $data['amount'];
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::DUPLICATE_REFERENCE, $playerId);
			}

			#get bet transaction
			$where_bet_transaction = [
				'round_id' => $data['round_id'], 
				'trans_type' => self::METHOD_WITHDRAW
			];
			
			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, $where_bet_transaction);
			if(empty($bet_transaction_details) && $this->checkPreviousMonth()){
				$bet_transaction_details  = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($previousTableName, $where_bet_transaction);
				if(!empty($bet_transaction_details)){
					$flag_update_on_previous_table = true;
				}
			}

			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::UNKNOWN_TRANSACTION, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_ROLLEDBACK, $playerId);
			}

			$flag_where_update = [
				'round_id' => $data['round_id'],
				'game_platform_id' => $this->game_platform_id
			];

			$flag_set_update = [
				'status' => Game_logs::STATUS_SETTLED,
				'win_amount' => $data['amount'],
			];

			$flagTable = $flag_update_on_previous_table ? $previousTableName : $tableName;

			$settle_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($flagTable, $flag_where_update, $flag_set_update);
			if(!$settle_bet){
				return $this->resultForAdjustWallet(self::GENERAL_ERROR, $playerId); 
			}

			$data['bet_amount'] 	= $bet_transaction_details['amount'];
			$data['win_amount'] 	= $data['amount'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
			}
			
			#implement isEndRound
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				$this->wallet_model->setGameProviderIsEndRound(true); 
			}

			#related unique id
			if(method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')){
				$related_unique_id = isset($bet_transaction_details['reference']) ? 'game-'.$this->game_platform_id.'-'.$bet_transaction_details['reference'] : null;
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_unique_id); 
			}

			#related action type
			if(method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')){
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET); 
			}

			if(method_exists($this->wallet_model, 'setExternalGameId')){
				$this->wallet_model->setExternalGameId($data['game_module']); 
			}

		}else if($transaction_type == self::METHOD_ROLLBACK_TRANSACTION){
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::TRANSACTION_ALREADY_ROLLEDBACK, $playerId);
			}
			$bet_transaction_details_where = [
				'reference' => $data['reference'],
				'trans_type' => self::METHOD_WITHDRAW,
			];

			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, $bet_transaction_details_where);
			if(empty($bet_transaction_details) && $this->checkPreviousMonth()){
				$bet_transaction_details  = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($previousTableName, $bet_transaction_details_where);
				if(!empty($bet_transaction_details)){
					$flag_update_on_previous_table = true;
				}
			}

			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::UNKNOWN_TRANSACTION, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::ALREADY_SETTLED, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}
			
			#get the bet amount to be refunded
			$amount = isset($bet_transaction_details['amount']) ? $bet_transaction_details['amount'] : 0;

			$flag_where_update = [
				'reference' => $data['reference'],
				'trans_type' => self::METHOD_WITHDRAW
			];

			$flag_set_update = [
				'status' => Game_logs::STATUS_CANCELLED
			];

			$flagTable = $flag_update_on_previous_table ? $previousTableName : $tableName;
			
			$update_bet_status_to_cancelled = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($flagTable, $flag_where_update, $flag_set_update);

			if(!$update_bet_status_to_cancelled){
				return $this->resultForAdjustWallet(self::GENERAL_ERROR, $playerId); 
			}
			$data['status'] = GAME_LOGS::STATUS_CANCELLED;

			if(!empty($bet_transaction_details['game_module'])){
				$data['game_module'] = $bet_transaction_details['game_module'];

				if(method_exists($this->wallet_model, 'setExternalGameId')){
					$this->wallet_model->setExternalGameId($bet_transaction_details['game_module']); 
				}
			}

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
			}

			#implement isEnd
			if(method_exists($this->wallet_model, 'setGameProviderIsEndRound')){
				$this->wallet_model->setGameProviderIsEndRound(true); 
			}
			
			#related unique id
			if(method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')){
				$related_unique_id = isset($bet_transaction_details['reference']) ? 'game-'.$this->game_platform_id.'-'.$bet_transaction_details['reference'] : null;
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_unique_id); 
			}

			#related action type
			if(method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')){
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET); 
			}
		}else{
			return $this->resultForAdjustWallet(self::METHOD_NOT_SUPPORTED, $playerId);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('TOM HORN-amounts', [$beforeBalance, $afterBalance, $amount]);

		$amount_operator = '>';
		$configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
		if(!empty($configEnabled)){
			$amount_operator = '>=';
		} 

		if($this->utils->compareResultFloat($amount, $amount_operator, 0)){ 	
			#credit
			$data['balance_adjustment_method'] 	= 'credit';
			$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);

			if (method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
				if($this->wallet_model->getRemoteWalletErrorCode() == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
					$response = true;
				}
			}

			if(!$response){
				return $this->resultForAdjustWallet(self::GENERAL_ERROR, $playerId);
			}
			$afterBalance 						= $beforeBalance + $amount;
			$this->utils->debug_log('TOM HORN', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else if($this->utils->compareResultFloat($amount, '<', 0)){	
			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('TOM HORN', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

			if (method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
				if($this->wallet_model->getRemoteWalletErrorCode() == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
					$response = true;
				}
			}

			if(!$response){
				return $this->resultForAdjustWallet(self::GENERAL_ERROR, $playerId);
			}
		}

		$data['transaction_date']		  	= isset($data['timestamp']) ? date("Y-m-d H:i:s", $data['timestamp']) : date("Y-m-d H:i:s");
		$data['extra_info']				 	= $this->raw_request;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;
		$data['game_username']				= $player_info->game_username;
        $data['response_result_id'] 	    = $this->utils->getRequestId();
		
		$this->utils->debug_log('TOM HORN--adjust-wallet', $data);
		
		$insertTransaction = $this->original_seamless_wallet_transactions->insertTransactionData($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::SUCCESS, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::GENERAL_ERROR, $playerId);
	}

	public function checkPreviousMonth(){
		if(date('j', $this->utils->getTimestampNow()) <= $this->game_api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
			return true;
		}

		return false;
	}

	public function resultForAdjustWallet($code, $player_id = null, $before_balance = null, $after_balance = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code === self::SUCCESS || $code === self::DUPLICATE_REFERENCE ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $code === self::SUCCESS || $code === self::DUPLICATE_REFERENCE ? $current_balance : null,
				'before_balance'  => $code === self::SUCCESS || $code === self::DUPLICATE_REFERENCE ? $before_balance : null,
				'after_balance'   => $code === self::SUCCESS || $code === self::DUPLICATE_REFERENCE ? $after_balance : null,
			];
		$this->utils->debug_log("TOM HORN--AdjustWalletResult" , $response);
		return $response;
	}
	

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("TOM HORN SEAMLESS SERVICE: (handleExternalResponse)",
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
		$this->utils->debug_log("##### TOM HORN SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
	
		return $output;
	}

	//default external response template
	public function externalQueryResponse(){
        return ['data'=>[],'error'=>null];
	}
	
	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	public function parseRequest(){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$request_json = file_get_contents('php://input');
			$this->raw_request = $request_json;
			$this->utils->debug_log("TOM HORN SEAMLESS SERVICE raw:", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("TOM HORN SEAMLESS SERVICE raw parsed:", $request_json);
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