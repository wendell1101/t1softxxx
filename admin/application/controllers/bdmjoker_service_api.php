<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/*
Operator Integration APIs
    5.1.Authenticate Token
    5.2.Authenticate Username/Password (Optional)
    5.3.Balance
    5.4.Bet 
    5.5.Settle Bet
    5.6.Cancel Bet
    5.7.Bonus Win
    5.8.Jackpot Win
    5.9.Transaction
    5.10. Withdraw
    5.11. Deposit
*/

class Bdmjoker_service_api extends BaseController {
    use Seamless_service_api_module;

    private $headers;
    private $http_status_code;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];

    const SUCCESS = [
        'Message' => 'Success',
        'Status' => 0
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'Message' => 'IP not allowed',
        'Status' => 1
    ];

    const ERROR_INVALID_APPID = [
        'Message' => 'Invalid AppID',
        'Status' => 2
    ];

    const ERROR_INTERNAL_SERVER_ERROR = [
        'Message' => 'Internal server error',
        'Status' => 1000
    ];

    const ERROR_INVALID_SIGNATURE = [
        'Message' => 'Invalid signature',
        'Status' => 5
    ];

    const ERROR_INVALID_TIMESTAMP = [
        'Message' => 'Invalid timestamp',
        'Status' => 6
    ];

    const ERROR_GAME_UNDER_MAINTENANCE = [
        'Message' => 'Server under maintenance',
        'Status' => 999
    ];

    const ERROR_INVALID_PARAM = [
        'Message' => 'Invalid parameters',
        'Status' => 4
    ];

    const ERROR_INVALID_TOKEN = [
        'Message' => 'Invalid token',
        'Status' => 3
    ];

    const ERROR_TRANSACTION_EXIST = [
        'Message' => 'Transaction already exist',
        'Status' => 1000
    ];

    const ERROR_NOT_ENOUGH_BALANCE = [
        'Message' => 'Insufficient fund Bet, Withdraw',
        'Status' => 100
    ];

    const ERROR_INVALID_USERNAMEORPASSWORD = [
        'Message' => 'Invalid Username or Password',
        'Status' => 7
    ];

    const ERROR_BAD_REQUEST = [
        'Message' => 'Bad request',
        'Status' => 1000
    ];

    const ERROR_ROUND_NOT_EXIST = [
        'Message' => 'Round not exist',
        'Status' => 1000
    ];

    #for failed transaction testing
    const ERROR_TRANSACTION_FORCE_FAILED= [
        'Message' => 'Forced to failed',
        'Status' => 1000
    ];

    const ERROR_REFERENCE_TRANSACTION_NOT_EXIST = [
        'Message' => 'Reference bet id not exist',
        'Status' => 1000
    ];

    const ALLOWED_METHOD_PARAMS = ['authenticate_token','authenticate','balance','bet','settle_bet','cancel_bet','bonus_win','jackpot_win','transaction','withdraw','deposit'];
    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token','common_seamless_wallet_transactions','common_seamless_error_logs','external_system'));
        $this->load->library(array('salt'));
        $this->headers = getallheaders();
        $this->http_status_code = 200;
    }

    public function index($method = null) {
        if(empty($method)){
            return $this->returnJsonResult(self::ERROR_BAD_REQUEST);
        }
        $this->request = file_get_contents('php://input');
        parse_str($this->request, $request);
        $this->request_method = $method;
        $api = BDM_SEAMLESS_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        $this->request_headers = $this->input->request_headers();
        $this->transaction_data = $request;

        if (in_array($method, ['bet', 'settle_bet', 'cancel_bet', 'bonus_win', 'jackpot_win', 'withdraw', 'deposit'])) {
            $this->transaction_data['external_unique_id'] = isset($request['id']) ? $method . '-' . $request['id'] : null;
        }

        $this->utils->debug_log('bdm service request_headers', $this->request_headers);
        $this->utils->debug_log('bdm service method', $method);
        $this->utils->debug_log('bdm service request', $request);
        $this->player_id = null;

        if(!$this->api) {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        $this->use_remote_wallet_failed_transaction_monthly_table = $this->api->use_remote_wallet_failed_transaction_monthly_table;

        if($method == "generate_hash"){
            return $this->generate_hash($request);
        }

        if (!$this->api->validateWhiteIP()) {
            $this->http_status_code = 401;
            $this->response_result_id = $this->setResponseResult();
            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED);
        }

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        if($method == "status"){
            return $this->getRoundStatus($request);
        }

        if(strpos($method, "-") !== false){
            $method = str_replace('-', '_', $method);
        }

        if(!isset($request['appid']) || $this->api->app_id !== $request['appid']){
            return $this->setResponse(self::ERROR_INVALID_APPID);
        }

        if(!isset($request['timestamp']) || !$this->isValidTimeStamp($request['timestamp'])){
            return $this->setResponse(self::ERROR_INVALID_TIMESTAMP);
        }

        $request_hash = null;
        if(isset($request['hash'])){
            $request_hash = $request['hash'];
            unset($request['hash']);
        }
        $hash = $this->api->generateHash($request);
        if(empty($request_hash) || $request_hash !== $hash) {
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

    function generate_hash($request){
        if(isset($request['hash'])){
            unset($request['hash']);
        }

        $hash =  $this->api->generateHash($request);
        $this->returnJsonResult($hash);
    }

    function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    #5.1.Authenticate Token
    public function authenticate_token($request){

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
                "Username" => $playerDetails['game_username'],
                "Balance" => $this->api->dBtoGameAmount($balance),
                "Message" => self::SUCCESS['Message'],
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse(self::SUCCESS, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        return $this->setResponse(self::SUCCESS, $response);
    }

    #5.2.Authenticate Username/Password (Optional)
    public function authenticate($request){
        $username = isset($request['username']) ? $request['username'] : null;
        $password = isset($request['password']) ? $request['password'] : null;
        $appId = isset($request['appid']) ? $request['appid']."." : null;
        if(empty($username) || empty($password)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        if(substr($username, 0, strlen($appId)) !== $appId){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        } else {
             $username = substr($username, strlen($appId));
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        if($this->checkPassword($password, $playerDetails['password'])){
            $playername = $playerDetails['username'];
            $balance = 0;
            $this->player_id = $playerDetails['player_id'];
            $token = $this->api->getPlayerToken($this->player_id);
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
                    "Token" => $token,
                    "Balance" => $this->api->dBtoGameAmount($balance),
                    "Message" => self::SUCCESS['Message'],
                    "Status" => self::SUCCESS['Status']
                );
                return $this->setResponse(self::SUCCESS, $response);
            } else {
                return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
            }
        } else {
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }
    }

    private function checkPassword($password, $playerPassword) {
        return ($this->salt->decrypt($playerPassword, $this->getDeskeyOG()) == $password);
    }

    #5.3.Balance
    public function balance($request){
        $gameUsername = isset($request['username']) ? $request['username'] : null;
        if(empty($gameUsername)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
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
                "Balance" => $this->api->dBtoGameAmount($balance),
                "Message" => self::SUCCESS['Message'],
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse(self::SUCCESS, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }

        return $this->setResponse(self::SUCCESS, $response);
    }

    #5.4.Bet 
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

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            #The Bet have status success if The Bet already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => "The Bet already existed",
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }

        $cancelTransId = isset($request['id']) ? $request['id'] : null;
        $isCancelExist = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $cancelTransId, 'cancel_bet');
        if(!empty($isCancelExist)){
            #if cancel exist override amount and return success
            $request['amount'] = 0;
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'decrease';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
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
                } else {
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance += $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
                    );
                }

            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE; #not enough balance or invalid amount
            }

            return $success;
        });

        $this->transaction_data = $request;

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    #5.5.Settle Bet
    public function settle_bet($request){
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

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            #The settled have status success if The settled already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => "The Bet was settled",
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }

        $roundId = isset($request['roundid']) ? $request['roundid'] : null;
        $bets = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $roundId, 'round_id','bet');
        if(empty($bets)){
            return $this->setResponse(self::ERROR_ROUND_NOT_EXIST);
        }
        
        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'increase';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
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
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance -= $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
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

    #5.6.Cancel Bet
    public function cancel_bet($request){
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

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            // return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
            #The Cancel Bet have status success if The Cancel Bet already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => "The CancelBet already existed",
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }

        #check reference transaction bet
        $betId = isset($request['betid']) ? $request['betid'] : null;
        $betTransaction = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $betId, 'transaction_id','bet');
        if(empty($betTransaction)){
            $betTransaction['amount'] = 0; #allow insert if cancel is first than bet but set amount to 0
            // return $this->setResponse(self::ERROR_REFERENCE_TRANSACTION_NOT_EXIST);
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['amount'] = $betTransaction['amount'];
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'increase';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
            $amount = $request['amount'];
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
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance -= $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
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

    #5.7.Bonus Win
    public function bonus_win($request){
        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            #The bonus_win have status success if The bonus_win already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => self::SUCCESS['Message'],
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }
        
        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'increase';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
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
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance -= $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
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

    #5.8.Jackpot Win
    public function jackpot_win($request){
        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            #The jackpot_win have status success if The jackpot_win already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => self::SUCCESS['Message'],
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }
        
        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'increase';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
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
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance -= $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
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

    #5.9.Transaction (Fish Details No Player Balance Adjustment)
    #amount = bet
    #result = gain/payout
    #winloss = result - amount
    public function transaction($request){
        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode) {
            $amount = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $request['amount'] = $amount; #override amount
            $playerName = $playerDetails['username'];
            $success = false; #default

            $balance = $this->getPlayerBalance($playerName);
            $request['before_balance'] = $balance;
            $request['player_id'] = $playerDetails['player_id'];
            $request['response_result_id'] = $this->response_result_id;
            $request['after_balance'] = $balance;
            $request['transaction_type'] = 'transaction';
            $request['bet_amount'] = $amount;
            $payout = isset($request['result']) ? $this->api->gameAmountToDB($request['result']) : null;
            $request['result_amount'] = $payout - $amount;

            $transId = $this->processRequestData($request);
            if($transId){
                $success = true;
                $errorCode = self::SUCCESS;
                $response = array(
                    "Balance" => $this->api->dBtoGameAmount($balance),
                    "Message" => self::SUCCESS['Message'],
                    "Status" => self::SUCCESS['Status']
                );
            }

            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    #5.10. Withdraw (PLAY GAME FISH)
    public function withdraw($request){
        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            #The withdraw have status success if The withdraw already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => self::SUCCESS['Message'],
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'decrease';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
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
                } else {
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance += $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
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

    ##5.10. Deposit (PLAY GAME FISH)
    public function deposit($request){
        $username = isset($request['username']) ? $request['username'] : null;
        if(empty($username)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($username, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_INVALID_USERNAMEORPASSWORD);
        }

        $transactionId = isset($request['id']) ? __FUNCTION__ . '-' . $request['id'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId);
        if($isTransactionExist){
            #The deposit have status success if The deposit already existed
            $isLocked = false;
            $currentBalance = $this->getPlayerBalance($playerDetails['username'], $isLocked);
            $errorCode = self::SUCCESS;
            $response = array(
                "Balance" => $this->api->dBtoGameAmount($currentBalance),
                "Message" => self::SUCCESS['Message'],
                "Status" => self::SUCCESS['Status']
            );
            return $this->setResponse($errorCode, $response);
        }
        
        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['external_unique_id'] = $transactionId;
        $request['transaction_type'] = __FUNCTION__;
        $request['player_id'] = $playerDetails['player_id'];
        $request['game_username'] = $playerDetails['game_username'];
        $request['adjustment_type'] = 'increase';

        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$request, &$response, &$errorCode) {
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
                    $uniqueid_of_seamless_service = $this->api->getPlatformCode().'-'.$request['external_unique_id'];
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
                    $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                }

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    if ($this->remote_wallet_status == $this->ssa_remote_wallet_code_double_unique_id) {
                        $success = true;
                        $beforeBalance -= $amount;
                    }
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            $afterBalance = !empty($afterBalance) ? $afterBalance : $this->getPlayerBalance($playerName);

            #proceed on success adjustment
            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response = array(
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "Message" => self::SUCCESS['Message'],
                        "Status" => self::SUCCESS['Status']
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

    public function processRequestData($request){

        $dataToInsert = array(
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL,
            "game_id" => isset($request['gamecode']) ? $request['gamecode'] : NULL,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "status" => isset($request['status']) ? $request['status'] : NULL,
            "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL,
            "extra_info" => json_encode($this->request), #actual request
            "start_at" => isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp']/1000) : NULL, 
            "end_at" => isset($request['timestamp']) ? date('Y-m-d H:i:s', $request['timestamp']/1000) : NULL, 
            "round_id" => isset($request['roundid']) ? $request['roundid'] : NULL,
            "transaction_id" => isset($request['id']) ? $request['id'] : NULL, #mark as bet id
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            #for transaction
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,
        );

        #override transaction id, common on for cancel
        if(isset($request['betid'])){
            $dataToInsert['transaction_id'] = $request['betid']; #to mark related cancel for 1 specific transaction
        }

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
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
        $flag = $returnCode['Status'] == self::SUCCESS['Status'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
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
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->response_result_id, null, $this->player_id, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }

        if($flag == Response_result::FLAG_ERROR || !empty($this->remote_wallet_status)){
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
                // $this->common_seamless_error_logs->insertTransaction($errorLogInsertData);

                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $this->transaction_data);
            }
        }

        #unset some field that not need on output but need for internal checking
        if(isset($response['code'])){
            unset($response['code']);
        }
        if(isset($response['message'])){
            unset($response['message']);
        }
        
        return $this->returnJsonResult($response, true, '*', false, false, $this->http_status_code);
    }

    private function setResponseResult(){
        $response_result_id = $this->response_result->saveResponseResult(
            $this->api->getPlatformCode(),
            Response_result::FLAG_NORMAL,
            $this->request_method,
            json_encode($this->request),
            [],#default empty response
            $this->http_status_code,
            null,
            is_array($this->headers) ? json_encode($this->headers) : $this->headers
        );

        return $response_result_id;
    }

    private function getRoundStatus($request){
        $roundId = isset($request['roundid']) ? $request['roundid'] : null;
        $username = isset($request['username']) ? $request['username'] : null;
        $data =  $this->api->queryRoundStatus($roundId, $username);
        $this->returnJsonResult($data);
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => !empty($data['id']) ? $data['id'] : null,
            'round_id' => !empty($data['roundid']) ? $data['roundid'] : null,
            'external_game_id' => !empty($data['gamecode']) ? $data['gamecode'] : null,
            'player_id' => !empty($data['player_id']) ? $data['player_id'] : null,
            'game_username' => !empty($data['game_username']) ? $data['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => !empty($data['transaction_type']) ? $data['transaction_type'] : null,
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_raw_data' => json_encode($this->request),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => !empty($data['timestamp']) ? date('Y-m-d H:i:s', $data['timestamp'] / 1000) : $this->utils->getNowForMysql(),
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

/* . FAQ

1. How "Bet" is associated with "Settle"?
A Settle can be for multiple Bet. Bet and Settle are associated by RoundID.

2. When to use the "Bonus Win" API?
The Bonus can be thought as Settle without Bet. Third party should add balance to
player when receive the event.

3. When to use the "Jackpot Win" API?
The Jackpot can be thought as Settle without Bet. Third party should add balance to
player when receive the event.

4. Can “Cancel” be received after “Settle”?
Yes. There is case that Cancel will be sent after Settle. In this case, the balance
should be added back to player. Third party should adjust transaction.

5. When to use the “Withdraw” API?
This API is used for to player transfer money to play game Fish.

6. When to use the “Deposit” API?
This API is used for to return money back.

7. When to use the “Transaction” API?
This API is used for to send bet detail grouping transaction (Fish) in which the bet
detail is not sent when Bet. The API has no impact on the player balance.

8. When to use the “Transaction is being processed” status code?
When Joker sends the first request to Third party but it processed longer than the
timeout, Joker will re-send again.

a. The first request is being processed -> the second request should be returned
with the status 201

b. The first request is completed -> the second request should be returned with the
correct status (which makes the call idempotent).
*/
