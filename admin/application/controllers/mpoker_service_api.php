<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class mpoker_service_api extends BaseController {

    const INVALID_ARGUMENTS = [
        'http_code' => 400,
        'code' => 0,
        // 'description' => 'Input is invalid'
    ];

    const INVALID_SIGNATURE = [
        'http_code' => 401,
        'code' => 0,
        // 'description' => 'Signature is invalid'
    ];

    const ACCOUNT_NOT_FOUND = [
        'http_code' => 400,
        'code' => 11,
        // 'description' => 'Account is not existing'
    ];

    const NEGATIVE_REQUEST_AMOUNT = [
        'http_code' => 400,
        'code' => 31,
        // 'description' => 'Request amount is greater than balance'
    ];

    const INVALID_REQUEST_AMOUNT = [
        'http_code' => 400,
        'code' => 138,
        // 'description' => 'Request amount is invalid'
    ];

    const FREQUENT_TRANSACTION_REQUEST = [
        'http_code' => 400,
        'code' => 146,
        // 'description' => 'Repeating transaction request'
    ];

    const EXISTS_TRANSACTION_REQUEST = [
        'http_code' => 400,
        'code' => 34,
        // 'description' => 'Transaction request is already existing'
    ];

    const TRANSACTION_NOT_FOUND = [
        'http_code' => 400,
        'code' => 26,
        // 'description' => 'Transaction does not exist'
    ];

    const EXCEPTION_OCCURRED = [
        'http_code' => 500,
        'code' => 138,
        // 'description' => 'Exception occurred'
    ];

    const REQUEST_OK = [
        'http_code' => 200,
        'code' => 0,
        // 'description' => 'Success'
    ];

    const INVALID_IP = [
        'http_code' => 500,
        'code' => 138,
        // 'description' => 'IP not whitelisted'
    ];

    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCEL = 'cancel';


    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'common_seamless_error_logs', 'game_logs'));
        $this->headers = getallheaders();
    }

    private $request;
    private $response;
    private $currentPlayer;
    private $wallet_transaction_id;

    public function index($game_plaform_id, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($game_plaform_id);
        $this->request =  new stdClass();
        $this->request->function = $method;
        $this->parseRequest();

        $this->CI->load->model('mpoker_seamless_wallet_transactions', 'wallet_transactions');
        $this->wallet_transactions->tableName = $this->api->originalTable;

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::INVALID_ARGUMENTS);
            $this->utils->debug_log('MPoker Seamless' . __FUNCTION__ . ' Invalid method');
        }
        $this->$method();
    }

    public function getTransactionByRoundIdAndPlayerId($round_id, $player_id, $transaction_type) {
        $this->CI->db->select('*');
        $this->CI->db->where('round_id', $round_id);
        $this->CI->db->where('player_id', $player_id);
        $this->CI->db->where('transaction_type', $transaction_type);
        $this->CI->db->from('mpoker_seamless_wallet_transactions');
        $query = $this->db->get();
        $row = $query->row_array();
        if (!empty($row)) {
            return true;
        }
        return false;
    }

    public function getBalance1() {

        $balance = $this->api->queryPlayerBalance($this->currentPlayer->username)['balance'];
        if($balance === false){
            $this->utils->debug_log('MPoker Seamless' . __FUNCTION__ . ' error');
            return $this->setResponse(self::EXCEPTION_OCCURRED);
        }

        $response = [
            'balance' => $balance,
        ];

        return $this->setResponse(self::REQUEST_OK, $response);
    }

    public function withdraw() {
    /*
    {
    "gameId": "510",
    "gameNo": "",
    "account": "300215_testdevtestagain6",
    "orderId": "214748394403-510-138336700-34962199",
    "roundId": "50-1649862851-138336700",
    "player_id": "testdevtestagain6",
    "channelId": 300215,
    "requestAmount": "10"
    }*/
        $rule_set = [
            'gameId' => 'required',
            'gameNo' => 'required',
            'account' => 'required',
            'orderId' => 'required',
            'roundId' => 'required',
            'playerId' => 'required',
            'channelId' => 'required',
            'requestAmount' => 'required',
        ];
        $this->validateRequest($rule_set);

        $player_info = $this->currentPlayer;

        $transaction_data = [
            'code' => self::REQUEST_OK
        ];
        $transaction_result = $this->lockAndTransForPlayerBalance($player_info->player_id, function() use($player_info, &$transaction_data) {

            $old_transaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->request->params['param']['orderId'], 'external_unique_id');

            if($old_transaction) {
                $transaction_data['code'] = self::EXISTS_TRANSACTION_REQUEST;
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' transaction exists already');
                $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
                return true;
            }
            if($this->request->params['param']['requestAmount'] < 0) {
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' amount is less then 0');
                $transaction_data['code'] = self::NEGATIVE_REQUEST_AMOUNT;
                return false;
            }
            $transaction_data = $this->adjustWallet(self::TRANSACTION_DEBIT, $player_info);
            return true;
        });

        if($transaction_result) {
            $data = [
                'balance' => $transaction_data['after_balance'],
            ];
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::REQUEST_OK) {
                return $this->setResponse($transaction_data['code']);
            }

            $this->utils->debug_log('MPoker Seamless' . __FUNCTION__ . ' error', $transaction_result);
            return $this->setResponse(self::EXCEPTION_OCCURRED);
        }
    }

    public function adjustWallet($transaction_type, $player_info, $extra = []) {
        $return_data = [
            'code' => self::REQUEST_OK
        ];
        $wallet_transaction = [];
        $return_data['before_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
        $this->request->params['param']['requestAmount'] = ($transaction_type == SELF::TRANSACTION_ROLLBACK) ?  $this->request->params['param']['requestAmount'] = null :  $this->request->params['param']['requestAmount'];
        $amount = $this->request->params['param']['requestAmount'];

        //for canceled amount
        if($amount === null) {
            $amount = abs($extra['amount']);
        }
        if ($transaction_type == self::TRANSACTION_CREDIT) {
            if($amount > 0) {
                $response = $this->wallet_model->incSubWallet($player_info->player_id, $this->api->getPlatformCode(), $amount);
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'success');
                if(!$response) {
                    $return_data['code'] = self::EXCEPTION_OCCURRED;
                }
            }

            $wallet_transaction['amount'] = $amount;
            $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
        }
        else if($transaction_type == self::TRANSACTION_DEBIT) {
            if($return_data['before_balance'] < $amount) {

                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'debit failed: insufficient balance', $return_data['before_balance']);
                $return_data['code'] = self::INVALID_REQUEST_AMOUNT;
                $return_data['after_balance'] = $return_data['before_balance'];
            }
            else {

                $succ=$this->wallet_model->decSubWallet($player_info->player_id, $this->api->getPlatformCode(), $amount);
                if(!$succ){
                    $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'debit failed');
                    $return_data['code']=self::EXCEPTION_OCCURRED;
                }else{
                    $wallet_transaction['amount'] = $amount * -1;
                }
                $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
            }
        }
        else if($transaction_type == self::TRANSACTION_ROLLBACK) {
            if($amount == 0) {
                $wallet_transaction['amount'] = $amount;
                $return_data['after_balance'] = $return_data['before_balance'];
            }
            else {
                $succ=$this->wallet_model->incSubWallet($player_info->player_id, $this->api->getPlatformCode(), $amount);
                if(!$succ){
                    $return_data['code']=self::EXCEPTION_OCCURRED;
                    $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' error');
                }else{
                    $wallet_transaction['amount'] = $amount;
                }
                $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
            }
        }
        $request_timestamp = date("Y-m-d H:i:s", ($this->request->params['timestamp'] / 1000));
        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];
        $wallet_transaction['player_id'] = $player_info->player_id;
        $wallet_transaction['game_id'] = ($transaction_type == SELF::TRANSACTION_ROLLBACK) ? $extra['game_id'] : $this->request->params['param']['gameId'];
        $wallet_transaction['status'] = 'ok';
        $wallet_transaction['external_unique_id'] = ($transaction_type == self::TRANSACTION_ROLLBACK) ? $this->request->params['param']['orderId'] .'-rollback' : $this->request->params['param']['orderId'];

        $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'response!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!', json_encode($this->request->params));
        $wallet_transaction['extra_info'] = json_encode($this->request->params);
        $wallet_transaction['start_at'] = $request_timestamp;
        $wallet_transaction['end_at'] = $request_timestamp;
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['round_id'] = $this->request->params['param']['roundId'];
        $wallet_transaction['cost'] = intval($this->utils->getExecutionTimeToNow()*1000);

        if($return_data['code'] == self::REQUEST_OK) {
            $this->wallet_transaction_id = $this->wallet_transactions->insertRow($wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;
            if(!$this->wallet_transaction_id) {

                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'failed!!!!!!!!!!!!!!!!!!!!!!');
                throw new Exception('failed to insert transaction');
            }
        }

        $return_data['before_balance'] = $wallet_transaction['before_balance'];
        $return_data['after_balance'] = $wallet_transaction['after_balance'];
        $return_data['transaction_id'] = $wallet_transaction['external_unique_id'];
        return $return_data;
    }

    public function deposit() {
    /*{
    "gameId": "510",
    "gameNo": "",
    "account": "300215_testdevtestagain6",
    "orderId": "214748394403-510-138336700-34962199",
    "roundId": "50-1649862851-138336700",
    "player_id": "testdevtestagain6",
    "channelId": 300215,
    "requestAmount": "10"
    }*/
    $rule_set = [
        'gameId' => 'required',
        'gameNo' => 'required',
        'account' => 'required',
        'orderId' => 'required',
        'roundId' => 'required',
        'playerId' => 'required',
        'channelId' => 'required',
        'requestAmount' => 'required',
    ];
    $this->validateRequest($rule_set);

    $player_info = $this->currentPlayer;

    $transaction_data = [
        'code' => self::REQUEST_OK
    ];
    $this->utils->debug_log('MPoker Seamless deposit/credit', $transaction_data);

    $transaction_result = $this->lockAndTransForPlayerBalance($player_info->player_id, function() use($player_info, &$transaction_data) {

        $old_transaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->request->params['param']['orderId'], 'external_unique_id');

        $existsRoundId = $this->getTransactionByRoundIdAndPlayerId($this->request->params['param']['roundId'], $player_info->player_id, self::TRANSACTION_DEBIT);
        if($existsRoundId){
            if($old_transaction) {
                $transaction_data['code'] = self::EXISTS_TRANSACTION_REQUEST;
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' transaction exists already');
                $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
                return true;
            }
            if($this->request->params['param']['requestAmount'] < 0) {
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' amount is less then 0');
                $transaction_data['code'] = self::NEGATIVE_REQUEST_AMOUNT;
                return false;
            }
            $transaction_data = $this->adjustWallet(self::TRANSACTION_CREDIT, $player_info);
            return true;
        }
        else {
            $transaction_data['code'] = self::TRANSACTION_NOT_FOUND;
            $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];
            $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' transaction not found');
        }

    });

    if($transaction_result) {
        $data = [
            'balance' => $transaction_data['after_balance'],
        ];
        return $this->setResponse($transaction_data['code'], $data);
    }
    else {
        if($transaction_data['code'] != self::REQUEST_OK) {
            return $this->setResponse($transaction_data['code']);
        }

        $this->utils->debug_log('MPoker Seamless ' . __FUNCTION__ . 'error', $transaction_result);
        return $this->setResponse(self::EXCEPTION_OCCURRED);
    }
    }

    public function cancel() {
        /*{
        "gameNo": "",
        "account": "300215_testdevtestagain6",
        "orderId": "214748394403-510-138336700-34962199",
        "roundId": "50-1649862851-138336700",
        "player_id": "testdevtestagain6",
        "channelId": 300215,
        "requestAmount": "10"
        }*/
        $rule_set = [
            'gameNo' => 'required',
            'account' => 'required',
            'orderId' => 'required',
            'roundId' => 'required',
            'playerId' => 'required',
            'channelId' => 'required',
        ];
        $this->validateRequest($rule_set);

        $player_info = $this->currentPlayer;

        $transaction_data = [
            'code' => self::REQUEST_OK
        ];
        $this->utils->debug_log('MPoker Seamless cancel', $transaction_data);

        $transaction_result = $this->lockAndTransForPlayerBalance($player_info->player_id, function() use($player_info, &$transaction_data) {

            $old_transaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->request->params['param']['orderId'], 'external_unique_id');
            $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info->username)['balance'];

            if(!$old_transaction)  {
                $transaction_data['code'] = self::TRANSACTION_NOT_FOUND;
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' transaction not found');
                return true;
            }
            if($old_transaction->transaction_type === self::TRANSACTION_CANCEL) {
                $transaction_data['code'] = self::EXISTS_TRANSACTION_REQUEST;
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' transaction exists already');
                return true;
            }

            $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $old_transaction->external_unique_id, ['transaction_type' => self::TRANSACTION_CANCEL]);
            $transaction_data = $this->adjustWallet(self::TRANSACTION_ROLLBACK, $player_info, (array) $old_transaction);
            return true;
        });

        if($transaction_result) {
            $data = [
                'balance' => $transaction_data['after_balance'],
            ];
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::REQUEST_OK) {
                return $this->setResponse($transaction_data['code']);
            }

            $this->utils->debug_log('MPoker Seamless ' . __FUNCTION__ . ' error', $transaction_result);
            return $this->setResponse(self::EXCEPTION_OCCURRED);
        }
    }


    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && !array_key_exists($key, $this->request->params['param'])) {
                    $is_valid = false;
                    $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'missing parameter', $key);
                    break;
                }
                if($rule == 'numeric' && !is_numeric($this->request->params['param'][$key])) {
                    $is_valid = false;
                    $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , 'not numeric', $key);
                    break;
                }
            }
            if(!$is_valid) {
                return $this->setResponse(self::INVALID_ARGUMENTS);
                $this->utils->debug_log('MPoker Seamless ' . __METHOD__ , ' invalid input');
            }
        }
        return $is_valid;
    }

    private function parseRequest() {
        $this->request->params =  $this->getInputGetAndPost() ?: [];
        $postData = file_get_contents("php://input");
        if(!empty($postData)) {
            $this->request->params['param'] = $postData;
        }

        $this->utils->debug_log('MPoker Seamless request params', $this->request->params);
        $encryptedParams = $this->request->params['param'];
        $this->request->params['param'] =  json_decode($this->api->decrypt($this->request->params['param']), true);
        $playerParams = $this->request->params['param'];

        if (!$this->api->validateWhiteIP()) {
            return $this->setResponse(self::INVALID_IP);
        }

        if($this->api->agent != $this->request->params['channelId']) {
            $this->utils->debug_log('MPoker Seamless invalid channelId');
            return $this->setResponse(self::INVALID_ARGUMENTS);
        }
        $signature = md5($encryptedParams . $this->request->params['timestamp'] . $this->api->md5Key);

        if ($this->request->params['signature'] != $signature) {
            $this->utils->debug_log('MPoker Seamless invalid signature');
            return $this->setResponse(self::INVALID_SIGNATURE);
        }

        $this->load->model(['game_provider_auth']);
        $this->currentPlayer = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($playerParams['playerId'], $this->api->getPlatformCode());
        if(empty($this->currentPlayer)) {
            $this->utils->debug_log('MPoker Seamless empty player');
            return $this->setResponse(self::ACCOUNT_NOT_FOUND);
        }

        $this->utils->debug_log('MPoker Seamless currentPlayer', $this->currentPlayer);
        //for withdraw & deposit
        // if($this->request->function == "withdraw" || $this->request->function == "deposit"){
        //     $time_str = $this->api->timestamp_str('YmdHis', '');
        //     $orderId = $this->api->agent . $time_str . $this->currentPlayer->username;
        //     if($orderId != $this->request->params['param']['orderId']){
        //         return $this->setResponse(self::INVALID_ARGUMENTS);
        //     }
        // }

    }

    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = Response_result::FLAG_NORMAL;
        $http_status_code = (int)$data['http_code'];
        unset($data['http_code']);
        $data = json_encode($data);
        $encryptedParams = $this->api->encrypt($data, $this->api->desKey);
        // $encryptedParams = $data;

        $fields = [
            'player_id' => !empty($this->currentPlayer) ? $this->currentPlayer->player_id : null
        ];

        if($this->api) {
            $cost = intval($this->utils->getExecutionTimeToNow()*1000);
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->request->function,
                json_encode($this->request->params),
                $data,
                $http_status_code,
                null,
                null,
                $fields,
                false,
                null,
                $cost
            );
        }

        if($this->wallet_transaction_id) {
            $this->wallet_transactions->updateResponseResultId($this->wallet_transaction_id, $response_result_id);
        }
        $this->output->set_status_header($http_status_code)->set_content_type('text/plain;charset=UTF-8')->set_output($encryptedParams);
        $this->output->_display();
        exit();
    }
}
