<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Hotgraph_service_api extends BaseController {

    const RETURN_OK = [
        'error' => 0,
        'description' => 'Success'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'error' => 1,
        'description' => 'IP not allowed'
    ];

    const RETURN_FAIL_TRANSACTION_PROCESSED = [
        'error' => 1,
        'description' => 'Transaction already processed'
    ];

    const ERROR_NOT_FOUND_PLAYER = [
        'error' => 2,
        'description' => 'Player not found or is logged out'
    ];

    const ERROR_BET_NOT_ALLOWED = [
        'error' => 3,
        'description' => 'Bet is not allowed'
    ];

    const ERROR_INVALID_PARAMETERS = [
        'error' => 4,
        'description' => 'Invalid parameters'
    ];

    const ERROR_INVALID_SIGNATURE = [
        'error' => 5,
        'description' => 'Invalid signature'
    ];

    const ERROR_PLAYER_BANNED = [
        'error' => 6,
        'description' => 'Player is frozen'
    ];

    const ERROR_BAD_REQUEST = [
        'error' => 7,
        'description' => 'Bad parameters in the request, please check post parameters'
    ];

    const ERROR_DISABLED_API = [
        'error' => 8,
        'description' => 'Game is not found or disabled'
    ];

    const ERROR_BET_NOT_FOUND_FOR_ROLLBACK = [
        'error' => 9,
        'description' => 'Debit for this transactionId was not found, rollback aborted.'
    ];

    const ERROR_INSUFFICIENT_FUND= [
        'error' => 100,
        'description' => 'Internal server error'
    ];

    const ERROR_INTERNAL_ERROR= [
        'error' => 500,
        'description' => 'Internal server error'
    ];

    const BLACKLIST_METHODS = [
        'checkBalance',
        'placeBets',
        'settleBets',
		'cancelBets',
    ];

    const STATUS_OPEN = "OPEN";
    const STATUS_SETTLED = "SETTLED";
    const STATUS_REFUND = "REFUND";

    const TRANSACTION_CANCELLED = 'cancel';
    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_SETTLED = 'settled';

    private $requestParams;
    private $api;
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $wallet_transaction;

    public function __construct() {
        parent::__construct();
    }

    public function index($api, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($api);

        if(!$this->api) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->CI->load->model('common_seamless_wallet_transactions', 'wallet_transactions');
        if(!method_exists($this, $method)) {
            $this->requestParams->function = $method;
            $this->requestParams->params = json_decode(file_get_contents("php://input"), true);
            $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , $method . ' method not allowed');
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->requestParams = new stdClass();
        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        return $this->$method();
    }

    private function isSuccess($code) {
        switch ($code) {
            case self::RETURN_OK:
                return true;
            default:
                return false;
        }
    }

    public function checkBalance() {

        $rule_set = [
            'id' => 'required',
            'timestampMillis' => 'required',
            'productId' => 'required',
			'currency' => 'required',
			'username' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $player_info = $this->currentPlayer;

        if(!empty($player_info)) {
            $data = [
				'id' => $this->requestParams->params['id'],
				"statusCode" => 0,
				"timestampMillis"=> $this->requestParams->params['timestampMillis'],
				"productId" => $this->requestParams->params['productId'],
				'currency' => $this->requestParams->params['currency'],
				'balance' => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                'username' => $this->api->getGameUsernameByPlayerUsername($player_info['username'])
            ];
            return $this->setResponse(self::RETURN_OK, $data);
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function placeBets() {

        $rule_set = [
            'id' => 'required',
            'timestampMillis' => 'required',
            'productId' => 'required',
            'currency' => 'required',
            'username' => 'required',
            'txns' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

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

                        $transaction_data['betId'] = $txns_value['id'];
                        $transaction_data['status'] = $txns_value['status'];
                        $transaction_data['roundId'] = $txns_value['roundId'];
                        $transaction_data['betAmount'] = $txns_value['betAmount'];
                        $transaction_data['gameCode'] = $txns_value['gameCode'];
                        $transaction_data['playInfo'] = $txns_value['playInfo'];
                        $transaction_data['txnId'] = $txns_value['txnId'];
                        $transaction_data['external_unique_id'] = $this->requestParams->params['id']."-".$txns_value['txnId'];

                        $transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $txns_value['txnId'], 'transaction_id');

                        if($old_transaction = $this->findObjectByTransactionType($transaction, self::TRANSACTION_DEBIT)) {
                            $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'existing transaction', $txns_value['txnId'], $old_transaction);
                            $transaction_data['code'] = self::RETURN_OK;
                            $transaction_data['transaction_id'] = $old_transaction->external_unique_id;
                            $transaction_data['round_id'] = $old_transaction->round_id;
                            $transaction_data['balanceAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                            return true;
                        }

                        if($transaction_data['betAmount'] < 0) {
                            $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                            return false;
                        }

                        if($transaction_data['status'] != self::STATUS_OPEN) {
                            $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                            return false;
                        }

                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_DEBIT, $player_info, $transaction_data);
                        $this->utils->debug_log('HOTGRAPH ', 'ADJUST_WALLET_DEBIT_TAL: ', $adjustWallet);

                        if($adjustWallet['code']!= self::RETURN_OK){
                            return false;
                        }else{
                            $transaction_data['balanceAfter'] = $adjustWallet['after_balance'];
                            $transaction_data['code'] = self::RETURN_OK;
                        }

                    }

                    return true;

                });

                if($transaction_result) {
                    $transaction_data['balanceAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                    $this->utils->debug_log('HOTGRAPH ', 'TRANSACTION_TAL: ', $transaction_data);

                    $data =  array (
                        'id' => $this->requestParams->params['id'],
                        'statusCode' => 0,
                        'timestampMillis' => $this->requestParams->params['timestampMillis'],
                        'productId' => $this->requestParams->params['productId'],
                        'currency' => $this->requestParams->params['currency'],
                        'balanceBefore' => $transaction_data['balanceBefore'],
                        'balanceAfter' => $transaction_data['balanceAfter'],
                        'username' => $this->requestParams->params['username']
                    );

                    return $this->setResponse($transaction_data['code'], $data);

                }else {
                    $this->utils->debug_log('HOTGRAPH ', 'TRANSACTION_ERROR_TAL: ', $transaction_data);

                    if($transaction_data['code'] != self::RETURN_OK) {
                        $data = array(
                                'id' =>  $this->requestParams->params['id'],
                                'statusCode' => 806,
                                'timestampMillis' => $this->requestParams->params['timestampMillis'],
                                'productId' => $this->requestParams->params['productId']
                            );

                        return $this->setResponse($transaction_data['code'], $data);
                    }

                    return $this->setResponse(self::ERROR_INTERNAL_ERROR);
                }

        }

        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function settleBets() {
        $rule_set = [
            'id' => 'required',
            'timestampMillis' => 'required',
            'productId' => 'required',
            'currency' => 'required',
            'username' => 'required',
            'txns' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $player_info = $this->currentPlayer;

        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $txns = $controller->requestParams->params['txns'];

        $transaction_data['balanceBefore'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $data = array();

        $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$txns) {


            foreach($txns as $txns_key => $txns_value){

                $debitTransaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $txns_value['txnId'], 'transaction_id', self::TRANSACTION_DEBIT);

                $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'debitTransaction', $debitTransaction);

                if(!empty($debitTransaction)) {
                    if($debitTransaction->status == self::STATUS_SETTLED) {

                        $creditTransaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $txns_value['txnId'], 'transaction_id', self::TRANSACTION_CREDIT);

                        if(empty($creditTransaction)) {
                            $transaction_data['code'] = self::RETURN_FAIL_TRANSACTION_PROCESSED;
                            $transaction_data['transaction_id'] = $debitTransaction->external_unique_id;
                            $transaction_data['round_id'] = $debitTransaction->round_id;
                            $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                            return false;
                        }else {
                            $transaction_data['code'] = self::RETURN_OK;
                            $transaction_data['transaction_id'] = $debitTransaction->external_unique_id;
                            $transaction_data['round_id'] = $debitTransaction->round_id;
                            $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                            return true;
                        }

                    }

                }else {
                    $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'bet not found!!');
                    $transaction_data['code'] = self::ERROR_INVALID_PARAMETERS;
                    return false;
                }

                $transaction_data['betId'] = $txns_value['id'];
                $transaction_data['status'] = $txns_value['status'];
                $transaction_data['roundId'] = $txns_value['roundId'];
                $transaction_data['gameCode'] = $txns_value['gameCode'];
                $transaction_data['playInfo'] = $txns_value['playInfo'];
                $transaction_data['payoutAmount'] = $txns_value['payoutAmount'];
                $transaction_data['txnId'] = $txns_value['txnId'];
                $transaction_data['external_unique_id'] = $this->requestParams->params['id']."-".$txns_value['txnId'];

                $transaction_data['code'] = self::RETURN_OK;

                $adjustWallet = $controller->adjustWallet(self::TRANSACTION_CREDIT, $player_info, $transaction_data);
                $this->utils->debug_log('HOTGRAPH ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);

                $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $txns_value['roundId'], ['status' => self::STATUS_SETTLED]);
                $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'response', $transaction_data['code']);
                if(!$this->isSuccess($transaction_data['code'])) {
                    return false;
                }
            }

            return true;
        });

        $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , $transaction_result, $transaction_data);

        $transaction_data['balanceAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $data =  array (
            'id' => $this->requestParams->params['id'],
            'statusCode' => 0,
            'productId' => $this->requestParams->params['productId'],
            'timestampMillis' => $this->requestParams->params['timestampMillis'],
            'username' => $this->requestParams->params['username'],
            'currency' => $this->requestParams->params['currency'],
            'balanceBefore' => $transaction_data['balanceBefore'],
            'balanceAfter' => $transaction_data['balanceAfter']
        );

        return $this->setResponse($transaction_data['code'], $data);
    }

    private function cancelBets() {

        $rule_set = [
            'id' => 'required',
            'productId' => 'required',
            'username' => 'required',
            'currency' => 'required',
            'timestampMillis' => 'required',
            'timestampMillis' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $player_info = $this->currentPlayer;

        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $txns = $controller->requestParams->params['txns'];

        $transaction_data['balanceBefore'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $data = array();

        $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$txns) {

            foreach($txns as $txns_key => $txns_value){

                $transaction_data['betId'] = $txns_value['id'];
                $transaction_data['status'] = $txns_value['status'];
                $transaction_data['roundId'] = $txns_value['roundId'];
                $transaction_data['betAmount'] = $txns_value['betAmount'];
                $transaction_data['gameCode'] = $txns_value['gameCode'];
                $transaction_data['playInfo'] = $txns_value['playInfo'];
                $transaction_data['txnId'] = $txns_value['txnId'];
                $transaction_data['external_unique_id'] = $this->requestParams->params['id']."-".$txns_value['txnId'];

                if($transaction_data['status'] != self::STATUS_REFUND) {
                    $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                    return false;
                }

                $transaction = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $txns_value['txnId'], 'transaction_id');

                $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'get existing transaction', $txns_value['txnId'], $transaction);

                if($old_transaction = $this->findObjectByTransactionType($transaction, self::TRANSACTION_DEBIT)) {

                    $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'existing transaction', $txns_value['txnId'], $old_transaction);

                    if($rollback_transaction = $this->findObjectByTransactionType($transaction, self::TRANSACTION_CANCELLED)) {
                        $transaction_data['code'] = self::RETURN_OK;
                        $transaction_data['transaction_id'] = $rollback_transaction->external_unique_id;
                        $transaction_data['round_id'] = $rollback_transaction->round_id;
                        $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        return true;
                    }

                    if($txns_value['betAmount'] == abs($old_transaction->amount)) {
                        $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'cancelling transaction id', $txns_value['txnId']);
                        $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $txns_value['txnId'], ['status' => self::TRANSACTION_CANCELLED]);
                    }else {
                        $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'rollback amount is not same as debit amount');
                        $transaction_data['code'] = self::ERROR_INVALID_PARAMETERS;
                        return false;
                    }

                }

                if($txns_value['betAmount'] <= 0) {
                    $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'amount is less then or equal to 0');
                    $transaction_data['code'] = self::ERROR_INVALID_PARAMETERS;
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return false;
                }




                $adjustWallet = $controller->adjustWallet(self::TRANSACTION_CANCELLED, $player_info, $transaction_data);
                $this->utils->debug_log('HOTGRAPH ', 'TRANSACTION_CANCELLED: ', $adjustWallet);

                $transaction_data['code'] = self::RETURN_OK;

                if(!$this->isSuccess($transaction_data['code'])) {
                    return false;
                }

            }

            return true;
        });

        $transaction_data['balanceAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $data =  array (
            'id' => $this->requestParams->params['id'],
            'statusCode' => 0,
            'timestampMillis' => $this->requestParams->params['timestampMillis'],
            'productId' => $this->requestParams->params['productId'],
            'currency' => $this->requestParams->params['currency'],
            'balanceBefore' => $transaction_data['balanceBefore'],
            'balanceAfter' => $transaction_data['balanceAfter'],
            'username' => $this->requestParams->params['username'],
        );

        return $this->setResponse($transaction_data['code'], $data);
    }

    private function adjustWallet($transaction_type, $player_info, $extra = []) {
        $return_data = [
            'code' => self::RETURN_OK
        ];
        $wallet_transaction = [];
        $return_data['before_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        $amount = 0;

        if($transaction_type == self::TRANSACTION_CREDIT) {
            //Settling bet
            $betdetails = $this->wallet_model->getbetDetails($this->api->getPlatformCode(), $extra['txnId']);

            $betAmount = $betdetails['bet_amount'];
            $payoutAmount = $extra['payoutAmount'];
            $result = $payoutAmount - $betAmount;

            if($result > 0) {
                $response = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $result);
                if(!$response) {
                    $return_data['code'] = self::ERROR_BAD_REQUEST;
                }
            }


            $wallet_transaction['amount'] = $result;
            $wallet_transaction['bet_amount'] = $betAmount;
            $wallet_transaction['result_amount'] = $result;

            $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        }else if($transaction_type == self::TRANSACTION_DEBIT) {
            //Placing bet
            $amount = $extra['betAmount'];
            if($return_data['before_balance'] < $amount) {
                $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'insufficient balance');
                $return_data['code'] = self::ERROR_INSUFFICIENT_FUND;
                $return_data['after_balance'] = $return_data['before_balance'];
            }else {
                if($amount > 0) {
                    $response = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
                    if(!$response) {
                        $return_data['code'] = self::ERROR_INSUFFICIENT_FUND;
                    }
                }

                $wallet_transaction['amount'] = $amount * -1;
                $wallet_transaction['bet_amount'] = $amount;

                $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            }
        }else if($transaction_type == self::TRANSACTION_CANCELLED) {
            $amount = $extra['betAmount'];

            if($amount > 0) {
                $response = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
                if(!$response) {
                    $return_data['code'] = self::ERROR_BAD_REQUEST;
                }
            }

            $wallet_transaction['amount'] = $amount;
            $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        }

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
                    $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'missing parameter', $key);
                    break;
                }
                if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
                    $is_valid = false;
                    $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'not numeric', $key);
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
        // $this->utils->debug_log('HOTGRAPH ' . __METHOD__ , 'raw parameters: ', $raw_params);

        $params = json_decode($raw_params, true);

        $this->requestParams->function = $functionName ;
        $this->requestParams->params = $params;

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            echo "not valid parameter";
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if($params['productId'] != $this->api->product_id) {
            echo "product id is not correct";
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->CI->load->model('common_token');
        $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($this->requestParams->params['username']);

        if(empty($this->currentPlayer) && in_array($functionName, self::BLACKLIST_METHODS)) {
            return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
        }
        else if(!empty($this->currentPlayer)) {
            $this->currentPlayer['playerId'] = $this->currentPlayer['playerId'];
        }

    }

    private function setResponse($returnCode, $data = []) {
        $data['timestamp'] = intval(microtime(true)*1000);
        $data['productId'] = !empty($this->api->product_id) ? $this->api->product_id : '';
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

        $data = json_encode($data);

        $fields = [];

        if(!empty($this->currentPlayer)) {
            $fields = ['player_id' => $this->currentPlayer['playerId']];
        }

        if($this->api) {
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->function,
                json_encode($this->requestParams->params),
                $data,
                200,
                null,
                null
            );
        }

        $this->output->set_content_type('application/json')->set_output($data);
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

}