<?php

use Lcobucci\JWT\Token\DataSet;

 if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/**
 * http://admin.brl.staging.smash.t1t.in/wm_seamless_service_api/[API ID]
 */
class Wm_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const SUCCESS = '0x00';

	const TRANSTYPE_CALLBALANCE = 'CallBalance';
	const TRANSTYPE_POINTINOUT = 'PointInout';
	const TRANSTYPE_TIMEOUTBETRETURN = 'TimeoutBetReturn';
	const TRANSTYPE_SEND_MEMBER_REPORT = 'SendMemberReport';

    //error codes
    const ERROR_INSUFFICIENT_BALANCE = '0x01';
    const ERROR_INVALID_FUNCTION = '0x02';
	const ERROR_CANNOT_FIND_PLAYER = '0x03';
	const ERROR_IP_NOT_ALLOWED = '0x10';
	const ERROR_INVALID_PARAMETERS = '0x11';
	const ERROR_SERVER = '0x12';
	const ERROR_CONNECTION_TIMED_OUT = '0x13';
	const ERROR_GAME_UNDER_MAINTENANCE = '0x14';
	const ERROR_SERVICE_NOT_AVAILABLE = '0x15';
	const ERROR_INVALID_SIGNATURE = '0x16';
	const ERROR_PLAYER_BLOCKED = '0x17';
	const ERROR_POINTINOUT_DECREASE_MISSING = '0x18';
	const ERROR_POINTINOUT_INCREASE_MISSING = '0x19';
	const ERROR_TIMEOUTBETRETURN_DECREASE_MISSING = '0x20';
	const ERROR_TIMEOUTBETRETURN_INCREASE_PROCESSED = '0x21';
	const ERROR_REFUND_PAYOUT_ALREADY_EXISTS = '0x22';
	const ERROR_PAYOUT_REFUND_ALREADY_EXISTS = '0x23';

    

	const RESPONSE_CODE_MAP = [
        self::SUCCESS=>0,
		self::ERROR_INSUFFICIENT_BALANCE=>10805,        
		self::ERROR_CANNOT_FIND_PLAYER=>10501,          
		self::ERROR_INVALID_FUNCTION=>900,      
		self::ERROR_INVALID_SIGNATURE=>10303, 
        
        
		self::ERROR_PLAYER_BLOCKED=>10505,
		self::ERROR_GAME_UNDER_MAINTENANCE=>9994,
		self::ERROR_SERVICE_NOT_AVAILABLE=>9995,
		self::ERROR_CONNECTION_TIMED_OUT=>9996,
		self::ERROR_IP_NOT_ALLOWED=>9997,
        self::ERROR_INVALID_PARAMETERS=>9998,
        self::ERROR_SERVER=>9999,
        self::ERROR_POINTINOUT_DECREASE_MISSING=>9999,
        self::ERROR_POINTINOUT_INCREASE_MISSING=>9999,
        self::ERROR_TIMEOUTBETRETURN_DECREASE_MISSING=>9999,
        self::ERROR_TIMEOUTBETRETURN_INCREASE_PROCESSED=>9999,
        self::ERROR_REFUND_PAYOUT_ALREADY_EXISTS=>9999,
        self::ERROR_PAYOUT_REFUND_ALREADY_EXISTS=>9999,
	];

	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
        self::ERROR_INSUFFICIENT_BALANCE=>200,
		self::ERROR_SERVER=>500,
		self::ERROR_INVALID_FUNCTION=>500,
		self::ERROR_CONNECTION_TIMED_OUT=>500,
		self::ERROR_GAME_UNDER_MAINTENANCE=>400,
		self::ERROR_SERVICE_NOT_AVAILABLE=>400,
		self::ERROR_INVALID_PARAMETERS=>400,
		self::ERROR_CANNOT_FIND_PLAYER=>400,
		self::ERROR_IP_NOT_ALLOWED=>401,
        self::ERROR_INVALID_SIGNATURE=>500,
        self::ERROR_PLAYER_BLOCKED=>400,
        self::ERROR_POINTINOUT_DECREASE_MISSING=>400,
        self::ERROR_POINTINOUT_INCREASE_MISSING=>400,
        self::ERROR_TIMEOUTBETRETURN_DECREASE_MISSING=>400,
        self::ERROR_TIMEOUTBETRETURN_INCREASE_PROCESSED=>400,
        self::ERROR_REFUND_PAYOUT_ALREADY_EXISTS=>422,
        self::ERROR_PAYOUT_REFUND_ALREADY_EXISTS=>422,
	];


	private $game_api;
	private $game_platform_id;
	private $player_id;
	private $request;
    private $currency;
    private $country;
    private $organization;
    private $method;
    private $host_name;

	private $headers;

	// Additionals
	private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];

	public function __construct() {
		parent::__construct();
        //$this->ssa_init();

		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','wm_casino_transactions', 'ip'));

		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log(__METHOD__ . "  (__construct)", $this->request);

		$this->utils->debug_log(__METHOD__ . "  (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

    public function index($gamePlatformId=null){

        $method = (isset($this->request['cmd'])?(string)$this->request['cmd']:null);
		
		//common checking

		if(!$this->initialize($gamePlatformId)){
			$errorCode = self::ERROR_SERVICE_NOT_AVAILABLE;
			$externalResponse = [];            
			$externalResponse['errorCode'] = $this->getExternalErrorCode($errorCode);
			$externalResponse['errorMessage'] = $this->getErrorSuccessMessage($errorCode);
			$externalResponse['result'] = [];

			$this->utils->error_log(__METHOD__ . " ERROR INITIALIZING API", $gamePlatformId);
			return $this->handleExternalResponse(false, $method, $this->request, $externalResponse, $errorCode, null);
		}

        switch ($method) {
            case Wm_casino_transactions::TRANSTYPE_CALLBALANCE:
                return $this->callBalance($gamePlatformId);
            	break;
            case Wm_casino_transactions::TRANSTYPE_POINTINOUT:
                return $this->pointInOut($gamePlatformId);
            	break;
            case Wm_casino_transactions::TRANSTYPE_TIMEOUTBETRETURN:
                return $this->timeoutBetReturn($gamePlatformId);
            	break;
			case Wm_casino_transactions::TRANSTYPE_SEND_MEMBER_REPORT:
				return $this->sendMemberReport($gamePlatformId);
				break;
            default:
                $errorCode = self::ERROR_SERVICE_NOT_AVAILABLE;
                $externalResponse = [];            
                $externalResponse['errorCode'] = $this->getExternalErrorCode($errorCode);
                $externalResponse['errorMessage'] = $this->getErrorSuccessMessage($errorCode);
                $externalResponse['result'] = [];

				$this->utils->error_log(__METHOD__ . " ERROR identifying method", $method);
                return $this->handleExternalResponse(false, $method, $this->request, $externalResponse, $errorCode, null);
              }
    }

	

	public function callBalance($gamePlatformId=null){
        $this->utils->debug_log(__METHOD__, $gamePlatformId);

		$externalResponse = $this->externalQueryResponse();

		$callType = Wm_casino_transactions::TRANSTYPE_CALLBALANCE;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$balance = 0;
		$player_id = $gameUsername  = null;
		$success = false;

		$rules = [
			'cmd'=>['required'],
			'signature'=>['required'],
            'user'=>['required'],
            'requestDate'=>['required']
		];

		try {

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

            $signature = $this->request['signature'];
			if(!$this->isValidSignature($signature)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}

			// get player details
			$gameUsername = $this->request['user'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                throw new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

			/*$response = $this->game_api->queryPlayerReadonlyBalanceByPlayerId($player_id);
			
			if(empty($response)||(isset($response['success'])&& $response['success']==false)){
				throw new Exception(self::ERROR_SERVER);
			}

			$balance=$response['balance'];*/
			$balance = $this->game_api->queryPlayerBalance($player_username)['balance'];
			if($balance===false){
				throw new Exception(self::ERROR_SERVER);
			}

			$balance = $this->game_api->dBtoGameAmount($balance);

			$success = true;
			$errorCode = self::SUCCESS;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}
        
        $externalResponse = [];            
        $externalResponse['errorCode'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['errorMessage'] = $this->getErrorSuccessMessage($errorCode);
        $externalResponse['result'] = [
            'user' => $gameUsername, 
            'money' => $balance, 
            'responseDate' => $this->utils->formatDateTimeForMysql(new \DateTime())
        ];

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function sendMemberReport($gamePlatformId=null){
        $this->utils->debug_log(__METHOD__, $gamePlatformId);

		$externalResponse = $this->externalQueryResponse();

		$callType = Wm_casino_transactions::TRANSTYPE_SEND_MEMBER_REPORT;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$balance = 0;
		$player_id = $gameUsername  = null;
		$success = false;

		$rules = [
			'cmd'=>['required'],
			'signature'=>['required'],
            'requestDate'=>['required']
		];

		try {

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

            $signature = $this->request['signature'];
			if(!$this->isValidSignature($signature)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}

			$tableName = $this->game_api->getTransactionsTable();
			$prevTranstable = $this->CI->wm_casino_transactions->getTransactionsPreviousTable($this->game_api->original_transactions_table);
			$checkOtherTable = $this->game_api->checkOtherTransactionTable();

			# update transactions for each report
			$trans = ( ( isset($this->request['result']) && is_array($this->request['result']) ) ?$this->request['result']:[] );
			foreach($trans as $transaction){

				//save transaction to use for game logs
				$trans_data = [];
				$trans_data['user'] 				= isset($transaction['user'])?$transaction['user']:null;//string
				$trans_data['cmd'] 					= Wm_casino_transactions::TRANSTYPE_SEND_MEMBER_REPORT;
				$trans_data['money'] 				= 0;//string
				$trans_data['amount'] 				= 0;//string
				$trans_data['request_date'] 		= isset($transaction['betTime'])?$transaction['betTime']:null;//datetime
				$trans_data['dealid'] 	    		= isset($transaction['betId'])?$transaction['betId']:null;//string
				$trans_data['gtype'] 	        	= isset($transaction['gid'])?$transaction['gid']:null;//string
				$trans_data['type'] 	        	= null;//datetime
				$trans_data['betdetail'] 	    	= null;//datetime
				
				$trans_data['code'] 	        	= null;//string
				$trans_data['category'] 	        = null;//string

				$trans_data['game_platform_id'] 	= $this->game_api->getPlatformCode();		
				$trans_data['bet_id'] 				= isset($transaction['betId'])?$transaction['betId']:null;//string
				$trans_data['round_id'] 			= isset($transaction['round'])?$transaction['round']:null;//string	

				if(!empty($trans_data['gtype'])){
					$trans_data['round_id'] = $trans_data['gtype'] . '_' . $trans_data['round_id'];
				}
				
				$subroundId = isset($transaction['subround'])?$transaction['subround']:'';//string
				if(!empty($subroundId)){
					$trans_data['round_id'] .= '_'.$subroundId;
				}

				if(empty($trans_data['round_id'])){
					$trans_data['round_id'] 			= isset($transaction['event'])?$transaction['event']:null;//string		
				}

				$trans_data['gameno'] 	    		= $trans_data['round_id'];
				
				$trans_data['player_id'] 			= null;//string
				$trans_data['trans_type'] 			= $trans_data['cmd'];//string
				$trans_data['wallet_adjustment_mode'] = null;//string
				$trans_data['before_balance'] 		= 0;
				$trans_data['after_balance'] 		= 0;	
				$trans_data['status'] 				= Game_logs::STATUS_SETTLED;	

				$gameResult = 	isset($transaction['gameResult'])?$transaction['gameResult']:null;//string
				if($gameResult=='The council canceled'){
					$trans_data['status'] 				= Game_logs::STATUS_CANCELLED;
				}
	
				$trans_data['payout'] 				= isset($transaction['result'])?$transaction['result']:null;//string
				$trans_data['bet_amount'] 			= isset($transaction['bet'])?$transaction['bet']:0;//string
				if(isset($transaction['validbet'])){
					$trans_data['bet_amount'] 			= isset($transaction['validbet'])?$transaction['validbet']:0;//string
				}

				$trans_data['result_amount'] 		= isset($transaction['winLoss'])?$transaction['winLoss']:0;//string

				$trans_data['bet_amount'] = $this->game_api->gameAmountToDBTruncateNumber($trans_data['bet_amount']);
				$trans_data['result_amount'] = $this->game_api->gameAmountToDBTruncateNumber($trans_data['result_amount']);

				$trans_data['response_result_id'] 	= null;	
				$trans_data['external_uniqueid'] 	= $trans_data['user'].'_'.$trans_data['round_id'].'_'.$transaction['betId'];
				$trans_data['raw_data'] 			= @json_encode($this->request);//text	       
				$trans_data['bet_result'] 			= @json_encode($transaction);//json	       
		
				$trans_data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);
				$isAdded = $this->game_api->insertIgnoreTransactionRecord($trans_data, 0, 0, 0);

				if($isAdded===false){
					# alert error save transaction
				}
				
			}

			$success = true;
			$errorCode = self::SUCCESS;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}
        
        $externalResponse = [];            
        $externalResponse['errorCode'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['errorMessage'] = $this->getErrorSuccessMessage($errorCode);
        $externalResponse['result'] = [
            'user' => $gameUsername, 
            'money' => $balance, 
            'responseDate' => $this->utils->formatDateTimeForMysql(new \DateTime())
        ];

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function pointInOut($gamePlatformId=null){
        $this->utils->debug_log(__METHOD__, $gamePlatformId);

		$externalResponse = $this->externalQueryResponse();

		$callType = Wm_casino_transactions::TRANSTYPE_POINTINOUT;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$money = $balance = 0;
		$dealId = $player_id = $gameUsername  = null;
		$success = false;
        $params = [];
		$after_balance = 0;

		$rules = [
			'cmd'=>['required'],
			'signature'=>['required'],
            'user'=>['required'],
            'money'=>['required'],
            'requestDate'=>['required'],
            'dealid'=>['required'],
            'gtype'=>['required'],
            'type'=>['required'],
            'betdetail'=>['required'],
            'gameno'=>['required'],
            'code'=>['required'],
            'category'=>['required'],
            //'betId'=>['required'],
            //'payout'=>['required']
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

            $signature = $this->request['signature'];
			if(!$this->isValidSignature($signature)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}

			// get player details
			$gameUsername = $this->request['user'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                throw new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			
			$player_id = $params['player_id'] = $player->player_id;
            $params['player_username'] = $player_username;
            $params['cmd'] = $this->request['cmd'];
            //$params['signature'] = $this->request['signature'];
            $params['user'] = $this->request['user'];
			$money = $params['money'] = $this->request['money'];
			$params['request_date'] = $this->request['requestDate'];
			$dealId = $params['dealid'] = $this->request['dealid'];
			$params['gtype'] = $this->request['gtype'];
			$params['type'] = $this->request['type'];
			$params['betdetail'] = $this->request['betdetail'];
			$params['gameno'] = $this->request['gameno'];
			$code = $params['code'] = $this->request['code'];
			$params['category'] = $this->request['category'];
			$params['bet_id'] = isset($this->request['betId'])?$this->request['betId']:null;
			$params['payout'] = isset($this->request['payout'])?$this->request['payout']:null;
            //addons
			$params['before_balance'] = 0;
			$params['after_balance'] = 0;
			$params['trans_type'] = $callType;

			$params['external_uniqueid'] = $params['cmd'].'-'.$code.'-'.$this->request['dealid'];
			$params['game_platform_id'] = $this->game_api->getPlatformCode();
			$params['raw_data'] = json_encode($this->request);
			$params['round_id'] = str_replace('_cancel', '', $this->request['gameno']);

			//delay triggers
			if($code==2){
				sleep($this->game_api->trigger_delay_pointinout2);
			}
			if($code==1){
				sleep($this->game_api->trigger_delay_pointinout1);
			}
			
			if(!array_key_exists($params['code'], Wm_casino_transactions::CODES)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			/***
			 * CODE reference
			 * code: 1:when member wins
			 * code: 2:when memeber bet
			 * code: 3:when a round change and affected this member :lose change to win 
			 * code: 4:when a round change and affected this member :win change to lose 
			 * code: 5:manual adding credit to member or manual deducting credit from member
			 * 
			 */

			if($code==Wm_casino_transactions::CODE_POINT_INCREASE){//1
				$mode = 'credit';
				$params['trans_type'] = $callType.'Increase';
				$params['status'] = Game_logs::STATUS_SETTLED;

			}elseif($code==Wm_casino_transactions::CODE_POINT_DECREASE){//2
				$mode = 'debit';
				$params['trans_type'] = $callType.'Decrease';
				$params['status'] = Game_logs::STATUS_PENDING;

			}elseif($code==Wm_casino_transactions::CODE_POINT_INCREASE_BY_GAME_RESET){//3
				$mode = 'credit';
				$params['trans_type'] = $callType.'IncreaseLoseToWin';
				$params['status'] = Game_logs::STATUS_SETTLED;

			}elseif($code==Wm_casino_transactions::CODE_POINT_DECREASE_BY_GAME_RESET){//4
				$mode = 'debit';
				$params['trans_type'] = $callType.'DecreaseWinToLose';
				$params['status'] = Game_logs::STATUS_SETTLED;

			}elseif($code==Wm_casino_transactions::CODE_RE_PAYOUT){//5
				$mode = 'debit';
				$amount = floatval($params['money']);
				if($amount>=0){
					$mode = 'credit';
				}

				$params['trans_type'] = $callType.'RePayout';
				$params['status'] = Game_logs::STATUS_SETTLED;
			}else{
				$params['trans_type'] = $callType.'Unknown';
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
			
			$params['wallet_adjustment_mode'] = $mode;

            $is_refund_exists = $this->isTransactionExists($this->game_api->getTransactionsTable(), [
                'cmd' => 'TimeoutBetReturn',
                'round_id' => $params['round_id'],
            ]);

            if ($is_refund_exists) {
                throw new Exception(self::ERROR_PAYOUT_REFUND_ALREADY_EXISTS);
            }

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				$mode,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $mode, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("WM SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
                
				return $trans_success;
			});

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($code==Wm_casino_transactions::CODE_POINT_INCREASE &&
				isset($additionalResponse['MissingPointInoutDecrease']) && 
				$additionalResponse['MissingPointInoutDecrease']==true){
				throw new Exception(self::ERROR_POINTINOUT_DECREASE_MISSING);
			}

			$this->utils->debug_log( "WM SEAMLESS SERVICE", [
				'$params' => $params,
				'$this->game_api->enable_mock_failed_transaction_player_list' => $this->game_api->enable_mock_failed_transaction_player_list,
				'$this->game_api->enable_mock_failed_transaction' => $this->game_api->enable_mock_failed_transaction,
			]);
			

			if($trans_success==false || ( $this->game_api->enable_mock_failed_transaction && in_array( $params['player_username'], $this->game_api->enable_mock_failed_transaction_player_list) ) ){
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
				$this->save_remote_wallet_failed_transaction($this->ssa_insert, $params);
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}
        
        $externalResponse = [];            
        $externalResponse['errorCode'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['errorMessage'] = $this->getErrorSuccessMessage($errorCode);
		$after_balance = $this->game_api->dBtoGameAmount($after_balance);
		$externalResponse['result'] = [
			'money' => (string)$money, 
			'responseDate' => $this->utils->formatDateTimeForMysql(new \DateTime()),
            'dealid' => $dealId, 
            'cash' => (string)$after_balance, 
        ];

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	
    }

	public function timeoutBetReturn($gamePlatformId=null){

        $this->utils->debug_log(__METHOD__, $gamePlatformId);

		$externalResponse = $this->externalQueryResponse();

		$callType = Wm_casino_transactions::TRANSTYPE_TIMEOUTBETRETURN;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$money = $balance = 0;
		$dealId  = $player_id = $gameUsername  = null;
		$success = false;
        $params = [];

		$after_balance = null;

		$rules = [
			'cmd'=>['required'],
			'signature'=>['required'],
            'user'=>['required'],
            'money'=>['required'],
            'requestDate'=>['required'],
            'dealid'=>['required'],
            'gtype'=>['required'],
            'type'=>['required'],
            'betdetail'=>['required'],
            'gameno'=>['required'],
            'code'=>['required'],
            'category'=>['required']
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

            $signature = $this->request['signature'];
			if(!$this->isValidSignature($signature)){
				throw new Exception(self::ERROR_INVALID_SIGNATURE);
			}

			// get player details
			$gameUsername = $this->request['user'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                throw new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			
			$player_id = $params['player_id'] = $player->player_id;
            $params['cmd'] = $this->request['cmd'];
            //$params['signature'] = $this->request['signature'];
            $params['user'] = $this->request['user'];
			$money = $params['money'] = $this->request['money'];
			$params['request_date'] = $this->request['requestDate'];
			$dealId = $params['dealid'] = $this->request['dealid'];
			$params['gtype'] = $this->request['gtype'];
			$params['type'] = $this->request['type'];
			$params['betdetail'] = $this->request['betdetail'];
			$params['gameno'] = $this->request['gameno'];
			$code = $params['code'] = $this->request['code'];
			$params['category'] = $this->request['category'];
			//$params['bet_id'] = $this->request['betId'];
			//$params['payout'] = $this->request['payout'];

            //addons
			$params['before_balance'] = 0;
			$params['after_balance'] = 0;
			$params['trans_type'] = $callType;
			//$params['external_uniqueid'] = $params['cmd'].'PointInout-'.$code.'-'.$this->request['dealid'];
			$params['external_uniqueid'] = 'PointInout-'.$code.'-'.$this->request['dealid'];
			$params['game_platform_id'] = $this->game_api->getPlatformCode();
			$params['raw_data'] = json_encode($this->request);
			$params['round_id'] = str_replace('_cancel', '', $this->request['gameno']);

			if(!array_key_exists($params['code'], Wm_casino_transactions::CODES)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if($code==Wm_casino_transactions::CODE_POINT_DECREASE){//increase debited
				$mode = 'credit';
				$params['status'] = Game_logs::STATUS_CANCELLED;
				$params['trans_type'] = $callType.'Decrease';
				$params['external_uniqueid'] = $params['cmd'].'-'.$code.'-'.$this->request['dealid'];
			}elseif($code==Wm_casino_transactions::CODE_POINT_INCREASE){//decrease credited
				$mode = 'credit';
				$params['trans_type'] = $callType.'Increase';
				$params['status'] = Game_logs::STATUS_SETTLED;
			}else{
				$params['trans_type'] = $callType.'Unknown';
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}
        
			$params['wallet_adjustment_mode'] = $mode;

            $is_payout_exists = $this->isTransactionExists($this->game_api->getTransactionsTable(), [
                'cmd' => 'PointInout',
                'wallet_adjustment_mode' => 'credit',
                'round_id' => $params['round_id'],
            ]);

            if ($is_payout_exists) {
                throw new Exception(self::ERROR_REFUND_PAYOUT_ALREADY_EXISTS);
            }
			
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				$mode,
				&$insufficient_balance, 
				&$previous_balance, 
				&$after_balance, 
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $mode, $this->request, $previous_balance, $after_balance);
				$this->utils->debug_log("WM SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);	
				return $trans_success;
			});

			if($insufficient_balance){						
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){				
				
            }

			if($code==Wm_casino_transactions::CODE_POINT_INCREASE &&
				isset($additionalResponse['MissingPointInoutDecrease']) && 
				$additionalResponse['MissingPointInoutDecrease']==true){
				throw new Exception(self::ERROR_POINTINOUT_DECREASE_MISSING);
			}

			if(
				isset($additionalResponse['TimeoutBetReturnDecreaseMissing']) && 
				$additionalResponse['TimeoutBetReturnDecreaseMissing']==true){
				throw new Exception(self::ERROR_TIMEOUTBETRETURN_DECREASE_MISSING);
			}

			if(
				isset($additionalResponse['TimeoutBetReturnIncreaseAlreadyProcessed']) && 
				$additionalResponse['TimeoutBetReturnIncreaseAlreadyProcessed']==true){
				throw new Exception(self::ERROR_TIMEOUTBETRETURN_INCREASE_PROCESSED);
			}

			if($trans_success==false || ( $this->game_api->enable_mock_failed_transaction && in_array( $params['player_username'], $this->game_api->enable_mock_failed_transaction_player_list) ) ){
				$this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
				$this->save_remote_wallet_failed_transaction($this->ssa_insert, $params);
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}

		if($after_balance===null){
			$_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);
			$after_balance = $_req['balance'];
		}
        
        $externalResponse = [];            
        $externalResponse['errorCode'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['errorMessage'] = $this->getErrorSuccessMessage($errorCode);

		$after_balance = $this->game_api->dBtoGameAmount($after_balance);
        $externalResponse['result'] = [
			'money' => (string)$money, 
            'cash' => (string)$after_balance, 
            'responseDate' => $this->utils->formatDateTimeForMysql(new \DateTime()),
            'dealid' => $dealId, 
        ];

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	
    }



    ############ METHODS

	public function parseRequest(){
        $request_json = file_get_contents('php://input');
        $this->utils->debug_log(__METHOD__ . " RAW DATA:", 'request_json', $request_json);
		parse_str($request_json, $this->request);
        $this->utils->debug_log(__METHOD__ . " RAW DATA:", 'parse_str', $this->request);

		return $this->request;
	}

	private function initialize($gamePlatformId){
		$this->utils->debug_log(__METHOD__ . " gamePlatformId: " . $gamePlatformId);

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->game_platform_id = $this->getValidPlatformId();
        }		

        $this->game_api = $this->CI->utils->loadExternalSystemLibObject($this->game_platform_id);

        if(!$this->game_api){
			$this->utils->error_log(__METHOD__ . " ERROR LOAD: ", $gamePlatformId, $this->game_platform_id);
			return false;
        }

		$this->game_api->request = $this->request;
		$this->use_remote_wallet_failed_transaction_monthly_table = $this->game_api->use_remote_wallet_failed_transaction_monthly_table;

		$this->utils->debug_log(__METHOD__ . "  (initialize) currency: ", $this->currency);
        $this->wm_casino_transactions->setTableName($this->game_api->getTransactionsTable());

		return true;
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $ruleArr){
		
			foreach($ruleArr as $rule){
				if($rule=='required'&&!isset($request[$key])){
					$this->utils->error_log(__METHOD__ . "  (isValidParams) Missing Parameters: ". $key, $request, $rules);
					return false;
				}
	
				if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
					$this->utils->error_log(__METHOD__ . "  (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
					return false;
				}
	
				if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
					$this->utils->error_log(__METHOD__ . "  (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
					return false;
				}
			}//each rule group
		}//each field rule

		return true;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = WM_SEAMLESS_GAME_API;
		return $this->game_platform_id;
	}

	public function isNumeric($amount){
		return is_numeric($amount);
	}

	public function getExternalErrorCode($code){
		if(array_key_exists($code, self::RESPONSE_CODE_MAP)){
			return self::RESPONSE_CODE_MAP[$code];
		}
        return self::RESPONSE_CODE_MAP[self::ERROR_SERVER];
	}


	public function getErrorSuccessMessage($code){
		$message = '';

		if(!array_key_exists($code, self::HTTP_STATUS_CODE_MAP)){
			$message = $code;
			return $message;
		}

        switch ($code) {

			case self::SUCCESS:
				return lang('Success');

            case self::ERROR_INSUFFICIENT_BALANCE:
                return lang('Insufficient Balance');

            case self::ERROR_SERVER:
                return lang('Server Error');

            case self::ERROR_IP_NOT_ALLOWED:
                return lang('IP is not allowed');

            case self::ERROR_INVALID_FUNCTION:
                return lang('Invalid Function');

            case self::ERROR_CANNOT_FIND_PLAYER:
                return lang('Cannot find player.');    

			case self::ERROR_INVALID_PARAMETERS:
				return lang('Invalid parameters.');   

			case self::ERROR_SERVICE_NOT_AVAILABLE:
				return lang('Service not available.');  

			case self::ERROR_POINTINOUT_DECREASE_MISSING:
				return lang('Point in and out decrease missing.');  

			case self::ERROR_POINTINOUT_INCREASE_MISSING:
				return lang('Point in and out increase missing.'); 

			case self::ERROR_TIMEOUTBETRETURN_DECREASE_MISSING:
				return lang('Timoutbetreturn decrease is missing.'); 
		

			case self::ERROR_TIMEOUTBETRETURN_INCREASE_PROCESSED:
				return lang('Timoutbetreturn increase already processed.'); 
			
    
				

			default:
				$this->CI->utils->error_log(__METHOD__ . "  (getErrorSuccessMessage) error: ", $code);
				return $code;
		}
	}

	//default external response template
	public function externalQueryResponse(){
		return array(
            "errorCode" => self::ERROR_SERVER,            
            "errorMessage" => null,            
            "result" => json_encode(['user'=>null,'money'=>null,'responseDate'=>null])
		);
	}

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->CI->utils->debug_log(__METHOD__ . "  (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code,
            'fields', $fields);

		if(strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log(__METHOD__ . "  (handleExternalResponse) Connection timed out.",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code,
            'fields', $fields);
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}

		$httpStatusCode = $this->getHttpStatusCode($error_code);

		//add request_id
		if(empty($response)){
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	public function getHttpStatusCode($errorCode){
		$httpCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_SERVER];
		foreach(self::HTTP_STATUS_CODE_MAP as $key => $value){
			if($errorCode==$key){
				$httpCode = $value;
			}
		}
		return $httpCode;
	}

	public function getPlayerByGameUsername($gameUsername){
		$player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $gameUsername, $player->username];
	}

	public function isValidSignature($signature){

		if($this->game_api->signature==$signature){
			return true;
		}
		$this->utils->error_log(__METHOD__ . "  (isValidSignature)", $signature);
		return false;
    }




    ############ / METHODS

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

	private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {

		$save_data = $md5_data = [
            'transaction_id' => !empty(  $data['external_uniqueid'] ) ?  $data['external_uniqueid'] : null,
            'round_id' => !empty(  $data['round_id'] ) ?  $data['round_id'] : null,
            'external_game_id' => !empty(  $data['gtype'] ) ?  $data['gtype'] : null,
            'player_id' => !empty($data['player_id']) ? $data['player_id'] : null,
            'game_username' => !empty($data['player_username']) ? $data['player_username'] : null,
            'amount' => isset($data['money']) ? $data['money'] : null,
            'balance_adjustment_type' => !empty($data['wallet_adjustment_mode']) && $data['wallet_adjustment_mode'] == 'debit' ? $this->ssa_decrease : $this->ssa_increase,
            'action' => !empty(  $data['cmd'] ) ?  $data['cmd'] : null,
            'game_platform_id' => $this->game_api->getPlatformCode(),
            'transaction_raw_data' => json_encode($this->request),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' =>  !empty(  $data['external_uniqueid'] ) ?  $data['external_uniqueid'] : null,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($this->use_remote_wallet_failed_transaction_monthly_table) {
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

        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);
    }

    protected function isTransactionExists($table, $where = []) {
        $checkOtherTable = $this->game_api->checkOtherTransactionTable();

        $is_exists = $this->ssa_is_transaction_exists($table, $where);

        if (!$is_exists && $checkOtherTable) {
            $is_exists = $this->ssa_is_transaction_exists($this->wm_casino_transactions->getTransactionsPreviousTable($table), $where);
        }

        return $is_exists;
    }

}///END OF FILE////////////