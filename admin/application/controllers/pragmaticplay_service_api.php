<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Pragmaticplay_service_api extends BaseController {

    const RETURN_OK = [
        'error' => 0,
        'description' => 'Success'
    ];

    const ERROR_INSUFFICIENT_BALANCE = [
        'error' => 1,
        'description' => 'Insufficient balance'
    ];

    const ERROR_NOT_FOUND_PLAYER = [
        'error' => 2,
        'description' => 'Player not found or is logged out'
    ];

    const ERROR_BET_NOT_ALLOWED = [
        'error' => 3,
        'description' => 'Bet is not allowed'
    ];

    const ERROR_AUTH_FAILED = [
        'error' => 4,
        'description' => 'Player authentication failed due to invalid, not found or expired token'
    ];

    const ERROR_INVALID_HASH = [
        'error' => 5,
        'description' => 'Invalid hash code'
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

    const ERROR_INTERNAL_ERROR= [
        'error' => 100,
        'description' => 'Internal server error'
    ];

    const ERROR_BET_NOT_FOUND = [
        'error' => 120,
        'description' => 'Bet transaction not found'
    ];

    const ERROR_TRANSACTION_ALREADY_PROCESSED_BY_CREDIT = [
        'error' => 120,
        'description' => 'Transaction already processed by credit.'
    ];

    const ERROR_TRANSACTION_ALREADY_PROCESSED_BY_REFUND = [
        'error' => 120,
        'description' => 'Transaction already processed by refund.'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'error' => 403,
        'description' => 'IP Address is not allowed.'
    ];

    const BLACKLIST_METHODS = [
        'authenticate',
        'bet',
        'balance',
    ];

    const TRANSACTION_ROLLBACK = 'rollback';//refund
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';//result
    const TRANSACTION_DEBIT = 'debit';//bet
    const TRANSACTION_BONUS = 'bonus';//bonusWin
    const TRANSACTION_ADJUSTMENT = 'adjustment';//Adjustment
    const TRANSACTION_ENDROUND = 'endRound';//endRound
    const TRANSACTION_PROMOWIN = 'promoWin';//promoWIn
    const TRANSACTION_JACKPOT = 'jackpot';//jackpotWIn

    private $requestParams;
    private $api;
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $wallet_transaction;
    private $headers;
    private $http_status_code;
    private $transaction_table_name;
    private $previous_transaction_table_name;
    private $remote_wallet_status = null;

    public function __construct() {
        parent::__construct();
        $this->headers = getallheaders();
        $this->http_status_code = 200;
    }

    public function index($api, $method) {
        $this->requestParams = new stdClass();
        $this->requestParams->function = $method;
        $this->requestParams->params = $this->input->post() ?: [];

        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        if($this->api->isMaintenance() || $this->api->isDisabled() || !$this->api->isActive()) {
            return $this->setResponse(self::ERROR_DISABLED_API);
        }
        $this->CI->load->model('pragmaticplay_seamless_wallet_transactions', 'pp_transactions');
        // $this->pp_transactions->tableName = $this->api->transaction_table_name;
        $this->transaction_table_name = $this->api->getSeamlessWalletTransactionsTable();
        $this->previous_transaction_table_name = $this->api->getPreviousSeamlessWalletTransactionsTable();

        // $this->requestParams = new stdClass();
        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        return $this->$method();
    }

    public function authenticate() {
        $rule_set = [
            'hash' => 'required',
            'providerId' => 'required',
            'token' => 'required',
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {
            if ($this->api->allow_multi_session) {
                list($token, $sign_key) = $this->common_token->createTokenWithSignKeyBy($player_info['player_id'], 'player_id', 60*30); // 30 minutes expiration
            } else {
                $token = $this->requestParams->params['token'];
            }

            $data = [
                'userId' => $this->api->getGameUsernameByPlayerUsername($player_info['username']),
                'currency' => $this->api->currency,
                'cash' => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                'bonus' => 0,
                'token' => $token,
            ];
            return $this->setResponse(self::RETURN_OK, $data);
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function authenticate2() {
        $rule_set = [
            'providerId' => 'required',
            'hash' => 'required',
            'username' => 'required',
            'password' => 'required',
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $player_info = (array) $this->api->getPlayerInfoByUsername($this->requestParams->params['username']);
        if($player_info) {
            $password = $password = $this->api->getPassword($player_info['username'])['password'];
            if($password == $this->requestParams->params['password']) {
                $data = [
                    'userId' => $this->api->getGameUsernameByPlayerUsername($player_info['username']),
                    'currency' => $this->api->currency,
                    'cash' => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                    'bonus' => 0
                ];
                return $this->setResponse(self::RETURN_OK, $data);
            }
        }
        return $this->setResponse(self::ERROR_AUTH_FAILED);
    }

    public function balance() {
        $rule_set = [
            'hash' => 'required',
            'providerId' => 'required',
            'userId' => 'required',
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {
            $data = [
                'currency' => $this->api->currency,
                'cash' => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                'bonus' => 0
            ];
            return $this->setResponse(self::RETURN_OK, $data);
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function bet() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            'gameId' => 'required',
            'roundId' => 'required',
            'amount' => 'required',
            'reference' => 'required',
            'providerId' => 'required',
            'timestamp' => 'required',
            'roundDetails' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$old_transaction) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }

                if($old_transaction) {
                    $transaction_data['code'] = self::RETURN_OK;
                    $transaction_data['transaction_id'] = $old_transaction[0]['external_uniqueid'];
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }
                if($controller->requestParams->params['amount'] < 0) {
                    $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                    return false;
                }

                $transaction_data = $controller->adjustWallet(self::TRANSACTION_DEBIT, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                $data = [
                    // 'transactionId' => $transaction_data['transaction_id'],
                    'transactionId' => $this->requestParams->params['reference'],
                    'currency' => $this->api->currency,
                    'cash' => $transaction_data['after_balance'],
                    'bonus' => 0,
                    'usedPromo' => 0
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                $this->saveRemoteWalletError(self::TRANSACTION_DEBIT);
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function result() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            'gameId' => 'required',
            'roundId' => 'required',
            'amount' => 'required',
            'reference' => 'required',
            'providerId' => 'required',
            'timestamp' => 'required',
            'roundDetails' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
      
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {


                $user_id = $this->requestParams->params['userId'];
                $game_id = isset($this->requestParams->params['gameId']) ? $this->requestParams->params['gameId'] : '';
                $round_id = isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '';

    
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (empty($old_transaction)) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }
                
                if($old_transaction) {
                    $transaction_data['code'] = self::RETURN_OK;
                    $transaction_data['transaction_id'] = $old_transaction[0]['external_uniqueid'];
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }
                if($controller->requestParams->params['amount'] < 0) {
                    $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                    return false;
                }

                // current_table
                $player_transactions = $this->pp_transactions->queryPlayerTransactionsByRound($this->requestParams->params['userId'], $this->requestParams->params['gameId'], $this->requestParams->params['roundId'], $this->transaction_table_name);
                
                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    // previous_table
                    if (empty($player_transactions)) {
                        $player_transactions = $this->pp_transactions->queryPlayerTransactionsByRound($this->requestParams->params['userId'], $this->requestParams->params['gameId'], $this->requestParams->params['roundId'], $this->previous_transaction_table_name);
                    }
                }

                if (!empty($player_transactions)) {
                    if ($player_transactions[0]['transaction_type'] == self::TRANSACTION_CANCELLED) {
                        $transaction_data['code'] = self::ERROR_TRANSACTION_ALREADY_PROCESSED_BY_REFUND;
                        return false;
                    }
                } else {
                    $transaction_data['code'] = self::ERROR_BET_NOT_FOUND;
                    return false;
                }			

                if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
        
                    // current_table
                    $bet_transaction = $this->pp_transactions->queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, self::TRANSACTION_DEBIT, $this->transaction_table_name);
        
                    if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                        if (empty($bet_transaction)) {
                            // previous_table
                            $bet_transaction = $this->pp_transactions->queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, self::TRANSACTION_DEBIT, $this->previous_transaction_table_name);
                        }
                    }
        
                    $related_action = Wallet_model::REMOTE_RELATED_ACTION_BET;
                    $related_uniqueid = isset($bet_transaction['external_uniqueid']) ? 'game-'.$bet_transaction['external_uniqueid'] : null;
                    if(empty($related_uniqueid)){
                        $this->utils->debug_log("PP SEAMLESS SERVICE API: (endRound) bet_transaction", 
                        'bet_transaction', $bet_transaction, 'params', $this->requestParams->params,
                        'transaction_table_name', $this->transaction_table_name, 
                        'previous_transaction_table_name', $this->previous_transaction_table_name,
                        'user_id', $user_id, 'game_id', $game_id, 'round_id', $round_id);
                    }

                    if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                        $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
                    }
                    if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                        $this->wallet_model->setRelatedActionOfSeamlessService($related_action);
                    }
        
                }

                $transaction_data = $controller->adjustWallet(self::TRANSACTION_CREDIT, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                $data = [
                    // 'transactionId' => $transaction_data['transaction_id'],
                    'transactionId' => $this->requestParams->params['reference'],
                    'currency' => $this->api->currency,
                    'cash' => $transaction_data['after_balance'],
                    'bonus' => 0,
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                $this->saveRemoteWalletError(self::TRANSACTION_CREDIT);
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    /**
     * Using this method the Pragmatic Play system will notify Casino Operator about winning that the player is awarded as a 
     * result of a campaign that is finished. Notification is asynchronous and may come to the operator with a short delay 
     * after the campaign is over. Operator should handle the transaction in their system and send promo win transaction id 
     * back to the Pragmatic Play.
     */
    public function promoWin() {
        $rule_set = [
            'hash' => 'required',
            'providerId' => 'required',
            'timestamp' => 'required',
            'userId' => 'required',
            'campaignId' => 'required',
            'campaignType' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'reference' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$old_transaction) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }

                if($old_transaction) {
                    $transaction_data['code'] = self::RETURN_OK;
                    $transaction_data['transaction_id'] = $old_transaction[0]['external_uniqueid'];
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }
                if($controller->requestParams->params['amount'] < 0) {
                    $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                    return false;
                }
                $transaction_data = $controller->adjustWallet(self::TRANSACTION_PROMOWIN, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT, true);
                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                $data = [
                    // 'transactionId' => $transaction_data['transaction_id'],
                    'transactionId' => $this->requestParams->params['reference'],
                    'currency' => $this->api->currency,
                    'cash' => $transaction_data['after_balance'],
                    'bonus' => 0,
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    /***
     * OGP-34759
     * Using this method a Pragmatic Play system will send to Casino Operator winning 
     * result of all rounds played on Free SpinsBonus. Casino Operator will change a player 
     * balance in appliance with this request and will return an updated balance.
     * NO BALANCE ADJUSTMENT NEEDED, already adjusted in "Result" api call
     */
    public function bonusWin() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            'amount' => 'required',
            'reference' => 'required',
            'providerId' => 'required',
            'timestamp' => 'required',
            'bonusCode' => 'required',
            'roundId' => 'required',
            'gameId' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$old_transaction) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }

                if($old_transaction) {
                    $transaction_data['code'] = self::RETURN_OK;
                    $transaction_data['transaction_id'] = $old_transaction[0]['external_uniqueid'];
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }
                if($controller->requestParams->params['amount'] < 0) {
                    $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                    return false;
                }
                $transaction_data = $controller->adjustWallet(self::TRANSACTION_BONUS, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT, true);
                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                $data = [
                    'transactionId' => $this->requestParams->params['reference'],
                    'currency' => $this->api->currency,
                    'cash' => $transaction_data['after_balance'],
                    'bonus' => 0,
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function jackpotWin() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            'gameId' => 'required',
            'roundId' => 'required',
            'amount' => 'required',
            'reference' => 'required',
            'providerId' => 'required',
            'timestamp' => 'required',
            'jackpotId' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$old_transaction) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }

                if($old_transaction) {
                    $transaction_data['code'] = self::RETURN_OK;
                    $transaction_data['transaction_id'] = $old_transaction[0]['external_uniqueid'];
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }
                if($controller->requestParams->params['amount'] < 0) {
                    $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                    return false;
                }
                $transaction_data = $controller->adjustWallet(self::TRANSACTION_JACKPOT, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT, true);
                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                $data = [
                    // 'transactionId' => $transaction_data['transaction_id'],
                    'transactionId' => $this->requestParams->params['reference'],
                    'currency' => $this->api->currency,
                    'cash' => $transaction_data['after_balance'],
                    'bonus' => 0
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function refund() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            // 'gameId' => 'required',
            // 'roundId' => 'required',
            // 'amount' => 'required',
            'reference' => 'required',
            'providerId' => 'required',
            // 'timestamp' => 'required',
            // 'roundDetails' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {
                $user_id = $this->requestParams->params['userId'];
                $game_id = isset($this->requestParams->params['gameId']) ? $this->requestParams->params['gameId'] : '';
                $round_id = isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '';

                
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$old_transaction) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }

                if($old_transaction) {
                    if($old_transaction[0]['transaction_type'] == self::TRANSACTION_CANCELLED) {
                        $transaction_data['transaction_id'] = $old_transaction[1]['external_uniqueid'];
                    }
                    else {
                        if (!isset($this->requestParams->params['roundId'])) {
                            $this->requestParams->params['roundId'] = !empty($old_transaction[0]['round_id']) ? $old_transaction[0]['round_id'] : null;
                        }

                        if (!isset($this->requestParams->params['gameId'])) {
                            $this->requestParams->params['gameId'] = !empty($old_transaction[0]['game_id']) ? $old_transaction[0]['game_id'] : null;
                        }

                        if (!isset($this->requestParams->params['timestamp'])) {
                            $this->requestParams->params['timestamp'] = !empty($old_transaction[0]['timestamp']) ? strtotime($old_transaction[0]['timestamp']) * 1000 : null;
                        }

                        if (!isset($this->requestParams->params['amount'])) {
                            $this->requestParams->params['amount'] = !empty($old_transaction[0]['amount']) ? abs($old_transaction[0]['amount']) : null;
                        }

                        // current_table
                        $table_name = $this->transaction_table_name;
                        $is_credit_exist = $this->pp_transactions->isPlayerTransactionAlreadyProcessedByTypeUserGameRoundCustom([
                            'transaction_type' => self::TRANSACTION_CREDIT,
                            // 'transaction_id' => $this->requestParams->params['reference'],
                            'user_id' => $this->requestParams->params['userId'],
                            'game_id' => $this->requestParams->params['gameId'],
                            'round_id' => $this->requestParams->params['roundId'],
                        ], $table_name);

                        if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                            if (!$is_credit_exist) {
                                // previous_table
                                $table_name = $this->previous_transaction_table_name;
                                $is_credit_exist = $this->pp_transactions->isPlayerTransactionAlreadyProcessedByTypeUserGameRoundCustom([
                                    'transaction_type' => self::TRANSACTION_CREDIT,
                                    // 'transaction_id' => $this->requestParams->params['reference'],
                                    'user_id' => $this->requestParams->params['userId'],
                                    'game_id' => $this->requestParams->params['gameId'],
                                    'round_id' => $this->requestParams->params['roundId'],
                                ], $table_name);
                            }
                        }

                        if (!$is_credit_exist) {

                            if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                    
                                // current_table
                                $bet_transaction = $this->pp_transactions->queryPlayerBetTransactionByRoundId($user_id, $round_id, self::TRANSACTION_DEBIT, $this->transaction_table_name);
                    
                                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                                    if (!$old_transaction) {
                                        // previous_table
                                        $bet_transaction = $this->pp_transactions->queryPlayerBetTransactionByRoundId($user_id, $round_id, self::TRANSACTION_DEBIT, $this->previous_transaction_table_name);
                                    }
                                }
                    
                                $related_action = Wallet_model::REMOTE_RELATED_ACTION_BET;
                                $related_uniqueid = isset($bet_transaction['external_uniqueid']) ? 'game-'.$bet_transaction['external_uniqueid'] : null;
                                
                                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
                                }
                                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                                    $this->wallet_model->setRelatedActionOfSeamlessService($related_action);
                                }
                    
                            }


                            // current_table
                            $is_updated = $this->pp_transactions->cancelTransaction($old_transaction[0]['transaction_id'], $this->transaction_table_name, true);

                            if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                                if (!$is_updated) {
                                    // previous_table
                                    $this->pp_transactions->cancelTransaction($old_transaction[0]['transaction_id'], $this->previous_transaction_table_name);
                                }
                            }

                            $transaction_data = $controller->adjustWallet(self::TRANSACTION_ROLLBACK, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND, true);
                        } else {
                            $transaction_data['code'] = self::ERROR_TRANSACTION_ALREADY_PROCESSED_BY_CREDIT;
                        }
                    }
                }
                else {
                    $transaction_data['code'] = self::ERROR_BET_NOT_FOUND;
                }
                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                if($transaction_data['code'] != self::RETURN_OK && $transaction_data['code'] != self::ERROR_BET_NOT_FOUND) {
                    return $this->setResponse($transaction_data['code']);
                }
                $data = [
                    // 'transactionId' => isset($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : 0,
                    'transactionId' => $this->requestParams->params['reference'],
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function adjustment() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            'gameId' => 'required',
            'roundId' => 'required',
            'amount' => 'required',
            'reference' => 'required',
            'providerId' => 'required',
            'validBetAmount' => 'required',
            'timestamp' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {

            $controller = $this;
            $transaction_data = [
                'code' => self::RETURN_OK
            ];
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['playerId'], function() use($controller, $player_info, &$transaction_data) {
                // current_table
                $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$old_transaction) {
                        // previous_table
                        $old_transaction = $this->pp_transactions->searchByExternalTransactionIdByTransactionType($controller->requestParams->params['reference'], $this->previous_transaction_table_name);
                    }
                }

                if($old_transaction) {
                    $transaction_data['code'] = self::RETURN_OK;
                    $transaction_data['transaction_id'] = $old_transaction[0]['external_uniqueid'];
                    $transaction_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    return true;
                }

                $transaction_data = $controller->adjustWallet(self::TRANSACTION_ADJUSTMENT, $player_info, Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT);

                if($transaction_data['code']!= self::RETURN_OK){
                    return false;
                }
                return true;
            });

            if($transaction_result) {
                $data = [
                    // 'transactionId' => $transaction_data['transaction_id'],
                    'transactionId' => $this->requestParams->params['reference'],
                    'currency' => $this->api->currency,
                    'cash' => $transaction_data['after_balance'],
                    'bonus' => 0
                ];
                return $this->setResponse($transaction_data['code'], $data);
            }
            else {
                if($transaction_data['code'] != self::RETURN_OK) {
                    return $this->setResponse($transaction_data['code']);
                }
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    /***
     * OGP-34759
     * Request path: POST /endRound.htmlEvery time a game round is over, 
     * the Pragmatic Play system will call EndRound method, so that Operator 
     * can finalize thegame round transactions on their side in real time.
     * NO BALANCE ADJUSTMENT NEEDED, already adjusted in "Result" api call, 
     * it is only used to flag end round, to ask GP to enable this
     */
    public function endRound() {
        $rule_set = [
            'hash' => 'required',
            'userId' => 'required',
            'gameId' => 'required',
            'roundId' => 'required',
            'providerId' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $transaction_type = self::TRANSACTION_ENDROUND;

        # get player information
        if(empty($this->currentPlayer)) {
            $user_name = $this->api->getPlayerUsernameByGameUsername($this->requestParams->params['userId']);
            $this->currentPlayer = (array) $this->api->getPlayerInfoByUsername($user_name);
        }
        $player_info = $this->currentPlayer;

        # insert transaction
        $user_id = $wallet_transaction['user_id'] = $this->requestParams->params['userId'];
        $game_id = $wallet_transaction['game_id'] = isset($this->requestParams->params['gameId']) ? $this->requestParams->params['gameId'] : '';
        $round_id = $wallet_transaction['round_id'] = isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '';
        $wallet_transaction['transaction_id'] = $wallet_transaction['round_id'];
        if(isset($this->requestParams->params['reference'])&&!empty($this->requestParams->params['reference'])){
            $wallet_transaction['transaction_id'] = $this->requestParams->params['reference'];
        }
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['provider_id'] = $this->requestParams->params['providerId'];
        $wallet_transaction['timestamp'] = date("Y-m-d H:i:s");
        if(isset($this->requestParams->params['timestamp'])){
            $wallet_transaction['timestamp'] = date("Y-m-d H:i:s", ($this->requestParams->params['timestamp']/ 1000));
        }

        $wallet_transaction['round_details'] = isset($this->requestParams->params['roundDetails']) ? $this->requestParams->params['roundDetails'] : '';
        $wallet_transaction['before_balance'] = 0;
        $wallet_transaction['after_balance'] = 0;
        $wallet_transaction['jackpot_id'] = null;
        $wallet_transaction['campaign_id'] = null;
        $wallet_transaction['campaign_type'] = null;
        $wallet_transaction['currency'] = isset($this->requestParams->params['currency']) ? $this->requestParams->params['currency'] : $this->api->currency;
        $wallet_transaction['bonus_code'] = null;
        
        $uniqueid_of_seamless_service = $transaction_type . '-'.$round_id.'-'.$user_id;

        $wallet_transaction['external_uniqueid'] = $uniqueid_of_seamless_service;

        // current_table
        $old_transaction = $this->pp_transactions->queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, $transaction_type, $this->transaction_table_name);

        if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
            if (empty($old_transaction)) {
                // previous_table
                $old_transaction = $this->pp_transactions->queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, $transaction_type, $this->previous_transaction_table_name);
            }
        }

        $remoteActionType = 'payout';
        if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
            $this->wallet_model->setGameProviderActionType($remoteActionType);
        }

        $external_game_id = isset($this->requestParams->params['gameId']) ? $this->requestParams->params['gameId'] : '';
        if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
        }

        if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
            $this->wallet_model->setGameProviderRoundId($round_id);
        }

        if (method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
            $this->wallet_model->setGameProviderIsEndRound(true);
        }			

        if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){

            // current_table
            $bet_transaction = $this->pp_transactions->queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, self::TRANSACTION_DEBIT, $this->transaction_table_name);

            if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                if (empty($bet_transaction)) {
                    // previous_table
                    $bet_transaction = $this->pp_transactions->queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, self::TRANSACTION_DEBIT, $this->previous_transaction_table_name);
                }
            }

            $related_action = Wallet_model::REMOTE_RELATED_ACTION_BET;
            $related_uniqueid = isset($bet_transaction['external_uniqueid']) ? 'game-'.$bet_transaction['external_uniqueid'] : null;
            if(empty($related_uniqueid)){
                $this->utils->debug_log("PP SEAMLESS SERVICE API: (endRound) bet_transaction", 
                'bet_transaction', $bet_transaction, 'params', $this->requestParams->params,
                'transaction_table_name', $this->transaction_table_name, 
                'previous_transaction_table_name', $this->previous_transaction_table_name,
                'user_id', $user_id, 'game_id', $game_id, 'round_id', $round_id);
            }
            
            if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
            }
            if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                $this->wallet_model->setRelatedActionOfSeamlessService($related_action);
            }

        }

        $afterBalance = 0;

        if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
            $this->utils->debug_log("PP SEAMLESS SERVICE API: (endRound) amount 0 call remote wallet", 
            'wallet_transaction', $wallet_transaction, 'params', $this->requestParams->params);

            $succ= $this->sendRemoteWalletIncreaseZeroAmount($player_info['playerId'], $this->wallet_model, $this->api->getPlatformCode(), $afterBalance);
            if(!$succ){
                return $this->setResponse(self::ERROR_INTERNAL_ERROR);
            }
        } 

        if(empty($old_transaction)){
            #insert transaction
            $this->wallet_transaction_id =  $this->pp_transactions->insertTransaction($wallet_transaction, $this->transaction_table_name);
            $this->wallet_transaction = $wallet_transaction;
        }else{
            $this->wallet_transaction_id =  $old_transaction['id'];
            $this->wallet_transaction = $old_transaction;
        }

        if($afterBalance===false){
            $afterBalance=$this->api->queryPlayerBalance($player_info['username'])['balance'];
        }

        if($this->wallet_transaction_id) {
            $data = [
                //'currency' => $this->api->currency,
                'cash' => $afterBalance,
                'bonus' => 0
            ];
            return $this->setResponse(self::RETURN_OK, $data);            
        }

        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    public function adjustWallet($transaction_type, $player_info, $remoteActionType, $is_end = false, $relatedAction = null, $relatedUniqueId = null) {
        $return_data = [
            'code' => self::RETURN_OK
        ];
        $wallet_transaction = [];
        $return_data['before_balance'] = $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        $after_balance = null;
        $amount = $this->requestParams->params['amount'];

        if(array_key_exists('promoWinAmount', $this->requestParams->params)) {
            $amount += $this->requestParams->params['promoWinAmount'];
        }

        //OGP-28649
        $uniqueid_of_seamless_service = null;

        $round_id = isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '';

        if(isset($this->requestParams->params['reference'])&&!empty($this->requestParams->params['reference'])){
            $uniqueid_of_seamless_service = $this->requestParams->params['reference'];
        }

        if(empty($uniqueid_of_seamless_service)){
            $uniqueid_of_seamless_service = $this->api->getSecureId($this->transaction_table_name, 'external_uniqueid', true, 'PP');
        }

        $uniqueid_of_seamless_service = $this->api->getPlatformCode() .'-'. $transaction_type . '-'.$round_id.'-'.$uniqueid_of_seamless_service;
        
        /*if( $transaction_type == self::TRANSACTION_CREDIT ) {
            $remoteActionType = 'payout';
        }else if($transaction_type == self::TRANSACTION_DEBIT){
            $remoteActionType = 'bet';
        }else if($transaction_type == self::TRANSACTION_ADJUSTMENT) {
            $remoteActionType = 'adjustment';
        }else if($transaction_type == self::TRANSACTION_ROLLBACK) {
            $remoteActionType = 'refund';
        }else if($transaction_type == self::TRANSACTION_BONUS) {
            $remoteActionType = 'adjustment';
        }else if($transaction_type == self::TRANSACTION_PROMOWIN) {
            $remoteActionType = 'adjustment';
        }else{
            $this->utils->error_log("PP Undefined transaction_type", 
            'transaction_type', $transaction_type,
            'requestParams', $this->requestParams);
            if ($this->utils->isEnabledRemoteWalletClient()) {

            }
        }*/

        if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()) {
            if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                $this->wallet_model->setGameProviderActionType($remoteActionType);
            }

            $external_game_id = isset($this->requestParams->params['gameId']) ? $this->requestParams->params['gameId'] : '';
            if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
            }

            if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                $this->wallet_model->setGameProviderRoundId($round_id);
            }

            if (method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
                $this->wallet_model->setGameProviderIsEndRound($is_end);
            }                    
            
            if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService') && !empty($relatedUniqueId)) {
                $this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueId);
            }

            if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService') && !empty($relatedAction)) {
                $this->wallet_model->setRelatedActionOfSeamlessService($relatedAction);
            }
        }

        if($transaction_type == self::TRANSACTION_CREDIT) {
            //result
            $succ=$this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);

            $this->utils->debug_log("PP SEAMLESS SERVICE API: (result credit)", 
            'return_data', $return_data, 'params', $this->requestParams->params, 'amount', $amount, 'after_balance', $after_balance);

            if(!$succ){
                $return_data['code']=self::ERROR_BET_NOT_ALLOWED;

                if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                    # treat success if remote wallet return double uniqueid
                    if ($this->utils->isEnabledRemoteWalletClient()) {
                        $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                        if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                            $succ = true;
                            $return_data['code']=self::RETURN_OK;
                            $wallet_transaction['amount'] = $amount;
                        }
                    }
                }

                $this->remote_wallet_status = $this->remoteWalletErrorCode();
            }else{
                $wallet_transaction['amount'] = $amount;
            }
            $return_data['after_balance'] = !empty($after_balance) ? $after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];

        } else if ($transaction_type == self::TRANSACTION_PROMOWIN){
            #promoWin
            $succ=$this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);

            $this->utils->debug_log("PP SEAMLESS SERVICE API: (promoWin)", 
            'return_data', $return_data, 'params', $this->requestParams->params, 'amount', $amount, 'after_balance', $after_balance);

            if(!$succ){
                $return_data['code']=self::ERROR_BET_NOT_ALLOWED;

                if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                    # treat success if remote wallet return double uniqueid
                    if ($this->utils->isEnabledRemoteWalletClient()) {
                        $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                        if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                            $succ = true;
                            $return_data['code']=self::RETURN_OK;
                            $wallet_transaction['amount'] = $amount;
                        }
                    }
                }
            }else{
                $wallet_transaction['amount'] = $amount;
            }
            $return_data['after_balance'] = !empty($after_balance) ? $after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];

        } else if ($transaction_type == self::TRANSACTION_JACKPOT){

            #jackpotWin
            $succ=$this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);

            $this->utils->debug_log("PP SEAMLESS SERVICE API: (jackpotWin)", 
            'return_data', $return_data, 'params', $this->requestParams->params, 'amount', $amount, 'after_balance', $after_balance);

            if(!$succ){
                $return_data['code']=self::ERROR_BET_NOT_ALLOWED;

                if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                    # treat success if remote wallet return double uniqueid
                    if ($this->utils->isEnabledRemoteWalletClient()) {
                        $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                        if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                            $succ = true;
                            $return_data['code']=self::RETURN_OK;
                            $wallet_transaction['amount'] = $amount;
                        }
                    }
                }
            }else{
                $wallet_transaction['amount'] = $amount;
            }
            $return_data['after_balance'] = !empty($after_balance) ? $after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];

        }
        else if($transaction_type == self::TRANSACTION_BONUS) {
            #bonusWin
            $wallet_transaction['amount'] = $amount;
            $return_data['after_balance'] = $return_data['before_balance'];


            $this->utils->debug_log("PP SEAMLESS SERVICE API: (bonusWin) no adjustment needed, adjustment made in result as per GP", 
            'return_data', $return_data, 'params', $this->requestParams->params);
        }
        else if($transaction_type == self::TRANSACTION_DEBIT) {
            #bet
            if($return_data['before_balance'] < $amount) {
                $return_data['code'] = self::ERROR_INSUFFICIENT_BALANCE;
                $return_data['after_balance'] = $return_data['before_balance'];
            }
            else {
                if($amount == 0) { // free spin
                    $wallet_transaction['amount'] = $amount;
                    $return_data['after_balance'] = $return_data['before_balance'];
                }
                else {
                    $succ=$this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);
                    if(!$succ){
                        $return_data['code']=self::ERROR_INSUFFICIENT_BALANCE;
                           # treat success if remote wallet return double uniqueid
                           if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                                $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
                                    $this->CI->utils->debug_log('PP--REMOTE_WALLET_CODE_DOUBLE_UNIQUEID');
                                    $wallet_transaction['amount'] = $amount * -1;
                                    $return_data['code'] = self::RETURN_OK;
                                }    
                            }
                            
                            $this->remote_wallet_status = $this->remoteWalletErrorCode();
                    }else{
                        $wallet_transaction['amount'] = $amount * -1;
                    }
                    $return_data['after_balance'] = !empty($after_balance) ? $after_balance :$this->api->queryPlayerBalance($player_info['username'])['balance'];
                }
            }


            $this->utils->debug_log("PP SEAMLESS SERVICE API: (bet)", 
            'return_data', $return_data, 'params', $this->requestParams->params);
        }
        else if($transaction_type == self::TRANSACTION_ROLLBACK) {
            #refund
            if($amount == 0) {
                $wallet_transaction['amount'] = $amount;
                $return_data['after_balance'] = $return_data['before_balance'];
            }
            else {
                $succ=$this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);
                if(!$succ){
                    $return_data['code']=self::ERROR_BET_NOT_ALLOWED;

                    if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                        # treat success if remote wallet return double uniqueid
                        if ($this->utils->isEnabledRemoteWalletClient()) {
                            $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                            if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $succ = true;
                                $return_data['code']=self::RETURN_OK;
                                $wallet_transaction['amount'] = $amount;
                            }
                        }
                    }

                    $this->remote_wallet_status = $this->remoteWalletErrorCode();
                }else{
                    $wallet_transaction['amount'] = $amount;
                }
                $return_data['after_balance'] = !empty($after_balance) ? $after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];
            }
        }
        else if($transaction_type == self::TRANSACTION_ADJUSTMENT) {
            #adjustment
            //negative amount
            if ($amount < 0) {
                $amount = abs($amount);
                if ($return_data['before_balance'] < $amount) {
                    $return_data['code'] = self::ERROR_INSUFFICIENT_BALANCE;
                    $return_data['after_balance'] = $return_data['before_balance'];
                } else {
                    if ($amount == 0) { // free spin
                        $wallet_transaction['amount'] = $amount;
                        $return_data['after_balance'] = $return_data['before_balance'];
                    } else {
                        $succ = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);
                        if (!$succ) {
                            $return_data['code']=self::ERROR_INSUFFICIENT_BALANCE;

                            if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                                # treat success if remote wallet return double uniqueid
                                if ($this->utils->isEnabledRemoteWalletClient()) {
                                    $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                                    if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                        $succ = true;
                                        $return_data['code']=self::RETURN_OK;
                                        $wallet_transaction['amount'] = $amount * -1;
                                    }
                                }
                            }

                            $this->remote_wallet_status = $this->remoteWalletErrorCode();
                        } else {
                            $wallet_transaction['amount'] = $amount * -1;
                        }
                        $return_data['after_balance'] = !empty($after_balance) ? $after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    }
                }
            } else { //positive amount
                $succ = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount, $after_balance);
                if (!$succ) {
                    $return_data['code'] = self::ERROR_BET_NOT_ALLOWED;

                    if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
                        # treat success if remote wallet return double uniqueid
                        if ($this->utils->isEnabledRemoteWalletClient()) {
                            $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                            if ($remoteErrorCode == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $succ = true;
                                $return_data['code']=self::RETURN_OK;
                                $wallet_transaction['amount'] = $amount;
                            }
                        }
                    }

                    $this->remote_wallet_status = $this->remoteWalletErrorCode();
                }else{
                    $wallet_transaction['amount'] = $amount;
                }
                $return_data['after_balance'] = !empty($after_balance) ? $after_balance : $this->api->queryPlayerBalance($player_info['username'])['balance'];
            }
        }


        $wallet_transaction['user_id'] = $this->requestParams->params['userId'];
        $wallet_transaction['game_id'] = isset($this->requestParams->params['gameId']) ? $this->requestParams->params['gameId'] : '';
        $wallet_transaction['round_id'] = isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '';
        $wallet_transaction['transaction_id'] = $this->requestParams->params['reference'];
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['provider_id'] = $this->requestParams->params['providerId'];
        $wallet_transaction['timestamp'] = date("Y-m-d H:i:s", ($this->requestParams->params['timestamp']/ 1000));
        $wallet_transaction['round_details'] = isset($this->requestParams->params['roundDetails']) ? $this->requestParams->params['roundDetails'] : '';
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];
        $wallet_transaction['jackpot_id'] = isset($this->requestParams->params['jackpotId']) ? $this->requestParams->params['jackpotId'] : '';
        $wallet_transaction['campaign_id'] = isset($this->requestParams->params['campaignId']) ? $this->requestParams->params['campaignId'] : (isset($this->requestParams->params['promoCampaignID']) ? $this->requestParams->params['promoCampaignID'] : '');
        $wallet_transaction['campaign_type'] = isset($this->requestParams->params['campaignType']) ? $this->requestParams->params['campaignType'] : (isset($this->requestParams->params['promoCampaignType']) ? $this->requestParams->params['promoCampaignType'] : '');;
        $wallet_transaction['currency'] = isset($this->requestParams->params['currency']) ? $this->requestParams->params['currency'] : $this->api->currency;
        $wallet_transaction['bonus_code'] = isset($this->requestParams->params['bonusCode']) ? $this->requestParams->params['bonusCode'] : '';
        //$wallet_transaction['external_uniqueid'] = $this->api->getSecureId($this->pp_transactions->tableName, 'external_uniqueid', true, 'PP');
        $wallet_transaction['external_uniqueid'] = $uniqueid_of_seamless_service;

        if ($transaction_type == self::TRANSACTION_ADJUSTMENT) {
            if (isset($this->requestParams->params['validBetAmount'])) {
                $roundDetails = [
                    'validBetAmount' => !empty($this->requestParams->params['validBetAmount']) ? $this->requestParams->params['validBetAmount'] : 0,
                ];

                $wallet_transaction['round_details'] = json_encode($roundDetails);
            }
        }

        if($return_data['code'] == self::RETURN_OK) {
            // current_table
            $this->wallet_transaction_id = $this->pp_transactions->insertTransaction($wallet_transaction, $this->transaction_table_name);
            $this->wallet_transaction = $wallet_transaction;

            if(!$this->wallet_transaction_id) {
                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    // previous_table
                    $this->wallet_transaction_id = $this->pp_transactions->insertTransaction($wallet_transaction, $this->previous_transaction_table_name);

                    if (!$this->wallet_transaction_id) {
                        throw new Exception('failed to insert transaction');
                    }
                }
            }
        }

        if($transaction_type == self::TRANSACTION_CREDIT) {
            //set pragmatic player settled datetime
            // current_table
            $is_updated = $this->pp_transactions->updateSettledAt($wallet_transaction['round_id'], $wallet_transaction['user_id'], null, $this->transaction_table_name, true);

            if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                if (!$is_updated) {
                    // previous_table
                    $this->pp_transactions->updateSettledAt($wallet_transaction['round_id'], $wallet_transaction['user_id'], null, $this->previous_transaction_table_name);
                }
            }
        }

        $return_data['before_balance'] = $wallet_transaction['before_balance'];
        $return_data['after_balance'] = $wallet_transaction['after_balance'];
        $return_data['transaction_id'] = $wallet_transaction['external_uniqueid'];
        return $return_data;
    }

    public function saveRemoteWalletError($transaction_type){
        $uniqueid_of_seamless_service = null;

        $round_id = isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '';

        if(isset($this->requestParams->params['reference'])&&!empty($this->requestParams->params['reference'])){
            $uniqueid_of_seamless_service = $this->requestParams->params['reference'];
        }

        if(empty($uniqueid_of_seamless_service)){
            $uniqueid_of_seamless_service = $this->api->getSecureId($this->transaction_table_name, 'external_uniqueid', true, 'PP');
        }

        $uniqueid_of_seamless_service = $this->api->getPlatformCode() .'-'. $transaction_type . '-'.$round_id.'-'.$uniqueid_of_seamless_service;
        $this->utils->debug_log("PP-saveRemoteWalletError",$this->requestParams->params,$uniqueid_of_seamless_service);
        $failed_transaction_data = $md5_data = [
            'round_id' => isset($this->requestParams->params['roundId']) ? $this->requestParams->params['roundId'] : '',
            'transaction_id' => $this->requestParams->params['reference'],
            'external_game_id' => $this->requestParams->params['gameId'],
            'player_id' => $this->currentPlayer['playerId'],
            'game_username' => $this->requestParams->params['userId'],
            'amount' => $this->requestParams->params['amount'],
            'balance_adjustment_type' => $transaction_type,
            'action' => $this->requestParams->function,
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_raw_data' => json_encode($this->requestParams->params),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => date("Y-m-d H:i:s", ($this->requestParams->params['timestamp']/ 1000)),
            'request_id' => $this->utils->getRequestId(),
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'headers' => json_encode(getallheaders()),
            'external_uniqueid' => $uniqueid_of_seamless_service
        ];
        
        $failed_transaction_data['md5_sum'] = md5(json_encode($md5_data));

        $where = ['external_uniqueid' => $uniqueid_of_seamless_service];
        if($this->isFailedTransactionExist($where)){
            $this->saveFailedTransaction('update',$failed_transaction_data, $where);
        }else{
            $this->saveFailedTransaction('insert',$failed_transaction_data);
        }
    }

    public function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                    $is_valid = false;
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    public function preProcessRequest($functionName, $rule_set = []) {
        $params = $this->input->post() ?: [];
        $this->requestParams->function = $functionName;
        $this->requestParams->params = $params;

        if (!$this->api->validateWhiteIP()) {
            $this->http_status_code = 403;
            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED);
        }

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $hash = $this->generateHash($params);

        if($hash != $params['hash']) {
            if($this->api->show_hash_code) {
                return $this->setResponse(self::ERROR_INVALID_HASH, [ 'hash' => $hash ]);
            }
            return $this->setResponse(self::ERROR_INVALID_HASH);
        }

        if($params['providerId'] != $this->api->provider_id) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if(isset($this->requestParams->params['token'])) {
            $this->CI->load->model('common_token');
            $this->currentPlayer = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->requestParams->params['token'], $this->api->getPlatformCode());
            if(empty($this->currentPlayer) && in_array($functionName, self::BLACKLIST_METHODS)) {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }
            else if(!empty($this->currentPlayer)) {
                $this->currentPlayer['playerId'] = $this->currentPlayer['player_id'];
            }
        }
    }

    private function generateHash($parameters) {
        unset($parameters['hash']);

        ksort($parameters);
        $payload = '';
        foreach($parameters as $key => $param) {
            if(!empty($payload)) {
                $payload .= '&';
            }
            $payload .= "{$key}={$param}";
        }
        $hash = md5($payload . $this->api->secretKey);
        return $hash;
    }

    private function testGenerateHash(){
        $parameters= $this->requestParams->params;
        unset($parameters['hash']);

        ksort($parameters);
        $payload = '';
        foreach($parameters as $key => $param) {
            if(!empty($payload)) {
                $payload .= '&';
            }
            $payload .= "{$key}={$param}";
        }
        $hash = md5($payload . $this->api->secretKey);
        echo $hash;
    }
    
    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);
        return $this->setOutput($data);
    }

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->api->getPlatformCode(), $this->wallet_transaction['game_id']);
        $betType = null;
        switch($this->requestParams->function) {
            case 'bet':
                $betType = 'Bet';
                break;
            case 'result':
            case 'promoWin':
            case 'bonusWin':
            case 'jackpotWin':
                $betType = 'Win';
                break;
            case 'refund':
                $betType = 'Refund';
                break;
            default:
                $betType = null;
                break;
        }

        if ($betType == null) {
            return;
        }

        $data = [
            "activity_id" =>  strval($this->wallet_transaction_id),
            "amount" => (float) abs($this->wallet_transaction['amount']),
            "balance_after" =>  $this->wallet_transaction['after_balance'],
            "balance_before" =>  $this->wallet_transaction['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->api->currency,
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  $this->wallet_transaction['round_id'],
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->currentPlayer['playerId'],
            "vendor_id" =>  strval($this->api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->wallet_transaction['amount']) : 0,
        ];

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

    public function truncateAmount($amount, $precision = 2)
    {
        if ($amount == 0) {
            return $amount;
        }

        $value = floatval($amount);
        return floatval(bcdiv($value, 1, $precision));
    }

    private function setOutput($data = []) {
        $flag = $data['error'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if(array_key_exists('cash', $data)) {
            $data['cash'] = $this->truncateAmount($data['cash']);
        }
        if(array_key_exists('bonus', $data)) {
            $data['bonus'] = $this->truncateAmount($data['bonus']);
        }
        if(array_key_exists('usedPromo', $data)) {
            $data['usedPromo'] = $this->truncateAmount($data['usedPromo']);
        }

        $data = json_encode($data);

        if($this->api) {
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->function,
                json_encode($this->requestParams->params),
                $data,
                $this->http_status_code,
                null,
                is_array($this->headers) ? json_encode($this->headers) : $this->headers,
                ['player_id' => isset($this->currentPlayer['playerId']) && !empty($this->currentPlayer['playerId']) ? $this->currentPlayer['playerId'] : null],
                false,
                null,
                intval($this->utils->getExecutionTimeToNow()*1000) //costMs
            );
            if($this->wallet_transaction_id) {
                // current_table
                $is_updated = $this->pp_transactions->updateResponseResultId($this->wallet_transaction_id, $response_result_id, $this->transaction_table_name);

                if ($this->api->checkPreviousSeamlessWalletTransactionsTable()) {
                    if (!$is_updated) {
                        // previous_table
                        $this->pp_transactions->updateResponseResultId($this->wallet_transaction_id, $response_result_id, $this->previous_transaction_table_name);
                    }
                }

                if($flag == Response_result::FLAG_NORMAL && $this->utils->getConfig('enable_fast_track_integration')) {
                    $this->sendToFastTrack(); //need to optimize to use queue
                }
            }
        }

        $this->output->set_status_header($this->http_status_code)->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

    public function remove_token() {
        $rule_set = [
            'hash' => 'required',
            'providerId' => 'required',
            'sessionId' => 'required',
            'playerId' => 'required'
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $player_info = $this->currentPlayer;
        if(!empty($player_info)) {
            $token = $this->requestParams->params['token'];
            $success = $this->common_token->deleteToken($token);
            if($success){
                return $this->setResponse(self::RETURN_OK);
            }

            return $this->setResponse(self::ERROR_INTERNAL_ERROR);
        }
        return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
    }

    private function sendRemoteWalletIncreaseZeroAmount($playerId, $walletModel, $gamePlatformId, &$afterBalance){
        $succ=$this->wallet_model->incRemoteWallet($playerId, 0, $gamePlatformId, $afterBalance);
            if(!$succ){
                $remoteErrorCode = $walletModel->getRemoteWalletErrorCode();
                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
                    $succ = true;
                }
            }

        return $succ;
    }

    private function saveFailedTransaction($query_type='insert', $data=[], $where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $this->utils->debug_log("PP SEAMLESS SERVICE API: saveFailedTransaction",$query_type, $table_name, $data, $where);
        $this->original_seamless_wallet_transactions->saveTransactionData($table_name, $query_type, $data, $where);
    }

    private function isFailedTransactionExist($where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $isExisting = $this->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
        $this->utils->debug_log("PP SEAMLESS SERVICE API: isFailedTransactionExist",$table_name, $where, $isExisting);
        return $isExisting;
    }

    private function remoteWalletErrorCode(){
        $this->load->model(['wallet_model']);
        if (method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
            $errorCode = $this->wallet_model->getRemoteWalletErrorCode();
            $this->utils->debug_log("PP SEAMLESS SERVICE API: remoteWalletErrorCode", $errorCode);
            return $errorCode;
        }
        return null;
    }

}
