<?php

use Lcobucci\JWT\Token\DataSet;

 if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * http://admin.brl.staging.smash.t1t.in/t1lottery_seamless_service_api/[API ID]
 */
class Ygg_service_api extends BaseController {

    const SUCCESS = '0x00';

    //error codes
	const ERROR_SERVICE_NOT_AVAILABLE = '0x01';
	const ERROR_INVALID_SIGN = '0x02';
	const ERROR_INVALID_PARAMETERS = '0x03';
	const ERROR_INSUFFICIENT_BALANCE = '0x04';
	const ERROR_SERVER = '0x05';
	const ERROR_CANNOT_FIND_PLAYER = '0x06';
	const ERROR_TRANSACTION_ALREADY_EXIST = '0x07';
	const ERROR_IP_NOT_ALLOWED = '0x08';
	const ERROR_BET_DONT_EXIST = '0x09';
	const ERROR_GAME_UNDER_MAINTENANCE = '0x10';
	const ERROR_CONNECTION_TIMED_OUT = '0x11';
	const ERROR_REFUND_PAYOUT_EXIST = '0x12';
    const ERROR_PLAYER_BLOCKED = '0x13';
    const ERROR_PLAYER_PLAYER_NOT_AUTHORIZED = '0x14';
    const ERROR_BONUS_LIMIT = '0x15';
    const ERROR_ALREADY_PROCESSED_BY_ENDWAGER = '0x16';
    const ERROR_ALREADY_PROCESSED_BY_CANCELWAGER = '0x17';
    const ERROR_BET_DONT_EXIST_CANNOT_CANCEL = '0x18';

	const RESPONSE_CODE_MAP = [
        self::SUCCESS=>0,
        self::ERROR_CANNOT_FIND_PLAYER=>1000,
        self::ERROR_INSUFFICIENT_BALANCE=>1006,
        self::ERROR_PLAYER_BLOCKED=>1007,
        self::ERROR_PLAYER_PLAYER_NOT_AUTHORIZED=>1008,
        self::ERROR_BONUS_LIMIT=>1009,
		self::ERROR_SERVICE_NOT_AVAILABLE=>1,
		self::ERROR_INVALID_SIGN=>1,
		self::ERROR_INVALID_PARAMETERS=>1,
		self::ERROR_SERVER=>1,
		self::ERROR_TRANSACTION_ALREADY_EXIST=>1,
		self::ERROR_IP_NOT_ALLOWED=>1,
		self::ERROR_BET_DONT_EXIST=>1,
		self::ERROR_GAME_UNDER_MAINTENANCE=>1008,
		self::ERROR_CONNECTION_TIMED_OUT=>1,
		self::ERROR_REFUND_PAYOUT_EXIST=>1,
		self::ERROR_ALREADY_PROCESSED_BY_ENDWAGER=>1,
		self::ERROR_ALREADY_PROCESSED_BY_CANCELWAGER=>1,
		self::ERROR_BET_DONT_EXIST_CANNOT_CANCEL=>0,
	];

	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_SERVICE_NOT_AVAILABLE=>404,
		self::ERROR_INVALID_SIGN=>400,
		self::ERROR_INVALID_PARAMETERS=>400,
		self::ERROR_INSUFFICIENT_BALANCE=>406,
		self::ERROR_SERVER=>500,
		self::ERROR_CANNOT_FIND_PLAYER=>400,
		self::ERROR_TRANSACTION_ALREADY_EXIST=>409,
		self::ERROR_IP_NOT_ALLOWED=>401,
		self::ERROR_BET_DONT_EXIST=>400,
		self::ERROR_REFUND_PAYOUT_EXIST=>400,
		self::ERROR_GAME_UNDER_MAINTENANCE=>503,
		self::ERROR_CONNECTION_TIMED_OUT=>423,
        self::ERROR_PLAYER_BLOCKED=>401,
        self::ERROR_PLAYER_PLAYER_NOT_AUTHORIZED=>404,
		self::ERROR_BONUS_LIMIT=>400,
		self::ERROR_ALREADY_PROCESSED_BY_ENDWAGER=>400,
		self::ERROR_ALREADY_PROCESSED_BY_CANCELWAGER=>400,
		self::ERROR_BET_DONT_EXIST_CANNOT_CANCEL=>200,
	];

	const TRANSTYPE_PLAYERINFO = 'playerinfo';
	const TRANSTYPE_GETBALANCE = 'getbalance';
	const TRANSTYPE_WAGER = 'wager';
    const TRANSTYPE_CANCEL_WAGER = 'cancelwager';
    const TRANSTYPE_APPEND_WAGER_RESULT = 'appendwagerresult';
    const TRANSTYPE_END_WAGER= 'endwager';
    const TRANSTYPE_CAMPAIGN_PAYOUT= 'campaignpayout';

	private $game_api;
	private $game_platform_id;
	private $player_id;
	private $request;
    private $currency;
    private $country;
    private $organization;

    private $transaction_for_fast_track = null;

	private $headers;

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','t1lottery_transactions', 'ip'));

		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("YGG SEAMLESS SERVICE: (__construct)", $this->request);

		$this->utils->debug_log("YGG SEAMLESS SERVICE: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("YGG SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

        if(!$this->game_api){
			$this->utils->debug_log("YGG SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

		$this->game_api->request = $this->request;

        $this->currency = $this->game_api->getCurrency();
        $this->country = $this->game_api->getCountry();
        $this->organization = $this->game_api->getOrganization();
		$this->utils->debug_log("YGG SEAMLESS SERVICE: (initialize) currency: ", $this->currency);
        $this->t1lottery_transactions->tableName = $this->game_api->getTransactionsTable();

		return true;
	}

	public function playerinfo($gamePlatformId=null){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (balance)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::TRANSTYPE_PLAYERINFO;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$balance = 0;
		$player_id = $gameUsername  = null;
		$success = false;

		$rules = [
			'org'=>'required',
			'sessiontoken'=>'required',
            //'cat1'=>'','cat2'=>'','cat3'=>'','cat4'=>'','cat5'=>'','cat6'=>'','cat7'=>'','cat8'=>'','cat9'=>'',
            'lang'=>'required',
			'version'=>'required',
            //'tag1'=>'','tag2'=>'','tag3'=>'','tag4'=>'','tag5'=>'','tag6'=>'','tag7'=>'','tag8'=>'','tag9'=>'',
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

			if(!empty($this->game_api->trigger_player_info_error_response)){
				throw new Exception($this->game_api->trigger_player_info_error_response);
			}

			// get player details
			$token = $this->request['sessiontoken'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus || !isset($player->player_id) || empty($player->player_id)){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                throw new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

            $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
            $player_username,
                &$balance) {

                $balance = $this->game_api->getPlayerBalance($player_id);
                if($balance===false){
                    return false;
                }

                return true;
            });

			if(!$success){
				throw new Exception(self::ERROR_SERVER);
			}
			$success = true;
			$errorCode = self::SUCCESS;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}

        $externalResponse['code'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['data'] = [];
        $externalResponse['data']['playerId'] = $gameUsername;
        $externalResponse['data']['organization'] = $this->organization;
        $externalResponse['data']['balance'] = $this->formatBalance($balance);
        $externalResponse['data']['currency'] = $this->currency;
        $externalResponse['data']['homeCurrency'] = $this->currency;
        $externalResponse['data']['country'] = $this->country;
        $externalResponse['msg']= $this->getErrorSuccessMessage($errorCode);

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function getbalance($gamePlatformId=null){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (getbalance)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::TRANSTYPE_GETBALANCE;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$balance = 0;
		$player_id = $gameUsername  = null;
		$success = false;

		$rules = [
			'org'=>'required',
			'sessiontoken'=>'required',
            'playerid'=>'required',
            'gameid'=>'required',
            'description'=>'description',
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

			if(!empty($this->game_api->trigger_player_info_error_response)){
				throw new Exception($this->game_api->trigger_player_info_error_response);
			}

			// get player details
			$token = $this->request['sessiontoken'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                throw new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

            $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
            $player_username,
                &$balance) {

                $balance = $this->game_api->getPlayerBalance($player_id);
                if($balance===false){
                    return false;
                }

                return true;
            });

			if(!$success){
				throw new Exception(self::ERROR_SERVER);
			}
			$success = true;
			$errorCode = self::SUCCESS;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}
        $externalResponse['code'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['data'] = [];
        $externalResponse['data']['currency'] = $this->currency;
        $externalResponse['data']['applicableBonus'] = 0;
        $externalResponse['data']['homeCurrency'] = $this->currency;
        $externalResponse['data']['organization'] = $this->organization;
        $externalResponse['data']['balance'] = $this->formatBalance($balance);
        $externalResponse['data']['nickname'] = $gameUsername;
        $externalResponse['data']['playerId'] = $gameUsername;
        $externalResponse['data']['bonus'] = 0;

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function wager($gamePlatformId=null){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (wager)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::TRANSTYPE_WAGER;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'org'=>'required',
			'sessiontoken'=>'required',
			'playerid'=>'required',
			'amount'=>'required',
            'amount'=>'numeric',
			'amount'=>'nonNegative',
            'currency'=>'required',
            'reference'=>'required',//act as round
            'subreference'=>'required',//the uniqueid
            'description'=>'required',
			'cat4'=>'required',
			'cat5'=>'required',
            //'prepaidtticketid'=>'required',//to disable
            //'prepaidvalue'=>'required',
            //'prepaidcost'=>'required',
            //'prepaidref'=>'required',
            //'cat1'=>'','cat2'=>'','cat3'=>'','cat4'=>'','cat5'=>'','cat6'=>'','cat7'=>'','cat8'=>'','cat9'=>'',
            //'tag1'=>'','tag2'=>'','tag3'=>'','tag4'=>'','tag5'=>'','tag6'=>'','tag7'=>'','tag8'=>'','tag9'=>'',
            'lang'=>'required',
			'version'=>'required',
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

			if(!empty($this->game_api->trigger_wager_error_response)){
				throw new Exception($this->game_api->trigger_wager_error_response);
			}

			if(isset($this->request['currency']) && $this->request['currency'] != $this->currency){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			// get player details
            $token = $this->request['sessiontoken'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus || !isset($player->player_id) || empty($player->player_id)){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                throw new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

            $dataObj = new DateTime();
			$params['start_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['end_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['game_platform_id'] = $this->game_api->getPlatformCode();
			$params['amount'] = $this->request['amount'];
			$params['bet_amount'] = $this->request['amount'];
			$params['win_amount'] = 0;
			$params['cancelled_amount'] = 0;
			$params['result_amount'] = 0;
            $params['before_balance'] = 0;
            $params['after_balance'] = 0;
			$params['player_id'] = $player_id;
			$params['game_name'] = $this->request['cat4'];
			$params['game_id'] = $this->request['cat5'];
			$params['transaction_type'] = $callType;
			$params['status'] = Game_logs::PENDING;
			$params['transaction_id'] = $this->request['subreference'];
            $params['external_uniqueid'] = $this->request['subreference'];
            $params['round_id'] = $this->request['reference'];
			$params['extra_info'] = json_encode($this->request);
			$params['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);
			$mode = 'debit';
			$params['wallet_adjustment_mode'] = $mode;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$mode) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance, $mode);
				$this->utils->debug_log("YGG SEAMLESS SERVICE lockAndTransForPlayerBalance wager",
				'trans_success',$trans_success,
				'previous_balance',$previous_balance,
				'after_balance',$after_balance,
				'insufficient_balance',$insufficient_balance,
				'isAlreadyExists',$isAlreadyExists,
				'additionalResponse',$additionalResponse,
				'isTransactionAdded',$isTransactionAdded);
				return $trans_success;
			});

            $balance = $after_balance;

			if($insufficient_balance){
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);//page 20
			}

			if($isAlreadyExists){
				//throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

        $externalResponse['code'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['data'] = [];
        $externalResponse['data']['currency'] = $this->currency;//
        $externalResponse['data']['applicableBonus'] = 0;//
        $externalResponse['data']['homeCurrency'] = $this->currency;//
        $externalResponse['data']['organization'] = $this->organization;
        $externalResponse['data']['balance'] = $this->formatBalance($balance);
        $externalResponse['data']['nickname'] = $gameUsername;
        $externalResponse['data']['playerId'] = $gameUsername;
        $externalResponse['msg']= $this->getErrorSuccessMessage($errorCode);

		if($errorCode<>self::SUCCESS){
			/*$externalResponse['data']['popupMessage'] = [];
			$externalResponse['data']['popupMessage']['title'] = lang('Message');
			$externalResponse['data']['popupMessage']['content'] = lang($this->getErrorSuccessMessage($errorCode));
			$externalResponse['data']['popupMessage']['buttons'] = [];
			$externalResponse['data']['popupMessage']['channel'] = 'BOTH';
			$externalResponse['data']['popupMessage']['buttons'][] = [
				"label"=>lang("TERMS"),
				"actionType"=>"REDIRECT",
				"url"=>$this->game_api->getReturnUrl()
			];
			$externalResponse['data']['popupMessage']['buttons'][] = [
				"label"=>lang("CLOSE"),
				"actionType"=>"REDIRECT",
				"url"=>null
			];*/
		}

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function cancelwager($gamePlatformId=null){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (cancelwager)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::TRANSTYPE_CANCEL_WAGER;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$betExist = true;
		$previous_balance = $after_balance = 0;
		$isAlreadyProcessedByEndwager = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'org'=>'required',
			'playerid'=>'required',
			'reference'=>'required',//act as round
            'subreference'=>'required',//the uniqueid
			'version'=>'required',
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				// throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(isset($this->request['currency']) && $this->request['currency'] != $this->currency){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!empty($this->game_api->trigger_cancelwager_error_response)){
				throw new Exception($this->game_api->trigger_cancelwager_error_response);
			}

			// get player details
            $gameUsername = $this->request['playerid'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($gameUsername);

            if(!$playerStatus || !isset($player->player_id) || empty($player->player_id)){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                //  new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

            $dataObj = new DateTime();
			$params['start_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['end_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['game_platform_id'] = $this->game_api->getPlatformCode();
			$params['amount'] = 0;
			$params['bet_amount'] = 0;
			$params['cancelled_amount'] = 0;
			$params['win_amount'] = 0;
			$params['result_amount'] = 0;
            $params['before_balance'] = 0;
            $params['after_balance'] = 0;
			$params['player_id'] = $player_id;
			$params['game_id'] = null;
			$params['transaction_type'] = $callType;
			$params['status'] = Game_logs::STATUS_CANCELLED;
            $params['round_id'] = $this->request['reference'];
			$params['transaction_id'] = $this->request['subreference'];
			$params['external_uniqueid'] = $callType.'-'.$this->request['subreference'];
			$params['extra_info'] = json_encode($this->request);
			$params['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);
			$mode = 'credit';
			$params['wallet_adjustment_mode'] = $mode;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$mode,
				&$betExist,
                &$isAlreadyProcessedByEndwager) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance, $mode);
				$this->utils->debug_log("YGG SEAMLESS SERVICE lockAndTransForPlayerBalance cancelwager",
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

                if (isset($additionalResponse['isAlreadyProcessedByEndwager'])) {
					$isAlreadyProcessedByEndwager = $additionalResponse['isAlreadyProcessedByEndwager'];
				}

				return $trans_success;
			});

            $balance = $after_balance;

			if(!$betExist){
				throw new Exception(self::ERROR_BET_DONT_EXIST_CANNOT_CANCEL);
			}

			if($isAlreadyExists){
				$this->utils->debug_log("YGG SEAMLESS SERVICE TO RETURN SUCCESS IT ALREASDY PROCESSED", $params);
			}

            if ($isAlreadyProcessedByEndwager) {
                throw new Exception(self::ERROR_ALREADY_PROCESSED_BY_ENDWAGER);
            }

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

        $externalResponse['code'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['data'] = [];
        $externalResponse['data']['currency'] = $this->currency;//
		$externalResponse['data']['playerId'] = $gameUsername;
		$externalResponse['data']['organization'] = $this->organization;
        $externalResponse['data']['balance'] = $this->formatBalance($balance);
        $externalResponse['msg']= $this->getErrorSuccessMessage($errorCode);

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function appendwagerresult($gamePlatformId=null){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (appendwagerresult)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::TRANSTYPE_APPEND_WAGER_RESULT;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$betExist = true;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'org'=>'required',
			'playerid'=>'required',
			'amount'=>'required',
            'amount'=>'numeric',
            'amount'=>'nonNegative',
			//'isJackpotWin'=>'required',
			'bonusprize'=>'required',
            'bonusprize'=>'numeric',
			'currency'=>'required',
			'reference'=>'required',//act as round
            'subreference'=>'required',//the uniqueid
			'description'=>'required',
			'cat4'=>'required',
			'cat5'=>'required',
			//'cat1'=>'','cat2'=>'','cat3'=>'','cat4'=>'','cat5'=>'','cat6'=>'','cat7'=>'','cat8'=>'','cat9'=>'',
            //'tag1'=>'','tag2'=>'','tag3'=>'','tag4'=>'','tag5'=>'','tag6'=>'','tag7'=>'','tag8'=>'','tag9'=>'',
            //'lang'=>'required',
			'version'=>'required',
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				// throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(isset($this->request['currency']) && $this->request['currency'] != $this->currency){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!empty($this->game_api->trigger_appendwagerresult_error_response)){
				throw new Exception($this->game_api->trigger_appendwagerresult_error_response);
			}

			// get player details
            $gameUsername = $this->request['playerid'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($gameUsername);

            if(!$playerStatus || !isset($player->player_id) || empty($player->player_id)){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                //  new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

            $dataObj = new DateTime();
			$params['start_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['end_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['game_platform_id'] = $this->game_api->getPlatformCode();
			$params['amount'] = $this->request['amount'];
			$params['bet_amount'] = 0;
			$params['win_amount'] = $this->request['amount'];
			$params['result_amount'] = 0;
			$params['cancelled_amount'] = 0;
            $params['before_balance'] = 0;
            $params['after_balance'] = 0;
			$params['player_id'] = $player_id;
			$params['game_name'] = $this->request['cat4'];
			$params['game_id'] = $this->request['cat5'];
			$params['transaction_type'] = $callType;
			$params['status'] = Game_logs::PENDING;
            $params['transaction_id'] = $this->request['subreference'];
            $params['external_uniqueid'] = $this->request['subreference'];
            $params['round_id'] = $this->request['reference'];
			$params['extra_info'] = json_encode($this->request);
			$params['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);
			$mode = 'credit';
			$params['wallet_adjustment_mode'] = $mode;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$mode,
				&$betExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance, $mode);
				$this->utils->debug_log("YGG SEAMLESS SERVICE lockAndTransForPlayerBalance appendwagerresult",
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
				return $trans_success;
			});

            $balance = $after_balance;

			if($isAlreadyExists){
				//throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);
			}

			if(!$betExist){
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

        $externalResponse['code'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['data'] = [];
		$externalResponse['data']['organization'] = $this->organization;
		$externalResponse['data']['playerId'] = $gameUsername;
        $externalResponse['data']['currency'] = $this->currency;//
        $externalResponse['data']['applicableBonus'] = 0;//
        $externalResponse['data']['homeCurrency'] = $this->currency;//
		$externalResponse['data']['balance'] = $this->formatBalance($balance);
		$externalResponse['data']['nickname'] = $gameUsername;
		$externalResponse['data']['bonus'] = 0;
        $externalResponse['msg']= $this->getErrorSuccessMessage($errorCode);

		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function endwager($gamePlatformId=null){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (endwager)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::TRANSTYPE_END_WAGER;
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$betExist = true;
		$previous_balance = $after_balance = 0;
		$isAlreadyProcessedByCancelwager = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$rules = [
			'org'=>'required',
			'playerid'=>'required',
			'amount'=>'required',
            'amount'=>'numeric',
			'amount'=>'nonNegative',
			//'isJackpotWin'=>'required',
			'bonusprize'=>'required',
            'bonusprize'=>'numeric',
			'currency'=>'required',
			'reference'=>'required',//act as round
            'subreference'=>'required',//the uniqueid
			'description'=>'required',
			'cat4'=>'required',
			'cat5'=>'required',
			//'cat1'=>'','cat2'=>'','cat3'=>'','cat4'=>'','cat5'=>'','cat6'=>'','cat7'=>'','cat8'=>'','cat9'=>'',
            //'tag1'=>'','tag2'=>'','tag3'=>'','tag4'=>'','tag5'=>'','tag6'=>'','tag7'=>'','tag8'=>'','tag9'=>'',
            'lang'=>'required',
			'version'=>'required',
			//'prepaidref'=>'required',
			//'prepaidticketid'=>'required',
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				// throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(isset($this->request['currency']) && $this->request['currency'] != $this->currency){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!empty($this->game_api->trigger_endwager_error_response)){
				throw new Exception($this->game_api->trigger_endwager_error_response);
			}

			// get player details
            $gameUsername = $this->request['playerid'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByGameUsername($gameUsername);

            if(!$playerStatus || !isset($player->player_id) || empty($player->player_id)){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            if ($this->game_api->isBlocked($player_username)) {
                //  new Exception(self::ERROR_PLAYER_BLOCKED);
            }

			$player_id = $player->player_id;

            $dataObj = new DateTime();
			$params['start_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['end_at'] = $dataObj->format('Y-m-d H:i:s');
			$params['game_platform_id'] = $this->game_api->getPlatformCode();
			$params['amount'] = $this->request['amount'];
			$params['bet_amount'] = 0;
			$params['win_amount'] = $this->request['amount'];
			$params['cancelled_amount'] = 0;
			$params['result_amount'] = 0;
            $params['before_balance'] = 0;
            $params['after_balance'] = 0;
			$params['player_id'] = $player_id;
			$params['game_name'] = $this->request['cat4'];
			$params['game_id'] = $this->request['cat5'];
			$params['transaction_type'] = $callType;
			$params['status'] = Game_logs::STATUS_SETTLED;
			$params['transaction_id'] = $this->request['subreference'];
            $params['external_uniqueid'] = $this->request['subreference'];
            $params['round_id'] = $this->request['reference'];
			$params['extra_info'] = json_encode($this->request);
			$params['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);
			$mode = 'credit';
			$params['wallet_adjustment_mode'] = $mode;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				$mode,
				&$betExist,
                &$isAlreadyProcessedByCancelwager) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->game_api->debitCreditAmountToWallet($params, $this->request, $previous_balance, $after_balance, $mode);
				$this->utils->debug_log("YGG SEAMLESS SERVICE lockAndTransForPlayerBalance bet",
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

                if (isset($additionalResponse['isAlreadyProcessedByCancelwager'])) {
					$isAlreadyProcessedByCancelwager = $additionalResponse['isAlreadyProcessedByCancelwager'];
				}

				return $trans_success;
			});

            $balance = $after_balance;

			if($isAlreadyExists){
				//throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);
			}

			if(!$betExist){
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}

            if ($isAlreadyProcessedByCancelwager) {
                throw new Exception(self::ERROR_ALREADY_PROCESSED_BY_CANCELWAGER);
            }

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

        $externalResponse['code'] = $this->getExternalErrorCode($errorCode);
        $externalResponse['data'] = [];
		$externalResponse['data']['organization'] = $this->organization;
		$externalResponse['data']['playerId'] = $gameUsername;
        $externalResponse['data']['currency'] = $this->currency;//
        $externalResponse['data']['applicableBonus'] = 0;//
        $externalResponse['data']['homeCurrency'] = $this->currency;//
		$externalResponse['data']['balance'] = $this->formatBalance($balance);
		$externalResponse['data']['nickname'] = $gameUsername;
		$externalResponse['msg']= $this->getErrorSuccessMessage($errorCode);


		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("YGG SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}

			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("YGG SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}

			if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
				$this->utils->error_log("YGG SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
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

			case self::ERROR_INVALID_SIGN:
				return lang('Invalid signature');

			case self::ERROR_INVALID_PARAMETERS:
				return lang('Invalid parameters');

            case self::ERROR_SERVICE_NOT_AVAILABLE:
                return lang('Service not available');

            case self::ERROR_INSUFFICIENT_BALANCE:
                return lang('Insufficient Balance');

			case self::ERROR_SERVER:
				return lang('Server Error');

			case self::ERROR_IP_NOT_ALLOWED:
				return lang('IP is not allowed');

			case self::ERROR_TRANSACTION_ALREADY_EXIST:
				return lang('Transactions already exists.');

			case self::ERROR_CANNOT_FIND_PLAYER:
				return lang('Cannot find player.');

            case self::ERROR_BET_DONT_EXIST_CANNOT_CANCEL:
			case self::ERROR_BET_DONT_EXIST:
				return lang('Bet dont exist.');

			case self::ERROR_REFUND_PAYOUT_EXIST:
				return lang('Payout already exist.');

			case self::ERROR_GAME_UNDER_MAINTENANCE:
				return lang('Game under maintenance.');

			case 'Connection timed out.':
			case self::ERROR_CONNECTION_TIMED_OUT:
				return lang('Connection timed out.');

            case self::ERROR_PLAYER_BLOCKED:
                return lang('The account is blocked and no bets can be performed');

            case self::ERROR_PLAYER_PLAYER_NOT_AUTHORIZED:
                return lang('You are not allowed to perform the bet due to gaming limits');

            case self::ERROR_BONUS_LIMIT:
                return lang('You cannot place this bet due to max bet limit on bonus funds');

            case self::ERROR_ALREADY_PROCESSED_BY_ENDWAGER:
                return lang('Already processed by endwager');

            case self::ERROR_ALREADY_PROCESSED_BY_CANCELWAGER:
                return lang('Already processed by cancelwager.');

			default:
				$this->CI->utils->error_log("YGG SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				return $code;
		}
	}

	public function isValidAgent($agentId){

		if($this->game_api->agent_id==$agentId){
			return true;
		}
		$this->utils->error_log("YGG SEAMLESS SERVICE: (isValidAgent)", $agentId);
		return false;
    }

	//default external response template
	public function externalQueryResponse(){
		return array(
            "status" => [
                "code" => 999,
                "message" => 'ERROR'
            ]
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

	//http://admin.og.local/amb_service_api/getGames/5849
	public function getGames($gamePlatformId=null){


		try {
			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

			$get_bal_req = $this->game_api->queryGameListFromGameProvider();
			echo "<pre>";
			print_r($get_bal_req['data']['games']);
		} catch (Exception $error) {
			echo "error: " . $error->getMessage();
		}
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

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->CI->utils->debug_log("YGG SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code,
            'fields', $fields);

		if(strpos($error_code, 'timed out') !== false) {
			$this->CI->utils->error_log("YGG SEAMLESS SERVICE: (handleExternalResponse) Connection timed out.",
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
		//$response['request_id'] = $this->utils->getRequestId();
		//$response['cost_ms'] = $cost;

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	public function getPlayerByToken($token, $refreshTimout = true){
        $player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id, $refreshTimout);

        if(!$player){
            return [false, null, null, null];
        }
        $this->player = $player;
        return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByGameUsername($gameUsername){
		$player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $gameUsername, $player->username];
	}

	public function parseRequest(){
        $request_json = file_get_contents('php://input');
		$this->request = json_decode($request_json, true);
        $this->utils->debug_log("YGG SEAMLESS SERVICE raw:", $request_json, 'request', $this->request);
        //var_dump($this->request);
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
		$precision = floatval($this->game_api->getSystemInfo('game_amount_conversion_precision', 2));
		return round($balance,$precision);
	}

	public function isNumeric($amount){
		return is_numeric($amount);
	}

	public function isParametersValid($data){
		return true;
	}

	public function generatePlayerToken($gamePlatformId){
        $this->utils->debug_log("YGG SEAMLESS SERVICE: (bet)");

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}

		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

	public function testGenerateToken($gamePlatformId){

        $this->utils->debug_log("YGG SEAMLESS SERVICE: (testGenerateToken)");

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}

		$arr = $this->request;


		$token=$this->game_api->generatePlayerToken($arr['username']);

		echo "<br>token: ".$token;
	}


}///END OF FILE////////////