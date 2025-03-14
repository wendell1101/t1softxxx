<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * DIGITAIN game integration
 * OGP-24704
 *
 * @author  Jerbey Capoquian
 * GetBalance
 * GetUserInfo
 * CreditBet
 * DebitByBatch
 * ChequeRedact
 * RollBackByBatch
 *
 * By function:
    
 *
 * 
 * Related File
     - game_api_digitain_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Digitain_seamless_game_service_api extends BaseController {

    const STATUS_CODE_INVALID_IP = 401;
    const ALLOWED_METHOD_PARAMS = ['GetBalance', 'GetUserInfo', 'CreditBet', 'DebitByBatch', 'ChequeRedact', 'RollBackByBatch'];
    const SUCCESS = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_UNKNOWN = 0;

    #trans type increase
    const TRANS_TYPE_WIN = 2;
    const TRANS_TYPE_DEBITINCREASE = 4;
    const TRANS_TYPE_BOREDRAWMONEYBACKWIN = 5;
    #trans type decrease
    const TRANS_TYPE_DEBITDECREASE = 3;
    const TRANS_TYPE_BOREDRAWMONEYBACKDECREASE = 6;

    const TRANSACTION_TYPE_TO_INCREASE = [self::TRANS_TYPE_WIN, self::TRANS_TYPE_DEBITINCREASE, self::TRANS_TYPE_BOREDRAWMONEYBACKWIN];
    const TRANSACTION_TYPE_TO_DECREASE = [self::TRANS_TYPE_DEBITDECREASE, self::TRANS_TYPE_BOREDRAWMONEYBACKDECREASE];

    #Response Code

    const RESPONSE_SUCCESS = [
        "ResponseCode" => 0,
        "Description" => "Success"
    ];

    const RESPONSE_NETWORKERROR = [
        "ResponseCode" => 1,
        "Description" => "NetworkError"
    ];

    const RESPONSE_CLIENTBLOCKED = [
        "ResponseCode" => 13,
        "Description" => "ClientBlocked"
    ];

    const RESPONSE_ACCOUNTNOTFOUND = [
        "ResponseCode" => 19,
        "Description" => "AccountNotFound" //(If in Token and ClientId couple of something wrong)
    ];

    const RESPONSE_CURRENCYNOTEXISTS = [
        "ResponseCode" => 20,
        "Description" => "CurrencyNotExists"//(Currency does not exist)
    ];

    const RESPONSE_GENERALEXCEPTION = [
        "ResponseCode" => 21,
        "Description" => "GeneralException"
    ];

    const RESPONSE_CLIENTNOTFOUND = [
        "ResponseCode" => 22,
        "Description" => "ClientNotFound"//(ClientId does not exist)
    ];

    const RESPONSE_DOCUMENTNOTFOUND = [
        "ResponseCode" => 28,
        "Description" => "DocumentNotFound"//(If in the system, there is no CreditBet by the sameOrder Number)
    ];

    const RESPONSE_WRONGTOKEN = [
        "ResponseCode" => 37,
        "Description" => "WrongToken"
    ];

    const RESPONSE_CLIENTMAXLIMITEXCEEDED = [
        "ResponseCode" => 45,
        "Description" => "ClientMaxLimitExceeded"  
    ];

    const RESPONSE_TRANSACTIONALREADYEXISTS = [
        "ResponseCode" => 46,
        "Description" => "TransactionAlreadyExists"
    ];

    const RESPONSE_CANNOTDELETEROLLBACKDOCUMENT = [
        "ResponseCode" => 56,
        "Description" => "CanNotDeleteRollbackDocument"
    ];

    const RESPONSE_DOCUMENTALREADYROLLBACKED = [
        "ResponseCode" => 58,
        "Description" => "DocumentAlreadyRollbacked"
    ];

    const RESPONSE_NOTALLOWED = [
        "ResponseCode" => 68,
        "Description" => "NotAllowed"//(CreditBet by the same OrderNumber)
    ];

    const RESPONSE_PARTNERNOTFOUND = [
        "ResponseCode" => 70,
        "Description" => "PartnerNotFound"
    ];

    const RESPONSE_LOWBALANCE = [
        "ResponseCode" => 71,
        "Description" => "LowBalance"//(see Note2)
    ];

    const RESPONSE_BADREQUEST = [
        "ResponseCode" => 400,
        "Description" => "BadRequest"
    ];

    const RESPONSE_FORBIDDEN = [
        "ResponseCode" => 403,
        "Description" => "Forbidden"
    ];

    const RESPONSE_INTERNALSERVERERROR = [
        "ResponseCode" => 500,
        "Description" => "InternalServerError"
    ];

    const RESPONSE_INVALIDINPUTPARAMETERS = [
        "ResponseCode" => 1013,
        "Description" => "InvalidInputParameters"//(for example,, invalid BetState, WinType)
    ];

    const INVALIDSIGNATURE = [
        "ResponseCode" => 1016,
        "Description" => "InvalidSignature"
    ];

    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'common_seamless_error_logs', 'external_system', 'player_model'));
    }


    public function index($method = null) {
        if(empty($method)){
            return $this->returnJsonResult(self::RESPONSE_BADREQUEST);
        }

        $api = DIGITAIN_SEAMLESS_API;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->returnJsonResult(self::RESPONSE_INTERNALSERVERERROR);
        } 

        $this->request_headers = $this->input->request_headers();
        $request_json = file_get_contents('php://input');
        $this->request = $request = json_decode($request_json, true);
        $this->request_method = $method;

        $this->utils->debug_log('DIGITAIN_SEAMLESS_API service request_headers', $this->request_headers);
        $this->utils->debug_log('DIGITAIN_SEAMLESS_API service method', $method);
        $this->utils->debug_log('DIGITAIN_SEAMLESS_API service request', $request);
        $this->player_id = null;

        if($method == "generate_sign"){
            $param_method=  filter_input(INPUT_GET, 'method', FILTER_SANITIZE_URL);
            $order_fields=  explode(',', filter_input(INPUT_GET, 'order', FILTER_SANITIZE_URL));
            return $this->generateSign($param_method, $request, $json_output = true, $order_fields);
        }

        $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }

        if(!$this->api) {
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::RESPONSE_FORBIDDEN;
            $error_response['Description'] = "Forbidden: IP address rejected.({$ip})";
            return $this->setResponse($error_response);
        }
    
        if(!$this->external_system->isGameApiActive($api) || $this->external_system->isGameApiMaintenance($api)) {
            return $this->setResponse(self::RESPONSE_INTERNALSERVERERROR);
        }

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        if(!in_array($method, self::ALLOWED_METHOD_PARAMS)) {
            return $this->setResponse(self::RESPONSE_BADREQUEST);
        }

        return $this->$method($request);
    }

    private function generateSign($method, $request, $json_output = false, $order_fields = []){
        if(!empty($order_fields)){
            $request  = array_replace(array_flip($order_fields), $request);#reorder by order_fields
        }

        $sign = "";
        if(!empty($request)){
            $request_sign = null;
            if(isset($request['Signature'])){
                $request_sign = $request['Signature'];
                unset($request['Signature']);
            }
            $str = "method{$method}";
            foreach ($request as $key => $value) {
                if(!empty($order_fields)){
                    if(!in_array($key, $order_fields)){
                        continue;
                    }
                }
                $str .= "{$key}{$value}";
            }
            $str .= $this->api->secret_key;
            // echo "<pre>";
            // echo $str;
            // echo "<br>";
            $sign = md5($str);   
            $this->utils->debug_log('validateSign', "generated_sign", $sign, "request_sign", $request_sign, "string", $str);
        }
        if($json_output){
            $response = array(
                "sign" => $sign,
                "string" => $str
            );
            $this->returnJsonResult($response);
        } else {
            return $sign;
        }  
    }

    private function validateSign($request, $order_fields = []){
        $json_output = false;
        $valid = false;
        if(!empty($request)){
            if(isset($request['Signature'])){
                $request_sign = $request['Signature'];
                $sign = $this->generateSign($this->request_method, $request, $json_output, $order_fields);
                $valid = $sign == $request_sign;

                // $request_sign = $request['Signature'];
                // unset($request['Signature']);
                // $str = "method{$this->request_method}";
                // foreach ($request as $key => $value) {
                //     if(!empty($order_fields)){
                //         if(!in_array($key, $order_fields)){
                //             continue;
                //         }
                //     }
                //     $str .= "{$key}{$value}";
                // }
                // $str .= $this->api->secret_key;
                // $sign = md5($str);
                // $valid = $sign == $request_sign;
                // $this->utils->debug_log('validateSign', "generated_sign", $sign, "request_sign", $request_sign, "string", $str);
            }
        }
        return $valid;
    }

    private function validateRequest($request, $required_fields) {
        $request_keys = array_keys($request);
        $valid = true;
        if(!empty($required_fields)){
            foreach ($required_fields as $field) {
                if(!in_array($field, $request_keys)){
                   $valid = false;
                   break;
                } 
            }
        }
        return $valid;
    }

    private function isValidAmount($amount){
        $amount= trim($amount);
        if(!is_numeric($amount)) {
            return false;
        } else {
            if($amount < 0 ){
                return false;
            }
            return true;
        }
    }

    private function CreditBet($request){
        $order_fields = ['PartnerId', 'TimeStamp', 'Token', 'CurrencyId', 'OrderNumber', 'GameId', 'TransactionId', 'Info', 'DeviceTypeId', 'TypeId', 'BetState', 'PossibleWin'];
        $required_fields = ['PartnerId', 'TimeStamp', 'Token', 'CurrencyId', 'OrderNumber', 'GameId', 'TransactionId', 'Info', 'DeviceTypeId', 'TypeId', 'BetState', 'PossibleWin', 'OperationItems', 'BetCommission', 'Signature', 'ViewTypeID', 'TrackingId', 'IpAddress', 'Order'];
        $response_order_fields = ['ResponseCode', 'Description', 'TimeStamp', 'TransactionId'];
        // if(!$this->validateRequest($request, $required_fields)){
        //     $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
        //     return $this->setResponse($response);
        // }

        if(!$this->validateSign($request, $order_fields)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::INVALIDSIGNATURE, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $partner_id = isset($request['PartnerId']) ? $request['PartnerId'] : null;
        if(empty($partner_id) || $partner_id != $this->api->partner_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_PARTNERNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $currency = isset($request['CurrencyId']) ? $request['CurrencyId'] : null;
        if(empty($currency) || $currency != $this->api->currency){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CURRENCYNOTEXISTS, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $token = isset($request['Token']) ? $request['Token']: null;
        if(empty($token)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $external_account_id = isset($request['OperationItems'][0]['ClientId']) ? $request['OperationItems'][0]['ClientId'] : null;
        $this->player_id = $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(empty($external_account_id) || !$player_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $game_username = $this->api->getGameUsernameByPlayerId($player_id);
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $player_token = $this->common_token->getValidPlayerToken($player_id);
        if($player_token != $token){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $unique_id = isset($request['TransactionId']) ? $request['TransactionId'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $unique_id);
        if($isTransactionExist){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_TRANSACTIONALREADYEXISTS, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $bet_id = isset($request['OrderNumber']) ? $request['OrderNumber'] : null;
        $bet_Details = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $bet_id, 'CreditBet');
        if(!empty($bet_Details)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_NOTALLOWED, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        if(!isset($request['OperationItems'][0]['Amount']) || !$this->isValidAmount($request['OperationItems'][0]['Amount'])){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
        $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, &$request, &$error_code) {
            $amount = isset($request['OperationItems'][0]['Amount']) ? $this->api->gameAmountToDB($request['OperationItems'][0]['Amount']) : null;
            $player_name = $player_details['username'];
            $before_balance = $this->getPlayerBalance($player_name);
            $success = false; #default
            if($this->utils->compareResultFloat($amount, '>', 0)) {
                if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                    $error_code = self::RESPONSE_LOWBALANCE;
                    return false;
                }

                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                    $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                } else {
                    $success = $this->wallet_model->decSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            #proceed on success adjustment
            if($success){
                
                $success = false; #reset $success
                $afterBalance = $this->getPlayerBalance($player_name);
                $request['before_balance'] = $before_balance;
                $request['player_id'] = $player_details['player_id'];
                $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;
                $request['transaction_type'] = 'CreditBet';
                $request['amount'] = $request['bet_amount'] = $amount;
                $request['result_amount'] = -$amount;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $error_code = self::RESPONSE_SUCCESS;
                    $request['OperationOutputItem'] = array(
                        "ClientId" => $player_details['player_id'],
                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                        "CurrencyId" => $this->api->currency
                    );
                }

            } else {
                $error_code = self::RESPONSE_INTERNALSERVERERROR; #not enough balance or invalid amount
            }
            return $success;
        });
        $response = $this->getDefaultResponseFormat(__FUNCTION__, $error_code, $request, $response_order_fields);
        return $this->setResponse($response);
    }

    private function ChequeRedact($request){
        $order_fields = ['PartnerId', 'TimeStamp', 'OrderNumber', 'TransactionId', 'Info', 'PossibleWin'];
        $required_fields = ['OrderNumber', 'TransactionId', 'Info', 'PossibleWin', 'PartnerId', 'Order', 'TimeStamp', 'Signature'];
        
        // if(!$this->validateRequest($request, $required_fields)){
        //     $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request);
        //     return $this->setResponse($response);
        // }

        if(!$this->validateSign($request, $order_fields)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::INVALIDSIGNATURE, $request);
            return $this->setResponse($response);
        }

        $partner_id = isset($request['PartnerId']) ? $request['PartnerId'] : null;
        if(empty($partner_id) || $partner_id != $this->api->partner_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_PARTNERNOTFOUND, $request);
            return $this->setResponse($response);
        }

        $unique_id = isset($request['TransactionId']) ? $request['TransactionId'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $unique_id);
        if($isTransactionExist){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_TRANSACTIONALREADYEXISTS, $request);
            return $this->setResponse($response);
        }

        $bet_id = isset($request['OrderNumber']) ? $request['OrderNumber'] : null;
        $bet_Details = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $bet_id, 'CreditBet');
        if(empty($bet_Details)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_DOCUMENTNOTFOUND, $request);
            return $this->setResponse($response);
        }

        $rollback_details = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $bet_id, 'RollBackByBatch');
        if(!empty($rollback_details)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_NOTALLOWED, $request);
            return $this->setResponse($response);
        }

        $data = $request;
        $data['response_result_id'] = $this->response_result_id;
        $data['transaction_type'] = __FUNCTION__;
        $transId = $this->processRequestData($data);
        if($transId){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_SUCCESS, $request);
            return $this->setResponse($response);
        } else {
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INTERNALSERVERERROR, $request);
            return $this->setResponse($response);
        }
    }

    private function DebitByBatch($request){
        $order_fields = ['PartnerId', 'TimeStamp'];
        $required_fields = ['PartnerId', 'TimeStamp', 'Items'];
        $response_order_fields = ['ResponseCode', 'Description', 'TimeStamp'];
        // if(!$this->validateRequest($request, $required_fields)){
        //     $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
        //     return $this->setResponse($response);
        // }

        if(!$this->validateSign($request, $order_fields)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::INVALIDSIGNATURE, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $partner_id = isset($request['PartnerId']) ? $request['PartnerId'] : null;
        if(empty($partner_id) || $partner_id != $this->api->partner_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_PARTNERNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $items = isset($request['Items']) ? $request['Items'] : [];
        $order_trans = array_column($items, 'TransactionId');
        array_multisort($order_trans, SORT_ASC, $items);#order by trans id

        $response_item =[];
        $order_number_encounter_error = [];
        if(!empty($items)){
            foreach ($items as $key => $item) {
                if(in_array($item['OrderNumber'], $order_number_encounter_error)){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_NOTALLOWED, $item);
                    continue;
                }

                $currency = isset($item['CurrencyId']) ? $item['CurrencyId'] : null;
                if(empty($currency) || $currency != $this->api->currency){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_CURRENCYNOTEXISTS, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }
                
                $unique_id = isset($item['TransactionId']) ? $item['TransactionId'] : null;
                $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $unique_id);
                if($isTransactionExist){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_TRANSACTIONALREADYEXISTS, $item);
                    // $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }

                $bet_id = isset($item['OrderNumber']) ? $item['OrderNumber'] : null;
                $bet_Details = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->api->getPlatformCode(), $bet_id, 'CreditBet');
                if(empty($bet_Details)){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_DOCUMENTNOTFOUND, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }

                if(!isset($item['Amount']) || !$this->isValidAmount($item['Amount'])){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_INVALIDINPUTPARAMETERS, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }

                $external_account_id = isset($item['ClientId']) ? $item['ClientId'] : null;
                if(isset($bet_Details[0]['player_id'])){
                    if($bet_Details[0]['player_id'] != $external_account_id){
                        $response_item[] = $this->getItemResponseFormat(self::RESPONSE_NOTALLOWED, $item);
                        $order_number_encounter_error[] = $item['OrderNumber'];
                        continue;
                    }
                }
                $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
                if(empty($external_account_id) || !$player_id){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_CLIENTNOTFOUND, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }

                $game_username = $this->api->getGameUsernameByPlayerId($player_id);
                $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
                if(empty($player_details)){
                    $response_item[] = $this->getItemResponseFormat(self::RESPONSE_CLIENTNOTFOUND, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }
                
                $error_code = self::RESPONSE_INTERNALSERVERERROR; #default
                $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $item, &$response_item, &$error_code) {
                    $amount = isset($item['Amount']) ? $this->api->gameAmountToDB($item['Amount']) : null;
                    $player_name = $player_details['username'];
                    $before_balance = $this->getPlayerBalance($player_name);
                    $success = false; #default
                    $decrease = false;
                    if($this->utils->compareResultFloat($amount, '>', 0)) {
                        if(in_array($item['TransactionTypeId'], self::TRANSACTION_TYPE_TO_DECREASE)){
                            if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                                $error_code = self::RESPONSE_LOWBALANCE;
                                return false;
                            }
                            $decrease = true;
                            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                            } else {
                                $success = $this->wallet_model->decSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
                            }
                        } else if(in_array($item['TransactionTypeId'], self::TRANSACTION_TYPE_TO_INCREASE)){
                            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                            } else {
                                $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
                            }
                        } else {
                            $error_code = self::RESPONSE_INVALIDINPUTPARAMETERS;
                            $success = false;
                        }
            
                    } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                        $success = true;#allowed amount 0
                    } else { #default error
                        $success = false;
                    }
                    // $success = false;
                    #proceed on success adjustment
                    if($success){
                        $success = false; #reset $success
                        $afterBalance = $this->getPlayerBalance($player_name);
                        $item['before_balance'] = $before_balance;
                        $item['player_id'] = $player_details['player_id'];
                        $item['response_result_id'] = $this->response_result_id;
                        $item['after_balance'] = $afterBalance;
                        $item['transaction_type'] = 'DebitByBatch';
                        $item['amount'] = $amount;
                        $item['result_amount'] = $decrease ? -$amount: $amount;
                        $transId = $this->processRequestData($item);
                        if($transId){
                            $success = true;
                            $response_item[] = $this->getItemResponseFormat(self::RESPONSE_SUCCESS, $item);
                        }

                    } else {
                        // $error_code = self::RESPONSE_INTERNALSERVERERROR; #not enough balance or invalid amount
                        if($this->utils->compareResultFloat($amount, '>', $before_balance)) {
                            $error_code = self::RESPONSE_LOWBALANCE;
                        }
                    }
                    return $success;
                });
                
                if(!$success){
                    $response_item[] = $this->getItemResponseFormat($error_code, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }
            }  
            $request['OutputItems'] = $response_item;
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_SUCCESS, $request, $response_order_fields);
            return $this->setResponse($response);
        } else {
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
            return $this->setResponse($response);
        }
    }

    private function RollBackByBatch($request){
        $order_fields = ['PartnerId', 'TimeStamp'];
        $required_fields = ['PartnerId', 'TimeStamp', 'Items'];
        $response_order_fields = ['ResponseCode', 'Description', 'TimeStamp'];
        // if(!$this->validateRequest($request, $required_fields)){
        //     $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
        //     return $this->setResponse($response);
        // }

        if(!$this->validateSign($request, $order_fields)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::INVALIDSIGNATURE, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $partner_id = isset($request['PartnerId']) ? $request['PartnerId'] : null;
        if(empty($partner_id) || $partner_id != $this->api->partner_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_PARTNERNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $items = isset($request['Items']) ? $request['Items'] : [];
        $response_item =[];
        if(!empty($items)){
            foreach ($items as $key => $item) {
                
                $unique_id = isset($item['TransactionId']) ? $item['TransactionId'] : null;
                $bet_id = isset($item['OrderNumber']) ? $item['OrderNumber'] : null;

                $rollback_details = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $bet_id, 'transaction_id', 'RollBackByBatch');
                if(!empty($rollback_details)){
                    $response_item[] = $this->getRollBackItemResponseFormat(self::RESPONSE_DOCUMENTALREADYROLLBACKED, $item);
                    continue;
                }

                $bet_Details = (array)$this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $bet_id, 'transaction_id', 'CreditBet');
                if(empty($bet_Details)){
                    $response_item[] = $this->getRollBackItemResponseFormat(self::RESPONSE_DOCUMENTNOTFOUND, $item);
                    continue;
                } else {
                    if($unique_id != $bet_Details['external_unique_id']){
                        $response_item[] = $this->getRollBackItemResponseFormat(self::RESPONSE_DOCUMENTNOTFOUND, $item);
                        continue;
                    }
                }

                $player_id = $bet_Details['player_id'];
                $game_username = $this->api->getGameUsernameByPlayerId($player_id);
                $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
                if(empty($player_details)){
                    $response_item[] = $this->getRollBackItemResponseFormat(self::RESPONSE_CLIENTNOTFOUND, $item);
                    continue;
                }
                
                $item['RollbackAmount'] = $bet_Details['amount'];
                $error_code = self::RESPONSE_INTERNALSERVERERROR;
                $success = $this->lockAndTransForPlayerBalance($player_details['player_id'], function() use($player_details, $item, &$response_item, &$error_code) {
                    $amount = isset($item['RollbackAmount']) ? $this->api->gameAmountToDB($item['RollbackAmount']) : null;
                    $player_name = $player_details['username'];
                    $before_balance = $this->getPlayerBalance($player_name);
                    $success = false; #default
                    if($this->utils->compareResultFloat($amount, '>', 0)) {
                        if($this->utils->getConfig('enable_seamless_single_wallet')) {
                            $reason_id=Abstract_game_api::REASON_UNKNOWN;
                            $success = $this->wallet_model->transferSeamlessSingleWallet($player_details['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                        } else {
                            $success = $this->wallet_model->incSubWallet($player_details['player_id'], $this->api->getPlatformCode(), $amount);
                        }
                    } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                        $success = true;#allowed amount 0
                    } else { #default error
                        $success = false;
                    }
                    
                    #proceed on success adjustment
                    if($success){
                        $success = false; #reset $success
                        $afterBalance = $this->getPlayerBalance($player_name);
                        $item['before_balance'] = $before_balance;
                        $item['player_id'] = $player_details['player_id'];
                        $item['response_result_id'] = $this->response_result_id;
                        $item['after_balance'] = $afterBalance;
                        $item['transaction_type'] = 'RollBackByBatch';
                        $item['amount'] = $amount;
                        $item['result_amount'] = $amount;
                        $item['external_unique_id'] = $item['TransactionId'].'-rollback';
                        $transId = $this->processRequestData($item);
                        if($transId){
                            $success = true;
                            $response_item[] = $this->getRollBackItemResponseFormat(self::RESPONSE_SUCCESS, $item);
                        }

                    } else {
                        $error_code = self::RESPONSE_INTERNALSERVERERROR; #encounter error on adding balance
                    }
                    return $success;
                });
                if(!$success){
                    $response_item[] = $this->getRollBackItemResponseFormat($error_code, $item);
                    $order_number_encounter_error[] = $item['OrderNumber'];
                    continue;
                }
            }  
            $request['OutputItems'] = $response_item;
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_SUCCESS, $request, $response_order_fields);
            return $this->setResponse($response);
        } else {
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
            return $this->setResponse($response);
        }
    }

    private function getRollBackItemResponseFormat($errorCode, $item){
        $item_data_response = array(
            "TransactionId" => $item['TransactionId']
        );
        return array_merge($item_data_response, $errorCode);
    }

    private function getItemResponseFormat($errorCode, $item){
        $item_data_response = array(
            "OrderNumber" => $item['OrderNumber'],
            "TransactionId" => $item['TransactionId'],
            "ClientId" => $item['ClientId'],
        );
        return array_merge($item_data_response, $errorCode);
    }

    private function getDefaultResponseFormat($method, $errorCode, $request, $order_fields=['ResponseCode', 'Description', 'TimeStamp', 'TransactionId']){
        switch (strtolower($method)) {
            case 'getbalance':
                $available_balance = isset($request['AvailableBalance']) ? $request['AvailableBalance'] : 0;
                $response = array(
                    "TimeStamp" => $this->utils->getTimestampNow(),
                    "Token" => isset($request['Token']) ? $request['Token'] : null,
                    "AvailableBalance" => $this->api->dBtoGameAmount($available_balance),
                    "CurrencyId" => $this->api->currency
                );
                break;
            case 'getuserinfo':
                $available_balance = isset($request['AvailableBalance']) ? $request['AvailableBalance'] : 0;
                $response = array(
                    "TimeStamp" => $this->utils->getTimestampNow(),
                    "Token" => isset($request['Token']) ? $request['Token'] : null,
                    "ClientId" => isset($request['player_id']) ? $request['player_id'] : 0,
                    "CurrencyId" => $this->api->currency,
                    "FirstName" => isset($request['firstName']) ? $request['firstName'] : "",
                    "LastName" => isset($request['lastName']) ? $request['lastName'] : "",
                    "Gender" => isset($request['gender']) ? $this->getGenderCode($request['gender']) : self::GENDER_UNKNOWN,
                    "BirthDate" => isset($request['birthdate']) ? $request['birthdate'] : "",
                    "BetShopId" => "",
                    "TerritoryId" => "",
                    "AvailableBalance" => $this->api->dBtoGameAmount($available_balance)
                );
                break;
            case 'creditbet':
                $response = array(
                    "OperationItems" => isset($request['OperationOutputItem']) ? $request['OperationOutputItem'] : [], 
                    "TimeStamp" => $this->utils->getTimestampNow(),
                    "TransactionId" => isset($request['TransactionId']) ? $request['TransactionId'] : 0, # 0 = error no transation id get
                );
                break;
            case 'debitbybatch':
            case 'rollbackbybatch':
                $response = array(
                    "TimeStamp" => $this->utils->getTimestampNow(),
                    "Items" => isset($request['OutputItems']) ? $request['OutputItems'] : [], 
                );
                break;
            default:
                $response = array(
                    "TimeStamp" => $this->utils->getTimestampNow(),
                    "TransactionId" => isset($request['TransactionId']) ? $request['TransactionId'] : 0, # 0 = error no transation id get
                );
                break;
        }
        
        $json_output = false;
        $response = array_merge($errorCode, $response);
        $response['Signature'] = $this->generateSign($method, $response, $json_output, $order_fields);
        return $response;
    }

    private function processRequestData($request){

        $dataToInsert = array(
            #provider
            "status" => isset($request['BetState']) ? $request['BetState'] : NULL, #New = 1
            "game_id" => isset($request['GameId']) ? $request['GameId'] : NULL,
            "external_unique_id" => isset($request['TransactionId']) ? $request['TransactionId'] : NULL,
            "transaction_id" => isset($request['OrderNumber']) ? $request['OrderNumber'] : NULL, #mark as bet id
            #default
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL,
            "transaction_type" => isset($request['transaction_type']) ? $request['transaction_type'] : NULL,
            "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "extra_info" => json_encode($this->request), #actual request
            "start_at" => $this->utils->getNowForMysql(), 
            "end_at" => $this->utils->getNowForMysql(), 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            "round_id" =>  NULL,
            #trans
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,
        );
        if(isset($request['external_unique_id'])){
            $dataToInsert['external_unique_id'] = $request['external_unique_id'];
        }

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
    }

    private function GetBalance($request){
        $required_fields = ['PartnerId', 'TimeStamp', 'Token', 'ClientId', 'CurrencyId', 'Signature'];
        $order_fields = ['PartnerId', 'TimeStamp', 'Token', 'ClientId', 'CurrencyId'];
        $response_order_fields=['ResponseCode', 'Description', 'TimeStamp', 'Token', 'AvailableBalance', 'CurrencyId'];
        // if(!$this->validateRequest($request, $required_fields)){
        //     $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
        //     return $this->setResponse($response);
        // }

        if(!$this->validateSign($request, $order_fields)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::INVALIDSIGNATURE, $request, $response_order_fields);
            return $this->setResponse($response);
        }
        
        $partner_id = isset($request['PartnerId']) ? $request['PartnerId'] : null;
        if(empty($partner_id) || $partner_id != $this->api->partner_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_PARTNERNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $currency = isset($request['CurrencyId']) ? $request['CurrencyId'] : null;
        if(empty($currency) || $currency != $this->api->currency){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CURRENCYNOTEXISTS, $request, $response_order_fields);
            return $this->setResponse($response);
        }
        
        $token = isset($request['Token']) ? $request['Token'] : null;
        if(empty($token)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $external_account_id = isset($request['ClientId']) ? $request['ClientId'] : null;
        $this->player_id = $player_id = $this->api->getPlayerIdByExternalAccountId($external_account_id);
        if(empty($external_account_id) || !$player_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $game_username = $this->api->getGameUsernameByPlayerId($player_id);
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->api->getPlatformCode());
        if(empty($player_details)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $player_token = $this->common_token->getValidPlayerToken($player_id);
        if($player_token != $token){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTBLOCKED, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTBLOCKED, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $balance = 0;
        if($player_id){
            $player_name = isset($player_details['username']) ? $player_details['username'] : null;
            $this->lockAndTransForPlayerBalance($player_id, function() use(&$balance, $player_name) {
                $balance = $this->getPlayerBalance($player_name);
                if($balance === false) {
                    $balance = 0;
                    return false;
                }
                return true;
            });
        }
        $request['AvailableBalance'] = $balance;
        $request = array_merge($request, $player_details);
        $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_SUCCESS, $request, $response_order_fields);
        return $this->setResponse($response);
    }

    private function GetUserInfo($request){
        $required_fields = ['Token', 'TimeStamp', 'PartnerId', 'Signature'];
        $request_order_fields = ['PartnerId', 'TimeStamp', 'Token'];
        $response_order_fields=['ResponseCode', 'Description', 'TimeStamp', 'Token', 'ClientId', 'CurrencyId', 'FirstName', 'LastName', 'Gender', 'BirthDate'];
        // if(!$this->validateRequest($request, $required_fields)){  
        //     $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_INVALIDINPUTPARAMETERS, $request, $response_order_fields);
        //     return $this->setResponse($response);
        // }

        if(!$this->validateSign($request, $request_order_fields)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::INVALIDSIGNATURE, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $partner_id = isset($request['PartnerId']) ? $request['PartnerId'] : null;
        if(empty($partner_id) || $partner_id != $this->api->partner_id){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_PARTNERNOTFOUND, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $token = isset($request['Token']) ? $request['Token'] : null;
        if(empty($token)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $refresh = false;
        $player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode(), $refresh);
        if(empty($player_details)){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $player_token = $this->common_token->getValidPlayerToken($player_details['player_id']);
        if($player_token != $token){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_WRONGTOKEN, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        if($this->api->isBlockedUsernameInDB($player_details['game_username'])){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTBLOCKED, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        if($this->player_model->isBlocked($player_details['player_id'])){
            $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_CLIENTBLOCKED, $request, $response_order_fields);
            return $this->setResponse($response);
        }

        $balance = 0;
        $player_info = [];
        if($player_details){
            $this->player_id = $player_id = $player_details['player_id'];
            $player_name = isset($player_details['username']) ? $player_details['username'] : null;
            $this->lockAndTransForPlayerBalance($player_id, function() use(&$balance, $player_name) {
                $balance = $this->getPlayerBalance($player_name);
                if($balance === false) {
                    $balance = 0;
                    return false;
                }
                return true;
            });
            $player_info = $this->player_model->getPlayerAccountInfo($player_id);
        }
        list($token, $sign_key) = $this->common_token->createTokenWithSignKeyBy($player_details['player_id'], 'player_id');
        $request['AvailableBalance'] = $balance;
        $request['Token'] = $token; #override token
        $request = array_merge($request, $player_details, $player_info);
        $response = $this->getDefaultResponseFormat(__FUNCTION__, self::RESPONSE_SUCCESS, $request, $response_order_fields);
        return $this->setResponse($response);
    }

    private function getGenderCode($genderStr){
        $gender_code = self::GENDER_MALE;#default
        switch (strtolower($genderStr)) {
            case 'male':
                $gender_code = self::GENDER_MALE;
                break;
            case 'female':
                $gender_code = self::GENDER_FEMALE;
                break;
            default:
                $gender_code = self::GENDER_UNKNOWN;
                break;
        }
        return $gender_code;
    }

    private function getPlayerBalance($player_name, $is_locked = true){
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $player_id = $this->api->getPlayerIdFromUsername($player_name);
            $seamless_balance = 0;
            $seamless_reason_id = null;

            if(!$is_locked){
                $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, &$seamless_balance, &$seamless_reason_id) {
                    return  $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
                });
            } else {
                $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            }
            return $seamless_balance;
        }
        else {
            $get_bal_req = $this->api->queryPlayerBalance($player_name);
            if($get_bal_req['success']) {
                return $get_bal_req['balance'];
            }
            else {
                return false;
            }
        }
    }

    private function setResponse($response) {
        return $this->setOutput($response);
    }

    private function setOutput($response) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;
        $http_status_code = 0;
        $flag = $response['ResponseCode'] == self::RESPONSE_SUCCESS['ResponseCode'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($this->response_result_id) {
            $disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
            if($disabled_response_results_table_only){
                $respRlt = $this->response_result->readNewResponseById($this->response_result_id);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                $content['costMs'] = intval($this->utils->getExecutionTimeToNow()*1000);
                $content['full_url']=$this->utils->paddingHostHttp(uri_string());
                if(isset($response['status_code'])  && $response['status_code'] == self::STATUS_CODE_INVALID_IP){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
                $this->response_result->updateNewResponse($respRlt);
            } else {
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->response_result_id);
                }
    
                $response_result = $this->response_result->getResponseResultById($this->response_result_id);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
    
                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                $content['costMs'] = intval($this->utils->getExecutionTimeToNow()*1000);
                $content['full_url']=$this->utils->paddingHostHttp(uri_string());
                if(isset($response['status_code'])  && $response['status_code'] == self::STATUS_CODE_INVALID_IP){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->response_result_id, null, $this->player_id, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }

        // if($flag == Response_result::FLAG_ERROR){
        //     if($this->api){
        //         $request_id = $this->utils->getRequestId();
        //         $now = $this->utils->getNowForMysql();
        //         $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
        //         $commonSeamlessErrorDetails = json_encode($response);
        //         $errorLogInsertData = [
        //             'game_platform_id' => $this->api->getPlatformCode(),
        //             'response_result_id' => $this->response_result_id,
        //             'request_id' => $request_id,
        //             'elapsed_time' => $elapsed,
        //             'error_date' => $now,
        //             'extra_info' => $commonSeamlessErrorDetails
        //         ];
        //         $this->common_seamless_error_logs->insertTransaction($errorLogInsertData);
        //     }
        // }

        #unset some field that not need on output but need for internal checking
        if(isset($response['code'])){
            unset($response['code']);
        }
        if(isset($response['message'])){
            unset($response['message']);
        }
        if(isset($response['status_code'])){
            unset($response['status_code']);
        }
        
        return $this->returnJsonResult($response, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code);
    }

    private function setResponseResult(){
        $response_result_id = $this->response_result->saveResponseResult(
            $this->api->getPlatformCode(),
            Response_result::FLAG_NORMAL,
            $this->request_method,
            json_encode($this->request),
            [],#default empty response
            200,
            null,
            null
        );

        return $response_result_id;
    }

   
}

