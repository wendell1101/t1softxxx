<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * KPLAY seamless game integration
 * OGP-27432
 *
 * @author  Jerbey Capoquian
* Debit – API to deduct player’s money
* Credit – API to add back player’s money
* Balance – API to get latest player’s money
* Bonus – API to get player’s bonus (Applicable for slot only)
* Buyin – API to deduct player’s money for fishing game (Applicable for fishing only)
* 
* <admin site>/kplay_service_api/debit
* <admin site>/kplay_service_api/balance
* <admin site>/kplay_service_api/credit
* <admin site>/kplay_service_api/bonus
* <admin site>/kplay_service_api/resettle
* <admin site>/kplay_service_api/buyin
 * 
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_kplay_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Kplay_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['debit', 'credit', 'balance', 'bonus', 'buyin', 'resettle', 'results'];
    const STATUS_SUCCESS = 1;
    const STATUS_CANCEL = 1;
    const STATUS_ERROR = 0;
    const KPLAY_EVO_PRODUCT_ID = 1;

    #Response Code
    const RESPONSE_SUCCESS = [
        "status" => 1
    ];
    
    const RESPONSE_ACCESS_DENIED = [
        "status" => 0,
        "error" => "ACCESS_DENIED"
    ];

    const RESPONSE_INVALID_PRODUCT = [
        "status" => 0,
        "error" => "INVALID_PRODUCT"
    ];

    const RESPONSE_INVALID_PARAMETER = [
        "status" => 0,
        "error" => "INVALID_PARAMETER"
    ];

    const RESPONSE_INVALID_USER = [
        "status" => 0,
        "error" => "INVALID_USER"
    ];

    const RESPONSE_INTERNAL_ERROR = [
        "status" => 0,
        "error" => "INTERNAL_ERROR"
    ];

    const RESPONSE_DUPLICATE_DEBIT = [
        "status" => 0,
        "error" => "DUPLICATE_DEBIT"
    ];

    const RESPONSE_DUPLICATE_CREDIT = [
        "status" => 0,
        "error" => "DUPLICATE_CREDIT"
    ];

    const RESPONSE_DUPLICATE_BONUS = [
        "status" => 0,
        "error" => "DUPLICATE_BONUS"
    ];

    const RESPONSE_INVALID_DEBIT = [
        "status" => 0,
        "error" => "INVALID_DEBIT"
    ];

    const RESPONSE_INSUFFICIENT_FUNDS = [
        "status" => 0,
        "error" => "INSUFFICIENT_FUNDS"
    ];

    const RESPONSE_UNKNOWN_ERROR = [
        "status" => 0,
        "error" => "UNKNOWN_ERROR"
    ];

    const RESPONSE_FORBIDDEN = [
        "status" => 0,
        "error" => "ACCESS_DENIED"
    ];

    const RESPONSE_FORCE_FAILED = [
        "status" => 0,
        "error" => "FORCE_FAILED"
    ];
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'external_system', 'player_model', 'game_provider_auth'));
    }

    private function getPlatformIdByProductId($request){
        $prd_id = isset($request['prd_id']) ? $request['prd_id'] : null;
        $defaultPlaformId = null;
        if(!empty($prd_id)){
            switch ($prd_id) {
                case self::KPLAY_EVO_PRODUCT_ID:
                    $defaultPlaformId = KPLAY_EVO_SEAMLESS_GAME_API;
                    break;
                
                default:
                    $defaultPlaformId = KPLAY_SEAMLESS_GAME_API;
                    break;
            }
        }
        return $defaultPlaformId;
    }

    public function api($method = null) {
        $this->request_headers = $this->input->request_headers();
        $this->request = $request = json_decode(file_get_contents('php://input'), true);
        $this->game_platform_id = $this->getPlatformIdByProductId($this->request);
        $this->request_method = $method;
        $this->player_id = null;
        $this->ip_invalid = false;

        $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API service request_headers', $this->request_headers);
        $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API service method', $method);
        $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API service request', $request);

        if(empty($method)){
            return $this->returnJsonResult(self::RESPONSE_INVALID_PARAMETER);
        }

        $this->api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->api) {
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult(self::RESPONSE_INTERNAL_ERROR);
        } 

        $secret_key = $this->api->agent_secret_key;
        $request_secret_key = isset($this->request_headers['Secret-Key']) ? $this->request_headers['Secret-Key'] : null; 
        if(empty($request_secret_key)){
            $request_secret_key = isset($request['secret-key']) ? $request['secret-key'] : null; 
        }
        if($secret_key != $request_secret_key){
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult(self::RESPONSE_ACCESS_DENIED);
        }

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!$this->api) {
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::RESPONSE_FORBIDDEN;
            $error_response['error'] = "ACCESS_DENIED.{$ip}";
            $this->ip_invalid = true;
            return $this->setResponse($error_response);
        }
    
        if(!$this->external_system->isGameApiActive($this->game_platform_id) || $this->external_system->isGameApiMaintenance($this->game_platform_id)) {
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
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

    private function balance(){
        $request = $this->request;
        $external_account_id = isset($request["user_id"]) ? $request["user_id"] : null;
        if(empty($external_account_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $this->player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(!$this->player_id){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
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

        if($success){
            $response = self::RESPONSE_SUCCESS;
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            return $this->setResponse($response);
        } else {
            $this->utils->debug_log('KPLAY_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }
    }

    private function debit(){
        if($this->api->force_bet_failed_response){ #force to response failed bet
            return $this->setResponse(self::RESPONSE_FORCE_FAILED);
        }
        $request = $this->request;
        $amount = isset($request["amount"]) ? $request["amount"] : 0;
        $external_account_id = isset($request["user_id"]) ? $request["user_id"] : null;
        $txn_id = isset($request["txn_id"]) ? $request["txn_id"] : null;

        if(empty($external_account_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $this->player_id = $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(!$player_id){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if(!$this->isValidAmount($amount)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        if($this->game_provider_auth->isBlockedUsernameInDB($player_id, $this->api->getPlatformCode())){
           return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        $uniqueid = "D".$txn_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_DUPLICATE_DEBIT);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_FUNDS;
                    return false;
                }
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance();
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'debit';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $request['bet_amount'] = $request['amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $afterBalance;
                }

            } else {
                #not enough balance or invalid amount
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_FUNDS;
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

    private function buyin(){
        if($this->api->force_bet_failed_response){ #force to response failed bet
            return $this->setResponse(self::RESPONSE_FORCE_FAILED);
        }

        $request = $this->request;
        $amount = isset($request["amount"]) ? $request["amount"] : 0;
        $external_account_id = isset($request["user_id"]) ? $request["user_id"] : null;
        $txn_id = isset($request["txn_id"]) ? $request["txn_id"] : null;

        if(empty($external_account_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $this->player_id = $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(!$player_id){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if(!$this->isValidAmount($amount)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        if($this->game_provider_auth->isBlockedUsernameInDB($player_id, $this->api->getPlatformCode())){
           return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        $uniqueid = "D".$txn_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_DUPLICATE_DEBIT);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_FUNDS;
                    return false;
                }
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance();
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'buyin';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $request['bet_amount'] = $request['amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $afterBalance;
                }

            } else {
                #not enough balance or invalid amount
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_FUNDS;
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
        if($this->api->force_win_failed_response){ #force to response failed bet
            return $this->setResponse(self::RESPONSE_FORCE_FAILED);
        }
        
        $request = $this->request;
        $amount = isset($request["amount"]) ? $request["amount"] : 0;
        $external_account_id = isset($request["user_id"]) ? $request["user_id"] : null;
        $txn_id = isset($request["txn_id"]) ? $request["txn_id"] : null;

        if(empty($external_account_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $this->player_id = $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(!$player_id){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if(!$this->isValidAmount($amount)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        if($this->game_provider_auth->isBlockedUsernameInDB($player_id, $this->api->getPlatformCode())){
           return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        $uniqueid = "C".$txn_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_DUPLICATE_CREDIT);
        }

        $debit_uniqueid = "D".$txn_id;
        $is_debit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $debit_uniqueid);
        if(!$is_debit_exist){
            return $this->setResponse(self::RESPONSE_INVALID_DEBIT);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance();
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'credit';
                $request['external_unique_id'] = $uniqueid;
                $status = (isset($request['is_cancel']) && $request['is_cancel'] == self::STATUS_CANCEL) ? GAME_LOGS::STATUS_CANCELLED :  GAME_LOGS::STATUS_SETTLED;
                $request['status'] = $status;
                $request['result_amount'] = $request['amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $afterBalance;
                }

            } else {
                #not enough balance or invalid amount
                // if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                //     $error_code = self::RESPONSE_INSUFFICIENT_FUNDS;
                // }
            }
            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($error_code);
        }
    }

    private function bonus(){
        $request = $this->request;
        $amount = isset($request["amount"]) ? $request["amount"] : 0;
        $external_account_id = isset($request["user_id"]) ? $request["user_id"] : null;
        $txn_id = isset($request["txn_id"]) ? $request["txn_id"] : null;

        if(empty($external_account_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $this->player_id = $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(!$player_id){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if(!$this->isValidAmount($amount)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        if($this->game_provider_auth->isBlockedUsernameInDB($player_id, $this->api->getPlatformCode())){
           return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setResponse(self::RESPONSE_INVALID_USER);
        }

        $uniqueid = "B".$txn_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_DUPLICATE_BONUS);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance();
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'bonus';
                $request['external_unique_id'] = $uniqueid;
                $status =  GAME_LOGS::STATUS_SETTLED;
                $request['status'] = $status;
                $request['result_amount'] = $request['amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $afterBalance;
                }

            } else {
                #not enough balance or invalid amount
                // if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                //     $error_code = self::RESPONSE_INSUFFICIENT_FUNDS;
                // }
            }
            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($error_code);
        }
    }



    #Note: Only available for BTI and Pinnacle. will add if have ticket 
    private function resettle(){
        echo "not implemented";
    }

    private function results(){
        $request = $this->request;
        $txn_id = isset($request["txn_id"]) ? $request["txn_id"] : null;
        $prd_id = isset($request["prd_id"]) ? $request["prd_id"] : null;
        if(empty($txn_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $results = $this->api->checkTicketStatus($prd_id, $txn_id);
        $response = array(
            "api_results" => $results,
        );

        $resolve = 1;
        if(isset($results['success']) && $results['success'] ){
            if(isset($results['status']) && $results['status'] == self::STATUS_SUCCESS ){
                if(isset($results['type']) && $results['type'] == $resolve ){
                    // echo 123123;exit();
                    $uniqueid = "C".$txn_id;
                    $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
                    if(!$is_exist){
                        $bet_id = "D".$txn_id;
                        $bet_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $bet_id);
                        if(!empty($bet_details)){
                            $extra_info = json_decode($bet_details['extra_info'], true);
                            $user_id = isset($extra_info['user_id']) ? $extra_info['user_id'] : null;
                            $response['bet_exist'] = true;
                            $response['credit_exist'] = false;
                            $response['missing_credit_request'] = array(
                                "amount" => isset($results["payout"]) ? $results["payout"] : null,
                                "prd_id" => $prd_id,
                                "txn_id" => $txn_id,
                                "user_id" => $user_id,
                                "is_cancel" => isset($results["is_cancel"]) ? $results["is_cancel"] : null,
                            );
                        } else {
                            $response['bet_exist'] = false;
                        }
                    } else{
                        $response['credit_exist'] = true;

                    }
                }
            }
        }
        return $this->returnJsonResult($response);
    }

    private function processRequestData($request){
        $dataToInsert = array(
            "round_id" => isset($request['txn_id']) ? $request['txn_id'] : NULL, 
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "game_id" => isset($request['game_id']) ? $request['game_id'] : NULL, 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "transaction_id" => isset($request['txn_id']) ? $request['txn_id'] : NULL, 
            #default
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => $this->player_id,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "response_result_id" => $this->response_result_id,
            "extra_info" => json_encode($this->request), #actual request
            "start_at" => $this->utils->getNowForMysql(),
            "end_at" => $this->utils->getNowForMysql(),
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            #trans
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,
        );

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
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

