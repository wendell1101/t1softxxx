<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * Bti seamless game integration
 * OGP-26860
 *
 * @author  Jerbey Capoquian
 * status
 * refresh_session
 * 
 * 
 * 
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_bti_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Bti_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['validateToken', 'reserve', 'debitreserve', 'cancelreserve', 'commitreserve', 'creditcustomer', 'debitcustomer'];
    const SUCCESS = 0;

    #Response Code
    const RESPONSE_SUCCESS = [
        "error_code" => 0,
        "error_message" => "OK"
    ];
    
    const RESPONSE_BADREQUEST = [
        "error_code" => -10,
        "error_message" => "WrongRequest"
    ];

    const RESPONSE_INTERNALSERVERERROR = [
        "error_code" => -1,
        "error_message" => "GeneralError"
    ];

    const RESPONSE_WRONGTOKEN = [
        "error_code" => -3,
        "error_message" => "TokenNotValid"
    ];

    const RESPONSE_CLIENTNOTFOUND = [
        "error_code" => -2,
        "error_message" => "CustomerNotFound"
    ];

    const RESPONSE_CLIENTBLOCKED = [
        "error_code" => -6,
        "error_message" => "RestrictedCustomer"
    ];

    const RESPONSE_SESSIONTIMEOUT = [
        "error_code" => -23,
        "error_message" => "NoExistingSession"
    ];

    const RESPONSE_LOWBALANCE = [
        "error_code" => -4,
        "error_message" => "InsufficientFunds"
    ];

    const RESPONSE_RESERVEIDNOTEXIST = [
        "error_code" => -1,
        "error_message" => "ReserveWasNotFound"
    ];

    const RESPONSE_REQUESTIDALREADYEXIST = [
        "error_code" => -1,
        "error_message" => "RequesIdAlreadyExist"
    ];

    const RESPONSE_AMOUNTINVALID = [
        "error_code" => -1,
        "error_message" => "AmountInvalid"
    ];

    const RESPONSE_DEBITRESERVEBIGGERTHANRESERVE = [
        "error_code" => -1,
        "error_message" => "TotalDebitReserveBiggerThanReserve"
    ];

    const RESPONSE_RESERVEALREADYCANCELED = [
        "error_code" => -1,
        "error_message" => "ReserveAlreadyCanceled"
    ];

    const RESPONSE_RESERVEALREADYCOMMIT = [
        "error_code" => -1,
        "error_message" => "ReserveAlreadyCommit"
    ];

    const RESPONSE_RESERVENOTFOUNDONXML = [
        "error_code" => -1,
        "error_message" => "ReserveNotFoundOnXml"
    ];

    const RESPONSE_FORBIDDEN = [
        "error_code" => -1,
        "error_message" => "IPForbidden"
    ];

    const RESPONSE_INVALIDCUSTOMERFORRESERVE = [
        "error_code" => -1,
        "error_message" => "InvalidCustomerForReserve"
    ];

    const RESPONSE_RESERVEALREADYDEBITED = [
        "error_code" => -1,
        "error_message" => "ReserveAlreadyDebited"
    ];

    
    const WHL_STATUS_CALLBACK = 'status';
    const WHL_REFRESH_SESSION_CALLBACK = 'refresh';
    const ALLOWED_DECIMAL_PLACES_ON_VALIDATING_TOTAL_RESERVE = 0.01;
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'external_system', 'player_model'));
    }


    public function index($method = null) {
        if(empty($method)){
            return $this->returnJsonResult(self::RESPONSE_BADREQUEST);
        }
        $method = lcfirst($method);

        $api = BTI_SEAMLESS_GAME_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->returnJsonResult(self::RESPONSE_INTERNALSERVERERROR);
        } 

        $this->request_headers = $this->input->request_headers();
        $this->request = $request = trim(file_get_contents('php://input'));
        $this->request_method = $method;

        $this->utils->debug_log('BTI_SEAMLESS_GAME_API service request_headers', $this->request_headers);
        $this->utils->debug_log('BTI_SEAMLESS_GAME_API service method', $method);
        $this->utils->debug_log('BTI_SEAMLESS_GAME_API service request', $request);
        $this->player_id = null;
        $this->ip_invalid = false;
        $this->allow_negative_on_debit = $this->api->getSystemInfo('allow_negative_on_debit', true);

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }

        if(!$this->api) {
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::RESPONSE_FORBIDDEN;
            $error_response['error_message'] = "IPAddressRejected.{$ip}";
            $this->ip_invalid = true;
            return $this->setResponse($error_response);
        }
    
        if(!$this->external_system->isGameApiActive($api) || $this->external_system->isGameApiMaintenance($api)) {
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        return $this->$method();
    }

    public function whl($method){
        $token = isset($_GET["token"]) ? $_GET["token"] : null;
        if(strtolower($method) == self::WHL_STATUS_CALLBACK) {
            $response = array(
                'uid' => random_string('unique'),
                'token' => $token,
                'status' => 'anon',
                'message' => 'failure',
                'balance' => '0'
            );
        } else if(strtolower($method) == self::WHL_REFRESH_SESSION_CALLBACK) {
            $response = array(
                'status' => 'failure',
                'token' => $token,
                'message' => 'failure',
                'balance' => '0'
            );
        }

        $api = BTI_SEAMLESS_GAME_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->returnJsonResult($response);
        } 

        
        $player_id = $this->api->getPlayerIdByToken($token);
        if(empty($token) || !$player_id){
            return $this->returnJsonResult($response);
        }

        $this->player_id = $player_id;
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
            if(strtolower($method) == self::WHL_STATUS_CALLBACK) {
                $response = array(
                    'uid' => random_string('unique'),
                    'token' => $token,
                    'status' => $player_id ? 'real' : 'anon',
                    'message' => $player_id ? '' : 'failure',
                    'balance' => (string)$balance
                );
            } else if(strtolower($method) == self::WHL_REFRESH_SESSION_CALLBACK) {
                $response = array(
                    'status' => $player_id ? 'success' : 'failure',
                    'token' => $token,
                    'message' => $player_id ? '' : 'failure',
                    'balance' => (string)$balance
                );
            }
        }
        
        return $this->returnJsonResult($response);
    }

    private function returnJsonB($response){
        $str =  "jsoncb"."(".json_encode($response).");";
        $output = $this->output->set_content_type('application/json')
                        ->set_output($str);
        $this->utils->debug_log('BTI_SEAMLESS_GAME_API service output_sent', $str);
        return $output;
    }

    private function validateToken(){
        $token = isset($_GET["auth_token"]) ? $_GET["auth_token"] : null;
        if(empty($token)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        $player_id = $this->api->getPlayerIdByToken($token);
        if(empty($player_id)){
            return $this->setResponse(self::RESPONSE_WRONGTOKEN);
        }

        $this->player_id = $player_id;
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
            $response['cust_id'] = $this->api->getGameUsernameByPlayerId($player_id);
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            $response['cust_login'] = $response['cust_id'];
            $response['country'] = $response['city'] = "NA"; 
            $response['currency_code'] = $this->api->currency;
            return $this->setResponse($response);
        } else {
            $this->utils->debug_log('BTI_SEAMLESS_GAME_API GENERAL error line', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }
    }

    private function reserve(){
        $game_username = isset($_GET["cust_id"]) ? $_GET["cust_id"] : null;
        $reserve_id = isset($_GET["reserve_id"]) ? $_GET["reserve_id"] : null;
        $reserve_amount = isset($_GET["amount"]) ? $_GET["amount"] : 0;

        if(!$this->isValidAmount($reserve_amount)){
            return $this->setResponse(self::RESPONSE_AMOUNTINVALID);
        }

        if(empty($game_username) || empty($reserve_id)){
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_CLIENTNOTFOUND);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
           return $this->setResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $player_token = $this->common_token->getValidPlayerToken($player_id);
        if(empty($player_token)){
            return $this->setResponse(self::RESPONSE_SESSIONTIMEOUT);
        }

        $is_reserve = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
        if($is_reserve){
            $reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $reserve_id);
            if(!empty($reserve_details)){
                $response = self::RESPONSE_SUCCESS;
                $response['balance'] = $this->api->dBtoGameAmount($reserve_details['after_balance']);
                $response['trx_id'] = $reserve_details['response_result_id'];
                return $this->setResponse($response);
            }
        }

        $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $reserve_id, $reserve_amount, &$response, &$error_code) {
            $amount = isset($reserve_amount) ? $this->api->gameAmountToDB($reserve_amount) : null;
            $player_name = $player_details['username'];
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_LOWBALANCE;
                    return false;
                }
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabled_remote_wallet_client_on_currency)){
                    $unique_id_for_remote=$this->api->getPlatformCode().'-'.$reserve_id;       
                    $this->wallet_model->setUniqueidOfSeamlessService($unique_id_for_remote);
                } 
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
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
                $r_data['before_balance'] = $before_balance;
                $r_data['player_id'] = $player_details['player_id'];
                $r_data['response_result_id'] = $this->response_result_id;
                $r_data['after_balance'] = $afterBalance;
                $r_data['transaction_type'] = 'reserve';
                $r_data['amount'] = $amount;
                $r_data['external_unique_id'] = $reserve_id;
                $r_data['transaction_id'] = $reserve_id;
                $r_data['status'] = GAME_LOGS::STATUS_PENDING;
                $transId = $this->processRequestData($r_data);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['trx_id'] = $this->response_result_id;
                }

            } else {
                #not enough balance or invalid amount
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_LOWBALANCE;
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

    private function debitreserve(){
        $game_username = isset($_GET["cust_id"]) ? $_GET["cust_id"] : null;
        $reserve_id = isset($_GET["reserve_id"]) ? $_GET["reserve_id"] : null;
        $reserve_amount = isset($_GET["amount"]) ? $_GET["amount"] : 0;
        $request_id = isset($_GET["req_id"]) ? $_GET["req_id"] : null;
        $purchase_id = isset($_GET["purchase_id"]) ? $_GET["purchase_id"] : null;

        if(!$this->isValidAmount($reserve_amount)){
            return $this->setNoErrorResponse(self::RESPONSE_AMOUNTINVALID);
        }

        if(empty($game_username) || empty($reserve_id)){
            return $this->setNoErrorResponse(self::RESPONSE_BADREQUEST);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTNOTFOUND);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
           return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $balance = null;
        if($this->player_model->isBlocked($player_id)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $is_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
        if(!$is_reserve_exist){
            $response = self::RESPONSE_RESERVEIDNOTEXIST;
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            return $this->setNoErrorResponse($response);
        } else {
            $reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $reserve_id);
            if(!empty($reserve_details)){
                $balance = $reserve_details['after_balance'];
                $current_debit_reserve_amount = $this->api->gameAmountToDB($reserve_amount);
                $total_debit_reserve = $this->common_seamless_wallet_transactions->getSumAmountByTransactionAndTransType($this->api->getPlatformCode(), $reserve_id, 'debitreserve');
                $total_amount = $current_debit_reserve_amount + $total_debit_reserve;
                $reserve_details_amount = isset($reserve_details['amount']) ? $reserve_details['amount'] : 0;
                
                if($this->utils->compareResultFloat($total_amount, '>', $reserve_details_amount)) {
                    $allowed_decimal = $this->checkDiff($total_amount, $reserve_details_amount);
                    if(!$allowed_decimal){
                        $response = self::RESPONSE_DEBITRESERVEBIGGERTHANRESERVE;
                        $response['balance'] = $this->api->dBtoGameAmount($balance);
                        return $this->setNoErrorResponse($response);
                    } else {
                        $reserve_amount = $reserve_amount - self::ALLOWED_DECIMAL_PLACES_ON_VALIDATING_TOTAL_RESERVE;
                    }
                }
            } else {
                $response = self::RESPONSE_RESERVEIDNOTEXIST;
                $response['balance'] = $this->api->dBtoGameAmount($balance);
                return $this->setNoErrorResponse($response);
            }
        }

        $commit_reserve_id = "CMR_".$reserve_id;
        $is_commit_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $commit_reserve_id);
        if($is_commit_reserve_exist){
            $response = self::RESPONSE_RESERVEALREADYCOMMIT;
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            return $this->setNoErrorResponse($response);
        }

        $cancel_reserve_id = "CR_".$reserve_id;
        $is_cancel_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $cancel_reserve_id);
        if($is_cancel_reserve_exist){
            $response = self::RESPONSE_RESERVEALREADYCANCELED;
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            return $this->setNoErrorResponse($response);
        }

        $is_request_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $request_id);
        if($is_request_exist){
            $response = self::RESPONSE_REQUESTIDALREADYEXIST;
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            return $this->setNoErrorResponse($response);
        }

        $reserve_amount = $this->api->gameAmountToDB($reserve_amount); 

        $dr_data['player_id'] = $player_details['player_id'];
        $dr_data['before_balance'] = $balance;
        $dr_data['after_balance'] = $balance;
        $dr_data['transaction_type'] = 'debitreserve';
        $dr_data['status'] = GAME_LOGS::STATUS_PENDING;
        $dr_data['external_unique_id'] = $request_id; #request id
        $dr_data['transaction_id'] = $reserve_id;# related reserve id
        $dr_data['amount'] = $reserve_amount;
        $dr_data['bet_amount'] = $reserve_amount;
        $dr_data['result_amount'] = -$reserve_amount;
        $dr_data['response_result_id'] = $this->response_result_id;
        $dr_data['purchase_id'] = $purchase_id;
        $transId = $this->processRequestData($dr_data);
        if($transId){
            $response = self::RESPONSE_SUCCESS;
            $response['balance'] = $this->api->dBtoGameAmount($balance);
            $response['trx_id'] = $this->response_result_id;
            return $this->setResponse($response);
        } else {
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);#dont use no error response, so that it will request again incase have failure on saving on db
        }
    }

    private function checkDiff($total_debit_reserve, $reserve_amount, $precision = 2){
        $difference = bcsub($total_debit_reserve, $reserve_amount, $precision);
        $allowed_decimal = $this->api->gameAmountToDB(self::ALLOWED_DECIMAL_PLACES_ON_VALIDATING_TOTAL_RESERVE);
        if(abs($difference) == abs($allowed_decimal)){
            return true;
        } 
        return false;
    }

    private function cancelreserve(){
        $game_username = isset($_GET["cust_id"]) ? $_GET["cust_id"] : null;
        $reserve_id = isset($_GET["reserve_id"]) ? $_GET["reserve_id"] : null;

        if(empty($game_username) || empty($reserve_id)){
            return $this->setNoErrorResponse(self::RESPONSE_BADREQUEST);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTNOTFOUND);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
           return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $is_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
        if(!$is_reserve_exist){
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
        } else {
            $reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $reserve_id);
            if(empty($reserve_details)){
                return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
            }
            if($reserve_details['player_id'] != $player_id){
                return $this->setNoErrorResponse(self::RESPONSE_INVALIDCUSTOMERFORRESERVE);
            }
        }

        $cancel_reserve_id = "CR_".$reserve_id;
        $is_cancel_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $cancel_reserve_id);
        if($is_cancel_reserve_exist){
            $cancel_reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $cancel_reserve_id);
            if(!empty($cancel_reserve_details)){
                $response = self::RESPONSE_RESERVEALREADYCANCELED;
                $response['balance'] = $this->api->dBtoGameAmount($cancel_reserve_details['after_balance']);
                $response['trx_id'] = $cancel_reserve_details['response_result_id'];
                return $this->setNoErrorResponse($response);
            }
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEALREADYCANCELED);
        }

        $debit_reserve_data = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $reserve_id, 'debitreserve');
        if(!empty($debit_reserve_data)){
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEALREADYDEBITED);
        }
        // $is_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
        // if(!$is_reserve_exist){
        //     return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
        // }

        // $reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $reserve_id);
        // if(empty($reserve_details)){
        //     return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
        // }

        $amount = isset($reserve_details['amount']) ? $reserve_details['amount'] : 0;

        $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $reserve_id, $amount, $cancel_reserve_id, &$response, &$error_code) {
            $amount = isset($amount) ? $amount : null;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabled_remote_wallet_client_on_currency)){
                    $unique_id_for_remote=$this->api->getPlatformCode().'-'.$cancel_reserve_id;       
                    $this->wallet_model->setUniqueidOfSeamlessService($unique_id_for_remote);
                } 

                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
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
                $r_data['before_balance'] = $before_balance;
                $r_data['player_id'] = $player_details['player_id'];
                $r_data['response_result_id'] = $this->response_result_id;
                $r_data['after_balance'] = $afterBalance;
                $r_data['transaction_type'] = 'cancelreserve';
                $r_data['amount'] = $amount;
                $r_data['external_unique_id'] = $cancel_reserve_id;
                $r_data['transaction_id'] = $reserve_id;
                $r_data['result_amount'] = $amount;
                $r_data['status'] = GAME_LOGS::STATUS_CANCELLED;

                $transId = $this->processRequestData($r_data);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
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

    private function commitreserve(){
        $game_username = isset($_GET["cust_id"]) ? $_GET["cust_id"] : null;
        $reserve_id = isset($_GET["reserve_id"]) ? $_GET["reserve_id"] : null;
        $purchase_id = isset($_GET["purchase_id"]) ? $_GET["purchase_id"] : null;

        if(empty($game_username) || empty($reserve_id)){
            return $this->setNoErrorResponse(self::RESPONSE_BADREQUEST);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTNOTFOUND);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
           return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $commit_reserve_id = "CMR_".$reserve_id;
        $is_commit_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $commit_reserve_id);
        if($is_commit_reserve_exist){
            $commit_reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $commit_reserve_id);
            if(!empty($commit_reserve_details)){
                $response = self::RESPONSE_RESERVEALREADYCOMMIT;
                $response['balance'] = $this->api->dBtoGameAmount($commit_reserve_details['after_balance']);
                $response['trx_id'] = $commit_reserve_details['response_result_id'];
                return $this->setNoErrorResponse($response);
            }
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEALREADYCOMMIT);
        }

        $cancel_reserve_id = "CR_".$reserve_id;
        $is_cancel_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $cancel_reserve_id);
        if($is_cancel_reserve_exist){
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEALREADYCANCELED);
        }

        $is_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
        if(!$is_reserve_exist){
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
        }

        $reserve_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $reserve_id);
        if(empty($reserve_details)){
            return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
        }

        $total_debit_reserve = $this->common_seamless_wallet_transactions->getSumAmountByTransactionAndTransType($this->api->getPlatformCode(), $reserve_id, 'debitreserve');
        $reserve_amount = isset($reserve_details['amount']) ? $reserve_details['amount'] : 0;

        if($this->utils->compareResultFloat($total_debit_reserve, '>', $reserve_amount)) {
            return $this->setNoErrorResponse(self::RESPONSE_DEBITRESERVEBIGGERTHANRESERVE);
        }
        $amount = $reserve_amount - $total_debit_reserve;

        $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $reserve_id, $amount, $commit_reserve_id, $purchase_id, &$response, &$error_code) {
            $amount = isset($amount) ? $amount : null;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabled_remote_wallet_client_on_currency)){
                    $unique_id_for_remote=$this->api->getPlatformCode().'-'.$commit_reserve_id;       
                    $this->wallet_model->setUniqueidOfSeamlessService($unique_id_for_remote);
                } 

                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
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
                $r_data['before_balance'] = $before_balance;
                $r_data['player_id'] = $player_details['player_id'];
                $r_data['response_result_id'] = $this->response_result_id;
                $r_data['after_balance'] = $afterBalance;
                $r_data['transaction_type'] = 'commitreserve';
                $r_data['amount'] = $amount;
                $r_data['external_unique_id'] = $commit_reserve_id;
                $r_data['transaction_id'] = $reserve_id;
                $r_data['status'] = GAME_LOGS::STATUS_ACCEPTED;
                $r_data['purchase_id'] = $purchase_id;
                $transId = $this->processRequestData($r_data);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['trx_id'] = $this->response_result_id;
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

    private function creditcustomer(){
        $game_username = isset($_GET["cust_id"]) ? $_GET["cust_id"] : null;
        $credit_request_id = isset($_GET["req_id"]) ? $_GET["req_id"] : null;
        $credit_amount = isset($_GET["amount"]) ? $_GET["amount"] : 0;
        $purchase_id = isset($_GET["purchase_id"]) ? $_GET["purchase_id"] : null;
        $request = $this->request;
        $xml   = simplexml_load_string($request);
        $request_array = json_decode(json_encode((array) $xml), true);
        $reserve_id = isset($request_array['Purchases']['Purchase']['@attributes']['ReserveID']) ? $request_array['Purchases']['Purchase']['@attributes']['ReserveID'] : null;
        if($reserve_id){
            $cancel_reserve_id = "CR_".$reserve_id;
            $is_cancel_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $cancel_reserve_id);
            if($is_cancel_reserve_exist){
                return $this->setNoErrorResponse(self::RESPONSE_RESERVEALREADYCANCELED);
            }

            $is_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
            if(!$is_reserve_exist){
                return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
            }
        } else {
            return $this->setNoErrorResponse(self::RESPONSE_RESERVENOTFOUNDONXML);
        }

        if(!$this->isValidAmount($credit_amount)){
            return $this->setNoErrorResponse(self::RESPONSE_AMOUNTINVALID);
        }

        if(empty($game_username) || empty($credit_request_id)){
            return $this->setNoErrorResponse(self::RESPONSE_BADREQUEST);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTNOTFOUND);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
           return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $is_credit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $credit_request_id);
        if($is_credit_exist){
            $credit_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $credit_request_id);
            if(!empty($credit_details)){
                $response = self::RESPONSE_SUCCESS;
                $response['balance'] = $this->api->dBtoGameAmount($credit_details['after_balance']);
                $response['trx_id'] = $credit_details['response_result_id'];
                return $this->setNoErrorResponse($response);
            }
        }

        $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $credit_amount, $credit_request_id, $purchase_id, $reserve_id, &$response, &$error_code) {
            $amount = isset($credit_amount) ? $this->api->gameAmountToDB($credit_amount) : null;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabled_remote_wallet_client_on_currency)){
                    $unique_id_for_remote=$this->api->getPlatformCode().'-'.$credit_request_id;       
                    $this->wallet_model->setUniqueidOfSeamlessService($unique_id_for_remote);
                }
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
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
                $r_data['before_balance'] = $before_balance;
                $r_data['player_id'] = $player_details['player_id'];
                $r_data['response_result_id'] = $this->response_result_id;
                $r_data['after_balance'] = $afterBalance;
                $r_data['transaction_type'] = 'creditcustomer';
                $r_data['amount'] = $amount;
                $r_data['external_unique_id'] = $credit_request_id;
                $r_data['purchase_id'] = $purchase_id;
                $r_data['status'] = GAME_LOGS::STATUS_SETTLED;
                $r_data['transaction_id'] = $reserve_id;
                $r_data['result_amount'] = $amount;

                $transId = $this->processRequestData($r_data);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['trx_id'] = $this->response_result_id;
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

    private function debitcustomer(){
        $game_username = isset($_GET["cust_id"]) ? $_GET["cust_id"] : null;
        $debit_request_id = isset($_GET["req_id"]) ? $_GET["req_id"] : null;
        $debit_amount = isset($_GET["amount"]) ? $_GET["amount"] : 0;
        $purchase_id = isset($_GET["purchase_id"]) ? $_GET["purchase_id"] : null;
        $request = $this->request;
        $xml   = simplexml_load_string($request);
        $request_array = json_decode(json_encode((array) $xml), true);
        $reserve_id = isset($request_array['Purchases']['Purchase']['@attributes']['ReserveID']) ? $request_array['Purchases']['Purchase']['@attributes']['ReserveID'] : null;
        if($reserve_id){
            $cancel_reserve_id = "CR_".$reserve_id;
            $is_cancel_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $cancel_reserve_id);
            if($is_cancel_reserve_exist){
                return $this->setNoErrorResponse(self::RESPONSE_RESERVEALREADYCANCELED);
            }

            $is_reserve_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $reserve_id);
            if(!$is_reserve_exist){
                return $this->setNoErrorResponse(self::RESPONSE_RESERVEIDNOTEXIST);
            }
        } else {
            return $this->setNoErrorResponse(self::RESPONSE_RESERVENOTFOUNDONXML);
        }

        if(!$this->isValidAmount($debit_amount)){
            return $this->setNoErrorResponse(self::RESPONSE_AMOUNTINVALID);
        }

        if(empty($game_username) || empty($debit_request_id)){
            return $this->setNoErrorResponse(self::RESPONSE_BADREQUEST);
        }

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTNOTFOUND);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
           return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        if($this->player_model->isBlocked($player_id)){
            return $this->setNoErrorResponse(self::RESPONSE_CLIENTBLOCKED);
        }

        $is_debit_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $debit_request_id);
        if($is_debit_exist){
            $debit_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $debit_request_id);
            if(!empty($debit_details)){
                $response = self::RESPONSE_SUCCESS;
                $response['balance'] = $this->api->dBtoGameAmount($debit_details['after_balance']);
                $response['trx_id'] = $debit_details['response_result_id'];
                return $this->setNoErrorResponse($response);
            }
        }

        $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
        $response = array();
        $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $debit_amount, $debit_request_id, $reserve_id, $purchase_id, &$response, &$error_code) {
            $amount = isset($debit_amount) ? $this->api->gameAmountToDB($debit_amount) : null;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            $decrease = false;
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance) && !$this->allow_negative_on_debit) {
                    $error_code = self::RESPONSE_SUCCESS;
                    $response['error_message'] = self::RESPONSE_LOWBALANCE['error_message'];
                    return false;
                }
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabled_remote_wallet_client_on_currency)){
                    $unique_id_for_remote=$this->api->getPlatformCode().'-'.$debit_request_id;       
                    $this->wallet_model->setUniqueidOfSeamlessService($unique_id_for_remote);
                } 
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    if($this->allow_negative_on_debit && empty($enabled_remote_wallet_client_on_currency)){
                        $success = $this->wallet_model->decSubWalletAllowNegative($player_details['player_id'], $this->api->getPlatformCode(), $amount);
                    } else {
                        $success = $this->wallet_model->decSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
                    }
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
                $r_data['before_balance'] = $before_balance;
                $r_data['player_id'] = $player_details['player_id'];
                $r_data['response_result_id'] = $this->response_result_id;
                $r_data['after_balance'] = $afterBalance;
                $r_data['transaction_type'] = 'debitcustomer';
                $r_data['amount'] = $amount;
                $r_data['external_unique_id'] = $debit_request_id;
                $r_data['purchase_id'] = $purchase_id;
                $r_data['status'] = GAME_LOGS::STATUS_SETTLED;
                $r_data['result_amount'] = -$amount;
                $r_data['transaction_id'] = $reserve_id;

                $transId = $this->processRequestData($r_data);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['trx_id'] = $this->response_result_id;
                }

            } 
            return $success;
        });

        if(!empty($response) && $success){
           return $this->setResponse($response);
        } else {
            return $this->setResponse(array_merge($error_code, $response));
        }
    }

    private function setNoErrorResponse($response){
        if($response['error_code'] != self::RESPONSE_SUCCESS['error_code']){
            $response['error_code'] = self::RESPONSE_SUCCESS['error_code'];#override error code
        }
        return $this->setOutput($response);
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
        #save the actual xml to json so that can recognize the request
        $request_array = array(
            "url" => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            "data" => $this->request
        );
        $request_json = json_encode($request_array);

        $dataToInsert = array(
            "round_id" => isset($request['purchase_id']) ? $request['purchase_id'] : NULL, 
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "game_id" => "sportsbook",
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "transaction_id" => isset($request['transaction_id']) ? $request['transaction_id'] : NULL, #reserve id
            #default
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "extra_info" => $request_json, #actual request
            "start_at" => $this->utils->getNowForMysql(), 
            "end_at" => $this->utils->getNowForMysql(), 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            #trans
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,
        );

        if(empty($dataToInsert['transaction_id'])){
            $data = $this->request;
            $xml   = simplexml_load_string($data);
            $array = json_decode(json_encode((array) $xml), true);
            $dataToInsert['transaction_id'] = isset($array['@attributes']['reserve_id']) ? $array['@attributes']['reserve_id'] : null;
            if($request['transaction_type'] == "debitcustomer" || $request['transaction_type'] == "creditcustomer"){
                $dataToInsert['transaction_id'] = isset($request_array['Purchases']['Purchase']['@attributes']['ReserveID']) ? $request_array['Purchases']['Purchase']['@attributes']['ReserveID'] : null;
            }
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
        if(!isset($response['balance'])){
            $response['balance'] = 0;
            if($this->player_id){
                $is_locked = false;
                $balance = $this->getPlayerBalance($is_locked);
                if($balance === false) {
                    $balance = 0;
                }
                $response['balance'] =  $this->api->dBtoGameAmount($balance);
            }
        }
        if(!isset($response['trx_id'])){
            $response['trx_id'] = $this->response_result_id; #try use response result id for easy checking of logs, default is row id from transaction
        }

        $response_output = http_build_query($response, "", "\r\n");
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
                $content['resultText'] = $response_output;
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
                $content['resultText'] = $response_output;
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

        http_response_code($http_status_code);
        $output = $this->output->set_content_type('text/plain')->set_output($response_output);
        $this->utils->debug_log('BTI_SEAMLESS_GAME_API service output_sent', $response_output);
        return $output;
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

