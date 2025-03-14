<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Vivogaming_service_api extends BaseController {	
    use Seamless_service_api_module;

    const SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];
    	
	const INSUFFICIENT_FUNDS = [
        'code' => 300,
        'message' => 'Insufficient funds',
    ];
	const OPERATION_FAILED = [
        'code' => 301,
        'message' => 'Operation failed',
    ];

	const BET_NOT_EXISTS = [
        'code' => 301,
        'message' => 'Bet not exists',
    ];

	const REFUND_EXISTS = [
        'code' => 301,
        'message' => 'already processed by CANCELED_BET action',
    ];

	const WIN_EXISTS = [
        'code' => 301,
        'message' => 'already processed by WIN action',
    ];

	const GAME_MAINTENANCE = [
        'code' => 301,
        'message' => 'Game is under maintenance',
    ];

	const GAME_DISABLED = [
        'code' => 301,
        'message' => 'Game is disabled',
    ];

	const IP_ADDRESS_NOT_ALLOWED = [
        'code' => 301,
        'message' => 'IP address is not allowed',
    ];

	const UNKNOWN_TRANSACTION_ID = [
        'code' => 302,
        'message' => 'Unknown transaction id',
    ];

	const TRANSACTION_ID_ALREADY_PROCESSED = [
        'code' => 302,
        'message' => 'Transaction id is already processed',
    ];

	const UNKNOWN_USERID = [
        'code' => 310,
        'message' => 'Unknown user ID',
    ];

	const INTERNAL_ERROR = [
        'code' => 399,
        'message' => 'Internal Error',
    ];

	const INVALID_TOKEN = [
        'code' => 400,
        'message' => 'Invalid token',
    ];

	const INVALID_HASH = [
        'code' => 500,
        'message' => 'Invalid hash',
    ];

	const BET_LIMIT_REACHED = [
        'code' => 812,
        'message' => 'Betting limit reached',
    ];

	const WIN_OF_UNRECOGNIZED_BET = [
        'code' => 299,
        'message' => 'Win of unrecognized bet',
    ];

    const INVALID_TRANSACTION_TYPE = [
        'code' => 5,
        'message' => 'Invalid transaction type',
    ];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;
	private $response_result_id;
	private $transaction_id;

	private $headers;
    private $seamless_service_unique_id;
    private $show_response_message;
    private $show_hint;
    private $currency;
    private $generated_hash;
    private $use_monthly_transactions_table;
    private $force_check_previous_transactions_table;
    private $external_uniqueid;

	public function __construct() {
		parent::__construct();
        $this->ssa_init();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','vivogaming_transactions'));
		
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');	

		$this->game_platform_id = $gamePlatformId;

		// set db
		/* if(!$this->validateCurrencyAndSwitchDB()){
			$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (initialize) validateCurrencyAndSwitchDB: " . $gamePlatformId);
			return false;
		} */

		/*if(empty($gamePlatformId)){
			$this->getValidPlatformId();
		}*/
		
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		if(!$this->game_api){
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE ERROR: (initialize) Invalid Game API: ".$this->game_platform_id);	
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE ERROR: (initialize) GET Active Currency: ".$this->utils->getActiveCurrencyKeyOnMDB());	
			
			return false;
		}

		if(!$this->game_api->currency){
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE ERROR: (initialize) No Currency: ". $this->game_api->currency);	
			return false;
		}

        $this->show_response_message = $this->game_api->show_response_message;
        $this->show_hint = $this->game_api->show_hint;
        $this->currency = $this->game_api->currency;
		$this->vivogaming_transactions->tableName = $this->game_api->original_transactions_table;
        $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
        $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
        $this->failed_remote_params = [];

		return true;
	}

	public function Authenticate($gamePlatformId=null){
        $this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (Authenticate) gamePlatformId: " . $gamePlatformId);	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'Authenticate';
		$params = [];

		$balance = 0;

		try {        

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

            $params['token'] = $token = $this->input->get('token');
			$params['hash'] = $this->input->get('hash');
			$params['trans_time'] = $this->trans_time;

            if (!$this->ssa_is_game_api_active($this->game_api)) {
                throw new Exception(self::GAME_DISABLED['message'], self::GAME_DISABLED['code']);
            }

            if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                throw new Exception(self::GAME_MAINTENANCE['message'], self::GAME_MAINTENANCE['code']);
            }

            if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                throw new Exception(self::IP_ADDRESS_NOT_ALLOWED['message'], self::IP_ADDRESS_NOT_ALLOWED['code']);
            }

            if(!$token){
                throw new Exception(self::INVALID_TOKEN['message'], self::INVALID_TOKEN['code']);
            }            

            list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($token);

            if(!$player_status){
                throw new Exception(self::INVALID_TOKEN['message'], self::INVALID_TOKEN['code']);
			}	

			// validate hash
			$isValid = $this->isHashValid($params['hash'], [$params['token'],$this->game_api->hash_key]);
			if(!$isValid){		
                $externalResponse['RESPONSE']['HINT'] = $this->generated_hash;	
				throw new Exception(self::INVALID_HASH['message'], self::INVALID_HASH['code']);
			}		

			$player_id = $player->player_id;

			$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
				$player_name,				
				&$balance) {		
					
				$balance = $this->getPlayerBalance($player_name, $player_id);
				if($balance===false){
					return false;
				}
			
				return true;								
			});

			if($trans_success==false){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

			$externalResponse['RESPONSE']['RESULT'] = 'OK';			
			$externalResponse['RESPONSE']['USERID'] = $game_username;			
			$externalResponse['RESPONSE']['BALANCE'] = $this->formatBalance($balance);
			return $this->handleExternalResponse(true, $callType, $this->request, [], $externalResponse, self::SUCCESS['code'], self::SUCCESS['message']);		

		} catch (Exception $error) {
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: ($callType) CATCHED ERROR", $params, $this->request, $externalResponse);			
			$error_code = $error->getCode();
			$error_message = $error->getMessage();
			$externalResponse['RESPONSE']['RESULT'] = 'FAILED';						
			return $this->handleExternalResponse(false, $callType, $this->request, [], $externalResponse, $error_code, $error_message);
		}		
	}

	public function GetBalance($gamePlatformId=null){
        $this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (GetBalance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'GetBalance';
		$params = [];
		$balance = 0;

		try {        

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

            $params['user_id'] = $userId = $this->input->get('userId');
			$params['hash'] = $this->input->get('hash');
			$params['trans_time'] = $this->trans_time;

            /* if (!$this->ssa_is_game_api_active($this->game_api)) {
                throw new Exception(self::GAME_DISABLED['message'], self::GAME_DISABLED['code']);
            }

            if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                throw new Exception(self::GAME_MAINTENANCE);
            } */

            if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                throw new Exception(self::IP_ADDRESS_NOT_ALLOWED['message'], self::IP_ADDRESS_NOT_ALLOWED['code']);
            }

            if(!$userId){
                throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}        
			
			// validate hash
			$isValid = $this->isHashValid($params['hash'], [$params['user_id'],$this->game_api->hash_key]);
			if(!$isValid){			
                $externalResponse['RESPONSE']['HINT'] = $this->generated_hash;
				throw new Exception(self::INVALID_HASH['message'], self::INVALID_HASH['code']);
			}    

            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($userId);

            if(!$player_status){
                throw new Exception(self::UNKNOWN_USERID['message'], self::UNKNOWN_USERID['code']);
			}		

			$player_id = $player->player_id;

			$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$player_username,				
				&$balance) {		
					
				$balance = $this->getPlayerBalance($player_username, $player_id);
				if($balance===false){
					return false;
				}
			
				return true;								
			});

			if($trans_success==false){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

			$externalResponse['RESPONSE']['RESULT'] = 'OK';			
			$externalResponse['RESPONSE']['BALANCE'] = $this->formatBalance($balance);
			return $this->handleExternalResponse(true, $callType, $this->request, [], $externalResponse, self::SUCCESS['code'], self::SUCCESS['message']);		

		} catch (Exception $error) {
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: ($callType) CATCHED ERROR", $params, $this->request, $externalResponse);			
			$error_code = $error->getCode();
			$error_message = $error->getMessage();
			$externalResponse['RESPONSE']['RESULT'] = 'FAILED';						
			return $this->handleExternalResponse(false, $callType, $this->request, [], $externalResponse, $error_code, $error_message);
		}		
	}

	public function isParametersValid($data){
		return true;
	}

	public function ChangeBalance($gamePlatformId=null){	
        $this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (ChangeBalance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'ChangeBalance';

		$previous_balance = $after_balance = $balance = 0;
		$insufficient_balance = $isAlreadyExists = false;
		
		try {        

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

			$params['user_id'] = $userId= $this->input->get('userId');
			$params['amount'] = $this->input->get('Amount');
			$params['trans_id'] = $this->input->get('TransactionID');
			$params['trans_type'] = $this->input->get('TrnType');
			$params['trans_desc'] = $this->input->get('TrnDescription');
			$params['round_id'] = $this->input->get('roundId');
			$params['game_id'] = $this->input->get('gameId');
			$params['history'] = $this->input->get('History');
			$params['is_round_finished'] = $this->input->get('isRoundFinished');
			$params['hash'] = $this->input->get('hash');
			$params['session_id'] = $this->input->get('sessionId');
			$params['trans_time'] = $this->trans_time;			

            if ($params['trans_type'] == 'BET') {
                if (!$this->ssa_is_game_api_active($this->game_api)) {
                    throw new Exception(self::GAME_DISABLED['message'], self::GAME_DISABLED['code']);
                }

                if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                    throw new Exception(self::GAME_MAINTENANCE['message'], self::GAME_MAINTENANCE['code']);
                }
            }

            if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                throw new Exception(self::IP_ADDRESS_NOT_ALLOWED['message'], self::IP_ADDRESS_NOT_ALLOWED['code']);
            }

			// check parameters
            if(!$this->isParametersValid($this->request)){
                throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
            }            

			// get player info
            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($userId);

			// check player info
            if(!$player_status){
                throw new Exception(self::UNKNOWN_USERID['message'], self::UNKNOWN_USERID['code']);
			}        
			
			// validate hash
			$isValid = $this->isHashValid($params['hash'], [
				$params['user_id'], 
				$params['amount'], 
				$params['trans_type'],
				$params['trans_desc'],
				$params['round_id'],
				$params['game_id'],
				$params['history'],
				$this->game_api->hash_key
				]);
			if(!$isValid){			
                $externalResponse['RESPONSE']['HINT'] = $this->generated_hash;
				throw new Exception(self::INVALID_HASH['message'], self::INVALID_HASH['code']);
			}
			
			$mode = null;
			if($params['trans_type']=='BET'){
				$mode = 'credit';
			}elseif($params['trans_type']=='WIN'){
				$mode = 'debit';
			}elseif($params['trans_type']=='CANCELED_BET'){//refund
				$mode = 'debit';
			}else{
				throw new Exception(self::INVALID_TRANSACTION_TYPE['message'], self::INVALID_TRANSACTION_TYPE['code']);
			}

			$player_id = $player->player_id;
			$params['player_id'] = $player_id;
			$params['player_name'] = $player_username;
			$params['external_uniqueid'] = $this->external_uniqueid = $this->utils->mergeArrayValues([$params['trans_type'], $params['trans_id'], $params['round_id'], $params['player_id']]);

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player, 
				$mode,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);

				return $trans_success;
			});

			if(!empty($this->failed_remote_params)){
				$this->save_remote_wallet_failed_transaction($this->ssa_insert, $this->failed_remote_params);
			}

            if (isset($additionalResponse['message'])) {
                if ($additionalResponse['message'] == self::BET_NOT_EXISTS['message']) {
                    throw new Exception(self::BET_NOT_EXISTS['message'], self::BET_NOT_EXISTS['code']);
                }
    
                if ($additionalResponse['message'] == self::REFUND_EXISTS['message']) {
                    throw new Exception(self::REFUND_EXISTS['message'], self::REFUND_EXISTS['code']);
                }

                if ($additionalResponse['message'] == self::WIN_EXISTS['message']) {
                    throw new Exception(self::WIN_EXISTS['message'], self::WIN_EXISTS['code']);
                }
            }

			// insufficient balance
			if($insufficient_balance){						
				throw new Exception(self::INSUFFICIENT_FUNDS['message'], self::INSUFFICIENT_FUNDS['code']);
			}

			if($trans_success==false){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

            // should return balance without changes
			/* if($isAlreadyExists){				
				throw new Exception(self::TRANSACTION_ID_ALREADY_PROCESSED['message'], self::TRANSACTION_ID_ALREADY_PROCESSED['code']);
			} */

			$externalResponse['RESPONSE']['RESULT'] = 'OK';			
			$externalResponse['RESPONSE']['BALANCE'] = $this->formatBalance($after_balance);
			$externalResponse['RESPONSE']['ECSYSTEMTRANSACTIONID'] = $params['trans_id'];
			return $this->handleExternalResponse(true, $callType, $this->request, [], $externalResponse);		

		} catch (Exception $error) {
			$error_code = $error->getCode();
			$error_message = $error->getMessage();
			$externalResponse['RESPONSE']['RESULT'] = 'FAILED';						
			return $this->handleExternalResponse(false, $callType, $this->request, [], $externalResponse, $error_code, $error_message);
		}		
	}

	public function Status($gamePlatformId=null){
        $this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (GetBalance)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = 'GetBalance';

		$balance = 0;

		try {        

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}

            $params['user_id'] = $userId = $this->input->get('userId');
			$params['trans_id'] = $this->input->get('casinoTransactionId');
			$params['hash'] = $this->input->get('hash');
			$params['trans_time'] = $this->trans_time;

            if(!$userId){
                throw new Exception(self::INTERNAL_ERROR['message'], self::INTERNAL_ERROR['code']);
			}        
			
			// validate hash
			$isValid = $this->isHashValid($params['hash'], [$params['user_id'],$params['trans_id'],$this->game_api->hash_key]);
			if(!$isValid){			
                $externalResponse['RESPONSE']['HINT'] = $this->generated_hash;
				throw new Exception(self::INVALID_HASH['message'], self::INVALID_HASH['code']);
			}    

            list($player_status, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($userId);

            if(!$player_status){
                throw new Exception(self::UNKNOWN_USERID['message'], self::UNKNOWN_USERID['code']);
			}	

			$result = $this->CI->vivogaming_transactions->isTransactionExist($params['trans_id']);
			if(!$result){

                if ($this->use_monthly_transactions_table) {
                    if ($this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        $result = $this->CI->vivogaming_transactions->isTransactionExist($params['trans_id'], $this->game_api->ymt_get_previous_year_month_table());
                    }
                    
                    if (!$result) {
                        throw new Exception(self::UNKNOWN_TRANSACTION_ID['message'], self::UNKNOWN_TRANSACTION_ID['code']);
                    }
                } else {
                    throw new Exception(self::UNKNOWN_TRANSACTION_ID['message'], self::UNKNOWN_TRANSACTION_ID['code']);
                }
			}		

			$externalResponse['RESPONSE']['RESULT'] = 'OK';			
			$externalResponse['RESPONSE']['ECSYSTEMTRANSACTIONID'] = $params['trans_id'];
			return $this->handleExternalResponse(true, $callType, $this->request, [], $externalResponse, self::SUCCESS['code'], self::SUCCESS['message']);		

		} catch (Exception $error) {
			$error_code = $error->getCode();
			$error_message = $error->getMessage();
			$externalResponse['RESPONSE']['RESULT'] = 'FAILED';					
			return $this->handleExternalResponse(false, $callType, $this->request, [], $externalResponse, $error_code, $error_message);
		}		
	}

	public function isHashValid($hash, $data){		
		$this->generated_hash = $generatedHash = $this->generateHash($data);

		if($hash==$generatedHash){
			return true;
		}
		$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: (isHashValid)", $data, $hash, $generatedHash);
		return false;
	}

	public function generateHash($data){
		$hash = implode('', $data);
		return md5($hash);
	}

	//default external response template
	public function externalQueryResponse(){
		return array(
			"REQUEST" => [],
			"TIME" => date('D M Y H:i:s'),
			"RESPONSE" => array(
				"RESULT" => 'FAILED',				
            ),
		);
	}

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode){
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		if(is_array($response)){
			$response = json_encode($response);
		}
		if(is_array($params)){
			$params = json_encode($params);
		}
        return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id, 
        	$flag, 
        	$callMethod, 
        	$params, 
        	$response, 
        	$httpStatusCode, 
        	null, 
			is_array($this->headers)?json_encode($this->headers):$this->headers
        );
	}
	
	public function handleExternalResponse($status, $type, $request, $extra, $externalResponse, $error_code=null, $response_message = null){
		$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (handleExternalResponse)", $extra);

		if(!is_null($error_code) && $error_code != 0){
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE ERROR ($type) :", $request, $error_code, $externalResponse);		
		}

		$request = is_array($request) ? $request : array($request);
		foreach($request as $key => $value){
			if(!empty($value)){
				$_key = strtoupper($key);
				$externalResponse['REQUEST'][$_key] = $value;
			}
		}

		foreach($extra as $key => $value){
			if(!empty($value)){
				$_key = strtoupper($key);
				$externalResponse['RESPONSE'][$_key] = $value;
			}
		}

        $hint = isset($externalResponse['RESPONSE']['HINT']) ? $externalResponse['RESPONSE']['HINT'] : null;

		if($externalResponse['RESPONSE']['RESULT']=='OK'){
			$httpStatusCode = 200;
			$externalResponse['RESPONSE']['CURRENCY'] = strtoupper($this->currency);
		}else{			
			$httpStatusCode = 400;
			$externalResponse['RESPONSE'] = [];
			$externalResponse['RESPONSE']['RESULT'] = 'FAILED';
			$externalResponse['RESPONSE']['CODE'] = $this->getErrorCode($error_code);

            if ($this->show_response_message && !empty($response_message)) {
                if ($response_message == self::IP_ADDRESS_NOT_ALLOWED['message']) {
                    $response_message .= " ({$this->ssa_get_ip_address()})";
                }

                $externalResponse['RESPONSE']['MESSAGE'] = $response_message;
            }

            if ($this->show_hint && !empty($hint)) {
                $externalResponse['RESPONSE']['HINT'] = $hint;
            }
		}

		$externalResponse['TIME'] = date('d M Y H:i:s', strtotime($this->trans_time));
		
		$this->response_result_id = $this->saveResponseResult($status, $type, $request, $externalResponse, $httpStatusCode);
        
        if ($this->response_result_id) {
            $data = [
                'response_result_id' => $this->response_result_id,
            ];

            $this->ssa_update_transaction_without_result($this->vivogaming_transactions->tableName, $data, 'external_uniqueid', $this->external_uniqueid);
        }

		$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><VGSSYSTEM></VGSSYSTEM>");
		$xmlResponseData = $this->CI->utils->arrayToXml($externalResponse, $xml_object);

		return $this->returnXml($xmlResponseData);
	}

	public function getPlayerByToken($token){
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsername($username){
		$player = $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

    public function getPlayerByGameUsername($game_username){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->game_platform_id);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);		
		$result = $this->CI->vivogaming_transactions->insertIgnoreRow($trans_record);		
		return $result;
	}

	public function generateUniqueTransactionId($data){
		$generatedUniqueId = $data['trans_id'];
		return $generatedUniqueId;
	}

	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['game_platform_id'] 	= $this->game_platform_id;
		$data['trans_id'] 			= isset($raw_data['trans_id'])?$raw_data['trans_id']:null;
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;
		$data['trans_desc'] 		= isset($raw_data['trans_desc'])?$raw_data['trans_desc']:null;
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;
		$data['game_id'] 			= isset($raw_data['game_id'])?$raw_data['game_id']:null;
		$data['history'] 			= isset($raw_data['history'])?$raw_data['history']:null;
		$data['is_round_finished']	= isset($raw_data['is_round_finished'])?$raw_data['is_round_finished']:null;
		$data['hash'] 				= isset($raw_data['hash'])?$raw_data['hash']:null;
		$data['session_id'] 		= isset($raw_data['session_id'])?$raw_data['session_id']:null;
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;		
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;
		$data['status'] 			= 0;
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;		
		$data['raw_data'] 			= @json_encode($this->request);
		$data['trans_time'] 		= isset($raw_data['trans_time'])?$raw_data['trans_time']:null;
		$data['external_uniqueid'] 	= isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : null;
		$data['currency'] 			= $this->currency;
		$data['seamless_service_unique_id'] = $this->seamless_service_unique_id;
		$data['external_game_id'] = isset($raw_data['game_id']) ? $raw_data['game_id'] : null;
		return $data;
	}

	public function getErrorCode($code){
		return $code;
	}

	public function parseRequest(){				
        //$request_json = file_get_contents('php://input');        
		//$this->request = json_decode($request_json, true);
		$this->request = $this->input->get();
		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = VIVOGAMING_SEAMLESS_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('vivogaming_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function formatBalance($balance){
		return $balance;
	}

	public function isValidAmount($amount){
		return is_numeric($amount);
	}

	public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance){
		$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);

		//initialize params
		$player_id			= $params['player_id'];				
		$amount 			= abs($params['amount']);
		
		//initialize response
		$success = false;
		$isValidAmount = true;		
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];

        //get and process balance
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
			}else{				
				$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request, $this->params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

        if (isset($params['external_uniqueid'])) {
            $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $params['external_uniqueid']]);
            $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);

			#set action type here.
			if($params['trans_type']=='WIN'){
				$action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
				$is_end = true;
			}elseif($params['trans_type']=='CANCELED_BET'){//refund
				$action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
				$is_end = true;
			}else{
				$action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
				$is_end = false;
			}
			$this->wallet_model->setGameProviderActionType($action_type); 
			$this->ssa_set_game_provider_round_id($params['round_id']);
			$this->ssa_set_game_provider_is_end_round($is_end);
			
            $transaction_already_exists = $this->ssa_is_transaction_exists($this->vivogaming_transactions->tableName, ['external_uniqueid' => $params['external_uniqueid']]);
            if ($transaction_already_exists) {
                return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }

            // check transaction_already_exists in previous table
            if ($this->use_monthly_transactions_table) {
                if ($this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {

                    if (!$transaction_already_exists) {
                        $transaction_already_exists = $this->ssa_is_transaction_exists($this->game_api->ymt_get_previous_year_month_table(), ['external_uniqueid' => $params['external_uniqueid']]);
                        if ($transaction_already_exists) {
                            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                        }
                    }
                }
            }

            if ($params['trans_type'] == 'WIN' || $params['trans_type'] == 'CANCELED_BET') {
                $where_bet_exists = [
                    'trans_type' => 'BET',
                    'round_id' => $params['round_id'],
                    'player_id' => $player_id,
                ];

                $is_bet_exists = $this->ssa_is_transaction_exists($this->vivogaming_transactions->tableName, $where_bet_exists);
    
                if (!$is_bet_exists) {
                    // check is_bet_exists in previous table
                    if ($this->use_monthly_transactions_table) {
                        if ($this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            $is_bet_exists = $this->ssa_is_transaction_exists($this->game_api->ymt_get_previous_year_month_table(), $where_bet_exists);
                
                            if (!$is_bet_exists) {
                                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::BET_NOT_EXISTS, $isTransactionAdded);
                            }
                        }else{
							return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::BET_NOT_EXISTS, $isTransactionAdded);
						}
                    } else {
                        return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::BET_NOT_EXISTS, $isTransactionAdded);
                    }
                }
            }

            if ($params['trans_type'] == 'WIN') {
                $where_refund_exists = [
                    'trans_type' => 'CANCELED_BET',
                    'round_id' => $params['round_id'],
                    'player_id' => $player_id,
                ];

                $is_refund_exists = $this->ssa_is_transaction_exists($this->vivogaming_transactions->tableName, $where_refund_exists);
    
                if ($is_refund_exists) {
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::REFUND_EXISTS, $isTransactionAdded);
                } else {
                    // check is_refund_exists in previous table
                    if ($this->use_monthly_transactions_table) {
                        if ($this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            $is_refund_exists = $this->ssa_is_transaction_exists($this->game_api->ymt_get_previous_year_month_table(), $where_refund_exists);
                
                            if ($is_refund_exists) {
                                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::REFUND_EXISTS, $isTransactionAdded);
                            }
                        }
                    }
                }
            }

            if ($params['trans_type'] == 'CANCELED_BET') {
                $where_refund_exists = [
                    'trans_type' => $params['trans_type'],
                    'round_id' => $params['round_id'],
                    'player_id' => $player_id,
                ];

                $is_refund_exists = $this->ssa_is_transaction_exists($this->vivogaming_transactions->tableName, $where_refund_exists);

                if ($is_refund_exists) {
                    return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                } else {
                    // check is_refund_exists in previous table
                    if ($this->use_monthly_transactions_table) {
                        if ($this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            $is_refund_exists = $this->ssa_is_transaction_exists($this->game_api->ymt_get_previous_year_month_table(), $where_refund_exists);
                
                            if ($is_refund_exists) {
                                return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                            }
                        }
                    }
                }

                $where_win_exists = [
                    'trans_type' => 'WIN',
                    'round_id' => $params['round_id'],
                    'player_id' => $player_id,
                ];

                $is_win_exists = $this->ssa_is_transaction_exists($this->vivogaming_transactions->tableName, $where_win_exists);
    
                if ($is_win_exists) {
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::WIN_EXISTS, $isTransactionAdded);
                } else {
                    // check is_win_exists in previous table
                    if ($this->use_monthly_transactions_table) {
                        if ($this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            $is_win_exists = $this->ssa_is_transaction_exists($this->game_api->ymt_get_previous_year_month_table(), $where_win_exists);
                
                            if ($is_win_exists) {
                                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, self::WIN_EXISTS, $isTransactionAdded);
                            }
                        }
                    }
                }
            }
        }

        if (isset($params['game_id'])) {
            $this->ssa_set_external_game_id($params['game_id']);
        }

		if($params['trans_type']=='BET'){
			$mode = 'debit';
		}elseif($params['trans_type']=='WIN'){
			$mode = 'credit';
		}elseif($params['trans_type']=='CANCELED_BET'){//refund
			$mode = 'credit';
			$flagrefunded = true;
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		$isValidAmount 		= $this->isValidAmount($params['amount']);

		if(!$isValidAmount){
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		#added this condition ($params['trans_type'] == 'WIN' && $amount >= 0), need to call incSubWallet on 0 amount
		if($amount<>0 || ($params['trans_type'] == 'WIN' && $amount >= 0)){

			if(!$isValidAmount){
				$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) isValidAmount", $isValidAmount);
				$success = false;
				$isValidAmount = false;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}	

			if($mode=='debit'){
                $afterBalance = $afterBalance - $amount;
            }else{
                $afterBalance = $afterBalance + $amount;
            }

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request, $this->params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}				
			
			$success = $this->transferGameWallet($player_id, $this->game_platform_id, $mode, $amount);
			if(!$success){
                if ($this->ssa_enabled_remote_wallet()) {
                	$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE FAILED REMOTE: (debitCreditAmountToWallet)", $params);
                	$this->failed_remote_params = $params;
                	// $this->save_remote_wallet_failed_transaction($this->ssa_insert, $params);
                }
			}

			if(!$success){
				$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request, $this->params);
			}

		}else{
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;
			}else{
				$success = false;
			}

			//insert transaction
			$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded);
	}

	 private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
	 	$use_monthly = true;
        $save_data = $md5_data = [
            'transaction_id' => !empty($data['trans_id']) ? $data['trans_id'] : null,
            'round_id' => !empty($data['round_id']) ? $data['round_id'] : null,
            'external_game_id' => !empty($data['game_id']) ? $data['game_id'] : null,
            'player_id' => !empty($data['player_id']) ? $data['player_id'] : null,
            'game_username' => null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['trans_type']) && strtolower($data['trans_type']) == "bet" ? $this->ssa_decrease : $this->ssa_increase,
            'action' => !empty($data['trans_type']) ? $data['trans_type'] : null,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->request),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->ssa_get_remote_wallet_error_code(),
            'transaction_date' => $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => !empty($data['external_uniqueid']) ? $data['external_uniqueid'] : null,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($use_monthly) {
            $year_month = $this->utils->getThisYearMonth();
            $table_name = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$year_month}";
        } else {
            $table_name = $this->ssa_failed_remote_common_seamless_transactions_table;
        }

        if ($this->ssa_is_transaction_exists($table_name, ['external_uniqueid' => $save_data['external_uniqueid']])) {
            $query_type = $this->ssa_update;

            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $save_data['external_uniqueid'],
                ];
            }
        }
        $this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE FAILED REMOTE: (table)", $table_name);
        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $use_monthly);
    }

	public function getPlayerBalance($playerName, $player_id){	
		//not using querySeamlessSingleWallet this function is for seamless wallet only applicable in GW	
		$get_bal_req = $this->game_api->queryPlayerBalance($playerName);
		$this->utils->debug_log("VIVOGAMING SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}	
	}
	
	protected function validateCurrencyAndSwitchDB(){
		$currency = strtolower($this->currency);
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($currency)){
			$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: (validateCurrencyAndSwitchDB) Empty currency name: " . $currency);
            return false;
        }else{
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($currency)){
				//invalid currency name
				$this->utils->error_log("VIVOGAMING SEAMLESS SERVICE: (validateCurrencyAndSwitchDB) Invalid currency name: " . $currency);
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($currency);
                return true;
            }
        }
    }

	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount){
		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
			$success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);	
		}elseif($mode=='credit'){
			$success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
		}

		if ($this->utils->isEnabledRemoteWalletClient()) {
			$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
			if(!$success && $remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
				$this->utils->debug_log("VIVO SEAMLESS SERVICE: (incRemoteWallet) treated as success remoteErrorCode: " , $remoteErrorCode);
				$success = true;
			}
		}

		return $success;
	}

}///END OF FILE////////////