<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Caletagaming_service_api extends BaseController {
    use Seamless_service_api_module;

    const SUCCESS = [
        'code' => 200,
        'message' => 'RS_OK',
        'status' => 'RS_OK'
    ];

    const ERROR_INTERNAL_SERVER_ERROR = [
        'code' => 500,
        'message' => 'Internal server error.',
        'status' => 'RS_ERROR_UNKNOWN'
    ];

    const ERROR_INVALID_SIGNATURE = [
        'code' => 500,
        'message' => 'Invalid signature.',
        'status' => 'RS_ERROR_INVALID_SIGNATURE'
    ];

    const ERROR_BAD_REQUEST = [
        'code' => 400,
        'message' => 'Bad request.',
        'status' => 'RS_ERROR_WRONG_SYNTAX'
    ];

    const ERROR_GAME_UNDER_MAINTENANCE = [
        'code' => 500,
        'message' => 'Game under maintenance.',
        'status' => 'RS_ERROR_UNKNOWN'
    ];

    const ERROR_INVALID_PARAM = [
        'code' => 500,
        'message' => 'Invalid parameter.',
        'status' => 'RS_ERROR_WRONG_SYNTAX'
    ];

    const ERROR_INVALID_TOKEN = [
        'code' => 500,
        'message' => 'Invalid token.',
        'status' => 'RS_ERROR_INVALID_TOKEN'
    ];

    const ERROR_TRANSACTION_EXIST = [
        'code' => 400,
        'message' => 'Transaction already exist.',
        'status' => 'RS_ERROR_DUPLICATE_TRANSACTION'
    ];

    const ERROR_NOT_ENOUGH_BALANCE = [
        'code' => 402,
        'message' => 'Not enough available balance or invalid amount.',
        'status' => 'RS_ERROR_NOT_ENOUGH_MONEY'
    ];

    const ERROR_PLAYER_NOT_FOUND = [
        'code' => 404,
        'message' => 'Player not found.',
        'status' => 'RS_ERROR_UNKNOWN'
    ];

    const ERROR_TRANSACTION_ROLLED_BACK = [
        'code' => 400,
        'message' => 'Rollback transaction failed',
        'status' => 'RS_ERROR_TRANSACTION_ROLLED_BACK'
    ];

    const ERROR_TRANSACTION_WIN = [
        'code' => 400,
        'message' => 'Win transaction failed',
        'status' => 'RS_ERROR_TRANSACTION_WIN'
    ];


    #for failed transaction testing
    const ERROR_TRANSACTION_FORCE_FAILED= [
        'code' => 500,
        'message' => 'Forced to failed.',
        'status' => 'RS_ERROR_UNKNOWN'
    ];

    const ERROR_INVALID_IP= [
        'code' => 500,
        'message' => 'Invalid Ip.',
        'status' => 'RS_ERROR_UNKNOWN',
        'statusCode' => self::STATUS_CODE_INVALID_IP
    ];

    const ERROR_REFERENCE_TRANSACTION_NOT_EXIST = [
        'code' => 400,
        'message' => 'Reference transaction not exist.',
        'status' => 'RS_ERROR_TRANSACTION_DOES_NOT_EXIST'
    ];

    const ALLOWED_METHOD_PARAMS = ['bet','balance','rollback','win'];
    const ALLOWED_FUNCTION = ["wallet"];
    const DEFAULT_TRIM_LENGTH = -33;
    const STATUS_CODE_INVALID_IP = 401;
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token','common_seamless_wallet_transactions','common_seamless_error_logs','external_system'));
        $function = $this->uri->segment(2);
        if(!in_array($function ,  self::ALLOWED_FUNCTION)){
            show_error('No permissions', 403);
            exit;
        }
    }

    public function wallet($method = null) {
        if(empty($method)){
            return $this->returnJsonResult(self::ERROR_BAD_REQUEST);
        }
        $request_json = file_get_contents('php://input');
        $this->request = json_decode($request_json, true);
        $this->request_method = $method;
        $api = CALETA_SEAMLESS_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        $this->request_headers = $this->input->request_headers();

        $this->utils->debug_log('caleta service request_headers', $this->request_headers);
        $this->utils->debug_log('caleta service method', $method);
        $this->utils->debug_log('caleta service request', $request_json);
        $this->player_id = null;
        $this->failed_remote_params = [];

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        if(!$this->api) {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $this->request_headers['Request-Ip'] = $ip;
            $error_response = self::ERROR_INVALID_IP;
            $error_response['message'] = "Forbidden: IP address rejected.({$ip})";
            return $this->setResponse($error_response);
        }

        $sign = isset($this->request_headers['X-Auth-Signature']) ? $this->request_headers['X-Auth-Signature'] : null;
        if(!$sign){
            return $this->setResponse(self::ERROR_INVALID_SIGNATURE);
        }

        $valid = $this->api->verify($request_json, $sign);
        if(!$valid){
            return $this->setResponse(self::ERROR_INVALID_SIGNATURE);
        }

        if(!$this->external_system->isGameApiActive($api) || $this->external_system->isGameApiMaintenance($api)) {
            return $this->setResponse(self::ERROR_GAME_UNDER_MAINTENANCE);
        }

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        return $this->$method();
    }

    function bet(){
        if($this->api->force_bet_failed_response){ #force to response failed bet
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $request = $this->request;
        $token = isset($request['token']) ? $request['token'] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $gameCode = isset($request['game_code']) ? $request['game_code'] : null;
        if(!empty($gameCode)){
            $this->getPlayerToken($token);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($playerDetails)){ 
            if($this->api->allowed_invalid_token_on_request){ #check if allowed expired or invalid token
                $gameUsername = isset($request['supplier_user']) ? $request['supplier_user'] : null;
                $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
                if(empty($playerDetails)){
                    return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
                }
            } else {
                return $this->setResponse(self::ERROR_INVALID_TOKEN);
            }
        }
        $this->player_id = $playerDetails['player_id'];
        $transactionId = isset($request['transaction_uuid']) ? __FUNCTION__ . '-' . $request['transaction_uuid'] : null;
        $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
        if(!empty($configEnabled)){
            $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$transactionId;       
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
            $this->wallet_model->setExternalGameId(isset($request['game_code']) ? $request['game_code'] : null);
        } 
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            // return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
            $betDetails = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $transactionId);
            if(!empty($betDetails)){
                $errorCode = self::SUCCESS;
                $response = [
                    'user' => $playerDetails['game_username'],
                    'status' => $errorCode['message'],
                    'request_uuid' => $request['request_uuid'],
                    'currency' => $this->api->currency,
                    'balance' => $this->api->dBtoGameAmount($betDetails['after_balance']),
                ];
                return $this->setResponse($errorCode, $response);
            } else {
                return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
            }
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $beforeBalance = $this->getPlayerBalance($playerName);
            $afterBalance = null;
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                    if(!$success){
                        if ($this->ssa_enabled_remote_wallet()) {
                            $this->utils->debug_log("CALETA SEAMLESS SERVICE FAILED REMOTE: (bet)", $request);
                            $this->failed_remote_params = $request;
                        }
                    }
                } else {
                    $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                    if(!$success){
                        if ($this->ssa_enabled_remote_wallet()) {
                            $this->utils->debug_log("CALETA SEAMLESS SERVICE FAILED REMOTE: (bet)", $request);
                            $this->failed_remote_params = $request;
                        }
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
                // $afterBalance = $this->getPlayerBalance($playerName);
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance($playerName);
                    if($afterBalance === false){
                        return false;
                    }
                }
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'bet';

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = [
                        'user' => $playerDetails['game_username'],
                        'status' => $errorCode['message'],
                        'request_uuid' => $request['request_uuid'],
                        'currency' => $this->api->currency,
                        'balance' => $this->api->dBtoGameAmount($afterBalance),
                    ];
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE; #not enough balance or invalid amount
            }
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    function rollback(){ # rollback is for bet only
        if($this->api->force_rollback_failed_response){ #force to response failed rollback
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $request = $this->request;
        $token = isset($request['token']) ? $request['token'] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $gameCode = isset($request['game_code']) ? $request['game_code'] : null;
        if(!empty($gameCode)){
            $this->getPlayerToken($token);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($playerDetails)){ 
            #Allowed invalid or expired token, recheck by game username
            $gameUsername = isset($request['user']) ? $request['user'] : null;
            $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
            if(empty($playerDetails)){
                return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
            }
            
        }
        $this->player_id = $playerDetails['player_id'];
        $transactionId = isset($request['transaction_uuid']) ? __FUNCTION__ . '-' . $request['transaction_uuid'] : null;
        $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
        if(!empty($configEnabled)){
            $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$transactionId;       
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
            $this->wallet_model->setExternalGameId(isset($request['game_code']) ? $request['game_code'] : null);
        } 
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            // return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
            $rollbackDetails = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $transactionId);
            if(!empty($rollbackDetails)){
                $errorCode = self::SUCCESS;
                $response = [
                    'user' => $playerDetails['game_username'],
                    'status' => $errorCode['message'],
                    'request_uuid' => $request['request_uuid'],
                    'currency' => $this->api->currency,
                    'balance' => $this->api->dBtoGameAmount($rollbackDetails['after_balance']),
                ];
                return $this->setResponse($errorCode, $response);
            } else {
                return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
            }
        }

        #check reference transaction either bet or win
        $referenceTransactionId = isset($request['reference_transaction_uuid']) ? $request['reference_transaction_uuid'] : null;
        $referenceTransaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $referenceTransactionId, 'transaction_id');
        if(empty($referenceTransaction)){
            return $this->setResponse(self::ERROR_REFERENCE_TRANSACTION_NOT_EXIST);
        }

        #to check if already proccessed by win
        $win_transaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $referenceTransactionId, 'transaction_id', 'win');
        if(!empty($win_transaction)){
            return $this->setResponse(self::ERROR_TRANSACTION_ROLLED_BACK);
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = 'rollback-'.$referenceTransaction['transaction_type'];

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $beforeBalance = $this->getPlayerBalance($playerName);
            $afterBalance = null;
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    if($request['transaction_type'] == 'rollback-win'){
                        $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                    } else if($request['transaction_type'] == 'rollback-bet'){
                        $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                    } else {
                        $success = false;#default
                    }
                } else {
                    if($request['transaction_type'] == 'rollback-win'){
                        $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else if($request['transaction_type'] == 'rollback-bet'){
                        $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else {
                        $success = false;#default
                    }

                    if(!$success){
                        if ($this->ssa_enabled_remote_wallet()) {
                            $this->utils->debug_log("CALETA SEAMLESS SERVICE FAILED REMOTE: (rollback)", $request);
                            $this->failed_remote_params = $request;
                        }
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
                // $afterBalance = $this->getPlayerBalance($playerName);
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance($playerName);
                    if($afterBalance === false){
                        return false;
                    }
                }
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                $request['round_closed'] = Game_logs::STATUS_CANCELLED;
                
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = [
                        'user' => $playerDetails['game_username'],
                        'status' => $errorCode['message'],
                        'request_uuid' => $request['request_uuid'],
                        'currency' => $this->api->currency,
                        'balance' => $this->api->dBtoGameAmount($afterBalance),
                    ];
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE; #not enough balance or invalid amount
            }
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    function win(){
        if($this->api->force_win_failed_response){ #force to response failed win
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $request = $this->request;
        $token = isset($request['token']) ? $request['token'] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $gameCode = isset($request['game_code']) ? $request['game_code'] : null;
        if(!empty($gameCode)){
            $this->getPlayerToken($token);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($playerDetails)){ 
            #Allowed invalid or expired token, recheck by game username
            $gameUsername = isset($request['supplier_user']) ? $request['supplier_user'] : null;
            $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
            if(empty($playerDetails)){
                return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
            }
            
        }
        $this->player_id = $playerDetails['player_id'];
        $transactionId = isset($request['transaction_uuid']) ? __FUNCTION__ . '-' . $request['transaction_uuid'] : null;
        $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
        if(!empty($configEnabled)){
            $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$transactionId;       
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
            $this->wallet_model->setExternalGameId(isset($request['game_code']) ? $request['game_code'] : null);

                        
            $round_id = $request['round'];

            $previousTransaction = $this->common_seamless_wallet_transactions->getRoundData([
                "round_id" => $round_id, 
                "game_platform_id" => $this->api->getPlatformCode(),
                "transaction_type" => Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET
            ]);
            

            $this->utils->debug_log('CALETA -' .__FUNCTION__, [
                '$previousTransaction' =>  $previousTransaction
            ]);


            $relatedUniqueIdOfSeamlessService = 'game-'.$this->api->getPlatformCode().'-';
            
            $relatedUniqueIdOfSeamlessService .= $previousTransaction[0]['external_unique_id'];
            $relatedActionOfSeamlessService = $previousTransaction[0]['transaction_type'];
            
            $this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueIdOfSeamlessService);
            $this->wallet_model->setRelatedActionOfSeamlessService($relatedActionOfSeamlessService);

            $this->wallet_model->setGameProviderIsEndRound(true); 

        } 
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
        }

        #check reference transaction bet
        $referenceTransactionId = isset($request['reference_transaction_uuid']) ? $request['reference_transaction_uuid'] : null;
        $referenceTransaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $referenceTransactionId, 'transaction_id');
        if(empty($referenceTransaction)){
            return $this->setResponse(self::ERROR_REFERENCE_TRANSACTION_NOT_EXIST);
        }

        #to check if already proccessed by rollback
        $rollback_transaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $referenceTransactionId, 'transaction_id', 'rollback-bet');
        if(!empty($rollback_transaction)){
            return $this->setResponse(self::ERROR_TRANSACTION_WIN);
        }
        
        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = 'win';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $beforeBalance = $this->getPlayerBalance($playerName);
            $afterBalance = null;
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                    if(!$success){
                        if ($this->ssa_enabled_remote_wallet()) {
                            $this->utils->debug_log("CALETA SEAMLESS SERVICE FAILED REMOTE: (win)", $request);
                            $this->failed_remote_params = $request;
                        }
                    } 
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                // $success = true;#allowed amount 0
                
                // Call incSubWallet even if 0 win.
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    if ($this->ssa_enabled_remote_wallet()) {
                        $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else {
                        $success = true;
                    }
                }
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                // $afterBalance = $this->getPlayerBalance($playerName);
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance($playerName);
                    if($afterBalance === false){
                        return false;
                    }
                }
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = [
                        'user' => $playerDetails['game_username'],
                        'status' => $errorCode['message'],
                        'request_uuid' => $request['request_uuid'],
                        'currency' => $this->api->currency,
                        'balance' => $this->api->dBtoGameAmount($afterBalance),
                    ];
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE; #not enough balance or invalid amount
            }
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    public function processRequestData($request){

        $dataToInsert = array(
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL,
            "game_id" => isset($request['game_code']) ? $request['game_code'] : NULL,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "status" => isset($request['round_closed']) ? $request['round_closed'] : NULL,
            "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "extra_info" => json_encode($this->request), #actual request
            "start_at" => $this->utils->getNowForMysql(),
            "end_at" => $this->utils->getNowForMysql(),
            "round_id" => isset($request['round']) ? $request['round'] : NULL,
            "transaction_id" => isset($request['transaction_uuid']) ? $request['transaction_uuid'] : NULL, #mark as bet id
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
        );
        #override transaction id, common on for win and rollback
        if(isset($request['reference_transaction_uuid'])){
            $dataToInsert['transaction_id'] = $request['reference_transaction_uuid']; #to mark related rollback and win for 1 specific transaction
        }

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
    }

    function balance(){
        $request = $this->request;
        $token = isset($request['token']) ? $request['token'] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $gameCode = isset($request['game_code']) ? $request['game_code'] : null;
        if(!empty($gameCode)){
            $this->getPlayerToken($token);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_TOKEN);
        }

        $playername = $playerDetails['username'];
        $balance = 0;
        $this->player_id = $playerDetails['player_id'];
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use(&$balance, $playername) {
            $balance = $this->getPlayerBalance($playername);
            if($balance === false) {
                $balance = 0;
                return false;
            }
            return true;
        });

        if($success){
            $response = [
                'user' => $playerDetails['game_username'],
                'status' => self::SUCCESS['message'],
                'request_uuid' => $request['request_uuid'],
                'currency' => $this->api->currency,
                'balance' => $this->api->dBtoGameAmount($balance),
            ];
            return $this->setResponse(self::SUCCESS, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    #trim gamecode hash
    function getPlayerToken(&$token){
        $length = self::DEFAULT_TRIM_LENGTH; # strlen(-) + strlen(md5), 
        $token = substr($token, 0, $length);
    }

    private function getPlayerBalance($playerName, $is_locked = true){
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $player_id = $this->api->getPlayerIdFromUsername($playerName);
            $seamless_balance = 0;
            $seamless_reason_id = null;

            if(!$is_locked){
                $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, &$seamless_balance, &$seamless_reason_id) {
                    return  $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
                });
            } else {
                $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            }
            // $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            return $seamless_balance;
        }
        else {
            $get_bal_req = $this->api->queryPlayerBalance($playerName);
            if($get_bal_req['success']) {
                return $get_bal_req['balance'];
            }
            else {
                return false;
            }
        }
    }

    private function saveRemoteWalletFailedTransaction($queryType, $data, $where = []) {
        $useMonthly = true;
        $increaseType = ['rollback-bet', 'win'];
        $failedParams = [
            'transaction_id' => !empty($data['transaction_uuid']) ? $data['transaction_uuid'] : null,
            'round_id' => !empty($data['round']) ? $data['round'] : null,
            'external_game_id' => !empty($data['game_code']) ? $data['game_code'] : null,
            'player_id' => $this->player_id,
            'game_username' => null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => isset($data['transaction_type']) && in_array($data['transaction_type'], $increaseType) ? $this->ssa_increase : $this->ssa_decrease,
            'action' => $this->request_method,
            'game_platform_id' => CALETA_SEAMLESS_API,
            'transaction_raw_data' => json_encode($this->request),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->ssa_get_remote_wallet_error_code(),
            'transaction_date' => $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => !empty($data['external_unique_id']) ? $data['external_unique_id'] : null,
        ];

        $failedParams['md5_sum'] = md5(json_encode($failedParams));

        if (empty($failedParams['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($useMonthly) {
            $yearMonth = $this->utils->getThisYearMonth();
            $tableName = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$yearMonth}";
        } else {
            $tableName = $this->ssa_failed_remote_common_seamless_transactions_table;
        }

        if ($this->ssa_is_transaction_exists($tableName, ['external_uniqueid' => $failedParams['external_uniqueid']])) {
            $queryType = $this->ssa_update;

            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $failedParams['external_uniqueid'],
                ];
            }
        }
        $this->utils->debug_log("CALETA SEAMLESS SERVICE FAILED REMOTE: (table)", $tableName);
        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $queryType, $failedParams, $where, $useMonthly);
    }

    private function setResponse($returnCode, $response = []) {
        if(!empty($this->failed_remote_params)){
            $this->saveRemoteWalletFailedTransaction($this->ssa_insert, $this->failed_remote_params);
        }
        return $this->setOutput($returnCode, $response);
    }


    private function setOutput($returnCode, $response = []) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;
        $http_status_code = 0;
        $flag = $returnCode['code'] == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($flag == Response_result::FLAG_ERROR){
            $response = $returnCode;
            $response['request_uuid']= isset($this->request['request_uuid']) ? $this->request['request_uuid'] : null;
            $response['currency']= $this->api->currency;
            if(isset($this->request['supplier_user'])){
                $response['user'] = $this->request['supplier_user'];
            }
            if(isset($this->request['user'])){
                $response['user'] = $this->request['user'];
            }

            if(isset($response['user'])){
                $playerName = $this->api->getPlayerUsernameByGameUsername($response['user']);
                $balance = $this->getPlayerBalance($playerName, self::FALSE);
                $response['balance'] = $this->api->dBtoGameAmount($balance);
            }
        }
        if($this->response_result_id) {
            $disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
            if($disabled_response_results_table_only){
                $respRlt = $this->response_result->readNewResponseById($this->response_result_id);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                if(isset($response['message']) && isset($response['statusCode'])  && $response['statusCode'] == self::STATUS_CODE_INVALID_IP){
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
                if(isset($response['message']) && isset($response['statusCode'])  && $response['statusCode'] == self::STATUS_CODE_INVALID_IP){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->response_result_id, null, $this->player_id, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }

        if($flag == Response_result::FLAG_ERROR){
            if($this->api){
                $request_id = $this->utils->getRequestId();
                $now = $this->utils->getNowForMysql();
                $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
                $commonSeamlessErrorDetails = json_encode($response);
                $errorLogInsertData = [
                    'game_platform_id' => $this->api->getPlatformCode(),
                    'response_result_id' => $this->response_result_id,
                    'request_id' => $request_id,
                    'elapsed_time' => $elapsed,
                    'error_date' => $now,
                    'extra_info' => $commonSeamlessErrorDetails
                ];
                $this->common_seamless_error_logs->insertTransaction($errorLogInsertData);
            }
        }

        #unset some field that not need on output but need for internal checking
        if(isset($response['code'])){
            unset($response['code']);
        }
        if(isset($response['message'])){
            unset($response['message']);
        }
        if(isset($response['statusCode'])){
            unset($response['message']);
            unset($response['statusCode']);
            unset($response['user']);
            unset($response['balance']);
        }
        
        // return $this->returnJsonResult($response);
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
