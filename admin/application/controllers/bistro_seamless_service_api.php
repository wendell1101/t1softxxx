<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * http://admin.brl.staging.smash.t1t.in/bistro_seamless_service_api/[API ID]
 */
class Bistro_seamless_service_api extends BaseController {

    const SUCCESS = 0;

    //error codes
	const ERROR_SERVICE_NOT_AVAILABLE = 1;
	const ERROR_INVALID_SIGN = 2;
	const ERROR_INVALID_PARAMETERS = 3;
	const ERROR_INSUFFICIENT_BALANCE = 4;
	const ERROR_SERVER = 5;
	const ERROR_CANNOT_FIND_PLAYER = 6;
	const ERROR_TRANSACTION_ALREADY_EXIST = 7;
	const ERROR_IP_NOT_ALLOWED = 8;
	const ERROR_BET_DONT_EXIST = 9;
	const ERROR_GAME_UNDER_MAINTENANCE = 10;
	const ERROR_MAXIMUM_PAYOUTS = 11;

	const MAXIMUM_PAYOUTS = 20;

	const ERROR_CODES = [
		self::ERROR_SERVICE_NOT_AVAILABLE,
		self::ERROR_INVALID_SIGN,
		self::ERROR_INVALID_PARAMETERS,
		self::ERROR_INSUFFICIENT_BALANCE,
		self::ERROR_SERVER,
		self::ERROR_CANNOT_FIND_PLAYER,
		self::ERROR_TRANSACTION_ALREADY_EXIST,
		self::ERROR_IP_NOT_ALLOWED,
		self::ERROR_BET_DONT_EXIST,
		self::ERROR_GAME_UNDER_MAINTENANCE,
		self::ERROR_MAXIMUM_PAYOUTS,
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
		self::ERROR_GAME_UNDER_MAINTENANCE=>503,
		self::ERROR_MAXIMUM_PAYOUTS=>400,
	];

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;

    private $transaction_for_fast_track = null;

	private $headers;

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','bistro_transactions', 'ip'));

		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_records = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (__construct)", $this->request);

		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (REQUEST_URI)", $_SERVER['REQUEST_URI']);
	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

        if(!$this->game_api){
			$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

        $this->currency = $this->game_api->getCurrency();
		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (initialize) ERROR currency: ", $this->currency);
        $this->bistro_transactions->tableName = $this->game_api->original_transactions_table;

		return true;
	}

	public function player_info($gamePlatformId=null){
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (balance)");

		$externalResponse = $this->externalQueryResponse();

		$callType = 'player_info';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'sign'=>'required',
			'token'=>'required'
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

            if (!$this->game_api->validateWhiteIP()) {
                $callType = 'validateWhiteIP';
                throw new Exception(self::ERROR_IP_NOT_ALLOWED);
            }

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if($this->game_api->trigger_player_info_error_response<>0){
				throw new Exception($this->game_api->trigger_player_info_error_response);
			}

			// get player details
			$token = $this->request['token'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

			$player_id = $player->player_id;

			$success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$player_username,
				&$balance) {

				$balance = $this->getPlayerBalance($player_username, $player_id);
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
			$currency = $this->currency;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['is_success'] = $success;
		$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;
		$externalResponse['balance'] = $this->formatBalance($balance);
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function bet($gamePlatformId=null){
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (bet)");

		$externalResponse = $this->externalQueryResponse();

		$callType = 'bet';
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
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			'amount'=>'required|isNumeric|nonNegative',
			// 'amount'=>'isNumeric',
			'currency'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'unique_id'=>'required',
			'sign'=>'required',
			'game_code'=>'required',
			// 'number'=>'required', number must not be required as per game provider
			// 'amount'=>'nonNegative'
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

            if (!$this->game_api->validateWhiteIP()) {
                $callType = 'validateWhiteIP';
                throw new Exception(self::ERROR_IP_NOT_ALLOWED);
            }

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			$currency = $this->currency;

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if($this->game_api->trigger_bet_error_response<>0){
				throw new Exception($this->game_api->trigger_bet_error_response);
			}

			// get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
			$params['bet_id'] = $this->request['bet_id'];
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['sign'] =  $this->request['sign'];
			$player_id = $params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;
			// $params['number'] = $this->request['number'];

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("BISTRO SEAMLESS SERVICE API lockAndTransForPlayerBalance bet",
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
				throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);// to ask if what to return if trans already exist
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['is_success'] = $success;
		$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;
		$externalResponse['balance'] = $this->formatBalance($balance);
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function payout($gamePlatformId=null){
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (payout)");

		$externalResponse = $this->externalQueryResponse();

		$callType = 'payout';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$currency = null;
		$previous_balance = $after_balance = 0;
		$betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$additionalResponse = [];
		$rules = [
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			'amount'=>'required|isNumeric|nonNegative',
			// 'amount'=>'isNumeric',
			'currency'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'unique_id'=>'required',
			'sign'=>'required',
			'game_code'=>'required',
			// 'amount'=>'nonNegative'
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

            if (!$this->game_api->validateWhiteIP()) {
                $callType = 'validateWhiteIP';
                throw new Exception(self::ERROR_IP_NOT_ALLOWED);
            }

			//if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				//throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			//}

			$currency = $this->currency;

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if($this->game_api->trigger_payout_error_response<>0){
				throw new Exception($this->game_api->trigger_payout_error_response);
			}

            // get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
			$params['bet_id'] = $this->request['bet_id'];
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			// $params['number'] = $this->request['number']; as per game provider we should remove this parameters
			$params['sign'] =  $this->request['sign'];
			$player_id = $params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				&$betExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("BISTRO SEAMLESS SERVICE API lockAndTransForPlayerBalance payout",
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

			if($isAlreadyExists){
				throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);// to ask if what to return if trans already exist
			}

			if(!$betExist){
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $after_balance;

		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['is_success'] = $success;
		$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;
		$externalResponse['balance'] = $this->formatBalance($balance);
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function batch_payout($gamePlatformId=null){
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (payout)");

		$externalResponse = $this->externalQueryResponse();

		$callType = 'batch_payout';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$gameUsername = null;
		$success = false;
		$currency = null;
		$previous_balance = $after_balance = 0;
		$betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$additionalResponse = [];
		$rules = [
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'payouts'=>'required'
		];

		$over_all_success = true;

		$err_unique_id = array();

		$is_exception = false;


		$payout_response_arr = array();

		$no_of_failed_payouts = 0;

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

            if (!$this->game_api->validateWhiteIP()) {
                $callType = 'validateWhiteIP';
                throw new Exception(self::ERROR_IP_NOT_ALLOWED);
            }

			//if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				//throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			//}

			$currency = $this->currency;

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if($this->game_api->trigger_payout_error_response<>0){
				throw new Exception($this->game_api->trigger_payout_error_response);
			}

			$payouts = $this->request['payouts'];

			if(count($payouts) > self::MAXIMUM_PAYOUTS) {
				throw new Exception(self::ERROR_MAXIMUM_PAYOUTS);
			}


			foreach($payouts as $payout) {

				$success = true;

				$payout_response = array();

				// get player details
				$params['username'] = $gameUsername = $payout['username'];
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);

				if(!$playerStatus){
					$success = false;
					$over_all_success = false;
					$errorCode = self::ERROR_CANNOT_FIND_PLAYER;

					$payout_response['is_success'] = $success;
					$payout_response['err_msg'] = $this->getErrorSuccessMessage($errorCode);
					$payout_response['unique_id'] = $payout['unique_id'];


					$payout_response_arr[] = $payout_response;

					$err_unique_id[] = $payout['unique_id'];

					$no_of_failed_payouts++;

					continue;
				}

				// check if valid amount

				if(!is_numeric($payout['amount'])) {

					$success = false;
					$over_all_success = false;
					$errorCode = self::ERROR_INVALID_PARAMETERS;

					$payout_response['is_success'] = $success;
					$payout_response['err_msg'] = $this->getErrorSuccessMessage($errorCode);
					$payout_response['unique_id'] = $payout['unique_id'];


					$payout_response_arr[] = $payout_response;

					$err_unique_id[] = $payout['unique_id'];

					$no_of_failed_payouts++;

					continue;

				}

				$params['timestamp'] = $this->request['timestamp'];
				$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
				$params['merchant_code'] = $this->request['merchant_code'];
				$params['amount'] = $payout['amount'];
				$params['currency'] = $payout['currency'];
				$params['bet_id'] = $payout['bet_id'];
				$params['unique_id'] = $payout['unique_id'];
				$params['round_id'] = $payout['round_id'];
				$params['game_code'] = $payout['game_code'];
				// $params['number'] = $this->request['number']; as per game provider we should remove this parameters
				$params['sign'] =  $this->request['sign'];
				$player_id = $params['player_id'] = $player->player_id;
				$params['player_name'] = $player_username;
				$params['trans_type'] = $callType;

				$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
					$params,
					&$insufficient_balance,
					&$previous_balance,
					&$after_balance,
					&$isAlreadyExists,
					&$additionalResponse,
					&$betExist) {

					list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
					$this->utils->debug_log("BISTRO SEAMLESS SERVICE API lockAndTransForPlayerBalance batch_payout",
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

				if($isAlreadyExists){
					$success = false;
					$over_all_success = false;
					$errorCode = self::ERROR_TRANSACTION_ALREADY_EXIST;
				} else if(!$betExist){
					$success = false;
					$over_all_success = false;
					$errorCode = self::ERROR_BET_DONT_EXIST;
				} else if($trans_success==false){
					$success = false;
					$over_all_success = false;
					$errorCode = self::ERROR_SERVER;
				} else if($insufficient_balance){
					$success = false;
					$over_all_success = false;
					$errorCode = self::ERROR_INSUFFICIENT_BALANCE;
				}

				$balance = $after_balance;

				if(!$success) {
					$no_of_failed_payouts++;
					$over_all_success = false;
					$err_unique_id[] = $payout['unique_id'];
				} else {
					$errorCode = self::SUCCESS;
				}

				$payout_response['is_success'] = $success;
				$payout_response['err_msg'] = $this->getErrorSuccessMessage($errorCode);
				$payout_response['username'] = $gameUsername;
				if($success) {
					$payout_response['balance'] = $this->formatBalance($balance);
					$payout_response['currency'] = $currency;
				}
				$payout_response['unique_id'] = $payout['unique_id'];


				$payout_response_arr[] = $payout_response;

			}



		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$over_all_success = false;
			$is_exception = true;
		}

		if($over_all_success) {
			$errorCode = self::SUCCESS;
			$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
		} else {

			if($is_exception) {
				$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
			} else {
				$unique_ids = implode(",", $err_unique_id);
				$externalResponse['err_msg'] = "Error with a following unique id [$unique_ids]";
			}

		}

		if($no_of_failed_payouts > 0) {
			// as per game provider if 1 of the payout is success the outer is_success must be true
			// all failed, that must be false

			$total_payout_response = count($payout_response_arr);

			if($total_payout_response != $no_of_failed_payouts) {
				$over_all_success = true;
				$errorCode = self::SUCCESS;
			} else {
				$over_all_success = false;
			}
		}

		$externalResponse['is_success'] = $over_all_success;
		$externalResponse['payouts'] = $payout_response_arr;
		//

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode);
	}

	public function settle($gamePlatformId=null){
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (settle)");

		$externalResponse = $this->externalQueryResponse();

		$callType = 'settle';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$success = false;
		$previous_balance = $after_balance = 0;
		$rules = [
			'unique_id'=>'required',
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'game_code'=>'required',
			'round_id'=>'required',
			'sign'=>'required',
			'opencode'=>'required'
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

            if (!$this->game_api->validateWhiteIP()) {
                $callType = 'validateWhiteIP';
                throw new Exception(self::ERROR_IP_NOT_ALLOWED);
            }

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if($this->game_api->trigger_settle_error_response<>0){
				throw new Exception($this->game_api->trigger_payout_error_response);
			}

            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['sign'] =  $this->request['sign'];
			$params['trans_type'] = $callType;
			$params['currency'] = $this->currency;
			$params['opencode'] = isset($this->request['opencode'])?$this->request['opencode']:null;

			//insert transaction
			$this->insertIgnoreTransactionRecord($params, $previous_balance, $after_balance);

			if($this->game_api->enable_settle_by_queue){
				//process remote queue
				$this->load->library(['lib_queue']);

				//add it to queue job
				$params['transactions_table'] = $this->bistro_transactions->tableName;
				$callerType=Queue_result::CALLER_TYPE_ADMIN;
				$caller=$this->authentication->getUserId();
				$state='';
				$this->load->library(['language_function','authentication']);
				$lang=$this->language_function->getCurrentLanguage();
				$caller = $this->authentication->getUserId();
				$state = null;
				$lang = $this->language_function->getCurrentLanguage();
				$systemId = Queue_result::SYSTEM_UNKNOWN;
				$funcName = 'bistro_settle_round';
				$token =  $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
			}

			$success = true;
			$errorCode = self::SUCCESS;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['is_success'] = $success;
		$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode);
	}

	public function refund($gamePlatformId=null){
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (cancel)");

		$externalResponse = $this->externalQueryResponse();

		$callType = 'refund';
		$errorCode = self::ERROR_SERVER;
		$externalResponse = [];
		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$additionalResponse = [];
		$rules = [
			'timestamp'=>'required',
			'merchant_code'=>'required',
			'username'=>'required',
			'amount'=>'required|isNumeric|nonNegative',
			// 'amount'=>'isNumeric',
			'currency'=>'required',
			'bet_id'=>'required',
			'round_id'=>'required',
			'unique_id'=>'required',
			'sign'=>'required',
			'game_code'=>'required',
			// 'amount'=>'nonNegative'
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_SERVICE_NOT_AVAILABLE);
			}

            if (!$this->game_api->validateWhiteIP()) {
                $callType = 'validateWhiteIP';
                throw new Exception(self::ERROR_IP_NOT_ALLOWED);
            }

			if($this->external_system->isGameApiMaintenance($this->game_platform_id)){
				throw new Exception(self::ERROR_GAME_UNDER_MAINTENANCE);
			}

			$currency = $this->currency;

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_PARAMETERS);
			}

			if(!$this->isValidSign($this->request)){
				throw new Exception(self::ERROR_INVALID_SIGN);
			}

			if($this->game_api->trigger_refund_error_response<>0){
				throw new Exception($this->game_api->trigger_refund_error_response);
			}

			// get player details
            $params['username'] = $gameUsername = $this->request['username'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);

            if(!$playerStatus){
                throw new Exception(self::ERROR_CANNOT_FIND_PLAYER);
			}

            $params['timestamp'] = $this->request['timestamp'];
			$params['timestamp_parsed'] =  date('Y-m-d H:i:s', $params['timestamp']);
			$params['merchant_code'] = $this->request['merchant_code'];
			$params['amount'] = $this->request['amount'];
			$params['currency'] = $this->request['currency'];
			$params['bet_id'] = $this->request['bet_id'];
			$params['unique_id'] = $this->request['unique_id'];
            $params['round_id'] = $this->request['round_id'];
            $params['game_code'] = $this->request['game_code'];
			$params['sign'] =  $this->request['sign'];
			$player_id = $params['player_id'] = $player->player_id;
			$params['player_name'] = $player_username;
			$params['trans_type'] = $callType;

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
				&$betExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("BISTRO SEAMLESS SERVICE API lockAndTransForPlayerBalance bet",
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

			if($isAlreadyExists){
				throw new Exception(self::ERROR_TRANSACTION_ALREADY_EXIST);// to ask if what to return if trans already exist
			}

			if(!$betExist){
				throw new Exception(self::ERROR_BET_DONT_EXIST);
			}

			if($trans_success==false){
				throw new Exception(self::ERROR_SERVER);
			}

			if($insufficient_balance){
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			$success = true;
			$errorCode = self::SUCCESS;
            $balance = $after_balance;

		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['is_success'] = $success;
		$externalResponse['err_msg'] = $this->getErrorSuccessMessage($errorCode);
		$externalResponse['username'] = $gameUsername;
		$externalResponse['balance'] = $this->formatBalance($balance);
		$externalResponse['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	private function getTime(){
		$date = new DateTime();
		return gmdate('Y-m-d\TH:i:s\Z', $date->format('U'));
	}

	protected function isValidSign($request){

		$signKey=$this->game_api->getApiSignKey();
		$boolean_to_string_on_sign=false;
		list($sign, $signString)=$this->common_token->generateSign($request, $signKey, ['sign'], $boolean_to_string_on_sign);

		$requestSign=$request['sign'];

		$this->CI->utils->debug_log('sign string:'.$signString.', sign:'.$sign.', signKey:'.$signKey.', request sign:'.$requestSign);

		return $sign===$requestSign;
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){

			$rule_arr = explode("|",$rule);

			foreach($rule_arr as $rule_value) {
				if($rule_value=='required'&&!isset($request[$key])){
					$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (isValidParams) Missing Parameters: ". $key, $request, $rules);
					return false;
				}

				if($rule_value=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
					$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
					return false;
				}

				if($rule_value=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
					$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
					return false;
				}
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
			case self::ERROR_INVALID_SIGN:
				$message = lang('Invalid signature');
				break;
			case self::ERROR_INVALID_PARAMETERS:
				$message = lang('Invalid parameters');
				break;
            case self::ERROR_SERVICE_NOT_AVAILABLE:
                $message = lang('Service not available');
                break;
            case self::ERROR_INSUFFICIENT_BALANCE:
                $message = lang('Insufficient Balance');
                break;
			case self::ERROR_SERVER:
				$message = lang('Server Error');
				break;
			case self::ERROR_IP_NOT_ALLOWED:
				$message = lang('IP address is not allowed.');
				break;
			case self::ERROR_TRANSACTION_ALREADY_EXIST:
				$message = lang('Transactions already exists.');
				break;
			case self::ERROR_CANNOT_FIND_PLAYER:
				$message = lang('Cannot find player.');
				break;
			case self::ERROR_BET_DONT_EXIST:
				$message = lang('Bet dont exist.');
				break;
			case self::ERROR_GAME_UNDER_MAINTENANCE:
				$message = lang('Game under maintenance.');
				break;
			case self::ERROR_MAXIMUM_PAYOUTS:
				$message = lang('Too much payouts in requests.');
				break;
			default:
				$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (getErrorSuccessMessage) error: ", $code);
				$message = "Unknown";
				break;
		}

		return $message;
	}

	public function isValidAgent($agentId){

		if($this->game_api->agent_id==$agentId){
			return true;
		}
		$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (isValidAgent)", $agentId);
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

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        if($error_code<>self::SUCCESS){
            $this->utils->error_log("BISTRO SEAMLESS SERVICE API: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);
        }else{
            $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (handleExternalResponse)", $response, 'error_code', $error_code);
        }

		$httpStatusCode = 400;

		if(isset($error_code) && array_key_exists($error_code, self::HTTP_STATUS_CODE_MAP)){
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$error_code];
		}

		//add request_id
		if(empty($response)){
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);
		$response['request_id'] = $this->utils->getRequestId();
		$response['cost_ms'] = $cost;

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	public function getPlayerByToken($token){
		$username = $this->game_api->decrypt($token);
		$this->utils->debug_log("ICONIC SEAMLESS SERVICE: (getPlayerByToken)", $username);
		$player = $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);

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
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);
		if(($trans_record['trans_type']=='payout' || $trans_record['trans_type']=='batch_payout') && $this->game_api->flag_bet_transaction_settled){
			//mark bet as settled
			$this->CI->bistro_transactions->flagBetTransactionSettled($trans_record);
		}
		return $this->CI->bistro_transactions->insertIgnoreRow($trans_record);
	}

	public function makeTransactionRecord($raw_data){
		$data = [];
		$data['username'] 			= isset($raw_data['username'])?$raw_data['username']:null;//string
		$data['timestamp'] 			= isset($raw_data['timestamp'])?$raw_data['timestamp']:null;//string
		$data['timestamp_parsed'] 	= isset($raw_data['timestamp_parsed'])?$raw_data['timestamp_parsed']:null;//datetime
		$data['merchant_code'] 		= isset($raw_data['merchant_code'])?$raw_data['merchant_code']:null;//string
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;//double
		$data['currency'] 			= isset($raw_data['currency'])?$raw_data['currency']:null;//string
		$data['game_code'] 			= isset($raw_data['game_code'])?$raw_data['game_code']:null;//string
		//$data['unique_id'] 			= isset($raw_data['unique_id'])?$raw_data['unique_id']:null;//string
		$data['bet_id'] 			= isset($raw_data['bet_id'])?$raw_data['bet_id']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string
		//$data['sign'] 				= isset($raw_data['sign'])?$raw_data['sign']:null;//string
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;//string
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;//string
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;
		$data['game_platform_id'] 	= $this->game_platform_id;
		$data['status'] 			= $this->getTransactionStatus($raw_data);
		$data['raw_data'] 			= @json_encode($this->request);//text
		$data['external_uniqueid'] 	= $this->generateUniqueId($raw_data);
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;
		$data['game_platform_id'] 	= $this->game_platform_id;
		$data['number'] 		= isset($raw_data['number'])?$raw_data['number']:null;
		$data['opencode'] 		= isset($raw_data['opencode'])?$raw_data['opencode']:null;
		$data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);

		return $data;
	}

	private function getTransactionStatus($data){

		if($data['trans_type']=='payout' || $data['trans_type']=='batch_payout'){
			return Game_logs::STATUS_SETTLED;
		}elseif($data['trans_type']=='refund'){
			return Game_logs::STATUS_REFUND;
		}elseif($data['trans_type']=='settle'){//initially pending if processed by cronjob then it will be flag as processed
			return Game_logs::STATUS_PENDING;
		}else{
			return Game_logs::STATUS_PENDING;
		}
	}

	private function generateUniqueId($data){
		return $data['unique_id'];
	}

	public function getErrorCode($code){

		if(!in_array($code, self::ERROR_CODES)){
			$this->utils->error_log("BISTRO SEAMLESS SERVICE API getErrorCode UNKNOWN ERROR:", $this->request, $code);
			return self::UNKNOWN_ERROR;
		}

		return $code;
	}

	public function parseRequest(){
        $request_json = file_get_contents('php://input');
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API raw:", $request_json);
		$this->request = json_decode($request_json, true);
		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = BISTRO_SEAMLESS_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('t1lottery_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function formatBalance($balance){
		$precision = intval($this->game_api->getSystemInfo('conversion_precision', 2));
		return bcdiv($balance, 1, $precision);
	}

	public function isNumeric($amount){
		return is_numeric($amount);
	}

	public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance){
		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmount)", $params, $previousBalance, $afterBalance);

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

		if($params['trans_type']=='bet'){
			$mode = 'debit';
		}elseif($params['trans_type']=='payout' || $params['trans_type']=='batch_payout'){
			$mode = 'credit';
		}elseif($params['trans_type']=='refund'){
			$mode = 'credit';
			$flagrefunded = true;
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		if($amount<>0){

			//get and process balance
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);

			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}

			}else{
				$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//check if bet transaction exists
			if($params['trans_type']=='refund' || $params['trans_type']=='payout' || $params['trans_type']=='batch_payout'){
				$flagrefunded = true;
				$check_bet_params = ['bet_id'=>$params['bet_id'], 'round_id'=>$params['round_id'], 'player_id'=>$player_id, 'trans_type'=>'bet'];
				$betExist = $this->CI->bistro_transactions->getTransactionByParamsArray($check_bet_params);

				if(empty($betExist)){
					$additionalResponse['betExist']=false;
					$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist", 'betExist', $betExist, 'params',$params, 'check_bet_params', $check_bet_params);
					$afterBalance = $previousBalance;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}

				$additionalResponse['betExist']=true;
			}

			if($params['trans_type']=='refund'){
				$this->CI->bistro_transactions->flagTransactionRefunded($betExist['external_uniqueid']);
			}

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}

			$success = $this->transferGameWallet($player_id, $this->game_platform_id, $mode, $amount);

			if(!$success){
				$this->utils->error_log("BISTRO SEAMLESS SERVICE API: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
			}

		}else{
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;

				//insert transaction
				$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
			}else{
				$success = false;
			}
		}

		return array($success,
						$previousBalance,
						$afterBalance,
						$insufficientBalance,
						$isAlreadyExists,
						$additionalResponse,
						$isTransactionAdded);
	}

	public function getPlayerBalance($playerName, $player_id){
		$get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
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
        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (bet)");

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}

		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

	public function testGenerateSign($gamePlatformId){

        $this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (testGenerateSign)");

		/*$rawjson = '{
			"token": "WUtzeHZjc0RvM0JvcHNneWtzcklOZz09",
			"unique_id": "34d3b48d-5c4a-4a71-af52-339a4a74da96",
			"merchant_code": "3d9b1a89471f44cdb9b8b71a7d64ddba",
			"timestamp": 1626665637,
			"sign": "6e784d6b1b46b0716abd81f211cc79c8e3ed4018"
		}';*/

		$arr = $this->request;

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}

		$signKey=$this->game_api->getApiSignKey();
		$boolean_to_string_on_sign=false;
		$sign=$this->game_api->generateSignatureByParams($arr, ['sign']);

		echo "<br>sign: ".$sign;
	}

    public function isIPAllowed(){
		return true;
        $success=false;

        $this->backend_api_white_ip_list=$this->utils->getConfig('backend_api_white_ip_list');

        $success=$this->ip->checkWhiteIpListForAdmin(function ($ip, &$payload){
			$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (isIPAllowed)", $ip);
            if($this->ip->isDefaultWhiteIP($ip)){
                $this->utils->debug_log('it is default white ip', $ip);
                return true;
            }
            foreach ($this->backend_api_white_ip_list as $whiteIp) {
                if($this->utils->compareIP($ip, $whiteIp)){
                    $this->utils->debug_log('found white ip', $whiteIp, $ip);
                    //found
                    return true;
                }
            }
            //not found
            return false;
        }, $payload);

		$this->utils->debug_log("BISTRO SEAMLESS SERVICE API: (isIPAllowed)", $success);
        return $success;
    }

}///END OF FILE////////////