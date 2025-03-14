<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/../../../submodules/core-lib/application/libraries/third_party/jwt_v6/jwt.php';
require_once dirname(__FILE__) . '/../../../submodules/core-lib/application/libraries/third_party/jwt_v6/key.php';
/**
 * BETBY Single Wallet API Controller
 * OGP-29178
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
 * {{url}}/betby_seamless_service_api/ping
 * {{url}}/betby_seamless_service_api/bet/make
 * {{url}}/betby_seamless_service_api/bet/commit
 * {{url}}/betby_seamless_service_api/bet/settlement
 * {{url}}/betby_seamless_service_api/bet/refund
 * {{url}}/betby_seamless_service_api/bet/win
 * {{url}}/betby_seamless_service_api/bet/lost
 * {{url}}/betby_seamless_service_api/bet/discard
 * {{url}}/betby_seamless_service_api/bet/rollback
 * 
 * Related File
     - game_api_betby_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Betby_seamless_service_api extends BaseController {

    private $rawRequest;
    private $request;
    private $requestMethod;
    private $requestHeaders;
    private $responseResultId;
    private $api;
    private $playerId;
    private $ipInvalid;
    private $multiCurrencyEnabled;
    private $currency;
    private $defaultStatusErrorCode;

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    const TRANSACTION_TABLE = 'betby_seamless_wallet_transactions';
    const ALLOWED_METHOD_PARAMS = ['make', 'commit', 'settlement', 'refund', 'win', 'lost', 'discard', 'rollback'];
    const FUNC_WITH_VALIDATION = ['make', 'refund', 'win', 'lost', 'discard', 'rollback'];
    const FUNC_ALLOW_BLOCKED_PLAYER = ['refund', 'win', 'lost', 'discard', 'rollback'];
    const FUNC_WITH_CURRENCY_PARAM = ['make', 'refund', 'win', 'lost', 'rollback'];
    const FUNC_NO_CURRENCY_VALIDATION = ['discard'];
    const METHOD_ALLOWED_IN_EXPIRED_TOKEN = ['refund', 'win', 'lost', 'discard', 'rollback'];
    const STATUS_ERROR_CODE = 400;
    const STATUS_SUCCESS_CODE = 200;
    const CODE_SUCCESS = 0;
    const SBE_ERROR_CODE_PLAYER_LOCKED = 1005;
    const SBE_ERROR_CODE_PLAYER_NOT_FOUND = 1006;
    const SBE_ERROR_CODE_SESSION_EXPIRED = 1007;
    const SBE_ERROR_CODE_INSUFFICIENT_BALANCE = 2001;
    const SBE_ERROR_CODE_INVALID_CURRENCY = 2002;
    const SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND = 2003;
    const SBE_ERROR_CODE_BAD_REQUEST = 2004;
    const SBE_ERROR_CODE_JWT_TOKEN = 2005;
    const SBE_ERROR_CODE_BONUS_NOT_FOUND = 3001;
    const SBE_ERROR_CODE_PLAYER_LIMIT_EXCEEDED = 4001;
    const SBE_ERROR_CODE_BONUS_LIMIT_EXCEEDED = 4002;

    const SBE_ERROR_CODE_IP_RESTRICTION = 403;
    const STATUS_CODE_INVALID_IP = 401;
    
    const ERROR_MESSAGE = [
           0 => "Success",
         403 => "IP not allowed",
        1005 => "Player is blocked",
        1006 => "Player not found",
        1007 => "Session is expired",
        2001 => "Not enough money",
        2002 => "Invalid currency",
        2003 => "Parent transaction not found",
        2004 => "Bad request",
        2005 => "Invalid JWT token",
        3001 => "Bonus not found",
        4001 => "Player limits exceeded",
        4002 => "Maximum bonus bet limit exceeded",
    ];

    public function api($method = null, $action = null){
        switch (strtolower($method)) {
            case 'ping':
                return $this->ping();
                break;
            case 'bet':
                return $this->bet($action);
                break;
            
            default:
                return $this->returnJsonResult("Invalud URI");
                break;
        }
    }

    private function ping(){
        $response = array("timestamp" => $this->utils->getTimestampNow());
        return $this->returnJsonResult($response);
    }

    private function generateResponseArray($code){
        $response = array(
            "code" => $code,
            "message" => self::ERROR_MESSAGE[$code]
        );
        return $response;
    }

    /**
     * getCurrencyAndValidateDB
     * @param  array $reqParams
     * @return [type]            [description]
     */
    private function getCurrencyAndValidateDB($currency) {
        if(!empty($currency)) {
            # Get Currency Code for switching of currency and db forMDB
            $valid = $this->validateCurrencyAndSwitchDB($currency);
            return $valid;
        } else {
            return false;
        }
    }

    protected function validateCurrencyAndSwitchDB($currency){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($currency)){
            return false;
        }else{
            $currency = strtolower($currency);
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($currency)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($currency);
                return true;
            }
        }
    }

    # Returns the public key generated by provider
    private function getProviderPubKeyForDecodingPayload() {
        $publicKey = $this->utils->getConfig('betby_pub_key'); #provide by provider
        // $publicKey = $this->getSystemInfo('betby_pub_key'); ; #provide by provider
        // $publicKey = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxasGC2ORvr11XjqU5bQ/aWqO+3mq/DYhF6lZDtcP4E0oluZ0Ta8YpOMa0tA8ajqDfz8CXNO9T80/ENroiBUDVUCjFsvAVIvdCk8jLuLvG+dUWC7lJ2xPsg7ncIAFU8U3guaSeFERvfCKJHh9W9fk7KguCedOCcgr1m+5ni4srtjFYxUqPp+DT2+3Q7alXKFrtVeCENycolJ7YKSR+l2lCSK2EUxPpwk+TIgCZz0tLdsNd54jqQTfdABYsnhxb8zTq7NbupekfZ73Y4pLmMHum/qqi2kHAoxJaU4ew5YdrWJZwoEhUH1Fzyq3BRqDIJ+X0DJfgi2RYvqnni8C9N12tu+7Nh0FHHF9Hc98R7enBwp6RDU0GY7clbmZJQwiz/npWCQ3meJl8gzc0b1eT8kAIG86Pojdp12K4C+rvO/IHKPBCaFUyxMwt4R/nSH7eJTRwzGwGKM/xpoM9mEHcvffzf5Rzf9+B5paRfJXKig9ZmbRbJb9x6+soPJ3wx9tzWjv2wvZW8ui8+5jyLjp5UF536E5jY/66LbUIF3hbKne8H8radBOJbFSxwoXE/jrI+DDo43eYJq7OqSO943Fih2zt11CUlAhNRgrz6dnEUYYc9/aD9nF1F6zC8YqzZTM45I1Hxm1Bmov+q3Cvzs6uDYbWuScKQboLtRoGqDGGv+2wz8CAwEAAQ==';
        /*
         -----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxasGC2ORvr11XjqU5bQ/
aWqO+3mq/DYhF6lZDtcP4E0oluZ0Ta8YpOMa0tA8ajqDfz8CXNO9T80/ENroiBUD
VUCjFsvAVIvdCk8jLuLvG+dUWC7lJ2xPsg7ncIAFU8U3guaSeFERvfCKJHh9W9fk
7KguCedOCcgr1m+5ni4srtjFYxUqPp+DT2+3Q7alXKFrtVeCENycolJ7YKSR+l2l
CSK2EUxPpwk+TIgCZz0tLdsNd54jqQTfdABYsnhxb8zTq7NbupekfZ73Y4pLmMHu
m/qqi2kHAoxJaU4ew5YdrWJZwoEhUH1Fzyq3BRqDIJ+X0DJfgi2RYvqnni8C9N12
tu+7Nh0FHHF9Hc98R7enBwp6RDU0GY7clbmZJQwiz/npWCQ3meJl8gzc0b1eT8kA
IG86Pojdp12K4C+rvO/IHKPBCaFUyxMwt4R/nSH7eJTRwzGwGKM/xpoM9mEHcvff
zf5Rzf9+B5paRfJXKig9ZmbRbJb9x6+soPJ3wx9tzWjv2wvZW8ui8+5jyLjp5UF5
36E5jY/66LbUIF3hbKne8H8radBOJbFSxwoXE/jrI+DDo43eYJq7OqSO943Fih2z
t11CUlAhNRgrz6dnEUYYc9/aD9nF1F6zC8YqzZTM45I1Hxm1Bmov+q3Cvzs6uDYb
WuScKQboLtRoGqDGGv+2wz8CAwEAAQ==
-----END PUBLIC KEY-----
*/

        $publicKey = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($publicKey, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($publicKey);
    }

    private function deCodePayloadUsingRSA($payload){
        $publicKey = $this->getProviderPubKeyForDecodingPayload();
        $jwt = new JWTBetby;
        $request = $jwt->decode($payload, new KEY($publicKey, 'RS256'));
        $request = json_encode($request);
        $request = json_decode($request, true);
        return $request;
    }

    private function multi_currency_bet($action){
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;
        $isTesting = false;
        if (isset($_GET['password'])) {
            $isTesting = $_GET['password'] == $this->utils->getConfig('betby_testing_password');
        }

        $this->requestMethod = $action;
        $this->rawRequest = file_get_contents("php://input");
        $this->utils->debug_log("BB RAW REQUEST >>>>>>>>>>>>>>> {$this->rawRequest}");
        $this->request = $request = json_decode($this->rawRequest, true);
        $this->requestHeaders = $this->input->request_headers();
        // $this->responseResultId = $this->setResponseResult();
        // $this->api = $this->utils->loadExternalSystemLibObject(BETBY_SEAMLESS_GAME_API);
        $this->playerId = null;
        $this->ipInvalid = false;

        if(!$isTesting){
            $payload = isset($request['payload']) ? $request['payload'] : null;
            if(!empty($payload)){
                try {  
                    $data = $this->deCodePayloadUsingRSA($payload);  
                } catch (Exception $e) {
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "(PAYLOAD) " . $e->getMessage();
                    return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
                }

                if(!empty($data)){
                    $payload_data = isset($data['payload']) ? $data['payload'] : [];
                    if(!empty($payload_data)){
                        $this->request = $request = $payload_data;
                    }
                } else{
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Empty data.";
                    return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
                }
            } else {
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorResponse['message'] = "Empty payload.";
                return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
            }
        }

        $superDB = null;
        $transTable = $this->utils->getConfig('betby_super_table'); #try target super like database.table ex: og_amusino_super.betby_seamless_wallet_transactions
        if(empty($transTable)){
            $transTable = self::TRANSACTION_TABLE;
            $superDB = $this->multiple_db_model->getSuperDBFromMDB();
            // $superDB = null; make null on local testing
        }

        $currency = isset($request['currency']) ? $request['currency'] : null;
        if(empty($currency)){
            $currency = isset($request['transaction']['currency']) ? $request['transaction']['currency'] : null;
        }
        if($action == "discard"){ #action no currency, need to check on super
            $transId = isset($request['transaction_id']) ? "multi_currency_bet_".$request['transaction_id'] : null; #transactionid
            $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustomWithdb($transTable, ['external_unique_id'=> $transId],['id', 'currency'], $superDB);
            $currency = isset($transactionDetails['currency']) ? $transactionDetails['currency'] : null;
        }

        if($action == "commit" || $action == "settlement"){
            $betTransactionId = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
            if($this->multiCurrencyEnabled){
                $betTransactionIdArray = explode("_", $betTransactionId);
                $currency = isset($betTransactionIdArray[1]) ? $betTransactionIdArray[1] : null;
            }
        }

        $this->currency = $currency;

        // $currency = "idr"; #testing
        // if(in_array($action, self::FUNC_WITH_CURRENCY_PARAM)){
        if(!empty($currency)){
            $currency = strtolower($currency);
            if($action == "make"){ #bet only to save initial transaction for discard
                $transactionId = isset($request['transaction']['id']) ? $request['transaction']['id'] : NULL;
                if(empty($transactionId)){
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Transaction id missing on param.";
                    return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
                }
                $dataToInsert = array(
                    "action" => 'multi_currency_bet',  
                    "currency" => $currency, 
                    #transaction details
                    "transaction_id" => isset($request['transaction']['id']) ? $request['transaction']['id'] : NULL, 
                    #sbe
                    "external_unique_id" => "multi_currency_bet_".$transactionId, 
                );
                $betId = $this->original_seamless_wallet_transactions->insertIgnoreTransactionData($transTable, $dataToInsert, $superDB);
            }
            
            $is_valid = $this->getCurrencyAndValidateDB($currency);
            if(!$is_valid) {
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorResponse['message'] = "Failed switch currency.";
                // return $this->setResponse($errorResponse);
                return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
            } else { 
                $this->api = $this->utils->loadExternalSystemLibObject(BETBY_SEAMLESS_GAME_API);
                if(!$this->api) {
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Load game failed.";
                    $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
                    return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
                }
                $this->responseResultId = $this->setResponseResult();
                if(!$this->api->validateWhiteIP()){
                    $ip = $this->input->ip_address();
                    if($ip=='0.0.0.0'){
                        $ip=$this->input->getRemoteAddr();
                    }
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_IP_RESTRICTION);
                    $errorResponse['message'] = "IP not allowed({$ip})";
                    $this->ipInvalid = true;
                    return $this->setResponse($errorResponse);
                }

                if(!method_exists($this, $this->requestMethod)) {
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Method not exist.";
                    $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
                    return $this->setResponse($errorResponse);
                }

                if(!in_array($this->requestMethod, self::ALLOWED_METHOD_PARAMS)) {
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Method not allowed.";
                    $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
                    return $this->setResponse($errorResponse);
                }

                if(in_array($action, self::FUNC_WITH_VALIDATION)){
                    $gameUsername = isset($request['player_id']) ? $request['player_id'] : null;
                    if(empty($gameUsername)){
                        $gameUsername = isset($request['transaction']['ext_player_id']) ? $request['transaction']['ext_player_id'] : null;
                    }

                    if(empty($gameUsername)){ #if still empty try get ext_player_id
                        $gameUsername = isset($request['ext_player_id']) ? $request['ext_player_id'] : null;
                    }

                    $token = isset($request['session_id']) ? $request['session_id'] : null;
                    $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
                    if(empty($playerDetails)){
                        $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_NOT_FOUND);
                        return $this->setResponse($errorResponse);
                    }

                    // $tokenDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
                    // if(empty($tokenDetails) && !in_array($this->requestMethod, self::METHOD_ALLOWED_IN_EXPIRED_TOKEN)){
                    //     $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_SESSION_EXPIRED);
                    //     return $this->setResponse($errorResponse);
                    // } else{
                    //     if(isset($tokenDetails['player_id'])){
                    //         if($tokenDetails['player_id'] != $playerDetails['player_id']){
                    //             $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_SESSION_EXPIRED);
                    //             return $this->setResponse($errorResponse);
                    //         }
                    //     }
                    // }

                    $this->playerId = $playerDetails['player_id'];
                    if($this->api->isBlockedUsernameInDB($playerDetails['game_username']) && !in_array($action, self::FUNC_ALLOW_BLOCKED_PLAYER)){
                        $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_LOCKED);
                        return $this->setResponse($errorResponse);
                    }

                    if($this->player_model->isBlocked($playerDetails['player_id']) && !in_array($action, self::FUNC_ALLOW_BLOCKED_PLAYER)){
                        $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_LOCKED);
                        return $this->setResponse($errorResponse);
                    }
                }
                
                return $this->$action();
            }
        } else {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "No currency detected.";
            return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
        }
    }

    private function bet($action = null){
        $this->defaultStatusErrorCode = ($action != "discard") ? self::STATUS_ERROR_CODE : self::STATUS_SUCCESS_CODE;
        $this->multiCurrencyEnabled = $this->utils->getConfig('betby_multi_currency_enabled');
        if($this->multiCurrencyEnabled){
            return $this->multi_currency_bet($action);
        }
        
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;
        $isTesting = false;
        if (isset($_GET['password'])) {
            $isTesting = $_GET['password'] == $this->utils->getConfig('betby_testing_password');
        }

        $this->requestMethod = $action;
        $this->rawRequest = file_get_contents("php://input");
        $this->utils->debug_log("BB RAW REQUEST >>>>>>>>>>>>>>> {$this->rawRequest}");
        $this->request = $request = json_decode($this->rawRequest, true);
        $this->requestHeaders = $this->input->request_headers();
        $this->responseResultId = $this->setResponseResult();
        $this->api = $this->utils->loadExternalSystemLibObject(BETBY_SEAMLESS_GAME_API);
        $this->playerId = null;
        $this->ipInvalid = false;

        if(!$this->api) {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Load game failed.";
            $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->returnJsonResult($errorResponse, $addOrigin, $origin, $pretty, $partialOutputOnError, $this->defaultStatusErrorCode);
        }

        if(!$isTesting){
            $payload = isset($request['payload']) ? $request['payload'] : null;
            if(!empty($payload)){
                try {
                    $data = $this->api->deCodePayloadUsingRSA($payload);   
                } catch (Exception $e) {
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "(PAYLOAD) " . $e->getMessage();
                    return $this->setResponse($errorResponse);
                }

                if(!empty($data)){
                    $payload_data = isset($data['payload']) ? $data['payload'] : [];
                    if(!empty($payload_data)){
                        $this->request = $request = $payload_data;
                    }
                } else{
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Empty data.";
                    return $this->setResponse($errorResponse);
                }
            } else {
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorResponse['message'] = "Empty payload.";
                return $this->setResponse($errorResponse);
            }
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_IP_RESTRICTION);
            $errorResponse['message'] = "IP not allowed({$ip})";
            $this->ipInvalid = true;
            return $this->setResponse($errorResponse);
        }

        if(!method_exists($this, $this->requestMethod)) {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Method not exist.";
            $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        if(!in_array($this->requestMethod, self::ALLOWED_METHOD_PARAMS)) {
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Method not allowed.";
            $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        if(in_array($action, self::FUNC_WITH_VALIDATION)){
            $gameUsername = isset($request['player_id']) ? $request['player_id'] : null;
            if(empty($gameUsername)){
                $gameUsername = isset($request['transaction']['ext_player_id']) ? $request['transaction']['ext_player_id'] : null;
            }
            if(empty($gameUsername)){ #if still empty try get ext_player_id
                $gameUsername = isset($request['ext_player_id']) ? $request['ext_player_id'] : null;
            }
            $token = isset($request['session_id']) ? $request['session_id'] : null;
            $currency = isset($request['currency']) ? $request['currency'] : null;
            if(empty($currency)){
                $currency = isset($request['transaction']['currency']) ? $request['transaction']['currency'] : null;
            }
            $this->currency = $currency;

            $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
            if(empty($playerDetails)){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_NOT_FOUND);
                return $this->setResponse($errorResponse);
            }

            // $tokenDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $this->api->getPlatformCode());
            // if(empty($tokenDetails) && !in_array($this->requestMethod, self::METHOD_ALLOWED_IN_EXPIRED_TOKEN)){
            //     $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_SESSION_EXPIRED);
            //     return $this->setResponse($errorResponse);
            // } else{
            //     if(isset($tokenDetails['player_id'])){
            //         if($tokenDetails['player_id'] != $playerDetails['player_id']){
            //             $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_SESSION_EXPIRED);
            //             return $this->setResponse($errorResponse);
            //         }
            //     }
            // }

            $this->playerId = $playerDetails['player_id'];
            if($this->api->isBlockedUsernameInDB($playerDetails['game_username']) && !in_array($action, self::FUNC_ALLOW_BLOCKED_PLAYER)){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_LOCKED);
                return $this->setResponse($errorResponse);
            }

            if($this->player_model->isBlocked($playerDetails['player_id']) && !in_array($action, self::FUNC_ALLOW_BLOCKED_PLAYER)){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PLAYER_LOCKED);
                return $this->setResponse($errorResponse);
            }

            if($currency != $this->api->currency && !in_array($action, self::FUNC_NO_CURRENCY_VALIDATION)){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_INVALID_CURRENCY);
                return $this->setResponse($errorResponse);
            }
        }
        
        return $this->$action();
    }

    private function rollback(){
        $request = $this->request;
        $amount = isset($request['transaction']['amount']) ? $request['transaction']['amount'] : null;
        if(!$this->isValidAmount($amount)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        } 

        $uniqueid = isset($request['transaction']['id']) ? $request['transaction']['id'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid transaction id value.";
            return $this->setResponse($errorResponse);
        }

        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id already exist.";
            return $this->setResponse($errorResponse);
        }

        $betTransactionId = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
        if($this->multiCurrencyEnabled){
            $betTransactionIdArray = explode("_", $betTransactionId);
            $betTransactionId = isset($betTransactionIdArray[0]) ? $betTransactionIdArray[0] : $betTransactionId;
            $request['bet_transaction_id'] = $betTransactionId;# override
        }

        $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["id" =>$betTransactionId]);
        if(!$isBetExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
            return $this->setResponse($errorResponse);
        } else {
            $betTransactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['id'=> $betTransactionId],['action']);
            if(strtolower($betTransactionDetails['action']) != "make"){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
                return $this->setResponse($errorResponse);
            }
        }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData(self::TRANSACTION_TABLE, ["bet_transaction_id" =>$betTransactionId, "action !=" => "commit"]);
        $parentTransactionId = !empty($lastId) ? $lastId : $betTransactionId;
        if($lastStatus != GAME_LOGS::STATUS_SETTLED && $lastStatus != GAME_LOGS::STATUS_REFUND){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                    $message = "already discarded.";
                    break;
                // case GAME_LOGS::STATUS_REFUND:
                //     $message = "already refunded.";
                //     break;
                default:
                    $message = "still pending.";
                    break;
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id {$message}";
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST); #default
        $errorCode['message'] = "Lock and transfer failed";
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $parentTransactionId, $request, &$response, &$errorCode) {
            $success = false;
            $amountToDeduct = isset($request['transaction']['amount']) ? $this->api->gameAmountToDB($request['transaction']['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorCode['message'] = "Failed fetch before balance";
                return false;
            }

            if($this->utils->compareResultFloat($amountToDeduct, '>', 0)) {
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $this->wallet_model->setGameProviderActionType('refund');

                // $success = $this->wallet_model->decSubWallet($playerId, $this->api->getPlatformCode(), $amountToDeduct, $afterBalance);
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(empty($enabled_remote_wallet_client_on_currency)){
                    $success = $this->wallet_model->decSubWalletAllowNegative($playerId, $this->api->getPlatformCode(), $amountToDeduct);
                } else {
                    $success = $this->wallet_model->decSubWallet($playerId, $this->api->getPlatformCode(), $amountToDeduct, $afterBalance);
                }
                if(!$success){
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorCode['message'] = "Failed on add balance.";
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amountToDeduct, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                        $errorCode['message'] = "Failed fetch after balance";
                        return false;
                    }
                }

                $request['before_balance'] = $beforeBalance;
                $request['after_balance'] = $afterBalance;
                $request['action'] = "rollback";
                $request['sbe_status'] = GAME_LOGS::STATUS_PENDING; #mark as running
                $request['bet_amount'] = 0;
                $request['result_amount'] = -$amountToDeduct;
                $request['external_unique_id'] = $uniqueid;
                $request['amount'] = $amountToDeduct;
                $request['player_id'] = $request['transaction']['ext_player_id'];
                $request['parent_transaction_id'] = $parentTransactionId;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $response = array(
                        "code" => self::CODE_SUCCESS,
                        // "id" => (string)$transId,
                        "id" => ($this->multiCurrencyEnabled) ? (string) $transId . "_" . $this->currency: (string) $transId,
                        "ext_transaction_id" => (string)$uniqueid, 
                        // "parent_transaction_id" => $parentTransactionId,
                        "parent_transaction_id" => ($this->multiCurrencyEnabled) ? (string) $parentTransactionId . "_" . $this->currency: (string) $parentTransactionId,
                        "user_id" => $request['transaction']['ext_player_id'],
                        "operation" => "rollback",
                        "amount" => $this->api->dBtoGameAmount($request['amount']),
                        "currency" => $this->api->currency,
                        "balance" => $this->api->dBtoGameAmount($afterBalance),
                    );
                }
            }

            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    private function discard(){
        $request = $this->request;
        $transId = isset($request['transaction_id']) ? $request['transaction_id'] : null; #transactionid
        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $transId);
        if(!$isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id not exist.";
            return $this->setResponse($errorResponse);
        } else{
            $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['external_unique_id'=> $transId],['id', 'amount', 'action', 'sbe_player_id']);
            if(!empty($transactionDetails)){
                if($transactionDetails['sbe_player_id'] != $this->playerId){
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Transaction player id not match.";
                    return $this->setResponse($errorResponse);
                }

                if($transactionDetails['action'] != "make"){
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Transaction action not match.";
                    return $this->setResponse($errorResponse);
                }

                $betTransactionId = isset($transactionDetails['id']) ? $transactionDetails['id'] : null;
                list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData(self::TRANSACTION_TABLE, ["bet_transaction_id" =>$betTransactionId, "action !=" => "commit"]);
                if(!empty($lastStatus) && $lastStatus !=  GAME_LOGS::STATUS_PENDING){
                    switch ($lastStatus) {
                        case GAME_LOGS::STATUS_VOID:
                            $message = "already discarded.";
                            break;
                        case GAME_LOGS::STATUS_REFUND:
                            $message = "already refunded.";
                            break;
                        default:
                            $message = "already settled.";
                            break;
                    }
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Transaction id {$message}";
                    return $this->setResponse($errorResponse);
                }
                
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST); #default
                $errorCode['message'] = "Lock and transfer failed";
                $response = array();
                $playerId = $this->playerId;
                $uniqueid = "discard-".$transactionDetails['id'];
                $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
                if($isExist){
                    $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorResponse['message'] = "Transaction already discarded.";
                    return $this->setResponse($errorResponse);
                }
                $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $request, $transactionDetails, $uniqueid, &$response, &$errorCode) {
                    $success = false;
                    $amountToAdd = isset($transactionDetails['amount']) ? $transactionDetails['amount'] : null;
                    $beforeBalance = $this->getPlayerBalance();
                    $afterBalance = null;
                    if($beforeBalance === false){
                        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                        $errorCode['message'] = "Failed fetch before balance";
                        return false;
                    }

                    if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                        $this->wallet_model->setGameProviderActionType('refund');

                        $success = $this->wallet_model->incSubWallet($playerId, $this->api->getPlatformCode(), $amountToAdd, $afterBalance);
                        if(!$success){
                            $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                            $errorCode['message'] = "Failed on add balance.";
                            return false;
                        }
         
                    } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                        $success = true;#allowed amount 0
                    } else { #default error
                        $success = false;
                    }

                    if($success){
                        $success = false; #reset $success
                        if(is_null($afterBalance)){
                            $afterBalance = $this->getPlayerBalance();
                            if($afterBalance === false){
                                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                                $errorCode['message'] = "Failed fetch after balance";
                                return false;
                            }
                        }
                        
                        $request['before_balance'] = $beforeBalance;
                        $request['after_balance'] = $afterBalance;
                        $request['action'] = "discard";
                        $request['sbe_status'] = GAME_LOGS::STATUS_VOID;
                        $request['bet_amount'] = 0;
                        $request['result_amount'] = $amountToAdd;
                        $request['external_unique_id'] = $uniqueid;
                        $request['amount'] = $amountToAdd;
                        $request['player_id'] = $request['ext_player_id'];
                        $request['bet_transaction_id'] = $transactionDetails['id'];
                        $transId = $this->processRequestData($request);
                        if($transId){
                            $success = true;
                            $response = array(
                                "code" => self::CODE_SUCCESS,
                                "balance" => (float) $this->api->dBtoGameAmount($afterBalance),
                            );
                        }
                    }

                    return $success;
                });

                if(!empty($response) && $success){
                    return $this->setResponse($response);
                } else {
                    return $this->setResponse($errorCode);
                }

            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id not exist.";
            return $this->setResponse($errorResponse);
        }
    }

    private function lost(){
        $request = $this->request;
        $amount = isset($request['amount']) ? $request['amount'] : null;
        if(!$this->isValidAmount($amount)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        } 

        $uniqueid = isset($request['transaction']['id']) ? $request['transaction']['id'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid transaction id value.";
            return $this->setResponse($errorResponse);
        }

        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id already exist.";
            return $this->setResponse($errorResponse);
        }

        $betTransactionId = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
        if($this->multiCurrencyEnabled){
            $betTransactionIdArray = explode("_", $betTransactionId);
            $betTransactionId = isset($betTransactionIdArray[0]) ? $betTransactionIdArray[0] : $betTransactionId;
            $request['bet_transaction_id'] = $betTransactionId;# override
        }

        $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["id" =>$betTransactionId]);
        if(!$isBetExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
            return $this->setResponse($errorResponse);
        } else {
            $betTransactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['id'=> $betTransactionId],['action']);
            if(strtolower($betTransactionDetails['action']) != "make"){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
                return $this->setResponse($errorResponse);
            }
        }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData(self::TRANSACTION_TABLE, ["bet_transaction_id" =>$betTransactionId, "action !=" => "commit"]);
        $parentTransactionId = !empty($lastId) ? $lastId : $betTransactionId;
        if(!empty($lastStatus) && $lastStatus !=  GAME_LOGS::STATUS_PENDING){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                    $message = "already discarded.";
                    break;
                case GAME_LOGS::STATUS_REFUND:
                    $message = "already refunded.";
                    break;
                default:
                    $message = "already settled.";
                    break;
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id {$message}";
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST); #default
        $errorCode['message'] = "Lock and transfer failed";
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $parentTransactionId, $request, &$response, &$errorCode) {
            $success = false;
            $amountToAdd = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            if($beforeBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorCode['message'] = "Failed fetch before balance";
                return false;
            }

            if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorCode['message'] = "Amount should be 0";
                return false;
            } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; #reset $success
                $request['before_balance'] = $beforeBalance;
                $request['after_balance'] = $beforeBalance;
                $request['action'] = "lost";
                $request['sbe_status'] = GAME_LOGS::STATUS_SETTLED;
                $request['bet_amount'] = 0;
                $request['result_amount'] = 0;
                $request['external_unique_id'] = $uniqueid;
                $request['amount'] = $amountToAdd;
                $request['player_id'] = $request['transaction']['ext_player_id'];
                $request['parent_transaction_id'] = $parentTransactionId;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $response = array(
                        "code" => self::CODE_SUCCESS,
                        // "id" => (string)$transId,
                        "id" => ($this->multiCurrencyEnabled) ? (string) $transId . "_" . $this->currency: (string) $transId,
                        "ext_transaction_id" => (string)$uniqueid, 
                        // "parent_transaction_id" => $parentTransactionId,
                        "parent_transaction_id" => ($this->multiCurrencyEnabled) ? (string) $parentTransactionId . "_" . $this->currency: (string) $parentTransactionId,
                        "user_id" => $request['transaction']['ext_player_id'],
                        "operation" => "lost",
                        "amount" => (float)$this->api->dBtoGameAmount($request['amount']),
                        "currency" => $this->api->currency,
                        "balance" => (float)$this->api->dBtoGameAmount($beforeBalance),
                    );
                }
            }

            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    private function win(){
        $request = $this->request;
        $amount = isset($request['amount']) ? $request['amount'] : null;
        if(!$this->isValidAmount($amount)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        } 

        $uniqueid = isset($request['transaction']['id']) ? $request['transaction']['id'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid transaction id value.";
            return $this->setResponse($errorResponse);
        }

        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id already exist.";
            return $this->setResponse($errorResponse);
        }

        $betTransactionId = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
        if($this->multiCurrencyEnabled){
            $betTransactionIdArray = explode("_", $betTransactionId);
            $betTransactionId = isset($betTransactionIdArray[0]) ? $betTransactionIdArray[0] : $betTransactionId;
            $request['bet_transaction_id'] = $betTransactionId;# override
        }

        $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["id" =>$betTransactionId]);
        if(!$isBetExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
            return $this->setResponse($errorResponse);
        } else {
            $betTransactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['id'=> $betTransactionId],['action']);
            if(strtolower($betTransactionDetails['action']) != "make"){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
                return $this->setResponse($errorResponse);
            }
        }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData(self::TRANSACTION_TABLE, ["bet_transaction_id" =>$betTransactionId, "action !=" => "commit"]);
        $parentTransactionId = !empty($lastId) ? $lastId : $betTransactionId;
        if(!empty($lastStatus) && $lastStatus !=  GAME_LOGS::STATUS_PENDING){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                    $message = "already discarded.";
                    break;
                case GAME_LOGS::STATUS_REFUND:
                    $message = "already refunded.";
                    break;
                default:
                    $message = "already settled.";
                    break;
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id {$message}";
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST); #default
        $errorCode['message'] = "Lock and transfer failed";
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $parentTransactionId, $request, &$response, &$errorCode) {
            $success = false;
            $amountToAdd = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorCode['message'] = "Failed fetch before balance";
                return false;
            }

            if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $this->wallet_model->setGameProviderActionType('payout');

                $success = $this->wallet_model->incSubWallet($playerId, $this->api->getPlatformCode(), $amountToAdd, $afterBalance);
                if(!$success){
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorCode['message'] = "Failed on add balance.";
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                        $errorCode['message'] = "Failed fetch after balance";
                        return false;
                    }
                }

                $request['before_balance'] = $beforeBalance;
                $request['after_balance'] = $afterBalance;
                $request['action'] = "win";
                $request['sbe_status'] = GAME_LOGS::STATUS_SETTLED;
                $request['bet_amount'] = 0;
                $request['result_amount'] = $amountToAdd;
                $request['external_unique_id'] = $uniqueid;
                $request['amount'] = $amountToAdd;
                $request['player_id'] = $request['transaction']['ext_player_id'];
                $requesst['parent_transaction_id'] = $parentTransactionId;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $response = array(
                        "code" => self::CODE_SUCCESS,
                        // "id" => (string)$transId,
                        "id" => ($this->multiCurrencyEnabled) ? (string) $transId . "_" . $this->currency: (string) $transId,
                        "ext_transaction_id" => (string)$uniqueid, 
                        // "parent_transaction_id" => $parentTransactionId,
                        "parent_transaction_id" => ($this->multiCurrencyEnabled) ? (string) $parentTransactionId . "_" . $this->currency: (string) $parentTransactionId,
                        "user_id" => $request['transaction']['ext_player_id'],
                        "operation" => "win",
                        "amount" => (float) $this->api->dBtoGameAmount($request['amount']),
                        "currency" => $this->api->currency,
                        "balance" => (float) $this->api->dBtoGameAmount($afterBalance),
                    );
                }
            }

            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    private function refund(){
        $request = $this->request;
        $amount = isset($request['transaction']['amount']) ? $request['transaction']['amount'] : null;
        if(!$this->isValidAmount($amount)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        }

        $uniqueid = isset($request['transaction']['id']) ? $request['transaction']['id'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid transaction id value.";
            return $this->setResponse($errorResponse);
        }

        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id already exist.";
            return $this->setResponse($errorResponse);
        }

        $betTransactionId = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
        if($this->multiCurrencyEnabled){
            $betTransactionIdArray = explode("_", $betTransactionId);
            $betTransactionId = isset($betTransactionIdArray[0]) ? $betTransactionIdArray[0] : $betTransactionId;
            $request['bet_transaction_id'] = $betTransactionId;# override
        }

        $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["id" =>$betTransactionId]);
        if(!$isBetExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
            return $this->setResponse($errorResponse);
        } else {
            $betTransactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(self::TRANSACTION_TABLE, ['id'=> $betTransactionId],['action']);
            if(strtolower($betTransactionDetails['action']) != "make"){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_PARENT_TRANSACTION_NOT_FOUND);
                return $this->setResponse($errorResponse);
            }
        }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData(self::TRANSACTION_TABLE, ["bet_transaction_id" =>$betTransactionId, "action !=" => "commit"]);
        $parentTransactionId = !empty($lastId) ? $lastId : $betTransactionId;
        if(!empty($lastStatus) && $lastStatus != GAME_LOGS::STATUS_PENDING){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                    $message = "already discarded.";
                    break;
                case GAME_LOGS::STATUS_REFUND:
                    $message = "already refunded.";
                    break;
                default:
                    $message = "already settled.";
                    break;
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id {$message}";
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST); #default
        $errorCode['message'] = "Lock and transfer failed";
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $parentTransactionId, $request, &$response, &$errorCode) {
            $success = false;
            $amountToAdd = isset($request['transaction']['amount']) ? $this->api->gameAmountToDB($request['transaction']['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorCode['message'] = "Failed fetch before balance";
                return false;
            }

            if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $this->wallet_model->setGameProviderActionType('refund');

                $success = $this->wallet_model->incSubWallet($playerId, $this->api->getPlatformCode(), $amountToAdd, $afterBalance);
                if(!$success){
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorCode['message'] = "Failed on refund balance.";
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                        $errorCode['message'] = "Failed fetch after balance";
                        return false;
                    }
                }

                $request['before_balance'] = $beforeBalance;
                $request['after_balance'] = $afterBalance;
                $request['action'] = "refund";
                $request['sbe_status'] = GAME_LOGS::STATUS_REFUND;
                $request['bet_amount'] = 0;
                $request['result_amount'] = $amountToAdd;
                $request['external_unique_id'] = $uniqueid;
                $request['amount'] = $amountToAdd;
                $request['player_id'] = $request['transaction']['ext_player_id'];
                $request['parent_transaction_id'] = $parentTransactionId;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $response = array(
                        "code" => self::CODE_SUCCESS,
                        // "id" => (string)$transId,
                        "id" => ($this->multiCurrencyEnabled) ? (string) $transId . "_" . $this->currency: (string) $transId,
                        "ext_transaction_id" => (string)$uniqueid, 
                        // "parent_transaction_id" => $parentTransactionId,
                        "parent_transaction_id" => ($this->multiCurrencyEnabled) ? (string) $parentTransactionId . "_" . $this->currency: (string) $parentTransactionId,
                        "user_id" => $request['transaction']['ext_player_id'],
                        "operation" => "refund",
                        "amount" => $request['transaction']['amount'],
                        "currency" => $this->api->currency,
                        "balance" => (float) $this->api->dBtoGameAmount($afterBalance),
                    );
                }
            }

            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }
    }

    private function commit(){
        $request = $this->request;
        $betTransactionId = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
        if($this->multiCurrencyEnabled){
            $betTransactionIdArray = explode("_", $betTransactionId);
            $betTransactionId = isset($betTransactionIdArray[0]) ? $betTransactionIdArray[0] : $betTransactionId;
            $request['bet_transaction_id'] = $betTransactionId;# override
        }
        $isExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["id" =>$betTransactionId]);
        if(!$isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction not exist.";
            $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData(self::TRANSACTION_TABLE, ["bet_transaction_id" =>$betTransactionId, "action !=" => "commit"]);
        $parentTransactionId = !empty($lastId) ? $lastId : $betTransactionId;
        if(!empty($lastStatus) && $lastStatus !=  GAME_LOGS::STATUS_PENDING){
            switch ($lastStatus) {
                case GAME_LOGS::STATUS_VOID:
                    $message = "already discarded.";
                    break;
                case GAME_LOGS::STATUS_REFUND:
                    $message = "already refunded.";
                    break;
                default:
                    $message = "already settled.";
                    break;
            }
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id {$message}";
            return $this->setResponse($errorResponse);
        }

        $uniqueid = "commit-{$betTransactionId}";
        $requestExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($requestExist){
            $errorResponse = $this->generateResponseArray(self::CODE_SUCCESS);
            return $this->setResponse($errorResponse);
        } else{
            $request['sbe_status'] = GAME_LOGS::STATUS_PENDING;
            $request['external_unique_id'] = $uniqueid;
            $request['action'] = "commit";
            $request['bet_transaction_id'] = $betTransactionId;
            $request['parent_transaction_id'] = $parentTransactionId;
            $transId = $this->processRequestData($request);
            if(!$transId){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorResponse['message'] = "Error on commit.";
                $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
                return $this->setResponse($errorResponse);
            }
            $errorResponse = $this->generateResponseArray(self::CODE_SUCCESS);
            return $this->setResponse($errorResponse);
        }
    }

    private function settlement(){
        $request = $this->request;
        $bet_transaction_id = isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : null;
        if($this->multiCurrencyEnabled){
            $betTransactionIdArray = explode("_", $betTransactionId);
            $betTransactionId = isset($betTransactionIdArray[0]) ? $betTransactionIdArray[0] : $betTransactionId;
            $request['bet_transaction_id'] = $betTransactionId;# override
        }
        $isExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom(self::TRANSACTION_TABLE, ["id" =>$bet_transaction_id]);
        if(!$isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction not exist.";
            $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
            return $this->setResponse($errorResponse);
        }

        $uniqueid = "settlement-{$bet_transaction_id}";
        $requestExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($requestExist){
            $errorResponse = $this->generateResponseArray(self::CODE_SUCCESS);
            return $this->setResponse($errorResponse);
        } else{
            $status = isset($request['status']) ? $request['status'] : null;
            $request['sbe_status'] = $this->getSettlementStatus($status);
            $request['external_unique_id'] = $uniqueid;
            $request['action'] = "settlement";
            $request['bet_transaction_id'] = $betTransactionId;
            $transId = $this->processRequestData($request);
            if(!$transId){
                $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorResponse['message'] = "Error on settlement.";
                $this->utils->debug_log('BB INTERNAL ERROR LINE >>>>>>>>>>>>>>>', __LINE__);
                return $this->setResponse($errorResponse);
            }
            $errorResponse = $this->generateResponseArray(self::CODE_SUCCESS);
            return $this->setResponse($errorResponse);
        }
    }

    private function getSettlementStatus($status){
        switch (strtolower($status)) {
            case 'won':
            case 'lost':
            case 'cashed out':
            case 'half-won':
            case 'half-lost':
                return GAME_LOGS::STATUS_SETTLED;
                break;
            case 'canceled':
                return GAME_LOGS::STATUS_CANCELLED;
                break;
            case 'refund':
                return GAME_LOGS::STATUS_REFUND;
                break;
            default:
                return GAME_LOGS::STATUS_PENDING;
                break;
        }
    }

    private function make(){
        $request = $this->request;

        if($this->external_system->isGameApiMaintenance(BETBY_SEAMLESS_GAME_API)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "The game is under maintenance.";
            return $this->setResponse($errorResponse);   
        }

        $amount = isset($request['amount']) ? $request['amount'] : null;
        if(!$this->isValidAmount($amount)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid money value.";
            return $this->setResponse($errorResponse);
        }

        $uniqueid = isset($request['transaction']['id']) ? $request['transaction']['id'] : null; #transactionid
        if(empty($uniqueid)){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Invalid transaction id value.";
            return $this->setResponse($errorResponse);
        }

        $isExist = $this->original_seamless_wallet_transactions->isTransactionExist(self::TRANSACTION_TABLE, $uniqueid);
        if($isExist){
            $errorResponse = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
            $errorResponse['message'] = "Transaction id already exist.";
            return $this->setResponse($errorResponse);
        }

        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST); #default
        $errorCode['message'] = "Lock and transfer failed";
        $response = array();
        $playerId = $this->playerId;
        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $request, &$response, &$errorCode) {
            $success = false;
            $amountToDeduct = isset($request['amount']) ? $this->api->gameAmountToDB($request['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance = null;
            if($beforeBalance === false){
                $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                $errorCode['message'] = "Failed fetch before balance";
                return false;
            }

            if($this->utils->compareResultFloat($amountToDeduct, '>', 0)) {
                if($this->utils->compareResultFloat($amountToDeduct, '>', $beforeBalance)) {
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_INSUFFICIENT_BALANCE);
                    return false;
                }

                $enabledRemoteWalletClientOnCurr=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabledRemoteWalletClientOnCurr)){
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('bet');
                } 
                $success = $this->wallet_model->decSubWallet($playerId, $this->api->getPlatformCode(), $amountToDeduct, $afterBalance);
                if(!$success){
                    $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                    $errorCode['message'] = "Failed on deduct balance.";
                    return false;
                }
 
            } elseif ($this->utils->compareResultFloat($amountToDeduct, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        $errorCode = $this->generateResponseArray(self::SBE_ERROR_CODE_BAD_REQUEST);
                        $errorCode['message'] = "Failed fetch after balance";
                        return false;
                    }
                }

                $request['before_balance'] = $beforeBalance;
                $request['after_balance'] = $afterBalance;
                $request['action'] = "make";
                $request['sbe_status'] = GAME_LOGS::STATUS_PENDING;
                $request['bet_amount'] = $amountToDeduct;
                $request['result_amount'] = -$amountToDeduct;
                $request['external_unique_id'] = $uniqueid;
                $request['amount'] = $amountToDeduct;
                $transId = $this->processRequestData($request);
                if($transId){
                    $success = true;
                    $response = array(
                        "code" => self::CODE_SUCCESS,
                        "id" => ($this->multiCurrencyEnabled) ? (string) $transId . "_" . $this->currency: $transId,
                        "ext_transaction_id" => (string)$uniqueid, 
                        "parent_transaction_id" => null,
                        "user_id" => $request['player_id'],
                        "operation" => "bet",
                        "amount" => (float)$this->api->dBtoGameAmount($request['amount']),
                        "currency" => $this->api->currency,
                        "balance" => (float)$this->api->dBtoGameAmount($afterBalance),
                    );
                }
            }

            return $success;
        });

        if(!empty($response) && $success){
            return $this->setResponse($response);
        } else {
            return $this->setResponse($errorCode);
        }
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
            return true;
        }
    }

    private function processRequestData($request){
        $md5 = null;
        if(in_array($this->requestMethod, self::FUNC_WITH_VALIDATION)){
            switch (strtolower($request['action'])) {
                case 'make':
                    $array_keys = ['currency', 'player_id', 'session_id'];
                    $float_key = ['amount'];
                    break;
                default:
                    $array_keys = [];
                    $float_key = [];
                    break;
            }
            $md5 = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($request, $array_keys, $float_key);
        }
        $dataToInsert = array(
            "action" => isset($request['action']) ? $request['action'] : NULL, 
            "amount" => isset($request['amount']) ? $request['amount'] : 0, 
            "currency" => isset($request['currency']) ? $request['currency'] : NULL, 
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL, 
            "session_id" => isset($request['session_id']) ? $request['session_id'] : NULL, 
            "bonus_id" => isset($request['bonus_id']) ? $request['bonus_id'] : NULL, 
            "bonus_type" => isset($request['bonus_type']) ? $request['bonus_type'] : NULL, 
            "potential_win" => isset($request['potential_win']) ? $request['potential_win'] : NULL, 
            "potential_comboboost_win" => isset($request['potential_comboboost_win']) ? $request['potential_comboboost_win'] : NULL, 
            "bet_transaction_id" => isset($request['bet_transaction_id']) ? $request['bet_transaction_id'] : NULL,
            "parent_transaction_id" => isset($request['parent_transaction_id']) ? $request['parent_transaction_id'] : NULL,

            #transaction details
            "transaction_id" => isset($request['transaction']['id']) ? $request['transaction']['id'] : NULL, 
            "betslip_id" => isset($request['transaction']['betslip_id']) ? $request['transaction']['betslip_id'] : NULL, 
            "operator_id" => isset($request['transaction']['operator_id']) ? $request['transaction']['operator_id'] : NULL, 
            "operator_brand_id" => isset($request['transaction']['operator_brand_id']) ? $request['transaction']['operator_brand_id'] : NULL, 
            "timestamp" => isset($request['transaction']['timestamp']) ? $request['transaction']['timestamp'] : NULL, 
            "date_time" => isset($request['transaction']['timestamp']) ? date("Y-m-d H:i:s", $request['transaction']['timestamp']) : NULL, 
            "cross_rate_euro" => isset($request['transaction']['cross_rate_euro']) ? $request['transaction']['cross_rate_euro'] : NULL, 
            "operation" => isset($request['transaction']['operation']) ? $request['transaction']['operation'] : NULL, 

            #array params
            "transaction" =>  isset($request['transaction']) ? json_encode($request['transaction']) : NULL, 
            "betslip" =>  isset($request['betslip']) ? json_encode($request['betslip']) : NULL, 
            "selections" =>  isset($request['selections']) ? json_encode($request['selections']) : NULL, 
            "request_json" =>  json_encode($this->request), 

            #sbe
            "sbe_status" => isset($request['sbe_status']) ? $request['sbe_status'] : NULL,  
            "bet_amount" => isset($request['bet_amount']) ? $request['bet_amount'] : NULL,  
            "result_amount" => isset($request['result_amount']) ? $request['result_amount'] : NULL,  
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL, 
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL, 
            "external_unique_id" => isset($request['external_unique_id']) ? $request['external_unique_id'] : NULL, 
            "response_result_id" => $this->responseResultId,
            "sbe_player_id" => $this->playerId,
            "md5_sum" => $md5,
        );
        if(isset($request['transaction']['currency'])){
            $dataToInsert['currency'] = $request['transaction']['currency'];
        }

        $unix_timestamp_on_ms = 13;
        if(isset($request['transaction_id']['timestamp']) &&  strlen($request['transaction_id']['timestamp']) == $unix_timestamp_on_ms){
            $dataToInsert['date_time'] = isset($request['transaction_id']['timestamp']) ? date('Y-m-d H:i:s', $request['transaction_id']['timestamp'] / 1000) : NULL;
        }

        $transId = $this->original_seamless_wallet_transactions->insertTransactionData(self::TRANSACTION_TABLE, $dataToInsert);
        return $transId;
    }

    private function getPlayerBalance($is_locked = true){
        if($this->playerId){
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                if($this->utils->isEnabledRemoteWalletClient()){
                    $useReadonly = true;
                    return $this->player_model->getPlayerSubWalletBalance($this->playerId, BETBY_SEAMLESS_GAME_API, $useReadonly);
                }
            }
            return $this->wallet_model->readonlyMainWalletFromDB($this->playerId);
        } else {
            return false;
        }
    }


    private function setResponse($response) {
        return $this->setOutput($response);
    }

    private function setOutput($response) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;
        $statusCode = 0;
        $flag = $response['code'] == self::CODE_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($flag == Response_result::FLAG_NORMAL){
            if(isset($response['code'])){
                unset($response['code']);
            }
        }
        

        if($this->responseResultId) {
            $disableResponseResultsTableOnly=$this->utils->getConfig('disabled_response_results_table_only');
            if($disableResponseResultsTableOnly){
                $respRlt = $this->response_result->readNewResponseById($this->responseResultId);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->requestHeaders;
                if($this->ipInvalid){
                    $statusCode = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
                $respRlt['player_id'] = $this->playerId;
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->responseResultId);
                    $statusCode = $content['status_code'] = $this->defaultStatusErrorCode;
                }
                $this->response_result->updateNewResponse($respRlt);
            } else {
                $response_result = $this->response_result->getResponseResultById($this->responseResultId);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
    
                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->requestHeaders;
                if($this->ipInvalid){
                    $statusCode = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                }
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->responseResultId);
                    $statusCode = $content['status_code'] = $this->defaultStatusErrorCode;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->responseResultId, null, $this->playerId, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }

        return $this->returnJsonResult((object)$response, $addOrigin, $origin, $pretty, $partialOutputOnError, $statusCode);
    }

    private function setResponseResult(){
        $responseResultId = $this->response_result->saveResponseResult(
            BETBY_SEAMLESS_GAME_API,
            Response_result::FLAG_NORMAL,
            $this->requestMethod,
            $this->rawRequest,
            [],#default empty response
            200,
            null,
            null
        );

        return $responseResultId;
    }
}

