<?php

use function GuzzleHttp\json_decode;

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class kagaming_service_api extends BaseController {

	protected $gamePlatformId = KA_SEAMLESS_API;
	protected $currencyCode;
	private $api;
	private $requestParams;
	private $player_id;
    private $secret_key;
    private $headers;


    const SUCCESS_MSG = "success";

    const STATUS_CODE_SUCCESS = 0;
    const STATUS_CODE_ERR_REQUEST_HANDLING = 1;
    const STATUS_CODE_INVALID_REQUEST = 2;
    const STATUS_CODE_INVALID_HASH = 3;
    const STATUS_CODE_INVALID_PLAYER = 4;
    const STATUS_CODE_IP_NOT_ALLOWED = 4;
    const STATUS_CODE_REQUEST_MISMATCH = 5;

    // START
    const STATUS_CODE_INVALID_TOKEN = 100;
    const STATUS_CODE_TOKEN_IN_USED = 101;

    // PLAY
    const STATUS_CODE_INSUFFICIENT_BALANCE = 200;
    const STATUS_CODE_WAGER_PERMISSION_DENIED = 201;

    // CREDIT
    const STATUS_CODE_TRANSACTION_NOT_EXIST_CREDIT = 300;
    const STATUS_CODE_TRANSACTION_OPERATOR_DENIED = 301;

    // CREDIT
    const STATUS_CODE_TRANSACTION_NOT_EXIST_REVOKE = 400;
    const STATUS_CODE_TRANSACTION_NO_LONGER_REVOCABLE = 401;

    // END
    const STATUS_CODE_SESSION_NOT_EXIST = 500;

    // GAME LIST REPORT
    const STATUS_CODE_INVALID_PARTNER = 600;

    // PLAYER REPORT
    const STATUS_CODE_INVALID_PARTNER_PLAYER = 2002;

    const STATUS_MSG_INTERNAL_ERROR = "Internal Server Error. Please Contact your System Administrator";

    const STATUS_MESSAGE = array(
        self::STATUS_CODE_SUCCESS => "success",
        self::STATUS_CODE_ERR_REQUEST_HANDLING => "Exception or error in request handling.",
        self::STATUS_CODE_INVALID_REQUEST => "Invalid request – missing parameters in the request or invalid format",
        self::STATUS_CODE_INVALID_HASH => "Invalid hash.",
        self::STATUS_CODE_INVALID_PLAYER => "Invalid player",
        self::STATUS_CODE_REQUEST_MISMATCH => "Request mismatch.",
        self::STATUS_CODE_INVALID_TOKEN => "Invalid token",
        self::STATUS_CODE_TOKEN_IN_USED => "Token already used",
        self::STATUS_CODE_INSUFFICIENT_BALANCE => "Insufficient balance (cashable or bonus) to play bet",
        self::STATUS_CODE_WAGER_PERMISSION_DENIED => "Wager permission denied or blocked by Licensee",
        self::STATUS_CODE_TRANSACTION_NOT_EXIST_CREDIT => "Transaction does not exist",
        self::STATUS_CODE_TRANSACTION_OPERATOR_DENIED => "Licensee or operator denied crediting to player (cashable or bonus) balance",
        self::STATUS_CODE_TRANSACTION_NOT_EXIST_REVOKE => "Transaction does not exist",
        self::STATUS_CODE_TRANSACTION_NO_LONGER_REVOCABLE => "Transaction no longer revocable",
        self::STATUS_CODE_SESSION_NOT_EXIST => "Session does not exist",
        self::STATUS_CODE_INVALID_PARTNER => "Invalid partner",
        self::STATUS_CODE_INVALID_PARTNER_PLAYER => "Invalid partner player",
        self::STATUS_CODE_IP_NOT_ALLOWED => "IP address is not allowed."
    );

    const PLAY = 'play';
    const CREDIT = 'credit';
    const REVOKE = 'revoke';

    const UPDATE_INSERT_MESSAGE = 'no changes, data is already inserted';

    const MD5_FIELDS_FOR_ORIGINAL = [
    	'playerId','UserName','SessionId','TransactionId','Currency','Selections','BetPerSelection','FreeGames','GameId',
        'RoundId','RoundsRemaining','Timestamp','OperatorName','Token','CreditAmount','PlayerIp','transaction_type'
    ];
    const MD5_FLOAT_AMOUNT_FIELDS = [
    	'BetAmount','WinAmount','CreditAmount'
    ];

	public function __construct() {

		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','game_description_model','external_system',
			'common_seamless_wallet_transactions','original_game_logs_model','pgsoft_seamless_game_logs'));
		// $this->game_api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
		$this->requestParams = new stdClass();

        $this->processRequest();

        $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
        $this->secret_key = $this->api->getSecretKey();
        $this->headers = getallheaders();
	}

    public function start() {

        $hash = $_GET["hash"];

        $params = $this->requestParams->params;

        $gameUsername = $params->partnerPlayerId;
        $sessionId = $params->sessionId;
        $currency = $params->currency;
        $valid_hash = $this->hash_is_valid($this->secret_key, json_encode($params), $hash);

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->params->action = 'validateWhiteIP';
            return $this->setResponse(self::STATUS_CODE_IP_NOT_ALLOWED, self::STATUS_MESSAGE[self::STATUS_CODE_IP_NOT_ALLOWED]);
        }

        if($valid_hash) {

            $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

            if(!empty($playerId)) {

                $player_info = $this->player_model->getPlayerInfoById($playerId);


                $this->player_id = $playerId;

                $self = $this;

                $player_balance = 0;

                $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$player_info,&$player_balance) {

                    $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];

                    $player_balance = $self->api->dBtoGameAmount($player_balance);

                    return true;

                });


                if($trans_success) {

                    $response_data = array(
                        "playerId" => $gameUsername,
                        "sessionId" => $sessionId,
                        "balance" => $player_balance,
                        "currency" => $currency
                    );

                    return $this->setResponse(self::STATUS_CODE_SUCCESS, self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS], $response_data);

                } else {

                    return $this->setResponse(self::STATUS_CODE_ERR_REQUEST_HANDLING, self::STATUS_MSG_INTERNAL_ERROR);

                }

            } else {

                return $this->setResponse(self::STATUS_CODE_INVALID_PLAYER, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_PLAYER]);

            }

        } else {

            return $this->setResponse(self::STATUS_CODE_INVALID_HASH, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_HASH]);

        }

    }

    public function play() {


        $hash = $_GET["hash"];

        $params = $this->requestParams->params;

        $this->CI->utils->debug_log('KA GAMING Request Parameters', $params);

        $gameUsername = $params->partnerPlayerId;
        $sessionId = $params->sessionId;
        $currency = $params->currency;
        $valid_hash = $this->hash_is_valid($this->secret_key, json_encode($params), $hash);

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->params->action = 'validateWhiteIP';
            return $this->setResponse(self::STATUS_CODE_IP_NOT_ALLOWED, self::STATUS_MESSAGE[self::STATUS_CODE_IP_NOT_ALLOWED]);
        }

        if($valid_hash) {

            $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

            if(!empty($playerId)) {

                $player_info = $this->player_model->getPlayerInfoById($playerId);

                $response_code = array(
                    "statusCode" => self::STATUS_CODE_SUCCESS,
                    "status" => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS]
                );

                $self = $this;

                $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
                    $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];
                    $player_balance = $self->api->dBtoGameAmount($player_balance);
                    $transaction_id = $params->transactionId;
                    $round_id = $params->round;

                    $new_trans_id = $transaction_id . "-" . $round_id;
                    $platform_id = $self->api->getPlatformCode();
                    $existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$new_trans_id,"transaction_id",SELF::PLAY);

                    if(!empty($existingRow)){

                        $response_code['after_balance'] = $player_balance;
                        return true;

                    }

                    $betAmount = isset($params->betAmount) ? $params->betAmount : null;

                    if (($player_balance - $betAmount) < 0) {
                        $response_code['statusCode'] = self::STATUS_CODE_INSUFFICIENT_BALANCE;
                        $response_code['status'] = self::STATUS_MESSAGE[self::STATUS_CODE_INSUFFICIENT_BALANCE];
                        return false;
                    }

                    $response_code = $self->adjustWallet(self::PLAY, $player_info, $params);

                    if($response_code['statusCode'] != self::STATUS_CODE_SUCCESS) {
                        return false;
                    }

                    if($response_code['statusCode'] != self::STATUS_CODE_SUCCESS && $response_code['status'] == self::UPDATE_INSERT_MESSAGE) {
                        return false;
                    }

                    return true;
                });


                if($trans_success) {


                    if(!array_key_exists('after_balance', $response_code)) {
                        $balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        $balance = $this->api->dBtoGameAmount($balance);
                    } else {
                        $balance = $response_code["after_balance"];
                    }

                    $response_data = array(
                        "balance" => $balance
                    );

                    #for testing purposes, to trigger revoke request
                    if($this->api->getSystemInfo('force_trigger_revoke_on_play', false) && $gameUsername == $this->api->getSystemInfo('force_trigger_revoke_game_username', null)){
                        return null;
                    }

                    return $this->setResponse($response_code["statusCode"], $response_code["status"], $response_data);


                } else {

                    if($response_code["statusCode"] == self::STATUS_CODE_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

                        // it should not be success if failed. lockAndTransForPlayerBalance
                        return $this->setResponse(self::STATUS_CODE_ERR_REQUEST_HANDLING, self::STATUS_MSG_INTERNAL_ERROR);

                    } else {

                        return $this->setResponse($response_code["statusCode"], $response_code["status"]);

                    }




                }

            } else {
                return $this->setResponse(self::STATUS_CODE_INVALID_PLAYER, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_PLAYER]);
            }


        } else {

            return $this->setResponse(self::STATUS_CODE_INVALID_HASH, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_HASH]);

        }

    }

    public function credit() {


        $hash = $_GET["hash"];

        $params = $this->requestParams->params;

        $gameUsername = $params->partnerPlayerId;
        $sessionId = $params->sessionId;
        $currency = $params->currency;
        $valid_hash = $this->hash_is_valid($this->secret_key, json_encode($params), $hash);

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->params->action = 'validateWhiteIP';
            return $this->setResponse(self::STATUS_CODE_IP_NOT_ALLOWED, self::STATUS_MESSAGE[self::STATUS_CODE_IP_NOT_ALLOWED]);
        }

        if($valid_hash) {

            $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

            if(!empty($playerId)) {

                $player_info = $this->player_model->getPlayerInfoById($playerId);

                $response_code = array(
                    "statusCode" => self::STATUS_CODE_SUCCESS,
                    "status" => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS]
                );

                $self = $this;

                $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
                    $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];
                    $player_balance = $self->api->dBtoGameAmount($player_balance);
                    $transaction_id = $params->transactionId;
                    $credit_index = $params->creditIndex;
                    $new_trans_id = $transaction_id . "-credit-" . $credit_index;
                    $platform_id = $self->api->getPlatformCode();
                    $existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$new_trans_id,"transaction_id",SELF::CREDIT);

                    if(!empty($existingRow)){

                        $response_code['after_balance'] = $player_balance;
                        return true;

                    }

                    $response_code = $self->adjustWallet(self::CREDIT, $player_info, $params);

                    if($response_code['statusCode'] != self::STATUS_CODE_SUCCESS) {
                        return false;
                    }

                    if($response_code['statusCode'] != self::STATUS_CODE_SUCCESS && $response_code['status'] == self::UPDATE_INSERT_MESSAGE) {
                        return false;
                    }

                    return true;
                });


                if($trans_success) {


                    if(!array_key_exists('after_balance', $response_code)) {
                        $balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        $balance = $this->api->dBtoGameAmount($balance);
                    } else {
                        $balance = $response_code["after_balance"];
                    }

                    $response_data = array(
                        "balance" => $balance
                    );

                    #for testing purposes, to trigger revoke request
                    if($this->api->getSystemInfo('force_trigger_revoke_on_credit', false) && $gameUsername == $this->api->getSystemInfo('force_trigger_revoke_game_username', null)){
                        return null;
                    }

                    return $this->setResponse($response_code["statusCode"], $response_code["status"], $response_data);


                } else {


                    if($response_code["statusCode"] == self::STATUS_CODE_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

                        // it should not be success if failed. lockAndTransForPlayerBalance
                        return $this->setResponse(self::STATUS_CODE_ERR_REQUEST_HANDLING, self::STATUS_MSG_INTERNAL_ERROR);

                    } else {

                        return $this->setResponse($response_code["statusCode"], $response_code["status"]);

                    }

                }

            } else {

                return $this->setResponse(self::STATUS_CODE_INVALID_PLAYER, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_PLAYER]);

            }


        } else {

            return $this->setResponse(self::STATUS_CODE_INVALID_HASH, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_HASH]);

        }

    }

    public function revoke() {

        $hash = $_GET["hash"];

        $params = $this->requestParams->params;

        $gameUsername = $params->partnerPlayerId;
        $sessionId = $params->sessionId;
        $currency = $params->currency;
        $valid_hash = $this->hash_is_valid($this->secret_key, json_encode($params), $hash);

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->params->action = 'validateWhiteIP';
            return $this->setResponse(self::STATUS_CODE_IP_NOT_ALLOWED, self::STATUS_MESSAGE[self::STATUS_CODE_IP_NOT_ALLOWED]);
        }

        if($valid_hash) {

            $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

            if(!empty($playerId)) {

                $player_info = $this->player_model->getPlayerInfoById($playerId);

                $response_code = array(
                    "statusCode" => self::STATUS_CODE_SUCCESS,
                    "status" => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS]
                );

                $self = $this;

                $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
                    $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];
                    $player_balance = $self->api->dBtoGameAmount($player_balance);
                    $transaction_id = $params->transactionId;
                    $platform_id = $self->api->getPlatformCode();

                    $response_code = $self->adjustWallet(self::REVOKE, $player_info, $params);

                    if($response_code['statusCode'] != self::STATUS_CODE_SUCCESS) {
                        return false;
                    }

                    if($response_code['statusCode'] != self::STATUS_CODE_SUCCESS && $response_code['status'] == self::UPDATE_INSERT_MESSAGE) {
                        return false;
                    }

                    return true;
                });


                if($trans_success) {


                    if(!array_key_exists('after_balance', $response_code)) {
                        $balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        $balance = $this->api->dBtoGameAmount($balance);
                    } else {
                        $balance = $response_code["after_balance"];
                    }

                    $response_data = array(
                        "balance" => $balance
                    );

                    return $this->setResponse($response_code["statusCode"], $response_code["status"], $response_data);


                } else {


                    if($response_code["statusCode"] == self::STATUS_CODE_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

                        // it should not be success if failed. lockAndTransForPlayerBalance
                        return $this->setResponse(self::STATUS_CODE_ERR_REQUEST_HANDLING, self::STATUS_MSG_INTERNAL_ERROR);

                    } else {

                        return $this->setResponse($response_code["statusCode"], $response_code["status"]);

                    }

                }

            } else {

                return $this->setResponse(self::STATUS_CODE_INVALID_PLAYER, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_PLAYER]);

            }


        } else {

            return $this->setResponse(self::STATUS_CODE_INVALID_HASH, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_HASH]);

        }

    }

    public function balance() {

        $hash = $_GET["hash"];

        $params = $this->requestParams->params;

        $gameUsername = $params->partnerPlayerId;
        $sessionId = $params->sessionId;
        $currency = $params->currency;
        $valid_hash = $this->hash_is_valid($this->secret_key, json_encode($params), $hash);

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->params->action = 'validateWhiteIP';
            return $this->setResponse(self::STATUS_CODE_IP_NOT_ALLOWED, self::STATUS_MESSAGE[self::STATUS_CODE_IP_NOT_ALLOWED]);
        }

        if($valid_hash) {

            $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

            if(!empty($playerId)) {

                $player_info = $this->player_model->getPlayerInfoById($playerId);


                $this->player_id = $playerId;


                $player_balance = 0;

                $self = $this;

                $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$player_info,&$player_balance) {

                    $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];
                    $player_balance = $self->api->dBtoGameAmount($player_balance);

                    return true;

                });

                if($trans_success) {

                    $response_data = array(
                        "balance" => $player_balance
                    );

                    return $this->setResponse(self::STATUS_CODE_SUCCESS, self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS], $response_data);
                } else {

                    return $this->setResponse(self::STATUS_CODE_ERR_REQUEST_HANDLING, self::STATUS_MSG_INTERNAL_ERROR);

                }

            } else {

                return $this->setResponse(self::STATUS_CODE_INVALID_PLAYER, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_PLAYER]);

            }

        } else {

            return $this->setResponse(self::STATUS_CODE_INVALID_HASH, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_HASH]);

        }

    }

    public function end() {

        $hash = $_GET["hash"];

        $params = $this->requestParams->params;

        $gameUsername = $params->partnerPlayerId;
        $valid_hash = $this->hash_is_valid($this->secret_key, json_encode($params), $hash);

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->params->action = 'validateWhiteIP';
            return $this->setResponse(self::STATUS_CODE_IP_NOT_ALLOWED, self::STATUS_MESSAGE[self::STATUS_CODE_IP_NOT_ALLOWED]);
        }

        if($valid_hash) {

            $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

            if(!empty($playerId)) {


                return $this->setResponse(self::STATUS_CODE_SUCCESS, self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS]);

            } else {

                return $this->setResponse(self::STATUS_CODE_INVALID_PLAYER, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_PLAYER]);

            }

        } else {

            return $this->setResponse(self::STATUS_CODE_INVALID_HASH, self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_HASH]);

        }

    }

    private function hash_is_valid($secret, $payload, $hash) {
        $computed_hash = $this->compute_hash($secret, $payload);
        return hash_equals($hash,$computed_hash);
    }

    private function compute_hash($secret, $payload)
    {
        $hexHash = hash_hmac('sha256', $payload, $secret);
        return $hexHash;
    }

	protected function processRequest(){
		$request = file_get_contents('php://input');
		$this->CI->utils->debug_log(__FUNCTION__, 'KAGaming (Raw Input): ', $request);
		$decoded_params=json_decode($request);
		$this->CI->utils->debug_log(__FUNCTION__, 'KAGaming (Raw array input): ', $decoded_params);
		$this->requestParams->params = $decoded_params;

		return $this->requestParams;
	}


	private function adjustWallet($action, $player_info, $trans_record) {

        $existingTransaction = array();

		$response_code = array(
            "statusCode" => self::STATUS_CODE_SUCCESS,
            "status" => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS]
        );

        $before_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $response_code['before_balance'] = $this->api->dBtoGameAmount($before_balance);

        if($action == self::PLAY) {

            $betAmount = $trans_record->betAmount;

            if($betAmount > 0) {

                // deduct the betAmount
                /**
                 * If play event was a free game event. If it is a free
                 *  game event, licensee should not deduct bet amount
                 *  and only add the win amount back to the player’s
                 *  balance
                 */


                $will_deduct = isset($trans_record->freeGames) ? !$trans_record->freeGames : true;

                if($will_deduct) {

                    $betAmount = $this->api->gameAmountToDB($betAmount);

                    $this->CI->utils->debug_log('KA GAMING BET AMOUNT', $betAmount);
                    $deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $betAmount);
                    $this->CI->utils->debug_log('KA GAMING DEDUCT BALANCE', $deduct_balance);

                }
                /**
                 * my suggestion is to ignore Selections and BetPerSelection, it is just some more information for slot game feature,
                 * I can not ensure the formula rule for all games; for wallet system,
                 * it is a garbage data, you can just process betAmount, winAbount, freeGames to know this spin is a free game or not, and how much the player wager
                 * (if not a free game) and win
                 */


            }

            $winAmount = $trans_record->winAmount;

            if ($winAmount > 0) {

                $winAmount = $this->api->gameAmountToDB($winAmount);

                $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $winAmount);
                $this->CI->utils->debug_log('KA GAMING DEDUCT BALANCE', $add_balance);

            }

            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->api->dBtoGameAmount($after_balance);

        } elseif ($action == self::CREDIT) {
            $transaction_id = $trans_record->transactionId;
            $existingTransactionPlay = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(),$transaction_id,"round_id", SELF::PLAY);

            #check if play transction is existing, if not, return error
            if(empty($existingTransactionPlay)){
                return [
                    'statusCode' => self::STATUS_CODE_TRANSACTION_NOT_EXIST_CREDIT,
                    'status' => self::STATUS_MESSAGE[self::STATUS_CODE_TRANSACTION_NOT_EXIST_CREDIT],
                ];
            }

            #check if play transction is already revoked, return error 
            if($existingTransactionPlay->status == 'revoked'){
                return [
                    'statusCode' => self::STATUS_CODE_INVALID_REQUEST,
                    'status' => self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_REQUEST]
                ];
            }

            #additional note: even though the credit transctiuon already revoked, credit transction can be request again if player tries to relaunch the game

            $amount = $trans_record->amount;

            $amount = $this->api->gameAmountToDB($amount);

            $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
            $this->CI->utils->debug_log('KA GAMING DEDUCT BALANCE', $add_balance);

            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->api->dBtoGameAmount($after_balance);

        } elseif ($action == self::REVOKE) {

            // check first if the transaction record is existing.
            $transaction_id = $trans_record->transactionId;

            $round_id = $trans_record->round;

            $new_trans_id = $transaction_id . "-" . $round_id;

            $platform_id = $this->api->getPlatformCode();

            $existingTransactionRevoke = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$new_trans_id,"transaction_id", SELF::REVOKE);

            if(!EMPTY($existingTransactionRevoke)) { // this way is to check if the revoke request is already triggered. this condition will avoid the adjusting the balance if its alread triggered.

                $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                $response_code = [
                    'statusCode' => self::STATUS_CODE_SUCCESS,
                    'status' => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS],
                    'after_balance' => $this->api->dBtoGameAmount($after_balance)
                ];

                return $response_code;

            } else {


                // for revoking credit transaction - credit transaction has no round id
                $existingTransactionRevokeForCredit = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id", SELF::REVOKE);

                if(!EMPTY($existingTransactionRevokeForCredit)) {

                    $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                    $response_code = [
                        'statusCode' => self::STATUS_CODE_SUCCESS,
                        'status' => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS],
                        'after_balance' => $this->api->dBtoGameAmount($after_balance)
                    ];

                    return $response_code;

                }
            }

            if($trans_record->revokedAction == self::PLAY){
                #revoke play revokedAction = 'play'
                $existingTransactionPlay = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"round_id", SELF::PLAY);

                #if not found any related transaction, return error 
                if(empty($existingTransactionPlay)){
                    $this->CI->utils->debug_log('KA GAMING revokeAction: play - $existingTransactionPlay not found');
                    return [
                        'statusCode' => self::STATUS_CODE_TRANSACTION_NOT_EXIST_REVOKE,
                        'status' => self::STATUS_MESSAGE[self::STATUS_CODE_TRANSACTION_NOT_EXIST_REVOKE],
                    ];
                }
                
                #procceed with player balance movement
                $transaction_amount = $existingTransactionPlay->amount;

                if($transaction_amount > 0) {

                    $this->CI->utils->debug_log('KA GAMING DEDUCT AMOUNT (revoke)', $transaction_amount);
                    $deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $transaction_amount);
                    $this->CI->utils->debug_log('KA GAMING DEDUCT AMOUNT (revoke)', $deduct_balance);


                } else if ($transaction_amount < 0) {

                    $this->CI->utils->debug_log('KA GAMING INCREASE AMOUNT (revoke)', $transaction_amount);
                    $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), abs($transaction_amount));

                    $this->CI->utils->debug_log('KA GAMING INCREASE AMOUNT (revoke)', $add_balance);
                }

                $trans_record->amount = -$transaction_amount;
            }else if($trans_record->revokedAction == self::CREDIT){
                #revoke credit revokedAction = 'credit', as per GP credit transaction can be revoked too
                $existingTransactionCredit = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"round_id", SELF::CREDIT);

                #if not found any related transaction, return error 
                if(empty($existingTransactionCredit)){
                    $this->CI->utils->debug_log('KA GAMING revokeAction: credit - $existingTransactionCredit not found');
                    return [
                        'statusCode' => self::STATUS_CODE_TRANSACTION_NOT_EXIST_REVOKE,
                        'status' => self::STATUS_MESSAGE[self::STATUS_CODE_TRANSACTION_NOT_EXIST_REVOKE],
                    ];
                }
                #procceed with player balance movement
                $extra_info = $existingTransactionCredit->extra_info;

                $extra_info_arr = json_decode($extra_info);

                $credAmount = $extra_info_arr->CreditAmount;

                if($credAmount > 0) {

                    $this->CI->utils->debug_log('KA GAMING CREDIT AMOUNT (revoke)', $credAmount);
                    $deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $credAmount);
                    $this->CI->utils->debug_log('KA GAMING DEDUCT BALANCE', $deduct_balance);

                }
            }else{
                return [
                    'statusCode' => self::STATUS_CODE_INVALID_REQUEST,
                    'status' => self::STATUS_MESSAGE[self::STATUS_CODE_INVALID_REQUEST]
                ];
            }

            

            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->api->dBtoGameAmount($after_balance);

        } else {
            $response_code['statusCode'] = self::STATUS_CODE_ERR_REQUEST_HANDLING;
            $response_code['status'] = self::STATUS_MESSAGE[self::STATUS_CODE_ERR_REQUEST_HANDLING];
            return $response_code;
        }

		$insertOnTrans = $this->processTransaction($response_code['before_balance'],$response_code['after_balance'],$trans_record,$player_info);

		if(!$insertOnTrans['success']) {
			$response_code = [
				'statusCode' => self::STATUS_CODE_SUCCESS,
				'status' => self::STATUS_MESSAGE[self::STATUS_CODE_SUCCESS],
				'after_balance' => (int)$insertOnTrans['result']['after_balance']
			];
		}

		return $response_code;

	}

    private function processTransaction($before_balance,$after_balance,$trans_record,$player_info) {

    	$apiId = $this->gamePlatformId;
    	$transaction_type = $this->requestParams->params->action;
    	$playerId = $this->player_model->getPlayerIdByUsername($player_info['username']);
    	$SessionId = isset($trans_record->sessionId) ? $trans_record->sessionId : null;
		$UserName = isset($trans_record->partnerPlayerId) ? $trans_record->partnerPlayerId : null;
        $TimeStamp = isset($trans_record->timestamp) ? $trans_record->timestamp : null;
		$TransactionId = isset($trans_record->transactionId) ? $trans_record->transactionId : null;
        $BetAmount = isset($trans_record->betAmount) ? $this->api->gameAmountToDB($trans_record->betAmount) : null;
        $WinAmount = isset($trans_record->winAmount) ? $this->api->gameAmountToDB($trans_record->winAmount) : null;
		$Currency = isset($trans_record->currency) ? $trans_record->currency : null;
        $Selections = isset($trans_record->selections) ? $trans_record->selections : null;
        $BetPerSelection = isset($trans_record->betPerSelection) ? $trans_record->betPerSelection : null;
        $FreeGames = isset($trans_record->freeGames) ? $trans_record->freeGames : null;
		$GameId = isset($trans_record->gameId) ? $trans_record->gameId : null;
        $RoundId = isset($trans_record->round) ? $trans_record->round : null;
        $RoundsRemaining = isset($trans_record->roundsRemaining) ? $trans_record->roundsRemaining : null;
        $OperatorName = isset($trans_record->operatorName) ? $trans_record->operatorName : null;
        $Token = isset($trans_record->token) ? $trans_record->token : null;
        $CreditAmount =  isset($trans_record->amount) ? $this->api->gameAmountToDB($trans_record->amount) : null;
        $PlayerIp = isset($trans_record->playerIp) ? $trans_record->playerIp : null;

		$externalUniqueId = $TransactionId;
        $now = (new DateTime())->format('Y-m-d H:i:s');

        if(!empty($TimeStamp)) {
            $seconds = $TimeStamp / 1000;

            $dateTime = date('Y-m-d H:i:s', $seconds);

            $dateTime = $this->api->gameTimeToServerTime($dateTime);
        }

        $startTime = !empty($TimeStamp) ? $dateTime : $now;
        $endTime = !empty($TimeStamp) ? $dateTime : $now;


        if($transaction_type == SELF::PLAY || $transaction_type == SELF::REVOKE) {

            $Round = $RoundId;
            $RoundId = $TransactionId;
            $TransactionId = $TransactionId . "-" . $Round;

        } else if($transaction_type == SELF::CREDIT) {

            $CreditIndex = isset($trans_record->creditIndex) ? $trans_record->creditIndex : null;

            $RoundId = $TransactionId;
            $TransactionId = $TransactionId . "-credit-" . $CreditIndex;
        }



    	$extra_info = [
    		'playerId' => $playerId,
            'UserName' => $UserName,
            'SessionId' => $SessionId,
            'TransactionId' => $TransactionId,
            'BetAmount' => $BetAmount,
            'WinAmount' => $WinAmount,
            'Currency' => $Currency,
            'Selections' => $Selections,
            'BetPerSelection' => $BetPerSelection,
            'FreeGames' => $FreeGames,
            'GameId' => $GameId,
            'RoundId' => $RoundId,
            'RoundsRemaining' => $RoundsRemaining,
            'Timestamp' => $TimeStamp,
            'OperatorName' => $OperatorName,
            'Token' => $Token,
            'CreditAmount' => $transaction_type == SELF::CREDIT ? $CreditAmount : null,
            'PlayerIp' => $PlayerIp,
            'transaction_type' => $transaction_type
        ];


        $amount = 0;

        if($transaction_type == self::PLAY) {

            if($FreeGames) {

                $amount = $WinAmount;

            } else {

                $amount = $WinAmount - $BetAmount;

            }



        } else if($transaction_type == self::CREDIT) {
            $amount = $CreditAmount;

            $Type =  isset($trans_record->type) ? $trans_record->type : null;
            $CreditIndex = isset($trans_record->creditIndex) ? $trans_record->creditIndex : null;

            $extra_info["Type"] = $Type;
            $extra_info["CreditIndex"] = $CreditIndex;

        } else if($transaction_type == SELF::REVOKE) {

            $amount = $trans_record->amount;

            $transaction_id = $trans_record->transactionId;
            $round = $trans_record->round;

            $new_trans_id = $transaction_id . "-" . $round;


            $platform_id = $this->api->getPlatformCode();



            // check first if the revoke is already triggered

            $existingTransactionRevoke = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$new_trans_id,"transaction_id", SELF::REVOKE);

            if(!EMPTY($existingTransactionRevoke)) {

                $result = [];

                $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                $result['after_balance'] = $this->api->dBtoGameAmount($after_balance);

                return ['success' => true, 'result' => $result];

            }

            $existingTransactionPlay = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$new_trans_id,"transaction_id", SELF::PLAY);

            if(!EMPTY($existingTransactionPlay) && $trans_record->revokedAction == self::PLAY) {

                $existing_transaction_id = $existingTransactionPlay->transaction_id;

                $this->common_seamless_wallet_transactions->setTransactionStatus( $this->api->getPlatformCode(), $existing_transaction_id, 'transaction_id', 'revoked', SELF::PLAY);

                // $existing_extra_info = $existingTransactionPlay->extra_info;

                // $extra_info_arr = json_decode($existing_extra_info);

                // $FreeGames = isset($extra_info_arr->FreeGames) ? $extra_info_arr->FreeGames : false;
                // $WinAmount = $extra_info_arr->WinAmount;
                // $BetAmount = $extra_info_arr->BetAmount;
                // $extra_info["FreeGames"] = $FreeGames;
                // $extra_info["WinAmount"] = $WinAmount;
                // $extra_info["BetAmount"] = $BetAmount;

                // if($FreeGames) {

                //     $amount = $WinAmount;

                // } else {

                //     $amount = $WinAmount - $BetAmount;

                // }

            } else {

                $existingTransactionCredit = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id", SELF::CREDIT);

                if(!EMPTY($existingTransactionCredit) && $trans_record->revokedAction == self::CREDIT) {

                    $existing_transaction_id = $existingTransactionCredit->transaction_id;

                    $this->common_seamless_wallet_transactions->setTransactionStatus( $this->api->getPlatformCode(), $existing_transaction_id, 'transaction_id', 'revoked', SELF::CREDIT);

                    // $extra_info = $existingTransactionCredit->extra_info;

                    // $extra_info_arr = json_decode($extra_info);

                    // $amount = isset($extra_info_arr->CreditAmount) ? $extra_info_arr->CreditAmount : 0;
                }

                // } else {

                //     $amount = 0;
                // }

            }
            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $result['after_balance'] = $this->api->dBtoGameAmount($after_balance);
        }

        $extraInfo = json_encode($extra_info);


        $gameRecords = [
            [
                'game_platform_id' => $apiId,
                'amount' => $amount,
                'game_id' => $GameId,
                'transaction_type' => $transaction_type,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' =>  $transaction_type.'-'.$TransactionId,
                'extra_info' => $extraInfo,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'transaction_id' => $TransactionId,
                'before_balance' => $this->api->gameAmountToDB($before_balance),
                'after_balance' => $this->api->gameAmountToDB($after_balance),
                'player_id' => $playerId,
                'round_id' => $RoundId,
                'bet_amount' => !$FreeGames ? $BetAmount : 0,
                'result_amount' => $WinAmount,
                'status' => 'ok'
            ]
        ];

        $this->processGameRecords($gameRecords);

        $success=true;
        $result=[];

        $existingRow = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->gamePlatformId,$TransactionId,"transaction_id",$transaction_type);
        if(!empty($existingRow)) {
        	$dataRecord = $gameRecords[0];
            if($dataRecord["amount"] != $existingRow->amount) {

                $external_uniqueid = 'update-'.$transaction_type.'-'.$TransactionId.'-'.$before_balance.$after_balance.'-'.$RoundId;
                $gameRecords[0]['external_unique_id'] = $external_uniqueid;

                $this->common_seamless_wallet_transactions->insertRow($gameRecords[0]);

            } else {
                $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                $result['after_balance'] = $this->api->dBtoGameAmount($after_balance);
				$success=false;
            }


        } else {
                $this->common_seamless_wallet_transactions->insertRow($gameRecords[0]);
        }

        if(!array_key_exists('after_balance', $result)) {
            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            $result['after_balance'] = $this->api->dBtoGameAmount($after_balance);
        }

        return ['success' => $success, 'result' => $result];
    }

    /**
     * Process Game Records Array
     *
     * @param array $gameRecords
     * @return void
     */
    public function processGameRecords(&$gameRecords){
        $elapsed=intval($this->utils->getExecutionTimeToNow()*1000);
        if(! empty($gameRecords)){
            foreach($gameRecords as $index => $record){
                $data['game_platform_id'] = isset($record['game_platform_id']) ? $record['game_platform_id'] : null;
                $data['amount'] = isset($record['amount']) ? $record['amount'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                $data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
                $data['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_unique_id'] = isset($record['external_unique_id']) ? $record['external_unique_id'] : null;
                $data['extra_info'] = isset($record['extra_info']) ? $record['extra_info'] : null;
                $data['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                $data['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                $data['transaction_id'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                $data['elapsed_time'] = isset($record['elapsed_time']) ? $record['elapsed_time'] : $elapsed;
                $data['round_id'] = isset($record['round_id']) ? $record['round_id'] : null;
                $data['bet_amount'] = isset($record['bet_amount']) ? $record['bet_amount'] : null;
                $data['result_amount'] = isset($record['result_amount']) ? $record['result_amount'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;

                if($record['transaction_type'] == self::CREDIT){
                    $data['result_amount'] = isset($record['amount']) ? $record['amount'] : null;
                }

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }



    private function setResponse($statusCode, $message, $data = []) {

        if($statusCode == SELF::STATUS_CODE_SUCCESS)
        {
            $message = SELF::SUCCESS_MSG;
        }

    	$code = ['statusCode' => $statusCode, 'status' => $message];
        $data = array_merge($code,$data);
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['statusCode'] == self::STATUS_CODE_SUCCESS ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $data = json_encode($data);
        $fields = ['player_id' => $this->player_id];

        if ($this->requestParams->params->action == 'validateWhiteIP') {
            $http_status_code = 401;
        } else {
            $http_status_code = 200;
        }

        if($this->api) {
            $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->params->action,
                json_encode($this->requestParams->params),
                $data,
                $http_status_code,
                null,
                is_array($this->headers) ? json_encode($this->headers) : $this->headers,
                $fields
            );
        }

        $this->output->set_status_header($http_status_code)->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }


}