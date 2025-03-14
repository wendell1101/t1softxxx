<?php

use function PHPSTORM_META\map;

 if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/../../../submodules/core-lib/application/libraries/third_party/jwt_v6/jwt.php';

/**
 * API DOC: https://docs.betsy.gg/#/about/integration_process
 *
 * Seamless Wallet API (Endpoint)
 * The Operator must implement the following API Endpoints.
 * user data    /beter_sports_service_api/{gamePlatformId}/user/profile
 * balance      /beter_sports_service_api/{gamePlatformId}/user/balance
 * place bet    /beter_sports_service_api/{gamePlatformId}/payment/bet
 * settle       /beter_sports_service_api/{gamePlatformId}/payment/bet
 * unsettle       /beter_sports_service_api/{gamePlatformId}/payment/bet
 * rollback       /beter_sports_service_api/{gamePlatformId}/payment/bet
 */

class Beter_sports_service_api extends BaseController {

    const BET_TYPE_1 = 1; //place bet or unsettle bet
    const BET_TYPE_2 = 2; //settle bet
    const BET_TYPE_3 = 3; //rollback bet

    const TRANSACTION_BET = 'bet';
    const TRANSACTION_SETTLE = 'settle';
    const TRANSACTION_RESETTLE = 'resettle';
    const TRANSACTION_UNSETTLE = 'unsettle';
    const TRANSACTION_ROLLBACK = 'rollback';

    const RESULT_WON = "won";
    const RESULT_LOST = "lose";

    const SUCCESS = 0;

    /*
    * Error Code
    * https://docs.betsy.gg/#/additional_information/error_codes
    */
    const REQ_SIGN_INVALID = [
        'code' => 1,
        'message' => 'Request signature is invalid'
    ];

    const INVALID_TOKEN = [
        'code' => 3,
        'message' => 'An invalid token'
    ];

    const TOKEN_EXPIRED = [
        'code' => 4,
        'message' => 'The token is out of date'
    ];

    const REQ_DATA_INVALID = [
        'code' => 5,
        "message" => 'A request data validation error'
    ];

    const NOT_ENOUGH_BALANCE = [
        "code" => 7,
        "message" => 'Not enough money',
        "http_status_code" => 400
    ];

    const BET_ALREADY_PROCESSED = [
        'code' => 8,
        "message" => 'The bet has already been processed'
    ];

    const USER_BLOCKED = [
        'code' => 9,
        "message" => 'The user is blocked'
    ];

    const USER_NOT_VERIFIED = [
        'code' => 15,
        "message" => 'The user is not verified'
    ];

    const SYSTEM_ERROR = [
        'code' => 100,
        "message" => 'System error. This error triggers the Rollback'
    ];


    private $headers;
    private $requests;
    private $game_api;
    private $cid;
    public $partner_secret;

    private $currentPlayer = [];

    public $original_seamless_wallet_transactions_table;
    public $currency;
    public $resultCode;

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions'));        
    }

    public function index($api, $module, $method) {
        $this->game_api = $this->utils->loadExternalSystemLibObject($api);
        $this->partner_secret = $this->game_api->partner_secret;
        $this->cid = $this->game_api->cid;
        $this->currency = $this->game_api->currency;

        $this->utils->debug_log('BETSY ' . __METHOD__ , 'partner secret', $this->partner_secret);

        $this->requests = new stdClass();
        $this->requests->function = $method;
        $this->requests->params = json_decode(file_get_contents("php://input"), true);

        $this->retrieveHeaders();

        if(!$this->game_api) {
            $error_response = self::SYSTEM_ERROR;
            $error_response['message'] = "Invalid game api.";
            return $this->setOutput($error_response);
        }

        if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
            $error_response = self::SYSTEM_ERROR;
            $error_response['message'] = "Game is on maintenance.";
            return $this->setOutput($error_response);
        }

        $this->original_seamless_wallet_transactions_table = $this->game_api->original_transaction_table;

        return $this->$method();
    }

    public function helper_service($method){
        $this->retrieveHeaders();
        
        $this->game_api = $this->utils->loadExternalSystemLibObject(BETER_SPORTS_SEAMLESS_GAME_API);
        $this->partner_secret = $this->game_api->partner_secret;

        if($method == "encrypt"){
            $request = json_decode(file_get_contents("php://input"), true);# json format
            $token = $this->generateJwtToken($request);
            echo ($token);
        } else if($method == "decrypt"){
            $request = file_get_contents('php://input'); #text format
            $result = json_encode($this->decodeJwtToken($request));
            echo($result);
        } else {
            echo "Invalid request. Please try again!!!";
        }
    }

    protected function generateJwtToken($payload)
    {
        $jwt = new JWT;
        $generated_jwt_token = $jwt->encode($payload,$this->partner_secret,'HS256');
        return $generated_jwt_token;
    }

    protected function decodeJwtToken($payload)
    {
        $jwt = new JWT;
        $json = $jwt->decode($payload,$this->partner_secret);
        return $json;
    }

    /**
     * HTTP message validation includes the following steps:
     * Step 1. Get x-sign-jws HTTP header.
     * Step 2. Get BASE64URL HTTP body.
    * Step 3. Put the generated BASE64URL string into the Payload section of the x-sign-jws string.
    * Step 4. Validate JWS. Use PARTNER_SECRET as a hash generation key in signature.
     */
    public function validateJWT(){
        // $jws[0] - header, $jws[1] - payload, $jws[2] - signature
        if(isset($this->headers['X-Sign-Jws'])){
            $x_request_jws = explode('.', $this->headers['X-Sign-Jws']);
            $this->utils->debug_log('BETSY ' . __METHOD__ , 'FROM HEADER>>>>>', $x_request_jws);
        }

        $own_generate_jws = $this->generateJwtToken($this->requests->params);

        $explode_gen_jws = explode('.',$own_generate_jws);
        $this->utils->debug_log('BETSY ' . __METHOD__ , 'OWNED>>>>>', $explode_gen_jws);

        // $jws[0] - header, $jws[1] - payload, $jws[2] - signature
        // $jws = explode('.', $this->headers['x-sign-jws']);
        // $requestJWSSignature = $jwt->urlsafeB64Decode($jws[2]);
        // $requestBody = json_encode($this->requests->params);
        // $jwt = new JWT;
        // $generatedJWSSignature = hash_hmac('SHA256', $jws[0] . '.' . $jwt->urlsafeB64Encode($requestBody) , $this->partner_secret, true);

        // if (hash_equals($requestJWSSignature, $generatedJWSSignature)) {
            return true;
        // } else {
        //     return false;
        // }
    }

    public function retrieveHeaders() {
        $this->headers = getallheaders();
    }

    public function getPlayerDetails($token){
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

    /**
     * This request will be performed when user loads an iframe and every time we will need additional information for processing user requests on our side.
     * When you receive a request you should perform the following actions on your side:
    * 1. Check that the method is POST.
    * 2. Verify request signature.
    * 3. Verify user token.
    * 4. Respond with a user profile data.
    *
    * https://{PARTNER_API_URL}/user/profile
    */
    public function profile(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rule_set = [
                "token"=> "required",
                "requestId"=> "required",
            ];

            $this->preProcessRequest(__FUNCTION__, $rule_set);

            if(!$this->game_api->validateWhiteIP()){
                $ip = $this->input->ip_address();
                if($ip=='0.0.0.0'){
                    $ip=$this->input->getRemoteAddr();
                }
                $error_response = self::SYSTEM_ERROR;
                $error_response['message'] = "IP not allowed({$ip})";
                return $this->setOutput($error_response);
            }

            $jwt = $this->validateJWT();
            if(!$jwt){
                return $this->setOutput(self::REQ_SIGN_INVALID);
            }

            $playerDetails = $this->getPlayerDetails($this->requests->params['token']);

            if(!empty($playerDetails)) {
                $data = [
                    "userId" => $playerDetails['game_username'],
                    "currency" => $this->currency,
                    "currencies" => [$this->currency],
                    'code' => self::SUCCESS
                    // "isTest" => false, // Optional. If this parameter is missing, a user will be count as isTest = false.
                ];
                return $this->setOutput($data);
            }else{
                return $this->setOutput(self::INVALID_TOKEN);
            }

        }else{
            return $this->setOutput(self::REQ_DATA_INVALID);
        }
    }

    public function balance(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rule_set = [
                "token"=> "required",
                "userId" => "required",
                "currency" => "required",
                "requestId"=> "required",
            ];

            $this->preProcessRequest(__FUNCTION__, $rule_set);

            if(!$this->game_api->validateWhiteIP()){
                $ip = $this->input->ip_address();
                if($ip=='0.0.0.0'){
                    $ip=$this->input->getRemoteAddr();
                }
                $error_response = self::SYSTEM_ERROR;
                $error_response['message'] = "IP not allowed({$ip})";
                return $this->setOutput($error_response);
            }

            $jwt = $this->validateJWT();
            if(!$jwt){
                return $this->setOutput(self::REQ_SIGN_INVALID);
            }

            $playerDetails = $this->getPlayerDetails($this->requests->params['token']);

            if(!empty($playerDetails)) {
                $data = [
                    "userId" => $playerDetails['player_id'],
                    "currency" => $this->currency,
                    "amount" => $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($playerDetails['username'])['balance']),
                    'code' => self::SUCCESS
                ];
                return $this->setOutput($data);
            }else{
                return $this->setOutput(self::INVALID_TOKEN);
            }

        }else{
            return $this->setOutput(self::REQ_DATA_INVALID);
        }
    }

    public function bet(){

        $rule_set = [
            "type" => "required",
            "amount" => "required",
            "transactionId" => "required",
            "requestId"=> "required",
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->game_api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::SYSTEM_ERROR;
            $error_response['message'] = "IP not allowed({$ip})";
            return $this->setOutput($error_response);
        }

        $jwt = $this->validateJWT();
        if(!$jwt){
            return $this->setOutput(self::REQ_SIGN_INVALID);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Place bet
            //This request will be performed every time a user tries to place a bet.
            //When you receive a request you should perform the following actions on your side:

            // 1. Check that the method is POST and the type === 1.
            // 2. Verify request signature.
            // 3. Verify user token.
            // 4. Check whether the user has enough funds and can place bet with corresponding amount and currency. Perform any other checks required by your system.
            // 5. Debit bet amount from the user balance.
            // 6. Respond to a request informing us about operation results.

            if($this->requests->params['type'] === self::BET_TYPE_1){

                $playerDetails = $this->getPlayerDetails($this->requests->params['token']);

                if(!empty($playerDetails)) {

                    $current_balance = $this->game_api->dBtoGameAmount($this->game_api->queryPlayerBalance($playerDetails['username'])['balance']);

                    if($current_balance < $this->requests->params['amount']){
                       return $this->setOutput(self::NOT_ENOUGH_BALANCE);
                    }

                    $controller = $this;
                    $bet_details  = $controller->requests->params;
                    $adjustWallet = [];

                    //Lock Balance
                    $transaction_result = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$bet_details, &$adjustWallet) {

                        //check if bet already existing
                        $existing_transaction_id = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_seamless_wallet_transactions_table, $bet_details['requestId']);

                        if(!empty($existing_transaction_id)){
                            $controller->resultCode = self::BET_ALREADY_PROCESSED;
                            return false;
                        }else{
                            $game_id = null;

                            if(isset($bet_details['metadata'])){
                                $game_id = $bet_details['metadata'][0]['tournamentId'];
                            }
                            $transaction_data['bet_amount'] =  $bet_details['amount'];
                            $transaction_data['result_amount'] =  $bet_details['amount'] * -1;
                            $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                            $transaction_data['round_id'] =  $bet_details['transactionId'];
                            $transaction_data['game_id'] = $game_id;
                            $transaction_data['external_unique_id'] = $bet_details['requestId'];
                            $transaction_data['extra_info'] = isset($bet_details['metadata']) ? json_encode($bet_details['metadata']) : "";
                            $transaction_data['bonus_id'] = isset($bet_details['bonusId']) ? $bet_details['bonusId'] : "";
                            $transaction_data['bonus_template_id'] = isset($bet_details['bonusTemplateId']) ? $bet_details['bonusTemplateId'] : "";
                            $transaction_data['game_type'] = isset($bet_details['gameType']) ? $bet_details['gameType'] : "";

                            $adjustWallet = $controller->adjustWallet(self::TRANSACTION_BET, $playerDetails, $transaction_data);

                            if($adjustWallet['code'] == self::SUCCESS){
                                return true;
                            }else{
                                return false;
                            }
                        }
                    });

                    $date = new DateTime();

                    if($transaction_result){
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "transactionTime" => $date->format('Y-m-d\TH:i:sO'),
                            'code' => self::SUCCESS
                        );

                        return $this->setOutput($data);
                    }else{
                        switch ($controller->resultCode['code']) {
                            case self::BET_ALREADY_PROCESSED['code']:
                                $data = self::BET_ALREADY_PROCESSED;
                            break;
                                break;
                            default:
                                $data = self::SYSTEM_ERROR;
                            break;
                        }

                        if(!empty($controller->resultCode)){
                            return $this->setOutput($data);
                        }else{
                            return $this->setOutput(self::SYSTEM_ERROR);
                        }
                    }
                }else{
                    return $this->setOutput(self::INVALID_TOKEN);
                }
            }

        }else if($_SERVER['REQUEST_METHOD'] === 'PUT'){

            if($this->requests->params['type'] === self::BET_TYPE_1){
                // Unsettle
                // This request will be performed in case of temporary cancellation of the event result.
                // When you receive a request you should perform the following actions on your side:

                // 1. Check that the method is PUT and the type === 1.
                // 2. Verify request signature.
                // 3. Find the bet in your system by transactionId (this id initially was sent with place bet request).
                // 4. Revert previously applied settlement results by updating user balance and performing corresponding payment operations.
                // 5. This type of request is almost the same as Resettle with the only difference that you do not need to perform a new payment after you have reverted previous bet result.
                // 6. As a response we are expecting to get the same format as for Place bet request.

                $playerDetails = $this->getPlayerDetails($this->requests->params['token']);

                if(!empty($playerDetails)) {

                    $controller = $this;
                    $bet_details  = $controller->requests->params;
                    $adjustWallet = [];

                    $fields = [
                        "transaction_id" => $bet_details['transactionId']
                    ];

                    //Lock Balance
                    $transaction_result = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$bet_details, &$adjustWallet) {
                        $game_id = null;

                        //Check if request id is already existing
                        $existing_request_id = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_seamless_wallet_transactions_table, $bet_details['requestId']);

                        if(!empty($existing_request_id)){
                            $controller->resultCode = self::BET_ALREADY_PROCESSED;
                            return false;
                        }

                        //check if bet [transactionId] = transaction_id/round_id is existing
                        $fields = array(
                            "transaction_id" => $bet_details['transactionId'],
                            "transaction_type" => self::TRANSACTION_BET,
                            "player_id" => $this->currentPlayer['player_id']
                        );
                        $bet_transaction_existing = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $fields);

                        if(empty($bet_transaction_existing)){
                            $controller->resultCode = self::REQ_DATA_INVALID;
                            return false;
                        }else{

                            if(isset($bet_transaction_existing[0]['game_id'])){
                                $game_id = $bet_transaction_existing[0]['game_id'];
                            }
                            // resettle conditions
                            // 1. Re-check previously applied bet settlement (use transactionId). If everything is matching — just respond with success.
                            // 2. If the bet payout (amount value from the previous Settle request) on your side differs — revert the previous assessment.
                            // 3. Make a new payment based on the new amount received from the Settle request.
                            $settle_fields = array(
                                "transaction_id" => $bet_details['transactionId'],
                                "transaction_type" => self::TRANSACTION_SETTLE,
                                "status" => self::TRANSACTION_SETTLE,
                                "player_id" => $this->currentPlayer['player_id']
                            );
                            $settled_transaction = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $settle_fields);

                            $this->utils->debug_log('BETSY ' . __METHOD__ , 'for unsettle - get settled_transaction', $settled_transaction);

                            if(!empty($settled_transaction)){

                                $transaction_data['coefficient'] = $bet_details['coefficient'];
                                $transaction_data['game_type'] = $bet_details['gameType'];
                                $transaction_data['bonus_id'] = $bet_details['bonusId'];
                                $transaction_data['bonus_template_id'] = $bet_details['bonusTemplateId'];

                                $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                                $transaction_data['unsettle_amount'] =  $bet_details['amount'];

                                $transaction_data['round_id'] =  $bet_details['transactionId'];
                                $transaction_data['game_id'] = $game_id;
                                $transaction_data['external_unique_id'] = $bet_details['requestId'];

                                $adjustWallet = $controller->adjustWallet(self::TRANSACTION_UNSETTLE, $playerDetails, $transaction_data);

                                if($adjustWallet['code'] == self::SUCCESS){
                                    return true;
                                }else{
                                    return false;
                                }

                            }

                            $transaction_data['coefficient'] = $bet_details['coefficient'];
                            $transaction_data['result_type'] = $bet_details['resultType'];
                            $transaction_data['game_type'] = $bet_details['gameType'];
                            $transaction_data['bonus_id'] = $bet_details['bonusId'];
                            $transaction_data['bonus_template_id'] = $bet_details['bonusTemplateId'];

                            $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                            $transaction_data['win_lose_amount'] =  $bet_details['amount'];
                            $transaction_data['bet_amount'] = $bet_transaction_existing[0]['bet_amount'];

                            $transaction_data['round_id'] =  $bet_details['transactionId'];
                            $transaction_data['game_id'] = $game_id;
                            $transaction_data['external_unique_id'] = $bet_details['requestId'];

                            $adjustWallet = $controller->adjustWallet(self::TRANSACTION_SETTLE, $playerDetails, $transaction_data);

                            if($adjustWallet['code'] == self::SUCCESS){
                                return true;
                            }else{
                                return false;
                            }
                        }



                    });

                    $date = new DateTime();

                    if($transaction_result){
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "transactionTime" => $date->format('Y-m-d\TH:i:sO'),
                            'code' => self::SUCCESS
                        );

                        return $this->setOutput($data);
                    }else{
                        switch ($controller->resultCode['code']) {
                            case self::BET_ALREADY_PROCESSED['code']:
                                $data = self::BET_ALREADY_PROCESSED;
                            break;
                            case self::REQ_DATA_INVALID['code']:
                                $data = self::REQ_DATA_INVALID;
                            break;
                            default:
                                $data = self::SYSTEM_ERROR;
                            break;
                        }

                        if(!empty($controller->resultCode)){
                            return $this->setOutput($data);
                        }else{
                            return $this->setOutput(self::SYSTEM_ERROR);
                        }
                    }
                }else{
                    return $this->setOutput(self::INVALID_TOKEN);
                }

            }else if($this->requests->params['type'] === self::BET_TYPE_2){
                // Settle
                // This request will be performed when our system calculates result and payout for previously placed bet.
                // When you receive a request you should perform the following actions on your side:

                // 1. Check that the method is PUT and the type === 2.
                // 2. Verify request signature.
                // 3. If the request data contains field "resultType": "cashout" — you must verify user token. In other cases there is no requirement for token to be validated because other requests will be initiated not by a user but by our system.
                // 4. Find the bet in your system by transactionId (this id initially was sent with place bet request).
                // 5. Update the user balance and perform payout based on the amount and currency fields.
                // amount === 0 — means lose, do nothing with balance
                // amount > 0 — meaning win/refund/cashout. You should add the amount value to the user balance.
                // 6. Respond to a request informing us about operation results.og_wilson_staging_vnd

                $playerDetails = $this->getPlayerDetails($this->requests->params['token']);

                if(!empty($playerDetails)) {

                    $controller = $this;
                    $bet_details  = $controller->requests->params;
                    $adjustWallet = [];

                    $fields = [
                        "transaction_id" => $bet_details['transactionId']
                    ];

                    //Lock Balance
                    $transaction_result = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$bet_details, &$adjustWallet) {
                        $game_id = null;

                        //Check if request id is already existing
                        $existing_request_id = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_seamless_wallet_transactions_table, $bet_details['requestId']);

                        if(!empty($existing_request_id)){
                            $controller->resultCode = self::BET_ALREADY_PROCESSED;
                            return false;
                        }

                        //check if bet [transactionId] = transaction_id/round_id is existing
                        $fields = array(
                            "transaction_id" => $bet_details['transactionId'],
                            "transaction_type" => self::TRANSACTION_BET,
                            "player_id" => $this->currentPlayer['player_id']
                        );
                        $bet_transaction_existing = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $fields);

                        if(empty($bet_transaction_existing)){
                            $controller->resultCode = self::REQ_DATA_INVALID;
                            return false;
                        }else{

                            if(isset($bet_transaction_existing[0]['game_id'])){
                                $game_id = $bet_transaction_existing[0]['game_id'];
                            }
                            // resettle conditions
                            // 1. Re-check previously applied bet settlement (use transactionId). If everything is matching — just respond with success.
                            // 2. If the bet payout (amount value from the previous Settle request) on your side differs — revert the previous assessment.
                            // 3. Make a new payment based on the new amount received from the Settle request.
                            $settle_fields = array(
                                "transaction_id" => $bet_details['transactionId'],
                                "transaction_type" => self::TRANSACTION_SETTLE,
                                "player_id" => $this->currentPlayer['player_id']
                            );
                            $settled_transaction = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $settle_fields);

                            $this->utils->debug_log('BETSY ' . __METHOD__ , 'for resettle - get settled_transaction', $settled_transaction);

                            if(!empty($settled_transaction)){
                                //Resettle condition number 1
                                if($settled_transaction[0]['amount'] == $bet_details['amount'] && $settled_transaction[0]['result_type'] == $bet_details['resultType']){
                                    if($settled_transaction[0]['status'] == self::TRANSACTION_SETTLE){
                                        return true;
                                    }else if($settled_transaction[0]['status'] == self::TRANSACTION_UNSETTLE){

                                        $transaction_data['coefficient'] = $bet_details['coefficient'];
                                        $transaction_data['result_type'] = $bet_details['resultType'];
                                        $transaction_data['game_type'] = $bet_details['gameType'];
                                        $transaction_data['bonus_id'] = $bet_details['bonusId'];
                                        $transaction_data['bonus_template_id'] = $bet_details['bonusTemplateId'];

                                        $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                                        $transaction_data['win_lose_amount'] =  $bet_details['amount'];
                                        $transaction_data['bet_amount'] = $bet_transaction_existing[0]['bet_amount'];

                                        $transaction_data['round_id'] =  $bet_details['transactionId'];
                                        $transaction_data['game_id'] = $game_id;
                                        $transaction_data['external_unique_id'] = $bet_details['requestId'];

                                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_RESETTLE, $playerDetails, $transaction_data);

                                        if($adjustWallet['code'] == self::SUCCESS){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }

                                }else{
                                    //Resettle condition number 2
                                    $transaction_data['coefficient'] = $bet_details['coefficient'];
                                    $transaction_data['result_type'] = $bet_details['resultType'];
                                    $transaction_data['game_type'] = $bet_details['gameType'];
                                    $transaction_data['bonus_id'] = $bet_details['bonusId'];
                                    $transaction_data['bonus_template_id'] = $bet_details['bonusTemplateId'];

                                    $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                                    $transaction_data['win_lose_amount'] =  $bet_details['amount'];
                                    $transaction_data['bet_amount'] = 0;

                                    $transaction_data['round_id'] =  $bet_details['transactionId'];
                                    $transaction_data['game_id'] = $game_id;
                                    $transaction_data['external_unique_id'] = $bet_details['requestId'];

                                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_RESETTLE, $playerDetails, $transaction_data);

                                    if($adjustWallet['code'] == self::SUCCESS){
                                        return true;
                                    }else{
                                        return false;
                                    }
                                }
                            }

                            $transaction_data['coefficient'] = $bet_details['coefficient'];
                            $transaction_data['result_type'] = $bet_details['resultType'];
                            $transaction_data['game_type'] = $bet_details['gameType'];
                            $transaction_data['bonus_id'] = $bet_details['bonusId'];
                            $transaction_data['bonus_template_id'] = $bet_details['bonusTemplateId'];

                            $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                            $transaction_data['win_lose_amount'] =  $bet_details['amount'];
                            $transaction_data['bet_amount'] = $bet_transaction_existing[0]['bet_amount'];

                            $transaction_data['round_id'] =  $bet_details['transactionId'];
                            $transaction_data['game_id'] = $game_id;
                            $transaction_data['external_unique_id'] = $bet_details['requestId'];

                            $adjustWallet = $controller->adjustWallet(self::TRANSACTION_SETTLE, $playerDetails, $transaction_data);

                            if($adjustWallet['code'] == self::SUCCESS){
                                return true;
                            }else{
                                return false;
                            }
                        }



                    });

                    $date = new DateTime();

                    if($transaction_result){
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "transactionTime" => $date->format('Y-m-d\TH:i:sO'),
                            'code' => self::SUCCESS
                        );

                        return $this->setOutput($data);
                    }else{
                        switch ($controller->resultCode['code']) {
                            case self::BET_ALREADY_PROCESSED['code']:
                                $data = self::BET_ALREADY_PROCESSED;
                            break;
                            case self::REQ_DATA_INVALID['code']:
                                $data = self::REQ_DATA_INVALID;
                            break;
                            default:
                                $data = self::SYSTEM_ERROR;
                            break;
                        }

                        if(!empty($controller->resultCode)){
                            return $this->setOutput($data);
                        }else{
                            return $this->setOutput(self::SYSTEM_ERROR);
                        }
                    }
                }else{
                    return $this->setOutput(self::INVALID_TOKEN);
                }

            }else if($this->requests->params['type'] === self::BET_TYPE_3){
                //Rollback
                // This request will be performed in case of the following errors during Place bet call:
                // - You've responded with HTTP status code 400 and error code 100 System error.
                // - Your server returned HTTP status code other than 200 or 400.
                // - Your system haven't respond to our request within the 5s timeout.
                // - When you receive a request you should perform the following actions on your side:
                // 1. Check that the method is PUT and the type === 3
                // 2. Verify request signature.
                // 3. Find the bet in your system by transactionId (this id initially was sent with place bet request).
                // 4. If the bet is found, you should cancel it and refund the amount back to the user.
                // 5. You will get the same data as for Settle request but without resultType field.

                // As a response we are expecting to get the same format as for Place bet request.
                // You should respond positively (HTTP status code 200) in the following cases:
                // - There is no bet found in your system and there is no need for a return of funds to a user.
                // - The bet occurred and you have successfully returned the money back to a user.
                // - The bet occurred and the money was returned to user during previous Rollback request and therefore this Rollback request can be safely skipped.

                $playerDetails = $this->getPlayerDetails($this->requests->params['token']);

                if(!empty($playerDetails)) {

                    $controller = $this;
                    $bet_details  = $controller->requests->params;
                    $adjustWallet = [];

                    //Lock Balance
                    $transaction_result = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, &$bet_details, &$adjustWallet) {

                        $existing_request_id = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_seamless_wallet_transactions_table, $bet_details['requestId']);

                        if(!empty($existing_request_id)){
                            $controller->resultCode = self::REQ_DATA_INVALID;
                            return false;
                        }

                        //check if bet [transactionId] = transaction_id/round_id is existing
                        $fields = array(
                            "transaction_id" => $bet_details['transactionId'],
                            "transaction_type" => self::TRANSACTION_BET,
                            "player_id" => $this->currentPlayer['player_id']
                        );

                        $bet_transaction_existing = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $fields);

                        $game_id = null;
                        if(isset($bet_transaction_existing[0]['game_id'])){
                            $game_id = $bet_transaction_existing[0]['game_id'];
                        }

                        if(!empty($bet_transaction_existing)){
                            $transaction_data['coefficient'] = $bet_details['coefficient'];
                            $transaction_data['game_type'] = $bet_details['gameType'];
                            $transaction_data['bonus_id'] = $bet_details['bonusId'];
                            $transaction_data['bonus_template_id'] = $bet_details['bonusTemplateId'];

                            $transaction_data['transaction_id'] =  $bet_details['transactionId'];
                            $transaction_data['rollback_amount'] =  $bet_details['amount'];

                            $transaction_data['round_id'] =  $bet_details['requestId'];
                            $transaction_data['game_id'] = $game_id;
                            $transaction_data['external_unique_id'] = $bet_details['requestId'];

                            $adjustWallet = $controller->adjustWallet(self::TRANSACTION_ROLLBACK, $playerDetails, $transaction_data);

                            if($adjustWallet['code'] == self::SUCCESS){
                                return true;
                            }else{
                                return false;
                            }
                        }else{
                            return true;
                        }
                    });

                    $date = new DateTime();

                    if($transaction_result){
                        $data = array(
                            "transactionId" => $this->requests->params['transactionId'],
                            "transactionTime" => $date->format('Y-m-d\TH:i:sO'),
                            'code' => self::SUCCESS
                        );

                        return $this->setOutput($data);
                    }else{
                        switch ($controller->resultCode['code']) {
                            case self::BET_ALREADY_PROCESSED['code']:
                                $data = self::BET_ALREADY_PROCESSED;
                            break;
                            case self::REQ_DATA_INVALID['code']:
                                $data = self::REQ_DATA_INVALID;
                            break;
                            default:
                                $data = self::SYSTEM_ERROR;
                            break;
                        }

                        if(!empty($controller->resultCode)){
                            return $this->setOutput($data);
                        }else{
                            return $this->setOutput(self::SYSTEM_ERROR);
                        }
                    }
                }else{
                    return $this->setOutput(self::INVALID_TOKEN);
                }
            }
        }


    }
    protected function genJWTTokenLastString()
    {
        $payload = json_encode($this->requests->params);

        $jwt = JWT::encode(
            $payload,
            $this->partner_secret,
            'HS256'
        );

        $jws = explode('.', $jwt);

        return $jws[2];
    }

    private function setOutput($data = []) {

        $flag = ($data['code']==self::SUCCESS) ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $httpStatusCode = 200;

        $httpStatusText = isset($data['message']) ? $data['message'] : 'Success';

        if(isset($data['http_status_code'])){
            $httpStatusCode = $data['http_status_code'];
        }

        unset($data['http_status_code']);

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

    public function preProcessRequest($functionName="", $rule_set = []) {
        $this->requests->function = $functionName ;

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            return $this->setOutput(self::SYSTEM_ERROR);
        }
    }

    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {

                if(is_array($this->requests->params)){
                    if($rule == 'required' && !array_key_exists($key, $this->requests->params)) {
                        $is_valid = false;
                        $this->utils->debug_log('BETSY ' . __METHOD__ , 'missing parameter', $key);
                        break;
                    }
                    if($rule == 'numeric' && !is_numeric($this->requests->params[$key])) {
                        $is_valid = false;

                        $this->utils->debug_log('BETSY ' . __METHOD__ , 'not numeric', $key);
                        break;
                    }
                }else{
                    $is_valid = false;

                    $this->utils->debug_log('BETSY ' . __METHOD__ , 'pass paramater is not an array', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
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

        if($transaction_type== self::TRANSACTION_BET){

            $game_amount_to_db_bet_amount = $this->game_api->gameAmountToDBTruncateNumber($extra['bet_amount']);

            $wallet_transaction['amount'] = $extra['bet_amount']; // to save in DB not yet converted
            $wallet_transaction['bet_amount'] = $extra['bet_amount']; // to save in DB not yet converted
            $wallet_transaction['result_amount'] = $extra['result_amount'];

            $wallet_transaction['status'] = self::TRANSACTION_BET;

            $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $game_amount_to_db_bet_amount, $after_balance);

            if(!$response) {
                $this->resultCode = self::SYSTEM_ERROR;
                $return_data['code'] = self::SYSTEM_ERROR;
            }

        }else if($transaction_type == self::TRANSACTION_SETTLE){
            $this->utils->debug_log('BETSY', 'SETTLE!!!!');

            $amount = $this->game_api->gameAmountToDBTruncateNumber($extra['win_lose_amount']);
            $bet = $this->game_api->gameAmountToDBTruncateNumber($extra['bet_amount']);

            $result_amount = $amount - $bet;

            $wallet_transaction['amount'] =  $amount;
            $wallet_transaction['bet_amount'] =  0; //$bet;
            $wallet_transaction['result_amount'] =  $amount; //$result_amount;

            $wallet_transaction['status'] = self::TRANSACTION_SETTLE;

            if($amount > 0){
                $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $amount, $after_balance);
                $this->utils->debug_log('BETSY', 'ADD-AMOUNT: ', $response);
            }

            $response = true;

            $where = array (
                'game_id' => $extra['game_id'],
                'transaction_id' =>  $extra['transaction_id'],
                'player_id' => $player_info['player_id'],
                'transaction_type' => self::TRANSACTION_BET
            );

            $update_data = array("status" => self::TRANSACTION_SETTLE);
            $success_update = $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);

        }else if($transaction_type == self::TRANSACTION_RESETTLE){
            $amount = $this->game_api->gameAmountToDBTruncateNumber($extra['win_lose_amount']);
            $bet = isset($extra['bet_amount']) ? $this->game_api->gameAmountToDBTruncateNumber($extra['bet_amount']) : 0;

            $result_amount = $amount - $bet;

            $wallet_transaction['amount'] =  $amount;
            $wallet_transaction['bet_amount'] =  $bet;
            $wallet_transaction['result_amount'] =  $result_amount;

            $wallet_transaction['status'] = self::TRANSACTION_SETTLE;

            if($extra['result_type'] == self::RESULT_WON){
                $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $amount, $after_balance);
                $this->utils->debug_log('BETSY', 'ADD-AMOUNT-RESETTLED: ', $response);
            }else{
                $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $amount, $after_balance);
                $this->utils->debug_log('BETSY', 'SUB-AMOUNT-RESETTLED: ', $response);
            }

            $response = true;

            $where = array (
                'transaction_id' => $extra['transaction_id'],
                'game_id' => $extra['game_id'],
                'player_id' => $player_info['player_id'],
                'transaction_type' => self::TRANSACTION_BET
            );

            $update_data = array("status" => self::TRANSACTION_SETTLE, 'flag_of_updated_result' => 1,);
            $success_update = $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);

            $this->utils->debug_log('BETSY ', 'UPDATE_BET_TRANSACTION: ', $success_update);


        }else if($transaction_type== self::TRANSACTION_UNSETTLE){
            $game_amount_to_db_refund_amount =  $this->game_api->gameAmountToDBTruncateNumber($extra['unsettle_amount']);

            $this->utils->debug_log('BETSY ', 'ADJSUT TRANSACTION_TYPE_UNSETTLED: ');

            $wallet_transaction['amount'] =  $extra['unsettle_amount'];
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['result_amount'] = $extra['unsettle_amount'];
            $wallet_transaction['status'] = self::TRANSACTION_UNSETTLE;

            if($extra['unsettle_amount'] > 0){
                $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $game_amount_to_db_refund_amount, $after_balance);
                $this->utils->debug_log('BETSY ', 'ADD-AMOUNT: ', $response);
            }

            $return_data['after_balance'] = $after_balance;

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

            #Insert unsettle data
            $this->wallet_transaction_id = $this->original_seamless_wallet_transactions->insertTransactionData($this->original_seamless_wallet_transactions_table, $wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;

            #Update original bet transaction that is settled to unsettle
            $response = true;

            $where = array (
                'transaction_id' => $extra['transaction_id'],
                'game_id' => $extra['game_id'],
                'player_id' => $player_info['player_id']
            );

            $this->utils->debug_log('BETSY ', 'UPDATE_BET_TRANSACTION: ', $where);

            $update_data = array("status" => self::TRANSACTION_UNSETTLE, 'flag_of_updated_result' => 1,);
            $success_update = $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);

            $this->utils->debug_log('BETSY ', 'UPDATE_BET_TRANSACTION: ', $success_update);
            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }else{
                $return_data['code'] = self::SUCCESS;
                return $return_data;
            }
        }else if($transaction_type== self::TRANSACTION_ROLLBACK){
            $rollback_amount =  $this->game_api->gameAmountToDBTruncateNumber($extra['rollback_amount']);

            $this->utils->debug_log('BETER ', 'ADJSUT TRANSACTION_TYPE_REFUND: ');

            $wallet_transaction['amount'] =  $extra['rollback_amount'];
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['result_amount'] = $extra['rollback_amount'];
            $wallet_transaction['status'] = self::TRANSACTION_ROLLBACK;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->game_api->getPlatformCode(), $rollback_amount, $after_balance);
            $this->utils->debug_log('BETSY ', 'ADD-AMOUNT: ', $response);

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

            #Insert refund data
            $this->wallet_transaction_id = $this->original_seamless_wallet_transactions->insertTransactionData($this->original_seamless_wallet_transactions_table, $wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;

            #Update original bet transaction to REFUND
            $response = true;

            $where = array (
                'transaction_id' => $extra['transaction_id'],
                'game_id' => $extra['game_id'],
                'player_id' => $player_info['player_id'],
                'status' => self::TRANSACTION_BET
            );

            $update_data = array("status" => self::TRANSACTION_ROLLBACK, 'flag_of_updated_result' => 1,);
            $success_update = $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);

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
        $wallet_transaction['coefficient'] = isset($extra['coefficient']) ? $extra['coefficient'] : null;
        $wallet_transaction['result_type'] = isset($extra['result_type']) ? $extra['result_type'] : null;
        $wallet_transaction['game_type'] = isset($extra['game_type']) ? $extra['game_type'] : null;
        $wallet_transaction['bonus_id'] = isset($extra['bonus_id']) ? $extra['bonus_id'] : null;
        $wallet_transaction['bonus_template_id'] = isset($extra['bonus_template_id']) ? $extra['bonus_template_id'] : null;


        if($return_data['code'] == self::SUCCESS['error_code']) {
            $this->wallet_transaction_id = $this->original_seamless_wallet_transactions->insertTransactionData($this->original_seamless_wallet_transactions_table, $wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;
            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }
        }

        return $return_data;
    }

}