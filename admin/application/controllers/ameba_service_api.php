<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/../../../submodules/core-lib/application/libraries/third_party/jwt.php';
/**
 * Ameba seamless game integration
 * OGP-28516
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
    The Operator must implement the following API Endpoints.
    get_balance         /ameba_service_api/{gamePlatformId}/{currencycode}
    bet                 /ameba_service_api/{gamePlatformId}/{currencycode}
    cancel_bet          /ameba_service_api/{gamePlatformId}/{currencycode}
    payout              /ameba_service_api/{gamePlatformId}/{currencycode}
 * 
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_ameba_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Ameba_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['get_balance', 'bet', 'cancel_bet', 'payout'];
    const METHOD_ALLOWED_IN_MAINTENANCE = ["result"];
    const STATUS_SUCCESS = 0;
    const TRANSACTION_TABLE = 'ameba_seamless_transactions';

    #Response Code
    const RESPONSE_SUCCESS = [
        "error_code" => "OK"
    ];

    const RESPONSE_UNKNOWN_ERROR = [
        "error_code" => "Unknown",
        "error_message" => "Unknown errors."
    ];

    const RESPONSE_PLAYER_NOT_FOUND = [
        "error_code" => "PlayerNotFound",
        "error_message" => "The player does not exist."
    ];

    const RESPONSE_TRANSACTION_ALREADY_EXIST = [
        "error_code" => "AlreadyProcessed",
        "error_message" => "Transaction already exist."
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        "error_code" => "InsufficientBalance",
        "error_message" => "The player balance is not enough for placing bet."
    ];

    const RESPONSE_TRANSACTION_NOT_MATCH = [
        "error_code" => "TransactionNotMatch",
        "error_message" => "The transaction exists but with different details."
    ];

    const RESPONSE_BET_NOT_FOUND = [
        "error_code" => "BetNotFound",
        "error_message" => "The bet transaction with the transaction id does not exist."
    ];

    const RESPONSE_BET_NOT_MATCH = [
        "error_code" => "BetNotMatch",
        "error_message" => "The bet transaction exists but with different details."
    ];

    const RESPONSE_IP_ACCESS_RESTRICTED = [
        "error_code" => "Unknown",
        "error_message" => "IP not allowed."
    ];

    const RESPONSE_FORCE_FAILED = [
        "error_code" => 401,
        "error_message" => "FORCE_FAILED."
    ];

    const BET_MD5_FIELDS_FOR_ORIGINAL = [
        'site_id',
        'account_name',
        'game_id',
        'round_id',
        'tx_id',
        'bet_amt',
        'free'
    ];

    const BET_MD5_FLOAT_AMOUNT_FIELDS = [];

    const PAYOUT_MD5_FIELDS_FOR_ORIGINAL = [
        'site_id',
        'account_name',
        'game_id',
        'round_id',
        'tx_id',
        'payout_amt',
        'sum_payout_amt'
    ];

    const PAYOUT_MD5_FLOAT_AMOUNT_FIELDS = [];

    const ACTION_BET = "bet";
    const ACTION_CANCEL_BET = "cancel_bet";
    const ACTION_PAYOUT = "payout";


    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model'));
    }

    public function helper_service($method){
        if($method == "encrypt"){
            $request = json_decode(file_get_contents('php://input'), true);# json format
            $token = $this->generateJwtToken($request);
            echo ($token); 
        } else if($method == "decrypt"){
            $request = file_get_contents('php://input'); #text format
            $result = json_encode($this->decodeJwtToken($request));
            echo($result); 
        } else {
            echo "Invalid request. Please try again!!!";
        }
    }

    protected function generateJwtToken($payload)
    {
        $jwt = new JWT;
        $generated_jwt_token = $jwt->encode($payload,$this->utils->getConfig('ameba_seamless_api_key'));
        return $generated_jwt_token;
    }

    protected function decodeJwtToken($payload)
    {
        $jwt = new JWT;
        $json = $jwt->decode($payload,$this->utils->getConfig('ameba_seamless_api_key'));
        return $json;
    }
   
    public function api($gamePlatformId, $currencyCode = null) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;

        $this->request_headers = $this->input->request_headers();

        $bearer_token = isset($this->request_headers['Authorization']) ? $this->request_headers['Authorization'] : null;
        $tokens = explode(" ", $bearer_token);
        $authorization_token = null;
        if(!empty($tokens)){
            if(isset($tokens['0']) && $tokens['0'] === "Bearer"){
                $authorization_token = isset($tokens['1']) ? $tokens['1']: null;
            }
        }

        if(empty($authorization_token)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid token.";
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        }

        $this->request = $request = (array)$this->decodeJwtToken($authorization_token);
        // $this->request = $request = json_decode(file_get_contents('php://input'), true);
        $this->game_platform_id = $gamePlatformId;
        $method = isset($request['action']) ? $request['action'] : null;
        $this->utils->debug_log('ameba service request_headers', $this->request_headers);
        $this->utils->debug_log('ameba service method', $method);
        $this->utils->debug_log('ameba service request', $request);

        $this->generated_md5 = null;
        if($method == self::ACTION_BET || $method == self::ACTION_CANCEL_BET){
            if(!$this->validateRequest($request, self::BET_MD5_FIELDS_FOR_ORIGINAL)){
                $error_response = self::RESPONSE_UNKNOWN_ERROR;
                $error_response['error_message'] = "Invalid parameter.";
                return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
            }
            $this->generated_md5 = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($request, self::BET_MD5_FIELDS_FOR_ORIGINAL, self::BET_MD5_FLOAT_AMOUNT_FIELDS);
        }

        if($method == self::ACTION_PAYOUT){
            if(!$this->validateRequest($request, self::PAYOUT_MD5_FIELDS_FOR_ORIGINAL)){
                $error_response = self::RESPONSE_UNKNOWN_ERROR;
                $error_response['error_message'] = "Invalid parameter.";
                return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
            }
            $this->generated_md5 = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($request, self::PAYOUT_MD5_FIELDS_FOR_ORIGINAL, self::PAYOUT_MD5_FLOAT_AMOUNT_FIELDS);
        }
        
        $this->request_method = $method;
        $this->player_id = null;
        $this->ip_invalid = false;

        if(empty($method)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid action.";
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        }

        if(empty($method) || empty($currencyCode)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid url.";
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        }

        $this->currencyCode = $currencyCode;
        $is_valid_currency=$this->getCurrencyAndValidateDB();
        if(!$is_valid_currency) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid Currency Switch.";
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        }

        $this->api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->api) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid API.";
            $this->utils->debug_log('ameba INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        } 

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Response Failed.";
            $this->utils->debug_log('ameba INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($error_response);
        }

        if(!$this->api) {
            $this->utils->debug_log('ameba INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_UNKNOWN_ERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::RESPONSE_IP_ACCESS_RESTRICTED;
            $error_response['error_message'] = "IP not allowed({$ip})";
            $this->ip_invalid = true;
            return $this->setResponse($error_response);
        }

        if((!$this->external_system->isGameApiActive($this->game_platform_id) || $this->external_system->isGameApiMaintenance($this->game_platform_id)) && !in_array($method, self::METHOD_ALLOWED_IN_MAINTENANCE)) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Game disabled or maintenance.";
            $this->utils->debug_log('ameba INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($error_response);
        }

        if(!method_exists($this, $method)) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Action not exist.";
            $this->utils->debug_log('ameba INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($error_response);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Action not alowed.";
            $this->utils->debug_log('ameba INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($error_response);
        }

        $site_id = isset($request['site_id']) ? $request['site_id'] : null;
        if(empty($site_id)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Empty site id.";
            return $this->setResponse($error_response);
        } else {
            if($site_id != $this->api->site_id){
                $error_response = self::RESPONSE_UNKNOWN_ERROR;
                $error_response['error_message'] = "Invalid site id.";
                return $this->setResponse($error_response);
            }
        }

        $game_username = isset($request['account_name']) ? $request['account_name'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_PLAYER_NOT_FOUND);
        }

        $this->player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        return $this->$method();
    }

    /**
     * getCurrencyAndValidateDB
     *
     * @return [type]            [description]
     */
    private function getCurrencyAndValidateDB() {
        if($this->utils->getConfig('ameba_test_local')){ #local testing only
            return true;
        }
        if(isset($this->currencyCode) && !empty($this->currencyCode)) {
            # Get Currency Code for switching of currency and db forMDB
            $is_valid=$this->validateCurrencyAndSwitchDB();

            return $is_valid;
        } else {
            return false;
        }
    }

    protected function validateCurrencyAndSwitchDB(){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($this->currencyCode)){
            return false;
        }else{
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($this->currencyCode)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($this->currencyCode);
                return true;
            }
        }
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

    private function get_balance(){
        $request = $this->request;
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
        $response['balance'] = (string)$this->api->dBtoGameAmount($balance);
        return $this->setResponse($response);
    }

    private function bet(){
        $request = $this->request;
        $bet_amt = isset($request['bet_amt']) ? $request['bet_amt'] : null;
        if(!$this->isValidAmount($bet_amt)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid bet amount value.";
            return $this->setResponse($error_response);
        }

        $uniqueid = isset($request['tx_id']) ? $request['tx_id'] : null; #transactionid
        if(empty($uniqueid)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid tx_id.";
            return $this->setResponse($error_response);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            $bet_record = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['external_unique_id'=> $uniqueid],['md5_sum']);
            if($bet_record['md5_sum'] != $this->generated_md5){
                return $this->setResponse(self::RESPONSE_TRANSACTION_NOT_MATCH);
            }
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        }

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        $player_id = $this->player_id;
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $bet_amount = isset($request['bet_amt']) ? $this->api->gameAmountToDB($request['bet_amt']) : null;
            $free = isset($request['free']) ? $request['free'] : false;
            if($free){ #free bet 
                $bet_amount = 0;
            }
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($bet_amount, '>', 0)) {
                if($this->utils->compareResultFloat($bet_amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_BALANCE;
                    return false;
                }
                
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $bet_amount, $reason_id);
                } else {
                    $round_id = isset($request['round_id']) ? $request['round_id'] : null; #roundid
                    $this->wallet_model->setGameProviderRoundId($round_id);
                    $this->wallet_model->setGameProviderIsEndRound(false);    

                    //OGP-28649
                    $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$uniqueid; 
                    $external_game_id = isset($request['game_id']) ? $request['game_id'] : null;      
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$external_game_id); 
                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                    $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $bet_amount);
                }
            } elseif ($this->utils->compareResultFloat($bet_amount, '=', 0)) {
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
                $request['transaction_type'] = 'bet';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['time'] = isset($request['time']) ? $request['time'] : null;;
                }

            } else {
                #not enough balance or invalid amount
                if($this->utils->compareResultFloat($bet_amount, '>', $before_balance)) {
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

    private function cancel_bet(){
        $request = $this->request;
        $bet_amt = isset($request['bet_amt']) ? $request['bet_amt'] : null;
        if(!$this->isValidAmount($bet_amt)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid bet amount value.";
            return $this->setResponse($error_response);
        }

        $tx_id = isset($request['tx_id']) ? $request['tx_id'] : null; #transactionid
        if(empty($tx_id)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid tx_id.";
            return $this->setResponse($error_response);
        }

        $round_id = isset($request['round_id']) ? $request['round_id'] : null; #roundid
        if(empty($round_id)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid round_id.";
            return $this->setResponse($error_response);
        }

        $is_bet_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $tx_id);
        if($is_bet_exist){
            $bet_record = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['external_unique_id'=> $tx_id],['md5_sum']);
            if($bet_record['md5_sum'] != $this->generated_md5){
                return $this->setResponse(self::RESPONSE_BET_NOT_MATCH);
            } else {
                $update_data = array("round_id" => "CB".$round_id."-".$this->utils->getTimestampNow());
                $success_update = $this->original_seamless_wallet_transactions->updateTransactionDataWithResult(self::TRANSACTION_TABLE, $update_data, 'external_unique_id', $tx_id);
            }
        } else {
            return $this->setResponse(self::RESPONSE_BET_NOT_FOUND);
        }

        $uniqueid = "CB". $tx_id;
        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            $bet_record = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['external_unique_id'=> $uniqueid],['md5_sum']);
            if($bet_record['md5_sum'] != $this->generated_md5){
                return $this->setResponse(self::RESPONSE_TRANSACTION_NOT_MATCH);
            }
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        }

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        $player_id = $this->player_id;
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $rollback_amount = isset($request['bet_amt']) ? $this->api->gameAmountToDB($request['bet_amt']) : null;
            $free = isset($request['free']) ? $request['free'] : false;
            if($free){ #free bet 
                $rollback_amount = 0;
            }
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($rollback_amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $rollback_amount, $reason_id);
                } else {
                    $round_id = isset($request['round_id']) ? $request['round_id'] : null; #roundid
                    $this->wallet_model->setGameProviderRoundId($round_id);
                    $this->wallet_model->setGameProviderIsEndRound(true); 
                    
                    //OGP-28649
                    $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$uniqueid;    
                    $external_game_id = isset($request['game_id']) ? $request['game_id'] : null;       
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$external_game_id); 
                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $rollback_amount);
                }
            } elseif ($this->utils->compareResultFloat($rollback_amount, '=', 0)) {
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
                $request['transaction_type'] = 'cancel_bet';
                $request['external_unique_id'] = $uniqueid;
                $request['round_id'] = "CB".$request['round_id']."-".$this->utils->getTimestampNow();
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['time'] = isset($request['time']) ? $request['time'] : null;;
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

    private function payout(){
        $request = $this->request;
        $payout_amt = isset($request['payout_amt']) ? $request['payout_amt'] : 0;
        if(!$this->isValidAmount($payout_amt)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid payout amount value.";
            return $this->setResponse($error_response);
        }

        $rebate_amt = isset($request['rebate_amt']) ? $request['rebate_amt'] : 0;
        if(!$this->isValidAmount($rebate_amt)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid rebate amount value.";
            return $this->setResponse($error_response);
        }

        $prize_amt = isset($request['prize_amt']) ? $request['prize_amt'] : 0;
        $prize_type = isset($request['prize_type']) ? $request['prize_type'] : null;
        if($prize_type == "rpcash"){
            if(!$this->isValidAmount($prize_amt)){
                $error_response = self::RESPONSE_UNKNOWN_ERROR;
                $error_response['error_message'] = "Invalid prize amount value.";
                return $this->setResponse($error_response);
            }
        }

        $sum_payout_amt = isset($request['sum_payout_amt']) ? $request['sum_payout_amt'] : null;
        if(!$this->isValidAmount($sum_payout_amt)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid sum payout amount value.";
            return $this->setResponse($error_response);
        }

        $round_id = isset($request['round_id']) ? $request['round_id'] : null;
        $is_round_exist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE,['round_id' => $round_id]);
        if(!$is_round_exist){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Round not exist.";
            return $this->setResponse($error_response);
        }
   
        $uniqueid = isset($request['tx_id']) ? $request['tx_id'] : null; #transactionid
        if(empty($uniqueid)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['error_message'] = "Invalid tx_id.";
            return $this->setResponse($error_response);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            $existing_record = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['external_unique_id'=> $uniqueid],['md5_sum']);
            if($existing_record['md5_sum'] != $this->generated_md5){
                return $this->setResponse(self::RESPONSE_TRANSACTION_NOT_MATCH);
            } else {
                $update_data = array("time" => isset($request['time']) ? $this->api->gameTimeToServerTime($request['time']) : NULL);
                $success_update = $this->original_seamless_wallet_transactions->updateTransactionDataWithResult(self::TRANSACTION_TABLE, $update_data, 'external_unique_id', $uniqueid);
                $this->utils->debug_log('ameba update_payoutdata', $success_update);
            }
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        }

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        $player_id = $this->player_id;
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $sum_payout_amt = isset($request['sum_payout_amt']) ? $this->api->gameAmountToDB($request['sum_payout_amt']) : 0;
            $before_balance = $this->getPlayerBalance();
            $success = false; #default

            $amount_operator = '>';
            $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($configEnabled)){
                $amount_operator = '>=';
            } 

            if($this->utils->compareResultFloat($sum_payout_amt, $amount_operator, 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $sum_payout_amt, $reason_id);
                } else {
                    $round_id = isset($request['round_id']) ? $request['round_id'] : null; #roundid
                    $this->wallet_model->setGameProviderRoundId($round_id);
                    $this->wallet_model->setGameProviderIsEndRound(true); 

                    //OGP-28649
                    $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$uniqueid;     
                    $external_game_id = isset($request['game_id']) ? $request['game_id'] : null;   
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$external_game_id); 
                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $sum_payout_amt);
                }
            } elseif ($this->utils->compareResultFloat($sum_payout_amt, '=', 0)) {
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
                $request['transaction_type'] = 'payout';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['time'] = isset($request['time']) ? $request['time'] : null;;
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

    private function isValidAmount($amount){
        $amount= trim($amount);
        if(!is_numeric($amount)) {
            return false;
        } else {
            return true;
        }
    }

    
    private function processRequestData($request){
        $dataToInsert = array(
            "player_id" => $this->player_id, 
            "action" => isset($request['action']) ? $request['action'] : NULL, 
            "site_id" => isset($request['site_id']) ? $request['site_id'] : NULL, 
            "account_name" => isset($request['account_name']) ? $request['account_name'] : NULL, 
            "bet_amt" => isset($request['bet_amt']) ? $this->api->gameAmountToDB($request['bet_amt']) : 0, 
            "payout_amt" => isset($request['payout_amt']) ? $this->api->gameAmountToDB($request['payout_amt']) : 0, 
            "rebate_amt" => isset($request['rebate_amt']) ? $this->api->gameAmountToDB($request['rebate_amt']) : 0,
            "game_id" => isset($request['game_id']) ? $request['game_id'] : NULL, 
            "round_id" => isset($request['round_id']) ? $request['round_id'] : NULL, 
            "tx_id" => isset($request['tx_id']) ? $request['tx_id'] : NULL, 
            "free" => isset($request['free']) ? $request['free'] : NULL, 
            "sessionid" => isset($request['sessionid']) ? $request['sessionid'] : NULL, 
            "time" => isset($request['time']) ? $this->api->gameTimeToServerTime($request['time']) : NULL, 
            "jp" => isset($request['jp']) ? json_encode($request['jp']) : NULL, 
            "prize_type" => isset($request['prize_type']) ? $request['prize_type'] : NULL, 
            "prize_amt" => isset($request['prize_amt']) ? $this->api->gameAmountToDB($request['prize_amt']) : 0,
            "sum_payout_amt" => isset($request['sum_payout_amt']) ? $this->api->gameAmountToDB($request['sum_payout_amt']) : 0,
            "json_request" =>  json_encode($this->request), 
            #sbe
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL, 
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL, 
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL, 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL, 
            "response_result_id" => $this->response_result_id,
            "md5_sum" => $this->generated_md5,
        );

        $transId = $this->original_seamless_wallet_transactions->insertTransactionData(self::TRANSACTION_TABLE, $dataToInsert);
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
        $flag = $response['error_code'] == self::STATUS_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        

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
        // $http_status_code = isset($response['status']) ? $response['status'] : $http_status_code;
        // if(isset($response['status'])){
        //     unset($response['status']);
        // }
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

