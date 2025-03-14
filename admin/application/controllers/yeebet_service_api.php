<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';
/**
 * Yeebet Single Wallet API Controller
 * OGP-30980
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
    For seamless wallet integration only. Please refer to API 2.8
    APP ID : <provided by provider>
    Secret key : <provided by provider>
    Callback endpoint : <domain>/yeebet_service_api
    Balance interface : /balance
    Deposit interface : /deposit
    Withdraw interface : /withdraw
    Rollback interface : /rollback
 * 
 * Related File
     - game_api_yeebet_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Yeebet_service_api extends BaseController {
    use Seamless_service_api_module;

    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;


    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    const TRANSACTION_TABLE = 'yeebet_seamless_wallet_transactions';
    const ALLOWED_ACTION = ['balance', 'deduct', 'withdraw', 'deposit', 'rollback'];
    const ERROR_CODE_SUCCESS = 0;
    const ERROR_SYSTEM_ERROR = -1000;
    const ERROR_SYSTEM_INTERNAL = -1001;
    const ERROR_PARAMETER = -1002;
    const ERROR_APPID = -1005;
    const ERROR_APPID_EXIST = -1006;
    const ERROR_INVALID_SIGN = -1007;
    const ERROR_EXPIRED = -1008;
    const ERROR_REQUEST = -1009;
    const ERROR_ILLEGAL_IP = -1010;
    const ERROR_USER_REGISTER = -1011;
    const ERROR_USER_NOT_EXIST = -1012 ;
    const ERROR_USER_EXIST = -1013 ;
    const ERROR_USER_PROHIBITED = -1014;
    const ERROR_USER_PASSWORD = -1015;
    const ERROR_USER_BALANCE_INSUFFICIENT = -1020;
    const ERROR_WITHDRAWAL = -1021;
    const ERROR_QUOTA = -1022;
    const ERROR_DEDUCE = -1030;
    const ERROR_VERIFICATION = -1040;
    const ERROR_DOMAIN = -1041;
    const ERROR_ACCOUNT_BALANCE = -3002;
    const ERROR_EXCEED_BET = -3003;
    const ERROR_GAME_DISABLED = -3004;
    const ERROR_WIN_LIMIT = -3006;
    const ERROR_LOSE_LIMIT = -3007;
    const ERROR_NO_BET_LIMIT = -3008;
    const ERROR_BET_LIMIT = -3009;
    const ERROR_BET_NOT_EXIST = -4000;
    const ERROR_FORCE_ERROR = -4001;
    const ERROR_BET_ALREADY_SETTLED = -4002;
    const ERROR_BET_ALREADY_CANCELED = -4003;
    const ERROR_BET_ALREADY_ROLLBACKED = -4004;
    const ERROR_BET_STILL_PENDING = -4005;
    const ERROR_BET_UNKNOWN_STATUS = -4006;
    const ERROR_INVALID_OPERATION = -4007;

    const ERROR_MESSAGE = [
           0 => "Success",
        -1000 => "The system is error, please contact",
        -1001 => "Internal abnormalities,please contact",
        -1002 => "Parameter error,please check",
        -1005 => "Appid error",
        -1006 => "Appid does not exist，please contact",
        -1007 => "Sign error，please check",
        -1008 => "Expired",
        -1009 => "The request is too frequent",
        -1010 => "Illegal IP",
        -1011 => "Not registered, please register first",
        -1012 => "User does not exist",
        -1013 => "User already exists",
        -1014 => "User is prohibited",
        -1015 => "User password error",
        -1020 => "User balance insufficient",
        -1021 => "The amount of withdrawal of deposits is non - zero",
        -1022 => "Quota does not exist",
        -1030 => "Insufficient deduce amount",
        -1040 => "Verification Code Verification code error",
        -1041 => "Illegal domain name",
        -3002 => "Your account has Insufficient funds. [3002]",
        -3003 => "Your stake had exceeded your bet limit settings, please try again. [3003]",
        -3004 => "Game disabled. Please contact your upline for details, thank you. [3004]",
        -3006 => "Win limit hit! Please contact your upline, thank you. [3006]",
        -3007 => "Lose limit hit ! Please contact your upline, thank you. [3007]",
        -3008 => "You have no bet limit setting for this game, please contact your upline to set it. [3008]",
        -3009 => "Your bet limit had been updated, please try again. [3009]",
         #default
        -1146 => "Internal table error.",
        -4000 => "Serial number type [1] not exist.",
        -4001 => "[Internal] Force error.",
        -4002 => "Serial number already settled.",
        -4003 => "Serial number already cancelled.",
        -4004 => "Serial number already rollbacked.",
        -4005 => "Serial number still pending.",
        -4006 => "Serial numbers have unknown status.",
        -4007 => "Invalid type operation.",
    ];

    const TYPE_BET = 1;
    const TYPE_BET_CANCEL = 7;
    const TYPE_SETTLE = 9;
    const TYPE_RESETTLE = 10;
    const TYPE_ROllBACK = 12;

    const TYPE_OPERATION_DEDUCT = [self::TYPE_BET, self::TYPE_BET_CANCEL, self::TYPE_RESETTLE];
    const TYPE_OPERATION_PAY = [self::TYPE_BET_CANCEL, self::TYPE_SETTLE, self::TYPE_RESETTLE];
    const TYPE_OPERATION_ROLLBACK = [self::TYPE_ROllBACK];

    public function getExternalGameId(){
        return YEEBET_SEAMLESS_GAME_API;
    }

    //Only public function used on routes mapping
    public function api($action = null){
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->action = $action;
        $this->requestBody = file_get_contents("php://input");
        $this->requestHeaders = $this->input->request_headers();
        $this->api = $this->utils->loadExternalSystemLibObject($this->getExternalGameId());
        $this->playerId = null;
        $this->url = $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']);
        $this->jsonRequest = false;
        $this->remoteWalletEnabled = $this->ssa_enabled_remote_wallet();

        if($this->api){
            $this->use_remote_wallet_failed_transaction_monthly_table = $this->api->use_remote_wallet_failed_transaction_monthly_table;
        }

        $params = [];
        $response = [];
        if(strtolower($this->requestMethod) == 'get'){
            $urlComponents = parse_url($this->url);
            parse_str($urlComponents['query'], $params);
        }elseif(strtolower($this->requestMethod) == 'post'){
            if(isset($_SERVER["CONTENT_TYPE"]) && strtolower($_SERVER["CONTENT_TYPE"]) == "application/json" ){
                $this->jsonRequest = true;
                $params = json_decode($this->requestBody, true);
            } else {
                parse_str($this->requestBody, $params);
            }
        }

        if($this->action == "getSign"){
            return $this->getSign($params);
        }

        try {
            if(!$this->api) {
                throw new Exception(__LINE__, self::ERROR_SYSTEM_INTERNAL);
            }

            if(!$this->api->validateWhiteIP()){
                throw new Exception(__LINE__, self::ERROR_ILLEGAL_IP);
            }

            if(!method_exists($this, $this->action) || empty($params)) {
                throw new Exception(__LINE__, self::ERROR_PARAMETER);
            }

            if(!in_array($this->action, self::ALLOWED_ACTION)) {
                throw new Exception(__LINE__, self::ERROR_PARAMETER);
            }

            if(in_array($this->action, $this->api->list_of_method_for_force_error)){
                throw new Exception(__LINE__, self::ERROR_FORCE_ERROR);
            }

            $this->validateParams($params);
            $this->authenticate($params);

            list($errorCode, $response) = $this->$action($params);

        } catch (Exception $e) {
            $this->utils->debug_log('yeebet_seamless encounter error at line', $e->getMessage());
            $errorCode = $e->getCode();
        }

        return $this->setResponse($errorCode, $response);
    }

    #Function to validate params
    private function validateParams($params){
        $appid = isset($params['appid']) ? $params['appid'] : null;
        $sign = isset($params['sign']) ? $params['sign'] : null;
        $amount = isset($params['amount']) ? $params['amount'] : null;
        $generatedSign = $this->generateSign($params);
        if($generatedSign != $sign){
            $this->utils->debug_log("yeebet_seamless generated sign {$generatedSign}, request sign {$sign}");
            throw new Exception(__LINE__, self::ERROR_INVALID_SIGN);
        }

        if($this->api->seamless_app_id != $appid){
            throw new Exception(__LINE__, self::ERROR_APPID_EXIST);
        }

        if(!is_null($amount) && !$this->isValidAmount($amount)){
            throw new Exception(__LINE__, self::ERROR_PARAMETER);
        }
        return true;
    }

    #Function for player authentication
    private function authenticate($params){
        $gameUsername = isset($params['username']) ? $params['username'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            throw new Exception(__LINE__, self::ERROR_USER_NOT_EXIST); 
        }

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            throw new Exception(__LINE__, self::ERROR_USER_NOT_EXIST); 
        }
        $this->playerId = $playerId;
        return $playerId;
    }

    //Function to generate sign base from request params
    private function generateSign($params){
        $secretKey = $this->api->seamless_secret_key;
        $data = array_change_key_case($params, CASE_LOWER);
        $data = array_filter($data);

        ksort($data);
        $dataStr = '';
        foreach($data AS $key => $value){
            if($key <> 'sign' && $key <> 'bets'){
                $dataStr .= $key . '=' . $value . '&';
            }
        }
        $dataStr = substr_replace($dataStr, "", -1);
        $encryptedKey = md5($dataStr . "&key=" . $secretKey);
        return $encryptedKey;
    }

    //Function to deduct player balance
    private function deduct($params){
        if(!$this->checkRequiredFields($params, ['appid', 'username', 'amount', 'notifyid', 'type', 'serialnumber', 'sign'])){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        if($this->external_system->isGameApiMaintenance($this->getExternalGameId())){
            throw new Exception(__LINE__, self::ERROR_GAME_DISABLED);   
        }

        $gameUsername = isset($params['username']) ? $params['username'] : null;
        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__, self::ERROR_USER_PROHIBITED); 
        }

        $amount = isset($params['amount']) ? $params['amount'] : null;
        if($amount > 0){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        $uniqueid = isset($params['serialnumber'], $params['type'], $params['notifyid']) ? $params['serialnumber'].'-'.$params['type'].'-'.$params['notifyid'] : null;
        $params['external_uniqueid'] = $uniqueid;
        $exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsTable(), $uniqueid);
        if($exist){
            $notifDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_unique_id'=> $uniqueid],['id', 'after_balance', 'serial_number']);

            return [self::ERROR_CODE_SUCCESS, [
                "balance" => $notifDetails['after_balance'],
                "serialnumber" => $notifDetails['serial_number'],
                "orderno" => $notifDetails['id'],
                ]
            ];
        } else {
            if($this->api->use_monthly_transactions_table){
                $exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsPreviousTable(), $uniqueid);
                if($exist){
                    $notifDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_unique_id'=> $uniqueid],['id', 'after_balance', 'serial_number']);

                    return [self::ERROR_CODE_SUCCESS, [
                        "balance" => $notifDetails['after_balance'],
                        "serialnumber" => $notifDetails['serial_number'],
                        "orderno" => $notifDetails['id'],
                        ]
                    ];
                }
            }
        }

        $type = isset($params['type']) ? $params['type'] : null;
        if(!in_array($type, self::TYPE_OPERATION_DEDUCT)){
            throw new Exception(__LINE__, self::ERROR_INVALID_OPERATION);
        }

        if($type != self::TYPE_BET){
            $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']]);
            if(!$isBetExist){
                if($this->api->use_monthly_transactions_table){
                    $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']]);
                    if(!$isBetExist){
                        throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
                    }
                } else {
                    throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
                }
            }

            list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["serial_number" => $params['serialnumber']]);
            if(empty($lastId)){
                if($this->api->use_monthly_transactions_table){ #try get previous month
                    list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsPreviousTable(), ["serial_number" => $params['serialnumber']]);
                }
            }
            if(!empty($lastStatus)){
                switch ($lastStatus) {
                    case GAME_LOGS::STATUS_VOID:
                    case GAME_LOGS::STATUS_REJECTED:
                    case GAME_LOGS::STATUS_CANCELLED:
                        throw new Exception(__LINE__, self::ERROR_BET_ALREADY_CANCELED); 
                        break;
                    case GAME_LOGS::STATUS_REFUND:
                        throw new Exception(__LINE__, self::ERROR_BET_ALREADY_ROLLBACKED); 
                        break;
                    case GAME_LOGS::STATUS_SETTLED:
                        if($type == self::TYPE_SETTLE && !$this->api->allow_multiple_settlement){
                            throw new Exception(__LINE__, self::ERROR_BET_ALREADY_SETTLED); 
                        }
                        #allow do nothing
                        break;
                    case GAME_LOGS::STATUS_PENDING:
                        #allow do nothing
                        break;
                    default: #pending
                        throw new Exception(__LINE__, self::ERROR_BET_UNKNOWN_STATUS); 
                        break;
                }
            } else{
                throw new Exception(__LINE__, self::ERROR_BET_UNKNOWN_STATUS); 
            }
        }
        
        $errorCode = self::ERROR_SYSTEM_ERROR;
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode) {
            $amount = isset($params['amount']) ? $this->api->gameAmountToDB($params['amount']) : null;
            $uniqueid = isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                return false;
            }

            if($this->remoteWalletEnabled){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET,
                    false
                );
            }

            if($this->utils->compareResultFloat($amount, '<', 0)) {
                $amount = abs($amount);
                if($this->utils->compareResultFloat($amount, '>', $beforeBalance)) {
                    $errorCode = self::ERROR_USER_BALANCE_INSUFFICIENT;
                    return false;
                }

                $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if(!$success){
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }
 
            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        return false;
                    }
                }

                $params['before_balance'] = $beforeBalance;
                $params['after_balance'] = $afterBalance;
                $transId = $this->processRequestData($params);
                if($transId){
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = [
                        "balance" => $afterBalance, 
                        "serialnumber" => $params['serialnumber'],
                        "orderno" => $transId
                    ];
                }
            }
            return $success;
        });
        
        if($success==false || ( $this->api->enable_mock_failed_transaction && in_array( $params['username'], $this->api->enable_mock_failed_transaction_player_list) ) ){
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $params, 'deduct');
        }

        return [$errorCode, $response];
    }

    private function getSbeStatusBytype($type){
        switch ($type) {
            case self::TYPE_BET:#Bet
                return GAME_LOGS::STATUS_PENDING;
                break;
            case self::TYPE_BET_CANCEL:#Bet Cancel
                return GAME_LOGS::STATUS_CANCELLED;
                break;
            case self::TYPE_SETTLE:#Settle
                return GAME_LOGS::STATUS_SETTLED;
                break;
            case self::TYPE_RESETTLE:#Re Settle
                return GAME_LOGS::STATUS_SETTLED;
                break;
            case self::TYPE_ROllBACK: #Rollback
                return GAME_LOGS::STATUS_REFUND;
                break;
            default:
                return GAME_LOGS::STATUS_PENDING;
                break;
        }
    }

    //Function to deduct player balance same us deduct
    private function withdraw($params){
        list($errorCode, $response) = $this->deduct($params);
        return [$errorCode, $response];
    }

    //Function to add player balance 
    private function deposit($params){
        if(!$this->checkRequiredFields($params, ['appid', 'username', 'amount', 'notifyid', 'type', 'serialnumber', 'sign'])){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        if($this->external_system->isGameApiMaintenance($this->getExternalGameId())){
            throw new Exception(__LINE__, self::ERROR_GAME_DISABLED);   
        }

        $gameUsername = isset($params['username']) ? $params['username'] : null;
        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__, self::ERROR_USER_PROHIBITED); 
        }

        $amount = isset($params['amount']) ? $params['amount'] : null;
        if($amount < 0){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        $uniqueid = isset($params['serialnumber'], $params['type'], $params['notifyid']) ? $params['serialnumber'].'-'.$params['type'].'-'.$params['notifyid'] : null;
        $params['external_uniqueid'] = $uniqueid;
        $exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsTable(), $uniqueid);
        if($exist){
            $notifDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_unique_id'=> $uniqueid],['id', 'after_balance', 'serial_number']);

            return [self::ERROR_CODE_SUCCESS, [
                "balance" => $notifDetails['after_balance'],
                "serialnumber" => $notifDetails['serial_number'],
                "orderno" => $notifDetails['id'],
                ]
            ];
        } else {
            if($this->api->use_monthly_transactions_table){
                $exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsPreviousTable(), $uniqueid);
                if($exist){
                    $notifDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_unique_id'=> $uniqueid],['id', 'after_balance', 'serial_number']);

                    return [self::ERROR_CODE_SUCCESS, [
                        "balance" => $notifDetails['after_balance'],
                        "serialnumber" => $notifDetails['serial_number'],
                        "orderno" => $notifDetails['id'],
                        ]
                    ];
                }
            }
        }

        $type = isset($params['type']) ? $params['type'] : null;
        if(!in_array($type, self::TYPE_OPERATION_PAY)){
            throw new Exception(__LINE__, self::ERROR_INVALID_OPERATION);
        }

        $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']], ['external_unique_id']);
        if(empty($betDetails)){
            if($this->api->use_monthly_transactions_table){
                $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']], ['external_unique_id']);
                if(empty($betDetails)){
                    throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
                }
            } else {
                throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
            }
        }

        $params['bet_external_unique_id'] = "game-{$this->api->getPlatformCode()}-{$betDetails['external_unique_id']}";

        // $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']]);
        // if(!$isBetExist){
        //     if($this->api->use_monthly_transactions_table){
        //         $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']]);
        //         if(!$isBetExist){
        //             throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
        //         }
        //     } else {
        //         throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
        //     }
        // }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["serial_number" => $params['serialnumber']]);
        if(empty($lastId)){
            if($this->api->use_monthly_transactions_table){ #try get previous month
                list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsPreviousTable(), ["serial_number" => $params['serialnumber']]);
            }
        }
        if(!empty($lastStatus)){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                case GAME_LOGS::STATUS_REJECTED:
                case GAME_LOGS::STATUS_CANCELLED:
                    throw new Exception(__LINE__, self::ERROR_BET_ALREADY_CANCELED); 
                    break;
                case GAME_LOGS::STATUS_REFUND:
                    throw new Exception(__LINE__, self::ERROR_BET_ALREADY_ROLLBACKED); 
                    break;
                case GAME_LOGS::STATUS_SETTLED:
                    if($type == self::TYPE_SETTLE && !$this->api->allow_multiple_settlement){
                        throw new Exception(__LINE__, self::ERROR_BET_ALREADY_SETTLED); 
                    }
                    #allow do nothing
                    break;
                case GAME_LOGS::STATUS_PENDING:
                    #allow do nothing
                    break;
                default: #pending
                    throw new Exception(__LINE__, self::ERROR_BET_UNKNOWN_STATUS); 
                    break;
            }
        } else{
            throw new Exception(__LINE__, self::ERROR_BET_UNKNOWN_STATUS); 
        }
        

        $errorCode = self::ERROR_SYSTEM_ERROR;
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode) {
            $amount = isset($params['amount']) ? $this->api->gameAmountToDB($params['amount']) : null;
            $uniqueid = isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                return false;
            }

            if($this->remoteWalletEnabled){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT,
                    true,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET
                );
            }

            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if(!$success){
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
                if($this->remoteWalletEnabled){
                    $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
                }
            } else { #default error
                $success = false;
            }
 
            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        return false;
                    }
                }

                $params['before_balance'] = $beforeBalance;
                $params['after_balance'] = $afterBalance;
                $transId = $this->processRequestData($params);
                if($transId){
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = [
                        "balance" => $afterBalance, 
                        "serialnumber" => $params['serialnumber'],
                        "orderno" => $transId
                    ];
                }
            }
            return $success;
        });

        if($success==false || ( $this->api->enable_mock_failed_transaction && in_array( $params['username'], $this->api->enable_mock_failed_transaction_player_list) ) ){
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $params, 'deposit');

        }

        return [$errorCode, $response];
    }

    //Function to add player balance if deposit or deduct balance fail
    private function rollback($params){
        if(!$this->checkRequiredFields($params, ['appid', 'username', 'amount', 'notifyid', 'type', 'serialnumber', 'sign'])){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        if($this->external_system->isGameApiMaintenance($this->getExternalGameId())){
            throw new Exception(__LINE__, self::ERROR_GAME_DISABLED);   
        }

        $gameUsername = isset($params['username']) ? $params['username'] : null;
        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__, self::ERROR_USER_PROHIBITED); 
        }

        $amount = isset($params['amount']) ? $params['amount'] : null;
        if($amount < 0){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }

        $type = isset($params['type']) ? $params['type'] : null;
        if(!in_array($type, self::TYPE_OPERATION_ROLLBACK)){
            throw new Exception(__LINE__, self::ERROR_INVALID_OPERATION);
        }

        $uniqueid = isset($params['serialnumber'], $params['type'], $params['notifyid']) ? $params['serialnumber'].'-'.$params['type'].'-'.$params['notifyid'] : null;
        $params['external_uniqueid'] = $uniqueid;
        $exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsTable(), $uniqueid);
        if($exist){
            $notifDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_unique_id'=> $uniqueid],['id', 'after_balance', 'serial_number']);

            return [self::ERROR_CODE_SUCCESS, [
                "balance" => $notifDetails['after_balance'],
                "serialnumber" => $notifDetails['serial_number'],
                "orderno" => $notifDetails['id'],
                ]
            ];
        } else {
            if($this->api->use_monthly_transactions_table){
                $exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->api->getTransactionsPreviousTable(), $uniqueid);
                if($exist){
                    $notifDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_unique_id'=> $uniqueid],['id', 'after_balance', 'serial_number']);

                    return [self::ERROR_CODE_SUCCESS, [
                        "balance" => $notifDetails['after_balance'],
                        "serialnumber" => $notifDetails['serial_number'],
                        "orderno" => $notifDetails['id'],
                        ]
                    ];
                }
            }
        }

        $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']], ['external_unique_id']);
        if(empty($betDetails)){
            if($this->api->use_monthly_transactions_table){
                $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']], ['external_unique_id']);
                if(empty($betDetails)){
                    throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
                }
            } else {
                throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
            }
        }

        $params['bet_external_unique_id'] = "game-{$this->api->getPlatformCode()}-{$betDetails['external_unique_id']}";

        // $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']]);
        // if(!$isBetExist){
        //     if($this->api->use_monthly_transactions_table){
        //         $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ["type" =>self::TYPE_BET, "serial_number" => $params['serialnumber']]);
        //         if(!$isBetExist){
        //             throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
        //         }
        //     } else {
        //         throw new Exception(__LINE__, self::ERROR_BET_NOT_EXIST); 
        //     } 
        // }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["serial_number" => $params['serialnumber']]);
        if(empty($lastId)){
            if($this->api->use_monthly_transactions_table){ #try get previous month
                list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsPreviousTable(), ["serial_number" => $params['serialnumber']]);
            }
        }
        if(!empty($lastStatus)){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                case GAME_LOGS::STATUS_REJECTED:
                case GAME_LOGS::STATUS_CANCELLED:
                    throw new Exception(__LINE__, self::ERROR_BET_ALREADY_CANCELED); 
                    break;
                case GAME_LOGS::STATUS_REFUND:
                    throw new Exception(__LINE__, self::ERROR_BET_ALREADY_ROLLBACKED); 
                    break;
                case GAME_LOGS::STATUS_SETTLED:
                    throw new Exception(__LINE__, self::ERROR_BET_ALREADY_SETTLED);
                    break;
                case GAME_LOGS::STATUS_PENDING:
                    #allow do nothing
                    break;
                default:
                    throw new Exception(__LINE__, self::ERROR_BET_UNKNOWN_STATUS); 
                    break;
            }
        } else {
            throw new Exception(__LINE__, self::ERROR_BET_UNKNOWN_STATUS); 
        }

        $errorCode = self::ERROR_SYSTEM_ERROR;
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode) {
            $amount = isset($params['amount']) ? $this->api->gameAmountToDB($params['amount']) : null;
            $uniqueid = isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                return false;
            }

            if($this->remoteWalletEnabled){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND,
                    true,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET
                );
            }

            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                if(!$success){
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
                if($this->remoteWalletEnabled){
                    $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
                }
            } else { #default error
                $success = false;
            }
 
            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        return false;
                    }
                }

                $params['before_balance'] = $beforeBalance;
                $params['after_balance'] = $afterBalance;
                $transId = $this->processRequestData($params);
                if($transId){
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = [
                        "balance" => $afterBalance, 
                        "serialnumber" => $params['serialnumber'],
                        "orderno" => $transId
                    ];
                }
            }
            return $success;
        });

        if($success==false || ( $this->api->enable_mock_failed_transaction && in_array( $params['username'], $this->api->enable_mock_failed_transaction_player_list) ) ){
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $params, 'rollback');

        }

        return [$errorCode, $response];
    }

    //Function to get sign of param, #testing only
    private function getSign($params){
        if(!$this->api) {
            return $this->returnJsonResult(["error" => self::ERROR_SYSTEM_INTERNAL]);
        }

        if(!$this->api->validateWhiteIP()){
            return $this->returnJsonResult(["error" => self::ERROR_ILLEGAL_IP]);
        }
        $seamless_secret_key = $this->api->seamless_secret_key;
        $secret_key_param = null;
        if(isset($params['secretkey'])){
            $secret_key_param = $params['secretkey'];
        }
        if($secret_key_param != $seamless_secret_key){
            return $this->returnJsonResult(["error" => "secret key not exist!!"]);
        }

        $secretKey = $secret_key_param;
        $data = array_change_key_case($params, CASE_LOWER);
        $data = array_filter($data);

        ksort($data);
        $dataStr = '';
        foreach($data AS $key => $value){
            if($key <> 'sign' && $key <> 'bets' && $key <> 'secretkey'){
                $dataStr .= $key . '=' . $value . '&';
            }
        }
        $dataStr = substr_replace($dataStr, "", -1);
        $encryptedKey = md5($dataStr . "&key=" . $secretKey);

        return $this->returnJsonResult(["sign" => $encryptedKey]);
    }

    //Function to get player balance
    private function balance($params){
        if(!$this->checkRequiredFields($params, ['appid', 'username', 'notifyid', 'sign'])){
            throw new Exception(__LINE__, self::ERROR_PARAMETER); 
        }
        return [self::ERROR_CODE_SUCCESS, ["balance" => $this->getPlayerBalance()]];
    }

    // Function to get error message by code
    private function getErrorMessage($errorCode) {
        $errorMessage = self::ERROR_MESSAGE;
        $errorMessage =  isset($errorMessage[$errorCode]) ? $errorMessage[$errorCode] : "Unknown Error Code";
        return $errorMessage;
    }

    //Function to check if required params are exist
    private function checkRequiredFields($params, $requiredParams) {
        $keys = array_keys($params);
        $valid = true;
        if(!empty($requiredParams)){
            foreach ($requiredParams as $field) {
                if(!in_array($field, $keys)){
                   $valid = false;
                   break;
                } 
            }
        }
        return $valid;
    }

    //Function to check if amount is value number
    private function isValidAmount($amount){
        $amount= trim($amount);
        if(!is_numeric($amount)) {
            return false;
        } else {
            return true;
        }
    }

    private function processRequestData($params){
        $dataToInsert = array(
            #default
            "game_platform_id" => $this->api->getPlatformCode(), 
            "player_id" => $this->playerId, 

            #params
            "app_id" => isset($params['appid']) ? $params['appid'] : NULL, 
            "user_name" => isset($params['username']) ? $params['username'] : NULL, 
            "amount" => isset($params['amount']) ? $params['amount'] : NULL, 
            "notify_id" => isset($params['notifyid']) ? $params['notifyid'] : NULL, 
            "type" => isset($params['type']) ? $params['type'] : NULL, 
            "serial_number" => isset($params['serialnumber']) ? $params['serialnumber'] : NULL, 
            "sign" => isset($params['sign']) ? $params['sign'] : NULL, 
            "bets" => isset($params['bets']) ? $params['bets'] : NULL, 
            "game_create_time" => isset($params['bets']['createtime']) ? date('Y-m-d H:i:s', $params['bets']['createtime']) : NULL,
            "external_game_id" => isset($params['bets']['gameid']) ? $params['bets']['gameid'] : NULL, 

            #sbe default
            "sbe_status" => isset($params['sbe_status']) ? $params['sbe_status'] : NULL, 
            "before_balance" => isset($params['before_balance']) ? $params['before_balance'] : NULL, 
            "after_balance" => isset($params['after_balance']) ? $params['after_balance'] : NULL, 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000), 
            "response_result_id" => null, 
            "request_id" => $this->utils->getRequestId(), 
            "external_unique_id" => isset($params['external_uniqueid']) ? $params['external_uniqueid'] : NULL,
            "md5_sum" => isset($params['sign']) ? $params['sign'] : NULL,
            "sbe_status" => $this->getSbeStatusBytype($params['type']),
            "request_body" => $this->requestBody,
        );

        if(!empty($this->api->gameid_prefix)){
            $dataToInsert['external_game_id'] = $this->api->gameid_prefix . "-" .  $dataToInsert['external_game_id']; 
        }

        if(is_array($params['bets'])){
            $dataToInsert['bets'] = json_encode($params['bets']);
        }
 
        $transId = $this->original_seamless_wallet_transactions->insertTransactionData($this->api->getTransactionsTable(), $dataToInsert);
        return $transId;
    }

    //Function to get balance of exist player
    private function getPlayerBalance(){
        if($this->playerId){
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                if($this->utils->isEnabledRemoteWalletClient()){
                    $useReadonly = true;
                    return $this->player_model->getPlayerSubWalletBalance($this->playerId, $this->getExternalGameId(), $useReadonly);
                }
            }
            return $this->wallet_model->readonlyMainWalletFromDB($this->playerId);
        } else {
            return false;
        }
    }

    //Function to merge default ouput and response
    private function setResponse($errorCode, $response = []) {
        $defaultResponse = [
            "result" => $errorCode,
            "desc" => $this->getErrorMessage($errorCode),
            "balance" => 0.00
        ];
        $output = array_merge($defaultResponse, (array)$response);
        return $this->setOutput($errorCode, $output);
    }

    //Function to return output and save response and request
    private function setOutput($errorCode, $response = []){
        $extraFields = [
            "full_url" => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI'])
        ];
        
        if($this->playerId){
            $extraFields = [
                'player_id'=> $this->playerId
            ];
        }

        $flag = $errorCode == self::ERROR_CODE_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $encodedString = urlencode( base64_encode($this->requestBody));
        $urlToGetParams = $this->utils->getSystemHost('admin').'/echoinfo/print_encoded_string/' . $encodedString;
        if(!$this->jsonRequest){
            $requestBody = json_encode("access param using this link: {$urlToGetParams}");
        } else {
            $requestBody = $this->requestBody;
        }

        $responseResultId = $this->response_result->saveResponseResult(
            $this->getExternalGameId(),
            $flag,
            $this->action,
            $requestBody,
            $response,
            200,
            null,
            null,
            $extraFields
        );

        return $this->returnJsonResult($response);
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $action = null, $where = []) {

        $this->utils->debug_log('YEEBET SERVICE API', [
            '$data' => $data,
            '$this->requestBody' => $this->requestBody,
        ] );


		$save_data = $md5_data = [
            'transaction_id' => !empty(  $data['external_uniqueid'] ) ?  $data['external_uniqueid'] : null,
            'round_id' => !empty(  $data['bets']['gameroundid'] ) ?  $data['bets']['gameroundid']: null,
            'external_game_id' => $this->getExternalGameId(),
            'player_id' => !empty($this->playerId) ? $this->playerId : null,
            'game_username' => !empty($data['username']) ? $data['username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => $action == 'deduct' ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $action,
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_raw_data' => $this->requestBody,
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
        if ($this->api->use_remote_wallet_failed_transaction_monthly_table) {
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

        $status  =  $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->api->use_remote_wallet_failed_transaction_monthly_table);
        
        $this->utils->debug_log('YEEBET SERVICE API', [
            '$data' => $data,
            '$this->ssa_failed_remote_common_seamless_transactions_table' => $this->ssa_failed_remote_common_seamless_transactions_table,
            '$this->requestBody' => $this->requestBody,
            '$save_data' => $save_data,
            '$status' => $status,
        ] );
        
        return $status;

    }

    private function applyRemoteWalletSettings(
        $params,
        $actionType,
        $isEndRound,
        $relatedActionType = null
    ) {
        if ($this->remoteWalletEnabled) {
            $uniqueId = $this->api->getPlatformCode() . '-' . $params['external_uniqueid'];
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueId);

            $roundId = isset($params['bets']['gameno']) ? $params['bets']['gameno'] : NULL;
            $roundId = isset($params['bets']['gameroundno']) ? $params['bets']['gameroundno'] : $roundId;
            $gameId = isset($params['bets']['gameid']) ? $params['bets']['gameid'] : NULL;
            $relatedUniqueId = isset($params['bet_external_unique_id']) ? $params['bet_external_unique_id'] : NULL;

            if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                $this->wallet_model->setGameProviderRoundId($roundId);
            }
            if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                $this->wallet_model->setGameProviderIsEndRound($isEndRound);
            }
            if (method_exists($this->wallet_model, 'setExternalGameId')) {
                $this->wallet_model->setExternalGameId($gameId);
            }
            if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                $this->wallet_model->setGameProviderActionType($actionType);
            }
            if ($relatedUniqueId && method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                $this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueId);
            }
            if ($relatedActionType && method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                $this->wallet_model->setRelatedActionOfSeamlessService($relatedActionType);
            }
        }
    }

}

