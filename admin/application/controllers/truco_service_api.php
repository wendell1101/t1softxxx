<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/*
Operator Integration APIs
    -player_info
    -bet
    -payout
    -refund
    -settle
*/

class Truco_service_api extends BaseController {

    const SUCCESS = [
        'is_success' => true
    ];

    const ERROR_INVALID_MERCHANT_CODE = [
        'is_success' => false,
        'err_msg' => 'Invalid merchant_code'
    ];

    const ERROR_INTERNAL_SERVER_ERROR = [
        'is_success' => false,
        'err_msg' => 'Internal server error'
    ];

    const ERROR_INVALID_SIGNATURE = [
        'is_success' => false,
        'err_msg' => 'Invalid signature'
    ];

    const ERROR_INVALID_TIMESTAMP = [
        'is_success' => false,
        'err_msg' => 'Invalid timestamp'
    ];

    const ERROR_GAME_UNDER_MAINTENANCE = [
        'is_success' => false,
        'err_msg' => 'Server under maintenance'
    ];

    const ERROR_INVALID_PARAM = [
        'is_success' => false,
        'err_msg' => 'Invalid parameters'
    ];

    const ERROR_INVALID_TOKEN = [
        'is_success' => false,
        'err_msg' => 'Invalid token'
    ];

    const ERROR_TRANSACTION_EXIST = [
        'is_success' => false,
        'err_msg' => 'Unique id of transaction already exist'
    ];

    const ERROR_NOT_ENOUGH_BALANCE_OR_INVALID = [
        'is_success' => false,
        'err_msg' => 'Insufficient balance or invalid amount'
    ];

    const ERROR_INVALID_USERNAMEORPASSWORD = [
        'is_success' => false,
        'err_msg' => 'Invalid Username or Password'
    ];

    const ERROR_BAD_REQUEST = [
        'is_success' => false,
        'err_msg' => 'Bad request'
    ];

    #for failed transaction testing
    const ERROR_TRANSACTION_FORCE_FAILED= [
        'is_success' => false,
        'err_msg' => 'Forced to failed'
    ];

    const ERROR_REFERENCE_TRANSACTION_NOT_EXIST = [
        'is_success' => false,
        'err_msg' => 'Reference bet id not exist'
    ];

    const ERROR_BET_ALREADY_HAVE_PAYOUT = [
        'is_success' => false,
        'err_msg' => 'Bet already have payout'
    ];

    const ERROR_INVALID_IP = [
        'is_success' => false,
        'err_msg' => 'Invalid Ip.',
        'status_code' => self::STATUS_CODE_INVALID_IP
    ];
    const STATUS_CODE_INVALID_IP = 401;

    const ALLOWED_METHOD_PARAMS = ['player_info','bet','payout','refund','settle'];
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token','common_seamless_wallet_transactions','common_seamless_error_logs','external_system'));
    }

    private function generateSignature($request){
        if(isset($request['sign'])){
            unset($request['sign']);
        }

        $sign =  $this->api->generateSignature($request);
        $this->returnJsonResult($sign);
    }

    private function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
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

    public function index($method = null) {
        if(empty($method)){
            return $this->returnJsonResult(self::ERROR_BAD_REQUEST);
        }

        $api = TRUCO_SEAMLESS_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->returnJsonResult(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        $this->request_headers = $this->input->request_headers();
        $this->request = file_get_contents('php://input');
        $this->request_method = $method;
        parse_str($this->request, $request);

        $this->utils->debug_log('truco service request_headers', $this->request_headers);
        $this->utils->debug_log('truco service method', $method);
        $this->utils->debug_log('truco service request', $request);
        $this->player_id = null;

        if($method == "generate_sign"){
            return $this->generateSignature($request);
        }

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
            $error_response = self::ERROR_INVALID_IP;
            $error_response['err_msg'] = "Forbidden: IP address rejected.({$ip})";
            return $this->setResponse($error_response);
        }

        if(strpos($method, "-") !== false){
            $method = str_replace('-', '_', $method);
        }

        if(!isset($request['merchant_code']) || $this->api->merchant_code !== $request['merchant_code']){
            return $this->setResponse(self::ERROR_INVALID_MERCHANT_CODE);
        }

        if(!isset($request['timestamp']) || !$this->isValidTimeStamp($request['timestamp'])){
            return $this->setResponse(self::ERROR_INVALID_TIMESTAMP);
        }

        $request_sign = null;
        if(isset($request['sign'])){
            $request_sign = $request['sign'];
            unset($request['sign']);
        }

        $sign = $this->api->generateSignature($request);
        if(empty($request_sign) || $request_sign !== $sign) {
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

        return $this->$method($request);
    }

    public function bet($request){
        if($this->api->force_bet_failed_response){ #force to response failed bet
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $uniqueId = isset($request['unique_id']) ? $request['unique_id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
        if($isTransactionExist){
            return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
        }

        // $refundTransId = isset($request['bet_id']) ? $request['bet_id'] : null;
        // $isRefunded = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $refundTransId, 'refund');
        // if(!empty($isRefunded)){
        //     #if refund exist override amount and return success
        //     $request['amount'] = 0;
        // }

        if(!isset($request['amount']) || !$this->isValidAmount($request['amount'])){
            return $this->setResponse(self::ERROR_NOT_ENOUGH_BALANCE_OR_INVALID);
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $uniqueId;
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $beforeBalance = $this->getPlayerBalance($playerName);
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance($playerName);
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'bet';
                $request['bet_amount'] = $amount;
                $request['result_amount'] = -$amount;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "is_success" => $success,
                        "username" => $playerDetails['game_username'],
                        "balance" => $this->api->dBtoGameAmount($afterBalance),
                        "currency" => $this->api->currency
                    );
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE_OR_INVALID; #not enough balance or invalid amount
            }
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    public function payout($request){
        if($this->api->force_win_failed_response){ #force to response failed win
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $uniqueId = isset($request['unique_id']) ? $request['unique_id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
        if($isTransactionExist){
            return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
        }

        if(!isset($request['amount']) || !$this->isValidAmount($request['amount'])){
            return $this->setResponse(self::ERROR_NOT_ENOUGH_BALANCE_OR_INVALID);
        }
        
        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $uniqueId;
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $beforeBalance = $this->getPlayerBalance($playerName);
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance($playerName);
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'payout';
                $request['bet_amount'] = 0;
                $request['result_amount'] = $amount;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "is_success" => $success,
                        "username" => $playerDetails['game_username'],
                        "balance" => $this->api->dBtoGameAmount($afterBalance),
                        "currency" => $this->api->currency
                    );
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE_OR_INVALID; #not enough balance or invalid amount
            }
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    public function refund($request){
        if($this->api->force_rollback_failed_response){ #force to response failed cancel
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $uniqueId = isset($request['unique_id']) ? $request['unique_id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
        if($isTransactionExist){
            return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
        }

        #check reference transaction bet
        $betId = isset($request['bet_id']) ? $request['bet_id'] : null;
        $betTransaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $betId, 'transaction_id','bet');
        if(empty($betTransaction)){
            return $this->setResponse(self::ERROR_REFERENCE_TRANSACTION_NOT_EXIST);
        } else {
            $payoutTransaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $betId, 'transaction_id','payout');
            if(!empty($payoutTransaction)){
                return $this->setResponse(self::ERROR_BET_ALREADY_HAVE_PAYOUT);
            }
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $uniqueId;
        $request['amount'] = $betTransaction['amount'];
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = $request['amount'];
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $beforeBalance = $this->getPlayerBalance($playerName);
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance($playerName);
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'refund';

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "is_success" => $success,
                        "username" => $playerDetails['game_username'],
                        "balance" => $this->api->dBtoGameAmount($afterBalance),
                        "currency" => $this->api->currency
                    );
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE; #not enough balance or invalid amount
            }
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    public function settle($request){
        $round = isset($request['round_id']) ? $request['round_id'] : null;
        if(empty($round)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $uniqueId = isset($request['unique_id']) ? $request['unique_id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
        if($isTransactionExist){
            return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $request['external_unique_id'] = $uniqueId;
        $success = false; #default
        $request['response_result_id'] = $this->response_result_id;
        $request['status'] = $request['transaction_type'] = 'settle';

        $transId = $this->processRequestData($request);
        if($transId){
            $success = true;
            $errorCode = self::SUCCESS;
            $response = array(
                "is_success" => $success,
                "currency" => $this->api->currency
            );
        }

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse($errorCode);
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
            "status" => isset($request['status']) ? $request['status'] : NULL,
            "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "extra_info" => json_encode($this->request), #actual request
            "start_at" => isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp']/1000) : NULL, 
            "end_at" => isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp']/1000) : NULL, 
            "round_id" => isset($request['round_id']) ? $request['round_id'] : NULL,
            "transaction_id" => isset($request['bet_id']) ? $request['bet_id'] : NULL, #mark as bet id
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            #for transaction
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,
        );

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
    }

    public function player_info($request){
        $token = isset($request['token']) ? $request['token'] : null;
        if(empty($token)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
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
            $response = array(
                "is_success" => true,
                "username" => $playerDetails['game_username'],
                "balance" => $this->api->dBtoGameAmount($balance),
                "currency" => $this->api->currency
            );
            return $this->setResponse(self::SUCCESS, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        return $this->setResponse(self::SUCCESS, $response);
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

    private function setResponse($returnCode, $response = []) {
        return $this->setOutput($returnCode, $response);
    }


    private function setOutput($returnCode, $response = []) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;
        $http_status_code = 0;
        $flag = $returnCode['is_success'] == self::SUCCESS['is_success'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($flag == Response_result::FLAG_ERROR){
            $response = $returnCode;
        }
        if($this->response_result_id) {
            $disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
            if($disabled_response_results_table_only){
                $respRlt = $this->response_result->readNewResponseById($this->response_result_id);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                if(isset($returnCode['status_code'])  && $returnCode['status_code'] == self::STATUS_CODE_INVALID_IP){
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
                if(isset($returnCode['status_code'])  && $returnCode['status_code'] == self::STATUS_CODE_INVALID_IP){
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
        if(isset($response['status_code'])){
            unset($response['status_code']);
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

