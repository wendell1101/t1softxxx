<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';
/**
 * http://admin.brl.staging.smash.t1t.in/Pgsoft_seamless_game_service_api/VerifySession
 * http://admin.brl.staging.smash.t1t.in/Pgsoft_seamless_game_service_api/Cash/Get
 * http://admin.brl.staging.smash.t1t.in/Pgsoft_seamless_game_service_api/Cash/TransferInOut
 * http://admin.brl.staging.smash.t1t.in/Pgsoft_seamless_game_service_api/Cash/Adjustment
 */
class Pgsoft_seamless_game_service_api extends BaseController {
    use Seamless_service_api_module;

    //success codes
    const SUCCESS = '0x0';

    //error codes
    const ERROR_INVALID_REQUEST = '0x1034';
    const ERROR_INVALID_OPERATOR = '0x1204';
    const ERROR_GAME_MAINTENANCE = '0x1400';
    const ERROR_INVALID_PLAYER = '0x1305';
    const ERROR_INVALID_PLAYER_SESSION_TOKEN = '0x1300';
    const ERROR_INTERNAL_SERVER_ERROR = '0x1200';
    const ERROR_INSUFFICIENT_BALANCE = '0x3202';  //ok
    const ERROR_BET_ALREADY_EXIST = '0x3032';
    const ERROR_BET_FAILED = '0x3033';// ok
    const ERROR_BET_DOES_NOT_EXIST = '0x3021';// ok
    const ERROR_PAYOUT_FAILED = '0x3034';
    const ERROR_INVALID_PLAYER_SESSION = '0x1307';
    const ERROR_PLAYER_SESSION_EXPIRED = '0x1308';
    const ERROR_PARAMS_VALUE_CANNOT_BE_NULL = '0x3001';
    const ERROR_BET_FAILED_EXCEPTION = '0x3073';
    const ERROR_CANNOT_ROLLBACK = '0x3074';

	const ERROR_CODES = [
		self::ERROR_INVALID_REQUEST,
        self::ERROR_INVALID_OPERATOR,
		self::ERROR_GAME_MAINTENANCE,
        self::ERROR_INVALID_PLAYER,
		self::ERROR_INVALID_PLAYER_SESSION_TOKEN,
		self::ERROR_INTERNAL_SERVER_ERROR,
		self::ERROR_INSUFFICIENT_BALANCE,
		self::ERROR_BET_ALREADY_EXIST,
		self::ERROR_BET_FAILED,
		self::ERROR_BET_DOES_NOT_EXIST,
		self::ERROR_PAYOUT_FAILED,
		self::ERROR_INVALID_PLAYER_SESSION,
		self::ERROR_PLAYER_SESSION_EXPIRED,
		self::ERROR_PARAMS_VALUE_CANNOT_BE_NULL,
		self::ERROR_BET_FAILED_EXCEPTION,
		self::ERROR_CANNOT_ROLLBACK,
	];


	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS => 200,

		self::ERROR_INVALID_REQUEST => 200,
        self::ERROR_INVALID_OPERATOR => 200,
		self::ERROR_INVALID_PLAYER_SESSION_TOKEN => 200,
        self::ERROR_INVALID_PLAYER => 200,
        self::ERROR_GAME_MAINTENANCE => 503,
        self::ERROR_INTERNAL_SERVER_ERROR => 200,
        self::ERROR_INSUFFICIENT_BALANCE => 200,
        self::ERROR_BET_ALREADY_EXIST => 200,
        self::ERROR_BET_FAILED => 200,
		self::ERROR_BET_DOES_NOT_EXIST => 200,
        self::ERROR_PAYOUT_FAILED => 200,
        self::ERROR_INVALID_PLAYER_SESSION => 200,
        self::ERROR_PARAMS_VALUE_CANNOT_BE_NULL => 200,
        self::ERROR_BET_FAILED_EXCEPTION => 200,
	];

	const ERROR_CODE_MAP = [
		self::SUCCESS=>null,

        self::ERROR_INVALID_REQUEST => '1034',
        self::ERROR_GAME_MAINTENANCE => '1400',
        self::ERROR_INVALID_PLAYER => '1305',
        self::ERROR_INTERNAL_SERVER_ERROR => '1200',
        self::ERROR_INSUFFICIENT_BALANCE => '3202',
        self::ERROR_BET_ALREADY_EXIST => '3032',
        self::ERROR_BET_FAILED => '3033',
        self::ERROR_PAYOUT_FAILED => '3034',
		self::ERROR_BET_DOES_NOT_EXIST=>'3021',
        self::ERROR_INVALID_PLAYER_SESSION => '1307',
        self::ERROR_PLAYER_SESSION_EXPIRED => '1308',
        self::ERROR_PARAMS_VALUE_CANNOT_BE_NULL => '3001',
        self::ERROR_BET_FAILED_EXCEPTION => '3073',
	];

    const METHOD_VERIFY_SESSION = 'verifySession';
    const METHOD_CASH_GET = 'cashGet';
    const METHOD_CASH_TRANSFERINOUT = 'cashTransferInOut';
    const METHOD_CASH_ADJUSTMENT = 'cashAdjustment';
    const METHOD_ROLLBACK = 'rollback';

	public $game_api;
	public $game_platform_id;
	public $player_id;
	public $request;
	public $currency;

	public $start_time;
	public $end_time;

	private $headers;

	private $remote_wallet_status = null;

	private $remote_wallet_params = null;

	public function __construct() {
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','pgsoft_seamless_transactions', 'ip', 'original_seamless_wallet_transactions'));

		$this->host_name =  $_SERVER['HTTP_HOST'];

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->trans_record = [];

		$this->parseRequest();

		$this->retrieveHeaders();

		$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (__construct)", 'request', $this->request, 'REQUEST_URI', $_SERVER['REQUEST_URI']);
		$this->ip_invalid = false;

	}

	public function initialize($gamePlatformId){
		$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (initialize) gamePlatformId: " . $gamePlatformId);

		$this->trans_time = date('Y-m-d H:i:s');

		$this->game_platform_id = $gamePlatformId;

		if(empty($gamePlatformId)){
			$this->getValidPlatformId();
        }

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);

        if(!$this->game_api){
			$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $gamePlatformId);
			return false;
        }

        $this->currency = $this->game_api->getCurrency();
		//$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (initialize) ERROR currency: ", $this->currency);
        $this->pgsoft_seamless_transactions->tableName = $this->game_api->original_transactions_table;

		return true;
	}

    public function isOperatorValid($operatorToken){
        return $this->game_api->operator_token==$operatorToken;
    }

    public function isSecretKeyValid($secretKey){
        return $this->game_api->secret_key==$secretKey;
    }

    public function isCurrencyCodeValid($currency){
        return $this->game_api->currency==$currency;
    }

	public function verifySession($gamePlatformId=null){
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (auth)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::METHOD_VERIFY_SESSION;
		$errorCode = self::ERROR_INTERNAL_SERVER_ERROR;

		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
			'operator_token'=>'required',
			'secret_key'=>'required',
			'operator_player_session'=>'required',
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

            if (!$this->game_api->validateWhiteIP()) {
            	$this->ip_invalid = true;
                throw new Exception(self::ERROR_INVALID_REQUEST);
            }

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			}

            if(!$this->isOperatorValid($this->request['operator_token'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            if(!$this->isSecretKeyValid($this->request['secret_key'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}


			// get player details
			$token = strtolower($this->request['operator_player_session']);
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByToken($token);

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_PLAYER_SESSION_TOKEN);
			}

			$player_id = $player->player_id;

			$success = true;
			$errorCode = self::SUCCESS;
			$currency = $this->currency;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['data']['player_name'] = $gameUsername;
        $externalResponse['data']['nickname'] = isset($player_username) && !empty($player_username) ? $player_username : null;
		$externalResponse['data']['currency'] = $currency;
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function cashGet($gamePlatformId=null){
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashGet)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::METHOD_CASH_GET;
		$errorCode = self::ERROR_INTERNAL_SERVER_ERROR;

		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$rules = [
			'operator_token'=>'required',
			'secret_key'=>'required',
			'player_name'=>'required',
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

            if (!$this->game_api->validateWhiteIP()) {
            	$this->ip_invalid = true;
                throw new Exception(self::ERROR_INVALID_REQUEST);
            }

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			}

            if(!$this->isOperatorValid($this->request['operator_token'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            if(!$this->isSecretKeyValid($this->request['secret_key'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}


			// get player details
			// and validate operator_player_session to player_name
			$player_name = $this->request['player_name'];
			//list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
			if(isset($this->request['operator_player_session']) && !empty($this->request['operator_player_session'])){
				$token = $this->request['operator_player_session'];
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsernameAndToken($player_name, $token);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
			}

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_PLAYER);
			}

			$player_id = $player->player_id;
			$external_game_id = isset($this->request['game_id']) ? $this->request['game_id'] : null;
			$this->wallet_model->setExternalGameId($external_game_id);

			// $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id,
			// $player_username,
			// 	&$balance) {

			// 	$balance = $this->getPlayerBalance($player_username, $player_id);
			// 	if($balance===false){
			// 		return false;
			// 	}

			// 	return true;
			// });


			// if(!$success){
			// 	throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
			// }
			$use_read_only = true;
			$balance = $this->player_model->getPlayerSubWalletBalance($player_id, $gamePlatformId, $use_read_only);
			
			$success = true;
			$errorCode = self::SUCCESS;
			$currency = $this->currency;

		} catch (Exception $error) {
            $errorCode = $error->getMessage();
			$success = false;
		}

        $externalResponse['data']['currency_code'] = $currency;
		$externalResponse['data']['balance_amount'] = $this->game_api->dBtoGameAmount($balance); //$this->game_api->gameAmountToDBTruncateNumber($balance) //$this->formatBalance($balance)
        $externalResponse['data']['updated_time'] = intval($this->getUpdateTime());

        $this->CI->utils->debug_log('PGSOFT SEAMLESS SERVICE: (cashGet) externalResponse:', $externalResponse);
		$fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function cashTransferInOut($gamePlatformId=null){
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashTransferInOut)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::METHOD_CASH_TRANSFERINOUT;
		$errorCode = self::ERROR_INTERNAL_SERVER_ERROR;

		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$betExist = true;
		$txId = null;
		$rules = [
			'operator_token'=>'required',
			'secret_key'=>'required',
			//'operator_player_session'=>'required',
			'player_name'=>'required',
			'game_id'=> ['required', 'isNumeric'],
			'parent_bet_id'=>'required',
			'bet_id'=>'required',
			'currency_code'=>'required',
			'transfer_amount'=> ['required', 'isNumeric'],
			'transaction_id'=>'required',
			'create_time'=>'required',
			'updated_time'=>'required',
		];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

            if (!$this->game_api->validateWhiteIP()) {
            	$this->ip_invalid = true;
                throw new Exception(self::ERROR_INVALID_REQUEST);
            }

			/* if(!$this->isIPAllowed()){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			} */

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			}

            if(!$this->isOperatorValid($this->request['operator_token'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            if(!$this->isSecretKeyValid($this->request['secret_key'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            if(!$this->isCurrencyCodeValid($this->request['currency_code'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            // invalid amount
            $haystack = floatval($this->request['bet_amount']);
            $needle = '1.0E+';

            if (strpos($haystack, $needle) !== false) {
                throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
            } else {
                if(!$this->isValidTransferAmount($this->request)){
                    throw new Exception(self::ERROR_BET_FAILED_EXCEPTION);
                }
            }

			// get player details
			$player_name = $this->request['player_name'];
            //list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
			if(isset($this->request['operator_player_session']) && !empty($this->request['operator_player_session'])){
				$token = $this->request['operator_player_session'];
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsernameAndToken($player_name, $token);
			}else{
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);
			}

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_PLAYER);
			}

            $params = [];
            //required params
            $params['player_id']                        = $player_id = $player->player_id;
            $params['operator_token']                   = $this->request['operator_token'];
            $params['secret_key']                       = $this->request['secret_key'];
            $params['player_name']                      = $this->request['player_name'];
            $params['game_id']                          = $this->request['game_id'];
            $params['parent_bet_id']                    = $this->request['parent_bet_id'];
            $params['bet_id']                           = $this->request['bet_id'];
            $params['currency_code']                    = $this->request['currency_code'];
            $params['bet_amount']                       = floatVal($this->request['bet_amount']);
            $params['win_amount']                       = floatVal($this->request['win_amount']);
            $params['transfer_amount']                  = $this->request['transfer_amount'];//the deduct/add amount
            $params['transaction_id']                   = $this->request['transaction_id'];//the unique identifier
            $params['bet_type']                         = $this->request['bet_type'];
            $params['updated_time']                     = $this->request['updated_time'];
			$params['updated_time_parsed']              = $this->unixtimeToDateTime($this->request['updated_time']);
			$externalUniqueId = $params['external_uniqueid']                = $params['transaction_id'];
			$params['trans_type']                       = $callType;

            //optional params
            //$params['platform']                       = isset($this->request['platform'])?$this->request['platform']:null;
            $params['operator_player_session']          = isset($this->request['operator_player_session']) ? $this->request['operator_player_session'] : null;
            $params['wallet_type']                      = isset($this->request['wallet_type']) ? $this->request['wallet_type'] : null;
            $params['is_minus_count']                   = isset($this->request['is_minus_count']) ? $this->processIsParameter($this->request['is_minus_count']) : null;
            $params['is_validate_bet']                  = isset($this->request['is_validate_bet']) ? $this->processIsParameter($this->request['is_validate_bet']) : null;
            $params['is_adjustment']                    = isset($this->request['is_adjustment']) ? $this->processIsParameter($this->request['is_adjustment']) : null;
            $params['is_parent_zero_stake']             = isset($this->request['is_parent_zero_stake']) ? $this->processIsParameter($this->request['is_parent_zero_stake']) : null;
            $params['is_feature']                       = isset($this->request['is_feature']) ? $this->processIsParameter($this->request['is_feature']) : null;
            $params['is_feature_buy']                   = isset($this->request['is_feature_buy']) ? $this->processIsParameter($this->request['is_feature_buy']) : null;
            $params['is_wager']                         = isset($this->request['is_wager']) ? $this->processIsParameter($this->request['is_wager']) : null;
            $params['free_game_transaction_id']         = isset($this->request['free_game_transaction_id']) ? $this->request['free_game_transaction_id'] : null;
            $params['free_game_name']                   = isset($this->request['free_game_name']) ? $this->request['free_game_name'] : null;
            $params['free_game_id']                     = isset($this->request['free_game_id']) ? $this->request['free_game_id'] : null;
            //$params['is_minus_count']                 = isset($this->request['is_minus_count']) ? $this->request['is_minus_count'] : null;
            $params['bonus_transaction_id']             = isset($this->request['bonus_transaction_id']) ? $this->request['bonus_transaction_id'] : null;
            $params['bonus_name']                       = isset($this->request['bonus_name']) ? $this->request['bonus_name'] : null;
            $params['bonus_id']                         = isset($this->request['bonus_id']) ? $this->request['bonus_id'] : null;
            $params['bonus_balance_amount']             = isset($this->request['bonus_balance_amount']) ? $this->request['bonus_balance_amount'] : null;
            $params['bonus_ratio_amount']               = isset($this->request['bonus_ratio_amount']) ? $this->request['bonus_ratio_amount'] : null;
            $params['jackpot_rtp_contribution_amount']  = isset($this->request['jackpot_rtp_contribution_amount']) ? $this->request['jackpot_rtp_contribution_amount'] : null;
			$params['is_end_round']  = isset($this->request['is_end_round']) ? $this->request['is_end_round'] : false;

			$remoteActionType = 'bet-payout';

			if( 
				$this->utils->compareResultFloat($params['bet_amount'], '==', 0) && 
				$this->utils->compareResultFloat($params['win_amount'], '>=', 0)
				){
				$remoteActionType = 'payout';
			}

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				$remoteActionType,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
                &$betExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType);
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE lockAndTransForPlayerBalance cashTransferInOut",
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

			if($this->utils->isEnabledRemoteWalletClient()){
				if(!is_null($this->remote_wallet_status) && $this->remote_wallet_status<>0){

					# update before and after balance if already exist/ok if jumped
					if($this->remote_wallet_status==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
						//update transaction, make before and after balance same as remote response
						$tableName = $this->game_api->getTransactionsTable();
						$updateData['after_balance'] = $after_balance;
						$updateTrans = $this->CI->pgsoft_seamless_transactions->updateTransacionData($externalUniqueId, $updateData, $tableName);
						$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashTransferInOut)", 
						"updateData", $updateData,
						"updateTrans", $updateTran,
						"tableName", $tableName);
					}

					#save failed transaction
					$this->load->model(['failed_seamless_transactions']);
					$transRecord = $this->trans_record;
					$failedTrans = [
						'transaction_id'=>isset($transRecord['transaction_id']) ?$transRecord['transaction_id']:null,//ok
						'round_id'=>isset($transRecord['transaction_id']) ?$transRecord['transaction_id']:null,//ok
						'external_game_id'=>isset($transRecord['game_id']) ?$transRecord['game_id']:null,//ok
						'player_id'=>isset($transRecord['player_id']) ?$transRecord['player_id']:null,//ok
						'game_username'=>isset($transRecord['player_name']) ?$transRecord['player_name']:null,//ok
						'amount'=>isset($transRecord['orig_transfer_amount']) ?abs($transRecord['orig_transfer_amount']):null,//ok
						'balance_adjustment_type'=>$transRecord['orig_transfer_amount']<0 ?'decrease':'increase',//ok
						'action'=>isset($transRecord['trans_type']) ?$transRecord['trans_type']:null,//ok
						'game_platform_id'=>$this->game_platform_id,//ok
						'transaction_raw_data'=>is_array($this->request)?json_encode($this->request):$this->request,//ok
						'remote_raw_data'=>is_array($this->remote_wallet_params)?json_encode($this->remote_wallet_params):$this->remote_wallet_params,
						'external_uniqueid'=>isset($transRecord['external_uniqueid']) ?$transRecord['external_uniqueid']:null,//ok
						'remote_wallet_status'=>$this->remote_wallet_status,
						'transaction_date'=>isset($transRecord['updated_time_parsed']) ?$transRecord['updated_time_parsed']:null,//ok
						'created_at'=>isset($transRecord['created_at']) ?$transRecord['created_at']:$this->utils->getNowDateTime()->format('Y-m-d H:i:s'),//ok
						'updated_at'=>isset($transRecord['updated_at']) ?$transRecord['updated_at']:$this->utils->getNowDateTime()->format('Y-m-d H:i:s'),//ok
						'request_id'=>$this->utils->getRequestId(),//ok
						'headers'=>is_array($this->headers)?json_encode($this->headers):$this->headers,//ok
						'full_url'=>$this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),//ok
					];
					$failedTransSave = $this->failed_seamless_transactions->insertTransaction($failedTrans);
					$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashTransferInOut)", 
					"failedTransSave", $failedTransSave,
					"failedTrans", $failedTrans);
				}
			}

			if($insufficient_balance){
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){
				// $errorCode = self::ERROR_BET_ALREADY_EXIST;
				// Return previous successful response for the duplicated request.
				$errorCode = self::SUCCESS;
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashTransferInOut) already exist", $this->request, $params);
			}else{
				$errorCode = self::SUCCESS;
			}

			if($trans_success==false){
                if(!$betExist){
                    throw new Exception(self::ERROR_BET_DOES_NOT_EXIST);
                }
				throw new Exception(self::ERROR_BET_FAILED);
			}


			$success = true;
            $currency = $this->currency;
            $balance = $after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['data']['currency_code'] = $currency;
		$externalResponse['data']['balance_amount'] = $this->game_api->dBtoGameAmount($balance); //$this->formatBalance
        $externalResponse['data']['updated_time'] = intval($this->request['updated_time']); // $this->request['updated_time'];

        $fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	private function isValidTransferAmount($request){
        return strval($this->ssa_truncate_amount($request['win_amount'] - $request['bet_amount'])) == (string)$this->ssa_truncate_amount($request['transfer_amount']);
	}


    /**
     * ! not in use for now
     * ? PGSoft will use this API to perform player’s balance addition or deduction. This API can be used for certain events, such as Cash Tournament.
     */
	public function cashAdjustment($gamePlatformId=null){
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashAdjustment)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::METHOD_CASH_ADJUSTMENT;
		$errorCode = self::ERROR_INTERNAL_SERVER_ERROR;

		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$txId = null;
		$rules = [
			'operator_token'=>'required',
			'secret_key'=>'required',
			'player_name'=>'required',
			'currency_code'=>'required',
			'transfer_amount'=> ['required', 'isNumeric'],
			'adjustment_id'=>'required',
			'adjustment_transaction_id'=>'required',
			'adjustment_time'=>'required',
			'transaction_type'=>'required',
			'bet_type'=>'required',
		];

        $origAdjustmentAmount =  $params['transfer_amount'] = $this->request['transfer_amount'];//the deduct/add amount
        $params['adjustment_time'] = $this->request['adjustment_time'];

		try {

            //? will throw error upon use since not in use.
            // throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

            if (!$this->game_api->validateWhiteIP()) {
            	$this->ip_invalid = true;
                throw new Exception(self::ERROR_INVALID_REQUEST);
            }

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			}

            if(!$this->isOperatorValid($this->request['operator_token'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            if(!$this->isSecretKeyValid($this->request['secret_key'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            if(!$this->isCurrencyCodeValid($this->request['currency_code'])){
				throw new Exception(self::ERROR_INVALID_OPERATOR);
			}

            // invalid amount
            $haystack = floatval($this->request['transfer_amount']);
            $needle = '-1.0E+';

            if (strpos($haystack, $needle) !== false) {
                throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
            }

			// get player details
			$player_name = $this->request['player_name'];
            list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($player_name);

            if(!$playerStatus){
                throw new Exception(self::ERROR_INVALID_PLAYER);
			}

            $params = [];
            //required params
            $params['player_id'] = $player_id = $player->player_id;
            $params['operator_token'] = $this->request['operator_token'];
            $params['secret_key'] = $this->request['secret_key'];
            $params['player_name'] = $this->request['player_name'];
			$params['currency_code'] = $this->request['currency_code'];
			$origAdjustmentAmount =  $params['transfer_amount'] = $this->request['transfer_amount'];//the deduct/add amount
			$params['transaction_id'] = $this->request['adjustment_transaction_id'];//the unique identifier, use adjustment_transaction_id as unique
			$params['adjustment_transaction_id'] = $this->request['adjustment_transaction_id'];//the unique identifier of adjustment
			$params['adjustment_time'] = $this->request['adjustment_time'];
            $params['adjustment_time_parsed'] = $this->unixtimeToDateTime($this->request['adjustment_time']);
            $params['bet_type'] = $this->request['bet_type'];
			$params['adjustment_transaction_type'] = isset($this->request['adjustment_transaction_type']) ? $this->request['adjustment_transaction_type'] : "";
			$params['external_uniqueid'] = $params['transaction_id'];
            $params['trans_type'] = $callType;

			$remoteActionType = 'adjustment';

			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($player,
				$params,
				$remoteActionType,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
                &$betExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance, $remoteActionType);
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE lockAndTransForPlayerBalance cashAdjustment",
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

			if($insufficient_balance){
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashAdjustment) already exist", $this->request, $params);
			}

			if($trans_success==false){
                if(!$betExist){
                    throw new Exception(self::ERROR_BET_DOES_NOT_EXIST);
                }
				throw new Exception(self::ERROR_BET_FAILED);
			}


			$success = true;
			$errorCode = self::SUCCESS;
            $currency = $this->currency;
            $balance = $after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		//updated_time should match with adjustment_time
		//$externalResponse['data']['adjust_amount'] = $this->game_api->dBtoGameAmount($params['transfer_amount']);
		$externalResponse['data']['adjust_amount'] = $origAdjustmentAmount;
		$externalResponse['data']['balance_before'] = $this->game_api->dBtoGameAmount($previous_balance); //$this->formatBalance
		$externalResponse['data']['balance_after'] = $this->game_api->dBtoGameAmount($balance); //$this->formatBalance
        $externalResponse['data']['updated_time'] = intval($params['adjustment_time']); //$this->getUpdateTime();

        $fields = [
			'player_id'		=> $player_id,
		];
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	// For manual rollback of failed bet transaction
	public function rollback($gamePlatformId=null){
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (rollbackTrans)");

		$externalResponse = $this->externalQueryResponse();

		$callType = self::METHOD_ROLLBACK;
		$errorCode = self::ERROR_INTERNAL_SERVER_ERROR;

		$player_id = null;
		$balance = 0;
		$currency = null;
		$gameUsername = null;
		$success = false;
		$previous_balance = $after_balance = 0;
		$betExist = $isTransactionAdded = $insufficient_balance = $isAlreadyExists = false;
		$txId = null;
		$isPrevious = false;
		$rules = [
			'transaction_id'=>'required'
		];
		$params = [];

		try {

			if(!$this->initialize($gamePlatformId)){
				throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
			}

            if($this->utils->setNotActiveOrMaintenance($gamePlatformId)) {
                throw new Exception(self::ERROR_GAME_MAINTENANCE);
            }

            if (!$this->game_api->validateWhiteIP()) {
            	$this->ip_invalid = true;
                throw new Exception(self::ERROR_INVALID_REQUEST);
            }

			if(!$this->isValidParams($this->request, $rules)){
				throw new Exception(self::ERROR_INVALID_REQUEST);
			}

			$uniqueid = $this->request['transaction_id'];
			$transDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid]);
			if(empty($transDetails)){
				$transDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid]);
				if(empty($transDetails)){
					throw new Exception(self::ERROR_INTERNAL_SERVER_ERROR);
				}
				$isPrevious = true;
			}
			if($transDetails['transfer_amount'] >= 0){
				throw new Exception(self::ERROR_CANNOT_ROLLBACK);

			}
			unset($transDetails['id']);
            $params = $transDetails;
            $params['orig_transfer_amount'] = $params['transfer_amount'] = abs($transDetails['transfer_amount']);
			$params['transaction_id'] = $params['external_uniqueid'] = "R-".$params['transaction_id'];
            $params['trans_type'] = $callType;
            $params['adjustment_time'] = $params['updated_time'] = $this->utils->getTimestampNow() * 1000;
            $params['adjustment_time_parsed'] = $this->utils->getNowForMysql();

			$trans_success = $this->lockAndTransForPlayerBalance($transDetails['player_id'], function() use(
				$params,
				&$insufficient_balance,
				&$previous_balance,
				&$after_balance,
				&$isAlreadyExists,
				&$additionalResponse,
                &$betExist) {

				list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($params, $previous_balance, $after_balance);
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE lockAndTransForPlayerBalance cashAdjustment",
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

			if($insufficient_balance){
				throw new Exception(self::ERROR_INSUFFICIENT_BALANCE);
			}

			if($isAlreadyExists){
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (cashAdjustment) already exist", $this->request, $params);
			}

			if($trans_success==false){
                if(!$betExist){
                    throw new Exception(self::ERROR_BET_DOES_NOT_EXIST);
                }
				throw new Exception(self::ERROR_BET_FAILED);
			}


			$success = true;
			$errorCode = self::SUCCESS;
            $currency = $this->currency;
            $balance = $after_balance;
		} catch (Exception $error) {
			$errorCode = $error->getMessage();
			$success = false;
		}

		$externalResponse['data']['adjust_amount'] = isset($params['orig_transfer_amount']) ? $params['orig_transfer_amount'] : null;
		$externalResponse['data']['balance_before'] = $this->game_api->dBtoGameAmount($previous_balance); //$this->formatBalance
		$externalResponse['data']['balance_after'] = $this->game_api->dBtoGameAmount($balance); //$this->formatBalance
        $externalResponse['data']['updated_time'] = isset($params['adjustment_time']) ? intval($params['adjustment_time']) : null; //$this->getUpdateTime();

        $fields = [
			'player_id'		=> $player_id,
		];

		if($success){
			if(!$isAlreadyExists){
				if($isPrevious){
					$this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->game_api->getTransactionsPreviousTable(), ['external_uniqueid' => $transDetails['external_uniqueid']], ['trans_status' => GAME_LOGS::STATUS_REFUND]);
				} else {
					$this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->game_api->getTransactionsTable(), ['external_uniqueid' => $transDetails['external_uniqueid']], ['trans_status' => GAME_LOGS::STATUS_REFUND]);
				}
			}
		}
		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
	}

	public function unixtimeToDateTime($timestamp){
		$timestamp = $timestamp/1000;
		return date('Y-m-d H:i:s', $timestamp);
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
            if (is_array($rule)) {
                foreach ($rule as $value) {
                    if($value=='required'&&!isset($request[$key])){
                        $this->utils->error_log("PGSOFT SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
                        return false;
                    }
        
                    if($value=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
                        $this->utils->error_log("PGSOFT SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
                        return false;
                    }
        
                    if($value=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
                        $this->utils->error_log("PGSOFT SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
                        return false;
                    }
                }
            } else {
                if($rule=='required'&&!isset($request[$key])){
                    $this->utils->error_log("PGSOFT SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
                    return false;
                }
    
                if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
                    $this->utils->error_log("PGSOFT SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
                    return false;
                }
    
                if($rule=='nonNegative'&&isset($request[$key])&&$request[$key]<0){
                    $this->utils->error_log("PGSOFT SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
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
            case self::ERROR_INVALID_REQUEST:
                $message = lang('Invalid request');
                break;
            case self::ERROR_INVALID_OPERATOR:
                $message = lang('Invalid operator');
                break;
            case self::ERROR_INVALID_OPERATOR:
                $message = lang('Invalid operator/key');
                break;
            case self::ERROR_INVALID_OPERATOR:
                $message = lang('Invalid operator/key');
                break;
            case self::ERROR_INVALID_PLAYER_SESSION_TOKEN:
                $message = lang('Invalid Player session token');
                break;
            case self::ERROR_INVALID_PLAYER:
                $message = lang('Invalid Player');
                break;
            case self::ERROR_INTERNAL_SERVER_ERROR:
                $message = lang('Internal server error');
                break;
            case self::ERROR_INSUFFICIENT_BALANCE:
                $message = lang('Insufficient player balance');
                break;
            case self::ERROR_BET_ALREADY_EXIST:
                $message = lang('Bet already existed');
                break;
            case self::ERROR_BET_FAILED:
                $message = lang('Bet failed');
                break;
            case self::ERROR_BET_DOES_NOT_EXIST:
                $message = lang('Bet does not exist');
                break;
            case self::ERROR_PAYOUT_FAILED:
                $message = lang('Payout failed');
                break;
            case self::ERROR_INVALID_PLAYER_SESSION:
                $message = lang('Invalid Player session');
                break;
            case self::ERROR_PLAYER_SESSION_EXPIRED:
                $message = lang('Player session expired');
                break;
            case self::ERROR_PARAMS_VALUE_CANNOT_BE_NULL:
                $message = lang('Value cannot be null');
                break;
            case self::ERROR_BET_FAILED_EXCEPTION:
                $message = lang('BetFailedException');
                break;
           	case self::ERROR_CANNOT_ROLLBACK:
                $message = lang('Cannot rollback');
                break;
			default:
				$this->utils->error_log("PGSOFT SEAMLESS SERVICE: (getErrorSuccessMessage) error: ", $code);
				$message = $code;
				break;
		}

		return $message;
	}

	public function getErrorReturnCode($code){
		$return = "1034";

        foreach(self::ERROR_CODE_MAP as $key => $value){
            if($key==$code){
                return $value;
            }
        }

		return $return;
	}

	//default external response template
	public function externalQueryResponse(){
        return ['data'=>[],'error'=>null];
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

	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);

		$httpStatusCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_INTERNAL_SERVER_ERROR];

		if($error_code=='Connection timed out.'){
			$error_code = self::ERROR_INTERNAL_SERVER_ERROR;
		}

		if(isset($error_code) && array_key_exists($error_code, self::HTTP_STATUS_CODE_MAP)){
			$httpStatusCode = self::HTTP_STATUS_CODE_MAP[$error_code];
		}

		if($this->ip_invalid){
			$httpStatusCode = 403;
		}

		//add request_id
		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

        if($error_code<>self::SUCCESS){
			$response['data'] = null;
            $response['error'] = [];
            $response['error']['code'] = $this->getErrorReturnCode($error_code);
            $response['error']['message'] = $this->getErrorSuccessMessage($error_code);
        }else{
			$response['error'] = null;
		}


        //$response['cost_ms'] = $cost;
        //$response['request_id'] = $this->utils->getRequestId();

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### PGSOFT SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
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

	public function getPlayerByUsernameAndToken($gameUsername, $token){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

    public function getUpdateTime(){
        $milliseconds = round(microtime(true) * 1000);
        return $milliseconds;
    }

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_record = $trans_record = $this->makeTransactionRecord($data);
		$tableName = $this->game_api->getTransactionsTable();
        $this->CI->pgsoft_seamless_transactions->setTableName($tableName);
		return $this->CI->pgsoft_seamless_transactions->insertIgnoreRow($trans_record);
	}

	public function makeTransactionRecord($raw_data){
        $transaction_type = isset($raw_data['trans_type']) ? $raw_data['trans_type'] : null;
        $is_end_round = isset($raw_data['is_end_round']) && $raw_data['is_end_round'] == 'True' ? true : false;

		$data = [];
		$data['player_id'] 			                = isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
		$data['operator_token'] 	                = isset($raw_data['operator_token']) ? $raw_data['operator_token'] : null;
		$data['secret_key'] 		                = isset($raw_data['secret_key']) ? $raw_data['secret_key'] : null;
		$data['currency_code'] 			            = isset($raw_data['currency_code']) ? $raw_data['currency_code'] : null;
		$data['player_name'] 			            = isset($raw_data['player_name']) ? $raw_data['player_name'] : null;
		$data['game_id'] 			                = isset($raw_data['game_id']) ? $raw_data['game_id'] : null;
		$data['parent_bet_id'] 			            = isset($raw_data['parent_bet_id']) ? $raw_data['parent_bet_id'] : null;
		$data['bet_id'] 			                = isset($raw_data['bet_id']) ? $raw_data['bet_id'] : null;
		$data['bet_amount'] 			            = isset($raw_data['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['bet_amount']) : null;
		$data['win_amount'] 			            = isset($raw_data['win_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($raw_data['win_amount']) : null;
		$data['transfer_amount'] 			        = isset($raw_data['transfer_amount']) ?  $this->game_api->gameAmountToDBTruncateNumber($raw_data['transfer_amount']) : null;
		$data['orig_bet_amount'] 			    	= isset($raw_data['bet_amount']) ? $raw_data['bet_amount'] : null;
		$data['orig_win_amount'] 			    	= isset($raw_data['win_amount']) ? $raw_data['win_amount'] : null;
		$data['orig_transfer_amount'] 				= isset($raw_data['transfer_amount']) ? $raw_data['transfer_amount'] : null;
		$data['transaction_id'] 			        = isset($raw_data['transaction_id']) ? $raw_data['transaction_id'] : null;
		$data['bet_type'] 			                = isset($raw_data['bet_type']) ? $raw_data['bet_type'] : null;
		$data['updated_time'] 			            = isset($raw_data['updated_time']) ? $raw_data['updated_time'] : null;
		$data['operator_player_session']            = isset($raw_data['operator_player_session']) ? $raw_data['operator_player_session'] : null;
		$data['wallet_type'] 			            = isset($raw_data['wallet_type']) ? $raw_data['wallet_type'] : null;
		$data['is_minus_count'] 			        = isset($raw_data['is_minus_count']) ? $this->processIsParameter($raw_data['is_minus_count']) : null;
		$data['is_validate_bet'] 			        = isset($raw_data['is_validate_bet']) ? $this->processIsParameter($raw_data['is_validate_bet']) : null;
		$data['is_adjustment'] 			            = isset($raw_data['is_adjustment']) ? $this->processIsParameter($raw_data['is_adjustment']) : null;
		$data['is_parent_zero_stake'] 		        = isset($raw_data['is_parent_zero_stake']) ? $this->processIsParameter($raw_data['is_parent_zero_stake']) : null;
		$data['is_feature'] 			            = isset($raw_data['is_feature']) ? $this->processIsParameter($raw_data['is_feature']) : null;
		$data['is_feature_buy'] 			        = isset($raw_data['is_feature_buy']) ? $this->processIsParameter($raw_data['is_feature_buy']) : null;
		$data['is_wager'] 			                = isset($raw_data['is_wager']) ? $this->processIsParameter($raw_data['is_wager']) : null;
		$data['free_game_transaction_id'] 	        = isset($raw_data['free_game_transaction_id']) ? $raw_data['free_game_transaction_id'] : null;
		$data['free_game_name'] 			        = isset($raw_data['free_game_name']) ? $raw_data['free_game_name'] : null;
		$data['free_game_id'] 			            = isset($raw_data['free_game_id']) ? $raw_data['free_game_id'] : null;
		$data['bonus_transaction_id'] 		        = isset($raw_data['bonus_transaction_id']) ? $raw_data['bonus_transaction_id'] : null;
		$data['bonus_name'] 			            = isset($raw_data['bonus_name']) ? $raw_data['bonus_name'] : null;
		$data['bonus_id'] 			                = isset($raw_data['bonus_id']) ? $raw_data['bonus_id'] : null;
		$data['bonus_balance_amount'] 		        = isset($raw_data['bonus_balance_amount']) ? $raw_data['bonus_balance_amount'] : null;
		$data['bonus_ratio_amount'] 		        = isset($raw_data['bonus_ratio_amount']) ? $raw_data['bonus_ratio_amount'] : null;
		$data['jackpot_rtp_contribution_amount'] 	= isset($raw_data['jackpot_rtp_contribution_amount']) ? $raw_data['jackpot_rtp_contribution_amount'] : null;
		$data['adjustment_transaction_id'] 			= isset($raw_data['adjustment_transaction_id']) ? $raw_data['adjustment_transaction_id'] : null;
		$data['adjustment_time'] 			        = isset($raw_data['adjustment_time']) ? $raw_data['adjustment_time'] : null;
		$data['adjustment_time_parsed'] 			= isset($raw_data['adjustment_time_parsed']) ? $raw_data['adjustment_time_parsed'] : null;
		$data['adjustment_transaction_type'] 		= isset($raw_data['adjustment_transaction_type']) ? $raw_data['adjustment_transaction_type'] : null;

		//common
        $data['trans_type'] 		                = $transaction_type;
        $data['trans_status'] 		                = $this->transactionStatus($transaction_type, $is_end_round);
		$data['elapsed_time'] 		                = intval($this->utils->getExecutionTimeToNow()*1000);
        $data['external_uniqueid']                  = isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : null;
		$data['balance_adjustment_method']          = isset($raw_data['balance_adjustment_method']) ? $raw_data['balance_adjustment_method'] : null;
		$data['before_balance'] 	                = isset($raw_data['before_balance']) ? floatVal($raw_data['before_balance']) : 0;
		$data['after_balance'] 		                = isset($raw_data['after_balance']) ? floatVal($raw_data['after_balance']) : 0;
		$data['game_platform_id'] 	                = $this->game_platform_id;

		return $data;
	}

    private function transactionStatus($transaction_type, $is_end_round) {
        $status = GAME_LOGS::STATUS_SETTLED;

        if ($transaction_type == self::METHOD_CASH_TRANSFERINOUT && !$is_end_round) {
            $status = GAME_LOGS::STATUS_PENDING;
        }

        return $status;
    }

    /**
     * Parameters that have 'is_' as their prefix will output either 0 or 1
     */
    public function processIsParameter($parameter = null){
        if ($parameter == 'True'){
            return 1;
        } else if ($parameter == 'False'){
            return 0;
        }
        return null;
    }

	public function parseRequest(){
        $request_json = file_get_contents('php://input');
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE raw:", $request_json);

        $this->request = json_decode($request_json, true);

        if (!$this->request){
            parse_str($request_json, $request_json);
            $this->utils->debug_log("PGSOFT SEAMLESS SERVICE raw parsed:", $request_json);
            $this->request = $request_json;
        }

		return $this->request;
	}

	public function getValidPlatformId(){
		$this->game_platform_id = PGSOFT_SEAMLESS_API;
		$multiple_currency_domain_mapping = (array)@$this->utils->getConfig('t1lottery_multiple_currency_domain_mapping');
		if (array_key_exists($this->host_name,$multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
		    $this->game_platform_id  = $multiple_currency_domain_mapping[$this->host_name];
		}

		return;
	}

	public function formatBalance($amount) {
        return floatval(bcdiv($amount, 1,2));
    }

	public function isNumeric($amount){
		return is_numeric($amount);
	}

	public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance, $actionType = 'bet'){
		$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);

		//initialize params
		$player_id			= $params['player_id'];
		$transfer_amount 	= $this->game_api->gameAmountToDBTruncateNumber($params['transfer_amount']);
		$amount 			= abs($this->game_api->gameAmountToDBTruncateNumber($params['transfer_amount']));
		$bet_amount 	    = isset($params['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($params['bet_amount']) : 0;

		//initialize response
		$success = false;
		$isValidAmount = true;
		$insufficientBalance = false;
		$isAlreadyExists = false;
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];

		if($transfer_amount>=0){
			$mode = 'credit';
		}else{
			$mode = 'debit';
		}
		$params['balance_adjustment_method'] = $mode;

		//get and process balance
		$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);

		//$existingBet = $this->pgsoft_seamless_transactions->isTransactionExist($params['transaction_id'], $mode);
		$existingTrans = false;

		$prevRoundData = [];
		
		if(!isset($params['parent_bet_id'])||empty($params['parent_bet_id'])){
			$check_bet_params = ['transaction_id'=>strval($params['transaction_id'])];
		} else {
            $check_bet_params = ['parent_bet_id'=>strval($params['parent_bet_id'])];
        }

		$currentTableName = $this->game_api->getTransactionsTable();
		$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmount)", 'currentTableName', $currentTableName);
		$currentRoundData = $this->pgsoft_seamless_transactions->getRoundData($currentTableName, $check_bet_params);

        $checkOtherTable = $this->game_api->checkOtherTransactionTable();

		if($this->game_api->force_check_other_transaction_table&&$this->game_api->use_monthly_transactions_table){
			$checkOtherTable = true;
		}

		if($checkOtherTable){
			# get prev table
			$prevTranstable = $this->game_api->getTransactionsPreviousTable();

			$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmount)", 'prevTranstable', $prevTranstable);
			# get data from prev table
			$prevRoundData = $this->pgsoft_seamless_transactions->getRoundData($prevTranstable, $check_bet_params);
		}

		$roundData = array_merge($currentRoundData, $prevRoundData);
		foreach($roundData as $roundDataRow){
			if($roundDataRow['transaction_id']==$params['transaction_id']){
				$existingTrans = $roundDataRow;
			}
		}

		# existing transactions/probably retry
		if(!empty($existingTrans)){
			$this->utils->error_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: existing ".$mode, $existingTrans);

			//$getBetDetails = $this->pgsoft_seamless_transactions->getExistingTransaction($params['transaction_id'], $mode);

			$isAlreadyExists = true;
			//Return the previous successful response for the duplicate request.
			$previousBalance = $existingTrans['before_balance'];
			$afterBalance = $existingTrans['after_balance'];
			// $afterBalance = $previousBalance = $get_balance;
			return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		if($mode=='debit' && $this->utils->isEnabledRemoteWalletClient()){
			$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ignored insufficient balance checking bet_amount > get_balance ", $params, $previousBalance, $afterBalance);

		}else{

			# bet not existing proceed
			if (($bet_amount > $get_balance) && ($params['trans_type'] != 'rollback')){
				$insufficientBalance = true;
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: insufficientBalance bet_amount > get_balance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

		}

		# set remote wallet properties
		$TransGuid = isset($params['transaction_id']) ? $params['transaction_id'] : null;
		$uniqueid_of_seamless_service = $this->game_api->getPlatformCode().'-'.$TransGuid;
		$external_game_id = isset($params['game_id']) ? $params['game_id'] : null;

		if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
			$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
		}

		if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
			$this->wallet_model->setGameProviderActionType($actionType);
		}

		if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
			$round_id = isset($params['parent_bet_id']) ? $params['parent_bet_id'] : null;
			$this->wallet_model->setGameProviderRoundId($round_id);
		}        
		
		if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
			$isEnd = isset($params['is_end_round']) && $params['is_end_round']=='True' ? true : false;
			$this->wallet_model->setGameProviderIsEndRound($isEnd);
		}

		if($actionType=='bet-payout'){
			if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
				$betAmount = isset($params['bet_amount'])?$params['bet_amount']:0;
				$this->wallet_model->setGameProviderBetAmount($betAmount);
			}
			if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
				$payoutAmount = isset($params['win_amount'])?$params['win_amount']:0;
				$this->wallet_model->setGameProviderPayoutAmount($payoutAmount);
			}
		}

		#OGP-33517 implement related unique id and related action
		if(
			(isset($params['is_feature']) && $params['is_feature']) ||
			$actionType=='payout' #OGP-34807 fix freespin related action and action
		){
			$parent_bet_id = $params['parent_bet_id'];
			$bet_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsTable(), ['bet_id'=> $parent_bet_id]);
			if(empty($bet_details)){
				$bet_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->game_api->getTransactionsPreviousTable(), ['bet_id'=> $parent_bet_id]);
			}

			$related_action = Wallet_model::REMOTE_RELATED_ACTION_BET_PAYOUT;
			$related_uniqueid = isset($bet_details['transaction_id']) ? 'game-'.$this->game_api->getPlatformCode().'-'.$bet_details['transaction_id'] : null;
			
			if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
				$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
			}
			if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
				$this->wallet_model->setRelatedActionOfSeamlessService($related_action);
			}
		}
		
		if($amount<>0){

			if($get_balance !== false){

				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}

			}else{
				$this->utils->error_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			if($mode=='debit' && $this->utils->isEnabledRemoteWalletClient()){
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ignored insufficient balance checking ", $params, $previousBalance, $afterBalance);
				if($mode=='debit' && $previousBalance < $amount ){
					$afterBalance = $previousBalance;
				}
			} else{
				if($mode=='debit' && $previousBalance < $amount ){
					$afterBalance = $previousBalance;
					$insufficientBalance = true;
					$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_record);
				$isAlreadyExists = true;
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}

			$success = $this->transferGameWallet($player_id, $this->game_platform_id, $mode, $amount, $previousBalance, $afterBalance, $insufficientBalance);

			if(!$success){
				$this->utils->error_log("PGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
			}

			if(is_null($afterBalance)){
				$afterBalance = $this->getPlayerBalance($params['player_name'], $player_id);
			}

		}else{
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;

				if($this->utils->compareResultFloat($amount, '=', 0)){
					if($this->utils->isEnabledRemoteWalletClient()){
						$success = $this->transferGameWallet($player_id, $this->game_platform_id, 'credit', $amount, $previousBalance, $afterBalance, $insufficientBalance);
					} 
				}

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
		$external_game_id = isset($this->request['game_id']) ? $this->request['game_id'] : null;
		$this->wallet_model->setExternalGameId($external_game_id);
		$get_bal_req = $this->game_api->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount, &$beforeBalance=null, &$afterBalance=null, &$insufficientBalance=false, &$remoteErrorCode = null){
		
		$this->utils->debug_log( __METHOD__. " PGSOFT SEAMLESS SERVICEtreated as success remoteErrorCode: " , $beforeBalance, $afterBalance);

		$success = false;

		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
			$success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
		}elseif($mode=='credit'){
			$success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
		}

		if($this->utils->isEnabledRemoteWalletClient()){
			$this->remote_wallet_status = $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
			$this->remote_wallet_params = $this->wallet_model->getRemoteApiParams();
			if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
				$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (transferGameWallet) treated as success remoteErrorCode: " , $remoteErrorCode);
				return true;
			}elseif($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_INSUFFICIENT_BALANCE){
				$insufficientBalance = true;
			}
		}
		return $success;
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
        $this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (bet)");

		if(!$this->initialize($gamePlatformId)){
			echo "Error initialize";
		}

		$username = $_POST['username'];
		$result = $this->game_api->generatePlayerToken($username);
		var_dump($result);
	}

    public function isIPAllowed(){
		return true;
        $success=false;

        $this->backend_api_white_ip_list=$this->utils->getConfig('backend_api_white_ip_list');

        $success=$this->ip->checkWhiteIpListForAdmin(function ($ip, &$payload){
			$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (isIPAllowed)", $ip);
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

		$this->utils->debug_log("PGSOFT SEAMLESS SERVICE: (isIPAllowed)", $success);
        return $success;
    }

}///END OF FILE////////////
