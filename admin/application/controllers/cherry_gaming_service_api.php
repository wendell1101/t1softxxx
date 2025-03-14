<?php

use function PHPSTORM_META\map;

 if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Cherry_gaming_service_api extends BaseController {

    const TXNID_ALREADY_PROCESSED = 1;
    const TXNREVID_ALREADY_PROCESSED =2;
    const TXNREVID_NOT_EXISTING = 3;
    const PARAMETER_ERROR = 4;

    const SUCCESS = [
        'ErrorCode' => 0,
        'ErrorDescription' => "Success"
    ];

    const SYSTEM_MAINTENACE = [
        'ErrorCode' => 1009,
        'ErrorDescription' => "System in maintenance"
    ];
    
    const INVALID_PARAMETER = [
        'ErrorCode' => 1020,
        'ErrorDescription' => "Invalid parameter"
    ];

    const ERROR_CODE_IP_NOT_AUTHORIZED = [
        'ErrorCode' => 1024,
        'ErrorDescription' => "Service not available"
    ];

    const ERROR_CODE_SERVER_BUSY = [
        'ErrorCode' => 1026,
        'ErrorDescription' => "Server busy"
    ];

    const TRANSACTION_ALREADY_PROCESSED = [
        'ErrorCode' => 0, 
        'ErrorDescription' => "Duplicate TxnID"
    ];

    const REV_TRANSACTION_ALREADY_PROCESSED = [
        'ErrorCode' => 0, 
        'ErrorDescription' => "Transaction Rev ID is already cancelled"
    ];

    const TRANSACTION_NOT_EXISTING = [
        'ErrorCode' => 0, 
        'ErrorDescription' => "Not existing TxnID"
    ];
    
    
    const INVALID_PLAYER = [
        'ErrorCode' => 2001,
        'ErrorDescription' => "Player name does not exist in operator's platform"
    ];

    const INSUFFICIENT_PLAYER_BALANCE = [
        'ErrorCode' => 2002,
        'ErrorDescription' => "Player does not have enough balance to place the bet"
    ];

    const INVALID_CURRENCY = [
        'ErrorCode' => 2003,
        'ErrorDescription' => "Currency does not match with record in operator's platform"
    ];

    const PLAYER_ACCOUNT_LOCKED = [
        'ErrorCode' => 2004,
        'ErrorDescription' => "Player account is locked in operator’s platform"
    ];

    const GENERAL_ERROR = [
        'ErrorCode' => 2999,
        'ErrorDescription' => "Genereal Error"
    ];

    const HTTP_STATUS_CODE_MAP = [
        self::SUCCESS['ErrorCode']=>200,
        self::SYSTEM_MAINTENACE['ErrorCode']=>503,
        self::INVALID_PARAMETER['ErrorCode']=>400,
        self::ERROR_CODE_IP_NOT_AUTHORIZED['ErrorCode']=>401,
        self::ERROR_CODE_SERVER_BUSY['ErrorCode']=>503,
        self::TRANSACTION_ALREADY_PROCESSED['ErrorCode']=>400,
        self::TRANSACTION_NOT_EXISTING['ErrorCode']=>400,
        self::INVALID_PLAYER['ErrorCode']=>400,
        self::INSUFFICIENT_PLAYER_BALANCE['ErrorCode']=>400,
        self::INVALID_CURRENCY['ErrorCode']=>400,
        self::PLAYER_ACCOUNT_LOCKED['ErrorCode']=>400,
        self::GENERAL_ERROR['ErrorCode']=>400,
    ];

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_WIN = 'win';
    const TRANSACTION_TYPE_LOSS = 'loss';
    const TRANSACTION_TYPE_CANCEL = 'cancel';
    const TRANSACTION_TYPE_REFUND = 'refund';

    const STATUS_SETTLED = 'SETTLED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REFUND = 'REFUND';

    private $headers;
    private $requests;
    private $game_api;
    private $currency;

    private $lobby_code;
    private $api_key;
    private $api_url;
    
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $resultCode;
    private $specialCode; 

    public function __construct() {
        parent::__construct();
    }

    public function index($api, $method) {
        $this->game_api = $this->utils->loadExternalSystemLibObject($api);

        $this->lobby_code = $this->game_api->lobby_code;
        $this->api_key = $this->game_api->api_key;
        $this->api_url = $this->game_api->api_url;
        $this->currency = $this->game_api->currency;

        $this->retrieveHeaders();

        $this->requests = new stdClass();
        $this->requests->function = $method;
        $this->requests->params = json_decode(file_get_contents("php://input"), true);

        if(!$this->game_api) {
            return $this->setOutput(self::SYSTEM_MAINTENACE);
        }

        if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
            return $this->setOutput(self::SYSTEM_MAINTENACE);
        }

        $this->CI->load->model('cherry_gaming_seamless_wallet_transactions', 'cg_transactions');
        $this->cg_transactions->tableName = $this->game_api->original_transaction_table_name;

        return $this->$method();
    }

    /**
     * CGAPI sends this action to operator server to query the current balance of a player.
     * CGAPI will not retry this action.
     */
    public function SwPlayerBal(){
        $this->CI->load->model('common_token');

        $rule_set = [
            "PlayerName" => "required",
            "Currency" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $check_currency = $this->checkCurrency($this->requests->params['Currency']);
        if(!$check_currency){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::INVALID_CURRENCY);
        }

        $player_info = $this->getPlayer($this->requests->params['PlayerName']);

        if(!empty($player_info)) {
            $data = [
                "PlayerName" => $this->requests->params['PlayerName'],
                "Currency" => $this->requests->params['Currency'],
                "Amount" =>  $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']),
                "ErrorCode" => self::SUCCESS['ErrorCode']
            ];

            return $this->setOutput($data);
        }else{
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }

    /**
     * CGAPI sends this action to operators when players bet. Operators have to confirm if these betting actions are allowed.
     */
    public function SwPlayerBet(){
        
        $this->CI->load->model('common_token');

        $rule_set = [
            "PlayerName" => "required",
            "Currency" => "required",
            "Amount" => "required",
            "TxnID" => "required",
            "PlayerIP" => "required",
            "GameType" => "required",
            "BetSource" => "required",
            "HostID" => "required",
            "GameID" => "required",
            "BetDetail" => "required",
            "DateTime" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->game_api->validateWhiteIP()){
            $this->utils->debug_log('CG ' . __METHOD__ , ' IP is not allowed');
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $check_currency = $this->checkCurrency($this->requests->params['Currency']);
        if(!$check_currency){
            $this->utils->debug_log('CG ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::INVALID_CURRENCY);
        }

        $player_info = $this->getPlayer($this->requests->params['PlayerName']);

        if(!empty($player_info)) {
            $controller = $this;
            $bet_details  = $controller->requests->params;

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requests->params['Amount'] < 0){
                $this->utils->debug_log('CG ' . __METHOD__ , ' amount is negative');
                return $this->setOutput(self::INVALID_PARAMETER);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $current_balance = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);
                $existing_transaction_id = $this->cg_transactions->searchByExternalTransactionByTransactionId($bet_details['TxnID']);

                if(!empty($existing_transaction_id)){
                    $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                    return false;
                }else{

                    #if a Player does not have enough funds for withdrawal, the error “Insufficient funds” must be returned.
                    if($current_balance < $bet_details['Amount']){
                        $controller->resultCode = self::INSUFFICIENT_PLAYER_BALANCE;
                        return false;
                    }

                    $transaction_data['bet_amount'] =  $bet_details['Amount'];
                    $transaction_data['result_amount'] =  $bet_details['Amount'] * -1;
                    $transaction_data['currency'] =  $bet_details['Currency'];
                    $transaction_data['transaction_id'] =  $bet_details['TxnID'];
                    $transaction_data['game_id'] = $bet_details['HostID'];
                    
                    $transaction_data['external_unique_id'] = $bet_details['TxnID']."-".$bet_details['GameID'];
                    $transaction_data['round_id'] = $bet_details['GameID'];
                    $transaction_data['extra_info'] = json_encode($bet_details['BetDetail']);

                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_BET, $player_info, $transaction_data);
                    $this->utils->debug_log('CG ', 'ADJUST_WALLET_DEBIT_TAL: ', $adjustWallet);

                    if($adjustWallet['code'] == self::SUCCESS['ErrorCode']){
                        return true;
                    }else{
                        return false;
                    }
                }
            });

            if($transaction_result){
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']),
                    "ErrorCode" => self::SUCCESS['ErrorCode']
                );

                return $this->setOutput($data);
            }else{
                switch ($controller->resultCode['ErrorCode']) {
                    case self::INSUFFICIENT_PLAYER_BALANCE['ErrorCode']:
                        $data = self::INSUFFICIENT_PLAYER_BALANCE;
                    break;
                    case self::INVALID_PARAMETER['ErrorCode']:
                        $data = self::INVALID_PARAMETER;
                    break;
                    case self::TRANSACTION_ALREADY_PROCESSED['ErrorCode']: 
                        $data = self::TRANSACTION_ALREADY_PROCESSED;
                        break;
                    default:
                        $data = self::ERROR_CODE_SERVER_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SERVER_BUSY);
                }
            }

        }else{
            
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }

     /**
     * CGAPI sends this action to operators if players win in a game. 
     * If a player bets multiple times and/or in different bet types in the same game, 
     *  CGAPI will send only 1 action to the operator while the amount is the overall win amount of the player in the same game.
     */
    public function SwPlayerWin(){
        
        $this->CI->load->model('common_token');

        $rule_set = [
            "PlayerName" => "required",
            "Currency" => "required",
            "TxnID" => "required",
            "GameType" => "required",
            "PayoutTime" => "required",
            "HostID" => "required",
            "GameID" => "required",
            "DateTime" => "required",
            "Amount" => "required"
            
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $check_currency = $this->checkCurrency($this->requests->params['Currency']);
        if(!$check_currency){
            $this->utils->debug_log('CG ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::INVALID_CURRENCY);
        }

        $player_info = $this->getPlayer($this->requests->params['PlayerName']);

        if(!empty($player_info)) {
            $controller = $this;
            $bet_details  = $controller->requests->params;

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requests->params['Amount'] < 0){
                $this->utils->debug_log('CG ' . __METHOD__ , ' amount is negative');
                $controller->specialCode = self::PARAMETER_ERROR;
                return $this->setOutput(self::INVALID_PARAMETER);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $existing_transaction_id = $this->cg_transactions->searchByExternalTransactionByTransactionId($bet_details['TxnID']);

                if(!empty($existing_transaction_id)){
                    $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                    $controller->specialCode = self::TXNID_ALREADY_PROCESSED;
                    return false;
                }else{
                    //$bet_details['GameID'] is round_id
                    $get_bet_transaction = $this->cg_transactions->getBetTransactions($bet_details['GameID'], self::STATUS_PENDING, $player_info['player_id']);


                    if($get_bet_transaction!=false){

                        // $bet_transaction_result = $controller->game_api->queryBetRecord($get_bet_transaction->transaction_id);

                        // $total_win = 0;
                        // foreach($bet_transaction_result['resultArr']['BetRecordList'] as $trans){
                        //     if($trans['PayoutAmount']>0){
                        //         $total_win += $trans['PayoutAmount'];
                        //     }
                        // }


                        $total_bet_amount = $get_bet_transaction->total_bet_amount;
                        // $bet_and_win_amount = $total_win; //$bet_details['Amount'];
                        $bet_and_win_amount = $bet_details['Amount'];
                       
                        $transaction_data['total_bet_amount'] = $total_bet_amount;
                        $transaction_data['amount'] =  $bet_and_win_amount; // Payout amount
                        $transaction_data['currency'] =  $bet_details['Currency'];
                        $transaction_data['transaction_id'] =  $bet_details['TxnID'];
                        $transaction_data['game_id'] = $bet_details['HostID'];
                        
                        $transaction_data['external_unique_id'] = $bet_details['TxnID']."-".$bet_details['GameID'];
                        $transaction_data['round_id'] = $bet_details['GameID'];
                        $transaction_data['end_at'] = $bet_details['PayoutTime'];

                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_WIN, $player_info, $transaction_data);
                        $this->utils->debug_log('CG ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);

                        if($adjustWallet['code'] == self::SUCCESS['ErrorCode']){
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        $controller->resultCode = self::TRANSACTION_NOT_EXISTING;
                        $controller->specialCode = self::TXNREVID_NOT_EXISTING;
                        return false;
                    }
                    
                }
            });

            if($transaction_result){
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']),
                    "ErrorCode" => self::SUCCESS['ErrorCode'],
                );

                return $this->setOutput($data);
            }else{
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance'])
                );

                switch ($controller->specialCode) {
                    case self::PARAMETER_ERROR:
                        $data = self::INVALID_PARAMETER;
                    break;
                    case self::TXNID_ALREADY_PROCESSED: 
                        $data['ErrorCode'] = self::TRANSACTION_ALREADY_PROCESSED['ErrorCode'];
                        $data['ErrorDescription'] = self::TRANSACTION_ALREADY_PROCESSED['ErrorDescription'];
                    break;
                    case self::TXNREVID_NOT_EXISTING:
                        $data['ErrorCode'] = self::TRANSACTION_NOT_EXISTING['ErrorCode'];
                        $data['ErrorDescription'] = self::TRANSACTION_NOT_EXISTING['ErrorDescription'];
                    break;
                    default:
                        $data = self::ERROR_CODE_SERVER_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SERVER_BUSY);
                }
            }

        }else{
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }
    
    /**
     * CGAPI sends this action to operators if players lose in a game. If a player bets multiple times and/or in different bet types in the same game, CGAPI will send only 1 action to the operator while the amount is the overall loss amount of the player in the same game.
     * For failed response, CGAPI will retry sending this action for 2 hours until a response is received.
     */
    public function SwPlayerLoss(){
        
        $this->CI->load->model('common_token');

        $rule_set = [
            "PlayerName" => "required",
            "Currency" => "required",
            "TxnID" => "required",
            "GameType" => "required",
            "PayoutTime" => "required",
            "HostID" => "required",
            "GameID" => "required",
            "DateTime" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $check_currency = $this->checkCurrency($this->requests->params['Currency']);
        if(!$check_currency){
            $this->utils->debug_log('CG ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::INVALID_CURRENCY);
        }

        $player_info = $this->getPlayer($this->requests->params['PlayerName']);

        if(!empty($player_info)) {
            $controller = $this;
            $bet_details  = $controller->requests->params;

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $existing_transaction_id = $this->cg_transactions->searchByExternalTransactionByTransactionId($bet_details['TxnID']);

                if(!empty($existing_transaction_id)){
                    $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                    $controller->specialCode = self::TXNID_ALREADY_PROCESSED;
                    return false;
                }else{
                    //$bet_details['GameID'] is round_id
                    // $get_bet_transaction = $this->cg_transactions->searchByExternalTransactionByRoundIdAndStatus($bet_details['GameID'], self::STATUS_PENDING, $player_info['player_id']);
                    $get_bet_transaction = $this->cg_transactions->getBetTransactions($bet_details['GameID'], self::STATUS_PENDING, $player_info['player_id']);

                    $this->utils->debug_log('CG ' . __METHOD__ , $get_bet_transaction);
                    if($get_bet_transaction!=false){
                        $total_bet_amount = $get_bet_transaction->total_bet_amount;

                        $transaction_data['currency'] =  $bet_details['Currency'];
                        $transaction_data['transaction_id'] =  $bet_details['TxnID'];
                        $transaction_data['game_id'] = $bet_details['HostID'];
                        $transaction_data['total_bet_amount'] = $total_bet_amount;
                        
                        $transaction_data['external_unique_id'] = $bet_details['TxnID']."-".$bet_details['GameID'];
                        $transaction_data['round_id'] = $bet_details['GameID'];
                        $transaction_data['end_at'] = $bet_details['PayoutTime'];

                        $transaction_data['result_amount'] =  $total_bet_amount * -1;

                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_LOSS, $player_info, $transaction_data);
                        $this->utils->debug_log('CG ', 'ADJUST_WALLET_SETTLED_GAME_LOSS: ', $adjustWallet);

                        if($adjustWallet['code'] == self::SUCCESS['ErrorCode']){
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        $controller->resultCode = self::TRANSACTION_NOT_EXISTING;
                        $controller->specialCode = self::TXNREVID_NOT_EXISTING;
                        return false;
                    }
                    
                }
            });

            if($transaction_result){
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']),
                    "ErrorCode" => self::SUCCESS['ErrorCode'],
                );

                return $this->setOutput($data);
            }else{
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance'])
                );

                switch ($controller->specialCode) {
                    case self::PARAMETER_ERROR:
                        $data = self::INVALID_PARAMETER;
                    break;
                    case self::TXNID_ALREADY_PROCESSED: 
                        $data['ErrorCode'] = self::TRANSACTION_ALREADY_PROCESSED['ErrorCode'];
                        $data['ErrorDescription'] = self::TRANSACTION_ALREADY_PROCESSED['ErrorDescription'];
                    break;
                    case self::TXNREVID_NOT_EXISTING:
                        $data['ErrorCode'] = self::TRANSACTION_NOT_EXISTING['ErrorCode'];
                        $data['ErrorDescription'] = self::TRANSACTION_NOT_EXISTING['ErrorDescription'];
                    break;
                    default:
                        $data = self::ERROR_CODE_SERVER_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SERVER_BUSY);
                }
            }

        }else{
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }

    /**
     * CGAPI sends this action when a bet is canceled. A canceled bet may be due to the failed response of SwPlayerBet from operators or the game is canceled.
     */
    public function SwBetCancel(){
        $this->CI->load->model('common_token');

        $rule_set = [
            "PlayerName" => "required",
            "Currency" => "required",
            "Amount" => "required",
            "TxnID" => "required", //A unique ID of this action
            "TxnRevID" => "required",  //TxnID of correlated SwPlayerBet action
            "GameType" => "required",
            "GameID" => "required",
            "CancelReasonID" => "required", //1 - Game cancelled, 0 - Other reasons
            "HostID" => "required",          
            "DateTime" => "required",
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->game_api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $check_currency = $this->checkCurrency($this->requests->params['Currency']);
        if(!$check_currency){
            $this->utils->debug_log('CG ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::INVALID_CURRENCY);
        }

        $player_info = $this->getPlayer($this->requests->params['PlayerName']);

        if(!empty($player_info)) {
            $controller = $this;
            $bet_details  = $controller->requests->params;

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requests->params['Amount'] < 0){
                $this->utils->debug_log('CG ' . __METHOD__ , ' amount is negative');
                $controller->specialCode = self::PARAMETER_ERROR;
                return $this->setOutput(self::INVALID_PARAMETER);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $existing_transaction_id = $this->cg_transactions->searchByExternalTransactionByTransactionId($bet_details['TxnID']);
                // $existing_pending_transaction_rev_id = $this->cg_transactions->searchByExternalTransactionByTransactionIdStatus($bet_details['TxnRevID'], self::STATUS_PENDING);
                $existing_pending_transaction_rev_id = $this->cg_transactions->getBetTransactions($bet_details['GameID'], self::STATUS_PENDING, $player_info['player_id']);
                $existing_cancelled_transaction_rev_id = $this->cg_transactions->searchByExternalTransactionByTransactionIdStatus($bet_details['TxnRevID'], self::STATUS_CANCELLED);

                if(!empty($existing_transaction_id)){
                    $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                    $controller->specialCode = self::TXNID_ALREADY_PROCESSED;
                    return false;
                }else{

                    if(!empty($existing_cancelled_transaction_rev_id)){
                        $controller->resultCode = self::REV_TRANSACTION_ALREADY_PROCESSED;
                        $controller->specialCode = self::TXNREVID_ALREADY_PROCESSED;
                        return false;
                    }

                    if($existing_pending_transaction_rev_id!=false){
                        $total_bet_amount = $existing_pending_transaction_rev_id->total_bet_amount;

                        $transaction_data['total_bet_amount'] = $total_bet_amount;
                        $transaction_data['amount'] =  $bet_details['Amount']; // Return amount
                        $transaction_data['currency'] =  $bet_details['Currency'];
                        $transaction_data['transaction_id'] =  $bet_details['TxnID'];
                        $transaction_data['transaction_id_to_cancel'] =  $bet_details['TxnRevID'];
                        $transaction_data['game_id'] = $bet_details['HostID'];
                        
                        $transaction_data['external_unique_id'] = $bet_details['TxnID']."-".$bet_details['GameID'];
                        $transaction_data['round_id'] = $bet_details['GameID'];
    
                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_REFUND, $player_info, $transaction_data);
                        $this->utils->debug_log('CG ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);
    
                        if($adjustWallet['code'] == self::SUCCESS['ErrorCode']){
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        $controller->resultCode = self::TRANSACTION_NOT_EXISTING;
                        $controller->specialCode = self::TXNREVID_NOT_EXISTING;
                        return false;
                    }
                    
                }
            });

            if($transaction_result){
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']),
                    "ErrorCode" => self::SUCCESS['ErrorCode'],
                );

                return $this->setOutput($data);
            }else{
                $data = array(
                    "PlayerName" => $this->requests->params['PlayerName'],
                    "Currency" =>   $this->requests->params['Currency'],
                    "Amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance'])
                );

                switch ($controller->specialCode) {
                    case self::PARAMETER_ERROR:
                        $data = self::INVALID_PARAMETER;
                    break;
                    case self::TXNID_ALREADY_PROCESSED: 
                        $data['ErrorCode'] = self::TRANSACTION_ALREADY_PROCESSED['ErrorCode'];
                        $data['ErrorDescription'] = self::TRANSACTION_ALREADY_PROCESSED['ErrorDescription'];
                    break;
                    case self::TXNREVID_NOT_EXISTING:
                        $data['ErrorCode'] = self::TRANSACTION_NOT_EXISTING['ErrorCode'];
                        $data['ErrorDescription'] = self::TRANSACTION_NOT_EXISTING['ErrorDescription'];
                    break;
                    case self::TXNREVID_ALREADY_PROCESSED:
                        $data['ErrorCode'] = self::REV_TRANSACTION_ALREADY_PROCESSED['ErrorCode'];
                        $data['ErrorDescription'] = self::REV_TRANSACTION_ALREADY_PROCESSED['ErrorDescription'];
                    break;
                    default:
                   exit;
                        $data = self::ERROR_CODE_SERVER_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SERVER_BUSY);
                }
            }

        }else{
            return $this->setOutput(self::INVALID_PLAYER);
        }
    }

    private function adjustWallet($transaction_type, $player_info, $extra = []) {

        $this->CI->load->model('wallet_model');

        $return_data = [
            'code' => self::SUCCESS['ErrorCode']
        ];

        $wallet_transaction = [];

        $return_data['before_balance'] = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

        $uniqueid_of_seamless_service=$this->game_api->getPlatformCode().'-'.$extra['transaction_id'];       
        $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service); 

        if($transaction_type== self::TRANSACTION_TYPE_BET){

            $this->utils->debug_log('CG ', 'transaction type bet');
            $game_amount_to_db_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['bet_amount']);

            $wallet_transaction['amount'] = $game_amount_to_db_bet_amount;
            $wallet_transaction['result_amount'] = $extra['result_amount']; 
            $wallet_transaction['bet_amount'] = $game_amount_to_db_bet_amount; 
            $wallet_transaction['status'] = self::STATUS_PENDING;

            $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $game_amount_to_db_bet_amount);

            if(!$response) {
                $this->resultCode = self::INVALID_PARAMETER;
                $return_data['code'] = self::INVALID_PARAMETER;
            }

        }else if($transaction_type == self::TRANSACTION_TYPE_WIN){
            
            $this->utils->debug_log('CG ', 'transaction type win');
            $total_win = $this->game_api->gameAmountToDBTruncateNumber($extra['amount']);
            $total_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['total_bet_amount']);

            $result_amount = $total_win - $total_bet_amount;

            $wallet_transaction['amount'] =  $total_win;
            $wallet_transaction['result_amount'] =  $result_amount;
            $wallet_transaction['bet_amount'] =  0;
            $wallet_transaction['total_bet_amount'] = $total_bet_amount;
            $wallet_transaction['status'] = self::STATUS_SETTLED;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $total_win);

            $this->utils->debug_log('CG ', 'increase wallet: ', $response);

            if(!$response && $total_win != 0) {
                $return_data['code'] = self::INVALID_PARAMETER;
            }else if(!$response && $total_win == 0){
                $response = true;
            }

            $update = array(
                'status' => self::STATUS_SETTLED
            );
            $this->cg_transactions->updateTransaction($update, $player_info['player_id'], $extra['round_id'], self::STATUS_PENDING);
        }else if($transaction_type == self::TRANSACTION_TYPE_LOSS){
            $this->utils->debug_log('CG ', 'transaction type lose');

            $total_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['total_bet_amount']);
            
            $wallet_transaction['amount'] =  0;
            $wallet_transaction['result_amount'] =  $extra['result_amount'];;
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['total_bet_amount'] = $total_bet_amount;
            $wallet_transaction['status'] = self::STATUS_SETTLED;
            
            $update = array(
                'status' => self::STATUS_SETTLED
            );
            $this->cg_transactions->updateTransaction($update, $player_info['player_id'], $extra['round_id'], self::STATUS_PENDING);
           
            $response = true;
        }else if($transaction_type== self::TRANSACTION_TYPE_REFUND){
            $this->utils->debug_log('CG ', 'transaction type cancel');
            $total_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['total_bet_amount']);
            $game_amount_to_db_refund_amount =  $this->game_api->gameAmountToDBTruncateNumber($extra['amount']);
           
            $wallet_transaction['amount'] = $game_amount_to_db_refund_amount;
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['result_amount'] =  $game_amount_to_db_refund_amount;
            $wallet_transaction['status'] = self::STATUS_REFUND;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $game_amount_to_db_refund_amount);

            $return_data['after_balance'] = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);
            
            $wallet_transaction['total_bet_amount'] = 0;
            $wallet_transaction['game_platform_id'] = $this->game_api->getPlatformCode();
            $wallet_transaction['player_id'] = $player_info['player_id'];
            $wallet_transaction['currency'] =  $extra['currency'];
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

            #Insert refund data
            $this->wallet_transaction_id = $this->cg_transactions->refundTransaction($wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;

            #Update original bet transaction to SETTLED
            $update = array(
                'transaction_type' => self::TRANSACTION_TYPE_CANCEL,
                'status' => self::STATUS_CANCELLED,
                'flag_of_updated_result' => 1,
                'total_bet_amount' => $total_bet_amount
            );
            $this->cg_transactions->updateTransactionByTransactionId($update, $player_info['player_id'], $extra['round_id'], $extra['transaction_id_to_cancel'], self::STATUS_PENDING);

            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }else{
                $return_data['code'] = self::SUCCESS['ErrorCode'];
                return $return_data;
            }
        }

        $return_data['after_balance'] = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($player_info['username'])['balance']);

        $wallet_transaction['game_platform_id'] = $this->game_api->getPlatformCode();
        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['transaction_id'] =  $extra['transaction_id'];
        $wallet_transaction['transaction_type'] = $transaction_type;
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

        if($return_data['code'] == self::SUCCESS['ErrorCode']) {
            $this->wallet_transaction_id = $this->cg_transactions->insertTransaction($wallet_transaction);
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
                        $this->utils->debug_log('Evenbet ' . __METHOD__ , 'missing parameter', $key);
                        break;
                    }
                    if($rule == 'numeric' && !is_numeric($this->requests->params[$key])) {
                        $is_valid = false;

                        $this->utils->debug_log('Evenbet ' . __METHOD__ , 'not numeric', $key);
                        break;
                    }
                }else{
                    $is_valid = false;

                    $this->utils->debug_log('Evenbet ' . __METHOD__ , 'pass paramater is not an array', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    public function retrieveHeaders() {
        $this->headers = getallheaders();
    }

    public function getPlayer($gameusername){
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

    public function getPlayerGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');

        if(isset($uid)){
            $player_info = (array) $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($player_id, $this->api->getPlatformCode());
            if(empty($player_info)){
                return $player_info['game_username'];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function checkCurrency($currency){
        if($currency!=$this->currency){
            return false;
        }else{
            return true;
        }
    }

    public function checkSignature($generated, $pass_sign){
        if($generated == $pass_sign){
            return true;
        }else{
            return false;
        }
    }

    public function preProcessRequest($functionName="", $rule_set = []) {

        $this->requests->function = $functionName ;

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            
            return $this->setOutput(self::INVALID_PARAMETER);
        }

    }

    private function setOutput($data = []) {
        $flag = $data['ErrorCode'] == self::SUCCESS['ErrorCode'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $httpStatusCode = 200;
        $httpStatusText = "Success";

        if(isset($data['ErrorCode']) && array_key_exists($data['ErrorCode'], self::HTTP_STATUS_CODE_MAP)){
            $httpStatusCode = self::HTTP_STATUS_CODE_MAP[$data['ErrorCode']];
        }

        $data = json_encode($data);

        $fields = array(
            'player_id' => isset($this->currentPlayer['player_id']) ? $this->currentPlayer['player_id'] : 0
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

}