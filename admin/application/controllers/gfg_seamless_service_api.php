<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * Good Fortune Gaming(GFG) Single Wallet API Controller
 * OGP-28870
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
 * {{url}}/gfg_seamless_service_api/api/Balance/GetBalance
 * {{url}}/gfg_seamless_service_api/api/Balance/LockBalance
 * {{url}}/gfg_seamless_service_api/api/Balance/UnLockBalance
 *  
 *
 * 
 * Related File
     - game_api_gfg_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Gfg_seamless_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['getBalance', 'lockBalance', "unLockBalance"];
    const METHOD_ALLOWED_IN_MAINTENANCE = ["result"];
    const METHOD_ALLOWED_IN_EXPIRED_TOKEN = ["test"];
    const STATUS_SUCCESS = 0;
    const TRANSACTION_TABLE = 'gfg_seamless_transactions';

    #LOCK BALANCE ERROR CODE 
    const LB_ERROR_CODE_FREQUENT = 9005;
    const LB_ERROR_CODE_INVALID_CREDS = 9001;
    const LB_ERROR_CODE_INVALID_PLAYER = 9002;
    const LB_ERROR_CODE_ACCOUNT_DISABLED = 9003;
    const LB_ERROR_CODE_AMOUNT_ZERO = 9019;
    const LB_ERROR_CODE_BALANCE_INSUFFICIENT = 9004;
    const LB_ERROR_CODE_INVALID_GAME_ID = 9906;
    const LB_ERROR_CODE_PENDING_CONFIRMATION = 9006;
    const LB_ERROR_CODE_DUPLICATE_ORDER_ID = 9008;
    const LB_ERROR_CODE_FAILED_RESERVE = 9009;
    const LB_ERROR_CODE_OPERATION_FAILED = 9013;
    const LB_ERROR_CODE_IN_CONFRIMATION = 9012;
    const LB_ERROR_CODE_FAILED_RESERVE_EXCEPTION = 10009;

    #UNBLOCK BALANCE ERROR CODE
    const UB_ERROR_CODE_FREQUENT = 8001;
    const UB_ERROR_CODE_INVALID_GAME_ID = 8806;
    const UB_ERROR_CODE_INVALID_ORDER_NUMBER = 8502;
    const UB_ERROR_CODE_INVALID_SETTLEMENT_STATUS = 8503;
    const UB_ERROR_CODE_INCONSISTENT_RESERVE_AMOUNT = 8003;
    const UB_ERROR_CODE_SETTLEMENT_FAILED = 8004;
    const UB_ERROR_CODE_BALANCE_UPDATE_FAILED = 8100;
    const UB_ERROR_CODE_SETTLEMENT_FAILED_2 = 8005;
    const UB_ERROR_CODE_SETTLEMENT_FAILED_EXCEPTION = 8019;

    #CUSTOM SBE ERROR CODE
    const CODE_SUCCESS = 0;
    const SBE_ERROR_CODE_IP_RESTRICTION = 403;
    const SBE_ERROR_CODE_UNKNOWN_ERROR = 7000;
    const SBE_ERROR_CODE_PLAYER_NOT_EXIST = 7001;
    const SBE_ERROR_CODE_INVALID_ACCOUNT_FORMAT = 7003;
    const SBE_ERROR_CODE_INVALID_TOKEN = 7004;
    const SBE_ERROR_CODE_INVALID_SIGN = 7005;
    const SBE_ERROR_CODE_PLAYER_ACCOUNT_LOCKED = 7006;
    const SBE_ERROR_CODE_INVALID_PARAM = 7007;

    const ERROR_MESSAGE = [
        #lock balance error code
           0 => "Success",
        9005 => "Request for the same order is too frequent",
        9001 => "Invalid user credentials",
        9002 => "Invalid player",
        9003 => "Account disabled, please contact customer service",
        9019 => "Operation amount cannot be 0",
        9004 => "User balance insufficient",
        9906 => "Invalid gameId:{xxx}",
        9006 => "Pending confirmation",
        9008 => "Duplicate order number (Order number has been released, double-locking)",
        9009 => "Failed to reserve balance",
        9013 => "Operation failed",
        9012 => "In confirmation",
       10009 => "Failed to reserve balance (Exception)",
       #unlock balance error code
        8001 => "Request for the same order is too frequent",
        8806 => "Invalid gameId:{xxx}",
        8502 => "Invalid order number",
        8503 => "Invalid settlement status",
        8003 => "Inconsistent settlement base for reserved amount",
        8004 => "Settlement failed, please try again",
        8100 => "Failed to operate balance",
        8005 => "Settlement failed, please try again",
        8019 => "Settlement exception",
        #sbe error code
        403 => "IP not allowed",
        7000 => "Unknown errors",
        7001 => "Player does not exist",
        7003 => "Invalid account format",
        7004 => "Invalid token",
        7005 => "Invalid signature",
        7006 => "Player account is locked",
        7007 => "Invalid request parameter",
    ];

    const BALANCE_REQUIRED_FIELDS = [
        'token',
        'accountId',
        'timestamp'
    ];

    const LOCKBALANCE_REQUIRED_FIELDS = [
        'token',
        'money',
        'gameId',
        'orderId',
        'accountId',
        'minLockMoney',
        // 'timestamp'
    ];

    const UNLOCKBALANCE_REQUIRED_FIELDS = [
        'token',
        'money',
        'gameId',
        'orderId',
        'accountId',
        'lockMoney',
        // 'timestamp'
    ];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model'));
    }

    public function balance_api($action = null){
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;

        $this->rawRequest = file_get_contents("php://input");
        $this->utils->debug_log("GFG RAW REQUEST >>>>>>>>>>>>>>> {$this->rawRequest}");
        $this->request = $request = json_decode($this->rawRequest, true);

        $this->requestMethod = $action = lcfirst($action);
        // $this->request = $request= json_decode(file_get_contents('php://input'), true);
        $this->requestHeaders = $this->input->request_headers();
        $requestSign = isset($this->requestHeaders['Authorization']) ? $this->requestHeaders['Authorization'] : null;
        $this->responseResultId = $this->setResponseResult();
        $this->api = $this->utils->loadExternalSystemLibObject(GFG_SEAMLESS_GAME_API);
        $this->playerId = null;
        $this->ipInvalid = false;

        if(!$this->api) {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $this->utils->debug_log('GFG INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError);
        } else{
            if($requestSign !== md5($this->rawRequest.$this->api->api_key)){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_SIGN);
                return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError);
            }
        }

        if(empty($action)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $this->utils->debug_log('GFG INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError);
        }

        if(!$this->responseResultId){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $this->utils->debug_log('GFG INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_IP_RESTRICTION);
            $errorResponse['msg'] = "IP not allowed({$ip})";
            $this->ipInvalid = true;
            return $this->setResponse($errorResponse);
        }

        if(!method_exists($this, $this->requestMethod)) {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $this->utils->debug_log('gfg INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        if(!in_array($this->requestMethod, self::ALLOWED_METHOD_PARAMS)) {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $this->utils->debug_log('gfg INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        if($this->external_system->isGameApiMaintenance(GFG_SEAMLESS_GAME_API)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $errorResponse['msg'] = "Game is on maitenance.";
            $this->utils->debug_log('gfg INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse); 
        }

        $accountId = isset($request['accountId']) ? $request['accountId'] : null;
        $accountArray = explode("_", $accountId);
        $prefix = isset($accountArray['0']) ? $accountArray['0'] : null;
        $gameUsername = isset($accountArray['1']) ? $accountArray['1'] : null;
        if($prefix != $this->api->agent){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_ACCOUNT_FORMAT);
            return $this->setResponse($errorResponse);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_NOT_EXIST);
            return $this->setResponse($errorResponse);
        }

        $token = isset($request['token']) ? $request['token'] : null;
        $tokenDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($tokenDetails) && !in_array($this->requestMethod, self::METHOD_ALLOWED_IN_EXPIRED_TOKEN)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_TOKEN);
            return $this->setResponse($errorResponse);
        } else{
            if($tokenDetails['player_id'] != $playerDetails['player_id']){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_TOKEN);
                return $this->setResponse($errorResponse);
            }
        }

        $this->playerId = $playerDetails['player_id'];
        if($this->api->isBlockedUsernameInDB($playerDetails['game_username'])){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_ACCOUNT_LOCKED);
            return $this->setResponse($errorResponse);
        }

        if($this->player_model->isBlocked($playerDetails['player_id'])){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_ACCOUNT_LOCKED);
            return $this->setResponse($errorResponse);
        }

        return $this->$action();
    }

    private function generateResponseArray($code){
        $response = array(
            "code" => $code,
            "msg" => self::ERROR_MESSAGE[$code]
        );
        return $response;
    }

    private function validateRequest($request, $required_fields) {
        $request_keys = array_keys($request);
        $valid = true;
        if(!empty($required_fields)){
            foreach ($required_fields as $field) {
                if(!in_array($field, $request_keys)){
                   $valid = false;
                   break;
                } 
            }
        }
        return $valid;
    }

    private function unLockBalance(){
        $request = $this->request;
        if(!$this->validateRequest($request, self::UNLOCKBALANCE_REQUIRED_FIELDS)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_PARAM);
            return $this->setResponse($errorResponse);
        }

        $money = isset($request['money']) ? $request['money'] : null;
        if(!$this->isValidAmount($money)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $errorResponse['msg'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        }

        $uniqueid = isset($request['orderId']) ? "ULB".$request['orderId'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $errorResponse['msg'] = "Invalid orderId value.";
            return $this->setResponse($errorResponse);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            $errorResponse = $this->generateResponseArray(self::UB_ERROR_CODE_BALANCE_UPDATE_FAILED);
            return $this->setResponse($errorResponse);
        }

        $gameCode = isset($request['gameId']) ? $request['gameId'] : null;
        $existGameCode = $this->game_description_model->checkIfGameCodeExist($this->api->getPlatformCode(), $gameCode);
        if(!$existGameCode || empty($gameCode)){
            $errorResponse = $this->generateResponseArray(self::LB_ERROR_CODE_INVALID_GAME_ID);
            $errorResponse['msg'] = "Invalid gameId: {$gameCode}";
            return $this->setResponse($errorResponse);
        }

        $orderId = isset($request['orderId']) ? $request['orderId'] : null;
        $orderExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["order_id" =>$orderId]);
        if(!$orderExist){
            $errorResponse = $this->generateResponseArray(self::UB_ERROR_CODE_INVALID_ORDER_NUMBER);
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR); #default
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $request, &$response, &$errorCode, $gameCode) {
            $success = false;
            $money = isset($request['money']) ? $this->api->gameAmountToDB($request['money']) : null;
            $lockMoney = isset($request['lockMoney']) ? $this->api->gameAmountToDB($request['lockMoney']) : null;
            // $beforeBalance = false;
            $beforeBalance = $this->getPlayerBalance();
            $beforeLockBalance = $this->getPlayerLockedWalletBalance();
            if($this->utils->compareResultFloat($lockMoney, '=', $beforeLockBalance)){
                // $success = $this->wallet_model->lockedWalletAllToMainWallet($playerId);
                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                $success = $this->wallet_model->transferLockedWallet($playerId, Wallet_model::TRANSFER_TYPE_OUT, $lockMoney, $reason_id);
                if($success){
                    $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                    if(!empty($enabled_remote_wallet_client_on_currency)){
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-UB-'.$uniqueid;      
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameCode);
                    }
                    $success = $this->wallet_model->incSubWallet($playerId, $this->api->getPlatformCode(), $lockMoney);
                }
            } else {
                $errorCode = $this->generateResponseArray(self::UB_ERROR_CODE_INCONSISTENT_RESERVE_AMOUNT);
                return false;
            }

            if($success){ #success transfer locked wallet
                $beforeBalance = $this->getPlayerBalance();
                if($this->utils->compareResultFloat($money, '>', 0)) {
                    $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                    if(!empty($enabled_remote_wallet_client_on_currency)){
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-UB-W-'.$uniqueid;  
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameCode);  
                    }
                    $success = $this->wallet_model->incSubWallet($playerId, $this->api->getPlatformCode(), $money);
                } elseif ($this->utils->compareResultFloat($money, '<', 0)) {
                    $money = abs($money);
                    if($this->utils->compareResultFloat($money, '>', $beforeBalance)) {
                        $errorCode = $this->generateResponseArray(self::UB_ERROR_CODE_BALANCE_UPDATE_FAILED);
                        return false;
                    }
                    $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                    if(!empty($enabled_remote_wallet_client_on_currency)){
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-UB-L-'.$uniqueid;  
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameCode);
                    }
                    $success = $this->wallet_model->decSubWallet($playerId, $this->api->getPlatformCode(), $money);
                } elseif ($this->utils->compareResultFloat($money, '=', 0)) {
                    $success = true;#allowed amount 0
                } else { #default error
                    $success = false;
                }
            }
            
            // proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance();
                $afterLockedBalance = $this->getPlayerLockedWalletBalance();
                if($beforeBalance === false || $afterBalance === false){
                    return false;
                }
                $request['before_balance'] = $beforeBalance;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'UB';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $request['before_lock_balance'] = $beforeLockBalance;
                $request['after_lock_balance'] = $afterLockedBalance;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = $response = $this->generateResponseArray(self::CODE_SUCCESS);
                    $response['data']['Data'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['timestamp'] = $this->utils->getTimestampNow() * 1000;
                }

            } 
            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    private function lockBalance(){
        $request = $this->request;
        if(!$this->validateRequest($request, self::LOCKBALANCE_REQUIRED_FIELDS)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_PARAM);
            return $this->setResponse($errorResponse);
        }

        $money = isset($request['money']) ? $request['money'] : null;
        if(!$this->isValidAmount($money)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $errorResponse['msg'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        }

        $minLockMoney = isset($request['minLockMoney']) ? $request['minLockMoney'] : null;
        if(!$this->isValidAmount($minLockMoney)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $errorResponse['msg'] = "Invalid min lock money value.";
            return $this->setResponse($errorResponse);
        }

        $uniqueid = isset($request['orderId']) ? "LB".$request['orderId'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
            $errorResponse['msg'] = "Invalid orderId value.";
            return $this->setResponse($errorResponse);
        }

        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($isExist){
            $unlockUniqueId = isset($request['orderId']) ? "ULB".$request['orderId'] : null;
            $isUnlock = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $unlockUniqueId);
            if($isUnlock){
                $errorResponse = $this->generateResponseArray(self::LB_ERROR_CODE_DUPLICATE_ORDER_ID);
                return $this->setResponse($errorResponse);
            } else {
                $balance = $this->getPlayerBalance();
                $playerLock = false;
                $lockBalance = $this->getPlayerLockedWalletBalance($playerLock);
                $response = $this->generateResponseArray(self::CODE_SUCCESS);
                $outputBalance = $balance + $lockBalance;
                $response['data']['money'] = $this->api->dBtoGameAmount($lockBalance);
                $response['data']['balance'] = $this->api->dBtoGameAmount($outputBalance);
                $response['data']['accountId'] = isset($request['accountId']) ? $request['accountId'] : null;
                return $this->setResponse($response);
            }
        }

        $gameCode = isset($request['gameId']) ? $request['gameId'] : null;
        $existGameCode = $this->game_description_model->checkIfGameCodeExist($this->api->getPlatformCode(), $gameCode);
        if(!$existGameCode || empty($gameCode)){
            $errorResponse = $this->generateResponseArray(self::LB_ERROR_CODE_INVALID_GAME_ID);
            $errorResponse['msg'] = "Invalid gameId: {$gameCode}";
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR); #default
        $errorCode['msg'] = "Lock and tranfer failed";
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $request, &$response, &$errorCode, $gameCode) {
            $lockMoney = isset($request['money']) ? $this->api->gameAmountToDB($request['money']) : null;
            $minLockMoney = isset($request['minLockMoney']) ? $this->api->gameAmountToDB($request['minLockMoney']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $beforeLockBalance = $this->getPlayerLockedWalletBalance();
            if($beforeBalance === false || $beforeLockBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Failed fetch balance";
                return false;
            }

            if($this->utils->compareResultFloat($beforeLockBalance, '>', 0)){
                $errorCode = $this->generateResponseArray(self::LB_ERROR_CODE_OPERATION_FAILED);
                return false;
            }

            if($this->utils->compareResultFloat($beforeBalance, '<', 0)){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Negative balance";
                return false; 
            }

            if($this->utils->compareResultFloat($beforeBalance, '=', 0)){
                if($this->utils->compareResultFloat($lockMoney, '<=', 0)){
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                    $errorCode['msg'] = "Invalid lock money value";
                    return false;
                }

                if($this->utils->compareResultFloat($minLockMoney, '>', 0)){
                    $errorCode = $this->generateResponseArray(self::LB_ERROR_CODE_BALANCE_INSUFFICIENT);
                    $errorCode['msg'] = "Balance 0 and minimum lock money > 0";
                    return false;
                }

                if($this->utils->compareResultFloat($minLockMoney, '<', 0)){
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                    $errorCode['msg'] = "Balance 0 and minimum lock money < 0";
                    return false;
                }

                if($this->utils->compareResultFloat($minLockMoney, '=', 0)){ #success allow 0 like fishing
                    $request['before_balance'] = 0;
                    $request['after_balance'] = 0;
                    $request['transaction_type'] = 'LB';
                    $request['external_unique_id'] = $uniqueid;
                    $request['status'] = GAME_LOGS::STATUS_PENDING;
                    $request['before_lock_balance'] = 0;
                    $request['after_lock_balance'] = 0;
                    $success = $this->processRequestData($request);
                    if(!$success){
                        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                        $errorCode['msg'] = "Failed trans 0:0";
                        return false;
                    }
                    $errorCode = $response = $this->generateResponseArray(self::CODE_SUCCESS);
                    $response['data']['money'] = 0;
                    $response['data']['balance'] = 0;
                    $response['data']['accountId'] = isset($request['accountId']) ? $request['accountId'] : null;
                    return true; #sucess
                }
            }

            if($this->utils->compareResultFloat($lockMoney, '<=', 0)){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Invalid money value";
                return false;
            }

            if($this->utils->compareResultFloat($minLockMoney, '>', $lockMoney)){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_PARAM);
                return false;
            }

            if($this->utils->compareResultFloat($beforeBalance, '<', $minLockMoney)) {
                $errorCode = $this->generateResponseArray(self::LB_ERROR_CODE_BALANCE_INSUFFICIENT);
                $errorCode['msg'] = "Balance less than minimum lock money";
                return false;
            }

            $lockBalance = null;
            if($this->utils->compareResultFloat($beforeBalance, '>=', $minLockMoney) && $this->utils->compareResultFloat($beforeBalance, '<', $lockMoney)) {
                $lockBalance = $beforeBalance;
            }

            if($this->utils->compareResultFloat($beforeBalance, '>=', $lockMoney)) {
                $lockBalance = $lockMoney;
            }

            if($this->utils->compareResultFloat($lockBalance, '<=', 0) || is_null($lockBalance)){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Invalid lock balance value";
                return false;
            }

            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameCode);
            } 

            $success = $this->wallet_model->decSubWallet($playerId, $this->api->getPlatformCode(), $lockBalance);
            if(!$success){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Failed decrease wallet {$lockBalance}";
                return false;
            }

            $reason_id=Abstract_game_api::REASON_UNKNOWN;
            $success = $this->wallet_model->transferLockedWallet($playerId, Wallet_model::TRANSFER_TYPE_IN, $lockBalance, $reason_id);
            if(!$success){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Failed transfer locked wallet {$lockBalance}";
                return false;
            }

            $afterBalance = $this->getPlayerBalance();
            $afterLockedBalance = $this->getPlayerLockedWalletBalance();
            if($afterBalance === false || $afterLockedBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Failed fetch balance";
                return false;
            }

            $request['before_balance'] = $beforeBalance;
            $request['after_balance'] = $afterBalance;
            $request['transaction_type'] = 'LB';
            $request['external_unique_id'] = $uniqueid;
            $request['status'] = GAME_LOGS::STATUS_PENDING;
            $request['before_lock_balance'] = $beforeLockBalance;
            $request['after_lock_balance'] = $afterLockedBalance;
            $success = $this->processRequestData($request);
            if(!$success){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
                $errorCode['msg'] = "Failed trans";
                return false;
            }

            $errorCode = $response = $this->generateResponseArray(self::CODE_SUCCESS);
            $outputBalance = $afterBalance + $afterLockedBalance;
            $response['data']['money'] = $this->api->dBtoGameAmount($afterLockedBalance);
            $response['data']['balance'] = $this->api->dBtoGameAmount($outputBalance);
            $response['data']['accountId'] = isset($request['accountId']) ? $request['accountId'] : null;
            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }

    }

    private function getBalance(){
        $request = $this->request;
        if(!$this->validateRequest($request, self::BALANCE_REQUIRED_FIELDS)){
            $response = self::RESPONSE_INVALID_REQUEST_PARAMETER;
            return $this->setResponse($response);
        }
        // $balance = 0;
        // $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$balance) {
            $balance = $this->getPlayerBalance();
            $this->utils->debug_log("GFG getBalance fetch >>>>>>>>>>>>>>> {$balance}");
            // if($balance === false) {
            //     $balance = 0;
            //     return false;
            // }
            // return true;
        // });
        // if($success){
            $response = $this->generateResponseArray(self::CODE_SUCCESS);
            $isLocked = false;
            $lockedBalance = $this->getPlayerLockedWalletBalance($isLocked);
            $response['data']['Data'] = $this->api->dBtoGameAmount($balance+$lockedBalance);
            $response['timestamp'] = $this->utils->getTimestampNow() * 1000;
            return $this->setResponse($response);
        // } else{
        //     $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_UNKNOWN_ERROR);
        //     $this->utils->debug_log('gfg INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
        //     return $this->setResponse($errorResponse);
        // }
        
    }

    private function isValidAmount($amount){
        $amount= trim($amount);
        if(!is_numeric($amount)) {
            return false;
        } else {
            return true;
        }
    }

    
    private function processRequestData($request){
        $md5 = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($request, ['token', 'money', 'orderId', 'accountId', 'gameId'], []);
        $dataToInsert = array(
            "player_id" => $this->playerId, 
            "token" => isset($request['token']) ? $request['token'] : NULL, 
            "game_id" => isset($request['gameId']) ? $request['gameId'] : NULL, 
            "order_id" => isset($request['orderId']) ? $request['orderId'] : NULL, 
            "money" => isset($request['money']) ? $this->api->gameAmountToDB($request['money']) : 0, 
            "min_lock_money" => isset($request['minLockMoney']) ? $this->api->gameAmountToDB($request['minLockMoney']) : 0, 
            "lock_money" => isset($request['lockMoney']) ? $this->api->gameAmountToDB($request['lockMoney']) : 0, 
            "account_id" => isset($request['accountId']) ? $request['accountId'] : NULL, 
            "timestamp" => isset($request['timestamp']) ? $request['timestamp'] : NULL, 
            "date_time" => isset($request['timestamp']) ? date("Y-m-d H:i:s", $request['timestamp']) : NULL, 
            "round_id" => isset($request['roundId']) ? $request['roundId'] : NULL, 
            "json_request" =>  json_encode($this->request), 
            #sbe
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL, 
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL, 
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL, 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL, 
            "response_result_id" => $this->responseResultId,
            "md5_sum" => $md5,
            "before_lock_balance" => isset($request['before_lock_balance']) ? $request['before_lock_balance'] : NULL, 
            "after_lock_balance" => isset($request['after_lock_balance']) ? $request['after_lock_balance'] : NULL, 
        );

        $unix_timestamp_on_ms = 13;
        if(isset($request['timestamp']) &&  strlen($request['timestamp']) == $unix_timestamp_on_ms){
            $dataToInsert['date_time'] = isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp'] / 1000) : NULL;
        }

        $transId = $this->original_seamless_wallet_transactions->insertTransactionData(self::TRANSACTION_TABLE, $dataToInsert);
        return $transId;
    }

    private function getPlayerBalance($is_locked = true){
        if($this->playerId){
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                if($this->utils->isEnabledRemoteWalletClient()){
                    $useReadonly = true;
                    return $this->player_model->getPlayerSubWalletBalance($this->playerId, GFG_SEAMLESS_GAME_API, $useReadonly);
                }
            }
            return $this->wallet_model->readonlyMainWalletFromDB($this->playerId);
            // if($this->utils->getConfig('enable_seamless_single_wallet')) {
            //     $playerId = $this->playerId;
            //     $balance = 0;
            //     $reasonId = null;
            //     if(!$is_locked){
            //         $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, &$balance, &$reasonId) {
            //             return  $this->wallet_model->querySeamlessSingleWallet($playerId, $balance, $reasonId);
            //         });
            //     } else {
            //         $this->wallet_model->querySeamlessSingleWallet($playerId, $balance, $reasonId);
            //     }
            //     return $balance;

            // } else {
            //     $playerInfo = (array)$this->api->getPlayerInfo($this->playerId);
            //     $playerName = $playerInfo['username'];
            //     $result = $this->api->queryPlayerBalance($playerName);
            //     if($result['success']) {
            //         return $result['balance'];
            //     }
            //     else {
            //         return false;
            //     }
            // } 
        } else {
            return false;
        }
    }

    private function getPlayerLockedWalletBalance($isLocked = true){
        if($this->playerId){
            $playerId= $this->playerId;
            $balance = 0;
            $reasonId = null;
            if(!$isLocked){
                $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, &$balance, &$reasonId) {
                    return  $this->wallet_model->queryLockedWallet($playerId, $balance, $reasonId);
                });
            } else {
                $this->wallet_model->queryLockedWallet($playerId, $balance, $reasonId);
            }
            return $balance;
        } else {
            return false;
        }
    }

    private function setResponse($response) {
        return $this->setOutput($response);
    }

    private function setOutput($response) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;
        $statusCode = 0;
        $flag = $response['code'] == self::STATUS_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        

        if($this->responseResultId) {
            $disableResponseResultsTableOnly=$this->utils->getConfig('disabled_response_results_table_only');
            if($disableResponseResultsTableOnly){
                $respRlt = $this->response_result->readNewResponseById($this->responseResultId);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->requestHeaders;
                if($this->ipInvalid){
                    $statusCode = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
                $respRlt['player_id'] = $this->playerId;
                $this->response_result->updateNewResponse($respRlt);
            } else {
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->responseResultId);
                }
    
                $response_result = $this->response_result->getResponseResultById($this->responseResultId);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
    
                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->requestHeaders;
                if($this->ipInvalid){
                    $statusCode = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->responseResultId, null, $this->playerId, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }

        return $this->returnJsonResult((object)$response, $addOrigin, $origin, $pretty, $partialOutputOnError, $statusCode);
    }

    private function setResponseResult(){
        $responseResultId = $this->response_result->saveResponseResult(
            GFG_SEAMLESS_GAME_API,
            Response_result::FLAG_NORMAL,
            $this->requestMethod,
            $this->rawRequest,
            [],#default empty response
            200,
            null,
            null
        );

        return $responseResultId;
    }
}

