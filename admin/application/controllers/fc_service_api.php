<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';
/**
 * FA CHAI Gaming seamless game integration
 * OGP-28346
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
    The Operator must implement the following API Endpoints.
    3-2. Get Balance        /fc_service_api/balance
    3-3. Betting Information & Game Results     /fc_service_api/bet_result
 * 
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_fc_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Fc_service_api extends BaseController {
    use Seamless_service_api_module;

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['balance', 'bet_result', 'cancel_bet_result', 'bet', 'settle', 'cancel_bet'];
    const METHOD_ALLOWED_IN_MAINTENANCE = ["result"];
    const STATUS_SUCCESS = 0;
    const TRANSACTION_TABLE = 'fc_seamless_transactions';

    #Response Code
    const RESPONSE_SUCCESS = [
        "Result" => 0
    ];

    const RESPONSE_INTERNAL_ERROR_GAME_DISABLED_OR_MAINTENANCE = [
        "Result" => 408,
        "ErrorText" => "Games is under maintenance."
    ];

    const RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST = [
        "Result" => 999,
        "ErrorText" => "Method not exist."
    ];

    const RESPONSE_INVALID_CURRENCY = [
        "Result" => 999,
        "ErrorText" => "Invalid currency."
    ];

    const RESPONSE_CURRENCY_MISSING = [
        "Result" => 1012,
        "ErrorText" => "Currency code is missing."
    ];

    const RESPONSE_INVALID_ACCOUNT = [
        "Result" => 500,
        "ErrorText" => "Account does not exist."
    ];

    const RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED = [
        "Result" => 407,
        "ErrorText" => "Account locked."
    ];

    const RESPONSE_INTERNAL_ERROR_METHOD_NOT_ALLOWED = [
        "Result" => 999,
        "ErrorText" => "Method not allowed."
    ];

    const RESPONSE_TRANSACTION_ALREADY_EXIST = [
        "Result" => 205,
        "ErrorText" => "Duplicate Transaction ID number."
    ];

    const RESPONSE_INVALID_AMOUNT = [
        "Result" => 999,
        "ErrorText" => "Invaid amount."
    ];

    const RESPONSE_BANK_ID_EMPTY = [
        "Result" => 210,
        "ErrorText" => "Transaction ID number cannot be empty."
    ];

    const RESPONSE_RECORD_ID_EMPTY = [
        "Result" => 999,
        "ErrorText" => "Record ID cannot be empty."
    ];

    const RESPONSE_BANK_NOT_EXIST = [
        "Result" => 221,
        "ErrorText" => "Transaction ID number not exist."
    ];

    const RESPONSE_REVERT_CANCEL_BET = [
        "Result" => 799,
        "ErrorText" => "Revert Cancel Bet."
    ];

    const RESPONSE_BET_ID_EMPTY = [
        "Result" => 999,
        "ErrorText" => "Bet ID number cannot be empty."
    ];

    const RESPONSE_BET_ID_NOT_EXIST = [
        "Result" => 999,
        "ErrorText" => "Bet ID not exist."
    ];
    
    const RESPONSE_INSUFFICIENT_BALANCE = [
        "Result" => 203,
        "ErrorText" => "Your points balance not enough."
    ];

    const RESPONSE_UNKNOWN_ERROR = [
        "Result" => 999,
        "ErrorText" => "Unknown errors."
    ];

    const RESPONSE_IP_ACCESS_RESTRICTED = [
        "Result" => 410,
        "ErrorText" => "IP not allowed."
    ];

    const RESPONSE_FORCE_FAILED = [
        "Result" => 401,
        "ErrorText" => "FORCE_FAILED."
    ];

    public $agent_key;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model'));

        $this->game_platform_id = FC_SEAMLESS_GAME_API;
        $this->api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        $this->agent_key = $this->api->agent_key;
        $this->use_remote_wallet_failed_transaction_monthly_table = $this->api->use_remote_wallet_failed_transaction_monthly_table;
    }
    public function helper_service($method){
        if($method == "encrypt"){
            $request = json_decode(file_get_contents('php://input'), true);# json format
            $json_param = json_encode($request);
            echo ($this->AESencode($json_param)); 
        } else if($method == "decrypt"){
            $request = file_get_contents('php://input'); #text format
            $json = ($this->AESdecode($request)); 
            echo $json;
        } else {
            echo "Invalid request. Please try again!!!";
        }
    }

    // AES Encryption ECB Mode
    private function AESencode($_values)
    {
        Try {
            //$this->utils->getConfig('fc_seamless_agent_key')
            $this->utils->debug_log('FC_SEAMLESS_GAME_API aes encode key', $this->agent_key);
            $data = openssl_encrypt($_values, 'AES-128-ECB', $this->agent_key , OPENSSL_RAW_DATA);
            $data = base64_encode($data);
        }
        Catch (\Exception $e) {
        }
        return $data;
    }

    // AES Decrypt ECB Mode
    private function AESdecode($_values)
    {
        $data = null;
        Try {
            //$this->utils->getConfig('fc_seamless_agent_key')
            $this->utils->debug_log('FC_SEAMLESS_GAME_API aes decode key', $this->agent_key);
            $data = openssl_decrypt(base64_decode($_values), 'AES-128-ECB', $this->agent_key , OPENSSL_RAW_DATA);
        }
        Catch (\Exception $e) {
        }
        return $data;
    }


    public function api($method = null) {
        $this->request_headers = $this->input->request_headers();
        // $this->request = $request = json_decode(file_get_contents('php://input'), true);
        $this->actual_request = file_get_contents('php://input');
        parse_str( $this->actual_request, $request_array);

        $this->utils->debug_log('FC_SEAMLESS_GAME_API parse str request_array', $request_array);

        $params = isset($request_array['Params']) ? $request_array['Params'] : null;
        $json_string = $this->AESdecode($params);
        $this->request = $request = json_decode($json_string, true);
        $this->request_method = $method;
        $this->player_id = null;
        $this->ip_invalid = false;

        $this->utils->debug_log('FC_SEAMLESS_GAME_API service request_headers', $this->request_headers);
        $this->utils->debug_log('FC_SEAMLESS_GAME_API service method', $method);
        $this->utils->debug_log('FC_SEAMLESS_GAME_API service request', $this->request);

        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;

        if(empty($method)){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['ErrorText'] = "Invalid URL.";
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        }
        $this->currencyCode = null;
        if(isset($request['Currency'])){
            $this->currencyCode = strtolower($request['Currency']);
        }

        if(isset($request['currency'])){
            $this->currencyCode = strtolower($request['currency']);
        }
        $is_valid_currency=$this->getCurrencyAndValidateDB();
        if(!$is_valid_currency) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['ErrorText'] = "Invalid Currency Switch.";
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        }

        if(!$this->api) {
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['ErrorText'] = "Invalid API.";
            $this->utils->debug_log('FC_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult($error_response, $addOrigin, $origin, $pretty, $partial_output_on_error);
        } 

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            $error_response = self::RESPONSE_UNKNOWN_ERROR;
            $error_response['ErrorText'] = "Response Failed.";
            $this->utils->debug_log('FC_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($error_response);
        }

        if(!$this->api) {
            $this->utils->debug_log('FC_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_UNKNOWN_ERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::RESPONSE_IP_ACCESS_RESTRICTED;
            $error_response['ErrorText'] = "IP not allowed({$ip})";
            $this->ip_invalid = true;
            return $this->setResponse($error_response);
        }

        if((!$this->external_system->isGameApiActive($this->game_platform_id) || $this->external_system->isGameApiMaintenance($this->game_platform_id)) && !in_array($method, self::METHOD_ALLOWED_IN_MAINTENANCE)) {
            $this->utils->debug_log('FC_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_GAME_DISABLED_OR_MAINTENANCE);
        }

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('FC_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            $this->utils->debug_log('FC_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_METHOD_NOT_ALLOWED);
        }

        return $this->$method();
    }

    /**
     * getCurrencyAndValidateDB
     *
     * @return [type]            [description]
     */
    private function getCurrencyAndValidateDB() {
        if($this->utils->getConfig('fc_test_local')){ #local testing only
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

    /*
        3-2. Get Balance
         When to use
            ◆ Get currently balance of player.
            ◆ If player is idle, this request will call once every second.
            ◆ The player will be kicked out from the game if there is timeout situation happened.
     */
    private function balance(){
        $request = $this->request;

        $currency = isset($request['Currency']) ? $request['Currency'] : null;
        if(empty($currency)){
            return $this->setResponse(self::RESPONSE_CURRENCY_MISSING);
        } else {
            if($currency != $this->api->currency){
                return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
            }
        }

        $game_username = isset($request['MemberAccount']) ? $request['MemberAccount'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_ACCOUNT);
        }

        $this->player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
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
        $response['MainPoints'] = $this->api->dBtoGameAmount($balance);
        return $this->setResponse($response);
    }

    /*
        3-3. Betting Information & Game Results
         When to use
            ◆ Call this API when player bet, will inform the game results.
            ◆ Before deduction will confirm the player balance is sufficient, and after deduction will
            return the player remaining balance.
            ◆ When there is time out situation happened, the player will be kickout from the game
            and cancel bet request will be sent.
            ◆ Common error code description
                 500: Player ID not exist
                 203: Player balance is insufficient
                 For other error codes, please refer to 「Error Code Appendix」
            ◆ Fishing and Dozer provide verification parameters RequireAmt , used to represent
            actual bet amount without game win in the interval。
     */
    private function bet_result(){
        $request = $this->request;
        $record_id = isset($request['RecordID']) ? $request['RecordID'] : null;
        if(empty($record_id)){
            return $this->setResponse(self::RESPONSE_RECORD_ID_EMPTY);
        }

        $currency = isset($request['Currency']) ? $request['Currency'] : null;
        if(empty($currency)){
            return $this->setResponse(self::RESPONSE_CURRENCY_MISSING);
        } else {
            if($currency != $this->api->currency){
                return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
            }
        }

        $bet_amount = isset($request['Bet']) ? $request['Bet'] : null;
        $win_amount = isset($request['Win']) ? $request['Win'] : null;
        $net_amount = isset($request['NetWin']) ? $request['NetWin'] : null;
        if(!$this->isValidAmount($bet_amount) || !$this->isValidAmount($win_amount) || !$this->isValidAmount($net_amount)){
            return $this->setResponse(self::RESPONSE_INVALID_AMOUNT);
        }

        $game_username = isset($request['MemberAccount']) ? $request['MemberAccount'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_ACCOUNT);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        $uniqueid = isset($request['BankID']) ? "BR".$request['BankID'] : null; #transactionid
        if(empty($uniqueid)){
            return $this->setResponse(self::RESPONSE_BANK_ID_EMPTY);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        } 

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $bet_amount = isset($request['Bet']) ? $this->api->gameAmountToDB($request['Bet']) : 0;
            $win_amount = isset($request['Win']) ? $this->api->gameAmountToDB($request['Win']) : 0;
            $net_amount = isset($request['NetWin']) ? $this->api->gameAmountToDB($request['NetWin']) : 0;
            $seamless_service_unique_id = $this->utils->mergeArrayValues([$this->api->getPlatformCode(), $uniqueid]);
            $external_game_id = isset($request['GameID']) ? $request['GameID'] : null;

            $this->ssa_set_uniqueid_of_seamless_service($seamless_service_unique_id, $external_game_id);
            $this->ssa_set_game_provider_action_type(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT);
            $this->ssa_set_game_provider_bet_amount($bet_amount);
            $this->ssa_set_game_provider_payout_amount($win_amount);
            $this->ssa_set_game_provider_is_end_round(true);

            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $adjustment_type = null;
            $adjustment_amount = 0;
            $success = false; #default
            if($this->utils->compareResultFloat($bet_amount, '>', 0)) {
                if($this->utils->compareResultFloat($bet_amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_BALANCE;
                    return false;
                }
                if($this->utils->compareResultFloat($net_amount, '<', 0)) { #lose
                    $adjustment_type = 'decrease';
                    $adjustment_amount = abs($net_amount);

                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $adjustment_amount, $reason_id);
                    } else {
                        $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $adjustment_amount, $after_balance);
                    }

                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if (!$success) {
                        if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                            $success = true;

                            if ($adjustment_type == 'decrease') {
                                $before_balance += $adjustment_amount;
                            }
                        }
                    }
                } else if($this->utils->compareResultFloat($net_amount, '>', 0)){ #win
                    $adjustment_type = 'increase';
                    $adjustment_amount = $net_amount;

                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $adjustment_amount, $reason_id);
                    } else {
                        $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $adjustment_amount, $after_balance);
                    }

                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if (!$success) {
                        if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                            $success = true;

                            if ($adjustment_type == 'increase') {
                                $before_balance -= $adjustment_amount;
                            }
                        }
                    }
                } else if($this->utils->compareResultFloat($net_amount, '=', 0)){ #zero amount
                    $success = true;
                    if($this->ssa_enabled_remote_wallet()){
                        $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), 0, $after_balance);
                    }
                }
                
            } elseif ($this->utils->compareResultFloat($bet_amount, '=', 0)) {
                $success = false;#allowed amount 0
                if($this->utils->compareResultFloat($win_amount, '>', 0)){ #win
                    $adjustment_type = 'increase';
                    $adjustment_amount = $win_amount;

                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $adjustment_amount, $reason_id);
                    } else {
                        $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $adjustment_amount, $after_balance);
                    }

                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if (!$success) {
                        if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                            $success = true;

                            if ($adjustment_type == 'increase') {
                                $before_balance -= $adjustment_amount;
                            }
                        }
                    }
                }
                if($this->utils->compareResultFloat($win_amount, '=', 0)){ #o win
                    $success = true;
                    if($this->ssa_enabled_remote_wallet()){
                        $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), 0, $after_balance);
                    }
                }
            } else { #default error
                $success = false;
            }

            $after_balance = !empty($after_balance) ? $after_balance : $this->getPlayerBalance();
            $request['transaction_type'] = 'bet_result';
            $request['external_unique_id'] = $uniqueid;

            if (!empty($this->remote_wallet_status) && !empty($request)) {
                $request['adjustment_type'] = $adjustment_type;
                $request['amount'] = $adjustment_amount;
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $request);
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $after_balance;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['MainPoints'] = $this->api->dBtoGameAmount($after_balance);
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

    /*
    3-1. Cancel Bet&GameResults
         When to use
            ◆ Call this API when bet request failed.
            ◆ If there is no response or respond error 999 to the request, it will be called
            continuously for 2 hours until successfully received correct response.
            ◆ If you confirm that the record is correct, please return the Result code 799, and this
        record will be reverted.
     */
    private function cancel_bet_result(){
        $request = $this->request;

        $currency = isset($request['currency']) ? $request['currency'] : null;
        if(empty($currency)){
            return $this->setResponse(self::RESPONSE_CURRENCY_MISSING);
        } else {
            if($currency != $this->api->currency){
                return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
            }
        }

        $game_username = isset($request['MemberAccount']) ? $request['MemberAccount'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_ACCOUNT);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        $uniqueid = isset($request['BankID']) ? "BR".$request['BankID'] : null; #transactionid
        if(empty($uniqueid)){
            return $this->setResponse(self::RESPONSE_BANK_ID_EMPTY);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_REVERT_CANCEL_BET);
        } else{
            return $this->setResponse(self::RESPONSE_BANK_NOT_EXIST);
        }
    }

    /*
    3-4. Bet
         When to use
            ◆ Used for Color Game.
            ◆ Confirmed whether player has enough balance to bet, then responds player Balance
            currently to FC.
            ◆ Send CancelBet request to client if FC did not receive response or time out.

     */
    private function bet(){
        $request = $this->request;

        $currency = isset($request['Currency']) ? $request['Currency'] : null;
        if(empty($currency)){
            return $this->setResponse(self::RESPONSE_CURRENCY_MISSING);
        } else {
            if($currency != $this->api->currency){
                return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
            }
        }

        $bet_amount = isset($request['Bet']) ? $request['Bet'] : null;
        if(!$this->isValidAmount($bet_amount)){
            return $this->setResponse(self::RESPONSE_INVALID_AMOUNT);
        }

        $game_username = isset($request['MemberAccount']) ? $request['MemberAccount'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_ACCOUNT);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        $uniqueid = isset($request['BetID']) ? "B".$request['BetID'] : null; #transactionid
        if(empty($uniqueid)){
            return $this->setResponse(self::RESPONSE_BET_ID_EMPTY);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        } 

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $bet_amount = isset($request['Bet']) ? $this->api->gameAmountToDB($request['Bet']) : null;
            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $adjustment_type = 'decrease';
            $success = false; #default
            $seamless_service_unique_id = $this->utils->mergeArrayValues([$this->api->getPlatformCode(), $uniqueid]);
            $external_game_id = isset($request['GameID']) ? $request['GameID'] : null;

            $this->ssa_set_uniqueid_of_seamless_service($seamless_service_unique_id, $external_game_id);
            $this->ssa_set_game_provider_action_type(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);

            if($this->utils->compareResultFloat($bet_amount, '>', 0)) {
                if($this->utils->compareResultFloat($bet_amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_INSUFFICIENT_BALANCE;
                    return false;
                }
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_OUT, $bet_amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($player_id, $this->api->getPlatformCode(), $bet_amount, $after_balance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $before_balance += $bet_amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($bet_amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $after_balance = !empty($after_balance) ? $after_balance : $this->getPlayerBalance();
            $request['transaction_type'] = 'bet';
            $request['external_unique_id'] = $uniqueid;

            if (!empty($this->remote_wallet_status) && !empty($request)) {
                $request['adjustment_type'] = $adjustment_type;
                $request['amount'] = $bet_amount;
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $request);
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $after_balance;
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['MainPoints'] = $this->api->dBtoGameAmount($after_balance);
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
    /*
    3-5. Settle
     When to use
        ◆ Used for Color Game.
        ◆ Send this request when game settle to inform the result of this round, then responds player Balance currently to FC.
        ◆ Retry this request to client for 2 hours until successfully.
     */
    private function settle(){
        $request = $this->request;

        $record_id = isset($request['RecordID']) ? $request['RecordID'] : null;
        if(empty($record_id)){
            return $this->setResponse(self::RESPONSE_RECORD_ID_EMPTY);
        }

        $currency = isset($request['Currency']) ? $request['Currency'] : null;
        if(empty($currency)){
            return $this->setResponse(self::RESPONSE_CURRENCY_MISSING);
        } else {
            if($currency != $this->api->currency){
                return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
            }
        }

        $bet_amount = isset($request['Bet']) ? $request['Bet'] : null;
        $win_amount = isset($request['Win']) ? $request['Win'] : null;
        $net_amount = isset($request['NetWin']) ? $request['NetWin'] : null;
        if(!$this->isValidAmount($bet_amount) || !$this->isValidAmount($win_amount) || !$this->isValidAmount($net_amount)){
            return $this->setResponse(self::RESPONSE_INVALID_AMOUNT);
        }

        $game_username = isset($request['MemberAccount']) ? $request['MemberAccount'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_ACCOUNT);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        $uniqueid = isset($request['BankID']) ? "S".$request['BankID'] : null; #transactionid
        if(empty($uniqueid)){
            return $this->setResponse(self::RESPONSE_BANK_ID_EMPTY);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        } 

        if (!empty($request['SettleBetIDs']) && is_array($request['SettleBetIDs'])) {
            foreach ($request['SettleBetIDs'] as $result) {
                if (!empty($result['betID'])) {
                    $is_bet_exist = $this->ssa_is_transaction_exists(self::TRANSACTION_TABLE, [
                        'transaction_type' => 'bet',
                        'player_id' => $player_id,
                        'bet_id' => $result['betID'],
                        'record_id' => $record_id,
                    ]);

                    if (!$is_bet_exist) {
                        return $this->setResponse(self::RESPONSE_BET_ID_NOT_EXIST);
                    }
                }

                if (!empty($result['betID'])) {
                    $is_already_cancelled = $this->ssa_is_transaction_exists(self::TRANSACTION_TABLE, [
                        'transaction_type' => 'bet',
                        'player_id' => $player_id,
                        'bet_id' => $result['betID'],
                        'record_id' => $record_id,
                        'status' => Game_logs::STATUS_CANCELLED,
                    ]);

                    if ($is_already_cancelled) {
                        return $this->setResponse(self::RESPONSE_FORCE_FAILED);
                    }
                }
            }
        }

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $bet_amount = isset($request['Bet']) ? $this->api->gameAmountToDB($request['Bet']) : null;
            $win_amount = isset($request['Win']) ? $this->api->gameAmountToDB($request['Win']) : null;
            $net_amount = isset($request['NetWin']) ? $this->api->gameAmountToDB($request['NetWin']) : null;

            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $adjustment_type = 'increase';
            $success = false; #default
            $seamless_service_unique_id = $this->utils->mergeArrayValues([$this->api->getPlatformCode(), $uniqueid]);
            $external_game_id = isset($request['GameID']) ? $request['GameID'] : null;

            $this->ssa_set_uniqueid_of_seamless_service($seamless_service_unique_id, $external_game_id);
            $this->ssa_set_game_provider_action_type(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);

            if (!empty($request['SettleBetIDs']) && is_array($request['SettleBetIDs'])) {
                $related_unique_id = null;
                $related_unique_id_array = [];

                foreach ($request['SettleBetIDs'] as $result) {
                    if (!empty($result['betID'])) {
                        $unique_id = $this->utils->mergeArrayValues(['game', $this->api->getPlatformCode(), "B" . $result['betID']]);

                        if (count($request['SettleBetIDs']) > 1) {
                            array_push($related_unique_id_array, $unique_id);
                        } else {
                            $related_unique_id = $unique_id;
                        }
                    }
                }

                if (!empty($related_unique_id_array)) {
                    $this->ssa_set_related_uniqueid_array_of_seamless_service($related_unique_id_array);
                } else {
                    $this->ssa_set_related_uniqueid_of_seamless_service($related_unique_id);
                }
            }

            $this->ssa_set_related_action_of_seamless_service(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
            $this->ssa_set_game_provider_is_end_round(true);

            $amount_operator = '>';
            $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($configEnabled)){
                $amount_operator = '>=';
            } 

            if($this->utils->compareResultFloat($win_amount, $amount_operator, 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $win_amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $win_amount, $after_balance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $before_balance -= $win_amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($win_amount, '=', 0)) {
                $success = true;
                if($this->ssa_enabled_remote_wallet()){
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), 0, $after_balance);
                }
            } else { #default error
                $success = false;
            }

            $after_balance = !empty($after_balance) ? $after_balance : $this->getPlayerBalance();
            $request['transaction_type'] = 'settle';
            $request['external_unique_id'] = $uniqueid;

            if (!empty($this->remote_wallet_status) && !empty($request)) {
                $request['adjustment_type'] = $adjustment_type;
                $request['amount'] = $win_amount;
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $request);
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $after_balance;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['MainPoints'] = $this->api->dBtoGameAmount($after_balance);
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

    /*
    3-6. CancelBet
         When to use
            ◆ Used for Color Game.
            ◆ Retry this request to client for 2 hours until successfully.
    */
    private function cancel_bet(){
        $request = $this->request;

        $currency = isset($request['Currency']) ? $request['Currency'] : null;
        if(empty($currency)){
            return $this->setResponse(self::RESPONSE_CURRENCY_MISSING);
        } else {
            if($currency != $this->api->currency){
                return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
            }
        }

        $bet_amount = isset($request['Bet']) ? $request['Bet'] : null;
        if(!$this->isValidAmount($bet_amount)){
            return $this->setResponse(self::RESPONSE_INVALID_AMOUNT);
        }

        $game_username = isset($request['MemberAccount']) ? $request['MemberAccount'] : null;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_ACCOUNT);
        }

        $this->player_id = $player_id = $player_details['player_id'];
        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED);
        }

        $uniqueid = isset($request['BetID']) ? "CB".$request['BetID'] : null; #transactionid
        if(empty($uniqueid)){
            return $this->setResponse(self::RESPONSE_BET_ID_EMPTY);
        }

        $is_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($is_exist){
            return $this->setResponse(self::RESPONSE_TRANSACTION_ALREADY_EXIST);
        } 

        $betid = isset($request['BetID']) ? "B".$request['BetID'] : null;
        $is_bet_exist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $betid);
        if(!$is_bet_exist){
            return $this->setResponse(self::RESPONSE_BET_ID_NOT_EXIST);
        } 

        $is_already_settled = $this->ssa_is_transaction_exists(self::TRANSACTION_TABLE, [
            'transaction_type' => 'bet',
            'player_id' => $player_id,
            'bet_id' => !empty($request['BetID']) ? $request['BetID'] : null,
            'status' => Game_logs::STATUS_SETTLED,
        ]);
        if($is_already_settled){
            return $this->setResponse(self::RESPONSE_FORCE_FAILED);
        }

        $this->ssa_set_related_uniqueid_of_seamless_service(!empty($betid) ? $this->utils->mergeArrayValues(['game', $this->api->getPlatformCode(), $betid]) : null);
        $this->ssa_set_related_action_of_seamless_service(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);

        $error_code = self::RESPONSE_UNKNOWN_ERROR; #default
        $response = array();
        
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $request, &$response, &$error_code) {
            $bet_amount = isset($request['Bet']) ? $this->api->gameAmountToDB($request['Bet']) : null;

            $before_balance = $this->getPlayerBalance();
            $after_balance = null;
            $adjustment_type = 'increase';
            $success = false; #default
            $seamless_service_unique_id = $this->utils->mergeArrayValues([$this->api->getPlatformCode(), $uniqueid]);
            $external_game_id = isset($request['GameID']) ? $request['GameID'] : null;

            $this->ssa_set_uniqueid_of_seamless_service($seamless_service_unique_id, $external_game_id);
            $this->ssa_set_game_provider_action_type(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
            $this->ssa_set_game_provider_is_end_round(true);


            if($this->utils->compareResultFloat($bet_amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $bet_amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $bet_amount, $after_balance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $before_balance -= $bet_amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($bet_amount, '=', 0)) {
                $success = true;
                if($this->ssa_enabled_remote_wallet()){
                    $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), 0, $after_balance);
                }
            } else { #default error
                $success = false;
            }

            $after_balance = !empty($after_balance) ? $after_balance : $this->getPlayerBalance();
            $request['transaction_type'] = 'cancel_bet';
            $request['external_unique_id'] = $uniqueid;

            if (!empty($this->remote_wallet_status) && !empty($request)) {
                $request['adjustment_type'] = $adjustment_type;
                $request['amount'] = $bet_amount;
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $request);
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $before_balance;
                $request['after_balance'] = $after_balance;
                $request['status'] = GAME_LOGS::STATUS_CANCELLED;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['MainPoints'] = $this->api->dBtoGameAmount($after_balance);
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
            "record_id" => isset($request['RecordID']) ? $request['RecordID'] : NULL, 
            "bet_id" => isset($request['BetID']) ? $request['BetID'] : NULL, 
            "bank_id" => isset($request['BankID']) ? $request['BankID'] : NULL, 
            "member_account" => isset($request['MemberAccount']) ? $request['MemberAccount'] : NULL, 
            "currency" => isset($request['Currency']) ? $request['Currency'] : NULL, 
            "game_id" => isset($request['GameID']) ? $request['GameID'] : NULL, 
            "game_type" => isset($request['GameType']) ? $request['GameType'] : NULL, 
            "is_buy_feature" => isset($request['isBuyFeature']) ? $request['isBuyFeature'] : NULL, 
            "bet" => isset($request['Bet']) ? $this->api->gameAmountToDB($request['Bet']) : NULL, 
            "win" => isset($request['Win']) ? $this->api->gameAmountToDB($request['Win']) : NULL, 
            "jp_bet" => isset($request['jpBet']) ? $this->api->gameAmountToDB($request['jpBet']) : NULL, 
            "jp_prize" => isset($request['JPPrize']) ? $this->api->gameAmountToDB($request['JPPrize']) : NULL, 
            "net_win" => isset($request['NetWin']) ? $this->api->gameAmountToDB($request['NetWin']) : NULL, 
            "require_amt" => isset($request['RequireAmt']) ? $this->api->gameAmountToDB($request['RequireAmt']) : NULL, 
            "refund" => isset($request['Refund']) ? $this->api->gameAmountToDB($request['Refund']) : NULL, 
            "game_date" => isset($request['GameDate']) ? $this->api->gameTimeToServerTime($request['GameDate']) : NULL, 
            "create_date" => isset($request['CreateDate']) ? $this->api->gameTimeToServerTime($request['CreateDate']) : NULL, 
            "ts" => isset($request['Ts']) ? $request['Ts'] : NULL,  
            "settle_bet_ids" => isset($request['SettleBetIDs']) ? json_encode($request['SettleBetIDs']) : NULL, 
            "json_request" =>  json_encode($this->request), 
            #sbe
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL, 
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL, 
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL, 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL, 
            "response_result_id" => $this->response_result_id,
        );
        // echo "<pre>";
        // print_r($dataToInsert);exit();
        $transId = $this->original_seamless_wallet_transactions->insertTransactionData(self::TRANSACTION_TABLE, $dataToInsert);

        if ($transId) {
            if ($request['transaction_type'] == 'settle') {
                if (!empty($request['SettleBetIDs']) && is_array($request['SettleBetIDs'])) {
                    foreach ($request['SettleBetIDs'] as $bet_transaction) {
                        if (!empty($bet_transaction['betID'])) {
                            $this->ssa_update_transaction_with_result_custom(self::TRANSACTION_TABLE, ['status' => $request['status']], ['transaction_type' => 'bet', 'bet_id' => $bet_transaction['betID']]);
                        }
                    }
                }
            }

            if ($request['transaction_type'] == 'cancel_bet') {
                if (!empty($request['BetID'])) {
                    $this->ssa_update_transaction_with_result_custom(self::TRANSACTION_TABLE, ['status' => $request['status']], ['transaction_type' => 'bet', 'bet_id' => $request['BetID']]);
                }
            }
        }

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
        $flag = $response['Result'] == self::STATUS_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        

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
            json_encode($this->actual_request),
            // $this->actual_request,
            [],#default empty response
            200,
            null,
            null
        );

        return $response_result_id;
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => !empty($data['RecordID']) ? $data['RecordID'] : null,
            'round_id' => !empty($data['RecordID']) ? $data['RecordID'] : null,
            'external_game_id' => !empty($data['GameID']) ? $data['GameID'] : null,
            'player_id' => $this->player_id,
            'game_username' => !empty($data['MemberAccount']) ? $data['MemberAccount'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => !empty($data['transaction_type']) ? $data['transaction_type'] : null,
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_raw_data' => json_encode($this->request),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => !empty($data['CreateDate']) ? $data['CreateDate'] : $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => !empty($data['external_unique_id']) ? $data['external_unique_id'] : null,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($this->use_remote_wallet_failed_transaction_monthly_table) {
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

        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);
    }
}

