<?php

use function GuzzleHttp\json_decode;

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class loto_service_api extends BaseController {

	protected $gamePlatformId = LOTO_SEAMLESS_API;
	protected $currencyCode;
	private $api;
	private $requestParams;
	private $player_id;
    private $secret_key;


    // as per game provider LOTO only supports vietnamese language
    const SUCCESS_MSG = "Thành công"; // success
    const MSG_PLAYER_NOT_EXIST = "Người chơi không tồn tại"; // player not exists
    const MSG_PLAYER_LOCK = "Người chơi bị khóa"; // Player Lock
    const MSG_INSUFFICIENT_BALANCE = "Số dư của người chơi không đủ"; // Insufficient user balance
    const MSG_INVALID_PLAYER = "Không tìm thấy người chơi"; // Player cannot be found
    const MSG_INVALID_TYPE = "Không hợp lệ"; // Invalid Type
    const MSG_INVALID_AMOUNT = "Số tiền không hợp lệ"; // Invalid Amount

    const SUCCESS_CODE = 200;
    const ERR_OTHER_FAILED = 1000;
    const ERR_PLATFORM_NOT_EXIST = 1001;
    const ERR_USERNAME_ALREADY_EXIST = 1002;
    const ERR_INCORRECT_PASSWORD = 1003;
    const ERR_LOGIN_FAILED = 1004;
    const ERR_CALL_FAILED = 1005;
    const ERR_METHOD_NOT_EXIST = 1006;
    const ERR_NON_WHITELISTED_USERS = 1007;
    const ERR_SIGNATURE_ERROR = 1008;
    const ERR_AMOUNT_SYNCRONIZATION_FAILED = 1009;
    const ERR_TOKEN_ERROR = 1010;
    const ERR_USERNAME_GREATER_THAN_30 = 1011;
    const ERR_USERNAME_LESS_THAN_14 = 1012;
    const ERR_USERNAME_MUST_START_PLATFORM = 1013;

    const WIN = 4;
    const LOSS = 3;

    const ERR_STATUS_CODE = 500;

    const INSTANT_LOTTERY_ID = ["100","107"];

    const STATUS_MESSAGE = array(
        self::SUCCESS_CODE => "Thành công",
        self::ERR_OTHER_FAILED => "Không thành công.",
        self::ERR_PLATFORM_NOT_EXIST => "Nền tảng không tồn tại",
        self::ERR_USERNAME_ALREADY_EXIST => "Tên người dùng đã tồn tại",
        self::ERR_INCORRECT_PASSWORD => "Mật khẩu không đúng",
        self::ERR_LOGIN_FAILED => "Đăng nhập thất bại",
        self::ERR_CALL_FAILED => "Cuộc gọi không thành công",
        self::ERR_METHOD_NOT_EXIST => "Phương thức không tồn tại",
        self::ERR_NON_WHITELISTED_USERS => "Người dùng không có trong danh sách trắng",
        self::ERR_SIGNATURE_ERROR => "lỗi xác thực",
        self::ERR_AMOUNT_SYNCRONIZATION_FAILED => "Đồng bộ hóa số lượng không thành công",
        self::ERR_TOKEN_ERROR => "Lỗi mã thông báo",
        self::ERR_USERNAME_GREATER_THAN_30 => "Độ dài tên người dùng không vượt quá 30 ký tự",
        self::ERR_USERNAME_LESS_THAN_14 => "Giao dịch không thể thu hồi được ",
        self::ERR_USERNAME_MUST_START_PLATFORM => "Phiên không tồn tại",
    );

    const SYNCCREDIT = 'syncCredit';
    const BET = 'bet';
    const OPENWIN = 'openWin';
    const CANCEL = 'cancel';

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
        $this->secret_key = $this->api->getApiKey();
        $this->platform_key = $this->api->getLotoPlatformId();

	}

    private function generateResponseSign($type, $data){

        switch ($type) {
            case self::SYNCCREDIT:
                // MD5 (type+username+balance+timestamp+seqNo+secret key).
                $generated_sign = md5($type.$data["username"].$data["balance"].$data["timestamp"].$data["seqNo"].$this->secret_key);
                break;
            case self::BET:
                // Signature method: MD5 (seqNo+username+platform+orderNo+status+type+balance+Secret key)"].
                $generated_sign = md5($data["seqNo"].$data["username"].$data["platform"].$data["orderNo"].$data["status"].$data["type"].$data["balance"].$this->secret_key);
                break;
            case self::OPENWIN:
                // Signature method: MD5 (seqNo+username+platform+orderNo+status+type+balance+Secret key)"].
                $generated_sign = md5($data["seqNo"].$data["username"].$data["platform"].$data["orderNo"].$data["status"].$data["type"].$data["balance"].$this->secret_key);
                break;
            case self::CANCEL:
                // Signature method: MD5 (seqNo+username+platform+orderNo+status+type+balance+Secret key)"].
                $generated_sign = md5($data["seqNo"].$data["username"].$data["platform"].$data["orderNo"].$data["status"].$data["type"].$data["balance"].$this->secret_key);
                break;
            default:
                $generated_sign = "";
                break;
        }

        return $generated_sign;

    }

    public function syncCredit() {

        $params = $this->requestParams->params;

        $gameUsername = $params->username;

        $response_data = array(
            "seqNo" => $params->seqNo,
            "type" => $params->type,
            "username" => $gameUsername,
            "balance" => "0", // balance should be string
            "platform" => $params->platform,
            "timestamp" => time(),
            //"sign" => $params->sign
        );

        $valid_platform = $this->validatePlatform($params->platform);

        if(!$valid_platform) {
            $response_data["sign"] = $this->generateResponseSign(self::SYNCCREDIT, $response_data);
            return $this->setResponse(self::ERR_PLATFORM_NOT_EXIST, self::STATUS_MESSAGE[self::ERR_PLATFORM_NOT_EXIST], $response_data);
        }

        $valid_sign = $this->sign_is_valid($this->secret_key, $params);

        if($valid_sign) {

            $playerName = ltrim($gameUsername,$this->platform_key . "_");
            $playerId = $this->api->getPlayerIdByGameUsername($playerName);

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

                    $response_data["balance"] = "" . $player_balance; // balance should be string

                    $response_data["sign"] = $this->generateResponseSign(self::SYNCCREDIT, $response_data);

                    return $this->setResponse(self::SUCCESS_CODE, self::STATUS_MESSAGE[self::SUCCESS_CODE], $response_data);

                } else {

                    return $this->setResponse(self::ERR_OTHER_FAILED, self::MSG_PLAYER_LOCK);

                }

            } else {

                return $this->setResponse(self::ERR_OTHER_FAILED, self::MSG_PLAYER_NOT_EXIST);

            }

        } else {

            return $this->setResponse(self::ERR_SIGNATURE_ERROR, self::STATUS_MESSAGE[self::ERR_SIGNATURE_ERROR]);

        }

    }

    public function bet() {


        $params = $this->requestParams->params;

         $response_data = array(
            "seqNo" => $params->seqNo,
            "type" => $params->type,
            "username" => $params->username,
            "platform" => $params->platform,
            "timestamp" => time(),
            "balance" => "0",
            // "sign" => $params->sign,
        );

        $valid_platform = $this->validatePlatform($params->platform);

        if(!$valid_platform) {
            $response_data["sign"] = $this->generateResponseSign(self::BET, $response_data);
            return $this->setResponse(self::ERR_PLATFORM_NOT_EXIST, self::STATUS_MESSAGE[self::ERR_PLATFORM_NOT_EXIST], $response_data);
        }

        $gameUsername = $params->username;
        $valid_sign = $this->sign_is_valid($this->secret_key, $params);

        if($valid_sign) {

            $playerName = ltrim($gameUsername,$this->platform_key . "_");
            $playerId = $this->api->getPlayerIdByGameUsername($playerName);

            if(!empty($playerId)) {

                $player_info = $this->player_model->getPlayerInfoById($playerId);

                $response_code = array(
                    "statusCode" => self::SUCCESS_CODE,
                    "status" => self::STATUS_MESSAGE[self::SUCCESS_CODE]
                );

                $self = $this;

                $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
                    $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];

                    $player_balance = $self->api->dBtoGameAmount($player_balance);

                    $bet_amount = $params->money;

                    if(!is_numeric($bet_amount)) {
                        $response_code['statusCode'] = self::ERR_STATUS_CODE;
                        $response_code['status'] = self::MSG_INVALID_AMOUNT;
                        return false;
                    }

                    if (($player_balance - $bet_amount) < 0) {
                        $response_code['statusCode'] = self::ERR_STATUS_CODE;
                        $response_code['status'] = self::MSG_INSUFFICIENT_BALANCE;
                        return false;
                    }

                    $round_id = $params->seqNo;

                    $bet_details = $params->betDetails;
                    $lottery_id = $bet_details->lotteryId;
                    $lottery_list = $bet_details->lotteryList;


                    $platform_id = $self->api->getPlatformCode();

                    foreach($lottery_list as $lottery) {

                        $transaction_id = $lottery->orderNo;

                        $existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::BET);

                        if(!empty($existingRow)) {
                            continue;
                        }

                        $response_code = $self->adjustWallet(self::BET, $player_info, $params, $lottery);

                        if($response_code['statusCode'] != self::SUCCESS_CODE) {
                            return false;
                        }

                        if($response_code['statusCode'] != self::SUCCESS_CODE && $response_code['status'] == self::UPDATE_INSERT_MESSAGE) {
                            return false;
                        }
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

                    $response_data["balance"] = "" . $balance; // balance should be string

                    $response_data["sign"] = $this->generateResponseSign(self::BET, $response_data);

                    return $this->setResponse($response_code["statusCode"], $response_code["status"], $response_data);

                } else {

                    $balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                    $balance = $this->api->dBtoGameAmount($balance);
                    $response_data["balance"] = "" . $balance;

                    $response_data["sign"] = $this->generateResponseSign(self::BET, $response_data);

                    if($response_code["statusCode"] == self::SUCCESS_CODE) { // this scenario is for if player is locked and as you can see the default value for response code is success

                        // it should not be success if failed. lockAndTransForPlayerBalance

                        return $this->setResponse(self::ERR_STATUS_CODE, self::MSG_PLAYER_LOCK, $response_data);

                    } else {

                        return $this->setResponse($response_code["statusCode"], $response_code["status"], $response_data);

                    }
                }

            } else {

                $response_data["sign"] = $this->generateResponseSign(self::BET, $response_data);


                return $this->setResponse(self::ERR_STATUS_CODE, self::MSG_INVALID_PLAYER, $response_data);
            }


        } else {

            $response_data["sign"] = $this->generateResponseSign(self::BET, $response_data);

            return $this->setResponse(self::ERR_SIGNATURE_ERROR, self::STATUS_MESSAGE[self::ERR_SIGNATURE_ERROR], $response_data);

        }

    }

    public function openWin() {


        $params = $this->requestParams->params;

        $datas = $params->data;

        $response_arr = array();

        foreach($datas as $data) {

            $response_data = array(
                "seqNo" => $data->seqNo,
                "type" => $data->type,
                "username" => $data->username,
                "platform" => $data->platform,
                "timestamp" => time(),
                "balance" => "0",
                "orderNo" => $data->orderNo
                // "sign" => $data->sign,
            );

            $valid_platform = $this->validatePlatform($data->platform);

            if(!$valid_platform) {

                $response_data["status"] = self::ERR_PLATFORM_NOT_EXIST;
                $response_data["msg"] = self::STATUS_MESSAGE[self::ERR_PLATFORM_NOT_EXIST];

                $response_data["sign"] = $this->generateResponseSign(self::OPENWIN, $response_data);

                $response_arr[] = $response_data;

                continue;
            }

            $gameUsername = $data->username;
            $valid_sign = $this->sign_is_valid($this->secret_key, $data);

            if($valid_sign) {

                $playerName = ltrim($gameUsername,$this->platform_key . "_");
                $playerId = $this->api->getPlayerIdByGameUsername($playerName);

                if(!empty($playerId)) {

                    $player_info = $this->player_model->getPlayerInfoById($playerId);

                    $response_code = array(
                        "statusCode" => self::SUCCESS_CODE,
                        "status" => self::STATUS_MESSAGE[self::SUCCESS_CODE]
                    );

                    $self = $this;

                    $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $data) {
                        $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];

                        $player_balance = $self->api->dBtoGameAmount($player_balance);


                        $round_id = $data->seqNo;

                        $transaction_id = $data->orderNo;

                        if(isset($data->money)) {
                            if(!is_numeric($data->money)) {
                                $response_code['statusCode'] = self::ERR_STATUS_CODE;
                                $response_code['status'] = self::MSG_INVALID_AMOUNT;
                                return false;
                            }
                        }

                        $platform_id = $self->api->getPlatformCode();

                        $existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::OPENWIN);

                        if(!empty($existingRow)) {
                            $response_code['after_balance'] = $player_balance;
                            return true;
                        }

                        $response_code = $self->adjustWallet(self::OPENWIN, $player_info, $data);

                        if($response_code['statusCode'] != self::SUCCESS_CODE) {
                            return false;
                        }

                        if($response_code['statusCode'] != self::SUCCESS_CODE && $response_code['status'] == self::UPDATE_INSERT_MESSAGE) {
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

                        $response_data["balance"] = "". $balance;
                        $response_data["status"] = $response_code["statusCode"];
                        $response_data["msg"] = $response_code["status"];

                        $response_data["sign"] = $this->generateResponseSign(self::OPENWIN, $response_data);

                    } else {

                        $balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        $balance = $this->api->dBtoGameAmount($balance);
                        $response_data["balance"] = "". $balance;

                        if($response_code["statusCode"] == self::SUCCESS_CODE) { // this scenario is for if player is locked and as you can see the default value for response code is success

                            // // it should not be success if failed. lockAndTransForPlayerBalance
                            $response_data["status"] = self::ERR_STATUS_CODE;
                            $response_data["msg"] = self::MSG_PLAYER_LOCK;

                        } else {

                            $response_data["status"] = $response_code["statusCode"];
                            $response_data["msg"] = $response_code["status"];

                        }

                        $response_data["sign"] = $this->generateResponseSign(self::OPENWIN, $response_data);
                    }

                    $response_arr[] = $response_data;

                } else {

                    $response_data["status"] = self::ERR_STATUS_CODE;
                    $response_data["msg"] = self::MSG_INVALID_PLAYER;

                    $response_arr[] = $response_data;
                }

            }  else {

                //return $this->setResponse(self::ERR_SIGNATURE_ERROR, self::STATUS_MESSAGE[self::ERR_SIGNATURE_ERROR], $response_data);
                $response_data["status"] = self::ERR_SIGNATURE_ERROR;
                $response_data["msg"] = self::STATUS_MESSAGE[self::ERR_SIGNATURE_ERROR];

                $response_data["sign"] = $this->generateResponseSign(self::OPENWIN, $response_data);

                $response_arr[] = $response_data;

            }

        }

        return $this->setOutput($response_arr, false, self::OPENWIN);
    }

    public function cancel() {


        $params = $this->requestParams->params;

        $datas = $params->data;

        $response_arr = array();

        foreach($datas as $data) {

            $response_data = array(
                "seqNo" => $data->seqNo,
                "type" => $data->type,
                "username" => $data->username,
                "platform" => $data->platform,
                "timestamp" => time(),
                "balance" => "0",
                "orderNo" => $data->orderNo
                // "sign" => $data->sign,
            );

            $valid_platform = $this->validatePlatform($data->platform);

            if(!$valid_platform) {

                $response_data["status"] = self::ERR_PLATFORM_NOT_EXIST;
                $response_data["msg"] = self::STATUS_MESSAGE[self::ERR_PLATFORM_NOT_EXIST];

                $response_data["sign"] = $this->generateResponseSign(self::CANCEL, $response_data);

                $response_arr[] = $response_data;

                continue;
            }

            $gameUsername = $data->username;
            $valid_sign = $this->sign_is_valid($this->secret_key, $data);

            if($valid_sign) {

                $playerName = ltrim($gameUsername,$this->platform_key . "_");
                $playerId = $this->api->getPlayerIdByGameUsername($playerName);

                if(!empty($playerId)) {

                    $player_info = $this->player_model->getPlayerInfoById($playerId);

                    $response_code = array(
                        "statusCode" => self::SUCCESS_CODE,
                        "status" => self::STATUS_MESSAGE[self::SUCCESS_CODE]
                    );

                    $self = $this;

                    $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $data) {
                        $player_balance = $self->api->queryPlayerBalance($player_info['username'])['balance'];

                        $player_balance = $self->api->dBtoGameAmount($player_balance);


                        $round_id = $data->seqNo;

                        $transaction_id = $data->orderNo;

                        if(isset($data->money)) {
                            if(!is_numeric($data->money)) {
                                $response_code['statusCode'] = self::ERR_STATUS_CODE;
                                $response_code['status'] = self::MSG_INVALID_AMOUNT;
                                return false;
                            }
                        }


                        $platform_id = $self->api->getPlatformCode();

                        $existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::CANCEL);

                        if(!empty($existingRow)) {
                            $response_code['after_balance'] = $player_balance;
                            return true;
                        }

                        $response_code = $self->adjustWallet(self::CANCEL, $player_info, $data);

                        if($response_code['statusCode'] != self::SUCCESS_CODE) {
                            return false;
                        }

                        if($response_code['statusCode'] != self::SUCCESS_CODE && $response_code['status'] == self::UPDATE_INSERT_MESSAGE) {
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

                        $response_data["balance"] = "". $balance;
                        $response_data["status"] = $response_code["statusCode"];
                        $response_data["msg"] = $response_code["status"];


                    } else {

                        $balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
                        $balance = $this->api->dBtoGameAmount($balance);

                        $response_data["balance"] = "". $balance;

                        if($response_code["statusCode"] == self::SUCCESS_CODE) { // this scenario is for if player is locked and as you can see the default value for response code is success

                            // // it should not be success if failed. lockAndTransForPlayerBalance
                            $response_data["status"] = self::ERR_STATUS_CODE;
                            $response_data["msg"] = self::MSG_PLAYER_LOCK;

                        } else {

                            $response_data["status"] = $response_code["statusCode"];
                            $response_data["msg"] = $response_code["status"];

                        }
                    }

                    $response_data["sign"] = $this->generateResponseSign(self::CANCEL, $response_data);

                    $response_arr[] = $response_data;

                } else {

                    $response_data["status"] = self::ERR_STATUS_CODE;
                    $response_data["msg"] = self::MSG_INVALID_PLAYER;

                    $response_data["sign"] = $this->generateResponseSign(self::CANCEL, $response_data);

                    $response_arr[] = $response_data;
                }

            }  else {

                //return $this->setResponse(self::ERR_SIGNATURE_ERROR, self::STATUS_MESSAGE[self::ERR_SIGNATURE_ERROR], $response_data);
                $response_data["status"] = self::ERR_SIGNATURE_ERROR;
                $response_data["msg"] = self::STATUS_MESSAGE[self::ERR_SIGNATURE_ERROR];

                $response_data["sign"] = $this->generateResponseSign(self::CANCEL, $response_data);

                $response_arr[] = $response_data;

            }

        }

        return $this->setOutput($response_arr, false, self::CANCEL);
    }

    private function sign_is_valid($secret, $params) {

        $sign = $params->sign;
        $type = $params->type;

        switch ($type) {
            case self::SYNCCREDIT:
                // Signature method：MD5( type+username+platform+seqNo+Secret Key )。
                $generated_sign = md5($type.$params->username.$params->platform.$params->seqNo.$secret);
                break;
            case self::BET:
                // MD5 (seqNo+username+platform+type+money+secret key).
                $generated_sign = md5($params->seqNo.$params->username.$params->platform.$params->type.$params->money.$secret);
                break;
            case self::OPENWIN:
                // Signature method: MD5 (seqNo+username+platform+orderNo+status+type+money+Secret key).
                $generated_sign = md5($params->seqNo.$params->username.$params->platform.$params->orderNo.$params->status.$params->type.$params->money.$secret);
                break;
            case self::CANCEL:
                // Signature method: MD5(seqNo+Username+Platform+orderNo+status+type+money+Secret secret)
                $generated_sign = md5($params->seqNo.$params->username.$params->platform.$params->orderNo.$params->status.$params->type.$params->money.$secret);
                break;
            default:
                return false;
                break;
        }


        if($sign == $generated_sign) {
            return true;
        } else {
            return false;
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


	private function adjustWallet($action, $player_info, $params, $lottery = null) {

        $existingTransaction = array();

		$response_code = array(
            "statusCode" => self::SUCCESS_CODE,
            "status" => self::STATUS_MESSAGE[self::SUCCESS_CODE]
        );

        $before_balance =  $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $response_code['before_balance'] = $this->api->dBtoGameAmount($before_balance);

        if($action == self::BET) {

            $bet_amount = $lottery->money;

            if($bet_amount > 0) {

                    $bet_amount = $this->api->gameAmountToDB($bet_amount);

                    $this->CI->utils->debug_log('LOTO SEAMLESS BET AMOUNT', $bet_amount);
                    $deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $bet_amount);
                    $this->CI->utils->debug_log('LOTO SEAMLESS DEDUCT BALANCE', $deduct_balance);

            }

            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];


            $response_code["after_balance"] = $this->api->dBtoGameAmount($after_balance);

        } elseif ($action == self::OPENWIN) {

            $status = $params->status;

            if($status == self::WIN) {

                $amount = $params->money;

                if($amount > 0) {

                    $amount = $this->api->gameAmountToDB($amount);

                    $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
                    $this->CI->utils->debug_log('LOTO SEAMLESS INCREASE BALANCE', $add_balance);

                }



            }

            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->api->dBtoGameAmount($after_balance);

        } elseif ($action == self::CANCEL) {

            $bet_amount = $params->money;

            if($bet_amount > 0) {

                    $bet_amount = $this->api->gameAmountToDB($bet_amount);

                    $this->CI->utils->debug_log('LOTO SEAMLESS BET AMOUNT', $bet_amount);
                    $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $bet_amount);
                    $this->CI->utils->debug_log('LOTO SEAMLESS INCREASE BALANCE', $add_balance);

            }

            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->api->dBtoGameAmount($after_balance);


        } else {
            $response_code['statusCode'] = self::ERR_STATUS_CODE;
            $response_code['status'] = self::MSG_INVALID_TYPE;
            return $response_code;
        }
        // }


		$insertOnTrans = $this->processTransaction($response_code['before_balance'],$response_code['after_balance'],$params,$player_info, $lottery);

		if(!$insertOnTrans['success']) {
			$response_code = [
				'statusCode' => self::SUCCESS_CODE,
				'status' => self::STATUS_MESSAGE[self::SUCCESS_CODE],
				'after_balance' => (int)$insertOnTrans['result']['after_balance']
			];
		}

		return $response_code;

	}

    private function processTransaction($before_balance,$after_balance,$params,$player_info, $lottery) {

    	$apiId = $this->gamePlatformId;

        $playerId = $this->player_model->getPlayerIdByUsername($player_info['username']);

    	$transaction_type = $params->type;
        $Username = isset($params->username) ? $params->username : null;
        $RoundId = isset($params->seqNo) ? $params->seqNo : null;
        $Platform = isset($params->platform) ? $params->platform : null;
        $Sign = isset($params->sign) ? $params->sign : null;

        $now = (new DateTime())->format('Y-m-d H:i:s');

        $startTime = $now;
        $endTime = $now;

        $WinAmount = 0;

        $extra_info = [
    		'PlayerId' => $playerId,
            'UserName' => $Username,
            'TransactionType' => $transaction_type,
            'SeqNo' => $RoundId,
            'Platform' => $Platform,
            'Sign' => $Sign
        ];

        if($transaction_type == self::BET) {
            $BetAmount = isset($lottery->money) ? $lottery->money : null;
            $TransactionId = isset($lottery->orderNo) ? $lottery->orderNo : null;
            $Codes = isset($lottery->codes) ? $lottery->codes : null;
            $Issue = isset($lottery->issue) ? $lottery->issue : null;
            $IP = isset($lottery->ip) ? $lottery->ip : null;
            $MethodId = isset($lottery->methodId) ? $lottery->methodId : null;
            $MethodName = isset($lottery->methodName) ? $lottery->methodName : null;
            $Remark = isset($lottery->remark) ? $lottery->remark : null;
            $BetType = isset($lottery->remark) ? $lottery->remark : null;
            $Times = isset($lottery->times) ? $lottery->times : null;

            $LotteryId = isset($params->betDetails->lotteryId) ? $params->betDetails->lotteryId : null;
            // for openwin, lottery id is not set. need to get the game id on existing bet transaction

            // NOTE: Originally, there is no startTime and openTime, I just requested it to the game provider to provide us in their request.
            $IssueStartTime = isset($lottery->startTime) ? $lottery->startTime : $now;

            $startTime = $now; // time when you bet
            $endTime = isset($lottery->openTime) ? $lottery->openTime : $now; // draw time


            $extra_info["OrderNo"] = $TransactionId;
            $extra_info["BetAmount"] = $BetAmount;
            $extra_info["Codes"] = $Codes;
            $extra_info["Issue"] = $Issue;
            $extra_info["IP"] = $IP;
            $extra_info["MethodId"] = $MethodId;
            $extra_info["MethodName"] = $MethodName;
            $extra_info["Remark"] = $Remark;
            $extra_info["BetType"] = $BetType;
            $extra_info["Times"] = $Times;
            $extra_info["LotteryId"] = $LotteryId;
            $extra_info["StartTime"] = $IssueStartTime; // a time when the issue start not the bet time
            $extra_info["EndTime"] = isset($lottery->endTime) ? $lottery->endTime : null;; // sales end time
            $extra_info["OpenTime"] = $endTime; // draw time

        } else if($transaction_type == self::OPENWIN) {
            $WinAmount = isset($params->money) ? $params->money : null;
            $TransactionId = isset($params->orderNo) ? $params->orderNo : null;
            $Status = isset($params->status) ? $params->status : null;
            $WinNumber = isset($params->winNumber) ? $params->winNumber : null;

            $extra_info["OrderNo"] = $TransactionId;
            $extra_info["WinAmount"] = $WinAmount;
            $extra_info["Status"] = $Status;
            $extra_info["WinNumber"] = $WinNumber;

            // get the existing lottery id per round id and transaction id
            $existingTransaction = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(),$TransactionId,"transaction_id",SELF::BET);

            $LotteryId = null;
            if(!empty($existingTransaction)) {
                $LotteryId = $existingTransaction->game_id;
                $startTime = $existingTransaction->start_at;
                $endTime = $existingTransaction->end_at;

                $existing_transaction_id = $existingTransaction->transaction_id;

                $this->common_seamless_wallet_transactions->setTransactionStatus( $this->api->getPlatformCode(), $existing_transaction_id, 'transaction_id', 'settled', SELF::BET);
            }

            $amount = $WinAmount;
        } else if($transaction_type == self::CANCEL) {

            $BetAmount = isset($params->money) ? $params->money : null;
            $TransactionId = isset($params->orderNo) ? $params->orderNo : null;
            $Status = isset($params->status) ? $params->status : null;
            $WinNumber = isset($params->winNumber) ? $params->winNumber : null;

            $extra_info["OrderNo"] = $TransactionId;
            $extra_info["BetAmount"] = $BetAmount;
            $extra_info["Status"] = $Status;

            // get the existing lottery id per round id and transaction id
            $existingTransaction = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(),$TransactionId,"transaction_id",SELF::BET);

            $LotteryId = null;
            if(!empty($existingTransaction)) {

                $LotteryId = $existingTransaction->game_id;
                $startTime = $existingTransaction->start_at;
                $endTime = $existingTransaction->end_at;

                $existing_transaction_id = $existingTransaction->transaction_id;

                $this->common_seamless_wallet_transactions->setTransactionStatus( $this->api->getPlatformCode(), $existing_transaction_id, 'transaction_id', 'cancelled', SELF::BET);
            }

            $amount = $BetAmount;

        }

        $extraInfo = json_encode($extra_info);


        $gameRecords = [
            [
                'game_platform_id' => $apiId,
                'amount' => $transaction_type == self::BET ? $this->api->gameAmountToDB($BetAmount) : $this->api->gameAmountToDB($amount),
                'game_id' => $LotteryId,
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
                $data['status'] = isset($record['status']) ? $record['status'] : null;

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }



    private function setResponse($statusCode, $message, $data = []) {

        if($statusCode == SELF::SUCCESS_CODE)
        {
            $message = SELF::SUCCESS_MSG;
        }

    	$code = ['status' => $statusCode, 'msg' => $message];
        $data = array_merge($code,$data);
        return $this->setOutput($data);
    }

    private function setOutput($data = [], $is_bet = true, $transaction_type = NULL) {

        if($is_bet){
            $flag = $data['status'] == self::SUCCESS_CODE ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        } else {
            $flag = Response_result::FLAG_NORMAL;
        }

        $data = json_encode($data);
        $fields = ['player_id' => $this->player_id];

        if($this->api) {
            $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $is_bet ? $this->requestParams->params->type : $transaction_type,
                json_encode($this->requestParams->params),
                $data,
                200,
                null,
                null,
                $fields
            );
        }

        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

    public function validatePlatform($request_platform_code) {

        if($this->platform_key == $request_platform_code) {
            return true;
        }

        return false;

    }

}