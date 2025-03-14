<?php

if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/BaseController.php";
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Evolution_seamless_service_api extends BaseController
{
    use Seamless_service_api_module;

    private $auth_token; # the authentication token of game provider when accessing our endpoint
    protected $red_rake_game_api; # the evolution game api class
    protected $current_method; # the current method executing
    protected $extraInfoAuthtoken; # the token in extra info
    protected $response_result_id;
    protected $evoRequestData; # this is the request data of evolution to us
    protected $currency;

    protected $responseFormattedBalance = null;
    protected $responseFormattedBonus = null;
    protected $enable_game_whitelist_validation = false;


    protected $foundMissingTableId = null;
    private $seamless_service_unique_id = null;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];

    # API actions
    const ACTION_CHECK = "check";
    const ACTION_BALANCE = "balance";
    const ACTION_DEBIT = "debit";
    const ACTION_CREDIT = "credit";
    const ACTION_CANCEL = "cancel";
    const ACTION_PROMO_PAYOUT = "promo_payout";
    const ACTION_PROMO_DEBIT = "promo_debit";
    const ACTION_PROMO_CREDIT = "promo_credit";
    const ACTION_PROMO_CANCEL = "promo_cancel";
    const ACTION_PROMO_PAYOUT_UNDEFINED = "promo_payout_undefined";
    const ACTION_SID = "sid"; # for testing purpose only
    
    const API_ACTIONS =[
        self::ACTION_CHECK,
        self::ACTION_BALANCE,
        self::ACTION_DEBIT,
        self::ACTION_CREDIT,
        self::ACTION_CANCEL,
        self::ACTION_SID,
        self::ACTION_PROMO_PAYOUT,
        self::ACTION_PROMO_DEBIT,
        self::ACTION_PROMO_CREDIT,
        self::ACTION_PROMO_CANCEL
    ];

    protected $SUBPROVIDERS = [
        EVOLUTION_SEAMLESS_GAMING_API, EVOLUTION_NETENT_SEAMLESS_GAMING_API, EVOLUTION_NLC_SEAMLESS_GAMING_API, EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, 
        EVOLUTION_BTG_SEAMLESS_GAMING_API
    ];

    # error codes constant
    const CODE_ERROR_METHOD_NOT_ALLOWED = 1;

    private $transaction_for_fast_track = null;

    private $evolution_game_api = null;
    private $newTokenValidity = null;
    private $tokenTimeComparison = null;
    private $allowExpiredTokenOnCredit = null;
    private $allowExpiredTokenOnDebit = null;
    private $playerInfo = null;

    public function __construct()
    {
        parent::__construct();
        $this->ssa_init();
        # load model
        $this->load->model(array(
            "wallet_model",
            "game_provider_auth",
            "common_token",
            "player_model",
            "evolution_seamless_thb1_wallet_transactions_model"
        ));

		$this->retrieveHeaders();

    }

    private function initialize($method, $gamePlatformId = null){

        $this->request_data = $this->request();
        $this->evoRequestData = new stdClass();
        $externalGameId = null;

        $origGamePlatformId = $gamePlatformId;

        #load
        if(empty($gamePlatformId)){
            $gamePlatformId = EVOLUTION_SEAMLESS_GAMING_API;
        }

        $this->currency = $currency = null;

        $sid = isset($this->request_data['sid'])?$this->request_data['sid']:null;

        //$jsonStr = $this->game_api->decrypt($sid);
        $jsonDecode = explode('|', $sid);
        $currency = isset($jsonDecode[0])?$jsonDecode[0]:null;
        $this->token= isset($jsonDecode[1])?$jsonDecode[1]:null;

        $this->CI->utils->debug_log("EVOLUTION SEAMLESS initialize", 'sid', $sid, 
        'jsonDecode', $jsonDecode, 
        'token', $this->token, 
        'currency', $currency);
        
        if(empty($sid) && $method!=self::ACTION_SID){
            $this->CI->utils->error_log("EVOLUTION SEAMLESS empty sid", 'method', $method, 
            'gamePlatformId', $gamePlatformId, 
            'currency', $currency,
        'sid', $sid);
            return false;
        }

        #load DB
        if($method!=self::ACTION_CHECK){
            $currency = isset($this->request_data['currency'])?$this->request_data['currency']:null;
        }       

        #load DB
        if($method==self::ACTION_SID){
            $userId = isset($this->request_data['userId'])?$this->request_data['userId']:null;
            $userIdArr = explode('-', $userId);
            $currency = isset($userIdArr[0])?$userIdArr[0]:null;
            $this->CI->utils->debug_log("EVOLUTION SEAMLESS get currency by userId", 'method', $method, 
            'userId', $userId, 
            'currency', $currency,
            'userIdArr', $userIdArr,
            'gamePlatformId', $gamePlatformId);
        }         
        
        if(empty($currency)){
            $this->CI->utils->error_log("EVOLUTION SEAMLESS empty currency", 'method', $method, 
            'gamePlatformId', $gamePlatformId, 
            'currency', $currency);

            return false;
        }		

        $currency = strtolower($currency);
        
        if(!$this->utils->isAvailableCurrencyKey($currency)){
            $this->CI->utils->debug_log("EVOLUTION SEAMLESS currency not available", 'method', $method, 
            'gamePlatformId', $gamePlatformId, 
            'currency', $currency);
			return false;
		}

        $this->currency = $this->request_data['currency'] = $currency;

		$_multiple_db=Multiple_db::getSingletonInstance();
		$res = $_multiple_db->switchCIDatabase($currency);
        if(!$res){

            $this->CI->utils->debug_log("EVOLUTION SEAMLESS currency error cannot load db", 'method', $method, 
            'gamePlatformId', $gamePlatformId, 
            'currency', $currency);
			return false;
        }

        ####### OVERRIDE API by external game id
        $this->CI->utils->debug_log("EVOLUTION SEAMLESS load game api", 'method', $method, 
        'gamePlatformId', $gamePlatformId, 
        'currency', $currency);
        $this->evolution_game_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $this->enable_game_whitelist_validation = $this->evolution_game_api->enable_game_whitelist_validation;
        $gameDescData = null;

        # if method is check use sid to identify game platform id
        if($method==self::ACTION_CHECK || $method==self::ACTION_SID || $method==self::ACTION_BALANCE){
            $gamePlatformId = isset($jsonDecode[4])?$jsonDecode[4]:$origGamePlatformId;
        }else{

            # get game platform by game code
            $this->foundMissingTableId = $externalGameId = isset($this->request_data['game']['details']['table']['id'])?$this->request_data['game']['details']['table']['id']:null;

            if(empty($externalGameId) && $method==self::ACTION_PROMO_PAYOUT){

                $this->CI->utils->debug_log("EVOLUTION SEAMLESS action is promo_payout", 
                'gamePlatformId', $gamePlatformId, 
                'currency', $currency,
                'request_data', $this->request_data);

                $voucherId = null;

                if(isset($this->request_data['promoTransaction']['voucherId'])){
                    $voucherId = $this->request_data['promoTransaction']['voucherId'];
                }else{
                    $this->CI->utils->error_log("EVOLUTION SEAMLESS missing voucherId in promo_payout", 
                    'gamePlatformId', $gamePlatformId, 
                    'currency', $currency,
                    'request_data', $this->request_data);
                }

                # if promo payout get the game_id from promo_debit using vendor id as per GP "the voucher ID can be the key value to map"
                $externalGameId = null;

                # need to loop through subproviders
                $subproviderApis = $this->SUBPROVIDERS;
                $promoDebitData = null;

                if(!empty($voucherId)){
                    foreach($subproviderApis as $subApiId){
                        $subApi = $this->utils->loadExternalSystemLibObject($subApiId);
                        if(!$subApi){
                            continue;
                        }
                        $transTable = $subApi->getTransactionsTable();
                        $whereCondition = [
                            'promoTransactionVoucherId'=>$voucherId, 
                            'action'=>self::ACTION_PROMO_DEBIT,
                            'userId'=>$this->request_data['userId']
                        ];
                        $promoDebitData = $this->evolution_seamless_thb1_wallet_transactions_model->getRoundData($transTable, $whereCondition);
    
                        $this->CI->utils->debug_log("EVOLUTION SEAMLESS loop subprovider find promo_payout promo_debit", 
                        'whereCondition', $whereCondition, 
                        'promoDebitData', $promoDebitData, 
                        'subApiId', $subApiId, 
                        'currency', $currency,
                        'transTable', $transTable);
                        if(!empty($promoDebitData )){
                            break;
                        }
    
                    }
                }//end check voucherId
                
                //if(empty($promoDebitData) || count($promoDebitData)!=1*/ || !isset($promoDebitData[0]['gameDetailsTableId'])){
                if(empty($promoDebitData) || !isset($promoDebitData[0]['gameDetailsTableId'])){
                    $this->CI->utils->error_log("EVOLUTION SEAMLESS cannot find promo_payout promo_debit", 
                    'whereCondition', $whereCondition, 
                    'promoDebitData', $promoDebitData, 
                    'gamePlatformId', $gamePlatformId, 
                    'currency', $currency,
                    'transTable', $transTable);

                    $this->foundMissingTableId = $externalGameId = 'promo_payout';
                }else{
                    $this->foundMissingTableId = $externalGameId = $promoDebitData[0]['gameDetailsTableId'];
                }

                /*$transTable = $this->evolution_game_api->getTransactionsTable();
                $whereCondition = [
                    'promoTransactionVoucherId'=>$this->request_data['promoTransaction']['voucherId'], 
                    'action'=>self::ACTION_PROMO_DEBIT,
                    'userId'=>$this->request_data['userId']
                ];
                $promoDebitData = $this->evolution_seamless_thb1_wallet_transactions_model->getRoundData($transTable, $whereCondition);
                if(empty($promoDebitData) || count($promoDebitData)!=1 || !isset($promoDebitData[0]['gameDetailsTableId'])){
                    $this->CI->utils->error_log("EVOLUTION SEAMLESS cannot find promo_payout promo_debit", 
                    'whereCondition', $whereCondition, 
                    'promoDebitData', $promoDebitData, 
                    'gamePlatformId', $gamePlatformId, 
                    'currency', $currency,
                    'transTable', $transTable);
                }else{
                    $externalGameId = $promoDebitData[0]['gameDetailsTableId'];
                }*/
                
            }
            
            # block if table is is empty
            if(empty($externalGameId)){
                $this->CI->utils->error_log("EVOLUTION SEAMLESS empty tableid", 'method', $method, 
                    'gamePlatformId', $gamePlatformId, 
                    'currency', $currency,
                    'request', $this->request_data);
            }
            $this->CI->load->model(['game_description_model']);
        
            # get game description by external game id and  game platform
            if(!empty($externalGameId)){
                $gameDescData = $this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatformIds($this->SUBPROVIDERS,$externalGameId, true);
            }

            $this->CI->utils->debug_log("EVOLUTION SEAMLESS gameDescData", 'method', $method, 
            'gamePlatformId', $gamePlatformId, 
            'currency', $currency,
            'sid', $sid,
            'externalGameId', $externalGameId,
            'gameDescData', $gameDescData);

            if(empty($gameDescData)){

                $this->CI->utils->error_log("EVOLUTION SEAMLESS empty gameDescData", 'method', $method, 
                'gamePlatformId', $gamePlatformId, 
                'currency', $currency,
                'sid', $sid,
                'externalGameId', $externalGameId);

                # send MM notification
                if($this->evolution_game_api||!isset($this->evolution_game_api->enable_mm_channel_nofifications)){
                    $this->evolution_game_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                }
                if($this->evolution_game_api->enable_mm_channel_nofifications){
                    $message = "@all EVOLUTION Seamless service missing game description error"."\n";			
                    $message .= "Game platform id: " . $gamePlatformId."\n";
                    $message .= "External game id: " . $externalGameId."\n";
                    $message .= "Method: " . $method."\n";
                    $message .= json_encode($this->request_data);
                    $this->sendNotificationToMattermost("EVOLUTION SEAMLESS SERVICE ($gamePlatformId)", $message, 'danger');
                }

                # just save the promo_payout
                $this->evolution_seamless_thb1_wallet_transactions_model->tableName = $this->evolution_game_api->getTransactionsTable();

                $this->promo_payout_undefined();

                
                return false;
            }else{
                $gamePlatformId = isset($gameDescData['game_platform_id'])?$gameDescData['game_platform_id']:null;
            }
        }

        $this->CI->utils->debug_log("EVOLUTION SEAMLESS load game api 2", 'method', $method, 
        'gamePlatformId', $gamePlatformId, 
        'currency', $currency, 
        'gameDescData', $gameDescData);

        # verify if game platform id in the list
        if(!in_array($gamePlatformId, $this->SUBPROVIDERS)){
            $this->CI->utils->error_log("EVOLUTION SEAMLESS gamePlatformId not in the sub provider list", 'method', $method, 
            'gamePlatformId', $gamePlatformId, 
            'currency', $currency,
        'sid', $sid,
    'subproviders', $this->SUBPROVIDERS);

            # send MM notification
            if($this->evolution_game_api->enable_mm_channel_nofifications){
                $message = "@all EVOLUTION Seamless service invalid game provider id error"."\n";			
                $message .= "Game platform id: " . $gamePlatformId."\n";
                $message .= "External game id: " . $externalGameId."\n";
                $message .= "Method: " . $method."\n";
                $message .= json_encode($this->request_data);
                $this->sendNotificationToMattermost("EVOLUTION SEAMLESS SERVICE ($gamePlatformId)", $message, 'danger');
            }
            return false;
        }
        ####### /OVERRIDE API by external game id

        $this->CI->utils->debug_log("EVOLUTION SEAMLESS load game api 3", 'method', $method, 
        'gamePlatformId', $gamePlatformId, 
        'currency', $currency);

        $this->auth_token = $this->input->get("authToken") ?: null;
        $this->evolution_game_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        
        $this->newTokenValidity = $this->evolution_game_api->getSystemInfo('newTokenValidity','+2 hours');
        $this->tokenTimeComparison = $this->evolution_game_api->getSystemInfo('tokenTimeComparison','-10 minutes');
        $this->allowExpiredTokenOnCredit = $this->evolution_game_api->getSystemInfo('allowExpiredTokenOnCredit',true);
        $this->allowExpiredTokenOnDebit = $this->evolution_game_api->getSystemInfo('allowExpiredTokenOnDebit',false);
        $tablename = $this->evolution_seamless_thb1_wallet_transactions_model->tableName = $this->evolution_game_api->getTransactionsTable();
        $this->use_remote_wallet_failed_transaction_monthly_table = $this->evolution_game_api->use_remote_wallet_failed_transaction_monthly_table;

        $this->CI->utils->debug_log("EVOLUTION SEAMLESS DB INFO", 'tableName', $tablename, 
        'use_remote_wallet_failed_transaction_monthly_table', $this->use_remote_wallet_failed_transaction_monthly_table);

		$this->retrieveHeaders();

        return true;
    }

    public function sendNotificationToMattermost($user,$message,$notifType,$texts_and_tags=null){

        $this->load->helper('mattermost_notification_helper');

        $notif_message = array(
            array(
                'text' => $message,
                'type' => $notifType
            )
        );
        return sendNotificationToMattermost($user, $this->evolution_game_api->mm_channel, $notif_message, $texts_and_tags);
    }

    /**
     * This is the first Entry of all Request
     * 
     * @param string $method the request method of game provider
     */
    public function index($gamePlatformId, $method)
    {
        # check if method is POST in the request
        if(! $this->isPostMethod()){
            return $this->showError("TEMPORARY_ERROR");
        }

        $init = $this->initialize($method, $gamePlatformId);
        if(!$init){
            $response = [
                "status" => 'UNKNOWN_ERROR',
                "message" => "Initialization error"                
            ];

            if($method==self::ACTION_PROMO_PAYOUT){
                $response['status'] = 'TEMPORARY_ERROR';
            }
            # set current method executing
            $this->current_method = $method;

            # save response result
            $response_result_id = $this->saveToResponseResult($this->current_method,$this->request_data, null, [], $response);
            return $this->outputData($response);
        }

        $this->current_method = "index";

        # check IP address
        if(! $this->evolution_game_api->validateWhiteIP()){
            $response = [
                "status" => 'ERROR_INVALID_IP',
                "message" => "IP Not allowed"                
            ];
            # set current method executing
            $this->current_method = $method;

            # save response result
            // $response_result_id = $this->saveToResponseResult($this->current_method,$data, null, [], $response);
            return $this->outputData($response, null, null, true);
        }

        $extraInfoAuthtoken = $this->evolution_game_api->getSystemInfo("auth_token");
        
        if($this->isProviderAuthorize($extraInfoAuthtoken)){

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API provider trying to access method: ", $method);

            if(in_array((string) $method,self::API_ACTIONS)){

                $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API provider access method: ", $method);

                try{
                    # set current method executing
                    $this->current_method = $method;

                    # save response result
                    $response_result_id = $this->saveToResponseResult($this->current_method,$this->request_data);

                    $this->response_result_id = $response_result_id;

                    # for testing purposes only
                    /*if(self::ACTION_SID == $method){

                        return $this->check();
                    }*/

                    if(isset($this->request_data['promoTransaction'])){

                        #return $this->promo_payout();

                    }

                    return $this->$method();

                }catch(\Exception $e){
                    return $this->showError($e->getMessage());
                }
                
            }else{
                # method is not exist
                return $this->showError("INVALID_PARAMETER");
            }
        }

        return $this->showError("INVALID_PARAMETER");
    }

    /** 
     * 
    */
    protected function check()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;

        $userId = $eObj->userId;

        $returnData = [
            "sid" => $sid,
            "uuid" => $eObj->uuid
        ];

        $playerId = null;

        if(property_exists($eObj,"sid")){

            # refresh token here, if 10 minutes before current timeout_at
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            $playerId = !empty($playerInfo->player_id) ? $playerInfo->player_id : null;
            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." method with data: ",$playerInfo);

            if(! empty($playerInfo)){

                $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." game_username ",$playerInfo->game_username, 'userId', $userId);

                //check if game username from player info is same as in parameters
                if($userId != $playerInfo->game_username){
                    $returnData["status"] = "INVALID_PARAMETER";

                    return $this->outputData($returnData, null, $playerId, true);
                }

                $returnData["status"] = "OK";

                $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." method with data: ",$returnData);

                return $this->outputData($returnData, null, $playerId, true);
            }

            $returnData["status"] = "INVALID_SID";

            return $this->outputData($returnData, null, $playerId, true);
        }

        $returnData["status"] = "INVALID_PARAMETER";

        return $this->outputData($returnData, null, $playerId, true);
    }

    /** 
     * 
    */
    protected function sid()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;

        $returnData = [
            "sid" => $sid,
            "uuid" => (isset($eObj->uuid)?$eObj->uuid:null)
        ];

        $playerId = null;

        if(property_exists($eObj,"userId")){

            $gameUsername = $eObj->userId;
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->evolution_game_api->getPlatformCode());		 
            $playerId = !empty($playerInfo->player_id)?$playerInfo->player_id:null;

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." playerInfo by getPlayerCompleteDetailsByGameUsername: ",$playerInfo, 'gameUsername', $gameUsername);
            
            if(!empty($token) && empty($playerInfo)){
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            
                //$playerInfo = $this->common_token->getPlayerInfoByToken($token,false,true,$this->tokenTimeComparison,$this->newTokenValidity);
                $playerId = !empty($playerInfo->player_id)?$playerInfo->player_id:null;
                $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." playerInfo by getPlayerInfoByToken: ",$playerInfo, 'gameUsername', $gameUsername);
            }


            if(! empty($playerInfo)){

                # generate player session
                $sessionArr = [
                    'token'=>$this->evolution_game_api->getPlayerTokenByUsername($playerInfo->username),
                    'currency'=>$this->currency,
                    'player_username'=>$playerInfo->username,
                    'player_gameusername'=>$gameUsername,
                    'game_platform_id'=>$this->evolution_game_api->getPlatformCode()
                ];
                $playerSessionId=$this->evolution_game_api->generateSessionId($sessionArr);

                $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." sessionArr",$sessionArr, 'playerSessionId', $playerSessionId);
            


                $returnData["status"] = "OK";
                #$returnData["sid"] = $this->common_token->getPlayerToken($playerId);
                $returnData["sid"] = $playerSessionId;

                return $this->outputData($returnData, null, $playerId, true);
            }

            $returnData["status"] = "INVALID_PARAMETER";
            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." EMPTY PLAYER INFO", 'playerInfo', $playerInfo);
            

            return $this->outputData($returnData, null, $playerId, true);
        }

        $returnData["status"] = "INVALID_PARAMETER";
        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." EMPTY userid", 'playerInfo', $playerInfo);

        return $this->outputData($returnData, null, $playerId, true);
    }

    /** 
     * 
    */
    protected function balance()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $userId = $eObj->userId;
        $eObj->eCurrentMethod = __FUNCTION__;
        $playerId = null;

        if(property_exists($eObj,"sid")){

            # refresh token here, if 10 minutes before current timeout_at
           $this->playerInfo = $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());

            if(! empty($this->playerInfo)){                
                $playerId = !empty($playerInfo->player_id) ? $playerInfo->player_id : null;
                $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." game_username ",$playerInfo->game_username, 'userId', $userId);

                //check if game username from player info is same as in parameters
                if($userId != $playerInfo->game_username){
                    $returnData["status"] = "INVALID_PARAMETER";

                    return $this->outputData($returnData, null, $playerId, true);
                }

                $eObj->eResponseStatusCode = "OK";

                return $this->getStandardResponse($eObj);
            }

            $eObj->eResponseStatusCode = "INVALID_SID";

            return $this->getStandardResponse($eObj);
        }
        
        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }

    /** 
     * 
    */
    protected function debit()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $eObj->userId;

        if($this->isParamComplete($eObj)){

            # get complete player info
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            /*if(empty($playerInfo)) {
                $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                return $this->getStandardResponse($eObj);
            }*/
            
            if(empty($playerInfo)) {
                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            if(empty($playerInfo)) {
                $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                return $this->getStandardResponse($eObj);
            }
                
            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." game_username ",$playerInfo->game_username, 'userId', $userId);

            
            # check if token is same as player game account
            if($userId != $playerInfo->game_username){
                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            # check API maintenance
            if (!$this->external_system->isGameApiActive($this->get_platform_code()) || $this->external_system->isGameApiMaintenance($this->get_platform_code())) {
                $eObj->eResponseStatusCode = 'TEMPORARY_ERROR';
                return $this->getStandardResponse($eObj);
            }

            if ($this->enable_game_whitelist_validation) {
                if (!empty($eObj->game->details->table->id) && !$this->utils->isGameWhitelisted($this->evolution_game_api->getPlatformCode(), $eObj->game->details->table->id)) {
                    $eObj->eResponseStatusCode = "FAILED";

                    $this->utils->debug_log('game_whitelist', 'enable_game_whitelist_validation', $this->enable_game_whitelist_validation, 
                    'game_platform_id', $this->evolution_game_api->getPlatformCode(), 'status', $eObj->eResponseStatusCode, 'method', __FUNCTION__);

                    return $this->getStandardResponse($eObj, true);
                }
            }

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data
            $before_balance = $tData["before_balance"];

            # check if already exist
            # check if there is pending cancel
            # get all related transactions
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionRefId'=>$eObj->transaction->refId];
            $roundData = $this->evolution_seamless_thb1_wallet_transactions_model->getRoundData($transTable, $whereCondition);
            $betAmount = $tData["amount"];
            $withPendingCancel = false;

            $debitExist = false;
            foreach($roundData as $roundRow){
                # check if debit exist
                if($roundRow['action']==self::ACTION_DEBIT){
                    $eObj->eResponseStatusCode = "BET_ALREADY_EXIST";
                    return $this->getStandardResponse($eObj);
                }

                # check if with pending cancel
                if($roundRow['action']==self::ACTION_CANCEL){
                    $betAmount = 0;
                    $withPendingCancel = true;
                }
            }

            # check if balance is sufficient
            $balanceSufficient = $this->utils->compareResultFloat($before_balance,">=",$betAmount);
            if(!$balanceSufficient){
                $eObj->eResponseStatusCode = "INSUFFICIENT_FUNDS";
                return $this->getStandardResponse($eObj);
            }

            # do the deduct transaction
            $eObj->eAction = __FUNCTION__;
            if($betAmount>0){
                $after_balance = null;
                $is_success_trans = $this->doDeduct($tData["playerId"],$betAmount,$eObj,$before_balance, $after_balance, 'bet', false);
    
                if($is_success_trans){
                    $eObj->eResponseStatusCode = "OK";
                    return $this->getStandardResponse($eObj);
                }

            }else{
                $this->doInsertToGameTransactions($eObj,$before_balance,$before_balance);
                $eObj->eResponseStatusCode = "OK";
                if($withPendingCancel){
                    $eObj->eResponseStatusCode = "FINAL_ERROR_ACTION_FAILED";
                }
                return $this->getStandardResponse($eObj);
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj);

        }

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }

    /** 
     * 
    */
    protected function credit()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $username = $eObj->userId;

        if($this->isParamComplete($eObj)){

            # refresh token here, if 10 minutes before current timeout_at
            /*$playerInfo = $this->common_token->getPlayerInfoByToken($token,true,true,$this->tokenTimeComparison,$this->newTokenValidity);
            if(empty($playerInfo) && $this->allowExpiredTokenOnCredit){
                $playerInfo = $this->common_token->getPlayerInfoByOldToken($token);
                $player_username = $this->evolution_game_api->getPlayerUsernameByGameUsername($username);
                if(empty($playerInfo)) {
                    $playerInfo = (array) $this->evolution_game_api->getPlayerInfoByUsername($player_username);
                } else {
                    if($player_username != $playerInfo['username']){
                        $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                        return $this->getStandardResponse($eObj);
                    }
                }
            }*/

            # try get player by token
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            if(empty($playerInfo)) {

                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            # check if token is same as player game account
            if($userId != $playerInfo->game_username){
                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            /*if(empty($playerInfo)) {
                $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                return $this->getStandardResponse($eObj);
            }*/

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data
            $before_balance = $after_balance = $tData["before_balance"];

            # get all related transactions
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionRefId'=>$eObj->transaction->refId];
            $roundData = $this->evolution_seamless_thb1_wallet_transactions_model->getRoundData($transTable, $whereCondition);

            $debitExist = false;

            foreach($roundData as $roundRow){

                # exist already
                //$_uniqueId = $tData['transactionId'].'-'.$eObj->eCurrentMethod;
                //if($roundRow['external_uniqueid']==$_uniqueId){
                if($roundRow['action']==self::ACTION_CANCEL && $eObj->transaction->refId==$roundRow['transactionRefId']){
                    $eObj->eResponseStatusCode = "BET_ALREADY_SETTLED";
                    return $this->getStandardResponse($eObj);
                }

                # check if already cancelled
                if($roundRow['action']==self::ACTION_CANCEL){
                    $eObj->eResponseStatusCode = "BET_ALREADY_SETTLED";
                    return $this->getStandardResponse($eObj);
                }

                # check if already cancelled
                if($roundRow['action']==self::ACTION_CREDIT){
                    $eObj->eResponseStatusCode = "OK";
                    return $this->getStandardResponse($eObj);
                }                   

                # check if debit exist
                if($roundRow['action']==self::ACTION_DEBIT){
                    $debitExist = true;
                    #implement increase/decrease balance related_uniqueid and related_action
                    $related_uniqueid = isset($roundRow['external_uniqueid']) ? 'game-'.$this->evolution_game_api->getPlatformCode().'-'.$roundRow['external_uniqueid'] : null;
                    $related_action = Wallet_model::REMOTE_RELATED_ACTION_BET;

                    if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                        $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
                    }
                    if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                        $this->wallet_model->setRelatedActionOfSeamlessService($related_action);
                    }
                }
            }

            # check if debit does not exist
            if(!$debitExist){
                $eObj->eResponseStatusCode = "BET_DOES_NOT_EXIST";
                return $this->getStandardResponse($eObj);
            }

            if(!$this->evolution_game_api->enable_skip_credit_player_validation ){
                # player checkpoint for validity
                $isPlayerAllowedToBet = $this->isPlayerAllowedToBet($tData["before_balance"],$tData["amount"],$tData["playerName"],$tData["playerId"],$tData["transactionId"],false);

                if(! is_null($isPlayerAllowedToBet)){

                    $eObj->eResponseStatusCode = $isPlayerAllowedToBet;

                    return $this->getStandardResponse($eObj);
                }
            }
            
            # do the increment transaction
            $eObj->eAction = __FUNCTION__;
            $is_success_trans = $this->doIncrement($tData["playerId"],$tData["amount"],$eObj,$before_balance,$after_balance, 'payout', true);

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." transaction is:",$is_success_trans);

            if($is_success_trans){

                $eObj->eResponseStatusCode = "OK";

                return $this->getStandardResponse($eObj);
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj);

        }

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }

    /** 
     * 
    */
    protected function promo_payout()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $username = $eObj->userId;

        $isPromo = false;
        if(property_exists($eObj,"promoTransaction")){
            $isPromo = true;
        }

        if($this->isParamComplete($eObj, true)){
            # refresh token here, if 10 minutes before current timeout_at
            /*$playerInfo = $this->common_token->getPlayerInfoByToken($token,true,true,$this->tokenTimeComparison,$this->newTokenValidity);
            if(empty($playerInfo) && $this->allowExpiredTokenOnCredit){
                $playerInfo = $this->common_token->getPlayerInfoByOldToken($token);
                $player_username = $this->evolution_game_api->getPlayerUsernameByGameUsername($username);
                if(empty($playerInfo)) {
                    $playerInfo = (array) $this->evolution_game_api->getPlayerInfoByUsername($player_username);
                } else {
                    if($player_username != $playerInfo['username']){
                        $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                        return $this->getStandardResponse($eObj);
                    }
                }
            }*/

            # try get player by token
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            if(empty($playerInfo)) {

                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            # check if token is same as player game account
            if($userId != $playerInfo->game_username){
                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            /*if(empty($playerInfo)){
                $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                return $this->getStandardResponse($eObj);
            }*/

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data
            $before_balance = $after_balance = $tData["before_balance"];

            # get transaction data
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionId'=>$eObj->promoTransaction->id];
            $transData = $this->evolution_seamless_thb1_wallet_transactions_model->getTransactionData($transTable, $whereCondition);

            if(!empty($transData)){
                $eObj->eResponseStatusCode = "OK";
                return $this->getStandardResponse($eObj);
            }

            # do the increment transaction
            $eObj->eAction = __FUNCTION__;
            $is_success_trans = $this->doIncrement($tData["playerId"],$tData["amount"],$eObj,$before_balance,$after_balance, 'payout', true);

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." transaction is:",$is_success_trans);

            if($is_success_trans){

                $eObj->eResponseStatusCode = "OK";

                return $this->getStandardResponse($eObj);
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj);

        }

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }

    /** 
     * 
    */
    protected function promo_payout_undefined()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $username = $eObj->userId;

        $isPromo = false;
        if(property_exists($eObj,"promoTransaction")){
            $isPromo = true;
        }
        $before_balance = $after_balance = 0;


        $eObj->eAction = __FUNCTION__;

        # get transaction data
        $transTable = $this->evolution_game_api->getTransactionsTable();
        $promoTransactionId = isset($eObj->promoTransaction->id) ? $eObj->promoTransaction->id : null;
        $whereCondition = ['transactionId'=>$promoTransactionId.'-'.self::ACTION_PROMO_PAYOUT_UNDEFINED];
        $transData = $this->evolution_seamless_thb1_wallet_transactions_model->getTransactionData($transTable, $whereCondition);

        if(!empty($transData)){
            $eObj->eResponseStatusCode = "OK";
            return $this->getStandardResponse($eObj);
        }

        $is_success_trans = $this->doInsertToGameTransactions($eObj,$before_balance,$after_balance);

        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." transaction is:",$is_success_trans);

        //$eObj->eResponseStatusCode = "TEMPORARY_ERROR";

        //return $this->getStandardResponse($eObj, false);
        return true;
    }

    /** 
     * 
    */
    protected function promo_debit()
    {

        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__);

        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $username = $eObj->userId;

        $isPromo = false;
        if(property_exists($eObj,"promoTransaction")){
            $isPromo = true;
        }

        if($this->isParamComplete($eObj, true)){

            # try get player by token
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            if(empty($playerInfo)) {

                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            # check if token is same as player game account
            if($userId != $playerInfo->game_username){

                $this->CI->utils->error_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__,
                'userId', $userId,
                'game_username', $playerInfo->game_username  
                );

                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data
            $before_balance = $after_balance = $tData["before_balance"];

            # get transaction data
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionId'=>$eObj->promoTransaction->id];
            $transData = $this->evolution_seamless_thb1_wallet_transactions_model->getTransactionData($transTable, $whereCondition);

            # already exist
            if(!empty($transData)){
                $eObj->eResponseStatusCode = "OK";
                return $this->getStandardResponse($eObj, false, true);
            }

            # do the increment transaction
            $eObj->eAction = __FUNCTION__;

            $is_success_trans = $this->doInsertToGameTransactions($eObj,$before_balance,$after_balance);

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." transaction is:",$is_success_trans);

            $eObj->eResponseStatusCode = "OK";

            return $this->getStandardResponse($eObj, false);

            /*if($is_success_trans){

                $eObj->eResponseStatusCode = "OK";

                return $this->getStandardResponse($eObj, false, true);
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj, false, true);*/

        }



        $this->CI->utils->error_log("EVOLUTION_SEAMLESS_THB1_API incomplete parameters".__FUNCTION__,
        'eObj', (array)$eObj
        );

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj, false, true);
    }

    /** 
     * 
    */
    protected function promo_credit()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $username = $eObj->userId;

        $isPromo = false;
        if(property_exists($eObj,"promoTransaction")){
            $isPromo = true;
        }

        if($this->isParamComplete($eObj, true)){

            # try get player by token
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            if(empty($playerInfo)) {

                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            # check if token is same as player game account
            if($userId != $playerInfo->game_username){
                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data
            $before_balance = $after_balance = $tData["before_balance"];

            # get transaction data
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionId'=>$eObj->promoTransaction->id];
            $transData = $this->evolution_seamless_thb1_wallet_transactions_model->getTransactionData($transTable, $whereCondition);

            # already exist
            if(!empty($transData)){
                $eObj->eResponseStatusCode = "OK";
                return $this->getStandardResponse($eObj);
            }

            # do the increment transaction
            $eObj->eAction = __FUNCTION__;

            $is_success_trans = $this->doInsertToGameTransactions($eObj,$before_balance,$after_balance);

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." transaction is:",$is_success_trans);
            
            $eObj->eResponseStatusCode = "OK";

            return $this->getStandardResponse($eObj);

            /*if($is_success_trans){

                $eObj->eResponseStatusCode = "OK";

                return $this->getStandardResponse($eObj);
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj);*/

        }

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }

    /** 
     * 
     * */
    protected function promo_cancel()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eCurrentMethod = __FUNCTION__;
        $userId = $username = $eObj->userId;

        $isPromo = false;
        if(property_exists($eObj,"promoTransaction")){
            $isPromo = true;
        }

        if($this->isParamComplete($eObj, true)){

            # try get player by token
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            if(empty($playerInfo)) {

                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            # check if token is same as player game account
            if($userId != $playerInfo->game_username){
                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data
            $before_balance = $after_balance = $tData["before_balance"];

            # get transaction data
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionId'=>$eObj->promoTransaction->id];
            $transData = $this->evolution_seamless_thb1_wallet_transactions_model->getTransactionData($transTable, $whereCondition);

            # already exist
            if(!empty($transData)){
                $eObj->eResponseStatusCode = "OK";
                return $this->getStandardResponse($eObj);
            }

            # do the increment transaction
            $eObj->eAction = __FUNCTION__;

            $is_success_trans = $this->doInsertToGameTransactions($eObj,$before_balance,$after_balance);

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." transaction is:",$is_success_trans);
            
            $eObj->eResponseStatusCode = "OK";

            return $this->getStandardResponse($eObj, false);

            /*if($is_success_trans){

                $eObj->eResponseStatusCode = "OK";

                return $this->getStandardResponse($eObj);
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj);*/

        }

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }


    /**
     * 
     */
    protected function cancel()
    {
        $dataObject = $this->request(false);
        $eObj = $this->evoRequestData->params = $dataObject;
        $token =  $this->token;
        $sid = $eObj->sid;
        $eObj->eAction = __FUNCTION__;
        $eObj->eCurrentMethod = __FUNCTION__;
        $username = $eObj->userId;
        $userId = $eObj->userId;
        
        if($this->isParamComplete($eObj)){

            # refresh token here, if 10 minutes before current timeout_at
            /*$playerInfo = $this->common_token->getPlayerInfoByToken($token,true,true,$this->tokenTimeComparison,$this->newTokenValidity);
            if(empty($playerInfo) && $this->allowExpiredTokenOnCredit){
                $playerInfo = $this->common_token->getPlayerInfoByOldToken($token);
                $player_username = $this->evolution_game_api->getPlayerUsernameByGameUsername($username);
                if(empty($playerInfo)) {
                    $playerInfo = (array) $this->evolution_game_api->getPlayerInfoByUsername($player_username);
                } else {
                    if($player_username != $playerInfo['username']){
                        $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                        return $this->getStandardResponse($eObj);
                    }
                }
            }*/

            # try get player by token
            $playerInfo = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->evolution_game_api->getPlatformCode());
            if(empty($playerInfo)) {

                # try get by game username
                $playerInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($userId, $this->evolution_game_api->getPlatformCode());
                if(empty($playerInfo)) {
                    $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                    return $this->getStandardResponse($eObj);
                }
            }

            $this->playerInfo = $playerInfo;

            $_playerInfo = [];
            $_playerInfo['playerId'] = $playerInfo->player_id;
            $_playerInfo['username'] = $playerInfo->username;
            $tData = $this->getDataForTransaction($_playerInfo, $eObj); # the transaction Data

            if(empty($playerInfo)){
                $eObj->eResponseStatusCode = "INVALID_TOKEN_ID";
                return $this->getStandardResponse($eObj);
            }            
            
            # check if token is same as player game account
            if($userId != $playerInfo->game_username){
                $eObj->eResponseStatusCode = "INVALID_PARAMETER";
                return $this->getStandardResponse($eObj, true);
            }

            $before_balance = $after_balance = $tData["before_balance"];

            # get all related transactions
            $transTable = $this->evolution_game_api->getTransactionsTable();
            $whereCondition = ['transactionRefId'=>$eObj->transaction->refId];
            $roundData = $this->evolution_seamless_thb1_wallet_transactions_model->getRoundData($transTable, $whereCondition);

            $playerId= $tData["playerId"];
            $debitExist = false;


            foreach($roundData as $roundRow){
                
                # exist already
                if($roundRow['action']==self::ACTION_CANCEL && $eObj->transaction->refId==$roundRow['transactionRefId']){
                    $eObj->eResponseStatusCode = "BET_ALREADY_SETTLED";
                    return $this->getStandardResponse($eObj);
                    /*if(!empty($roundRow['refundedIn'])){
                        $eObj->eResponseStatusCode = "BET_ALREADY_SETTLED";
                        return $this->getStandardResponse($eObj);
                    }
                    $eObj->eResponseStatusCode = "OK";
                    return $this->getStandardResponse($eObj);*/
                }

                # check if already cancelled
                if($roundRow['action']==self::ACTION_CANCEL){
                    $eObj->eResponseStatusCode = "BET_ALREADY_SETTLED";
                    return $this->getStandardResponse($eObj);
                }

                # check if already cancelled
                if($roundRow['action']==self::ACTION_CREDIT){
                    $eObj->eResponseStatusCode = "BET_ALREADY_SETTLED";
                    return $this->getStandardResponse($eObj);
                }              

                # check if debit exist
                if($roundRow['action']==self::ACTION_DEBIT){
                    $debitExist = true;

                    #implement increase/decrease balance related_uniqueid and related_action
                    $related_uniqueid = isset($roundRow['external_uniqueid']) ? 'game-'.$this->evolution_game_api->getPlatformCode().'-'.$roundRow['external_uniqueid'] : null;
                    $related_action = Wallet_model::REMOTE_RELATED_ACTION_BET;

                    if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                        $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid);
                    }
                    if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                        $this->wallet_model->setRelatedActionOfSeamlessService($related_action);
                    }
                }
            }

            //check if debit does not exist
            if(!$debitExist){
                $eObj->eResponseStatusCode = "BET_DOES_NOT_EXIST";

                //save the transaction
                
                $this->doInsertToGameTransactions($eObj,$tData["before_balance"],$tData["before_balance"]);

                return $this->getStandardResponse($eObj);
            }

            # player checkpoint for validity
            /*$isPlayerAllowedToBet = $this->isPlayerAllowedToBet($tData["before_balance"],$tData["amount"],$tData["playerName"],$tData["playerId"],$tData["transactionId"],false,false);

            if(! is_null($isPlayerAllowedToBet)){

                $eObj->eResponseStatusCode = $isPlayerAllowedToBet;

                return $this->getStandardResponse($eObj);
            }*/

            $updatedRows = $this->evolution_seamless_thb1_wallet_transactions_model->updateRefundedTransaction($tData["transactionId"]);

            $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." updated rows is: ",$updatedRows);

            if($updatedRows == 0){

                $eObj->eResponseStatusCode = "BET_DOES_NOT_EXIST";

                return $this->getStandardResponse($eObj);
            }

            if($updatedRows > 0){

                # do the increment transaction
                $transactionAmount = $this->evolution_seamless_thb1_wallet_transactions_model->getTransactionAmount($tData["transactionId"]);
                
                # set refundedTransactionId in cancel action
                $eObj->eRefundedTransactionId = $tData["transactionId"];
                # we concat transactionId and  transactionRefId because transactionId is unique in table and that is the transactionId of bet to be refunded
                $eObj->transaction->id = $tData["transactionId"] . "-". $eObj->transaction->refId;
                $is_success_trans = $this->doIncrement($tData["playerId"],$transactionAmount,$eObj,$before_balance,$after_balance, 'refund', true);

                if($is_success_trans){

                    $eObj->eResponseStatusCode = "OK";

                    return $this->getStandardResponse($eObj);
                }
            }

            $eObj->eResponseStatusCode = "TEMPORARY_ERROR";

            return $this->getStandardResponse($eObj);
        }

        $eObj->eResponseStatusCode = "INVALID_PARAMETER";

        return $this->getStandardResponse($eObj);
    }


    /** 
     * Return error, with error code
     * 
     * @param int $code the code error
     * 
     * @return json
    */
    public function showError($code)
    {
        # update content to error code and error message, field when we received error
        $errorData = [
            "code" => $code
        ];

        if(! empty($this->response_result_id)){
            $this->db->where("id",$this->response_result_id)
                    ->update("response_results",[
                        "content" => json_encode($errorData),
                        "flag"=>2,
                    'cost_ms' => $cost
                ]);
        }
       
        return $this->outputData([
            "status" => $code
        ]);
    }

    /**
     * Output Data in json header format
     * 
     * @param array $data the data to be outputed
     * @param int $response_result_id the response result id of the request
     * @param int $player_id the player id
     */
    private function outputData($data=[],$response_result_id=null,$player_id=null, $save_response_result = false)
    {
        $output = json_encode((array) $data);

        if($this->evolution_game_api->seamless_api_return_balance_no_quote){
            $output = str_replace('"player_balance"', $this->responseFormattedBalance, $output);
            $output = str_replace('"player_bonus"', $this->responseFormattedBonus, $output);
        }

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);
        if($response_result_id){
            $this->db->update(
                "response_results",[
                    "content" => $output,
                    "player_id" => $player_id,
                    'cost_ms' => $cost
                ],[
                    "id" => $response_result_id
                ]
            );
        }

        if ($save_response_result) {
            $response_result_id = $this->saveToResponseResult($this->current_method, $this->request(), null, ['player_id' => $player_id], $output);
        }

        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration')) {
            $this->sendToFastTrack();
        }

        if(isset($data['status']) && $data['status']=='ERROR_INVALID_IP'){
            $httpStatusCode = 401;
            $this->output->set_status_header($httpStatusCode);
            return $this->output->set_content_type("application/json")
            ->set_output($output);
        }

        return $this->output->set_content_type("application/json")
                            ->set_output($output);
    }

    /**
     * Check if provider's auth token is match with our auth token in extra info
     * 
     * @param string $authToken
     * 
     * @return boolean it's true when authenticated otherwise false
     */
    private function isProviderAuthorize($authToken)
    {
        $this->utils->debug_log(__CLASS__, __METHOD__, 'auth_token', ['request' => $this->auth_token, 'extra_info' => $authToken]);
        if(! empty($authToken) && $this->auth_token == $authToken){
            
            return true;
        }

        return false;
    }

    /**
     * Get Request Data
     * 
     * @param boolean $is_array
     * 
     * @return array
     */
    public function request($is_array=true)
    {
        $request = file_get_contents("php://input");
        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_API ".__FUNCTION__." RAW: ",$request );

        if(! $this->isJson($request)){
            // TODO
            //return $this->getMessageFromStatusCode(8);
        }

        $request_array = json_decode($request,$is_array);

        return $request_array;
    }

    /** 
     * Detect if parameter is json data type
     * 
     * @param mixed the data to check if json or not
     * 
     * @return boolean
    */
    public function isJson($data = null)
    {
        $rlt = true;
        
        if(null === @json_decode($data)){
            $rlt = false;
        }
        
        return $rlt;
    }

    /** 
     * Save Request to Response result
     * 
     * @return int the last insert id
    */
    private function saveToResponseResult($request_method, $request_params = null,$extra=null,$fields=[], $response = [])
    {
        $flag = Response_result::FLAG_NORMAL; # default to 1

        $data = is_array($request_params) ? json_encode($request_params) : $request_params;

        if(empty($extra)){
            $extra = is_array($this->headers)?json_encode($this->headers):$this->headers;
        }

        $statusCode = 200;

        if(is_array($response)){
            if(isset($response['status'])&&$response['status']=='ERROR_INVALID_IP'){
                $statusCode = 401;
                $flag = Response_result::FLAG_ERROR;
            }
            $response = json_encode($response);
        }
        $cost = intval($this->utils->getExecutionTimeToNow()*1000);
        $lastInsertId = $this->CI->response_result->saveResponseResult(
            $this->get_platform_code(), #1
            $flag,#2
            $request_method,#3
            $data,#4
            $response,#5
            $statusCode,#6
            null,#7
            $extra,#8
            $fields, #9
			false,
			null,
			$cost
        );

        return $lastInsertId;
    }

    /** 
     * Get game provider id
     * 
     * @return int the game provider id
    */
    public function get_platform_code(){

        if(!$this->evolution_game_api){
            $this->evolution_game_api = $this->utils->loadExternalSystemLibObject(EVOLUTION_SEAMLESS_GAMING_API);
        }

        return $this->evolution_game_api->getPlatformCode();
    }

    /** 
     * Check if token is valid
     * 
     * @param string $token the token to validate
     * 
     * @return boolean
    */
    protected function isTokenValid($token)
    {
        $playerInfo = $this->evolution_game_api->getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    /**
     * Check if player is exist in game provider
     * 
     * @param int $playerId
     * @param int $game_platform_id
     * 
     * @return boolean 
     */
    protected function isPlayerExistInProvider($playerId,$game_platform_id)
    {
        $login_name = $this->db->select("login_name")
                               ->from("game_provider_auth")
                               ->where("game_provider_id",$game_platform_id)
                               ->where("player_id",$playerId)
                               ->get()
                               ->result_array();

        if(! empty($login_name)){
            return true;
        }

        return false;
    }

    /**
     * Get Player Balance
     * 
     * @param int $playerId the player id
     * @return int
     */
    protected function getPlayerSubWalletBalance($playerId)
    {

        if(empty($playerId)){
            return $this->showError("TEMPORARY_ERROR");
        }

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId,$this->get_platform_code());

        return $balance;
    }

    /** 
     * Get Needed Data for the Transaction
     * 
     * @param array $playerInfo
     * @param object $eObj
     * 
     * @return array
    */
    private function getDataForTransaction($playerInfo,$eObj)
    {

        $playerId = isset($playerInfo["playerId"]) ? $playerInfo["playerId"] : null;
        $controller = $this;
        $before_balance = 0;

        $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,&$before_balance){
            $before_balance = $controller->getPlayerSubWalletBalance($playerId);

            return true;
        });

        
        $playerName = isset($playerInfo["username"]) ? $playerInfo["username"] : null;
        
        if(property_exists($eObj,"promoTransaction")){//for promotion
            $transactionId = $eObj->promoTransaction->id;
            $amount = $eObj->promoTransaction->amount;
        }else{
            $transactionId = $eObj->transaction->id;
            $amount = $eObj->transaction->amount;
        }

        $returnData = [
            "playerId" => $playerId,
            "before_balance" => $before_balance,
            "amount" => $amount,
            "playerName" => $playerName,
            "playerId" => $playerId,
            "transactionId" => $transactionId
        ];

        return $returnData;
    }

    /** 
     * Check if Player is Allowed to bet
     * - if bet is greater than player balance
     * - if player is blocked
     * - check duplicate transaction ID
     * 
     * @param int $before_balance
     * @param int $betAmount
     * @param string $playerName
     * @param int $playerId
     * @param int $transactionId
     * @param boolean|false $is_bet
     * @param boolean|true $is_not_refund
     * 
     * @return mixed
    */
    public function isPlayerAllowedToBet($before_balance,$betAmount,$playerName,$playerId,$transactionId,$is_bet=false,$is_not_refund=true)
    {
        $isPlayerBlockedAll = $this->player_model->isBlocked($playerId); # block in player table

        if($this->evolution_game_api->isBlocked($playerName) || $isPlayerBlockedAll){
             return "ACCOUNT_LOCKED";
        }
        
        return null;


        /*if($this->evolution_seamless_thb1_wallet_transactions_model->isTransactionAlreadyExist($transactionId) &&$is_not_refund){
            return "BET_ALREADY_EXIST";
        }
        else {

            $isPlayerBlockedAll = $this->player_model->isBlocked($playerId); # block in player table

            if($this->evolution_game_api->isBlocked($playerName) || $isPlayerBlockedAll){
                 return "ACCOUNT_LOCKED";
            }

            $isAllowedToBet = $this->utils->compareResultFloat($before_balance,">=",$betAmount); # check if current balance is greater than bet

            if(! $isAllowedToBet && $is_bet){
                return "INSUFFICIENT_FUNDS";
            }
        }
        
        return null;*/
    }

    /** 
     * Do Deduct to player sub wallet balance
     * 
     * @param int $playerId
     * @param  double $betAmount
     * @param object $eObj
     * 
     * @return boolean
    */
    private function doDeduct($playerId,$betAmount,$eObj,&$before_balance = null, &$after_balance = null, $actionType = 'bet', $isEnd = false){

        $controller = $this;

        $is_success_trans = $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,$betAmount,$eObj, $before_balance, $after_balance, $actionType, $isEnd){

            if($before_balance===null){
                $before_balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $controller->evolution_game_api->getPlatformCode());
            }

            $transactionId = isset($eObj,$eObj->transaction->id) ? $eObj->transaction->id : null;
            $round_id = $gameId = isset($eObj,$eObj->game->id) ? $eObj->game->id : null;
            $uniqueid = $transactionId . "-".$eObj->eAction;
            $this->seamless_service_unique_id = $uniqueIdOfSeamlessService=$controller->evolution_game_api->getPlatformCode().'-'.$uniqueid;       
            
            if (method_exists($controller->wallet_model, 'setUniqueidOfSeamlessService')) {
                $gameDetailsTableId = isset($eObj,$eObj->game->details->table->id) ? $eObj->game->details->table->id : null;
                $controller->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameDetailsTableId);
            }

            if (method_exists($controller->wallet_model, 'setGameProviderActionType')) {
                $controller->wallet_model->setGameProviderActionType($actionType);
            }
    
            if (method_exists($controller->wallet_model, 'setGameProviderRoundId')) {
                $controller->wallet_model->setGameProviderRoundId($round_id);
            }        
            
            if (method_exists($controller->wallet_model, 'setGameProviderIsEndRound')) {
                $controller->wallet_model->setGameProviderIsEndRound($isEnd);
            }

            $isDeduct = $controller->wallet_model->decSubWallet($playerId, $controller->get_platform_code(),$betAmount, $after_balance);

            $controller->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API deduct to subwallet is: ",$isDeduct);

            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
            $save_transaction = false;
            $is_processed = false;

            if (!$isDeduct) {
                // remote wallet error
                if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                    // treat success if remote wallet return double uniqueid
                    if ($this->ssa_remote_wallet_error_double_unique_id()) {
                        $isDeduct = true;
                        $before_balance += $betAmount;
                    }

                    if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                        $save_transaction = true;
                    }

                    if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                        $save_transaction = true;
                    }

                    if ($this->ssa_remote_wallet_error_maintenance()) {
                        $save_transaction = true;
                    }

                    if ($this->ssa_remote_wallet_error_game_not_available()) {
                        $save_transaction = true;
                    }
                }
            }

            $this->transaction_data = [
                'adjustment_type' => $this->ssa_decrease,
                'player_id' => $playerId,
            ];

            if (!empty($this->remote_wallet_status)) {
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $eObj);
            }

            if ($isDeduct) {
                $is_processed = true;
            }

            if($isDeduct || $save_transaction){

                if($after_balance===null){
                    $after_balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $controller->evolution_game_api->getPlatformCode());
                }
                
                $eObj->eAfterBalance = $after_balance;
                $eObj->is_processed = $is_processed;
                
                # insert in evolution wallet transaction table
                $controller->doInsertToGameTransactions($eObj,$before_balance,$after_balance);
            }

            return $isDeduct;

        });

        return $is_success_trans;
    }

    /** 
     * Do Increment to player sub wallet balance
     * 
     * @param int $playerId
     * @param  double $betAmount
     * @param object $eObj
     * 
     * @return boolean
    */
    private function doIncrement($playerId,$betAmount,$eObj,&$before_balance = null, &$after_balance = null, $actionType = 'payout', $isEnd = false){
        
        $controller = $this;
        
        $is_success_trans = $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,$betAmount,$eObj, $before_balance, $after_balance, $actionType, $isEnd){

            if($before_balance===null){
                $before_balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $controller->evolution_game_api->getPlatformCode());
            }

            $transactionId = isset($eObj,$eObj->transaction->id) ? $eObj->transaction->id : null;
            //uniqueid for promo
            if(isset($eObj->promoTransaction->id)){
                $transactionId = $eObj->promoTransaction->id;
            }

            $round_id = $gameId = isset($eObj,$eObj->game->id) ? $eObj->game->id : null;
            $uniqueid = $transactionId . "-".$eObj->eAction;
            $this->seamless_service_unique_id = $uniqueIdOfSeamlessService=$controller->evolution_game_api->getPlatformCode().'-'.$uniqueid;   

            if (method_exists($controller->wallet_model, 'setUniqueidOfSeamlessService')) {
                $gameDetailsTableId = isset($eObj,$eObj->game->details->table->id) ? $eObj->game->details->table->id : null;
                $controller->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $gameDetailsTableId);
            }

            if (method_exists($controller->wallet_model, 'setGameProviderActionType')) {
                $controller->wallet_model->setGameProviderActionType($actionType);
            }
    
            if (method_exists($controller->wallet_model, 'setGameProviderRoundId')) {
                $controller->wallet_model->setGameProviderRoundId($round_id);
            }        
            
            if (method_exists($controller->wallet_model, 'setGameProviderIsEndRound')) {
                $controller->wallet_model->setGameProviderIsEndRound($isEnd);
            }

            $save_transaction = false;
            $is_processed = false;

            if($betAmount > 0){

                $isIncrement = $controller->wallet_model->incSubWallet($playerId, $controller->get_platform_code(),$betAmount, $after_balance);

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$isIncrement) {
                    // remote wallet error
                    if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                        // treat success if remote wallet return double uniqueid
                        if ($this->ssa_remote_wallet_error_double_unique_id()) {
                            $isIncrement = true;
                            $before_balance -= $betAmount;
                        }

                        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                            $save_transaction = true;
                        }

                        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                            $save_transaction = true;
                        }

                        if ($this->ssa_remote_wallet_error_maintenance()) {
                            $save_transaction = true;
                        }

                        if ($this->ssa_remote_wallet_error_game_not_available()) {
                            $save_transaction = true;
                        }
                    }
                }

            }else{

                $isIncrement = true;

                if($this->utils->compareResultFloat($betAmount, '=', 0)){
                    if($this->utils->isEnabledRemoteWalletClient()){
                        $this->utils->debug_log("EVOLUTION SEAMLESS SERVICE API: (doIncrement) amount 0 call remote wallet", $this->request_data);
                        $isIncrement=$controller->wallet_model->incRemoteWallet($playerId, $betAmount, $controller->get_platform_code(), $after_balance);

                        $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                        if (!$isIncrement) {
                            // remote wallet error
                            if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                                // treat success if remote wallet return double uniqueid
                                if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                    $isIncrement = true;
                                    $before_balance -= $betAmount;
                                }

                                if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                    $save_transaction = true;
                                }

                                if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                    $save_transaction = true;
                                }

                                if ($this->ssa_remote_wallet_error_maintenance()) {
                                    $save_transaction = true;
                                }

                                if ($this->ssa_remote_wallet_error_game_not_available()) {
                                    $save_transaction = true;
                                }
                            }
                        }
                    } 
                }
            }

            $controller->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API increment to subwallet is: ",$isIncrement);

            $this->transaction_data = [
                'adjustment_type' => $this->ssa_increase,
                'player_id' => $playerId,
            ];

            $this->utils->debug_log(__METHOD__, 'remote_wallet_status', $this->remote_wallet_status);

            if (!empty($this->remote_wallet_status)) {
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $eObj);
            }

            if ($isIncrement) {
                $is_processed = true;
            }

            if($isIncrement || $save_transaction){

                if($after_balance===null){
                    $after_balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $controller->evolution_game_api->getPlatformCode());
                }
                
                $eObj->eAfterBalance = $after_balance;
                $eObj->is_processed = $is_processed;
                
                # insert in evolution wallet transaction table
                $tableName = $controller->evolution_game_api->getTransactionsTable();
                $controller->doInsertToGameTransactions($eObj,$before_balance,$after_balance, $tableName);
            }

            return $isIncrement;

        });

        return $is_success_trans;
    }

    /** 
     * Get The Standard Response of Request
     * 
     * @param object $eObj
     * 
     * @return array
    */
    private function getStandardResponse($eObj, $forceBalanceNull = false, $returnFloatBal = false)
    {
        $this->CI->load->model(['game_provider_auth','common_seamless_error_logs']);
        $status = $eObj->eResponseStatusCode;

        $playerId = null;
        $balance = 0;
        if ($status != 'INVALID_TOKEN_ID') {
            $playerId = (!empty($this->playerInfo)&&!empty($this->playerInfo->player_id)?$this->playerInfo->player_id:null);
            
            if(empty($playerId)){
                $playerId = $this->CI->game_provider_auth->getPlayerIdByPlayerName($eObj->userId,$this->get_platform_code());
            }
            $balance = property_exists($eObj,"eAfterBalance") ? $eObj->eAfterBalance : null;
            if(is_null($balance)){
                $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->evolution_game_api->getPlatformCode());
                $this->utils->debug_log("EVOLUTION SEAMLESS SERVICE: (player_model->getPlayerSubWalletBalance)", $balance);	
            }
        }
        $bonus = property_exists($eObj,"eBonus") ? $eObj->eBonus : 0;
        $retransmission = property_exists($eObj,"eRetransmission") ? $eObj->eRetransmission : false;
        $uuid = property_exists($eObj,"uuid") ? $eObj->uuid : null;
        $flag = Response_result::FLAG_NORMAL;

        if($status != "OK"){
            $flag = Response_result::FLAG_ERROR;
        }
        $this->utils->debug_log(__METHOD__. " (balance)", $balance);	

        $balance = $this->evolution_game_api->truncateBalanceWithPrecision($balance);

        $bonus = $this->evolution_game_api->truncateBalanceWithPrecision($bonus);
        
        $this->responseFormattedBalance = $balance;
        $this->responseFormattedBonus = $bonus;

        $response = [
            "status" => $status,
            "balance" => $balance,
            "bonus" => $bonus,
            "retransmission" => $retransmission,
            "uuid" => $uuid
        ];

        //if($returnFloatBal){
            $response['balance'] = floatval($balance);
            $response['bonus'] = floatval($bonus);
        //}

        if($this->evolution_game_api->seamless_api_return_balance_no_quote){
            $response['balance'] = "player_balance";
            $response['bonus'] = "player_bonus";
        }

        if($status=='INVALID_PARAMETER' && $forceBalanceNull){
            $response['retransmission'] = null;
            $response['balance'] = null;
            $response['bonus'] = null;

            $this->responseFormattedBalance = null;
            $this->responseFormattedBonus = null;
        }

        $rResponse = is_array($response) ? json_encode($response) : $response;

        if($this->evolution_game_api->seamless_api_return_balance_no_quote){
            $rResponse = str_replace('"player_balance"', $this->responseFormattedBalance, $rResponse);
            $rResponse = str_replace('"player_bonus"', $this->responseFormattedBonus, $rResponse);
        }

        $rId = $this->saveToResponseResult($eObj->eCurrentMethod,(array) $this->evoRequestData->params,null,["content"=>$rResponse,'player_id'=>$playerId,'flag'=>$flag], $rResponse);

        if($status != "OK"){

            # save it in common_seamless_error_logs table
            $gamePlatformId = $this->get_platform_code();
            $response_result_id = $rId;
            $request_id = $this->utils->getRequestId();
            $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
            $now = $this->utils->getNowForMysql();
            $commonSeamlessErrorDetails = json_encode(['response'=>$rResponse,'request'=>$this->evoRequestData->params]);
            $insertData = [
                'game_platform_id' => $gamePlatformId,
                'response_result_id' => $response_result_id,
                'request_id' => $request_id,
                'elapsed_time' => $elapsed,
                'error_date' => $now,
                'extra_info' => $commonSeamlessErrorDetails,
                'error_id' => $this->getStatusCode($status)
            ];
            try{
                $this->common_seamless_error_logs->insertTransaction($insertData);
            }catch(\Exception $e){
                $this->utils->error_log(__METHOD__.' error inserting into common_seamless_error_log',$e->getMessage());
            }
        }

        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".$eObj->eCurrentMethod." method with data: ",$eObj);

        return $this->outputData($response);
    }

    function getStatusCode($status){

        switch ($status) {
            case 'INVALID_TOKEN_ID':
                return common_seamless_error_logs::ERROR_ID_INVALID_TOKEN;
                break;
            case 'INVALID_PARAMETER':
                return common_seamless_error_logs::ERROR_ID_INVALID_PARAMETER;
                break;
            case 'ERROR_INVALID_IP':
                return common_seamless_error_logs::ERROR_ID_INVALID_IP;
                break;
            
            default:
                return common_seamless_error_logs::ERROR_ID_UNKNOWN;
                break;
        }
    }

    /**
     * Is Parameter Complete in Response
     * 
     * @param Object $eObj
     * 
     * @return boolean
     */
    private function isParamComplete($eObj, $isPromo = false)
    {
        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__, 'eObj', (array)$eObj);
        
        if($isPromo){

            $isComplete = isset($eObj,
            $eObj->sid,
            $eObj->userId,
            $eObj->currency,
            #$eObj->game,
            #$eObj->game->id,
            #$eObj->game->type,
            #$eObj->game->details,
            #$eObj->game->details->table,
            #$eObj->game->details->table->id,
            $eObj->promoTransaction,
            $eObj->promoTransaction->id,
            $eObj->promoTransaction->amount,
            #$eObj->promoTransaction->voucherId,
            $eObj->uuid
            );

        }else{

            $isComplete = isset($eObj,
            $eObj->sid,
            $eObj->userId,
            $eObj->currency,
            $eObj->game,
            $eObj->game->id,
            $eObj->game->type,
            $eObj->game->details,
            $eObj->game->details->table,
            $eObj->game->details->table->id,
            $eObj->transaction,
            $eObj->transaction->id,
            $eObj->transaction->refId,
            $eObj->transaction->amount,
            $eObj->uuid
            );

        }

        return $isComplete;
    }

        /** 
     * Insert data to red_rake_game_transactions table
     * 
     * @param object $data
     * @param int|null $before_balance
     * @param int|null $after_balance
     * 
     * @return void
     * 
    */
    public function doInsertToGameTransactions($data,$before_balance=null,$after_balance=null, $tableName = null)
    {

        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." data",$data);

        $action = isset($data,$data->eAction) ? $data->eAction : null;
        $sid = isset($data,$data->sid) ? $data->sid : null;
        $userId = isset($data,$data->userId) ? $data->userId : null;
        $channelType = isset($data,$data->channel->type) ? $data->channel->type : null;
        $uuid = isset($data,$data->uuid) ? $data->uuid : null;
        $currency = isset($data,$data->currency) ? $data->currency : null;
        $gameId = isset($data,$data->game->id) ? $data->game->id : null;
        $gameType = isset($data,$data->game->type) ? $data->game->type : null;
        $gameDetailsTableId = isset($data,$data->game->details->table->id) ? $data->game->details->table->id : null;
        $gameDetailsTableVid = isset($data,$data->game->details->table->vid) ? $data->game->details->table->vid : null;
        $transactionId = isset($data,$data->transaction->id) ? $data->transaction->id : null;
        if(isset($data->promoTransaction->id)){
            $transactionId = $data->promoTransaction->id;
        }
        $transactionRefId = isset($data,$data->transaction->refId) ? $data->transaction->refId : null;
        $transactionAmount = isset($data,$data->transaction->amount) ? $data->transaction->amount : null;
        $refundedTransactionId = isset($data,$data->eRefundedTransactionId) ? $data->eRefundedTransactionId : null;
        $refundedIn = isset($data,$data->eRefundedIn) ? $data->eRefundedIn : null;
        $beforeBalance = (! empty($before_balance)) ? $before_balance : 0;
        $afterBalance = (! empty($after_balance)) ? $after_balance : 0;
        $isBonus =  isset($data,$data->eIsBonus) ? $data->eIsBonus : null;
        $response_result_id = (! empty($this->response_result_id)) ? $this->response_result_id : null;
        //$external_uniqueid = $transactionId . "-".$gameId;
        $external_uniqueid = $transactionId.'-'.$action;



        $promoTransactionId = isset($data,$data->promoTransaction->id) ? $data->promoTransaction->id : null;
        $promoTransactionVoucherId = isset($data,$data->promoTransaction->voucherId) ? $data->promoTransaction->voucherId : null;
        $promoTransactionAmount = isset($data,$data->promoTransaction->amount) ? $data->promoTransaction->amount : null;

        $allData = (array)$data;

        if($data->eAction==self::ACTION_CANCEL){
            $transactionId.='-'.self::ACTION_CANCEL;
        }

        if($data->eAction==self::ACTION_PROMO_PAYOUT_UNDEFINED){
            $transactionId.='-'.self::ACTION_PROMO_PAYOUT_UNDEFINED;
            if(empty($gameDetailsTableId)){
                $gameDetailsTableId = self::ACTION_PROMO_PAYOUT_UNDEFINED;
            }
        }

        if($data->eAction==self::ACTION_PROMO_PAYOUT && empty($gameDetailsTableId)){
            $gameDetailsTableId = $this->foundMissingTableId;
        }

        $is_processed = isset($data, $data->is_processed) ? $data->is_processed : null;

        $insertData = [
            "action" => $action,
            "sid" => $sid,
            "userId" => $userId,
            "channelType" => $channelType,
            "uuid" => $uuid,
            "currency" => $currency,
            "gameId" => $gameId,
            "gameType" => $gameType,
            "gameDetailsTableId" => $gameDetailsTableId,
            "gameDetailsTableVid" => $gameDetailsTableVid,
            "transactionId" => $transactionId,
            "transactionRefId" => $transactionRefId,
            "transactionAmount" => $transactionAmount,
            "refundedTransactionId" => $refundedTransactionId,
            "refundedIn" => $refundedIn,
            "beforeBalance" => $beforeBalance,
            "afterBalance" => $afterBalance,
            "isBonus" => $isBonus,
            "response_result_id" => $response_result_id,
            "external_uniqueid" => $external_uniqueid,
            "raw_data" => json_encode($allData),
            "promoTransactionId" => $promoTransactionId,
            "promoTransactionVoucherId" => $promoTransactionVoucherId,
            "promoTransactionAmount" => $promoTransactionAmount,
            "game_platform_id" => $this->get_platform_code(),
            'remote_wallet_status' => $this->remote_wallet_status,
            'seamless_service_unique_id' => $this->utils->mergeArrayValues(['game', $this->seamless_service_unique_id]),
            'is_processed' => $is_processed,

        ];

        $this->insertTransactionWithLog($insertData, $tableName);
    }

    /** 
     * Insert Transaction with debug Log
     * 
     * @param array $data
     * 
     * @return int
    */
    private function insertTransactionWithLog($data, $tableName = null)
    {
        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." data",$data);

        if(!empty($tableName)){
            $this->evolution_seamless_thb1_wallet_transactions_model->setTableName($tableName);
        }

        $affected_rows = $this->evolution_seamless_thb1_wallet_transactions_model->insertIgnoreRow($data);

        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API insert transaction count is: ",$affected_rows);

        if($affected_rows) {
            $this->transaction_for_fast_track = $data;
            $this->transaction_for_fast_track['id'] = $this->CI->evolution_seamless_thb1_wallet_transactions_model->getLastInsertedId();
        }
        return $affected_rows;
    }

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->evolution_game_api->getPlatformCode(), $this->transaction_for_fast_track['gameDetailsTableId']);
        $betType = null;
        switch($this->transaction_for_fast_track['action']) {
            case 'debit':
                $betType = 'Bet';
                break;
            case 'credit':
                $betType = 'Win';
                break;
            case 'cancel':
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
            "activity_id" =>  strval($this->transaction_for_fast_track['id']),
            "amount" => (float) abs($this->transaction_for_fast_track['transactionAmount']),
            "balance_after" =>  $this->transaction_for_fast_track['beforeBalance'],
            "balance_before" =>  $this->transaction_for_fast_track['afterBalance'],
            "bonus_wager_amount" =>  0.00,
            "currency" => $this->evolution_game_api->currency_code,
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : $this->transaction_for_fast_track['gameDetailsTableId'],
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  $this->transaction_for_fast_track['gameId'],
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->evolution_game_api->getPlayerIdInGameProviderAuth($this->transaction_for_fast_track['userId']),
            "vendor_id" =>  strval($this->evolution_game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->evolution_game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['transactionAmount']) : 0,
        ];

        $this->CI->utils->debug_log('EVOLUTION_SEAMLESS_THB1_API (sendToFastTrack)', $data, $this->transaction_for_fast_track);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        
        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API ".__FUNCTION__." data",$data);

        $action = !empty($data->eAction) ? $data->eAction : null;
        $transactionId = !empty($data->transaction->id) ? $data->transaction->id : null;

        if (isset($data->promoTransaction->id)) {
            $transactionId = $data->promoTransaction->id;
        }

        $external_uniqueid = $transactionId . '-' . $action;

        $allData = (array)$data;

        if ($data->eAction == self::ACTION_CANCEL) {
            $transactionId .= '-' . self::ACTION_CANCEL;
        }

        if ($data->eAction == self::ACTION_PROMO_PAYOUT_UNDEFINED) {
            $transactionId .= '-' . self::ACTION_PROMO_PAYOUT_UNDEFINED;
        }

        $save_data = $md5_data = [
            'transaction_id' => $transactionId,
            'round_id' => !empty($data->transaction->refId) ? $data->transaction->refId : null,
            'external_game_id' => !empty($data->game->id) ? $data->game->id : null,
            'player_id' => !empty($this->transaction_data['player_id']) ? $this->transaction_data['player_id'] : null,
            'game_username' => !empty($data->userId) ? $data->userId : null,
            'amount' => isset($data->transaction->amount) ? $data->transaction->amount : null,
            'balance_adjustment_type' => !empty($this->transaction_data['adjustment_type']) && $this->transaction_data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $action,
            'game_platform_id' => $this->get_platform_code(),
            'transaction_raw_data' => json_encode($allData),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $external_uniqueid,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($this->use_remote_wallet_failed_transaction_monthly_table) {
            $year_month = $this->utils->getThisYearMonth();
            $table_name = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$year_month}";
        } else {
            $table_name = $this->ssa_failed_remote_common_seamless_transactions_table;
        }

        if ($this->ssa_is_transaction_exists($table_name, ['external_uniqueid' => $save_data['external_uniqueid']])) {
            $query_type = $this->ssa_update;

            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $save_data['external_uniqueid'],
                ];
            }
        }

        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);
    }
}