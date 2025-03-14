<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Amb_pgsoft_service_api extends BaseController {

    const SUCCESS = 0;
    const INSUFFICIENT_FUND=10002;
    const IP_NOT_ALLOWED=40003;
    const BAD_REQUEST = 400;
    const PLAYER_NOT_FOUND = 10001;
    const TRANSACTION_NOT_FOUND = 20001;
    const INVALID_TOKEN = 30001;
    const INTERNAL_ERROR =50001;
    const WRONG_GAME_API = 8;

    const RETURN_OK = [
        'error' => 0,
        'description' => 'Success'
    ];

    const ERROR_NOT_FOUND_PLAYER = [
        'error' => 10001,
        'description' => 'User not found'
    ];

    const ERROR_INSUFFICIENT_FUND= [
        'error' => 10002,
        'description' => 'User has insufficient balance to proceed with'
    ];

    const ERROR_TRANSACTION_NOT_FOUND = [
        'error' => 20001,
        'description' => 'Transaction not found'
    ];

    const ERROR_INVALID_TOKEN = [
        'error' => 30001,
        'description' => 'Invalid token'
    ];

    const ERROR_BAD_REQUEST = [
        'error' => 40003,
        'description' => 'Forbidden request'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'error' => 40003,
        'description' => 'IP Not allowed'
    ];

    const ERROR_SERVICE_NOT_AVAILABLE = [
        'error' => 40003,
        'description' => 'Service Not Available'
    ];

    const ERROR_INTERNAL_ERROR= [
        'error' => 50001,
        'description' => 'Internal server error'
    ];

    const BLACKLIST_METHODS = [
        'checkBalance',
        'settleBets',
    ];

    const HTTP_STATUS_CODE_MAP = [
        self::SUCCESS=>200,
        self::INSUFFICIENT_FUND=>200,
        self::IP_NOT_ALLOWED=>200,
        self::BAD_REQUEST=>200,
        self::PLAYER_NOT_FOUND=>200,
        self::TRANSACTION_NOT_FOUND=>200,
        self::INVALID_TOKEN=>200,
        self::INTERNAL_ERROR=>200
    ];

    const STATUS_SETTLED = "SETTLED";

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    private $requestHeaders;
    private $requestParams;
    private $resultCode;
    private $api;
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $headers;

    public function __construct() {
        parent::__construct();
        $this->headers = getallheaders();
    }

    public function index($api, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        $ip = $this->input->ip_address();
        if($ip=='0.0.0.0'){
            $ip=$this->input->getRemoteAddr();
        }
        $this->utils->debug_log('AMBPGSOFT request  >>>>>>> ip' , $ip);
        $this->requestIp = $ip;

        if(!$this->api) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->CI->load->model('common_token', 'common_seamless_wallet_transactions', 'wallet_transactions');

        if(!method_exists($this, $method)) {
            $this->requestHeaders = $this->input->request_headers();
            $this->requestParams->function = $method;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('AMB_PGSOFT ' . __METHOD__ , $method . ' method not allowed');
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->requestParams = new stdClass();
        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        return $this->$method();
    }

    public function checkBalance() {

        $this->CI->load->model('common_token');

        $rule_set = [
            'id' => 'required',
            'timestampMillis' => 'required',
            'productId' => 'required',
            'currency' => 'required',
            'username' => 'required',
            'sessionToken' => 'required'
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_IP_NOT_ALLOWED['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId'],
                "ip" => $this->requestIp
            ];

            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED, $data);
        }

        if($this->external_system->isGameApiMaintenance($this->api->getPlatformCode())){
            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_SERVICE_NOT_AVAILABLE['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId'],
            ];

            return $this->setResponse(self::ERROR_SERVICE_NOT_AVAILABLE, $data);
        }

        $player_info = $this->currentPlayer;

        if(!empty($player_info)) {
            $data = [
                'id' => $this->requestParams->params['id'],
                "statusCode" => 0,
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId'],
                'currency' => $this->requestParams->params['currency'],
                'balance' => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                'username' => $this->api->getGameUsernameByPlayerUsername($player_info['username']),
                // 'sessionToken' => $this->common_token->getPlayerToken($this->currentPlayer['playerId']),
            ];

            return $this->setResponse(self::RETURN_OK, $data);
        }else{
            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_NOT_FOUND_PLAYER['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId'],
            ];

            return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER, $data);
        }

    }

    public function settleBets() {
        $this->CI->load->model('common_seamless_wallet_transactions', 'wallet_transactions');

        $rule_set = [
            'id' => 'required',
            'timestampMillis' => 'required',
            'productId' => 'required',
            'currency' => 'required',
            'username' => 'required',
            'txns' => 'required'
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            $data = array(
                'id' =>  $this->requestParams->params['id'],
                'statusCode' => self::ERROR_IP_NOT_ALLOWED['error'],
                'timestampMillis' => $this->requestParams->params['timestampMillis'],
                'productId' => $this->requestParams->params['productId'],
                "ip" => $this->requestIp
            );

            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED, $data);
        }

        if($this->external_system->isGameApiMaintenance($this->api->getPlatformCode())){
            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_SERVICE_NOT_AVAILABLE['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId'],
            ];

            return $this->setResponse(self::ERROR_SERVICE_NOT_AVAILABLE, $data);
        }

        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['username']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }

        $player_info = $this->currentPlayer;

        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];

            $txns = $controller->requestParams->params['txns'];

            $transaction_data['balanceBefore'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $data = array();

            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$txns) {

                foreach($txns as $txns_key => $txns_value){

                    if(!is_numeric($txns_value['betAmount']) || !is_numeric($txns_value['payoutAmount']) || $txns_value['betAmount'] < 0 || $txns_value['payoutAmount'] < 0){
                        $this->utils->debug_log('AMBPGSOFT ' . __METHOD__ , 'betAmount or payoutAmount is not numeric');

                        $controller->resultCode = self::ERROR_BAD_REQUEST;
                        return false;
                    }else{
                        $transactionId = "SETTLED-".$txns_value['txnId'];

                        $exist_transaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $transactionId);

                        if(!empty($exist_transaction)){
                            //if existing return success
                            $this->utils->debug_log('AMBPGSOFT ' . __METHOD__ , 'existing transaction', $txns_value['txnId']);
                            return true;
                        }else{

                            //Check balance if has sufficient balance
                            $current_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                            if($current_balance < $txns_value['betAmount']) {
                                $this->utils->debug_log('AMBPGSOFTTAL ', 'Player has insufficient balance', 'current_balance', $current_balance, 'bet_amount', $txns_value['betAmount']);
                                $controller->resultCode = self::ERROR_INSUFFICIENT_FUND;
                                return false;
                            }else{

                                $transaction_data['betId'] = $txns_value['id'];
                                $transaction_data['status'] = self::STATUS_SETTLED;
                                $transaction_data['roundId'] = $txns_value['roundId'];
                                $transaction_data['gameCode'] = $txns_value['gameCode'];
                                $transaction_data['playInfo'] = $txns_value['playInfo'];
                                $transaction_data['betAmount'] = $txns_value['betAmount'];
                                $transaction_data['payoutAmount'] = $txns_value['payoutAmount'];
                                $transaction_data['txnId'] = $txns_value['txnId'];
                                $transaction_data['external_unique_id'] = self::STATUS_SETTLED."-".$txns_value['txnId'];

                                 if($transaction_data['betAmount'] < 0) {
                                    $controller->resultCode = self::ERROR_BAD_REQUEST;
                                    return false;
                                }

                                $transaction_data['code'] = self::RETURN_OK;

                                $adjustWallet = $controller->adjustWallet(self::TRANSACTION_CREDIT, $player_info, $transaction_data);
                                $this->utils->debug_log('AMBPGSOFTTAL ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);

                            }
                        }

                    }

                }

                return true;

            });

            $transaction_data['balanceAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];


            if($transaction_result) {
                $this->utils->debug_log('AMBPGSOFT ', 'TRANSACTION_TAL: ', $transaction_data);

                $data =  array (
                    'id' => $this->requestParams->params['id'],
                    'statusCode' => self::RETURN_OK['error'],
                    'timestampMillis' => $this->requestParams->params['timestampMillis'],
                    'productId' => $this->requestParams->params['productId'],
                    'currency' => $this->requestParams->params['currency'],
                    'balanceBefore' => $transaction_data['balanceBefore'],
                    'balanceAfter' => $transaction_data['balanceAfter'],
                    'username' => $this->requestParams->params['username']
                );

                return $this->setResponse(self::RETURN_OK, $data);

            }else {
                $this->utils->debug_log('AMBPGSOFT ', 'TRANSACTION_ERROR_TAL: ', $transaction_data);

                $statusCode = "";

                switch ($controller->resultCode['error']) {
                    case self::ERROR_INSUFFICIENT_FUND['error']:
                        $statusCode = self::ERROR_INSUFFICIENT_FUND['error'];
                      break;
                    case self::ERROR_BAD_REQUEST['error']:
                        $statusCode = self::ERROR_BAD_REQUEST['error'];
                      break;
                    default:
                        $statusCode = self::ERROR_INTERNAL_ERROR['error'];
                      break;
                  }

                  $data = array(
                    'id' =>  $this->requestParams->params['id'],
                    'statusCode' => $statusCode,
                    'timestampMillis' => $this->requestParams->params['timestampMillis'],
                    'productId' => $this->requestParams->params['productId'],
                );

                $this->utils->debug_log('AMBPGSOFT ', 'TRANSACTION_ERROR_TAL: ', $controller->resultCode);

                if(!empty($controller->resultCode)){
                    return $this->setResponse($controller->resultCode, $data);
                }else{
                    return $this->setResponse(self::ERROR_INTERNAL_ERROR, $data);
                }

            }

        }else{
            $data = array(
                    'id' =>  $this->requestParams->params['id'],
                    'statusCode' => self::ERROR_NOT_FOUND_PLAYER['error'],
                    'timestampMillis' => $this->requestParams->params['timestampMillis'],
                    'productId' => $this->requestParams->params['productId'],
                );

            return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER, $data);
        }
    }

    private function adjustWallet($transaction_type, $player_info, $extra = []) {

        $return_data = [
            'code' => self::RETURN_OK
        ];

        $wallet_transaction = [];
        $return_data['before_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        //Settling bet
        $betAmount = $extra['betAmount'];
        $payoutAmount = $extra['payoutAmount'];

        $result =   $payoutAmount - $betAmount;


        if($result > 0) {
            $response = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $result);
            if(!$response) {
                $return_data['code'] = self::ERROR_BAD_REQUEST;
            }
        }else if($result < 0 && $payoutAmount!=0){
            $response = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $betAmount);
            $response = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $payoutAmount);

            if(!$response) {
                $return_data['code'] = self::ERROR_BAD_REQUEST;
            }
        }else if($result < 0 && $payoutAmount==0){
            $response = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $betAmount);
            if(!$response) {
                $return_data['code'] = self::ERROR_BAD_REQUEST;
            }
        }

        $wallet_transaction['amount'] = $result;
        $wallet_transaction['bet_amount'] = $betAmount; //$extra['betAmount'];
        $wallet_transaction['result_amount'] = $result; //$amount;

        if($result <= 0){
            $transaction_type = self::TRANSACTION_DEBIT;
        }else{
            $transaction_type = self::TRANSACTION_CREDIT;
        }

        $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $request_timestamp = date("Y-m-d H:i:s", ($this->requestParams->params['timestampMillis'] / 1000));

        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];
        $wallet_transaction['player_id'] = $player_info['playerId'];
        $wallet_transaction['game_id'] = $extra['gameCode'];
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['status'] = $extra['status'];
        $wallet_transaction['external_unique_id'] = $extra['external_unique_id'];
        $wallet_transaction['transaction_id'] = $extra['txnId'];
        $wallet_transaction['round_id'] = $extra['roundId'];
        $wallet_transaction['start_at'] = $request_timestamp;
        $wallet_transaction['end_at'] = $request_timestamp;
        $wallet_transaction['created_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['updated_at'] = date("Y-m-d H:i:s");

        if($return_data['code'] == self::RETURN_OK) {
            $this->wallet_transaction_id = $this->wallet_transactions->insertRow($wallet_transaction);
            $return_data['wallet_transaction_id'] = $this->wallet_transaction_id;
        }

        $return_data['round_id'] = $extra['roundId'];
        $return_data['transaction_id'] = $this->requestParams->params['id'];

        return $return_data;
    }

    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                    $is_valid = false;
                    $this->utils->debug_log('AMB_PGSOFT ' . __METHOD__ , 'missing parameter', $key);
                    break;
                }
                if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
                    $is_valid = false;
                    $this->utils->debug_log('AMB_PGSOFT ' . __METHOD__ , 'not numeric', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    public function preProcessRequest($functionName="", $rule_set = []) {

        $raw_params = file_get_contents("php://input");

        $params = json_decode($raw_params, true);

        $this->requestParams->function = $functionName ;
        $this->requestParams->params = $params;

        $is_valid = $this->validateRequest($rule_set);
        // print_r($rule_set);

        if(!$is_valid) {
            //parameter/s not valid
            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_BAD_REQUEST['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => isset($this->requestParams->params['productId']) ? $this->requestParams->params['productId'] : ""
            ];

            return $this->setResponse(self::ERROR_BAD_REQUEST, $data);
        }

        if($params['productId'] != $this->api->product_id) {
            //product id is not correct
            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_BAD_REQUEST['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId']
            ];

            return $this->setResponse(self::ERROR_BAD_REQUEST, $data);
        }

        $this->CI->load->model('game_provider_auth');

        $this->currentPlayer = (array) $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($this->requestParams->params['username'], $this->api->getPlatformCode());

        if(empty($this->currentPlayer) && in_array($functionName, self::BLACKLIST_METHODS)) {

            $data = [
                'id' => $this->requestParams->params['id'],
                'statusCode' => self::ERROR_NOT_FOUND_PLAYER['error'],
                "timestampMillis"=> $this->requestParams->params['timestampMillis'],
                "productId" => $this->requestParams->params['productId']
            ];

            return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER, $data);
        }else if(!empty($this->currentPlayer)) {
            $this->currentPlayer['playerId'] = $this->currentPlayer['player_id'];
        }

    }

    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);

        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['error'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if(array_key_exists('balance', $data)) {
            $data['balance'] = (float) $data['balance'];
        }

        if(array_key_exists('roundId', $data)) {
            $data['roundId'] = (int) $data['roundId'];
        }

        $httpStatusCode = 200;
        $httpStatusText = "Bad Request";

        if(isset($data['error']) && array_key_exists($data['error'], self::HTTP_STATUS_CODE_MAP)){
            $httpStatusCode = self::HTTP_STATUS_CODE_MAP[$data['error']];
            $httpStatusText = $data['description'];
        }

        unset($data['error']);
        unset($data['description']);

        $data = json_encode($data);

        $fields = array(
            'player_id' => isset($this->currentPlayer['playerId']) ? $this->currentPlayer['playerId'] : 0
        );

        if($this->api) {

            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(), #1
                $flag, #2
                $this->requestParams->function, #3
                json_encode($this->requestParams->params), #4
                $data, #5
                $httpStatusCode, #6
                $httpStatusText, #7
                $this->requestHeaders, #8
                $fields, #9
                false, #10
                null, #11
                intval($this->utils->getExecutionTimeToNow()*1000) #12
            );
        }

        $this->output->set_status_header($httpStatusCode);
        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

}