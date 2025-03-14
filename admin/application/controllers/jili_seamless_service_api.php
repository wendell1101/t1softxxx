<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * http://admin.brl.staging.smash.t1t.in/jili_seamless_service_api/[API ID]/[METHOD]
 */
class Jili_seamless_service_api extends BaseController {	
	
    //success codes
    const SUCCESS = '0x0';
    
    //error codes	
    const ERROR_ALREADY_ACCEPTED = '0x01';
    const ERROR_ALREADY_CANCELLED = '0x02';
    const ERROR_INSUFFICIENT_BALANCE = '0x03';
	const ERROR_INVALID_PARAMETERS = '0x04';
	const ERROR_BET_DONT_EXIST = '0x05';
    const ERROR_TOKEN_EXPIRED = '0x06';
    const ERROR_CANNOT_FIND_PLAYER = '0x07';
    const ERROR_UNKNOWN = '0x08';
    const ERROR_SERVER = '0x09';
    const ERROR_GAME_MAINTENANCE = '0x10';
    const ERROR_IP_NOT_ALLOWED = '0x11';
    const ERROR_CANCEL_REFUSED = '0x12';
    const ERROR_ROUND_NOT_FOUND = '0x13';
	const ERROR_CONNECTION_TIMED_OUT = '0x14';
    
	
	const ERROR_CODES = [
		self::ERROR_ALREADY_ACCEPTED,
		self::ERROR_INSUFFICIENT_BALANCE,
		self::ERROR_INVALID_PARAMETERS,
		self::ERROR_BET_DONT_EXIST,
		self::ERROR_TOKEN_EXPIRED,
		self::ERROR_UNKNOWN,
		self::ERROR_SERVER,
		self::ERROR_IP_NOT_ALLOWED,
		self::ERROR_GAME_MAINTENANCE,
		self::ERROR_CANCEL_REFUSED,
		self::ERROR_ROUND_NOT_FOUND,
		self::ERROR_ALREADY_CANCELLED,
		self::ERROR_CONNECTION_TIMED_OUT,
	];
	
	/*const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_ALREADY_ACCEPTED=>409,
		self::ERROR_BET_DONT_EXIST=>409,
		self::ERROR_INSUFFICIENT_BALANCE=>406,
		self::ERROR_TOKEN_EXPIRED=>406,
		self::ERROR_UNKNOWN=>500,
		self::ERROR_SERVER=>500,
		self::ERROR_GAME_MAINTENANCE=>500,
		self::ERROR_IP_NOT_ALLOWED=>401,
		self::ERROR_CANCEL_REFUSED=>409,
		self::ERROR_ROUND_NOT_FOUND=>409,
		self::ERROR_ALREADY_CANCELLED=>409,
	];*/
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_ALREADY_ACCEPTED=>200,
		self::ERROR_BET_DONT_EXIST=>200,
		self::ERROR_INSUFFICIENT_BALANCE=>200,
		self::ERROR_TOKEN_EXPIRED=>200,
		self::ERROR_UNKNOWN=>500,
		self::ERROR_SERVER=>500,
		self::ERROR_GAME_MAINTENANCE=>500,
		self::ERROR_IP_NOT_ALLOWED=>401,
		self::ERROR_CANCEL_REFUSED=>200,
		self::ERROR_ROUND_NOT_FOUND=>200,
		self::ERROR_ALREADY_CANCELLED=>200,
		self::ERROR_CONNECTION_TIMED_OUT=>500,
	];

    const METHOD_AUTH = 'auth';
    const METHOD_BET = 'bet';
    const METHOD_CANCEL_BET = 'cancelBet';
    const METHOD_SESSION_BET = 'sessionBet';
	const METHOD_CANCEL_SESSION_BET = 'cancelSessionBet';

	const METHOD_SESSION_BETTYPE_BET = 1;
	const METHOD_SESSION_BETTYPE_PAYOUT = 2;
	
	public $game_api;
	public $game_platform_id;
	public $currency;
	public $player_id;
	public $request;

	public $start_time;
	public $end_time;
	public $game_provider_bet_amount = 0;
	public $game_provider_payout_amount = 0;
	public $seamless_service_related_unique_id = null;
	private $response_result_id;
	private $after_balance;
	private $remote_wallet_error_code = null;
	private $params_to_insert_incase_failed = null;

	public function __construct() {
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','jili_seamless_transactions', 'ip'));
		
		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("JILI SEAMLESS SERVICE: (__construct)", 'request', $this->request, 'REQUEST_URI', $_SERVER['REQUEST_URI']);
		
	}

	public function initialize($gamePlatformId){
		
		$this->utils->debug_log("JILI SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		// if(!$this->isIPAllowed()){
		// 	$this->utils->debug_log("JILI Seamless IP Not ALOOOWED" . $gamePlatformId);
		// } 

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }
        
        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
	
        if(!$this->game_api){
			$this->utils->debug_log("JILI SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }
        
        $this->currency = $this->game_api->getCurrency();
		//$this->utils->debug_log("JILI SEAMLESS SERVICE: (initialize) ERROR currency: ", $this->currency);
        $this->jili_seamless_transactions->tableName = $this->game_api->original_transactions_table;

		return true;
	}

	public function auth($gamePlatformId=null){
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (auth)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = self::METHOD_AUTH;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$token = $this->request['token'];
		$rules = [
			'reqId'=>'required',
			'token'=>'required',
		];

		try {     

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVER);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }
			
			if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} 
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}   
			
			if(!empty($this->game_api->trigger_auth_error_response)){
				throw new Exception($this->game_api->trigger_auth_error_response);
			}   

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus){                
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}		

			$player_id = $player->player_id;

			// $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			// $player_username,				
			// 	&$balance) {		
					
			// 	$balance = $this->getPlayerBalance($player_username, $player_id);
			// 	if($balance===false){
			// 		return false;
			// 	}
			
			// 	return true;								
			// });
			// 
			$success = true;
			$balance = $this->getPlayerBalance($player_id);
			if($balance===false){
				$success =  false;
			}

			if(!$success){
				throw new Exception(self::ERROR_SERVER);
			}
			// $success = true;
			$errorCode = self::SUCCESS;
			$currency = $this->currency;
			
		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}	
		
		//$externalResponse['message'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;					
		$externalResponse['currency'] = $currency;
        $externalResponse['balance'] = $this->formatBalance($balance);	
        $externalResponse['token'] = $token;	
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	public function bet($gamePlatformId=null){	
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (bet)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = self::METHOD_BET;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$token=null;
		$txId = null;
		$rules = [
			'reqId'=>'required',
            'token'=>'required',
            'currency'=>'required',
            'game'=>'required',
            'game'=>'isNumeric',
            'round'=>'required',
            //'round'=>'isNumeric',
            'wagersTime'=>'required',
            'wagersTime'=>'isNumeric',
            'betAmount'=>'required',
            'betAmount'=>'isNumeric',
            'winloseAmount'=>'required',
            'winloseAmount'=>'isNumeric',
		];
		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVER);
			} 

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

			$currency = $this->currency;
			
			if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}   
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!empty($this->game_api->trigger_bet_error_response)){
				throw new Exception($this->game_api->trigger_bet_error_response);
			}

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

			//try userid token might be expired
            if(!$playerStatus && isset($this->request['userId'])){                
                $userId = $this->request['userId'];
            	list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($userId);
			}


			if(!$playerStatus){                
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

			$player_id = $player->player_id;
            $params['token'] = $this->request['token'];
			$params['user_id'] = isset($this->request['userId'])?$this->request['userId']:$gameUsername;            
            $params['player_id'] = $player_id;
            $params['req_id'] = $this->request['reqId'];
            $params['currency'] = $this->request['currency'];
            $params['game'] = $this->request['game'];

            $params['round'] = (string)$this->request['round'];
            $params['wagers_time'] = $this->request['wagersTime'];			
            $params['bet_amount'] = $this->game_provider_bet_amount = $this->request['betAmount'];
            $params['winlose_amount'] = $this->game_provider_payout_amount = $this->request['winloseAmount'];
            $params['jp_contribute'] = isset($this->request['jpContribute'])?$this->request['jpContribute']:0;
            $params['jp_win'] = isset($this->request['jpWin'])?$this->request['jpWin']:0;            
			$params['external_uniqueid'] = $this->formatExternalUniqueId(self::METHOD_BET, $params['round'], $player_id);
            $params['is_free_round'] = isset($this->request['isFreeRound'])&&$this->request['isFreeRound']==true?BaseModel::DB_FALSE:BaseModel::DB_FALSE;
            $params['trans_type'] = $callType;
			$params['preserve'] = 0;

			$params['player_name'] = $player_username;

			$isEnd = true;
			$actionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse,
				$isEnd,
				$actionType) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $actionType, $isEnd);
				$this->utils->debug_log("JILI SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded,
				'isEnd',$isEnd,
				'actionType',$actionType);	
				$this->after_balance = $after_balance;
				return $trans_success;
			});

			if(!$trans_success){
				if(!is_null($this->params_to_insert_incase_failed)){
					$params['remote_wallet_status'] = $this->remote_wallet_error_code;
					$params = $this->params_to_insert_incase_failed['params'];
					$previousBalance = $this->params_to_insert_incase_failed['previous_balance'];
					$afterBalance = $this->params_to_insert_incase_failed['after_balance'];
					$flagRefunded = $this->params_to_insert_incase_failed['flag_refunded'];
					$params['external_uniqueid'] = "failed-".$params['external_uniqueid'];
					$params['is_failed'] = 1;
					$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagRefunded);
					if(!$isAdded){
						$this->utils->debug_log("JILI @bet - failed to insert failed transaction");
					}
					$this->utils->debug_log("JILI @bet - successfully insert failed transaction");
				}
			}


			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_ALREADY_ACCEPTED);// to ask if what to return if trans already exist
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$check_trans_params = ['external_uniqueid'=>$this->trans_record['external_uniqueid']];
			$trans = $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_trans_params);
			$txId = isset($trans['id'])?$trans['id']:null;

			$success = true;
			$errorCode = self::SUCCESS;

            $balance = $this->after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}	
		
		//$externalResponse['message'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;					
		$externalResponse['currency'] = $currency;
        $externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['txId'] = $txId;
        $externalResponse['token'] = $token;
		$fields = [
			'player_id'		=> $player_id,
		];
		
		$this->utils->debug_log("JILI @bet external response: ", $externalResponse);

		if($this->game_api->enable_mock_cancel_bet && in_array($params['player_name'], $this->game_api->enable_mock_cancel_player_list)){
			$this->utils->debug_log("JILI @bet -  triggered force cancel on bet request");
			http_response_code(502); # return 505 bad gateway error to trigger cancel
			$errorCode = 502;
		}
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	public function cancelBet($gamePlatformId=null){	
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (cancelBet)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = self::METHOD_CANCEL_BET;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$currency = null;
		$previous_balance = $after_balance = 0;
		$cancelBetRefused = $betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = $alreadyCancelled = false;
		$txId = null;
		$additionalResponse = [];
		$rules = [
			'reqId'=>'required',
            'token'=>'required',
            'currency'=>'required',
            'game'=>'required',
            'game'=>'isNumeric',
            'round'=>'required',
            //'round'=>'isNumeric',            
            'betAmount'=>'required',
            'betAmount'=>'isNumeric',
            'winloseAmount'=>'required',
            'winloseAmount'=>'isNumeric',
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVER);
			}   

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }
			
			$currency = $this->currency;
			
			if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}  
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!empty($this->game_api->trigger_cancelbet_error_response)){
				throw new Exception($this->game_api->trigger_cancelbet_error_response);
			}

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

			//try userid token might be expired
            if(!$playerStatus && isset($this->request['userId'])){                
                $userId = $this->request['userId'];
            	list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($userId);
			}

			if(!$playerStatus){                
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}
            
			$player_id = $player->player_id;
            $params['token'] = $this->request['token'];
			$params['user_id'] = isset($this->request['userId'])?$this->request['userId']:$gameUsername; 
            $params['player_id'] = $player_id;
            $params['req_id'] = $this->request['reqId'];
            $params['currency'] = $this->request['currency'];
            $params['game'] = $this->request['game'];
            $params['round'] = (string)$this->request['round'];
            $params['wagers_time'] = time()*1000;			
            $params['bet_amount'] = $this->request['betAmount'];
            $params['winlose_amount'] = $this->request['winloseAmount'];
			$params['external_uniqueid'] = $this->formatExternalUniqueId(self::METHOD_CANCEL_BET, $params['round'], $player_id);
            $params['trans_type'] = $callType;

			$params['player_name'] = $player_username;

			$isEnd = true;
			$actionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse,
				&$betExist,
				&$cancelBetRefused,
				$isEnd,
				$actionType) {
				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $actionType, $isEnd, Wallet_model::REMOTE_RELATED_ACTION_BET_PAYOUT);
				$this->utils->debug_log("JILI SEAMLESS SERVICE lockAndTransForPlayerBalance cancelBet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				if(isset($additionalResponse['betExist'])){
					$betExist=$additionalResponse['betExist'];	
				}
				if(isset($additionalResponse['cancelBetRefused'])){
					$cancelBetRefused=$additionalResponse['cancelBetRefused'];	
				}
				$this->after_balance = $after_balance;
				return $trans_success;
			});
			$alreadyCancelled = isset($additionalResponse['alreadyCancelled']) ? $additionalResponse['alreadyCancelled'] : false;
			if(!$trans_success){
				if(!is_null($this->params_to_insert_incase_failed)){
					$params['remote_wallet_status'] = $this->remote_wallet_error_code;
					$params = $this->params_to_insert_incase_failed['params'];
					$previousBalance = $this->params_to_insert_incase_failed['previous_balance'];
					$afterBalance = $this->params_to_insert_incase_failed['after_balance'];
					$flagRefunded = $this->params_to_insert_incase_failed['flag_refunded'];
					$params['external_uniqueid'] = "failed-".$params['external_uniqueid'];
					$params['is_failed'] = 1;
					$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagRefunded);
					if(!$isAdded){
						$this->utils->debug_log("JILI @cancelBet - failed to insert failed transaction");
					}
					$this->utils->debug_log("JILI @cancelBet - successfully insert failed transaction");
				}
			}
			if($isAlreadyExists){				
				throw new Exception(self::ERROR_ALREADY_CANCELLED);
			}

			if($alreadyCancelled){				
				throw new Exception(self::ERROR_ALREADY_CANCELLED);
			}

			// $alreadySettled = isset($additionalResponse['alreadySettled']) ? $additionalResponse['alreadySettled'] : false;
			if($cancelBetRefused){
				throw new Exception(self::ERROR_CANCEL_REFUSED);
			}

			if(!$betExist){				
				throw new Exception(self::ERROR_ROUND_NOT_FOUND);
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$check_trans_params = ['external_uniqueid'=>$this->trans_record['external_uniqueid']];
			$trans = $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_trans_params);
			$txId = isset($trans['id'])?$trans['id']:null;

			$success = true;
			$errorCode = self::SUCCESS;
			$balance = $this->after_balance;
            
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}				

		//$externalResponse['message'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;					
		$externalResponse['currency'] = $currency;
        $externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['txId'] = $txId;
        $externalResponse['token'] = $token;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	public function sessionBet($gamePlatformId=null){	
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (sessionBet)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = self::METHOD_SESSION_BET;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$token=null;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$txId = null;
		$rules = [
			'reqId'=>'required',
            'token'=>'required',
            'currency'=>'required',
            'game'=>'required',
            'game'=>'isNumeric',
            'round'=>'required',
            //'round'=>'isNumeric',
            'wagersTime'=>'required',
            'wagersTime'=>'isNumeric',
            'betAmount'=>'required',
            'betAmount'=>'isNumeric',
            'winloseAmount'=>'required',
            'winloseAmount'=>'isNumeric',
            'sessionId'=>'required',
            'sessionId'=>'isNumeric',
            'type'=>'required',
            'type'=>'isNumeric',
		];
		
		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVER);
			} 

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

			$currency = $this->currency;
			
			if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}   
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!empty($this->game_api->trigger_sessionbet_error_response)){
				throw new Exception($this->game_api->trigger_sessionbet_error_response);
			}

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

			//try userid token might be expired
            if(!$playerStatus && isset($this->request['userId'])){                
                $userId = $this->request['userId'];
            	list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($userId);
			}

			if(!$playerStatus){                
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

			$player_id = $player->player_id;
            $params['token'] = $this->request['token'];
			$params['user_id'] = isset($this->request['userId'])?$this->request['userId']:null;            
            $params['player_id'] = $player_id;
            $params['req_id'] = $this->request['reqId'];
            $params['currency'] = $this->request['currency'];
            $params['game'] = $this->request['game'];
            $params['round'] = (string)$this->request['round'];
            $params['wagers_time'] = $this->request['wagersTime'];			
            $params['bet_amount'] = $this->game_provider_bet_amount = $this->request['betAmount'];
            $params['winlose_amount'] = $this->game_provider_payout_amount = $this->request['winloseAmount'];
            $params['jp_contribute'] = isset($this->request['jpContribute'])?$this->request['jpContribute']:0;
            $params['jp_win'] = isset($this->request['jpWin'])?$this->request['jpWin']:0;            
			$params['session_id'] = isset($this->request['sessionId'])?strval($this->request['sessionId']):null;            
			$params['type'] = isset($this->request['type'])?$this->request['type']:null;                       
			$params['turnover'] = isset($this->request['turnover'])?$this->request['turnover']:0;             
			$params['preserve'] = isset($this->request['preserve'])?$this->request['preserve']:0;            
			$params['external_uniqueid'] = $this->formatExternalUniqueId(self::METHOD_SESSION_BET, $params['round'], $player_id);
            $params['is_free_round'] = isset($this->request['isFreeRound'])&&$this->request['isFreeRound']==true?BaseModel::DB_FALSE:BaseModel::DB_FALSE;
            $params['trans_type'] = $callType;

			$params['player_name'] = $player_username;

			$isEnd = true;
			$actionType = Wallet_model::REMOTE_RELATED_ACTION_BET_PAYOUT;

			$session_type = isset($params['type'])?$params['type']:false;
			if($session_type==self::METHOD_SESSION_BETTYPE_BET){
				$isEnd = false;
				$actionType = Wallet_model::REMOTE_RELATED_ACTION_BET;
				$related_action_bet = null;
			}elseif($session_type==self::METHOD_SESSION_BETTYPE_PAYOUT){
				$isEnd = true;
				$actionType = Wallet_model::REMOTE_RELATED_ACTION_PAYOUT;
				$related_action_bet = Wallet_model::REMOTE_RELATED_ACTION_BET;
			}else{
				throw new Exception(self::ERROR_UNKNOWN);
			}

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				$related_action_bet,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse,
				$isEnd,
				$actionType) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $actionType, $isEnd, $related_action_bet);
				$this->utils->debug_log("JILI SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'additionalResponse',$additionalResponse,
				'isAlreadyExists',$isAlreadyExists,
				'isTransactionAdded',$isTransactionAdded);	
				$this->after_balance = $after_balance;
				return $trans_success;
			});

			if(!$trans_success){
				if(!is_null($this->params_to_insert_incase_failed)){
					$params['remote_wallet_status'] = $this->remote_wallet_error_code;
					$params = $this->params_to_insert_incase_failed['params'];
					$previousBalance = $this->params_to_insert_incase_failed['previous_balance'];
					$afterBalance = $this->params_to_insert_incase_failed['after_balance'];
					$flagRefunded = $this->params_to_insert_incase_failed['flag_refunded'];
					$params['external_uniqueid'] = "failed-".$params['external_uniqueid'];
					$params['is_failed'] = 1;
					$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagRefunded);
					if(!$isAdded){
						$this->utils->debug_log("JILI @sessionBet - failed to insert failed transaction");
					}
					$this->utils->debug_log("JILI @sessionBet - successfully insert failed transaction");
				}
			}
			

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_ALREADY_ACCEPTED);// to ask if what to return if trans already exist
			}

			$relatedBetNotExist = isset($additionalResponse['relatedBetNotExist']) ? $additionalResponse['relatedBetNotExist'] : false;
			if($relatedBetNotExist && $params['type'] == self::METHOD_SESSION_BETTYPE_PAYOUT){
				throw new Exception(self::ERROR_ROUND_NOT_FOUND);
			}

			$alreadyCancelled = isset($additionalResponse['alreadyCancelled']) ? $additionalResponse['alreadyCancelled'] : false;
			if($alreadyCancelled){				
				throw new Exception(self::ERROR_ALREADY_CANCELLED);
			}

			$alreadySettled = isset($additionalResponse['alreadySettled']) ? $additionalResponse['alreadySettled'] : false;
			if($alreadySettled){
				throw new Exception(self::ERROR_CANCEL_REFUSED);
			}

			

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}



			$check_trans_params = ['external_uniqueid'=>$this->trans_record['external_uniqueid']];
			$trans = $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_trans_params);
			$txId = isset($trans['id'])?$trans['id']:null;

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $this->after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}	
		
		//$externalResponse['message'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;					
		$externalResponse['currency'] = $currency;
        $externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['txId'] = $txId;
        $externalResponse['token'] = $token;
		$fields = [
			'player_id'		=> $player_id,
		];

		if($this->game_api->enable_mock_cancel_bet && in_array($player_username, $this->game_api->enable_mock_cancel_player_list)){
			$this->utils->debug_log("JILI @sesionBet -  triggered force cancel on bet request");
			http_response_code(502); # return 505 bad gateway error to trigger cancel
			$errorCode = 502;
		}

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	public function cancelSessionBet($gamePlatformId=null){	
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (cancelSessionBet)");	
     
		$externalResponse = $this->externalQueryResponse();	

		$callType = self::METHOD_CANCEL_SESSION_BET;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$currency = null;
		$previous_balance = $after_balance = 0;
		$cancelBetRefused = $betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$txId = null;
		$additionalResponse = [];
		$rules = [
			'reqId'=>'required',
            'token'=>'required',
            'currency'=>'required',
            'game'=>'required',
            'game'=>'isNumeric',
            'round'=>'required',
            //'round'=>'isNumeric',            
            'betAmount'=>'required',
            'betAmount'=>'isNumeric',
            'winloseAmount'=>'required',
            'winloseAmount'=>'isNumeric',
            'sessionId'=>'required',
            'sessionId'=>'isNumeric',
            'type'=>'required',
            'type'=>'isNumeric',
		];
		
		try {  

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVER);
			}   

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }
			
			$currency = $this->currency;
			
			if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}  
			
			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}  
			
			if(!empty($this->game_api->trigger_cancelsessionbet_error_response)){
				throw new Exception($this->game_api->trigger_cancelsessionbet_error_response);
			}

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

			//try userid token might be expired
            if(!$playerStatus && isset($this->request['userId'])){                
                $userId = $this->request['userId'];
            	list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($userId);
			}

			if(!$playerStatus){                
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}
            
			$player_id = $player->player_id;
            $params['token'] = $this->request['token'];
			$params['user_id'] = isset($this->request['userId'])?$this->request['userId']:null;            
            $params['player_id'] = $player_id;
            $params['req_id'] = $this->request['reqId'];
            $params['currency'] = $this->request['currency'];
            $params['game'] = $this->request['game'];
            $params['round'] = (string)$this->request['round'];
            $params['wagers_time'] = time()*1000;			
            $params['bet_amount'] = $this->request['betAmount'];
            $params['winlose_amount'] = $this->request['winloseAmount'];
			$params['external_uniqueid'] = $this->formatExternalUniqueId(self::METHOD_CANCEL_SESSION_BET, $params['round'], $player_id);
            $params['type'] = isset($this->request['type'])?$this->request['type']:null;                       
			$params['session_id'] = isset($this->request['sessionId'])?strval($this->request['sessionId']):null;            
			$params['preserve'] = isset($this->request['preserve'])?$this->request['preserve']:null;            
			$params['trans_type'] = $callType;

			$params['player_name'] = $player_username;

			$isEnd = true;
			$actionType = 'refund';

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse,
				&$betExist,
				&$cancelBetRefused,
				$isEnd,
				$actionType) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $actionType, $isEnd, Wallet_model::REMOTE_RELATED_ACTION_BET);
				$this->utils->debug_log("JILI SEAMLESS SERVICE lockAndTransForPlayerBalance payout",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				if(isset($additionalResponse['betExist'])){
					$betExist=$additionalResponse['betExist'];	
				}
				if(isset($additionalResponse['cancelBetRefused'])){
					$cancelBetRefused=$additionalResponse['cancelBetRefused'];	
				}
				$this->after_balance = $after_balance;
				return $trans_success;
			});

			if(!$trans_success){
				if(!is_null($this->params_to_insert_incase_failed)){
					$params['remote_wallet_status'] = $this->remote_wallet_error_code;
					$params = $this->params_to_insert_incase_failed['params'];
					$previousBalance = $this->params_to_insert_incase_failed['previous_balance'];
					$afterBalance = $this->params_to_insert_incase_failed['after_balance'];
					$flagRefunded = $this->params_to_insert_incase_failed['flag_refunded'];
					$params['external_uniqueid'] = "failed-".$params['external_uniqueid'];
					$params['is_failed'] = 1;
					$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagRefunded);
					if(!$isAdded){
						$this->utils->debug_log("JILI @cancelSessionBet - failed to insert failed transaction");
					}
					$this->utils->debug_log("JILI @cancelSessionBet - successfully insert failed transaction");
				}
			}
			if($cancelBetRefused){				
				throw new Exception(self::ERROR_CANCEL_REFUSED);
			}

			if($isAlreadyExists){				
				throw new Exception(self::ERROR_ALREADY_CANCELLED);// to ask if what to return if trans already exist
			}

			if(!$betExist){				
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}
			$alreadyCancelled = isset($additionalResponse['alreadyCancelled']) ? $additionalResponse['alreadyCancelled'] : false;
			if($alreadyCancelled){				
				throw new Exception(self::ERROR_ALREADY_CANCELLED);
			}

			$alreadySettled = isset($additionalResponse['alreadySettled']) ? $additionalResponse['alreadySettled'] : false;
			if($alreadySettled || $cancelBetRefused){
				throw new Exception(self::ERROR_CANCEL_REFUSED);
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$check_trans_params = ['external_uniqueid'=>$this->trans_record['external_uniqueid']];
			$trans = $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_trans_params);
			$txId = isset($trans['id'])?$trans['id']:null;

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $this->after_balance;
            
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}				

		//$externalResponse['message'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;					
		$externalResponse['currency'] = $currency;
        $externalResponse['balance'] = $this->formatBalance($balance);	
		$externalResponse['txId'] = $txId;
        $externalResponse['token'] = $token;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("JILI SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);	
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("JILI SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("JILI SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);	
				return false;
			}
		}

		return true;
	}

	public function getErrorSuccessMessage($code){
		$message = '';

        switch ($code) {
			case self::SUCCESS:
				$message = lang('Success');
				break;
            case self::ERROR_ALREADY_ACCEPTED:
                $message = lang('Already accepted');
                break;	
            case self::ERROR_INSUFFICIENT_BALANCE:
                $message = lang('Insufficient Balance');
                break;
            case self::ERROR_INVALID_PARAMETERS:
                $message = lang('Invalid parameters');
                break;
            case self::ERROR_TOKEN_EXPIRED:
                $message = lang('Token Expired');
                break;	
            case self::ERROR_CANNOT_FIND_PLAYER:
                $message = lang('Cannot find player');
                break;	
            case self::ERROR_UNKNOWN:
                $message = lang('Unknown Error');
                break;
            case self::ERROR_SERVER:
                $message = lang('Server Error');
                break;
            case self::ERROR_GAME_MAINTENANCE:
                $message = lang('Game under maintenance');
                break;	
            case self::ERROR_IP_NOT_ALLOWED:
                $message = lang('IP Address is not whitelisted');
                break;	
            case self::ERROR_BET_DONT_EXIST:
                $message = lang('Bet dont exist.');
                break;		
			case self::ERROR_CANCEL_REFUSED:
				$message = lang('The bet is already accepted and cannot be cancelled.');
				break;			
			case self::ERROR_ROUND_NOT_FOUND:
				$message = lang('Round not found.');
				break;			
			case 'Connection timed out.':
			case self::ERROR_CONNECTION_TIMED_OUT:
				return lang('Connection timed out.');
				break;
		
				
			default:
				$this->utils->error_log("JILI SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}
		
		return $message;
	}

	public function getErrorReturnCode($code){
		$return = 0;

        switch ($code) {
			case self::SUCCESS:
				$return = 0;
				break;
			case self::ERROR_ALREADY_CANCELLED:
			case self::ERROR_ALREADY_ACCEPTED:
				$return = 1;
				break;
			case self::ERROR_INSUFFICIENT_BALANCE:
			case self::ERROR_ROUND_NOT_FOUND:
			case self::ERROR_BET_DONT_EXIST:
				$return = 2;
				break;
			case self::ERROR_INVALID_PARAMETERS:
				$return = 3;
				break;
			case self::ERROR_TOKEN_EXPIRED:
			case self::ERROR_CANNOT_FIND_PLAYER:
				$return = 4;
				break;
			case self::ERROR_SERVER:
			case self::ERROR_UNKNOWN:
			case self::ERROR_GAME_MAINTENANCE:
			case self::ERROR_IP_NOT_ALLOWED:
			case self::ERROR_CONNECTION_TIMED_OUT:
				$return = 5;
				break;
			case self::ERROR_CANCEL_REFUSED:
				$return = 6;
				break;
			default:				
			$this->utils->error_log("JILI SEAMLESS SERVICE: (getErrorReturnCode) unknown error: ", $code);
				$return = -1;
				break;
		}
		
		return $return;
	}
    
	//default external response template
	public function externalQueryResponse(){
		return array(
            "message" => "",
			"username" => "",
			"currency" => "",
			"balance" => "",
			"token" => "",
			"errorCode" => "",
		);
	}

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $statusText = null, $extra = null, $fields = [], $cost = null){
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
        	$statusText, 
			$extra,
			$fields,
			false,
			null,
			$cost
        );
	}

	//http://admin.og.local/jili_seamless_service_api/getGames/6003
	public function getGames($gamePlatformId=null){
		

		try { 
			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVER);
			}

			$get_bal_req = $this->game_api->queryGameListFromGameProvider();
			echo "<pre>";
			print_r($get_bal_req['data']['games']);
		} catch (Exception $error) {
			echo "error: " . $error->getMessage();
		}
	}
	
	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        if($error_code<>self::SUCCESS){
            $this->utils->error_log("JILI SEAMLESS SERVICE: (handleExternalResponse)", 
            'status', $status, 
            'type', $type, 
            'data', $data, 
            'response', $response, 
            'error_code', $error_code);	
        }else{
            $this->utils->debug_log("JILI SEAMLESS SERVICE: (handleExternalResponse)", 
            $response, 
            'error_code', $error_code);	
        }
		
		$httpStatusCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_SERVER];

		if($error_code=='Connection timed out.'){			
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}

		if(isset($error_code) && array_key_exists($error_code, self::HTTP_STATUS_CODE_MAP)){
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$error_code];	
		}

		//add request_id
		if(empty($response)){
			$response = [];
		}
        $cost = intval($this->utils->getExecutionTimeToNow()*1000);
		
        $response['errorCode'] = $this->getErrorReturnCode($error_code);
		$response['message'] = $this->getErrorSuccessMessage($error_code);		
        $response['cost_ms'] = $cost;		
        $response['request_id'] = $this->utils->getRequestId();		

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);	
		
		$uniqueId = isset($data['reqId']) ? $data['reqId'] : null;
		if(!is_null($uniqueId)){
			$this->jili_seamless_transactions->updateResponseResultId($this->game_api->getTransactionsTable(), $uniqueId, $this->response_result_id);
		}


		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### JILI SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		return $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
	}

	public function getPlayerByToken($token){	
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);		 

		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByUsername($gameUsername){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){		
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_record = $trans_record = $this->makeTransactionRecord($data);		
		
		return $this->CI->jili_seamless_transactions->insertIgnoreRow($trans_record);        		
	}

	public function makeTransactionRecord($raw_data){
		$data = [];		
		$data['token'] 			    = isset($raw_data['token'])?$raw_data['token']:null;
        $data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;
        $data['currency'] 			= isset($raw_data['currency'])?$raw_data['currency']:null;
        $data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;
        $data['round'] 			    = isset($raw_data['round'])?(string)$raw_data['round']:null;
        $data['wagers_time'] 		= isset($raw_data['wagers_time'])?$raw_data['wagers_time']:null;        
		$data['balance_adjustment_amount'] = isset($raw_data['balance_adjustment_amount'])?$raw_data['balance_adjustment_amount']:0;
        $data['bet_amount'] = isset($raw_data['bet_amount'])?$raw_data['bet_amount']:null;
        $data['winlose_amount'] = isset($raw_data['winlose_amount'])?$raw_data['winlose_amount']:null;
        $data['jp_contribute'] = isset($raw_data['jp_contribute'])?$raw_data['jp_contribute']:null;
        $data['jp_win'] = isset($raw_data['jp_win'])?$raw_data['jp_win']:null;
        $data['is_free_round'] = isset($raw_data['is_free_round'])?$raw_data['is_free_round']:null;
        
        $data['user_id'] = isset($raw_data['user_id'])?$raw_data['user_id']:null;
        $data['preserve'] = isset($raw_data['preserve'])?$raw_data['preserve']:null;
        $data['turnover'] = isset($raw_data['turnover'])?$raw_data['turnover']:null;
        $data['game'] = isset($raw_data['game'])?$raw_data['game']:null;

		$data['session_id'] = isset($raw_data['session_id'])?strval($raw_data['session_id']):null;
		$data['type'] = isset($raw_data['type'])?$raw_data['type']:null;        
        
        $data['trans_status'] = Game_logs::STATUS_PENDING;


        if($data['trans_type']==self::METHOD_BET || $data['is_free_round']==BaseModel::DB_TRUE){
            $data['trans_status'] = Game_logs::STATUS_SETTLED;
        }
        if($data['trans_type']==self::METHOD_SESSION_BET && 
        $data['type']==self::METHOD_SESSION_BETTYPE_PAYOUT){
            $data['trans_status'] = Game_logs::STATUS_SETTLED;
        }

		if(in_array($data['trans_type'], [self::METHOD_CANCEL_BET, self::METHOD_CANCEL_SESSION_BET])){
            $data['trans_status'] = Game_logs::STATUS_CANCELLED;
        }

		if($this->game_api->record_cancel_bet_transaction){
			if(isset($data['bet_dont_exist'])&&$data['bet_dont_exist']==true){
				$data['trans_status'] = -1;
			}
			if(isset($data['cancel_bet_refused'])&&$data['cancel_bet_refused']==true){
				$data['trans_status'] = -2;
			}
		}

        $wagers_time_parsed 				= $data['wagers_time'];
        $wagers_time_parsed 				= date('Y-m-d H:i:s', $wagers_time_parsed);
        $data['wagers_time_parsed'] 		= $wagers_time_parsed;
		$data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['external_uniqueid'] 			= isset($raw_data['external_uniqueid'])?$raw_data['external_uniqueid']:null;
		$data['reg_id']						= isset($raw_data['req_id'])?$raw_data['req_id']:null;
		$data['balance_adjustment_method'] 	= isset($raw_data['balance_adjustment_method'])?$raw_data['balance_adjustment_method']:null;
		$data['before_balance'] 			= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 				= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;	
		$data['game_platform_id'] 			= $this->game_platform_id;	
		$data['remote_wallet_status'] 		= isset($raw_data['remote_wallet_status'])?intval($raw_data['remote_wallet_status']):null;
		$data['is_failed'] 					= isset($raw_data['is_failed'])? $raw_data['is_failed'] : 0;
		$data['raw_data'] 					= json_encode($this->request);
		
		return $data;
	}

	public function getErrorCode($code){
		if(!in_array($code, self::ERROR_CODES)){
			$this->utils->error_log("JILI SEAMLESS SERVICE getErrorCode UNKNOWN ERROR:", $this->request, $code); 
			return self::ERROR_UNKNOWN;
		}
		return $code;
	}

	public function parseRequest(){				
        $request_json = file_get_contents('php://input');       
        $this->utils->debug_log("JILI SEAMLESS SERVICE raw:", $request_json); 
		$this->request = json_decode($request_json, true);		

		if(isset($this->request['round'])){
			$this->request['round'] = strval($this->request['round']);
		}	

		if(isset($this->request['sessionId'])){
			$this->request['sessionId'] = strval($this->request['sessionId']);
		}

		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = T1LOTTERY_SEAMLESS_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('t1lottery_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function formatBalance($balance){
		return floatval($balance);
	}

	public function isNumeric($amount){
		return is_numeric($amount);
	}

    public function calculateAmount($data){
        $bet_amount = isset($data['bet_amount'])?abs($data['bet_amount']):0;
        $winlose_amount = isset($data['winlose_amount'])?abs($data['winlose_amount']):0;
        //$jp_contribute = isset($data['jp_contribute'])?abs($data['jp_contribute']):0;
        $jp_win = isset($data['jp_win'])?abs($data['jp_win']):0;
		$preserve = isset($data['preserve'])?abs($data['preserve']):0;
		$trans_type = isset($data['trans_type'])?$data['trans_type']:false;
		$session_type = isset($data['type'])?$data['type']:false;

		$balanceAdjustment = 0;
		$total_debit = $bet_amount;
		$total_credit = $winlose_amount + $jp_win;

		if($trans_type && $trans_type==self::METHOD_SESSION_BET){
			if($session_type==self::METHOD_SESSION_BETTYPE_BET){
				$total_debit += $preserve;
			}elseif($session_type==self::METHOD_SESSION_BETTYPE_PAYOUT){
				$total_credit += $preserve;
			}
		}
	
		$balanceAdjustment = $total_credit-$total_debit;

		return $balanceAdjustment;     
    }

	public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance, $actionType = 'bet', $isEnd = false, $seamless_service_related_action = null){
		$this->utils->debug_log("JILI SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance, $seamless_service_related_action);

		//initialize params
		$player_id			= $params['player_id'];				
		$balanceAdjustment 	= $this->calculateAmount($params);
        $amount             = abs($balanceAdjustment);

		$params['balance_adjustment_amount']   = $amount;
		$uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$params['external_uniqueid'];
		$gameUniqueId = isset($params['game']) ? $params['game'] : null;

		
		if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
            $this->wallet_model->setRelatedActionOfSeamlessService($seamless_service_related_action);
        }

        if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameUniqueId);
        }

        if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
            $this->wallet_model->setGameProviderActionType($actionType);
        }

		$round_id = isset($params['round'])?(string)$params['round']:null;
		if($params['trans_type']==self::METHOD_SESSION_BET || $params['trans_type']==self::METHOD_CANCEL_SESSION_BET){
			$round_id = isset($params['session_id'])?strval($params['session_id']):$round_id;
		}
        if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
            $this->wallet_model->setGameProviderRoundId($round_id);
        }

        if( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
            $this->wallet_model->setGameProviderIsEndRound($isEnd);
        }

        if($actionType == 'bet-payout') {
            if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
                $this->wallet_model->setGameProviderBetAmount($this->game_provider_bet_amount);
            }

            if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
                $this->wallet_model->setGameProviderPayoutAmount($this->game_provider_payout_amount);
            }
        }

		//initialize response
		$success = false;
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];


		if($balanceAdjustment<0){
			$mode = 'debit';            
		}elseif($balanceAdjustment>=0){
			$mode = 'credit';
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}



        if($params['trans_type']==self::METHOD_CANCEL_BET || $params['trans_type']==self::METHOD_CANCEL_SESSION_BET){
			if($balanceAdjustment<0){
				$mode = 'credit';            
			}elseif($balanceAdjustment>=0){
				$mode = 'debit';
			}
			$flagrefunded = true;		
        }

        if($params['trans_type']==self::METHOD_SESSION_BET || $params['trans_type']==self::METHOD_CANCEL_SESSION_BET){
			//The settlement (type=2) should never be cancelled.
			if($params['trans_type']==self::METHOD_CANCEL_SESSION_BET && $params['type']==self::METHOD_SESSION_BETTYPE_PAYOUT){
				$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: not allowed to cancel ", self::METHOD_CANCEL_SESSION_BET, 'type', self::METHOD_SESSION_BETTYPE_PAYOUT,'params', $params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			if(
				!isset($params['type']) || 
				!in_array($params['type'], [self::METHOD_SESSION_BETTYPE_BET,self::METHOD_SESSION_BETTYPE_PAYOUT])
			){
				$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debitCreditAmountToWallet missing type", $params);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}
        }

		$params['balance_adjustment_method'] = $mode;

		//get and process balance
		$get_balance = $this->getPlayerBalance($player_id);
					
		if($get_balance!==false){
			$afterBalance = $previousBalance = $get_balance;
			if($mode=='debit'){
				$afterBalance = $afterBalance - $amount;
			}else{
				$afterBalance = $afterBalance + $amount;
			}
			
		}else{				
			$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		//check if bet transaction exists

		if($params['trans_type']==self::METHOD_CANCEL_BET){
			$check_bet_params = ['round'=> (string)$params['round'],'player_id'=>$params['player_id'], 'trans_type' => 'bet'];
			$betExist = $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_bet_params);
			
			if(!$betExist){
				$params['bet_dont_exist'] = true;
				$additionalResponse['betExist']=false;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}
		}	

		if($params['trans_type']==self::METHOD_CANCEL_BET||$params['trans_type']==self::METHOD_CANCEL_SESSION_BET){
			$flagrefunded = true;
			$check_bet_params = ['round'=> (string)$params['round'],'player_id'=>$params['player_id']];


			$betExist = $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_bet_params);

			$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) cancelBet", 'params',$params, 'betTransData', $betExist);
			if(empty($betExist)){
				$additionalResponse['betExist']=false;
				$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION", 'betExist', $betExist, 'params',$params, 'check_bet_params', $check_bet_params);
					$params['bet_dont_exist'] = true;

				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$additionalResponse['betExist']=true;
				// $additionalResponse['cancelBetRefused']=true;
				// $params['cancel_bet_refused'] = true;
			}

			$afterBalance = $previousBalance;
			
			$cancelledStatuses  = [Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED];
			$settledStatuses = [Game_logs::STATUS_SETTLED];

			if(isset($betExist['trans_status']) && in_array($betExist['trans_status'], $cancelledStatuses)){
				$additionalResponse['alreadyCancelled']=true;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			if($params['trans_type']==self::METHOD_CANCEL_SESSION_BET && isset($betExist['trans_status']) && in_array($betExist['trans_status'], $settledStatuses)){
				$additionalResponse['alreadySettled']=true;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			if(!$this->game_api->record_cancel_bet_transaction){
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}
			$bet_external_unique_id = isset($betExist['external_uniqueid']) ? 'game-'.$this->game_api->getPlatformCode().'-'.$betExist['external_uniqueid'] : null;
			$this->seamless_service_related_unique_id = $bet_external_unique_id;

		}

		if($params['trans_type'] == self::METHOD_SESSION_BET && $params['type']==self::METHOD_SESSION_BETTYPE_PAYOUT && !empty($params['session_id']))
		{
			$check_bet_params 		= ['session_id'=> (string)$params['session_id'],'player_id'=>$params['player_id'], 'type'=>self::METHOD_SESSION_BETTYPE_BET];
			$bet_transaction 		= $this->CI->jili_seamless_transactions->getTransactionByParamsArray($check_bet_params);
			if(!$bet_transaction){
				$additionalResponse['relatedBetNotExist']=true;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			$cancelledStatuses  = [Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED];
			$settledStatuses = [Game_logs::STATUS_SETTLED];

			if(isset($bet_transaction['trans_status']) && in_array($bet_transaction['trans_status'], $cancelledStatuses)){
				$additionalResponse['alreadyCancelled']=true;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}
			
			$bet_external_unique_id = isset($bet_transaction['external_uniqueid']) ? 'game-'.$this->game_api->getPlatformCode().'-'.$bet_transaction['external_uniqueid'] : null;
			$this->seamless_service_related_unique_id = $bet_external_unique_id;
		}

		if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
            $this->wallet_model->setRelatedUniqueidOfSeamlessService($this->seamless_service_related_unique_id);
        }

        if($params['trans_type']==self::METHOD_SESSION_BET && 
        $params['type']==self::METHOD_SESSION_BETTYPE_PAYOUT &&
		!empty($params['session_id'])){
            //flag bet session round as settled
			$flagSettledParams=['player_id'=>$player_id,'session_id'=>$params['session_id']];
			$this->jili_seamless_transactions->flagSessionBetTransactionSettled($flagSettledParams);
        }

		if($mode=='debit' && $previousBalance < $amount ){
			$afterBalance = $previousBalance;
			$insufficientBalance = true;
			$this->utils->debug_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);				
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		//insert transaction
		$params['remote_wallet_status'] = $this->remote_wallet_error_code;
		$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);
		$this->params_to_insert_incase_failed = array('params' => $params, 'previous_balance' => $previousBalance, 'after_balance' => $afterBalance, 'flag_refunded' => $flagrefunded);

		if($isAdded===false){
			$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		//rollback amount because it already been processed
		if($isAdded==0){
			$this->utils->debug_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
			$isAlreadyExists = true;					
			$afterBalance = $previousBalance;
			return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}else{
			$isTransactionAdded = true;
		}	

		if($this->utils->compareResultFloat($amount, '=', 0)){
			$success = true;
			if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
				if($this->utils->isEnabledRemoteWalletClient()){
					$this->utils->debug_log("JILI SEAMLESS SERVICE API: (adjustWallet) amount 0 call remote wallet", 'request', $this->request);
					$success=$this->wallet_model->incRemoteWallet($player_id, $amount, $this->game_platform_id, $afterBalance);
				} 
			}
		}else{
			$success = $this->transferGameWallet($player_id, $this->game_platform_id, $mode, $amount);
			$this->utils->debug_log("JILI @debitCreditAmountToWallet - @transferGameWallet result: ", $success);
		}	


		if(!$success){
			$afterBalance = $previousBalance;
			$this->utils->error_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
		}

		$raw_bet_external_unique_id = isset($betExist['external_uniqueid']) ? $betExist['external_uniqueid'] : null;
		$external_unique_id = isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null;	

		$get_balance = $this->getPlayerBalance($player_id);
		$this->utils->debug_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) get_balance", $get_balance, 'afterBalance', $afterBalance);
		
		if($get_balance !== null){
			$afterBalance = $get_balance;
		}

		if(in_array($params['trans_type'], [self::METHOD_CANCEL_BET, self::METHOD_CANCEL_SESSION_BET])){
            $data['trans_status'] = Game_logs::STATUS_CANCELLED;
            $data['remote_wallet_status'] = $this->remote_wallet_error_code;
			if(isset($data['trans_status'])){
				$updateBetTrans = $this->jili_seamless_transactions->updateDataByTransactionID($data, $raw_bet_external_unique_id, $this->game_api->original_transactions_table);
				if(!$updateBetTrans){
					$this->utils->debug_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet) trans_status are not updated for related bet");
				}
			}
			$data['after_balance'] = $get_balance;
			if(isset($data['after_balance'])){
				$updateAfterBalance = $this->jili_seamless_transactions->updateDataByTransactionID($data, $external_unique_id, $this->game_api->original_transactions_table);
			}

			if(!$updateAfterBalance){
				$this->utils->debug_log("JILI SEAMLESS SERVICE: (debitCreditAmountToWallet)  balance are not updated for cancel request");
			}
		}else{
			$data['remote_wallet_status'] = $this->remote_wallet_error_code;
			$updateBetTrans = $this->jili_seamless_transactions->updateDataByTransactionID($data, $external_unique_id, $this->game_api->original_transactions_table);
		}

		$rlt = array($success, $previousBalance,$afterBalance, $insufficientBalance,$isAlreadyExists,$additionalResponse,$isTransactionAdded);
		$this->utils->debug_log("JILI @debitCreditAmountToWallet - final response:", $rlt);
		return $rlt;
	}

	public function getPlayerBalance($player_id){			
		// $get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);
		// $this->utils->debug_log("JILI SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		// if($get_bal_req['success']){			
		// 	return $get_bal_req['balance'];
		// }else{
		// 	return false;
		// }
		// 
		if($player_id){
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                if($this->utils->isEnabledRemoteWalletClient()){
                    $useReadonly = true;
                    return $this->player_model->getPlayerSubWalletBalance($player_id, $this->game_platform_id, $useReadonly);
                }
            }
            return $this->wallet_model->readonlyMainWalletFromDB($player_id);
        } else {
            return false;
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

		if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')){
			if($this->utils->isEnabledRemoteWalletClient()){
				$this->remote_wallet_error_code = $this->wallet_model->getRemoteWalletErrorCode();
				$this->utils->debug_log("Jili @transferGameWallet remote wallet error code: " . $this->remote_wallet_error_code);
			} 
		}
		return $success;
	}

	public function isParametersValid($data){
		return true;
	}

    public function sendNotificationToMattermost($user,$message,$notifType,$texts_and_tags=null){
		if(!$this->game_api->enable_mm_channel_nofifications){
			return false;
		}
    	$this->load->helper('mattermost_notification_helper');

    	$notif_message = array(
    		array(
    			'text' => $message,
    			'type' => $notifType
    		)
    	);
    	return sendNotificationToMattermost($user, $this->game_api->mm_channel, $notif_message, $texts_and_tags);
    }

	public function generatePlayerToken($gamePlatformId){	
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (bet)");	

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   
		
		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

	public function testGenerateKey($gamePlatformId){	
		
        $this->utils->debug_log("JILI SEAMLESS SERVICE: (testGenerateKey)");

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}   

		$sign=$this->game_api->generateKey($arr);

		echo "<br>Key: ".$sign;
	}
	
    public function isIPAllowed(){
    	if(!$this->game_api->validateWhiteIP()){
            return false;
        }
        return true;
    }

	private function formatExternalUniqueId($type, $uniqueId, $player_id){
		return (string)$type.'-'.$uniqueId.'-'.$player_id;
	}
}///END OF FILE////////////