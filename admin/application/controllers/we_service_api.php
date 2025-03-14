<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * WE seamless game integration
 * OGP-27885
 *
 * @author  Jerbey Capoquian
* validate – Validating the token provided by the operator
* balance – Get the player's balance from the operator, the returned balance is
            in cents and must be an integer
* debit – Decrease the player's balance (bet、free bet、transfer in、tips), the
            returned balance is in cents and must be an integer
* credit – Increase the player's balance (win、transfer out、refund), the
            returned balance is in cents and must be an integer.
* rollback – AWhen an error occurs in the game and WE determine the game round is
            canceled, we'll return the balance that must be returned to the
            player in the game round. The returned balance is in cents and must
            be an integer.
* 
* <admin site>/we_service_api/validate
* <admin site>/we_service_api/balance
* <admin site>/we_service_api/debit
* <admin site>/we_service_api/credit
* <admin site>/we_service_api/rollback
 * 
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_we_seamless.php
 */

/*
Operator Integration APIs
    
*/

class We_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 403;
    const ALLOWED_METHOD_PARAMS = ['validate', 'balance', 'debit', 'credit', 'rollback'];
    const STATUS_SUCCESS = 200;



    #Response Code
    const RESPONSE_SUCCESS = [
        "status" => 200
    ];
    
    const RESPONSE_BADREQUEST = [
        "status" => 400,
        "error" => "Bad Request"
    ];

    const RESPONSE_INCORRECT_APP_SECRET = [
        "status" => 401,
        "error" => "Incorrect appSecret"
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        "status" => 402,
        "error" => "Insufficient balance"
    ];

    const RESPONSE_IP_ACCESS_RESTRICTED = [
        "status" => 403,
        "error" => "IP Access Restricted"
    ];

    const RESPONSE_INVALID_TOKEN = [
        "status" => 404,
        "error" => "Invalid Token"
    ];

    const RESPONSE_DUPLICATE_TRANSACTION = [
        "status" => 409,
        "error" => "Duplicate transaction"
    ];

    const RESPONSE_CANT_CREDIT = [
        "status" => 410,
        "error" => "Can't credit the transaction"
    ];

    const RESPONSE_CANT_CANCEL = [
        "status" => 410,
        "error" => "Can’t cancel the transaction"
    ];

    const RESPONSE_INTERNAL_ERROR = [
        "status" => 500,
        "error" => "Internal Server Error"
    ];

    const RESPONSE_FORCE_FAILED = [
        "status" => 401,
        "error" => "FORCE_FAILED"
    ];
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'external_system', 'player_model', 'game_provider_auth'));
    }

    public function api($method = null) {
        $this->request_headers = $this->input->request_headers();
        $this->request = file_get_contents("php://input");
        $request = [];
        parse_str(file_get_contents('php://input'), $request);

        $this->game_platform_id = WE_SEAMLESS_GAME_API;
        $this->request_method = $method;
        $this->player_id = null;
        $this->ip_invalid = false;
        $this->free_bet = false;
        $multiple_data_on_rounds = isset($request['data']) ? true : false;

        $this->utils->debug_log('WE_SEAMLESS_GAME_API service request_headers', $this->request_headers);
        $this->utils->debug_log('WE_SEAMLESS_GAME_API service method', $method);
        $this->utils->debug_log('WE_SEAMLESS_GAME_API service request', $request);

        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;

        if(empty($method)){
            return $this->returnJsonResult(self::RESPONSE_INTERNAL_ERROR, $addOrigin, $origin, $pretty, $partial_output_on_error, self::RESPONSE_INTERNAL_ERROR['status']);
        }

        $this->api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->api) {
            $this->utils->debug_log('WE_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult(self::RESPONSE_INTERNAL_ERROR, $addOrigin, $origin, $pretty, $partial_output_on_error, self::RESPONSE_INTERNAL_ERROR['status']);
        } 

        $app_secret = $this->api->app_secret;
        $request_app_secret = isset($request['appSecret']) ? $request['appSecret'] : null; 
        if($app_secret != $request_app_secret && !$multiple_data_on_rounds){
            return $this->returnJsonResult(self::RESPONSE_INCORRECT_APP_SECRET, $addOrigin, $origin, $pretty, $partial_output_on_error, self::RESPONSE_INCORRECT_APP_SECRET['status']);
        }

        $operator_id = $this->api->operator_id;
        $request_operator_id = isset($request['operatorID']) ? $request['operatorID'] : null; 
        if($operator_id != $request_operator_id && !$multiple_data_on_rounds){
            return $this->returnJsonResult(self::RESPONSE_BADREQUEST, $addOrigin, $origin, $pretty, $partial_output_on_error, self::RESPONSE_BADREQUEST['status']);
        }

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            $this->utils->debug_log('WE_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!$this->api) {
            $this->utils->debug_log('WE_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::RESPONSE_IP_ACCESS_RESTRICTED;
            $error_response['error'] = "ACCESS_DENIED.{$ip}";
            $this->ip_invalid = true;
            return $this->setResponse($error_response);
        }

        $this->allow_negative_on_rollback = $this->api->getSystemInfo('allow_negative_on_rollback', true);
    
        if(!$this->external_system->isGameApiActive($this->game_platform_id) || $this->external_system->isGameApiMaintenance($this->game_platform_id)) {
            $this->utils->debug_log('WE_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('WE_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            $this->utils->debug_log('WE_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        return $this->$method();
    }

    private function isValidAmount($amount){
        $amount= trim($amount);
        if(!is_numeric($amount)) {
            return false;
        } else {
            if($amount < 0 ){
                return false;
            }
            return true;
        }
    }

    
    private function processRequestData($request){
        $dataToInsert = array(
            "round_id" => isset($request['gameRoundID']) ? $request['gameRoundID'] : NULL, 
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            //"game_id" => isset($request['gameID']) ? $this->getGameTypeCode($request['gameID']) : 'unknown', 
            "game_id" => isset($request['gameID']) ? $request['gameID'] : 'unknown', // will use table id for game launch, no need to get game type code
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "transaction_id" => isset($request['betID']) ? $request['betID'] : NULL, 
            #default
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => $this->player_id,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "response_result_id" => $this->response_result_id,
            "extra_info" => json_encode($this->request), #actual request
            "start_at" => isset($request['time']) ? date("Y-m-d H:i:s", $request['time']) : NULL,
            "end_at" => isset($request['time']) ? date("Y-m-d H:i:s", $request['time']) : NULL,
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            #trans
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : 0,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : 0,
        );

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
    }

    // private function getStatusCode($status){
    //     switch (strtolower($status)) {
    //         case 'new':
    //         case 'processing':
    //             return GAME_LOGS::STATUS_PENDING;
    //             break;
    //         case 'complete':
    //             return GAME_LOGS::STATUS_SETTLED;
    //             break;
    //         case 'cancel':
    //             return GAME_LOGS::STATUS_CANCELLED;
    //             break;
            
    //         default:
    //             return GAME_LOGS::STATUS_PENDING;
    //             break;
    //     }
    // }

    private function getGameTypeCode($game_id){
        $array = explode("-", $game_id);
        $second_key = 1;
        if(isset($array[$second_key])){
            return $array[$second_key];
        } else {
            return "unknown";
        }
    }

    
    private function getPlayerBalance($is_locked = true){
        if($this->player_id){
            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                $player_id= $this->player_id;
                $balance = 0;
                $reasonId = null;
                if(!$is_locked){
                    $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, &$balance, &$reasonId) {
                        return  $this->wallet_model->querySeamlessSingleWallet($player_id, $balance, $reasonId);
                    });
                } else {
                    $this->wallet_model->querySeamlessSingleWallet($player_id, $balance, $reasonId);
                }
                return $balance;

            } else {
                $playerInfo = (array)$this->api->getPlayerInfo($this->player_id);
                $playerName = $playerInfo['username'];
                $get_bal_req = $this->api->queryPlayerBalance($playerName);
                if($get_bal_req['success']) {
                    return $get_bal_req['balance'];
                }
                else {
                    return false;
                }
            } 
        } else {
            return false;
        }
    }

    private function validateRequiredParams($request_params, $required_fields){
        $validate_flag = true;
        foreach ($required_fields as $field_key) {
            if (!array_key_exists($field_key, $request_params)) {
                $this->utils->debug_log(__METHOD__, '======= Missing required params', $field_key);
                $validate_flag = false;
            }
        }
        return $validate_flag;
    }

    private function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    private function debit(){
        $request_params = [];
        $required_fields = ['token', 'operatorID', 'appSecret', 'playerID', 'gameID', 'betID', 'gameRoundID', 'betType', 'amount', 'currency', 'type', 'time'];
        parse_str($this->request, $request_params);
        if(!$this->validateRequiredParams($request_params, $required_fields)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        $token = isset($request_params['token']) ? $request_params['token'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_TOKEN);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $game_username = isset($request_params['playerID']) ? $request_params['playerID'] : null;
        if($game_username != $player_details['game_username']){
            return $this->setResponse(self::RESPONSE_INVALID_TOKEN);
        }

        $bet_amount = $amount = isset($request_params["amount"]) ? $this->api->gameAmountToDB($request_params["amount"]) : 0;
        if(!$this->isValidAmount($amount)){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $this->free_bet = isset($request_params["type"]) && $request_params["type"] == "free_bet" ? true : false;
        if($this->free_bet){
            $amount = 0;
        }

        $time = isset($request_params["time"]) ? $request_params["time"] : null;
        if(!$this->isValidTimeStamp($request_params['time'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $bet_id = isset($request_params["betID"]) ? $request_params["betID"] : null;
        $uniqueid = "D".$bet_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_DUPLICATE_TRANSACTION);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $amount, $bet_amount, $request_params, &$response, &$error_code) {
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_BALANCE;
                    return false;
                }
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $amount, $after_balance);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                if(is_null($after_balance)){
                    $after_balance = $this->getPlayerBalance();
                }
                // $afterBalance = $this->getPlayerBalance();
                $request_params['before_balance'] = $before_balance;
                $request_params['after_balance'] = $after_balance;
                $request_params['transaction_type'] = 'debit';
                $request_params['external_unique_id'] = $uniqueid;
                $request_params['status'] = GAME_LOGS::STATUS_PENDING;
                $request_params['amount'] = $amount;
                $request_params['bet_amount'] = $bet_amount;
                $request_params['result_amount'] = -$amount;
                $transId = $this->processRequestData($request_params);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($after_balance);
                    $response['currency'] = $this->api->currency;
                    $response['time'] = $this->utils->getTimestampNow();
                    $response['refID'] = (string) $this->response_result_id;
                }

            } else {
                #not enough balance or invalid amount
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_BALANCE;
                }
            }
            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($error_code);
        }
    }

    private function rollback(){
        $request_params = [];
        $required_fields = ['operatorID', 'appSecret', 'playerID', 'gameID', 'betID', 'amount', 'currency', 'type', 'time'];
        parse_str($this->request, $request_params);
        if(!$this->validateRequiredParams($request_params, $required_fields)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        $game_username = isset($request_params['playerID']) ? $request_params['playerID'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $amount = isset($request_params["amount"]) ? $request_params["amount"] : 0;
        if(!$this->isValidAmount($amount)){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $time = isset($request_params["time"]) ? $request_params["time"] : null;
        if(!$this->isValidTimeStamp($request_params['time'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $bet_id = isset($request_params["betID"]) ? $request_params["betID"] : null;
        $uniqueid = "R".$bet_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_DUPLICATE_TRANSACTION);
        }

        $debit_uniqueid = "D".$bet_id;
        $is_debit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $debit_uniqueid);
        if(!$is_debit_exist){
            return $this->setResponse(self::RESPONSE_CANT_CANCEL);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request_params, $bet_id, &$response, &$error_code) {
            $amount = isset($request_params['amount']) ? $this->api->gameAmountToDB($request_params['amount']) : 0;
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $success = false; #default
            $is_deduct = false;
            $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $bet_amount = $this->common_seamless_wallet_transactions->getAmountBasedInTransactionId($bet_id, 'debit');
                $credit_amount = $this->common_seamless_wallet_transactions->getAmountBasedInTransactionId($bet_id, 'credit');
                $amount = $bet_amount - $credit_amount;
                if($this->utils->compareResultFloat($amount, '>', 0)){
                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                    } else {
                        $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $amount, $after_balance);
                    }
                }

                if($this->utils->compareResultFloat($amount, '<', 0)){
                    $amount = abs($amount);
                    $is_deduct = true;
                    if($this->utils->compareResultFloat($amount, '>', $before_balance) && !$this->allow_negative_on_rollback) {
                        $error_code = self::RESPONSE_INSUFFICIENT_BALANCE;
                        return false;
                    }
                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                    } else {
                        $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                        if($this->allow_negative_on_rollback && empty($enabled_remote_wallet_client_on_currency)){
                            $success = $this->wallet_model->decSubWalletAllowNegative($player_id, $this->api->getPlatformCode(), $amount);
                        } else {
                            $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $amount, $after_balance);
                        }
                    }

                }
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                if(is_null($after_balance)){
                    $after_balance = $this->getPlayerBalance();
                }
                // $afterBalance = $this->getPlayerBalance();
                $request_params['before_balance'] = $before_balance;
                $request_params['after_balance'] = $after_balance;
                $request_params['transaction_type'] = 'rollback';
                $request_params['external_unique_id'] = $uniqueid;
                $request_params['status'] = GAME_LOGS::STATUS_CANCELLED;
                $request_params['amount'] = $amount;
                $request_params['result_amount'] = $is_deduct ? -$amount : $amount;
                $transId = $this->processRequestData($request_params);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($after_balance);
                    $response['currency'] = $this->api->currency;
                    $response['time'] = $this->utils->getTimestampNow();
                    $response['refID'] = (string) $this->response_result_id;
                }

            } 
            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($error_code);
        }
    }

    private function credit(){
        $request_params = [];
        $required_fields = ['data'];
        parse_str($this->request, $request_params);
        if(!$this->validateRequiredParams($request_params, $required_fields)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        $data = isset($request_params['data']) ? json_decode($request_params['data'], true) : [];
        $data = $this->creditSort($data, 'playerID');

        if(!empty($data) && count($data) == 1){
            $keys = array_keys($data);
            $game_username = isset($keys[0]) ? $keys[0] : null;
            if(!empty($game_username)){
                $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
                if(empty($player_details)){
                    return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
                }

                $this->player_id = $player_id = $player_details['player_id'];
                if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
                    return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
                }

                if($this->player_model->isBlocked($player_details['player_id'])){
                    return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
                }

                $player_round_data = isset($data[$game_username]) ? $data[$game_username] : [];
                if(!empty($player_round_data)){
                    $error_code = self::RESPONSE_INTERNAL_ERROR; #default
                    $response = array();
                    $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, &$response, &$error_code, $player_round_data) {
                        $success = false;
                        foreach ($player_round_data as $data) {
                            $success = false; #reset each loop
                            $amount = isset($data['amount']) ? $this->api->gameAmountToDB($data['amount']) : 0;
                            if(!$this->isValidAmount($amount)){
                                $error_code = self::RESPONSE_INTERNAL_ERROR;
                                break;
                            }

                            $time = isset($data["time"]) ? $data["time"] : null;
                            if(!$this->isValidTimeStamp($data['time'])){
                                $error_code = self::RESPONSE_INTERNAL_ERROR;
                                break;
                            }

                            $bet_id = isset($data["betID"]) ? $data["betID"] : null;
                            $uniqueid = "C".$bet_id;
                            $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
                            if($is_exist){
                                $error_code = self::RESPONSE_DUPLICATE_TRANSACTION;
                                break;
                            }

                            $debit_uniqueid = "D".$bet_id;
                            $is_debit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $debit_uniqueid);
                            if(!$is_debit_exist){
                                $error_code = self::RESPONSE_CANT_CREDIT;
                                break;
                            }

                            $rollback_uniquid = "R".$bet_id;
                            $is_rollback_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $rollback_uniquid);
                            if($is_rollback_exist){
                                $error_code = self::RESPONSE_CANT_CREDIT;
                                break;
                            }

                            $before_balance = $this->getPlayerBalance();
                            $after_balance = null;
                            if($this->utils->compareResultFloat($amount, '>', 0)) {
                                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                                } else {
                                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $amount, $after_balance);
                                }
                            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                                $success = true;#allowed amount 0
                            } else { #default error
                                $success = false;
                            }

                            #proceed on success adjustment
                            if($success){
                                $success = false; #reset $success
                                if(is_null($after_balance)){
                                    $after_balance = $this->getPlayerBalance();
                                }
                                // $afterBalance = $this->getPlayerBalance();
                                $data['before_balance'] = $before_balance;
                                $data['after_balance'] = $after_balance;
                                $data['transaction_type'] = 'credit';
                                $data['external_unique_id'] = $uniqueid;
                                $data['status'] = (isset($data['type']) &&  strtolower($data['type']) == "refund") ? GAME_LOGS::STATUS_REFUND : GAME_LOGS::STATUS_SETTLED;
                                $data['result_amount'] = $data['amount'] = $amount;
                                $transId = $this->processRequestData($data);
                                if($transId){
                                    $success = true;
                                    $error_code = $response = self::RESPONSE_SUCCESS;
                                    $response['balance'] = $this->api->dBtoGameAmount($after_balance);
                                    $response['currency'] = $this->api->currency;
                                    $response['time'] = $this->utils->getTimestampNow();
                                    $response['refID'] = (string) $this->response_result_id;
                                } else {
                                    $error_code = self::RESPONSE_INTERNAL_ERROR;
                                    break;
                                }
                            } else {
                                $error_code = self::RESPONSE_INTERNAL_ERROR;
                                break;
                            }  
                        }
                        return $success;
                    });
 
                    if($success && !empty($response)){
                        return $this->setResponse($response);
                    } else {
                        return $this->setResponse($error_code);
                    }
                } else {
                    return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
                }
            } else {
                return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
            }
        } else { #multiple player on credit, should 1 only
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }
    }

    private function creditSort($array, $sortkey){
        foreach ($array as $key=>$val) $output[$val[$sortkey]][]=$val;
        return $output;
    }

    private function validate(){
        $request_params = [];
        $required_fields = ['token', 'operatorID', 'appSecret'];
        parse_str($this->request, $request_params);
        if(!$this->validateRequiredParams($request_params, $required_fields)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }
        $token = isset($request_params['token']) ? $request_params['token'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_TOKEN);
        }

        $this->player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $balance = $this->getPlayerBalance();
        $response = self::RESPONSE_SUCCESS;
        $response['balance'] = $this->api->dBtoGameAmount($balance);
        $response['playerID'] = $player_details['game_username'];
        $response['nickname'] = $player_details['game_username'];
        $response['currency'] = $this->api->currency;
        $response['time'] = $this->utils->getTimestampNow();
        return $this->setResponse($response);
    }

    private function balance(){
        $request_params = [];
        $required_fields = ['token', 'operatorID', 'appSecret', 'playerID'];
        parse_str($this->request, $request_params);
        if(!$this->validateRequiredParams($request_params, $required_fields)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }
        $token = isset($request_params['token']) ? $request_params['token'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_TOKEN);
        }

        $this->player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        $game_username = isset($request_params['playerID']) ? $request_params['playerID'] : null;
        if($game_username != $player_details['game_username']){
            return $this->setResponse(self::RESPONSE_INVALID_TOKEN);
        }

        $balance = 0;
        $success = $this->lockAndTransForPlayerBalance($this->player_id, function() use(&$balance) {
            $balance = $this->getPlayerBalance();
            if($balance === false) {
                $balance = 0;
                return false;
            }
            return true;
        });

        $response = self::RESPONSE_SUCCESS;
        $response['balance'] = $this->api->dBtoGameAmount($balance);
        $response['currency'] = $this->api->currency;
        $response['time'] = $this->utils->getTimestampNow();
        return $this->setResponse($response);
    }

    private function setResponse($response) {
        return $this->setOutput($response);
    }

    private function setOutput($response) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;
        $http_status_code = 0;
        $flag = $response['status'] == self::STATUS_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        

        if($this->response_result_id) {
            $disableResponseResultsTableOnly=$this->utils->getConfig('disabled_response_results_table_only');
            if($disableResponseResultsTableOnly){
                $respRlt = $this->response_result->readNewResponseById($this->response_result_id);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                if($this->ip_invalid){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
                $respRlt['player_id'] = $this->player_id;
                $this->response_result->updateNewResponse($respRlt);
            } else {
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->response_result_id);
                }
    
                $response_result = $this->response_result->getResponseResultById($this->response_result_id);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
    
                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                if($this->ip_invalid){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->response_result_id, null, $this->player_id, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }
        $http_status_code = isset($response['status']) ? $response['status'] : $http_status_code;
        if(isset($response['status'])){
            unset($response['status']);
        }
        return $this->returnJsonResult((object)$response, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code);
    }

    private function setResponseResult(){
        $response_result_id = $this->response_result->saveResponseResult(
            $this->api->getPlatformCode(),
            Response_result::FLAG_NORMAL,
            $this->request_method,
            json_encode($this->request),
            [],#default empty response
            200,
            null,
            null
        );

        return $response_result_id;
    }
}

