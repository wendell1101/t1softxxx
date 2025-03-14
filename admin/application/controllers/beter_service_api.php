<?php

use function PHPSTORM_META\map;

 if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * API DOC: https://beterlive.atlassian.net/wiki/external/622952453/YjI2OTMxZjNmODE4NDJkNmJlYzI5YmZlYTRlMzIyMTM#Overview
 */
class Beter_service_api extends BaseController {

    const TXNID_ALREADY_PROCESSED = 1;
    const TXNREVID_ALREADY_PROCESSED =2;
    const TXNREVID_NOT_EXISTING = 3;
    const PARAMETER_ERROR = 4;

    /* 
    * Error from GP
    */
    const INVALID_SESSION_KEY = [
        'error_code' => 422,
        'message' => 'Session key is invalid or expired',
        'code' => "invalid.session.key"
    ];

    const INSUFFICIENT_BALANCE = [
        'error_code' => 422,
        'message' => 'Insufficient player balance',
        'code' => "insufficient.balance"
    ];

    const INVALID_TRANSACTION_ID = [
        'error_code' => 422,
        'message' => 'Unexpected transaction id',
        'code' => "invalid.transaction.id"
    ];

    const INVALID_PLAYER = [
        'error_code' => 422,
        'message' => 'Player is not found',
        'code' => "player.not.found"
    ];

    # examples of wrong behaviour: 
    # cancel before bet
    # bet/cancel after confirm
    const INVALID_CASINO_BEHAVIOUR = [
        'error_code' => 422,
        'message' => 'Unexpected casino logic behaviour',
        'code' => "invalid.casino.behaviour"
    ];

    const INTERNAL_ERROR = [
        'error_code' => 422,
        'message' => 'Internal error',
        'code' => "error.internal"
    ];

    #Amount is less than 0
    const INVALID_AMOUNT = [
        'error_code' => 422,
        'message' => 'Amount is less than 0',
        'code' => "bad.request"
    ];

    # Returned when gameId is not registered on casino side
    # NOTE FROM GP: Optional. (For example, we have noticed that our blackjack tables have all seats occupied and we have temporarily opened another blackjack table. 
    # If you need to reject bets on this new table, if it is not registered on the casino side, apply these processing errors)
    const UNKNOWN_GAME = [
        'error_code' => 422,
        'message' => 'Game id not found',
        'code' => "unknown.game"
    ];

    /* 
    * Default Error Messages
    */
    const SUCCESS = [
        'error_code' => 0,
        'message' => "Success",
        'code' => 0
    ];

    // For special cases like system maintenance and whitelist 
    const INVALID_HASH = [
        'error_code' => 403,
        'message' => 'Forbidden',
        'code' => "error.internal"
    ];
    
    const HTTP_STATUS_CODE_MAP = [
        self::INVALID_SESSION_KEY['code'] => 422,
        self::INSUFFICIENT_BALANCE['code'] => 422,
        self::INVALID_TRANSACTION_ID['code'] => 422,
        self::INVALID_PLAYER['code'] => 422,
        self::INVALID_CASINO_BEHAVIOUR['code'] => 422,
        self::INTERNAL_ERROR['code'] => 422,
        self::INVALID_AMOUNT['code'] => 422,
        self::SUCCESS['code']=>200,
        self::INVALID_HASH['code']=>403
    ];

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_END = 'end';

    const TRANSACTION_TYPE_CANCEL = 'cancel';
    const TRANSACTION_TYPE_REFUND = 'refund';

    const STATUS_SETTLED = 'SETTLED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REFUND = 'REFUND';

    private $headers;
    private $requests;
    private $game_api;
    private $cid;
    private $currency;
    private $sec_key;
    
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $resultCode;

    public $original_seamless_wallet_transactions_table;

    public function __construct() {
        parent::__construct();

    }

    public function index($api, $method) {
        $this->game_api = $this->utils->loadExternalSystemLibObject($api);
        $this->currency = $this->game_api->currency;
        $this->sec_key = $this->game_api->api_key;

        $this->retrieveHeaders();

        $this->requests = new stdClass();
        $this->requests->function = $method;
        $this->requests->params = json_decode(file_get_contents("php://input"), true);

        if(!$this->game_api) {
            return $this->setOutput(self::INVALID_HASH);
        }

        if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
            return $this->setOutput(self::INVALID_HASH);
        }

        $this->cid = $this->game_api->cid;
        $this->CI->load->model('Original_seamless_wallet_transactions', 'og_transaction');
        $this->original_seamless_wallet_transactions_table = $this->game_api->original_transaction_table_name;

        return $this->$method();
    }

    public function getPlayerDetails($token){
        $this->CI->load->model('common_token');

        if(isset($token)){
            $this->currentPlayer =(array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_api->getPlatformCode());
            if(empty($this->currentPlayer)){
                return [];
            }else{
                return $this->currentPlayer;
            }
        }else{
            return [];
        }
    }

    public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');

        if(isset($gameusername)){
            
            $this->currentPlayer = (array) $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(empty($this->currentPlayer)){
                return [];
            }else{
                return $this->currentPlayer;
            }
        }else{
            return [];
        }
    }

    // Called in order to retrieve player details, balance, and verify if the token is valid.
    public function sessionInfo(){  
        if(!isset($this->headers['X-Request-Sign'])){
            return $this->setOutput(self::INVALID_HASH);
        }

        if($this->genXRequestSign() != $this->headers['X-Request-Sign']){
            return $this->setOutput(self::INVALID_HASH);
        }
        
        $this->CI->load->model('common_token');

        $rule_set = [
            "casino" => "required",
            "username" => "required",
            "sessionToken" => "required", 
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        //Check IP 
        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::INVALID_HASH);
        }

        $valid_username = $this->getPlayerByGameUsername($this->requests->params['username']);

        if(empty($valid_username)){
            return $this->setOutput(self::INVALID_PLAYER);
        }
        
        $playerDetails = $this->getPlayerDetails($this->requests->params['sessionToken']);

        if(!empty($playerDetails)) {

            if($this->requests->params['username'] != $playerDetails['game_username']){
                return $this->setOutput(self::INVALID_PLAYER);
            }

            //As per provider if wrong cid must be player.not.found
            if($this->requests->params['casino'] != $this->cid ){
                return $this->setOutput(self::INVALID_PLAYER);
            }

            $data = [
                "username" => $this->requests->params['username'],
                "currency" => $this->game_api->currency,
                "country" => $this->game_api->country,
                "balance" =>  $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($playerDetails['username'])['balance']),
                "displayName" => $this->requests->params['username'],
                'code' => self::SUCCESS['code']
            ];

            return $this->setOutput($data);
        }else{
            return $this->setOutput(self::INVALID_SESSION_KEY);
        }
    }

    //Called when a player makes a bet. Might be sent once or multiple times.
    public function bet(){
        if(!isset($this->headers['X-Request-Sign'])){
            return $this->setOutput(self::INVALID_HASH);
        }

        if($this->genXRequestSign() != $this->headers['X-Request-Sign']){
            return $this->setOutput(self::INVALID_HASH);
        }

        $this->CI->load->model('common_token');

        #gameCode = game round code
        #gameId = game table identifier
        $rule_set = [
            "casino"=> "required",
            "username"=> "required",
            "sessionToken"=> "required",
            "gameCode"=> "required",
            "transactionId"=> "required",
            "amount"=> "required|numeric",
            "gameId"=> "required",
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        //Check IP 
        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::INVALID_HASH);
        }

        $player_info = $this->getPlayerDetails($this->requests->params['sessionToken']);

        if(!empty($player_info)) {
            
            if($this->requests->params['username'] != $player_info['game_username']){
                return $this->setOutput(self::INVALID_PLAYER);
            }

            if($this->requests->params['casino'] != $this->cid ){
                return $this->setOutput(self::INVALID_PLAYER);
            }

            $controller = $this;
            $bet_details  = $controller->requests->params;
            $adjustWallet = [];

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requests->params['amount'] < 0){
                $this->utils->debug_log('BETER ' . __METHOD__ , ' amount is negative');
                return $this->setOutput(self::INVALID_AMOUNT);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details, &$adjustWallet) {
                //check if bet already settled
                //examples of wrong behaviour: bet/cancel after confirm
                //must return invalid.casino.behaviour
                $where2 = array (
                    'game_id' => $bet_details['gameId'],
                    'round_id' =>  $bet_details['gameCode'],
                    'player_id' => $player_info['player_id'],
                    'transaction_id' =>  $bet_details['transactionId'],
                    'status' => self::STATUS_SETTLED
                );
                $existing_settled_transaction = $this->og_transaction->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, $where2);
                $this->utils->debug_log('BETER ', 'existing_settled_transaction: ', $existing_settled_transaction);

                if($existing_settled_transaction){
                    $controller->resultCode = self::INVALID_CASINO_BEHAVIOUR;
                    return false;
                }

                //***start betAfterConfirm() */
                // bet > confirm > bet . the 2nd bet has the same game code 
                $where1 = array (
                    'game_id' => $bet_details['gameId'],
                    'round_id' =>  $bet_details['gameCode'],
                    'player_id' => $player_info['player_id'],
                    'status' => self::STATUS_SETTLED
                );
                $has_settled_transaction = $this->og_transaction->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, $where1);
                $this->utils->debug_log('BETER ', 'has_settled_transaction: ', $has_settled_transaction);

                if($has_settled_transaction){
                    $controller->resultCode = self::INVALID_CASINO_BEHAVIOUR;
                    return false;
                }
                //***end betAfterConfirm() */

                $current_balance = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

                $external_unique_id = $bet_details['gameCode']."-".$bet_details['transactionId'];

                //check if bet already existing
                $existing_transaction_id = $this->og_transaction->isTransactionExist($this->original_seamless_wallet_transactions_table, $external_unique_id);

                if(!empty($existing_transaction_id)){
                    //As per GP return success  when already processed
                    $controller->resultCode = self::SUCCESS;
                    return true;
                }else{

                    #if a Player does not have enough funds for withdrawal, the error “Insufficient funds” must be returned.
                    if($current_balance < $bet_details['amount']){
                        $controller->resultCode = self::INSUFFICIENT_BALANCE;
                        return false;
                    }

                    $transaction_data['bet_amount'] =  $bet_details['amount'];
                    $transaction_data['result_amount'] =  $bet_details['amount'] * -1;
                    $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                    $transaction_data['round_id'] =  $bet_details['gameCode'];
                    $transaction_data['game_id'] = $bet_details['gameId'];
                    $transaction_data['external_unique_id'] = $external_unique_id;

                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_BET, $player_info, $transaction_data);
                    $this->utils->debug_log('BETER ', 'ADJUST_WALLET_DEBIT_TAL: ', $adjustWallet);

                    if($adjustWallet['code'] == self::SUCCESS['code']){
                        return true;
                    }else{
                        return false;
                    }
                }
            });

            $current_balance = !empty($adjustWallet['after_balance']) ? $adjustWallet['after_balance'] : $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

            if($transaction_result){
                $data = array(
                    "transactionId" => $this->requests->params['transactionId'],
                    "balance" => $current_balance,
                    'code' => self::SUCCESS['code']
                );

                return $this->setOutput($data);
            }else{
                switch ($controller->resultCode['code']) {
                    
                    case self::INSUFFICIENT_BALANCE['code']:
                        $data = self::INSUFFICIENT_BALANCE;
                    break;
                    case self::INVALID_AMOUNT['code']:
                        $data = self::INVALID_AMOUNT;
                    break;
                    case self::INVALID_TRANSACTION_ID['code']:
                        $data = self::INVALID_TRANSACTION_ID;
                        break;
                    case self::INVALID_CASINO_BEHAVIOUR['code']:
                        $data = self::INVALID_CASINO_BEHAVIOUR;
                        break;
                    case self::SUCCESS['code']:
                        // echo "why here";
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "balance" => $current_balance,
                            'code' => self::SUCCESS['code']
                        );
                        break;
                    default:
                        $data = self::INTERNAL_ERROR;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::INTERNAL_ERROR);
                }
            }
        }else{
            return $this->setOutput(self::INVALID_SESSION_KEY);
        }

    }

    #Called when the game is over. Finalizes the game. Live game server always sends this request (if there was at least one bet, even canceled).
    #NOTE: transactionid for confirmGame is always unique and will NOT be the same with bet transaction id
    public function confirmGame(){ 
        if(!isset($this->headers['X-Request-Sign'])){
            return $this->setOutput(self::INVALID_HASH);
        }

        if($this->genXRequestSign() != $this->headers['X-Request-Sign']){
            return $this->setOutput(self::INVALID_HASH);
        }

        $this->CI->load->model('common_token');

        $rule_set = [
            "casino" => "required",
            "username" => "required",
            "gameCode" => "required",
            "transactionId" => "required",
            "totalBetAmount" => "required",
            "totalWinAmount" => "required",
            "gameId" => "required",
            "sessionToken" => "required",
            "gameResultUrl" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        //Check IP 
        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::INVALID_HASH);
        }

        //Do not verify session if expired in cancel and win
        //$player_info = $this->getPlayerDetails($this->requests->params['sessionToken']);
        $player_info = $this->getPlayerByGameUsername($this->requests->params['username']);
        
        if(!empty($player_info)) {

            if($this->requests->params['casino'] != $this->cid ){
                return $this->setOutput(self::INVALID_PLAYER);
            }

            $controller = $this;
            $bet_details  = $controller->requests->params;
            $adjustWallet = [];

            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details, &$adjustWallet) {
                $external_unique_id_bet = $bet_details['gameCode']."-".$bet_details['transactionId'];
                $external_unique_id_settled = $bet_details['gameCode']."-".$bet_details['transactionId']."-settled";

                //*** start Check if bet transaction is already existing  
                // $existing_transaction_id_bet = $this->og_transaction->isTransactionExist($this->original_seamless_wallet_transactions_table, $external_unique_id_bet);
                // $this->utils->debug_log('BETER ', 'existing_transaction_id_bet: ', $existing_transaction_id_bet);
                $where = array (
                    'round_id' => $bet_details['gameCode'],
                    'game_id' => $bet_details['gameId'],
                    "status" => self::STATUS_PENDING,
                    "player_id" => $player_info['player_id']
                );
                $existing_transaction_id_bet = $this->og_transaction->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $where);
                $this->utils->debug_log('BETER ', 'existing_transaction_id_bet: ', $existing_transaction_id_bet);
                //*** end Check if bet transaction is already existing  

                //Check if confirmgame transaction is already settled/existing, the bet transaction should be settled as well
                $existing_transaction_id_settled = $this->og_transaction->isTransactionExist($this->original_seamless_wallet_transactions_table, $external_unique_id_settled);
                $this->utils->debug_log('BETER ', 'existing_transaction_id_settled: ', $existing_transaction_id_settled);

                $is_transaction_id_same_with_bet = $this->og_transaction->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, [
                    'transaction_type' => self::TRANSACTION_TYPE_BET,
                    'transaction_id' =>  $bet_details['transactionId'],
                ]);

                if ($is_transaction_id_same_with_bet) {
                    $controller->resultCode = self::INVALID_TRANSACTION_ID;
                    return false;
                }
                
                if($existing_transaction_id_settled){
                    /* $controller->resultCode = self::INVALID_CASINO_BEHAVIOUR;
                    return false; */
                    $controller->resultCode = self::SUCCESS;
                    return true;
                }

                if(empty($existing_transaction_id_bet)){
                    $this->utils->debug_log('BETER ', 'FALSE: ', $existing_transaction_id_bet);
                    $controller->resultCode = self::INVALID_CASINO_BEHAVIOUR;
                    return false;
                }else{
                    if (isset($existing_transaction_id_bet[0]['bet_amount']) && $existing_transaction_id_bet[0]['bet_amount'] != $bet_details['totalBetAmount']) {
                        $controller->resultCode = self::INVALID_CASINO_BEHAVIOUR;
                        return false;
                    }
                    //Get transactions with the same round, game code and player
                    // $where = array (
                    //     'round_id' => $bet_details['gameCode'],
                    //     'game_id' => $bet_details['gameId'],
                    //     "status" => self::STATUS_PENDING,
                    //     "player_id" => $player_info['player_id']
                    // );
                    // $get_bet_transaction = $this->og_transaction->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $where);
                    

                    // $this->utils->debug_log('BETER ', 'CHECK IF BET IS EXISTING: ', $get_bet_transaction);

                    // if(!empty($get_bet_transaction)){

                        $total_bet_amount = $bet_details['totalBetAmount'];
                        $win_amount = $bet_details['totalWinAmount'];

                        $transaction_data['total_bet_amount'] =  $total_bet_amount; 
                        $transaction_data['amount'] =  $win_amount;

                        $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                        $transaction_data['game_id'] = $bet_details['gameId'];
                        $transaction_data['round_id'] = $bet_details['gameCode'];
                        $transaction_data['external_unique_id'] = $external_unique_id_settled;
                        
                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_END, $player_info, $transaction_data);
                        $this->utils->debug_log('BETER ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);

                        if($adjustWallet['code'] == self::SUCCESS['code']){
                            return true;
                        }else{
                            return false;
                        }
                    // }else{
                    //     $controller->resultCode = self::INVALID_TRANSACTION_ID;
                    //     return false;
                    // }
                    
                }
            });

            $current_balance = !empty($adjustWallet['after_balance']) ? $adjustWallet['after_balance'] : $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

            if($transaction_result){
                $data = array(
                    "transactionId" => $this->requests->params['transactionId'],
                    "balance" => $current_balance,
                    'code' => self::SUCCESS['code']
                );

                return $this->setOutput($data);
            }else{
                
                switch ($controller->resultCode['code']) {
                    case self::INVALID_AMOUNT['code']:
                        $data = self::INVALID_AMOUNT;
                        break;
                    case self::INVALID_TRANSACTION_ID['code']:
                        $data = self::INVALID_TRANSACTION_ID;
                        break;
                    case self::INVALID_CASINO_BEHAVIOUR['code']:
                        $data = self::INVALID_CASINO_BEHAVIOUR;
                        break;
                    case self::SUCCESS['code']:
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "balance" => $current_balance,
                            'code' => self::SUCCESS['code']
                        );
                        break;
                    default:
                        $data = self::INTERNAL_ERROR;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::INTERNAL_ERROR);
                }
            }

        }else{
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }
    
    /* Called when the bet was placed, but then canceled due to technical or human error.
    * Examples:
    * - Bet arrived too late. We sent a request to the casino, but the response arrived too late, the dealer already started the game round and started dealing cards.
    * - The dealer made a mistake and dealt the wrong card to a player. His game must be canceled and bets returned.
    */
    public function cancel(){
        if(!isset($this->headers['X-Request-Sign'])){
            return $this->setOutput(self::INVALID_HASH);
        }

        if($this->genXRequestSign() != $this->headers['X-Request-Sign']){
            return $this->setOutput(self::INVALID_HASH);
        }

        $this->CI->load->model('common_token');

        #gameCode = game round code
        #gameId = game table identifier
        $rule_set = [
            "casino"=> "required",
            "username"=> "required",
            "gameCode"=> "required",
            "transactionId"=> "required",
            "amount"=> "required|numeric",
            "sessionToken"=> "required",
            "gameId"=> "required",
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        
        //Check IP 
        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::INVALID_HASH);
        }

        //Do not verify session if expired in cancel and win
        //$player_info = $this->getPlayerDetails($this->requests->params['sessionToken']);
        $player_info = $this->getPlayerByGameUsername($this->requests->params['username']);

        if(!empty($player_info)) {

            if($this->requests->params['casino'] != $this->cid ){
                return $this->setOutput(self::INVALID_PLAYER);
            }

            $controller = $this;
            $bet_details  = $controller->requests->params;
            $adjustWallet = [];

            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details, &$adjustWallet) {
                //*** start Check if already settled, return invalid.casino.behaviour
                $where2 = array (
                    'game_id' => $bet_details['gameId'],
                    'round_id' =>  $bet_details['gameCode'],
                    'player_id' => $player_info['player_id'],
                    // 'transaction_id' =>  $bet_details['transactionId'],
                    'status' => self::STATUS_SETTLED
                );
                $existing_settled_transaction = $this->og_transaction->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, $where2);
                $this->utils->debug_log('BETER ', 'existing_settled_transaction: ', $existing_settled_transaction);

                if($existing_settled_transaction){
                    $controller->resultCode = self::INVALID_CASINO_BEHAVIOUR;
                    return false;
                }
                //*** end Check if already settled, return invalid.casino.behaviour
                
                 //Check if refund transaction is already existing 
                 $where_existing_refund_transaction_id = array (
                    'game_id' => $bet_details['gameId'],
                    'round_id' =>  $bet_details['gameCode'],
                    'transaction_id' =>  $bet_details['transactionId'],
                    'player_id' => $player_info['player_id'],
                    'status' => self::STATUS_REFUND
                );
                $existing_refund_transaction_id = $this->og_transaction->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, $where_existing_refund_transaction_id);
                $this->utils->debug_log('BETER ', 'existing_refund_transaction_id: ', $existing_refund_transaction_id);

                // $existing_transaction_id = $this->og_transaction->isTransactionExist($this->original_seamless_wallet_transactions_table, $external_unique_id);

                if($existing_refund_transaction_id){
                    $controller->resultCode = self::SUCCESS;
                    return true;
                }else{
                    $external_unique_id = $bet_details['gameCode']."-".$bet_details['transactionId'];

                    //Check if bet is pending
                    $where = array (
                        'game_id' => $bet_details['gameId'],
                        'round_id' =>  $bet_details['gameCode'],
                        'player_id' => $player_info['player_id'],
                        'transaction_id' =>  $bet_details['transactionId'],
                        'status' => self::STATUS_PENDING
                    );
                    $existing_pending_transaction = $this->og_transaction->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, $where);
                    $this->utils->debug_log('BETER ', 'existing_pending_transaction: ', $existing_pending_transaction);

                    if($existing_pending_transaction){
                        $transaction_data['amount'] =  $bet_details['amount']; // Return amount
                        $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                        $transaction_data['game_id'] = $bet_details['gameId'];
                        
                        $transaction_data['external_unique_id'] = $external_unique_id."-refund";
                        $transaction_data['round_id'] = $bet_details['gameCode'];
    
                        $this->utils->debug_log('BETER ', 'transaction_data:', $transaction_data);

                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_REFUND, $player_info, $transaction_data);
                        $this->utils->debug_log('BETER ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);
    
                        if($adjustWallet['code'] == self::SUCCESS['code']){
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        $controller->resultCode = self::INVALID_TRANSACTION_ID;
                        return false;
                    }
                    
                }
            });
            
            $current_balance = !empty($adjustWallet['after_balance']) ? $adjustWallet['after_balance'] : $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

            if($transaction_result){
                $data = array(
                    "transactionId" => $this->requests->params['transactionId'],
                    "balance" => $current_balance,
                    'code' => self::SUCCESS['code']
                );

                return $this->setOutput($data);
            }else{
                
                switch ($controller->resultCode['code']) {
                    case self::INVALID_AMOUNT['code']:
                        $data = self::INVALID_AMOUNT;
                    break;
                    case self::INVALID_CASINO_BEHAVIOUR["code"]:
                        $data = self::INVALID_CASINO_BEHAVIOUR;
                        break;
                    case self::INVALID_TRANSACTION_ID["code"]:
                        $data = self::INVALID_TRANSACTION_ID;
                        break;
                    case self::SUCCESS['code']:
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "balance" => $current_balance,
                            'code' => self::SUCCESS['code']
                        );
                        break;
                    default:
                        $data = self::INTERNAL_ERROR;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::INTERNAL_ERROR);
                }
            }
        }else{
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }
   

    private function adjustWallet($transaction_type, $player_info, $extra = []) {

        $this->CI->load->model('wallet_model');

        $return_data = [
            'code' => self::SUCCESS['error_code']
        ];

        $wallet_transaction = [];
        $after_balance = null;
        $game_id = isset($extra['game_id']) ? $extra['game_id'] : null;
        $return_data['before_balance'] = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);
        $uniqueid_of_seamless_service = $this->game_api->getPlatformCode() . '-' . $extra['external_unique_id'];

        $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 
        $this->wallet_model->setExternalGameId($game_id); 

        if($transaction_type== self::TRANSACTION_TYPE_BET){

            $this->utils->debug_log('BETER ', 'transaction type bet');
            $game_amount_to_db_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['bet_amount']);

            $wallet_transaction['amount'] = $extra['bet_amount']; // to save in DB not yet converted
            $wallet_transaction['bet_amount'] = $extra['bet_amount']; // to save in DB not yet converted
            $wallet_transaction['result_amount'] = $extra['result_amount']; 
            
            $wallet_transaction['status'] = self::STATUS_PENDING;

            $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $game_amount_to_db_bet_amount, $after_balance);

            if(!$response) {
                $this->resultCode = self::INTERNAL_ERROR;
                $return_data['code'] = self::INTERNAL_ERROR;
            }

        }else if($transaction_type == self::TRANSACTION_TYPE_END){
            
            $this->utils->debug_log('BETER ', 'transaction type END game');
            $total_win = $this->game_api->gameAmountToDBTruncateNumber($extra['amount']);
            $total_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['total_bet_amount']);

            $result_amount = $extra['amount'] - $extra['total_bet_amount'];

            $wallet_transaction['amount'] =  $extra['amount'];
            $wallet_transaction['result_amount'] =  $result_amount;
            $wallet_transaction['bet_amount'] =  0;
            $wallet_transaction['total_bet_amount'] = isset($extra['total_bet_amount']) ? $extra['total_bet_amount'] : 0;
            $wallet_transaction['status'] = self::STATUS_SETTLED;

            if($total_win >0){
                $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $total_win, $after_balance);
                $this->utils->debug_log('BETER ', 'ADD-AMOUNT: ', $response);
            }
            
            $response = true;

            $where = array (
                'game_id' => $extra['game_id'],
                'round_id' =>  $extra['round_id'],
                'player_id' => $player_info['player_id'],
                'status' => self::STATUS_PENDING
            );

            $update_data = array("status" => self::STATUS_SETTLED);
            $success_update = $this->og_transaction->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);
            
        }else if($transaction_type== self::TRANSACTION_TYPE_REFUND){
            $game_amount_to_db_refund_amount =  $this->game_api->gameAmountToDBTruncateNumber($extra['amount']);
           
            $this->utils->debug_log('BETER ', 'ADJSUT TRANSACTION_TYPE_REFUND: ');

            $wallet_transaction['amount'] =  $extra['amount'];
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['result_amount'] = $extra['amount'];
            $wallet_transaction['status'] = self::STATUS_REFUND;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $game_amount_to_db_refund_amount, $after_balance);
            $this->utils->debug_log('BETER ', 'ADD-AMOUNT: ', $response);

            $return_data['after_balance'] = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);
            
            $wallet_transaction['game_platform_id'] = $this->game_api->getPlatformCode();
            $wallet_transaction['player_id'] = $player_info['player_id'];
            $wallet_transaction['transaction_type'] = $transaction_type;
            $wallet_transaction['transaction_id'] =  $extra['transaction_id'];
            $wallet_transaction['game_id'] =  isset($extra['game_id']) ? $extra['game_id'] : null;
            $wallet_transaction['round_id'] =  $extra['round_id'];
            $wallet_transaction['before_balance'] = $return_data['before_balance'];
            $wallet_transaction['after_balance'] = $return_data['after_balance'];
            $wallet_transaction['start_at'] = date("Y-m-d H:i:s");
            $wallet_transaction['end_at'] = date("Y-m-d H:i:s");
            $wallet_transaction['extra_info'] = isset($extra['extra_info']) ? $extra['extra_info'] : null;
            $wallet_transaction['external_unique_id'] = $extra['external_unique_id'];
            $wallet_transaction['created_at'] = date("Y-m-d H:i:s");
            $wallet_transaction['updated_at'] = date("Y-m-d H:i:s");

            $this->utils->debug_log('BETER ', 'external unique id: ', $wallet_transaction['external_unique_id']);

            #Insert refund data
            $this->wallet_transaction_id = $this->og_transaction->insertTransactionData($this->original_seamless_wallet_transactions_table, $wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;

            #Update original bet transaction to SETTLED
            $response = true;

            $where = array (
                'transaction_id' => $extra['transaction_id'],
                'game_id' => $extra['game_id'],
                'round_id' =>  $extra['round_id'],
                'player_id' => $player_info['player_id'],
                'status' => self::STATUS_PENDING
            );

            $update_data = array("status" => self::STATUS_CANCELLED, "transaction_type" => self::TRANSACTION_TYPE_CANCEL, 'flag_of_updated_result' => 1,);
            $success_update = $this->og_transaction->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);
            
            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }else{
                $return_data['code'] = self::SUCCESS['error_code'];
                return $return_data;
            }
        }

        $return_data['after_balance'] = !empty($after_balance) ? $this->game_api->dBtoGameAmount($after_balance) : $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

        $wallet_transaction['game_platform_id'] = $this->game_api->getPlatformCode();
        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['transaction_id'] =  $extra['transaction_id'];
        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['game_id'] =  $extra['game_id'];
        $wallet_transaction['round_id'] =  $extra['round_id'];
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];
        $wallet_transaction['start_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['end_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['extra_info'] = isset($extra['extra_info']) ? $extra['extra_info'] : null;
        $wallet_transaction['external_unique_id'] = $extra['external_unique_id'];
        $wallet_transaction['created_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['updated_at'] = date("Y-m-d H:i:s");


        if($return_data['code'] == self::SUCCESS['error_code']) {
            $this->wallet_transaction_id = $this->og_transaction->insertTransactionData($this->original_seamless_wallet_transactions_table, $wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;
            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }
        }

        return $return_data;
    }

    private function validateRequest($rule_set) {

        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {

                if(is_array($this->requests->params)){
                    if($rule == 'required' && !array_key_exists($key, $this->requests->params)) {
                        $is_valid = false;
                        $this->utils->debug_log('BETER ' . __METHOD__ , 'missing parameter', $key);
                        break;
                    }
                    if($rule == 'numeric' && !is_numeric($this->requests->params[$key])) {
                        $is_valid = false;

                        $this->utils->debug_log('BETER ' . __METHOD__ , 'not numeric', $key);
                        break;
                    }
                }else{
                    $is_valid = false;

                    $this->utils->debug_log('BETER ' . __METHOD__ , 'pass paramater is not an array', $key);
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

        
        $this->requests->function = $functionName ;
        
        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            return $this->setOutput(self::INTERNAL_ERROR);
        }

    }

    public function retrieveHeaders() {
        $this->headers = getallheaders();
    }

    private function setOutput($data = []) {
                    
        $flag = ($data['code']==self::SUCCESS['code']) ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $httpStatusCode = 200;
        

        if(isset($data['code']) && array_key_exists($data['code'], self::HTTP_STATUS_CODE_MAP)){
            $httpStatusCode = self::HTTP_STATUS_CODE_MAP[$data['code']];
        }

        $httpStatusText = isset($data['message']) ? $data['message'] : 'Success';

        #In case if request processing fails - Wallet API must return JSON response with code 422 with two fields: code and message.
        unset($data['error_code']);

        $data = json_encode($data);

        $fields = array(
            'player_id' => isset($this->currentPlayer['player_id']) ? $this->currentPlayer['player_id'] : NULL
        );

        if($this->game_api) {
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->game_api->getPlatformCode(), #1
                $flag, #2
                $this->requests->function, #3
                json_encode($this->requests->params), #4
                $data, #5
                $httpStatusCode, #6
                $httpStatusText, #7
                is_array($this->headers) ? json_encode($this->headers) : $this->headers, #8
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

    public function genXRequestSign(){  
        $jsonstring = stripslashes($this->sec_key.json_encode($this->requests->params));
        $x_req = hash('sha256', $jsonstring);
        return $x_req;
    }

    public function testinggenXRequestSign(){  
        $jsonstring = stripslashes($this->sec_key.json_encode($this->requests->params));
        $x_req = hash('sha256', $jsonstring);
        echo $x_req;
    }

}