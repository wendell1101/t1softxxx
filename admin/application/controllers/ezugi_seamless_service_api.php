<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Ezugi_seamless_service_api extends BaseController {

    const RETURN_OK = [
        'errorCode' => 0,
        'errorDescription' => 'ok'
    ];

    const RETURN_OK_TRANSACTION_PROCESSED = [
        'errorCode' => 0,
        'errorDescription' => 'Transaction already processed'
    ];

    const RETURN_OK_TRANSACTION_ROLLBACKED = [
        'errorCode' => 0,
        'errorDescription' => 'Transaction already rollbacked'
    ];

    const ERROR_INSUFFICIENT_BALANCE = [
        'errorCode' => 3,
        'errorDescription' => 'Insufficient funds'
    ];

    const ERROR_BAD_REQUEST = [
        'errorCode' => 1,
        'errorDescription' => 'Bad request'
    ];

    const ERROR_INTERNAL_ERROR = [
        'errorCode' => 1,
        'errorDescription' => 'Internal Error'
    ];

    const ERROR_INVALID_AMOUNT = [
        'errorCode' => 1,
        'errorDescription' => 'Invalid Amount'
    ];

    const ERROR_TOKEN_EXPIRED = [
        'errorCode' => 6,
        'errorDescription' => 'Token not found'
    ];

    const ERROR_NOT_FOUND_PLAYER = [
        'errorCode' => 7,
        'errorDescription' => 'User not found'
    ];

    const ERROR_BET_NOT_FOUND = [
        'errorCode' => 9,
        'errorDescription' => 'Transaction not found'
    ];

    const ERROR_BET_NOT_FOUND_FOR_ROLLBACK = [
        'errorCode' => 9,
        'errorDescription' => 'Debit for this transactionId was not found, rollback aborted.'
    ];

    const ERROR_BET_DEBIT_AFTER_ROLLBACK = [
        'errorCode' => 1,
        'errorDescription' => 'Debit after rollback'
    ];

    const ERROR_BET_DEBIT_AFTER_DEBIT_PROCESSED = [
        'errorCode' => 1,
        'errorDescription' => 'Credit after debit is already processed'
    ];

    const ERROR_GAME_API_NOT_FOUND = [
        'errorCode' => 1,
        'errorDescription' => 'Game API not found'
    ];

    const ERROR_INVALID_HASH = [
        'errorCode' => 1,
        'errorDescription' => 'Invalid hash'
    ];

    const ERROR_WRONG_OPERATOR_ID = [
        'errorCode' => 1,
        'errorDescription' => 'Invalid OperatorId'
    ];

    const ERROR_MISSING_DEBIT_TRANSACTION_ID = [
        'errorCode' => 9,
        'errorDescription' => 'Missing debitTransactionID'
    ];

    const RETURN_FAIL_TRANSACTION_PROCESSED = [
        'errorCode' => 1,
        'errorDescription' => 'Transaction already processed'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'errorCode' => 1,
        'errorDescription' => 'IP Address is not allowed.'
    ];

    const ERROR_TRANSACTION_TIMOUT = [
        'errorCode' => 10,
        'errorDescription' => 'Transaction timed out'
    ];

    const ERROR_INVALID_CURRENCY = [
        'errorCode' => 1,
        'errorDescription' => 'Invalid currency'
    ];

    private $requestParams;
    private $currentPlayer;
    private $headers;
    private $http_status_code;
    private $use_ezugi_seamless_wallet_transactions;

    private $time_start;
    private $time_end;
    private $extra_params = [];
    private $is_dev_function = false;
    private $seamless_service_related_unique_id = null;
    private $seamless_service_related_action = null;

    // monthly transaction table
    protected $use_monthly_transactions_table = false;
    protected $force_check_previous_transactions_table = false;

    const METHOD_WHITELIST_FOR_PUBLIC_USE = [
        'auth',
        'debit',
        'credit',
        'rollback',

        //dev function
        'fixMissingCredit',
    ];

    const WHITELIST_METHOD_FOR_EXPIRED_TOKEN = [
        'credit',
        'rollback',
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_STATUS_ROLLBACK = 'rollback';
    const TRANSACTION_STATUS_OK = 'ok';
    const TRANSACTION_STATUS_PROCESSED = 'processed';
    const TRANSACTION_ROLLBACK_NO_DEBIT = 'rollback-no-debit';
    const TRANSACTION_DEBIT_NO_DEBIT = 'debit-no-debit';

    public function index($api, $method) {
        $this->CI->load->model('common_token');
        $this->headers = getallheaders();
        $this->http_status_code = 200;
        $this->requestParams = new stdClass();
        $this->api = $this->utils->loadExternalSystemLibObject($api);

        if(!$this->api) {
            $this->requestParams->function = $method;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('EZUGI ' . __METHOD__ , $api . ' game api not found');
            return $this->setResponse(self::ERROR_GAME_API_NOT_FOUND);
        }

        $this->use_ezugi_seamless_wallet_transactions = $this->api->use_ezugi_seamless_wallet_transactions;

        if(!$this->api->validateWhiteIP()) {
            $this->requestParams->function = $method;
            $this->http_status_code = 401;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('EZUGI ' . __METHOD__ , $api . ' ip address not allowed');
            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED);
        }
        if($this->utils->setNotActiveOrMaintenance($api)) {
            $this->requestParams->function = $method;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('EZUGI ' . __METHOD__ , $api . ' game api not active');
            return $this->setResponse(self::ERROR_GAME_API_NOT_FOUND);
        }

        if($this->use_ezugi_seamless_wallet_transactions){
            $this->CI->load->model('ezugi_seamless_wallet_transactions', 'wallet_transactions');
            $this->wallet_transactions->tableName = $this->api->original_transaction_table;

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->api->force_check_previous_transactions_table;
        }else{
            $this->CI->load->model('common_seamless_wallet_transactions', 'wallet_transactions');
        }
        
        if(!method_exists($this, $method) || !in_array($method, self::METHOD_WHITELIST_FOR_PUBLIC_USE)) {
            $this->requestParams->function = $method;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('EZUGI ' . __METHOD__ , $method . ' method not allowed');
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        return $this->$method();
    }

    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                    $is_valid = false;
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'missing parameter', $key);
                    break;
                }
                if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
                    $is_valid = false;
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'not numeric', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    private function preProcessRequest($functionName, $rule_set = []) {
        $raw_params = file_get_contents("php://input");
        $this->utils->debug_log('EZUGI ' . __METHOD__ , 'raw parameters: ', $raw_params);
        $params = json_decode($raw_params, true) ?: [];
        $this->requestParams->function = $functionName;
        $this->requestParams->params = $params;

        if ($this->is_dev_function) {
            $this->requestParams->params = !empty($this->extra_params) ? $this->extra_params : $params;
        }

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            $this->utils->debug_log('EZUGI ' . __METHOD__ , 'request parameter not valid');
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $headers = $this->input->request_headers();
        $hash = $this->generateHash($raw_params);

        if(!hash_equals($hash, $headers['Hash']) && !$this->is_dev_function) {
            $this->utils->debug_log('EZUGI ' . __METHOD__ , 'hash invalid');
            return $this->setResponse(self::ERROR_INVALID_HASH);
        }

        if(isset($params['operatorId']) && $params['operatorId'] != $this->api->operator_id) {
            $this->utils->debug_log('EZUGI ' . __METHOD__ , 'operator id not valid');
            return $this->setResponse(self::ERROR_WRONG_OPERATOR_ID);
        }

        if(isset($params['currency']) && $params['currency'] != $this->api->currency) {
            $this->utils->debug_log('EZUGI ' . __METHOD__ , 'currency not valid');
            return $this->setResponse(self::ERROR_INVALID_CURRENCY);
        }

        if(isset($this->requestParams->params['token'])) {
            $this->currentPlayer = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->requestParams->params['token'], $this->api->getPlatformCode(), true, 10, 30);
            if(empty($this->currentPlayer) && !in_array($functionName, self::WHITELIST_METHOD_FOR_EXPIRED_TOKEN)) {
                $this->utils->debug_log('EZUGI ' . __METHOD__ , 'expired token');
                return $this->setResponse(self::ERROR_TOKEN_EXPIRED);
            }
            else if(!empty($this->currentPlayer)) {
                if(isset($this->requestParams->params['uid']) && $this->currentPlayer['game_username'] != $this->requestParams->params['uid']) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'valid token but invalid user');
                    return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
                }
                $this->currentPlayer['playerId'] = $this->currentPlayer['player_id'];
            }
            else {
                $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['uid']);
                $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
                if(empty($this->currentPlayer)) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'player not found');
                    return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
                }
                /* $this->currentPlayer['playerId'] = $this->currentPlayer['player_id']; */
                $this->utils->debug_log('EZUGI ' . __METHOD__ , 'player found but expired token', $functionName);
            }
        }
    }

    private function generateHash($params) {
        $key = $this->api->key;
        $hash = base64_encode(hash_hmac('sha256', $params, $key, true));
        return $hash;
    }

    public function getNickName($n) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
     
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
     
        return $randomString;
    }

    private function auth() {
        $this->time_start = microtime(true); 
        $rule_set = [
            'platformId' => 'required',
            'operatorId' => 'required',
            'token' => 'required',
            'timestamp' => 'required',
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $this->utils->debug_log('EZUGI ' . __METHOD__ , $this->currentPlayer); 

        // $game_username = $this->api->getGameUsernameByPlayerUsername($this->currentPlayer['username']);
        // $player_id = $this->api->getPlayerIdByGameUsername($game_username);
        list($token, $sign_key) = $this->common_token->createTokenWithSignKeyBy($this->currentPlayer['player_id'], 'player_id', 60*30); // 30 minutes expiration
        $data = [
            'uid' => $this->currentPlayer['game_username'], //$game_username,
            'nickname' => $this->getNickName(8),
            'token' => $token,
            'balance' => $this->api->queryPlayerBalance($this->currentPlayer['username'])['balance'],
            'currency' => $this->api->currency
        ];

        if($this->api->enable_delete_token){
            $this->common_token->deleteToken($this->requestParams->params['token']);
        }
        $this->utils->debug_log('EZUGI ' . __METHOD__ , 'deleted token', $this->requestParams->params['token']);
        return $this->setResponse(self::RETURN_OK, $data);
    }

    private function debit() {
        $rule_set = [
            'serverId' => 'required',
            'operatorId' => 'required',
            'token' => 'required',
            'uid' => 'required',
            'transactionId' => 'required',
            'roundId' => 'required',
            'gameId' => 'required',
            'tableId' => 'required',
            'currency' => 'required',
            'debitAmount' => 'required|numeric',
            'betTypeID' => 'required',
            'platformId' => 'required',
            'timestamp' => 'required',
        ];

        try {

            $this->preProcessRequest(__FUNCTION__, $rule_set);
            $player_info = $this->currentPlayer;

            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($player_info, &$transaction_data) {

                if($this->use_ezugi_seamless_wallet_transactions){
                    $transaction = $this->wallet_transactions->searchByTransactionIdAndTransactionType($this->requestParams->params['transactionId']);

                    if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        if (empty($transaction)) {
                            $transaction = $this->wallet_transactions->searchByTransactionIdAndTransactionType($this->requestParams->params['transactionId'], null, $this->api->previous_table);
                        }
                    }
                }else{
                    $transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], 'transaction_id');
                }
                
                if($this->findObjectByTransactionType($transaction, self::TRANSACTION_ROLLBACK)) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'existing rollback for debit request', $this->requestParams->params['transactionId']);
                    $remoteActionType = 'rollback';
                    $transaction_data = $this->adjustWallet(self::TRANSACTION_DEBIT_NO_DEBIT, $player_info, [], $remoteActionType);
                    $transaction_data['code'] = self::ERROR_BET_DEBIT_AFTER_ROLLBACK;
                    return true;
                }

                if($old_transaction = $this->findObjectByTransactionType($transaction, self::TRANSACTION_DEBIT)) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'existing transaction', $this->requestParams->params['transactionId'], $old_transaction);
                    $transaction_data['code'] = self::RETURN_OK_TRANSACTION_PROCESSED;
                    $transaction_data['transaction_id'] = $old_transaction->external_unique_id;
                    $transaction_data['round_id'] = $old_transaction->round_id;
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }
                if($this->requestParams->params['debitAmount'] < 0) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'amount is less then 0');
                    $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                    return false;
                }
                $remoteActionType = 'bet';
                $transaction_data = $this->adjustWallet(self::TRANSACTION_DEBIT, $player_info, [], $remoteActionType);

                $this->utils->debug_log('EZUGI ' . __METHOD__ , 'response', $transaction_data['code']);
                if(!$this->isSuccess($transaction_data['code'])) {
                    return false;
                }
                return true;
            });

        } catch (Exception $error) {
            $this->utils->error_log('EZUGI ' . __METHOD__ , ' ERROR ', $error->getMessage());
            $transaction_data['code'] = self::ERROR_INTERNAL_ERROR;
        }       

        if(!$transaction_result&&$transaction_data['code']==self::RETURN_OK){            
            $transaction_data['code'] = self::ERROR_INTERNAL_ERROR;
        }

        $this->utils->debug_log('EZUGI ' . __METHOD__ , $transaction_result, $transaction_data);
        $this->preProcessResponse($transaction_data);

        $tokenResponse = isset($this->requestParams->params['token'])?$this->requestParams->params['token']:$this->common_token->getPlayerToken($this->currentPlayer['playerId']);
        //$this->common_token->refreshToken($tokenResponse, $this->currentPlayer['playerId']);
        $data = [
            'roundId' => $transaction_data['round_id'],
            'uid' => $this->api->getGameUsernameByPlayerUsername($this->currentPlayer['username']),
            'balance' => $transaction_data['after_balance'],
            'transactionId' => $transaction_data['transaction_id'],
            'currency' => $this->api->currency,
            'token' => $tokenResponse,
        ];
        return $this->setResponse($transaction_data['code'], $data);
    }

    private function isSuccess($code) {
        switch ($code) {
            case self::RETURN_OK_TRANSACTION_PROCESSED;
            case self::RETURN_OK:
                return true;
            default:
                return false;
        }
    }

    private function credit() {
        $rule_set = [
            'serverId' => 'required',
            'operatorId' => 'required',
            'token' => 'required',
            'uid' => 'required',
            'transactionId' => 'required',
            'roundId' => 'required',
            'gameId' => 'required',
            'tableId' => 'required',
            'currency' => 'required',
            'creditAmount' => 'required|numeric',
            'betTypeID' => 'required',
            'platformId' => 'required',
            'timestamp' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $player_info = $this->currentPlayer;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        try {
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($player_info, &$transaction_data) {
                if(!array_key_exists('debitTransactionId', $this->requestParams->params)) {
                    $transaction_data['code'] = self::ERROR_MISSING_DEBIT_TRANSACTION_ID;
                    return false;
                }

                #check if debit exist
                if($this->use_ezugi_seamless_wallet_transactions){
                    $debitTransaction = $this->wallet_transactions->getTransactionObjectByField($this->requestParams->params['debitTransactionId'], self::TRANSACTION_DEBIT);

                    if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        if (empty($debitTransaction)) {
                            $debitTransaction = $this->wallet_transactions->getTransactionObjectByField($this->requestParams->params['debitTransactionId'], self::TRANSACTION_DEBIT, $this->api->previous_table);
                        }
                    }
                }else{
                    $debitTransaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->requestParams->params['debitTransactionId'], 'transaction_id', self::TRANSACTION_DEBIT);
                }
         
                if(!empty($debitTransaction)) {
                    $this->seamless_service_related_unique_id = isset($debitTransaction->external_unique_id) ? $this->utils->mergeArrayValues(['game', $this->api->getPlatformCode(), $debitTransaction->external_unique_id]) : null;
                    $this->seamless_service_related_action = isset($debitTransaction->transaction_type) ? $this->relatedActionMapping($debitTransaction->transaction_type) : null;

                    #debit exist
                    #already rollback
                    if($debitTransaction->status == self::TRANSACTION_STATUS_ROLLBACK){
                        $transaction_data['code'] = self::RETURN_OK_TRANSACTION_ROLLBACKED;
                        $transaction_data['transaction_id'] = $debitTransaction->external_unique_id;
                        $transaction_data['round_id'] = $debitTransaction->round_id;
                        $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        return true;
                    }

                    #already processed
                    if($debitTransaction->status == self::TRANSACTION_STATUS_PROCESSED) {
                        if($this->use_ezugi_seamless_wallet_transactions){
                            $creditTransaction = $this->wallet_transactions->searchByTransactionIdAndTransactionType($this->requestParams->params['transactionId'], self::TRANSACTION_CREDIT);

                            if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                if (empty($creditTransaction)) {
                                    $creditTransaction = $this->wallet_transactions->searchByTransactionIdAndTransactionType($this->requestParams->params['transactionId'], self::TRANSACTION_CREDIT, $this->api->previous_table);
                                }
                            }
                        }else{
                            $creditTransaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], 'transaction_id', self::TRANSACTION_CREDIT);
                        }
                        // $existingCreditTransaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->requestParams->params['debitTransactionId'], 'transaction_id', self::TRANSACTION_CREDIT);                
                        #no credit
                        if(empty($creditTransaction)) {
                            $transaction_data['code'] = self::RETURN_FAIL_TRANSACTION_PROCESSED;
                            $transaction_data['transaction_id'] = $debitTransaction->external_unique_id;
                            $transaction_data['round_id'] = $debitTransaction->round_id;
                            $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                            return false;
                        }
                        else {
                        #with credit
                            $transaction_data['code'] = self::RETURN_OK_TRANSACTION_PROCESSED;
                            //$transaction_data['transaction_id'] = $debitTransaction->external_unique_id;
                            $transaction_data['transaction_id'] = $this->requestParams->params['transactionId'];
                            $transaction_data['round_id'] = $debitTransaction->round_id;
                            $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                            return true;
                        }
                    }
                }
                else {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'bet not found!!');
                    $transaction_data['code'] = self::ERROR_BET_NOT_FOUND;
                    return false;
                }
                if($this->requestParams->params['creditAmount'] < 0) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'amount is less then 0');
                    $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                    return false;
                }
                
                $remoteActionType = 'payout';
                $transaction_data = $this->adjustWallet(self::TRANSACTION_CREDIT, $player_info, [], $remoteActionType);
                $updated = $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $this->requestParams->params['debitTransactionId'], ['status' => self::TRANSACTION_STATUS_PROCESSED]);

                if ($this->use_ezugi_seamless_wallet_transactions) {
                    if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        if (!$updated) {
                            $updated = $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $this->requestParams->params['debitTransactionId'], ['status' => self::TRANSACTION_STATUS_PROCESSED], $this->api->previous_table);
                        }
                    }
                }

                $this->utils->debug_log('EZUGI ' . __METHOD__ , 'response', $transaction_data['code']);
                if(!$this->isSuccess($transaction_data['code'])) {
                    return false;
                }
                return true;
            });

        } catch (Exception $error) {
            $this->utils->error_log('EZUGI ' . __METHOD__ , ' ERROR ', $error->getMessage());
            $transaction_data['code'] = self::ERROR_INTERNAL_ERROR;
        }       

        if(!$transaction_result&&$transaction_data['code']==self::RETURN_OK){            
            $transaction_data['code'] = self::ERROR_INTERNAL_ERROR;
        }

        $this->utils->debug_log('EZUGI ' . __METHOD__ , $transaction_result, $transaction_data);
        $this->preProcessResponse($transaction_data);
        $tokenResponse = isset($this->requestParams->params['token'])?$this->requestParams->params['token']:$this->common_token->getPlayerToken($this->currentPlayer['playerId']);
        //$this->common_token->refreshToken($tokenResponse, $this->currentPlayer['playerId']);
        $data = [
            'roundId' => $transaction_data['round_id'],
            'uid' => $this->api->getGameUsernameByPlayerUsername($this->currentPlayer['username']),
            'balance' => $transaction_data['after_balance'],
            'transactionId' => isset($this->requestParams->params['transactionId'])?$this->requestParams->params['transactionId']:$transaction_data['transaction_id'],
            'currency' => $this->api->currency,
            'token' => $tokenResponse,
        ];
        return $this->setResponse($transaction_data['code'], $data);
    }

    private function rollback() {
        $rule_set = [
            'serverId' => 'required',
            'operatorId' => 'required',
            'token' => 'required',
            'uid' => 'required',
            'transactionId' => 'required',
            'roundId' => 'required',
            'gameId' => 'required',
            'tableId' => 'required',
            'currency' => 'required',
            'rollbackAmount' => 'required|numeric',
            'betTypeID' => 'required',
            'platformId' => 'required',
            'timestamp' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $player_info = $this->currentPlayer;

        $transaction_data = [
            'code' => self::RETURN_OK,
        ];

        try {
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($player_info, &$transaction_data) {

                if($this->use_ezugi_seamless_wallet_transactions){
                    $transaction = $this->wallet_transactions->searchByTransactionIdAndTransactionType($this->requestParams->params['transactionId']);

                    if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        if (empty($transaction)) {
                            $transaction = $this->wallet_transactions->searchByTransactionIdAndTransactionType($this->requestParams->params['transactionId'], null, $this->api->previous_table);
                        }
                    }
                }else{
                    $transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], 'transaction_id');
                }
                
                $debitData = $this->findObjectByTransactionType($transaction, self::TRANSACTION_DEBIT);
                $rollbackData = $this->findObjectByTransactionType($transaction, self::TRANSACTION_ROLLBACK);

                if(empty($debitData)){
                    if(empty($rollbackData)){
                        //no debit and no rollback data
                        $this->utils->debug_log('EZUGI ' . __METHOD__ , 'bet not found!!');
                        $remoteActionType = 'rollback';
                        $transaction_data = $this->adjustWallet(self::TRANSACTION_ROLLBACK_NO_DEBIT, $player_info, [], $remoteActionType);
                        $transaction_data['code'] = self::ERROR_BET_NOT_FOUND_FOR_ROLLBACK;//to check
                        $transaction_data['transaction_id'] = $this->requestParams->params['transactionId'];
                        $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];                        
                        return true;
                    }else{
                        //no debit and with rollback data                    

                        $transaction_data['code'] = self::ERROR_BET_NOT_FOUND_FOR_ROLLBACK;//to check
                        $transaction_data['transaction_id'] = $rollbackData->external_unique_id;
                        $transaction_data['round_id'] = $rollbackData->round_id;
                        $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];                        
                        return true;
                    }

                }else{
                    $this->seamless_service_related_unique_id = isset($debitData->external_unique_id) ? $this->utils->mergeArrayValues(['game', $this->api->getPlatformCode(), $debitData->external_unique_id]) : null;
                    $this->seamless_service_related_action = isset($debitData->transaction_type) ? $this->relatedActionMapping($debitData->transaction_type) : null;

                    //with debit
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'existing transaction', $this->requestParams->params['transactionId'], $debitData);
                    
                    
                    #validate if debit transaction already processed 
                    if(!empty($transaction)){
                        foreach ($transaction as $trans){
                            if($trans->status == self::TRANSACTION_STATUS_PROCESSED){
                                $transaction_data['code'] = self::RETURN_FAIL_TRANSACTION_PROCESSED;
                                $transaction_data['transaction_id'] = $this->requestParams->params['transactionId'];
                                $transaction_data['round_id'] = $trans->round_id;
                                $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                                return true;
                            }
                        }
                    }

                    if(empty($rollbackData)){
                        //no rollback yet
                        if($this->requestParams->params['rollbackAmount'] == abs($debitData->amount)) {
                            $this->utils->debug_log('EZUGI ' . __METHOD__ , 'cancelling transaction id', $this->requestParams->params['transactionId']);
                            $updated = $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], ['status' => self::TRANSACTION_STATUS_ROLLBACK]);

                            if ($this->use_ezugi_seamless_wallet_transactions) {
                                if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                    if (!$updated) {
                                        $updated = $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], ['status' => self::TRANSACTION_STATUS_ROLLBACK], $this->api->previous_table);
                                    }
                                }
                            }
                            //this point will proceed with balance adustment
                        }else {
                            $this->utils->debug_log('EZUGI ' . __METHOD__ , 'rollback amount is not same as debit amount');
                            $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                            return false;
                        }

                    }else{
                        //with rollback
                        $transaction_data['code'] = self::RETURN_OK_TRANSACTION_PROCESSED;
                        $transaction_data['transaction_id'] = $this->requestParams->params['transactionId'];
                        $transaction_data['round_id'] = $rollbackData->round_id;
                        $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        return true;
                    }                
                }

                if($this->requestParams->params['rollbackAmount'] <= 0) {
                    $this->utils->debug_log('EZUGI ' . __METHOD__ , 'amount is less then or equal to 0');
                    $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return false;
                }
                $remoteActionType = 'rollback';
                $transaction_data = $this->adjustWallet(self::TRANSACTION_ROLLBACK, $player_info, [], $remoteActionType);

                $this->utils->debug_log('EZUGI ' . __METHOD__ , 'response', $transaction_data['code']);
                if(!$this->isSuccess($transaction_data['code'])) {
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return false;
                }
                return true;
            });

        } catch (Exception $error) {
            $this->utils->error_log('EZUGI ' . __METHOD__ , ' ERROR ', $error->getMessage());
            $transaction_data['code'] = self::ERROR_INTERNAL_ERROR;
        }       

        if(!$transaction_result&&$transaction_data['code']==self::RETURN_OK){            
            $transaction_data['code'] = self::ERROR_INTERNAL_ERROR;
        }
        
        $this->preProcessResponse($transaction_data);        
        $this->utils->error_log('EZUGI ' . __METHOD__ , $transaction_result, $transaction_data);
        $tokenResponse = isset($this->requestParams->params['token'])?$this->requestParams->params['token']:$this->common_token->getPlayerToken($this->currentPlayer['playerId']);
        //$this->common_token->refreshToken($tokenResponse, $this->currentPlayer['playerId']);
        $data = [
            'roundId' => $transaction_data['round_id'] ,
            'uid' => $this->api->getGameUsernameByPlayerUsername($this->currentPlayer['username']),
            'balance' => $transaction_data['after_balance'],
            'transactionId' => $transaction_data['transaction_id'],
            'currency' => $this->api->currency,
            'token' => $tokenResponse,
        ];        
        return $this->setResponse($transaction_data['code'], $data);
    }

    private function preProcessResponse(&$data) {
        if(!array_key_exists('uid', $data)) {
            $data['uid'] = $this->requestParams->params['uid'];
        }
        if(!array_key_exists('round_id', $data)) {
            $data['round_id'] = $this->requestParams->params['roundId'];
        }
        if(!array_key_exists('token', $data)) {
            $data['token'] = $this->requestParams->params['token'];
        }
        if(!array_key_exists('uid', $data)) {
            $data['uid'] = $this->requestParams->params['uid'];
        }
        if(!array_key_exists('after_balance', $data)) {
            if(!$this->currentPlayer) {
                $data['after_balance'] = 0;
            }
            else {
                $data['after_balance'] = $this->api->queryPlayerBalance($this->currentPlayer['username'])['balance'];
            }
        }
        if(!array_key_exists('transaction_id', $data)) {
            $data['transaction_id'] = $this->requestParams->params['transactionId'];
        }
        if(!array_key_exists('currency', $data)) {
            $data['currency'] = $this->api->currency;
        }
    }

    private function adjustWallet($transaction_type, $player_info, $extra = [], $remoteActionType = 'bet') {
        $return_data = [
            'code' => self::RETURN_OK            
        ];
        $wallet_transaction = [];
        $return_data['before_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        $return_data['after_balance'] = $return_data['before_balance'];

        $amount = 0;
        $status = self::TRANSACTION_STATUS_OK;

        //to add
        $uniqueid = $this->requestParams->params['transactionId'] . ( $transaction_type == self::TRANSACTION_ROLLBACK ? '-' . self::TRANSACTION_ROLLBACK : '' );
        $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$uniqueid;   
        if (method_exists($this->wallet_model, 'setGameProviderActionType')) {    
            $this->wallet_model->setGameProviderActionType($remoteActionType);
        }
        $external_game_id = isset($this->requestParams->params['tableId']) ?$this->requestParams->params['tableId']:null;
       
        $this->utils->debug_log('EZUGI bermar ' . __METHOD__ , ' params: ', $this->requestParams->params);
        if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id); 
        }

        $round_id = isset($this->requestParams->params['roundId']) ?$this->requestParams->params['roundId']:null;
        
        if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
            $this->wallet_model->setGameProviderRoundId($round_id);
        }

        if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
            if($transaction_type == self::TRANSACTION_CREDIT || $transaction_type == self::TRANSACTION_ROLLBACK){
                $this->wallet_model->setGameProviderIsEndRound(true);
            }
        }

        if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
            $this->wallet_model->setRelatedUniqueidOfSeamlessService($this->seamless_service_related_unique_id);
        }

        if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
            $this->wallet_model->setRelatedActionOfSeamlessService($this->seamless_service_related_action);
        }

        if($transaction_type == self::TRANSACTION_CREDIT) {
            $amount = $this->requestParams->params['creditAmount'];
            if($amount > 0) {
                $response = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
                if(!$response) {
                    $return_data['code'] = self::ERROR_BAD_REQUEST;

                    # treat success if remote wallet return double uniqueid
                    if (!empty($this->utils->getConfig('enabled_remote_wallet_client_on_currency')) && $this->utils->isEnabledRemoteWalletClient()) {
                        $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                        if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                            $return_data['code']=self::RETURN_OK;
                            $wallet_transaction['amount'] = $amount;
                        }
                    }
                }
            }
            
            if($this->utils->compareResultFloat($amount, '=', 0)){
                if(!empty($this->utils->getConfig('enabled_remote_wallet_client_on_currency')) && $this->utils->isEnabledRemoteWalletClient()){
                    $this->utils->debug_log("EZUGI SEAMLESS SERVICE API: (adjustWallet) amount 0 call remote wallet", 'params', $this->requestParams->params);
                    $afterBalance = 0;
                    $succ=$this->wallet_model->incRemoteWallet($player_info['playerId'], $amount, $this->api->getPlatformCode(), $afterBalance);
                } 
            }

            $wallet_transaction['amount'] = $amount;

            $return_data['after_balance']  = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        }
        else if($transaction_type == self::TRANSACTION_DEBIT) {
            $amount = $this->requestParams->params['debitAmount'];
            if($return_data['before_balance'] < $amount) {
                $this->utils->debug_log('EZUGI ' . __METHOD__ , 'insufficient balance');
                $return_data['code'] = self::ERROR_INSUFFICIENT_BALANCE;
                $return_data['after_balance'] = $return_data['before_balance'];
            }
            else {
                if($amount > 0) {
                    $response = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
                    if(!$response) {
                        $return_data['code'] = self::ERROR_INSUFFICIENT_BALANCE;

                        # treat success if remote wallet return double uniqueid
                        if (!empty($this->utils->getConfig('enabled_remote_wallet_client_on_currency')) && $this->utils->isEnabledRemoteWalletClient()) {
                            $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                            if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $return_data['code']=self::RETURN_OK;
                                $wallet_transaction['amount'] = $amount;
                            }
                        }
                    }
                }
                $wallet_transaction['amount'] = $amount * -1;

                $return_data['after_balance']  = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            }            
        }
        else if($transaction_type == self::TRANSACTION_ROLLBACK) {
            $amount = $this->requestParams->params['rollbackAmount'];
            if($amount > 0) {
                $response = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
                if(!$response) {
                    $return_data['code'] = self::ERROR_BAD_REQUEST;
                    # treat success if remote wallet return double uniqueid
                    if (!empty($this->utils->getConfig('enabled_remote_wallet_client_on_currency')) && $this->utils->isEnabledRemoteWalletClient()) {
                        $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                        if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                            $return_data['code']=self::RETURN_OK;
                            $wallet_transaction['amount'] = $amount;
                        }
                    }
                }
            }

            $wallet_transaction['amount'] = $amount;
            $return_data['after_balance']  = $this->api->queryPlayerBalance($player_info['username'])['balance'];   
        }
        else if($transaction_type == self::TRANSACTION_ROLLBACK_NO_DEBIT) {
            $transaction_type = self::TRANSACTION_ROLLBACK;
            $wallet_transaction['amount'] = $this->requestParams->params['rollbackAmount'];
            $return_data['after_balance'] = $return_data['before_balance'];
        }
        else if($transaction_type == self::TRANSACTION_DEBIT_NO_DEBIT) {
            $transaction_type = self::TRANSACTION_DEBIT;
            $status = self::TRANSACTION_ROLLBACK;
            $wallet_transaction['amount'] = $this->requestParams->params['debitAmount'];
            $return_data['after_balance'] = $return_data['before_balance'];
        }

        $request_timestamp = date("Y-m-d H:i:s", ($this->requestParams->params['timestamp'] / 1000));
        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];
        $wallet_transaction['player_id'] = $player_info['playerId'];
        $wallet_transaction['game_id'] = $this->requestParams->params['gameId'];
        $wallet_transaction['status'] = $status;
        $wallet_transaction['external_unique_id'] = $this->requestParams->params['transactionId'] . ( $transaction_type == self::TRANSACTION_ROLLBACK ? '-' . self::TRANSACTION_ROLLBACK : '' );
        $wallet_transaction['extra_info'] = json_encode($this->requestParams->params);
        $wallet_transaction['start_at'] = $request_timestamp;
        $wallet_transaction['end_at'] = $request_timestamp;
        $wallet_transaction['transaction_id'] = $this->requestParams->params['transactionId'];
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['round_id'] = $this->requestParams->params['roundId'];

        if($return_data['code'] == self::RETURN_OK) {
            $this->wallet_transaction_id = $this->wallet_transactions->insertData($this->wallet_transactions->tableName, $wallet_transaction);
            // $this->wallet_transaction = $wallet_transaction;
            // if(!$this->wallet_transaction_id) {
            //     throw new Exception('failed to insert transaction');
            // }
            $return_data['inseertt00'] = $this->wallet_transaction_id;
        }

        $return_data['round_id'] = $this->requestParams->params['roundId'];
        $return_data['transaction_id'] = $this->requestParams->params['transactionId'];        

        return $return_data;

    }

    private function setResponse($returnCode, $data = []) {
        $data['timestamp'] = intval(microtime(true)*1000);
        $data['operatorId'] = !empty($this->api->operator_id) ? $this->api->operator_id : '';
        $data = array_merge($data, $returnCode);

        if($returnCode['errorCode'] != 0 && $this->requestParams->function != 'auth') {
            if(!array_key_exists('uid', $data)) {
                $data['uid'] = !empty($this->requestParams->params['uid']) ? $this->requestParams->params['uid'] : null;
            }
            if(!array_key_exists('roundId', $data)) {
                $data['roundId'] = !empty($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : null;
            }
            if(!array_key_exists('token', $data)) {
                $data['token'] = !empty($this->requestParams->params['token']) ? $this->requestParams->params['token'] : null;
            }
            /* if(!array_key_exists('uid', $data)) {
                $data['uid'] = $this->requestParams->params['uid'];
            } */
            if(!array_key_exists('balance', $data)) {
                $data['balance'] = 0;
            }
            if(!array_key_exists('transactionId', $data)) {
                $data['transactionId'] = !empty($this->requestParams->params['transactionId']) ? $this->requestParams->params['transactionId'] : null;
            }
            if(!array_key_exists('currency', $data)) {
                $data['currency'] = !empty($this->api->currency) ? $this->api->currency : '';
            }
        }
        if($this->requestParams->function == 'credit' && empty($this->currentPlayer)) {

            if(isset($this->requestParams->params['token'])) {
                $this->currentPlayer = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->requestParams->params['token'], $this->api->getPlatformCode(), true, 10, 30);
                if(empty($this->currentPlayer)) {
                    $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['uid']);
                    $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
                    if(!empty($this->currentPlayer)) {
                        $data['balance'] = $this->api->queryPlayerBalance($this->currentPlayer['username'])['balance'];
                    }
                }
            }
        }
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {

        $this->time_end = microtime(true); 

        $flag = $data['errorCode'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if(array_key_exists('balance', $data)) {
            $data['balance'] = number_format((float)$data['balance'], 2, '.', '');
        }

        if(array_key_exists('operatorId', $data)) {
            $data['operatorId'] = (int) $data['operatorId'];
        }

        if(array_key_exists('roundId', $data)) {
            $data['roundId'] = (int) $data['roundId'];
        }

        $data['time_start'] = isset($this->time_start) ? $this->time_start : NULL;
        $data['time_end'] = isset($this->time_end) ? $this->time_end : NULL;
        $data['execution_time'] = isset($this->time_start) ? $this->time_end - $this->time_start : NULL;

        $data = json_encode($data);

        $fields = [];

        if(!empty($this->currentPlayer) && isset($this->currentPlayer['playerId']) && !empty($this->currentPlayer['playerId'])) {
            $fields = ['player_id' => $this->currentPlayer['playerId']];
        }

        if($this->api) {
            $cost = intval($this->utils->getExecutionTimeToNow()*1000);
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->function,
                json_encode($this->requestParams->params),
                $data,
                $this->http_status_code,
                null,
                is_array($this->headers) ? json_encode($this->headers) : $this->headers,
                $fields,
                false,
                null,
                $cost
            );

            // if($this->wallet_transaction_id) {
            //     $this->pp_transactions->updateResponseResultId($this->wallet_transaction_id, $response_result_id);

            //     // if($flag == Response_result::FLAG_NORMAL && $this->utils->getConfig('enable_fast_track_integration')) {
            //     //     $this->sendToFastTrack(); //need to optimize to use queue
            //     // }
            // }
        }

        $this->output->set_status_header($this->http_status_code)->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

    private function findObjectByTransactionType($array, $transaction_type){
        if (!empty($array) && is_array($array)) {
            foreach ($array as $element) {
                if ($element->transaction_type == $transaction_type) {
                    return $element;
                }
            }
        }
        return null;
    }

    public function request_params() {
        $raw_params = file_get_contents("php://input");
        $this->utils->debug_log('EZUGI ' . __METHOD__ , 'raw parameters: ', $raw_params);
        return json_decode($raw_params, true) ?: [];
    }

    public function fixMissingCredit() {
        $this->is_dev_function = true;
        $request = $this->request_params();
        $is_valid = false;
        $message = '';

        if (isset($request['creditTransactionId'])) {
            $creditTransactionId = $request['creditTransactionId'];
            $debitTransactionId = !empty($creditTransactionId) ? 'D' . substr($creditTransactionId, 1) : null;
            $is_valid = true;

            if (isset($request['creditAmount'])) {
                $creditAmount = $request['creditAmount'];
                $is_valid = true;
            } else {
                $creditAmount = 0;
                $message = 'creditAmount is required';
                $is_valid = false;
            }
        } else {
            $creditTransactionId = null;
            $debitTransactionId = null;
            $message = 'creditTransactionId is required';
        }

        $response = [
            'message' => $message
        ];

        if ($is_valid) {
            if ($this->use_ezugi_seamless_wallet_transactions) {
                $debitTransaction = (array) $this->wallet_transactions->getTransactionObjectByField($debitTransactionId, self::TRANSACTION_DEBIT);

                if ($this->use_monthly_transactions_table && $this->api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                    if (empty($debitTransaction)) {
                        $debitTransaction = (array) $this->wallet_transactions->getTransactionObjectByField($debitTransactionId, self::TRANSACTION_DEBIT, $this->api->previous_table);
                    }
                }
            } else {
                $debitTransaction = (array) $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $debitTransactionId, 'transaction_id', self::TRANSACTION_DEBIT);
            }
    
            $debit_info = [];
            if (!empty($debitTransaction) && !empty($debitTransaction['extra_info'])) {
                $debit_info = json_decode($debitTransaction['extra_info'], true);
    
                switch ($this->api->getPlatformCode()) {
                    case EZUGI_EVO_SEAMLESS_API:
                    case EZUGI_REDTIGER_SEAMLESS_API:
                        $this->extra_params = [
                            'uid'=>  !empty($debit_info['uid']) ? $debit_info['uid'] : null,
                            'token'=> !empty($debit_info['token']) ? $debit_info['token'] : null,
                            'gameId'=> !empty($debit_info['gameId']) ? $debit_info['gameId'] : null,
                            'roundId'=> !empty($debit_info['roundId']) ? $debit_info['roundId'] : null,
                            'tableId'=> !empty($debit_info['tableId']) ? $debit_info['tableId'] : null,
                            'currency'=> !empty($debit_info['currency']) ? $debit_info['currency'] : null,
                            'serverId'=> 1,
                            'betTypeID'=> 101,
                            'timestamp'=> !empty($debit_info['timestamp']) ? $debit_info['timestamp'] : null,
                            'isEndRound'=> false,
                            'operatorId'=> !empty($debit_info['operatorId']) ? $debit_info['operatorId'] : null,
                            'platformId'=> 0,
                            'creditIndex'=> '1|1',
                            'creditAmount'=> $creditAmount,
                            'returnReason'=> 0,
                            'transactionId'=> $creditTransactionId,
                            'debitTransactionId'=> !empty($debit_info['transactionId']) ? $debit_info['transactionId'] : null,
                        ];
                        break;
                    default:
                        $this->extra_params = [];
                        break;
                }
    
                return $this->credit();
            } else {
                $response['message'] = 'Debit not found';
            }
        }

        $this->output->set_status_header($this->http_status_code)->set_content_type('application/json')->set_output(json_encode($response));
        $this->output->_display();
        exit();
    }

    protected function relatedActionMapping($transaction_type) {
        switch ($transaction_type) {
            case self::TRANSACTION_DEBIT:
                $action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                break;
            case self::TRANSACTION_CREDIT:
                $action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                break;
            case self::TRANSACTION_ROLLBACK:
                $action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
                break;
            default:
                $action_type = null;
                break;
        }

        return $action_type;
    }
}