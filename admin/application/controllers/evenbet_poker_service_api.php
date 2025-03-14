<?php

use function PHPSTORM_META\map;

 if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Evenbet_poker_service_api extends BaseController {

    const SUCCESS = [
        'errorCode' => 0,
        'errorDescription' => "Completed successfully"
    ];

    const TRANSACTION_ALREADY_PROCESSED = [
        'errorCode' => 12, //to display 0 in result
        'errorDescription' => "Transaction already processed"
    ];

    const ERROR_CODE_INVALID_SIGNATURE = [
        'errorCode' => 1,
        'errorDescription' => "Invalid signature"
    ];

    const ERROR_CODE_PLAYER_NOT_FOUND = [
        'errorCode' => 2,
        'errorDescription' => "Player not found"
    ];

    const INSUFFICIENT_FUNDS = [
        'errorCode' => 3,
        'errorDescription' => "Insufficient funds"
    ];

    const ERROR_CODE_PARAMETER_ERROR = [
        'errorCode' => 4,
        'errorDescription' => "Invalid request params"
    ];

    const REF_TRANSACTION_NOT_EXIST = [
        'errorCode' => 5,
        'errorDescription' => "Reference transaction does not exist"
    ];

    const REF_TRANSACTION_HAS_INCOMPATIBLE_DATA = [
        'errorCode' => 6,
        'errorDescription' => "Reference transaction has incompatible data"
    ];

    const ERROR_CODE_IP_NOT_AUTHORIZED = [
        'errorCode' => 7,
        'errorDescription' => "IP is not authorized"
    ];

    const ERROR_CODE_SERVER_ERROR = [
        'errorCode' => 8,
        'errorDescription' => "Server error"
    ];

    const ERROR_CODE_UNDER_MAINTENANCE = [
        'errorCode' => 9,
        'errorDescription' => "Under maintenance"
    ];

    const ERROR_CODE_SYSTEM_BUSY = [
        'errorCode' => 10,
        'errorDescription' => "System busy. Please try again."
    ];

    const ERROR_CODE_METHOD_NOT_FOUND = [
        'errorCode' => 404,
        'errorDescription' => "Page not found."
    ];

    const HTTP_STATUS_CODE_MAP = [
        self::SUCCESS['errorCode']=>200,
        self::ERROR_CODE_IP_NOT_AUTHORIZED['errorCode']=>401,
        self::ERROR_CODE_INVALID_SIGNATURE['errorCode']=>400,
        self::ERROR_CODE_PLAYER_NOT_FOUND['errorCode']=>400,
        self::INSUFFICIENT_FUNDS['errorCode']=>400,
        self::ERROR_CODE_PARAMETER_ERROR['errorCode']=>400,
        self::REF_TRANSACTION_NOT_EXIST['errorCode']=>400,
        self::REF_TRANSACTION_HAS_INCOMPATIBLE_DATA['errorCode']=>400,
        self::ERROR_CODE_SERVER_ERROR['errorCode']=>500,
        self::ERROR_CODE_UNDER_MAINTENANCE['errorCode']=>503,
        self::ERROR_CODE_SYSTEM_BUSY['errorCode']=>503
    ];

    const TRANSACTION_TYPE_BET = 'debit';
    const TRANSACTION_TYPE_PAYOUT = 'credit';
    const TRANSACTION_TYPE_REFUND = 'refund';

    const STATUS_SETTLED = 'SETTLED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REFUND = 'REFUND';

    const METHOD_GET_BALANCE = 'GetBalance';
    const METHOD_GET_CASH = 'GetCash';
    const METHOD_RETURN_CASH = 'ReturnCash';
    const METHOD_ROLLBACK = 'Rollback';

    private $requestHeaders;
    private $requestParams;
    private $api;
    private $currency;
    private $client_id;
    private $default_secret_key;
    private $seamless_wallet_secret;
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $resultCode;
    private $headers;
    private $conversion_rate;

    public function __construct() {
        parent::__construct();
        $this->headers = getallheaders();
    }

    public function index($api, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($api);

        $this->client_id = $this->api->client_id;
        $this->default_secret_key = $this->api->default_secret_key;
        $this->seamless_wallet_secret = $this->api->seamless_wallet_secret;
        $this->currency = $this->api->currency;
        $this->conversion_rate = $this->api->conversion_rate;

        $this->requestParams = new stdClass();
        $this->requestParams->function = $method;
        $this->requestParams->params = json_decode(file_get_contents("php://input"), true);

        if(!$this->api) {
            return $this->setOutput(self::ERROR_CODE_UNDER_MAINTENANCE);
        }

        $this->CI->load->model('evenbet_poker_seamless_wallet_transactions', 'evenbet_transactions');
        $this->evenbet_transactions->tableName = $this->api->original_transaction_table_name;

        if(!method_exists($this, $method)) {
            $this->requestHeaders = $this->input->request_headers();
            $this->utils->debug_log('EvenBet ' . __METHOD__ , $method . ' method not allowed');
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

        if(!method_exists($this, $method)) {
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

        return $this->$method();
    }

    public function transact(){
        if($this->requestParams->params['method'] == self::METHOD_GET_BALANCE){
            $this->getBalance();
        }else if($this->requestParams->params['method'] == self::METHOD_GET_CASH){
            $this->getCash();
        }else if($this->requestParams->params['method'] == self::METHOD_RETURN_CASH){
            $this->returnCash();
        }else if($this->requestParams->params['method'] == self::METHOD_ROLLBACK){
            $this->rollback();
        }else{
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }
    }

    /**
     * The request is made when the user enters the system.
     *
     * IMPORTANT NOTE: Game Provider display amount is in cents (1/100), we need to send money in cents
     */
    public function getBalance(){
        $this->CI->load->model('common_token');

        $rule_set = [
            "method" => "required",
            "userId" => "required",
            "currency" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if($this->api->isMaintenance() || $this->api->isDisabled()) {
            return $this->setOutput(self::ERROR_CODE_UNDER_MAINTENANCE);
        }

        if(!$this->api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $generated_signature = $this->generate_seamless_signature($this->requestParams->params);
        $header_signature = $this->headers['Sign'];

        $this->utils->debug_log('Evenbet seamless generated signature' . __METHOD__ , $generated_signature);

        #sign. The request signature must match the parameters passed. In case it does not, then the error “Invalid signature” has to be returned.
        $check_signature = $this->checkSignature($generated_signature, $header_signature);
        if(!$check_signature){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , 'signature is not valid');
            return $this->setOutput(self::ERROR_CODE_INVALID_SIGNATURE);
        }

        $check_currency = $this->checkCurrency($this->requestParams->params['currency']);
        if(!$check_currency){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

        $player_info = $this->getPlayer($this->requestParams->params['userId']);

        if(!empty($player_info)) {
            $data = [
                "balance" =>  $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                "errorCode" => self::SUCCESS['errorCode'],
                "errorDescription" => self::SUCCESS['errorDescription'],
            ];

            return $this->setOutput($data);
        }else{
            return $this->setOutput(self::ERROR_CODE_PLAYER_NOT_FOUND);
        }
    }

    /**
     * A request to withdraw money from the user is executed when the player:
    * - takes a seat at a table
    * - pays for tournament participation
    * - pays additional tournament fees (re-buy, add-on)
    *
    * If a correct message with an error (errorCode) is received, the request is not going to be resent.
    * If one of the following events happens:
    * - Timeout occurs when attempting to make a request
    * - Server side error (4xx, 5xx)
    * - Received response cannot be interpreted (a text message is received instead of JSON, no errorCode sent)
    * Then the request is going to be resent through a certain interval. The number of repetitions and intervals' length can be specified.
    * By default, the request will be resent 1 time again 1 second after the error message is received.
     */
    public function getCash(){
        $rule_set = [
            "method" => "required",
            "userId" => "required",
            "amount" => "required|numeric",
            "currency" => "required",
            "transactionId" => "required"
        ];

        #request parameters, marked as Mandatory, must be passed in the request. If the parameters are not passed, then the error “Invalid request params” is returned.
        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if($this->api->isMaintenance() || $this->api->isDisabled()) {
            return $this->setOutput(self::ERROR_CODE_UNDER_MAINTENANCE);
        }

        if(!$this->api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $generated_signature = $this->generate_seamless_signature($this->requestParams->params);
        $header_signature = $this->headers['Sign'];

        #sign. The request signature must match the parameters passed. In case it does not, then the error “Invalid signature” has to be returned.
        $check_signature = $this->checkSignature($generated_signature, $header_signature);
        if(!$check_signature){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , 'signature is not valid');
            return $this->setOutput(self::ERROR_CODE_INVALID_SIGNATURE);
        }

        $check_currency = $this->checkCurrency($this->requestParams->params['currency']);
        if(!$check_currency){
            #currency should have a valid ISO-code used in your system. If not, then the error “Invalid request params” must be returned.
            $this->utils->debug_log('Evenbet ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

        $player_info = $this->getPlayer($this->requestParams->params['userId']);

        #userId must exist. If a player does not exist, then the error “Player not found” must be returned.
        if(!empty($player_info)) {

            $controller = $this;
            $bet_details  = $controller->requestParams->params;

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requestParams->params['amount'] < 0){
                $this->utils->debug_log('Evenbet ' . __METHOD__ , ' amount is negative');
                return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $current_balance = $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']);
                $existing_transaction_id = $this->evenbet_transactions->searchByExternalTransactionByTransactionId($bet_details['transactionId']);

                if(!empty($existing_transaction_id)){
                    $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                    return false;
                }else{

                    #if a Player does not have enough funds for withdrawal, the error “Insufficient funds” must be returned.
                    if($current_balance < $bet_details['amount']){
                        $controller->resultCode = self::INSUFFICIENT_FUNDS;
                        return false;
                    }

                    $transaction_data['bet_amount'] =  $bet_details['amount'];
                    $transaction_data['currency'] =  $bet_details['currency'];
                    $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                    $transaction_data['token'] = isset($bet_details['sessionId']) ? $bet_details['sessionId'] : null;
                    $transaction_data['game_id'] = isset($bet_details['tableId']) ? $bet_details['tableId'] : null;
                    $transaction_data['tournament_id'] = isset($bet_details['tournamentId']) ? $bet_details['tournamentId'] : null;
                    $transaction_data['transaction_type'] = self::TRANSACTION_TYPE_BET; // $bet_details['transactionType']
                    $transaction_data['transaction_sub_type'] = isset($bet_details['transactionSubType']) ? $bet_details['transactionSubType'] : null;
                    $transaction_data['tournament_buy_in'] = isset($bet_details['tournamentBuyIn']) ? $bet_details['tournamentBuyIn'] : null;
                    $transaction_data['tournament_entryFee'] = isset($bet_details['tournamentEntryFee']) ? $bet_details['tournamentEntryFee'] : null;
                    $transaction_data['tournament_bounty_knockout'] = isset($bet_details['tournamentBountyKnockout']) ? $bet_details['tournamentBountyKnockout'] : null;

                    $transaction_data['external_unique_id'] = $bet_details['transactionId']."-withdraw";
                    $transaction_data['round_id'] = $bet_details['transactionId'];

                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_BET, $player_info, $transaction_data);
                    $this->utils->debug_log('Evenbet ', 'ADJUST_WALLET_DEBIT_TAL: ', $adjustWallet);

                    if($adjustWallet['code'] == self::SUCCESS['errorCode']){
                        return true;
                    }else{
                        return false;
                    }
                }
            });

            if($transaction_result){
                $data = array(
                    "balance" => $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                    "errorCode" => self::SUCCESS['errorCode'],
                    "errorDescription" => self::SUCCESS['errorDescription'],
                );

                return $this->setOutput($data);
            }else{
                #If the error “Insufficient funds” or “Transaction already processed” occurs, it is required to return all parameters.
                #If other errors occur, you should only return the parameters errorCode and errorDescription.
                switch ($controller->resultCode['errorCode']) {
                    case self::INSUFFICIENT_FUNDS['errorCode']:
                        $data = array(
                            "balance" => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                            'errorCode' => self::INSUFFICIENT_FUNDS['errorCode'],
                            'errorDescription' => self::INSUFFICIENT_FUNDS['errorDescription']
                        );
                    break;
                    case self::ERROR_CODE_PARAMETER_ERROR['errorCode']:
                        $data = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                    case self::TRANSACTION_ALREADY_PROCESSED['errorCode']:
                        #if a transaction with passed transactionId has already been handled on your side, then it is required to return errorCode = 0 with the comment “Transaction already processed”.
                        #You should not process the transaction for the second time.
                        $data = array(
                            "balance" => $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                            'errorCode' => self::SUCCESS['errorCode'],
                            'errorDescription' => self::TRANSACTION_ALREADY_PROCESSED['errorDescription']
                        );
                        break;
                    default:
                        $data = self::ERROR_CODE_SYSTEM_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SYSTEM_BUSY);
                }
            }

        }else{
            return $this->setOutput(self::ERROR_CODE_PLAYER_NOT_FOUND);
        }
    }

    /**
     * A request to deposit money to the user's account is made when the player:
     *
     * - Stands up from a table
     * - Receives a tournament prize
     * - Receives a refund for buy-in in a cancelled tournament
     *
     * In case an error is received, the request is going to be resent. The request will be sent in 30 seconds intervals through 2 days.
     * 
     * IMPORTANT NOTE: If game started and then set to maintenance, returnCash should be allowed as long as the IP is whitelisted 
     */
    public function returnCash(){
        $rule_set = [
            "method" => "required",
            "userId" => "required",
            "amount" => "required|numeric",
            "currency" => "required",
            "transactionId" => "required"
        ];

        #request parameters, marked as Mandatory, must be passed in the request. If the parameters are not passed, then the error “Invalid request params” is returned.
        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $generated_signature = $this->generate_seamless_signature($this->requestParams->params);
        $header_signature = $this->headers['Sign'];

        #sign. The request signature must match the parameters passed. In case it does not, then the error “Invalid signature” has to be returned.
        $check_signature = $this->checkSignature($generated_signature, $header_signature);
        if(!$check_signature){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , 'signature is not valid');
            return $this->setOutput(self::ERROR_CODE_INVALID_SIGNATURE);
        }

        #currency should have a valid ISO-code used in your system. If not, then the error “Invalid request params” must be returned.
        $check_currency = $this->checkCurrency($this->requestParams->params['currency']);
        if(!$check_currency){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

        $player_info = $this->getPlayer($this->requestParams->params['userId']);

        #userId must exist. If a player does not exist, then the error “Player not found” must be returned.
        if(!empty($player_info)) {

            $controller = $this;
            $bet_details  = $controller->requestParams->params;

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requestParams->params['amount'] < 0){
                $this->utils->debug_log('Evenbet ' . __METHOD__ , ' amount is negative');
                return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $existing_transaction_id = $this->evenbet_transactions->searchByExternalTransactionByTransactionId($bet_details['transactionId']);

                if(!empty($existing_transaction_id)){
                    $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                    return false;
                }else{
                    $transaction_data['amount'] =  $bet_details['amount'];
                    $transaction_data['currency'] =  $bet_details['currency'];
                    $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                    $transaction_data['token'] = isset($bet_details['sessionId']) ? $bet_details['sessionId'] : null;
                    $transaction_data['game_id'] = isset($bet_details['tableId']) ? $bet_details['tableId'] : null;
                    $transaction_data['tournament_id'] = isset($bet_details['tournamentId']) ? $bet_details['tournamentId'] : null;
                    $transaction_data['transaction_type'] = self::TRANSACTION_TYPE_PAYOUT; // $bet_details['transactionType']
                    $transaction_data['transaction_sub_type'] = isset($bet_details['transactionSubType']) ? $bet_details['transactionSubType'] : null;
                    $transaction_data['tournament_buy_in'] = isset($bet_details['tournamentBuyIn']) ? $bet_details['tournamentBuyIn'] : null;
                    $transaction_data['tournament_entryFee'] = isset($bet_details['tournamentEntryFee']) ? $bet_details['tournamentEntryFee'] : null;
                    $transaction_data['tournament_bounty_knockout'] = isset($bet_details['tournamentBountyKnockout']) ? $bet_details['tournamentBountyKnockout'] : null;

                    $transaction_data['external_unique_id'] = $bet_details['transactionId']."-deposit";
                    $transaction_data['round_id'] = $bet_details['transactionId'];

                    $transaction_data['to_update'] = true;

                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_PAYOUT, $player_info, $transaction_data);
                    $this->utils->debug_log('Evenbet ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);

                    if($adjustWallet['code'] == self::SUCCESS['errorCode']){
                        return true;
                    }else{
                        return false;
                    }
                }
            });

            if($transaction_result){
                $data = array(
                    "balance" => $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                    "errorCode" => self::SUCCESS['errorCode'],
                    "errorDescription" => self::SUCCESS['errorDescription'],
                );

                return $this->setOutput($data);
            }else{
                #If the error “Transaction already processed” appeared, it is required to return all parameters. For other errors, please return errorCode and errorDescription parameters only.
                switch ($controller->resultCode['errorCode']) {
                    case self::ERROR_CODE_PARAMETER_ERROR['errorCode']:
                        $data = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                    case self::TRANSACTION_ALREADY_PROCESSED['errorCode']:
                        #if a transaction with passed transactionId has already been handled on your side, then it is required to return errorCode = 0 with the comment “Transaction already processed”.
                        #You should not process the transaction for the second time.
                        $data = array(
                            "balance" => $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                            'errorCode' => self::SUCCESS['errorCode'],
                            'errorDescription' => self::TRANSACTION_ALREADY_PROCESSED['errorDescription']
                        );
                    break;
                    default:
                        $data = self::ERROR_CODE_SYSTEM_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SYSTEM_BUSY);
                }
            }

        }else{
            return $this->setOutput(self::ERROR_CODE_PLAYER_NOT_FOUND);
        }
    }

    /**
     * A request to rollback the transaction is executed when:
    * - there was an error of crediting funds to our system
    * - there was an unrecognizable error from an external system in response to getCash query
    *
    * Rollback transaction is executed only if the corresponding setting is set to "true" in the configuration of your Seamless Wallet.
    * The default condition is "false", the rollback transaction is activated only if the client is technically capable to make rollbacks.
    *
    * IMPORTANT NOTE: If game started and then set to maintenance, rollback should be allowed as long as the IP is whitelisted 
     */
    public function rollback(){
        $rule_set = [
            "method" => "required",
            "userId" => "required",
            "amount" => "required|numeric",
            "currency" => "required",
            "transactionId" => "required",
            "referenceTransactionId" => "required"
        ];

        #request parameters, marked as Mandatory, must be passed in the request. If the parameters are not passed, then the error “Invalid request params” is returned.
        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $generated_signature = $this->generate_seamless_signature($this->requestParams->params);
        $header_signature = $this->headers['Sign'];

        #sign. The request signature must match the parameters passed. In case it does not, then the error “Invalid signature” has to be returned.
        $check_signature = $this->checkSignature($generated_signature, $header_signature);
        if(!$check_signature){
            $this->utils->debug_log('Evenbet ' . __METHOD__ , 'signature is not valid');
            return $this->setOutput(self::ERROR_CODE_INVALID_SIGNATURE);
        }

        $check_currency = $this->checkCurrency($this->requestParams->params['currency']);
        if(!$check_currency){
            #currency should have a valid ISO-code used in your system. If not, then the error “Invalid request params” must be returned.
            $this->utils->debug_log('Evenbet ' . __METHOD__ , ' currency not allowed');
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

        $player_info = $this->getPlayer($this->requestParams->params['userId']);

        #userId must exist. If a player does not exist, then the error “Player not found” must be returned.
        if(!empty($player_info)) {

            $controller = $this;
            $bet_details  = $controller->requestParams->params;

            #amount must always be a positive integer. If not, then the error “Invalid request params” must be returned.
            if($this->requestParams->params['amount'] < 0){
                $this->utils->debug_log('Evenbet ' . __METHOD__ , ' amount is negative');
                return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
            }

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_details) {
                $round_reference_transaction = $this->evenbet_transactions->searchByExternalTransactionByTransactionIdAndStatus( $bet_details['referenceTransactionId'], self::STATUS_SETTLED);
                $round_refund_bet_transaction = $this->evenbet_transactions->searchByExternalTransactionByTransactionIdAndStatus($bet_details['transactionId'], self::STATUS_REFUND);

                #if reference transaction does not exist, then the error “Reference transaction does not exist” must be returned.
                if(empty($round_reference_transaction)){
                    if(!empty($round_refund_bet_transaction)){
                        $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                        return false;
                    }

                    $this->utils->debug_log('Evenbet ' . __METHOD__ , ' Reference is NOT existing');
                    $controller->resultCode = self::REF_TRANSACTION_NOT_EXIST;
                    return false;
                }else{
                    $this->utils->debug_log('Evenbet ' . __METHOD__ , ' Reference is existing');

                    #if amount, currency of userId of reference transaction are different from the data of rollback transaction, then the error “Reference transaction has incompatible data” must be returned.
                    $game_username_from_DB = $this->getPlayerGameUsername($round_reference_transaction[0]['player_id']);

                    if($game_username_from_DB != false){
                        if($game_username_from_DB != $bet_details['userId']){
                            $controller->resultCode = self::REF_TRANSACTION_HAS_INCOMPATIBLE_DATA;
                            return false;
                        }
                    }

                    if($round_reference_transaction[0]['amount'] != $bet_details['amount'] || $round_reference_transaction[0]['currency'] != $bet_details['currency'] ){
                        $controller->resultCode = self::REF_TRANSACTION_HAS_INCOMPATIBLE_DATA;
                        return false;
                    }

                    if(!empty($round_refund_bet_transaction)){
                        $controller->resultCode = self::TRANSACTION_ALREADY_PROCESSED;
                        return false;
                    }else{
                        if (str_contains($round_reference_transaction[0]['external_unique_id'], '-withdraw')) { 
                            $transaction_data['amount'] =  $bet_details['amount'];
                            $transaction_data['currency'] =  $bet_details['currency'];
                            $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                            $transaction_data['token'] = isset($bet_details['sessionId']) ? $bet_details['sessionId'] : null;
                            $transaction_data['game_id'] = isset($bet_details['tableId']) ? $bet_details['tableId'] : null;
                            $transaction_data['tournament_id'] = isset($bet_details['tournamentId']) ? $bet_details['tournamentId'] : null;
                            $transaction_data['transaction_type'] = self::TRANSACTION_TYPE_REFUND; // $bet_details['transactionType']
                            $transaction_data['transaction_sub_type'] = isset($bet_details['transactionSubType']) ? $bet_details['transactionSubType'] : null;
                            $transaction_data['tournament_buy_in'] = isset($bet_details['tournamentBuyIn']) ? $bet_details['tournamentBuyIn'] : null;
                            $transaction_data['tournament_entryFee'] = isset($bet_details['tournamentEntryFee']) ? $bet_details['tournamentEntryFee'] : null;
                            $transaction_data['tournament_bounty_knockout'] = isset($bet_details['tournamentBountyKnockout']) ? $bet_details['tournamentBountyKnockout'] : null;

                            $transaction_data['external_unique_id'] = $bet_details['referenceTransactionId']."-refund";
                            $transaction_data['round_id'] = $bet_details['referenceTransactionId'];

                            $transaction_data['to_update'] = true;

                            $adjustWallet = $controller->adjustWallet(self::TRANSACTION_TYPE_REFUND, $player_info, $transaction_data);
                            $this->utils->debug_log('Evenbet ', 'ADJUST_WALLET_CREDIT_TAL: ', $adjustWallet);

                            if($adjustWallet['code'] == self::SUCCESS['errorCode']){
                                return true;
                            }else{
                                return false;
                            }
                        }else{
                            $controller->resultCode = self::REF_TRANSACTION_HAS_INCOMPATIBLE_DATA;
                            return false;
                        }
                        
                    }
                }


            });

            if($transaction_result){
                $data = array(
                    "balance" => $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                    "errorCode" => self::SUCCESS['errorCode'],
                    "errorDescription" => self::SUCCESS['errorDescription'],
                );

                return $this->setOutput($data);
            }else{
                switch ($controller->resultCode['errorCode']) {
                    case self::ERROR_CODE_PARAMETER_ERROR['errorCode']:
                        $data = self::ERROR_CODE_PARAMETER_ERROR;
                        break;
                    case self::REF_TRANSACTION_NOT_EXIST['errorCode']:
                        $data = self::REF_TRANSACTION_NOT_EXIST;
                        break;
                    case self::REF_TRANSACTION_HAS_INCOMPATIBLE_DATA['errorCode']:
                        $data = self::REF_TRANSACTION_HAS_INCOMPATIBLE_DATA;
                        break;
                    case self::TRANSACTION_ALREADY_PROCESSED['errorCode']:
                        #if a transaction with passed transactionId has already been handled on your side, then it is required to return errorCode = 0 with the comment “Transaction already processed”.
                        #You should not process the transaction for the second time.
                        $data = array(
                            "balance" => $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']),
                            'errorCode' => self::SUCCESS['errorCode'],
                            'errorDescription' => self::TRANSACTION_ALREADY_PROCESSED['errorDescription']
                        );
                        break;
                    default:
                        $data = self::ERROR_CODE_SYSTEM_BUSY;
                    break;
                }

                if(!empty($controller->resultCode)){
                    return $this->setOutput($data);
                }else{
                    return $this->setOutput(self::ERROR_CODE_SYSTEM_BUSY);
                }
            }

        }else{
            return $this->setOutput(self::ERROR_CODE_PLAYER_NOT_FOUND);
        }
    }

    private function adjustWallet($transaction_type, $player_info, $extra = []) {

        $return_data = [
            'code' => self::SUCCESS['errorCode']
        ];

        $wallet_transaction = [];

        $return_data['before_balance'] = $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']);

        if($extra['transaction_type'] == self::TRANSACTION_TYPE_BET){

            $this->utils->debug_log('Evenbet ', 'transaction type bet');
            $game_amount_to_db_bet_amount = $this->api->gameAmountToDBTruncateNumber($extra['bet_amount']);

            $wallet_transaction['amount'] = $extra['bet_amount']; //to save amount not yet converted
            $wallet_transaction['bet_amount'] = $extra['bet_amount']; //to save amount not yet converted
            $wallet_transaction['transaction_type'] = self::TRANSACTION_TYPE_BET;
            $wallet_transaction['status'] = self::STATUS_SETTLED;

            $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $game_amount_to_db_bet_amount);

            if(!$response) {
                $this->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                $return_data['code'] = self::ERROR_CODE_PARAMETER_ERROR;
            }

        }else if($extra['transaction_type'] == self::TRANSACTION_TYPE_PAYOUT){
            $game_amount_to_db_payout_amount = $this->api->gameAmountToDBTruncateNumber($extra['amount']);
            $payout_amount = $extra['amount'];

            $wallet_transaction['amount'] = $payout_amount;
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['result_amount'] = $payout_amount;
            $wallet_transaction['win_amount'] = $payout_amount;
            $wallet_transaction['status'] = self::STATUS_SETTLED;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $game_amount_to_db_payout_amount);

            $this->utils->debug_log('Evenbet ', 'ADD_AMOUNT_TAL: ', $response);

            if(!$response && $game_amount_to_db_payout_amount != 0) {
                $this->utils->debug_log('Evenbet ', 'ADD_AMOUNT_TAL: ', 'PAYOUT AMOUNT IS NOT ZERO', 'response:', $response);
                $return_data['code'] = self::ERROR_CODE_PARAMETER_ERROR;
            }else if(!$response && $game_amount_to_db_payout_amount == 0){
                $this->utils->debug_log('Evenbet ', 'ADD_AMOUNT_TAL: ', 'PAYOUT AMOUNT IS ZERO', 'response:', $response);
                $response = true;
            }

            // $this->evenbet_transactions->updateTransaction($extra['transaction_id'], self::STATUS_PENDING);
        }else if($extra['transaction_type'] == self::TRANSACTION_TYPE_REFUND){
            $game_amount_to_db_refund_amount =  $this->api->gameAmountToDBTruncateNumber($extra['amount']);
            $refund_amount = $extra['amount'];

            $wallet_transaction['amount'] = $refund_amount;
            $wallet_transaction['status'] = self::STATUS_REFUND;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $game_amount_to_db_refund_amount);

            $return_data['after_balance'] = $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']);

            $wallet_transaction['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
            $wallet_transaction['token'] = $extra['token'];
            $wallet_transaction['player_id'] = $player_info['player_id'];
            $wallet_transaction['currency'] =  $extra['currency'];
            $wallet_transaction['transaction_type'] = $extra['transaction_type'];
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
            $wallet_transaction['tournament_id'] = $extra['tournament_id'];
            $wallet_transaction['transaction_sub_type'] = $extra['transaction_sub_type'];
            $wallet_transaction['tournament_buy_in'] = $extra['tournament_buy_in'];
            $wallet_transaction['tournament_entryFee'] = $extra['tournament_entryFee'];
            $wallet_transaction['tournament_bounty_knockout'] = $extra['tournament_bounty_knockout'];

            #Insert refund data
            $this->wallet_transaction_id = $this->evenbet_transactions->refundTransaction($wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;

            #Update original bet transaction to refund
            $this->evenbet_transactions->updateOriginalBetToRefundStatus($extra['round_id'], self::STATUS_SETTLED); //Update BET status

            $this->utils->debug_log('Evenbet ', 'ADJUST_WALLET_REFUND refundTransaction', $this->wallet_transaction_id);

            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }else{
                $return_data = array(
                    'balance'=> $this->api->queryPlayerBalance($player_info['username'])['balance'],
                    'errorCode' => self::SUCCESS['errorCode'],
                    'errorDescription' => self::SUCCESS['errorDescription'],
                    'code' => self::SUCCESS['errorCode']
                );

                return $return_data;
            }
        }

        $return_data['after_balance'] = $this->api->dBtoGameAmount($this->api->queryPlayerBalance($player_info['username'])['balance']);

        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['token'] = $extra['token'];
        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['currency'] =  $extra['currency'];
        $wallet_transaction['transaction_type'] = $extra['transaction_type'];
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
        $wallet_transaction['tournament_id'] = $extra['tournament_id'];
        $wallet_transaction['transaction_sub_type'] = $extra['transaction_sub_type'];
        $wallet_transaction['tournament_buy_in'] = $extra['tournament_buy_in'];
        $wallet_transaction['tournament_entryFee'] = $extra['tournament_entryFee'];
        $wallet_transaction['tournament_bounty_knockout'] = $extra['tournament_bounty_knockout'];

        if($return_data['code'] == self::SUCCESS['errorCode']) {

            $this->wallet_transaction_id = $this->evenbet_transactions->insertTransaction($wallet_transaction);
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
                if(is_array($this->requestParams->params)){
                    if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                        $is_valid = false;
                        $this->utils->debug_log('Evenbet ' . __METHOD__ , 'missing parameter', $key);
                        break;
                    }
                    if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
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

    public function getPlayer($uid){
        $this->CI->load->model('game_provider_auth');

        if(isset($uid)){
            $this->currentPlayer = (array) $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($uid, $this->api->getPlatformCode());
            if(empty($this->currentPlayer)){
                return false;
            }else{
                return $this->currentPlayer;
            }
        }else{
            return false;
        }
    }

    public function getPlayerGameUsername($player_id){
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

        $this->requestParams->function = $functionName ;

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }

    }

    private function setOutput($data = []) {
        $flag = $data['errorCode'] == self::SUCCESS['errorCode'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $httpStatusCode = 200;
        $httpStatusText = "Success";

        if(isset($data['errorCode']) && array_key_exists($data['errorCode'], self::HTTP_STATUS_CODE_MAP)){
            $httpStatusCode = self::HTTP_STATUS_CODE_MAP[$data['errorCode']];
            $httpStatusText = $data['errorDescription'];
        }

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

    public function generate_seamless_signature($params){
        // Step 1. Gathering the required data
        $SECRET_KEY = $this->seamless_wallet_secret; // Your secret key
        $jsonMessage = json_encode($params); // JSON string

        // Step 2. Adding a secret key to the string and forming a signature using SHA256 algorithm
        $sign  = hash('sha256', $jsonMessage . $SECRET_KEY);

        return $sign;
    }

    public function gen_signature(){
        $params = $this->requestParams->params;

        $SECRET_KEY = $this->seamless_wallet_secret; // Your secret key
        $jsonMessage = json_encode($params); // JSON string

        // Step 2. Adding a secret key to the string and forming a signature using SHA256 algorithm
        $sign  = hash('sha256', $jsonMessage . $SECRET_KEY);

        echo $sign;
    }

    public function gen_signature_default_key(){
        $params = $this->requestParams->params;

         #Step 2. Delete the parameter 'clientId' from array with query parameters
         if (array_key_exists('clientId', $params)) {
            unset($params['clientId']);
        }

        #Step 3. Sort the parameters
        $this->sortArray($params);

         // Step 4. Concatenate the parameters into a string
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($params));
        $paramString = implode('', iterator_to_array($iterator));

         // Step 5. Add a secret key to the string
        $paramString = $paramString . $this->default_secret_key;

        $this->CI->utils->debug_log('EvenBet Poker paramString: (' . __FUNCTION__ . ')', $paramString);

        // Step 6. Generate a signature using the SHA256 algorithm
        $sign  = hash('sha256', $paramString);

        $this->CI->utils->debug_log('EvenBet Poker hash: (' . __FUNCTION__ . ')', $sign);

        echo $sign;
    }

    public function sortArray(&$array, $sortFlags = SORT_REGULAR){
        if (!is_array($array)) {
            return false;
        }

        // Sort array by parameter name
        return ksort($array, $sortFlags);
      }
}