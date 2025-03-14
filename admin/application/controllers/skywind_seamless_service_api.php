<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * Sky wind seamless game integration
 * OGP-27294
 *
 * @author  Jerbey Capoquian
 * https://api.example.com/api/validate_ticket
 * https://api.example.com/api/refresh_session
 * https://api.example.com/api/get_balance
 * https://api.example.com/api/debit
 * https://api.example.com/api/credit
 * https://api.example.com/api/rollback
 * 
 * 
 * 
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_skywind_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Skywind_seamless_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['validate_ticket', 'refresh_session', 'get_balance', 'debit', 'credit', 'rollback', 'logout_player', 'get_ticket', 'bonus'];
    const SUCCESS = 0;

    #Response Code
    const RESPONSE_SUCCESS = [
        "error_code" => 0,
    ];

    const ERROR_DUPLICATE_TRANS = [
        "error_code" => 1,
        "error_msg" => "Duplicate transaction"
    ];
    const ERROR_INTERNAL = [
        "error_code" => -1,
        "error_msg" => "Merchant internal error"
    ];
    const ERROR_PLAYER_NOT_FOUND = [
        "error_code" => -2,
        "error_msg" => "Player not found"
    ];
    const ERROR_EXPIRED_TOKEN = [
        "error_code" => -3,
        "error_msg" => "Game token expired"
    ];
    const ERROR_PLAYER_SUSPENDED = [
        "error_code" => -301,
        "error_msg" => "Player is suspended"
    ];
    const ERROR_BET_LIMITEXCEEDED = [
        "error_code" => -302,
        "error_msg" => "Bet Limit Was Exceeded"
    ];
    const ERROR_INSUFFICIENT_FUND = [
        "error_code" => -4,
        "error_msg" => "Insufficient balance"
    ];
    const ERROR_INSUFFICIENT_FREEBET = [
        "error_code" => -5,
        "error_msg" => "Insufficient free bets Balance"
    ];
    const ERROR_INVALID_FREEBET = [
        "error_code" => -6,
        "error_msg" => "Invalid free bet"
    ];
    const ERROR_TRANSACTION_NOT_FOUND = [
        "error_code" => -7,
        "error_msg" => "Transaction not found"
    ];
    const ERROR_FORBIDDEN = [
        "error_code" => -1,
        "error_msg" => "Forbidden: IP address rejected."
    ];
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'external_system', 'player_model'));
    }


    public function api($method = null) {
        if(empty($method)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->returnJsonResult(self::ERROR_INTERNAL);
        }

        $api = SKYWIND_SEAMLESS_GAME_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->returnJsonResult(self::ERROR_INTERNAL);
        } 

        if($method == "generate_sign"){
            return $this->generate_sign();
        }

        $this->request_headers = $this->input->request_headers();
        $request_json = file_get_contents('php://input');
        $this->request = $request = json_decode($request_json, true);
        $this->request_method = $method;

        $this->utils->debug_log('SKYWIND_SEAMLESS_GAME_API service request_headers', $this->request_headers);
        $this->utils->debug_log('SKYWIND_SEAMLESS_GAME_API service method', $method);
        $this->utils->debug_log('SKYWIND_SEAMLESS_GAME_API service request', $request);
        $this->player_id = null;
        $this->ip_invalid = false;
        $this->allow_negative_on_debit = $this->api->getSystemInfo('allow_negative_on_debit', true);
        $this->test_account_ids = $this->api->getSystemInfo('test_account_ids', []);
        $this->prefix_for_username = $this->api->getSystemInfo('prefix_for_username');
        $this->test_account_prefix = $this->api->getSystemInfo('test_account_prefix', "test");
        $this->default_test_account_password = $this->api->getSystemInfo('default_test_account_password',"123456");

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        if(!$this->api) {
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::ERROR_FORBIDDEN;
            $error_response['error_msg'] = "Forbidden: IP address rejected.({$ip})";
            $this->ip_invalid = true;
            return $this->setResponse($error_response);
        }
    
        if(!$this->external_system->isGameApiActive($api) || $this->external_system->isGameApiMaintenance($api)) {
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        return $this->$method();
    }

    private function generate_sign(){
        $request_json = file_get_contents('php://input');
        $request = json_decode($request_json, true);
        if(isset($request['hash'])){
            $hash = $request['hash'];
            unset($request['hash']);
            ksort($request);
            /* $string = "";
            foreach ($request as $key => $val) {
                if(!empty($val)){
                    $string .= $key . "=" .$val;
                }
            }

            $string = $string . $this->api->secret_key; */
            $string = urldecode(http_build_query($request) . $this->api->merch_password);
            $signature =  md5($string);
            $this->utils->debug_log('SKYWIND string >>>>>>>>', $string);
            $this->utils->debug_log('SKYWIND signature >>>>>>>>', $signature);
            $result = array(
                "string" => $string,
                "sign" => $signature
            );

            return $this->returnJsonResult($result);
        }
        return $this->returnJsonResult("No hash on request!!");
    }

    private function validate_merchant_account($request){
        if(isset($request['hash'])){ #for bonus format
            $hash = $request['hash'];
            unset($request['hash']);
            ksort($request);
            /* $string = "";
            foreach ($request as $key => $val) {
                if(!empty($val)){
                    $string .= $key . "=" .$val;
                }
            }

            $string = $string . $this->api->secret_key; */
            $string = urldecode(http_build_query($request) . $this->api->merch_password);
            $signature =  md5($string);
            $this->utils->debug_log('SKYWIND string >>>>>>>>', $string);
            $this->utils->debug_log('SKYWIND signature >>>>>>>>', $signature);
            return $hash == $signature;
        }

        $merch_id = isset($request["merch_id"]) ? $request["merch_id"] : null;
        $merch_pwd = isset($request["merch_pwd"]) ? $request["merch_pwd"] : null;
        if(empty($merch_id) || empty($merch_pwd)){
            return false;
        }

        if($merch_id != $this->api->merch_id){
            return false;
        }

        if($merch_pwd != $this->api->merch_password){
            return false;
        }
        return true;
    }

    private function bonus(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($game_username)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details) || !isset($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        $is_blocked = isset($player_details['blocked']) ? $player_details['blocked'] : false;
        if($is_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $is_game_blocked = isset($player_details['game_blocked']) ? $player_details['game_blocked'] : false;
        if($is_game_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $trx_id = isset($request["trx_id"]) ? $request["trx_id"] : null;
        if(empty($trx_id)){
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $uniqueid = "B".$trx_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $unique_details = (array)$this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            $response = array_merge(self::ERROR_DUPLICATE_TRANS, ["trx_id" => $trx_id, "balance" => $unique_details['after_balance']]);
            return $this->setResponse($response);
            // return $this->setResponse(self::ERROR_DUPLICATE_TRANS);
        }

        $amount = isset($request["amount"]) ? $request["amount"] : 0;
        if(!$this->isValidAmount($amount)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $error_code = self::ERROR_INTERNAL; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $player_name = $player_details['username'];
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
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
                $request['before_balance'] = $before_balance;
                $request['player_id'] = $player_details['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $after_balance;
                $request['transaction_type'] = "bonus";
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $request['result_amount'] = $amount;
                $request['game_code'] = isset($request['promo_type']) ? $request['promo_type'] : "bonus";
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $after_balance;
                    $response['trx_id'] = $request['trx_id'];
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

    #creating test account
    private function get_ticket(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null; #should prefix_username+testaccount prefix ex: dev+test<username>
        if(empty($game_username)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $test_account_prefix = $this->prefix_for_username.$this->test_account_prefix;#should prefix_username+testaccount prefix ex: dev+test<username>
        $prefix_count = strlen($test_account_prefix);
        if(substr( $game_username, 0, $prefix_count ) !== $test_account_prefix){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }


        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(!empty($player_details)){
            $this->player_id = $player_details['player_id'];
            $player_token = $this->common_token->getValidPlayerToken($this->player_id);
            if(!empty($player_token)){
                $response = self::RESPONSE_SUCCESS;
                $response['ticket'] = $player_token;
                return $this->setResponse($response);
            } else {
                list($new_token, $sign_key) = $this->common_token->createTokenWithSignKeyBy($player_details['player_id'], 'player_id');
                $response = self::RESPONSE_SUCCESS;
                $response['ticket'] = $new_token;
                return $this->setResponse($response);
            }  
        } else {
            $prefix_for_username = $this->prefix_for_username;
            $player_username = substr($game_username, strlen($prefix_for_username));
            $password = $this->default_test_account_password;
            $realname = $player_username;
            $username = $player_username;
            $levelId = 1;
            $agentId = null;
            $extra = ["is_demo_flag" => true];
            $playerId = $this->player_model->syncPlayerInfoFromExternal($username, $password, $realname, $levelId, $agentId, $extra);
            if($playerId){
                $rlt = $this->api->createPlayer($username, $playerId, $password, null, $extra);
                if($rlt['success']){
                    list($new_token, $sign_key) = $this->common_token->createTokenWithSignKeyBy($playerId, 'player_id');
                    $response = self::RESPONSE_SUCCESS;
                    $response['ticket'] = $new_token;
                    return $this->setResponse($response);
                } else {
                    $this->utils->debug_log('SKYWIND internal error line', __LINE__);
                    return $this->setResponse(self::ERROR_INTERNAL);
                }
            } else{
                $this->utils->debug_log('SKYWIND internal error line', __LINE__);
                return $this->setResponse(self::ERROR_INTERNAL);
            }
        }
    }

    private function logout_player(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($game_username)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details) || !isset($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        $uniqueid = isset($request["logout_id"]) ? $request["logout_id"] : null;
        if(empty($uniqueid)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            return $this->setResponse(self::ERROR_DUPLICATE_TRANS);
        }
        
        $request['player_id'] = $player_details['player_id'];
        $request['response_result_id'] = $this->response_result_id;
        $request['transaction_type'] = "logout_player";
        $request['external_unique_id'] = $uniqueid;
        $transId = $this->processRequestData($request);
        if($transId){
            return $this->setResponse(self::RESPONSE_SUCCESS);
        }
        return $this->setResponse(self::ERROR_INTERNAL);  
    }

    private function rollback(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        // $token = isset($request["cust_session_id"]) ? $request["cust_session_id"] : null;
        // if(empty($token)){
        //     return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        // }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($game_username)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details) || !isset($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        $is_blocked = isset($player_details['blocked']) ? $player_details['blocked'] : false;
        if($is_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $is_game_blocked = isset($player_details['game_blocked']) ? $player_details['game_blocked'] : false;
        if($is_game_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $trx_id = isset($request["trx_id"]) ? $request["trx_id"] : null;
        if(empty($trx_id)){
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $uniqueid = "R".$trx_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $unique_details = (array)$this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            $response = array_merge(self::ERROR_DUPLICATE_TRANS, ["trx_id" => $trx_id, "balance" => $unique_details['after_balance']]);
            return $this->setResponse($response);
            // return $this->setResponse(self::ERROR_DUPLICATE_TRANS);
        }

        $debitid = "D".$trx_id;
        $is_debit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $debitid);
        if(!$is_debit_exist){
            return $this->setResponse(self::ERROR_TRANSACTION_NOT_FOUND);
        }

        $creditid = "C".$trx_id;
        $is_credit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $creditid);
        if($is_credit_exist){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $bet = (array)$this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $debitid);
        if(empty($bet)){
            return $this->setResponse(self::ERROR_TRANSACTION_NOT_FOUND);
        }

        
        $amount = $request["amount"] = isset($bet["amount"]) ? $bet["amount"] : 0;
        $error_code = self::ERROR_INTERNAL; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $player_name = $player_details['username'];
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
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
                $request['before_balance'] = $before_balance;
                $request['player_id'] = $player_details['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $after_balance;
                $request['transaction_type'] = "rollback";
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_REFUND;
                $request['result_amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $after_balance;
                    $response['trx_id'] = $request['trx_id'];
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
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        // $token = isset($request["cust_session_id"]) ? $request["cust_session_id"] : null;
        // if(empty($token)){
        //     return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        // }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($game_username)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details) || !isset($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        // $player_token = $this->common_token->getValidPlayerToken($this->player_id);
        // if($player_token != $token){
        //     return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        // }

        $is_blocked = isset($player_details['blocked']) ? $player_details['blocked'] : false;
        if($is_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $is_game_blocked = isset($player_details['game_blocked']) ? $player_details['game_blocked'] : false;
        if($is_game_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $trx_id = isset($request["trx_id"]) ? $request["trx_id"] : null;
        if(empty($trx_id)){
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $uniqueid = "C".$trx_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $unique_details = (array)$this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            $response = array_merge(self::ERROR_DUPLICATE_TRANS, ["trx_id" => $trx_id, "balance" => $unique_details['after_balance']]);
            return $this->setResponse($response);
            // return $this->setResponse(self::ERROR_DUPLICATE_TRANS);
        }

        $debitid = "D".$trx_id;
        $is_debit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $debitid);
        if(!$is_debit_exist){
            return $this->setResponse(self::ERROR_TRANSACTION_NOT_FOUND);
        }

        $rollbackid = "R".$trx_id;
        $is_rollback_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $rollbackid);
        if($is_rollback_exist){
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $amount = isset($request["amount"]) ? $request["amount"] : 0;
        if(!$this->isValidAmount($amount)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $error_code = self::ERROR_INTERNAL; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $player_name = $player_details['username'];
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
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
                $request['before_balance'] = $before_balance;
                $request['player_id'] = $player_details['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $after_balance;
                $request['transaction_type'] = "credit";
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $request['result_amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $after_balance;
                    $response['trx_id'] = $request['trx_id'];
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

    private function debit(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $token = isset($request["cust_session_id"]) ? $request["cust_session_id"] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($game_username)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details) || !isset($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        $player_token = $this->common_token->getValidPlayerToken($this->player_id);
        if($player_token != $token){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $is_blocked = isset($player_details['blocked']) ? $player_details['blocked'] : false;
        if($is_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $is_game_blocked = isset($player_details['game_blocked']) ? $player_details['game_blocked'] : false;
        if($is_game_blocked){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $trx_id = isset($request["trx_id"]) ? $request["trx_id"] : null;
        if(empty($trx_id)){
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $uniqueid = "D".$trx_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $unique_details = (array)$this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            $response = array_merge(self::ERROR_DUPLICATE_TRANS, ["trx_id" => $trx_id, "balance" => $unique_details['after_balance']]);
            return $this->setResponse($response);
            // return $this->setResponse(self::ERROR_DUPLICATE_TRANS);
        }

        $amount = isset($request["amount"]) ? $request["amount"] : null;
        if(!$this->isValidAmount($amount)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }
        
        $error_code = self::ERROR_INTERNAL; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $uniqueid, $request, &$response, &$error_code) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : 0;
            $player_name = $player_details['username'];
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::ERROR_INSUFFICIENT_FUND;
                    return false;
                }
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
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
                $request['before_balance'] = $before_balance;
                $request['player_id'] = $player_details['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $after_balance;
                $request['transaction_type'] = 'debit';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $request['bet_amount'] = $amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $after_balance;
                    $response['trx_id'] = $request['trx_id'];
                }

            } else {
                #not enough balance or invalid amount
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::ERROR_INSUFFICIENT_FUND;
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

    private function get_balance(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $token = isset($request["cust_session_id"]) ? $request["cust_session_id"] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($token)){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details) || !isset($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        $player_token = $this->common_token->getValidPlayerToken($this->player_id);
        if($player_token != $token){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }
        
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
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
            $response['currency_code'] = $this->api->currency;
            return $this->setResponse($response);
        } else {
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }
    }

    private function validate_ticket(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $token = isset($request["ticket"]) ? $request["ticket"] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $player_id = $this->api->getPlayerIdByToken($token);
        if(empty($player_id)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $this->player_id = $player_id;
        $refresh = false;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode(), $refresh);
        if(empty($player_details)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $player_token = $this->common_token->getValidPlayerToken($player_details['player_id']);
        if($player_token != $token){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        $test_account = false;
        if($player_details['is_demo_flag'] || in_array($player_id, $this->test_account_ids)){
            $test_account = true;
        }

        $response = self::RESPONSE_SUCCESS;
        $response['cust_session_id'] = $token;
        $response['cust_id'] = $this->api->getGameUsernameByPlayerId($player_id);
        $response['currency_code'] = $this->api->currency;
        $response['test_cust'] = $test_account;
        $response['country'] = "NA"; 
        return $this->setResponse($response);
    }

    private function refresh_session(){
        $request = $this->request;
        $valid_merch = $this->validate_merchant_account($request);
        if(!$valid_merch){
            $this->utils->debug_log('SKYWIND internal error line', __LINE__);
            return $this->setResponse(self::ERROR_INTERNAL);
        }

        $token = isset($request["cust_session_id"]) ? $request["cust_session_id"] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $game_username = isset($request["cust_id"]) ? $request["cust_id"] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $player_id = $this->api->getPlayerIdByToken($token);
        if(empty($player_id)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $this->player_id = $player_id;
        $refresh = false;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode(), $refresh);
        if(empty($player_details)){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        $player_token = $this->common_token->getValidPlayerToken($player_details['player_id']);
        if($player_token != $token){
            return $this->setResponse(self::ERROR_EXPIRED_TOKEN);
        }

        if($game_username != $player_details['game_username']){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }


        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::ERROR_PLAYER_SUSPENDED);
        }

        list($new_token, $sign_key) = $this->common_token->createTokenWithSignKeyBy($player_details['player_id'], 'player_id');
        $response = self::RESPONSE_SUCCESS;
        $response['new_cust_session_id'] = $new_token;
        return $this->setResponse($response);
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
        $request_json = json_encode($this->request);
        $dataToInsert = array(
            "round_id" => isset($request['round_id']) ? $request['round_id'] : NULL, 
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "game_id" => isset($request['game_code']) ? $request['game_code'] : NULL, 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "transaction_id" => isset($request['trx_id']) ? $request['trx_id'] : NULL, 
            #default
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "extra_info" => $request_json, #actual request
            "start_at" => isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp']) : NULL, 
            "end_at" => isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp']) : NULL, 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            #trans
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,
        );

        $unix_timestamp_on_ms = 13;
        if(isset($request['timestamp']) &&  strlen($request['timestamp']) == $unix_timestamp_on_ms){
            $dataToInsert['end_at'] = $dataToInsert['start_at'] = isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp'] / 1000) : NULL;
        }

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
        $http_status_code = 200;
        $flag = $response['error_code'] == self::RESPONSE_SUCCESS['error_code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($this->response_result_id) {
            $disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
            if($disabled_response_results_table_only){
                $respRlt = $this->response_result->readNewResponseById($this->response_result_id);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                $content['costMs'] = intval($this->utils->getExecutionTimeToNow()*1000);
                $content['full_url']=$this->utils->paddingHostHttp($_SERVER['REQUEST_URI']);
                if($this->ip_invalid){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
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
                $content['costMs'] = intval($this->utils->getExecutionTimeToNow()*1000);
                $content['full_url']=$this->utils->paddingHostHttp($_SERVER['REQUEST_URI']);
                if($this->ip_invalid){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->response_result_id, null, $this->player_id, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }
        return $this->returnJsonResult($response, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code);
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

