<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class png_service_api extends BaseController {
    use Seamless_service_api_module;

    const RETURN_OK = [
        'statusCode' => 0,
        'statusMessage' => 'ok'
    ];

    const ERROR_INSUFFICIENT_BALANCE = [
        'statusCode' => 7,
        'statusMessage' => 'Insufficient funds'
    ];

    const ERROR_BAD_REQUEST = [
        'statusCode' => 12,
        'statusMessage' => 'Bad request'
    ];

    const ERROR_METHOD_NOT_ALLOWED = [
        'statusCode' => 12,
        'statusMessage' => 'Invalid Method'
    ];

    const ERROR_INTERNAL_ERROR = [
        'statusCode' => 12,
        'statusMessage' => 'Internal Error'
    ];

    const ERROR_INVALID_AMOUNT = [
        'statusCode' => 7,
        'statusMessage' => 'Invalid Amount'
    ];

    const ERROR_TOKEN_EXPIRED = [
        'statusCode' => 1,
        'statusMessage' => 'Token not found'
    ];

    const ERROR_NOT_FOUND_PLAYER = [
        'statusCode' => 4,
        'statusMessage' => 'User not found'
    ];

    const ERROR_BET_NOT_FOUND = [
        'statusCode' => 9,
        'statusMessage' => 'Transaction not found'
    ];

    const ERROR_GAME_API_NOT_FOUND = [
        'statusCode' => 12,
        'statusMessage' => 'Game API not found'
    ];

    const ERROR_INVALID_OPERATOR_ID = [
        'statusCode' => 12,
        'statusMessage' => 'Invalid Operator ID'
    ];

    const ERROR_INVALID_CURRENCY = [
        'statusCode' => 12,
        'statusMessage' => 'Invalid Currency'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'statusCode' => 12,
        'statusMessage' => 'IP Address is not allowed.'
    ];

    const ERROR_ALREADY_PROCESSED_BY_CREDIT = [
        'statusCode' => 12,
        'statusMessage' => 'Already processed by credit.'
    ];

    const ERROR_ALREADY_PROCESSED_BY_ROLLBACK = [
        'statusCode' => 12,
        'statusMessage' => 'Already processed by rollback.'
    ];

    private $requestParams;
    private $currentPlayer;
    private $headers;
    private $http_status_code;
    private $wallet_transaction = null;
    private $brand = null;
    private $game_platform_id;

    const METHOD_WHITELIST_FOR_PUBLIC_USE = [
        'authenticate',
        'balance',
        'reserve',
        'release',
        'cancelReserve',
        'getToken'
    ];

    const WHITELIST_METHOD_FOR_EXPIRED_TOKEN = [
        'credit',
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCELLED = 'cancel';
    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_STATUS_OK = 'ok';

    public function index($api, $method) {
        $this->CI->load->model('common_token');
        $this->headers = getallheaders();
        $this->http_status_code = 200;
        $this->requestParams = new stdClass();
        $this->game_platform_id = $api;


        $this->requestParams->function = $method;

        $raw_params = file_get_contents("php://input");
        $this->utils->debug_log('PNG ' . __METHOD__ , 'raw parameters: ', $raw_params);
        $this->requestParams->params = (array) simplexml_load_string($raw_params, "SimpleXMLElement", LIBXML_NOCDATA);

        $this->utils->debug_log('PNG ' . __METHOD__ , 'parsed parameters: ', $this->requestParams->params);

        # set currency based on the  currency-token
        $dbLoaded = false;
        if(!$dbLoaded && isset($this->requestParams->params['username']) && !empty($this->requestParams->params['username'])){
            $dbLoaded =$this->loadDbFromToken($this->requestParams->params['username']);
        }

        if(!$dbLoaded && isset($this->requestParams->params['currency']) && !empty($this->requestParams->params['currency'])){
            $currency = strtolower($this->requestParams->params['currency']);
            $dbLoaded = $this->loadDbByCurrency($currency);
        }

        if(!$dbLoaded){
            $this->utils->error_log('PNG ' . __METHOD__ , $api . ' cannot load DB');
            //return $this->setResponse(self::ERROR_INVALID_CURRENCY);
        }

        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            $this->utils->error_log('PNG ' . __METHOD__ , $api . ' game api not found');
            return $this->setResponse(self::ERROR_GAME_API_NOT_FOUND);
        }

        $this->brand = $this->api->brand;

        if(!$this->api->validateWhiteIP()) {
            $this->requestParams->function = $method;
            $this->http_status_code = 401;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('PNG ' . __METHOD__ , $api . ' ip address not allowed');
            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED);
        }
        if($this->utils->setNotActiveOrMaintenance($api)) {
            $this->utils->debug_log('PNG ' . __METHOD__ , $api . ' game api not active');
            return $this->setResponse(self::ERROR_GAME_API_NOT_FOUND);
        }
        $this->load->model('common_seamless_wallet_transactions', 'wallet_transactions');
        $this->wallet_transactions->tableName = 'png_seamless_wallet_transactions';
        if(!method_exists($this, $method) || !in_array($method, self::METHOD_WHITELIST_FOR_PUBLIC_USE)) {
            $this->utils->debug_log('PNG ' . __METHOD__ , $method . ' method not allowed');
            return $this->setResponse(self::ERROR_METHOD_NOT_ALLOWED);
        }

        return $this->$method();
    }

    private function loadDbFromToken($token){
        $this->utils->debug_log('PNG ' . __METHOD__ , ' token', $token);
        $currency = null;
        //switch to target db
        if(!is_null($token)){
            $currencyExplode = explode('-', $token);
            if(isset($currencyExplode[0])){
                $currency = strtolower($currencyExplode[0]);
                if(!$this->utils->isAvailableCurrencyKey($currency)){

                    return false;
                }
                
                $this->utils->debug_log('PNG ' . __METHOD__ , ' currency', $currency);
                if(isset($currencyExplode[1]) && !empty($currencyExplode[1])){
                    $token=$currencyExplode[1];
                    $this->requestParams->params['username'] = $token;
                }
                $_multiple_db=Multiple_db::getSingletonInstance();
                $res = $_multiple_db->switchCIDatabase($currency);
                $this->utils->debug_log('PNG ' . __METHOD__ , ' switchCIDatabase result', $res);
                return true;
            }
        }
        return false;
    }

    private function loadDbByCurrency($currency){
        //switch to target db
        if(!empty($currency)){
            if($this->utils->isAvailableCurrencyKey($currency)){
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($currency);
                return true;
            }
        }
        return false;
    }

    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                    $is_valid = false;
                    $this->utils->debug_log('PNG ' . __METHOD__ , 'missing parameter', $key);
                    break;
                }
                if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
                    $is_valid = false;
                    $this->utils->debug_log('PNG ' . __METHOD__ , 'not numeric', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    private function preProcessRequest($rule_set = []) {
        $is_valid = $this->validateRequest($rule_set);
        $params = $this->requestParams->params;

        if(!$is_valid) {
            $this->utils->debug_log('PNG ' . __METHOD__ , 'request parameter not valid');
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if($params['productId'] != $this->api->pid) {
            $this->utils->debug_log('PNG ' . __METHOD__ , 'operator id not valid');
            return $this->setResponse(self::ERROR_INVALID_OPERATOR_ID);
        }

        if(!empty($params['currency']) && $params['currency'] != $this->api->currency) {
            $this->utils->debug_log('PNG ' . __METHOD__ , 'currency not valid');
            return $this->setResponse(self::ERROR_INVALID_CURRENCY);
        }

        if(isset($this->requestParams->params['username'])) {
            $this->currentPlayer = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->requestParams->params['username'], $this->api->getPlatformCode(), true, 10, 30);
        }
        else if(isset($this->requestParams->params['externalId'])) {
            $this->currentPlayer = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($this->requestParams->params['externalId'], $this->api->getPlatformCode(), true, 10, 30);
        }

        if(empty($this->currentPlayer)) {
            $this->utils->debug_log('PNG ' . __METHOD__ , 'expired token');
            if($this->requestParams->function == 'authenticate') {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }
            return $this->setResponse(self::ERROR_TOKEN_EXPIRED);
        }
    }

    private function authenticate() {
        $rule_set = [
            'username' => 'required',
            'productId' => 'required',
            'clientIP' => 'required',
            'contextId' => 'required',
            'accessToken' => 'required',
            'language' => 'required',
            'gameId' => 'required',
            'channel' => 'required',
        ];
        $this->preProcessRequest($rule_set);
        $playerId = $this->currentPlayer['player_id'];
        $this->wallet_model->setExternalGameId($this->requestParams->params['gameId']);
        $data = [
            'externalId' => $this->currentPlayer['game_username'],
            'userCurrency' => $this->api->currency,
            'nickname' => $this->currentPlayer['game_username'],
            'country' => $this->api->country,
            'birthdate' => date('Y-m-d', strtotime('1990-11-13')),
            'registration' => date("Y-m-d", strtotime($this->currentPlayer['created_at'])),
            'language' => $this->api->language,
            'real' => (string) $this->api->queryPlayerBalanceByPlayerId($playerId)['balance']
        ];

        if (!empty($this->brand)) {
            $data['affiliateId'] = $this->brand;
        }

        return $this->setResponse(self::RETURN_OK, $data);
    }

    private function balance() {
        $rule_set = [
            'externalId' => 'required',
            'productId' => 'required',
            'currency' => 'required',
            'gameId' => 'required',
            'accessToken' => 'required',
        ];
        $this->preProcessRequest($rule_set);
        $playerId = $this->currentPlayer['player_id'];
        $data = [
            'real' => (string) $this->api->queryPlayerBalanceByPlayerId($playerId)['balance']
        ];
        return $this->setResponse(self::RETURN_OK, $data);
    }

    private function reserve() {
        $rule_set = [
            'externalId' => 'required',
            'productId' => 'required',
            'transactionId' => 'required',
            'real' => 'required',
            'currency' => 'required',
            'gameId' => 'required',
            'gameSessionId' => 'required',
            'accessToken' => 'required',
            'roundId' => 'required',
            'channel' => 'required',
            'freegameExternalId' => 'required',
            'actualValue' => 'required',
        ];

        $this->preProcessRequest($rule_set);
        $player_info = $this->currentPlayer;

        $transaction_data = [
            'code' => self::ERROR_INTERNAL_ERROR
        ];
        $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($player_info, &$transaction_data) {

            $transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], 'external_unique_id');
            if($old_transaction = $this->findObjectByTransactionType($transaction, self::TRANSACTION_DEBIT)) {
                $this->utils->debug_log('PNG ' . __METHOD__ , 'existing transaction', $this->requestParams->params['transactionId'], $old_transaction);
                $transaction_data['code'] = self::RETURN_OK;
                $transaction_data['after_balance'] = isset($old_transaction->after_balance) ? $old_transaction->after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];
                return true;
            }
            if($this->requestParams->params['real'] < 0) {
                $this->utils->debug_log('PNG ' . __METHOD__ , 'amount is less then 0');
                $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                return false;
            }

            $transaction_data = $this->adjustWallet(self::TRANSACTION_DEBIT, $player_info, [], Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);

            $this->utils->debug_log('PNG ' . __METHOD__ , 'response', $transaction_data['code']);
            if($transaction_data['code'] != self::RETURN_OK) {
                return false;
            }
            return true;
        });

        $this->utils->debug_log('PNG ' . __METHOD__ , $transaction_result, $transaction_data);
        // if($transaction_data['code'] == self::RETURN_OK) {
        //     $data = [
        //         'real' => $transaction_data['after_balance'],
        //     ];

        //     return $this->setResponse($transaction_data['code'], $data);
        // }
        // else {
        //     return $this->setResponse($transaction_data['code']);
        // }

        $data = [
            'real' => isset($transaction_data['after_balance']) ? $transaction_data['after_balance'] : null,
        ];

        return $this->setResponse($transaction_data['code'], $data);
    }

    private function release() {
        $rule_set = [
            'externalId' => 'required',
            'productId' => 'required',
            'transactionId' => 'required',
            'real' => 'required',
            'currency' => 'required',
            'gameSessionId' => 'required',
            'state' => 'required',
            'type' => 'required',
            'gameId' => 'required',
            'accessToken' => 'required',
            'roundId' => 'required',
            'jackpotGain' => 'required',
            'jackpotLoss' => 'required',
            'jackpotGainSeed' => 'required',
            'jackpotGainId' => 'required',
            'channel' => 'required',
        ];

        $this->preProcessRequest($rule_set);
        $player_info = $this->currentPlayer;
        $transaction_data = [
            'code' => self::ERROR_INTERNAL_ERROR
        ];

        if($this->requestParams->params['state'] == 1) { // only summary once game is closed

            $this->utils->debug_log('PNG ' . __METHOD__ , ' summary only', $this->requestParams->params);
            return $this->setResponse(self::RETURN_OK);
        }

        $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($player_info, &$transaction_data) {
            
            $where = [
                'round_id' => strval($this->requestParams->params['roundId']),
                'player_id' => $this->currentPlayer['player_id'],
            ];
            $allRelatedTransactions = $this->wallet_transactions->getAllRelatedTransactionObjects($this->api->getPlatformCode(), $where);
            
            $rollbackTransaction = null;
            $debitTransaction = null;
            $creditTransaction = null;

            foreach($allRelatedTransactions as $relTrans){
                if($relTrans->transaction_type==self::TRANSACTION_ROLLBACK){
                    $rollbackTransaction = $relTrans;
                }
                if($relTrans->transaction_type==self::TRANSACTION_DEBIT){
                    $debitTransaction = $relTrans;
                }
                if($relTrans->transaction_type==self::TRANSACTION_CREDIT){
                    $creditTransaction = $relTrans;

                    //same credit already processed
                    if($this->requestParams->params['transactionId']==$relTrans->external_unique_id){
                        $this->utils->debug_log('PNG ' . __METHOD__ , 'same credit already processed!!', 'credit_already_exists', $relTrans);
                        $transaction_data['code'] = self::RETURN_OK;
                        $transaction_data['after_balance'] = isset($relTrans->after_balance) ? $relTrans->after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        return true;
                    }
                }
            }

            //no debit
            if (empty($debitTransaction)) {
                
                $this->utils->debug_log('PNG ' . __METHOD__ , 'bet not found!! allowed as per provider', $debitTransaction);
                //$transaction_data['code'] = self::ERROR_BET_NOT_FOUND;
                //$transaction_data['code'] = self::RETURN_OK;
                //$transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                //return false;
            }

            //already rollbacked
            if (!empty($rollbackTransaction)) {
                $transaction_data['code'] = self::ERROR_ALREADY_PROCESSED_BY_ROLLBACK;
                return false;
            }

            if($this->requestParams->params['real'] < 0) {
                $this->utils->debug_log('PNG ' . __METHOD__ , 'amount is less then 0');
                $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                return false;
            }

            $is_end = true;
            $transaction_data = $this->adjustWallet(self::TRANSACTION_CREDIT, $player_info, [], Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT, $is_end);
            $this->utils->debug_log('PNG ' . __METHOD__ , 'response', $transaction_data['code']);
            if($transaction_data['code'] != self::RETURN_OK) {
                return false;
            }
            return true;
        });

        $this->utils->debug_log('PNG ' . __METHOD__ , $transaction_result, $transaction_data);
        if($transaction_data['code'] == self::RETURN_OK) {
            $data = [
                'real' => $transaction_data['after_balance'],
            ];

            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            return $this->setResponse($transaction_data['code']);
        }
    }

    private function cancelReserve() {
        $rule_set = [
            'externalId' => 'required',
            'productId' => 'required',
            'transactionId' => 'required',
            'real' => 'required',
            'currency' => 'required',
            'accessToken' => 'required',
            'roundId' => 'required',
            'gameId' => 'required',
            'channel' => 'required',
            'actualValue' => 'required',
        ];

        $this->preProcessRequest($rule_set);
        $player_info = $this->currentPlayer;

        $transaction_data = [
            'code' => self::RETURN_OK,
        ];

        $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($player_info, &$transaction_data) {
            $where = [
                'round_id' => strval($this->requestParams->params['roundId']),
                'player_id' => $this->currentPlayer['player_id'],
                'transaction_type' => self::TRANSACTION_CREDIT,
            ];

            $creditTransaction = $this->wallet_transactions->getTransactionObjects($this->api->getPlatformCode(), $where);

            if (!empty($creditTransaction)) {
                $transaction_data['code'] = self::ERROR_ALREADY_PROCESSED_BY_CREDIT;
                return false;
            }

            $debit_transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], 'external_unique_id', self::TRANSACTION_DEBIT);
            if(empty($debit_transaction)) {
                $this->utils->debug_log('PNG ' . __METHOD__ , 'bet not found!!');
                $transaction_data['code'] = self::RETURN_OK;
                return false;
            }

            $rollback_transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], 'external_unique_id', self::TRANSACTION_ROLLBACK);
            if(!empty($rollback_transaction)) {
                $transaction_data['code'] = self::RETURN_OK;
                return true;
            }

            if($this->requestParams->params['real'] < 0) {
                $this->utils->debug_log('PNG ' . __METHOD__ , 'amount is less then or equal to 0');
                $transaction_data['code'] = self::ERROR_INVALID_AMOUNT;
                return false;
            }

            $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $this->requestParams->params['transactionId'], ['transaction_type' => self::TRANSACTION_CANCELLED]);
            $is_end = true;
            $transaction_data = $this->adjustWallet(self::TRANSACTION_ROLLBACK, $player_info, [], Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND, $is_end);

            $this->utils->debug_log('PNG ' . __METHOD__ , 'response', $transaction_data['code']);
            if($transaction_data['code'] != self::RETURN_OK) {
                return false;
            }
            return true;
        });
        $this->utils->debug_log('PNG ' . __METHOD__ , $transaction_result, $transaction_data);

        return $this->setResponse($transaction_data['code']);
    }

    private function adjustWallet($transaction_type, $player_info, $extra = [], $action_type = 'bet', $is_end = false) {
        $return_data = [
            'code' => self::RETURN_OK
        ];
        $wallet_transaction = [];

        $playerId = $this->currentPlayer['player_id'];

        $return_data['before_balance'] = $return_data['after_balance'] = 0;

        if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
            $return_data['before_balance'] = 0;
        }else{
            $return_data['before_balance'] = $this->api->queryPlayerBalanceByPlayerId($playerId)['balance'];
        }

        $afterBalance = null;

        $amount = 0;
        $status = self::TRANSACTION_STATUS_OK;

        # Set remote wallet required data
        $uniqueid_of_seamless_service=$this->api->getPlatformCode() . '-'.$this->requestParams->params['transactionId'];
        $external_game_id = (isset($this->requestParams->params['gameId'])?$this->requestParams->params['gameId']:null);     
        
        if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id); 
        }

        if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
            $this->wallet_model->setGameProviderActionType($action_type); 
        }

        $round_id = isset($this->requestParams->params['roundId'])?$this->requestParams->params['roundId']:null;
        $related_uniqueid_of_seamless_service = 'game-' . $this->api->getPlatformCode()."-{$transaction_type}-".$round_id;
        if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
            // $round_id = isset($this->requestParams->params['roundId'])?$this->requestParams->params['roundId']:null;
            $this->wallet_model->setGameProviderRoundId($round_id);
        }

        if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
            $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
        }

        if ( method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
            $this->wallet_model->setGameProviderIsEndRound($is_end);
        }

        if($transaction_type == self::TRANSACTION_CREDIT) {
            $amount = $this->requestParams->params['real'];
            if($amount > 0) {
                $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);

                $this->utils->debug_log(__METHOD__ , 'transaction_type', $transaction_type, '$afterBalance', $afterBalance);

                if($afterBalance===null){
                    $afterBalance = $this->api->queryPlayerBalanceByPlayerId($playerId)['balance'];                    
                }

                if(!$response) {
                    $return_data['code'] = self::ERROR_BAD_REQUEST;

                    if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                        # treat success if remote wallet return double uniqueid
                        if ($this->utils->isEnabledRemoteWalletClient()) {
                            $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                            if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $response = true;
                                $return_data['code'] = self::RETURN_OK;
                            }
                        }
                    }
                }else{
                    $return_data['before_balance'] = $afterBalance-$amount;
                }
            }

            $wallet_transaction['amount'] = $amount;
          
        }
        else if($transaction_type == self::TRANSACTION_DEBIT) {
            $amount = $this->requestParams->params['real'];
            $isInsufficient = false;
            if($amount > 0) {
                $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                if(!$response) {
                    $return_data['code'] = self::ERROR_INSUFFICIENT_BALANCE;

                    if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                        # treat success if remote wallet return double uniqueid
                        if ($this->utils->isEnabledRemoteWalletClient()) {
                            $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                            if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $response = true;
                                $return_data['code'] = self::RETURN_OK;
                            }
                        }
                    }
                }
            }
            $this->utils->debug_log(__METHOD__ , 'transaction_type', $transaction_type, '$afterBalance', $afterBalance);

            if($afterBalance===null){
                $afterBalance = $this->api->queryPlayerBalanceByPlayerId($playerId)['balance'];                    
            }

            $wallet_transaction['amount'] = $amount * -1;

            $walletCode = $this->wallet_model->getRemoteWalletErrorCode();
            if($walletCode==Wallet_model::REMOTE_WALLET_CODE_INSUFFICIENT_BALANCE){
                $isInsufficient = true;
            }
            
            if($isInsufficient){
                $return_data['before_balance'] = $afterBalance;
            }else{                
                $return_data['before_balance'] = $afterBalance+abs($amount);
            }

        }
        else if($transaction_type == self::TRANSACTION_ROLLBACK) {
            $amount = $this->requestParams->params['real'];
            if($amount > 0) {
                $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                if(!$response) {
                    $return_data['code'] = self::ERROR_BAD_REQUEST;

                    if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                        # treat success if remote wallet return double uniqueid
                        if ($this->utils->isEnabledRemoteWalletClient()) {
                            $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                            if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $response = true;
                                $return_data['code'] = self::RETURN_OK;
                            }
                        }
                    }
                }
            }
            $this->utils->debug_log(__METHOD__ , 'transaction_type', $transaction_type, '$afterBalance', $afterBalance);

            if($afterBalance===null){
                $afterBalance = $this->api->queryPlayerBalanceByPlayerId($playerId)['balance'];                    
            }

            $wallet_transaction['amount'] = $amount;           
            $return_data['before_balance'] = $afterBalance-abs($amount);
        }

        if( 
            ($transaction_type == self::TRANSACTION_ROLLBACK||$transaction_type == self::TRANSACTION_CREDIT) && 
            $this->utils->compareResultFloat($amount, '=', 0)
            ){
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                $this->utils->debug_log("PNG SEAMLESS SERVICE API: (adjustWallet) amount 0 call remote wallet", 'transaction_type', $transaction_type, 'player_info', $player_info);
                $response = $this->wallet_model->incRemoteWallet($playerId, $amount, $this->api->getPlatformCode(), $afterBalance);
                if(!$response){
                    $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                    if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                        $response = true;
                        $return_data['code'] = self::RETURN_OK;
                    }
                }
            } 
        }

        $return_data['after_balance'] = $afterBalance;
        if($return_data['after_balance']===null){
            $return_data['after_balance'] = $this->api->queryPlayerBalanceByPlayerId($playerId)['balance'];                    
        }
        if($transaction_type == self::TRANSACTION_CREDIT && $amount == 0) {
            $return_data['before_balance'] = $return_data['after_balance'];
        }

        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];
        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['game_id'] = $this->requestParams->params['gameId'];
        $wallet_transaction['status'] = $status;
        $wallet_transaction['external_unique_id'] = $this->requestParams->params['transactionId'] . ( $transaction_type == self::TRANSACTION_ROLLBACK ? '-' . self::TRANSACTION_ROLLBACK : '' );
        $wallet_transaction['extra_info'] = json_encode($this->requestParams->params);
        $wallet_transaction['start_at'] = $this->utils->getNowForMysql();
        $wallet_transaction['end_at'] = $this->utils->getNowForMysql();
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['round_id'] = $this->requestParams->params['roundId'];

        $wallet_transaction['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);

        $this->utils->debug_log(__METHOD__ , 'wallet_transaction', $wallet_transaction);

        if($return_data['code'] == self::RETURN_OK) {
            $this->wallet_transactions->insertRow($wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;
        }

        $return_data['round_id'] = $this->requestParams->params['roundId'];
        $return_data['external_unique_id'] = $this->requestParams->params['transactionId'];


        return $return_data;

    }

    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);
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
        $flag = $data['statusCode'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if(array_key_exists('real', $data)) {
            $data['real'] = bcdiv($data['real'], 1, 2);
        }

        $fields = [];

        if(!empty($this->currentPlayer)) {
            $fields = ['player_id' => $this->currentPlayer['player_id']];
        }

        if($this->api) {
            $cost = intval($this->utils->getExecutionTimeToNow()*1000);
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->function,
                json_encode($this->requestParams->params),
                json_encode($data),
                $this->http_status_code,
                null,
                is_array($this->headers) ? json_encode($this->headers) : $this->headers,
                $fields,
                false,
                null,
                $cost
            );

            if($this->wallet_transaction) {
                /*$this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $this->wallet_transaction['external_unique_id'], [
                    'response_result_id' => $response_result_id,
                    'cost' => $cost,
                ]);*/
            }
        }

        $xml = $this->toXML($data);

        $this->output->set_status_header($this->http_status_code)->set_content_type('application/xml')->set_output($xml->asXML());
        $this->output->_display();
        exit();
    }

    private function findObjectByTransactionType($array, $transaction_type, $external_unique_id = null){
        if (!empty($array) && is_array($array)) {
            foreach ($array as $element) {
                if ($element->transaction_type == $transaction_type) {
                    if(!empty($transaction_id)) {
                        if($element->external_unique_id != $external_unique_id) {
                            continue;
                        }
                    }
                    return $element;
                }
            }
        }
        return null;
    }

    private function toXML($data) {

        $xml = new SimpleXMLElement('<' . $this->requestParams->function . '/>');
        foreach($data as $key => $value){
            $xml->addChild($key, $value);
        }
        return $xml;
    }

    private function getToken() {
        $automation_tester_game_account = $this->api->getSystemInfo('automation_tester_game_account', []);
        $tokenTimeout = 3600;
        $params = $this->requestParams->params;
        $gameUsername = isset($params['externalId']) ? $params['externalId'] : null;

        if(empty($gameUsername)){
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if(!in_array($gameUsername, $automation_tester_game_account)){
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $token = $this->ssa_get_player_common_token_by_player_game_username($gameUsername, $this->game_platform_id, $tokenTimeout);
        return $this->setResponse(self::RETURN_OK, ['token' => $token]);
    }
}