<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';
/**
 * Bigpot seamless game integration
 * OGP-28086
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
    The Operator must implement the following API Endpoints.

    Endpoint            Path                                    Description
    Initial             http://{Operatordomain}/initial         Player Validation
    Balance             http://{Operatordomain}/balance         Get player balance
    Bet                 http://{Operatordomain}/bet             Debits on player's balance
    Result              http://{Operatordomain}/result          Credits on player's balance
    Rollback            http://{Operatordomain}/rollback        Rollback on player's bet
    Startgame           http://{Operatordomain}/startgame       Launching games in the lobby
 * 
 * 
 * Slot
    unique value --> gameId
    slot "transaction" is the same in the freespins 
    (In freespins, different gameId and same transaction id) 


    Table Games and Casual Games
    unique value --> transaction
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_bigpot_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Bigpot_service_api extends BaseController {
    use Seamless_service_api_module;

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ["initial", "balance", "bet", "result", "rollback"];
    const METHOD_ALLOWED_IN_MAINTENANCE = ["result"];
    const STATUS_SUCCESS = 1;

    #Response Code
    const RESPONSE_SUCCESS = [
        "code" => 1
    ];

    const RESPONSE_INVALID_REQUEST = [
        "code" => -1,
        "error" => "Invalid request"
    ];

    const RESPONSE_INVALID_PARAMETER = [
        "code" => -2,
        "error" => "Invalid parameter"
    ];


    const RESPONSE_INVALID_HASH = [
        "code" => -3,
        "error" => "Invalid hash"
    ];

    const RESPONSE_INVALID_TOKEN = [
        "code" => -4,
        "error" => "Invalid token"
    ];

    const RESPONSE_INVALID_CURRENCY = [
        "code" => -5,
        "error" => "Invalid currency"
    ];

    const RESPONSE_INVALID_OP_CODE = [
        "code" => -7,
        "error" => "Invalid opcode"
    ];

    const RESPONSE_INTERNAL_ERROR = [
        "code" => -8,
        "error" => "Seamless API error"
    ];

    const RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST = [
        "code" => -8,
        "error" => "Seamless API error (Method not exist)"
    ];

    const RESPONSE_INTERNAL_ERROR_GAME_DISABLED_OR_MAINTENANCE = [
        "code" => -8,
        "error" => "Seamless API error (Game is disabled or on maintenance)"
    ];

    const RESPONSE_INTERNAL_ERROR_PLAYER_LOCKED = [
        "code" => -8,
        "error" => "Seamless API error (Player is blocked)"
    ];

    const RESPONSE_INTERNAL_ERROR_TRANSACTIONID_NOT_EXIST= [
        "code" => -8,
        "error" => "Seamless API error (Transaction not exist)"
    ];

    const RESPONSE_INVALID_ACCOUNT = [
        "code" => -9,
        "error" => "Invalid account"
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        "code" => -33,
        "error" => "Not enough player's balance"
    ];

    const RESPONSE_IP_ACCESS_RESTRICTED = [
        "code" => 401,
        "error" => "IP Access Restricted"
    ];

    // code 0 means just fail
    const RESPONSE_FORCE_FAILED = [
        "code" => 0,
        "error" => "FORCE_FAILED"
    ];

    const RESPONSE_BET_NOT_FOUND = [
        "code" => 0,
        "error" => 'Bet not found'
    ];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'external_system', 'player_model', 'game_provider_auth'));
    }

    public function api($method = null) {
        $this->request_headers = $this->input->request_headers();
        $this->request = $request = json_decode(file_get_contents('php://input'), true);
        $this->game_platform_id = BIGPOT_SEAMLESS_GAME_API;
        $this->request_method = $method;
        $this->player_id = null;
        $this->ip_invalid = false;

        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API service request_headers', $this->request_headers);
        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API service method', $method);
        $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API service request', $request);

        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;

        if(empty($method)){
            return $this->returnJsonResult(self::RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST, $addOrigin, $origin, $pretty, $partial_output_on_error, self::RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST['code']);
        }

        $this->api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->api) {
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult(self::RESPONSE_INTERNAL_ERROR, $addOrigin, $origin, $pretty, $partial_output_on_error, self::RESPONSE_INTERNAL_ERROR['code']);
        } 

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR);
        }

        if(!$this->api) {
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
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
    
        if((!$this->external_system->isGameApiActive($this->game_platform_id) || $this->external_system->isGameApiMaintenance($this->game_platform_id)) && !in_array($method, self::METHOD_ALLOWED_IN_MAINTENANCE)) {
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_GAME_DISABLED_OR_MAINTENANCE);
        }

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            $this->utils->debug_log('BIGPOT_SEAMLESS_GAME_API INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_METHOD_NOT_EXIST);
        }

        return $this->$method();
    }

    private function initial(){
        $request = $this->request;

        $currency = isset($request['currency']) ? $request['currency'] : null;
        if($currency != $this->api->currency){
            return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
        }

        $token = isset($request['token']) ? $request['token'] : null;

        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($player_details)){
            return $this->setResponse(self::RESPONSE_INVALID_TOKEN);
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
        $response['account'] = $player_details['game_username'];
        $response['cash'] = $this->api->dBtoGameAmount($balance);
        $response['token'] = $token;
        return $this->setResponse($response);
    }

    private function balance(){
        $request = $this->request;

        $currency = isset($request['currency']) ? $request['currency'] : null;
        if($currency != $this->api->currency){
            return $this->setResponse(self::RESPONSE_INVALID_CURRENCY);
        }

        $game_username = isset($request['account']) ? $request['account'] : null;
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
        $response['currency'] = $this->api->currency;
        $response['balance'] = $this->api->dBtoGameAmount($balance);
        return $this->setResponse($response);
    }

    private function bet(){
        $request = $this->request;

        $game_username = isset($request['account']) ? $request['account'] : null;
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

        $bets = isset($request['bet']) ? $request['bet'] : [];
        $total_bet_amount = 0;
        if(!empty($bets)){
            $is_valid_amount = true;
            array_walk($bets, function($rows) use (&$is_valid_amount, &$total_bet_amount) {
                if(isset($rows['amount'])){
                    $total_bet_amount += $rows['amount'];
                    if($is_valid_amount){
                        $is_valid_amount = $this->isValidAmount($rows['amount']);
                    }
                } else{
                    $is_valid_amount = false;
                }
            });

            if(!$is_valid_amount){
                return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
            }
        }

        $transaction_id = isset($request["transactionId"]) ? $request["transactionId"] : null;
        if(empty($transaction_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }
        $uniqueid = "B".$transaction_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $bet_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            if(!empty($bet_details)){
                $response = self::RESPONSE_SUCCESS;
                $response['transactionId'] = $transaction_id;
                $response['cash'] = $this->api->dBtoGameAmount($bet_details['after_balance']);
                $response['account'] = $game_username;
                $response['currency'] = $this->api->currency;
                return $this->setResponse($response);
            }
        }
        
        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $amount = $this->api->gameAmountToDB($total_bet_amount);
        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $amount, $request, &$response, &$error_code) {
            $before_balance = $this->getPlayerBalance();
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
                    //OGP-28649
                    $tname = isset($request['tname']) ? $request['tname'] : null;
                    $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                    $this->wallet_model->setGameProviderActionType($remoteActionType);
                    $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$uniqueid;       
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $tname); 
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
                $request['transaction_type'] = 'bet';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_PENDING;
                $request['amount'] = $amount;
                $request['bet_amount'] = $amount;
                $request['result_amount'] = -$amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['transactionId'] = $request["transactionId"];
                    $response['cash'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['account'] = $request["account"];
                    $response['currency'] = $this->api->currency;
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

    private function result(){
        $request = $this->request;
        $round_id = isset($request["gameId"]) ? $request["gameId"] : null;
        $transaction_id = isset($request["transactionId"]) ? $request["transactionId"] : null;
        $slot_bonus_win = isset($request["slotBonusWin"]) ? $request["slotBonusWin"] : false;
        $force = isset($request['force']) && $request['force'] ? true : false;

        if(empty($transaction_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $payout_data = isset($request['payout_data']) ? $request['payout_data'] : [];
        if(count($payout_data) != 1){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }
        $game_username = isset($payout_data[0]['account']) ? $payout_data[0]['account'] : null;
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

        $uniqueid = !$slot_bonus_win ? "R".$transaction_id : $transaction_id."-".$round_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $result_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            if(!empty($result_details)){
                $response = self::RESPONSE_SUCCESS;
                $response['payout_data'][] = array(
                    "account" => $game_username,
                    "transactionId" => $transaction_id,
                    "cash" => $this->api->dBtoGameAmount($result_details['after_balance']),
                    "currency" => $this->api->currency
                );
                return $this->setResponse($response);
            }
        }

        $result_details = $this->common_seamless_wallet_transactions->get_transaction([
            'transaction_type' => __FUNCTION__,
            'player_id' => $player_id,
            'round_id' => $round_id,
        ]);

        if (!empty($result_details) && !$force) {
            $response = self::RESPONSE_SUCCESS;
            $response['payout_data'][] = array(
                "account" => $game_username,
                "transactionId" => $transaction_id,
                "cash" => $this->api->dBtoGameAmount($result_details['after_balance']),
                "currency" => $this->api->currency
            );
            return $this->setResponse($response);
        }

        $bet_uniqueid = "B".$transaction_id;
        /* $is_bet_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $bet_uniqueid);
        if($slot_bonus_win){
            $bet_record = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $round_id, 'round_id', 'bet');
            $is_bet_exist = empty($bet_record) ? false : true;
            if(!empty($bet_record)){
                #override transaction id for mapping on gamelogs on merging
                $request['transactionId'] = $bet_record->transaction_id;
            }
        }
        if(!$is_bet_exist){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_TRANSACTIONID_NOT_EXIST);
        } */

        $bet_transaction = $this->ssa_get_transaction('common_seamless_wallet_transactions', [
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_type' => 'bet',
            'external_unique_id' => $bet_uniqueid,
        ]);

        if ($slot_bonus_win) {
            $bet_transaction = $this->ssa_get_transaction('common_seamless_wallet_transactions', [
                'game_platform_id' => $this->api->getPlatformCode(),
                'transaction_type' => 'bet',
                'round_id' => $round_id,
            ]);

            if (!empty($bet_transaction)) {
                #override transaction id for mapping on gamelogs on merging
                $request['transactionId'] = $bet_transaction['transaction_id'];
            }
        }

        if (empty($bet_transaction)) {
            return $this->setResponse(self::RESPONSE_BET_NOT_FOUND);
        } else {
            if (isset($bet_transaction['status']) && $bet_transaction['status'] == Game_logs::STATUS_REFUND) {
                return $this->setResponse(self::RESPONSE_FORCE_FAILED);
            }
        }
        
        $payout_winning = isset($payout_data[0]['winning']) ? $payout_data[0]['winning'] : null;
        if(!$this->isValidAmount($payout_winning)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }
        
        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $amount = $this->api->gameAmountToDB($payout_winning);

        $extra = [
            'reference_transaction_id' => $bet_uniqueid,
        ];

        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $amount, $request, $game_username, $transaction_id, $payout_data, &$response, &$error_code, $extra) {
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
            $is_enable_single_wallet = $this->utils->getConfig('enable_seamless_single_wallet');

            #if enable remote wallet, it should accept 0 amount on increase_balance
            $is_enabled_remote_wallet_client_on_currency = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            $amount_operator = $is_enabled_remote_wallet_client_on_currency ? '>=' : '>';

            if($this->utils->compareResultFloat($amount, '>', 0) && $is_enable_single_wallet) {
                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                                 
            } elseif($this->utils->compareResultFloat($amount, $amount_operator, 0)){
                //OGP-28649
                $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                $unique_game_id = isset($payout_data[0]['gameId']) ? $payout_data[0]['gameId'] :null;
                $this->wallet_model->setGameProviderActionType($remoteActionType);
                $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $unique_game_id); 

                #OGP-34763
                $this->wallet_model->setRelatedUniqueidOfSeamlessService('game-'.$this->api->getPlatformCode().'-B'.$transaction_id);
                $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                $this->wallet_model->setGameProviderIsEndRound(true);
                
                $success = $this->wallet_model->incSubWallet($player_id, $this->api->getPlatformCode(), $amount);
            }elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
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
                $request['transaction_type'] = 'result';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_SETTLED;
                $request['amount'] = $amount;
                $request['bet_amount'] = 0;
                $request['result_amount'] = $amount;
                $transId = $this->processRequestData($request, $extra);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response['payout_data'][] = array(
                        "account" => $game_username,
                        "transactionId" => $transaction_id,
                        "cash" => $this->api->dBtoGameAmount($afterBalance),
                        "currency" => $this->api->currency
                    );
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
        $request = $this->request;
        $round_id = isset($request["gameId"]) ? $request["gameId"] : null;
        $transaction_id = isset($request["transactionId"]) ? $request["transactionId"] : null;
        $slot_bonus_win = isset($request["slotBonusWin"]) ? $request["slotBonusWin"] : false;
        $force = isset($request['force']) && $request['force'] ? true : false;
        $code = 1; 

        if(empty($transaction_id)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $game_username = isset($request['account']) ? $request['account'] : null;
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

        $uniqueid = !$slot_bonus_win ? "RB".$transaction_id : $transaction_id."-".$round_id;
        $is_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueid);
        if($is_exist){
            $result_details = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $uniqueid);
            if(!empty($result_details)){
                $response = self::RESPONSE_SUCCESS;
                $response['transactionId'] = $transaction_id;
                return $this->setResponse($response);
            }
        }

        $result_details = $this->common_seamless_wallet_transactions->get_transaction([
            'transaction_type' => __FUNCTION__,
            'player_id' => $player_id,
            'round_id' => $round_id,
        ]);

        if (!empty($result_details) && !$force) {
            $response = self::RESPONSE_SUCCESS;
            $response['transactionId'] = $transaction_id;
            return $this->setResponse($response);
        }

        $bet_uniqueid = "B".$transaction_id;
        /* $is_bet_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $bet_uniqueid);
        if(!$is_bet_exist){
            return $this->setResponse(self::RESPONSE_INTERNAL_ERROR_TRANSACTIONID_NOT_EXIST);
        } */

        $bet_transaction = $this->ssa_get_transaction('common_seamless_wallet_transactions', [
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_type' => 'bet',
            'external_unique_id' => $bet_uniqueid,
        ]);

        if (empty($bet_transaction)) {
            return $this->setResponse(self::RESPONSE_BET_NOT_FOUND);
        } else {
            if (isset($bet_transaction['status']) && $bet_transaction['status'] == Game_logs::STATUS_SETTLED) {
                return $this->setResponse(self::RESPONSE_FORCE_FAILED);
            }
        }

        $refund_amount = isset($request['amount']) ? $request['amount'] : null;
        if(!$this->isValidAmount($refund_amount)){
            return $this->setResponse(self::RESPONSE_INVALID_PARAMETER);
        }

        $error_code = self::RESPONSE_INTERNAL_ERROR; #default
        $response = array();
        $amount = $this->api->gameAmountToDB($refund_amount);

        $extra = [
            'reference_transaction_id' => $bet_uniqueid,
        ];

        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, $uniqueid, $amount, $request, $game_username, $transaction_id, &$response, &$error_code, $extra) {
            $before_balance = $this->getPlayerBalance();
            $success = false; #default
                
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 

            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_id, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    #OGP-34993
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService('game-'.$this->api->getPlatformCode().'-B'.$transaction_id);
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                    $this->wallet_model->setGameProviderIsEndRound(true);
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
                $request['transaction_type'] = 'rollback';
                $request['external_unique_id'] = $uniqueid;
                $request['status'] = GAME_LOGS::STATUS_REFUND;
                $request['amount'] = $amount;
                $request['bet_amount'] = 0;
                $request['result_amount'] = $amount;
                $transId = $this->processRequestData($request, $extra);
                if($transId){
                    $success = true;
                    $error_code = $response = self::RESPONSE_SUCCESS;
                    $response = array(
                        "code"          => 1, // 1 = Success, 0 = not found
                        "transactionId" => $transaction_id,
                    );
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
            if($amount < 0 ){
                return false;
            }
            return true;
        }
    }

    
    private function processRequestData($request, $extra = []){
        $dataToInsert = array(
            "round_id" => isset($request['gameId']) ? $request['gameId'] : NULL, 
            "status" => isset($request['status']) ? $request['status'] : NULL, 
            "game_id" => isset($request['tname']) ? $request['tname'] : 'unknown', 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "transaction_id" => isset($request['transactionId']) ? $request['transactionId'] : NULL, 
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
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : 0,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : 0,
        );

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);

        // update bet status
        if ($transId && !empty($extra['reference_transaction_id']) && in_array($request['transaction_type'], ['result', 'rollback'])) {
            $this->ssa_update_transaction_with_result_custom('common_seamless_wallet_transactions', ['status' => $request['status']], ['transaction_type' => 'bet', 'external_unique_id' => $extra['reference_transaction_id']]);
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
        $flag = $response['code'] == self::STATUS_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        

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

