<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Habanero_service_api extends BaseController {

	const SUCCESS = 200;

	const UNKNOWN = 100;
	const INVALID_TOKEN = 101;
	const INVALID_PASSKEY = 102;
	const CANNOT_FIND_PLAYER = 103;
	const INSUFFICIENT_FUNDS = 104;
	const ERROR_CREDIT = 105;
	const ERROR_DEBIT = 106;
	const ERROR_REFUND = 107;
	const ERROR_RECREDIT = 108;
	const INVALID_TRANSFERID = 109;
	const INVALID_TRANSACTION = 110;
	const ERROR_DEBITCREDIT = 111;
	const ERROR_IP_NOT_ALLOWED = 112;
    const ERROR_MAINTENANCE_OR_DISABLED = 113;
	const INVALID_CURRENCY = 114;
	const BET_NOT_EXIST = 115;

	const GAME_STATE_MODE_ROUND_START = 1;
	const GAME_STATE_MODE_ROUND_END = 2;
	const GAME_STATE_MODE_ROUND_EXPIRED = 3;
	const GAME_STATE_MODE_ROUND_CONTINUE = 0;


	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::UNKNOWN => 500,
		self::INVALID_TOKEN => 200,
		self::INVALID_PASSKEY => 200,
		self::CANNOT_FIND_PLAYER => 200,
		self::INSUFFICIENT_FUNDS => 200,
		self::ERROR_CREDIT => 200,
		self::ERROR_DEBIT => 200,
		self::ERROR_REFUND => 200,
		self::ERROR_RECREDIT => 200,
		self::INVALID_TRANSFERID => 200,
		self::INVALID_TRANSACTION => 200,
		self::ERROR_DEBITCREDIT => 200,
		self::INVALID_CURRENCY => 200,
		self::ERROR_IP_NOT_ALLOWED => 401,
		self::ERROR_MAINTENANCE_OR_DISABLED => 503,
		self::BET_NOT_EXIST => 200,
	];

	const DONE_CREDIT = 301;
	const DONE_DEBIT = 302;
	const ALREADY_CREDITED = 303;
	const DONE_RECREDIT = 304;

	public $request;
	public $passkey;
	public $trans_ids;
	public $transfer_ids;
	public $host_name;
	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $player;
	public $trans_records;
    public $uniqueid_of_seamless_service;
    public $use_monthly_transactions_table;
    public $previous_table;
    public $force_check_previous_transactions_table;

    private $transactions_for_fast_track = [];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','habanero_transactions','player_model','external_system', 'game_provider_auth'));
		$this->host_name =  $_SERVER['HTTP_HOST'];

		//$this->getValidPlatformId();
		$this->parseRequest();

		$this->retrieveHeaders();

		$this->passkey = $this->request->auth->passkey;

		$this->transfer_ids = $this->trans_ids = [];
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (__construct)", $this->request);
	}

	public function testGetPlayerByToken(){

		$result = $this->common_token->getPlayerCompleteDetailsByToken($_POST['token'], $this->game_platform_id, false);
		var_dump($result);

		exit;
	}

	public function testInsertIgnoreRow(){
		$data = [
			'keyname'=>'SGAllForOneXXXXX',
			'type'=>'fundtransferrequest',
			'external_uniqueid'=>'refundu3VxRId32xKMr5snfta4xxx1s'
		];
		$result = $this->habanero_transactions->insertIgnoreRow($data);
		var_dump($result);

		exit;
	}

	public function initialize($gamePlatformId){
		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
		}

		$this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

		if(!$this->game_api){
			return false;
		}

		$this->habanero_transactions->tableName = $this->game_api->original_transactions_table;
        $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
        $this->previous_table = $this->game_api->previous_table;
        $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;

		return true;
	}

    public function getTransaction($transferid) {
        $transaction = $this->habanero_transactions->getTransaction($transferid);
        
        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($transaction)) {
                $transaction = $this->habanero_transactions->getTransaction($transferid, $this->previous_table);
            }
        }
        
        return $transaction;
    }

    public function isTransactionExists($where) {
        $is_exists = $this->habanero_transactions->isTransactionExistCustom($where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exists) {
                $is_exists = $this->habanero_transactions->isTransactionExistCustom($where, $this->previous_table);
            }
        }

        return $is_exists;
    }

	public function auth($gamePlatformId=null){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (auth)");
		if(!$this->initialize($gamePlatformId)){
			$externalResponse['playerdetailresponse'] = $this->playerResponse();
			$externalResponse['playerdetailresponse']['status']['message'] = $this->getErrorSuccessMessage(self::UNKNOWN);
			return $this->handleExternalResponse(false, 'query', $this->request, $this->trans_ids, $externalResponse, [], self::UNKNOWN);
		}
		//initialize response
		$externalResponse['playerdetailresponse'] = $this->playerResponse();

		$fields = [];

		try {

			if(!$this->isValidPassKey($this->passkey)){
				throw new Exception(self::INVALID_PASSKEY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_MAINTENANCE_OR_DISABLED);
            }

			list($player_status, $player, $game_username, $playerName) = $this->getPlayerByToken($this->request->playerdetailrequest->token, false);

			if(!$player_status){
				$externalResponse['playerdetailresponse']['status']['message'] 	= $this->getErrorSuccessMessage(self::INVALID_TOKEN);
				return $this->handleExternalResponse(false, 'auth', $this->request, $this->trans_ids, $externalResponse, [], self::INVALID_TOKEN);
			}

			$fields = [
				'player_id'		=> $player->player_id,
			];

			$externalResponse['playerdetailresponse']['status']['autherror'] 	= false;
			$externalResponse['playerdetailresponse']['status']['success'] 		= true;
			$externalResponse['playerdetailresponse']['accountid'] 				= $game_username;
			$externalResponse['playerdetailresponse']['accountname'] 			= $game_username;
			$externalResponse['playerdetailresponse']['balance'] 				= $this->game_api->queryPlayerBalance($playerName)['balance'];

			if(isset($player->external_category) && $player->external_category){
				$externalResponse['playerdetailresponse']['segmentkey'] 			= $player->external_category;
			}

			return $this->handleExternalResponse($externalResponse['playerdetailresponse']['status']['success'], 'auth', $this->request, $this->trans_ids, $externalResponse, $fields, self::SUCCESS);

		} catch (Exception $error) {
			$error_code = $error->getMessage();
		    $this->utils->debug_log('HABANERO SEAMLESS '. __FUNCTION__ . ' ERROR EXCEPTION: ', $error_code);
			$externalResponse['playerdetailresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, 'auth', $this->request, $this->trans_ids, $externalResponse, $fields, $error_code);
		}
	}

	public function query($gamePlatformId=null){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (query)");

		if(!$this->initialize($gamePlatformId)){
			$externalResponse['fundtransferresponse'] = $this->queryResponse();
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::UNKNOWN);
			return $this->handleExternalResponse(false, 'query', $this->request, $this->trans_ids, $externalResponse, [], self::UNKNOWN);
		}

		$externalResponse['fundtransferresponse'] = $this->queryResponse();

		try {

			if(!$this->isValidPassKey($this->passkey)){
				throw new Exception(self::INVALID_PASSKEY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_MAINTENANCE_OR_DISABLED);
            }

			//PROCESS HERE
			$transfer_id = isset($this->request->queryrequest->transferid)?$this->request->queryrequest->transferid:'';

			if(!$transfer_id){
				throw new Exception(self::INVALID_TRANSFERID);
			}

			$transaction = $this->getTransaction($this->request->queryrequest->transferid);

			if(!$transaction){
				throw new Exception(self::INVALID_TRANSFERID);
			}else{
				if($transaction->fundinfo_amount<>$this->request->queryrequest->queryamount){
					throw new Exception(self::INVALID_TRANSACTION);
				}
			}

			$externalResponse['fundtransferresponse']['status']['success'] = true;

			return $this->handleExternalResponse($externalResponse['fundtransferresponse']['status']['success'], 'query', $this->request, $this->trans_ids, $externalResponse, [], self::SUCCESS);

		} catch (Exception $error) {
			$error_code = $error->getMessage();
		    $this->utils->debug_log('HABANERO SEAMLESS '. __FUNCTION__ . ' ERROR EXCEPTION: ', $error_code);
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, 'query', $this->request, $this->trans_ids, $externalResponse, [], $error_code);
		}
	}

	public function config($gamePlatformId=null){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (config)");

		if(!$this->initialize($gamePlatformId)){
			$externalResponse['configdetailresponse'] = $this->configResponse();
			$externalResponse['configdetailresponse']['status']['message'] = $this->getErrorSuccessMessage(self::UNKNOWN);
			return $this->handleExternalResponse(false, 'config', $this->request, $this->trans_ids, $externalResponse, [], self::UNKNOWN);
		}

		//initialize response
		$externalResponse['configdetailresponse'] = $this->configResponse();
		$defaultConfig = $this->configResponse();
		unset($defaultConfig['status']);

		try {

			if(!$this->isValidPassKey($this->passkey)){
				throw new Exception(self::INVALID_PASSKEY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_MAINTENANCE_OR_DISABLED);
            }

			list($player_status, $player, $game_username, $playerName) = $this->getPlayerByToken($this->request->configdetailrequest->token, true);

			if(!$player_status){
				$externalResponse['configdetailresponse']['status']['message'] 		= $this->getErrorSuccessMessage(self::INVALID_TOKEN);
				return $this->handleExternalResponse(false, 'auth', $this->request, $this->trans_ids, $externalResponse, [], self::INVALID_TOKEN);
			}

			$externalResponse['configdetailresponse']['status']['autherror'] 		= false;
			$externalResponse['configdetailresponse']['status']['success'] 		= true;

			foreach($defaultConfig as $key => $defaultConfigItem){
				$externalResponse['configdetailresponse'][$key] = isset($this->game_api->config[$key])?$this->game_api->config[$key]:$defaultConfigItem;
			}

			return $this->handleExternalResponse($externalResponse['configdetailresponse']['status']['success'], 'config', $this->request, $this->trans_ids, $externalResponse, [], self::SUCCESS);

		} catch (Exception $error) {
			$error_code = $error->getMessage();
		    $this->utils->debug_log('HABANERO SEAMLESS '. __FUNCTION__ . ' ERROR EXCEPTION: ', $error_code);
			$externalResponse['configdetailresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, 'config', $this->request, $this->trans_ids, $externalResponse, [], $error_code);
		}
	}

	public function endsession($gamePlatformId=null){
		$externalResponse = [];
		return $this->handleExternalResponse(false, 'endsession', $this->request, $this->trans_ids, $externalResponse, [], self::SUCCESS);
	}

	public function transaction($gamePlatformId=null){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (transaction)");

		$transactionType = 'transaction';

		if(!$this->initialize($gamePlatformId)){
			$externalResponse['fundtransferresponse'] = $this->fundsResponse();
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::UNKNOWN);
			return $this->handleExternalResponse(false, 'transaction', $this->request, $this->trans_ids, $externalResponse, [], self::UNKNOWN);
		}

		//initialize response
		$externalResponse['fundtransferresponse'] 		= $this->fundsResponse();

		$fields = [];

		try {

			if(!$this->isValidPassKey($this->passkey)){
				throw new Exception(self::INVALID_PASSKEY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_MAINTENANCE_OR_DISABLED);
            }

			$fundtransferrequest = $this->request->fundtransferrequest;

			$currency_of_funds = isset($fundtransferrequest->funds->fundinfo[0]->currencycode) ? $fundtransferrequest->funds->fundinfo[0]->currencycode : $fundtransferrequest->funds->refund->currencycode;

            $is_currency_valid = $this->isValidCurrency($currency_of_funds);

            if(!$is_currency_valid){
                throw new Exception(self::INVALID_CURRENCY);
            }

			//get player info
			list($player_status, $player, $game_username, $player_name) = $this->getPlayerByToken($this->request->fundtransferrequest->token, true);

			//check player info
			if(!$player_status){
				throw new Exception(self::INVALID_TOKEN);
			}

			$externalResponse['fundtransferresponse']['status']['autherror'] = false;

			$params = [
				'player_id'		=> $player->player_id,
				'player_name'	=> $player_name,
			];

			$fields['player_id'] = $player->player_id;

			$this->player_id = $player->player_id;

			if($fundtransferrequest->funds->debitandcredit == true){
				$transactionType = 'debitCreditTransaction';
				$params['transaction_type'] = $transactionType;
				$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;
				$this->handleDebitCredit($externalResponse, $fundtransferrequest, $params, $remoteActionType);
			}else{
				if($fundtransferrequest->isrefund){
					$transactionType = 'refundTransaction';
					$params['transaction_type'] = $transactionType;
					$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
					$this->handleRefund($externalResponse, $fundtransferrequest, $params, $remoteActionType);
				}elseif($fundtransferrequest->isrecredit){
					$transactionType = 'reCreditTransaction';
					$params['transaction_type'] = $transactionType;
					$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
					$this->handleReCredit($externalResponse, $fundtransferrequest, $params, $remoteActionType);
				}else{
					$transactionType = 'singleTransaction';
					$params['transaction_type'] = $transactionType;
					$this->handleSingleTransaction($externalResponse, $fundtransferrequest, $params);
				}
			}

			return $this->handleExternalResponse($externalResponse['fundtransferresponse']['status']['success'], $transactionType, $this->request, $this->transfer_ids, $externalResponse, $fields, self::SUCCESS);

		} catch (Exception $error) {
			$error_code = $error->getMessage();
		    $this->utils->debug_log('HABANERO SEAMLESS '. __FUNCTION__ . ' ERROR EXCEPTION: ', $error_code);
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, $transactionType, $this->request, $this->transfer_ids, $externalResponse, $fields, $error_code);
		}
	}

	/**
     * Trasfer mode handler
     */
	private function handleDebitCredit(&$externalResponse, $fundtransferrequest, $_params, $remoteActionType=Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (handleDebitCredit)", $_params);

		$player_id = $this->player->player_id;
		$player_name = $_params['player_name'];
		$previous_balance = $after_balance = 0;
		$insufficient_balance = false;
		$trans_success = false;
		$previous_balance = 0;
		$after_balance = 0;

		$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$remoteActionType,
			$player_name,
			$fundtransferrequest,
			$_params,
			&$insufficient_balance,
			&$previous_balance,
			&$after_balance,
			&$isAlreadyExists,
			&$isToRefundExists,
			&$isToRecreditExists,
			&$additionalResponse) {
			
			$bet_amount = (isset($fundtransferrequest->funds->fundinfo[0]->amount) && $fundtransferrequest->funds->fundinfo[0]->amount < 0) ? $fundtransferrequest->funds->fundinfo[0]->amount : 0;
			$payout_amount = (isset($fundtransferrequest->funds->fundinfo[1]->amount) && $fundtransferrequest->funds->fundinfo[1]->amount >= 0) ? $fundtransferrequest->funds->fundinfo[1]->amount : 0;

			/* 
			if($remoteActionType  == Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT){
				
				if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
					$this->wallet_model->setGameProviderBetAmount(abs($bet_amount));
				}
				if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
					$this->wallet_model->setGameProviderPayoutAmount($payout_amount);
				}
			} */

			foreach($fundtransferrequest->funds->fundinfo as $transactionRequest){
				$params = [];
				$params['player_id'] 	= $player_id;
				$params['amount'] 		= $transactionRequest->amount;
				$params['transfer_id'] 	= $transactionRequest->transferid;
				$params['initialdebittransferid'] 	= $transactionRequest->initialdebittransferid;
				$params['player_name'] 	= $player_name;
				$params['transaction_type']  = $_params['transaction_type'];
				
				$bet_amount = ($params['amount'] < 0) ? $params['amount'] : 0;
				$payout_amount = ($params['amount'] >= 0) ? $params['amount'] : 0;

				$isEnd = false;
				if($transactionRequest->gamestatemode==self::GAME_STATE_MODE_ROUND_START){
					$remoteActionType = 'bet';
				}elseif($transactionRequest->gamestatemode==self::GAME_STATE_MODE_ROUND_END){
					$remoteActionType = 'payout';
					$isEnd = true;
				}else{
					$remoteActionType = 'payout';
				}

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType, $isEnd);

				if(!$trans_success){
					return $trans_success;
				}
			}

			return true;

		});

		if($trans_success == true){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['successdebit'] = true;
			$externalResponse['fundtransferresponse']['status']['successcredit'] = true;
			$externalResponse['fundtransferresponse']['status']['message'] = 'Success';
		}else{
            $is_bet_exists = isset($additionalResponse['is_bet_exists']) && $additionalResponse['is_bet_exists'] ? $additionalResponse['is_bet_exists'] : false;
            $error_code = self::ERROR_DEBITCREDIT;

            if (!$is_bet_exists) {
                $error_code = self::BET_NOT_EXIST;
            }

			$externalResponse['fundtransferresponse']['status']['success'] = false;
			$externalResponse['fundtransferresponse']['status']['successdebit'] = false;
			$externalResponse['fundtransferresponse']['status']['successcredit'] = false;
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
		}

		$externalResponse['fundtransferresponse']['status']['nofunds'] = $insufficient_balance;
		$externalResponse['fundtransferresponse']['balance'] = $after_balance;
		return;
	}

	public function handleRefund(&$externalResponse, $fundtransferrequest, $_params, $remoteActionType=Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (handleRefund)", $_params);

		$player_id = $this->player->player_id;
		$player_name = $_params['player_name'];
		$previous_balance = $after_balance = 0;
		$insufficient_balance = false;
		$trans_success = false;
		$previous_balance = 0;
		$after_balance = 0;
		$isAlreadyExists = false;
		$isToRefundExists = false;

		$transactionRequest = $fundtransferrequest->funds->refund;

		$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
				$player_name,
				$remoteActionType,
				$transactionRequest,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$isToRefundExists,
				&$isToRecreditExists,
				&$additionalResponse) {

			$params = [];
			$params['player_id'] 	= $player_id;
			$params['amount'] 		= $transactionRequest->amount;
			$params['transfer_id'] 	= $transactionRequest->transferid;
			$params['player_name'] 	= $player_name;
			$params['to_refund_id'] = $transactionRequest->originaltransferid;

			$isEnd = true;

			list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType, $isEnd);

			return $trans_success;
		});

		$externalResponse['fundtransferresponse']['balance'] = $after_balance;

		if($isAlreadyExists){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['refundstatus'] = "1";
			return;
		}

		if(!$isToRefundExists){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['refundstatus'] = "2";
			$externalResponse['fundtransferresponse']['status']['message'] = "Original request never debited. So closing record.";
			return;
		}

		if($trans_success == true){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['refundstatus'] = "1";
		}else{
			$externalResponse['fundtransferresponse']['status']['success'] = false;
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::ERROR_REFUND);
		}
		return;
	}

	public function handleReCredit(&$externalResponse, $fundtransferrequest, $_params, $remoteActionType){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (handleReCredit)", $_params);

		$player_id = $this->player->player_id;
		$player_name = $_params['player_name'];
		$previous_balance = $after_balance = 0;
		$insufficient_balance = false;
		$trans_success = false;
		$previous_balance = 0;
		$after_balance = 0;
		$isAlreadyExists = false;
		$isToRefundExists = false;

		$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$player_name,
			$fundtransferrequest,
			$remoteActionType,
			&$insufficient_balance,
			&$previous_balance,
			&$after_balance,
			&$isAlreadyExists,
			&$isToRefundExists,
			&$isToRecreditExists,
			&$additionalResponse) {

			foreach($fundtransferrequest->funds->fundinfo as $transactionRequest){

				$params = [];
				$params['player_id'] 	= $player_id;
				$params['amount'] 		= $transactionRequest->amount;
				$params['transfer_id'] 	= $transactionRequest->transferid;
				$params['player_name'] 	= $player_name;
				$params['to_recredit_id'] = $transactionRequest->originaltransferid;

				$isEnd = false;
				if(isset($transactionRequest->gamestatemode) && $transactionRequest->gamestatemode==self::GAME_STATE_MODE_ROUND_END){
					$isEnd = true;
				}

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType, $isEnd);

				if(!$trans_success){
					return $trans_success;
				}
			}
			return true;
		});

		if($isAlreadyExists){
			$externalResponse['fundtransferresponse']['balance'] = $previous_balance;
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			return;
		}

		if($trans_success == true){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::DONE_RECREDIT);
		}else{
			$externalResponse['fundtransferresponse']['status']['success'] = false;
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::ERROR_RECREDIT);
		}
		$externalResponse['fundtransferresponse']['balance'] = $after_balance;
		return;
	}

	# handling single transaction, jackpot and freespin independent api call
	public function handleSingleTransaction(&$externalResponse, $fundtransferrequest, $_params){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (handleSingleTransaction)", $_params);

		$player_id = $this->player->player_id;
		$player_name = $_params['player_name'];
		$previous_balance = $after_balance = 0;
		$insufficient_balance = false;
		$trans_success = false;
		$previous_balance = 0;
		$after_balance = 0;
		$isAlreadyExists = false;
		$isToRefundExists = false;

		$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$player_name,
			$fundtransferrequest,
			&$insufficient_balance,
			&$previous_balance,
			&$after_balance,
			&$isAlreadyExists,
			&$isToRefundExists,
			&$isToRecreditExists,
			&$additionalResponse) {

			foreach($fundtransferrequest->funds->fundinfo as $transactionRequest){
				$params = [];
				$params['player_id'] 	= $player_id;
				$params['amount'] 		= $transactionRequest->amount;
				$params['transfer_id'] 	= $transactionRequest->transferid;
				$params['player_name'] 	= $player_name;
                $params['is_single_transaction'] = true;

				$isEnd = false;
				if(isset($transactionRequest->gamestatemode) && $transactionRequest->gamestatemode==self::GAME_STATE_MODE_ROUND_END){
					$isEnd = true;
				}

				$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;

				if($this->utils->compareResultFloat($params['amount'], '>=', 0)){
					$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
					if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
						$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
					}
					$related_bet_transfer_id = $transactionRequest->initialdebittransferid;
					$related_uniqueid_of_seamless_service='game-'.$this->game_platform_id.'-'.$related_bet_transfer_id;

					if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
						$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
					}
				}

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType, $isEnd);

				if(!$trans_success){
					return $trans_success;
				}
			}

			return true;

		});

		if($trans_success == true){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['message'] = "";
		}else{
            $is_bet_exists = isset($additionalResponse['is_bet_exists']) && $additionalResponse['is_bet_exists'] ? $additionalResponse['is_bet_exists'] : false;
            $error_code = self::ERROR_DEBITCREDIT;

            if (!$is_bet_exists) {
                $error_code = self::BET_NOT_EXIST;
            }

			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
			$externalResponse['fundtransferresponse']['status']['success'] = false;
			$externalResponse['fundtransferresponse']['status']['nofunds'] = $insufficient_balance;
			if($insufficient_balance){
				$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::INSUFFICIENT_FUNDS);
			}
		}

		$externalResponse['fundtransferresponse']['balance'] = $after_balance;
		return;
	}

	public function altfundsrequest($gamePlatformId=null){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (altfundsrequest)");

		if(!$this->initialize($gamePlatformId)){
			$externalResponse['fundtransferresponse'] = $this->fundsResponse();
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::UNKNOWN);
			return $this->handleExternalResponse(false, 'altfundsrequest', $this->request, $this->trans_ids, $externalResponse, [], self::UNKNOWN);
		}

		//initialize response
		$externalResponse['fundtransferresponse'] 		= $this->fundsResponse();

		$fields = [];

		try {

			if(!$this->isValidPassKey($this->passkey)){
				throw new Exception(self::INVALID_PASSKEY);
			}

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::ERROR_IP_NOT_ALLOWED);
			}

            if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::ERROR_MAINTENANCE_OR_DISABLED);
            }

			//get player info
			list($player_status, $player, $game_username, $player_name) = $this->getPlayerByGameUsername($this->request->altfundsrequest->accountid);

			//check player info
			if(!$player_status){
				throw new Exception(self::INVALID_TOKEN);
			}

			unset($externalResponse['fundtransferresponse']['status']['autherror']);
			$fundtransferrequest = $this->request->altfundsrequest;

			$params = [
				'player_id'		=> $player->player_id,
				'player_name'	=> $player_name,
			];

			$fields['player_id'] = $player->player_id;

			$this->player_id = $player->player_id;

			$this->handleAltfundsrequest($externalResponse, $fundtransferrequest, $params);

			return $this->handleExternalResponse($externalResponse['fundtransferresponse']['status']['success'], 'transaction', $this->request, $this->trans_ids, $externalResponse, $fields, self::SUCCESS);

		} catch (Exception $error) {
			$error_code = $error->getMessage();
		    $this->utils->debug_log('HABANERO SEAMLESS '. __FUNCTION__ . ' ERROR EXCEPTION: ', $error_code);
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage($error_code);
			return $this->handleExternalResponse(false, 'altfundsrequest', $this->request, $this->trans_ids, $externalResponse, $fields, $error_code);
		}
	}

	public function handleAltfundsrequest(&$externalResponse, $fundtransferrequest, $_params){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (handleAltfundsrequest)", $_params);

		$player_id = $this->player->player_id;
		$player_name = $_params['player_name'];
		$previous_balance = $after_balance = 0;
		$insufficient_balance = false;
		$trans_success = false;
		$previous_balance = 0;
		$after_balance = 0;
		$isAlreadyExists = false;
		$isToRefundExists = false;

		$trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			$player_name,
			$fundtransferrequest,
			&$insufficient_balance,
			&$previous_balance,
			&$after_balance,
			&$isAlreadyExists,
			&$isToRefundExists,
			&$isToRecreditExists,
			&$additionalResponse) {

			$remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;

			$params = [];
			$params['player_id'] 	= $player_id;
			$params['amount'] 		= $fundtransferrequest->amount;
			$params['transfer_id'] 	= $fundtransferrequest->transferid;
			$params['player_name'] 	= $player_name;

			$isEnd = true;

			if($remoteActionType  == Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT){
				
				if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
					$this->wallet_model->setGameProviderBetAmount(0);
				}
				if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
					$this->wallet_model->setGameProviderPayoutAmount(0);
				}
			} 

			list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType, $isEnd);

			return $trans_success;

		});

		if($trans_success == true){
			$externalResponse['fundtransferresponse']['status']['success'] = true;
			$externalResponse['fundtransferresponse']['status']['message'] = "Success.";
		}else{
			$externalResponse['fundtransferresponse']['status']['message'] = $this->getErrorSuccessMessage(self::ERROR_CREDIT);
			$externalResponse['fundtransferresponse']['status']['success'] = false;
		}

		$externalResponse['fundtransferresponse']['balance'] = $after_balance;
		return;
	}

    /**
     * Response template
     */
    public function playerResponse(){
        return array(
			"status" =>
				array(
					"success" => false,
					"nofunds" => false,
					"successdebit" => false,
					"successcredit" => false,
					"message" => "",
					"autherror" => true,
					"refundstatus" => 0
				),
			"accountid" => "",
			"accountname" => "",
			"balance" => 0,
			"currencycode" => ($this->game_api && isset($this->game_api->currency)?$this->game_api->currency:null),
			"country" => null,
			"fname" => null,
			"lname" => null,
			"email" => null,
			"tel" => null,
			"telalt" => null
		);
    }

    public function fundsResponse(){
		return array("status" =>
			array(
				"success" => false,
				"nofunds" => false,
				"successdebit" => false,
				"successcredit" => false,
				"message" => null,
				"autherror" => true,
				"refundstatus" => 0
			),
			"balance" => "",
			"currencycode" => ($this->game_api && isset($this->game_api->currency)?$this->game_api->currency:null),
			"remoteid" => null,
			"remotetransferid" => ""
		);
	}

    public function configResponse(){
		return array("status" =>
			array(
				"success" => false,
				"message" => null,
				"autherror" => true,
			),
			"minstake" => 1.0,
			"maxstake" => 5.0,
			"stakeincrement" => "0.10|0.50|1|5|10",
			"levelincrement" => "1|5|10",
			"defaultstake" => 0.50,
			"maxpaylimit" => 0.0,
			"mininsidestake" => 0.0,
			"maxinsidestake" => 0.0,
			"minoutsidestake" => 0.0,
			"maxoutsidestake" => 0.0
		);
	}

    public function queryResponse(){
		return array("status" =>
			array(
				"success" => false,
				"message" => null,
			)
		);
	}

	public function externalQueryResponse(){
		return array(
			"fundtransferresponse" => array(
				"status" => array(
					"success" => false
				),
				"remotetransferid" => null
			)
		);
	}

	/**
     * Helper Functions
     */
	private function saveResponseResult($success, $callMethod, $params, $response, $statusText = null, $extra = null, $fields = [], $httpStatusCode = 200){
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$extra = array_merge((array)$extra,(array)$this->headers);

		return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id,
        	$flag,
        	$callMethod,
        	json_encode($params),
        	json_encode($response),
        	$httpStatusCode,
        	$statusText,
			is_array($extra)?json_encode($extra):$extra,
			$fields,
			false,
			null,
			$cost
        );
	}

	public function handleExternalResponse($status, $type, $data, $transfer_ids, $response, $fields = [], $errorCode = null){
		$transfer_ids = (array)$transfer_ids;
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (handleExternalResponse)", $response, 'transfer_ids',$transfer_ids);

		$errorCode=(int)$errorCode;

		$httpStatusCode = 200;

		if(!empty($errorCode)){
			if(array_key_exists($errorCode, self::HTTP_STATUS_CODE_MAP)){
				$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$errorCode];
			}
		}

		$response_result_id = $this->saveResponseResult($status, $type, $data, $response, null, null, $fields, $httpStatusCode);
		foreach($transfer_ids as $trans_id){
			$saved = $this->habanero_transactions->updateResponseResultIdByTransferId($trans_id, $response_result_id);

            if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (!$saved) {
                    $saved = $this->habanero_transactions->updateResponseResultIdByTransferId($trans_id, $response_result_id, $this->previous_table);
                }
            }

			if(!$saved){
				$this->utils->error_log("HABANERO SEAMLESS SERVICE: (handleExternalResponse) error updating transaction",
				'transfer_id', $trans_id, 'transfer_ids', $transfer_ids, 'response', $response);
			}
		}

        if(!empty($this->transactions_for_fast_track) && $this->utils->getConfig('enable_fast_track_integration') && $status) {
            $this->sendToFastTrack();
        }

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	public function getPlayerByToken($token, $refreshTimout = false){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (getPlayerByToken)", $token);
		if(strpos($token, 'token:')!== false){
			//using new token
			//decrypt
			$token = str_replace('token:','',$token);//remove prefix
			$username = $this->game_api->decrypt($token);
			$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (getPlayerByToken)", $username);
			$player = $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);

			if(!$player){
				return [false, null, null, null];
			}
			$this->player = $player;
			return [true, $player, $player->game_username, $player->username];
		}else{
			$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id, $refreshTimout);

			if(!$player){
				return [false, null, null, null];
			}
			$this->player = $player;
			return [true, $player, $player->game_username, $player->username];
		}

	}

	public function getPlayerByGameUsername($gameUsername){
		$player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $gameUsername, $player->username];
	}

	public function insertTransactionRecord($transfer_id, $balance_before = 0, $balance_after = 0, $flagrefunded=false){
		$result = false;
		$this->trans_records = $this->makeTransactionRecord($this->request);
        $this->transactions_for_fast_track = [];
		foreach($this->trans_records as $trans_record){
			if($transfer_id==$trans_record['fundinfo_transferid']){
				$trans_record['balance_after'] = $this->roundDownAmount($balance_after);
				$trans_record['balance_before'] = $this->roundDownAmount($balance_before);
				$trans_record['player_id'] = isset($this->player->player_id)?$this->player->player_id:null;
				$result = $this->CI->habanero_transactions->insertIgnoreRow($trans_record);

                if($result) {
                    $trans_record['id'] = $this->CI->habanero_transactions->getLastInsertedId();
                    $this->transactions_for_fast_track[] = $trans_record;
                }
				if($flagrefunded){
					$this->CI->habanero_transactions->flagGameinstanceRefunded($trans_record['gameinstanceid']);
				}
			}
		}

		return $result;
	}

	public function makeTransactionRecord($request){
		$request = $this->objectToArray($request);
		$recordsets = $record = [];

		$isRetry = false;

		if(isset($request['fundtransferrequest']['funds']['refund'])){
			$isRetry = true;
		}

		if($request['type']=='altfundsrequest'){
			$recordsets[] = $request['altfundsrequest'];
		}else{
			if($isRetry){
				$recordsets[] = isset($request['fundtransferrequest']['funds']['refund'])?$request['fundtransferrequest']['funds']['refund']:[];
			}else{
				$recordsets = isset($request['fundtransferrequest']['funds']['fundinfo'])?$request['fundtransferrequest']['funds']['fundinfo']:[];
			}
		}

		foreach($recordsets as $key => $row){
			if($request['type']=='altfundsrequest'){
				$altfundsrequest = @$request['altfundsrequest'];
			}else{
				$fundtransferrequest = @$request['fundtransferrequest'];
			}

			$fundtransferrequest_game = @$request['fundtransferrequest']['gamedetails'];
			$temp = array(
						//'id' 									=> null,
						'type' 									=> isset($request['type'])?$request['type']:null,
						'dtsent' 								=> isset($request['dtsent'])?$request['dtsent']:null,
						'brandgameid' 							=> isset($request['basegame']['brandgameid'])?$request['basegame']['brandgameid']:null,
						'keyname' 								=> isset($request['basegame']['keyname'])?$request['basegame']['keyname']:null,
						'auth_brandid' 							=> isset($request['auth']['brandid'])?$request['auth']['brandid']:null,
						'auth_locale' 							=> isset($request['auth']['locale'])?$request['auth']['locale']:null,
						'auth_machinename' 						=> isset($request['auth']['machinename'])?$request['auth']['machinename']:null,
						'auth_passkey' 							=> isset($request['auth']['passkey'])?$request['auth']['passkey']:null,
						'auth_username' 						=> isset($request['auth']['username'])?$request['auth']['username']:null,
						'gameinstanceid' 						=> isset($fundtransferrequest['gameinstanceid'])?$fundtransferrequest['gameinstanceid']:null,
						'isrecredit' 							=> isset($fundtransferrequest['isrecredit'])?$fundtransferrequest['isrecredit']:null,
						'isrefund' 								=> isset($fundtransferrequest['isrefund'])?$fundtransferrequest['isrefund']:null,
						'isretry' 								=> isset($fundtransferrequest['isretry'])?$fundtransferrequest['isretry']:null,
						'retrycount' 							=> isset($fundtransferrequest['retrycount'])?$fundtransferrequest['retrycount']:null,
						'token' 								=> isset($fundtransferrequest['token'])?$fundtransferrequest['token']:null,
						'accountid' 							=> isset($fundtransferrequest['accountid'])?$fundtransferrequest['accountid']:null,
						'customplayertype' 						=> isset($fundtransferrequest['customplayertype'])?$fundtransferrequest['customplayertype']:null,
						'friendlygameinstanceid' 				=> isset($fundtransferrequest['friendlygameinstanceid'])?$fundtransferrequest['friendlygameinstanceid']:null,
						'funds_debitandcredit' 					=> isset($fundtransferrequest['funds']['debitandcredit'])?$fundtransferrequest['funds']['debitandcredit']:null,
						'fundinfo_amount' 						=> isset($row['amount'])?$this->roundDownAmount($row['amount']):null,
						'fundinfo_currencycode' 				=> isset($row['currencycode'])?$row['currencycode']:null,
						'fundinfo_dtevent' 						=> isset($row['dtevent'])?$row['dtevent']:null,
						'fundinfo_gamestatemode' 				=> isset($row['gamestatemode'])?$row['gamestatemode']:null,
						'fundinfo_initialdebittransferid' 		=> isset($row['initialdebittransferid'])?$row['initialdebittransferid']:null,
						'fundinfo_isbonus' 						=> isset($row['isbonus'])?$row['isbonus']:null,
						'fundinfo_jpcont' 						=> isset($row['jpcont'])?$this->roundDownAmount($row['jpcont']):null,
						'fundinfo_jpwin' 						=> isset($row['jpwin'])?$row['jpwin']:null,
						'fundinfo_jpid' 						=> isset($row['jpid'])?$row['jpid']:null,
						'fundinfo_transferid' 					=> isset($row['transferid'])?$row['transferid']:null,
						'fundinfo_originaltransferid' 			=> isset($row['originaltransferid'])?$row['originaltransferid']:null,
						'gamedetails_brandgameid' 				=> isset($fundtransferrequest_game['brandgameid'])?$fundtransferrequest_game['brandgameid']:null,
						'gamedetails_browser' 					=> isset($fundtransferrequest_game['browser'])?$fundtransferrequest_game['browser']:null,
						'gamedetails_channel' 					=> isset($fundtransferrequest_game['channel'])?$fundtransferrequest_game['channel']:null,
						'gamedetails_device'					=> isset($fundtransferrequest_game['device'])?$fundtransferrequest_game['device']:null,
						'gamedetails_friendlygameinstanceid' 	=> isset($fundtransferrequest_game['friendlygameinstanceid'])?$fundtransferrequest_game['brandgameid']:null,
						'gamedetails_gameinstanceid' 			=> isset($fundtransferrequest_game['gameinstanceid'])?$fundtransferrequest_game['gameinstanceid']:null,
						'gamedetails_gamesessionid' 			=> isset($fundtransferrequest_game['gamesessionid'])?$fundtransferrequest_game['gamesessionid']:null,
						'gamedetails_gametypeid' 				=> isset($fundtransferrequest_game['gametypeid'])?$fundtransferrequest_game['gametypeid']:null,
						'gamedetails_gametypename' 				=> isset($fundtransferrequest_game['gametypename'])?$fundtransferrequest_game['gametypename']:null,
						'gamedetails_keyname' 					=> isset($fundtransferrequest_game['keyname'])?$fundtransferrequest_game['keyname']:null,
						'gamedetails_name' 						=> isset($fundtransferrequest_game['name'])?$fundtransferrequest_game['name']:null,
						'bonusdetails_bonusbalanceid'			=> isset($fundtransferrequest['bonusdetails']['bonusbalanceid'])?$fundtransferrequest['bonusdetails']['bonusbalanceid']:null,
						'bonusdetails_couponid'					=> isset($fundtransferrequest['bonusdetails']['couponid'])?$fundtransferrequest['bonusdetails']['couponid']:null,
						'bonusdetails_coupontypeid'				=> isset($fundtransferrequest['bonusdetails']['coupontypeid'])?$fundtransferrequest['bonusdetails']['coupontypeid']:null,
						'bonusdetails_couponcode'				=> isset($fundtransferrequest['bonusdetails']['couponcode'])?$fundtransferrequest['bonusdetails']['couponcode']:null,
						'balance_before' 						=> 0,
						'balance_after' 						=> 0,
						'raw_data' 								=> json_encode($request),
						'is_valid_transaction' 					=> 1,
						'is_refunded' 							=> 0,
						'response_result_id' 					=> null,
						'trans_type' 							=> null,
						'player_id' 							=> 0,
						'elapsed_time' 							=> intval($this->utils->getExecutionTimeToNow()*1000),


						//altfundrequest
						'altcredittype' 						=> isset($row['altcredittype'])?$row['altcredittype']:null,
						'description' 							=> isset($row['description'])?$row['description']:null,
						'tournamentdetails_score' 				=> isset($row['tournamentdetails']) && isset($row['tournamentdetails']['score'])?$row['tournamentdetails']['score']:null,
						'tournamentdetails_rank' 				=> isset($row['tournamentdetails']) && isset($row['tournamentdetails']['rank'])?$row['tournamentdetails']['rank']:null,
						'tournamentdetails_tournamenteventid' 	=> isset($row['tournamentdetails']) && isset($row['tournamentdetails']['tournamenteventid'])?$row['tournamentdetails']['tournamenteventid']:null,

						//bonus
						'fundinfo_accounttransactiontype' 		=> isset($fundtransferrequest['accounttransactiontype'])?$fundtransferrequest['accounttransactiontype']:null,
						'fundinfo_gameinfeature' 				=> isset($fundtransferrequest['gameinfeature'])?$fundtransferrequest['gameinfeature']:null,
						'fundinfo_lastbonusaction' 				=> isset($fundtransferrequest['lastbonusaction'])?$fundtransferrequest['lastbonusaction']:null,


					);

			//altfundrequest overrides and temp populate data
			if($request['type']=='altfundsrequest'){
				$temp['accountid'] = isset($altfundsrequest['accountid'])?$altfundsrequest['accountid']:null;
				$temp['gamedetails_keyname'] = $temp['keyname'] = 'CreditAdjustment';
				$temp['gameinstanceid'] = $temp['fundinfo_transferid'];//make transfer id as instance id
				$temp['friendlygameinstanceid'] = $temp['gamedetails_friendlygameinstanceid'] = $temp['fundinfo_transferid'];//make transfer id as friendlyinstanceid
				$temp['fundinfo_isbonus'] = 0;
				$temp['fundinfo_jpcont'] = 0;
				$temp['fundinfo_jpwin'] = 0;
				$temp['isrecredit'] = 0;
				$temp['isrefund'] = 0;
				$temp['isretry'] = 0;
			}

			$temp['external_uniqueid'] = $temp['fundinfo_transferid'];

			$timestamp = strtotime($temp['fundinfo_dtevent']);
			$timestamp = date('Y-m-d H:i:s', $timestamp);
			$temp['fundinfo_dtevent_parsed'] = $timestamp;

			$timestamp = strtotime($temp['dtsent']);
			$timestamp = date('Y-m-d H:i:s', $timestamp);
			$temp['dtsent_parsed'] = $timestamp;


			if($request['type']=='altfundsrequest'){
				$temp['trans_type'] = 'credit adjustment';
			}else{
				if($temp['funds_debitandcredit']==true){
					if($temp['fundinfo_amount']>=0){
						$temp['trans_type'] = 'credit';
					}else{
						$temp['trans_type'] = 'debit';
					}
				}else{
					if($temp['fundinfo_gamestatemode']==0){
						$temp['trans_type'] = 'freespin';
					}elseif($temp['fundinfo_gamestatemode']==1){
						$temp['trans_type'] = 'single';
						if($temp['fundinfo_jpwin']){
							$temp['trans_type'] = 'jackpot';
						}elseif($temp['fundinfo_isbonus']){
							$temp['trans_type'] = 'bonus';
						}elseif($temp['isretry'] && $temp['isrefund']){
							$temp['trans_type'] = 'refund';
						}elseif($temp['isretry'] && $temp['isrecredit']){
							$temp['trans_type'] = 'recredit';
						}
					}elseif($temp['fundinfo_gamestatemode']==2){
						$temp['trans_type'] = 'lastround';
						if($temp['fundinfo_jpwin']){
							$temp['trans_type'] = 'jackpot';
						}elseif($temp['fundinfo_isbonus']){
							$temp['trans_type'] = 'bonus';
						}elseif($temp['isretry'] && $temp['isrefund']){
							$temp['trans_type'] = 'refund';
						}elseif($temp['isretry'] && $temp['isrecredit']){
							$temp['trans_type'] = 'recredit';
						}
					}

					if($temp['fundinfo_amount']>=0){
						$temp['trans_type'] .= ' credit';
					}else{
						$temp['trans_type'] .= ' debit';
					}
				}
			}


			$record[] = $temp;
		}

		return $record;
	}

	/*public $soapClient;

	public function getGames(){
		$this->soapClient = new SoapClient(Habanero_service_api_config::WebServiceEndpoint . "?wsdl", array('trace' => 1));

		$gamesrequest = new Habanero_service_api_game_request();
		$response = $this->soapClient->GetGames(array(
				"req" => $gamesrequest
			));
			echo "<pre>";
			$response = $this->objectToArray($response);
			print_r($response);
		return;
	}*/

	public function getErrorSuccessMessage($code){
		$message = '';

		switch ($code) {
			case self::INVALID_TOKEN:
				$message = lang('Invalid Auth Token.');
				break;
			case self::INVALID_PASSKEY:
				$message = lang('Invalid Passkey.');
				break;
			case self::INVALID_CURRENCY:
				$message = lang('Invalid currency.');
				break;
			case self::CANNOT_FIND_PLAYER:
				$message = lang('Cannot find player.');
				break;
			case self::INSUFFICIENT_FUNDS:
				$message = lang('Insufficient funds.');
				break;
			case self::ERROR_DEBITCREDIT:
				$message = lang('Error enountered when processing debit and credit.');
				break;
			case self::ERROR_CREDIT:
				$message = lang('Cannot credit amount.');
				break;
			case self::ERROR_DEBIT:
				$message = lang('Cannot debit amount.');
				break;
			case self::ERROR_REFUND:
				$message = lang('Could Not Refund.');
				break;
			case self::ERROR_RECREDIT:
				$message = lang('Could Not Recredit.');
				break;
			case self::ALREADY_CREDITED:
				$message = lang("Orignal Credit was done - so no need to do it again.");
				break;
			case self::DONE_RECREDIT:
				$message = lang("Original request never credited, so we did the re-credit");
				break;
			case self::DONE_CREDIT:
				$message = lang("Successfully credited an amount.");
				break;
			case self::DONE_DEBIT:
				$message = lang("Successfully debit an amount.");
				break;
			case self::INVALID_TRANSFERID:
				$message = lang("Invalid TransferID.");
				break;
			case self::INVALID_TRANSACTION:
				$message = lang("Invalid Transaction.");
				break;
			case self::ERROR_IP_NOT_ALLOWED:
				$message = lang("You IP is not allowed to access this API.");
				break;
			case self::ERROR_MAINTENANCE_OR_DISABLED:
				$message = lang("Game under maintenance or disabled.");
				break;
            case self::BET_NOT_EXIST:
				$message = lang("Bet not exist");
				break;
			default:
				$message = "Unknown";
				break;
		}

		return $message;
	}

	public function parseRequest(){
		$request_json = file_get_contents('php://input');

		$this->utils->debug_log("HABANERO SEAMLESS SERVICE raw:", $request_json);

		$this->request = json_decode($request_json);
		return;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = HABANERO_SEAMLESS_GAMING_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('habanero_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function isValidPassKey($key){
		if($this->game_api->getPassKey() <> $key){
			return false;
		}
		return true;
	}

	public function isValidCurrency($currency){
        if($this->game_api->currency <> $currency){
            return false;
        }
        return true;
    }

	public function objectToArray($obj) {
		if(is_object($obj)) $obj = (array) $obj;
		if(is_array($obj)) {
			$new = array();
			foreach($obj as $key => $val) {
				$new[$key] = $this->objectToArray($val);
			}
		}
		else $new = $obj;
		return $new;
	}

	public function isTransferIdRetry($transfer_id){
		return $this->habanero_transactions->isTransferIdRetry($transfer_id);
	}

	public function debitCreditAmountToWallet($request, &$previousBalance, &$afterBalance, $remoteActionType=Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT, $isEnd = false){
		$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount)", $request);

		//initialize params
		$player_id			= $request['player_id'];
		$transfer_id 		= $request['transfer_id'];
		$initialdebittransferid 		= $request['initialdebittransferid'];
		
		$amount 			= abs($request['amount']);
		$toRefundId			= isset($request['to_refund_id']) && !empty($request['to_refund_id']) ? $request['to_refund_id'] : null;
		$toReCreditId		= isset($request['to_recredit_id']) && !empty($request['to_recredit_id']) ? $request['to_recredit_id'] : null;
		$flagrefunded 		= false;
        $is_single_transaction = isset($request['is_single_transaction']) && $request['is_single_transaction'] ? $request['is_single_transaction'] : false;
        $game_username = !empty($this->request->fundtransferrequest->accountid) ? $this->request->fundtransferrequest->accountid : null;
        $game_code = !empty($this->request->fundtransferrequest->gamedetails->keyname) ? $this->request->fundtransferrequest->gamedetails->keyname : null;
        $round_id = !empty($this->request->fundtransferrequest->friendlygameinstanceid) ? $this->request->fundtransferrequest->friendlygameinstanceid : null;

		//initialize response
		$success = false;
		$isValidAmount = true;
		$insufficientBalance = false;
		$isAlreadyExists = false;
		$isToRefundExists = false;
		$isToRecreditExists = false;
		$isTransactionAdded = false;
		$additionalResponse	= [];

		$isValidAmount 		= $this->isValidAmount($request['amount']);

		$mode = 'debit';
		
		if($request['amount']>=0){
			$mode = 'credit';
		}
		
		
		if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
			$this->wallet_model->setGameProviderActionType($remoteActionType);
		}

		# OGP-33131
		$fundtransferrequest_game = isset($this->request->fundtransferrequest->gamedetails) ? $this->request->fundtransferrequest->gamedetails : null;		
		$game_unique_id= isset($fundtransferrequest_game->keyname) ? $fundtransferrequest_game->keyname : null;
		$uniqueid_of_seamless_service=$this->game_platform_id.'-'.$transfer_id;			     

		if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
			$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) uniqueid_of_seamless_service", $uniqueid_of_seamless_service, $game_unique_id);
			$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $game_unique_id);
		}

		if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
			$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) remoteActionType", $remoteActionType);
			$this->wallet_model->setGameProviderActionType($remoteActionType); 
        }
		
		if($mode <> 'debit'){
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
				$this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
			}
			$related_bet_transfer_id = $initialdebittransferid;
			$related_uniqueid_of_seamless_service='game-'.$this->game_platform_id.'-'.$related_bet_transfer_id;
	
			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
			}
		}
	
			
		if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
			$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) round_id", $round_id);
			$this->wallet_model->setGameProviderRoundId($round_id);
		}

		if ( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
			$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) isEnd", $isEnd);
			$this->wallet_model->setGameProviderIsEndRound($isEnd);
		}

		$this->transfer_ids[] = $transfer_id;

		if($isValidAmount && $amount<>0){

			if(!$isValidAmount){
				$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) isValidAmount", $isValidAmount);
				$success = false;
				$isValidAmount = false;
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
			}

			//get and process balance
			$get_balance = $this->getPlayerBalance($request['player_name']);

			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}

			}else{
				$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) ERROR: getBalance", $get_balance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
			}

			//check to re-credit
			if($toReCreditId){
				$originalTransfer = $this->getTransaction($toReCreditId);
				if(!empty($originalTransfer)){
					$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) isToRecreditExists", $isToRecreditExists);
					$isAlreadyExists = $isToRecreditExists = true;
					$afterBalance = $previousBalance;
					return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
				}else{
					$isToRecreditExists = false;
				}
			}

			//check to refund
			if($toRefundId){
				$originalTransfer = $this->getTransaction($toRefundId);
				if(empty($originalTransfer)){
					$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) isToRefundExists", $isToRefundExists);
					$isToRefundExists = false;
					$afterBalance = $previousBalance;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
				}else{
					$isToRefundExists = true;
					$flagrefunded = true;

                    $fields = [
                        'fundinfo_originaltransferid' => $toRefundId,
                        'is_refunded' => 1
                    ];

                    $is_already_processed = $this->isTransactionExists($fields);

                    if ($is_already_processed) {
                        $this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) refund is_already_processed", $is_already_processed);
                        $isAlreadyExists = true;
                        $afterBalance = $previousBalance;
                        return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
                    }
				}
			}

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
			}

            if ($mode == 'credit') {
                if ($is_single_transaction) {
                    $transaction_type = 'single debit';
                } else {
                    $transaction_type = 'debit';
                }

                $is_bet_exists = $this->isTransactionExists([
                    //'trans_type' => $transaction_type,
                    'accountid' => $game_username,
                    'gamedetails_keyname' => $game_code,
                    'friendlygameinstanceid' => $round_id,
                ]);
                
                if (!$is_bet_exists) {
                    $is_bet_exists = $this->isTransactionExists([
                        'trans_type' => 'debit',
                        'accountid' => $game_username,
                        'gamedetails_keyname' => $game_code,
                        'friendlygameinstanceid' => $round_id,
                    ]);

                    if (!$is_bet_exists) {
                        $this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) bet not exists", $is_bet_exists);
                        $afterBalance = $previousBalance;
    
                        $additionalResponse = [
                            'is_bet_exists' => false,
                        ];
    
                        return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
                    }
                }
            }

			//insert transaction
			$isAdded = $this->insertTransactionRecord($transfer_id, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) isAdded=false saving error", $isAdded, $this->trans_records);
				return false;
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (debitCreditAmount) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $isToRefundExists, $isToRecreditExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}
			//OGP-28649
			$fundtransferrequest_game = isset($this->request->fundtransferrequest->gamedetails) ? $this->request->fundtransferrequest->gamedetails : null;		
			$game_unique_id= isset($fundtransferrequest_game->keyname) ? $fundtransferrequest_game->keyname : null;
            $uniqueid_of_seamless_service=$this->game_platform_id.'-'.$transfer_id;		

			if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
	            $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $game_unique_id);
			}     


			if($mode=='debit'){
				$success = $this->wallet_model->decSubWallet($player_id, $this->game_platform_id, $amount);
			}else{
				$success = $this->wallet_model->incSubWallet($player_id, $this->game_platform_id, $amount);
			}

			if ($this->utils->isEnabledRemoteWalletClient()) {
				$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
				if(!$success && $remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
					$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (incRemoteWallet) treated as success remoteErrorCode: " , $remoteErrorCode, $request);
					$success = true;
				}

				if($remoteErrorCode){
					$this->saveRemoteWalletError($remoteErrorCode, $request, $mode);
				}
			}

		}else{
			if($isValidAmount){

				if($this->utils->compareResultFloat($amount, '=', 0)){
					if($this->utils->isEnabledRemoteWalletClient()){
						$this->utils->debug_log("HABANERO SEAMLESS SERVICE API: (debitCreditAmountToWallet) amount 0 call remote wallet", 'request', $request);
						$succRemote=$this->wallet_model->incRemoteWallet($player_id, $amount, $this->game_api->getPlatformCode(), $afterBalance);
						$remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
						if(!$succRemote && $remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
							$this->utils->debug_log("HABANERO SEAMLESS SERVICE: (incRemoteWallet) treated as success remoteErrorCode: " , $remoteErrorCode, $request);
							$success = true;
						}
					} 
				}

				$get_balance = $this->getPlayerBalance($request['player_name']);
				if($get_balance!==false){
					$previousBalance = $afterBalance = $get_balance;
					$success = true;
				}else{
					$success = false;
				}

				//insert transaction
				$this->insertTransactionRecord($transfer_id, $previousBalance, $afterBalance);
			}
		}

		return array($success,
						$previousBalance,
						$afterBalance,
						$insufficientBalance,
						$isAlreadyExists,
						$isToRefundExists,
						$isToRecreditExists,
						$additionalResponse,
						$isTransactionAdded);
	}

	private function isFailedTransactionExist($where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $isExisting = $this->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
        $this->utils->debug_log("HABANERO SERVICE API: isFailedTransactionExist",$table_name, $where, $isExisting);
        return $isExisting;
    }

    private function saveFailedTransaction($query_type='insert', $data=[], $where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $this->utils->debug_log("HABANERO SERVICE API: saveFailedTransaction",$query_type, $table_name, $data, $where);
        $this->original_seamless_wallet_transactions->saveTransactionData($table_name, $query_type, $data, $where);
    }

    private function saveRemoteWalletError($remoteErrorCode, $request, $mode){

        if($remoteErrorCode){
			$failed_external_uniqueid = isset($request['transfer_id']) ? $request['transfer_id'] : null;
			$failed_transaction_data = $md5_data = [
				'round_id' => isset($request['transfer_id']) ? $request['transfer_id'] : null,
				'transaction_id' => isset($request['transfer_id']) ? $request['transfer_id'] : null,
				'external_game_id' => isset($this->request->basegame->keyname) ? $this->request->basegame->keyname : null,
				'player_id' => isset($request['player_id']) ? $request['player_id'] : null,
				'game_username' => isset($request['player_name']) ? $request['player_name'] : null,
				'amount' => isset($request['amount']) ? abs($request['amount']) : null,
				'balance_adjustment_type' => isset($mode) ? $mode : null,
				'action' => isset($this->request->type) ? $this->request->type : null,
				'game_platform_id' => $this->game_api->getPlatformCode(),
				'transaction_raw_data' => json_encode($this->request),
				'remote_raw_data' => null,
				'remote_wallet_status' => $remoteErrorCode,
				'transaction_date' => date('Y-m-d H:i:s'),
				'request_id' => $this->utils->getRequestId(),
				'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
				'headers' => json_encode(getallheaders()),
				'external_uniqueid' => $failed_external_uniqueid,
			];
			
			$failed_transaction_data['md5_sum'] = md5(json_encode($md5_data));

			$where = ['external_uniqueid' => $failed_external_uniqueid];
			if($this->isFailedTransactionExist($where)){
				$this->saveFailedTransaction('update',$failed_transaction_data, $where);
			}else{
				$this->saveFailedTransaction('insert',$failed_transaction_data);
			}
        }
    }

	public function getPlayerBalance($playerName){
		$get_bal_req = $this->game_api->queryPlayerBalance($playerName);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function formatAmount($amount){
		$amount = $this->roundDownAmount($amount);
		return $amount;
	}

	public function roundDownAmount($number){
		$conversion_precision = floatval($this->game_api->getSystemInfo('game_amount_conversion_precision', 4));
		$fig = (int) str_pad('1', $conversion_precision+1, '0');
		return (floor($number * $fig) / $fig);
	}

	public function isValidAmount($amount){
		return is_numeric($amount);
	}

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);

        foreach ($this->transactions_for_fast_track as $transaction_for_fast_track) {

            $this->utils->debug_log("HABANERO SEAMLESS SERVICE: (sendToFastTrack) BEFORE", $transaction_for_fast_track);
            $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->game_api->getPlatformCode(), $transaction_for_fast_track['keyname']);
            $betType = null;
            switch($transaction_for_fast_track['trans_type']) {
                case 'single debit':
                case 'freespin':
                case 'debit':
                    $betType = 'Bet';
                    break;
                case 'freespin credit':
                case 'bonus credit':
                case 'lastround credit':
                case 'credit':
                case 'lastround':
                    $betType = 'Win';
                    break;
                case 'refund credit':
                case 'recredit credit':
                    $betType = 'Refund';
                    break;
                default:
                    $betType = null;
                    break;
            }

            if ($betType == null) {
                return;
            }

            $data = [
                "activity_id" =>  strval($transaction_for_fast_track['id']),
                "amount" => ($transaction_for_fast_track['trans_type'] == 'freespin') ? 0 : (float) abs($transaction_for_fast_track['fundinfo_amount']),
                "balance_after" =>  $transaction_for_fast_track['balance_after'],
                "balance_before" =>  $transaction_for_fast_track['balance_before'],
                "bonus_wager_amount" =>  ($transaction_for_fast_track['trans_type'] == 'freespin') ? (float) abs($transaction_for_fast_track['fundinfo_amount']) : 0,
                "currency" =>  $this->game_api->currency,
                "exchange_rate" =>  1,
                "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
                "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
                "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
                "is_round_end" =>  $betType == 'Win' ? true : false,
                "locked_wager_amount" =>  0.00,
                "origin" =>  $_SERVER['HTTP_HOST'],
                "round_id" =>  strval($transaction_for_fast_track['friendlygameinstanceid']),
                "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
                "type" =>  $betType,
                "user_id" => $this->game_api->getPlayerIdInGameProviderAuth($transaction_for_fast_track['accountid']),
                "vendor_id" =>  strval($this->game_api->getPlatformCode()),
                "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
                "wager_amount" => $betType == 'Bet' ? (float) abs($transaction_for_fast_track['fundinfo_amount']) : 0,
            ];

            $this->utils->debug_log("HABANERO SEAMLESS SERVICE: (sendToFastTrack)", $data);

            $this->load->library('fast_track');
            $this->fast_track->addToQueue('sendGameLogs', $data);
        }
    }

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

}

///END OF FILE////////////